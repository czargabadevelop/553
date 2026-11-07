<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PlayerReady implements ShouldBroadcast, ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public int $room_id;

    public int $user_id;

    public string $player_name;

    public bool $ready;

    public function __construct(int $roomId, int $userId, string $playerName, bool $ready)
    {
        $this->room_id = $roomId;
        $this->user_id = $userId;
        $this->player_name = $playerName;
        $this->ready = $ready;
    }

    public function broadcastAs(): string
    {
        return 'room.ready';
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('room.'.$this->room_id);
    }
}
