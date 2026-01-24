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
        Schema::create('castles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->foreignId('kingdom_id')->nullable()->constrained()->nullOnDelete();
            $table->string('biome');
            $table->decimal('tax_rate', 5, 2)->default(10.00);
            $table->integer('coordinates_x')->default(0);
            $table->integer('coordinates_y')->default(0);
            $table->timestamps();
        });

        // Add foreign key for capital_castle_id in kingdoms
        Schema::table('kingdoms', function (Blueprint $table) {
            $table->foreign('capital_castle_id')
                ->references('id')
                ->on('castles')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kingdoms', function (Blueprint $table) {
            $table->dropForeign(['capital_castle_id']);
        });
        Schema::dropIfExists('castles');
    }
};
