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
        Schema::create('dungeon_floor_monsters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dungeon_floor_id')->constrained()->cascadeOnDelete();
            $table->foreignId('monster_id')->constrained()->cascadeOnDelete();

            // Spawn configuration
            $table->unsignedSmallInteger('spawn_weight')->default(100); // Higher = more likely
            $table->unsignedSmallInteger('min_count')->default(1);
            $table->unsignedSmallInteger('max_count')->default(1);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dungeon_floor_monsters');
    }
};
