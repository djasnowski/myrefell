<?php

use App\Models\Item;
use App\Models\LocationNpc;
use App\Models\LocationStockpile;
use App\Models\Role;
use App\Models\Town;
use App\Models\User;
use App\Models\Village;
use App\Models\WorldState;
use App\Services\FoodConsumptionService;

beforeEach(function () {
    // Clear any existing data
    WorldState::query()->delete();
    LocationNpc::query()->delete();
    LocationStockpile::query()->delete();
    Role::query()->delete();
    Town::query()->delete();
    Village::query()->delete();
    User::query()->delete();
    Item::query()->delete();

    // Create the grain item with food_value for the new multi-food system
    Item::create([
        'name' => 'Grain',
        'description' => 'A sack of grain. The staple food of the realm.',
        'type' => 'resource',
        'subtype' => 'grain',
        'rarity' => 'common',
        'stackable' => true,
        'max_stack' => 1000,
        'base_value' => 2,
        'food_value' => 4, // Each unit feeds 4 people for a week
    ]);

    // Create a world state
    WorldState::factory()->create([
        'current_year' => 50,
        'current_season' => 'summer',
        'current_week' => 5,
    ]);
});

test('service can get town food stats', function () {
    $town = Town::create([
        'name' => 'Test Town',
        'description' => 'A test town',
        'biome' => 'plains',
        'granary_capacity' => 1000,
    ]);

    $service = new FoodConsumptionService;
    $stats = $service->getTownFoodStats($town);

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
    expect($stats['granary_capacity'])->toBe(1000);
});

test('food is consumed weekly from town stockpile', function () {
    $town = Town::create([
        'name' => 'Test Town',
        'description' => 'A test town',
        'biome' => 'plains',
        'granary_capacity' => 1000,
    ]);

    // Create 8 NPCs in the town (will need 2 units of grain at food_value=4)
    LocationNpc::factory()->count(8)->create([
        'location_type' => 'town',
        'location_id' => $town->id,
        'weeks_without_food' => 0,
    ]);

    // Add food to the stockpile
    $grainItem = Item::where('name', 'Grain')->first();
    $stockpile = LocationStockpile::getOrCreate('town', $town->id, $grainItem->id);
    $stockpile->addQuantity(100);

    $service = new FoodConsumptionService;
    $results = $service->processWeeklyConsumption();

    // Refresh stockpile
    $stockpile->refresh();

    // With 8 NPCs and food_value=4, need ceil(8/4)=2 units of grain
    expect($stockpile->quantity)->toBe(98);
    expect($results['food_consumed'])->toBe(2);
    expect($results['towns_processed'])->toBe(1);
});

