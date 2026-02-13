<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('house_rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_house_id')->constrained('player_houses')->onDelete('cascade');
            $table->string('room_type');
            $table->tinyInteger('grid_x');
            $table->tinyInteger('grid_y');
            $table->timestamps();

            $table->unique(['player_house_id', 'grid_x', 'grid_y']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('house_rooms');
    }
};
