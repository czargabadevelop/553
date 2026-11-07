<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SettlementCompleted implements ShouldBroadcast, ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public int $game_id;

    public int $player_id;

    public int $net_change_minor;

    public function __construct(int $gameId, int $playerId, int $netChangeMinor)
    {
        $this->game_id = $gameId;
        $this->player_id = $playerId;
        $this->net_change_minor = $netChangeMinor;
    }

    public function broadcastAs(): string
    {
        return 'game.settled';
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('game.'.$this->game_id);
    }
}
