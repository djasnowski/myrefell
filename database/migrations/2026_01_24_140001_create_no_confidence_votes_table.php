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
        Schema::create('no_confidence_votes', function (Blueprint $table) {
            $table->id();

            // The player/role being challenged
            $table->foreignId('target_player_id')->constrained('users')->cascadeOnDelete();
            $table->string('target_role'); // The role being challenged (elder, blacksmith, mayor, king, etc.)

            // Domain where this vote is taking place
            $table->string('domain_type'); // village, town, kingdom
            $table->unsignedBigInteger('domain_id');

            // Who initiated the vote
            $table->foreignId('initiated_by_user_id')->constrained('users')->cascadeOnDelete();

            // Vote status
            $table->enum('status', ['pending', 'open', 'closed', 'passed', 'failed'])
                ->default('pending');

            // Timing (48-hour voting period)
            $table->timestamp('voting_starts_at')->nullable();
            $table->timestamp('voting_ends_at')->nullable();
            $table->timestamp('finalized_at')->nullable();

            // Vote tallies
            $table->unsignedInteger('votes_for')->default(0);
            $table->unsignedInteger('votes_against')->default(0);
            $table->unsignedInteger('votes_cast')->default(0);

            // Quorum tracking (majority of eligible voters needed)
            $table->unsignedInteger('quorum_required')->default(0);
            $table->boolean('quorum_met')->default(false);

            // Reason for the vote
            $table->text('reason')->nullable();

            // Outcome notes
            $table->text('notes')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['domain_type', 'domain_id']);
            $table->index('status');
            $table->index('voting_ends_at');
            $table->index('target_player_id');
        });

        // Individual votes on no confidence motions
        Schema::create('no_confidence_ballots', function (Blueprint $table) {
            $table->id();

            $table->foreignId('no_confidence_vote_id')->constrained()->cascadeOnDelete();
            $table->foreignId('voter_user_id')->constrained('users')->cascadeOnDelete();

            // true = vote FOR removal, false = vote AGAINST removal
            $table->boolean('vote_for_removal');

            $table->timestamp('voted_at');

            $table->timestamps();

            // Each user can only vote once per no confidence vote
            $table->unique(['no_confidence_vote_id', 'voter_user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('no_confidence_ballots');
        Schema::dropIfExists('no_confidence_votes');
    }
};
