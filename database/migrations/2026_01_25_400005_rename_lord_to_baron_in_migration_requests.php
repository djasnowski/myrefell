<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Rename lord_* columns to baron_* in migration_requests table.
     */
    public function up(): void
    {
        Schema::table('migration_requests', function (Blueprint $table) {
            $table->renameColumn('lord_approved', 'baron_approved');
            $table->renameColumn('lord_decided_by', 'baron_decided_by');
            $table->renameColumn('lord_decided_at', 'baron_decided_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('migration_requests', function (Blueprint $table) {
            $table->renameColumn('baron_approved', 'lord_approved');
            $table->renameColumn('baron_decided_by', 'lord_decided_by');
            $table->renameColumn('baron_decided_at', 'lord_decided_at');
        });
    }
};
