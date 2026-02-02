<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tavern_dice_games', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('location_type');
            $table->unsignedBigInteger('location_id');
            $table->string('game_type'); // 'high_roll', 'hazard', 'doubles'
            $table->integer('wager');
            $table->json('rolls'); // e.g., [{"player": [3,4], "house": [2,5]}]
            $table->boolean('won');
            $table->integer('payout'); // positive for win, negative for loss
            $table->integer('energy_awarded');
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['location_type', 'location_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tavern_dice_games');
    }
};
