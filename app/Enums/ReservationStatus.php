<?php

namespace App\Enums;

enum ReservationStatus: string
{
    case Held = 'held';
    case Released = 'released';
    case Captured = 'captured';
    case Expired = 'expired';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    public function isOpen(): bool
    {
        return $this === self::Held;
    }
}
