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
    $service = new CalendarService;
    $state = WorldState::current();

    expect($state->current_week)->toBe(1);

    // Advance through a full week (DAYS_PER_WEEK days)
    for ($i = 0; $i < WorldState::DAYS_PER_WEEK; $i++) {
        $service->advanceDay();
    }

    $state->refresh();
    expect($state->current_week)->toBe(2);
});

test('calendar service advances season after max weeks', function () {
    $state = WorldState::factory()->create([
        'current_year' => 1,
        'current_season' => 'spring',
        'current_week' => WorldState::WEEKS_PER_SEASON,
        'current_day' => WorldState::DAYS_PER_WEEK,
    ]);

    $service = new CalendarService;
    $service->advanceDay();

    $state->refresh();
    expect($state->current_season)->toBe('summer');
    expect($state->current_week)->toBe(1);
});

test('calendar service advances year after winter', function () {
    $state = WorldState::factory()->create([
        'current_year' => 1,
        'current_season' => 'winter',
        'current_week' => WorldState::WEEKS_PER_SEASON,
        'current_day' => WorldState::DAYS_PER_WEEK,
    ]);

    $service = new CalendarService;
    $service->advanceDay();

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
        'current_day' => 1,
    ]);

    $dayOfYear = $state->getTotalDayOfYear();

    expect($state->getFormattedDate())->toBe("Day {$dayOfYear}, Week 7 of Autumn, Year 3");
});

test('world state calculates total week of year correctly', function () {
    $weeksPerSeason = WorldState::WEEKS_PER_SEASON;

    // Spring week 1 = week 1 of year
    $state = WorldState::factory()->create([
        'current_season' => 'spring',
        'current_week' => 1,
    ]);
    expect($state->getTotalWeekOfYear())->toBe(1);

    // Summer week 5 = week (WEEKS_PER_SEASON + 5) of year
    WorldState::query()->delete();
    $state = WorldState::factory()->create([
        'current_season' => 'summer',
        'current_week' => 5,
    ]);
    expect($state->getTotalWeekOfYear())->toBe($weeksPerSeason + 5);

    // Autumn week 10 = week (2 * WEEKS_PER_SEASON + 10) of year
    WorldState::query()->delete();
    $state = WorldState::factory()->create([
        'current_season' => 'autumn',
        'current_week' => 10,
    ]);
    expect($state->getTotalWeekOfYear())->toBe(2 * $weeksPerSeason + 10);

    // Winter week 12 = week (3 * WEEKS_PER_SEASON + 12) of year
    WorldState::query()->delete();
    $state = WorldState::factory()->create([
        'current_season' => 'winter',
        'current_week' => 12,
    ]);
    expect($state->getTotalWeekOfYear())->toBe(3 * $weeksPerSeason + 12);
});

test('calendar service can set date directly', function () {
    $service = new CalendarService;

    $state = $service->setDate(5, 'autumn', 8);

    expect($state->current_year)->toBe(5);
    expect($state->current_season)->toBe('autumn');
    expect($state->current_week)->toBe(8);
});

test('calendar service validates date parameters', function () {
    $service = new CalendarService;
    $maxWeek = WorldState::WEEKS_PER_SEASON;

    expect(fn () => $service->setDate(0, 'spring', 1))
        ->toThrow(\InvalidArgumentException::class, 'Year must be at least 1.');

    expect(fn () => $service->setDate(1, 'invalid', 1))
        ->toThrow(\InvalidArgumentException::class, 'Invalid season');

    expect(fn () => $service->setDate(1, 'spring', 0))
        ->toThrow(\InvalidArgumentException::class, "Week must be between 1 and {$maxWeek}");

    expect(fn () => $service->setDate(1, 'spring', $maxWeek + 1))
        ->toThrow(\InvalidArgumentException::class, "Week must be between 1 and {$maxWeek}");
});

test('calendar service returns correct calendar data', function () {
    WorldState::factory()->create([
        'current_year' => 2,
        'current_season' => 'summer',
        'current_week' => 6,
        'current_day' => 1,
    ]);

    $service = new CalendarService;
    $data = $service->getCalendarData();

    $expectedWeekOfYear = WorldState::WEEKS_PER_SEASON + 6;

    expect($data['year'])->toBe(2);
    expect($data['season'])->toBe('summer');
    expect($data['week'])->toBe(6);
    expect($data['week_of_year'])->toBe($expectedWeekOfYear);
    expect($data['travel_modifier'])->toBe(0.9);
    expect($data['gathering_modifier'])->toBe(1.0);
    expect($data['formatted_date'])->toContain('Week 6 of Summer, Year 2');
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
