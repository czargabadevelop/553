import { Head, useForm } from '@inertiajs/react';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout';

export default function AdminRooms({ rooms }) {
  const form = useForm({ name: '', description: '', entry_fee: '', platform_fee: '0.00' });

  const create = (event) => {
    event.preventDefault();
    form.post('/admin/rooms', { preserveScroll: true, onSuccess: () => form.reset() });
  };

  const toggle = (room) => {
    form.patch(`/admin/rooms/${room.id}`, { is_active: !room.is_active }, { preserveScroll: true });
  };

  return (
    <AuthenticatedLayout>
      <Head title="Rooms" />

      <main>
        <div className="mx-auto max-w-(--breakpoint-2xl) p-4 md:p-6">
          <h1 className="mb-6 text-xl font-semibold text-gray-800 dark:text-white/90">Room management</h1>

          <div className="grid grid-cols-1 gap-6 xl:grid-cols-3">
            <section className="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
              <h2 className="mb-4 text-lg font-semibold text-gray-800 dark:text-white/90">Create room</h2>
              <form onSubmit={create} className="space-y-3">
                <input className="w-full rounded-lg border border-gray-200 px-3 py-2 text-theme-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white" placeholder="Room name" value={form.data.name} onChange={(event) => form.setData('name', event.target.value)} />
                <textarea className="w-full rounded-lg border border-gray-200 px-3 py-2 text-theme-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white" placeholder="Description" value={form.data.description} onChange={(event) => form.setData('description', event.target.value)} />
                <input className="w-full rounded-lg border border-gray-200 px-3 py-2 text-theme-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white" placeholder="Entry fee (PHP)" value={form.data.entry_fee} onChange={(event) => form.setData('entry_fee', event.target.value)} />
                <input className="w-full rounded-lg border border-gray-200 px-3 py-2 text-theme-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white" placeholder="Platform fee (PHP)" value={form.data.platform_fee} onChange={(event) => form.setData('platform_fee', event.target.value)} />
                <button type="submit" disabled={form.processing} className="rounded-lg bg-brand-500 px-4 py-2 text-theme-sm font-medium text-white">Create room</button>
              </form>
            </section>

            <section className="xl:col-span-2 overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
              <div className="overflow-x-auto">
                <table className="w-full text-left text-theme-sm">
                  <thead className="border-b border-gray-100 text-theme-xs uppercase text-gray-500 dark:border-gray-800 dark:text-gray-400">
                    <tr>
                      <th className="px-4 py-3">Room</th>
                      <th className="px-4 py-3">Entry</th>
                      <th className="px-4 py-3">Fee</th>
                      <th className="px-4 py-3">Seated</th>
                      <th className="px-4 py-3">Games</th>
                      <th className="px-4 py-3">Active</th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-gray-100 dark:divide-gray-800">
                    {rooms.map((room) => (
                      <tr key={room.id}>
                        <td className="px-4 py-3 font-medium text-gray-800 dark:text-white/90">{room.name}</td>
                        <td className="px-4 py-3 text-gray-600 dark:text-gray-300">{room.entry_fee}</td>
                        <td className="px-4 py-3 text-gray-600 dark:text-gray-300">{room.platform_fee}</td>
                        <td className="px-4 py-3 text-gray-600 dark:text-gray-300">{room.seated}/{room.max_players}</td>
                        <td className="px-4 py-3 text-gray-600 dark:text-gray-300">{room.games_played}</td>
                        <td className="px-4 py-3">
                          <button type="button" onClick={() => toggle(room)} className={`rounded-full px-3 py-1 text-theme-xs font-medium ${room.is_active ? 'bg-success-50 text-success-600' : 'bg-gray-100 text-gray-500'}`}>
                            {room.is_active ? 'Enabled' : 'Disabled'}
                          </button>
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            </section>
          </div>
        </div>
      </main>
    </AuthenticatedLayout>
  );
}
