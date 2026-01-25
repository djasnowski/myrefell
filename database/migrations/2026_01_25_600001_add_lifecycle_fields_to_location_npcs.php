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
        Schema::table('location_npcs', function (Blueprint $table) {
            // Birth year (game year when NPC was born/created)
            $table->integer('birth_year')->default(1)->after('is_active');

            // Death year (null if alive)
            $table->integer('death_year')->nullable()->after('birth_year');

            // Family name for dynasty tracking
            $table->string('family_name')->nullable()->after('npc_name');

            // Personality traits (affects behavior)
            $table->json('personality_traits')->nullable()->after('death_year');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('location_npcs', function (Blueprint $table) {
            $table->dropColumn(['birth_year', 'death_year', 'family_name', 'personality_traits']);
        });
    }
};
