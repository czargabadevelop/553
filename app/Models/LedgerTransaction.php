<?php

namespace App\Models;

use App\Enums\LedgerTransactionType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Immutable ledger transaction. Entries are never updated or deleted;
 * corrections are new compensating transactions.
 */
class LedgerTransaction extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'reference',
        'type',
        'status',
        'idempotency_key',
        'currency',
        'description',
        'metadata',
        'posted_at',
        'created_by',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'type' => LedgerTransactionType::class,
            'metadata' => 'array',
            'posted_at' => 'datetime',
        ];
    }

    /** @return HasMany<WalletLedgerEntry, $this> */
    public function entries(): HasMany
    {
        return $this->hasMany(WalletLedgerEntry::class);
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isBalanced(): bool
    {
        $debits = (int) $this->entries->sum('debit_minor');
        $credits = (int) $this->entries->sum('credit_minor');

        return $debits === $credits;
    }
}
