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
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('icon')->default('crown');
            $table->text('description');
            $table->string('location_type'); // village, castle, kingdom
            $table->json('permissions')->nullable(); // JSON array of permission strings
            $table->json('bonuses')->nullable(); // JSON object with bonus values
            $table->unsignedInteger('salary')->default(0); // gold per day
            $table->unsignedInteger('tier')->default(1); // authority level
            $table->boolean('is_elected')->default(true); // elected vs appointed
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('max_per_location')->default(1); // how many can hold this role
            $table->timestamps();

            $table->index(['location_type', 'is_active']);
            $table->index('slug');
        });

        Schema::create('player_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('role_id')->constrained()->onDelete('cascade');
            $table->string('location_type'); // village, castle, kingdom
            $table->unsignedBigInteger('location_id');
            $table->enum('status', ['active', 'suspended', 'resigned', 'removed'])->default('active');
            $table->timestamp('appointed_at');
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('removed_at')->nullable();
            $table->foreignId('appointed_by_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('removed_by_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('removal_reason')->nullable();
            $table->unsignedInteger('total_salary_earned')->default(0);
            $table->timestamps();

            $table->unique(['role_id', 'location_type', 'location_id', 'status'], 'unique_active_role_location');
            $table->index(['user_id', 'status']);
            $table->index(['location_type', 'location_id', 'status']);
        });

        Schema::create('location_npcs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained()->onDelete('cascade');
            $table->string('location_type'); // village, castle, kingdom
            $table->unsignedBigInteger('location_id');
            $table->string('npc_name');
            $table->text('npc_description')->nullable();
            $table->string('npc_icon')->default('user');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['role_id', 'location_type', 'location_id'], 'unique_npc_role_location');
            $table->index(['location_type', 'location_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('location_npcs');
        Schema::dropIfExists('player_roles');
        Schema::dropIfExists('roles');
    }
};
