<?php

namespace App\Domain\Game;

use App\Enums\HandCategory;

/**
 * The evaluated strength of one hand. `tiebreakers` holds ranks in comparison
 * order (category rank is compared first). Suits are never ranked, so two
 * hands can be exactly equal.
 */
final readonly class HandRank
{
    /**
     * @param  list<int>  $tiebreakers
     */
    public function __construct(
        public HandCategory $category,
        public array $tiebreakers,
        public int $cardCount,
    ) {}

    /**
     * Strength key persisted with the hand for audit and sorting.
     */
    public function strengthKey(): string
    {
        $padded = array_map(fn (int $rank) => str_pad((string) $rank, 2, '0', STR_PAD_LEFT), $this->tiebreakers);

        return str_pad((string) $this->category->rank(), 2, '0', STR_PAD_LEFT).':'.implode('', $padded);
    }

    /**
     * Compare two hands.
     *
     * Returns 1 when this hand is stronger, -1 when weaker, 0 on a tie.
     * Category strength is compared first, even across hand sizes: a three-card
     * trip outranks a five-card two pair, so putting trips in the front hand is
     * correctly detected as a foul by the back/middle/front ordering check.
     */
    public function compareTo(self $other): int
    {
        $categoryComparison = $this->category->rank() <=> $other->category->rank();

        if ($categoryComparison !== 0) {
            return $categoryComparison;
        }

        $length = max(count($this->tiebreakers), count($other->tiebreakers));

        for ($index = 0; $index < $length; $index++) {
            $mine = $this->tiebreakers[$index] ?? 0;
            $theirs = $other->tiebreakers[$index] ?? 0;

            if ($mine !== $theirs) {
                return $mine <=> $theirs;
            }
        }

        return 0;
    }

    public function beats(self $other): bool
    {
        return $this->compareTo($other) === 1;
    }

    public function tiesWith(self $other): bool
    {
        return $this->compareTo($other) === 0;
    }

    public function isAtLeast(self $other): bool
    {
        return $this->compareTo($other) >= 0;
    }
}
