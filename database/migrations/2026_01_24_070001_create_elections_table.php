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
        Schema::create('elections', function (Blueprint $table) {
            $table->id();

            // Election type and role
            $table->enum('election_type', ['village_role', 'mayor', 'king']);
            $table->string('role')->nullable(); // For village roles: elder, blacksmith, etc.

            // Polymorphic relationship to village/town/kingdom
            $table->string('domain_type'); // village, town, kingdom
            $table->unsignedBigInteger('domain_id');

            // Election status
            $table->enum('status', ['pending', 'open', 'closed', 'completed', 'failed'])
                ->default('pending');

            // Timing
            $table->timestamp('voting_starts_at')->nullable();
            $table->timestamp('voting_ends_at')->nullable();
            $table->timestamp('finalized_at')->nullable();

            // Quorum tracking
            $table->unsignedInteger('quorum_required');
            $table->unsignedInteger('votes_cast')->default(0);
            $table->boolean('quorum_met')->default(false);

            // Winner and self-appointment
            $table->foreignId('winner_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_self_appointment')->default(false);

            // Audit trail
            $table->foreignId('initiated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['domain_type', 'domain_id']);
            $table->index('status');
            $table->index('voting_ends_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('elections');
    }
};
