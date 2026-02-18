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
        Schema::table('combat_sessions', function (Blueprint $table) {
            $table->unsignedTinyInteger('attack_style_index')->default(0)->after('training_style');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('combat_sessions', function (Blueprint $table) {
            $table->dropColumn('attack_style_index');
        });
    }
};
