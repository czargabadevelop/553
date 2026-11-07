<?php

namespace App\Domain\Wallet;

use App\Domain\Wallet\Exceptions\InsufficientFundsException;
use App\Domain\Wallet\Exceptions\InvalidReservationException;
use App\Enums\LedgerAccount;
use App\Enums\LedgerTransactionType;
use App\Enums\ReservationStatus;
use App\Enums\ReservationType;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletReservation;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Reserves and releases wallet funds. Every balance change goes through the
 * ledger inside a database transaction with row locking (agents.md §7).
 */
class WalletService
{
    public function __construct(private readonly LedgerService $ledger) {}

    public function walletFor(User $user): Wallet
    {
        return Wallet::query()->firstOrCreate(
            ['user_id' => $user->id],
            ['currency' => 'PHP', 'is_test_credit' => true],
        );
    }

    public function reserve(
        User $user,
        int $amountMinor,
        ReservationType $type,
        ?Model $reservable = null,
        ?string $idempotencyKey = null,
        ?CarbonInterface $expiresAt = null,
        array $metadata = [],
    ): WalletReservation {
        if ($amountMinor <= 0) {
            throw new InvalidArgumentException('A reservation amount must be positive.');
        }

        return DB::transaction(function () use ($user, $amountMinor, $type, $reservable, $idempotencyKey, $expiresAt, $metadata) {
            if ($idempotencyKey !== null) {
                $existing = WalletReservation::query()
                    ->where('idempotency_key', $idempotencyKey)
                    ->lockForUpdate()
                    ->first();

                if ($existing !== null) {
                    return $existing;
                }
            }

            $wallet = $this->walletFor($user);
            $wallet = Wallet::query()->whereKey($wallet->id)->lockForUpdate()->firstOrFail();

            if ($wallet->available_minor < $amountMinor) {
                throw InsufficientFundsException::forAmount($amountMinor, $wallet->available_minor);
            }

            $reservation = WalletReservation::create([
                'reference' => 'RSV-'.Str::upper(Str::random(16)),
                'user_id' => $user->id,
                'wallet_id' => $wallet->id,
                'type' => $type,
                'status' => ReservationStatus::Held,
                'amount_minor' => $amountMinor,
                'reservable_type' => $reservable?->getMorphClass(),
                'reservable_id' => $reservable?->getKey(),
                'idempotency_key' => $idempotencyKey,
                'expires_at' => $expiresAt,
                'metadata' => $metadata ?: null,
            ]);

            $this->ledger->post(
                type: LedgerTransactionType::Reservation,
                description: 'Reserve funds for '.$type->value,
                entries: [
                    ['account' => LedgerAccount::UserAvailable, 'wallet_id' => $wallet->id, 'debit' => $amountMinor],
                    ['account' => LedgerAccount::UserReserved, 'wallet_id' => $wallet->id, 'credit' => $amountMinor],
                ],
                idempotencyKey: 'reservation:'.$reservation->reference,
                actor: $user,
                metadata: ['reservation_id' => $reservation->id],
            );

            return $reservation->refresh();
        });
    }

    public function release(WalletReservation $reservation, string $reason = 'Reservation released'): WalletReservation
    {
        return DB::transaction(function () use ($reservation, $reason) {
            $reservation = WalletReservation::query()->whereKey($reservation->id)->lockForUpdate()->firstOrFail();

            if ($reservation->status !== ReservationStatus::Held) {
                throw new InvalidReservationException('Only held reservations can be released.');
            }

            $this->ledger->post(
                type: LedgerTransactionType::ReservationRelease,
                description: $reason,
                entries: [
                    ['account' => LedgerAccount::UserReserved, 'wallet_id' => $reservation->wallet_id, 'debit' => $reservation->amount_minor],
                    ['account' => LedgerAccount::UserAvailable, 'wallet_id' => $reservation->wallet_id, 'credit' => $reservation->amount_minor],
                ],
                idempotencyKey: 'reservation-release:'.$reservation->reference,
                actor: $reservation->user,
                metadata: ['reservation_id' => $reservation->id],
            );

            $reservation->forceFill([
                'status' => ReservationStatus::Released,
                'released_at' => now(),
            ])->save();

            return $reservation;
        });
    }
}
