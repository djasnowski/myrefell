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
        Schema::create('title_petitions', function (Blueprint $table) {
            $table->id();

            // Who is petitioning
            $table->foreignId('petitioner_id')->constrained('users')->cascadeOnDelete();

            // What title they want
            $table->foreignId('title_type_id')->constrained('title_types')->cascadeOnDelete();

            // Who they're petitioning (their proposed superior/grantor)
            $table->foreignId('petition_to_id')->constrained('users')->cascadeOnDelete();

            // Domain context (e.g., which barony for Knight)
            $table->string('domain_type')->nullable();
            $table->unsignedBigInteger('domain_id')->nullable();

            // Status: pending, approved, denied, withdrawn, expired
            $table->string('status')->default('pending');

            // Petitioner's message/reason
            $table->text('petition_message')->nullable();

            // If purchasing the title
            $table->boolean('is_purchase')->default(false);
            $table->unsignedInteger('gold_offered')->default(0);

            // Response from superior
            $table->text('response_message')->nullable();
            $table->timestamp('responded_at')->nullable();

            // Ceremony scheduling (if approved and requires ceremony)
            $table->boolean('ceremony_required')->default(false);
            $table->boolean('ceremony_completed')->default(false);
            $table->timestamp('ceremony_scheduled_at')->nullable();
            $table->timestamp('ceremony_completed_at')->nullable();

            // Expiration (petitions don't last forever)
            $table->timestamp('expires_at')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['petitioner_id', 'status']);
            $table->index(['petition_to_id', 'status']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('title_petitions');
    }
};
