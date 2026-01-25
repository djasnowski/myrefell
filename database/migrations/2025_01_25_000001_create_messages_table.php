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
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sender_id')->constrained('users')->onDelete('cascade');
            $table->string('channel_type'); // 'location' or 'private'
            $table->unsignedBigInteger('channel_id'); // location_id or conversation partner user_id
            $table->string('channel_location_type')->nullable(); // 'village', 'castle', 'kingdom' for location channels
            $table->text('content');
            $table->boolean('is_deleted')->default(false);
            $table->foreignId('deleted_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('deleted_at')->nullable();
            $table->string('deletion_reason')->nullable();
            $table->timestamps();

            // Index for fetching location messages
            $table->index(['channel_type', 'channel_location_type', 'channel_id', 'created_at']);
            // Index for fetching private messages between users
            $table->index(['channel_type', 'sender_id', 'channel_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
