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
        // Business types define the categories of businesses players can own
        Schema::create('business_types', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Smithy, Bakery, Tannery, Farm, Mine, etc.
            $table->string('icon')->default('store');
            $table->text('description');
            $table->string('category'); // production, service, extraction
            $table->string('location_type'); // village, town, barony
            $table->integer('purchase_cost'); // Gold to establish
            $table->integer('weekly_upkeep')->default(0); // Maintenance costs
            $table->integer('max_employees')->default(3);
            $table->string('primary_skill')->nullable(); // Skill used for production
            $table->integer('required_skill_level')->default(10); // Min skill to own
            $table->json('produces')->nullable(); // Items this business can produce
            $table->json('requires')->nullable(); // Resource requirements for production
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('location_type');
            $table->index('category');
        });

        // Player-owned businesses
        Schema::create('player_businesses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('business_type_id')->constrained()->cascadeOnDelete();
            $table->string('name'); // Custom name for the business
            $table->string('location_type'); // village, town, barony
            $table->unsignedBigInteger('location_id');
            $table->enum('status', ['active', 'closed', 'suspended'])->default('active');
            $table->integer('treasury')->default(0); // Business funds
            $table->integer('total_revenue')->default(0); // Lifetime revenue
            $table->integer('total_expenses')->default(0); // Lifetime expenses
            $table->integer('reputation')->default(50); // 0-100, affects prices/customers
            $table->timestamp('last_production_at')->nullable();
            $table->timestamp('last_upkeep_at')->nullable();
            $table->timestamp('established_at');
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['location_type', 'location_id']);
        });

        // Business employees (NPCs or players working at a business)
        Schema::create('business_employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); // Player employee
            $table->foreignId('location_npc_id')->nullable()->constrained()->nullOnDelete(); // NPC employee
            $table->string('role')->default('worker'); // worker, manager
            $table->integer('daily_wage');
            $table->integer('skill_level')->default(1); // Affects production efficiency
            $table->enum('status', ['employed', 'quit', 'fired'])->default('employed');
            $table->timestamp('hired_at');
            $table->timestamp('last_paid_at')->nullable();
            $table->timestamps();

            $table->index(['player_business_id', 'status']);
        });

        // Business inventory (stock held by the business)
        Schema::create('business_inventory', function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->constrained()->cascadeOnDelete();
            $table->integer('quantity')->default(0);
            $table->timestamps();

            $table->unique(['player_business_id', 'item_id']);
        });

        // Business transactions (financial history)
        Schema::create('business_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_business_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['sale', 'purchase', 'wage', 'upkeep', 'deposit', 'withdrawal', 'production']);
            $table->integer('amount'); // Positive for income, negative for expense
            $table->integer('balance_after');
            $table->string('description');
            $table->foreignId('related_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('related_item_id')->nullable()->constrained('items')->nullOnDelete();
            $table->timestamps();

            $table->index('player_business_id');
            $table->index('type');
        });

        // Business production orders (what the business is currently making)
        Schema::create('business_production_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->constrained()->cascadeOnDelete();
            $table->integer('quantity');
            $table->integer('quantity_completed')->default(0);
            $table->enum('status', ['pending', 'in_progress', 'completed', 'cancelled'])->default('pending');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['player_business_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('business_production_orders');
        Schema::dropIfExists('business_transactions');
        Schema::dropIfExists('business_inventory');
        Schema::dropIfExists('business_employees');
        Schema::dropIfExists('player_businesses');
        Schema::dropIfExists('business_types');
    }
};
