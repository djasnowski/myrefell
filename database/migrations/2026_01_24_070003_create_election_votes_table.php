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
        Schema::create('election_votes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('election_id')->constrained()->cascadeOnDelete();
            $table->foreignId('voter_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('candidate_id')->constrained('election_candidates')->cascadeOnDelete();

            $table->timestamp('voted_at');

            $table->timestamps();

            // Each user can only vote once per election
            $table->unique(['election_id', 'voter_user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('election_votes');
    }
};
