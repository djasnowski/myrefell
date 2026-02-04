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
        Schema::create('tab_activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->uuid('tab_id');
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->string('route')->nullable();
            $table->string('method', 10)->nullable();
            $table->boolean('is_new_tab')->default(false); // True if different tab_id within 5s of last request
            $table->uuid('previous_tab_id')->nullable(); // The previous tab_id if is_new_tab is true
            $table->timestamp('created_at')->useCurrent();

            // Indexes for fast querying
            $table->index('user_id');
            $table->index('tab_id');
            $table->index('created_at');
            $table->index(['user_id', 'created_at']);
            $table->index(['user_id', 'tab_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tab_activity_logs');
    }
};
