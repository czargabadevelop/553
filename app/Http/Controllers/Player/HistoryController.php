<?php

namespace App\Http\Controllers\Player;

use App\Enums\GameStatus;
use App\Http\Controllers\Controller;
use App\Models\GamePlayer;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HistoryController extends Controller
{
    public function index(Request $request): Response
    {
        $games = GamePlayer::query()
            ->with(['game.room', 'result'])
            ->where('user_id', $request->user()->id)
            ->whereHas('game', fn ($query) => $query->where('status', GameStatus::Completed))
            ->latest()
            ->paginate(15)
            ->through(fn (GamePlayer $player) => [
                'id' => $player->game->id,
                'code' => $player->game->code,
                'room' => $player->game->room?->name,
                'game_points' => $player->game_points,
                'fouled' => $player->is_fouled,
                'php_equivalent' => number_format(($player->result?->php_equivalent_minor ?? 0) / 100, 2),
                'fee' => number_format(($player->result?->fee_minor ?? 0) / 100, 2),
                'net' => number_format(($player->result?->net_minor ?? 0) / 100, 2),
                'completed_at' => $player->game->completed_at?->toIso8601String(),
            ]);

        return Inertia::render('Player/History', [
            'games' => $games,
        ]);
    }
}
