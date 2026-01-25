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
        // Guild benefits: preset bonuses that guilds provide to members
        Schema::create('guild_benefits', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->text('description');
            $table->string('icon')->default('award');

            // Which skill this benefit applies to (null = general)
            $table->string('skill_name')->nullable();

            // Bonuses as JSON
            // e.g., {"xp_bonus": 10, "speed_bonus": 5, "quality_bonus": 10}
            $table->json('effects')->nullable();

            // Minimum guild level to have this benefit
            $table->unsignedInteger('required_guild_level')->default(1);

            $table->timestamps();
        });

        // Guilds
        Schema::create('guilds', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->string('icon')->default('users');
            $table->string('color')->default('#f59e0b'); // amber

            // Primary skill this guild focuses on
            $table->string('primary_skill');

            // Location (town or barony where guild is registered)
            $table->string('location_type'); // town, barony
            $table->unsignedBigInteger('location_id');

            // Founder (player who created it)
            $table->foreignId('founder_id')->nullable()->constrained('users')->nullOnDelete();

            // Current guildmaster (elected by masters)
            $table->foreignId('guildmaster_id')->nullable()->constrained('users')->nullOnDelete();

            // Guild treasury
            $table->unsignedBigInteger('treasury')->default(0);

            // Guild level (increases with contributions)
            $table->unsignedInteger('level')->default(1);
            $table->unsignedBigInteger('total_contribution')->default(0);

            // Registration fee paid to found
            $table->unsignedInteger('founding_cost')->default(50000);

            // Membership fee for new members
            $table->unsignedInteger('membership_fee')->default(1000);

            // Weekly dues collected from members
            $table->unsignedInteger('weekly_dues')->default(100);

            // Whether the guild is public/accepting members
            $table->boolean('is_public')->default(true);

            // Whether the guild has monopoly rights in the location
            $table->boolean('has_monopoly')->default(false);
            $table->timestamp('monopoly_granted_at')->nullable();

            // Status
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index(['location_type', 'location_id']);
            $table->index(['primary_skill', 'is_active']);
        });

        // Guild benefits assignment (which benefits a guild has unlocked)
        Schema::create('guild_benefit_guild', function (Blueprint $table) {
            $table->id();
            $table->foreignId('guild_id')->constrained()->onDelete('cascade');
            $table->foreignId('guild_benefit_id')->constrained()->onDelete('cascade');
            $table->timestamps();

            $table->unique(['guild_id', 'guild_benefit_id']);
        });

        // Guild members
        Schema::create('guild_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('guild_id')->constrained()->onDelete('cascade');

            // Rank: guildmaster, master (5+ years, voting rights), journeyman (2-5 years), apprentice (0-2 years)
            $table->enum('rank', ['guildmaster', 'master', 'journeyman', 'apprentice'])->default('apprentice');

            // Contribution points (from crafting, donations, activities)
            $table->unsignedBigInteger('contribution')->default(0);

            // Years of membership (increases over time)
            $table->unsignedInteger('years_membership')->default(0);

            // Whether dues are paid up
            $table->boolean('dues_paid')->default(true);
            $table->timestamp('dues_paid_until')->nullable();

            $table->timestamp('joined_at');
            $table->timestamp('promoted_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'guild_id']);
            $table->index(['guild_id', 'rank']);
        });

        // Guild activities log (crafting, donations, meetings)
        Schema::create('guild_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('guild_id')->constrained()->onDelete('cascade');

            // Activity type
            $table->enum('activity_type', ['craft', 'donation', 'meeting', 'training', 'promotion', 'election', 'dues']);

            // Contribution gained from this activity
            $table->unsignedInteger('contribution_gained')->default(0);

            // Gold involved (for donations, dues)
            $table->unsignedInteger('gold_amount')->default(0);

            // Additional data (item crafted, etc.)
            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['guild_id', 'activity_type']);
        });

        // Guild elections (for guildmaster position)
        Schema::create('guild_elections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('guild_id')->constrained()->onDelete('cascade');

            // Election status
            $table->enum('status', ['nomination', 'voting', 'completed', 'cancelled'])->default('nomination');

            // Winner
            $table->foreignId('winner_id')->nullable()->constrained('users')->nullOnDelete();

            // Timing
            $table->timestamp('nomination_ends_at');
            $table->timestamp('voting_ends_at');
            $table->timestamp('completed_at')->nullable();

            $table->timestamps();

            $table->index(['guild_id', 'status']);
        });

        // Guild election candidates
        Schema::create('guild_election_candidates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('guild_election_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            $table->text('platform')->nullable();
            $table->unsignedInteger('votes')->default(0);

            $table->timestamps();

            $table->unique(['guild_election_id', 'user_id']);
        });

        // Guild election votes
        Schema::create('guild_election_votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('guild_election_id')->constrained()->onDelete('cascade');
            $table->foreignId('voter_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('candidate_id')->constrained('guild_election_candidates')->onDelete('cascade');

            $table->timestamps();

            $table->unique(['guild_election_id', 'voter_id']);
        });

        // Guild price controls (for monopolies)
        Schema::create('guild_price_controls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('guild_id')->constrained()->onDelete('cascade');

            // Item name this price control applies to
            $table->string('item_name');

            // Minimum price members must charge
            $table->unsignedInteger('min_price')->default(0);

            // Maximum price members can charge
            $table->unsignedInteger('max_price')->nullable();

            // Quality requirement
            $table->unsignedInteger('min_quality')->default(0);

            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['guild_id', 'item_name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('guild_price_controls');
        Schema::dropIfExists('guild_election_votes');
        Schema::dropIfExists('guild_election_candidates');
        Schema::dropIfExists('guild_elections');
        Schema::dropIfExists('guild_activities');
        Schema::dropIfExists('guild_members');
        Schema::dropIfExists('guild_benefit_guild');
        Schema::dropIfExists('guilds');
        Schema::dropIfExists('guild_benefits');
    }
};
