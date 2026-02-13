<?php

use App\Models\HouseFurniture;
use App\Models\HouseRoom;
use App\Models\HouseServant;
use App\Models\HouseStorage;
use App\Models\Item;
use App\Models\Kingdom;
use App\Models\PlayerHouse;
use App\Models\PlayerSkill;
use App\Models\ServantTask;
use App\Models\User;
use App\Services\ServantService;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

function createHouseWithServantQuarters(User $user, int $constructionLevel = 40): array
{
    $kingdom = Kingdom::factory()->create();
    $user->update(['current_kingdom_id' => $kingdom->id]);

    PlayerSkill::create(['player_id' => $user->id, 'skill_name' => 'construction', 'level' => $constructionLevel, 'xp' => 0]);
    $user->load('skills');

    $house = PlayerHouse::create([
        'player_id' => $user->id,
        'name' => 'My House',
        'tier' => 'cottage',
        'condition' => 100,
        'kingdom_id' => $kingdom->id,
    ]);

    $room = HouseRoom::create([
        'player_house_id' => $house->id,
        'room_type' => 'servant_quarters',
        'grid_x' => 0,
        'grid_y' => 0,
    ]);

    $bed = HouseFurniture::create([
        'house_room_id' => $room->id,
        'hotspot_slug' => 'bed',
        'furniture_key' => 'servant_cot',
    ]);

    return [$house, $room, $bed];
}

function createTestItems(): void
{
    Item::firstOrCreate(['name' => 'Wood'], ['type' => 'resource', 'subtype' => 'wood', 'rarity' => 'common', 'stackable' => true, 'max_stack' => 100, 'base_value' => 2]);
    Item::firstOrCreate(['name' => 'Plank'], ['type' => 'misc', 'subtype' => 'material', 'rarity' => 'common', 'stackable' => true, 'max_stack' => 100, 'base_value' => 20]);
    Item::firstOrCreate(['name' => 'Cooked Meat'], ['type' => 'consumable', 'subtype' => 'food', 'rarity' => 'common', 'stackable' => true, 'max_stack' => 100, 'base_value' => 15, 'food_value' => 20]);
}

test('can hire servant with all requirements met', function () {
    createTestItems();
    $user = User::factory()->create(['gold' => 100000]);
    [$house] = createHouseWithServantQuarters($user, 40);

    $service = app(ServantService::class);
    $result = $service->hireServant($user, 'handyman');

    expect($result['success'])->toBeTrue();
    expect($result['message'])->toContain('Handyman');

    $user->refresh();
    expect($user->gold)->toBe(95000); // 100000 - 5000
    expect(HouseServant::where('player_house_id', $house->id)->exists())->toBeTrue();
});

test('cannot hire without servant quarters room', function () {
    $kingdom = Kingdom::factory()->create();
    $user = User::factory()->create(['gold' => 100000, 'current_kingdom_id' => $kingdom->id]);
    PlayerSkill::create(['player_id' => $user->id, 'skill_name' => 'construction', 'level' => 40, 'xp' => 0]);
    $user->load('skills');

    PlayerHouse::create([
        'player_id' => $user->id,
        'name' => 'My House',
        'tier' => 'cottage',
        'condition' => 100,
        'kingdom_id' => $kingdom->id,
    ]);

    $service = app(ServantService::class);
    $result = $service->hireServant($user, 'handyman');

    expect($result['success'])->toBeFalse();
    expect($result['message'])->toContain('Servant Quarters');
});

test('cannot hire without bed furniture in servant quarters', function () {
    $kingdom = Kingdom::factory()->create();
    $user = User::factory()->create(['gold' => 100000, 'current_kingdom_id' => $kingdom->id]);
    PlayerSkill::create(['player_id' => $user->id, 'skill_name' => 'construction', 'level' => 40, 'xp' => 0]);
    $user->load('skills');

    $house = PlayerHouse::create([
        'player_id' => $user->id,
        'name' => 'My House',
        'tier' => 'cottage',
        'condition' => 100,
        'kingdom_id' => $kingdom->id,
    ]);

    HouseRoom::create([
        'player_house_id' => $house->id,
        'room_type' => 'servant_quarters',
        'grid_x' => 0,
        'grid_y' => 0,
    ]);

    $service = app(ServantService::class);
    $result = $service->hireServant($user, 'handyman');

    expect($result['success'])->toBeFalse();
    expect($result['message'])->toContain('bed');
});

