<?php

namespace App\Domain\Game;

use App\Enums\HandCategory;
use InvalidArgumentException;

/**
 * Pepito (Philippine 13-card Chinese poker) hand evaluation.
 *
 * Five-card hands use the full poker ranking. Three-card front hands only
 * recognise three of a kind, one pair and high card (agents.md §4).
 */
class HandEvaluator
{
    /**
     * @param  list<Card>  $cards
     */
    public function evaluate(array $cards): HandRank
    {
        return match (count($cards)) {
            5 => $this->five($cards),
            3 => $this->three($cards),
            default => throw new InvalidArgumentException('A hand must contain five or three cards.'),
        };
    }

    /**
     * @param  list<Card>  $cards
     */
    public function five(array $cards): HandRank
    {
        if (count($cards) !== 5) {
            throw new InvalidArgumentException('A five-card hand needs exactly five cards.');
        }

        $ranks = array_map(fn (Card $card) => $card->rank, $cards);
        rsort($ranks);

        $suits = array_unique(array_map(fn (Card $card) => $card->suit, $cards));
        $isFlush = count($suits) === 1;

        $straightHigh = $this->straightHigh($ranks);
        $isStraight = $straightHigh !== null;

        $counts = array_count_values($ranks);
        arsort($counts);

        $groups = [];
        foreach ($counts as $rank => $count) {
            $groups[$count][] = (int) $rank;
        }

        foreach ($groups as &$groupRanks) {
            rsort($groupRanks);
        }
        unset($groupRanks);

        $kickersDesc = $ranks;

        if ($isFlush && $isStraight && $straightHigh === 14) {
            return new HandRank(HandCategory::RoyalFlush, [14], 5);
        }

        if ($isFlush && $isStraight) {
            return new HandRank(HandCategory::StraightFlush, [$straightHigh], 5);
        }

        if (isset($groups[4])) {
            $quad = $groups[4][0];
            $kicker = $groups[1][0];

            return new HandRank(HandCategory::FourOfAKind, [$quad, $kicker], 5);
        }

        if (isset($groups[3]) && isset($groups[2])) {
            return new HandRank(HandCategory::FullHouse, [$groups[3][0], $groups[2][0]], 5);
        }

        if ($isFlush) {
            return new HandRank(HandCategory::Flush, $kickersDesc, 5);
        }

        if ($isStraight) {
            return new HandRank(HandCategory::Straight, [$straightHigh], 5);
        }

        if (isset($groups[3])) {
            $trip = $groups[3][0];
            $remaining = array_values(array_filter($kickersDesc, fn (int $rank) => $rank !== $trip));
            sort($remaining);

            return new HandRank(HandCategory::ThreeOfAKind, [$trip, ...array_reverse($remaining)], 5);
        }

        if (isset($groups[2]) && count($groups[2]) === 2) {
            $pairs = $groups[2];
            rsort($pairs);
            $kicker = $groups[1][0] ?? null;

            return new HandRank(HandCategory::TwoPair, [$pairs[0], $pairs[1], (int) $kicker], 5);
        }

        if (isset($groups[2])) {
            $pair = $groups[2][0];
            $kickers = array_values(array_filter($kickersDesc, fn (int $rank) => $rank !== $pair));

            return new HandRank(HandCategory::OnePair, [$pair, ...$kickers], 5);
        }

        return new HandRank(HandCategory::HighCard, $kickersDesc, 5);
    }

    /**
     * @param  list<Card>  $cards
     */
    public function three(array $cards): HandRank
    {
        if (count($cards) !== 3) {
            throw new InvalidArgumentException('A three-card hand needs exactly three cards.');
        }

        $ranks = array_map(fn (Card $card) => $card->rank, $cards);
        rsort($ranks);

        $counts = array_count_values($ranks);
        arsort($counts);

        if (max($counts) === 3) {
            return new HandRank(HandCategory::ThreeOfAKind, [$ranks[0]], 3);
        }

        if (max($counts) === 2) {
            $pair = array_search(2, $counts, true);
            $kicker = $ranks[0] === $pair ? $ranks[1] : $ranks[0];

            return new HandRank(HandCategory::OnePair, [(int) $pair, (int) $kicker], 3);
        }

        // Straights and flushes are not ranked in a three-card front hand.
        return new HandRank(HandCategory::HighCard, $ranks, 3);
    }

    /**
     * Highest straight top card, or null. Handles the ace-low wheel.
     *
     * @param  list<int>  $ranks
     */
    private function straightHigh(array $ranks): ?int
    {
        $unique = array_values(array_unique($ranks));

        if (count($unique) !== 5) {
            return null;
        }

        $sorted = $unique;
        rsort($sorted);

        if ($sorted[0] - $sorted[4] === 4) {
            return $sorted[0];
        }

        // A-2-3-4-5 counts as a five-high straight.
        if ($sorted === [14, 5, 4, 3, 2]) {
            return 5;
        }

        return null;
    }
}
