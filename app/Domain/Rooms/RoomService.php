<?php

namespace App\Domain\Rooms;

use App\Domain\Audit\AuditLogger;
use App\Domain\Settings\FeatureFlagService;
use App\Domain\Settings\SettingsService;
use App\Domain\Wallet\WalletService;
use App\Enums\ReservationType;
use App\Enums\RoomSeatStatus;
use App\Enums\RoomStatus;
use App\Events\LobbyUpdated;
use App\Events\PlayerJoinedRoom;
use App\Events\PlayerLeftRoom;
use App\Events\PlayerReady;
use App\Events\TableFilled;
use App\Models\Room;
use App\Models\RoomSeat;
use App\Models\User;
use App\Models\WalletReservation;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Seats players, reserves their entry cover and enforces the room lifecycle.
 * A game can never start without four seated, ready, funded players.
 */
class RoomService
{
    public function __construct(
        private readonly WalletService $wallets,
        private readonly SettingsService $settings,
        private readonly FeatureFlagService $flags,
        private readonly AuditLogger $audit,
    ) {}

    public function join(User $user, Room $room): RoomSeat
    {
        return DB::transaction(function () use ($user, $room) {
            $room = Room::query()->whereKey($room->id)->lockForUpdate()->firstOrFail();

            if (! $room->is_active || ! $room->status->acceptsPlayers()) {
                throw new RuntimeException('This room is not accepting players.');
            }

            if ($user->isSuspended()) {
                throw new RuntimeException('Suspended accounts cannot join rooms.');
            }

            $alreadySeated = $room->seats()
                ->where('user_id', $user->id)
                ->whereIn('status', [RoomSeatStatus::Seated->value, RoomSeatStatus::Ready->value])
                ->exists();

            if ($alreadySeated) {
                throw new RuntimeException('You are already seated at this table.');
            }

            $occupied = $room->seats()
                ->whereIn('status', [RoomSeatStatus::Seated->value, RoomSeatStatus::Ready->value])
                ->lockForUpdate()
                ->get();

            if ($occupied->count() >= $room->max_players) {
                throw new RuntimeException('This table is full.');
            }

            $wallet = $this->wallets->walletFor($user);

            if ($this->flags->disabled(FeatureFlagService::REAL_MONEY_ROOMS) && ! $wallet->is_test_credit) {
                throw new RuntimeException('Real-money rooms are currently disabled.');
            }

            $reservation = $this->reserveEntry($user, $room);

            $takenSeats = $occupied->pluck('seat_number')->all();
            $seatNumber = null;

            for ($candidate = 1; $candidate <= $room->max_players; $candidate++) {
                if (! in_array($candidate, $takenSeats, true)) {
                    $seatNumber = $candidate;
                    break;
                }
            }

            if ($seatNumber === null) {
                throw new RuntimeException('No seat is available at this table.');
            }

            $seat = RoomSeat::create([
                'room_id' => $room->id,
                'user_id' => $user->id,
                'seat_number' => $seatNumber,
                'status' => RoomSeatStatus::Seated,
                'joined_at' => now(),
                'reservation_id' => $reservation?->id,
            ]);

            $room->forceFill([
                'status' => $occupied->count() + 1 >= $room->max_players ? RoomStatus::Ready : RoomStatus::Filling,
            ])->save();

            $this->audit->log(
                action: 'room.player_joined',
                subject: $room,
                newValues: ['room_id' => $room->id, 'seat' => $seatNumber, 'user_id' => $user->id],
                actor: $user,
            );

            event(new PlayerJoinedRoom($room->id, $user->id, $user->displayName(), $seatNumber));

            if ($room->status === RoomStatus::Ready) {
                event(new TableFilled($room->id));
            }

            event(new LobbyUpdated(LobbySummary::build()));

            return $seat;
        });
    }

    public function leave(User $user, Room $room): void
    {
        DB::transaction(function () use ($user, $room) {
            $room = Room::query()->whereKey($room->id)->lockForUpdate()->firstOrFail();

            /** @var RoomSeat|null $seat */
            $seat = $room->seats()
                ->where('user_id', $user->id)
                ->whereIn('status', [RoomSeatStatus::Seated->value, RoomSeatStatus::Ready->value])
                ->lockForUpdate()
                ->first();

            if ($seat === null) {
                throw new RuntimeException('You are not seated at this table.');
            }

            if ($room->status === RoomStatus::InProgress) {
                throw new RuntimeException('You cannot leave a game that is in progress.');
            }

            if ($seat->reservation_id !== null) {
                $reservation = WalletReservation::query()->find($seat->reservation_id);

                if ($reservation !== null && $reservation->status->isOpen()) {
                    $this->wallets->release($reservation, 'Room entry cancelled');
                }
            }

            // Seats are unique per (room, seat_number), so an abandoned seat has
            // to be removed rather than kept as a "left" row or the number can
            // never be taken again. Game history lives on game_players.
            $seatNumber = $seat->seat_number;
            $seat->delete();

            $this->audit->log(
                action: 'room.player_left',
                subject: $room,
                oldValues: ['room_id' => $room->id, 'seat' => $seatNumber, 'user_id' => $user->id],
                actor: $user,
            );

            $remaining = $room->seats()
                ->whereIn('status', [RoomSeatStatus::Seated->value, RoomSeatStatus::Ready->value])
                ->count();

            $room->forceFill([
                'status' => $remaining === 0 ? RoomStatus::Waiting : RoomStatus::Filling,
            ])->save();

            event(new PlayerLeftRoom($room->id, $user->id, $user->displayName(), $seatNumber));
            event(new LobbyUpdated(LobbySummary::build()));
        });
    }

