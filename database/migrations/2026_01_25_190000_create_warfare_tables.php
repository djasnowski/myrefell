<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Armies - military forces belonging to players or NPCs
        Schema::create('armies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('commander_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedBigInteger('npc_commander_id')->nullable(); // NPC table may not exist
            $table->string('owner_type'); // kingdom, barony, player, mercenary
            $table->unsignedBigInteger('owner_id');
            $table->string('location_type'); // village, town, castle, field
            $table->unsignedBigInteger('location_id');
            $table->string('status')->default('mustering'); // mustering, marching, encamped, besieging, in_battle, disbanded
            $table->integer('morale')->default(100);
            $table->integer('supplies')->default(100); // days of supplies
            $table->integer('daily_supply_cost')->default(0);
            $table->integer('gold_upkeep')->default(0);
            $table->json('composition')->nullable(); // summary of unit types
            $table->timestamp('mustered_at')->nullable();
            $table->timestamps();

            $table->index(['owner_type', 'owner_id']);
            $table->index(['location_type', 'location_id']);
            $table->index('status');
        });

        // Army units - individual soldier groups in an army
        Schema::create('army_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('army_id')->constrained()->cascadeOnDelete();
            $table->string('unit_type'); // levy, militia, men_at_arms, knights, archers, crossbowmen, cavalry, siege_engineers
            $table->integer('count');
            $table->integer('max_count');
            $table->integer('attack')->default(1);
            $table->integer('defense')->default(1);
            $table->integer('morale_bonus')->default(0);
            $table->integer('upkeep_per_soldier')->default(1);
            $table->string('status')->default('ready'); // ready, exhausted, routed, destroyed
            $table->json('equipment')->nullable();
            $table->timestamps();

            $table->index('unit_type');
        });

        // Wars - conflicts between political entities
        Schema::create('wars', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('casus_belli'); // claim, conquest, rebellion, holy_war, defense, raid
            $table->foreignId('attacker_kingdom_id')->nullable()->constrained('kingdoms')->nullOnDelete();
            $table->foreignId('defender_kingdom_id')->nullable()->constrained('kingdoms')->nullOnDelete();
            $table->string('attacker_type')->nullable(); // kingdom, barony, player
            $table->unsignedBigInteger('attacker_id')->nullable();
            $table->string('defender_type')->nullable();
            $table->unsignedBigInteger('defender_id')->nullable();
            $table->string('status')->default('active'); // active, attacker_winning, defender_winning, white_peace, attacker_victory, defender_victory
            $table->integer('attacker_war_score')->default(0);
            $table->integer('defender_war_score')->default(0);
            $table->json('war_goals')->nullable(); // what the attacker wants
            $table->json('peace_terms')->nullable();
            $table->timestamp('declared_at');
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index(['attacker_type', 'attacker_id']);
            $table->index(['defender_type', 'defender_id']);
        });

        // War participants - allies and enemies in a war
        Schema::create('war_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('war_id')->constrained()->cascadeOnDelete();
            $table->string('participant_type'); // kingdom, barony, player
            $table->unsignedBigInteger('participant_id');
            $table->string('side'); // attacker, defender
            $table->string('role')->default('ally'); // primary, ally, vassal
            $table->boolean('is_war_leader')->default(false);
            $table->integer('contribution_score')->default(0);
            $table->timestamp('joined_at');
            $table->timestamp('left_at')->nullable();
            $table->timestamps();

            $table->index(['participant_type', 'participant_id']);
            $table->index('side');
        });

        // Battles - individual combat engagements
        Schema::create('battles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->foreignId('war_id')->nullable()->constrained()->nullOnDelete();
            $table->string('location_type');
            $table->unsignedBigInteger('location_id');
            $table->string('battle_type')->default('field'); // field, siege_assault, naval, skirmish
            $table->string('status')->default('ongoing'); // ongoing, attacker_victory, defender_victory, draw, inconclusive
            $table->string('phase')->default('engagement'); // engagement, melee, pursuit, aftermath
            $table->integer('day')->default(1);
            $table->integer('attacker_troops_start')->default(0);
            $table->integer('defender_troops_start')->default(0);
            $table->integer('attacker_casualties')->default(0);
            $table->integer('defender_casualties')->default(0);
            $table->json('battle_log')->nullable();
            $table->json('terrain_modifiers')->nullable();
            $table->json('weather_modifiers')->nullable();
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();

            $table->index(['location_type', 'location_id']);
            $table->index('status');
        });

        // Battle participants - armies involved in a battle
        Schema::create('battle_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('battle_id')->constrained()->cascadeOnDelete();
            $table->foreignId('army_id')->constrained()->cascadeOnDelete();
            $table->string('side'); // attacker, defender
            $table->boolean('is_commander')->default(false);
            $table->integer('troops_committed')->default(0);
            $table->integer('casualties')->default(0);
            $table->integer('morale_at_start')->default(100);
            $table->integer('morale_at_end')->nullable();
            $table->string('outcome')->nullable(); // victory, defeat, routed, withdrew
            $table->timestamps();

            $table->index('side');
        });

        // Sieges - prolonged attacks on fortifications
        Schema::create('sieges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('war_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('attacking_army_id')->constrained('armies')->cascadeOnDelete();
            $table->string('target_type'); // castle, town, village
            $table->unsignedBigInteger('target_id');
            $table->string('status')->default('active'); // active, assault, breached, captured, lifted, abandoned
            $table->integer('fortification_level')->default(100);
            $table->integer('garrison_strength')->default(0);
            $table->integer('garrison_morale')->default(100);
            $table->integer('supplies_remaining')->default(100); // percentage
            $table->integer('days_besieged')->default(0);
            $table->boolean('has_breach')->default(false);
            $table->json('siege_equipment')->nullable(); // rams, ladders, trebuchets, etc.
            $table->json('siege_log')->nullable();
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();

            $table->index(['target_type', 'target_id']);
            $table->index('status');
        });

        // Supply lines - logistics for armies
        Schema::create('supply_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('army_id')->constrained()->cascadeOnDelete();
            $table->string('source_type'); // village, town, castle
            $table->unsignedBigInteger('source_id');
            $table->string('status')->default('active'); // active, disrupted, severed
            $table->integer('supply_rate')->default(10); // supplies per day
            $table->integer('distance')->default(1); // in travel days
            $table->integer('safety')->default(100); // percentage, affects disruption chance
            $table->json('route')->nullable(); // path through locations
            $table->timestamps();

            $table->index(['source_type', 'source_id']);
            $table->index('status');
        });

        // Peace treaties - ending wars
        Schema::create('peace_treaties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('war_id')->constrained()->cascadeOnDelete();
            $table->string('treaty_type'); // white_peace, surrender, negotiated
            $table->string('winner_side')->nullable(); // attacker, defender, null for white peace
            $table->json('territory_changes')->nullable();
            $table->integer('gold_payment')->default(0);
            $table->integer('prisoner_exchange')->default(0);
            $table->json('other_terms')->nullable();
            $table->integer('truce_days')->default(365); // days of enforced peace
            $table->timestamp('signed_at');
            $table->timestamp('truce_expires_at');
            $table->timestamps();
        });

        // Mercenary companies - armies for hire
        Schema::create('mercenary_companies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('reputation')->default('unknown'); // unknown, poor, average, good, legendary
            $table->foreignId('army_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('hired_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('hired_by_type')->nullable(); // player, kingdom, barony
            $table->unsignedBigInteger('hired_by_entity_id')->nullable();
            $table->integer('hire_cost')->default(1000);
            $table->integer('daily_cost')->default(100);
            $table->integer('contract_days_remaining')->nullable();
            $table->string('specialization')->nullable(); // cavalry, siege, infantry, archers
            $table->string('home_region')->nullable();
            $table->boolean('is_available')->default(true);
            $table->json('history')->nullable(); // past contracts and battles
            $table->timestamps();

            $table->index('is_available');
            $table->index('reputation');
        });

        // War goals - what participants want from a war
        Schema::create('war_goals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('war_id')->constrained()->cascadeOnDelete();
            $table->string('goal_type'); // conquer_territory, subjugation, independence, raid, humiliate
            $table->string('target_type')->nullable(); // village, town, castle, barony, kingdom
            $table->unsignedBigInteger('target_id')->nullable();
            $table->string('claimant_type'); // player, kingdom, barony
            $table->unsignedBigInteger('claimant_id');
            $table->boolean('is_achieved')->default(false);
            $table->integer('war_score_value')->default(100);
            $table->timestamps();

            $table->index(['target_type', 'target_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('war_goals');
        Schema::dropIfExists('mercenary_companies');
        Schema::dropIfExists('peace_treaties');
        Schema::dropIfExists('supply_lines');
        Schema::dropIfExists('sieges');
        Schema::dropIfExists('battle_participants');
        Schema::dropIfExists('battles');
        Schema::dropIfExists('war_participants');
        Schema::dropIfExists('wars');
        Schema::dropIfExists('army_units');
        Schema::dropIfExists('armies');
    }
};
