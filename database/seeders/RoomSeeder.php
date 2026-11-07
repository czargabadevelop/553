<?php

namespace Database\Seeders;

use App\Enums\RoomStatus;
use App\Models\PlatformFeeSetting;
use App\Models\Room;
use Illuminate\Database\Seeder;

class RoomSeeder extends Seeder
{
    public function run(): void
    {
        $rooms = [
            ['name' => 'Bronze Table', 'slug' => 'bronze-table', 'entry_fee_minor' => 1_000, 'sort_order' => 1],
            ['name' => 'Silver Table', 'slug' => 'silver-table', 'entry_fee_minor' => 5_000, 'sort_order' => 2],
            ['name' => 'Gold Table', 'slug' => 'gold-table', 'entry_fee_minor' => 10_000, 'sort_order' => 3],
        ];

        foreach ($rooms as $room) {
            $model = Room::query()->updateOrCreate(
                ['slug' => $room['slug']],
                [
                    'name' => $room['name'],
                    'description' => 'Traditional 13-card Chinese Poker, four players.',
                    'entry_fee_minor' => $room['entry_fee_minor'],
                    'platform_fee_minor' => 0,
                    'min_players' => 4,
                    'max_players' => 4,
                    'status' => RoomStatus::Waiting,
                    'is_active' => true,
                    'sort_order' => $room['sort_order'],
                ],
            );

            $model->settings()->updateOrCreate([], [
                'arrangement_timeout_seconds' => 120,
                'turn_timeout_seconds' => 30,
                'auto_start' => true,
            ]);

            PlatformFeeSetting::query()->updateOrCreate(
                ['room_id' => $model->id],
                ['amount_minor' => 0, 'currency' => 'PHP', 'is_enabled' => false],
            );
        }
    }
}
