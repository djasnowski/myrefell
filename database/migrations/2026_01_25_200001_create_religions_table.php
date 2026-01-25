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
        // Beliefs: preset bonuses/penalties that can be assigned to religions
        Schema::create('beliefs', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->text('description');
            $table->string('icon')->default('sparkles');

            // Bonuses/penalties as JSON
            // e.g., {"gathering_xp_bonus": 10, "combat_xp_penalty": -5}
            $table->json('effects')->nullable();

            // Whether this is a positive or negative belief for display
            $table->enum('type', ['virtue', 'vice', 'neutral'])->default('neutral');

            $table->timestamps();
        });

        // Religions (and cults)
        Schema::create('religions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->string('icon')->default('church');
            $table->string('color')->default('#a855f7'); // purple

            // Type: cult (secret, small) or religion (public, large)
            $table->enum('type', ['cult', 'religion'])->default('cult');

            // Founder (player who created it)
            $table->foreignId('founder_id')->nullable()->constrained('users')->nullOnDelete();

            // Whether the religion is public/discoverable
            $table->boolean('is_public')->default(false);

            // Member limits based on type
            // Cult: 5 members max, Religion: no limit (but 15 required to become one)
            $table->unsignedInteger('member_limit')->default(5);

            // Gold required to found (0 for cult, 100000 for religion conversion)
            $table->unsignedInteger('founding_cost')->default(0);

            // Status
            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });

        // Religion beliefs (which beliefs a religion has adopted)
        Schema::create('religion_beliefs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('religion_id')->constrained()->onDelete('cascade');
            $table->foreignId('belief_id')->constrained()->onDelete('cascade');
            $table->timestamps();

            $table->unique(['religion_id', 'belief_id']);
        });

        // Religion members
        Schema::create('religion_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('religion_id')->constrained()->onDelete('cascade');

            // Rank: prophet (founder), priest (officers), follower (members)
            $table->enum('rank', ['prophet', 'priest', 'follower'])->default('follower');

            // Devotion level (increases with religious actions)
            $table->unsignedInteger('devotion')->default(0);

            $table->timestamp('joined_at');
            $table->timestamps();

            $table->unique(['user_id', 'religion_id']);
            $table->index(['religion_id', 'rank']);
        });

        // Religious structures (shrines, temples, cathedrals)
        Schema::create('religious_structures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('religion_id')->constrained()->onDelete('cascade');

            // Location (polymorphic - village, castle, kingdom)
            $table->string('location_type'); // village, castle, kingdom
            $table->unsignedBigInteger('location_id');

            // Type of structure
            $table->enum('structure_type', ['shrine', 'temple', 'cathedral'])->default('shrine');

            // Structure name
            $table->string('name')->nullable();

            // Cost to build each structure type
            // Shrine: 10,000 gold, Temple: 50,000 gold, Cathedral: 200,000 gold
            $table->unsignedInteger('build_cost')->default(10000);

            // Who built it
            $table->foreignId('built_by_id')->nullable()->constrained('users')->nullOnDelete();

            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['location_type', 'location_id']);
            $table->index(['religion_id', 'structure_type']);
        });

        // Kingdom religion status (state religion, banned religions)
        Schema::create('kingdom_religions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kingdom_id')->constrained()->onDelete('cascade');
            $table->foreignId('religion_id')->constrained()->onDelete('cascade');

            // Status: state (official religion), tolerated, banned
            $table->enum('status', ['state', 'tolerated', 'banned'])->default('tolerated');

            // Who set this status
            $table->foreignId('set_by_id')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->unique(['kingdom_id', 'religion_id']);
        });

        // Religious actions log (prayers, donations, rituals)
        Schema::create('religious_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('religion_id')->constrained()->onDelete('cascade');
            $table->foreignId('religious_structure_id')->nullable()->constrained()->onDelete('set null');

            // Action type
            $table->enum('action_type', ['prayer', 'donation', 'ritual', 'sacrifice', 'pilgrimage']);

            // Devotion gained from this action
            $table->unsignedInteger('devotion_gained')->default(0);

            // Gold spent (for donations)
            $table->unsignedInteger('gold_spent')->default(0);

            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['religion_id', 'action_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('religious_actions');
        Schema::dropIfExists('kingdom_religions');
        Schema::dropIfExists('religious_structures');
        Schema::dropIfExists('religion_members');
        Schema::dropIfExists('religion_beliefs');
        Schema::dropIfExists('religions');
        Schema::dropIfExists('beliefs');
    }
};
