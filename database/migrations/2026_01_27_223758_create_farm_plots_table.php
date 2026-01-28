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
        Schema::create('farm_plots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('crop_type_id')->nullable()->constrained()->nullOnDelete();

            // Location where the plot is
            $table->string('location_type'); // village, barony
            $table->unsignedBigInteger('location_id');

            // Plot status
            $table->enum('status', ['empty', 'planted', 'growing', 'ready', 'withered'])->default('empty');

            // Timing
            $table->timestamp('planted_at')->nullable();
            $table->timestamp('ready_at')->nullable();
            $table->timestamp('withers_at')->nullable(); // If not harvested in time

            // Quality affects yield
            $table->unsignedTinyInteger('quality')->default(50); // 0-100, affected by tending

            // How many times the plot has been tended this growth cycle
            $table->unsignedTinyInteger('times_tended')->default(0);

            // Watered status (resets daily)
            $table->boolean('is_watered')->default(false);
            $table->timestamp('last_watered_at')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['user_id', 'location_type', 'location_id']);
            $table->index(['status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('farm_plots');
    }
};
