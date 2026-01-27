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
            // Social class: serf, freeman, burgher, noble, clergy
            $table->string('social_class', 20)->default('freeman')->after('gender');

            // For serfs: which barony they are bound to
            $table->foreignId('bound_to_barony_id')->nullable()->after('social_class')
                ->constrained('baronies')->nullOnDelete();

            // Feudal obligations tracking
            $table->integer('labor_days_owed')->default(0)->after('bound_to_barony_id');
            $table->integer('labor_days_completed')->default(0)->after('labor_days_owed');
            $table->date('last_obligation_check')->nullable()->after('labor_days_completed');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['bound_to_barony_id']);
            $table->dropColumn([
                'social_class',
                'bound_to_barony_id',
                'labor_days_owed',
                'labor_days_completed',
                'last_obligation_check',
            ]);
        });
    }
};
