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
        Schema::table('player_houses', function (Blueprint $table) {
            $table->timestamp('upkeep_due_at')->nullable()->after('condition');
        });

        // Backfill existing houses with 7-day grace period
        DB::table('player_houses')
            ->whereNull('upkeep_due_at')
            ->update(['upkeep_due_at' => now()->addDays(7)]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('player_houses', function (Blueprint $table) {
            $table->dropColumn('upkeep_due_at');
        });
    }
};
