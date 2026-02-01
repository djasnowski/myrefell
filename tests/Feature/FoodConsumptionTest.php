<?php

use App\Models\Item;
use App\Models\LocationNpc;
use App\Models\LocationStockpile;
use App\Models\User;
use App\Models\Village;
use App\Models\WorldState;
use App\Services\CalendarService;
use App\Services\FoodConsumptionService;

beforeEach(function () {
    // Clear any existing data
    WorldState::query()->delete();
    LocationNpc::query()->delete();
    LocationStockpile::query()->delete();
    Village::query()->delete();
    User::query()->delete();
    Item::query()->delete();

    // Create the grain item
    Item::create([
        'name' => 'Grain',
        'description' => 'A sack of grain. The staple food of the realm.',
        'type' => 'resource',
        'subtype' => 'grain',
        'rarity' => 'common',
        'stackable' => true,
        'max_stack' => 1000,
        'base_value' => 2,
    ]);

    // Create a world state
    WorldState::factory()->create([
        'current_year' => 50,
        'current_season' => 'summer',
        'current_week' => 5,
    ]);
});

test('service can get village food stats', function () {
    $village = Village::create([
        'name' => 'Test Village',
        'description' => 'A test village',
        'biome' => 'plains',
        'granary_capacity' => 500,
    ]);

    $service = new FoodConsumptionService;
    $stats = $service->getVillageFoodStats($village);

    expect($stats)->toHaveKeys([
        'food_available',
        'food_needed_per_week',
        'weeks_of_food',
        'granary_capacity',
        'population',
        'npc_count',
        'player_count',
        'starving_npcs',
        'starving_players',
    ]);
    expect($stats['granary_capacity'])->toBe(500);
});

test('food is consumed weekly from village stockpile', function () {
    $village = Village::create([
        'name' => 'Test Village',
        'description' => 'A test village',
        'biome' => 'plains',
        'granary_capacity' => 500,
    ]);

    // Create some NPCs in the village
    LocationNpc::factory()->count(3)->create([
        'location_type' => 'village',
        'location_id' => $village->id,
        'weeks_without_food' => 0,
    ]);

    // Add food to the stockpile
    $grainItem = Item::where('name', 'Grain')->first();
    $stockpile = LocationStockpile::getOrCreate('village', $village->id, $grainItem->id);
    $stockpile->addQuantity(100);

    $service = new FoodConsumptionService;
    $results = $service->processWeeklyConsumption();

    // Refresh stockpile
    $stockpile->refresh();

    // Should have consumed 3 units (1 per NPC per week)
    expect($stockpile->quantity)->toBe(97);
    expect($results['food_consumed'])->toBe(3);
    expect($results['villages_processed'])->toBe(1);
});

test('npcs become starving when food runs out', function () {
    $village = Village::create([
        'name' => 'Test Village',
        'description' => 'A test village',
        'biome' => 'plains',
        'granary_capacity' => 500,
    ]);

    // Create NPCs but no food in stockpile
    $npcs = LocationNpc::factory()->count(3)->create([
        'location_type' => 'village',
        'location_id' => $village->id,
        'weeks_without_food' => 0,
    ]);

    $service = new FoodConsumptionService;
    $results = $service->processWeeklyConsumption();

    // NPCs should now be starving
    expect($results['npcs_starving'])->toBe(3);

    // Verify the database was updated
    foreach ($npcs as $npc) {
        $npc->refresh();
        expect($npc->weeks_without_food)->toBe(1);
    }
});

test('npcs die after maximum weeks without food', function () {
    $village = Village::create([
        'name' => 'Test Village',
        'description' => 'A test village',
        'biome' => 'plains',
        'granary_capacity' => 500,
    ]);

    // Create an NPC at the brink of starvation death
    $npc = LocationNpc::factory()->create([
        'location_type' => 'village',
        'location_id' => $village->id,
        'weeks_without_food' => FoodConsumptionService::MAX_WEEKS_WITHOUT_FOOD - 1,
        'birth_year' => 20,
    ]);

    $service = new FoodConsumptionService;
    $results = $service->processWeeklyConsumption();

    // NPC should have died from starvation
    expect($results['npcs_died'])->toBe(1);

    $npc->refresh();
    expect($npc->isDead())->toBeTrue();
    expect($npc->death_year)->toBe(50); // Current year
});

test('starvation counters reset when food is available', function () {
    $village = Village::create([
        'name' => 'Test Village',
        'description' => 'A test village',
        'biome' => 'plains',
        'granary_capacity' => 500,
    ]);

    // Create starving NPCs
    $npcs = LocationNpc::factory()->count(2)->create([
        'location_type' => 'village',
        'location_id' => $village->id,
        'weeks_without_food' => 2,
    ]);

    // Add enough food
    $grainItem = Item::where('name', 'Grain')->first();
    $stockpile = LocationStockpile::getOrCreate('village', $village->id, $grainItem->id);
    $stockpile->addQuantity(100);

    $service = new FoodConsumptionService;
    $service->processWeeklyConsumption();

    // Starvation counters should be reset
    foreach ($npcs as $npc) {
        $npc->refresh();
        expect($npc->weeks_without_food)->toBe(0);
    }
});

