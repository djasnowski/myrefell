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
        Schema::create('role_petitions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('petitioner_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('target_player_role_id')->constrained('player_roles')->onDelete('cascade');
            $table->foreignId('authority_user_id')->constrained('users')->onDelete('cascade');
            $table->string('authority_role_slug');
            $table->string('location_type');
            $table->unsignedBigInteger('location_id');
            $table->string('status')->default('pending');
            $table->text('petition_reason');
            $table->boolean('request_appointment')->default(false);
            $table->text('response_message')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['authority_user_id', 'status']);
            $table->index(['petitioner_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('role_petitions');
    }
};
