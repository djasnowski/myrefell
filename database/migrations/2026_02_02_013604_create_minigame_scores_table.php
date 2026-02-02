<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('minigame_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('minigame'); // e.g., 'archery', 'jousting'
            $table->unsignedInteger('score');
            $table->string('location_type'); // village, town, barony, duchy, kingdom
            $table->unsignedBigInteger('location_id');
            $table->timestamp('played_at'); // For daily tracking
            $table->timestamps();

            // Indexes for leaderboard queries
            $table->index(['minigame', 'location_type', 'location_id', 'played_at']);
            $table->index(['user_id', 'minigame', 'played_at']);
            $table->index(['minigame', 'score']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('minigame_scores');
    }
};
