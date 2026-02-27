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
        Schema::create('shops', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('npc_name');
            $table->text('npc_description')->nullable();
            $table->text('description')->nullable();
            $table->string('location_type');
            $table->unsignedBigInteger('location_id');
            $table->string('icon')->nullable();
            $table->integer('map_position_x')->default(50);
            $table->integer('map_position_y')->default(50);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['location_type', 'location_id']);
        });

        Schema::create('shop_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained('shops')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('items')->cascadeOnDelete();
            $table->integer('price');
            $table->integer('stock_quantity')->nullable();
            $table->integer('max_stock')->nullable();
            $table->integer('restock_hours')->nullable();
            $table->timestamp('last_restocked_at')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shop_items');
        Schema::dropIfExists('shops');
    }
};
