import { Head, router, usePage } from '@inertiajs/react';
import { useEffect, useMemo, useState } from 'react';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout';
import { echo } from '../../echo';

const SUIT_SYMBOLS = { s: '♠', h: '♥', d: '♦', c: '♣' };
const SUIT_COLORS = { s: 'text-gray-800 dark:text-white', h: 'text-error-500', d: 'text-error-500', c: 'text-gray-800 dark:text-white' };

const POSITIONS = [
  { key: 'back', label: 'Back', hint: '5 cards · strongest' },
  { key: 'middle', label: 'Middle', hint: '5 cards' },
  { key: 'front', label: 'Front', hint: '3 cards · weakest' },
];

function CardTile({ card, onClick, selected, draggable = true }) {
  return (
    <button
      type="button"
      draggable={draggable}
      onDragStart={(event) => event.dataTransfer.setData('text/plain', card.code)}
      onClick={onClick}
      className={`flex h-24 w-16 flex-col items-center justify-center rounded-xl border bg-white text-lg font-semibold shadow-theme-xs transition dark:bg-gray-900 ${
        selected ? 'border-brand-500 ring-2 ring-brand-500/40' : 'border-gray-200 hover:-translate-y-1 dark:border-gray-700'
      } ${SUIT_COLORS[card.suit]}`}
    >
      <span>{card.rank_label}</span>
      <span className="text-xl">{SUIT_SYMBOLS[card.suit]}</span>
    </button>
  );
}

