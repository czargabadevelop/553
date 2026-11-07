import { Head, Link, router } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout';
import { echo } from '../../echo';

export default function Lobby({ rooms }) {
  const act = (url) => router.post(url, {}, { preserveScroll: true });

  // The socket keeps the room cards live; an Inertia visit still wins because
  // it delivers fresh authoritative props.
  const [liveRooms, setLiveRooms] = useState(rooms);

  useEffect(() => {
    setLiveRooms(rooms);
  }, [rooms]);

  const seatedInRoom = liveRooms.some((room) => room.my_seat);
  const activeGame = liveRooms.find((room) => room.my_seat && room.active_game)?.active_game ?? null;

  useEffect(() => {
    if (!echo) {
      return undefined;
    }

    const channel = echo.channel('lobby');

    channel.listen('.lobby.updated', (event) => {
      setLiveRooms((current) =>
        current.map((room) => {
          const update = event.rooms?.find((item) => item.id === room.id);

          return update
            ? {
                ...room,
                status: update.status,
                seated: update.seated,
                ready: update.ready,
                active_game: update.active_game,
              }
            : room;
        })
      );
    });

    return () => echo.leave('lobby');
  }, []);

  // Safety net: the socket gives instant updates, this slow poll guarantees the
  // seat counts can never stay stale (missed event, dropped connection, etc.).
  useEffect(() => {
    if (!seatedInRoom || activeGame) {
      return undefined;
    }

    const timer = setInterval(() => {
      router.reload({ only: ['rooms'], preserveScroll: true, preserveState: true });
    }, 10000);

    return () => clearInterval(timer);
  }, [seatedInRoom, activeGame]);

  // When the last player readies up, walk every seated player to the table.
  useEffect(() => {
    if (activeGame) {
      router.visit(`/games/${activeGame}`);
    }
  }, [activeGame]);

  return (
    <AuthenticatedLayout>
      <Head title="Lobby" />

      <main>
        <div className="mx-auto max-w-(--breakpoint-2xl) p-4 md:p-6">
          <div className="mb-6">
            <h1 className="text-xl font-semibold text-gray-800 dark:text-white/90">Game lobby</h1>
            <p className="mt-1 text-theme-sm text-gray-500 dark:text-gray-400">
              Four players, 13 cards each. The full entry reservation is disclosed before you sit down.
            </p>
          </div>

          <div className="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
            {liveRooms.map((room) => {
              const full = room.seated >= room.max_players;
              const seated = Boolean(room.my_seat);
              const ready = room.my_seat?.status === 'ready';

              return (
                <div key={room.id} className="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
                  <div className="flex items-start justify-between">
                    <div>
                      <h2 className="text-lg font-semibold text-gray-800 dark:text-white/90">{room.name}</h2>
                      <p className="mt-1 text-theme-sm text-gray-500 dark:text-gray-400">{room.description}</p>
                    </div>
                    <span className="rounded-full bg-gray-100 px-3 py-1 text-theme-xs font-medium capitalize text-gray-600 dark:bg-white/5 dark:text-gray-300">
                      {room.status.replace('_', ' ')}
                    </span>
                  </div>

                  <dl className="mt-4 space-y-2 text-theme-sm">
                    <div className="flex justify-between">
                      <dt className="text-gray-500 dark:text-gray-400">Entry amount</dt>
                      <dd className="font-medium text-gray-800 dark:text-white/90">PHP {room.entry}</dd>
                    </div>
                    <div className="flex justify-between">
                      <dt className="text-gray-500 dark:text-gray-400">Platform fee</dt>
                      <dd className="font-medium text-gray-800 dark:text-white/90">PHP {room.platform_fee}</dd>
                    </div>
                    <div className="flex justify-between">
                      <dt className="text-gray-500 dark:text-gray-400">Total reservation</dt>
                      <dd className="font-semibold text-brand-500">PHP {room.required_reservation}</dd>
                    </div>
                    <div className="flex justify-between">
                      <dt className="text-gray-500 dark:text-gray-400">Seats</dt>
                      <dd className="font-medium text-gray-800 dark:text-white/90">
                        {room.seated}/{room.max_players}
                        <span className="ml-2 text-theme-xs text-gray-500 dark:text-gray-400">{room.ready ?? 0} ready</span>
                      </dd>
                    </div>
                  </dl>

                  <div className="mt-5 flex flex-wrap gap-2">
                    {!seated && (
                      <button
                        type="button"
                        disabled={full}
                        onClick={() => act(`/lobby/${room.id}/join`)}
                        className="rounded-lg bg-brand-500 px-4 py-2 text-theme-sm font-medium text-white hover:bg-brand-600 disabled:cursor-not-allowed disabled:opacity-50"
                      >
                        {full ? 'Table full' : 'Take a seat'}
                      </button>
                    )}

                    {seated && !ready && (
                      <button
                        type="button"
                        onClick={() => act(`/lobby/${room.id}/ready`)}
                        className="rounded-lg bg-success-500 px-4 py-2 text-theme-sm font-medium text-white hover:bg-success-600"
                      >
                        Ready up
                      </button>
                    )}

                    {seated && (
                      <button
                        type="button"
                        onClick={() => act(`/lobby/${room.id}/leave`)}
                        className="rounded-lg border border-gray-200 px-4 py-2 text-theme-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5"
                      >
                        Leave
                      </button>
                    )}

                    {room.active_game && (
                      <Link
                        href={`/games/${room.active_game}`}
                        className="rounded-lg bg-warning-500 px-4 py-2 text-theme-sm font-medium text-white hover:bg-warning-600"
                      >
                        Open table
                      </Link>
                    )}
                  </div>

                  {seated && (
                    <p className="mt-3 text-theme-xs text-gray-500 dark:text-gray-400">
                      You are seated at position {room.my_seat.seat_number} · {ready ? 'ready' : 'not ready'}
                    </p>
                  )}
                </div>
              );
            })}
          </div>
        </div>
      </main>
    </AuthenticatedLayout>
  );
}
