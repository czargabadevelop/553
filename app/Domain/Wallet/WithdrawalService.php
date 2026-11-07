<?php

namespace App\Domain\Wallet;

use App\Domain\Audit\AuditLogger;
use App\Domain\Settings\FeatureFlagService;
use App\Enums\LedgerAccount;
use App\Enums\LedgerTransactionType;
use App\Enums\ReservationStatus;
use App\Enums\ReservationType;
use App\Enums\WithdrawalStatus;
use App\Models\PayoutMethod;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletReservation;
use App\Models\Withdrawal;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Funds are reserved when a withdrawal is requested, released if it is rejected
 * or cancelled, and only debited when an administrator confirms the payout.
 */
class WithdrawalService
{
    public function __construct(
        private readonly WalletService $wallets,
        private readonly LedgerService $ledger,
        private readonly FeatureFlagService $flags,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * @param  array<string, mixed>  $payoutDetails
     */
    public function request(
        User $user,
        int $amountMinor,
        ?PayoutMethod $method,
        array $payoutDetails,
        ?string $idempotencyKey = null,
    ): Withdrawal {
        if ($amountMinor <= 0) {
            throw new RuntimeException('The withdrawal amount must be positive.');
        }

        return DB::transaction(function () use ($user, $amountMinor, $method, $payoutDetails, $idempotencyKey) {
            $wallet = $this->wallets->walletFor($user);

            if (! $wallet->is_test_credit && $this->flags->disabled(FeatureFlagService::REAL_MONEY_WITHDRAWALS)) {
                throw new RuntimeException('Real-money withdrawals are currently disabled.');
            }

            $reference = 'WDR-'.Str::upper(Str::random(12));

            $reservation = $this->wallets->reserve(
                user: $user,
                amountMinor: $amountMinor,
                type: ReservationType::Withdrawal,
                idempotencyKey: $idempotencyKey ?? 'withdrawal:'.$reference,
                metadata: ['reference' => $reference],
            );

            $withdrawal = Withdrawal::create([
                'reference' => $reference,
                'user_id' => $user->id,
                'wallet_id' => $wallet->id,
                'payout_method_id' => $method?->id,
                'amount_minor' => $amountMinor,
                'currency' => $wallet->currency,
                'status' => WithdrawalStatus::Pending,
                'payout_details_encrypted' => $payoutDetails,
                'requested_at' => now(),
                'reservation_id' => $reservation->id,
                'idempotency_key' => $idempotencyKey,
            ]);

            $this->audit->log('withdrawal.requested', $withdrawal, [], [
                'amount_minor' => $amountMinor,
                'reference' => $reference,
            ], $user);

            return $withdrawal;
        });
    }

    public function approve(Withdrawal $withdrawal, User $admin): Withdrawal
    {
        return $this->transition($withdrawal, $admin, [WithdrawalStatus::Pending, WithdrawalStatus::UnderReview], WithdrawalStatus::Approved, 'withdrawal.approved');
    }

    public function startProcessing(Withdrawal $withdrawal, User $admin): Withdrawal
    {
        return $this->transition($withdrawal, $admin, [WithdrawalStatus::Approved], WithdrawalStatus::Processing, 'withdrawal.processing');
    }

    public function complete(Withdrawal $withdrawal, User $admin): Withdrawal
    {
        return DB::transaction(function () use ($withdrawal, $admin) {
            $withdrawal = Withdrawal::query()->whereKey($withdrawal->id)->lockForUpdate()->firstOrFail();

            if (! in_array($withdrawal->status, [WithdrawalStatus::Approved, WithdrawalStatus::Processing], true)) {
                throw new RuntimeException('Only approved or processing withdrawals can be completed.');
            }

            $wallet = Wallet::query()->whereKey($withdrawal->wallet_id)->lockForUpdate()->firstOrFail();

            if (! $wallet->is_test_credit && $this->flags->disabled(FeatureFlagService::REAL_MONEY_WITHDRAWALS)) {
                throw new RuntimeException('Real-money withdrawals are currently disabled.');
            }

            $reservation = WalletReservation::query()->whereKey($withdrawal->reservation_id)->lockForUpdate()->first();

            if ($reservation !== null && $reservation->status === ReservationStatus::Held) {
                $this->wallets->release($reservation, 'Withdrawal approved for payout');
            }

            $transaction = $this->ledger->post(
                type: LedgerTransactionType::Withdrawal,
                description: 'Withdrawal '.$withdrawal->reference.' paid out',
                entries: [
                    ['account' => LedgerAccount::UserAvailable, 'wallet_id' => $wallet->id, 'debit' => $withdrawal->amount_minor],
                    ['account' => LedgerAccount::ExternalClearing, 'wallet_id' => null, 'credit' => $withdrawal->amount_minor],
                ],
                idempotencyKey: 'withdrawal:'.$withdrawal->reference,
                actor: $admin,
                metadata: ['withdrawal_id' => $withdrawal->id],
            );

            $wallet->forceFill([
                'lifetime_withdrawn_minor' => $wallet->lifetime_withdrawn_minor + $withdrawal->amount_minor,
            ])->save();

            $withdrawal->forceFill([
                'status' => WithdrawalStatus::Completed,
                'completed_at' => now(),
                'processed_at' => $withdrawal->processed_at ?? now(),
                'processed_by' => $withdrawal->processed_by ?? $admin->id,
                'ledger_transaction_id' => $transaction->id,
            ])->save();

            $this->audit->log('withdrawal.completed', $withdrawal, [], [
                'amount_minor' => $withdrawal->amount_minor,
            ], $admin);

            return $withdrawal;
        });
    }

    public function reject(Withdrawal $withdrawal, User $admin, string $reason): Withdrawal
    {
        return DB::transaction(function () use ($withdrawal, $admin, $reason) {
            $withdrawal = Withdrawal::query()->whereKey($withdrawal->id)->lockForUpdate()->firstOrFail();

            if ($withdrawal->status->isFinal()) {
                throw new RuntimeException('This withdrawal is already final.');
            }

            $reservation = WalletReservation::query()->whereKey($withdrawal->reservation_id)->lockForUpdate()->first();

            if ($reservation !== null && $reservation->status === ReservationStatus::Held) {
                $this->wallets->release($reservation, 'Withdrawal rejected');
            }

            $withdrawal->forceFill([
                'status' => WithdrawalStatus::Rejected,
                'reviewed_at' => now(),
                'reviewed_by' => $admin->id,
                'rejection_reason' => $reason,
            ])->save();

            $this->audit->log('withdrawal.rejected', $withdrawal, [], ['reason' => $reason], $admin);

            return $withdrawal;
        });
    }

    /**
     * @param  list<WithdrawalStatus>  $from
     */
    private function transition(Withdrawal $withdrawal, User $admin, array $from, WithdrawalStatus $to, string $action): Withdrawal
    {
        return DB::transaction(function () use ($withdrawal, $admin, $from, $to, $action) {
            $withdrawal = Withdrawal::query()->whereKey($withdrawal->id)->lockForUpdate()->firstOrFail();

            if (! in_array($withdrawal->status, $from, true)) {
                throw new RuntimeException('This withdrawal cannot move to '.$to->value.'.');
            }

            $withdrawal->forceFill([
                'status' => $to,
                'reviewed_at' => now(),
                'reviewed_by' => $admin->id,
                'processed_at' => $to === WithdrawalStatus::Processing ? now() : $withdrawal->processed_at,
                'processed_by' => $to === WithdrawalStatus::Processing ? $admin->id : $withdrawal->processed_by,
            ])->save();

            $this->audit->log($action, $withdrawal, ['status' => $withdrawal->getOriginal('status')], ['status' => $to->value], $admin);

            return $withdrawal;
        });
    }
}
