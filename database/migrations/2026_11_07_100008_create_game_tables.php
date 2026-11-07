<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('games', function (Blueprint $table) {
            $table->id();
            $table->string('code', 32)->unique();
            $table->foreignId('room_id')->constrained()->restrictOnDelete();
            $table->string('status', 32)->default('waiting')->index();
            $table->unsignedTinyInteger('player_count')->default(0);

            // Audit fingerprint of the shuffled deck; never the seed itself.
            $table->string('deck_fingerprint', 64)->nullable();
            $table->json('settings_snapshot')->nullable();
            $table->timestamp('arrangement_deadline_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable()->index();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancel_reason')->nullable();
            $table->timestamp('settled_at')->nullable();
            $table->timestamps();
        });

        Schema::create('game_players', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('seat_number');
            $table->string('status', 32)->default('waiting')->index();
            $table->smallInteger('game_points')->nullable();
            $table->boolean('is_fouled')->default(false);
            $table->string('foul_reason')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('reservation_id')->nullable()->constrained('wallet_reservations')->nullOnDelete();
            $table->timestamps();

            $table->unique(['game_id', 'seat_number'], 'game_players_seat_unique');
            $table->unique(['game_id', 'user_id'], 'game_players_user_unique');
        });

        Schema::create('game_cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->constrained()->cascadeOnDelete();
            $table->foreignId('game_player_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('card_rank');
            $table->char('card_suit', 1);
            $table->string('position', 16)->nullable()->index();
            $table->unsignedTinyInteger('deal_order')->default(0);
            $table->timestamp('revealed_at')->nullable();
            $table->timestamps();

            $table->unique(['game_id', 'card_rank', 'card_suit'], 'game_cards_unique_card');
            $table->index(['game_player_id', 'position'], 'game_cards_player_position_index');
        });

        Schema::create('game_hands', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_player_id')->constrained()->cascadeOnDelete();
            $table->string('position', 16);
            $table->string('category', 32);
            $table->string('strength_key', 96);
            $table->unsignedTinyInteger('card_count')->default(0);
            $table->timestamps();

            $table->unique(['game_player_id', 'position'], 'game_hands_player_position_unique');
        });

        Schema::create('game_hand_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_player_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('game_id')->constrained()->cascadeOnDelete();
            $table->json('hands');
            $table->boolean('is_valid')->default(true);
            $table->string('foul_reason')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();
        });

        Schema::create('game_hand_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->constrained()->cascadeOnDelete();
            $table->string('position', 16);
            $table->foreignId('player_one_id')->constrained('game_players')->cascadeOnDelete();
            $table->foreignId('player_two_id')->constrained('game_players')->cascadeOnDelete();
            $table->foreignId('winner_player_id')->nullable()->constrained('game_players')->nullOnDelete();
            $table->smallInteger('result');
            $table->timestamps();

            $table->unique(
                ['game_id', 'position', 'player_one_id', 'player_two_id'],
                'game_hand_results_pair_unique'
            );
        });

        Schema::create('game_player_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->constrained()->cascadeOnDelete();
            $table->foreignId('game_player_id')->unique()->constrained()->cascadeOnDelete();
            $table->smallInteger('game_points');
            $table->bigInteger('php_equivalent_minor');
            $table->unsignedBigInteger('fee_minor')->default(0);
            $table->bigInteger('net_minor');
            $table->boolean('is_fouled')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_player_results');
        Schema::dropIfExists('game_hand_results');
        Schema::dropIfExists('game_hand_submissions');
        Schema::dropIfExists('game_hands');
        Schema::dropIfExists('game_cards');
        Schema::dropIfExists('game_players');
        Schema::dropIfExists('games');
    }
};
