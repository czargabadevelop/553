<?php

namespace App\Domain\Wallet\Exceptions;

use RuntimeException;

class InsufficientFundsException extends RuntimeException
{
    public static function forAmount(int $requiredMinor, int $availableMinor): self
    {
        return new self(sprintf(
            'Insufficient available balance: %d minor units required, %d available.',
            $requiredMinor,
            $availableMinor,
        ));
    }
}
