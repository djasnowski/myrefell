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
        Schema::create('villages', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->foreignId('castle_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('is_town')->default(false); // Can be upgraded to town
            $table->unsignedInteger('population')->default(0);
            $table->unsignedBigInteger('wealth')->default(0);
            $table->string('biome');
            $table->integer('coordinates_x')->default(0);
            $table->integer('coordinates_y')->default(0);
            $table->timestamps();
        });

        // Add foreign key for home_village_id in users
        Schema::table('users', function (Blueprint $table) {
            $table->foreign('home_village_id')
                ->references('id')
                ->on('villages')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['home_village_id']);
        });
        Schema::dropIfExists('villages');
    }
};
