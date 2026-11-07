<?php

use App\Domain\Game\Card;
use App\Domain\Game\GameService;
use App\Domain\Rooms\RoomService;
use App\Domain\Wallet\Exceptions\InsufficientFundsException;
use App\Domain\Wallet\SettlementService;
use App\Domain\Wallet\WalletService;
use App\Enums\GameStatus;
use App\Enums\LedgerAccount;
use App\Enums\LedgerTransactionType;
use App\Enums\ReservationStatus;
use App\Enums\ReservationType;
use App\Enums\RoomSeatStatus;
use App\Enums\RoomStatus;
use App\Events\GameStarted;
use App\Events\LobbyUpdated;
use App\Events\PlayerReady;
use App\Events\PlayerSubmittedHands;
use App\Models\Game;
use App\Models\GameCard;
use App\Models\GamePlayer;
use App\Models\LedgerTransaction;
use App\Models\Room;
use App\Models\RoomSeat;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletLedgerEntry;
use Database\Seeders\FeatureFlagSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoomSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed([RolePermissionSeeder::class, SettingsSeeder::class, FeatureFlagSeeder::class, RoomSeeder::class]);
});

function fundedUser(int $minor = 100_000): User
{
    $user = User::factory()->create(['username' => 'p'.fake()->unique()->numberBetween(1, 99999)]);

    Wallet::create([
        'user_id' => $user->id,
        'currency' => 'PHP',
        'available_minor' => $minor,
        'is_test_credit' => true,
    ]);

    return $user;
}

function dealtHandCodes(Game $game, User $user): array
{
    $player = $game->players()->where('user_id', $user->id)->firstOrFail();

    $codes = GameCard::query()
        ->where('game_player_id', $player->id)
        ->orderBy('deal_order')
        ->get()
        ->map(fn (GameCard $card) => Card::RANK_LABELS[$card->card_rank].$card->card_suit)
        ->all();

    return [
        'back' => array_slice($codes, 0, 5),
        'middle' => array_slice($codes, 5, 5),
        'front' => array_slice($codes, 10, 3),
    ];
}

test('reserving funds moves money from available to reserved through the ledger', function () {
    $user = fundedUser(50_000);
    $wallet = $user->wallet;

    $reservation = app(WalletService::class)->reserve(
        $user,
        9_000,
        ReservationType::GameEntry,
        idempotencyKey: 'test-entry-1',
    );

    expect($reservation->status)->toBe(ReservationStatus::Held);

    $wallet->refresh();
    expect($wallet->available_minor)->toBe(41_000);
    expect($wallet->reserved_minor)->toBe(9_000);

    $transaction = LedgerTransaction::query()->where('type', LedgerTransactionType::Reservation)->firstOrFail();
    expect($transaction->isBalanced())->toBeTrue();
});

test('a reservation cannot exceed the available balance', function () {
    $user = fundedUser(1_000);

    app(WalletService::class)->reserve($user, 9_000, ReservationType::GameEntry);
})->throws(InsufficientFundsException::class);

test('reservations are idempotent and releasing returns the funds', function () {
    $user = fundedUser(50_000);
    $wallets = app(WalletService::class);

    $first = $wallets->reserve($user, 9_000, ReservationType::GameEntry, idempotencyKey: 'dup-key');
    $second = $wallets->reserve($user, 9_000, ReservationType::GameEntry, idempotencyKey: 'dup-key');

    expect($second->id)->toBe($first->id);
    expect($user->wallet->refresh()->reserved_minor)->toBe(9_000);

    $wallets->release($first);

    $wallet = $user->wallet->refresh();
    expect($wallet->available_minor)->toBe(50_000);
    expect($wallet->reserved_minor)->toBe(0);
});

test('joining a room reserves the maximum loss and leaving releases it', function () {
    $user = fundedUser(100_000);
    $room = Room::query()->where('slug', 'bronze-table')->firstOrFail();

    $rooms = app(RoomService::class);
    $seat = $rooms->join($user, $room);

    expect($seat->seat_number)->toBe(1);
    expect($seat->status)->toBe(RoomSeatStatus::Seated);
    expect($user->wallet->refresh()->reserved_minor)->toBe(9_000);
    expect($room->refresh()->status)->toBe(RoomStatus::Filling);

    $rooms->leave($user, $room);

    // The seat row is removed so the same seat number can be taken again;
    // the released reservation is the record of the abandoned entry.
    expect(RoomSeat::query()->where('room_id', $room->id)->where('user_id', $user->id)->exists())->toBeFalse();
    expect($user->wallet->refresh()->reserved_minor)->toBe(0);
    expect($user->wallet->available_minor)->toBe(100_000);
    expect($room->refresh()->status)->toBe(RoomStatus::Waiting);

    // Taking the same seat again works (unique room_id + seat_number).
    $again = $rooms->join($user, $room->refresh());

    expect($again->seat_number)->toBe(1);
});

