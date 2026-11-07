<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Wallet\DepositService;
use App\Domain\Wallet\WalletService;
use App\Domain\Wallet\WithdrawalService;
use App\Http\Controllers\Controller;
use App\Models\PaymentMethod;
use App\Models\PayoutMethod;
use App\Models\WalletLedgerEntry;
use App\Support\MoneyInput;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class WalletController extends Controller
{
    public function show(Request $request, WalletService $wallets): JsonResponse
    {
        $wallet = $wallets->walletFor($request->user());

        return response()->json([
            'wallet' => [
                'currency' => $wallet->currency,
                'available_minor' => $wallet->available_minor,
                'reserved_minor' => $wallet->reserved_minor,
                'is_test_credit' => $wallet->is_test_credit,
            ],
            'transactions' => WalletLedgerEntry::query()
                ->where('wallet_id', $wallet->id)
                ->with('transaction')
                ->latest()
                ->take(50)
                ->get()
                ->map(fn (WalletLedgerEntry $entry) => [
                    'reference' => $entry->transaction?->reference,
                    'type' => $entry->transaction?->type?->value,
                    'account' => $entry->account->value,
                    'debit_minor' => $entry->debit_minor,
                    'credit_minor' => $entry->credit_minor,
                    'posted_at' => $entry->created_at?->toIso8601String(),
                ]),
            'payment_methods' => PaymentMethod::query()->where('is_active', true)->get(['id', 'code', 'name']),
            'payout_methods' => PayoutMethod::query()->where('is_active', true)->get(['id', 'code', 'name', 'required_fields']),
        ]);
    }

    public function deposit(Request $request, DepositService $deposits): JsonResponse
    {
        $validated = $request->validate([
            'amount_minor' => ['required', 'integer', 'min:1'],
            'payment_method_id' => ['nullable', 'integer', 'exists:payment_methods,id'],
            'reference_number' => ['nullable', 'string', 'max:96'],
        ]);

        $deposit = $deposits->request(
            user: $request->user(),
            amountMinor: (int) $validated['amount_minor'],
            method: PaymentMethod::query()->find($validated['payment_method_id'] ?? null),
            referenceNumber: $validated['reference_number'] ?? null,
        );

        return response()->json([
            'message' => 'Deposit request submitted for review.',
            'reference' => $deposit->reference,
            'status' => $deposit->status->value,
        ], 201);
    }

    public function withdrawal(Request $request, WithdrawalService $withdrawals): JsonResponse
    {
        $validated = $request->validate([
            'amount_minor' => ['required', 'integer', 'min:1'],
            'payout_method_id' => ['required', 'integer', 'exists:payout_methods,id'],
            'details' => ['required', 'array'],
        ]);

        try {
            $withdrawal = $withdrawals->request(
                user: $request->user(),
                amountMinor: MoneyInput::toMinor($validated['amount_minor']),
                method: PayoutMethod::query()->find($validated['payout_method_id']),
                payoutDetails: $validated['details'],
            );
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Withdrawal requested; funds are reserved until payout is confirmed.',
            'reference' => $withdrawal->reference,
            'status' => $withdrawal->status->value,
        ], 201);
    }
}
