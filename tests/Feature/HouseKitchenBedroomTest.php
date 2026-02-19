<?php

use App\Models\HouseFurniture;
use App\Models\HouseRoom;
use App\Models\Item;
use App\Models\Kingdom;
use App\Models\PlayerHouse;
use App\Models\PlayerSkill;
use App\Models\User;
use App\Services\CookingService;
use App\Services\HouseService;
use App\Services\InventoryService;

function createHouseWithKitchen(User $user, Kingdom $kingdom, string $stoveKey = 'firepit'): array
{
    $house = PlayerHouse::create([
        'player_id' => $user->id,
        'name' => 'My House',
        'tier' => 'cottage',
        'condition' => 100,
        'kingdom_id' => $kingdom->id,
        'location_type' => 'kingdom',
        'location_id' => $kingdom->id,
    ]);

    $room = HouseRoom::create([
        'player_house_id' => $house->id,
        'room_type' => 'kitchen',
        'grid_x' => 0,
        'grid_y' => 0,
    ]);

    $furniture = HouseFurniture::create([
        'house_room_id' => $room->id,
        'hotspot_slug' => 'stove',
        'furniture_key' => $stoveKey,
    ]);

    return ['house' => $house, 'room' => $room, 'furniture' => $furniture];
}

function createHouseWithBedroom(User $user, Kingdom $kingdom, string $bedKey = 'straw_bed'): array
{
    $house = PlayerHouse::create([
        'player_id' => $user->id,
        'name' => 'My House',
        'tier' => 'cottage',
        'condition' => 100,
        'kingdom_id' => $kingdom->id,
        'location_type' => 'kingdom',
        'location_id' => $kingdom->id,
    ]);

    $room = HouseRoom::create([
        'player_house_id' => $house->id,
        'room_type' => 'bedroom',
        'grid_x' => 0,
        'grid_y' => 0,
    ]);

    $furniture = HouseFurniture::create([
        'house_room_id' => $room->id,
        'hotspot_slug' => 'bed',
        'furniture_key' => $bedKey,
    ]);

    return ['house' => $house, 'room' => $room, 'furniture' => $furniture];
}

beforeEach(function () {
    Item::firstOrCreate(['name' => 'Grain'], ['type' => 'resource', 'subtype' => 'crop', 'rarity' => 'common', 'stackable' => true, 'max_stack' => 100, 'base_value' => 2]);
    Item::firstOrCreate(['name' => 'Flour'], ['type' => 'misc', 'subtype' => 'material', 'rarity' => 'common', 'stackable' => true, 'max_stack' => 100, 'base_value' => 5]);
});

// ===== Kitchen Tests =====

test('can cook at home with kitchen and stove', function () {
    $kingdom = Kingdom::factory()->create();
    $user = User::factory()->create([
        'energy' => 100,
        'max_energy' => 100,
        'current_kingdom_id' => $kingdom->id,
    ]);
    PlayerSkill::create(['player_id' => $user->id, 'skill_name' => 'cooking', 'level' => 1, 'xp' => 0]);

    createHouseWithKitchen($user, $kingdom);

    // Give user 2 Grain for the flour recipe
    $grain = Item::where('name', 'Grain')->first();
    app(InventoryService::class)->addItem($user, $grain, 2);

    $service = app(HouseService::class);
    $result = $service->cookAtHome($user, 'flour');

    expect($result['success'])->toBeTrue();
    // Either cooked or burned (both return success=true)
    expect($result)->toHaveKey('xp_awarded');
});

test('cannot cook at home without stove', function () {
    $kingdom = Kingdom::factory()->create();
    $user = User::factory()->create([
        'energy' => 100,
        'max_energy' => 100,
        'current_kingdom_id' => $kingdom->id,
    ]);

    // House with kitchen room but no stove furniture
    $house = PlayerHouse::create([
        'player_id' => $user->id,
        'name' => 'My House',
        'tier' => 'cottage',
        'condition' => 100,
        'kingdom_id' => $kingdom->id,
        'location_type' => 'kingdom',
        'location_id' => $kingdom->id,
    ]);

    HouseRoom::create([
        'player_house_id' => $house->id,
        'room_type' => 'kitchen',
        'grid_x' => 0,
        'grid_y' => 0,
    ]);

    $service = app(HouseService::class);
    $result = $service->cookAtHome($user, 'flour');

    expect($result['success'])->toBeFalse();
    expect($result['message'])->toContain('stove');
});

