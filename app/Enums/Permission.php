<?php

namespace App\Enums;

/**
 * Administrative permissions. Financial approval and payout completion are
 * deliberately separate abilities (agents.md §12).
 */
enum Permission: string
{
    case ManageAdministrators = 'administrators.manage';
    case ManageRoles = 'roles.manage';
    case ManageSettings = 'settings.manage';
    case ManageFinancialSettings = 'settings.financial.manage';
    case ManageRooms = 'rooms.manage';
    case MonitorGames = 'games.monitor';
    case ReviewGames = 'games.review';
    case ViewPlayers = 'players.view';
    case ManagePlayers = 'players.manage';
    case ReviewKyc = 'kyc.review';
    case ReviewDeposits = 'deposits.review';
    case ApproveWithdrawals = 'withdrawals.approve';
    case CompletePayouts = 'payouts.complete';
    case ViewLedger = 'ledger.view';
    case ViewReports = 'reports.view';
    case ViewAuditLogs = 'audit.view';
    case AdjustWallets = 'wallets.adjust';

    public function label(): string
    {
        return match ($this) {
            self::ManageAdministrators => 'Manage administrators',
            self::ManageRoles => 'Manage roles and permissions',
            self::ManageSettings => 'Manage settings',
            self::ManageFinancialSettings => 'Manage financial settings',
            self::ManageRooms => 'Manage rooms',
            self::MonitorGames => 'Monitor active games',
            self::ReviewGames => 'Review game history',
            self::ViewPlayers => 'View players',
            self::ManagePlayers => 'Manage player accounts',
            self::ReviewKyc => 'Review KYC submissions',
            self::ReviewDeposits => 'Review deposits',
            self::ApproveWithdrawals => 'Approve withdrawals',
            self::CompletePayouts => 'Confirm payout completion',
            self::ViewLedger => 'View the ledger',
            self::ViewReports => 'View reports',
            self::ViewAuditLogs => 'View audit logs',
            self::AdjustWallets => 'Post wallet adjustments',
        };
    }

    public function group(): string
    {
        return match ($this) {
            self::ManageAdministrators, self::ManageRoles => 'access',
            self::ManageSettings, self::ManageFinancialSettings => 'settings',
            self::ManageRooms, self::MonitorGames, self::ReviewGames => 'games',
            self::ViewPlayers, self::ManagePlayers, self::ReviewKyc => 'players',
            self::ReviewDeposits, self::ApproveWithdrawals, self::CompletePayouts,
            self::ViewLedger, self::ViewReports, self::AdjustWallets => 'finance',
            self::ViewAuditLogs => 'audit',
        };
    }
}
