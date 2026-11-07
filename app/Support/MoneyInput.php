<?php

namespace App\Support;

use InvalidArgumentException;

/**
 * Converts user-entered decimal amounts ("150.75") into integer minor units
 * using string arithmetic only, so no float ever touches a balance.
 */
final class MoneyInput
{
    public static function toMinor(string|int|float|null $value): int
    {
        if ($value === null || $value === '') {
            throw new InvalidArgumentException('An amount is required.');
        }

        $raw = trim((string) $value);

        if (! preg_match('/^-?\d{1,15}(\.\d{1,2})?$/', $raw)) {
            throw new InvalidArgumentException('The amount must have at most two decimal places.');
        }

        $negative = str_starts_with($raw, '-');
        $raw = ltrim($raw, '-');

        [$units, $cents] = array_pad(explode('.', $raw, 2), 2, '0');

        $minor = ((int) $units * 100) + (int) str_pad($cents, 2, '0');

        return $negative ? -$minor : $minor;
    }

    public static function format(int $minor, string $currency = 'PHP'): string
    {
        return $currency.' '.number_format($minor / 100, 2, '.', ',');
    }
}
