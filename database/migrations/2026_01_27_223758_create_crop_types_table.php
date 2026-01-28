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
        Schema::create('crop_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('icon')->default('wheat');
            $table->text('description')->nullable();

            // Growing parameters
            $table->unsignedInteger('grow_time_minutes')->default(60); // Time to grow
            $table->unsignedInteger('farming_level_required')->default(1);
            $table->unsignedInteger('farming_xp')->default(10); // XP per harvest

            // Yield
            $table->unsignedInteger('yield_min')->default(1);
            $table->unsignedInteger('yield_max')->default(3);

            // Related items
            $table->foreignId('seed_item_id')->nullable()->constrained('items')->nullOnDelete();
            $table->foreignId('harvest_item_id')->nullable()->constrained('items')->nullOnDelete();

            // Cost to plant (if no seed item)
            $table->unsignedInteger('plant_cost')->default(0);

            // Seasonal availability (null = all seasons)
            $table->json('seasons')->nullable(); // ['spring', 'summer', 'autumn']

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('crop_types');
    }
};
