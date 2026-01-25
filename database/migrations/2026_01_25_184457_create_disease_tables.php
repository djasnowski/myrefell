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
        // Disease types
        Schema::create('disease_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description');
            $table->enum('severity', ['minor', 'moderate', 'severe', 'plague']);
            $table->integer('base_spread_rate')->default(10); // % chance to spread per day
            $table->integer('mortality_rate')->default(5); // % chance of death per day if untreated
            $table->integer('base_duration_days')->default(7); // how long it lasts
            $table->integer('incubation_days')->default(1); // days before symptoms
            $table->json('symptoms')->nullable(); // affects on player
            $table->json('stat_penalties')->nullable(); // e.g., {"energy_regen": -50, "max_hp": -20}
            $table->boolean('is_contagious')->default(true);
            $table->boolean('grants_immunity')->default(true);
            $table->timestamps();
        });

        // Active outbreaks in locations
        Schema::create('disease_outbreaks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('disease_type_id')->constrained()->cascadeOnDelete();
            $table->string('location_type');
            $table->unsignedBigInteger('location_id');
            $table->enum('status', ['emerging', 'active', 'declining', 'contained', 'ended'])->default('emerging');
            $table->integer('infected_count')->default(0);
            $table->integer('recovered_count')->default(0);
            $table->integer('death_count')->default(0);
            $table->integer('peak_infected')->default(0);
            $table->timestamp('started_at');
            $table->timestamp('peaked_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->boolean('is_quarantined')->default(false);
            $table->timestamps();

            $table->index(['location_type', 'location_id']);
            $table->index('status');
        });

        // Individual infections (players and NPCs)
        Schema::create('disease_infections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('disease_outbreak_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('disease_type_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('location_npc_id')->nullable()->constrained()->cascadeOnDelete();
            $table->enum('status', ['incubating', 'symptomatic', 'recovering', 'recovered', 'deceased'])->default('incubating');
            $table->integer('severity_modifier')->default(0); // individual variation
            $table->integer('days_infected')->default(0);
            $table->integer('days_symptomatic')->default(0);
            $table->boolean('is_treated')->default(false);
            $table->timestamp('infected_at');
            $table->timestamp('symptoms_started_at')->nullable();
            $table->timestamp('recovered_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });

        // Disease immunity
        Schema::create('disease_immunities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('disease_type_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('location_npc_id')->nullable()->constrained()->cascadeOnDelete();
            $table->enum('immunity_type', ['recovered', 'vaccinated', 'natural']);
            $table->timestamp('acquired_at');
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->unique(['disease_type_id', 'user_id']);
        });

        // Quarantine orders
        Schema::create('quarantine_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('disease_outbreak_id')->constrained()->cascadeOnDelete();
            $table->string('location_type');
            $table->unsignedBigInteger('location_id');
            $table->foreignId('ordered_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('status', ['active', 'lifted'])->default('active');
            $table->timestamp('ordered_at');
            $table->timestamp('lifted_at')->nullable();
            $table->text('reason')->nullable();
            $table->timestamps();

            $table->index(['location_type', 'location_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quarantine_orders');
        Schema::dropIfExists('disease_immunities');
        Schema::dropIfExists('disease_infections');
        Schema::dropIfExists('disease_outbreaks');
        Schema::dropIfExists('disease_types');
    }
};
