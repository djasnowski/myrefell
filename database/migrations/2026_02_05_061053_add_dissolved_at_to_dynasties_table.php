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
        Schema::table('dynasties', function (Blueprint $table) {
            $table->string('status')->default('active')->after('name');
            $table->timestamp('dissolved_at')->nullable()->after('founded_at');
            $table->text('dissolution_reason')->nullable()->after('dissolved_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dynasties', function (Blueprint $table) {
            $table->dropColumn(['status', 'dissolved_at', 'dissolution_reason']);
        });
    }
};
