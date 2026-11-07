<?php

use App\Domain\Game\Card;
use App\Domain\Game\Deck;
use App\Domain\Game\FoulDetector;
use App\Domain\Game\HandEvaluator;
use App\Domain\Game\RoundEvaluator;
use App\Domain\Game\SettlementCalculator;
use App\Enums\HandCategory;

/**
 * @param  list<string>  $codes
 * @return list<Card>
 */
function pokerHand(array $codes): array
{
    return Card::fromCodes($codes);
}

function evaluator(): HandEvaluator
{
    return new HandEvaluator;
}

test('a standard deck contains 52 unique cards', function () {
    $deck = Deck::standard();
    $cards = $deck->cards();

    expect($cards)->toHaveCount(52);
    expect(array_unique(array_map(fn (Card $card) => $card->id(), $cards)))->toHaveCount(52);
});

test('a shuffled deck keeps all 52 cards and deals 13 unique cards to each of four players', function () {
    $deck = Deck::shuffled();
    $hands = $deck->deal(4, 13);

    expect($deck->count())->toBe(52);
    expect($hands)->toHaveCount(4);

    $all = [];

    foreach ($hands as $hand) {
        expect($hand)->toHaveCount(13);

        foreach ($hand as $card) {
            expect($card)->toBeInstanceOf(Card::class);
            $all[] = $card->id();
        }
    }

    expect(array_unique($all))->toHaveCount(52);

    $deckIds = array_map(fn (Card $card) => $card->id(), $deck->cards());
    sort($deckIds);
    sort($all);
    expect($all)->toBe($deckIds);
});

test('five-card hands are ranked in traditional order', function () {
    $expectations = [
        'royal flush' => ['AS', 'KS', 'QS', 'JS', 'TS', HandCategory::RoyalFlush],
        'straight flush' => ['9H', '8H', '7H', '6H', '5H', HandCategory::StraightFlush],
        'four of a kind' => ['9C', '9D', '9H', '9S', '2C', HandCategory::FourOfAKind],
        'full house' => ['KC', 'KD', 'KH', '4C', '4D', HandCategory::FullHouse],
        'flush' => ['AD', 'JD', '8D', '5D', '2D', HandCategory::Flush],
        'straight' => ['9S', '8C', '7D', '6H', '5S', HandCategory::Straight],
        'three of a kind' => ['7C', '7D', '7H', 'KS', '3C', HandCategory::ThreeOfAKind],
        'two pair' => ['JC', 'JD', '4C', '4S', '9H', HandCategory::TwoPair],
        'one pair' => ['5C', '5S', 'AD', '9H', '3C', HandCategory::OnePair],
        'high card' => ['AC', '9D', '7H', '5S', '2C', HandCategory::HighCard],
    ];

    $previousRank = PHP_INT_MAX;

    foreach ($expectations as $name => [$a, $b, $c, $d, $e, $category]) {
        $rank = evaluator()->five(pokerHand([$a, $b, $c, $d, $e]));

        expect($rank->category)->toBe($category, $name);
        expect($category->rank())->toBeLessThanOrEqual($previousRank);

        $previousRank = $category->rank();
    }
});

test('kickers and rank sets break ties correctly', function () {
    $aceKicker = evaluator()->five(pokerHand(['AC', 'AD', 'KH', '9S', '3C']));
    $aceQueenKicker = evaluator()->five(pokerHand(['AH', 'AS', 'QH', '9C', '3D']));

    expect($aceKicker->beats($aceQueenKicker))->toBeTrue();

    $twoPairHigh = evaluator()->five(pokerHand(['KC', 'KD', '9C', '9D', '2H']));
    $twoPairLow = evaluator()->five(pokerHand(['QC', 'QD', 'JC', 'JD', 'AH']));

    expect($twoPairHigh->beats($twoPairLow))->toBeTrue();

    $fullHouse = evaluator()->five(pokerHand(['KC', 'KD', 'KH', '4C', '4D']));
    $smallerFullHouse = evaluator()->five(pokerHand(['QC', 'QD', 'QH', 'AC', 'AD']));

    expect($fullHouse->beats($smallerFullHouse))->toBeTrue();
});

