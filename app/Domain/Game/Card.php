<?php

namespace App\Domain\Game;

use InvalidArgumentException;

/**
 * A standard playing card. Ranks are 2..14 (14 = Ace); suits are s/h/d/c.
 */
final readonly class Card
{
    public const SUITS = ['s', 'h', 'd', 'c'];

    /**
     * A card code is a rank label plus a suit: "As", "10h", "Kd".
     * Tens are three characters long, so length checks must not be used.
     */
    public const CODE_PATTERN = '/^(?:10|[2-9TJQKA])[shdc]$/i';

    public const RANK_LABELS = [
        2 => '2', 3 => '3', 4 => '4', 5 => '5', 6 => '6', 7 => '7', 8 => '8',
        9 => '9', 10 => '10', 11 => 'J', 12 => 'Q', 13 => 'K', 14 => 'A',
    ];

    public function __construct(
        public int $rank,
        public string $suit,
    ) {
        if ($rank < 2 || $rank > 14) {
            throw new InvalidArgumentException("Invalid card rank: {$rank}.");
        }

        if (! in_array($suit, self::SUITS, true)) {
            throw new InvalidArgumentException("Invalid card suit: {$suit}.");
        }
    }

    /**
     * Build a card from codes like "AS", "TH", "2C".
     */
    public static function fromCode(string $code): self
    {
        $code = trim($code);

        if (strlen($code) < 2) {
            throw new InvalidArgumentException("Invalid card code: {$code}.");
        }

        $suit = strtolower(substr($code, -1));
        $rankLabel = strtoupper(substr($code, 0, -1));

        $faceRanks = ['T' => 10, 'J' => 11, 'Q' => 12, 'K' => 13, 'A' => 14];

        $rank = ctype_digit($rankLabel)
            ? (int) $rankLabel
            : ($faceRanks[$rankLabel] ?? null);

        if ($rank === null || $rank < 2 || $rank > 14) {
            throw new InvalidArgumentException("Invalid card rank in code: {$code}.");
        }

        return new self($rank, $suit);
    }

    /**
     * @param  list<string>  $codes
     * @return list<self>
     */
    public static function fromCodes(array $codes): array
    {
        return array_map(fn (string $code) => self::fromCode($code), $codes);
    }

    public function code(): string
    {
        return self::RANK_LABELS[$this->rank].$this->suit;
    }

    public function rankLabel(): string
    {
        return self::RANK_LABELS[$this->rank];
    }

    public function equals(self $other): bool
    {
        return $this->rank === $other->rank && $this->suit === $other->suit;
    }

    /**
     * A stable identifier used to guarantee uniqueness inside a game.
     */
    public function id(): string
    {
        return $this->code();
    }
}
