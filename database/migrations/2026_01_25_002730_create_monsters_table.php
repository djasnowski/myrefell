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
        Schema::create('monsters', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->string('type'); // humanoid, beast, undead, dragon, demon, etc.
            $table->string('biome')->nullable(); // plains, tundra, coastal, volcano, etc.

            // Combat stats
            $table->unsignedSmallInteger('hp');
            $table->unsignedSmallInteger('max_hp');
            $table->unsignedSmallInteger('attack_level')->default(1);
            $table->unsignedSmallInteger('strength_level')->default(1);
            $table->unsignedSmallInteger('defense_level')->default(1);
            $table->unsignedSmallInteger('combat_level')->default(1);

            // Attack style
            $table->string('attack_style')->default('melee'); // melee, ranged, magic

            // Rewards
            $table->unsignedInteger('xp_reward');
            $table->unsignedInteger('gold_drop_min')->default(0);
            $table->unsignedInteger('gold_drop_max')->default(0);

            // Spawn requirements
            $table->unsignedSmallInteger('min_player_combat_level')->default(1);
            $table->boolean('is_boss')->default(false);
            $table->boolean('is_aggressive')->default(false);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('monsters');
    }
};
