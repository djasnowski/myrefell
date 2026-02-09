<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('player_houses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_id')->constrained('users')->onDelete('cascade');
            $table->string('name')->default('My House');
            $table->string('tier')->default('cottage');
            $table->integer('condition')->default(100);
            $table->foreignId('kingdom_id')->constrained('kingdoms')->onDelete('cascade');
            $table->timestamps();

            $table->unique('player_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('player_houses');
    }
};
