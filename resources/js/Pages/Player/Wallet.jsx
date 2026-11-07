import { Head, useForm } from '@inertiajs/react';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout';

export default function Wallet({ wallet, limits, flags, paymentMethods, payoutMethods, deposits, withdrawals, transactions }) {
  const depositForm = useForm({ amount: '', payment_method_id: paymentMethods[0]?.id ?? '', reference_number: '', proof: null });
  const withdrawalForm = useForm({ amount: '', payout_method_id: payoutMethods[0]?.id ?? '', details: {}, password: '' });

  const submitDeposit = (event) => {
    event.preventDefault();
    depositForm.post('/wallet/deposits', { preserveScroll: true, forceFormData: true, onSuccess: () => depositForm.reset() });
  };

  const submitWithdrawal = (event) => {
    event.preventDefault();
    withdrawalForm.post('/wallet/withdrawals', { preserveScroll: true, onSuccess: () => withdrawalForm.reset() });
  };

  const selectedPayout = payoutMethods.find((method) => String(method.id) === String(withdrawalForm.data.payout_method_id));

  return (
    <AuthenticatedLayout>
      <Head title="Wallet" />

      <main>
        <div className="mx-auto max-w-(--breakpoint-2xl) p-4 md:p-6">
          <div className="mb-6 flex flex-wrap items-center justify-between gap-3">
            <h1 className="text-xl font-semibold text-gray-800 dark:text-white/90">Wallet</h1>
            {wallet.is_test_credit && (
              <span className="rounded-full bg-warning-50 px-3 py-1 text-theme-xs font-semibold text-warning-600 dark:bg-warning-500/15">
                {wallet.test_credit_label}
              </span>
            )}
          </div>

          <div className="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div className="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
              <p className="text-theme-xs uppercase text-gray-500 dark:text-gray-400">Available</p>
              <p className="mt-2 text-2xl font-semibold text-gray-800 dark:text-white/90">{wallet.currency} {wallet.available}</p>
            </div>
            <div className="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
              <p className="text-theme-xs uppercase text-gray-500 dark:text-gray-400">Reserved</p>
              <p className="mt-2 text-2xl font-semibold text-gray-800 dark:text-white/90">{wallet.currency} {wallet.reserved}</p>
            </div>
            <div className="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
              <p className="text-theme-xs uppercase text-gray-500 dark:text-gray-400">Lifetime</p>
              <p className="mt-2 text-theme-sm text-gray-600 dark:text-gray-300">
                Deposits {wallet.lifetime.deposited} · Winnings {wallet.lifetime.winnings}
              </p>
              <p className="text-theme-sm text-gray-600 dark:text-gray-300">
                Losses {wallet.lifetime.losses} · Fees {wallet.lifetime.fees}
              </p>
            </div>
          </div>

          <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <section className="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
              <h2 className="mb-4 text-lg font-semibold text-gray-800 dark:text-white/90">Request a deposit</h2>
              <p className="mb-3 text-theme-xs text-gray-500 dark:text-gray-400">
                Deposits are credited only after an administrator verifies the payment. Uploading proof does not credit your wallet.
              </p>
              <form onSubmit={submitDeposit} className="space-y-3">
                <input
                  type="text"
                  inputMode="decimal"
                  placeholder={`Amount (min ${limits.min_deposit})`}
                  value={depositForm.data.amount}
                  onChange={(event) => depositForm.setData('amount', event.target.value)}
                  className="w-full rounded-lg border border-gray-200 px-3 py-2 text-theme-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                />
                <select
                  value={depositForm.data.payment_method_id}
                  onChange={(event) => depositForm.setData('payment_method_id', event.target.value)}
                  className="w-full rounded-lg border border-gray-200 px-3 py-2 text-theme-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                >
                  {paymentMethods.map((method) => (
                    <option key={method.id} value={method.id}>{method.name}</option>
                  ))}
                </select>
                <input
                  type="text"
                  placeholder="Payment reference number"
                  value={depositForm.data.reference_number}
                  onChange={(event) => depositForm.setData('reference_number', event.target.value)}
                  className="w-full rounded-lg border border-gray-200 px-3 py-2 text-theme-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                />
                <input
                  type="file"
                  accept="image/*,application/pdf"
                  onChange={(event) => depositForm.setData('proof', event.target.files[0])}
                  className="w-full rounded-lg border border-dashed border-gray-200 px-3 py-2 text-theme-xs dark:border-gray-700 dark:text-gray-300"
                />
                {depositForm.errors.amount && <p className="text-theme-xs text-error-500">{depositForm.errors.amount}</p>}
                <button type="submit" disabled={depositForm.processing} className="rounded-lg bg-brand-500 px-4 py-2 text-theme-sm font-medium text-white hover:bg-brand-600">
                  Submit deposit request
                </button>
              </form>
            </section>

            <section className="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
              <h2 className="mb-4 text-lg font-semibold text-gray-800 dark:text-white/90">Request a withdrawal</h2>
              <p className="mb-3 text-theme-xs text-gray-500 dark:text-gray-400">
                The amount is reserved immediately and released if the request is rejected.
              </p>
              <form onSubmit={submitWithdrawal} className="space-y-3">
                <input
                  type="text"
                  inputMode="decimal"
                  placeholder={`Amount (min ${limits.min_withdrawal})`}
                  value={withdrawalForm.data.amount}
                  onChange={(event) => withdrawalForm.setData('amount', event.target.value)}
                  className="w-full rounded-lg border border-gray-200 px-3 py-2 text-theme-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                />
                <select
                  value={withdrawalForm.data.payout_method_id}
                  onChange={(event) => withdrawalForm.setData('payout_method_id', event.target.value)}
                  className="w-full rounded-lg border border-gray-200 px-3 py-2 text-theme-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                >
                  {payoutMethods.map((method) => (
                    <option key={method.id} value={method.id}>{method.name}</option>
                  ))}
                </select>

                {(selectedPayout?.required_fields ?? []).map((field) => (
                  <input
                    key={field}
                    type="text"
                    placeholder={field.replace('_', ' ')}
                    value={withdrawalForm.data.details[field] ?? ''}
                    onChange={(event) => withdrawalForm.setData('details', { ...withdrawalForm.data.details, [field]: event.target.value })}
                    className="w-full rounded-lg border border-gray-200 px-3 py-2 text-theme-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                  />
                ))}

                <input
                  type="password"
                  placeholder="Confirm your password"
                  value={withdrawalForm.data.password}
                  onChange={(event) => withdrawalForm.setData('password', event.target.value)}
                  className="w-full rounded-lg border border-gray-200 px-3 py-2 text-theme-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                />
                {withdrawalForm.errors.amount && <p className="text-theme-xs text-error-500">{withdrawalForm.errors.amount}</p>}
                <button type="submit" disabled={withdrawalForm.processing} className="rounded-lg bg-brand-500 px-4 py-2 text-theme-sm font-medium text-white hover:bg-brand-600">
                  Submit withdrawal request
                </button>
              </form>
            </section>
          </div>

          <div className="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-3">
            <section className="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
              <h2 className="mb-3 text-theme-sm font-semibold text-gray-800 dark:text-white/90">Deposits</h2>
              {deposits.length === 0 ? <p className="text-theme-xs text-gray-500">No deposits yet.</p> : (
                <ul className="space-y-2 text-theme-xs">
                  {deposits.map((deposit) => (
                    <li key={deposit.reference} className="flex justify-between">
                      <span className="text-gray-600 dark:text-gray-300">{deposit.reference}</span>
                      <span className="font-medium text-gray-800 dark:text-white/90">{deposit.amount} · {deposit.status.replace('_', ' ')}</span>
                    </li>
                  ))}
                </ul>
              )}
            </section>

            <section className="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
              <h2 className="mb-3 text-theme-sm font-semibold text-gray-800 dark:text-white/90">Withdrawals</h2>
              {withdrawals.length === 0 ? <p className="text-theme-xs text-gray-500">No withdrawals yet.</p> : (
                <ul className="space-y-2 text-theme-xs">
                  {withdrawals.map((withdrawal) => (
                    <li key={withdrawal.reference} className="flex justify-between">
                      <span className="text-gray-600 dark:text-gray-300">{withdrawal.reference}</span>
                      <span className="font-medium text-gray-800 dark:text-white/90">{withdrawal.amount} · {withdrawal.status.replace('_', ' ')}</span>
                    </li>
                  ))}
                </ul>
              )}
            </section>

            <section className="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
              <h2 className="mb-3 text-theme-sm font-semibold text-gray-800 dark:text-white/90">Ledger activity</h2>
              <ul className="space-y-2 text-theme-xs">
                {transactions.map((entry) => (
                  <li key={`${entry.reference}-${entry.account}`} className="flex justify-between">
                    <span className="capitalize text-gray-600 dark:text-gray-300">{entry.type?.replace('_', ' ')}</span>
                    <span className="font-medium text-gray-800 dark:text-white/90">{entry.amount}</span>
                  </li>
                ))}
              </ul>
            </section>
          </div>
        </div>
      </main>
    </AuthenticatedLayout>
  );
}
