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
        Schema::create('blessing_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('icon')->default('sparkles');
            $table->text('description');
            $table->string('category'); // combat, skill, general
            $table->json('effects'); // {attack_bonus: 10, defense_bonus: 5, etc}
            $table->integer('duration_minutes')->default(60);
            $table->integer('cooldown_minutes')->default(0); // cooldown before same blessing can be given again
            $table->integer('prayer_level_required')->default(1); // priest's prayer level needed
            $table->integer('gold_cost')->default(0); // donation required
            $table->integer('energy_cost')->default(5); // energy cost for priest
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('blessing_types');
    }
};
