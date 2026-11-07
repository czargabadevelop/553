<?php

use App\Models\Game;
use App\Models\Room;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast channels
|--------------------------------------------------------------------------
|
| Private channels only. A player may subscribe to a room they are seated in
| and to a game they participate in. Private cards are never broadcast.
|
*/

Broadcast::channel('room.{roomId}', function ($user, int $roomId) {
    $room = Room::query()->find($roomId);

    if ($room === null) {
        return false;
    }

    return $room->seats()->where('user_id', $user->id)->whereNull('left_at')->exists()
        || $user->hasRole('super_administrator')
        || $user->hasRole('game_administrator');
});

Broadcast::channel('game.{gameId}', function ($user, int $gameId) {
    $game = Game::query()->find($gameId);

    if ($game === null) {
        return false;
    }

    return $game->players()->where('user_id', $user->id)->exists()
        || $user->hasRole('super_administrator')
        || $user->hasRole('game_administrator');
});
