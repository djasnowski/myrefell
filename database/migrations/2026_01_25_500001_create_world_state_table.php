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
        Schema::create('world_state', function (Blueprint $table) {
            $table->id();
            $table->integer('current_year')->default(1);
            $table->enum('current_season', ['spring', 'summer', 'autumn', 'winter'])->default('spring');
            $table->integer('current_week')->default(1); // 1-12 within season
            $table->timestamp('last_tick_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('world_state');
    }
};
