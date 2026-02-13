<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('house_servants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_house_id')->unique()->constrained('player_houses')->cascadeOnDelete();
            $table->string('servant_type');
            $table->string('name')->default('Servant');
            $table->boolean('on_strike')->default(false);
            $table->timestamp('last_paid_at')->nullable();
            $table->timestamp('hired_at');
            $table->timestamps();
        });

        Schema::create('servant_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('house_servant_id')->constrained('house_servants')->cascadeOnDelete();
            $table->string('task_type');
            $table->json('task_data');
            $table->string('status')->default('queued');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('estimated_completion')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->string('result_message')->nullable();
            $table->timestamps();

            $table->index(['house_servant_id', 'status']);
            $table->index(['status', 'estimated_completion']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('servant_tasks');
        Schema::dropIfExists('house_servants');
    }
};
