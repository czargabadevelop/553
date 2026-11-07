<?php

namespace App\Models;

use App\Enums\LedgerAccount;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WalletLedgerEntry extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'ledger_transaction_id',
        'wallet_id',
        'account',
        'debit_minor',
        'credit_minor',
        'currency',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'account' => LedgerAccount::class,
            'debit_minor' => 'integer',
            'credit_minor' => 'integer',
        ];
    }

    /** @return BelongsTo<LedgerTransaction, $this> */
    public function transaction(): BelongsTo
    {
        return $this->belongsTo(LedgerTransaction::class, 'ledger_transaction_id');
    }

    /** @return BelongsTo<Wallet, $this> */
    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    public function signedMinor(): int
    {
        return $this->credit_minor - $this->debit_minor;
    }
}
