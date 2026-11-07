<?php

namespace App\Models;

use App\Enums\GamePlayerStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class GamePlayer extends Model
{
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'game_id',
        'user_id',
        'seat_number',
        'status',
        'game_points',
        'is_fouled',
        'foul_reason',
        'submitted_at',
        'reservation_id',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => GamePlayerStatus::class,
            'seat_number' => 'integer',
            'game_points' => 'integer',
            'is_fouled' => 'boolean',
            'submitted_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Game, $this> */
    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<GameCard, $this> */
    public function cards(): HasMany
    {
        return $this->hasMany(GameCard::class);
    }

    /** @return HasMany<GameHand, $this> */
    public function hands(): HasMany
    {
        return $this->hasMany(GameHand::class);
    }

    /** @return HasOne<GameHandSubmission, $this> */
    public function submission(): HasOne
    {
        return $this->hasOne(GameHandSubmission::class);
    }

    /** @return HasOne<GamePlayerResult, $this> */
    public function result(): HasOne
    {
        return $this->hasOne(GamePlayerResult::class);
    }
}
