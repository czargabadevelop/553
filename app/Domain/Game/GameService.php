<?php

namespace App\Domain\Game;

use App\Domain\Audit\AuditLogger;
use App\Domain\Rooms\LobbySummary;
use App\Domain\Rooms\RoomService;
use App\Domain\Settings\FeatureFlagService;
use App\Domain\Settings\SettingsService;
use App\Domain\Wallet\SettlementService;
use App\Enums\GamePlayerStatus;
use App\Enums\GameStatus;
use App\Enums\HandPosition;
use App\Enums\RoomSeatStatus;
use App\Enums\RoomStatus;
use App\Events\AllHandsSubmitted;
use App\Events\GameCompleted;
use App\Events\GameEvaluated;
use App\Events\GameStarted;
use App\Events\LobbyUpdated;
use App\Events\PlayerSubmittedHands;
use App\Events\SettlementCompleted;
use App\Models\Game;
use App\Models\GameCard;
use App\Models\GameHand;
use App\Models\GameHandResult;
use App\Models\GameHandSubmission;
use App\Models\GamePlayer;
use App\Models\GamePlayerResult;
use App\Models\Room;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * The server is the only authority for deck generation, dealing, validation,
 * scoring and settlement (agents.md §11).
 */
class GameService
{
    /**
     * Stored on the player and the submission so the results screen explains
     * exactly why the round was lost.
     */
    public const TIMEOUT_FOUL_REASON = 'Your hands were not submitted before the deadline.';

    public function __construct(
        private readonly RoundEvaluator $roundEvaluator,
        private readonly FoulDetector $foulDetector,
        private readonly SettingsService $settings,
        private readonly FeatureFlagService $flags,
        private readonly SettlementService $settlements,
        private readonly RoomService $rooms,
        private readonly AuditLogger $audit,
    ) {}

    public function start(Room $room): Game
    {
        return DB::transaction(function () use ($room) {
            $room = Room::query()->whereKey($room->id)->lockForUpdate()->firstOrFail();

            if ($room->status === RoomStatus::InProgress) {
                throw new RuntimeException('A game is already in progress in this room.');
            }

            if (! $this->rooms->canStart($room)) {
                throw new RuntimeException('The table needs four ready players with reserved funds.');
            }

            $seats = $room->seats()
                ->whereIn('status', [RoomSeatStatus::Seated->value, RoomSeatStatus::Ready->value])
                ->orderBy('seat_number')
                ->get();

            if ($seats->count() !== $room->max_players) {
                throw new RuntimeException('Exactly four players are required.');
            }

            $deck = Deck::shuffled();
            $hands = $deck->deal(4, 13);

            $game = Game::create([
                'code' => 'G-'.Str::upper(Str::random(8)),
                'room_id' => $room->id,
                'status' => GameStatus::Arranging,
                'player_count' => 4,
                'deck_fingerprint' => $deck->fingerprint(),
                'settings_snapshot' => [
                    'point_value_minor' => $this->settings->integer('game.point_value_minor', 0),
                    'arrangement_timeout_seconds' => $this->settings->integer('game.arrangement_timeout_seconds', 120),
                ],
                'started_at' => now(),
                'arrangement_deadline_at' => now()->addSeconds($this->settings->integer('game.arrangement_timeout_seconds', 120)),
            ]);

            foreach ($seats->values() as $index => $seat) {
                $player = GamePlayer::create([
                    'game_id' => $game->id,
                    'user_id' => $seat->user_id,
                    'seat_number' => $seat->seat_number,
                    'status' => GamePlayerStatus::Waiting,
                    'reservation_id' => $seat->reservation_id,
                ]);

                foreach ($hands[$index] as $order => $card) {
                    GameCard::create([
                        'game_id' => $game->id,
                        'game_player_id' => $player->id,
                        'card_rank' => $card->rank,
                        'card_suit' => $card->suit,
                        'deal_order' => $order,
                    ]);
                }
            }

            $room->forceFill(['status' => RoomStatus::InProgress])->save();

            $this->audit->game($game, 'game.started', ['players' => $seats->pluck('user_id')->all()]);

            event(new GameStarted($game->id, $room->id));
            event(new LobbyUpdated(LobbySummary::build()));

            return $game->load('players');
        });
    }