test('the ace-low straight is a five-high straight ranked below six-high', function () {
    $wheel = evaluator()->five(pokerHand(['AC', '2D', '3H', '4S', '5C']));
    $sixHigh = evaluator()->five(pokerHand(['2C', '3D', '4H', '5S', '6C']));

    expect($wheel->category)->toBe(HandCategory::Straight);
    expect($wheel->tiebreakers[0])->toBe(5);
    expect($sixHigh->beats($wheel))->toBeTrue();
});

test('suits never break ties', function () {
    $spadesFlush = evaluator()->five(pokerHand(['AS', 'KS', 'QS', 'JS', '9S']));
    $heartsFlush = evaluator()->five(pokerHand(['AH', 'KH', 'QH', 'JH', '9H']));

    expect($spadesFlush->compareTo($heartsFlush))->toBe(0);

    $spadeHigh = evaluator()->three(pokerHand(['AS', 'KD', '7C']));
    $heartHigh = evaluator()->three(pokerHand(['AH', 'KC', '7D']));

    expect($spadeHigh->compareTo($heartHigh))->toBe(0);
});

test('three-card front hands only recognise trips, pairs and high card', function () {
    $trips = evaluator()->three(pokerHand(['9C', '9D', '9H']));
    $pair = evaluator()->three(pokerHand(['AC', 'AD', '2H']));
    $high = evaluator()->three(pokerHand(['AS', 'KD', '7C']));

    // A three-card straight is not a ranked category in the front hand.
    $straightish = evaluator()->three(pokerHand(['QS', 'KD', 'AH']));

    expect($trips->category)->toBe(HandCategory::ThreeOfAKind);
    expect($pair->category)->toBe(HandCategory::OnePair);
    expect($high->category)->toBe(HandCategory::HighCard);
    expect($straightish->category)->toBe(HandCategory::HighCard);

    $trips->compareTo($pair);
    expect($trips->beats($pair))->toBeTrue();
    expect($trips->beats($high))->toBeTrue();
    expect($pair->beats($high))->toBeTrue();

    $lowTrips = evaluator()->three(pokerHand(['3C', '3D', '3H']));
    expect($trips->beats($lowTrips))->toBeTrue();
});

test('an arrangement is valid when back is at least middle and middle at least front', function () {
    $detector = new FoulDetector(new HandEvaluator);

    $result = $detector->validate([
        'back' => pokerHand(['AS', 'KS', 'QS', 'JS', 'TS']),
        'middle' => pokerHand(['9H', '8H', '7H', '6H', '5H']),
        'front' => pokerHand(['2C', '2D', '2H']),
    ]);

    expect($result->isValid)->toBeTrue();
    expect($result->reason)->toBeNull();
});

test('a back hand weaker than the middle hand is a foul with a reason', function () {
    $detector = new FoulDetector(new HandEvaluator);

    $result = $detector->validate([
        'back' => pokerHand(['2C', '5D', '7H', '9S', 'JC']),
        'middle' => pokerHand(['AS', 'KS', 'QS', 'JS', 'TS']),
        'front' => pokerHand(['3C', '3D', '8H']),
    ]);

    expect($result->isFouled())->toBeTrue();
    expect($result->reason)->toContain('back hand');
});

test('a middle hand weaker than the front hand is a foul', function () {
    $detector = new FoulDetector(new HandEvaluator);

    $result = $detector->validate([
        'back' => pokerHand(['AS', 'KS', 'QS', 'JS', 'TS']),
        'middle' => pokerHand(['2C', '5D', '7H', '9S', 'JC']),
        'front' => pokerHand(['9C', '9D', '9H']),
    ]);

    expect($result->isFouled())->toBeTrue();
    expect($result->reason)->toContain('middle hand');
});

