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
        Schema::create('kingdoms', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->string('biome'); // forest, plains, mountains, swamps, desert, tundra, coastal, volcano
            $table->unsignedBigInteger('capital_castle_id')->nullable(); // Set after castles exist
            $table->decimal('tax_rate', 5, 2)->default(10.00); // Percentage
            $table->integer('coordinates_x')->default(0);
            $table->integer('coordinates_y')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kingdoms');
    }
};
