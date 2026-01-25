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
        Schema::create('market_prices', function (Blueprint $table) {
            $table->id();
            $table->string('location_type'); // village, town, barony
            $table->unsignedBigInteger('location_id');
            $table->foreignId('item_id')->constrained()->cascadeOnDelete();
            $table->integer('base_price'); // From item's base_value
            $table->integer('current_price'); // Dynamic price after modifiers
            $table->integer('supply_quantity')->default(0); // Current supply at location
            $table->integer('demand_level')->default(50); // 0-100, 50 is neutral
            $table->decimal('seasonal_modifier', 4, 2)->default(1.00); // Seasonal price effect
            $table->decimal('supply_modifier', 4, 2)->default(1.00); // Supply/demand effect
            $table->timestamp('last_updated_at')->useCurrent();
            $table->timestamps();

            // Unique constraint: one price per item per location
            $table->unique(['location_type', 'location_id', 'item_id'], 'market_prices_location_item_unique');

            // Index for efficient queries
            $table->index(['location_type', 'location_id']);
        });

        Schema::create('market_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('location_type');
            $table->unsignedBigInteger('location_id');
            $table->foreignId('item_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['buy', 'sell']);
            $table->integer('quantity');
            $table->integer('price_per_unit'); // Price at time of transaction
            $table->integer('total_gold');
            $table->timestamps();

            $table->index(['location_type', 'location_id']);
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('market_transactions');
        Schema::dropIfExists('market_prices');
    }
};
