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
        Schema::table('player_titles', function (Blueprint $table) {
            $table->integer('legitimacy')->default(50)->after('revoked_at');
            $table->integer('months_in_office')->default(0)->after('legitimacy');
        });

        // Update legitimacy_events to be polymorphic (work with both PlayerRole and PlayerTitle)
        Schema::table('legitimacy_events', function (Blueprint $table) {
            // Add polymorphic columns
            $table->string('holder_type')->after('player_role_id')->nullable();
            $table->unsignedBigInteger('holder_id')->after('holder_type')->nullable();

            $table->index(['holder_type', 'holder_id']);
        });

        // Copy existing player_role references to polymorphic columns
        DB::statement("UPDATE legitimacy_events SET holder_type = 'App\\Models\\PlayerRole', holder_id = player_role_id WHERE player_role_id IS NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('legitimacy_events', function (Blueprint $table) {
            $table->dropIndex(['holder_type', 'holder_id']);
            $table->dropColumn(['holder_type', 'holder_id']);
        });

        Schema::table('player_titles', function (Blueprint $table) {
            $table->dropColumn(['legitimacy', 'months_in_office']);
        });
    }
};
