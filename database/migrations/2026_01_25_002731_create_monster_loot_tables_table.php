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
        Schema::create('monster_loot_tables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('monster_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->constrained()->cascadeOnDelete();
            $table->decimal('drop_chance', 5, 2); // 0.00 to 100.00 percent
            $table->unsignedSmallInteger('quantity_min')->default(1);
            $table->unsignedSmallInteger('quantity_max')->default(1);
            $table->timestamps();

            $table->unique(['monster_id', 'item_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('monster_loot_tables');
    }
};
