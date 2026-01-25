<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Update the min_location_type enum to use 'barony' instead of 'castle'.
     */
    public function up(): void
    {
        // First, update any existing 'castle' values to 'barony'
        DB::statement("UPDATE horses SET min_location_type = 'barony' WHERE min_location_type = 'castle'");

        // Drop the existing constraint and create a new one with 'barony'
        DB::statement('ALTER TABLE horses DROP CONSTRAINT IF EXISTS horses_min_location_type_check');
        DB::statement("ALTER TABLE horses ADD CONSTRAINT horses_min_location_type_check CHECK (min_location_type IN ('village', 'town', 'barony', 'kingdom'))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert 'barony' values to 'castle'
        DB::statement("UPDATE horses SET min_location_type = 'castle' WHERE min_location_type = 'barony'");

        // Drop and recreate with 'castle'
        DB::statement('ALTER TABLE horses DROP CONSTRAINT IF EXISTS horses_min_location_type_check');
        DB::statement("ALTER TABLE horses ADD CONSTRAINT horses_min_location_type_check CHECK (min_location_type IN ('village', 'town', 'castle', 'kingdom'))");
    }
};
