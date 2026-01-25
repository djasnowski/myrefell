<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Crime types reference table
        Schema::create('crime_types', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 50)->unique();
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->enum('severity', ['minor', 'moderate', 'major', 'capital']);
            $table->enum('court_level', ['village', 'barony', 'kingdom', 'church']);
            $table->integer('base_fine')->default(0);
            $table->integer('base_jail_days')->default(0);
            $table->boolean('can_be_outlawed')->default(false);
            $table->boolean('can_be_executed')->default(false);
            $table->boolean('is_religious')->default(false);
            $table->timestamps();
        });

        // Crimes committed (instances)
        Schema::create('crimes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('crime_type_id')->constrained('crime_types')->cascadeOnDelete();
            $table->foreignId('perpetrator_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('victim_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('location_type', 20); // village, barony, kingdom
            $table->unsignedBigInteger('location_id');
            $table->text('description')->nullable();
            $table->json('evidence')->nullable(); // stored evidence data
            $table->enum('status', ['undetected', 'reported', 'under_investigation', 'trial_pending', 'resolved']);
            $table->timestamp('committed_at');
            $table->timestamp('detected_at')->nullable();
            $table->timestamps();

            $table->index(['perpetrator_id', 'status']);
            $table->index(['location_type', 'location_id']);
        });

        // Witnesses to crimes
        Schema::create('crime_witnesses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('crime_id')->constrained('crimes')->cascadeOnDelete();
            $table->foreignId('witness_id')->constrained('users')->cascadeOnDelete();
            $table->boolean('is_npc')->default(false);
            $table->unsignedBigInteger('npc_id')->nullable();
            $table->text('testimony')->nullable();
            $table->boolean('has_testified')->default(false);
            $table->timestamps();

            $table->unique(['crime_id', 'witness_id']);
        });

        // Accusations filed
        Schema::create('accusations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('crime_id')->nullable()->constrained('crimes')->nullOnDelete();
            $table->foreignId('accuser_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('accused_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('crime_type_id')->constrained('crime_types')->cascadeOnDelete();
            $table->string('location_type', 20);
            $table->unsignedBigInteger('location_id');
            $table->text('accusation_text');
            $table->json('evidence_provided')->nullable();
            $table->enum('status', ['pending', 'accepted', 'rejected', 'false_accusation', 'withdrawn']);
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('review_notes')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['accused_id', 'status']);
            $table->index(['location_type', 'location_id']);
        });

        // Trials
        Schema::create('trials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('crime_id')->constrained('crimes')->cascadeOnDelete();
            $table->foreignId('accusation_id')->nullable()->constrained('accusations')->nullOnDelete();
            $table->foreignId('defendant_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('judge_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('court_level', ['village', 'barony', 'kingdom', 'church']);
            $table->string('location_type', 20);
            $table->unsignedBigInteger('location_id');
            $table->enum('status', ['scheduled', 'in_progress', 'awaiting_verdict', 'concluded', 'appealed', 'dismissed']);
            $table->text('prosecution_argument')->nullable();
            $table->text('defense_argument')->nullable();
            $table->enum('verdict', ['guilty', 'not_guilty', 'dismissed'])->nullable();
            $table->text('verdict_reasoning')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('concluded_at')->nullable();
            $table->timestamps();

            $table->index(['defendant_id', 'status']);
            $table->index(['judge_id', 'status']);
        });

        // Punishments
        Schema::create('punishments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trial_id')->nullable()->constrained('trials')->nullOnDelete();
            $table->foreignId('criminal_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('type', ['fine', 'jail', 'exile', 'outlawry', 'execution', 'excommunication', 'community_service']);
            $table->integer('fine_amount')->nullable();
            $table->integer('jail_days')->nullable();
            $table->string('exile_from_type', 20)->nullable(); // village, barony, kingdom
            $table->unsignedBigInteger('exile_from_id')->nullable();
            $table->integer('community_service_hours')->nullable();
            $table->enum('status', ['pending', 'active', 'completed', 'pardoned', 'escaped']);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['criminal_id', 'status']);
            $table->index(['type', 'status']);
        });

        // Bounties
        Schema::create('bounties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('target_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('crime_id')->nullable()->constrained('crimes')->nullOnDelete();
            $table->string('poster_type', 20)->default('player'); // player, village, barony, kingdom
            $table->unsignedBigInteger('poster_location_id')->nullable();
            $table->integer('reward_amount');
            $table->enum('capture_type', ['alive', 'dead_or_alive', 'dead']);
            $table->text('reason');
            $table->enum('status', ['active', 'claimed', 'expired', 'cancelled']);
            $table->foreignId('claimed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('claimed_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['target_id', 'status']);
            $table->index(['status', 'expires_at']);
        });

        // Jail cells (for tracking who is in jail where)
        Schema::create('jail_inmates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prisoner_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('punishment_id')->constrained('punishments')->cascadeOnDelete();
            $table->string('jail_location_type', 20); // village, barony, kingdom
            $table->unsignedBigInteger('jail_location_id');
            $table->timestamp('jailed_at');
            $table->timestamp('release_at');
            $table->timestamp('released_at')->nullable();
            $table->boolean('escaped')->default(false);
            $table->timestamps();

            $table->index(['prisoner_id']);
            $table->index(['jail_location_type', 'jail_location_id']);
        });

        // Outlaws registry
        Schema::create('outlaws', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('punishment_id')->constrained('punishments')->cascadeOnDelete();
            $table->string('declared_by_type', 20); // barony, kingdom
            $table->unsignedBigInteger('declared_by_id');
            $table->text('reason');
            $table->enum('status', ['active', 'captured', 'killed', 'pardoned', 'expired']);
            $table->timestamp('declared_at');
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });

        // Exiles registry
        Schema::create('exiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('punishment_id')->constrained('punishments')->cascadeOnDelete();
            $table->string('exiled_from_type', 20); // village, barony, kingdom
            $table->unsignedBigInteger('exiled_from_id');
            $table->text('reason');
            $table->enum('status', ['active', 'expired', 'pardoned']);
            $table->timestamp('exiled_at');
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['exiled_from_type', 'exiled_from_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exiles');
        Schema::dropIfExists('outlaws');
        Schema::dropIfExists('jail_inmates');
        Schema::dropIfExists('bounties');
        Schema::dropIfExists('punishments');
        Schema::dropIfExists('trials');
        Schema::dropIfExists('accusations');
        Schema::dropIfExists('crime_witnesses');
        Schema::dropIfExists('crimes');
        Schema::dropIfExists('crime_types');
    }
};