    /**
     * Start the match as soon as the table qualifies. Safe to call after every
     * ready action: it returns null while the table is still filling.
     */
    public function startIfReady(Room $room): ?Game
    {
        $room = $room->fresh() ?? $room;

        if ($room->status === RoomStatus::InProgress) {
            return $this->activeGame($room);
        }

        if (! $this->rooms->canStart($room)) {
            return null;
        }

        try {
            return $this->start($room);
        } catch (RuntimeException $exception) {
            // Two ready requests can race for the last seat; the loser joins the
            // game the winner has just created instead of surfacing a 500.
            $active = $this->activeGame($room);

            if ($active !== null) {
                return $active;
            }

            throw $exception;
        }
    }

    private function activeGame(Room $room): ?Game
    {
        return $room->games()
            ->whereIn('status', [
                GameStatus::Dealing->value,
                GameStatus::Arranging->value,
                GameStatus::Evaluating->value,
            ])
            ->latest('id')
            ->first();
    }

    /**
     * Accept one player's 13-card arrangement and evaluate once all four are in.
     *
     * @param  array{back: list<string>, middle: list<string>, front: list<string>}  $handCodes
     */
    public function submit(Game $game, User $user, array $handCodes): GamePlayer
    {
        return DB::transaction(function () use ($game, $user, $handCodes) {
            $game = Game::query()->whereKey($game->id)->lockForUpdate()->firstOrFail();

            if ($game->status !== GameStatus::Arranging) {
                throw new RuntimeException('This game is not accepting submissions.');
            }

            // The deadline is enforced before the sweep job runs so a player
            // cannot slip a hand in after the table's timer has expired.
            if ($game->arrangement_deadline_at !== null && $game->arrangement_deadline_at->isPast()) {
                throw new RuntimeException('The arrangement deadline has passed.');
            }

            /** @var GamePlayer $player */
            $player = $game->players()->where('user_id', $user->id)->lockForUpdate()->firstOrFail();

            if ($player->submission()->exists()) {
                throw new RuntimeException('You have already submitted your hands.');
            }

            $hands = [];

            foreach ([HandPosition::Back, HandPosition::Middle, HandPosition::Front] as $position) {
                $codes = $handCodes[$position->value] ?? null;

                if (! is_array($codes) || count($codes) !== $position->cardCount()) {
                    throw new RuntimeException("The {$position->value} hand must contain exactly {$position->cardCount()} cards.");
                }

                $hands[$position->value] = Card::fromCodes($codes);
            }

            $this->assertCardsWereDealt($player, $hands);

            $foul = $this->foulDetector->validate($hands);

            GameHandSubmission::create([
                'game_player_id' => $player->id,
                'game_id' => $game->id,
                'hands' => $handCodes,
                'is_valid' => $foul->isValid,
                'foul_reason' => $foul->reason,
                'submitted_at' => now(),
                'ip_address' => app()->runningInConsole() ? null : request()->ip(),
            ]);

            foreach ($hands as $position => $cards) {
                $rank = $this->roundEvaluatorCategory($cards);

                GameHand::create([
                    'game_player_id' => $player->id,
                    'position' => $position,
                    'category' => $rank->category,
                    'strength_key' => $rank->strengthKey(),
                    'card_count' => count($cards),
                ]);

                foreach ($cards as $card) {
                    GameCard::query()
                        ->where('game_player_id', $player->id)
                        ->where('card_rank', $card->rank)
                        ->where('card_suit', $card->suit)
                        ->update(['position' => $position]);
                }
            }

            $player->forceFill([
                'status' => $foul->isValid ? GamePlayerStatus::Submitted : GamePlayerStatus::Fouled,
                'is_fouled' => $foul->isFouled(),
                'foul_reason' => $foul->reason,
                'submitted_at' => now(),
            ])->save();

            $submittedCount = $game->players()->whereNotNull('submitted_at')->count();
            $playerCount = $game->players()->count();

            event(new PlayerSubmittedHands($game->id, $user->id, $submittedCount, $playerCount));

            if ($submittedCount === $playerCount) {
                event(new AllHandsSubmitted($game->id));
            }

            $this->audit->game($game, 'hand.submitted', [
                'game_player_id' => $player->id,
                'is_valid' => $foul->isValid,
                'reason' => $foul->reason,
            ], $user);

            if ($game->players()->whereNull('submitted_at')->doesntExist()) {
                $this->evaluate($game);
            }

            return $player->refresh();
        });
    }

