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
        Schema::create('potion_buffs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('buff_type'); // attack, strength, defense, accuracy, agility, combat, overload
            $table->integer('bonus_percent'); // e.g., 10 for +10%
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->index(['user_id', 'buff_type']);
            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('potion_buffs');
    }
};