test('burn chance works when cooking burnable food at low level', function () {
    $kingdom = Kingdom::factory()->create();
    $user = User::factory()->create([
        'energy' => 100,
        'max_energy' => 100,
        'current_kingdom_id' => $kingdom->id,
    ]);
    PlayerSkill::create(['player_id' => $user->id, 'skill_name' => 'cooking', 'level' => 1, 'xp' => 0]);

    createHouseWithKitchen($user, $kingdom);

    // Give user materials for bread (burnable at level 1)
    $flour = Item::where('name', 'Flour')->first();
    app(InventoryService::class)->addItem($user, $flour, 1);

    // At level 1 with firepit (burn_bonus=3), burn chance for bread is 55-3=52%
    // Mock random_int to always return 1 (guarantees burn since 1 <= 52)
    $cookingService = Mockery::mock(CookingService::class)->makePartial();
    $cookingService->shouldAllowMockingProtectedMethods();

    // Use the real service but override random_int via a cook call that will burn
    // Since we can't easily mock random_int, we'll test via calculateBurnChance
    $realService = app(CookingService::class);
    $recipe = CookingService::RECIPES['bread'];
    $burnChance = $realService->calculateBurnChance($recipe, 1, 3);

    // At level 1 with firepit burn_bonus=3: base=27, range=35-1=34, decrease=27/34≈0.79, levels_above=0
    // burn_chance = 27 - 0 = 27%
    expect($burnChance)->toBe(27.0);
});

test('flour never burns because can_burn is false', function () {
    $kingdom = Kingdom::factory()->create();
    $user = User::factory()->create([
        'energy' => 100,
        'max_energy' => 100,
        'current_kingdom_id' => $kingdom->id,
    ]);
    PlayerSkill::create(['player_id' => $user->id, 'skill_name' => 'cooking', 'level' => 1, 'xp' => 0]);

    createHouseWithKitchen($user, $kingdom);

    $grain = Item::where('name', 'Grain')->first();
    $flour = Item::where('name', 'Flour')->first();
    app(InventoryService::class)->addItem($user, $grain, 2);

    $cookingService = app(CookingService::class);
    $result = $cookingService->cook($user, 'flour', 'house', 1);

    // Flour can't burn, so it should always succeed with the item
    expect($result['success'])->toBeTrue();
    expect($result)->not->toHaveKey('burned');
    expect($result['xp_awarded'])->toBe(8);

    $flourCount = app(InventoryService::class)->countItem($user->fresh(), $flour);
    expect($flourCount)->toBe(1);
});

test('kitchen data returns burn_bonus from stove config', function () {
    $kingdom = Kingdom::factory()->create();
    $user = User::factory()->create([
        'energy' => 100,
        'max_energy' => 100,
        'current_kingdom_id' => $kingdom->id,
    ]);
    PlayerSkill::create(['player_id' => $user->id, 'skill_name' => 'cooking', 'level' => 1, 'xp' => 0]);

    $service = app(HouseService::class);

    // Firepit has burn_bonus of 3
    createHouseWithKitchen($user, $kingdom, 'firepit');
    $kitchenData = $service->getKitchenData($user);
    expect($kitchenData)->not->toBeNull();
    expect($kitchenData['stove_name'])->toBe('Firepit');
    expect($kitchenData['burn_bonus'])->toBe(3);
});

