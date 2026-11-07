<?php

namespace App\Http\Controllers\Player;

use App\Domain\Wallet\WalletService;
use App\Enums\GameStatus;
use App\Enums\RoomSeatStatus;
use App\Http\Controllers\Controller;
use App\Models\GamePlayer;
use App\Models\Room;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request, WalletService $wallets): Response
    {
        $user = $request->user()->load('verification');
        $wallet = $wallets->walletFor($user);

        $completedGames = GamePlayer::query()
            ->where('user_id', $user->id)
            ->whereHas('game', fn ($query) => $query->where('status', GameStatus::Completed));

        return Inertia::render('Player/Dashboard', [
            'wallet' => [
                'available' => $wallet->available()->toDecimalString(),
                'reserved' => $wallet->reserved()->toDecimalString(),
                'currency' => $wallet->currency,
                'is_test_credit' => $wallet->is_test_credit,
            ],
            'stats' => [
                'played' => (clone $completedGames)->count(),
                'wins' => (clone $completedGames)->where('game_points', '>', 0)->count(),
                'losses' => (clone $completedGames)->where('game_points', '<', 0)->count(),
                'game_points' => (int) (clone $completedGames)->sum('game_points'),
                'fouls' => (clone $completedGames)->where('is_fouled', true)->count(),
            ],
            'verification' => $user->verification?->status?->value ?? 'unverified',
            'recentGames' => GamePlayer::query()
                ->with('game.room')
                ->where('user_id', $user->id)
                ->whereHas('game', fn ($query) => $query->where('status', GameStatus::Completed))
                ->latest()
                ->take(5)
                ->get()
                ->map(fn (GamePlayer $player) => [
                    'id' => $player->game->id,
                    'code' => $player->game->code,
                    'room' => $player->game->room?->name,
                    'points' => $player->game_points,
                    'fouled' => $player->is_fouled,
                    'completed_at' => $player->game->completed_at?->toIso8601String(),
                ]),
            'recentTransactions' => $wallet->reservations()->latest()->take(5)->get()->map(fn ($reservation) => [
                'reference' => $reservation->reference,
                'type' => $reservation->type->value,
                'amount' => number_format($reservation->amount_minor / 100, 2),
                'status' => $reservation->status->value,
            ]),
            'rooms' => Room::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get()
                ->map(fn (Room $room) => [
                    'id' => $room->id,
                    'name' => $room->name,
                    'entry' => $room->entryFee()->toDecimalString(),
                    'seated' => $room->seats()->whereIn('status', [RoomSeatStatus::Seated->value, RoomSeatStatus::Ready->value])->count(),
                    'max_players' => $room->max_players,
                ]),
        ]);
    }
}
