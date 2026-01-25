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
        // Tax collection records - audit trail of all tax movements
        Schema::create('tax_collections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payer_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('payer_location_type')->nullable(); // village, castle (for location-to-location taxes)
            $table->unsignedBigInteger('payer_location_id')->nullable();
            $table->string('receiver_location_type'); // village, castle, kingdom
            $table->unsignedBigInteger('receiver_location_id');
            $table->unsignedBigInteger('amount');
            $table->string('tax_type'); // income, property, trade, role_salary
            $table->string('description')->nullable();
            $table->date('tax_period'); // the period this tax covers
            $table->timestamps();

            $table->index(['payer_user_id', 'created_at']);
            $table->index(['receiver_location_type', 'receiver_location_id']);
            $table->index(['tax_period', 'tax_type']);
        });

        // Location treasury - stores gold for villages, castles, kingdoms
        Schema::create('location_treasuries', function (Blueprint $table) {
            $table->id();
            $table->string('location_type'); // village, castle, kingdom
            $table->unsignedBigInteger('location_id');
            $table->unsignedBigInteger('balance')->default(0);
            $table->unsignedBigInteger('total_collected')->default(0); // lifetime collections
            $table->unsignedBigInteger('total_distributed')->default(0); // lifetime distributions
            $table->timestamps();

            $table->unique(['location_type', 'location_id']);
            $table->index(['location_type', 'location_id']);
        });

        // Treasury transactions - track all treasury movements
        Schema::create('treasury_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('location_treasury_id')->constrained()->onDelete('cascade');
            $table->string('type'); // tax_income, salary_payment, transfer_in, transfer_out
            $table->bigInteger('amount'); // can be negative for outflows
            $table->unsignedBigInteger('balance_after');
            $table->string('description');
            $table->foreignId('related_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('related_location_type')->nullable();
            $table->unsignedBigInteger('related_location_id')->nullable();
            $table->timestamps();

            $table->index(['location_treasury_id', 'created_at']);
            $table->index(['type', 'created_at']);
        });

        // Salary payments - track salary distributions to role holders
        Schema::create('salary_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('player_role_id')->constrained()->onDelete('cascade');
            $table->unsignedBigInteger('amount');
            $table->string('source_location_type'); // where the salary came from
            $table->unsignedBigInteger('source_location_id');
            $table->date('pay_period');
            $table->timestamps();

            $table->index(['user_id', 'pay_period']);
            $table->index(['player_role_id', 'pay_period']);
            $table->unique(['player_role_id', 'pay_period'], 'unique_salary_per_period');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('salary_payments');
        Schema::dropIfExists('treasury_transactions');
        Schema::dropIfExists('location_treasuries');
        Schema::dropIfExists('tax_collections');
    }
};
