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
        Schema::create('manumission_requests', function (Blueprint $table) {
            $table->id();

            // The serf requesting freedom
            $table->foreignId('serf_id')->constrained('users')->cascadeOnDelete();

            // The baron who can grant freedom
            $table->foreignId('baron_id')->constrained('users')->cascadeOnDelete();

            // The barony the serf is bound to
            $table->foreignId('barony_id')->constrained('baronies')->cascadeOnDelete();

            // Request type: decree (free grant), purchase, military_service, exceptional_service
            $table->string('request_type', 30);

            // For purchase requests
            $table->integer('gold_offered')->default(0);

            // Reason/justification
            $table->text('reason')->nullable();

            // Status: pending, approved, denied, cancelled
            $table->string('status', 20)->default('pending');

            // Response from baron
            $table->text('response_message')->nullable();
            $table->timestamp('responded_at')->nullable();

            $table->timestamps();

            // A serf can only have one pending request
            $table->unique(['serf_id', 'status'], 'one_pending_per_serf');
        });

        // Ennoblement requests (freeman -> noble)
        Schema::create('ennoblement_requests', function (Blueprint $table) {
            $table->id();

            // The freeman requesting nobility
            $table->foreignId('requester_id')->constrained('users')->cascadeOnDelete();

            // The king who can grant nobility
            $table->foreignId('king_id')->constrained('users')->cascadeOnDelete();

            // The kingdom
            $table->foreignId('kingdom_id')->constrained('kingdoms')->cascadeOnDelete();

            // Request type: royal_decree, military_service, marriage, purchase
            $table->string('request_type', 30);

            // For purchase requests
            $table->integer('gold_offered')->default(0);

            // For marriage requests
            $table->foreignId('spouse_id')->nullable()->constrained('users')->nullOnDelete();

            // Reason/justification
            $table->text('reason')->nullable();

            // Status: pending, approved, denied, cancelled
            $table->string('status', 20)->default('pending');

            // Response from king
            $table->text('response_message')->nullable();
            $table->timestamp('responded_at')->nullable();

            // Title granted if approved
            $table->string('title_granted')->nullable();

            $table->timestamps();
        });

        // Track class change history
        Schema::create('social_class_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('old_class', 20);
            $table->string('new_class', 20);
            $table->string('reason', 100); // manumission, ennoblement, demotion, etc.
            $table->foreignId('granted_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('social_class_history');
        Schema::dropIfExists('ennoblement_requests');
        Schema::dropIfExists('manumission_requests');
    }
};