test('cannot hire without enough gold', function () {
    $user = User::factory()->create(['gold' => 100]);
    createHouseWithServantQuarters($user, 40);

    $service = app(ServantService::class);
    $result = $service->hireServant($user, 'handyman');

    expect($result['success'])->toBeFalse();
    expect($result['message'])->toContain('Not enough gold');
});

test('cannot hire second servant', function () {
    createTestItems();
    $user = User::factory()->create(['gold' => 200000]);
    [$house] = createHouseWithServantQuarters($user, 40);

    $service = app(ServantService::class);
    $service->hireServant($user, 'handyman');

    $user->refresh();
    $result = $service->hireServant($user, 'maid');

    expect($result['success'])->toBeFalse();
    expect($result['message'])->toContain('already have');
});

test('can dismiss servant', function () {
    createTestItems();
    $user = User::factory()->create(['gold' => 100000]);
    [$house] = createHouseWithServantQuarters($user, 40);

    $service = app(ServantService::class);
    $service->hireServant($user, 'handyman');

    $result = $service->dismissServant($user);

    expect($result['success'])->toBeTrue();
    expect(HouseServant::where('player_house_id', $house->id)->exists())->toBeFalse();
});

test('can assign sawmill run task', function () {
    createTestItems();
    $user = User::factory()->create(['gold' => 100000]);
    [$house] = createHouseWithServantQuarters($user, 40);

    $service = app(ServantService::class);
    $service->hireServant($user, 'handyman');

    // Add logs to storage
    $wood = Item::where('name', 'Wood')->first();
    HouseStorage::create(['player_house_id' => $house->id, 'item_id' => $wood->id, 'quantity' => 10]);

    $user->refresh();
    $result = $service->assignTask($user, 'sawmill_run', ['plank_name' => 'Plank', 'quantity' => 5]);

    expect($result['success'])->toBeTrue();
    expect(ServantTask::where('task_type', 'sawmill_run')->count())->toBe(1);

    // Gold deducted for fees (5 * 10 = 50)
    $user->refresh();
    expect($user->gold)->toBe(100000 - 5000 - 50); // hire_cost + fees
});

test('sawmill task completes correctly', function () {
    createTestItems();
    $user = User::factory()->create(['gold' => 100000]);
    [$house] = createHouseWithServantQuarters($user, 40);

    $service = app(ServantService::class);
    $service->hireServant($user, 'handyman');

    // Add logs to storage
    $wood = Item::where('name', 'Wood')->first();
    HouseStorage::create(['player_house_id' => $house->id, 'item_id' => $wood->id, 'quantity' => 10]);

    $user->refresh();
    $service->assignTask($user, 'sawmill_run', ['plank_name' => 'Plank', 'quantity' => 5]);

    // Fast forward the task
    $task = ServantTask::first();
    $task->update(['estimated_completion' => now()->subMinute()]);

    $service->completeTask($task);
    $task->refresh();

    expect($task->status)->toBe('completed');
    expect($task->result_message)->toContain('5x Plank');

    // Wood removed from storage (5 used)
    $woodStorage = HouseStorage::where('player_house_id', $house->id)->where('item_id', $wood->id)->first();
    expect($woodStorage->quantity)->toBe(5);

    // Planks added to storage
    $plank = Item::where('name', 'Plank')->first();
    $plankStorage = HouseStorage::where('player_house_id', $house->id)->where('item_id', $plank->id)->first();
    expect($plankStorage)->not->toBeNull();
    expect($plankStorage->quantity)->toBe(5);
});

test('processWeeklyWages sets on_strike when player broke', function () {
    createTestItems();
    $user = User::factory()->create(['gold' => 100000]);
    createHouseWithServantQuarters($user, 40);

    $service = app(ServantService::class);
    $service->hireServant($user, 'handyman');

    // Drain gold
    $user->update(['gold' => 0]);

    $stats = $service->processWeeklyWages();

    expect($stats['strikes'])->toBe(1);
    expect($stats['paid'])->toBe(0);

    $servant = HouseServant::first();
    expect($servant->on_strike)->toBeTrue();
});

