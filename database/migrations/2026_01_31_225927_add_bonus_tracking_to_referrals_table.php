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
        Schema::table('referrals', function (Blueprint $table) {
            $table->timestamp('bonus_rewarded_at')->nullable()->after('rewarded_at');
            $table->string('referrer_bonus_item')->nullable()->after('bonus_rewarded_at');
            $table->string('referred_bonus_item')->nullable()->after('referrer_bonus_item');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('referrals', function (Blueprint $table) {
            $table->dropColumn(['bonus_rewarded_at', 'referrer_bonus_item', 'referred_bonus_item']);
        });
    }
};
