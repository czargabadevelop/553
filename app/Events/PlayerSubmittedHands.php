<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Broadcasts only "who" submitted and how many are in. The submitted cards
 * are private and must never travel over a shared channel.
 */
class PlayerSubmittedHands implements ShouldBroadcast, ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public int $game_id;

    public int $user_id;

    public int $submitted_count;

    public int $player_count;

    public function __construct(int $gameId, int $userId, int $submittedCount, int $playerCount)
    {
        $this->game_id = $gameId;
        $this->user_id = $userId;
        $this->submitted_count = $submittedCount;
        $this->player_count = $playerCount;
    }

    public function broadcastAs(): string
    {
        return 'game.submitted';
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('game.'.$this->game_id);
    }
}
