<?php

namespace App\Enums;

/**
 * Hand categories, strongest first. Five-card hands use the full ranking;
 * the three-card front hand only recognises ThreeOfAKind, OnePair and HighCard.
 */
enum HandCategory: string
{
    case HighCard = 'high_card';
    case OnePair = 'one_pair';
    case TwoPair = 'two_pair';
    case ThreeOfAKind = 'three_of_a_kind';
    case Straight = 'straight';
    case Flush = 'flush';
    case FullHouse = 'full_house';
    case FourOfAKind = 'four_of_a_kind';
    case StraightFlush = 'straight_flush';
    case RoyalFlush = 'royal_flush';

    /**
     * Category strength, higher is stronger.
     */
    public function rank(): int
    {
        return match ($this) {
            self::HighCard => 1,
            self::OnePair => 2,
            self::TwoPair => 3,
            self::ThreeOfAKind => 4,
            self::Straight => 5,
            self::Flush => 6,
            self::FullHouse => 7,
            self::FourOfAKind => 8,
            self::StraightFlush => 9,
            self::RoyalFlush => 10,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::HighCard => 'High Card',
            self::OnePair => 'One Pair',
            self::TwoPair => 'Two Pair',
            self::ThreeOfAKind => 'Three of a Kind',
            self::Straight => 'Straight',
            self::Flush => 'Flush',
            self::FullHouse => 'Full House',
            self::FourOfAKind => 'Four of a Kind',
            self::StraightFlush => 'Straight Flush',
            self::RoyalFlush => 'Royal Flush',
        };
    }

    /**
     * Whether the category can be scored in a three-card front hand.
     */
    public function isFrontHandCategory(): bool
    {
        return in_array($this, [self::HighCard, self::OnePair, self::ThreeOfAKind], true);
    }

    /**
     * @return list<self>
     */
    public static function forFrontHand(): array
    {
        return array_values(array_filter(self::cases(), fn (self $category) => $category->isFrontHandCategory()));
    }
}