export default function Table({ game, me, seats, room_id: roomId, rematch, next_game: nextGame }) {
  const { props } = usePage();
  const cards = props.cards ?? [];

  // Live updates: submissions, evaluation, and rematch readiness.
  useEffect(() => {
    if (!echo) {
      return undefined;
    }

    const gameChannel = echo.private(`game.${game.id}`);

    gameChannel.listen('.game.submitted', () => router.reload({ only: ['seats', 'me', 'results'], preserveScroll: true }));
    gameChannel.listen('.game.completed', () => router.reload());

    if (roomId) {
      const roomChannel = echo.private(`room.${roomId}`);

      roomChannel.listen('.room.ready', () => router.reload({ only: ['rematch', 'next_game', 'seats'], preserveScroll: true }));
      roomChannel.listen('.room.left', () => router.reload({ only: ['rematch', 'next_game'], preserveScroll: true }));
    }

    // The next round is announced on the lobby channel (game N+1 starts after
    // this page already reloaded for the last ready), so watch it directly.
    const lobbyChannel = echo.channel('lobby');

    lobbyChannel.listen('.lobby.updated', (event) => {
      const room = event.rooms?.find((item) => item.id === roomId);

      if (room?.active_game && room.active_game !== game.id) {
        router.visit(`/games/${room.active_game}`);
      }
    });

    return () => {
      echo.leave(`game.${game.id}`);
      echo.leave('lobby');

      if (roomId) {
        echo.leave(`room.${roomId}`);
      }
    };
  }, [game.id, roomId]);

  // A new round started while this player was still looking at the results.
  useEffect(() => {
    if (nextGame) {
      router.visit(`/games/${nextGame}`);
    }
  }, [nextGame]);

  // Safety net for the rematch panel: readiness changes arrive instantly over
  // the socket, and this slow poll guarantees the panel cannot stay stale.
  const showingRematch = Boolean(results && rematch);

  useEffect(() => {
    if (!showingRematch || nextGame) {
      return undefined;
    }

    const timer = setInterval(() => {
      router.reload({ only: ['rematch', 'next_game', 'seats'], preserveScroll: true });
    }, 6000);

    return () => clearInterval(timer);
  }, [showingRematch, nextGame]);

  const initial = useMemo(() => {
    const layout = { back: [], middle: [], front: [] };
    cards.forEach((card, index) => {
      if (card.position && layout[card.position]) layout[card.position].push(card.code);
      else if (index < 5) layout.back.push(card.code);
      else if (index < 10) layout.middle.push(card.code);
      else layout.front.push(card.code);
    });
    return layout;
  }, [cards]);

  const [hands, setHands] = useState(initial);
  const [selected, setSelected] = useState(null);
  const [error, setError] = useState('');

  const byCode = useMemo(() => Object.fromEntries(cards.map((card) => [card.code, card])), [cards]);
  const assigned = Object.values(hands).flat();

  const place = (position, code = selected) => {
    if (!code) return;
    const limit = position === 'front' ? 3 : 5;

    setHands((current) => {
      const next = { back: [...current.back], middle: [...current.middle], front: [...current.front] };
      Object.keys(next).forEach((key) => {
        next[key] = next[key].filter((item) => item !== code);
      });

      if (next[position].length >= limit) {
        setError(`${position} already has ${limit} cards.`);
        return current;
      }

      next[position].push(code);
      return next;
    });

    setSelected(null);
  };

  const drop = (event, position) => {
    event.preventDefault();
    const code = event.dataTransfer.getData('text/plain');
    if (code) {
      // Pass the dragged code explicitly: the `selected` state from this render
      // is still null, so relying on it silently dropped every dragged card.
      place(position, code);
    }
  };

  const removeCard = (code, position) => {
    setHands((current) => ({
      ...current,
      [position]: current[position].filter((item) => item !== code),
    }));
  };

  const submit = () => {
    if (hands.back.length !== 5 || hands.middle.length !== 5 || hands.front.length !== 3) {
      setError('The back and middle hands need 5 cards each and the front hand needs 3.');
      return;
    }

    setError('');
    router.post(`/games/${game.id}/hands`, hands, { preserveScroll: true });
  };

  const results = props.results;
  const errors = props.errors ?? {};
  const serverError = errors.back || errors.middle || errors.front || Object.values(errors)[0];

  return (
    <AuthenticatedLayout>
      <Head title={`Table ${game.code}`} />

      <main>
        <div className="mx-auto max-w-(--breakpoint-2xl) p-4 md:p-6">
          <div className="mb-5 flex flex-wrap items-center justify-between gap-3">
            <div>
              <h1 className="text-xl font-semibold text-gray-800 dark:text-white/90">{game.room}</h1>
              <p className="text-theme-sm text-gray-500 dark:text-gray-400">
                {game.code} · <span className="capitalize">{game.status.replace('_', ' ')}</span>
                {game.deadline && ` · arrange by ${new Date(game.deadline).toLocaleTimeString()}`}
              </p>
            </div>
            <div className="flex gap-2">
              <span className="rounded-full bg-gray-100 px-3 py-1 text-theme-xs font-medium text-gray-600 dark:bg-white/5 dark:text-gray-300">
                Seat {me.seat}
              </span>
              {me.submitted && (
                <span className="rounded-full bg-success-50 px-3 py-1 text-theme-xs font-semibold text-success-600">
                  Hands submitted
                </span>
              )}
              {me.fouled && (
                <span className="rounded-full bg-error-50 px-3 py-1 text-theme-xs font-semibold text-error-600">
                  Foul: {me.foul_reason}
                </span>
              )}
            </div>
          </div>

          <div className="mb-6 grid grid-cols-2 gap-3 md:grid-cols-4">
            {seats.map((seat) => (
              <div
                key={seat.game_player_id}
                className={`rounded-2xl border p-4 ${
                  seat.is_me
                    ? 'border-brand-500 bg-brand-50/60 dark:bg-brand-500/10'
                    : 'border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]'
                }`}
              >
                <p className="text-theme-xs uppercase text-gray-500 dark:text-gray-400">Seat {seat.seat}</p>
                <p className="mt-1 text-theme-sm font-semibold text-gray-800 dark:text-white/90">{seat.name}</p>
                <p className="mt-1 text-theme-xs capitalize text-gray-500 dark:text-gray-400">
                  {seat.submitted ? 'submitted' : seat.status.replace('_', ' ')}
                </p>
              </div>
            ))}
          </div>

          {!results && !me.submitted && (
            <>
              <div className="mb-5 rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
                <div className="mb-4 flex items-center justify-between">
                  <h2 className="text-lg font-semibold text-gray-800 dark:text-white/90">Your cards</h2>
                  <p className="text-theme-xs text-gray-500 dark:text-gray-400">Click a card, then a hand — or drag it.</p>
                </div>

                <div className="flex flex-wrap gap-2">
                  {cards.filter((card) => !assigned.includes(card.code)).map((card) => (
                    <CardTile
                      key={card.code}
                      card={card}
                      selected={selected === card.code}
                      onClick={() => setSelected(selected === card.code ? null : card.code)}
                    />
                  ))}
                  {assigned.length === 13 && (
                    <p className="text-theme-sm text-gray-500 dark:text-gray-400">All 13 cards arranged.</p>
                  )}
                </div>
              </div>

              <div className="mb-5 grid grid-cols-1 gap-4 md:grid-cols-3">
                {POSITIONS.map((position) => (
                  <div
                    key={position.key}
                    onDragOver={(event) => event.preventDefault()}
                    onDrop={(event) => drop(event, position.key)}
                    className="min-h-40 rounded-2xl border-2 border-dashed border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-white/[0.03]"
                  >
                    <div className="mb-3 flex items-center justify-between">
                      <div>
                        <p className="text-theme-sm font-semibold text-gray-800 dark:text-white/90">{position.label}</p>
                        <p className="text-theme-xs text-gray-500 dark:text-gray-400">{position.hint}</p>
                      </div>
                      <span className={`text-theme-sm font-semibold ${hands[position.key].length === (position.key === 'front' ? 3 : 5) ? 'text-success-600' : 'text-gray-400'}`}>
                        {hands[position.key].length}/{position.key === 'front' ? 3 : 5}
                      </span>
                    </div>

                    <div className="flex flex-wrap gap-2">
                      {hands[position.key].map((code) => {
                        const card = byCode[code];

                        return card ? (
                          <div key={code} className="relative">
                            <CardTile card={card} onClick={() => removeCard(code, position.key)} />
                            <span className="absolute -right-1 -top-1 rounded-full bg-gray-800 px-1 text-[10px] text-white">×</span>
                          </div>
                        ) : null;
                      })}
                    </div>

                    <button
                      type="button"
                      onClick={() => place(position.key)}
                      className="mt-3 w-full rounded-lg border border-gray-200 px-3 py-2 text-theme-xs font-medium text-gray-600 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5"
                    >
                      Place selected card here
                    </button>
                  </div>
                ))}
              </div>

              {(error || serverError) && (
                <p className="mb-3 text-theme-sm font-medium text-error-500">{error || serverError}</p>
              )}

              <button
                type="button"
                onClick={submit}
                disabled={me.submitted}
                className="rounded-lg bg-brand-500 px-6 py-3 text-theme-sm font-semibold text-white hover:bg-brand-600 disabled:cursor-not-allowed disabled:opacity-50"
              >
                {me.submitted ? 'Hands submitted' : 'Submit hands'}
              </button>
            </>
          )}

          {me.submitted && !results && (
            <div className="rounded-2xl border border-gray-200 bg-white p-6 text-theme-sm text-gray-500 dark:border-gray-800 dark:bg-white/[0.03] dark:text-gray-400">
              Your hands are locked in. Waiting for the other players — results will appear here.
            </div>
          )}

          {results && (
            <div className="space-y-6">
              {rematch && (
                <div className="rounded-2xl border border-brand-200 bg-brand-50/50 p-5 dark:border-brand-500/30 dark:bg-brand-500/10">
                  <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                      <h2 className="text-lg font-semibold text-gray-800 dark:text-white/90">Play another round</h2>
                      <p className="mt-1 text-theme-sm text-gray-500 dark:text-gray-400">
                        The table stays together. Everyone readies up again for the next hand.
                      </p>
                    </div>
                    <span className="rounded-full bg-white px-3 py-1 text-theme-xs font-semibold text-brand-600 dark:bg-white/10 dark:text-brand-400">
                      {rematch.seats.filter((seat) => seat.ready).length}/{rematch.seats.length} ready
                    </span>
                  </div>

                  <div className="mt-4 grid grid-cols-2 gap-3 md:grid-cols-4">
                    {rematch.seats.map((seat) => (
                      <div
                        key={seat.seat}
                        className={`rounded-xl border p-3 ${
                          seat.ready
                            ? 'border-success-500 bg-success-50/70 dark:bg-success-500/10'
                            : 'border-gray-200 bg-white dark:border-gray-700 dark:bg-white/[0.03]'
                        }`}
                      >
                        <p className="text-theme-xs uppercase text-gray-500 dark:text-gray-400">Seat {seat.seat}</p>
                        <p className="mt-1 text-theme-sm font-semibold text-gray-800 dark:text-white/90">
                          {seat.name}
                          {seat.is_me ? ' (you)' : ''}
                        </p>
                        <p className="mt-1 text-theme-xs capitalize text-gray-500 dark:text-gray-400">
                          {!seat.seated ? 'left the table' : seat.ready ? 'ready' : 'waiting'}
                        </p>
                      </div>
                    ))}
                  </div>

                  <div className="mt-4 flex flex-wrap items-center gap-3">
                    {rematch.me_ready ? (
                      <span className="text-theme-sm font-medium text-success-600">You are ready. Waiting for the table…</span>
                    ) : rematch.can_ready ? (
                      <button
                        type="button"
                        onClick={() => router.post(`/lobby/${roomId}/ready`, {}, { preserveScroll: true })}
                        className="rounded-lg bg-success-500 px-5 py-2.5 text-theme-sm font-semibold text-white hover:bg-success-600"
                      >
                        Ready for next round
                      </button>
                    ) : (
                      <span className="text-theme-sm text-gray-500 dark:text-gray-400">You are no longer seated at this table.</span>
                    )}

                    <button
                      type="button"
                      onClick={() => router.visit('/lobby')}
                      className="rounded-lg border border-gray-200 px-4 py-2 text-theme-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5"
                    >
                      Back to lobby
                    </button>
                  </div>
                </div>
              )}

              <div className="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
                <h2 className="mb-4 text-lg font-semibold text-gray-800 dark:text-white/90">Game results</h2>

                <div className="overflow-x-auto">
                  <table className="w-full text-left text-theme-sm">
                    <thead className="text-theme-xs uppercase text-gray-500 dark:text-gray-400">
                      <tr>
                        <th className="px-3 py-2">Player</th>
                        <th className="px-3 py-2">Points</th>
                        <th className="px-3 py-2">PHP equivalent</th>
                        <th className="px-3 py-2">Fee</th>
                        <th className="px-3 py-2">Net</th>
                      </tr>
                    </thead>
                    <tbody className="divide-y divide-gray-100 dark:divide-gray-800">
                      {results.players.map((player) => (
                        <tr key={player.game_player_id}>
                          <td className="px-3 py-3 font-medium text-gray-800 dark:text-white/90">
                            {player.name}
                            {player.is_fouled && <span className="ml-2 rounded bg-error-50 px-2 py-0.5 text-theme-xs text-error-600">Foul</span>}
                          </td>
                          <td className="px-3 py-3">{player.game_points > 0 ? '+' : ''}{player.game_points}</td>
                          <td className="px-3 py-3">{player.php_equivalent}</td>
                          <td className="px-3 py-3">{player.fee}</td>
                          <td className={`px-3 py-3 font-semibold ${Number(player.net) > 0 ? 'text-success-600' : Number(player.net) < 0 ? 'text-error-500' : ''}`}>
                            {player.net}
                          </td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
              </div>

              <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                {results.players.map((player) => (
                  <div key={player.game_player_id} className="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
                    <h3 className="mb-3 text-theme-sm font-semibold text-gray-800 dark:text-white/90">{player.name}</h3>
                    {['back', 'middle', 'front'].map((position) => (
                      <div key={position} className="mb-2 flex items-center gap-2">
                        <span className="w-14 text-theme-xs uppercase text-gray-500 dark:text-gray-400">{position}</span>
                        <div className="flex flex-wrap gap-1">
                          {(player.hands?.[position] ?? []).map((code) => (
                            <span key={code} className="rounded border border-gray-200 px-2 py-1 text-theme-xs font-medium text-gray-700 dark:border-gray-700 dark:text-gray-200">
                              {code.slice(0, -1)}{SUIT_SYMBOLS[code.slice(-1)]}
                            </span>
                          ))}
                        </div>
                      </div>
                    ))}
                    {player.is_fouled && <p className="mt-2 text-theme-xs text-error-500">{player.foul_reason}</p>}
                  </div>
                ))}
              </div>
            </div>
          )}
        </div>
      </main>
    </AuthenticatedLayout>
  );
}
