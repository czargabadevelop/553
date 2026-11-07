<?php

namespace App\Domain\Wallet;

use App\Domain\Audit\AuditLogger;
use App\Domain\Settings\FeatureFlagService;
use App\Enums\DepositStatus;
use App\Enums\LedgerAccount;
use App\Enums\LedgerTransactionType;
use App\Models\Deposit;
use App\Models\PaymentMethod;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Deposits are credited only after an administrator independently verifies the
 * payment. Uploading proof never credits a wallet (agents.md §8).
 */
class DepositService
{
    public function __construct(
        private readonly LedgerService $ledger,
        private readonly FeatureFlagService $flags,
        private readonly AuditLogger $audit,
    ) {}

    public function request(
        User $user,
        int $amountMinor,
        ?PaymentMethod $method = null,
        ?string $referenceNumber = null,
        ?UploadedFile $proof = null,
        ?string $idempotencyKey = null,
    ): Deposit {
        if ($amountMinor <= 0) {
            throw new RuntimeException('The deposit amount must be positive.');
        }

        return DB::transaction(function () use ($user, $amountMinor, $method, $referenceNumber, $proof, $idempotencyKey) {
            $wallet = Wallet::query()->firstOrCreate(
                ['user_id' => $user->id],
                ['currency' => 'PHP', 'is_test_credit' => true],
            );

            if ($idempotencyKey !== null) {
                $existing = Deposit::query()->where('idempotency_key', $idempotencyKey)->first();

                if ($existing !== null) {
                    return $existing;
                }
            }

            $deposit = Deposit::create([
                'reference' => 'DEP-'.Str::upper(Str::random(12)),
                'user_id' => $user->id,
                'wallet_id' => $wallet->id,
                'payment_method_id' => $method?->id,
                'amount_minor' => $amountMinor,
                'currency' => $wallet->currency,
                'status' => DepositStatus::Pending,
                'reference_number' => $referenceNumber,
                'proof_path' => $proof?->store('deposit-proofs', 'local'),
                'instructions' => $method ? ['instructions' => $method->instructions] : null,
                'submitted_at' => now(),
                'idempotency_key' => $idempotencyKey,
            ]);

            $this->audit->log('deposit.requested', $deposit, [], [
                'amount_minor' => $amountMinor,
                'reference' => $deposit->reference,
            ], $user);

            return $deposit;
        });
    }

    public function approve(Deposit $deposit, User $admin): Deposit
    {
        return DB::transaction(function () use ($deposit, $admin) {
            $deposit = Deposit::query()->whereKey($deposit->id)->lockForUpdate()->firstOrFail();

            if ($deposit->status !== DepositStatus::Pending && $deposit->status !== DepositStatus::UnderReview) {
                throw new RuntimeException('Only pending or under-review deposits can be approved.');
            }

            $wallet = Wallet::query()->whereKey($deposit->wallet_id)->lockForUpdate()->firstOrFail();

            if ($realMoneyBlocked($wallet, $this->flags)) {
                throw new RuntimeException('Real-money deposits are currently disabled.');
            }

            $transaction = $this->ledger->post(
                type: LedgerTransactionType::Deposit,
                description: 'Deposit '.$deposit->reference.' approved',
                entries: [
                    ['account' => LedgerAccount::ExternalClearing, 'wallet_id' => null, 'debit' => $deposit->amount_minor],
                    ['account' => LedgerAccount::UserAvailable, 'wallet_id' => $wallet->id, 'credit' => $deposit->amount_minor],
                ],
                idempotencyKey: 'deposit:'.$deposit->reference,
                actor: $admin,
                metadata: ['deposit_id' => $deposit->id],
            );

            $wallet->forceFill([
                'lifetime_deposited_minor' => $wallet->lifetime_deposited_minor + $deposit->amount_minor,
            ])->save();

            $deposit->forceFill([
                'status' => DepositStatus::Approved,
                'reviewed_at' => now(),
                'reviewed_by' => $admin->id,
                'ledger_transaction_id' => $transaction->id,
            ])->save();

            $this->audit->log('deposit.approved', $deposit, [], [
                'amount_minor' => $deposit->amount_minor,
            ], $admin);

            return $deposit;
        });
    }

    public function reject(Deposit $deposit, User $admin, string $reason): Deposit
    {
        return DB::transaction(function () use ($deposit, $admin, $reason) {
            $deposit = Deposit::query()->whereKey($deposit->id)->lockForUpdate()->firstOrFail();

            if (! $deposit->status->isOpen()) {
                throw new RuntimeException('This deposit has already been reviewed.');
            }

            $deposit->forceFill([
                'status' => DepositStatus::Rejected,
                'reviewed_at' => now(),
                'reviewed_by' => $admin->id,
                'rejection_reason' => $reason,
            ])->save();

            $this->audit->log('deposit.rejected', $deposit, [], ['reason' => $reason], $admin);

            return $deposit;
        });
    }
}

/**
 * Real money may only move when the corresponding feature flag is enabled.
 */
function realMoneyBlocked(Wallet $wallet, FeatureFlagService $flags): bool
{
    return ! $wallet->is_test_credit
        && $flags->disabled(FeatureFlagService::REAL_MONEY_DEPOSITS);
}
