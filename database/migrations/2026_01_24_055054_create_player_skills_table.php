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
        Schema::create('player_skills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_id')->constrained('users')->cascadeOnDelete();
            $table->enum('skill_name', [
                'attack',
                'strength',
                'defense',
                'hitpoints',
                'range',
                'farming',
                'mining',
                'fishing',
                'woodcutting',
                'cooking',
                'smithing',
                'crafting',
            ]);
            $table->unsignedTinyInteger('level')->default(1); // 1-99
            $table->unsignedBigInteger('xp')->default(0);
            $table->timestamps();

            // Each player can only have one entry per skill
            $table->unique(['player_id', 'skill_name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('player_skills');
    }
};
