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
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('current_kingdom_id')->nullable()->after('current_location_id');
            $table->timestamp('kingdom_arrived_at')->nullable()->after('current_kingdom_id');

            $table->foreign('current_kingdom_id')->references('id')->on('kingdoms')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['current_kingdom_id']);
            $table->dropColumn(['current_kingdom_id', 'kingdom_arrived_at']);
        });
    }
};
