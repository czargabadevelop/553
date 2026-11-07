<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PlayerDisconnected implements ShouldBroadcast, ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public int $game_id;

    public int $user_id;

    public string $player_name;

    public function __construct(int $gameId, int $userId, string $playerName)
    {
        $this->game_id = $gameId;
        $this->user_id = $userId;
        $this->player_name = $playerName;
    }

    public function broadcastAs(): string
    {
        return 'game.player-disconnected';
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('game.'.$this->game_id);
    }
}