test('a game cannot start until four players are seated and ready', function () {
    $room = Room::query()->where('slug', 'bronze-table')->firstOrFail();
    $rooms = app(RoomService::class);
    $games = app(GameService::class);

    $players = collect(range(1, 3))->map(fn () => fundedUser());

    foreach ($players as $player) {
        $rooms->join($player, $room);
        $rooms->ready($player, $room);
    }

    expect(fn () => $games->start($room))->toThrow(RuntimeException::class);
});

test('a full four-player game evaluates, settles and stays zero-sum', function () {
    $room = Room::query()->where('slug', 'bronze-table')->firstOrFail();
    $rooms = app(RoomService::class);
    $games = app(GameService::class);

    $users = collect(range(1, 4))->map(fn () => fundedUser(100_000));

    foreach ($users as $user) {
        $rooms->join($user, $room);
        $rooms->ready($user, $room);
    }

    expect($rooms->canStart($room))->toBeTrue();

    $game = $games->start($room);

    expect($game->status)->toBe(GameStatus::Arranging);
    expect($game->players)->toHaveCount(4);
    expect(GameCard::query()->where('game_id', $game->id)->count())->toBe(52);
    expect($room->refresh()->status)->toBe(RoomStatus::InProgress);

    foreach ($users as $user) {
        $games->submit($game, $user, dealtHandCodes($game->refresh(), $user));
    }

    $game->refresh();

    expect($game->status)->toBe(GameStatus::Completed);
    expect($game->handResults()->count())->toBe(18);
    expect((int) $game->playerResults()->sum('game_points'))->toBe(0);

    // Test credits settle even while real-money settlement stays flagged off.
    expect($game->isSettled())->toBeTrue();
    expect($game->playerResults()->count())->toBe(4);

    foreach (LedgerTransaction::query()->with('entries')->get() as $transaction) {
        expect($transaction->isBalanced())->toBeTrue();
    }
});

test('hands cannot be resubmitted and must match the dealt cards', function () {
    $room = Room::query()->where('slug', 'bronze-table')->firstOrFail();
    $rooms = app(RoomService::class);
    $games = app(GameService::class);

    $users = collect(range(1, 4))->map(fn () => fundedUser(100_000));

    foreach ($users as $user) {
        $rooms->join($user, $room);
        $rooms->ready($user, $room);
    }

    $game = $games->start($room);
    $hands = dealtHandCodes($game, $users->first());

    expect(fn () => $games->submit($game, $users->first(), [
        'back' => ['AS', 'KS', 'QS', 'JS', 'TS'],
        'middle' => $hands['middle'],
        'front' => $hands['front'],
    ]))->toThrow(RuntimeException::class);

    $games->submit($game, $users->first(), $hands);

    expect(fn () => $games->submit($game->refresh(), $users->first(), $hands))
        ->toThrow(RuntimeException::class);
});

test('duplicate settlement is prevented by the idempotency guard', function () {
    $room = Room::query()->where('slug', 'bronze-table')->firstOrFail();
    $rooms = app(RoomService::class);
    $games = app(GameService::class);
    $settlements = app(SettlementService::class);

    $users = collect(range(1, 4))->map(fn () => fundedUser(100_000));

    foreach ($users as $user) {
        $rooms->join($user, $room);
        $rooms->ready($user, $room);
    }

    $game = $games->start($room);

    foreach ($users as $user) {
        $games->submit($game, $user, dealtHandCodes($game->refresh(), $user));
    }

    $settlementCount = LedgerTransaction::query()->where('type', LedgerTransactionType::GameSettlement)->count();

    $settled = $settlements->settle($game->refresh(), []);

    expect($settled)->toBeFalse();
    expect(LedgerTransaction::query()->where('type', LedgerTransactionType::GameSettlement)->count())->toBe($settlementCount);
    expect(LedgerTransaction::query()->where('type', LedgerTransactionType::ReservationRelease)->count())->toBe(4);
});

