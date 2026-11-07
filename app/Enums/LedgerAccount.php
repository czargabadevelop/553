<?php

namespace App\Enums;

/**
 * Ledger accounts. Every financial movement is posted between two of these
 * accounts so debit and credit entries always balance (agents.md §7).
 */
enum LedgerAccount: string
{
    case UserAvailable = 'user_available';
    case UserReserved = 'user_reserved';
    case PlatformRevenue = 'platform_revenue';
    case PlatformFeeClearing = 'platform_fee_clearing';
    case GameFloat = 'game_float';
    case ExternalClearing = 'external_clearing';

    public function label(): string
    {
        return str($this->value)->replace('_', ' ')->title()->toString();
    }
}
