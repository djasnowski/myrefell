<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('player_houses', function (Blueprint $table) {
            $table->string('location_type')->nullable()->after('kingdom_id');
            $table->unsignedBigInteger('location_id')->nullable()->after('location_type');

            $table->index(['location_type', 'location_id']);
        });

        // Backfill: existing houses are kingdom-scoped, map them to their kingdom location
        DB::table('player_houses')
            ->whereNull('location_type')
            ->update([
                'location_type' => 'kingdom',
                'location_id' => DB::raw('kingdom_id'),
            ]);
    }

    public function down(): void
    {
        Schema::table('player_houses', function (Blueprint $table) {
            $table->dropIndex(['location_type', 'location_id']);
            $table->dropColumn(['location_type', 'location_id']);
        });
    }
};
