<?php

namespace App\Models;

use App\Enums\HandCategory;
use App\Enums\HandPosition;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GameHand extends Model
{
    /** @var list<string> */
    protected $fillable = ['game_player_id', 'position', 'category', 'strength_key', 'card_count'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'position' => HandPosition::class,
            'category' => HandCategory::class,
            'card_count' => 'integer',
        ];
    }

    /** @return BelongsTo<GamePlayer, $this> */
    public function player(): BelongsTo
    {
        return $this->belongsTo(GamePlayer::class, 'game_player_id');
    }
}
