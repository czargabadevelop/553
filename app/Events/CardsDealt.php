<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Announcement that cards exist. The hand itself is never included: each
 * player loads their own cards from the authorized table page instead.
 */
class CardsDealt implements ShouldBroadcast, ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public int $game_id;

    public int $user_id;

    public function __construct(int $gameId, int $userId)
    {
        $this->game_id = $gameId;
        $this->user_id = $userId;
    }

    public function broadcastAs(): string
    {
        return 'game.cards-dealt';
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('game.'.$this->game_id);
    }
}
