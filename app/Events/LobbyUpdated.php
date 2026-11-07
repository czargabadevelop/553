<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Seat counts and room states for the public lobby. No player identities,
 * no cards and no balances: that is why this one may be a public channel.
 */
class LobbyUpdated implements ShouldBroadcast, ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    /** @var list<array{id: int, status: string, seated: int, active_game: int|null}> */
    public array $rooms;

    /**
     * @param  list<array{id: int, status: string, seated: int, active_game: int|null}>  $rooms
     */
    public function __construct(array $rooms)
    {
        $this->rooms = $rooms;
    }

    public function broadcastAs(): string
    {
        return 'lobby.updated';
    }

    public function broadcastOn(): Channel
    {
        return new Channel('lobby');
    }
}
