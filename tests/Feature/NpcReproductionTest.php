<?php

use App\Models\LocationNpc;
use App\Models\Role;
use App\Models\WorldState;
use App\Services\CalendarService;
use App\Services\NpcReproductionService;

beforeEach(function () {
    WorldState::query()->delete();
    LocationNpc::query()->delete();
    Role::query()->delete();

    WorldState::factory()->create([
        'current_year' => 50,
        'current_season' => 'spring',
        'current_week' => 1,
    ]);
});

test('npc can be male or female', function () {
    $male = LocationNpc::factory()->male()->create();
    $female = LocationNpc::factory()->female()->create();

    expect($male->gender)->toBe('male');
    expect($female->gender)->toBe('female');
});

test('npc is of reproductive age between 18 and 45', function () {
    $youngNpc = LocationNpc::factory()->age(15, 50)->female()->create();
    $adultNpc = LocationNpc::factory()->age(25, 50)->female()->create();
    $oldFemale = LocationNpc::factory()->age(50, 50)->female()->create();
    $oldMale = LocationNpc::factory()->age(50, 50)->male()->create();

    expect($youngNpc->isOfReproductiveAge(50))->toBeFalse();
    expect($adultNpc->isOfReproductiveAge(50))->toBeTrue();
    expect($oldFemale->isOfReproductiveAge(50))->toBeFalse(); // Women can't reproduce after 45
    expect($oldMale->isOfReproductiveAge(50))->toBeTrue(); // Men have no upper limit
});

test('npc can marry another npc', function () {
    $npc1 = LocationNpc::factory()->male()->create();
    $npc2 = LocationNpc::factory()->female()->create();

    $npc1->marry($npc2);

    $npc1->refresh();
    $npc2->refresh();

    expect($npc1->isMarried())->toBeTrue();
    expect($npc2->isMarried())->toBeTrue();
    expect($npc1->spouse_id)->toBe($npc2->id);
    expect($npc2->spouse_id)->toBe($npc1->id);
});

test('npc can check if married', function () {
    $unmarriedNpc = LocationNpc::factory()->create();
    $marriedNpc1 = LocationNpc::factory()->male()->create();
    $marriedNpc2 = LocationNpc::factory()->female()->create();

    $marriedNpc1->marry($marriedNpc2);

    expect($unmarriedNpc->isMarried())->toBeFalse();
    expect($marriedNpc1->refresh()->isMarried())->toBeTrue();
    expect($marriedNpc2->refresh()->isMarried())->toBeTrue();
});

test('npc respects birth cooldown', function () {
    $npc = LocationNpc::factory()->female()->age(25, 50)->create([
        'last_birth_year' => null,
    ]);

    expect($npc->canHaveChild(50))->toBeTrue();

    $npc->update(['last_birth_year' => 49]);
    expect($npc->refresh()->canHaveChild(50))->toBeFalse();

    $npc->update(['last_birth_year' => 48]);
    expect($npc->refresh()->canHaveChild(50))->toBeTrue();
});

test('dead npc cannot have children', function () {
    $npc = LocationNpc::factory()->female()->age(25, 50)->dead(45)->create();

    expect($npc->canHaveChild(50))->toBeFalse();
});

test('npc can have spouse relationship', function () {
    $husband = LocationNpc::factory()->male()->create();
    $wife = LocationNpc::factory()->female()->withSpouse($husband)->create();

    $husband->update(['spouse_id' => $wife->id]);

    expect($wife->spouse->id)->toBe($husband->id);
    expect($husband->refresh()->spouse->id)->toBe($wife->id);
});

test('npc can have parent relationships', function () {
    $parent1 = LocationNpc::factory()->female()->create();
    $parent2 = LocationNpc::factory()->male()->create();
    $child = LocationNpc::factory()->withParents($parent1, $parent2)->create();

    expect($child->parent1->id)->toBe($parent1->id);
    expect($child->parent2->id)->toBe($parent2->id);
});

test('npc can get all children', function () {
    $parent1 = LocationNpc::factory()->female()->create();
    $parent2 = LocationNpc::factory()->male()->create();

    $child1 = LocationNpc::factory()->withParents($parent1, $parent2)->create();
    $child2 = LocationNpc::factory()->withParents($parent1, $parent2)->create();

    $allChildren = $parent1->getAllChildren();

    expect($allChildren->count())->toBe(2);
    expect($allChildren->pluck('id')->toArray())->toContain($child1->id, $child2->id);
});

