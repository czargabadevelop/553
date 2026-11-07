<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Game\GameService;
use App\Domain\Rooms\RoomService;
use App\Domain\Settings\SettingsService;
use App\Enums\RoomSeatStatus;
use App\Http\Controllers\Controller;
use App\Models\Room;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class RoomController extends Controller
{
    public function index(SettingsService $settings): JsonResponse
    {
        $pointValue = $settings->integer('game.point_value_minor', 0);
        $maxLoss = $settings->integer('game.max_game_point_loss', 9);

        return response()->json([
            'data' => Room::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get()
                ->map(fn (Room $room) => [
                    'id' => $room->id,
                    'name' => $room->name,
                    'description' => $room->description,
                    'entry_fee_minor' => $room->entry_fee_minor,
                    'platform_fee_minor' => $room->platform_fee_minor,
                    'required_reservation_minor' => $room->requiredReservationMinor($pointValue, $maxLoss),
                    'seated' => $room->seats()->whereIn('status', [RoomSeatStatus::Seated->value, RoomSeatStatus::Ready->value])->count(),
                    'max_players' => $room->max_players,
                    'status' => $room->status->value,
                ]),
        ]);
    }

    public function join(Request $request, Room $room, RoomService $rooms): JsonResponse
    {
        try {
            $seat = $rooms->join($request->user(), $room);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Seat reserved.',
            'seat' => [
                'seat_number' => $seat->seat_number,
                'status' => $seat->status->value,
            ],
        ], 201);
    }

    public function leave(Request $request, Room $room, RoomService $rooms): JsonResponse
    {
        try {
            $rooms->leave($request->user(), $room);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json(['message' => 'Left the table and released the reservation.']);
    }

    public function ready(Request $request, Room $room, RoomService $rooms, GameService $games): JsonResponse
    {
        try {
            $seat = $rooms->ready($request->user(), $room);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        $game = $games->startIfReady($room);

        return response()->json([
            'message' => $game ? 'All four players are ready. Game started.' : 'Ready.',
            'seat' => ['status' => $seat->status->value],
            'game_id' => $game?->id,
        ]);
    }
}
