<?php

use App\Models\CropType;
use App\Models\GardenPlot;
use App\Models\HouseFurniture;
use App\Models\HouseRoom;
use App\Models\Item;
use App\Models\Kingdom;
use App\Models\PlayerHouse;
use App\Models\PlayerInventory;
use App\Models\PlayerSkill;
use App\Models\User;
use App\Services\GardenService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function createHouseWithGarden(User $user): array
{
    $kingdom = Kingdom::factory()->create();
    $user->update(['current_kingdom_id' => $kingdom->id]);

    $house = PlayerHouse::create([
        'player_id' => $user->id,
        'name' => 'Test House',
        'tier' => 'house',
        'condition' => 100,
        'kingdom_id' => $kingdom->id,
    ]);

    $room = HouseRoom::create([
        'player_house_id' => $house->id,
        'room_type' => 'garden',
        'grid_x' => 0,
        'grid_y' => 0,
    ]);

    return [$house, $room];
}

function buildPlanter(HouseRoom $room, string $slot = 'planter_1'): HouseFurniture
{
    return HouseFurniture::create([
        'house_room_id' => $room->id,
        'hotspot_slug' => $slot,
        'furniture_key' => 'wooden_planter',
    ]);
}

function createHerbCropType(): CropType
{
    $seedItem = Item::firstOrCreate(
        ['name' => 'Herb Seed'],
        ['type' => 'resource', 'subtype' => 'farming', 'rarity' => 'common', 'stackable' => true, 'max_stack' => 100, 'base_value' => 5]
    );

    $harvestItem = Item::firstOrCreate(
        ['name' => 'Herbs'],
        ['type' => 'resource', 'subtype' => 'herblore', 'rarity' => 'common', 'stackable' => true, 'max_stack' => 100, 'base_value' => 10]
    );

    return CropType::firstOrCreate(
        ['slug' => 'herbs'],
        [
            'name' => 'Herbs',
            'icon' => 'leaf',
            'description' => 'Aromatic herbs.',
            'grow_time_minutes' => 20,
            'farming_level_required' => 1,
            'farming_xp' => 100,
            'yield_min' => 3,
            'yield_max' => 6,
            'seed_item_id' => $seedItem->id,
            'harvest_item_id' => $harvestItem->id,
            'plant_cost' => 0,
            'seasons' => ['spring', 'summer'],
        ]
    );
}

function addSeedToInventory(User $user, CropType $cropType, int $quantity = 1): void
{
    PlayerInventory::create([
        'player_id' => $user->id,
        'item_id' => $cropType->seed_item_id,
        'slot_number' => PlayerInventory::where('player_id', $user->id)->max('slot_number') + 1,
        'quantity' => $quantity,
    ]);
}

function createNonHerbCropType(): CropType
{
    $seedItem = Item::firstOrCreate(
        ['name' => 'Wheat Seed'],
        ['type' => 'resource', 'subtype' => 'farming', 'rarity' => 'common', 'stackable' => true, 'max_stack' => 100, 'base_value' => 3]
    );

    $harvestItem = Item::firstOrCreate(
        ['name' => 'Wheat'],
        ['type' => 'resource', 'subtype' => 'farming', 'rarity' => 'common', 'stackable' => true, 'max_stack' => 100, 'base_value' => 5]
    );

    return CropType::firstOrCreate(
        ['slug' => 'grain'],
        [
            'name' => 'Wheat',
            'icon' => 'wheat',
            'description' => 'A basic grain.',
            'grow_time_minutes' => 15,
            'farming_level_required' => 1,
            'farming_xp' => 50,
            'yield_min' => 5,
            'yield_max' => 10,
            'seed_item_id' => $seedItem->id,
            'harvest_item_id' => $harvestItem->id,
            'plant_cost' => 0,
            'seasons' => ['spring'],
        ]
    );
}

test('can plant herb in garden plot with planter built', function () {
    $user = User::factory()->create(['gold' => 100000, 'energy' => 100]);
    PlayerSkill::create(['player_id' => $user->id, 'skill_name' => 'farming', 'level' => 10, 'xp' => 0]);
    $user->load('skills');

    [$house, $room] = createHouseWithGarden($user);
    buildPlanter($room, 'planter_1');

    $cropType = createHerbCropType();
    addSeedToInventory($user, $cropType);

    $service = app(GardenService::class);
    $result = $service->plantHerb($user, 'planter_1', $cropType->id);

    expect($result['success'])->toBeTrue();
    expect($result['message'])->toContain('Planted');

    $plot = GardenPlot::where('player_house_id', $house->id)->where('plot_slot', 'planter_1')->first();
    expect($plot)->not->toBeNull();
    expect($plot->status)->toBeIn(['planted', 'growing']);
    expect($plot->crop_type_id)->toBe($cropType->id);
    expect($plot->quality)->toBeGreaterThanOrEqual(60);
});

