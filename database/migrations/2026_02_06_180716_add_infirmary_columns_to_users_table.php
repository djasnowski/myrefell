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
            $table->boolean('is_in_infirmary')->default(false)->after('is_traveling');
            $table->timestamp('infirmary_started_at')->nullable()->after('is_in_infirmary');
            $table->timestamp('infirmary_heals_at')->nullable()->after('infirmary_started_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['is_in_infirmary', 'infirmary_started_at', 'infirmary_heals_at']);
        });
    }
};
