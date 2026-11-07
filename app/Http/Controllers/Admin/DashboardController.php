<?php

namespace App\Http\Controllers\Admin;

use App\Enums\DepositStatus;
use App\Enums\GameStatus;
use App\Enums\LedgerAccount;
use App\Enums\VerificationStatus;
use App\Enums\WithdrawalStatus;
use App\Http\Controllers\Controller;
use App\Models\AccountVerification;
use App\Models\AuditLog;
use App\Models\Deposit;
use App\Models\Game;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletLedgerEntry;
use App\Models\Withdrawal;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        $walletTotals = Wallet::query()
            ->selectRaw('COALESCE(SUM(available_minor), 0) as available, COALESCE(SUM(reserved_minor), 0) as reserved')
            ->first();

        return Inertia::render('Admin/Dashboard', [
            'metrics' => [
                'players' => User::query()->count(),
                'verified_players' => AccountVerification::query()->where('status', VerificationStatus::Approved)->count(),
                'active_players' => User::query()->where('last_login_at', '>=', now()->subDays(7))->count(),
                'games_total' => Game::query()->count(),
                'games_active' => Game::query()->whereIn('status', [GameStatus::Dealing, GameStatus::Arranging, GameStatus::Evaluating])->count(),
                'games_completed' => Game::query()->where('status', GameStatus::Completed)->count(),
                'deposits_total' => number_format(((int) Deposit::query()->where('status', DepositStatus::Approved)->sum('amount_minor')) / 100, 2),
                'withdrawals_total' => number_format(((int) Withdrawal::query()->where('status', WithdrawalStatus::Completed)->sum('amount_minor')) / 100, 2),
                'platform_revenue' => number_format(((int) WalletLedgerEntry::query()->where('account', LedgerAccount::PlatformRevenue)->sum('credit_minor')) / 100, 2),
                'wallet_available' => number_format(((int) ($walletTotals->available ?? 0)) / 100, 2),
                'wallet_reserved' => number_format(((int) ($walletTotals->reserved ?? 0)) / 100, 2),
                'pending_deposits' => Deposit::query()->whereIn('status', [DepositStatus::Pending, DepositStatus::UnderReview])->count(),
                'pending_withdrawals' => Withdrawal::query()->whereIn('status', [WithdrawalStatus::Pending, WithdrawalStatus::UnderReview, WithdrawalStatus::Approved, WithdrawalStatus::Processing])->count(),
            ],
            'recentGames' => Game::query()->with('room')->latest()->take(8)->get()->map(fn (Game $game) => [
                'id' => $game->id,
                'code' => $game->code,
                'room' => $game->room?->name,
                'status' => $game->status->value,
                'player_count' => $game->player_count,
                'completed_at' => $game->completed_at?->toIso8601String(),
            ]),
            'recentAudit' => AuditLog::query()->with('actor')->latest()->take(8)->get()->map(fn (AuditLog $log) => [
                'action' => $log->action,
                'actor' => $log->actor?->name,
                'created_at' => $log->created_at?->toIso8601String(),
            ]),
        ]);
    }
}
