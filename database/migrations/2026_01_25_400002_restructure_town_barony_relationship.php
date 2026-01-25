<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Changes: Towns now belong UNDER baronies, not above them.
     * Old: Kingdom → Town → Castle → Village
     * New: Kingdom → Barony → Town/Village
     */
    public function up(): void
    {
        // Step 1: Add barony_id to towns
        Schema::table('towns', function (Blueprint $table) {
            $table->foreignId('barony_id')->nullable()->after('kingdom_id')->constrained('baronies')->nullOnDelete();
            $table->index('barony_id');
        });

        // Step 2: Migrate data - assign towns to baronies based on old relationship
        // Each town gets assigned to a barony that was previously in that town
        DB::statement('
            UPDATE towns t
            SET barony_id = (
                SELECT b.id FROM baronies b
                WHERE b.town_id = t.id
                LIMIT 1
            )
            WHERE EXISTS (
                SELECT 1 FROM baronies b WHERE b.town_id = t.id
            )
        ');

        // Step 3: Drop the old town_id from baronies (relationship is now reversed)
        // Note: Foreign key constraint name is still 'castles_town_id_foreign' from before the rename
        Schema::table('baronies', function (Blueprint $table) {
            $table->dropForeign('castles_town_id_foreign');
            $table->dropIndex('castles_town_id_index');
            $table->dropColumn('town_id');
        });

        // Step 4: Drop kingdom_id from towns (now derived through barony)
        Schema::table('towns', function (Blueprint $table) {
            $table->dropForeign(['kingdom_id']);
            $table->dropIndex(['kingdom_id']);
            $table->dropColumn('kingdom_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Re-add kingdom_id to towns
        Schema::table('towns', function (Blueprint $table) {
            $table->foreignId('kingdom_id')->nullable()->after('description')->constrained()->cascadeOnDelete();
            $table->index('kingdom_id');
        });

        // Re-add town_id to baronies
        Schema::table('baronies', function (Blueprint $table) {
            $table->foreignId('town_id')->nullable()->after('kingdom_id')->constrained()->nullOnDelete();
            $table->index('town_id');
        });

        // Drop barony_id from towns
        Schema::table('towns', function (Blueprint $table) {
            $table->dropForeign(['barony_id']);
            $table->dropIndex(['barony_id']);
            $table->dropColumn('barony_id');
        });
    }
};
