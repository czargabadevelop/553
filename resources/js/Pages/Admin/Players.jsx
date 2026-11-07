import { Head, Link, router } from '@inertiajs/react';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout';

export default function AdminPlayers({ players, filters }) {
  const search = (event) => {
    event.preventDefault();
    router.get('/admin/players', { search: event.target.search.value }, { preserveState: true });
  };

  return (
    <AuthenticatedLayout>
      <Head title="Players" />

      <main>
        <div className="mx-auto max-w-(--breakpoint-2xl) p-4 md:p-6">
          <div className="mb-5 flex flex-wrap items-center justify-between gap-3">
            <h1 className="text-xl font-semibold text-gray-800 dark:text-white/90">Players</h1>
            <form onSubmit={search} className="flex gap-2">
              <input name="search" defaultValue={filters.search} placeholder="Search name, username or email" className="rounded-lg border border-gray-200 px-3 py-2 text-theme-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white" />
              <button type="submit" className="rounded-lg bg-brand-500 px-4 py-2 text-theme-sm font-medium text-white">Search</button>
            </form>
          </div>

          <div className="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
            <div className="overflow-x-auto">
              <table className="w-full text-left text-theme-sm">
                <thead className="border-b border-gray-100 text-theme-xs uppercase text-gray-500 dark:border-gray-800 dark:text-gray-400">
                  <tr>
                    <th className="px-4 py-3">Player</th>
                    <th className="px-4 py-3">Status</th>
                    <th className="px-4 py-3">KYC</th>
                    <th className="px-4 py-3">Available</th>
                    <th className="px-4 py-3">Reserved</th>
                    <th className="px-4 py-3">Roles</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-gray-100 dark:divide-gray-800">
                  {players.data.map((player) => (
                    <tr key={player.id} className="hover:bg-gray-50 dark:hover:bg-white/5">
                      <td className="px-4 py-3">
                        <Link href={`/admin/players/${player.id}`} className="font-medium text-brand-500">{player.name}</Link>
                        <p className="text-theme-xs text-gray-500 dark:text-gray-400">{player.username} · {player.email}</p>
                      </td>
                      <td className="px-4 py-3 capitalize text-gray-600 dark:text-gray-300">{player.account_status}</td>
                      <td className="px-4 py-3 capitalize text-gray-600 dark:text-gray-300">{player.verification_status.replace('_', ' ')}</td>
                      <td className="px-4 py-3 text-gray-600 dark:text-gray-300">{player.available}</td>
                      <td className="px-4 py-3 text-gray-600 dark:text-gray-300">{player.reserved}</td>
                      <td className="px-4 py-3 text-theme-xs text-gray-500 dark:text-gray-400">{(player.roles ?? []).join(', ')}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
            <div className="flex items-center justify-between border-t border-gray-100 px-4 py-3 text-theme-xs dark:border-gray-800">
              <span className="text-gray-500 dark:text-gray-400">Page {players.current_page} of {players.last_page}</span>
              <div className="flex gap-2">
                {players.prev_page_url && <Link href={players.prev_page_url} className="rounded border border-gray-200 px-3 py-1 dark:border-gray-700">Previous</Link>}
                {players.next_page_url && <Link href={players.next_page_url} className="rounded border border-gray-200 px-3 py-1 dark:border-gray-700">Next</Link>}
              </div>
            </div>
          </div>
        </div>
      </main>
    </AuthenticatedLayout>
  );
}
