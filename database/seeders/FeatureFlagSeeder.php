<?php

namespace Database\Seeders;

use App\Domain\Settings\FeatureFlagService;
use App\Models\FeatureFlag;
use Illuminate\Database\Seeder;

class FeatureFlagSeeder extends Seeder
{
    public function run(): void
    {
        $descriptions = [
            FeatureFlagService::REAL_MONEY_DEPOSITS => 'Allows real-money deposit requests to be credited.',
            FeatureFlagService::REAL_MONEY_WITHDRAWALS => 'Allows real-money withdrawal requests to be paid out.',
            FeatureFlagService::CASH_EQUIVALENT_SETTLEMENT => 'Settles game points against real wallet balances.',
            FeatureFlagService::PLATFORM_FEES => 'Charges the configured platform fee per completed match.',
            FeatureFlagService::REAL_MONEY_ROOMS => 'Enables rooms that accept real money.',
        ];

        foreach ($descriptions as $key => $description) {
            FeatureFlag::query()->updateOrCreate(
                ['key' => $key],
                ['description' => $description, 'enabled' => false],
            );
        }
    }
}
