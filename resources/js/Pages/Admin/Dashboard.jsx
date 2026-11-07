import { Head } from '@inertiajs/react';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout';

function Metric({ label, value, hint }) {
  return (
    <div className="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
      <p className="text-theme-xs uppercase text-gray-500 dark:text-gray-400">{label}</p>
      <p className="mt-2 text-2xl font-semibold text-gray-800 dark:text-white/90">{value}</p>
      {hint && <p className="mt-1 text-theme-xs text-gray-500 dark:text-gray-400">{hint}</p>}
    </div>
  );
}

export default function AdminDashboard({ metrics, recentGames, recentAudit }) {
  return (
    <AuthenticatedLayout>
      <Head title="Admin Overview" />

      <main>
        <div className="mx-auto max-w-(--breakpoint-2xl) p-4 md:p-6">
          <h1 className="mb-6 text-xl font-semibold text-gray-800 dark:text-white/90">Platform overview</h1>

          <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <Metric label="Players" value={metrics.players} hint={`${metrics.verified_players} verified · ${metrics.active_players} active this week`} />
            <Metric label="Games" value={metrics.games_total} hint={`${metrics.games_active} active · ${metrics.games_completed} completed`} />
            <Metric label="Deposits" value={`PHP ${metrics.deposits_total}`} hint={`${metrics.pending_deposits} awaiting review`} />
            <Metric label="Withdrawals" value={`PHP ${metrics.withdrawals_total}`} hint={`${metrics.pending_withdrawals} open requests`} />
            <Metric label="Platform revenue" value={`PHP ${metrics.platform_revenue}`} hint="Fees only, recorded in the ledger" />
            <Metric label="Wallet available" value={`PHP ${metrics.wallet_available}`} />
            <Metric label="Wallet reserved" value={`PHP ${metrics.wallet_reserved}`} />
            <Metric label="Pending requests" value={metrics.pending_deposits + metrics.pending_withdrawals} hint="Deposits + withdrawals" />
          </div>

          <div className="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
            <section className="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
              <h2 className="mb-4 text-lg font-semibold text-gray-800 dark:text-white/90">Recent games</h2>
              <div className="space-y-3">
                {recentGames.map((game) => (
                  <a key={game.id} href={`/admin/games/${game.id}`} className="flex items-center justify-between rounded-xl border border-gray-100 px-4 py-3 hover:bg-gray-50 dark:border-gray-800 dark:hover:bg-white/5">
                    <div>
                      <p className="text-theme-sm font-medium text-gray-800 dark:text-white/90">{game.code}</p>
                      <p className="text-theme-xs text-gray-500 dark:text-gray-400">{game.room} · {game.player_count} players</p>
                    </div>
                    <span className="text-theme-xs capitalize text-gray-600 dark:text-gray-300">{game.status.replace('_', ' ')}</span>
                  </a>
                ))}
              </div>
            </section>

            <section className="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
              <h2 className="mb-4 text-lg font-semibold text-gray-800 dark:text-white/90">Audit trail</h2>
              <ul className="space-y-3">
                {recentAudit.map((entry, index) => (
                  <li key={`${entry.action}-${index}`} className="flex items-center justify-between text-theme-sm">
                    <span className="text-gray-700 dark:text-gray-200">{entry.action}</span>
                    <span className="text-theme-xs text-gray-500 dark:text-gray-400">{entry.actor ?? 'system'}</span>
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
