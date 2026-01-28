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
        Schema::table('towns', function (Blueprint $table) {
            // Add barony_id if it doesn't exist
            if (!Schema::hasColumn('towns', 'barony_id')) {
                $table->foreignId('barony_id')->nullable()->after('kingdom_id')->constrained()->nullOnDelete();
            }

            // Add is_port if it doesn't exist
            if (!Schema::hasColumn('towns', 'is_port')) {
                $table->boolean('is_port')->default(false)->after('is_capital');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('towns', function (Blueprint $table) {
            if (Schema::hasColumn('towns', 'barony_id')) {
                $table->dropForeign(['barony_id']);
                $table->dropColumn('barony_id');
            }

            if (Schema::hasColumn('towns', 'is_port')) {
                $table->dropColumn('is_port');
            }
        });
    }
};
