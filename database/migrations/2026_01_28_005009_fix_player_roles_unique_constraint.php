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
        // Drop the old unique constraint that incorrectly includes status
        Schema::table('player_roles', function (Blueprint $table) {
            $table->dropUnique('unique_active_role_location');
        });

        // Create a partial unique index that only enforces uniqueness for active roles
        // This allows multiple resigned/removed records but only one active holder per role/location
        DB::statement('CREATE UNIQUE INDEX unique_active_role_per_location ON player_roles (role_id, location_type, location_id) WHERE status = \'active\'');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop the partial unique index
        DB::statement('DROP INDEX IF EXISTS unique_active_role_per_location');

        // Restore the old unique constraint
        Schema::table('player_roles', function (Blueprint $table) {
            $table->unique(['role_id', 'location_type', 'location_id', 'status'], 'unique_active_role_location');
        });
    }
};