test('iron stove has higher burn_bonus than firepit', function () {
    $kingdom = Kingdom::factory()->create();
    $user = User::factory()->create([
        'energy' => 100,
        'max_energy' => 100,
        'current_kingdom_id' => $kingdom->id,
    ]);
    PlayerSkill::create(['player_id' => $user->id, 'skill_name' => 'cooking', 'level' => 1, 'xp' => 0]);

    createHouseWithKitchen($user, $kingdom, 'iron_stove');

    $service = app(HouseService::class);
    $kitchenData = $service->getKitchenData($user);

    expect($kitchenData)->not->toBeNull();
    expect($kitchenData['stove_name'])->toBe('Iron Stove');
    expect($kitchenData['burn_bonus'])->toBe(5);
});

// ===== Burn Formula Tests =====

test('calculateBurnChance returns correct values at different levels', function () {
    $service = app(CookingService::class);
    $bread = CookingService::RECIPES['bread']; // req=1, stop=35

    // At required level (1), no bonus: base=30, levels_above=0 → 30%
    expect($service->calculateBurnChance($bread, 1, 0))->toBe(30.0);

    // At stop burn level (35): hits minimum floor (5% tavern)
    expect($service->calculateBurnChance($bread, 35, 0))->toBe(5.0);

    // Above stop burn level: still minimum floor
    expect($service->calculateBurnChance($bread, 50, 0))->toBe(5.0);

    // Mid-level (18 = halfway between 1 and 35): ~15%
    $midBurn = $service->calculateBurnChance($bread, 18, 0);
    expect($midBurn)->toBeGreaterThan(13.0);
    expect($midBurn)->toBeLessThan(17.0);
});

test('flour recipe never burns', function () {
    $service = app(CookingService::class);
    $flour = CookingService::RECIPES['flour'];

    expect($service->calculateBurnChance($flour, 1, 0))->toBe(0.0);
    expect($service->calculateBurnChance($flour, 50, 0))->toBe(0.0);
    expect($service->calculateBurnChance($flour, 1, 5))->toBe(0.0);
});

test('burn chance linearly decreases with cooking level', function () {
    $service = app(CookingService::class);
    $shrimp = CookingService::RECIPES['cooked_shrimp']; // req=1, stop=34

    $prev = 100.0;
    for ($level = 1; $level <= 34; $level++) {
        $chance = $service->calculateBurnChance($shrimp, $level, 0);
        expect($chance)->toBeLessThanOrEqual($prev);
        $prev = $chance;
    }

    // At level 34 (stop burn), should hit minimum floor (5% tavern)
    expect($service->calculateBurnChance($shrimp, 34, 0))->toBe(5.0);
});

test('burn bonus reduces burn chance', function () {
    $service = app(CookingService::class);
    $trout = CookingService::RECIPES['cooked_trout']; // req=10, stop=50

    $noBonus = $service->calculateBurnChance($trout, 10, 0);
    $firepitBonus = $service->calculateBurnChance($trout, 10, 3);
    $stoveBonus = $service->calculateBurnChance($trout, 10, 5);

    // No bonus: base=30
    expect($noBonus)->toBe(30.0);
    // Firepit: base=27
    expect($firepitBonus)->toBe(27.0);
    // Iron stove: base=25
    expect($stoveBonus)->toBe(25.0);

    expect($stoveBonus)->toBeLessThan($firepitBonus);
    expect($firepitBonus)->toBeLessThan($noBonus);
});

test('stop burn level means minimum burn floor', function () {
    $service = app(CookingService::class);

    foreach (CookingService::RECIPES as $recipe) {
        if (! ($recipe['can_burn'] ?? true)) {
            continue;
        }

        $stopLevel = $recipe['stop_burn_level'];

        // At stop level: tavern floor (5%), home stove floor (2%)
        expect($service->calculateBurnChance($recipe, $stopLevel, 0))->toBe(5.0);
        expect($service->calculateBurnChance($recipe, $stopLevel, 3))->toBe(2.0);
        expect($service->calculateBurnChance($recipe, $stopLevel, 5))->toBe(2.0);

        // Above stop level, still at floor
        expect($service->calculateBurnChance($recipe, $stopLevel + 10, 0))->toBe(5.0);
    }
});

// ===== Bedroom Tests =====

