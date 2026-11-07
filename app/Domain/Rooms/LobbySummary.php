<?php

namespace App\Domain\Rooms;

use App\Enums\GameStatus;
use App\Enums\RoomSeatStatus;
use App\Models\Room;

/**
 * Public, non-sensitive snapshot of every active room. Safe to broadcast on
 * the shared lobby channel: it never contains players, cards or balances.
 */
class LobbySummary
{
    /**
     * @return list<array{id: int, status: string, seated: int, active_game: int|null}>
     */
    public static function build(): array
    {
        return Room::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->map(fn (Room $room) => [
                'id' => $room->id,
                'status' => $room->status->value,
                'seated' => $room->seats()
                    ->whereIn('status', [RoomSeatStatus::Seated->value, RoomSeatStatus::Ready->value])
                    ->count(),
                'ready' => $room->seats()
                    ->where('status', RoomSeatStatus::Ready->value)
                    ->count(),
                'active_game' => $room->games()
                    ->whereIn('status', [
                        GameStatus::Dealing->value,
                        GameStatus::Arranging->value,
                        GameStatus::Evaluating->value,
                    ])
                    ->latest('id')
                    ->value('id'),
            ])
            ->all();
    }
}
