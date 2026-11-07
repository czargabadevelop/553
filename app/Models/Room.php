<?php

namespace App\Models;

use App\Domain\Wallet\Money;
use App\Enums\RoomStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Room extends Model
{
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'name',
        'slug',
        'description',
        'entry_fee_minor',
        'platform_fee_minor',
        'min_players',
        'max_players',
        'status',
        'is_active',
        'sort_order',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => RoomStatus::class,
            'is_active' => 'boolean',
            'entry_fee_minor' => 'integer',
            'platform_fee_minor' => 'integer',
            'min_players' => 'integer',
            'max_players' => 'integer',
        ];
    }

    /** @return HasOne<RoomSetting, $this> */
    public function settings(): HasOne
    {
        return $this->hasOne(RoomSetting::class);
    }

    /** @return HasMany<RoomSeat, $this> */
    public function seats(): HasMany
    {
        return $this->hasMany(RoomSeat::class);
    }

    /** @return HasMany<Game, $this> */
    public function games(): HasMany
    {
        return $this->hasMany(Game::class);
    }

    public function entryFee(): Money
    {
        return Money::fromMinor($this->entry_fee_minor);
    }

    public function platformFee(): Money
    {
        return Money::fromMinor($this->platform_fee_minor);
    }

    /**
     * Total amount a player must be able to reserve to sit down:
     * maximum possible game loss plus the disclosed platform fee.
     */
    public function requiredReservationMinor(int $gamePointValueMinor, int $maxGamePointLoss = 3): int
    {
        return ($maxGamePointLoss * $gamePointValueMinor) + $this->platform_fee_minor;
    }

    public function occupiedSeats(): int
    {
        return $this->seats()->whereIn('status', ['seated', 'ready'])->count();
    }
}
