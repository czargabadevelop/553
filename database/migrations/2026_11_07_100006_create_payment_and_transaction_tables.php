<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->string('code', 48)->unique();
            $table->string('name');
            $table->string('type', 32)->default('bank_transfer');
            $table->text('instructions')->nullable();
            $table->boolean('requires_proof')->default(true);
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedBigInteger('min_amount_minor')->nullable();
            $table->unsignedBigInteger('max_amount_minor')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('payout_methods', function (Blueprint $table) {
            $table->id();
            $table->string('code', 48)->unique();
            $table->string('name');
            $table->string('type', 32)->default('bank_transfer');
            $table->json('required_fields')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedBigInteger('min_amount_minor')->nullable();
            $table->unsignedBigInteger('max_amount_minor')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('deposits', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 48)->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('wallet_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payment_method_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('amount_minor');
            $table->char('currency', 3)->default('PHP');
            $table->string('status', 32)->default('pending')->index();
            $table->string('reference_number', 96)->nullable();
            $table->string('proof_path')->nullable();
            $table->json('instructions')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('rejection_reason')->nullable();
            $table->foreignId('ledger_transaction_id')->nullable()->constrained()->nullOnDelete();
            $table->string('idempotency_key', 96)->nullable()->unique();
            $table->timestamps();

            $table->index(['user_id', 'status'], 'deposits_user_status_index');
        });

        Schema::create('withdrawals', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 48)->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('wallet_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payout_method_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('amount_minor');
            $table->char('currency', 3)->default('PHP');
            $table->string('status', 32)->default('pending')->index();

            // Payout details are encrypted at rest and hidden from serialization.
            $table->text('payout_details_encrypted')->nullable();
            $table->timestamp('requested_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('processed_at')->nullable();
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->foreignId('ledger_transaction_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('reservation_id')->nullable()->constrained('wallet_reservations')->nullOnDelete();
            $table->string('idempotency_key', 96)->nullable()->unique();
            $table->timestamps();

            $table->index(['user_id', 'status'], 'withdrawals_user_status_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('withdrawals');
        Schema::dropIfExists('deposits');
        Schema::dropIfExists('payout_methods');
        Schema::dropIfExists('payment_methods');
    }
};
