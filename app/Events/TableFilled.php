<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TableFilled implements ShouldBroadcast, ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public int $room_id;

    public function __construct(int $roomId)
    {
        $this->room_id = $roomId;
    }

    public function broadcastAs(): string
    {
        return 'room.filled';
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('room.'.$this->room_id);
    }
}
