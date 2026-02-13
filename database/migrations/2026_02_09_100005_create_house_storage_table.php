<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('house_storage', function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_house_id')->constrained('player_houses')->onDelete('cascade');
            $table->foreignId('item_id')->constrained('items')->onDelete('cascade');
            $table->integer('quantity')->default(0);
            $table->timestamps();

            $table->unique(['player_house_id', 'item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('house_storage');
    }
};
