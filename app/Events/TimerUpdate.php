<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TimerUpdate implements ShouldBroadcast, ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public int $game_id;

    public int $seconds_remaining;

    public function __construct(int $gameId, int $secondsRemaining)
    {
        $this->game_id = $gameId;
        $this->seconds_remaining = $secondsRemaining;
    }

    public function broadcastAs(): string
    {
        return 'game.timer';
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('game.'.$this->game_id);
    }
}
