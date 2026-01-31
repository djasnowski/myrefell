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
        Schema::create('title_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');                          // Display name: "Baron", "Knight"
            $table->string('slug')->unique();                // Identifier: "baron", "knight"
            $table->unsignedTinyInteger('tier');             // Hierarchy: 1-10
            $table->string('category');                      // commoner, minor_nobility, landed_nobility, royalty
            $table->boolean('is_landed')->default(false);    // Requires domain (barony, duchy, kingdom)
            $table->string('domain_type')->nullable();       // village, barony, duchy, kingdom
            $table->unsignedInteger('limit_per_domain')->nullable(); // Max per domain (e.g., 1 Baron per Barony)
            $table->unsignedInteger('limit_per_superior')->nullable(); // Max per superior title holder
            $table->string('granted_by')->nullable();        // Comma-separated slugs of titles that can grant this
            $table->string('style_of_address')->nullable();  // "Sir", "Lord", "Your Grace", "Your Majesty"
            $table->string('female_variant')->nullable();    // "Dame", "Lady", "Duchess", "Queen"
            $table->text('description')->nullable();
            $table->unsignedInteger('prestige_bonus')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('tier');
            $table->index('category');
        });

        // Add title_type_id to player_titles for foreign key relationship
        Schema::table('player_titles', function (Blueprint $table) {
            $table->foreignId('title_type_id')->nullable()->after('id')->constrained('title_types')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('player_titles', function (Blueprint $table) {
            $table->dropForeign(['title_type_id']);
            $table->dropColumn('title_type_id');
        });

        Schema::dropIfExists('title_types');
    }
};