    /**
     * Resolve a round whose arrangement deadline has passed. Players who never
     * submitted are auto-fouled (decision D11), which keeps the table moving
     * instead of leaving three players waiting forever on one absent player.
     * Safe to call repeatedly: the game row lock and status check make it
     * idempotent, and the scheduler uses it as a backstop.
     */
    public function resolveDeadline(Game $game): ?EvaluationResult
    {
        return DB::transaction(function () use ($game) {
            $game = Game::query()->whereKey($game->id)->lockForUpdate()->firstOrFail();

            if ($game->status !== GameStatus::Arranging) {
                return null;
            }

            if ($game->arrangement_deadline_at === null || $game->arrangement_deadline_at->isFuture()) {
                return null;
            }

            $players = $game->players()->with('submission')->get();
            $missing = $players->filter(fn (GamePlayer $player) => $player->submission === null);

            foreach ($missing as $player) {
                $cards = GameCard::query()
                    ->where('game_player_id', $player->id)
                    ->orderBy('deal_order')
                    ->get();

                $hands = [
                    'back' => $cards->slice(0, 5)->values(),
                    'middle' => $cards->slice(5, 5)->values(),
                    'front' => $cards->slice(10, 3)->values(),
                ];

                $codes = [];

                foreach ($hands as $position => $positionCards) {
                    $codes[$position] = $positionCards
                        ->map(fn (GameCard $card) => Card::RANK_LABELS[$card->card_rank].$card->card_suit)
                        ->all();

                    $rank = $this->roundEvaluatorCategory(Card::fromCodes($codes[$position]));

                    GameHand::create([
                        'game_player_id' => $player->id,
                        'position' => $position,
                        'category' => $rank->category,
                        'strength_key' => $rank->strengthKey(),
                        'card_count' => count($codes[$position]),
                    ]);

                    GameCard::query()
                        ->whereIn('id', $positionCards->pluck('id'))
                        ->update(['position' => $position]);
                }

                GameHandSubmission::create([
                    'game_player_id' => $player->id,
                    'game_id' => $game->id,
                    'hands' => $codes,
                    'is_valid' => false,
                    'foul_reason' => self::TIMEOUT_FOUL_REASON,
                    'submitted_at' => now(),
                ]);

                $player->forceFill([
                    'status' => GamePlayerStatus::Fouled,
                    'is_fouled' => true,
                    'foul_reason' => self::TIMEOUT_FOUL_REASON,
                    'submitted_at' => now(),
                ])->save();

                $this->audit->game($game, 'hand.timed_out', [
                    'game_player_id' => $player->id,
                    'user_id' => $player->user_id,
                ]);
            }

            if ($missing->isNotEmpty()) {
                event(new AllHandsSubmitted($game->id));
            }

            return $this->evaluate($game);
        });
    }

