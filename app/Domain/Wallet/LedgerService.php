<?php

namespace App\Domain\Wallet;

use App\Enums\LedgerAccount;
use App\Enums\LedgerTransactionType;
use App\Models\LedgerTransaction;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletLedgerEntry;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Posts balanced double-entry transactions. Every posted transaction is
 * immutable: entries are only ever created, never updated or deleted.
 */
class LedgerService
{
    /**
     * @param  list<array{account: LedgerAccount, wallet_id?: int|null, debit?: int, credit?: int}>  $entries
     * @param  array<string, mixed>  $metadata
     */
    public function post(
        LedgerTransactionType $type,
        string $description,
        array $entries,
        ?string $idempotencyKey = null,
        ?User $actor = null,
        array $metadata = [],
        string $currency = 'PHP',
    ): LedgerTransaction {
        $this->assertBalanced($entries);

        return DB::transaction(function () use ($type, $description, $entries, $idempotencyKey, $actor, $metadata, $currency) {
            if ($idempotencyKey !== null) {
                $existing = LedgerTransaction::query()
                    ->where('idempotency_key', $idempotencyKey)
                    ->lockForUpdate()
                    ->first();

                if ($existing !== null) {
                    return $existing;
                }
            }

            $transaction = LedgerTransaction::create([
                'reference' => 'LTX-'.Str::upper(Str::random(16)),
                'type' => $type,
                'status' => 'posted',
                'idempotency_key' => $idempotencyKey,
                'currency' => $currency,
                'description' => $description,
                'metadata' => $metadata ?: null,
                'posted_at' => now(),
                'created_by' => $actor?->id,
            ]);

            foreach ($entries as $entry) {
                WalletLedgerEntry::create([
                    'ledger_transaction_id' => $transaction->id,
                    'wallet_id' => $entry['wallet_id'] ?? null,
                    'account' => $entry['account'],
                    'debit_minor' => $entry['debit'] ?? 0,
                    'credit_minor' => $entry['credit'] ?? 0,
                    'currency' => $currency,
                ]);
            }

            $this->applyBalanceEffects($entries);

            return $transaction->load('entries');
        });
    }

    /**
     * @param  list<array{account: LedgerAccount, wallet_id?: int|null, debit?: int, credit?: int}>  $entries
     */
    private function applyBalanceEffects(array $entries): void
    {
        foreach ($entries as $entry) {
            $walletId = $entry['wallet_id'] ?? null;

            if ($walletId === null) {
                continue;
            }

            $delta = (int) ($entry['credit'] ?? 0) - (int) ($entry['debit'] ?? 0);

            if ($delta === 0) {
                continue;
            }

            /** @var Wallet $wallet */
            $wallet = Wallet::query()->whereKey($walletId)->lockForUpdate()->firstOrFail();

            match ($entry['account']) {
                LedgerAccount::UserAvailable => $wallet->available_minor += $delta,
                LedgerAccount::UserReserved => $wallet->reserved_minor += $delta,
                default => null,
            };

            $wallet->save();
        }
    }

    /**
     * @param  list<array{account: LedgerAccount, wallet_id?: int|null, debit?: int, credit?: int}>  $entries
     */
    private function assertBalanced(array $entries): void
    {
        if (count($entries) < 2) {
            throw new InvalidArgumentException('A ledger transaction needs at least two entries.');
        }

        $debits = 0;
        $credits = 0;

        foreach ($entries as $entry) {
            $debit = (int) ($entry['debit'] ?? 0);
            $credit = (int) ($entry['credit'] ?? 0);

            if ($debit < 0 || $credit < 0) {
                throw new InvalidArgumentException('Ledger amounts must be positive.');
            }

            if (($debit > 0) === ($credit > 0)) {
                throw new InvalidArgumentException('Each entry must be either a debit or a credit.');
            }

            $debits += $debit;
            $credits += $credit;
        }

        if ($debits !== $credits) {
            throw new InvalidArgumentException("Unbalanced ledger transaction: debits {$debits} vs credits {$credits}.");
        }
    }
}
