import { Head, Link } from '@inertiajs/react';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout';

export default function History({ games }) {
  return (
    <AuthenticatedLayout>
      <Head title="Game History" />

      <main>
        <div className="mx-auto max-w-(--breakpoint-2xl) p-4 md:p-6">
          <h1 className="mb-6 text-xl font-semibold text-gray-800 dark:text-white/90">Game history</h1>

          <div className="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
            <div className="overflow-x-auto">
              <table className="w-full text-left text-theme-sm">
                <thead className="border-b border-gray-100 text-theme-xs uppercase text-gray-500 dark:border-gray-800 dark:text-gray-400">
                  <tr>
                    <th className="px-4 py-3">Game</th>
                    <th className="px-4 py-3">Table</th>
                    <th className="px-4 py-3">Points</th>
                    <th className="px-4 py-3">PHP</th>
                    <th className="px-4 py-3">Fee</th>
                    <th className="px-4 py-3">Net</th>
                    <th className="px-4 py-3">Completed</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-gray-100 dark:divide-gray-800">
                  {games.data.length === 0 && (
                    <tr>
                      <td colSpan={7} className="px-4 py-6 text-center text-gray-500 dark:text-gray-400">
                        No completed games yet.
                      </td>
                    </tr>
                  )}

                  {games.data.map((game) => (
                    <tr key={game.id} className="hover:bg-gray-50 dark:hover:bg-white/5">
                      <td className="px-4 py-3">
                        <Link href={`/games/${game.id}`} className="font-medium text-brand-500">{game.code}</Link>
                        {game.fouled && <span className="ml-2 rounded bg-error-50 px-2 py-0.5 text-theme-xs text-error-600">Foul</span>}
                      </td>
                      <td className="px-4 py-3 text-gray-600 dark:text-gray-300">{game.room}</td>
                      <td className="px-4 py-3 text-gray-800 dark:text-white/90">{game.game_points > 0 ? '+' : ''}{game.game_points}</td>
                      <td className="px-4 py-3 text-gray-600 dark:text-gray-300">{game.php_equivalent}</td>
                      <td className="px-4 py-3 text-gray-600 dark:text-gray-300">{game.fee}</td>
                      <td className={`px-4 py-3 font-medium ${Number(game.net) > 0 ? 'text-success-600' : Number(game.net) < 0 ? 'text-error-500' : 'text-gray-600'}`}>{game.net}</td>
                      <td className="px-4 py-3 text-theme-xs text-gray-500 dark:text-gray-400">{game.completed_at}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>

            <div className="flex items-center justify-between border-t border-gray-100 px-4 py-3 text-theme-xs dark:border-gray-800">
              <span className="text-gray-500 dark:text-gray-400">
                Page {games.current_page} of {games.last_page}
              </span>
              <div className="flex gap-2">
                {games.prev_page_url && <Link href={games.prev_page_url} className="rounded border border-gray-200 px-3 py-1 dark:border-gray-700">Previous</Link>}
                {games.next_page_url && <Link href={games.next_page_url} className="rounded border border-gray-200 px-3 py-1 dark:border-gray-700">Next</Link>}
              </div>
            </div>
          </div>
        </div>
      </main>
    </AuthenticatedLayout>
  );
}
