<?php

namespace App\Http\Controllers\Player;

use App\Domain\Settings\FeatureFlagService;
use App\Domain\Settings\SettingsService;
use App\Domain\Wallet\DepositService;
use App\Domain\Wallet\WalletService;
use App\Domain\Wallet\WithdrawalService;
use App\Http\Controllers\Controller;
use App\Models\PaymentMethod;
use App\Models\PayoutMethod;
use App\Models\WalletLedgerEntry;
use App\Support\MoneyInput;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class WalletController extends Controller
{
    public function index(Request $request, WalletService $wallets, SettingsService $settings): Response
    {
        $user = $request->user();
        $wallet = $wallets->walletFor($user);

        return Inertia::render('Player/Wallet', [
            'wallet' => [
                'available' => $wallet->available()->toDecimalString(),
                'reserved' => $wallet->reserved()->toDecimalString(),
                'currency' => $wallet->currency,
                'is_test_credit' => $wallet->is_test_credit,
                'test_credit_label' => $settings->string('wallet.test_credit_label', 'TEST CREDIT'),
                'lifetime' => [
                    'deposited' => number_format($wallet->lifetime_deposited_minor / 100, 2),
                    'withdrawn' => number_format($wallet->lifetime_withdrawn_minor / 100, 2),
                    'winnings' => number_format($wallet->lifetime_winnings_minor / 100, 2),
                    'losses' => number_format($wallet->lifetime_losses_minor / 100, 2),
                    'fees' => number_format($wallet->lifetime_fees_minor / 100, 2),
                ],
            ],
            'limits' => [
                'min_deposit' => MoneyInput::format($settings->integer('wallet.min_deposit_minor', 0)),
                'max_deposit' => MoneyInput::format($settings->integer('wallet.max_deposit_minor', 0)),
                'min_withdrawal' => MoneyInput::format($settings->integer('wallet.min_withdrawal_minor', 0)),
            ],
            'flags' => [
                'deposits' => app(FeatureFlagService::class)->enabled(FeatureFlagService::REAL_MONEY_DEPOSITS),
                'withdrawals' => app(FeatureFlagService::class)->enabled(FeatureFlagService::REAL_MONEY_WITHDRAWALS),
            ],
            'paymentMethods' => PaymentMethod::query()->where('is_active', true)->orderBy('sort_order')->get(),
            'payoutMethods' => PayoutMethod::query()->where('is_active', true)->orderBy('sort_order')->get(),
            'deposits' => $user->deposits()->latest()->take(10)->get()->map(fn ($deposit) => [
                'reference' => $deposit->reference,
                'amount' => $deposit->amount()->toDecimalString(),
                'status' => $deposit->status->value,
                'submitted_at' => $deposit->submitted_at?->toIso8601String(),
                'rejection_reason' => $deposit->rejection_reason,
            ]),
            'withdrawals' => $user->withdrawals()->latest()->take(10)->get()->map(fn ($withdrawal) => [
                'reference' => $withdrawal->reference,
                'amount' => $withdrawal->amount()->toDecimalString(),
                'status' => $withdrawal->status->value,
                'requested_at' => $withdrawal->requested_at?->toIso8601String(),
                'rejection_reason' => $withdrawal->rejection_reason,
            ]),
            'transactions' => WalletLedgerEntry::query()
                ->where('wallet_id', $wallet->id)
                ->with('transaction')
                ->latest()
                ->take(20)
                ->get()
                ->map(fn (WalletLedgerEntry $entry) => [
                    'reference' => $entry->transaction?->reference,
                    'type' => $entry->transaction?->type?->value,
                    'account' => $entry->account->value,
                    'amount' => number_format($entry->signedMinor() / 100, 2),
                    'posted_at' => $entry->created_at?->toIso8601String(),
                ]),
        ]);
    }

    public function storeDeposit(Request $request, DepositService $deposits): RedirectResponse
    {
        $validated = $request->validate([
            'amount' => ['required', 'string', 'regex:/^\d{1,13}(\.\d{1,2})?$/'],
            'payment_method_id' => ['nullable', 'integer', 'exists:payment_methods,id'],
            'reference_number' => ['nullable', 'string', 'max:96'],
            'proof' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:4096'],
        ]);

        try {
            $deposits->request(
                user: $request->user(),
                amountMinor: MoneyInput::toMinor($validated['amount']),
                method: PaymentMethod::query()->find($validated['payment_method_id'] ?? null),
                referenceNumber: $validated['reference_number'] ?? null,
                proof: $request->file('proof'),
            );
        } catch (RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Deposit request submitted. It will be credited after verification.');
    }

    public function storeWithdrawal(Request $request, WithdrawalService $withdrawals): RedirectResponse
    {
        $validated = $request->validate([
            'amount' => ['required', 'string', 'regex:/^\d{1,13}(\.\d{1,2})?$/'],
            'payout_method_id' => ['required', 'integer', 'exists:payout_methods,id'],
            'details' => ['required', 'array'],
            'details.*' => ['nullable', 'string', 'max:96'],
            'password' => ['required', 'current_password'],
        ]);

        try {
            $withdrawals->request(
                user: $request->user(),
                amountMinor: MoneyInput::toMinor($validated['amount']),
                method: PayoutMethod::query()->find($validated['payout_method_id']),
                payoutDetails: $validated['details'],
            );
        } catch (RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Withdrawal requested. The amount is reserved until payout is confirmed.');
    }
}
