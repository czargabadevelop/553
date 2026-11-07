<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug', 64)->unique();
            $table->text('description')->nullable();
            $table->unsignedBigInteger('entry_fee_minor')->default(0);
            $table->unsignedBigInteger('platform_fee_minor')->default(0);
            $table->unsignedTinyInteger('min_players')->default(4);
            $table->unsignedTinyInteger('max_players')->default(4);
            $table->string('status', 32)->default('waiting')->index();
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('room_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('arrangement_timeout_seconds')->default(120);
            $table->unsignedSmallInteger('turn_timeout_seconds')->default(30);
            $table->boolean('auto_start')->default(true);
            $table->json('restrictions')->nullable();
            $table->timestamps();
        });

        Schema::create('room_seats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('seat_number');
            $table->string('status', 32)->default('seated')->index();
            $table->timestamp('ready_at')->nullable();
            $table->timestamp('joined_at')->nullable();
            $table->timestamp('left_at')->nullable();
            $table->foreignId('reservation_id')->nullable()->constrained('wallet_reservations')->nullOnDelete();
            $table->timestamps();

            $table->unique(['room_id', 'seat_number'], 'room_seats_number_unique');
            $table->index(['room_id', 'status'], 'room_seats_room_status_index');
        });

        // Platform fee configuration per room or globally (room_id null).
        Schema::create('platform_fee_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('amount_minor')->default(0);
            $table->char('currency', 3)->default('PHP');
            $table->boolean('is_enabled')->default(false);
            $table->timestamp('effective_from')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->index(['room_id', 'is_enabled'], 'platform_fee_settings_room_enabled_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_fee_settings');
        Schema::dropIfExists('room_seats');
        Schema::dropIfExists('room_settings');
        Schema::dropIfExists('rooms');
    }
};
