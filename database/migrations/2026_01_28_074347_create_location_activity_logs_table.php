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
        Schema::create('location_activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('location_type');           // village, town, barony, duchy, kingdom
            $table->unsignedBigInteger('location_id');
            $table->string('activity_type');           // training, gathering, crafting, etc.
            $table->string('activity_subtype')->nullable();  // attack_training, mining, smithing
            $table->string('description');             // Human-readable: "Dan trained Attack"
            $table->json('metadata')->nullable();      // {xp_gained: 15, items: [...]}
            $table->timestamps();

            $table->index(['location_type', 'location_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('location_activity_logs');
    }
};
