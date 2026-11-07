<?php

namespace App\Enums;

enum GameStatus: string
{
    case Waiting = 'waiting';
    case Dealing = 'dealing';
    case Arranging = 'arranging';
    case Evaluating = 'evaluating';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return str($this->value)->replace('_', ' ')->title()->toString();
    }

    public function isActive(): bool
    {
        return $this === self::Dealing || $this === self::Arranging || $this === self::Evaluating;
    }

    public function isFinished(): bool
    {
        return $this === self::Completed || $this === self::Cancelled;
    }
}
