<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('garden_plots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_house_id')->constrained('player_houses')->cascadeOnDelete();
            $table->string('plot_slot');
            $table->foreignId('crop_type_id')->nullable()->constrained('crop_types')->nullOnDelete();
            $table->string('status')->default('empty');
            $table->timestamp('planted_at')->nullable();
            $table->timestamp('ready_at')->nullable();
            $table->timestamp('withers_at')->nullable();
            $table->integer('quality')->default(60);
            $table->integer('times_tended')->default(0);
            $table->boolean('is_watered')->default(false);
            $table->timestamp('last_watered_at')->nullable();
            $table->boolean('is_composted')->default(false);
            $table->timestamps();

            $table->unique(['player_house_id', 'plot_slot']);
        });

        Schema::table('player_houses', function (Blueprint $table) {
            $table->integer('compost_charges')->default(0);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('garden_plots');

        Schema::table('player_houses', function (Blueprint $table) {
            $table->dropColumn('compost_charges');
        });
    }
};
