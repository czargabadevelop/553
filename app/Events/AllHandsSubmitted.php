<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AllHandsSubmitted implements ShouldBroadcast, ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public int $game_id;

    public function __construct(int $gameId)
    {
        $this->game_id = $gameId;
    }

    public function broadcastAs(): string
    {
        return 'game.all-submitted';
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('game.'.$this->game_id);
    }
}
