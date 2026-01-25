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
        // Rename castles table to baronies
        Schema::rename('castles', 'baronies');

        // Rename lord_user_id to baron_user_id
        Schema::table('baronies', function (Blueprint $table) {
            $table->renameColumn('lord_user_id', 'baron_user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('baronies', function (Blueprint $table) {
            $table->renameColumn('baron_user_id', 'lord_user_id');
        });

        Schema::rename('baronies', 'castles');
    }
};
