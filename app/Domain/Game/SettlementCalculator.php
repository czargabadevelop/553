<?php

namespace App\Domain\Game;

use App\Domain\Wallet\Money;
use InvalidArgumentException;

/**
 * Converts game points into cash-point amounts using the configured value of
 * one game point. Game points stay separate from wallet balances (agents.md §7).
 */
class SettlementCalculator
{
    /**
     * @param  array<int, int>  $gamePointsById
     * @return array<int, array{game_points: int, php_minor: int, fee_minor: int, net_minor: int}>
     */
    public function calculate(
        array $gamePointsById,
        int $pointValueMinor,
        int $feeMinor = 0,
        bool $feesEnabled = false,
    ): array {
        if (array_sum($gamePointsById) !== 0) {
            throw new InvalidArgumentException('Game points must be zero-sum before fees.');
        }

        if ($pointValueMinor < 0 || $feeMinor < 0) {
            throw new InvalidArgumentException('Point value and fee must not be negative.');
        }

        $appliedFee = $feesEnabled ? $feeMinor : 0;
        $lines = [];

        foreach ($gamePointsById as $playerId => $points) {
            $phpMinor = $points * $pointValueMinor;

            $lines[$playerId] = [
                'game_points' => $points,
                'php_minor' => $phpMinor,
                'fee_minor' => $appliedFee,
                'net_minor' => $phpMinor - $appliedFee,
            ];
        }

        return $lines;
    }

    public function phpEquivalent(int $gamePoints, int $pointValueMinor): Money
    {
        return Money::fromMinor($gamePoints * $pointValueMinor);
    }
}
