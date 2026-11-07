<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('player_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('display_name', 64)->nullable();
            $table->date('date_of_birth')->nullable();
            $table->char('country_code', 2)->nullable()->index();
            $table->string('phone', 32)->nullable();

            // Responsible gaming settings (agents.md §14).
            $table->string('timezone', 64)->default('Asia/Manila');
            $table->string('avatar_path')->nullable();
            $table->timestamp('self_excluded_at')->nullable();
            $table->bigInteger('deposit_limit_minor')->nullable();
            $table->unsignedSmallInteger('session_limit_minutes')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();
        });

        Schema::create('account_verifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('status', 32)->default('pending')->index();
            $table->string('document_type', 32)->nullable();
            $table->json('document_paths')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status'], 'account_verifications_user_status_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_verifications');
        Schema::dropIfExists('player_profiles');
    }
};
