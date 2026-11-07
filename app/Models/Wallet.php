<?php

namespace App\Models;

use App\Domain\Wallet\Money;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Wallet extends Model
{
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'user_id',
        'currency',
        'available_minor',
        'reserved_minor',
        'lifetime_deposited_minor',
        'lifetime_withdrawn_minor',
        'lifetime_winnings_minor',
        'lifetime_losses_minor',
        'lifetime_fees_minor',
        'is_test_credit',
    ];

    /**
     * Attribute defaults mirroring the database so freshly created wallets are
     * usable before they are reloaded.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'currency' => 'PHP',
        'available_minor' => 0,
        'reserved_minor' => 0,
        'lifetime_deposited_minor' => 0,
        'lifetime_withdrawn_minor' => 0,
        'lifetime_winnings_minor' => 0,
        'lifetime_losses_minor' => 0,
        'lifetime_fees_minor' => 0,
        'is_test_credit' => true,
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'available_minor' => 'integer',
            'reserved_minor' => 'integer',
            'lifetime_deposited_minor' => 'integer',
            'lifetime_withdrawn_minor' => 'integer',
            'lifetime_winnings_minor' => 'integer',
            'lifetime_losses_minor' => 'integer',
            'lifetime_fees_minor' => 'integer',
            'is_test_credit' => 'boolean',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<WalletReservation, $this> */
    public function reservations(): HasMany
    {
        return $this->hasMany(WalletReservation::class);
    }

    public function available(): Money
    {
        return Money::fromMinor($this->available_minor, $this->currency);
    }

    public function reserved(): Money
    {
        return Money::fromMinor($this->reserved_minor, $this->currency);
    }

    public function total(): Money
    {
        return Money::fromMinor($this->available_minor + $this->reserved_minor, $this->currency);
    }
}