test('settlement moves exact amounts between wallets and books the platform fee', function () {
    $room = Room::query()->where('slug', 'bronze-table')->firstOrFail();
    $users = collect(range(1, 4))->map(fn () => fundedUser(50_000));

    $game = Game::create([
        'code' => 'G-SETTLE1',
        'room_id' => $room->id,
        'status' => GameStatus::Completed,
        'player_count' => 4,
    ]);

    $players = $users->values()->map(fn (User $user, int $index) => GamePlayer::create([
        'game_id' => $game->id,
        'user_id' => $user->id,
        'seat_number' => $index + 1,
    ]));

    // Game points 2 / -1 / -2 / 1 at PHP 10 per point, with a PHP 1 fee each.
    $lines = [
        $players[0]->id => ['game_points' => 2, 'php_minor' => 2_000, 'fee_minor' => 100, 'net_minor' => 1_900],
        $players[1]->id => ['game_points' => -1, 'php_minor' => -1_000, 'fee_minor' => 100, 'net_minor' => -1_100],
        $players[2]->id => ['game_points' => -2, 'php_minor' => -2_000, 'fee_minor' => 100, 'net_minor' => -2_100],
        $players[3]->id => ['game_points' => 1, 'php_minor' => 1_000, 'fee_minor' => 100, 'net_minor' => 900],
    ];

    expect(app(SettlementService::class)->settle($game, $lines))->toBeTrue();

    expect($users[0]->wallet->refresh()->available_minor)->toBe(51_900);
    expect($users[1]->wallet->refresh()->available_minor)->toBe(48_900);
    expect($users[2]->wallet->refresh()->available_minor)->toBe(47_900);
    expect($users[3]->wallet->refresh()->available_minor)->toBe(50_900);

    $transaction = LedgerTransaction::query()
        ->where('idempotency_key', 'game-settlement:'.$game->id)
        ->with('entries')
        ->firstOrFail();

    expect($transaction->isBalanced())->toBeTrue();
    expect((int) $transaction->entries->sum('debit_minor'))->toBe(3_200);
    expect((int) $transaction->entries->sum('credit_minor'))->toBe(3_200);
    expect((int) $users[0]->wallet->refresh()->lifetime_fees_minor)->toBe(100);
    expect($game->refresh()->isSettled())->toBeTrue();
});

test('reservation and settlement entries use the expected ledger accounts', function () {
    $room = Room::query()->where('slug', 'bronze-table')->firstOrFail();
    $rooms = app(RoomService::class);

    $user = fundedUser(100_000);
    $rooms->join($user, $room);

    $entry = WalletLedgerEntry::query()->where('wallet_id', $user->wallet->id)->firstOrFail();

    expect($entry->account)->toBeIn([LedgerAccount::UserAvailable, LedgerAccount::UserReserved]);
});

test('the fourth player to ready up automatically starts the game', function () {
    $room = Room::query()->where('slug', 'bronze-table')->firstOrFail();
    [$first, $second, $third, $fourth] = collect(range(1, 4))->map(fn () => fundedUser(100_000))->all();

    foreach ([$first, $second, $third, $fourth] as $user) {
        $this->actingAs($user)->post(route('lobby.join', $room));
    }

    foreach ([$first, $second, $third] as $user) {
        $this->actingAs($user)->post(route('lobby.ready', $room));
    }

    expect(Game::query()->count())->toBe(0);

    $this->actingAs($fourth)->post(route('lobby.ready', $room))->assertRedirect();

    $game = Game::query()->firstOrFail();

    expect($game->players()->count())->toBe(4);
    expect($game->status)->toBe(GameStatus::Arranging);
    expect($room->refresh()->status)->toBe(RoomStatus::InProgress);
    expect($this->actingAs($fourth)->get(route('games.show', $game))->status())->toBe(200);
});

