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
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('banned_at')->nullable()->after('is_admin');
        });

        Schema::create('user_bans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('banned_by')->constrained('users')->cascadeOnDelete();
            $table->text('reason');
            $table->timestamp('banned_at');
            $table->timestamp('unbanned_at')->nullable();
            $table->foreignId('unbanned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('unban_reason')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'banned_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_bans');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('banned_at');
        });
    }
};
