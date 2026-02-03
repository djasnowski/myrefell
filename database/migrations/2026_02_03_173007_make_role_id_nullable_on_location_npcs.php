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
        Schema::table('location_npcs', function (Blueprint $table) {
            // Drop the existing foreign key constraint
            $table->dropForeign(['role_id']);
        });

        Schema::table('location_npcs', function (Blueprint $table) {
            // Make role_id nullable (for children who don't hold roles)
            $table->unsignedBigInteger('role_id')->nullable()->change();

            // Re-add the foreign key constraint
            $table->foreign('role_id')->references('id')->on('roles')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('location_npcs', function (Blueprint $table) {
            $table->dropForeign(['role_id']);
        });

        Schema::table('location_npcs', function (Blueprint $table) {
            $table->unsignedBigInteger('role_id')->nullable(false)->change();
            $table->foreign('role_id')->references('id')->on('roles')->onDelete('cascade');
        });
    }
};
