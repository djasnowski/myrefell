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
        // Crafting orders - player requests for items to be crafted
        Schema::create('crafting_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('crafter_id')->nullable()->constrained('users')->nullOnDelete();

            // Recipe info
            $table->string('recipe_id');
            $table->unsignedInteger('quantity')->default(1);

            // Location where order was placed
            $table->string('location_type'); // village, castle, town
            $table->unsignedBigInteger('location_id');

            // Pricing
            $table->unsignedInteger('gold_cost'); // cost paid by customer
            $table->unsignedInteger('crafter_payment')->default(0); // payment to player crafter

            // Status tracking
            $table->enum('status', ['pending', 'accepted', 'completed', 'cancelled', 'expired'])->default('pending');
            $table->enum('fulfillment_type', ['npc', 'player'])->default('npc');

            // Timing
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('due_at')->nullable(); // 10 min after acceptance for player orders
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('expires_at')->nullable(); // when order becomes stale

            $table->timestamps();

            // Indexes
            $table->index(['location_type', 'location_id']);
            $table->index(['customer_id', 'status']);
            $table->index(['crafter_id', 'status']);
            $table->index('status');
            $table->index('expires_at');
        });

        // Location stockpiles - materials available at each location for NPC crafting
        Schema::create('location_stockpiles', function (Blueprint $table) {
            $table->id();
            $table->string('location_type'); // village, castle, town
            $table->unsignedBigInteger('location_id');
            $table->foreignId('item_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('quantity')->default(0);
            $table->timestamps();

            // Each location can only have one stockpile entry per item
            $table->unique(['location_type', 'location_id', 'item_id']);
            $table->index(['location_type', 'location_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('location_stockpiles');
        Schema::dropIfExists('crafting_orders');
    }
};
