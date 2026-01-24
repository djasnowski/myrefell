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
        Schema::create('player_titles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title'); // peasant, knight, lord, king
            $table->unsignedTinyInteger('tier'); // 1-4
            $table->string('domain_type')->nullable(); // village, castle, town, kingdom
            $table->unsignedBigInteger('domain_id')->nullable();
            $table->string('acquisition_method'); // signup, appointment, election, inheritance, conquest
            $table->foreignId('granted_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamp('granted_at')->useCurrent();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index(['domain_type', 'domain_id']);
            $table->index('is_active');
            $table->index('title');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('player_titles');
    }
};