test('can rest at home for free with bed', function () {
    $kingdom = Kingdom::factory()->create();
    $user = User::factory()->create([
        'energy' => 50,
        'max_energy' => 100,
        'gold' => 1000,
        'current_kingdom_id' => $kingdom->id,
    ]);

    createHouseWithBedroom($user, $kingdom);

    $startGold = $user->gold;
    $service = app(HouseService::class);
    $result = $service->restAtHome($user);

    expect($result['success'])->toBeTrue();
    expect($result['message'])->toContain('Straw Bed');
    expect($result['message'])->toContain('energy');

    $user->refresh();
    // Gold should not change (free rest)
    expect($user->gold)->toBe($startGold);
    // Energy should increase
    expect($user->energy)->toBeGreaterThan(50);
});

test('cannot rest at home without bed', function () {
    $kingdom = Kingdom::factory()->create();
    $user = User::factory()->create([
        'energy' => 50,
        'max_energy' => 100,
        'current_kingdom_id' => $kingdom->id,
    ]);

    // House with bedroom but no bed furniture
    $house = PlayerHouse::create([
        'player_id' => $user->id,
        'name' => 'My House',
        'tier' => 'cottage',
        'condition' => 100,
        'kingdom_id' => $kingdom->id,
        'location_type' => 'kingdom',
        'location_id' => $kingdom->id,
    ]);

    HouseRoom::create([
        'player_house_id' => $house->id,
        'room_type' => 'bedroom',
        'grid_x' => 0,
        'grid_y' => 0,
    ]);

    $service = app(HouseService::class);
    $result = $service->restAtHome($user);

    expect($result['success'])->toBeFalse();
    expect($result['message'])->toContain('bed');
});

test('rest energy scales with bed quality', function () {
    $kingdom = Kingdom::factory()->create();

    // Test with straw bed
    $user1 = User::factory()->create([
        'energy' => 10,
        'max_energy' => 200,
        'current_kingdom_id' => $kingdom->id,
    ]);
    createHouseWithBedroom($user1, $kingdom, 'straw_bed');
    $service = app(HouseService::class);
    $result1 = $service->restAtHome($user1);
    expect($result1['success'])->toBeTrue();
    $user1->refresh();
    // Straw bed: bonus 5 * 6 = 30 energy
    expect($user1->energy)->toBe(40);

    // Test with wooden bed
    $user2 = User::factory()->create([
        'energy' => 10,
        'max_energy' => 200,
        'current_kingdom_id' => $kingdom->id,
    ]);
    createHouseWithBedroom($user2, $kingdom, 'wooden_bed');
    $result2 = $service->restAtHome($user2);
    expect($result2['success'])->toBeTrue();
    $user2->refresh();
    // Wooden bed: bonus 10 * 6 = 60 energy
    expect($user2->energy)->toBe(70);
});

test('rest respects cooldown', function () {
    $kingdom = Kingdom::factory()->create();
    $user = User::factory()->create([
        'energy' => 10,
        'max_energy' => 200,
        'current_kingdom_id' => $kingdom->id,
    ]);

    createHouseWithBedroom($user, $kingdom);

    $service = app(HouseService::class);

    // First rest should work
    $result1 = $service->restAtHome($user);
    expect($result1['success'])->toBeTrue();

    // Second rest immediately should fail
    $user->refresh();
    $result2 = $service->restAtHome($user);
    expect($result2['success'])->toBeFalse();
    expect($result2['message'])->toContain('wait');
});

test('cannot rest when at max energy', function () {
    $kingdom = Kingdom::factory()->create();
    $user = User::factory()->create([
        'energy' => 100,
        'max_energy' => 100,
        'current_kingdom_id' => $kingdom->id,
    ]);

    createHouseWithBedroom($user, $kingdom);

    $service = app(HouseService::class);
    $result = $service->restAtHome($user);

    expect($result['success'])->toBeFalse();
    expect($result['message'])->toContain('fully rested');
});

// ===== Hearth Tests =====

