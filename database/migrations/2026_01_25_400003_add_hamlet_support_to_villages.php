<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds hamlet support: Villages with parent_village_id are hamlets.
     * Hamlets are governed by their parent village's elder and use
     * the parent village's services (bank, healer).
     */
    public function up(): void
    {
        Schema::table('villages', function (Blueprint $table) {
            // A hamlet is a village that belongs to another village
            // Note: castle_id is renamed to barony_id in the next migration
            $table->foreignId('parent_village_id')
                ->nullable()
                ->after('castle_id')
                ->constrained('villages')
                ->nullOnDelete();

            $table->index('parent_village_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('villages', function (Blueprint $table) {
            $table->dropForeign(['parent_village_id']);
            $table->dropIndex(['parent_village_id']);
            $table->dropColumn('parent_village_id');
        });
    }
};
