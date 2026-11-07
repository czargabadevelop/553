<?php

namespace App\Enums;

/**
 * The three hands a player builds from 13 cards (agents.md §4).
 */
enum HandPosition: string
{
    case Back = 'back';
    case Middle = 'middle';
    case Front = 'front';

    public function cardCount(): int
    {
        return match ($this) {
            self::Back, self::Middle => 5,
            self::Front => 3,
        };
    }

    /**
     * Position strength order: back is the strongest, front the weakest.
     */
    public function order(): int
    {
        return match ($this) {
            self::Back => 3,
            self::Middle => 2,
            self::Front => 1,
        };
    }

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
