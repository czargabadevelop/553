<?php

namespace App\Models;

use App\Domain\Wallet\Money;
use App\Enums\WithdrawalStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Withdrawal extends Model
{
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'reference',
        'user_id',
        'wallet_id',
        'payout_method_id',
        'amount_minor',
        'currency',
        'status',
        'payout_details_encrypted',
        'requested_at',
        'reviewed_at',
        'reviewed_by',
        'processed_at',
        'processed_by',
        'completed_at',
        'rejection_reason',
        'ledger_transaction_id',
        'reservation_id',
        'idempotency_key',
    ];

    /** @var list<string> */
    protected $hidden = ['payout_details_encrypted'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => WithdrawalStatus::class,
            'amount_minor' => 'integer',
            'payout_details_encrypted' => 'encrypted:array',
            'requested_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'processed_at' => 'datetime',
            'completed_at' => 'datetime',
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

    /** @return BelongsTo<PayoutMethod, $this> */
    public function payoutMethod(): BelongsTo
    {
        return $this->belongsTo(PayoutMethod::class);
    }

    /** @return BelongsTo<WalletReservation, $this> */
    public function reservation(): BelongsTo
    {
        return $this->belongsTo(WalletReservation::class);
    }

    public function amount(): Money
    {
        return Money::fromMinor($this->amount_minor, $this->currency);
    }
}
