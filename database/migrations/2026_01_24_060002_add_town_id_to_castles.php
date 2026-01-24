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
        Schema::table('castles', function (Blueprint $table) {
            $table->foreignId('town_id')->nullable()->after('kingdom_id')->constrained()->nullOnDelete();
            $table->foreignId('lord_user_id')->nullable()->after('town_id')->constrained('users')->nullOnDelete();

            $table->index('town_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('castles', function (Blueprint $table) {
            $table->dropForeign(['town_id']);
            $table->dropForeign(['lord_user_id']);
            $table->dropColumn(['town_id', 'lord_user_id']);
        });
    }
};
