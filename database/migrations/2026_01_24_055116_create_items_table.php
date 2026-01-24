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
        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->enum('type', ['weapon', 'armor', 'resource', 'consumable', 'tool', 'misc']);
            $table->string('subtype')->nullable(); // sword, axe, helmet, ore, fish, etc.
            $table->enum('rarity', ['common', 'uncommon', 'rare', 'epic', 'legendary'])->default('common');
            $table->boolean('stackable')->default(true);
            $table->unsignedSmallInteger('max_stack')->default(1000);

            // Combat stats
            $table->smallInteger('atk_bonus')->default(0);
            $table->smallInteger('str_bonus')->default(0);
            $table->smallInteger('def_bonus')->default(0);
            $table->smallInteger('hp_bonus')->default(0);

            // Weapon effectiveness system
            $table->string('effectiveness_type')->nullable(); // slashing, piercing, blunt, etc.
            $table->json('effective_against')->nullable(); // Array of monster types
            $table->json('weak_against')->nullable(); // Array of monster types

            // Equipment slot (for equippable items)
            $table->string('equipment_slot')->nullable(); // head, chest, legs, feet, hands, weapon, shield, ring, amulet

            // Requirements
            $table->unsignedTinyInteger('required_level')->default(1);
            $table->string('required_skill')->nullable(); // attack, defense, mining, etc.
            $table->unsignedTinyInteger('required_skill_level')->default(1);

            // Economy
            $table->unsignedBigInteger('base_value')->default(0); // Gold value

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};
