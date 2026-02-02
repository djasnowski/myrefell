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
        Schema::create('stable_stocks', function (Blueprint $table) {
            $table->id();
            $table->string('location_type'); // village, town, barony, duchy, kingdom
            $table->foreignId('horse_id')->constrained()->onDelete('cascade');
            $table->unsignedInteger('quantity')->default(0);
            $table->unsignedInteger('max_quantity')->default(5);
            $table->timestamp('last_restocked_at')->nullable();
            $table->timestamps();

            $table->unique(['location_type', 'horse_id']);
            $table->index('location_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stable_stocks');
    }
};
