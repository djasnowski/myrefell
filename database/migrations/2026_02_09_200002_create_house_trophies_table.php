<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('house_trophies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_house_id')->constrained('player_houses')->cascadeOnDelete();
            $table->string('slot');
            $table->foreignId('item_id')->constrained('items')->cascadeOnDelete();
            $table->string('monster_name');
            $table->string('monster_type');
            $table->integer('monster_combat_level');
            $table->boolean('is_boss')->default(false);
            $table->timestamp('mounted_at');
            $table->timestamps();

            $table->unique(['player_house_id', 'slot']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('house_trophies');
    }
};
