<?php

namespace Database\Seeders;

use App\Models\PaymentMethod;
use App\Models\PayoutMethod;
use Illuminate\Database\Seeder;

class PaymentMethodSeeder extends Seeder
{
    public function run(): void
    {
        PaymentMethod::query()->updateOrCreate(
            ['code' => 'manual-bank'],
            [
                'name' => 'Bank transfer (manual review)',
                'type' => 'bank_transfer',
                'instructions' => 'Transfer to the platform account and upload proof of payment. Deposits are credited only after manual verification.',
                'requires_proof' => true,
                'is_active' => true,
                'min_amount_minor' => 10_000,
                'max_amount_minor' => 5_000_000,
                'sort_order' => 1,
            ],
        );

        PaymentMethod::query()->updateOrCreate(
            ['code' => 'manual-ewallet'],
            [
                'name' => 'E-wallet (manual review)',
                'type' => 'e_wallet',
                'instructions' => 'Send to the platform e-wallet and upload the transaction receipt.',
                'requires_proof' => true,
                'is_active' => true,
                'min_amount_minor' => 10_000,
                'max_amount_minor' => 2_000_000,
                'sort_order' => 2,
            ],
        );

        PayoutMethod::query()->updateOrCreate(
            ['code' => 'bank-transfer'],
            [
                'name' => 'Bank transfer',
                'type' => 'bank_transfer',
                'required_fields' => ['bank_name', 'account_name', 'account_number'],
                'is_active' => true,
                'min_amount_minor' => 10_000,
                'max_amount_minor' => 2_000_000,
                'sort_order' => 1,
            ],
        );

        PayoutMethod::query()->updateOrCreate(
            ['code' => 'gcash'],
            [
                'name' => 'GCash',
                'type' => 'e_wallet',
                'required_fields' => ['mobile_number', 'account_name'],
                'is_active' => true,
                'min_amount_minor' => 10_000,
                'max_amount_minor' => 1_000_000,
                'sort_order' => 2,
            ],
        );
    }
}
