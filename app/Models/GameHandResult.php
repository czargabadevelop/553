<?php

namespace App\Models;

use App\Enums\HandPosition;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One of the 18 pairwise hand comparisons in a four-player game.
 * `result` is 1 when player one wins, -1 when player two wins, 0 on a tie.
 */
class GameHandResult extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'game_id',
        'position',
        'player_one_id',
        'player_two_id',
        'winner_player_id',
        'result',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'position' => HandPosition::class,
            'result' => 'integer',
        ];
    }

    /** @return BelongsTo<Game, $this> */
    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }
}
