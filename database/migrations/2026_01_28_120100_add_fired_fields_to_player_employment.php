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
        Schema::table('player_employment', function (Blueprint $table) {
            // ID of the supervisor who fired the worker
            $table->foreignId('fired_by')->nullable()->after('total_earnings')->constrained('users')->nullOnDelete();

            // When the worker was fired
            $table->timestamp('fired_at')->nullable()->after('fired_by');

            // Optional reason for firing
            $table->string('fired_reason')->nullable()->after('fired_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('player_employment', function (Blueprint $table) {
            $table->dropForeign(['fired_by']);
            $table->dropColumn(['fired_by', 'fired_at', 'fired_reason']);
        });
    }
};
