<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Game\Card;
use App\Domain\Game\GameService;
use App\Enums\GameStatus;
use App\Http\Controllers\Controller;
use App\Models\Game;
use App\Models\GameCard;
use App\Models\GamePlayer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class GameController extends Controller
{
    public function show(Request $request, Game $game): JsonResponse
    {
        $player = $game->players()->where('user_id', $request->user()->id)->first();

        if ($player === null) {
            return response()->json(['message' => 'You are not seated at this table.'], 403);
        }

        $cards = GameCard::query()
            ->where('game_player_id', $player->id)
            ->orderBy('deal_order')
            ->get()
            ->map(fn (GameCard $card) => [
                'code' => Card::RANK_LABELS[$card->card_rank].$card->card_suit,
                'rank' => $card->card_rank,
                'suit' => $card->card_suit,
                'position' => $card->position,
            ]);

        return response()->json([
            'game' => [
                'id' => $game->id,
                'code' => $game->code,
                'status' => $game->status->value,
                'deadline' => $game->arrangement_deadline_at?->toIso8601String(),
                'settled' => $game->isSettled(),
            ],
            'seat' => [
                'game_player_id' => $player->id,
                'seat_number' => $player->seat_number,
                'status' => $player->status->value,
                'submitted' => $player->submitted_at !== null,
            ],
            'cards' => $cards,
            'results' => $game->status === GameStatus::Completed ? $this->results($game) : null,
        ]);
    }

    public function submit(Request $request, Game $game, GameService $games): JsonResponse
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
            $games->submit($game, $request->user(), $validated);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json(['message' => 'Hands submitted.']);
    }

    /**
     * @return array<string, mixed>
     */
    private function results(Game $game): array
    {
        return [
            'players' => $game->players()->with(['submission', 'result'])->get()
                ->map(fn (GamePlayer $player) => [
                    'game_player_id' => $player->id,
                    'seat_number' => $player->seat_number,
                    'game_points' => $player->game_points,
                    'is_fouled' => $player->is_fouled,
                    'hands' => $player->submission?->hands,
                    'php_equivalent_minor' => $player->result?->php_equivalent_minor,
                    'fee_minor' => $player->result?->fee_minor,
                    'net_minor' => $player->result?->net_minor,
                ]),
            'comparisons' => $game->handResults()->get()->map(fn ($comparison) => [
                'position' => $comparison->position->value,
                'player_one_id' => $comparison->player_one_id,
                'player_two_id' => $comparison->player_two_id,
                'winner_player_id' => $comparison->winner_player_id,
                'result' => $comparison->result,
            ]),
        ];
    }
}
