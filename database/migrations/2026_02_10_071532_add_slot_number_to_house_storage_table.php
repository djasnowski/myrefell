<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('house_storage', function (Blueprint $table) {
            $table->integer('slot_number')->default(0)->after('item_id');
        });

        // Backfill existing storage items with sequential slot numbers per house
        $houses = DB::table('house_storage')
            ->select('player_house_id')
            ->distinct()
            ->pluck('player_house_id');

        foreach ($houses as $houseId) {
            $items = DB::table('house_storage')
                ->where('player_house_id', $houseId)
                ->orderBy('id')
                ->pluck('id');

            foreach ($items->values() as $index => $id) {
                DB::table('house_storage')
                    ->where('id', $id)
                    ->update(['slot_number' => $index]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('house_storage', function (Blueprint $table) {
            $table->dropColumn('slot_number');
        });
    }
};