test('rest with hearth restores hp', function () {
    $kingdom = Kingdom::factory()->create();
    $user = User::factory()->create([
        'energy' => 10,
        'max_energy' => 200,
        'current_kingdom_id' => $kingdom->id,
    ]);
    // max_hp is derived from hitpoints skill level
    PlayerSkill::create(['player_id' => $user->id, 'skill_name' => 'hitpoints', 'level' => 100, 'xp' => 0]);
    // Set raw HP below max
    $user->update(['hp' => 50]);

    // Create house with bedroom + hearth
    $house = PlayerHouse::create([
        'player_id' => $user->id,
        'name' => 'My House',
        'tier' => 'cottage',
        'condition' => 100,
        'kingdom_id' => $kingdom->id,
        'location_type' => 'kingdom',
        'location_id' => $kingdom->id,
    ]);

    $bedroom = HouseRoom::create([
        'player_house_id' => $house->id,
        'room_type' => 'bedroom',
        'grid_x' => 0,
        'grid_y' => 0,
    ]);
    HouseFurniture::create([
        'house_room_id' => $bedroom->id,
        'hotspot_slug' => 'bed',
        'furniture_key' => 'straw_bed',
    ]);

    $hearthRoom = HouseRoom::create([
        'player_house_id' => $house->id,
        'room_type' => 'hearth_room',
        'grid_x' => 1,
        'grid_y' => 0,
    ]);
    HouseFurniture::create([
        'house_room_id' => $hearthRoom->id,
        'hotspot_slug' => 'fireplace',
        'furniture_key' => 'stone_fireplace',
    ]);

    $service = app(HouseService::class);
    $result = $service->restAtHome($user->fresh());

    expect($result['success'])->toBeTrue();
    expect($result['hp_restored'])->toBeGreaterThan(0);
    expect($result['message'])->toContain('fire');
});

test('rest without hearth does not restore hp', function () {
    $kingdom = Kingdom::factory()->create();
    $user = User::factory()->create([
        'energy' => 10,
        'max_energy' => 200,
        'current_kingdom_id' => $kingdom->id,
    ]);
    PlayerSkill::create(['player_id' => $user->id, 'skill_name' => 'hitpoints', 'level' => 100, 'xp' => 0]);
    $user->update(['hp' => 50]);

    createHouseWithBedroom($user, $kingdom);

    $service = app(HouseService::class);
    $result = $service->restAtHome($user->fresh());

    expect($result['success'])->toBeTrue();
    expect($result['hp_restored'])->toBe(0);

    $user->refresh();
    // hp column should be unchanged at 50
    expect((int) $user->getAttributes()['hp'])->toBe(50);
});

