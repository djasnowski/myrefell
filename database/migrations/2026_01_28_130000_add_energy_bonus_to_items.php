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
        Schema::table('items', function (Blueprint $table) {
            $table->integer('energy_bonus')->default(0)->after('hp_bonus');
        });

        // Update Energy Elixir to have energy_bonus
        \App\Models\Item::where('name', 'Energy Elixir')->update(['energy_bonus' => 10]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->dropColumn('energy_bonus');
        });
    }
};
