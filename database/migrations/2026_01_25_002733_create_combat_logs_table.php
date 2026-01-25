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
        Schema::create('combat_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('combat_session_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('round');

            // Action details
            $table->enum('actor', ['player', 'monster']);
            $table->enum('action', ['attack', 'eat', 'flee']);
            $table->boolean('hit')->default(false);
            $table->unsignedSmallInteger('damage')->default(0);

            // State after action
            $table->unsignedSmallInteger('player_hp_after');
            $table->unsignedSmallInteger('monster_hp_after');

            // For eating food
            $table->foreignId('item_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedSmallInteger('hp_restored')->default(0);

            $table->timestamps();

            $table->index(['combat_session_id', 'round']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('combat_logs');
    }
};
