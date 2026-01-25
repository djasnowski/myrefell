<?php

use App\Models\LocationNpc;
use App\Models\Role;
use App\Models\WorldState;
use App\Services\CalendarService;
use App\Services\NpcLifecycleService;

beforeEach(function () {
    // Clear any existing world state and NPCs
    WorldState::query()->delete();
    LocationNpc::query()->delete();
    Role::query()->delete();
});

test('npc can calculate age correctly', function () {
    $npc = LocationNpc::factory()->create([
        'birth_year' => 10,
    ]);

    expect($npc->getAge(50))->toBe(40);
    expect($npc->getAge(10))->toBe(0);
    expect($npc->getAge(100))->toBe(90);
});

test('dead npc returns age at death', function () {
    $npc = LocationNpc::factory()->create([
        'birth_year' => 10,
        'death_year' => 70,
    ]);

    // Even if current year is 100, the age should be 60 (age at death)
    expect($npc->getAge(100))->toBe(60);
});

test('npc is dead when death year is set', function () {
    $livingNpc = LocationNpc::factory()->create([
        'death_year' => null,
    ]);

    $deadNpc = LocationNpc::factory()->create([
        'death_year' => 50,
    ]);

    expect($livingNpc->isDead())->toBeFalse();
    expect($livingNpc->isAlive())->toBeTrue();

    expect($deadNpc->isDead())->toBeTrue();
    expect($deadNpc->isAlive())->toBeFalse();
});

test('npc is adult at 16 years old', function () {
    $childNpc = LocationNpc::factory()->create([
        'birth_year' => 40,
    ]);

    $adultNpc = LocationNpc::factory()->create([
        'birth_year' => 34,
    ]);

    // At year 50: child is 10 years old, adult is 16
    expect($childNpc->isAdult(50))->toBeFalse();
    expect($adultNpc->isAdult(50))->toBeTrue();
});

test('npc is elderly at 50 years old', function () {
    $youngNpc = LocationNpc::factory()->create([
        'birth_year' => 10,
    ]);

    $elderlyNpc = LocationNpc::factory()->create([
        'birth_year' => 1,
    ]);

    // At year 50: young is 40 years old, elderly is 49 (not elderly yet)
    expect($youngNpc->isElderly(50))->toBeFalse();

    // At year 51: elderly is 50 years old
    expect($elderlyNpc->isElderly(51))->toBeTrue();
});

test('death probability is zero below minimum age', function () {
    $npc = LocationNpc::factory()->create([
        'birth_year' => 20,
    ]);

    // At year 50, NPC is 30 years old (below MIN_DEATH_AGE of 50)
    expect($npc->getDeathProbability(50))->toBe(0.0);
});

test('death probability is 100 percent at maximum age', function () {
    $npc = LocationNpc::factory()->create([
        'birth_year' => 1,
    ]);

    // At year 81, NPC is 80 years old (MAX_DEATH_AGE)
    expect($npc->getDeathProbability(81))->toBe(1.0);
});

test('death probability scales linearly with age', function () {
    $npc = LocationNpc::factory()->create([
        'birth_year' => 1,
    ]);

    // At year 51, NPC is 50 years old (MIN_DEATH_AGE) - should be 0%
    expect($npc->getDeathProbability(51))->toBe(0.0);

    // At year 66, NPC is 65 years old (halfway between 50 and 80) - should be 50%
    expect($npc->getDeathProbability(66))->toBe(0.5);

    // At year 81, NPC is 80 years old (MAX_DEATH_AGE) - should be 100%
    expect($npc->getDeathProbability(81))->toBe(1.0);
});

test('npc can die and updates correctly', function () {
    $npc = LocationNpc::factory()->create([
        'birth_year' => 1,
        'is_active' => true,
    ]);

    $npc->die(60);

    $npc->refresh();
    expect($npc->death_year)->toBe(60);
    expect($npc->is_active)->toBeFalse();
    expect($npc->isDead())->toBeTrue();
});

test('npc can check for personality traits', function () {
    $npc = LocationNpc::factory()->create([
        'personality_traits' => ['greedy', 'ambitious'],
    ]);

    expect($npc->hasTrait('greedy'))->toBeTrue();
    expect($npc->hasTrait('ambitious'))->toBeTrue();
    expect($npc->hasTrait('peaceful'))->toBeFalse();
});

test('alive scope filters correctly', function () {
    LocationNpc::factory()->create(['death_year' => null]);
    LocationNpc::factory()->create(['death_year' => null]);
    LocationNpc::factory()->create(['death_year' => 50]);

    expect(LocationNpc::alive()->count())->toBe(2);
});

test('dead scope filters correctly', function () {
    LocationNpc::factory()->create(['death_year' => null]);
    LocationNpc::factory()->create(['death_year' => 50]);
    LocationNpc::factory()->create(['death_year' => 60]);

    expect(LocationNpc::dead()->count())->toBe(2);
});

