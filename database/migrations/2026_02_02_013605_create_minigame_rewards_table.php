<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('minigame_rewards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('minigame'); // e.g., 'archery', 'jousting'
            $table->string('reward_type'); // 'daily', 'weekly', 'monthly'
            $table->unsignedInteger('rank'); // 1, 2, 3, etc.
            $table->string('location_type'); // village, town, barony, duchy, kingdom
            $table->unsignedBigInteger('location_id'); // Where they need to collect
            $table->unsignedInteger('gold_amount');
            $table->foreignId('item_id')->nullable()->constrained()->nullOnDelete();
            $table->string('item_rarity')->nullable(); // 'legendary', 'epic', 'rare'
            $table->date('period_start');
            $table->date('period_end');
            $table->timestamp('collected_at')->nullable(); // null = uncollected
            $table->timestamps();

            // Indexes for querying uncollected rewards
            $table->index(['user_id', 'collected_at']);
            $table->index(['minigame', 'reward_type', 'period_start']);
            $table->index(['location_type', 'location_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('minigame_rewards');
    }
};
