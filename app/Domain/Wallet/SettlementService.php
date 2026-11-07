<?php

namespace App\Domain\Wallet;

use App\Domain\Audit\AuditLogger;
use App\Domain\Settings\FeatureFlagService;
use App\Enums\LedgerAccount;
use App\Enums\LedgerTransactionType;
use App\Enums\ReservationStatus;
use App\Models\Game;
use App\Models\WalletReservation;
use Illuminate\Support\Facades\DB;

/**
 * Settles a completed game: releases entry reservations, then posts one
 * balanced ledger transaction for the whole table. Idempotent per game and
 * never moves real money while the cash-equivalent flag is disabled.
 */
class SettlementService
{
    public function __construct(
        private readonly LedgerService $ledger,
        private readonly WalletService $wallets,
        private readonly FeatureFlagService $flags,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * @param  array<int, array{game_points: int, php_minor: int, fee_minor: int, net_minor: int}>  $lines  keyed by game_player_id
     */
    public function settle(Game $game, array $lines): bool
    {
        return DB::transaction(function () use ($game, $lines) {
            $game = Game::query()->whereKey($game->id)->lockForUpdate()->firstOrFail();

            if ($game->isSettled()) {
                return false;
            }

            $players = $game->players()->with('user.wallet')->get();

            $realMoneyInvolved = $players->contains(
                fn ($player) => $player->user?->wallet !== null && ! $player->user->wallet->is_test_credit
            );

            // Real cash-equivalent settlement requires an explicit, approved flag.
            if ($realMoneyInvolved && $this->flags->disabled(FeatureFlagService::CASH_EQUIVALENT_SETTLEMENT)) {
                $this->audit->game($game, 'settlement.blocked', ['reason' => 'cash_equivalent_settlement disabled']);

                return false;
            }

            $entries = [];

            foreach ($players as $player) {
                $wallet = $player->user?->wallet ?? $this->wallets->walletFor($player->user);
                $line = $lines[$player->id] ?? null;

                if ($line === null) {
                    continue;
                }

                if ($player->reservation_id !== null) {
                    $reservation = WalletReservation::query()
                        ->whereKey($player->reservation_id)
                        ->lockForUpdate()
                        ->first();

                    if ($reservation !== null && $reservation->status === ReservationStatus::Held) {
                        $this->wallets->release($reservation, 'Game settled');
                    }
                }

                if ($line['net_minor'] < 0) {
                    $entries[] = [
                        'account' => LedgerAccount::UserAvailable,
                        'wallet_id' => $wallet->id,
                        'debit' => abs($line['net_minor']),
                    ];
                } elseif ($line['net_minor'] > 0) {
                    $entries[] = [
                        'account' => LedgerAccount::UserAvailable,
                        'wallet_id' => $wallet->id,
                        'credit' => $line['net_minor'],
                    ];
                }

                if ($line['fee_minor'] > 0) {
                    $entries[] = [
                        'account' => LedgerAccount::PlatformRevenue,
                        'wallet_id' => null,
                        'credit' => $line['fee_minor'],
                    ];
                }

                $wallet->forceFill([
                    'lifetime_winnings_minor' => $wallet->lifetime_winnings_minor + max(0, $line['php_minor']),
                    'lifetime_losses_minor' => $wallet->lifetime_losses_minor + max(0, -$line['php_minor']),
                    'lifetime_fees_minor' => $wallet->lifetime_fees_minor + $line['fee_minor'],
                ])->save();
            }

            if ($entries !== []) {
                $this->ledger->post(
                    type: LedgerTransactionType::GameSettlement,
                    description: 'Game '.$game->code.' settlement',
                    entries: $entries,
                    idempotencyKey: 'game-settlement:'.$game->id,
                    metadata: ['game_id' => $game->id, 'lines' => $lines],
                );
            }

            $game->forceFill(['settled_at' => now()])->save();

            $this->audit->game($game, 'game.settled', ['lines' => $lines]);

            return true;
        });
    }
}
