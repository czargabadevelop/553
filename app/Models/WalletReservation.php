<?php

namespace App\Models;

use App\Enums\ReservationStatus;
use App\Enums\ReservationType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class WalletReservation extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'reference',
        'user_id',
        'wallet_id',
        'type',
        'status',
        'amount_minor',
        'reservable_type',
        'reservable_id',
        'idempotency_key',
        'expires_at',
        'released_at',
        'captured_at',
        'metadata',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'type' => ReservationType::class,
            'status' => ReservationStatus::class,
            'amount_minor' => 'integer',
            'expires_at' => 'datetime',
            'released_at' => 'datetime',
            'captured_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Wallet, $this> */
    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    /** @return MorphTo<Model, $this> */
    public function reservable(): MorphTo
    {
        return $this->morphTo();
    }
}
