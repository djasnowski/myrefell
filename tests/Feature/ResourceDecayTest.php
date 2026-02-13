<?php

use App\Models\Item;
use App\Models\LocationStockpile;
use App\Models\PlayerInventory;
use App\Models\User;
use App\Models\Village;
use App\Models\WorldState;
use App\Services\CalendarService;
use App\Services\ResourceDecayService;

beforeEach(function () {
    // Clear any existing data
    WorldState::query()->delete();
    LocationStockpile::query()->delete();
    PlayerInventory::query()->delete();
    Village::query()->delete();
    User::query()->delete();
    Item::query()->delete();

    // Create spoiled food item first (needed for decays_into references)
    Item::create([
        'name' => 'Spoiled Food',
        'description' => 'Rotten food that is no longer edible.',
        'type' => 'misc',
        'subtype' => 'waste',
        'rarity' => 'common',
        'stackable' => true,
        'max_stack' => 100,
        'base_value' => 0,
    ]);

    // Create perishable items
    Item::create([
        'name' => 'Raw Trout',
        'description' => 'A freshly caught trout. Will spoil if not cooked.',
        'type' => 'resource',
        'subtype' => 'fish',
        'rarity' => 'common',
        'stackable' => true,
        'max_stack' => 100,
        'base_value' => 8,
        'is_perishable' => true,
        'spoil_after_weeks' => 2,
        'decays_into' => 'Spoiled Food',
    ]);

    Item::create([
        'name' => 'Grain',
        'description' => 'A sack of grain. Slowly decays over time.',
        'type' => 'resource',
        'subtype' => 'grain',
        'rarity' => 'common',
        'stackable' => true,
        'max_stack' => 1000,
        'base_value' => 2,
        'is_perishable' => true,
        'decay_rate_per_week' => 1,
    ]);

    Item::create([
        'name' => 'Iron Ore',
        'description' => 'A chunk of iron ore.',
        'type' => 'resource',
        'subtype' => 'ore',
        'rarity' => 'common',
        'stackable' => true,
        'max_stack' => 100,
        'base_value' => 15,
        'is_perishable' => false,
    ]);

    // Create a world state
    WorldState::factory()->create([
        'current_year' => 50,
        'current_season' => 'spring',
        'current_week' => 5,
    ]);
});

test('service can process weekly decay', function () {
    $service = new ResourceDecayService;
    $results = $service->processWeeklyDecay();

    expect($results)->toHaveKeys([
        'stockpiles_processed',
        'inventory_processed',
        'items_decayed',
        'items_spoiled',
        'items_destroyed',
    ]);
});

test('perishable items decay in stockpiles', function () {
    $village = Village::create([
        'name' => 'Test Village',
        'description' => 'A test village',
        'biome' => 'plains',
    ]);

    $grainItem = Item::where('name', 'Grain')->first();
    $stockpile = LocationStockpile::getOrCreate('village', $village->id, $grainItem->id);
    $stockpile->addQuantity(100);

    $service = new ResourceDecayService;
    $results = $service->processWeeklyDecay();

    $stockpile->refresh();

    // Should have lost 1 unit (decay_rate_per_week = 1)
    expect($stockpile->quantity)->toBe(99);
    expect($results['items_decayed'])->toBe(1);
});

test('non-perishable items do not decay', function () {
    $village = Village::create([
        'name' => 'Test Village',
        'description' => 'A test village',
        'biome' => 'plains',
    ]);

    $ironOre = Item::where('name', 'Iron Ore')->first();
    $stockpile = LocationStockpile::getOrCreate('village', $village->id, $ironOre->id);
    $stockpile->addQuantity(100);

    $service = new ResourceDecayService;
    $service->processWeeklyDecay();

    $stockpile->refresh();

    // No decay for non-perishable items
    expect($stockpile->quantity)->toBe(100);
});

test('items spoil after specified weeks', function () {
    $village = Village::create([
        'name' => 'Test Village',
        'description' => 'A test village',
        'biome' => 'plains',
    ]);

    $rawTrout = Item::where('name', 'Raw Trout')->first();
    $stockpile = LocationStockpile::getOrCreate('village', $village->id, $rawTrout->id);
    $stockpile->addQuantity(10);
    $stockpile->weeks_stored = 1; // One week away from spoiling
    $stockpile->save();

    $service = new ResourceDecayService;
    $results = $service->processWeeklyDecay();

    // Raw trout should have spoiled and been converted to spoiled food
    expect($results['items_spoiled'])->toBe(10);

    // Original stockpile should be deleted
    $stockpile = LocationStockpile::atLocation('village', $village->id)
        ->forItem($rawTrout->id)
        ->first();
    expect($stockpile)->toBeNull();

    // Spoiled food should exist
    $spoiledFood = Item::where('name', 'Spoiled Food')->first();
    $spoiledStockpile = LocationStockpile::atLocation('village', $village->id)
        ->forItem($spoiledFood->id)
        ->first();
    expect($spoiledStockpile)->not->toBeNull();
    expect($spoiledStockpile->quantity)->toBe(10);
});