test('a finished game keeps the table together for another round', function () {
    $room = Room::query()->where('slug', 'bronze-table')->firstOrFail();
    $rooms = app(RoomService::class);
    $games = app(GameService::class);

    $users = collect(range(1, 4))->map(fn () => fundedUser(100_000));

    foreach ($users as $user) {
        $rooms->join($user, $room);
        $rooms->ready($user, $room);
    }

    $game = $games->start($room);

    foreach ($users as $user) {
        $games->submit($game->refresh(), $user, dealtHandCodes($game->refresh(), $user));
    }

    $room->refresh();

    expect($game->refresh()->status)->toBe(GameStatus::Completed);
    expect($room->status)->toBe(RoomStatus::Filling);

    // Seats stay taken, but everyone must ready up again.
    $seatStates = $room->seats()->orderBy('seat_number')->get();

    expect($seatStates)->toHaveCount(4);
    expect($seatStates->every(fn ($seat) => $seat->status === RoomSeatStatus::Seated))->toBeTrue();
    expect($seatStates->every(fn ($seat) => $seat->ready_at === null))->toBeTrue();

    // No re-join needed: the same four ready up and a second game starts.
    foreach ($users as $user) {
        $rooms->ready($user, $room->refresh());
    }

    expect($rooms->canStart($room->refresh()))->toBeTrue();

    $second = $games->startIfReady($room->refresh());

    expect($second)->not->toBeNull();
    expect($second->players()->count())->toBe(4);
    expect($second->id)->not->toBe($game->id);
});

test('broadcast events are private and never carry cards', function () {
    Event::fake();

    $room = Room::query()->where('slug', 'bronze-table')->firstOrFail();
    $rooms = app(RoomService::class);
    $games = app(GameService::class);

    $users = collect(range(1, 4))->map(fn () => fundedUser(100_000));

    foreach ($users as $user) {
        $rooms->join($user, $room);
        $rooms->ready($user, $room);
    }

    $game = $games->start($room);

    foreach ($users as $user) {
        $games->submit($game->refresh(), $user, dealtHandCodes($game->refresh(), $user));
    }

    Event::assertDispatched(PlayerReady::class, fn (PlayerReady $event) => $event->broadcastOn() instanceof PrivateChannel);
    Event::assertDispatched(GameStarted::class, fn (GameStarted $event) => $event->broadcastOn() instanceof PrivateChannel);

    Event::assertDispatched(PlayerSubmittedHands::class, function (PlayerSubmittedHands $event) {
        return $event->broadcastOn() instanceof PrivateChannel
            && ! property_exists($event, 'hand_submissions')
            && ! property_exists($event, 'hands');
    });

    // The lobby channel is public on purpose, but only carries seat summaries.
    Event::assertDispatched(LobbyUpdated::class, function (LobbyUpdated $event) {
        $payload = json_encode($event->rooms);

        return $event->broadcastOn() instanceof Channel
            && ! str_contains($payload, 'card')
            && ! str_contains($payload, 'hands');
    });
});

test('a submitted hand may contain a ten card', function () {
    $room = Room::query()->where('slug', 'bronze-table')->firstOrFail();
    $rooms = app(RoomService::class);
    $games = app(GameService::class);

    $users = collect(range(1, 4))->map(fn () => fundedUser(100_000));

    foreach ($users as $user) {
        $rooms->join($user, $room);
        $rooms->ready($user, $room);
    }

    $game = $games->start($room);

    // "10s" is three characters; a length-based validation rule used to reject
    // every player holding any ten. This must pass validation and reach the
    // domain layer, which then rejects it only because the cards were not dealt.
    $cards = ['10s', 'As', 'Ks', 'Qs', 'Js', '9h', '8h', '7h', '6h', '5h', '4d', '3c', '2c'];

    $response = $this->actingAs($users->first())->post(route('games.submit', $game), [
        'back' => array_slice($cards, 0, 5),
        'middle' => array_slice($cards, 5, 5),
        'front' => array_slice($cards, 10, 3),
    ]);

    $response->assertSessionHasNoErrors();
    $response->assertSessionHas('error', 'The submitted cards do not match the cards that were dealt to you.');
});