test('players receive energy penalties when starving', function () {
    $village = Village::create([
        'name' => 'Test Village',
        'description' => 'A test village',
        'biome' => 'plains',
        'granary_capacity' => 500,
    ]);

    // Create a player in the village with full energy
    $player = User::factory()->create([
        'home_village_id' => $village->id,
        'energy' => 100,
        'max_energy' => 100,
        'weeks_without_food' => 0,
    ]);

    // No food in stockpile
    $service = new FoodConsumptionService;
    $results = $service->processWeeklyConsumption();

    expect($results['players_starving'])->toBe(1);
    expect($results['players_penalized'])->toBe(1);

    $player->refresh();
    expect($player->weeks_without_food)->toBe(1);
    expect($player->energy)->toBeLessThan(100);
});

test('service can add food to village stockpile', function () {
    $village = Village::create([
        'name' => 'Test Village',
        'description' => 'A test village',
        'biome' => 'plains',
        'granary_capacity' => 100,
    ]);

    $service = new FoodConsumptionService;
    $result = $service->addFoodToVillage($village, 50);

    expect($result)->toBe(50);

    $grainItem = Item::where('name', 'Grain')->first();
    $stockpile = LocationStockpile::atLocation('village', $village->id)
        ->forItem($grainItem->id)
        ->first();

    expect($stockpile->quantity)->toBe(50);
});

test('food addition respects granary capacity', function () {
    $village = Village::create([
        'name' => 'Test Village',
        'description' => 'A test village',
        'biome' => 'plains',
        'granary_capacity' => 100,
    ]);

    $service = new FoodConsumptionService;
    $service->addFoodToVillage($village, 150); // Try to add more than capacity

    $grainItem = Item::where('name', 'Grain')->first();
    $stockpile = LocationStockpile::atLocation('village', $village->id)
        ->forItem($grainItem->id)
        ->first();

    // Should be capped at capacity
    expect($stockpile->quantity)->toBe(100);
});

test('service can initialize village food supplies', function () {
    $village = Village::create([
        'name' => 'Test Village',
        'description' => 'A test village',
        'biome' => 'plains',
        'granary_capacity' => 500,
    ]);

    // Create some population
    LocationNpc::factory()->count(5)->create([
        'location_type' => 'village',
        'location_id' => $village->id,
    ]);

    $service = new FoodConsumptionService;
    $initialized = $service->initializeVillageFoodSupplies(12);

    expect($initialized)->toBe(1);

    // Check that food was added
    $grainItem = Item::where('name', 'Grain')->first();
    $stockpile = LocationStockpile::atLocation('village', $village->id)
        ->forItem($grainItem->id)
        ->first();

    // Should have 5 * 1 * 12 = 60 units of food
    expect($stockpile->quantity)->toBe(60);
});

test('calendar service dispatches food consumption job on week advance', function () {
    \Illuminate\Support\Facades\Queue::fake();

    $service = new CalendarService;
    $service->advanceWeek();

    \Illuminate\Support\Facades\Queue::assertPushed(\App\Jobs\ProcessFoodConsumption::class);
});

test('dead npcs do not consume food', function () {
    $village = Village::create([
        'name' => 'Test Village',
        'description' => 'A test village',
        'biome' => 'plains',
        'granary_capacity' => 500,
    ]);

    // Create living and dead NPCs
    LocationNpc::factory()->create([
        'location_type' => 'village',
        'location_id' => $village->id,
        'death_year' => null,
    ]);

    LocationNpc::factory()->dead()->create([
        'location_type' => 'village',
        'location_id' => $village->id,
    ]);

    // Add food
    $grainItem = Item::where('name', 'Grain')->first();
    $stockpile = LocationStockpile::getOrCreate('village', $village->id, $grainItem->id);
    $stockpile->addQuantity(100);

    $service = new FoodConsumptionService;
    $results = $service->processWeeklyConsumption();

    $stockpile->refresh();

    // Only 1 unit consumed (dead NPC doesn't count)
    expect($stockpile->quantity)->toBe(99);
    expect($results['food_consumed'])->toBe(1);
});

test('partial food shortage affects all population', function () {
    $village = Village::create([
        'name' => 'Test Village',
        'description' => 'A test village',
        'biome' => 'plains',
        'granary_capacity' => 500,
    ]);

    // Create 5 NPCs
    LocationNpc::factory()->count(5)->create([
        'location_type' => 'village',
        'location_id' => $village->id,
        'weeks_without_food' => 0,
    ]);

    // Add only 2 units of food (not enough for 5 NPCs)
    $grainItem = Item::where('name', 'Grain')->first();
    $stockpile = LocationStockpile::getOrCreate('village', $village->id, $grainItem->id);
    $stockpile->addQuantity(2);

    $service = new FoodConsumptionService;
    $results = $service->processWeeklyConsumption();

    $stockpile->refresh();

    // All food consumed
    expect($stockpile->quantity)->toBe(0);
    expect($results['food_consumed'])->toBe(2);

    // All NPCs are starving (even though partial food was available)
    expect($results['npcs_starving'])->toBe(5);
});
