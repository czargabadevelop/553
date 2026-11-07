<?php

namespace App\Domain\Game;

use App\Enums\HandPosition;

/**
 * Detects invalid 13-card arrangements on the server. Players and API clients
 * can never bypass this check.
 */
class FoulDetector
{
    public function __construct(private readonly HandEvaluator $evaluator) {}

    /**
     * @param  array{back: list<Card>, middle: list<Card>, front: list<Card>}  $hands
     */
    public function validate(array $hands): FoulResult
    {
        $expected = [
            HandPosition::Back->value => 5,
            HandPosition::Middle->value => 5,
            HandPosition::Front->value => 3,
        ];

        foreach ($expected as $position => $count) {
            $cards = $hands[$position] ?? null;

            if (! is_array($cards)) {
                return FoulResult::foul("The {$position} hand is missing.");
            }

            if (count($cards) !== $count) {
                return FoulResult::foul("The {$position} hand must contain exactly {$count} cards.");
            }
        }

        $allCards = [...$hands['back'], ...$hands['middle'], ...$hands['front']];

        if (count($allCards) !== 13) {
            return FoulResult::foul('An arrangement must contain exactly 13 cards.');
        }

        $ids = array_map(fn (Card $card) => $card->id(), $allCards);

        if (count(array_unique($ids)) !== 13) {
            return FoulResult::foul('The arrangement contains duplicate cards.');
        }

        $back = $this->evaluator->five($hands['back']);
        $middle = $this->evaluator->five($hands['middle']);
        $front = $this->evaluator->three($hands['front']);

        $ranks = [
            HandPosition::Back->value => $back,
            HandPosition::Middle->value => $middle,
            HandPosition::Front->value => $front,
        ];

        if (! $back->isAtLeast($middle)) {
            return FoulResult::foul('The back hand must be stronger than or equal to the middle hand.', $ranks);
        }

        if (! $middle->isAtLeast($front)) {
            return FoulResult::foul('The middle hand must be stronger than or equal to the front hand.', $ranks);
        }

        return FoulResult::valid($ranks);
    }
}
