<?php

namespace Database\Seeders;

use App\Models\SystemSetting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            ['key' => 'wallet.currency', 'value' => 'PHP', 'type' => 'string', 'group' => 'wallet'],
            ['key' => 'wallet.is_test_credit', 'value' => true, 'type' => 'boolean', 'group' => 'wallet'],
            ['key' => 'wallet.test_credit_label', 'value' => 'TEST CREDIT', 'type' => 'string', 'group' => 'wallet'],
            ['key' => 'wallet.min_deposit_minor', 'value' => 10_000, 'type' => 'integer', 'group' => 'wallet'],
            ['key' => 'wallet.max_deposit_minor', 'value' => 5_000_000, 'type' => 'integer', 'group' => 'wallet'],
            ['key' => 'wallet.min_withdrawal_minor', 'value' => 10_000, 'type' => 'integer', 'group' => 'wallet'],

            // A game point is worth this many minor units. Configurable, never hardcoded.
            ['key' => 'game.point_value_minor', 'value' => 1_000, 'type' => 'integer', 'group' => 'game'],
            ['key' => 'game.players_per_table', 'value' => 4, 'type' => 'integer', 'group' => 'game'],
            ['key' => 'game.arrangement_timeout_seconds', 'value' => 120, 'type' => 'integer', 'group' => 'game'],
            ['key' => 'game.max_loss_per_opponent', 'value' => 3, 'type' => 'integer', 'group' => 'game'],
            ['key' => 'game.max_game_point_loss', 'value' => 9, 'type' => 'integer', 'group' => 'game'],

            ['key' => 'responsible_gaming.min_age', 'value' => 21, 'type' => 'integer', 'group' => 'responsible_gaming'],
            ['key' => 'maintenance.enabled', 'value' => false, 'type' => 'boolean', 'group' => 'system'],
        ];

        foreach ($settings as $setting) {
            SystemSetting::query()->updateOrCreate(
                ['key' => $setting['key']],
                [
                    'value' => $setting['value'],
                    'type' => $setting['type'],
                    'group' => $setting['group'],
                ],
            );
        }
    }
}
