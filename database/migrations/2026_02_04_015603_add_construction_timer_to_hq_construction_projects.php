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
        Schema::table('hq_construction_projects', function (Blueprint $table) {
            $table->timestamp('construction_ends_at')->nullable()->after('started_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hq_construction_projects', function (Blueprint $table) {
            $table->dropColumn('construction_ends_at');
        });
    }
};