    /**
     * Mark a seated player ready. A fresh entry reservation is taken for each
     * round because settlement releases the previous one.
     */
    public function ready(User $user, Room $room): RoomSeat
    {
        return DB::transaction(function () use ($user, $room) {
            $room = Room::query()->whereKey($room->id)->lockForUpdate()->firstOrFail();

            /** @var RoomSeat $seat */
            $seat = $room->seats()
                ->where('user_id', $user->id)
                ->whereIn('status', [RoomSeatStatus::Seated->value, RoomSeatStatus::Ready->value])
                ->lockForUpdate()
                ->firstOrFail();

            // Ready is idempotent: repeated clicks (or a stale double post)
            // must not reserve the entry twice.
            if ($seat->status === RoomSeatStatus::Ready) {
                return $seat;
            }

            $existing = $seat->reservation_id !== null
                ? WalletReservation::query()->whereKey($seat->reservation_id)->lockForUpdate()->first()
                : null;

            if ($existing === null || ! $existing->status->isOpen()) {
                $reservation = $this->reserveEntry($user, $room);
                $seat->reservation_id = $reservation?->id;
            }

            $seat->forceFill([
                'status' => RoomSeatStatus::Ready,
                'ready_at' => now(),
            ])->save();

            event(new PlayerReady($room->id, $user->id, $user->displayName(), true));
            event(new LobbyUpdated(LobbySummary::build()));

            return $seat;
        });
    }

    /**
     * Return a finished table to the lobby without evicting anybody: every
     * seat stays taken but needs a fresh ready. Players who leave are removed
     * and their money released; everyone else can play another round.
     */
    public function resetForNextRound(Room $room): void
    {
        DB::transaction(function () use ($room) {
            $room = Room::query()->whereKey($room->id)->lockForUpdate()->firstOrFail();

            $seats = $room->seats()
                ->whereIn('status', [RoomSeatStatus::Seated->value, RoomSeatStatus::Ready->value])
                ->lockForUpdate()
                ->get();

            foreach ($seats as $seat) {
                if ($seat->reservation_id !== null) {
                    $reservation = WalletReservation::query()->find($seat->reservation_id);

                    if ($reservation !== null && $reservation->status->isOpen()) {
                        $this->wallets->release($reservation, 'Round completed');
                    }
                }

                $seat->forceFill([
                    'status' => RoomSeatStatus::Seated,
                    'ready_at' => null,
                    'reservation_id' => null,
                ])->save();
            }

            $room->forceFill([
                'status' => $seats->isEmpty() ? RoomStatus::Waiting : RoomStatus::Filling,
            ])->save();

            event(new LobbyUpdated(LobbySummary::build()));
        });
    }

    public function cancel(Room $room, string $reason): void
    {
        DB::transaction(function () use ($room) {
            $room = Room::query()->whereKey($room->id)->lockForUpdate()->firstOrFail();

            $seats = $room->seats()
                ->whereIn('status', [RoomSeatStatus::Seated->value, RoomSeatStatus::Ready->value])
                ->lockForUpdate()
                ->get();

            foreach ($seats as $seat) {
                if ($seat->reservation_id !== null) {
                    $reservation = WalletReservation::query()->find($seat->reservation_id);

                    if ($reservation !== null && $reservation->status->isOpen()) {
                        $this->wallets->release($reservation, 'Match cancelled');
                    }
                }

                $seat->delete();
            }

            $room->forceFill(['status' => RoomStatus::Cancelled, 'is_active' => false])->save();

            event(new LobbyUpdated(LobbySummary::build()));
        });
    }

    /**
     * Whether the table is ready to start: four players, all ready, all funded.
     */
    public function canStart(Room $room): bool
    {
        $seats = $room->seats()
            ->whereIn('status', [RoomSeatStatus::Seated->value, RoomSeatStatus::Ready->value])
            ->get();

        if ($seats->count() !== $room->max_players) {
            return false;
        }

        foreach ($seats as $seat) {
            if ($seat->status !== RoomSeatStatus::Ready) {
                return false;
            }

            if ($seat->reservation_id === null) {
                continue;
            }

            $reservation = WalletReservation::query()->find($seat->reservation_id);

            if ($reservation === null || ! $reservation->status->isOpen()) {
                return false;
            }
        }

        return true;
    }

    private function reserveEntry(User $user, Room $room): ?WalletReservation
    {
        $requiredMinor = $room->requiredReservationMinor(
            $this->settings->integer('game.point_value_minor', 0),
            $this->settings->integer('game.max_game_point_loss', 9),
        );

        if ($requiredMinor <= 0) {
            return null;
        }

        return $this->wallets->reserve(
            user: $user,
            amountMinor: $requiredMinor,
            type: ReservationType::GameEntry,
            reservable: $room,
            // Scope the key to the current round: replaying the same request is
            // idempotent, but a later round gets a fresh reservation instead of
            // the already released one.
            idempotencyKey: 'room-entry:'.$room->id.':'.$user->id.':'.$room->games()->count(),
            metadata: ['room_id' => $room->id],
        );
    }
}
