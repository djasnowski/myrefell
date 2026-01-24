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
            $table->foreignId('capital_town_id')->nullable()->after('capital_castle_id')->constrained('towns')->nullOnDelete();
            $table->foreignId('king_user_id')->nullable()->after('capital_town_id')->constrained('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kingdoms', function (Blueprint $table) {
            $table->dropForeign(['capital_town_id']);
            $table->dropForeign(['king_user_id']);
            $table->dropColumn(['capital_town_id', 'king_user_id']);
        });
    }
};
