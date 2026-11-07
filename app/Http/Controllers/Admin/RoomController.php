<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Audit\AuditLogger;
use App\Enums\RoomSeatStatus;
use App\Enums\RoomStatus;
use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Support\MoneyInput;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class RoomController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Rooms', [
            'rooms' => Room::query()
                ->with('settings')
                ->orderBy('sort_order')
                ->get()
                ->map(fn (Room $room) => [
                    'id' => $room->id,
                    'name' => $room->name,
                    'slug' => $room->slug,
                    'description' => $room->description,
                    'entry_fee' => $room->entryFee()->toDecimalString(),
                    'platform_fee' => $room->platformFee()->toDecimalString(),
                    'status' => $room->status->value,
                    'is_active' => $room->is_active,
                    'seated' => $room->seats()->whereIn('status', [RoomSeatStatus::Seated->value, RoomSeatStatus::Ready->value])->count(),
                    'max_players' => $room->max_players,
                    'active_games' => $room->games()->whereNotIn('status', ['completed', 'cancelled'])->count(),
                    'games_played' => $room->games()->where('status', 'completed')->count(),
                ]),
        ]);
    }

    public function store(Request $request, AuditLogger $audit): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'entry_fee' => ['required', 'string', 'regex:/^\d{1,13}(\.\d{1,2})?$/'],
            'platform_fee' => ['required', 'string', 'regex:/^\d{1,13}(\.\d{1,2})?$/'],
        ]);

        $room = Room::create([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']).'-'.Str::lower(Str::random(4)),
            'description' => $validated['description'] ?? null,
            'entry_fee_minor' => MoneyInput::toMinor($validated['entry_fee']),
            'platform_fee_minor' => MoneyInput::toMinor($validated['platform_fee']),
            'min_players' => 4,
            'max_players' => 4,
            'status' => RoomStatus::Waiting,
            'is_active' => true,
            'sort_order' => (int) Room::query()->max('sort_order') + 1,
        ]);

        $room->settings()->create([]);

        $audit->log('room.created', $room, [], [
            'name' => $room->name,
            'entry_fee_minor' => $room->entry_fee_minor,
        ], $request->user());

        return back()->with('success', 'Room created.');
    }

    public function update(Request $request, Room $room, AuditLogger $audit): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'entry_fee' => ['sometimes', 'string', 'regex:/^\d{1,13}(\.\d{1,2})?$/'],
            'platform_fee' => ['sometimes', 'string', 'regex:/^\d{1,13}(\.\d{1,2})?$/'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $old = $room->only(['name', 'entry_fee_minor', 'platform_fee_minor', 'is_active']);

        $room->fill(array_filter([
            'name' => $validated['name'] ?? null,
            'description' => $validated['description'] ?? null,
        ], fn ($value) => $value !== null));

        if (isset($validated['entry_fee'])) {
            $room->entry_fee_minor = MoneyInput::toMinor($validated['entry_fee']);
        }

        if (isset($validated['platform_fee'])) {
            $room->platform_fee_minor = MoneyInput::toMinor($validated['platform_fee']);
        }

        if (array_key_exists('is_active', $validated)) {
            $room->is_active = (bool) $validated['is_active'];
        }

        $room->save();

        $audit->log('room.updated', $room, $old, $room->only(['name', 'entry_fee_minor', 'platform_fee_minor', 'is_active']), $request->user());

        return back()->with('success', 'Room updated.');
    }
}
