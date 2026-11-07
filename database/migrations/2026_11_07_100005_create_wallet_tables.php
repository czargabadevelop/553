<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->char('currency', 3)->default('PHP');

            // All amounts are integer minor units (centavos). Never floats.
            $table->bigInteger('available_minor')->default(0);
            $table->bigInteger('reserved_minor')->default(0);
            $table->bigInteger('lifetime_deposited_minor')->default(0);
            $table->bigInteger('lifetime_withdrawn_minor')->default(0);
            $table->bigInteger('lifetime_winnings_minor')->default(0);
            $table->bigInteger('lifetime_losses_minor')->default(0);
            $table->bigInteger('lifetime_fees_minor')->default(0);

            // Development credits are clearly labelled and never real money.
            $table->boolean('is_test_credit')->default(true);
            $table->timestamps();
        });

        Schema::create('ledger_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 64)->unique();
            $table->string('type', 32)->index();
            $table->string('status', 16)->default('posted');
            $table->string('idempotency_key', 96)->nullable()->unique();
            $table->char('currency', 3)->default('PHP');
            $table->string('description')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('posted_at')->nullable()->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('wallet_ledger_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ledger_transaction_id')->constrained()->restrictOnDelete();
            $table->foreignId('wallet_id')->nullable()->constrained()->nullOnDelete();
            $table->string('account', 32)->index();
            $table->unsignedBigInteger('debit_minor')->default(0);
            $table->unsignedBigInteger('credit_minor')->default(0);
            $table->char('currency', 3)->default('PHP');
            $table->timestamps();

            $table->index(['wallet_id', 'account'], 'wallet_ledger_entries_wallet_account_index');
        });

        Schema::create('wallet_reservations', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 64)->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('wallet_id')->constrained()->cascadeOnDelete();
            $table->string('type', 32)->index();
            $table->string('status', 32)->default('held')->index();
            $table->unsignedBigInteger('amount_minor');
            $table->string('reservable_type', 96)->nullable();
            $table->unsignedBigInteger('reservable_id')->nullable();
            $table->string('idempotency_key', 96)->nullable()->unique();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('released_at')->nullable();
            $table->timestamp('captured_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['reservable_type', 'reservable_id'], 'wallet_reservations_reservable_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallet_reservations');
        Schema::dropIfExists('wallet_ledger_entries');
        Schema::dropIfExists('ledger_transactions');
        Schema::dropIfExists('wallets');
    }
};
