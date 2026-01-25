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
        Schema::create('dungeons', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->text('description')->nullable();

            // Dungeon theme and difficulty
            $table->string('theme'); // goblin_fortress, undead_crypt, dragon_lair, etc.
            $table->enum('difficulty', ['easy', 'normal', 'hard', 'nightmare'])->default('normal');
            $table->string('biome')->nullable(); // which biome this dungeon is in

            // Level requirements
            $table->unsignedSmallInteger('min_combat_level')->default(1);
            $table->unsignedSmallInteger('recommended_level')->default(5);

            // Structure
            $table->unsignedSmallInteger('floor_count')->default(3);

            // Boss monster on final floor
            $table->foreignId('boss_monster_id')->nullable()->constrained('monsters')->nullOnDelete();

            // Rewards
            $table->unsignedInteger('xp_reward_base')->default(100);
            $table->unsignedInteger('gold_reward_min')->default(50);
            $table->unsignedInteger('gold_reward_max')->default(200);

            // Entry cost
            $table->unsignedSmallInteger('energy_cost')->default(10);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dungeons');
    }
};
