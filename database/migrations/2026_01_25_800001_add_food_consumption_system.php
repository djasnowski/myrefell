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
        // Add starvation tracking to location_npcs
        Schema::table('location_npcs', function (Blueprint $table) {
            $table->integer('weeks_without_food')->default(0)->after('personality_traits');
        });

        // Add starvation tracking to users
        Schema::table('users', function (Blueprint $table) {
            $table->integer('weeks_without_food')->default(0)->after('max_energy');
        });

        // Add food supply tracking to villages
        Schema::table('villages', function (Blueprint $table) {
            $table->integer('granary_capacity')->default(500)->after('wealth');
            $table->integer('last_food_check_week')->nullable()->after('granary_capacity');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('location_npcs', function (Blueprint $table) {
            $table->dropColumn('weeks_without_food');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('weeks_without_food');
        });

        Schema::table('villages', function (Blueprint $table) {
            $table->dropColumn(['granary_capacity', 'last_food_check_week']);
        });
    }
};
