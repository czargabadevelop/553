<?php

namespace App\Domain\Game;

use InvalidArgumentException;
use Random\Engine\Secure;
use Random\Randomizer;

/**
 * A standard 52-card deck. Shuffling always uses a cryptographically secure
 * engine on the server; the client never influences card order.
 */
final class Deck
{
    /**
     * @param  list<Card>  $cards
     */
    private function __construct(private array $cards) {}

    public static function standard(): self
    {
        $cards = [];

        foreach (Card::SUITS as $suit) {
            foreach (array_keys(Card::RANK_LABELS) as $rank) {
                $cards[] = new Card((int) $rank, $suit);
            }
        }

        return new self($cards);
    }

    public static function shuffled(?Randomizer $randomizer = null): self
    {
        $randomizer ??= new Randomizer(new Secure);

        return new self(array_values($randomizer->shuffleArray(self::standard()->cards)));
    }

    /**
     * @return list<Card>
     */
    public function cards(): array
    {
        return $this->cards;
    }

    public function count(): int
    {
        return count($this->cards);
    }

    /**
     * Deal the deck round-robin into hands.
     *
     * @return list<list<Card>>
     */
    public function deal(int $players, int $cardsPerPlayer = 13): array
    {
        if ($players < 1) {
            throw new InvalidArgumentException('A deal needs at least one player.');
        }

        if ($players * $cardsPerPlayer > count($this->cards)) {
            throw new InvalidArgumentException('Not enough cards to deal.');
        }

        $hands = array_fill(0, $players, []);

        for ($index = 0; $index < $players * $cardsPerPlayer; $index++) {
            $hands[$index % $players][] = $this->cards[$index];
        }

        return $hands;
    }

    /**
     * SHA-256 fingerprint of the deck in order, stored for auditing.
     */
    public function fingerprint(): string
    {
        return hash('sha256', implode(',', array_map(fn (Card $card) => $card->code(), $this->cards)));
    }
}
