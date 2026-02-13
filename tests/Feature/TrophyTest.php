<?php

use App\Models\HouseFurniture;
use App\Models\HouseRoom;
use App\Models\HouseTrophy;
use App\Models\Item;
use App\Models\Kingdom;
use App\Models\Monster;
use App\Models\PlayerHouse;
use App\Models\PlayerInventory;
use App\Models\User;
use App\Services\HouseBuffService;
use App\Services\LootService;
use App\Services\TrophyService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function createHouseWithTrophyHall(User $user): array
{
    $kingdom = Kingdom::factory()->create();
    $user->update(['current_kingdom_id' => $kingdom->id]);

    $house = PlayerHouse::create([
        'player_id' => $user->id,
        'name' => 'Test House',
        'tier' => 'manor',
        'condition' => 100,
        'kingdom_id' => $kingdom->id,
    ]);

    $room = HouseRoom::create([
        'player_house_id' => $house->id,
        'room_type' => 'trophy_hall',
        'grid_x' => 0,
        'grid_y' => 0,
    ]);

    return [$house, $room];
}

function createTrophyItem(string $name = 'Bandit Trophy', string $rarity = 'rare'): Item
{
    return Item::firstOrCreate(
        ['name' => $name],
        [
            'type' => 'misc',
            'subtype' => 'trophy',
            'rarity' => $rarity,
            'stackable' => false,
            'max_stack' => 1,
            'base_value' => 500,
        ]
    );
}

function addTrophyToInventory(User $user, Item $item): void
{
    PlayerInventory::create([
        'player_id' => $user->id,
        'item_id' => $item->id,
        'slot_number' => PlayerInventory::where('player_id', $user->id)->max('slot_number') + 1,
        'quantity' => 1,
    ]);
}

test('trophy drops from eligible monster', function () {
    $user = User::factory()->create(['gold' => 10000]);
    $item = createTrophyItem('Bandit Trophy');

    $monster = Monster::create([
        'name' => 'Bandit',
        'description' => 'A bandit',
        'type' => 'humanoid',
        'hp' => 50,
        'max_hp' => 50,
        'attack_level' => 10,
        'strength_level' => 10,
        'defense_level' => 10,
        'combat_level' => 10,
        'attack_style' => 'melee',
        'xp_reward' => 50,
        'gold_drop_min' => 10,
        'gold_drop_max' => 20,
        'is_boss' => false,
    ]);

    $service = app(LootService::class);

    // Run many times — at 1% chance, should drop at least once in many rolls
    $dropped = false;
    for ($i = 0; $i < 500; $i++) {
        $rewards = $service->rollAndGiveLoot($user, $monster);
        foreach ($rewards['items'] as $reward) {
            if ($reward['name'] === 'Bandit Trophy') {
                $dropped = true;
                break 2;
            }
        }
    }

    expect($dropped)->toBeTrue();
});

test('no trophy drop from low-level monster', function () {
    $user = User::factory()->create(['gold' => 10000]);
    createTrophyItem('Rat Trophy');

    $monster = Monster::create([
        'name' => 'Rat',
        'description' => 'A rat',
        'type' => 'beast',
        'hp' => 10,
        'max_hp' => 10,
        'attack_level' => 1,
        'strength_level' => 1,
        'defense_level' => 1,
        'combat_level' => 3,
        'attack_style' => 'melee',
        'xp_reward' => 5,
        'gold_drop_min' => 1,
        'gold_drop_max' => 3,
        'is_boss' => false,
    ]);

    $service = app(LootService::class);

    $dropped = false;
    for ($i = 0; $i < 100; $i++) {
        $rewards = $service->rollAndGiveLoot($user, $monster);
        foreach ($rewards['items'] as $reward) {
            if (str_contains($reward['name'], 'Trophy')) {
                $dropped = true;
                break 2;
            }
        }
    }

    expect($dropped)->toBeFalse();
});

