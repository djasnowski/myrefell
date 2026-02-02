<?php

use App\Models\Item;
use App\Models\LocationStockpile;
use App\Models\MarketPrice;
use App\Models\MarketTransaction;
use App\Models\PlayerInventory;
use App\Models\User;
use App\Models\Village;
use App\Models\WorldState;
use App\Services\MarketService;

beforeEach(function () {
    // Clear any existing data
    WorldState::query()->delete();
    MarketPrice::query()->delete();
    MarketTransaction::query()->delete();
    LocationStockpile::query()->delete();
    PlayerInventory::query()->delete();
    Village::query()->delete();
    User::query()->delete();
    Item::query()->delete();

    // Create world state
    WorldState::factory()->create([
        'current_year' => 1,
        'current_season' => 'summer',
        'current_week' => 5,
    ]);
});

test('market prices are created for items at location', function () {
    $village = Village::create([
        'name' => 'Test Village',
        'description' => 'A test village',
        'biome' => 'plains',
        'granary_capacity' => 500,
    ]);

    $item = Item::create([
        'name' => 'Iron Ore',
        'description' => 'Raw iron ore',
        'type' => 'resource',
        'stackable' => true,
        'max_stack' => 100,
        'base_value' => 10,
    ]);

    $marketPrice = MarketPrice::getOrCreate('village', $village->id, $item);

    expect($marketPrice)->toBeInstanceOf(MarketPrice::class);
    expect($marketPrice->base_price)->toBe(10);
    expect($marketPrice->item_id)->toBe($item->id);
    expect($marketPrice->location_type)->toBe('village');
    expect($marketPrice->location_id)->toBe($village->id);
});

test('seasonal modifiers affect prices', function () {
    $village = Village::create([
        'name' => 'Test Village',
        'description' => 'A test village',
        'biome' => 'plains',
        'granary_capacity' => 500,
    ]);

    $item = Item::create([
        'name' => 'Bread',
        'description' => 'A loaf of bread',
        'type' => 'consumable',
        'stackable' => true,
        'max_stack' => 50,
        'base_value' => 100, // Higher base value to avoid rounding issues
    ]);

    $service = app(MarketService::class);

    // Add item to stockpile so it appears in market prices
    $stockpile = LocationStockpile::getOrCreate('village', $village->id, $item->id);
    $stockpile->addQuantity(50);

    // Summer - food is cheaper (0.9 modifier)
    $prices = $service->getMarketPrices('village', $village->id);
    $breadPrice = $prices->firstWhere('item_id', $item->id);

    expect((float) $breadPrice['seasonal_modifier'])->toBe(0.9);
    expect($breadPrice['current_price'])->toBeLessThan($breadPrice['base_price']);

    // Change to winter - food is more expensive
    WorldState::query()->delete();
    WorldState::factory()->winter()->create();

    // Get new prices
    MarketPrice::query()->delete(); // Force recalculation
    $winterPrices = $service->getMarketPrices('village', $village->id);
    $winterBreadPrice = $winterPrices->firstWhere('item_id', $item->id);

    expect((float) $winterBreadPrice['seasonal_modifier'])->toBe(1.3);
    expect($winterBreadPrice['current_price'])->toBeGreaterThan($winterBreadPrice['base_price']);
});

test('supply affects prices', function () {
    $village = Village::create([
        'name' => 'Test Village',
        'description' => 'A test village',
        'biome' => 'plains',
        'granary_capacity' => 500,
    ]);

    $item = Item::create([
        'name' => 'Iron Ore',
        'description' => 'Raw iron ore',
        'type' => 'resource',
        'stackable' => true,
        'max_stack' => 100,
        'base_value' => 10,
    ]);

    $service = app(MarketService::class);

    // Add low supply to stockpile (price should be higher)
    $stockpile = LocationStockpile::getOrCreate('village', $village->id, $item->id);
    $stockpile->addQuantity(5); // Low supply

    $lowSupplyPrices = $service->getMarketPrices('village', $village->id);
    $lowPrice = $lowSupplyPrices->firstWhere('item_id', $item->id);

    expect($lowPrice['supply_modifier'])->toBeGreaterThanOrEqual(1.0);

    // Add high supply
    $stockpile->addQuantity(150);

    // Update price
    $marketPrice = MarketPrice::atLocation('village', $village->id)
        ->forItem($item->id)
        ->first();
    $service->updatePrice($marketPrice);

    expect($marketPrice->supply_modifier)->toBeLessThan(1.0);
});

