<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Dynasties - family lineages
        Schema::create('dynasties', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('motto')->nullable();
            $table->foreignId('founder_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('current_head_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('coat_of_arms')->nullable(); // Path to image or description
            $table->integer('prestige')->default(0);
            $table->integer('wealth_score')->default(0);
            $table->integer('members_count')->default(1);
            $table->integer('generations')->default(1);
            $table->json('history')->nullable(); // Notable events
            $table->timestamp('founded_at');
            $table->timestamps();

            $table->index('prestige');
        });

        // Dynasty members - tracking lineage
        Schema::create('dynasty_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dynasty_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('npc_id')->nullable();
            $table->string('member_type')->default('player'); // player, npc
            $table->foreignId('father_id')->nullable()->constrained('dynasty_members')->nullOnDelete();
            $table->foreignId('mother_id')->nullable()->constrained('dynasty_members')->nullOnDelete();
            $table->string('first_name');
            $table->string('birth_name')->nullable(); // Maiden name for married women
            $table->string('gender');
            $table->integer('generation')->default(1);
            $table->integer('birth_order')->default(1); // Among siblings
            $table->boolean('is_legitimate')->default(true);
            $table->boolean('is_heir')->default(false);
            $table->boolean('is_disinherited')->default(false);
            $table->string('status')->default('alive'); // alive, dead, missing, exiled
            $table->date('birth_date')->nullable();
            $table->date('death_date')->nullable();
            $table->string('death_cause')->nullable();
            $table->timestamps();

            $table->index(['dynasty_id', 'generation']);
            $table->index('status');
            $table->index('is_heir');
        });

        // Marriages
        Schema::create('marriages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('spouse1_id')->constrained('dynasty_members')->cascadeOnDelete();
            $table->foreignId('spouse2_id')->constrained('dynasty_members')->cascadeOnDelete();
            $table->string('status')->default('active'); // active, divorced, annulled, widowed
            $table->string('marriage_type')->default('standard'); // standard, political, secret, morganatic
            $table->integer('dowry_amount')->default(0);
            $table->json('dowry_items')->nullable();
            $table->json('contract_terms')->nullable();
            $table->foreignId('officiant_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('location_type')->nullable();
            $table->unsignedBigInteger('location_id')->nullable();
            $table->date('wedding_date');
            $table->date('end_date')->nullable();
            $table->string('end_reason')->nullable();
            $table->timestamps();

            $table->index('status');
        });

        // Marriage proposals
        Schema::create('marriage_proposals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proposer_member_id')->constrained('dynasty_members')->cascadeOnDelete();
            $table->foreignId('proposed_member_id')->constrained('dynasty_members')->cascadeOnDelete();
            $table->foreignId('proposer_guardian_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('proposed_guardian_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('pending'); // pending, accepted, rejected, withdrawn, expired
            $table->integer('offered_dowry')->default(0);
            $table->json('offered_items')->nullable();
            $table->json('requested_terms')->nullable();
            $table->text('message')->nullable();
            $table->text('response_message')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();

            $table->index('status');
        });

        // Children (births)
        Schema::create('births', function (Blueprint $table) {
            $table->id();
            $table->foreignId('marriage_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('mother_id')->constrained('dynasty_members')->cascadeOnDelete();
            $table->foreignId('father_id')->nullable()->constrained('dynasty_members')->nullOnDelete();
            $table->foreignId('child_id')->constrained('dynasty_members')->cascadeOnDelete();
            $table->boolean('is_legitimate')->default(true);
            $table->boolean('is_stillborn')->default(false);
            $table->boolean('is_twins')->default(false);
            $table->date('birth_date');
            $table->string('location_type')->nullable();
            $table->unsignedBigInteger('location_id')->nullable();
            $table->timestamps();
        });

        // Succession rules per dynasty
        Schema::create('succession_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dynasty_id')->constrained()->cascadeOnDelete();
            $table->string('succession_type')->default('primogeniture'); // primogeniture, ultimogeniture, seniority, elective, gavelkind
            $table->string('gender_law')->default('agnatic'); // agnatic (males only), agnatic-cognatic (males first), cognatic (equal), enatic (females only)
            $table->boolean('allows_bastards')->default(false);
            $table->boolean('allows_adoption')->default(false);
            $table->integer('minimum_age')->default(16);
            $table->json('additional_requirements')->nullable();
            $table->timestamps();
        });

        // Inheritance claims
        Schema::create('inheritance_claims', function (Blueprint $table) {
            $table->id();
            $table->foreignId('claimant_id')->constrained('dynasty_members')->cascadeOnDelete();
            $table->string('claim_type'); // throne, title, property, wealth
            $table->string('target_type'); // kingdom, barony, dynasty, title
            $table->unsignedBigInteger('target_id');
            $table->string('claim_strength')->default('weak'); // weak, strong, pressed
            $table->string('claim_basis'); // birth, marriage, conquest, grant
            $table->string('status')->default('active'); // active, pressed, won, lost, renounced
            $table->json('supporting_evidence')->nullable();
            $table->timestamps();

            $table->index(['target_type', 'target_id']);
            $table->index('status');
        });

        // Dynasty alliances
        Schema::create('dynasty_alliances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dynasty1_id')->constrained('dynasties')->cascadeOnDelete();
            $table->foreignId('dynasty2_id')->constrained('dynasties')->cascadeOnDelete();
            $table->foreignId('marriage_id')->nullable()->constrained()->nullOnDelete();
            $table->string('alliance_type')->default('marriage'); // marriage, pact, blood_oath
            $table->string('status')->default('active'); // active, broken, expired
            $table->json('terms')->nullable();
            $table->date('formed_at');
            $table->date('expires_at')->nullable();
            $table->date('ended_at')->nullable();
            $table->timestamps();

            $table->index('status');
        });

        // Dynasty events (for history tracking)
        Schema::create('dynasty_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dynasty_id')->constrained()->cascadeOnDelete();
            $table->foreignId('member_id')->nullable()->constrained('dynasty_members')->nullOnDelete();
            $table->string('event_type'); // birth, death, marriage, divorce, succession, achievement, scandal
            $table->string('title');
            $table->text('description')->nullable();
            $table->integer('prestige_change')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamp('occurred_at');
            $table->timestamps();

            $table->index('event_type');
        });

        // Add dynasty reference to users
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('dynasty_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->foreignId('dynasty_member_id')->nullable()->after('dynasty_id')->constrained('dynasty_members')->nullOnDelete();
            $table->foreignId('spouse_id')->nullable()->after('dynasty_member_id')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('dynasty_id');
            $table->dropConstrainedForeignId('dynasty_member_id');
            $table->dropConstrainedForeignId('spouse_id');
        });

        Schema::dropIfExists('dynasty_events');
        Schema::dropIfExists('dynasty_alliances');
        Schema::dropIfExists('inheritance_claims');
        Schema::dropIfExists('succession_rules');
        Schema::dropIfExists('births');
        Schema::dropIfExists('marriage_proposals');
        Schema::dropIfExists('marriages');
        Schema::dropIfExists('dynasty_members');
        Schema::dropIfExists('dynasties');
    }
};