test('duplicate or missing cards are rejected', function () {
    $detector = new FoulDetector(new HandEvaluator);

    $duplicates = $detector->validate([
        'back' => pokerHand(['AS', 'AS', 'QS', 'JS', 'TS']),
        'middle' => pokerHand(['9H', '8H', '7H', '6H', '5H']),
        'front' => pokerHand(['2C', '2D', '2H']),
    ]);

    expect($duplicates->isFouled())->toBeTrue();
    expect($duplicates->reason)->toContain('duplicate');

    $wrongSize = $detector->validate([
        'back' => pokerHand(['AS', 'KS', 'QS', 'JS']),
        'middle' => pokerHand(['9H', '8H', '7H', '6H', '5H']),
        'front' => pokerHand(['2C', '2D', '2H']),
    ]);

    expect($wrongSize->isFouled())->toBeTrue();
    expect($wrongSize->reason)->toContain('5 cards');
});

test('four players produce 18 comparisons and zero-sum game points', function () {
    $deck = Deck::standard();
    $deal = $deck->deal(4, 13);

    $players = [];

    foreach ($deal as $index => $cards) {
        $players[] = [
            'id' => $index + 1,
            'hands' => [
                'back' => array_slice($cards, 0, 5),
                'middle' => array_slice($cards, 5, 5),
                'front' => array_slice($cards, 10, 3),
            ],
        ];
    }

    $evaluator = new RoundEvaluator(new HandEvaluator, new FoulDetector(new HandEvaluator));
    $result = $evaluator->evaluate($players);

    expect($result->comparisons)->toHaveCount(18);
    expect($result->isZeroSum())->toBeTrue();
    expect($result->totalGamePoints())->toBe(0);

    foreach ($result->comparisons as $comparison) {
        expect($comparison['result'])->toBeIn([-1, 0, 1]);
    }

    foreach ($result->gamePoints() as $points) {
        expect($points)->toBeGreaterThanOrEqual(-9)->toBeLessThanOrEqual(9);
    }
});

test('a fouled player loses all three hands to every opponent', function () {
    // A valid 13-card arrangement: royal flush (back) > straight flush (middle) > trips (front).
    $validHands = [
        'back' => pokerHand(['AS', 'KS', 'QS', 'JS', 'TS']),
        'middle' => pokerHand(['9H', '8H', '7H', '6H', '5H']),
        'front' => pokerHand(['2C', '2D', '2H']),
    ];

    $players = [];

    for ($index = 1; $index <= 4; $index++) {
        $players[] = [
            'id' => $index,
            'fouled' => $index === 1,
            'foul_reason' => $index === 1 ? 'Back hand weaker than middle hand.' : null,
            'hands' => $validHands,
        ];
    }

    $result = (new RoundEvaluator(new HandEvaluator, new FoulDetector(new HandEvaluator)))->evaluate($players);

    $points = $result->gamePoints();

    expect($points[1])->toBe(-9);
    expect($points[2])->toBe(3);
    expect($points[3])->toBe(3);
    expect($points[4])->toBe(3);
    expect(array_sum($points))->toBe(0);
    expect($result->player(1)['is_fouled'])->toBeTrue();
});

test('game points convert to cash points with the configured value and fee', function () {
    $calculator = new SettlementCalculator;
    $points = [1 => 2, 2 => -1, 3 => -2, 4 => 1];

    $lines = $calculator->calculate($points, 1_000);

    expect($lines[1]['php_minor'])->toBe(2_000);
    expect($lines[2]['php_minor'])->toBe(-1_000);
    expect($lines[3]['php_minor'])->toBe(-2_000);
    expect($lines[4]['php_minor'])->toBe(1_000);
    expect(array_sum(array_column($lines, 'php_minor')))->toBe(0);

    $withFees = $calculator->calculate($points, 1_000, 500, feesEnabled: true);
    expect($withFees[1]['fee_minor'])->toBe(500);
    expect($withFees[1]['net_minor'])->toBe(1_500);
    expect($withFees[2]['net_minor'])->toBe(-1_500);

    $withoutFees = $calculator->calculate($points, 1_000, 500, feesEnabled: false);
    expect($withoutFees[1]['fee_minor'])->toBe(0);
});

test('conversion rejects game points that are not zero-sum', function () {
    (new SettlementCalculator)->calculate([1 => 2, 2 => -1, 3 => 0, 4 => 0], 1_000);
})->throws(InvalidArgumentException::class);
