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
            // Rename 'name' to 'username' and make it unique
            $table->renameColumn('name', 'username');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->unique('username');

            // Character info
            $table->enum('gender', ['male', 'female'])->default('male')->after('username');

            // Location - foreign keys added later after villages table exists
            $table->unsignedBigInteger('home_village_id')->nullable()->after('gender');
            $table->string('current_location_type')->default('village')->after('home_village_id'); // village, town, castle, kingdom, wilderness, dungeon
            $table->unsignedBigInteger('current_location_id')->nullable()->after('current_location_type');

            // Player stats
            $table->unsignedSmallInteger('hp')->default(10)->after('current_location_id');
            $table->unsignedSmallInteger('max_hp')->default(10)->after('hp');
            $table->unsignedSmallInteger('energy')->default(150)->after('max_hp');
            $table->unsignedSmallInteger('max_energy')->default(150)->after('energy');
            $table->unsignedBigInteger('gold')->default(0)->after('max_energy');

            // Travel system
            $table->boolean('is_traveling')->default(false)->after('gold');
            $table->string('travel_destination_type')->nullable()->after('is_traveling');
            $table->unsignedBigInteger('travel_destination_id')->nullable()->after('travel_destination_type');
            $table->timestamp('travel_started_at')->nullable()->after('travel_destination_id');
            $table->timestamp('travel_arrives_at')->nullable()->after('travel_started_at');

            // Soft deletes for account suspension/deletion
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropColumn([
                'gender',
                'home_village_id',
                'current_location_type',
                'current_location_id',
                'hp',
                'max_hp',
                'energy',
                'max_energy',
                'gold',
                'is_traveling',
                'travel_destination_type',
                'travel_destination_id',
                'travel_started_at',
                'travel_arrives_at',
            ]);
            $table->dropUnique(['username']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->renameColumn('username', 'name');
        });
    }
};
