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
        Schema::create('house_portals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_house_id')->constrained('player_houses')->cascadeOnDelete();
            $table->unsignedTinyInteger('portal_slot');
            $table->string('destination_type');
            $table->unsignedBigInteger('destination_id');
            $table->string('destination_name');
            $table->timestamps();

            $table->unique(['player_house_id', 'portal_slot']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('house_portals');
    }
};
