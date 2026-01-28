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
        Schema::table('employment_jobs', function (Blueprint $table) {
            // Supervisor role that oversees this job
            $table->string('supervisor_role_slug')->nullable()->after('max_workers');

            // Percentage of wages that go to the supervisor (0-100)
            $table->unsignedTinyInteger('supervisor_cut_percent')->default(10)->after('supervisor_role_slug');

            // Item produced when working (if any)
            $table->string('produces_item')->nullable()->after('supervisor_cut_percent');

            // Chance to produce item (0-100)
            $table->unsignedTinyInteger('production_chance')->default(0)->after('produces_item');

            // Quantity produced on success
            $table->unsignedTinyInteger('production_quantity')->default(1)->after('production_chance');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employment_jobs', function (Blueprint $table) {
            $table->dropColumn([
                'supervisor_role_slug',
                'supervisor_cut_percent',
                'produces_item',
                'production_chance',
                'production_quantity',
            ]);
        });
    }
};
