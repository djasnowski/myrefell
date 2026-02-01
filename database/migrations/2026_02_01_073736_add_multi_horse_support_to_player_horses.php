<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('player_horses', function (Blueprint $table) {
            // Add is_active column - only one horse can be active per user
            $table->boolean('is_active')->default(false)->after('user_id');

            // Add sort order for stable inventory
            $table->unsignedTinyInteger('sort_order')->default(0)->after('is_active');
        });

        // Set existing horses as active (they were the user's only horse)
        DB::table('player_horses')->update(['is_active' => true]);

        // Drop the unique constraint on user_id to allow multiple horses
        Schema::table('player_horses', function (Blueprint $table) {
            $table->dropUnique(['user_id']);
        });

        // Add index for querying active horses
        Schema::table('player_horses', function (Blueprint $table) {
            $table->index(['user_id', 'is_active']);
            $table->index(['stabled_location_type', 'stabled_location_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('player_horses', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'is_active']);
            $table->dropIndex(['stabled_location_type', 'stabled_location_id']);
        });

        // Re-add unique constraint (will fail if users have multiple horses)
        Schema::table('player_horses', function (Blueprint $table) {
            $table->unique('user_id');
        });

        Schema::table('player_horses', function (Blueprint $table) {
            $table->dropColumn(['is_active', 'sort_order']);
        });
    }
};
