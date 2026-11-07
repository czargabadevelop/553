<?php

namespace App\Models;

use App\Domain\Wallet\Money;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GamePlayerResult extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'game_id',
        'game_player_id',
        'game_points',
        'php_equivalent_minor',
        'fee_minor',
        'net_minor',
        'is_fouled',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'game_points' => 'integer',
            'php_equivalent_minor' => 'integer',
            'fee_minor' => 'integer',
            'net_minor' => 'integer',
            'is_fouled' => 'boolean',
        ];
    }

    /** @return BelongsTo<GamePlayer, $this> */
    public function player(): BelongsTo
    {
        return $this->belongsTo(GamePlayer::class, 'game_player_id');
    }

    public function phpEquivalent(): Money
    {
        return Money::fromMinor($this->php_equivalent_minor);
    }

    public function net(): Money
    {
        return Money::fromMinor($this->net_minor);
    }
}
