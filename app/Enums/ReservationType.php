<?php

namespace App\Enums;

enum ReservationType: string
{
    case GameEntry = 'game_entry';
    case Withdrawal = 'withdrawal';
}