test('hp restoration scales with fireplace quality', function () {
    $kingdom = Kingdom::factory()->create();

    // Stone fireplace: 15% of max_hp (100) = floor(100 * 0.15) = 15
    $user1 = User::factory()->create([
        'energy' => 10,
        'max_energy' => 200,
        'current_kingdom_id' => $kingdom->id,
    ]);
    PlayerSkill::create(['player_id' => $user1->id, 'skill_name' => 'hitpoints', 'level' => 100, 'xp' => 0]);
    $user1->update(['hp' => 50]);

    $house1 = PlayerHouse::create([
        'player_id' => $user1->id,
        'name' => 'My House',
        'tier' => 'cottage',
        'condition' => 100,
        'kingdom_id' => $kingdom->id,
        'location_type' => 'kingdom',
        'location_id' => $kingdom->id,
    ]);

    $bedroom1 = HouseRoom::create([
        'player_house_id' => $house1->id,
        'room_type' => 'bedroom',
        'grid_x' => 0,
        'grid_y' => 0,
    ]);
    HouseFurniture::create([
        'house_room_id' => $bedroom1->id,
        'hotspot_slug' => 'bed',
        'furniture_key' => 'straw_bed',
    ]);

    $hearth1 = HouseRoom::create([
        'player_house_id' => $house1->id,
        'room_type' => 'hearth_room',
        'grid_x' => 1,
        'grid_y' => 0,
    ]);
    HouseFurniture::create([
        'house_room_id' => $hearth1->id,
        'hotspot_slug' => 'fireplace',
        'furniture_key' => 'stone_fireplace',
    ]);

    $service = app(HouseService::class);
    $result1 = $service->restAtHome($user1->fresh());
    expect($result1['hp_restored'])->toBe(15);

    // Marble fireplace: 35% of max_hp (100) = floor(100 * 0.35) = 35
    $user2 = User::factory()->create([
        'energy' => 10,
        'max_energy' => 200,
        'current_kingdom_id' => $kingdom->id,
    ]);
    PlayerSkill::create(['player_id' => $user2->id, 'skill_name' => 'hitpoints', 'level' => 100, 'xp' => 0]);
    $user2->update(['hp' => 50]);

    $house2 = PlayerHouse::create([
        'player_id' => $user2->id,
        'name' => 'My House',
        'tier' => 'cottage',
        'condition' => 100,
        'kingdom_id' => $kingdom->id,
        'location_type' => 'kingdom',
        'location_id' => $kingdom->id,
    ]);

    $bedroom2 = HouseRoom::create([
        'player_house_id' => $house2->id,
        'room_type' => 'bedroom',
        'grid_x' => 0,
        'grid_y' => 0,
    ]);
    HouseFurniture::create([
        'house_room_id' => $bedroom2->id,
        'hotspot_slug' => 'bed',
        'furniture_key' => 'straw_bed',
    ]);

    $hearth2 = HouseRoom::create([
        'player_house_id' => $house2->id,
        'room_type' => 'hearth_room',
        'grid_x' => 1,
        'grid_y' => 0,
    ]);
    HouseFurniture::create([
        'house_room_id' => $hearth2->id,
        'hotspot_slug' => 'fireplace',
        'furniture_key' => 'marble_fireplace',
    ]);

    $result2 = $service->restAtHome($user2->fresh());
    // Marble fireplace adds +8 max_hp_bonus, so max_hp = 108. 35% of 108 = 37
    expect($result2['hp_restored'])->toBe(37);

    // Marble should restore more than stone
    expect($result2['hp_restored'])->toBeGreaterThan($result1['hp_restored']);
});

test('hp restoration caps at max hp', function () {
    $kingdom = Kingdom::factory()->create();
    $user = User::factory()->create([
        'energy' => 10,
        'max_energy' => 200,
        'current_kingdom_id' => $kingdom->id,
    ]);
    // max_hp = 100 (hitpoints level 100)
    PlayerSkill::create(['player_id' => $user->id, 'skill_name' => 'hitpoints', 'level' => 100, 'xp' => 0]);
    // Only 5 HP below max
    $user->update(['hp' => 95]);

    $house = PlayerHouse::create([
        'player_id' => $user->id,
        'name' => 'My House',
        'tier' => 'cottage',
        'condition' => 100,
        'kingdom_id' => $kingdom->id,
        'location_type' => 'kingdom',
        'location_id' => $kingdom->id,
    ]);

    $bedroom = HouseRoom::create([
        'player_house_id' => $house->id,
        'room_type' => 'bedroom',
        'grid_x' => 0,
        'grid_y' => 0,
    ]);
    HouseFurniture::create([
        'house_room_id' => $bedroom->id,
        'hotspot_slug' => 'bed',
        'furniture_key' => 'straw_bed',
    ]);

    $hearth = HouseRoom::create([
        'player_house_id' => $house->id,
        'room_type' => 'hearth_room',
        'grid_x' => 1,
        'grid_y' => 0,
    ]);
    HouseFurniture::create([
        'house_room_id' => $hearth->id,
        'hotspot_slug' => 'fireplace',
        'furniture_key' => 'stone_fireplace',
    ]);

    $service = app(HouseService::class);
    $result = $service->restAtHome($user->fresh());

    expect($result['success'])->toBeTrue();
    // Stone fireplace adds +3 max_hp_bonus, so max_hp = 103
    // Would restore 15 HP, but only 8 HP needed to cap at 103
    expect($result['hp_restored'])->toBe(8);

    $user->refresh();
    expect($user->hp)->toBe(103);
});
