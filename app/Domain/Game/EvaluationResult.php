<?php

namespace App\Domain\Game;

use App\Enums\HandCategory;
use App\Enums\HandPosition;

/**
 * Complete result of comparing four players.
 *
 * Shape of `players`:
 *   array<int, array{
 *       id: int,
 *       game_points: int,
 *       is_fouled: bool,
 *       foul_reason: ?string,
 *       hands: array<string, array{category: string, strength_key: string, cards: list<Card>}>
 *   }>
 *
 * Shape of `comparisons` (18 rows for a foul-free four-player game):
 *   list<array{
 *       player_one: int,
 *       player_two: int,
 *       position: HandPosition,
 *       winner: ?int,
 *       result: int
 *   }>
 */
final readonly class EvaluationResult
{
    public function __construct(
        public array $players,
        public array $comparisons,
    ) {}

    /**
     * @return array<int, int>
     */
    public function gamePoints(): array
    {
        $points = [];

        foreach ($this->players as $player) {
            $points[$player['id']] = $player['game_points'];
        }

        return $points;
    }

    public function totalGamePoints(): int
    {
        return (int) array_sum($this->gamePoints());
    }

    public function isZeroSum(): bool
    {
        return $this->totalGamePoints() === 0;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function player(int $id): ?array
    {
        foreach ($this->players as $player) {
            if ($player['id'] === $id) {
                return $player;
            }
        }

        return null;
    }

    /**
     * Summarise one player's hand strengths for display.
     *
     * @return array<string, array{category: HandCategory, strength_key: string}>
     */
    public function handSummary(int $playerId): array
    {
        $player = $this->player($playerId);

        if ($player === null) {
            return [];
        }

        $summary = [];

        foreach ($player['hands'] as $position => $hand) {
            $summary[$position] = [
                'category' => $hand['category'],
                'strength_key' => $hand['strength_key'],
            ];
        }

        return $summary;
    }
}
