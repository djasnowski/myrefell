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
            // Buff/potion effects
            $table->string('buff_type')->nullable()->after('hp_bonus'); // attack, strength, defense, accuracy, agility, combat, overload, regeneration
            $table->unsignedSmallInteger('buff_percent')->default(0)->after('buff_type'); // Percentage boost
            $table->unsignedInteger('buff_duration')->default(0)->after('buff_percent'); // Duration in seconds

            // Prayer bonus for prayer potions
            $table->smallInteger('prayer_bonus')->default(0)->after('buff_duration');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->dropColumn([
                'buff_type',
                'buff_percent',
                'buff_duration',
                'prayer_bonus',
            ]);
        });
    }
};
