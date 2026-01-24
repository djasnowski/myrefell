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
        Schema::create('employment_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('icon')->default('briefcase');
            $table->text('description');
            $table->string('category'); // service, labor, skilled
            $table->string('location_type'); // village, castle, town
            $table->unsignedInteger('energy_cost')->default(10);
            $table->unsignedInteger('base_wage')->default(50); // gold per work action
            $table->unsignedInteger('xp_reward')->default(10);
            $table->string('xp_skill')->nullable(); // skill that gains XP
            $table->string('required_skill')->nullable();
            $table->unsignedInteger('required_skill_level')->default(1);
            $table->unsignedInteger('required_level')->default(1); // combat level
            $table->unsignedInteger('cooldown_minutes')->default(30); // time between work actions
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('max_workers')->default(10); // max workers per location
            $table->timestamps();

            $table->index('location_type');
            $table->index('is_active');
        });

        Schema::create('player_employment', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('employment_job_id')->constrained('employment_jobs')->onDelete('cascade');
            $table->string('location_type'); // where they work
            $table->unsignedBigInteger('location_id');
            $table->enum('status', ['employed', 'on_break', 'quit'])->default('employed');
            $table->timestamp('hired_at');
            $table->timestamp('last_worked_at')->nullable();
            $table->unsignedInteger('times_worked')->default(0);
            $table->unsignedInteger('total_earnings')->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'employment_job_id', 'location_type', 'location_id'], 'unique_player_job_location');
            $table->index(['user_id', 'status']);
            $table->index(['location_type', 'location_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('player_employment');
        Schema::dropIfExists('employment_jobs');
    }
};
