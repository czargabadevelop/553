<?php

namespace App\Http\Controllers\Player;

use App\Domain\Game\Card;
use App\Domain\Game\GameService;
use App\Enums\GameStatus;
use App\Enums\HandPosition;
use App\Enums\RoomSeatStatus;
use App\Http\Controllers\Controller;
use App\Models\Game;
use App\Models\GameCard;
use App\Models\GameHandResult;
use App\Models\GamePlayer;
use App\Models\GamePlayerResult;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class GameController extends Controller
{
    public function show(Request $request, Game $game): Response
    {
        $user = $request->user();
        $game->load('room', 'players.user');

        /** @var GamePlayer|null $me */
        $me = $game->players->firstWhere('user_id', $user->id);

        abort_if($me === null, 403, 'You are not seated at this table.');

        $myCards = GameCard::query()
            ->where('game_player_id', $me->id)
            ->orderBy('deal_order')
            ->get()
            ->map(fn (GameCard $card) => [
                'code' => Card::RANK_LABELS[$card->card_rank].$card->card_suit,
                'rank' => $card->card_rank,
                'rank_label' => Card::RANK_LABELS[$card->card_rank],
                'suit' => $card->card_suit,
                'position' => $card->position,
            ]);

        $revealed = $game->status === GameStatus::Completed;

        $room = $game->room;
        $seatByUser = $room !== null
            ? $room->seats()
                ->whereIn('status', [RoomSeatStatus::Seated->value, RoomSeatStatus::Ready->value])
                ->get()
                ->keyBy('user_id')
            : collect();

        $nextGame = $revealed && $room !== null
            ? $room->games()
                ->whereIn('status', [
                    GameStatus::Dealing->value,
                    GameStatus::Arranging->value,
                    GameStatus::Evaluating->value,
                ])
                ->whereKeyNot($game->id)
                ->latest('id')
                ->value('id')
            : null;

        return Inertia::render('Player/Table', [
            'game' => [
                'id' => $game->id,
                'code' => $game->code,
                'status' => $game->status->value,
                'room' => $game->room?->name,
                'deadline' => $game->arrangement_deadline_at?->toIso8601String(),
                'settled' => $game->isSettled(),
            ],
            'me' => [
                'game_player_id' => $me->id,
                'seat' => $me->seat_number,
                'status' => $me->status->value,
                'submitted' => $me->submitted_at !== null,
                'fouled' => $me->is_fouled,
                'foul_reason' => $me->foul_reason,
                'game_points' => $me->game_points,
            ],
            'seats' => $game->players->map(fn (GamePlayer $player) => [
                'game_player_id' => $player->id,
                'seat' => $player->seat_number,
                'name' => $player->user?->displayName() ?? 'Player',
                'submitted' => $player->submitted_at !== null,
                'status' => $player->status->value,
                'is_me' => $player->user_id === $user->id,
            ]),
            // Only the requesting player's cards are ever serialized.
            'cards' => $myCards,
            'results' => $revealed ? $this->results($game) : null,
            'room_id' => $game->room_id,
            'next_game' => $nextGame,
            // The countdown is computed against the server clock so a wrong
            // device clock cannot mislead the player about the deadline.
            'server_now' => now()->toIso8601String(),
            // Rematch state: the same seats stay at the table after a game, and
            // each player readies up again for the next round.
            'rematch' => $revealed ? [
                'seats' => $game->players->map(function (GamePlayer $player) use ($seatByUser, $user) {
                    $seat = $seatByUser->get($player->user_id);

                    return [
                        'seat' => $player->seat_number,
                        'name' => $player->user?->displayName() ?? 'Player',
                        'seated' => $seat !== null,
                        'ready' => $seat?->status === RoomSeatStatus::Ready,
                        'is_me' => $player->user_id === $user->id,
                    ];
                })->values(),
                'me_ready' => $seatByUser->get($user->id)?->status === RoomSeatStatus::Ready,
                'can_ready' => $seatByUser->get($user->id)?->status === RoomSeatStatus::Seated,
            ] : null,
        ]);
    }

    /**
     * Resolve a round whose timer has expired. Only a seated player may ask,
     * and the domain service verifies the deadline before doing anything; this
     * just makes resolution instant instead of waiting for the next sweep.
     */
    public function resolveTimeout(Request $request, Game $game, GameService $games): RedirectResponse
    {
        abort_unless(
            $game->players()->where('user_id', $request->user()->id)->exists(),
            403,
            'You are not seated at this table.',
        );

        try {
            $resolved = $games->resolveDeadline($game);
        } catch (RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return $resolved === null
            ? back()
            : back()->with('success', 'The arrangement timer expired — the round was resolved.');
    }

    public function submit(Request $request, Game $game, GameService $games): RedirectResponse
    {
        $validated = $request->validate([
            'back' => ['required', 'array', 'size:5'],
            'back.*' => ['string', 'regex:'.Card::CODE_PATTERN],
            'middle' => ['required', 'array', 'size:5'],
            'middle.*' => ['string', 'regex:'.Card::CODE_PATTERN],
            'front' => ['required', 'array', 'size:3'],
            'front.*' => ['string', 'regex:'.Card::CODE_PATTERN],
        ]);

        try {
            $games->submit($game, $request->user(), [
                'back' => $validated['back'],
                'middle' => $validated['middle'],
                'front' => $validated['front'],
            ]);
        } catch (RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Hands submitted.');
    }

    /**
     * Results are revealed only after the game is completed.
     *
     * @return array<string, mixed>
     */
    private function results(Game $game): array
    {
        $players = $game->players()->with(['submission', 'result'])->get();

        $comparisons = GameHandResult::query()
            ->where('game_id', $game->id)
            ->get()
            ->map(fn (GameHandResult $result) => [
                'position' => $result->position->value,
                'player_one_id' => $result->player_one_id,
                'player_two_id' => $result->player_two_id,
                'winner_player_id' => $result->winner_player_id,
                'result' => $result->result,
            ]);

        return [
            'players' => $players->map(function (GamePlayer $player) {
                /** @var GamePlayerResult|null $line */
                $line = $player->result;

                return [
                    'game_player_id' => $player->id,
                    'name' => $player->user?->displayName() ?? 'Player',
                    'seat' => $player->seat_number,
                    'game_points' => $player->game_points,
                    'is_fouled' => $player->is_fouled,
                    'foul_reason' => $player->foul_reason,
                    'hands' => $player->submission?->hands,
                    'php_equivalent' => $line ? number_format($line->php_equivalent_minor / 100, 2) : '0.00',
                    'fee' => $line ? number_format($line->fee_minor / 100, 2) : '0.00',
                    'net' => $line ? number_format($line->net_minor / 100, 2) : '0.00',
                ];
            }),
            'comparisons' => $comparisons,
            'positions' => collect(HandPosition::cases())->map(fn (HandPosition $position) => $position->value),
        ];
    }
}
