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
        Schema::table('title_types', function (Blueprint $table) {
            // Progression type: automatic, petition, appointment, special
            $table->string('progression_type')->default('appointment')->after('granted_by');

            // Requirements (JSON for flexibility)
            $table->json('requirements')->nullable()->after('progression_type');

            // Can this title be purchased?
            $table->boolean('can_purchase')->default(false)->after('requirements');
            $table->unsignedInteger('purchase_cost')->nullable()->after('can_purchase');

            // Service requirements
            $table->unsignedInteger('service_days_required')->nullable()->after('purchase_cost');
            $table->string('service_title_slug')->nullable()->after('service_days_required'); // e.g., "knight" for squires

            // Ceremony required?
            $table->boolean('requires_ceremony')->default(false)->after('service_title_slug');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('title_types', function (Blueprint $table) {
            $table->dropColumn([
                'progression_type',
                'requirements',
                'can_purchase',
                'purchase_cost',
                'service_days_required',
                'service_title_slug',
                'requires_ceremony',
            ]);
        });
    }
};
