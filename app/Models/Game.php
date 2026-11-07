<?php

namespace App\Models;

use App\Enums\GameStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Game extends Model
{
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'code',
        'room_id',
        'status',
        'player_count',
        'deck_fingerprint',
        'settings_snapshot',
        'arrangement_deadline_at',
        'started_at',
        'completed_at',
        'cancelled_at',
        'cancel_reason',
        'settled_at',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => GameStatus::class,
            'settings_snapshot' => 'array',
            'player_count' => 'integer',
            'arrangement_deadline_at' => 'datetime',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'settled_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Room, $this> */
    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    /** @return HasMany<GamePlayer, $this> */
    public function players(): HasMany
    {
        return $this->hasMany(GamePlayer::class)->orderBy('seat_number');
    }

    /** @return HasMany<GameCard, $this> */
    public function cards(): HasMany
    {
        return $this->hasMany(GameCard::class);
    }

    /** @return HasMany<GameHandResult, $this> */
    public function handResults(): HasMany
    {
        return $this->hasMany(GameHandResult::class);
    }

    /** @return HasMany<GamePlayerResult, $this> */
    public function playerResults(): HasMany
    {
        return $this->hasMany(GamePlayerResult::class);
    }

    public function isSettled(): bool
    {
        return $this->settled_at !== null;
    }
}
