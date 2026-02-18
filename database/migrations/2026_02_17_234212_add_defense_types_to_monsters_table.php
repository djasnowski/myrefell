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
        Schema::table('monsters', function (Blueprint $table) {
            $table->unsignedSmallInteger('stab_defense')->default(0)->after('defense_level');
            $table->unsignedSmallInteger('slash_defense')->default(0)->after('stab_defense');
            $table->unsignedSmallInteger('crush_defense')->default(0)->after('slash_defense');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('monsters', function (Blueprint $table) {
            $table->dropColumn(['stab_defense', 'slash_defense', 'crush_defense']);
        });
    }
};
