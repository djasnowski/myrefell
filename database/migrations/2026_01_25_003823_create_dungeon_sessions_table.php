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
        Schema::create('dungeon_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('dungeon_id')->constrained()->cascadeOnDelete();

            // Current progress
            $table->unsignedSmallInteger('current_floor')->default(1);
            $table->unsignedSmallInteger('monsters_defeated')->default(0);
            $table->unsignedSmallInteger('total_monsters_on_floor')->default(0);

            // Session state
            $table->enum('status', ['active', 'completed', 'failed', 'abandoned'])->default('active');

            // Accumulated rewards (given on completion)
            $table->unsignedInteger('xp_accumulated')->default(0);
            $table->unsignedInteger('gold_accumulated')->default(0);
            $table->json('loot_accumulated')->nullable(); // Items collected

            // Training style for XP distribution
            $table->enum('training_style', ['attack', 'strength', 'defense'])->default('attack');

            // Location where dungeon was entered from
            $table->string('entry_location_type');
            $table->unsignedBigInteger('entry_location_id');

            $table->timestamps();

            $table->index(['user_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dungeon_sessions');
    }
};
