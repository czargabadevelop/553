<?php

namespace App\Enums;

enum GamePlayerStatus: string
{
    case Waiting = 'waiting';
    case Submitted = 'submitted';
    case AutoSubmitted = 'auto_submitted';
    case Fouled = 'fouled';

    public function hasSubmitted(): bool
    {
        return $this === self::Submitted || $this === self::AutoSubmitted || $this === self::Fouled;
    }
}