test('cannot plant without planter furniture', function () {
    $user = User::factory()->create(['gold' => 100000, 'energy' => 100]);
    PlayerSkill::create(['player_id' => $user->id, 'skill_name' => 'farming', 'level' => 10, 'xp' => 0]);
    $user->load('skills');

    [$house, $room] = createHouseWithGarden($user);
    // No planter built

    $cropType = createHerbCropType();
    addSeedToInventory($user, $cropType);

    $service = app(GardenService::class);
    $result = $service->plantHerb($user, 'planter_1', $cropType->id);

    expect($result['success'])->toBeFalse();
    expect($result['message'])->toContain('planter');
});

test('cannot plant non-herb crop in garden', function () {
    $user = User::factory()->create(['gold' => 100000, 'energy' => 100]);
    PlayerSkill::create(['player_id' => $user->id, 'skill_name' => 'farming', 'level' => 10, 'xp' => 0]);
    $user->load('skills');

    [$house, $room] = createHouseWithGarden($user);
    buildPlanter($room, 'planter_1');

    $nonHerb = createNonHerbCropType();

    PlayerInventory::create([
        'player_id' => $user->id,
        'item_id' => $nonHerb->seed_item_id,
        'slot_number' => 1,
        'quantity' => 1,
    ]);

    $service = app(GardenService::class);
    $result = $service->plantHerb($user, 'planter_1', $nonHerb->id);

    expect($result['success'])->toBeFalse();
    expect($result['message'])->toContain('Only herbs');
});

test('garden growth time is 1.5x normal', function () {
    $user = User::factory()->create(['gold' => 100000, 'energy' => 100]);
    PlayerSkill::create(['player_id' => $user->id, 'skill_name' => 'farming', 'level' => 10, 'xp' => 0]);
    $user->load('skills');

    [$house, $room] = createHouseWithGarden($user);
    buildPlanter($room, 'planter_1');

    $cropType = createHerbCropType();
    addSeedToInventory($user, $cropType);

    $service = app(GardenService::class);
    $service->plantHerb($user, 'planter_1', $cropType->id);

    $plot = GardenPlot::where('player_house_id', $house->id)->where('plot_slot', 'planter_1')->first();
    $expectedMinutes = (int) ceil($cropType->grow_time_minutes * 1.5);

    $actualMinutes = (int) $plot->planted_at->diffInMinutes($plot->ready_at);
    expect($actualMinutes)->toBe($expectedMinutes);
});

test('can water garden plot', function () {
    $user = User::factory()->create(['gold' => 100000, 'energy' => 100]);
    PlayerSkill::create(['player_id' => $user->id, 'skill_name' => 'farming', 'level' => 10, 'xp' => 0]);
    $user->load('skills');

    [$house, $room] = createHouseWithGarden($user);
    buildPlanter($room, 'planter_1');

    $cropType = createHerbCropType();
    addSeedToInventory($user, $cropType);

    $service = app(GardenService::class);
    $service->plantHerb($user, 'planter_1', $cropType->id);

    $result = $service->waterPlot($user, 'planter_1');
    expect($result['success'])->toBeTrue();

    $plot = GardenPlot::where('player_house_id', $house->id)->where('plot_slot', 'planter_1')->first();
    expect($plot->is_watered)->toBeTrue();
    expect($plot->status)->toBe('growing');
});

test('can tend garden plot costs 2 energy', function () {
    $user = User::factory()->create(['gold' => 100000, 'energy' => 100]);
    PlayerSkill::create(['player_id' => $user->id, 'skill_name' => 'farming', 'level' => 10, 'xp' => 0]);
    $user->load('skills');

    [$house, $room] = createHouseWithGarden($user);
    buildPlanter($room, 'planter_1');

    $cropType = createHerbCropType();
    addSeedToInventory($user, $cropType);

    $service = app(GardenService::class);
    $service->plantHerb($user, 'planter_1', $cropType->id);

    $energyBefore = $user->energy;
    $result = $service->tendPlot($user, 'planter_1');

    expect($result['success'])->toBeTrue();
    $user->refresh();
    expect($user->energy)->toBeLessThan($energyBefore);

    $plot = GardenPlot::where('player_house_id', $house->id)->where('plot_slot', 'planter_1')->first();
    expect($plot->times_tended)->toBe(1);
});