test('an expired arrangement auto-fouls the missing player and completes the round', function () {
    $room = Room::query()->where('slug', 'bronze-table')->firstOrFail();
    $rooms = app(RoomService::class);
    $games = app(GameService::class);

    $users = collect(range(1, 4))->map(fn () => fundedUser(100_000));

    foreach ($users as $user) {
        $rooms->join($user, $room);
        $rooms->ready($user, $room);
    }

    $game = $games->start($room);

    foreach ($users->take(3) as $user) {
        $games->submit($game->refresh(), $user, dealtHandCodes($game->refresh(), $user));
    }

    // Nothing happens while the table still has time on the clock.
    expect($games->resolveDeadline($game->refresh()))->toBeNull();
    expect($game->refresh()->status)->toBe(GameStatus::Arranging);

    $game->forceFill(['arrangement_deadline_at' => now()->subSecond()])->save();

    $result = $games->resolveDeadline($game->refresh());

    expect($result)->not->toBeNull();

    $game->refresh();

    expect($game->status)->toBe(GameStatus::Completed);

    $timedOut = $game->players()->where('user_id', $users->last()->id)->firstOrFail();

    expect($timedOut->is_fouled)->toBeTrue();
    expect($timedOut->foul_reason)->toBe(GameService::TIMEOUT_FOUL_REASON);
    expect($timedOut->submission)->not->toBeNull();
    expect($timedOut->submission->is_valid)->toBeFalse();
    expect((int) $game->playerResults()->sum('game_points'))->toBe(0);
    expect($room->refresh()->status)->toBe(RoomStatus::Filling);

    // Repeated sweeps and late clicks must not evaluate the game twice.
    expect($games->resolveDeadline($game->refresh()))->toBeNull();
    expect($game->handResults()->count())->toBe(18);
});

test('a hand submitted after the deadline is rejected', function () {
    $room = Room::query()->where('slug', 'bronze-table')->firstOrFail();
    $rooms = app(RoomService::class);
    $games = app(GameService::class);

    $users = collect(range(1, 4))->map(fn () => fundedUser(100_000));

    foreach ($users as $user) {
        $rooms->join($user, $room);
        $rooms->ready($user, $room);
    }

    $game = $games->start($room);
    $game->forceFill(['arrangement_deadline_at' => now()->subSecond()])->save();

    $hands = dealtHandCodes($game->refresh(), $users->first());

    expect(fn () => $games->submit($game->refresh(), $users->first(), $hands))
        ->toThrow(RuntimeException::class, 'The arrangement deadline has passed.');
});

test('a completed game returns the table to the lobby even when settlement is blocked', function () {
    $room = Room::query()->where('slug', 'bronze-table')->firstOrFail();
    $rooms = app(RoomService::class);

    $users = collect(range(1, 4))->map(fn () => fundedUser(100_000));

    $this->mock(SettlementService::class, function ($mock) {
        $mock->shouldReceive('settle')->once()->andReturn(false);
    });

    $games = app(GameService::class);

    foreach ($users as $user) {
        $rooms->join($user, $room);
        $rooms->ready($user, $room);
    }

    $game = $games->start($room);

    foreach ($users as $user) {
        $games->submit($game->refresh(), $user, dealtHandCodes($game->refresh(), $user));
    }

    $game->refresh();

    expect($game->status)->toBe(GameStatus::Completed);
    expect($game->isSettled())->toBeFalse();
    expect($room->refresh()->status)->toBe(RoomStatus::Filling);

    // Nobody stays trapped in an in-progress room, and no funds stay reserved.
    expect($users->first()->wallet->refresh()->reserved_minor)->toBe(0);
});

test('the scheduler sweep resolves an abandoned table', function () {
    $room = Room::query()->where('slug', 'bronze-table')->firstOrFail();
    $rooms = app(RoomService::class);

    $users = collect(range(1, 4))->map(fn () => fundedUser(100_000));

    foreach ($users as $user) {
        $rooms->join($user, $room);
        $rooms->ready($user, $room);
    }

    $game = app(GameService::class)->start($room);
    $game->forceFill(['arrangement_deadline_at' => now()->subMinute()])->save();

    $this->artisan('games:resolve-expired')->assertSuccessful();

    $game->refresh();

    expect($game->status)->toBe(GameStatus::Completed);
    expect($game->players()->where('is_fouled', true)->count())->toBe(4);
    expect($room->refresh()->status)->toBe(RoomStatus::Filling);
});

test('only a seated player can trigger timeout resolution and never before the deadline', function () {
    $room = Room::query()->where('slug', 'bronze-table')->firstOrFail();
    $rooms = app(RoomService::class);

    $users = collect(range(1, 4))->map(fn () => fundedUser(100_000));
    $outsider = fundedUser(100_000);

    foreach ($users as $user) {
        $rooms->join($user, $room);
        $rooms->ready($user, $room);
    }

    $game = app(GameService::class)->start($room);

    $this->actingAs($outsider)
        ->post(route('games.timeout', $game))
        ->assertForbidden();

    // A seated player cannot force resolution while the clock is still running.
    $this->actingAs($users->first())
        ->post(route('games.timeout', $game))
        ->assertRedirect();

    expect($game->refresh()->status)->toBe(GameStatus::Arranging);
});