test('first name can be generated for male and female', function () {
    $maleName = LocationNpc::generateFirstName('male');
    $femaleName = LocationNpc::generateFirstName('female');

    expect($maleName)->toBeString();
    expect($femaleName)->toBeString();
    expect(strlen($maleName))->toBeGreaterThan(0);
    expect(strlen($femaleName))->toBeGreaterThan(0);
});

test('unmarried scope filters correctly', function () {
    LocationNpc::factory()->create(['spouse_id' => null]);
    $married1 = LocationNpc::factory()->create();
    $married2 = LocationNpc::factory()->withSpouse($married1)->create();
    $married1->update(['spouse_id' => $married2->id]);

    expect(LocationNpc::unmarried()->count())->toBe(1);
});

test('married scope filters correctly', function () {
    LocationNpc::factory()->create(['spouse_id' => null]);
    $married1 = LocationNpc::factory()->create();
    $married2 = LocationNpc::factory()->withSpouse($married1)->create();
    $married1->update(['spouse_id' => $married2->id]);

    expect(LocationNpc::married()->count())->toBe(2);
});

test('reproductive age scope filters correctly', function () {
    $currentYear = 50;

    LocationNpc::factory()->age(15, $currentYear)->female()->create(); // Too young
    LocationNpc::factory()->age(25, $currentYear)->female()->create(); // Good
    LocationNpc::factory()->age(50, $currentYear)->female()->create(); // Too old (female)
    LocationNpc::factory()->age(50, $currentYear)->male()->create(); // Still good (male)

    expect(LocationNpc::ofReproductiveAge($currentYear)->count())->toBe(2);
});

test('can reproduce scope filters correctly', function () {
    $currentYear = 50;

    // Can reproduce: 25 year old, no last birth
    LocationNpc::factory()->age(25, $currentYear)->female()->create([
        'last_birth_year' => null,
    ]);

    // Cannot: on cooldown
    LocationNpc::factory()->age(25, $currentYear)->female()->create([
        'last_birth_year' => 49,
    ]);

    // Cannot: dead
    LocationNpc::factory()->age(25, $currentYear)->female()->dead(45)->create();

    // Can: cooldown passed
    LocationNpc::factory()->age(25, $currentYear)->female()->create([
        'last_birth_year' => 47,
    ]);

    expect(LocationNpc::canReproduce($currentYear)->count())->toBe(2);
});

test('reproduction service processes marriages', function () {
    $currentYear = 50;

    // Create eligible unmarried NPCs at the same location
    LocationNpc::factory()->male()->age(25, $currentYear)->create([
        'location_type' => 'village',
        'location_id' => 1,
    ]);
    LocationNpc::factory()->female()->age(25, $currentYear)->create([
        'location_type' => 'village',
        'location_id' => 1,
    ]);

    $service = new NpcReproductionService();
    $results = $service->processYearlyReproduction();

    expect($results['marriages'])->toBe(1);
    expect(LocationNpc::married()->count())->toBe(2);
});

test('reproduction service does not marry npcs from different locations', function () {
    $currentYear = 50;

    // Create eligible unmarried NPCs at different locations
    LocationNpc::factory()->male()->age(25, $currentYear)->create([
        'location_type' => 'village',
        'location_id' => 1,
    ]);
    LocationNpc::factory()->female()->age(25, $currentYear)->create([
        'location_type' => 'village',
        'location_id' => 2,
    ]);

    $service = new NpcReproductionService();
    $results = $service->processYearlyReproduction();

    expect($results['marriages'])->toBe(0);
    expect(LocationNpc::married()->count())->toBe(0);
});

test('reproduction service creates children for married couples', function () {
    $currentYear = 50;

    // Create a married couple
    $husband = LocationNpc::factory()->male()->age(30, $currentYear)->create([
        'location_type' => 'village',
        'location_id' => 1,
    ]);
    $wife = LocationNpc::factory()->female()->age(28, $currentYear)->create([
        'location_type' => 'village',
        'location_id' => 1,
    ]);
    $husband->marry($wife);

    // Run reproduction multiple times to statistically get a child
    $service = new NpcReproductionService();
    $totalBirths = 0;
    for ($i = 0; $i < 20; $i++) {
        // Reset cooldowns between iterations
        $husband->update(['last_birth_year' => null]);
        $wife->update(['last_birth_year' => null]);

        $results = $service->processYearlyReproduction();
        $totalBirths += $results['births'];

        if ($totalBirths > 0) {
            break;
        }
    }

    // With 30% base rate over 20 tries, we should get at least one birth
    expect($totalBirths)->toBeGreaterThan(0);

    // Verify child was created correctly
    $child = LocationNpc::whereNotNull('parent1_id')->first();
    expect($child)->not()->toBeNull();
    expect($child->parent1_id)->toBeIn([$husband->id, $wife->id]);
    expect($child->parent2_id)->toBeIn([$husband->id, $wife->id]);
    expect($child->birth_year)->toBe($currentYear);
    expect($child->location_type)->toBe('village');
    expect($child->location_id)->toBe(1);
    expect($child->is_active)->toBeFalse();
});

