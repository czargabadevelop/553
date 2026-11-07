import { Head, Link, usePage } from '@inertiajs/react';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout';

function StatCard({ label, value, hint }) {
  return (
    <div className="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
      <p className="text-theme-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">{label}</p>
      <p className="mt-2 text-2xl font-semibold text-gray-800 dark:text-white/90">{value}</p>
      {hint && <p className="mt-1 text-theme-xs text-gray-500 dark:text-gray-400">{hint}</p>}
    </div>
  );
}

export default function PlayerDashboard({ wallet, stats, verification, recentGames, recentTransactions, rooms }) {
  const { auth } = usePage().props;

  return (
    <AuthenticatedLayout>
      <Head title="Player Dashboard" />

      <main>
        <div className="mx-auto max-w-(--breakpoint-2xl) p-4 md:p-6">
          <div className="mb-6 flex flex-wrap items-center justify-between gap-3">
            <div>
              <h1 className="text-xl font-semibold text-gray-800 dark:text-white/90">
                Welcome back, {auth.user?.name}
              </h1>
              <p className="mt-1 text-theme-sm text-gray-500 dark:text-gray-400">
                Philippine Pepito · four players, 13 cards each
              </p>
            </div>
            <div className="flex items-center gap-2">
              {wallet?.is_test_credit && (
                <span className="rounded-full bg-warning-50 px-3 py-1 text-theme-xs font-semibold text-warning-600 dark:bg-warning-500/15 dark:text-warning-500">
                  TEST CREDIT — not real money
                </span>
              )}
              <span className="rounded-full bg-gray-100 px-3 py-1 text-theme-xs font-medium capitalize text-gray-600 dark:bg-white/5 dark:text-gray-300">
                KYC: {verification.replace('_', ' ')}
              </span>
            </div>
          </div>

          <div className="grid grid-cols-12 gap-4 md:gap-6">
            <div className="col-span-12 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:col-span-8 xl:grid-cols-4">
              <StatCard label="Available" value={`${wallet?.currency ?? 'PHP'} ${wallet?.available ?? '0.00'}`} hint="Spendable cash points" />
              <StatCard label="Reserved" value={`${wallet?.currency ?? 'PHP'} ${wallet?.reserved ?? '0.00'}`} hint="Held for tables and payouts" />
              <StatCard label="Game points" value={stats.game_points} hint={`${stats.played} games played`} />
              <StatCard label="Wins / Losses" value={`${stats.wins} / ${stats.losses}`} hint={`${stats.fouls} fouls`} />

              <div className="col-span-12 rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
                <div className="mb-4 flex items-center justify-between">
                  <h2 className="text-lg font-semibold text-gray-800 dark:text-white/90">Recent games</h2>
                  <Link href="/history" className="text-theme-sm font-medium text-brand-500">View history</Link>
                </div>

                {recentGames.length === 0 ? (
                  <p className="text-theme-sm text-gray-500 dark:text-gray-400">No completed games yet.</p>
                ) : (
                  <div className="space-y-3">
                    {recentGames.map((game) => (
                      <Link
                        key={game.id}
                        href={`/games/${game.id}`}
                        className="flex items-center justify-between rounded-xl border border-gray-100 px-4 py-3 hover:bg-gray-50 dark:border-gray-800 dark:hover:bg-white/5"
                      >
                        <div>
                          <p className="text-theme-sm font-medium text-gray-800 dark:text-white/90">{game.code} · {game.room}</p>
                          <p className="text-theme-xs text-gray-500 dark:text-gray-400">{game.completed_at}</p>
                        </div>
                        <span className={`text-theme-sm font-semibold ${game.points > 0 ? 'text-success-600' : game.points < 0 ? 'text-error-500' : 'text-gray-500'}`}>
                          {game.points > 0 ? '+' : ''}{game.points} pts
                        </span>
                      </Link>
                    ))}
                  </div>
                )}
              </div>

              <div className="col-span-12 rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
                <h2 className="mb-4 text-lg font-semibold text-gray-800 dark:text-white/90">Recent reservations</h2>
                {recentTransactions.length === 0 ? (
                  <p className="text-theme-sm text-gray-500 dark:text-gray-400">No wallet activity yet.</p>
                ) : (
                  <ul className="divide-y divide-gray-100 dark:divide-gray-800">
                    {recentTransactions.map((entry) => (
                      <li key={entry.reference} className="flex items-center justify-between py-3">
                        <div>
                          <p className="text-theme-sm font-medium text-gray-800 dark:text-white/90">{entry.reference}</p>
                          <p className="text-theme-xs capitalize text-gray-500 dark:text-gray-400">{entry.type.replace('_', ' ')}</p>
                        </div>
                        <span className="text-theme-sm text-gray-700 dark:text-gray-300">PHP {entry.amount}</span>
                      </li>
                    ))}
                  </ul>
                )}
              </div>
            </div>

            <div className="col-span-12 xl:col-span-4">
              <div className="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
                <div className="mb-4 flex items-center justify-between">
                  <h2 className="text-lg font-semibold text-gray-800 dark:text-white/90">Available tables</h2>
                  <Link href="/lobby" className="text-theme-sm font-medium text-brand-500">Lobby</Link>
                </div>
                <div className="space-y-3">
                  {rooms.map((room) => (
                    <Link
                      key={room.id}
                      href="/lobby"
                      className="block rounded-xl border border-gray-100 px-4 py-3 hover:bg-gray-50 dark:border-gray-800 dark:hover:bg-white/5"
                    >
                      <p className="text-theme-sm font-medium text-gray-800 dark:text-white/90">{room.name}</p>
                      <p className="text-theme-xs text-gray-500 dark:text-gray-400">
                        Entry PHP {room.entry} · {room.seated}/{room.max_players} seated
                      </p>
                    </Link>
                  ))}
                </div>
              </div>
            </div>
          </div>
        </div>
      </main>
    </AuthenticatedLayout>
  );
}