    public function evaluate(Game $game): EvaluationResult
    {
        return DB::transaction(function () use ($game) {
            $game = Game::query()->whereKey($game->id)->lockForUpdate()->firstOrFail();

            if ($game->status === GameStatus::Completed) {
                throw new RuntimeException('This game has already been evaluated.');
            }

            $players = $game->players()->with('submission')->get();

            if ($players->count() !== 4 || $players->contains(fn (GamePlayer $player) => $player->submission === null)) {
                throw new RuntimeException('All four players must submit before evaluation.');
            }

            $input = [];

            foreach ($players as $player) {
                $submitted = $player->submission->hands;

                $input[] = [
                    'id' => $player->id,
                    'fouled' => $player->is_fouled,
                    'foul_reason' => $player->foul_reason,
                    'hands' => [
                        'back' => Card::fromCodes($submitted['back']),
                        'middle' => Card::fromCodes($submitted['middle']),
                        'front' => Card::fromCodes($submitted['front']),
                    ],
                ];
            }

            $result = $this->roundEvaluator->evaluate($input);

            $pointValueMinor = (int) ($game->settings_snapshot['point_value_minor'] ?? $this->settings->integer('game.point_value_minor', 0));
            $feesEnabled = $this->flags->enabled(FeatureFlagService::PLATFORM_FEES);
            $feeMinor = $feesEnabled ? ($game->room?->platform_fee_minor ?? 0) : 0;

            $lines = (new SettlementCalculator)->calculate(
                $result->gamePoints(),
                $pointValueMinor,
                $feeMinor,
                $feesEnabled,
            );

            foreach ($players as $player) {
                $playerResult = $result->player($player->id);

                $player->forceFill([
                    'game_points' => $playerResult['game_points'],
                    'is_fouled' => $playerResult['is_fouled'],
                    'foul_reason' => $playerResult['foul_reason'],
                ])->save();

                foreach ($result->comparisons as $comparison) {
                    if ($comparison['player_one'] !== $player->id && $comparison['player_two'] !== $player->id) {
                        continue;
                    }

                    GameHandResult::updateOrCreate(
                        [
                            'game_id' => $game->id,
                            'position' => $comparison['position'],
                            'player_one_id' => $comparison['player_one'],
                            'player_two_id' => $comparison['player_two'],
                        ],
                        [
                            'winner_player_id' => $comparison['winner'],
                            'result' => $comparison['result'],
                        ],
                    );
                }

                GamePlayerResult::updateOrCreate(
                    ['game_player_id' => $player->id],
                    [
                        'game_id' => $game->id,
                        'game_points' => $lines[$player->id]['game_points'],
                        'php_equivalent_minor' => $lines[$player->id]['php_minor'],
                        'fee_minor' => $lines[$player->id]['fee_minor'],
                        'net_minor' => $lines[$player->id]['net_minor'],
                        'is_fouled' => $playerResult['is_fouled'],
                    ],
                );
            }

            $game->forceFill([
                'status' => GameStatus::Completed,
                'completed_at' => now(),
            ])->save();

            GameCard::query()->where('game_id', $game->id)->whereNull('revealed_at')->update(['revealed_at' => now()]);

            $this->settlements->settle($game, $lines);

            event(new GameEvaluated($game->id, $result->players));
            event(new GameCompleted($game->id, $game->room_id));

            // Keep the same seats for another round: everyone just has to ready
            // up again, and anyone who leaves frees their seat. The table is
            // reset even when cash settlement is blocked, otherwise the room
            // would stay "in progress" forever and trap every player in it.
            if ($game->room !== null) {
                $this->rooms->resetForNextRound($game->room);
            }

            event(new LobbyUpdated(LobbySummary::build()));

            $winnerId = null;
            $maxPoints = -999;
            foreach ($result->players as $p) {
                if ($p['game_points'] > $maxPoints) {
                    $maxPoints = $p['game_points'];
                    $winnerId = $p['id'];
                }
            }

            event(new SettlementCompleted(
                $game->id,
                $winnerId,
                $winnerId !== null ? ($lines[$winnerId]['net_minor'] ?? 0) : 0
            ));

            $this->audit->game($game, 'game.evaluated', ['game_points' => $result->gamePoints()]);

            return $result;
        });
    }

    /**
     * @param  array<string, list<Card>>  $hands
     */
    private function assertCardsWereDealt(GamePlayer $player, array $hands): void
    {
        $dealt = GameCard::query()
            ->where('game_player_id', $player->id)
            ->get()
            ->map(fn (GameCard $card) => Card::RANK_LABELS[$card->card_rank].$card->card_suit)
            ->all();

        sort($dealt);

        $submitted = [];

        foreach ($hands as $cards) {
            foreach ($cards as $card) {
                $submitted[] = $card->code();
            }
        }

        sort($submitted);

        if (count(array_unique($submitted)) !== 13 || $submitted !== $dealt) {
            throw new RuntimeException('The submitted cards do not match the cards that were dealt to you.');
        }
    }

    /**
     * @param  list<Card>  $cards
     */
    private function roundEvaluatorCategory(array $cards): HandRank
    {
        return (new HandEvaluator)->evaluate($cards);
    }
}