test('npcs in town become starving when food runs out', function () {
    $town = Town::create([
        'name' => 'Test Town',
        'description' => 'A test town',
        'biome' => 'plains',
        'granary_capacity' => 1000,
    ]);

    // Create NPCs but no food in stockpile
    $npcs = LocationNpc::factory()->count(3)->create([
        'location_type' => 'town',
        'location_id' => $town->id,
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

test('npcs in town die after maximum weeks without food', function () {
    $town = Town::create([
        'name' => 'Test Town',
        'description' => 'A test town',
        'biome' => 'plains',
        'granary_capacity' => 1000,
    ]);

    // Create an NPC at the brink of starvation death
    $npc = LocationNpc::factory()->create([
        'location_type' => 'town',
        'location_id' => $town->id,
        'weeks_without_food' => FoodConsumptionService::MAX_WEEKS_WITHOUT_FOOD - 1,
        'birth_year' => 20,
    ]);

    $service = new FoodConsumptionService;
    $results = $service->processWeeklyConsumption();

    // NPC should have died from starvation
    expect($results['npcs_died'])->toBe(1);

    $npc->refresh();
    expect($npc->isDead())->toBeTrue();
    expect($npc->death_year)->toBe(50);
});

test('service can add food to town stockpile', function () {
    $town = Town::create([
        'name' => 'Test Town',
        'description' => 'A test town',
        'biome' => 'plains',
        'granary_capacity' => 500,
    ]);

    $service = new FoodConsumptionService;
    $result = $service->addFoodToTown($town, 50);

    expect($result)->toBe(50);

    $grainItem = Item::where('name', 'Grain')->first();
    $stockpile = LocationStockpile::atLocation('town', $town->id)
        ->forItem($grainItem->id)
        ->first();

    expect($stockpile->quantity)->toBe(50);
});

test('town food addition respects granary capacity', function () {
    $town = Town::create([
        'name' => 'Test Town',
        'description' => 'A test town',
        'biome' => 'plains',
        'granary_capacity' => 100,
    ]);

    $service = new FoodConsumptionService;
    $added = $service->addFoodToTown($town, 150); // Try to add more than capacity

    expect($added)->toBe(100); // Should return actual amount added

    $grainItem = Item::where('name', 'Grain')->first();
    $stockpile = LocationStockpile::atLocation('town', $town->id)
        ->forItem($grainItem->id)
        ->first();

    // Should be capped at capacity
    expect($stockpile->quantity)->toBe(100);
});

test('addFoodToLocation works for both villages and towns', function () {
    $village = Village::create([
        'name' => 'Test Village',
        'description' => 'A test village',
        'biome' => 'plains',
        'granary_capacity' => 500,
    ]);

    $town = Town::create([
        'name' => 'Test Town',
        'description' => 'A test town',
        'biome' => 'plains',
        'granary_capacity' => 1000,
    ]);

    $service = new FoodConsumptionService;

    $villageAdded = $service->addFoodToLocation('village', $village->id, 50);
    $townAdded = $service->addFoodToLocation('town', $town->id, 75);

    expect($villageAdded)->toBe(50);
    expect($townAdded)->toBe(75);

    $grainItem = Item::where('name', 'Grain')->first();

    $villageStockpile = LocationStockpile::atLocation('village', $village->id)
        ->forItem($grainItem->id)
        ->first();
    $townStockpile = LocationStockpile::atLocation('town', $town->id)
        ->forItem($grainItem->id)
        ->first();

    expect($villageStockpile->quantity)->toBe(50);
    expect($townStockpile->quantity)->toBe(75);
});

test('starvation counters reset in town when food is available', function () {
    $town = Town::create([
        'name' => 'Test Town',
        'description' => 'A test town',
        'biome' => 'plains',
        'granary_capacity' => 500,
    ]);

    // Create starving NPCs
    $npcs = LocationNpc::factory()->count(2)->create([
        'location_type' => 'town',
        'location_id' => $town->id,
        'weeks_without_food' => 2,
    ]);

    // Add enough food
    $grainItem = Item::where('name', 'Grain')->first();
    $stockpile = LocationStockpile::getOrCreate('town', $town->id, $grainItem->id);
    $stockpile->addQuantity(100);

    $service = new FoodConsumptionService;
    $service->processWeeklyConsumption();

    // Starvation counters should be reset
    foreach ($npcs as $npc) {
        $npc->refresh();
        expect($npc->weeks_without_food)->toBe(0);
    }
});

test('processWeeklyConsumption processes both villages and towns', function () {
    $village = Village::create([
        'name' => 'Test Village',
        'description' => 'A test village',
        'biome' => 'plains',
        'granary_capacity' => 500,
    ]);

    $town = Town::create([
        'name' => 'Test Town',
        'description' => 'A test town',
        'biome' => 'plains',
        'granary_capacity' => 1000,
    ]);

    // Create NPCs in both - 4 per location to match food_value for easy calculation
    LocationNpc::factory()->count(4)->create([
        'location_type' => 'village',
        'location_id' => $village->id,
        'weeks_without_food' => 0,
    ]);

    LocationNpc::factory()->count(4)->create([
        'location_type' => 'town',
        'location_id' => $town->id,
        'weeks_without_food' => 0,
    ]);

    // Add food to both
    $grainItem = Item::where('name', 'Grain')->first();
    LocationStockpile::getOrCreate('village', $village->id, $grainItem->id)->addQuantity(100);
    LocationStockpile::getOrCreate('town', $town->id, $grainItem->id)->addQuantity(100);

    $service = new FoodConsumptionService;
    $results = $service->processWeeklyConsumption();

    expect($results['villages_processed'])->toBe(1);
    expect($results['towns_processed'])->toBe(1);
    // With food_value=4, each location with 4 NPCs needs 1 unit of grain, total 2
    expect($results['food_consumed'])->toBe(2);
});
