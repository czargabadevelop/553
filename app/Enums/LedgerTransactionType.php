<?php

namespace App\Enums;

enum LedgerTransactionType: string
{
    case Deposit = 'deposit';
    case Withdrawal = 'withdrawal';
    case Reservation = 'reservation';
    case ReservationRelease = 'reservation_release';
    case GameSettlement = 'game_settlement';
    case PlatformFee = 'platform_fee';
    case AdministrativeAdjustment = 'administrative_adjustment';

    public function label(): string
    {
        return str($this->value)->replace('_', ' ')->title()->toString();
    }
}
