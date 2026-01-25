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
        // Add decay properties to items table
        Schema::table('items', function (Blueprint $table) {
            $table->boolean('is_perishable')->default(false)->after('base_value');
            $table->unsignedSmallInteger('decay_rate_per_week')->default(0)->after('is_perishable');
            $table->unsignedSmallInteger('spoil_after_weeks')->nullable()->after('decay_rate_per_week');
            $table->string('decays_into')->nullable()->after('spoil_after_weeks'); // Item name it transforms into when spoiled
        });

        // Track when items were stored in stockpiles for decay calculations
        Schema::table('location_stockpiles', function (Blueprint $table) {
            $table->unsignedInteger('weeks_stored')->default(0)->after('quantity');
            $table->timestamp('last_decay_at')->nullable()->after('weeks_stored');
        });

        // Track when items were added to player inventory for decay calculations
        Schema::table('player_inventory', function (Blueprint $table) {
            $table->unsignedInteger('weeks_stored')->default(0)->after('is_equipped');
            $table->timestamp('last_decay_at')->nullable()->after('weeks_stored');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->dropColumn(['is_perishable', 'decay_rate_per_week', 'spoil_after_weeks', 'decays_into']);
        });

        Schema::table('location_stockpiles', function (Blueprint $table) {
            $table->dropColumn(['weeks_stored', 'last_decay_at']);
        });

        Schema::table('player_inventory', function (Blueprint $table) {
            $table->dropColumn(['weeks_stored', 'last_decay_at']);
        });
    }
};
