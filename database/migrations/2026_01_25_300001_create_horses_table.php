<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('horses', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description');
            $table->decimal('speed_multiplier', 3, 1)->default(2.0);
            $table->unsignedInteger('base_price');
            $table->enum('min_location_type', ['village', 'town', 'castle', 'kingdom']);
            $table->unsignedInteger('base_stamina')->default(100); // Max stamina for this horse type
            $table->unsignedInteger('stamina_cost_per_travel')->default(10); // Stamina used per travel
            $table->unsignedTinyInteger('rarity')->default(50); // 1-100, lower = rarer
            $table->timestamps();
        });

        // Player's owned horse
        Schema::create('player_horses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('horse_id')->constrained()->onDelete('cascade');
            $table->string('custom_name')->nullable();
            $table->unsignedInteger('purchase_price');
            $table->unsignedInteger('stamina')->default(100); // Horse energy
            $table->unsignedInteger('max_stamina')->default(100);
            $table->boolean('is_stabled')->default(false); // Is horse at a stable?
            $table->string('stabled_location_type')->nullable();
            $table->unsignedBigInteger('stabled_location_id')->nullable();
            $table->timestamp('purchased_at');
            $table->timestamps();

            $table->unique('user_id'); // One horse per player
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('player_horses');
        Schema::dropIfExists('horses');
    }
};