test('payWages clears on_strike and resumes tasks', function () {
    createTestItems();
    $user = User::factory()->create(['gold' => 100000]);
    [$house] = createHouseWithServantQuarters($user, 40);

    $service = app(ServantService::class);
    $service->hireServant($user, 'handyman');

    // Set on strike
    $servant = HouseServant::first();
    $servant->update(['on_strike' => true]);

    $user->refresh();
    $result = $service->payWages($user);

    expect($result['success'])->toBeTrue();
    $servant->refresh();
    expect($servant->on_strike)->toBeFalse();

    $user->refresh();
    expect($user->gold)->toBe(100000 - 5000 - 100); // hire_cost + wage
});

test('on-strike servant rejects new task assignments', function () {
    createTestItems();
    $user = User::factory()->create(['gold' => 100000]);
    [$house] = createHouseWithServantQuarters($user, 40);

    $service = app(ServantService::class);
    $service->hireServant($user, 'handyman');

    $servant = HouseServant::first();
    $servant->update(['on_strike' => true]);

    $wood = Item::where('name', 'Wood')->first();
    HouseStorage::create(['player_house_id' => $house->id, 'item_id' => $wood->id, 'quantity' => 10]);

    $user->refresh();
    $result = $service->assignTask($user, 'sawmill_run', ['plank_name' => 'Plank', 'quantity' => 5]);

    expect($result['success'])->toBeFalse();
    expect($result['message'])->toContain('on strike');
});

test('servant_speed_bonus reduces task duration', function () {
    createTestItems();
    $user = User::factory()->create(['gold' => 100000]);
    [$house, $room] = createHouseWithServantQuarters($user, 55);

    // Add brass bell with servant_speed_bonus: 15
    HouseFurniture::create([
        'house_room_id' => $room->id,
        'hotspot_slug' => 'bell',
        'furniture_key' => 'brass_bell',
    ]);

    $service = app(ServantService::class);
    $service->hireServant($user, 'handyman');

    $wood = Item::where('name', 'Wood')->first();
    HouseStorage::create(['player_house_id' => $house->id, 'item_id' => $wood->id, 'quantity' => 10]);

    $user->refresh();
    $service->assignTask($user, 'sawmill_run', ['plank_name' => 'Plank', 'quantity' => 1]);

    $task = ServantTask::where('status', 'in_progress')->first();
    expect($task)->not->toBeNull();

    // base_speed=60, 1/6 carry = ceil(1/6)=1 trip, 60 seconds
    // With 15% speed bonus: 60 * 0.85 = 51
    $duration = (int) $task->started_at->diffInSeconds($task->estimated_completion);
    expect($duration)->toBe(51);
});

test('task queue processes in order', function () {
    createTestItems();
    $user = User::factory()->create(['gold' => 100000]);
    [$house] = createHouseWithServantQuarters($user, 40);

    $service = app(ServantService::class);
    $service->hireServant($user, 'handyman');

    $wood = Item::where('name', 'Wood')->first();
    HouseStorage::create(['player_house_id' => $house->id, 'item_id' => $wood->id, 'quantity' => 20]);

    $user->refresh();
    $service->assignTask($user, 'sawmill_run', ['plank_name' => 'Plank', 'quantity' => 3]);

    $user->refresh();
    $service->assignTask($user, 'sawmill_run', ['plank_name' => 'Plank', 'quantity' => 2]);

    $tasks = ServantTask::orderBy('id')->get();
    expect($tasks[0]->status)->toBe('in_progress');
    expect($tasks[1]->status)->toBe('queued');

    // Complete first task
    $tasks[0]->update(['estimated_completion' => now()->subMinute()]);
    $service->completeTask($tasks[0]);

    $tasks[0]->refresh();
    $tasks[1]->refresh();
    expect($tasks[0]->status)->toBe('completed');
    expect($tasks[1]->status)->toBe('in_progress');
});
