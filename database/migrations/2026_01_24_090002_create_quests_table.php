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
        Schema::create('quests', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('icon')->default('scroll');
            $table->text('description');
            $table->text('objective'); // What the player needs to do
            $table->string('category'); // combat, gathering, delivery, exploration
            $table->string('quest_type'); // kill, gather, deliver, visit
            $table->string('target_type')->nullable(); // monster, item, location
            $table->string('target_identifier')->nullable(); // specific target name
            $table->integer('target_amount')->default(1);
            $table->integer('required_level')->default(1);
            $table->string('required_skill')->nullable();
            $table->integer('required_skill_level')->nullable();
            $table->integer('gold_reward')->default(0);
            $table->integer('xp_reward')->default(0);
            $table->string('xp_skill')->nullable();
            $table->json('item_rewards')->nullable(); // Array of {item_id, quantity}
            $table->boolean('repeatable')->default(false);
            $table->integer('cooldown_hours')->default(0); // For repeatable quests
            $table->boolean('is_active')->default(true);
            $table->integer('weight')->default(100); // For random selection
            $table->timestamps();
        });

        Schema::create('player_quests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('quest_id')->constrained()->onDelete('cascade');
            $table->enum('status', ['active', 'completed', 'claimed', 'abandoned', 'expired'])->default('active');
            $table->integer('current_progress')->default(0);
            $table->timestamp('accepted_at');
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('claimed_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['user_id', 'quest_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('player_quests');
        Schema::dropIfExists('quests');
    }
};
