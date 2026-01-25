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
        // Festival types (seasonal, religious, royal)
        Schema::create('festival_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description');
            $table->enum('category', ['seasonal', 'religious', 'royal', 'special']);
            $table->string('season')->nullable(); // spring, summer, autumn, winter
            $table->integer('duration_days')->default(3);
            $table->json('bonuses')->nullable(); // e.g., {"trade_bonus": 10, "happiness": 5}
            $table->json('activities')->nullable(); // available activities
            $table->boolean('is_recurring')->default(true);
            $table->timestamps();
        });

        // Active festivals at locations
        Schema::create('festivals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('festival_type_id')->constrained()->cascadeOnDelete();
            $table->string('location_type'); // village, town, barony, kingdom
            $table->unsignedBigInteger('location_id');
            $table->string('name'); // can be customized
            $table->enum('status', ['scheduled', 'active', 'completed', 'cancelled'])->default('scheduled');
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->integer('budget')->default(0); // gold spent on festival
            $table->foreignId('organized_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->integer('attendance_count')->default(0);
            $table->json('results')->nullable(); // tournament winners, etc.
            $table->timestamps();

            $table->index(['location_type', 'location_id']);
            $table->index('status');
            $table->index('starts_at');
        });

        // Festival participants/attendees
        Schema::create('festival_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('festival_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('role', ['attendee', 'performer', 'vendor', 'organizer', 'competitor']);
            $table->integer('gold_spent')->default(0);
            $table->integer('gold_earned')->default(0);
            $table->json('activities_completed')->nullable();
            $table->timestamps();

            $table->unique(['festival_id', 'user_id']);
        });

        // Tournament types
        Schema::create('tournament_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description');
            $table->enum('combat_type', ['melee', 'joust', 'archery', 'wrestling', 'mixed']);
            $table->string('primary_stat'); // attack, strength, defense, combat_level
            $table->string('secondary_stat')->nullable();
            $table->integer('entry_fee')->default(100);
            $table->integer('min_level')->default(1);
            $table->integer('max_participants')->default(16);
            $table->json('prize_distribution')->nullable(); // e.g., {"1st": 50, "2nd": 30, "3rd": 20}
            $table->boolean('is_lethal')->default(false);
            $table->timestamps();
        });

        // Tournaments (can be part of festival or standalone)
        Schema::create('tournaments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tournament_type_id')->constrained()->cascadeOnDelete();
            $table->foreignId('festival_id')->nullable()->constrained()->nullOnDelete();
            $table->string('location_type');
            $table->unsignedBigInteger('location_id');
            $table->string('name');
            $table->enum('status', ['registration', 'in_progress', 'completed', 'cancelled'])->default('registration');
            $table->timestamp('registration_ends_at');
            $table->timestamp('starts_at');
            $table->timestamp('completed_at')->nullable();
            $table->integer('prize_pool')->default(0);
            $table->integer('current_round')->default(0);
            $table->integer('total_rounds')->default(0);
            $table->foreignId('sponsored_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->integer('sponsor_contribution')->default(0);
            $table->timestamps();

            $table->index(['location_type', 'location_id']);
            $table->index('status');
        });

        // Tournament competitors
        Schema::create('tournament_competitors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tournament_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->integer('seed')->nullable(); // bracket position
            $table->enum('status', ['registered', 'active', 'eliminated', 'winner', 'withdrew'])->default('registered');
            $table->integer('wins')->default(0);
            $table->integer('losses')->default(0);
            $table->integer('final_placement')->nullable();
            $table->integer('prize_won')->default(0);
            $table->integer('fame_earned')->default(0);
            $table->timestamps();

            $table->unique(['tournament_id', 'user_id']);
        });

        // Tournament matches
        Schema::create('tournament_matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tournament_id')->constrained()->cascadeOnDelete();
            $table->integer('round_number');
            $table->integer('match_number');
            $table->foreignId('competitor1_id')->constrained('tournament_competitors')->cascadeOnDelete();
            $table->foreignId('competitor2_id')->nullable()->constrained('tournament_competitors')->nullOnDelete(); // null = bye
            $table->foreignId('winner_id')->nullable()->constrained('tournament_competitors')->nullOnDelete();
            $table->enum('status', ['pending', 'in_progress', 'completed', 'cancelled'])->default('pending');
            $table->integer('competitor1_score')->default(0);
            $table->integer('competitor2_score')->default(0);
            $table->json('combat_log')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['tournament_id', 'round_number']);
        });

        // Royal events (coronations, weddings, funerals)
        Schema::create('royal_events', function (Blueprint $table) {
            $table->id();
            $table->enum('event_type', ['coronation', 'royal_wedding', 'royal_funeral', 'declaration', 'treaty_signing']);
            $table->string('location_type');
            $table->unsignedBigInteger('location_id');
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('status', ['scheduled', 'active', 'completed', 'cancelled'])->default('scheduled');
            $table->timestamp('scheduled_at');
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('primary_participant_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('secondary_participant_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['location_type', 'location_id']);
            $table->index('event_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('royal_events');
        Schema::dropIfExists('tournament_matches');
        Schema::dropIfExists('tournament_competitors');
        Schema::dropIfExists('tournaments');
        Schema::dropIfExists('tournament_types');
        Schema::dropIfExists('festival_participants');
        Schema::dropIfExists('festivals');
        Schema::dropIfExists('festival_types');
    }
};
