<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Wallet\DepositService;
use App\Domain\Wallet\WithdrawalService;
use App\Enums\DepositStatus;
use App\Enums\WithdrawalStatus;
use App\Http\Controllers\Controller;
use App\Models\Deposit;
use App\Models\LedgerTransaction;
use App\Models\Withdrawal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class FinanceController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Finance', [
            'deposits' => Deposit::query()
                ->with(['user', 'paymentMethod'])
                ->whereIn('status', [DepositStatus::Pending, DepositStatus::UnderReview])
                ->latest()
                ->get()
                ->map(fn (Deposit $deposit) => [
                    'id' => $deposit->id,
                    'reference' => $deposit->reference,
                    'player' => $deposit->user?->displayName(),
                    'amount' => $deposit->amount()->toDecimalString(),
                    'status' => $deposit->status->value,
                    'method' => $deposit->paymentMethod?->name,
                    'reference_number' => $deposit->reference_number,
                    'proof_path' => $deposit->proof_path,
                    'submitted_at' => $deposit->submitted_at?->toIso8601String(),
                ]),
            'withdrawals' => Withdrawal::query()
                ->with(['user', 'payoutMethod'])
                ->whereIn('status', [
                    WithdrawalStatus::Pending,
                    WithdrawalStatus::UnderReview,
                    WithdrawalStatus::Approved,
                    WithdrawalStatus::Processing,
                ])
                ->latest()
                ->get()
                ->map(fn (Withdrawal $withdrawal) => [
                    'id' => $withdrawal->id,
                    'reference' => $withdrawal->reference,
                    'player' => $withdrawal->user?->displayName(),
                    'amount' => $withdrawal->amount()->toDecimalString(),
                    'status' => $withdrawal->status->value,
                    'method' => $withdrawal->payoutMethod?->name,
                    'requested_at' => $withdrawal->requested_at?->toIso8601String(),
                ]),
            'pendingCount' => Deposit::query()->whereIn('status', [DepositStatus::Pending, DepositStatus::UnderReview])->count()
                + Withdrawal::query()->whereIn('status', [WithdrawalStatus::Pending, WithdrawalStatus::UnderReview, WithdrawalStatus::Approved, WithdrawalStatus::Processing])->count(),
        ]);
    }

    public function ledger(Request $request): Response
    {
        $type = (string) $request->query('type', '');

        $transactions = LedgerTransaction::query()
            ->with('entries')
            ->when($type !== '', fn ($query) => $query->where('type', $type))
            ->latest()
            ->paginate(25)
            ->withQueryString()
            ->through(fn (LedgerTransaction $transaction) => [
                'reference' => $transaction->reference,
                'type' => $transaction->type->value,
                'description' => $transaction->description,
                'currency' => $transaction->currency,
                'posted_at' => $transaction->posted_at?->toIso8601String(),
                'balanced' => $transaction->isBalanced(),
                'entries' => $transaction->entries->map(fn ($entry) => [
                    'account' => $entry->account->value,
                    'wallet_id' => $entry->wallet_id,
                    'debit' => number_format($entry->debit_minor / 100, 2),
                    'credit' => number_format($entry->credit_minor / 100, 2),
                ]),
            ]);

        return Inertia::render('Admin/Ledger', [
            'transactions' => $transactions,
            'filters' => ['type' => $type],
        ]);
    }

    public function approveDeposit(Request $request, Deposit $deposit, DepositService $deposits): RedirectResponse
    {
        try {
            $deposits->approve($deposit, $request->user());
        } catch (RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Deposit approved and credited through the ledger.');
    }

    public function rejectDeposit(Request $request, Deposit $deposit, DepositService $deposits): RedirectResponse
    {
        $validated = $request->validate(['reason' => ['required', 'string', 'max:255']]);

        try {
            $deposits->reject($deposit, $request->user(), $validated['reason']);
        } catch (RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Deposit rejected.');
    }

    public function approveWithdrawal(Request $request, Withdrawal $withdrawal, WithdrawalService $withdrawals): RedirectResponse
    {
        try {
            $withdrawals->approve($withdrawal, $request->user());
        } catch (RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Withdrawal approved.');
    }

    public function rejectWithdrawal(Request $request, Withdrawal $withdrawal, WithdrawalService $withdrawals): RedirectResponse
    {
        $validated = $request->validate(['reason' => ['required', 'string', 'max:255']]);

        try {
            $withdrawals->reject($withdrawal, $request->user(), $validated['reason']);
        } catch (RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Withdrawal rejected and funds released.');
    }

    public function completeWithdrawal(Request $request, Withdrawal $withdrawal, WithdrawalService $withdrawals): RedirectResponse
    {
        try {
            $withdrawals->startProcessing($withdrawal->refresh(), $request->user());
        } catch (RuntimeException) {
            // Already processing: still allow completion below.
        }

        try {
            $withdrawals->complete($withdrawal->refresh(), $request->user());
        } catch (RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Withdrawal marked completed after payout confirmation.');
    }
}
