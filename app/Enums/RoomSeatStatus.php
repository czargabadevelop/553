<?php

namespace App\Enums;

enum RoomSeatStatus: string
{
    case Seated = 'seated';
    case Ready = 'ready';
    case Left = 'left';

    public function isOccupied(): bool
    {
        return $this !== self::Left;
    }
}
