<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('house_furniture', function (Blueprint $table) {
            $table->id();
            $table->foreignId('house_room_id')->constrained('house_rooms')->onDelete('cascade');
            $table->string('hotspot_slug');
            $table->string('furniture_key');
            $table->timestamps();

            $table->unique(['house_room_id', 'hotspot_slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('house_furniture');
    }
};