test('player can buy items from market', function () {
    $village = Village::create([
        'name' => 'Test Village',
        'description' => 'A test village',
        'biome' => 'plains',
        'granary_capacity' => 500,
    ]);

    $item = Item::create([
        'name' => 'Iron Ore',
        'description' => 'Raw iron ore',
        'type' => 'resource',
        'stackable' => true,
        'max_stack' => 100,
        'base_value' => 10,
    ]);

    $user = User::factory()->create([
        'gold' => 100,
        'current_location_type' => 'village',
        'current_location_id' => $village->id,
    ]);

    // Add stock to the market
    $stockpile = LocationStockpile::getOrCreate('village', $village->id, $item->id);
    $stockpile->addQuantity(50);

    $service = app(MarketService::class);
    $result = $service->buyItem($user, $item->id, 5);

    expect($result['success'])->toBeTrue();

    $user->refresh();
    expect($user->gold)->toBeLessThan(100);

    // Check inventory
    expect($user->inventory()->where('item_id', $item->id)->exists())->toBeTrue();

    // Check stockpile decreased
    $stockpile->refresh();
    expect($stockpile->quantity)->toBe(45);

    // Check transaction recorded
    expect(MarketTransaction::where('user_id', $user->id)->exists())->toBeTrue();
});

test('player cannot buy without enough gold', function () {
    $village = Village::create([
        'name' => 'Test Village',
        'description' => 'A test village',
        'biome' => 'plains',
        'granary_capacity' => 500,
    ]);

    $item = Item::create([
        'name' => 'Iron Ore',
        'description' => 'Raw iron ore',
        'type' => 'resource',
        'stackable' => true,
        'max_stack' => 100,
        'base_value' => 1000,
    ]);

    $user = User::factory()->create([
        'gold' => 10,
        'current_location_type' => 'village',
        'current_location_id' => $village->id,
    ]);

    // Add stock
    $stockpile = LocationStockpile::getOrCreate('village', $village->id, $item->id);
    $stockpile->addQuantity(50);

    $service = app(MarketService::class);
    $result = $service->buyItem($user, $item->id, 1);

    expect($result['success'])->toBeFalse();
    expect($result['message'])->toBe("You don't have enough gold.");
});

test('player cannot buy without stock', function () {
    $village = Village::create([
        'name' => 'Test Village',
        'description' => 'A test village',
        'biome' => 'plains',
        'granary_capacity' => 500,
    ]);

    $item = Item::create([
        'name' => 'Iron Ore',
        'description' => 'Raw iron ore',
        'type' => 'resource',
        'stackable' => true,
        'max_stack' => 100,
        'base_value' => 10,
    ]);

    $user = User::factory()->create([
        'gold' => 1000,
        'current_location_type' => 'village',
        'current_location_id' => $village->id,
    ]);

    $service = app(MarketService::class);
    $result = $service->buyItem($user, $item->id, 5);

    expect($result['success'])->toBeFalse();
    expect($result['message'])->toBe('Not enough stock available.');
});

