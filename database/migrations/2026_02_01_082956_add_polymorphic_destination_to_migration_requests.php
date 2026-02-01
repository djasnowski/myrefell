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
        // Add polymorphic home location to users
        Schema::table('users', function (Blueprint $table) {
            $table->string('home_location_type')->nullable()->after('home_village_id');
            $table->unsignedBigInteger('home_location_id')->nullable()->after('home_location_type');
            $table->index(['home_location_type', 'home_location_id']);
        });

        // Migrate existing home_village_id data to new polymorphic columns
        DB::table('users')
            ->whereNotNull('home_village_id')
            ->update([
                'home_location_type' => 'village',
                'home_location_id' => DB::raw('home_village_id'),
            ]);

        // Add polymorphic destination to migration_requests
        Schema::table('migration_requests', function (Blueprint $table) {
            $table->string('to_location_type')->nullable()->after('to_village_id');
            $table->unsignedBigInteger('to_location_id')->nullable()->after('to_location_type');
            $table->string('from_location_type')->nullable()->after('from_village_id');
            $table->unsignedBigInteger('from_location_id')->nullable()->after('from_location_type');
            // Mayor approval columns for town migrations
            $table->boolean('mayor_approved')->nullable()->after('elder_approved');
            $table->unsignedBigInteger('mayor_decided_by')->nullable()->after('elder_decided_by');
            $table->timestamp('mayor_decided_at')->nullable()->after('elder_decided_at');
            $table->index(['to_location_type', 'to_location_id']);
            $table->index(['from_location_type', 'from_location_id']);
        });

        // Migrate existing village data to polymorphic columns
        DB::table('migration_requests')
            ->whereNotNull('to_village_id')
            ->update([
                'to_location_type' => 'village',
                'to_location_id' => DB::raw('to_village_id'),
            ]);

        DB::table('migration_requests')
            ->whereNotNull('from_village_id')
            ->update([
                'from_location_type' => 'village',
                'from_location_id' => DB::raw('from_village_id'),
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['home_location_type', 'home_location_id']);
            $table->dropColumn(['home_location_type', 'home_location_id']);
        });

        Schema::table('migration_requests', function (Blueprint $table) {
            $table->dropIndex(['to_location_type', 'to_location_id']);
            $table->dropIndex(['from_location_type', 'from_location_id']);
            $table->dropColumn([
                'to_location_type',
                'to_location_id',
                'from_location_type',
                'from_location_id',
                'mayor_approved',
                'mayor_decided_by',
                'mayor_decided_at',
            ]);
        });
    }
};
