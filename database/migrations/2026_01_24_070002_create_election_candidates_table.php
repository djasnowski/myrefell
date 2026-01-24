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
        Schema::create('election_candidates', function (Blueprint $table) {
            $table->id();

            $table->foreignId('election_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Campaign statement
            $table->text('platform')->nullable();

            // Status tracking
            $table->timestamp('declared_at');
            $table->timestamp('withdrawn_at')->nullable();
            $table->boolean('is_active')->default(true);

            // Denormalized vote count for performance
            $table->unsignedInteger('vote_count')->default(0);

            $table->timestamps();

            // Each user can only be a candidate once per election
            $table->unique(['election_id', 'user_id']);

            // Indexes
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('election_candidates');
    }
};
