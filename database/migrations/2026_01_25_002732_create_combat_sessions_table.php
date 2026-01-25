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
        Schema::create('combat_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('monster_id')->constrained()->cascadeOnDelete();

            // Combat state
            $table->unsignedSmallInteger('player_hp');
            $table->unsignedSmallInteger('monster_hp');
            $table->unsignedSmallInteger('round')->default(1);

            // Training style for XP distribution
            $table->enum('training_style', ['attack', 'strength', 'defense'])->default('attack');

            // Session status
            $table->enum('status', ['active', 'victory', 'defeat', 'fled'])->default('active');

            // Location where combat was initiated
            $table->string('location_type');
            $table->unsignedBigInteger('location_id');

            $table->timestamps();

            $table->index(['user_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('combat_sessions');
    }
};
