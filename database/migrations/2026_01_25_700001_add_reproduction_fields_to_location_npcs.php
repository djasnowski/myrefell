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
            // Gender for reproduction pairing
            $table->enum('gender', ['male', 'female'])->default('male')->after('family_name');

            // Spouse relationship (nullable, self-referential)
            $table->foreignId('spouse_id')->nullable()->after('gender')
                ->constrained('location_npcs')->nullOnDelete();

            // Parent relationships (nullable, self-referential)
            $table->foreignId('parent1_id')->nullable()->after('spouse_id')
                ->constrained('location_npcs')->nullOnDelete();
            $table->foreignId('parent2_id')->nullable()->after('parent1_id')
                ->constrained('location_npcs')->nullOnDelete();

            // Last year this NPC had a child (cooldown tracking)
            $table->integer('last_birth_year')->nullable()->after('parent2_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('location_npcs', function (Blueprint $table) {
            $table->dropForeign(['spouse_id']);
            $table->dropForeign(['parent1_id']);
            $table->dropForeign(['parent2_id']);
            $table->dropColumn(['gender', 'spouse_id', 'parent1_id', 'parent2_id', 'last_birth_year']);
        });
    }
};
