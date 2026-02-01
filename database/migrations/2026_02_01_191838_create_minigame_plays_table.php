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
        Schema::create('minigame_plays', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Play tracking
            $table->date('played_at');
            $table->unsignedTinyInteger('streak_count')->default(1);

            // Reward info
            $table->string('reward_type'); // common, uncommon, rare, epic
            $table->unsignedInteger('reward_value')->default(0); // e.g., gold amount
            $table->foreignId('reward_item_id')->nullable()->constrained('items')->nullOnDelete();

            $table->timestamps();

            // One play per user per day
            $table->unique(['user_id', 'played_at']);
            $table->index('played_at');
            $table->index(['user_id', 'played_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('minigame_plays');
    }
};