test('summer increases decay rate', function () {
    WorldState::query()->delete();
    WorldState::factory()->create([
        'current_year' => 50,
        'current_season' => 'summer',
        'current_week' => 5,
    ]);

    $village = Village::create([
        'name' => 'Test Village',
        'description' => 'A test village',
        'biome' => 'plains',
    ]);

    // Create an item with decay rate of 2
    Item::create([
        'name' => 'Test Perishable',
        'description' => 'Test item',
        'type' => 'resource',
        'subtype' => 'test',
        'rarity' => 'common',
        'stackable' => true,
        'max_stack' => 100,
        'base_value' => 1,
        'is_perishable' => true,
        'decay_rate_per_week' => 2,
    ]);

    $testItem = Item::where('name', 'Test Perishable')->first();
    $stockpile = LocationStockpile::getOrCreate('village', $village->id, $testItem->id);
    $stockpile->addQuantity(100);

    $service = new ResourceDecayService;
    $results = $service->processWeeklyDecay();

    $stockpile->refresh();

    // Summer modifier is 1.5, so decay is 2 * 1.5 = 3
    expect($stockpile->quantity)->toBe(97);
    expect($results['items_decayed'])->toBe(3);
});

test('winter decreases decay rate', function () {
    WorldState::query()->delete();
    WorldState::factory()->create([
        'current_year' => 50,
        'current_season' => 'winter',
        'current_week' => 5,
    ]);

    $village = Village::create([
        'name' => 'Test Village',
        'description' => 'A test village',
        'biome' => 'plains',
    ]);

    // Create an item with decay rate of 2
    Item::create([
        'name' => 'Test Perishable Winter',
        'description' => 'Test item',
        'type' => 'resource',
        'subtype' => 'test',
        'rarity' => 'common',
        'stackable' => true,
        'max_stack' => 100,
        'base_value' => 1,
        'is_perishable' => true,
        'decay_rate_per_week' => 2,
    ]);

    $testItem = Item::where('name', 'Test Perishable Winter')->first();
    $stockpile = LocationStockpile::getOrCreate('village', $village->id, $testItem->id);
    $stockpile->addQuantity(100);

    $service = new ResourceDecayService;
    $results = $service->processWeeklyDecay();

    $stockpile->refresh();

    // Winter modifier is 0.5, so decay is 2 * 0.5 = 1
    expect($stockpile->quantity)->toBe(99);
    expect($results['items_decayed'])->toBe(1);
});

test('player inventory items also decay', function () {
    $user = User::factory()->create();

    $grainItem = Item::where('name', 'Grain')->first();
    $slot = PlayerInventory::create([
        'player_id' => $user->id,
        'item_id' => $grainItem->id,
        'slot_number' => 1,
        'quantity' => 50,
        'is_equipped' => false,
        'weeks_stored' => 0,
    ]);

    $service = new ResourceDecayService;
    $results = $service->processWeeklyDecay();

    $slot->refresh();

    // Should have lost 1 unit
    expect($slot->quantity)->toBe(49);
    expect($results['inventory_processed'])->toBe(1);
});

test('player inventory items spoil after specified weeks', function () {
    $user = User::factory()->create();

    $rawTrout = Item::where('name', 'Raw Trout')->first();
    $slot = PlayerInventory::create([
        'player_id' => $user->id,
        'item_id' => $rawTrout->id,
        'slot_number' => 1,
        'quantity' => 5,
        'is_equipped' => false,
        'weeks_stored' => 1, // One week away from spoiling
    ]);

    $service = new ResourceDecayService;
    $results = $service->processWeeklyDecay();

    // Slot should now contain spoiled food
    $slot->refresh();
    $spoiledFood = Item::where('name', 'Spoiled Food')->first();
    expect($slot->item_id)->toBe($spoiledFood->id);
    expect($slot->quantity)->toBe(5);
    expect($slot->weeks_stored)->toBe(0); // Reset after transformation
});

test('items are destroyed when quantity reaches zero', function () {
    $village = Village::create([
        'name' => 'Test Village',
        'description' => 'A test village',
        'biome' => 'plains',
    ]);

    $grainItem = Item::where('name', 'Grain')->first();
    $stockpile = LocationStockpile::getOrCreate('village', $village->id, $grainItem->id);
    $stockpile->addQuantity(1); // Only 1 unit, will be destroyed by decay

    $service = new ResourceDecayService;
    $results = $service->processWeeklyDecay();

    // Stockpile should be deleted
    $stockpile = LocationStockpile::atLocation('village', $village->id)
        ->forItem($grainItem->id)
        ->first();
    expect($stockpile)->toBeNull();
    expect($results['items_destroyed'])->toBe(1);
});

