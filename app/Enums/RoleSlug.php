<?php

namespace App\Enums;

/**
 * The five roles described in agents.md §13.
 */
enum RoleSlug: string
{
    case SuperAdministrator = 'super_administrator';
    case FinanceAdministrator = 'finance_administrator';
    case GameAdministrator = 'game_administrator';
    case SupportAdministrator = 'support_administrator';
    case Player = 'player';

    public function label(): string
    {
        return match ($this) {
            self::SuperAdministrator => 'Super Administrator',
            self::FinanceAdministrator => 'Finance Administrator',
            self::GameAdministrator => 'Game Administrator',
            self::SupportAdministrator => 'Support Administrator',
            self::Player => 'Player',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::SuperAdministrator => 'Full system access, including administrators, roles, settings and financial configuration.',
            self::FinanceAdministrator => 'Deposits, withdrawals, ledger review and financial reports. No game-engine or administrator management.',
            self::GameAdministrator => 'Rooms, active game monitoring and game history. Cannot modify wallet balances.',
            self::SupportAdministrator => 'Read-only access to players, accounts and game history. No financial approvals.',
            self::Player => 'Own account, wallet, room participation and game history.',
        };
    }

    public function isAdministrator(): bool
    {
        return $this !== self::Player;
    }

    /**
     * Permissions granted to this role by default.
     *
     * @return list<Permission>
     */
    public function permissions(): array
    {
        return match ($this) {
            self::SuperAdministrator => Permission::cases(),
            self::FinanceAdministrator => [
                Permission::ViewPlayers,
                Permission::ReviewDeposits,
                Permission::ApproveWithdrawals,
                Permission::CompletePayouts,
                Permission::ViewLedger,
                Permission::ViewReports,
                Permission::ViewAuditLogs,
            ],
            self::GameAdministrator => [
                Permission::ViewPlayers,
                Permission::ManageRooms,
                Permission::MonitorGames,
                Permission::ReviewGames,
                Permission::ReviewKyc,
            ],
            self::SupportAdministrator => [
                Permission::ViewPlayers,
                Permission::ReviewKyc,
                Permission::ReviewGames,
            ],
            self::Player => [],
        };
    }
}
