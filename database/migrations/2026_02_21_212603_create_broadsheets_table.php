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
        Schema::create('broadsheets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('author_id')->constrained('users')->cascadeOnDelete();
            $table->string('title', 150);
            $table->json('content');
            $table->text('plain_text');
            $table->string('location_type');
            $table->unsignedBigInteger('location_id');
            $table->unsignedBigInteger('barony_id');
            $table->unsignedBigInteger('kingdom_id');
            $table->string('location_name');
            $table->unsignedInteger('view_count')->default(0);
            $table->unsignedInteger('endorse_count')->default(0);
            $table->unsignedInteger('denounce_count')->default(0);
            $table->unsignedInteger('comment_count')->default(0);
            $table->timestamp('published_at');
            $table->timestamps();

            $table->index(['location_type', 'location_id', 'published_at']);
            $table->index(['barony_id', 'published_at']);
            $table->index(['kingdom_id', 'endorse_count', 'published_at']);
            $table->index(['author_id', 'published_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('broadsheets');
    }
};
