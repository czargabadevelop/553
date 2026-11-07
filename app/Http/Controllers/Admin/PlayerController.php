<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Audit\AuditLogger;
use App\Enums\AccountStatus;
use App\Http\Controllers\Controller;
use App\Models\GamePlayer;
use App\Models\User;
use App\Models\WalletLedgerEntry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PlayerController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('search', ''));

        $players = User::query()
            ->with(['wallet', 'verification', 'roles'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('username', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(20)
            ->withQueryString()
            ->through(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
                'account_status' => $user->account_status?->value,
                'verification_status' => $user->verification?->status?->value ?? 'unverified',
                'available' => $user->wallet ? $user->wallet->available()->toDecimalString() : '0.00',
                'reserved' => $user->wallet ? $user->wallet->reserved()->toDecimalString() : '0.00',
                'roles' => $user->roles->pluck('slug'),
                'created_at' => $user->created_at?->toIso8601String(),
            ]);

        return Inertia::render('Admin/Players', [
            'players' => $players,
            'filters' => ['search' => $search],
        ]);
    }

    public function show(User $user): Response
    {
        $user->load('profile', 'wallet', 'verification', 'roles');

        return Inertia::render('Admin/PlayerDetail', [
            'player' => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
                'account_status' => $user->account_status?->value,
                'suspended_reason' => $user->suspended_reason,
                'verification_status' => $user->verification?->status?->value ?? 'unverified',
                'roles' => $user->roles->pluck('slug'),
                'profile' => $user->profile,
                'wallet' => $user->wallet ? [
                    'available' => $user->wallet->available()->toDecimalString(),
                    'reserved' => $user->wallet->reserved()->toDecimalString(),
                ] : null,
            ],
            'games' => GamePlayer::query()
                ->with('game')
                ->where('user_id', $user->id)
                ->latest()
                ->take(10)
                ->get()
                ->map(fn (GamePlayer $player) => [
                    'id' => $player->game->id,
                    'code' => $player->game->code,
                    'status' => $player->game->status->value,
                    'game_points' => $player->game_points,
                    'is_fouled' => $player->is_fouled,
                ]),
            'transactions' => WalletLedgerEntry::query()
                ->where('wallet_id', $user->wallet?->id)
                ->with('transaction')
                ->latest()
                ->take(20)
                ->get()
                ->map(fn (WalletLedgerEntry $entry) => [
                    'reference' => $entry->transaction?->reference,
                    'type' => $entry->transaction?->type?->value,
                    'account' => $entry->account->value,
                    'amount' => number_format($entry->signedMinor() / 100, 2),
                    'at' => $entry->created_at?->toIso8601String(),
                ]),
        ]);
    }

    public function suspend(Request $request, User $user, AuditLogger $audit): RedirectResponse
    {
        $validated = $request->validate(['reason' => ['required', 'string', 'max:255']]);
        $previous = $user->account_status;

        $user->forceFill([
            'account_status' => AccountStatus::Suspended,
            'suspended_at' => now(),
            'suspended_reason' => $validated['reason'],
        ])->save();

        $audit->log('player.suspended', $user, ['account_status' => $previous?->value], [
            'account_status' => AccountStatus::Suspended->value,
            'reason' => $validated['reason'],
        ], $request->user());

        return back()->with('success', 'Account suspended.');
    }

    public function reactivate(Request $request, User $user, AuditLogger $audit): RedirectResponse
    {
        $previous = $user->account_status;

        $user->forceFill([
            'account_status' => AccountStatus::Active,
            'suspended_at' => null,
            'suspended_reason' => null,
        ])->save();

        $audit->log('player.reactivated', $user, ['account_status' => $previous?->value], [
            'account_status' => AccountStatus::Active->value,
        ], $request->user());

        return back()->with('success', 'Account reactivated.');
    }
}