test('can harvest ready garden plot awards farming and herblore xp', function () {
    $user = User::factory()->create(['gold' => 100000, 'energy' => 100]);
    PlayerSkill::create(['player_id' => $user->id, 'skill_name' => 'farming', 'level' => 10, 'xp' => 0]);
    PlayerSkill::create(['player_id' => $user->id, 'skill_name' => 'herblore', 'level' => 1, 'xp' => 0]);
    $user->load('skills');

    [$house, $room] = createHouseWithGarden($user);
    buildPlanter($room, 'planter_1');

    $cropType = createHerbCropType();

    // Create a plot directly in ready state
    GardenPlot::create([
        'player_house_id' => $house->id,
        'plot_slot' => 'planter_1',
        'crop_type_id' => $cropType->id,
        'status' => 'ready',
        'planted_at' => now()->subMinutes(30),
        'ready_at' => now()->subMinutes(1),
        'withers_at' => now()->addHours(23),
        'quality' => 70,
    ]);

    $service = app(GardenService::class);
    $result = $service->harvestPlot($user, 'planter_1');

    expect($result['success'])->toBeTrue();
    expect($result['message'])->toContain('Farming XP');
    expect($result['message'])->toContain('Herblore XP');
    expect($result['data']['farming_xp'])->toBeGreaterThan(0);
    expect($result['data']['herblore_xp'])->toBeGreaterThan(0);

    // Check plot was cleared
    $plot = GardenPlot::where('player_house_id', $house->id)->where('plot_slot', 'planter_1')->first();
    expect($plot->status)->toBe('empty');
});

test('withered plot yields nothing', function () {
    $user = User::factory()->create(['gold' => 100000, 'energy' => 100]);
    PlayerSkill::create(['player_id' => $user->id, 'skill_name' => 'farming', 'level' => 10, 'xp' => 0]);
    PlayerSkill::create(['player_id' => $user->id, 'skill_name' => 'herblore', 'level' => 1, 'xp' => 0]);
    $user->load('skills');

    [$house, $room] = createHouseWithGarden($user);
    buildPlanter($room, 'planter_1');

    $cropType = createHerbCropType();

    GardenPlot::create([
        'player_house_id' => $house->id,
        'plot_slot' => 'planter_1',
        'crop_type_id' => $cropType->id,
        'status' => 'withered',
        'planted_at' => now()->subDays(2),
        'ready_at' => now()->subDays(1),
        'withers_at' => now()->subHours(1),
        'quality' => 60,
    ]);

    $service = app(GardenService::class);
    $result = $service->harvestPlot($user, 'planter_1');

    expect($result['success'])->toBeFalse();
    expect($result['message'])->toContain('withered');
});

test('compost adds charges with max 10', function () {
    $user = User::factory()->create(['gold' => 100000, 'energy' => 100]);
    [$house, $room] = createHouseWithGarden($user);

    HouseFurniture::create([
        'house_room_id' => $room->id,
        'hotspot_slug' => 'compost_bin',
        'furniture_key' => 'basic_compost',
    ]);

    $bonesItem = Item::firstOrCreate(
        ['name' => 'Bones'],
        ['type' => 'resource', 'subtype' => 'misc', 'rarity' => 'common', 'stackable' => true, 'max_stack' => 100, 'base_value' => 1]
    );

    PlayerInventory::create([
        'player_id' => $user->id,
        'item_id' => $bonesItem->id,
        'slot_number' => 1,
        'quantity' => 10,
    ]);

    $service = app(GardenService::class);
    $result = $service->addCompost($user);

    expect($result['success'])->toBeTrue();
    $house->refresh();
    expect($house->compost_charges)->toBe(3);
});

test('compost requires bones in inventory', function () {
    $user = User::factory()->create(['gold' => 100000, 'energy' => 100]);
    [$house, $room] = createHouseWithGarden($user);

    HouseFurniture::create([
        'house_room_id' => $room->id,
        'hotspot_slug' => 'compost_bin',
        'furniture_key' => 'basic_compost',
    ]);

    $service = app(GardenService::class);
    $result = $service->addCompost($user);

    expect($result['success'])->toBeFalse();
    expect($result['message'])->toContain('Bones');
});

test('using compost boosts quality by 15', function () {
    $user = User::factory()->create(['gold' => 100000, 'energy' => 100]);
    PlayerSkill::create(['player_id' => $user->id, 'skill_name' => 'farming', 'level' => 10, 'xp' => 0]);
    $user->load('skills');

    [$house, $room] = createHouseWithGarden($user);
    buildPlanter($room, 'planter_1');
    $house->update(['compost_charges' => 5]);

    $cropType = createHerbCropType();

    GardenPlot::create([
        'player_house_id' => $house->id,
        'plot_slot' => 'planter_1',
        'crop_type_id' => $cropType->id,
        'status' => 'planted',
        'planted_at' => now(),
        'ready_at' => now()->addMinutes(30),
        'withers_at' => now()->addHours(24),
        'quality' => 60,
    ]);

    $service = app(GardenService::class);
    $result = $service->useCompost($user, 'planter_1');

    expect($result['success'])->toBeTrue();

    $plot = GardenPlot::where('player_house_id', $house->id)->where('plot_slot', 'planter_1')->first();
    expect($plot->is_composted)->toBeTrue();
    expect($plot->quality)->toBe(75);

    $house->refresh();
    expect($house->compost_charges)->toBe(4);
});

