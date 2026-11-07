<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GameHandSubmission extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'game_player_id',
        'game_id',
        'hands',
        'is_valid',
        'foul_reason',
        'submitted_at',
        'ip_address',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'hands' => 'array',
            'is_valid' => 'boolean',
            'submitted_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<GamePlayer, $this> */
    public function player(): BelongsTo
    {
        return $this->belongsTo(GamePlayer::class, 'game_player_id');
    }
}
