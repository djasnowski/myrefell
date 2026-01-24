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
        // Available daily task templates
        Schema::create('daily_tasks', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description');
            $table->string('category'); // combat, gathering, crafting, service
            $table->string('task_type'); // kill, gather, craft, deliver, etc.

            // Requirements
            $table->string('target_type')->nullable(); // monster, item, location, etc.
            $table->string('target_identifier')->nullable(); // specific monster/item name
            $table->unsignedInteger('target_amount')->default(1);

            // Skill requirements
            $table->string('required_skill')->nullable();
            $table->unsignedInteger('required_skill_level')->default(1);

            // Location requirements
            $table->string('location_type')->nullable(); // village, castle, wilderness
            $table->boolean('home_village_only')->default(false);

            // Rewards
            $table->unsignedInteger('gold_reward')->default(0);
            $table->unsignedInteger('xp_reward')->default(0);
            $table->string('xp_skill')->nullable(); // which skill gets XP
            $table->unsignedInteger('energy_cost')->default(0);

            // Availability
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('weight')->default(100); // for random selection

            $table->timestamps();

            $table->index('category');
            $table->index('is_active');
        });

        // Player's assigned daily tasks
        Schema::create('player_daily_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('daily_task_id')->constrained()->cascadeOnDelete();

            // Progress tracking
            $table->unsignedInteger('current_progress')->default(0);
            $table->unsignedInteger('target_amount');
            $table->enum('status', ['active', 'completed', 'claimed', 'expired'])->default('active');

            // Timing
            $table->date('assigned_date');
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('claimed_at')->nullable();

            $table->timestamps();

            // One task per player per day per task type
            $table->unique(['user_id', 'daily_task_id', 'assigned_date']);
            $table->index(['user_id', 'assigned_date']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('player_daily_tasks');
        Schema::dropIfExists('daily_tasks');
    }
};