test('can mount trophy in display slot', function () {
    $user = User::factory()->create(['gold' => 100000]);
    [$house, $room] = createHouseWithTrophyHall($user);

    // Build display furniture
    HouseFurniture::create([
        'house_room_id' => $room->id,
        'hotspot_slug' => 'display_1',
        'furniture_key' => 'wooden_display',
    ]);

    $item = createTrophyItem('Bandit Trophy');
    addTrophyToInventory($user, $item);

    $service = app(TrophyService::class);
    $result = $service->mountTrophy($user, 'display_1', $item->id);

    expect($result['success'])->toBeTrue();
    expect($result['message'])->toContain('Mounted');
    expect(HouseTrophy::where('player_house_id', $house->id)->where('slot', 'display_1')->exists())->toBeTrue();
    expect($user->inventory()->where('item_id', $item->id)->count())->toBe(0);
});

test('cannot mount trophy without display furniture built', function () {
    $user = User::factory()->create(['gold' => 100000]);
    [$house, $room] = createHouseWithTrophyHall($user);

    $item = createTrophyItem('Bandit Trophy');
    addTrophyToInventory($user, $item);

    $service = app(TrophyService::class);
    $result = $service->mountTrophy($user, 'display_1', $item->id);

    expect($result['success'])->toBeFalse();
    expect($result['message'])->toContain('display case or pedestal');
});

test('can mount boss trophy on pedestal', function () {
    $user = User::factory()->create(['gold' => 100000]);
    [$house, $room] = createHouseWithTrophyHall($user);

    HouseFurniture::create([
        'house_room_id' => $room->id,
        'hotspot_slug' => 'pedestal',
        'furniture_key' => 'stone_pedestal',
    ]);

    $item = createTrophyItem('Goblin King Trophy', 'legendary');
    addTrophyToInventory($user, $item);

    $service = app(TrophyService::class);
    $result = $service->mountTrophy($user, 'pedestal', $item->id);

    expect($result['success'])->toBeTrue();

    $trophy = HouseTrophy::where('player_house_id', $house->id)->where('slot', 'pedestal')->first();
    expect($trophy)->not->toBeNull();
    expect($trophy->is_boss)->toBeTrue();
    expect($trophy->monster_name)->toBe('Goblin King');
});

test('cannot mount regular trophy on pedestal', function () {
    $user = User::factory()->create(['gold' => 100000]);
    [$house, $room] = createHouseWithTrophyHall($user);

    HouseFurniture::create([
        'house_room_id' => $room->id,
        'hotspot_slug' => 'pedestal',
        'furniture_key' => 'stone_pedestal',
    ]);

    $item = createTrophyItem('Bandit Trophy');
    addTrophyToInventory($user, $item);

    $service = app(TrophyService::class);
    $result = $service->mountTrophy($user, 'pedestal', $item->id);

    expect($result['success'])->toBeFalse();
    expect($result['message'])->toContain('boss trophies');
});

test('removing trophy returns it to inventory', function () {
    $user = User::factory()->create(['gold' => 100000]);
    [$house, $room] = createHouseWithTrophyHall($user);

    HouseFurniture::create([
        'house_room_id' => $room->id,
        'hotspot_slug' => 'display_1',
        'furniture_key' => 'wooden_display',
    ]);

    $item = createTrophyItem('Bear Trophy');
    addTrophyToInventory($user, $item);

    $service = app(TrophyService::class);
    $service->mountTrophy($user, 'display_1', $item->id);

    expect($user->inventory()->where('item_id', $item->id)->count())->toBe(0);

    $result = $service->removeTrophy($user, 'display_1');

    expect($result['success'])->toBeTrue();
    expect($user->inventory()->where('item_id', $item->id)->count())->toBe(1);
    expect(HouseTrophy::where('player_house_id', $house->id)->where('slot', 'display_1')->exists())->toBeFalse();
});

