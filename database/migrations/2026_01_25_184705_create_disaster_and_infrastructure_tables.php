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
        // Building types
        Schema::create('building_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description');
            $table->enum('category', ['housing', 'economic', 'military', 'religious', 'infrastructure']);
            $table->json('construction_requirements')->nullable(); // materials needed
            $table->integer('construction_days')->default(7);
            $table->integer('construction_labor')->default(10); // worker-days
            $table->integer('maintenance_cost')->default(10); // gold per week
            $table->integer('capacity')->default(0); // people or goods it can hold
            $table->json('bonuses')->nullable(); // e.g., {"defense": 10, "trade": 5}
            $table->boolean('is_fortification')->default(false);
            $table->timestamps();
        });

        // Buildings at locations
        Schema::create('buildings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('building_type_id')->constrained()->cascadeOnDelete();
            $table->string('location_type');
            $table->unsignedBigInteger('location_id');
            $table->string('name')->nullable(); // custom name
            $table->enum('status', ['planned', 'under_construction', 'operational', 'damaged', 'destroyed', 'abandoned'])->default('planned');
            $table->integer('condition')->default(100); // 0-100
            $table->integer('construction_progress')->default(0); // 0-100
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('built_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('construction_started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('last_maintenance_at')->nullable();
            $table->timestamps();

            $table->index(['location_type', 'location_id']);
            $table->index('status');
        });

        // Construction projects
        Schema::create('construction_projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('building_id')->constrained()->cascadeOnDelete();
            $table->enum('project_type', ['construction', 'repair', 'upgrade', 'demolition'])->default('construction');
            $table->enum('status', ['pending', 'in_progress', 'completed', 'cancelled'])->default('pending');
            $table->integer('progress')->default(0); // 0-100
            $table->integer('labor_invested')->default(0);
            $table->integer('labor_required');
            $table->json('materials_invested')->nullable();
            $table->json('materials_required')->nullable();
            $table->integer('gold_invested')->default(0);
            $table->integer('gold_required')->default(0);
            $table->foreignId('managed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index('status');
        });

        // Disaster types
        Schema::create('disaster_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description');
            $table->enum('category', ['weather', 'geological', 'fire', 'other']);
            $table->json('affected_seasons')->nullable(); // e.g., ["summer", "autumn"]
            $table->integer('base_chance')->default(1); // % per week
            $table->integer('duration_days')->default(1);
            $table->integer('building_damage')->default(20); // % damage to buildings
            $table->integer('crop_damage')->default(0); // % damage to crops
            $table->integer('casualty_rate')->default(0); // % chance of deaths
            $table->json('preventable_by')->nullable(); // buildings that reduce impact
            $table->timestamps();
        });

        // Active disasters
        Schema::create('disasters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('disaster_type_id')->constrained()->cascadeOnDelete();
            $table->string('location_type');
            $table->unsignedBigInteger('location_id');
            $table->enum('status', ['active', 'ended'])->default('active');
            $table->integer('severity')->default(50); // 0-100
            $table->integer('buildings_damaged')->default(0);
            $table->integer('buildings_destroyed')->default(0);
            $table->integer('casualties')->default(0);
            $table->integer('gold_damage')->default(0);
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->json('damage_report')->nullable();
            $table->timestamps();

            $table->index(['location_type', 'location_id']);
            $table->index('status');
        });

        // Building damage from disasters
        Schema::create('building_damages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('building_id')->constrained()->cascadeOnDelete();
            $table->foreignId('disaster_id')->nullable()->constrained()->nullOnDelete();
            $table->integer('damage_amount');
            $table->integer('condition_before');
            $table->integer('condition_after');
            $table->string('cause'); // disaster type or other cause
            $table->timestamp('occurred_at');
            $table->boolean('is_repaired')->default(false);
            $table->timestamps();

            $table->index('building_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('building_damages');
        Schema::dropIfExists('disasters');
        Schema::dropIfExists('disaster_types');
        Schema::dropIfExists('construction_projects');
        Schema::dropIfExists('buildings');
        Schema::dropIfExists('building_types');
    }
};
