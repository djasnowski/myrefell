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
        // Charters for founding new settlements
        Schema::create('charters', function (Blueprint $table) {
            $table->id();
            $table->string('settlement_name');
            $table->text('description')->nullable();

            // Settlement type: village, town, or castle
            $table->enum('settlement_type', ['village', 'town', 'castle'])->default('village');

            // Kingdom where the charter is issued
            $table->foreignId('kingdom_id')->constrained()->onDelete('cascade');

            // The issuer (King or authorized official who can grant charters)
            $table->foreignId('issuer_id')->nullable()->constrained('users')->nullOnDelete();

            // The founder (player who is founding the settlement)
            $table->foreignId('founder_id')->constrained('users')->onDelete('cascade');

            // Tax terms (JSON containing tax rates and conditions)
            // e.g., {"village_rate": 10, "kingdom_tribute": 15, "years_tax_free": 2}
            $table->json('tax_terms')->nullable();

            // Gold paid for the charter
            $table->unsignedBigInteger('gold_cost')->default(1000000);

            // Status: pending (awaiting approval), approved (can found), active (founded),
            // rejected, expired, failed (settlement became ruins)
            $table->enum('status', ['pending', 'approved', 'active', 'rejected', 'expired', 'failed'])->default('pending');

            // Number of signatories required and current count
            $table->unsignedInteger('required_signatories')->default(10);
            $table->unsignedInteger('current_signatories')->default(0);

            // Dates
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('founded_at')->nullable();
            $table->timestamp('expires_at')->nullable(); // Charter expires if not founded in time

            // Vulnerability window (new settlements are vulnerable for a period)
            $table->timestamp('vulnerability_ends_at')->nullable();

            // Location data for the new settlement
            $table->integer('coordinates_x')->nullable();
            $table->integer('coordinates_y')->nullable();
            $table->string('biome')->nullable();

            // If founded, link to the created settlement
            $table->unsignedBigInteger('founded_village_id')->nullable();
            $table->unsignedBigInteger('founded_castle_id')->nullable();

            // Rejection/failure reason
            $table->text('rejection_reason')->nullable();

            $table->timestamps();

            $table->index(['kingdom_id', 'status']);
            $table->index(['founder_id', 'status']);
            $table->index('status');
        });

        // Charter signatories (players who sign/endorse the charter)
        Schema::create('charter_signatories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('charter_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->text('comment')->nullable(); // Optional endorsement message
            $table->timestamps();

            $table->unique(['charter_id', 'user_id']);
            $table->index('charter_id');
        });

        // Settlement ruins (failed settlements that can be reclaimed)
        Schema::create('settlement_ruins', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->foreignId('kingdom_id')->constrained()->onDelete('cascade');
            $table->foreignId('original_charter_id')->nullable()->constrained('charters')->nullOnDelete();

            // Original founder who failed
            $table->foreignId('original_founder_id')->nullable()->constrained('users')->nullOnDelete();

            // Location
            $table->integer('coordinates_x');
            $table->integer('coordinates_y');
            $table->string('biome');

            // Gold cost to reclaim (usually less than new charter)
            $table->unsignedBigInteger('reclaim_cost')->default(500000);

            // Status
            $table->boolean('is_reclaimable')->default(true);

            $table->timestamp('ruined_at');
            $table->timestamps();

            $table->index(['kingdom_id', 'is_reclaimable']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settlement_ruins');
        Schema::dropIfExists('charter_signatories');
        Schema::dropIfExists('charters');
    }
};
