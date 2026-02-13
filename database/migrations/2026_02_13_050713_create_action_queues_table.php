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
        Schema::create('action_queues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('action_type');
            $table->json('action_params');
            $table->string('status');
            $table->integer('total')->default(0);
            $table->integer('completed')->default(0);
            $table->integer('total_xp')->default(0);
            $table->integer('total_quantity')->default(0);
            $table->string('item_name')->nullable();
            $table->json('last_level_up')->nullable();
            $table->string('stop_reason')->nullable();
            $table->timestamp('dismissed_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('action_queues');
    }
};