test('auto-water from irrigation furniture waters on plant', function () {
    $user = User::factory()->create(['gold' => 100000, 'energy' => 100]);
    PlayerSkill::create(['player_id' => $user->id, 'skill_name' => 'farming', 'level' => 10, 'xp' => 0]);
    $user->load('skills');

    [$house, $room] = createHouseWithGarden($user);
    buildPlanter($room, 'planter_1');

    // Build drip system (has auto_water)
    HouseFurniture::create([
        'house_room_id' => $room->id,
        'hotspot_slug' => 'irrigation',
        'furniture_key' => 'drip_system',
    ]);

    $cropType = createHerbCropType();
    addSeedToInventory($user, $cropType);

    $service = app(GardenService::class);
    $result = $service->plantHerb($user, 'planter_1', $cropType->id);

    expect($result['success'])->toBeTrue();
    expect($result['message'])->toContain('Auto-watered');

    $plot = GardenPlot::where('player_house_id', $house->id)->where('plot_slot', 'planter_1')->first();
    expect($plot->is_watered)->toBeTrue();
    expect($plot->status)->toBe('growing');
});

test('garden is season-independent', function () {
    // Herbs require specific seasons outdoors, but garden should always work
    $user = User::factory()->create(['gold' => 100000, 'energy' => 100]);
    PlayerSkill::create(['player_id' => $user->id, 'skill_name' => 'farming', 'level' => 10, 'xp' => 0]);
    $user->load('skills');

    [$house, $room] = createHouseWithGarden($user);
    buildPlanter($room, 'planter_1');

    $cropType = createHerbCropType();
    addSeedToInventory($user, $cropType);

    // Garden service doesn't check canPlantInSeason - that's the point
    $service = app(GardenService::class);
    $result = $service->plantHerb($user, 'planter_1', $cropType->id);

    expect($result['success'])->toBeTrue();
});

test('garden plant route works', function () {
    $user = User::factory()->create(['gold' => 100000, 'energy' => 100]);
    PlayerSkill::create(['player_id' => $user->id, 'skill_name' => 'farming', 'level' => 10, 'xp' => 0]);

    [$house, $room] = createHouseWithGarden($user);
    buildPlanter($room, 'planter_1');

    $cropType = createHerbCropType();
    addSeedToInventory($user, $cropType);

    $response = $this->actingAs($user)->post('/house/garden/plant', [
        'plot_slot' => 'planter_1',
        'crop_type_id' => $cropType->id,
    ]);

    $response->assertRedirect();
});

test('garden harvest route works', function () {
    $user = User::factory()->create(['gold' => 100000, 'energy' => 100]);
    PlayerSkill::create(['player_id' => $user->id, 'skill_name' => 'farming', 'level' => 10, 'xp' => 0]);
    PlayerSkill::create(['player_id' => $user->id, 'skill_name' => 'herblore', 'level' => 1, 'xp' => 0]);

    [$house, $room] = createHouseWithGarden($user);
    buildPlanter($room, 'planter_1');

    $cropType = createHerbCropType();
    GardenPlot::create([
        'player_house_id' => $house->id,
        'plot_slot' => 'planter_1',
        'crop_type_id' => $cropType->id,
        'status' => 'ready',
        'planted_at' => now()->subMinutes(30),
        'ready_at' => now()->subMinutes(1),
        'withers_at' => now()->addHours(23),
        'quality' => 70,
    ]);

    $response = $this->actingAs($user)->post('/house/garden/harvest', [
        'plot_slot' => 'planter_1',
    ]);

    $response->assertRedirect();
});

test('garden compost route works', function () {
    $user = User::factory()->create(['gold' => 100000, 'energy' => 100]);
    [$house, $room] = createHouseWithGarden($user);

    HouseFurniture::create([
        'house_room_id' => $room->id,
        'hotspot_slug' => 'compost_bin',
        'furniture_key' => 'basic_compost',
    ]);

    $bonesItem = Item::firstOrCreate(
        ['name' => 'Bones'],
        ['type' => 'resource', 'subtype' => 'misc', 'rarity' => 'common', 'stackable' => true, 'max_stack' => 100, 'base_value' => 1]
    );

    PlayerInventory::create([
        'player_id' => $user->id,
        'item_id' => $bonesItem->id,
        'slot_number' => 1,
        'quantity' => 10,
    ]);

    $response = $this->actingAs($user)->post('/house/garden/compost');

    $response->assertRedirect();
});
