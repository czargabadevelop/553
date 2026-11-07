import { Head, router, usePage } from '@inertiajs/react';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout';

export default function PlayerDetail({ player, games, transactions }) {
  const roles = usePage().props.auth?.user?.roles ?? [];
  const canModerate = roles.includes('super_administrator');

  const suspend = (event) => {
    event.preventDefault();
    const reason = event.target.reason.value;
    router.post(`/admin/players/${player.id}/suspend`, { reason }, { preserveScroll: true });
  };

  return (
    <AuthenticatedLayout>
      <Head title={`Player ${player.name}`} />

      <main>
        <div className="mx-auto max-w-(--breakpoint-2xl) p-4 md:p-6">
          <div className="mb-5">
            <h1 className="text-xl font-semibold text-gray-800 dark:text-white/90">{player.name}</h1>
            <p className="text-theme-sm text-gray-500 dark:text-gray-400">
              {player.username} · {player.email} · {player.account_status} · KYC {player.verification_status.replace('_', ' ')}
            </p>
          </div>

          <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <section className="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
              <h2 className="mb-3 text-lg font-semibold text-gray-800 dark:text-white/90">Wallet</h2>
              <p className="text-theme-sm text-gray-600 dark:text-gray-300">Available: {player.wallet?.available ?? '0.00'}</p>
              <p className="text-theme-sm text-gray-600 dark:text-gray-300">Reserved: {player.wallet?.reserved ?? '0.00'}</p>

              {canModerate && (
                <form onSubmit={suspend} className="mt-4 space-y-2">
                  <input name="reason" required placeholder="Suspension reason" className="w-full rounded-lg border border-gray-200 px-3 py-2 text-theme-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white" />
                  <button type="submit" className="rounded-lg bg-error-500 px-4 py-2 text-theme-sm font-medium text-white">Suspend account</button>
                </form>
              )}

              {canModerate && player.account_status === 'suspended' && (
                <button
                  type="button"
                  onClick={() => router.post(`/admin/players/${player.id}/reactivate`)}
                  className="mt-3 rounded-lg bg-success-500 px-4 py-2 text-theme-sm font-medium text-white"
                >
                  Reactivate account
                </button>
              )}
            </section>

            <section className="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
              <h2 className="mb-3 text-lg font-semibold text-gray-800 dark:text-white/90">Recent games</h2>
              <ul className="space-y-2 text-theme-sm">
                {games.map((game) => (
                  <li key={game.id} className="flex justify-between">
                    <a href={`/admin/games/${game.id}`} className="text-brand-500">{game.code}</a>
                    <span className="text-gray-600 dark:text-gray-300">{game.game_points ?? '—'} pts {game.is_fouled ? '(foul)' : ''}</span>
                  </li>
                ))}
                {games.length === 0 && <li className="text-gray-500">No games.</li>}
              </ul>
            </section>

            <section className="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
              <h2 className="mb-3 text-lg font-semibold text-gray-800 dark:text-white/90">Ledger entries</h2>
              <ul className="space-y-2 text-theme-xs">
                {transactions.map((entry) => (
                  <li key={`${entry.reference}-${entry.account}`} className="flex justify-between">
                    <span className="capitalize text-gray-600 dark:text-gray-300">{entry.type?.replace('_', ' ')}</span>
                    <span className="font-medium text-gray-800 dark:text-white/90">{entry.amount}</span>
                  </li>
                ))}
                {transactions.length === 0 && <li className="text-gray-500">No ledger activity.</li>}
              </ul>
            </section>
          </div>
        </div>
      </main>
    </AuthenticatedLayout>
  );
}
