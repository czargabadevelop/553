import { Head, Link, router } from '@inertiajs/react';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout';

export default function AdminGames({ games, rooms, filters, statuses }) {
  const apply = (event) => {
    event.preventDefault();
    router.get('/admin/games', { status: event.target.status.value, room: event.target.room.value }, { preserveState: true });
  };

  return (
    <AuthenticatedLayout>
      <Head title="Games" />

      <main>
        <div className="mx-auto max-w-(--breakpoint-2xl) p-4 md:p-6">
          <div className="mb-5 flex flex-wrap items-center justify-between gap-3">
            <h1 className="text-xl font-semibold text-gray-800 dark:text-white/90">Game monitoring</h1>
            <form onSubmit={apply} className="flex gap-2">
              <select name="status" defaultValue={filters.status} className="rounded-lg border border-gray-200 px-3 py-2 text-theme-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                <option value="">All statuses</option>
                {statuses.map((status) => (
                  <option key={status} value={status}>{status.replace('_', ' ')}</option>
                ))}
              </select>
              <select name="room" defaultValue={filters.room ?? ''} className="rounded-lg border border-gray-200 px-3 py-2 text-theme-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                <option value="">All rooms</option>
                {rooms.map((room) => (
                  <option key={room.id} value={room.id}>{room.name}</option>
                ))}
              </select>
              <button type="submit" className="rounded-lg bg-brand-500 px-4 py-2 text-theme-sm font-medium text-white">Filter</button>
            </form>
          </div>

          <div className="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
            <div className="overflow-x-auto">
              <table className="w-full text-left text-theme-sm">
                <thead className="border-b border-gray-100 text-theme-xs uppercase text-gray-500 dark:border-gray-800 dark:text-gray-400">
                  <tr>
                    <th className="px-4 py-3">Game</th>
                    <th className="px-4 py-3">Room</th>
                    <th className="px-4 py-3">Status</th>
                    <th className="px-4 py-3">Players</th>
                    <th className="px-4 py-3">Settled</th>
                    <th className="px-4 py-3">Completed</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-gray-100 dark:divide-gray-800">
                  {games.data.map((game) => (
                    <tr key={game.id} className="hover:bg-gray-50 dark:hover:bg-white/5">
                      <td className="px-4 py-3"><Link href={`/admin/games/${game.id}`} className="font-medium text-brand-500">{game.code}</Link></td>
                      <td className="px-4 py-3 text-gray-600 dark:text-gray-300">{game.room}</td>
                      <td className="px-4 py-3 capitalize text-gray-600 dark:text-gray-300">{game.status.replace('_', ' ')}</td>
                      <td className="px-4 py-3 text-gray-600 dark:text-gray-300">{game.players}</td>
                      <td className="px-4 py-3 text-gray-600 dark:text-gray-300">{game.settled ? 'Yes' : 'No'}</td>
                      <td className="px-4 py-3 text-theme-xs text-gray-500 dark:text-gray-400">{game.completed_at ?? '—'}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
            <div className="flex items-center justify-between border-t border-gray-100 px-4 py-3 text-theme-xs dark:border-gray-800">
              <span className="text-gray-500 dark:text-gray-400">Page {games.current_page} of {games.last_page}</span>
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
