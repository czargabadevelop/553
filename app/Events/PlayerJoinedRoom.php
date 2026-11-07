<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PlayerJoinedRoom implements ShouldBroadcast, ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public int $room_id;

    public int $user_id;

    public string $player_name;

    public int $seat_number;

    public function __construct(int $roomId, int $userId, string $playerName, int $seatNumber)
    {
        $this->room_id = $roomId;
        $this->user_id = $userId;
        $this->player_name = $playerName;
        $this->seat_number = $seatNumber;
    }

    public function broadcastAs(): string
    {
        return 'room.joined';
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('room.'.$this->room_id);
    }
}