test('player can sell items to market', function () {
    $village = Village::create([
        'name' => 'Test Village',
        'description' => 'A test village',
        'biome' => 'plains',
        'granary_capacity' => 500,
    ]);

    $item = Item::create([
        'name' => 'Iron Ore',
        'description' => 'Raw iron ore',
        'type' => 'resource',
        'stackable' => true,
        'max_stack' => 100,
        'base_value' => 10,
    ]);

    $user = User::factory()->create([
        'gold' => 0,
        'current_location_type' => 'village',
        'current_location_id' => $village->id,
    ]);

    // Add item to player inventory
    PlayerInventory::create([
        'player_id' => $user->id,
        'item_id' => $item->id,
        'slot_number' => 0,
        'quantity' => 10,
    ]);

    $service = app(MarketService::class);
    $result = $service->sellItem($user, $item->id, 5);

    expect($result['success'])->toBeTrue();

    $user->refresh();
    expect($user->gold)->toBeGreaterThan(0);

    // Check inventory decreased
    $inventory = $user->inventory()->where('item_id', $item->id)->first();
    expect($inventory->quantity)->toBe(5);

    // Check stockpile increased
    $stockpile = LocationStockpile::atLocation('village', $village->id)
        ->forItem($item->id)
        ->first();
    expect($stockpile->quantity)->toBe(5);

    // Check transaction recorded
    $tx = MarketTransaction::where('user_id', $user->id)->first();
    expect($tx->type)->toBe('sell');
});

test('player cannot sell items they do not have', function () {
    $village = Village::create([
        'name' => 'Test Village',
        'description' => 'A test village',
        'biome' => 'plains',
        'granary_capacity' => 500,
    ]);

    $item = Item::create([
        'name' => 'Iron Ore',
        'description' => 'Raw iron ore',
        'type' => 'resource',
        'stackable' => true,
        'max_stack' => 100,
        'base_value' => 10,
    ]);

    $user = User::factory()->create([
        'gold' => 100,
        'current_location_type' => 'village',
        'current_location_id' => $village->id,
    ]);

    $service = app(MarketService::class);
    $result = $service->sellItem($user, $item->id, 5);

    expect($result['success'])->toBeFalse();
    expect($result['message'])->toBe("You don't have enough of this item (equipped items cannot be sold).");
});

test('buy price is higher than sell price', function () {
    $village = Village::create([
        'name' => 'Test Village',
        'description' => 'A test village',
        'biome' => 'plains',
        'granary_capacity' => 500,
    ]);

    $item = Item::create([
        'name' => 'Iron Ore',
        'description' => 'Raw iron ore',
        'type' => 'resource',
        'stackable' => true,
        'max_stack' => 100,
        'base_value' => 100,
    ]);

    $marketPrice = MarketPrice::getOrCreate('village', $village->id, $item);

    expect($marketPrice->buy_price)->toBeGreaterThan($marketPrice->sell_price);
});

test('market access denied while traveling', function () {
    $village = Village::create([
        'name' => 'Test Village',
        'description' => 'A test village',
        'biome' => 'plains',
        'granary_capacity' => 500,
    ]);

    $user = User::factory()->create([
        'current_location_type' => 'village',
        'current_location_id' => $village->id,
        'is_traveling' => true,
        'travel_destination_type' => 'village',
        'travel_destination_id' => 2,
        'travel_arrives_at' => now()->addHour(),
    ]);

    $service = app(MarketService::class);
    expect($service->canAccessMarket($user))->toBeFalse();
});

test('transactions are recorded correctly', function () {
    $village = Village::create([
        'name' => 'Test Village',
        'description' => 'A test village',
        'biome' => 'plains',
        'granary_capacity' => 500,
    ]);

    $item = Item::create([
        'name' => 'Iron Ore',
        'description' => 'Raw iron ore',
        'type' => 'resource',
        'stackable' => true,
        'max_stack' => 100,
        'base_value' => 10,
    ]);

    $user = User::factory()->create([
        'gold' => 1000,
        'current_location_type' => 'village',
        'current_location_id' => $village->id,
    ]);

    // Add stock
    $stockpile = LocationStockpile::getOrCreate('village', $village->id, $item->id);
    $stockpile->addQuantity(50);

    $service = app(MarketService::class);
    $service->buyItem($user, $item->id, 3);

    $transactions = $service->getRecentTransactions($user);

    expect($transactions)->toHaveCount(1);
    expect($transactions[0]['type'])->toBe('buy');
    expect($transactions[0]['quantity'])->toBe(3);
    expect($transactions[0]['item_name'])->toBe('Iron Ore');
});
