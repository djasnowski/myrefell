<?php

use App\Models\User;
use App\Models\WorldState;
use App\Services\CalendarService;

beforeEach(function () {
    // Clear any existing world state
    WorldState::query()->delete();
});

test('guests are redirected from calendar page', function () {
    $this->get(route('calendar.index'))->assertRedirect(route('login'));
});

test('authenticated users can visit the calendar page', function () {
    $this->actingAs($user = User::factory()->create());

    $this->get(route('calendar.index'))->assertOk();
});

test('world state is created if it does not exist', function () {
    expect(WorldState::count())->toBe(0);

    $state = WorldState::current();

    expect(WorldState::count())->toBe(1);
    expect($state->current_year)->toBe(1);
    expect($state->current_season)->toBe('spring');
    expect($state->current_week)->toBe(1);
});

test('world state returns existing state if it exists', function () {
    WorldState::factory()->create([
        'current_year' => 5,
        'current_season' => 'winter',
        'current_week' => 10,
    ]);

    $state = WorldState::current();

    expect($state->current_year)->toBe(5);
    expect($state->current_season)->toBe('winter');
    expect($state->current_week)->toBe(10);
});

test('calendar service advances week correctly', function () {
    $service = new CalendarService();
    $state = WorldState::current();

    expect($state->current_week)->toBe(1);

    $service->advanceWeek();

    $state->refresh();
    expect($state->current_week)->toBe(2);
});

test('calendar service advances season after 12 weeks', function () {
    $state = WorldState::factory()->create([
        'current_year' => 1,
        'current_season' => 'spring',
        'current_week' => 12,
    ]);

    $service = new CalendarService();
    $service->advanceWeek();

    $state->refresh();
    expect($state->current_season)->toBe('summer');
    expect($state->current_week)->toBe(1);
});

test('calendar service advances year after winter', function () {
    $state = WorldState::factory()->create([
        'current_year' => 1,
        'current_season' => 'winter',
        'current_week' => 12,
    ]);

    $service = new CalendarService();
    $service->advanceWeek();

    $state->refresh();
    expect($state->current_year)->toBe(2);
    expect($state->current_season)->toBe('spring');
    expect($state->current_week)->toBe(1);
});

test('world state returns correct travel modifier by season', function () {
    $state = WorldState::factory()->spring()->create();
    expect($state->getTravelModifier())->toBe(1.2);

    WorldState::query()->delete();
    $state = WorldState::factory()->summer()->create();
    expect($state->getTravelModifier())->toBe(0.9);

    WorldState::query()->delete();
    $state = WorldState::factory()->autumn()->create();
    expect($state->getTravelModifier())->toBe(1.0);

    WorldState::query()->delete();
    $state = WorldState::factory()->winter()->create();
    expect($state->getTravelModifier())->toBe(1.3);
});

test('world state returns correct gathering modifier by season', function () {
    $state = WorldState::factory()->spring()->create();
    expect($state->getGatheringModifier())->toBe(0.8);

    WorldState::query()->delete();
    $state = WorldState::factory()->summer()->create();
    expect($state->getGatheringModifier())->toBe(1.0);

    WorldState::query()->delete();
    $state = WorldState::factory()->autumn()->create();
    expect($state->getGatheringModifier())->toBe(1.3);

    WorldState::query()->delete();
    $state = WorldState::factory()->winter()->create();
    expect($state->getGatheringModifier())->toBe(0.5);
});

test('world state formats date correctly', function () {
    $state = WorldState::factory()->create([
        'current_year' => 3,
        'current_season' => 'autumn',
        'current_week' => 7,
    ]);

    expect($state->getFormattedDate())->toBe('Week 7 of Autumn, Year 3');
});

test('world state calculates total week of year correctly', function () {
    // Spring week 1 = week 1 of year
    $state = WorldState::factory()->create([
        'current_season' => 'spring',
        'current_week' => 1,
    ]);
    expect($state->getTotalWeekOfYear())->toBe(1);

    // Summer week 5 = week 17 of year (12 + 5)
    WorldState::query()->delete();
    $state = WorldState::factory()->create([
        'current_season' => 'summer',
        'current_week' => 5,
    ]);
    expect($state->getTotalWeekOfYear())->toBe(17);

    // Autumn week 10 = week 34 of year (24 + 10)
    WorldState::query()->delete();
    $state = WorldState::factory()->create([
        'current_season' => 'autumn',
        'current_week' => 10,
    ]);
    expect($state->getTotalWeekOfYear())->toBe(34);

    // Winter week 12 = week 48 of year (36 + 12)
    WorldState::query()->delete();
    $state = WorldState::factory()->create([
        'current_season' => 'winter',
        'current_week' => 12,
    ]);
    expect($state->getTotalWeekOfYear())->toBe(48);
});

test('calendar service can set date directly', function () {
    $service = new CalendarService();

    $state = $service->setDate(5, 'autumn', 8);

    expect($state->current_year)->toBe(5);
    expect($state->current_season)->toBe('autumn');
    expect($state->current_week)->toBe(8);
});

test('calendar service validates date parameters', function () {
    $service = new CalendarService();

    expect(fn () => $service->setDate(0, 'spring', 1))
        ->toThrow(\InvalidArgumentException::class, 'Year must be at least 1.');

    expect(fn () => $service->setDate(1, 'invalid', 1))
        ->toThrow(\InvalidArgumentException::class, 'Invalid season');

    expect(fn () => $service->setDate(1, 'spring', 0))
        ->toThrow(\InvalidArgumentException::class, 'Week must be between 1 and 12');

    expect(fn () => $service->setDate(1, 'spring', 13))
        ->toThrow(\InvalidArgumentException::class, 'Week must be between 1 and 12');
});

test('calendar service returns correct calendar data', function () {
    WorldState::factory()->create([
        'current_year' => 2,
        'current_season' => 'summer',
        'current_week' => 6,
    ]);

    $service = new CalendarService();
    $data = $service->getCalendarData();

    expect($data['year'])->toBe(2);
    expect($data['season'])->toBe('summer');
    expect($data['week'])->toBe(6);
    expect($data['week_of_year'])->toBe(18);
    expect($data['travel_modifier'])->toBe(0.9);
    expect($data['gathering_modifier'])->toBe(1.0);
    expect($data['formatted_date'])->toBe('Week 6 of Summer, Year 2');
});

test('world state provides correct season descriptions', function () {
    $state = WorldState::factory()->spring()->create();
    expect($state->getSeasonDescription())->toContain('Planting season');

    WorldState::query()->delete();
    $state = WorldState::factory()->summer()->create();
    expect($state->getSeasonDescription())->toContain('Growing season');

    WorldState::query()->delete();
    $state = WorldState::factory()->autumn()->create();
    expect($state->getSeasonDescription())->toContain('Harvest season');

    WorldState::query()->delete();
    $state = WorldState::factory()->winter()->create();
    expect($state->getSeasonDescription())->toContain('Famine risk');
});

test('world state season check methods work correctly', function () {
    $state = WorldState::factory()->spring()->create();
    expect($state->isSpring())->toBeTrue();
    expect($state->isSummer())->toBeFalse();
    expect($state->isAutumn())->toBeFalse();
    expect($state->isWinter())->toBeFalse();

    WorldState::query()->delete();
    $state = WorldState::factory()->winter()->create();
    expect($state->isSpring())->toBeFalse();
    expect($state->isSummer())->toBeFalse();
    expect($state->isAutumn())->toBeFalse();
    expect($state->isWinter())->toBeTrue();
});
