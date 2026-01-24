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
        Schema::create('towns', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->foreignId('kingdom_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_capital')->default(false);
            $table->string('biome')->default('plains');
            $table->decimal('tax_rate', 5, 2)->default(10.00);
            $table->unsignedInteger('population')->default(0);
            $table->unsignedInteger('wealth')->default(0);
            $table->foreignId('mayor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->integer('coordinates_x')->default(0);
            $table->integer('coordinates_y')->default(0);
            $table->timestamps();

            $table->index('kingdom_id');
            $table->index('is_capital');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('towns');
    }
};
