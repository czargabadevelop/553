import { Head, Link, router } from '@inertiajs/react';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout';

export default function AdminLedger({ transactions, filters }) {
  const filter = (event) => {
    event.preventDefault();
    router.get('/admin/ledger', { type: event.target.type.value }, { preserveState: true });
  };

  return (
    <AuthenticatedLayout>
      <Head title="Ledger" />

      <main>
        <div className="mx-auto max-w-(--breakpoint-2xl) p-4 md:p-6">
          <div className="mb-5 flex flex-wrap items-center justify-between gap-3">
            <div>
              <h1 className="text-xl font-semibold text-gray-800 dark:text-white/90">Ledger</h1>
              <p className="mt-1 text-theme-xs text-gray-500 dark:text-gray-400">
                Double-entry, immutable. Corrections are compensating transactions — nothing is edited or deleted.
              </p>
            </div>
            <form onSubmit={filter} className="flex gap-2">
              <input name="type" defaultValue={filters.type} placeholder="Transaction type" className="rounded-lg border border-gray-200 px-3 py-2 text-theme-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white" />
              <button type="submit" className="rounded-lg bg-brand-500 px-4 py-2 text-theme-sm font-medium text-white">Filter</button>
            </form>
          </div>

          <div className="space-y-4">
            {transactions.data.map((transaction) => (
              <section key={transaction.reference} className="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
                <div className="mb-3 flex flex-wrap items-center justify-between gap-2">
                  <div>
                    <p className="text-theme-sm font-semibold text-gray-800 dark:text-white/90">{transaction.reference}</p>
                    <p className="text-theme-xs text-gray-500 dark:text-gray-400">
                      {transaction.type.replace('_', ' ')} · {transaction.description} · {transaction.posted_at}
                    </p>
                  </div>
                  <span className={`rounded-full px-3 py-1 text-theme-xs font-medium ${transaction.balanced ? 'bg-success-50 text-success-600' : 'bg-error-50 text-error-600'}`}>
                    {transaction.balanced ? 'Balanced' : 'UNBALANCED'}
                  </span>
                </div>

                <table className="w-full text-left text-theme-xs">
                  <thead className="text-gray-500 dark:text-gray-400">
                    <tr>
                      <th className="py-1">Account</th>
                      <th className="py-1">Wallet</th>
                      <th className="py-1">Debit</th>
                      <th className="py-1">Credit</th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-gray-100 dark:divide-gray-800">
                    {transaction.entries.map((entry, index) => (
                      <tr key={index}>
                        <td className="py-1.5 text-gray-700 dark:text-gray-200">{entry.account.replace('_', ' ')}</td>
                        <td className="py-1.5 text-gray-500 dark:text-gray-400">{entry.wallet_id ?? 'platform'}</td>
                        <td className="py-1.5 text-gray-700 dark:text-gray-200">{entry.debit}</td>
                        <td className="py-1.5 text-gray-700 dark:text-gray-200">{entry.credit}</td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </section>
            ))}

            {transactions.data.length === 0 && (
              <p className="rounded-2xl border border-gray-200 bg-white p-6 text-theme-sm text-gray-500 dark:border-gray-800 dark:bg-white/[0.03]">
                No ledger transactions yet.
              </p>
            )}
          </div>

          <div className="mt-4 flex items-center justify-between text-theme-xs">
            <span className="text-gray-500 dark:text-gray-400">Page {transactions.current_page} of {transactions.last_page}</span>
            <div className="flex gap-2">
              {transactions.prev_page_url && <Link href={transactions.prev_page_url} className="rounded border border-gray-200 px-3 py-1 dark:border-gray-700">Previous</Link>}
              {transactions.next_page_url && <Link href={transactions.next_page_url} className="rounded border border-gray-200 px-3 py-1 dark:border-gray-700">Next</Link>}
            </div>
          </div>
        </div>
      </main>
    </AuthenticatedLayout>
  );
}
