<?php

namespace App\Http\Controllers\Player;

use App\Domain\Game\GameService;
use App\Domain\Rooms\RoomService;
use App\Domain\Settings\SettingsService;
use App\Enums\RoomSeatStatus;
use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Models\RoomSeat;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class LobbyController extends Controller
{
    public function index(Request $request, SettingsService $settings): Response
    {
        $user = $request->user();
        $pointValue = $settings->integer('game.point_value_minor', 0);
        $maxLoss = $settings->integer('game.max_game_point_loss', 9);

        return Inertia::render('Player/Lobby', [
            'pointValue' => $pointValue,
            'rooms' => Room::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get()
                ->map(function (Room $room) use ($user, $pointValue, $maxLoss) {
                    $seat = $room->seats()
                        ->where('user_id', $user->id)
                        ->whereIn('status', [RoomSeatStatus::Seated->value, RoomSeatStatus::Ready->value])
                        ->first();

                    return [
                        'id' => $room->id,
                        'name' => $room->name,
                        'description' => $room->description,
                        'entry' => $room->entryFee()->toDecimalString(),
                        'platform_fee' => $room->platformFee()->toDecimalString(),
                        'required_reservation' => number_format($room->requiredReservationMinor($pointValue, $maxLoss) / 100, 2),
                        'seated' => $room->seats()->whereIn('status', [RoomSeatStatus::Seated->value, RoomSeatStatus::Ready->value])->count(),
                        'ready' => $room->seats()->where('status', RoomSeatStatus::Ready->value)->count(),
                        'max_players' => $room->max_players,
                        'status' => $room->status->value,
                        'my_seat' => $seat ? [
                            'seat_number' => $seat->seat_number,
                            'status' => $seat->status->value,
                        ] : null,
                        'active_game' => $room->games()
                            ->whereIn('status', ['dealing', 'arranging', 'evaluating'])
                            ->value('id'),
                    ];
                }),
        ]);
    }

    public function join(Request $request, Room $room, RoomService $rooms): RedirectResponse
    {
        try {
            $rooms->join($request->user(), $room);
        } catch (RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Seat reserved. Ready up when the table fills.');
    }

    public function leave(Request $request, Room $room, RoomService $rooms): RedirectResponse
    {
        try {
            $rooms->leave($request->user(), $room);
        } catch (RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'You left the table and your reservation was released.');
    }

    public function ready(Request $request, Room $room, RoomService $rooms, GameService $games): RedirectResponse
    {
        $seat = RoomSeat::query()
            ->where('room_id', $room->id)
            ->where('user_id', $request->user()->id)
            ->whereIn('status', [RoomSeatStatus::Seated->value, RoomSeatStatus::Ready->value])
            ->first();

        if ($seat === null) {
            return back()->with('error', 'Take a seat before readying up.');
        }

        try {
            $rooms->ready($request->user(), $room);
        } catch (RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        // The last player to ready up starts the match for the whole table.
        $game = $games->startIfReady($room);

        if ($game !== null) {
            return redirect()
                ->route('games.show', $game)
                ->with('success', 'All four players are ready — cards dealt.');
        }

        return back()->with('success', 'You are ready.');
    }
}
