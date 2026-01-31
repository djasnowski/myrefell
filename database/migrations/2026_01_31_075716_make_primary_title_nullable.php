<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('primary_title')->nullable()->default(null)->change();
        });

        // Update existing 'peasant' values to null
        DB::table('users')->where('primary_title', 'peasant')->update(['primary_title' => null]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Set null values back to 'peasant'
        DB::table('users')->whereNull('primary_title')->update(['primary_title' => 'peasant']);

        Schema::table('users', function (Blueprint $table) {
            $table->string('primary_title')->default('peasant')->change();
        });
    }
};
