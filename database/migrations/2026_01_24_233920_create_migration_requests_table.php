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
        Schema::create('migration_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('from_village_id')->constrained('villages')->onDelete('cascade');
            $table->foreignId('to_village_id')->constrained('villages')->onDelete('cascade');

            // Approval status at each level (null = pending, true = approved, false = denied)
            $table->boolean('elder_approved')->nullable(); // Destination village elder
            $table->boolean('lord_approved')->nullable();  // Destination castle lord
            $table->boolean('king_approved')->nullable();  // Destination kingdom king

            // Who approved/denied at each level
            $table->foreignId('elder_decided_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('lord_decided_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('king_decided_by')->nullable()->constrained('users')->onDelete('set null');

            $table->timestamp('elder_decided_at')->nullable();
            $table->timestamp('lord_decided_at')->nullable();
            $table->timestamp('king_decided_at')->nullable();

            $table->string('status')->default('pending'); // pending, approved, denied, completed, cancelled
            $table->text('denial_reason')->nullable();

            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['to_village_id', 'status']);
        });

        // Add last_migration_at to users for cooldown
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('last_migration_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('last_migration_at');
        });

        Schema::dropIfExists('migration_requests');
    }
};
