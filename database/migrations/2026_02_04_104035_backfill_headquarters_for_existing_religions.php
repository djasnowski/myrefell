<?php

use App\Models\Religion;
use App\Models\ReligionHeadquarters;
use App\Models\ReligionTreasury;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Get all religions that don't have a headquarters record
        $religions = Religion::whereDoesntHave('headquarters')->get();

        foreach ($religions as $religion) {
            // Create headquarters record (location to be set by prophet later)
            ReligionHeadquarters::create([
                'religion_id' => $religion->id,
                'tier' => 1,
            ]);

            // Create treasury if it doesn't exist
            if (! $religion->treasury) {
                ReligionTreasury::create([
                    'religion_id' => $religion->id,
                    'balance' => 0,
                    'total_collected' => 0,
                    'total_distributed' => 0,
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Don't delete - would lose data
    }
};
