<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GameCard extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'game_id',
        'game_player_id',
        'card_rank',
        'card_suit',
        'position',
        'deal_order',
        'revealed_at',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'card_rank' => 'integer',
            'deal_order' => 'integer',
            'revealed_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Game, $this> */
    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    /** @return BelongsTo<GamePlayer, $this> */
    public function player(): BelongsTo
    {
        return $this->belongsTo(GamePlayer::class, 'game_player_id');
    }
}
