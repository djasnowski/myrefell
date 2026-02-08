<?php

use App\Models\Belief;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $belief = Belief::where('name', 'Temperance')->first();

        if ($belief) {
            $effects = $belief->effects;
            $effects['energy_regen_bonus'] = 20;
            $belief->effects = $effects;
            $belief->save();
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $belief = Belief::where('name', 'Temperance')->first();

        if ($belief) {
            $effects = $belief->effects;
            $effects['energy_regen_bonus'] = 5;
            $belief->effects = $effects;
            $belief->save();
        }
    }
};