test('mounting over existing trophy swaps correctly', function () {
    $user = User::factory()->create(['gold' => 100000]);
    [$house, $room] = createHouseWithTrophyHall($user);

    HouseFurniture::create([
        'house_room_id' => $room->id,
        'hotspot_slug' => 'display_1',
        'furniture_key' => 'wooden_display',
    ]);

    $item1 = createTrophyItem('Bandit Trophy');
    $item2 = createTrophyItem('Bear Trophy');
    addTrophyToInventory($user, $item1);
    addTrophyToInventory($user, $item2);

    $service = app(TrophyService::class);

    // Mount first trophy
    $service->mountTrophy($user, 'display_1', $item1->id);
    expect(HouseTrophy::where('player_house_id', $house->id)->where('item_id', $item1->id)->exists())->toBeTrue();

    // Mount second trophy over it
    $result = $service->mountTrophy($user, 'display_1', $item2->id);

    expect($result['success'])->toBeTrue();

    // Old trophy returned to inventory
    expect($user->inventory()->where('item_id', $item1->id)->count())->toBe(1);
    // New trophy mounted
    $trophy = HouseTrophy::where('player_house_id', $house->id)->where('slot', 'display_1')->first();
    expect($trophy->item_id)->toBe($item2->id);
});

test('trophy stat bonuses appear in house effects', function () {
    $user = User::factory()->create(['gold' => 100000]);
    [$house, $room] = createHouseWithTrophyHall($user);

    HouseFurniture::create([
        'house_room_id' => $room->id,
        'hotspot_slug' => 'display_1',
        'furniture_key' => 'wooden_display',
    ]);

    $item = createTrophyItem('Bandit Trophy');
    addTrophyToInventory($user, $item);

    $trophyService = app(TrophyService::class);
    $trophyService->mountTrophy($user, 'display_1', $item->id);

    $buffService = app(HouseBuffService::class);
    $effects = $buffService->getHouseEffects($user);

    // Bandit = humanoid = attack_bonus +1
    expect($effects['attack_bonus'] ?? 0)->toBe(1);
});

test('trophy bonuses vary by monster type', function () {
    $user = User::factory()->create(['gold' => 100000]);
    [$house, $room] = createHouseWithTrophyHall($user);

    HouseFurniture::create(['house_room_id' => $room->id, 'hotspot_slug' => 'display_1', 'furniture_key' => 'wooden_display']);
    HouseFurniture::create(['house_room_id' => $room->id, 'hotspot_slug' => 'display_2', 'furniture_key' => 'wooden_display']);

    $bandit = createTrophyItem('Bandit Trophy');
    $bear = createTrophyItem('Bear Trophy');
    addTrophyToInventory($user, $bandit);
    addTrophyToInventory($user, $bear);

    $trophyService = app(TrophyService::class);
    $trophyService->mountTrophy($user, 'display_1', $bandit->id);
    $trophyService->mountTrophy($user, 'display_2', $bear->id);

    $buffService = app(HouseBuffService::class);
    $effects = $buffService->getHouseEffects($user);

    // Bandit (humanoid) = attack_bonus +1, Bear (beast) = strength_bonus +1
    expect($effects['attack_bonus'] ?? 0)->toBe(1);
    expect($effects['strength_bonus'] ?? 0)->toBe(1);
});

test('boss trophy on pedestal gets boosted bonuses', function () {
    $user = User::factory()->create(['gold' => 100000]);
    [$house, $room] = createHouseWithTrophyHall($user);

    HouseFurniture::create([
        'house_room_id' => $room->id,
        'hotspot_slug' => 'pedestal',
        'furniture_key' => 'stone_pedestal',
    ]);

    $item = createTrophyItem('Goblin King Trophy', 'legendary');
    addTrophyToInventory($user, $item);

    $trophyService = app(TrophyService::class);
    $trophyService->mountTrophy($user, 'pedestal', $item->id);

    $buffService = app(HouseBuffService::class);
    $effects = $buffService->getHouseEffects($user);

    // Goblin King (goblinoid) = attack_bonus, on pedestal = +3
    expect($effects['attack_bonus'] ?? 0)->toBe(3);
});
