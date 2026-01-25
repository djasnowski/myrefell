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
        Schema::table('player_roles', function (Blueprint $table) {
            $table->integer('legitimacy')->default(50)->after('total_salary_earned');
            $table->integer('months_in_office')->default(0)->after('legitimacy');
        });

        // Track legitimacy changes over time
        Schema::create('legitimacy_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_role_id')->constrained()->cascadeOnDelete();
            $table->string('event_type'); // election_landslide, election_narrow, war_won, war_lost, church_support, scandal, etc.
            $table->integer('legitimacy_change');
            $table->integer('legitimacy_before');
            $table->integer('legitimacy_after');
            $table->string('description')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['player_role_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('legitimacy_events');

        Schema::table('player_roles', function (Blueprint $table) {
            $table->dropColumn(['legitimacy', 'months_in_office']);
        });
    }
};