test('child inherits family name from father', function () {
    $currentYear = 50;

    $husband = LocationNpc::factory()->male()->age(30, $currentYear)->create([
        'family_name' => 'Fathername',
        'location_type' => 'village',
        'location_id' => 1,
    ]);
    $wife = LocationNpc::factory()->female()->age(28, $currentYear)->create([
        'family_name' => 'Mothername',
        'location_type' => 'village',
        'location_id' => 1,
    ]);
    $husband->marry($wife);

    $service = new NpcReproductionService();

    // Force a birth by calling the protected method via reflection
    $reflection = new ReflectionClass($service);
    $method = $reflection->getMethod('createChild');
    $method->setAccessible(true);
    $child = $method->invoke($service, $wife, $husband, $currentYear);

    expect($child->family_name)->toBe('Fathername');
});

test('reproduction updates last birth year for both parents', function () {
    $currentYear = 50;

    $husband = LocationNpc::factory()->male()->age(30, $currentYear)->create([
        'location_type' => 'village',
        'location_id' => 1,
        'last_birth_year' => null,
    ]);
    $wife = LocationNpc::factory()->female()->age(28, $currentYear)->create([
        'location_type' => 'village',
        'location_id' => 1,
        'last_birth_year' => null,
    ]);
    $husband->marry($wife);

    $service = new NpcReproductionService();

    // Force a birth
    $reflection = new ReflectionClass($service);
    $method = $reflection->getMethod('createChild');
    $method->setAccessible(true);
    $method->invoke($service, $wife, $husband, $currentYear);

    expect($wife->refresh()->last_birth_year)->toBe($currentYear);
    expect($husband->refresh()->last_birth_year)->toBe($currentYear);
});

test('reproduction service returns statistics', function () {
    $service = new NpcReproductionService();
    $stats = $service->getReproductionStatistics();

    expect($stats)->toBeArray();
    expect($stats)->toHaveKeys(['total_living', 'married_couples', 'eligible_for_reproduction', 'children_born_this_year', 'current_year']);
});

test('calendar service dispatches reproduction job on new year', function () {
    WorldState::query()->delete();
    WorldState::factory()->create([
        'current_year' => 1,
        'current_season' => 'winter',
        'current_week' => 12,
    ]);

    $service = new CalendarService();

    \Illuminate\Support\Facades\Queue::fake();

    $service->advanceWeek();

    $state = WorldState::current();
    expect($state->current_year)->toBe(2);

    \Illuminate\Support\Facades\Queue::assertPushed(\App\Jobs\ProcessNpcReproduction::class);
});

test('calendar service does not dispatch reproduction job on regular week advance', function () {
    WorldState::query()->delete();
    WorldState::factory()->create([
        'current_year' => 1,
        'current_season' => 'spring',
        'current_week' => 5,
    ]);

    $service = new CalendarService();

    \Illuminate\Support\Facades\Queue::fake();

    $service->advanceWeek();

    \Illuminate\Support\Facades\Queue::assertNotPushed(\App\Jobs\ProcessNpcReproduction::class);
});

test('couple does not exceed max children', function () {
    $currentYear = 50;

    $husband = LocationNpc::factory()->male()->age(30, $currentYear)->create([
        'location_type' => 'village',
        'location_id' => 1,
    ]);
    $wife = LocationNpc::factory()->female()->age(28, $currentYear)->create([
        'location_type' => 'village',
        'location_id' => 1,
    ]);
    $husband->marry($wife);

    // Create max children
    for ($i = 0; $i < NpcReproductionService::MAX_CHILDREN; $i++) {
        LocationNpc::factory()->withParents($wife, $husband)->create();
    }

    // Reset cooldowns
    $husband->update(['last_birth_year' => null]);
    $wife->update(['last_birth_year' => null]);

    // Try to have more children
    $service = new NpcReproductionService();
    $results = $service->processYearlyReproduction();

    // Should not have any new births
    expect($results['births'])->toBe(0);
});
