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
        Schema::create('dungeon_floors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dungeon_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('floor_number');

            // Floor configuration
            $table->string('name')->nullable(); // "Entrance Hall", "Deep Caverns", etc.
            $table->unsignedSmallInteger('monster_count')->default(3);
            $table->boolean('is_boss_floor')->default(false);

            // XP and loot multipliers for this floor
            $table->decimal('xp_multiplier', 3, 2)->default(1.00);
            $table->decimal('loot_multiplier', 3, 2)->default(1.00);

            $table->timestamps();

            $table->unique(['dungeon_id', 'floor_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dungeon_floors');
    }
};
