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
        // Add cult_only and required_hideout_tier columns to beliefs table
        Schema::table('beliefs', function (Blueprint $table) {
            $table->boolean('cult_only')->default(false)->after('type');
            $table->unsignedTinyInteger('required_hideout_tier')->nullable()->after('cult_only');
            $table->unsignedTinyInteger('hp_cost')->nullable()->after('required_hideout_tier');
            $table->unsignedSmallInteger('energy_cost')->nullable()->after('hp_cost');
        });

        // Add hideout columns to religions table
        Schema::table('religions', function (Blueprint $table) {
            $table->unsignedTinyInteger('hideout_tier')->default(0)->after('is_active');
            $table->string('hideout_location_type')->nullable()->after('hideout_tier');
            $table->unsignedBigInteger('hideout_location_id')->nullable()->after('hideout_location_type');
        });

        // Create cult hideout construction projects table
        Schema::create('cult_hideout_projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('religion_id')->constrained()->onDelete('cascade');

            // Project type
            $table->enum('project_type', ['build', 'upgrade'])->default('build');
            $table->unsignedTinyInteger('target_tier');

            // Requirements
            $table->unsignedInteger('gold_required')->default(0);
            $table->unsignedInteger('gold_invested')->default(0);
            $table->unsignedInteger('devotion_required')->default(0);
            $table->unsignedInteger('devotion_invested')->default(0);

            // Status
            $table->enum('status', ['pending', 'in_progress', 'constructing', 'completed', 'cancelled'])->default('pending');
            $table->timestamp('construction_ends_at')->nullable();

            // Metadata
            $table->foreignId('started_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['religion_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cult_hideout_projects');

        Schema::table('religions', function (Blueprint $table) {
            $table->dropColumn(['hideout_tier', 'hideout_location_type', 'hideout_location_id']);
        });

        Schema::table('beliefs', function (Blueprint $table) {
            $table->dropColumn(['cult_only', 'required_hideout_tier', 'hp_cost', 'energy_cost']);
        });
    }
};