test('elderly scope filters correctly', function () {
    // Current year 60
    LocationNpc::factory()->create(['birth_year' => 30]); // Age 30 - not elderly
    LocationNpc::factory()->create(['birth_year' => 20]); // Age 40 - not elderly
    LocationNpc::factory()->create(['birth_year' => 10]); // Age 50 - elderly
    LocationNpc::factory()->create(['birth_year' => 5]);  // Age 55 - elderly

    expect(LocationNpc::elderly(60)->count())->toBe(2);
});

test('npc name can be generated', function () {
    $name = LocationNpc::generateNpcName('elder');

    expect($name)->toBeString();
    expect(strlen($name))->toBeGreaterThan(0);
});

test('npc family name can be generated', function () {
    $familyName = LocationNpc::generateFamilyName();

    expect($familyName)->toBeString();
    expect(strlen($familyName))->toBeGreaterThan(0);
});

test('npc personality traits can be generated', function () {
    $traits = LocationNpc::generatePersonalityTraits();

    expect($traits)->toBeArray();
    expect(count($traits))->toBeGreaterThanOrEqual(1);
    expect(count($traits))->toBeLessThanOrEqual(2);

    foreach ($traits as $trait) {
        expect(LocationNpc::PERSONALITY_TRAITS)->toContain($trait);
    }
});

test('lifecycle service processes yearly aging', function () {
    WorldState::factory()->create([
        'current_year' => 60,
        'current_season' => 'spring',
        'current_week' => 1,
    ]);

    // Create NPCs of various ages
    $youngNpc = LocationNpc::factory()->create(['birth_year' => 30]); // Age 30
    $elderlyNpc = LocationNpc::factory()->create(['birth_year' => 1]); // Age 59

    $service = new NpcLifecycleService();
    $results = $service->processYearlyAging();

    expect($results)->toBeArray();
    expect($results)->toHaveKeys(['aged', 'died', 'replaced']);
    expect($results['aged'])->toBeGreaterThanOrEqual(0);
});

test('lifecycle service can create npc for role', function () {
    WorldState::factory()->create([
        'current_year' => 50,
        'current_season' => 'spring',
        'current_week' => 1,
    ]);

    $role = Role::factory()->create();

    $service = new NpcLifecycleService();
    $npc = $service->createNpcForRole($role, 'village', 1);

    expect($npc)->toBeInstanceOf(LocationNpc::class);
    expect($npc->role_id)->toBe($role->id);
    expect($npc->location_type)->toBe('village');
    expect($npc->location_id)->toBe(1);
    expect($npc->is_active)->toBeTrue();
    expect($npc->isAlive())->toBeTrue();
    expect($npc->birth_year)->toBeGreaterThan(0);
    expect($npc->family_name)->not()->toBeNull();
    expect($npc->personality_traits)->toBeArray();
});

test('lifecycle service returns statistics', function () {
    WorldState::factory()->create([
        'current_year' => 60,
    ]);

    LocationNpc::factory()->create(['death_year' => null, 'is_active' => true, 'birth_year' => 30]);
    LocationNpc::factory()->create(['death_year' => null, 'is_active' => true, 'birth_year' => 10]);
    LocationNpc::factory()->create(['death_year' => null, 'is_active' => false, 'birth_year' => 20]);
    LocationNpc::factory()->create(['death_year' => 55, 'is_active' => false, 'birth_year' => 5]);

    $service = new NpcLifecycleService();
    $stats = $service->getNpcStatistics();

    expect($stats['living'])->toBe(3);
    expect($stats['dead'])->toBe(1);
    expect($stats['active'])->toBe(2);
    expect($stats['elderly'])->toBe(1); // Only the one with birth_year 10 (age 50)
    expect($stats['current_year'])->toBe(60);
});

test('calendar service dispatches age npcs job on new year', function () {
    WorldState::factory()->create([
        'current_year' => 1,
        'current_season' => 'winter',
        'current_week' => 12,
    ]);

    // The job should be dispatched when advancing from winter week 12 to spring week 1
    $service = new CalendarService();

    // Use queue fake to catch dispatched jobs
    \Illuminate\Support\Facades\Queue::fake();

    $service->advanceWeek();

    $state = WorldState::current();
    expect($state->current_year)->toBe(2);
    expect($state->current_season)->toBe('spring');
    expect($state->current_week)->toBe(1);

    \Illuminate\Support\Facades\Queue::assertPushed(\App\Jobs\AgeNpcs::class);
});

test('calendar service does not dispatch age npcs job on regular week advance', function () {
    WorldState::factory()->create([
        'current_year' => 1,
        'current_season' => 'spring',
        'current_week' => 5,
    ]);

    $service = new CalendarService();

    \Illuminate\Support\Facades\Queue::fake();

    $service->advanceWeek();

    $state = WorldState::current();
    expect($state->current_year)->toBe(1);
    expect($state->current_week)->toBe(6);

    \Illuminate\Support\Facades\Queue::assertNotPushed(\App\Jobs\AgeNpcs::class);
});
