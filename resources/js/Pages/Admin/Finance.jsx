import { Head, router } from '@inertiajs/react';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout';

export default function AdminFinance({ deposits, withdrawals, pendingCount }) {
  const post = (url, data = {}) => router.post(url, data, { preserveScroll: true });

  return (
    <AuthenticatedLayout>
      <Head title="Finance" />

      <main>
        <div className="mx-auto max-w-(--breakpoint-2xl) p-4 md:p-6">
          <div className="mb-6 flex items-center justify-between">
            <h1 className="text-xl font-semibold text-gray-800 dark:text-white/90">Financial requests</h1>
            <span className="rounded-full bg-warning-50 px-3 py-1 text-theme-xs font-semibold text-warning-600">{pendingCount} pending</span>
          </div>

          <section className="mb-6 overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
            <h2 className="border-b border-gray-100 px-5 py-4 text-lg font-semibold text-gray-800 dark:border-gray-800 dark:text-white/90">Deposits awaiting review</h2>
            <div className="overflow-x-auto">
              <table className="w-full text-left text-theme-sm">
                <thead className="text-theme-xs uppercase text-gray-500 dark:text-gray-400">
                  <tr>
                    <th className="px-4 py-3">Reference</th>
                    <th className="px-4 py-3">Player</th>
                    <th className="px-4 py-3">Amount</th>
                    <th className="px-4 py-3">Method</th>
                    <th className="px-4 py-3">Proof</th>
                    <th className="px-4 py-3">Actions</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-gray-100 dark:divide-gray-800">
                  {deposits.map((deposit) => (
                    <tr key={deposit.id}>
                      <td className="px-4 py-3 font-medium text-gray-800 dark:text-white/90">{deposit.reference}</td>
                      <td className="px-4 py-3 text-gray-600 dark:text-gray-300">{deposit.player}</td>
                      <td className="px-4 py-3 text-gray-600 dark:text-gray-300">PHP {deposit.amount}</td>
                      <td className="px-4 py-3 text-gray-600 dark:text-gray-300">{deposit.method ?? '—'}</td>
                      <td className="px-4 py-3 text-theme-xs text-gray-500 dark:text-gray-400">{deposit.proof_path ? 'Uploaded' : 'None'}</td>
                      <td className="px-4 py-3">
                        <div className="flex gap-2">
                          <button type="button" onClick={() => post(`/admin/deposits/${deposit.id}/approve`)} className="rounded bg-success-500 px-3 py-1 text-theme-xs font-medium text-white">Approve</button>
                          <button
                            type="button"
                            onClick={() => {
                              const reason = window.prompt('Rejection reason');
                              if (reason) post(`/admin/deposits/${deposit.id}/reject`, { reason });
                            }}
                            className="rounded bg-error-500 px-3 py-1 text-theme-xs font-medium text-white"
                          >
                            Reject
                          </button>
                        </div>
                      </td>
                    </tr>
                  ))}
                  {deposits.length === 0 && (
                    <tr><td colSpan={6} className="px-4 py-6 text-center text-theme-sm text-gray-500">No deposits awaiting review.</td></tr>
                  )}
                </tbody>
              </table>
            </div>
          </section>

          <section className="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
            <h2 className="border-b border-gray-100 px-5 py-4 text-lg font-semibold text-gray-800 dark:border-gray-800 dark:text-white/90">Withdrawals</h2>
            <div className="overflow-x-auto">
              <table className="w-full text-left text-theme-sm">
                <thead className="text-theme-xs uppercase text-gray-500 dark:text-gray-400">
                  <tr>
                    <th className="px-4 py-3">Reference</th>
                    <th className="px-4 py-3">Player</th>
                    <th className="px-4 py-3">Amount</th>
                    <th className="px-4 py-3">Status</th>
                    <th className="px-4 py-3">Actions</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-gray-100 dark:divide-gray-800">
                  {withdrawals.map((withdrawal) => (
                    <tr key={withdrawal.id}>
                      <td className="px-4 py-3 font-medium text-gray-800 dark:text-white/90">{withdrawal.reference}</td>
                      <td className="px-4 py-3 text-gray-600 dark:text-gray-300">{withdrawal.player}</td>
                      <td className="px-4 py-3 text-gray-600 dark:text-gray-300">PHP {withdrawal.amount}</td>
                      <td className="px-4 py-3 capitalize text-gray-600 dark:text-gray-300">{withdrawal.status.replace('_', ' ')}</td>
                      <td className="px-4 py-3">
                        <div className="flex flex-wrap gap-2">
                          {(withdrawal.status === 'pending' || withdrawal.status === 'under_review') && (
                            <button type="button" onClick={() => post(`/admin/withdrawals/${withdrawal.id}/approve`)} className="rounded bg-brand-500 px-3 py-1 text-theme-xs font-medium text-white">Approve</button>
                          )}
                          {(withdrawal.status === 'approved' || withdrawal.status === 'processing') && (
                            <button type="button" onClick={() => post(`/admin/withdrawals/${withdrawal.id}/complete`)} className="rounded bg-success-500 px-3 py-1 text-theme-xs font-medium text-white">Confirm payout</button>
                          )}
                          <button
                            type="button"
                            onClick={() => {
                              const reason = window.prompt('Rejection reason');
                              if (reason) post(`/admin/withdrawals/${withdrawal.id}/reject`, { reason });
                            }}
                            className="rounded bg-error-500 px-3 py-1 text-theme-xs font-medium text-white"
                          >
                            Reject
                          </button>
                        </div>
                      </td>
                    </tr>
                  ))}
                  {withdrawals.length === 0 && (
                    <tr><td colSpan={5} className="px-4 py-6 text-center text-theme-sm text-gray-500">No open withdrawals.</td></tr>
                  )}
                </tbody>
              </table>
            </div>
          </section>
        </div>
      </main>
    </AuthenticatedLayout>
  );
}
