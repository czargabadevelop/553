<?php

namespace App\Models;

use App\Domain\Wallet\Money;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlatformFeeSetting extends Model
{
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'room_id',
        'amount_minor',
        'currency',
        'is_enabled',
        'effective_from',
        'created_by',
        'approved_by',
        'approved_at',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'amount_minor' => 'integer',
            'is_enabled' => 'boolean',
            'effective_from' => 'datetime',
            'approved_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Room, $this> */
    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function amount(): Money
    {
        return Money::fromMinor($this->amount_minor, $this->currency);
    }
}