test('weeks stored counter increments', function () {
    $village = Village::create([
        'name' => 'Test Village',
        'description' => 'A test village',
        'biome' => 'plains',
    ]);

    $grainItem = Item::where('name', 'Grain')->first();
    $stockpile = LocationStockpile::getOrCreate('village', $village->id, $grainItem->id);
    $stockpile->addQuantity(100);
    expect($stockpile->weeks_stored)->toBe(0);

    $service = new ResourceDecayService;
    $service->processWeeklyDecay();

    $stockpile->refresh();
    expect($stockpile->weeks_stored)->toBe(1);

    $service->processWeeklyDecay();
    $stockpile->refresh();
    expect($stockpile->weeks_stored)->toBe(2);
});

test('service can get location decay stats', function () {
    $village = Village::create([
        'name' => 'Test Village',
        'description' => 'A test village',
        'biome' => 'plains',
    ]);

    $rawTrout = Item::where('name', 'Raw Trout')->first();
    $stockpile = LocationStockpile::getOrCreate('village', $village->id, $rawTrout->id);
    $stockpile->addQuantity(10);
    $stockpile->weeks_stored = 1;
    $stockpile->save();

    $service = new ResourceDecayService;
    $stats = $service->getLocationDecayStats('village', $village->id);

    expect($stats)->toHaveCount(1);
    expect($stats[0]['item_name'])->toBe('Raw Trout');
    expect($stats[0]['quantity'])->toBe(10);
    expect($stats[0]['weeks_stored'])->toBe(1);
    expect($stats[0]['spoil_after_weeks'])->toBe(2);
    expect($stats[0]['weeks_until_spoil'])->toBe(1);
    expect($stats[0]['decays_into'])->toBe('Spoiled Food');
});

test('service can get player decay stats', function () {
    $user = User::factory()->create();

    $rawTrout = Item::where('name', 'Raw Trout')->first();
    PlayerInventory::create([
        'player_id' => $user->id,
        'item_id' => $rawTrout->id,
        'slot_number' => 1,
        'quantity' => 5,
        'is_equipped' => false,
        'weeks_stored' => 1,
    ]);

    $service = new ResourceDecayService;
    $stats = $service->getPlayerDecayStats($user->id);

    expect($stats)->toHaveCount(1);
    expect($stats[0]['item_name'])->toBe('Raw Trout');
    expect($stats[0]['quantity'])->toBe(5);
    expect($stats[0]['weeks_stored'])->toBe(1);
    expect($stats[0]['weeks_until_spoil'])->toBe(1);
});

test('calendar service dispatches resource decay job on week advance', function () {
    \Illuminate\Support\Facades\Queue::fake();

    // Set to last day of the week so advanceDay triggers week rollover
    $state = \App\Models\WorldState::current();
    $state->update(['current_day' => \App\Models\WorldState::DAYS_PER_WEEK]);

    $service = new CalendarService;
    $service->advanceDay();

    \Illuminate\Support\Facades\Queue::assertPushed(\App\Jobs\ProcessResourceDecay::class);
});

test('item model has perishable methods', function () {
    $rawTrout = Item::where('name', 'Raw Trout')->first();
    $ironOre = Item::where('name', 'Iron Ore')->first();
    $grain = Item::where('name', 'Grain')->first();

    expect($rawTrout->isPerishable())->toBeTrue();
    expect($rawTrout->spoilsAfterTime())->toBeTrue();
    expect($rawTrout->decaysOverTime())->toBeFalse(); // Has spoil_after but not decay_rate

    expect($grain->isPerishable())->toBeTrue();
    expect($grain->spoilsAfterTime())->toBeFalse(); // No spoil_after
    expect($grain->decaysOverTime())->toBeTrue(); // Has decay_rate

    expect($ironOre->isPerishable())->toBeFalse();
    expect($ironOre->spoilsAfterTime())->toBeFalse();
    expect($ironOre->decaysOverTime())->toBeFalse();
});

test('item can get spoiled item reference', function () {
    $rawTrout = Item::where('name', 'Raw Trout')->first();
    $spoiledItem = $rawTrout->getSpoiledItem();

    expect($spoiledItem)->not->toBeNull();
    expect($spoiledItem->name)->toBe('Spoiled Food');
});

test('stockpile can check if item has spoiled', function () {
    $village = Village::create([
        'name' => 'Test Village',
        'description' => 'A test village',
        'biome' => 'plains',
    ]);

    $rawTrout = Item::where('name', 'Raw Trout')->first();
    $stockpile = LocationStockpile::getOrCreate('village', $village->id, $rawTrout->id);
    $stockpile->addQuantity(10);

    expect($stockpile->hasSpoiled())->toBeFalse();

    $stockpile->weeks_stored = 2;
    $stockpile->save();

    expect($stockpile->hasSpoiled())->toBeTrue();
});
