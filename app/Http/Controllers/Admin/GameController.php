<?php

namespace App\Http\Controllers\Admin;

use App\Enums\GameStatus;
use App\Http\Controllers\Controller;
use App\Models\Game;
use App\Models\GameAuditLog;
use App\Models\GameHandResult;
use App\Models\Room;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GameController extends Controller
{
    public function index(Request $request): Response
    {
        $status = (string) $request->query('status', '');
        $roomId = $request->integer('room');

        $games = Game::query()
            ->with('room')
            ->withCount('players')
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->when($roomId > 0, fn ($query) => $query->where('room_id', $roomId))
            ->latest()
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Game $game) => [
                'id' => $game->id,
                'code' => $game->code,
                'room' => $game->room?->name,
                'status' => $game->status->value,
                'players' => $game->players_count,
                'started_at' => $game->started_at?->toIso8601String(),
                'completed_at' => $game->completed_at?->toIso8601String(),
                'settled' => $game->isSettled(),
            ]);

        return Inertia::render('Admin/Games', [
            'games' => $games,
            'rooms' => Room::query()->orderBy('sort_order')->get(['id', 'name']),
            'filters' => ['status' => $status, 'room' => $roomId ?: null],
            'statuses' => collect(GameStatus::cases())->map(fn (GameStatus $case) => $case->value),
        ]);
    }

    /**
     * Completed games are read-only: administrators can inspect submissions,
     * fouls and results, but never silently change them.
     */
    public function show(Game $game): Response
    {
        $game->load('room', 'players.user', 'playerResults');

        return Inertia::render('Admin/GameDetail', [
            'game' => [
                'id' => $game->id,
                'code' => $game->code,
                'room' => $game->room?->name,
                'status' => $game->status->value,
                'deck_fingerprint' => $game->deck_fingerprint,
                'started_at' => $game->started_at?->toIso8601String(),
                'completed_at' => $game->completed_at?->toIso8601String(),
                'settled' => $game->isSettled(),
            ],
            'players' => $game->players->map(fn ($player) => [
                'id' => $player->id,
                'name' => $player->user?->displayName(),
                'seat' => $player->seat_number,
                'status' => $player->status->value,
                'game_points' => $player->game_points,
                'is_fouled' => $player->is_fouled,
                'foul_reason' => $player->foul_reason,
                'submitted_at' => $player->submitted_at?->toIso8601String(),
                'hands' => $player->submission?->hands,
            ]),
            'comparisons' => GameHandResult::query()
                ->where('game_id', $game->id)
                ->get()
                ->map(fn (GameHandResult $result) => [
                    'position' => $result->position->value,
                    'player_one_id' => $result->player_one_id,
                    'player_two_id' => $result->player_two_id,
                    'winner_player_id' => $result->winner_player_id,
                    'result' => $result->result,
                ]),
            'audit' => GameAuditLog::query()
                ->where('game_id', $game->id)
                ->latest()
                ->get()
                ->map(fn (GameAuditLog $log) => [
                    'action' => $log->action,
                    'actor' => $log->actor,
                    'metadata' => $log->metadata,
                    'created_at' => $log->created_at?->toIso8601String(),
                ]),
        ]);
    }
}
