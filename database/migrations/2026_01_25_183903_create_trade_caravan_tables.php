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
        // Trade routes between settlements
        Schema::create('trade_routes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('origin_type'); // village, town
            $table->unsignedBigInteger('origin_id');
            $table->string('destination_type');
            $table->unsignedBigInteger('destination_id');
            $table->integer('distance'); // in travel units
            $table->integer('base_travel_days')->default(1);
            $table->enum('danger_level', ['safe', 'moderate', 'dangerous', 'perilous'])->default('safe');
            $table->integer('bandit_chance')->default(5); // % chance of bandit attack per day
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['origin_type', 'origin_id']);
            $table->index(['destination_type', 'destination_id']);
        });

        // Caravans - active trade expeditions
        Schema::create('caravans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete(); // null = NPC caravan
            $table->foreignId('trade_route_id')->nullable()->constrained()->nullOnDelete();
            $table->string('current_location_type');
            $table->unsignedBigInteger('current_location_id');
            $table->string('destination_type');
            $table->unsignedBigInteger('destination_id');
            $table->enum('status', ['preparing', 'traveling', 'arrived', 'returning', 'disbanded', 'destroyed'])->default('preparing');
            $table->integer('capacity')->default(100); // max goods units
            $table->integer('guards')->default(0); // number of guards
            $table->integer('gold_carried')->default(0);
            $table->integer('travel_progress')->default(0); // days traveled
            $table->integer('travel_total')->default(1); // total days needed
            $table->timestamp('departed_at')->nullable();
            $table->timestamp('arrived_at')->nullable();
            $table->boolean('is_npc')->default(false);
            $table->string('npc_merchant_name')->nullable();
            $table->timestamps();

            $table->index(['current_location_type', 'current_location_id']);
            $table->index(['destination_type', 'destination_id']);
            $table->index('status');
        });

        // Goods carried by caravans
        Schema::create('caravan_goods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('caravan_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->constrained()->cascadeOnDelete();
            $table->integer('quantity');
            $table->integer('purchase_price'); // price paid per unit
            $table->string('origin_type'); // where goods were bought
            $table->unsignedBigInteger('origin_id');
            $table->timestamps();

            $table->index('caravan_id');
        });

        // Events that happen during caravan travel
        Schema::create('caravan_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('caravan_id')->constrained()->cascadeOnDelete();
            $table->enum('event_type', [
                'bandit_attack',
                'weather_delay',
                'breakdown',
                'toll_paid',
                'goods_spoiled',
                'merchant_opportunity',
                'guard_desertion',
                'safe_arrival',
            ]);
            $table->text('description');
            $table->integer('gold_lost')->default(0);
            $table->integer('gold_gained')->default(0);
            $table->integer('goods_lost')->default(0);
            $table->integer('guards_lost')->default(0);
            $table->integer('days_delayed')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('caravan_id');
        });

        // Tariffs set by baronies/kingdoms on trade routes
        Schema::create('trade_tariffs', function (Blueprint $table) {
            $table->id();
            $table->string('location_type'); // barony, kingdom
            $table->unsignedBigInteger('location_id');
            $table->foreignId('item_id')->nullable()->constrained()->nullOnDelete(); // null = all goods
            $table->integer('tariff_rate')->default(10); // percentage
            $table->boolean('is_active')->default(true);
            $table->foreignId('set_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['location_type', 'location_id']);
        });

        // Tariff collections (revenue tracking)
        Schema::create('tariff_collections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('caravan_id')->constrained()->cascadeOnDelete();
            $table->foreignId('trade_tariff_id')->constrained()->cascadeOnDelete();
            $table->integer('amount_collected');
            $table->string('location_type');
            $table->unsignedBigInteger('location_id');
            $table->timestamps();

            $table->index(['location_type', 'location_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tariff_collections');
        Schema::dropIfExists('trade_tariffs');
        Schema::dropIfExists('caravan_events');
        Schema::dropIfExists('caravan_goods');
        Schema::dropIfExists('caravans');
        Schema::dropIfExists('trade_routes');
    }
};
