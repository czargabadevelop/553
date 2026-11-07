<?php

namespace App\Domain\Game;

/**
 * Outcome of validating a player's 13-card arrangement.
 */
final readonly class FoulResult
{
    /**
     * @param  array<string, HandRank>  $handRanks
     */
    public function __construct(
        public bool $isValid,
        public ?string $reason = null,
        public array $handRanks = [],
    ) {}

    public static function valid(array $handRanks): self
    {
        return new self(true, null, $handRanks);
    }

    public static function foul(string $reason, array $handRanks = []): self
    {
        return new self(false, $reason, $handRanks);
    }

    public function isFouled(): bool
    {
        return ! $this->isValid;
    }
}
