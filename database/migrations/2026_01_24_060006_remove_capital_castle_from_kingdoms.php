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
        Schema::table('kingdoms', function (Blueprint $table) {
            $table->dropForeign(['capital_castle_id']);
            $table->dropColumn('capital_castle_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kingdoms', function (Blueprint $table) {
            $table->foreignId('capital_castle_id')->nullable()->constrained('castles')->nullOnDelete();
        });
    }
};
