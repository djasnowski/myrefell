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
        // Religion treasuries (collective funds)
        Schema::create('religion_treasuries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('religion_id')->unique()->constrained()->onDelete('cascade');
            $table->unsignedBigInteger('balance')->default(0);
            $table->unsignedBigInteger('total_collected')->default(0);
            $table->unsignedBigInteger('total_distributed')->default(0);
            $table->timestamps();
        });

        // Religion treasury transactions
        Schema::create('religion_treasury_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('religion_treasury_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type'); // donation, upgrade_cost, feature_cost, withdrawal
            $table->bigInteger('amount'); // Positive for income, negative for expense
            $table->unsignedBigInteger('balance_after');
            $table->string('description')->nullable();
            $table->timestamps();

            $table->index(['religion_treasury_id', 'created_at']);
            $table->index('type');
        });

        // HQ Feature types (seeded reference data)
        Schema::create('hq_feature_types', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->text('description');
            $table->string('icon')->default('star');
            $table->string('category'); // altar, library, vault, garden, sanctum
            $table->unsignedTinyInteger('min_hq_tier')->default(1);
            $table->json('effects'); // Effect modifiers per level
            $table->unsignedTinyInteger('max_level')->default(3);
            $table->json('level_costs'); // Array of costs per level
            $table->timestamps();
        });

        // Religion headquarters
        Schema::create('religion_headquarters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('religion_id')->unique()->constrained()->onDelete('cascade');
            $table->string('location_type')->nullable(); // village, barony, town, etc.
            $table->unsignedBigInteger('location_id')->nullable();
            $table->unsignedTinyInteger('tier')->default(1);
            $table->string('name')->nullable();
            $table->unsignedBigInteger('total_devotion_invested')->default(0);
            $table->unsignedBigInteger('total_gold_invested')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['location_type', 'location_id']);
        });

        // Built features within an HQ
        Schema::create('religion_hq_features', function (Blueprint $table) {
            $table->id();
            $table->foreignId('religion_hq_id')->constrained('religion_headquarters')->onDelete('cascade');
            $table->foreignId('hq_feature_type_id')->constrained()->onDelete('cascade');
            $table->unsignedTinyInteger('level')->default(1);
            $table->timestamps();

            $table->unique(['religion_hq_id', 'hq_feature_type_id']);
        });

        // Construction projects (HQ upgrades or feature builds)
        Schema::create('hq_construction_projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('religion_hq_id')->constrained('religion_headquarters')->onDelete('cascade');
            $table->string('project_type'); // hq_upgrade, feature_build, feature_upgrade
            $table->foreignId('hq_feature_type_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedTinyInteger('target_level');
            $table->string('status')->default('pending'); // pending, in_progress, completed, cancelled
            $table->unsignedTinyInteger('progress')->default(0); // 0-100
            $table->unsignedBigInteger('gold_required')->default(0);
            $table->unsignedBigInteger('gold_invested')->default(0);
            $table->unsignedBigInteger('devotion_required')->default(0);
            $table->unsignedBigInteger('devotion_invested')->default(0);
            $table->json('items_required')->nullable(); // {item_id: quantity, ...}
            $table->json('items_invested')->nullable(); // {item_id: quantity, ...}
            $table->foreignId('started_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['religion_hq_id', 'status']);
            $table->index('project_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hq_construction_projects');
        Schema::dropIfExists('religion_hq_features');
        Schema::dropIfExists('religion_headquarters');
        Schema::dropIfExists('hq_feature_types');
        Schema::dropIfExists('religion_treasury_transactions');
        Schema::dropIfExists('religion_treasuries');
    }
};
