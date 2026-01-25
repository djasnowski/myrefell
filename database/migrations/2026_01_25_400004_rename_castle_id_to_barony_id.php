<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Rename castle_id to barony_id in villages table and update
     * all related foreign key references.
     */
    public function up(): void
    {
        // Rename castle_id to barony_id in villages
        Schema::table('villages', function (Blueprint $table) {
            // Drop old foreign key
            $table->dropForeign(['castle_id']);
        });

        Schema::table('villages', function (Blueprint $table) {
            // Rename the column
            $table->renameColumn('castle_id', 'barony_id');
        });

        Schema::table('villages', function (Blueprint $table) {
            // Add new foreign key constraint
            $table->foreign('barony_id')->references('id')->on('baronies')->nullOnDelete();
            $table->index('barony_id');
        });

        // Update charters table: rename founded_castle_id to founded_barony_id
        // and update settlement_type enum
        Schema::table('charters', function (Blueprint $table) {
            $table->renameColumn('founded_castle_id', 'founded_barony_id');
        });

        // Update settlement_type enum to use 'barony' instead of 'castle'
        // MySQL requires recreating the column for enum changes
        Schema::table('charters', function (Blueprint $table) {
            $table->string('settlement_type_new')->default('village')->after('settlement_type');
        });

        // Copy data with transformation
        \DB::statement("UPDATE charters SET settlement_type_new = CASE
            WHEN settlement_type = 'castle' THEN 'barony'
            ELSE settlement_type
        END");

        Schema::table('charters', function (Blueprint $table) {
            $table->dropColumn('settlement_type');
        });

        Schema::table('charters', function (Blueprint $table) {
            $table->renameColumn('settlement_type_new', 'settlement_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert settlement_type enum
        Schema::table('charters', function (Blueprint $table) {
            $table->string('settlement_type_old')->default('village')->after('settlement_type');
        });

        \DB::statement("UPDATE charters SET settlement_type_old = CASE
            WHEN settlement_type = 'barony' THEN 'castle'
            ELSE settlement_type
        END");

        Schema::table('charters', function (Blueprint $table) {
            $table->dropColumn('settlement_type');
        });

        Schema::table('charters', function (Blueprint $table) {
            $table->renameColumn('settlement_type_old', 'settlement_type');
        });

        // Revert charters column name
        Schema::table('charters', function (Blueprint $table) {
            $table->renameColumn('founded_barony_id', 'founded_castle_id');
        });

        // Revert villages column name
        Schema::table('villages', function (Blueprint $table) {
            $table->dropForeign(['barony_id']);
            $table->dropIndex(['barony_id']);
        });

        Schema::table('villages', function (Blueprint $table) {
            $table->renameColumn('barony_id', 'castle_id');
        });

        Schema::table('villages', function (Blueprint $table) {
            $table->foreign('castle_id')->references('id')->on('baronies')->nullOnDelete();
        });
    }
};
