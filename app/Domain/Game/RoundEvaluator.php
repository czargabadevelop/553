<?php

namespace App\Domain\Game;

use App\Enums\HandCategory;
use App\Enums\HandPosition;
use InvalidArgumentException;

/**
 * Compares every player against every other player, for all three hands.
 *
 * Four players produce 6 pairings x 3 hands = 18 comparisons. Each won hand is
 * +1 game point, each lost hand -1, ties 0, so the total is always zero before
 * platform fees (agents.md §4).
 *
 * Foul settlement (decision D1, option A): a fouled player loses all three
 * hands to every non-fouled opponent. When both players in a pairing foul, the
 * comparisons are ties so the table stays zero-sum.
 */
class RoundEvaluator
{
    public function __construct(
        private readonly HandEvaluator $evaluator,
        private readonly FoulDetector $foulDetector,
    ) {}

    /**
     * @param  list<array{
     *     id: int,
     *     hands: array{back: list<Card>, middle: list<Card>, front: list<Card>},
     *     fouled?: bool,
     *     foul_reason?: ?string
     * }>  $players
     */
    public function evaluate(array $players): EvaluationResult
    {
        if (count($players) !== 4) {
            throw new InvalidArgumentException('A game requires exactly four players.');
        }

        $state = [];
        $points = [];
        $comparisons = [];

        foreach ($players as $player) {
            $foul = $this->foulDetector->validate($player['hands']);
            $isFouled = ($player['fouled'] ?? false) || $foul->isFouled();
            $reason = $player['foul_reason'] ?? $foul->reason;

            $hands = [];

            foreach ([HandPosition::Back, HandPosition::Middle, HandPosition::Front] as $position) {
                $cards = $player['hands'][$position->value];
                $rank = $this->evaluator->evaluate($cards);

                $hands[$position->value] = [
                    'category' => $rank->category,
                    'strength_key' => $rank->strengthKey(),
                    'rank' => $rank,
                    'cards' => $cards,
                ];
            }

            $state[$player['id']] = [
                'id' => $player['id'],
                'is_fouled' => $isFouled,
                'foul_reason' => $isFouled ? ($reason ?? 'Invalid arrangement.') : null,
                'hands' => $hands,
            ];

            $points[$player['id']] = 0;
        }

        $ids = array_keys($state);

        for ($first = 0; $first < count($ids); $first++) {
            for ($second = $first + 1; $second < count($ids); $second++) {
                $oneId = $ids[$first];
                $twoId = $ids[$second];

                foreach ([HandPosition::Back, HandPosition::Middle, HandPosition::Front] as $position) {
                    $oneFouled = $state[$oneId]['is_fouled'];
                    $twoFouled = $state[$twoId]['is_fouled'];

                    if ($oneFouled && $twoFouled) {
                        $result = 0;
                    } elseif ($oneFouled) {
                        $result = -1;
                    } elseif ($twoFouled) {
                        $result = 1;
                    } else {
                        $result = $state[$oneId]['hands'][$position->value]['rank']
                            ->compareTo($state[$twoId]['hands'][$position->value]['rank']);
                    }

                    $points[$oneId] += $result;
                    $points[$twoId] -= $result;

                    $comparisons[] = [
                        'player_one' => $oneId,
                        'player_two' => $twoId,
                        'position' => $position,
                        'winner' => $result === 1 ? $oneId : ($result === -1 ? $twoId : null),
                        'result' => $result,
                    ];
                }
            }
        }

        $resultPlayers = [];

        foreach ($state as $id => $playerState) {
            $hands = [];

            foreach ($playerState['hands'] as $position => $hand) {
                $hands[$position] = [
                    'category' => $hand['category'],
                    'category_label' => $hand['category']->label(),
                    'strength_key' => $hand['strength_key'],
                    'cards' => $hand['cards'],
                ];
            }

            $resultPlayers[] = [
                'id' => $id,
                'game_points' => $points[$id],
                'is_fouled' => $playerState['is_fouled'],
                'foul_reason' => $playerState['foul_reason'],
                'hands' => $hands,
            ];
        }

        $evaluation = new EvaluationResult($resultPlayers, $comparisons);

        if (! $evaluation->isZeroSum()) {
            throw new InvalidArgumentException('Game points must aggregate to zero.');
        }

        return $evaluation;
    }

    /**
     * Helper used by tests and the UI: category of a three-card front hand.
     *
     * @param  list<Card>  $cards
     */
    public function frontHandCategory(array $cards): HandCategory
    {
        return $this->evaluator->three($cards)->category;
    }
}
