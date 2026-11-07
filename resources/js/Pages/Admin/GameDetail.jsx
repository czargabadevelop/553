import { Head } from '@inertiajs/react';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout';

export default function AdminGameDetail({ game, players, comparisons, audit }) {
  return (
    <AuthenticatedLayout>
      <Head title={`Game ${game.code}`} />

      <main>
        <div className="mx-auto max-w-(--breakpoint-2xl) p-4 md:p-6">
          <div className="mb-5">
            <h1 className="text-xl font-semibold text-gray-800 dark:text-white/90">{game.code}</h1>
            <p className="text-theme-sm text-gray-500 dark:text-gray-400">
              {game.room} · {game.status.replace('_', ' ')} · settled {game.settled ? 'yes' : 'no'} · deck {game.deck_fingerprint?.slice(0, 16)}…
            </p>
            <p className="mt-1 text-theme-xs text-gray-500 dark:text-gray-400">
              Completed games are read-only. Corrections require a compensating, audited transaction.
            </p>
          </div>

          <div className="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
            {players.map((player) => (
              <div key={player.id} className="rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-white/[0.03]">
                <div className="flex items-center justify-between">
                  <p className="text-theme-sm font-semibold text-gray-800 dark:text-white/90">{player.name}</p>
                  <span className="text-theme-xs text-gray-500 dark:text-gray-400">Seat {player.seat}</span>
                </div>
                <p className="mt-1 text-theme-xs capitalize text-gray-500 dark:text-gray-400">
                  {player.status.replace('_', ' ')} · {player.game_points ?? '—'} points
                </p>
                {player.is_fouled && <p className="mt-1 text-theme-xs text-error-500">Foul: {player.foul_reason}</p>}
                <div className="mt-3 space-y-1 text-theme-xs">
                  {['back', 'middle', 'front'].map((position) => (
                    <p key={position} className="text-gray-600 dark:text-gray-300">
                      <span className="uppercase text-gray-400">{position}:</span> {(player.hands?.[position] ?? []).join(' ')}
                    </p>
                  ))}
                </div>
              </div>
            ))}
          </div>

          <div className="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
            <section className="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
              <h2 className="mb-3 text-lg font-semibold text-gray-800 dark:text-white/90">Hand comparisons</h2>
              <div className="max-h-96 overflow-y-auto text-theme-xs">
                <table className="w-full text-left">
                  <thead className="text-gray-500 dark:text-gray-400">
                    <tr>
                      <th className="py-1">Position</th>
                      <th className="py-1">Pair</th>
                      <th className="py-1">Winner</th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-gray-100 dark:divide-gray-800">
                    {comparisons.map((comparison, index) => (
                      <tr key={index}>
                        <td className="py-1.5 capitalize text-gray-600 dark:text-gray-300">{comparison.position}</td>
                        <td className="py-1.5 text-gray-600 dark:text-gray-300">{comparison.player_one_id} vs {comparison.player_two_id}</td>
                        <td className="py-1.5 text-gray-800 dark:text-white/90">{comparison.winner_player_id ?? 'Tie'}</td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            </section>

            <section className="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
              <h2 className="mb-3 text-lg font-semibold text-gray-800 dark:text-white/90">Game audit</h2>
              <ul className="space-y-2 text-theme-xs">
                {audit.map((entry, index) => (
                  <li key={index} className="flex justify-between">
                    <span className="text-gray-600 dark:text-gray-300">{entry.action}</span>
                    <span className="text-gray-500 dark:text-gray-400">{entry.actor} · {entry.created_at}</span>
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
