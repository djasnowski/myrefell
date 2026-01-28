<?php

use App\Models\WorldState;
use App\Services\CalendarService;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    $this->service = app(CalendarService::class);
    Queue::fake(); // Prevent jobs from actually running
});

describe('getCurrentState', function () {
    test('creates world state if none exists', function () {
        expect(WorldState::count())->toBe(0);

        $state = $this->service->getCurrentState();

        expect($state)->toBeInstanceOf(WorldState::class);
        expect(WorldState::count())->toBe(1);
    });

    test('returns existing world state', function () {
        WorldState::create([
            'current_year' => 5,
            'current_season' => 'winter',
            'current_week' => 8,
        ]);

        $state = $this->service->getCurrentState();

        expect($state->current_year)->toBe(5);
        expect($state->current_season)->toBe('winter');
        expect($state->current_week)->toBe(8);
    });
});

describe('setDate', function () {
    test('sets date correctly', function () {
        $state = $this->service->setDate(3, 'autumn', 7);

        expect($state->current_year)->toBe(3);
        expect($state->current_season)->toBe('autumn');
        expect($state->current_week)->toBe(7);
    });

    test('throws exception for invalid year', function () {
        $this->service->setDate(0, 'spring', 1);
    })->throws(InvalidArgumentException::class, 'Year must be at least 1');

    test('throws exception for invalid season', function () {
        $this->service->setDate(1, 'monsoon', 1);
    })->throws(InvalidArgumentException::class, 'Invalid season');

    test('throws exception for week below range', function () {
        $this->service->setDate(1, 'spring', 0);
    })->throws(InvalidArgumentException::class, 'Week must be between 1');

    test('throws exception for week above range', function () {
        $this->service->setDate(1, 'spring', 13);
    })->throws(InvalidArgumentException::class, 'Week must be between 1');

    test('updates last tick time', function () {
        $state = $this->service->setDate(2, 'summer', 5);

        expect($state->last_tick_at)->not->toBeNull();
    });
});

describe('advanceWeek', function () {
    test('increments week within season', function () {
        WorldState::create([
            'current_year' => 1,
            'current_season' => 'spring',
            'current_week' => 5,
        ]);

        $state = $this->service->advanceWeek();

        expect($state->current_year)->toBe(1);
        expect($state->current_season)->toBe('spring');
        expect($state->current_week)->toBe(6);
    });

    test('advances to next season when week exceeds max', function () {
        WorldState::create([
            'current_year' => 1,
            'current_season' => 'spring',
            'current_week' => 12,
        ]);

        $state = $this->service->advanceWeek();

        expect($state->current_year)->toBe(1);
        expect($state->current_season)->toBe('summer');
        expect($state->current_week)->toBe(1);
    });

    test('advances year when transitioning from winter to spring', function () {
        WorldState::create([
            'current_year' => 1,
            'current_season' => 'winter',
            'current_week' => 12,
        ]);

        $state = $this->service->advanceWeek();

        expect($state->current_year)->toBe(2);
        expect($state->current_season)->toBe('spring');
        expect($state->current_week)->toBe(1);
    });

    test('progresses through all seasons correctly', function () {
        WorldState::create([
            'current_year' => 1,
            'current_season' => 'summer',
            'current_week' => 12,
        ]);

        // Summer -> Autumn
        $state = $this->service->advanceWeek();
        expect($state->current_season)->toBe('autumn');

        // Set to end of autumn
        $state->current_week = 12;
        $state->save();

        // Autumn -> Winter
        $state = $this->service->advanceWeek();
        expect($state->current_season)->toBe('winter');
        expect($state->current_year)->toBe(1); // Still year 1
    });
});

describe('shouldTick', function () {
    test('returns true when no last tick', function () {
        WorldState::create([
            'current_year' => 1,
            'current_season' => 'spring',
            'current_week' => 1,
            'last_tick_at' => null,
        ]);

        expect($this->service->shouldTick())->toBeTrue();
    });

    test('returns true when tick interval exceeded', function () {
        WorldState::create([
            'current_year' => 1,
            'current_season' => 'spring',
            'current_week' => 1,
            'last_tick_at' => now()->subDays(2), // 2 days ago
        ]);

        expect($this->service->shouldTick())->toBeTrue();
    });

    test('returns false when within tick interval', function () {
        WorldState::create([
            'current_year' => 1,
            'current_season' => 'spring',
            'current_week' => 1,
            'last_tick_at' => now()->subHours(12), // 12 hours ago
        ]);

        expect($this->service->shouldTick())->toBeFalse();
    });
});

describe('processTick', function () {
    test('processes tick when should tick', function () {
        WorldState::create([
            'current_year' => 1,
            'current_season' => 'spring',
            'current_week' => 1,
            'last_tick_at' => null,
        ]);

        $result = $this->service->processTick();

        expect($result)->toBeTrue();
        expect(WorldState::first()->current_week)->toBe(2);
    });

    test('does not process tick when not needed', function () {
        WorldState::create([
            'current_year' => 1,
            'current_season' => 'spring',
            'current_week' => 1,
            'last_tick_at' => now(),
        ]);

        $result = $this->service->processTick();

        expect($result)->toBeFalse();
        expect(WorldState::first()->current_week)->toBe(1);
    });
});

describe('getTravelModifier', function () {
    test('returns correct modifier for spring', function () {
        WorldState::create([
            'current_year' => 1,
            'current_season' => 'spring',
            'current_week' => 1,
        ]);

        expect($this->service->getTravelModifier())->toBe(1.2);
    });

    test('returns correct modifier for summer', function () {
        WorldState::create([
            'current_year' => 1,
            'current_season' => 'summer',
            'current_week' => 1,
        ]);

        expect($this->service->getTravelModifier())->toBe(0.9);
    });

    test('returns correct modifier for autumn', function () {
        WorldState::create([
            'current_year' => 1,
            'current_season' => 'autumn',
            'current_week' => 1,
        ]);

        expect($this->service->getTravelModifier())->toBe(1.0);
    });

    test('returns correct modifier for winter', function () {
        WorldState::create([
            'current_year' => 1,
            'current_season' => 'winter',
            'current_week' => 1,
        ]);

        expect($this->service->getTravelModifier())->toBe(1.3);
    });
});

describe('getGatheringModifier', function () {
    test('returns correct modifier for spring', function () {
        WorldState::create([
            'current_year' => 1,
            'current_season' => 'spring',
            'current_week' => 1,
        ]);

        expect($this->service->getGatheringModifier())->toBe(0.8);
    });

    test('returns correct modifier for summer', function () {
        WorldState::create([
            'current_year' => 1,
            'current_season' => 'summer',
            'current_week' => 1,
        ]);

        expect($this->service->getGatheringModifier())->toBe(1.0);
    });

    test('returns correct modifier for autumn', function () {
        WorldState::create([
            'current_year' => 1,
            'current_season' => 'autumn',
            'current_week' => 1,
        ]);

        expect($this->service->getGatheringModifier())->toBe(1.3);
    });

    test('returns correct modifier for winter', function () {
        WorldState::create([
            'current_year' => 1,
            'current_season' => 'winter',
            'current_week' => 1,
        ]);

        expect($this->service->getGatheringModifier())->toBe(0.5);
    });
});

describe('getCalendarData', function () {
    test('returns complete calendar data', function () {
        WorldState::create([
            'current_year' => 2,
            'current_season' => 'summer',
            'current_week' => 5,
            'last_tick_at' => now(),
        ]);

        $data = $this->service->getCalendarData();

        expect($data['year'])->toBe(2);
        expect($data['season'])->toBe('summer');
        expect($data['week'])->toBe(5);
        expect($data['week_of_year'])->toBe(17); // (1 * 12) + 5
        expect($data['formatted_date'])->toBe('Week 5 of Summer, Year 2');
        expect($data['travel_modifier'])->toBe(0.9);
        expect($data['gathering_modifier'])->toBe(1.0);
        expect($data['last_tick_at'])->not->toBeNull();
    });

    test('calculates week of year correctly for different seasons', function () {
        WorldState::create([
            'current_year' => 1,
            'current_season' => 'autumn',
            'current_week' => 3,
        ]);

        $data = $this->service->getCalendarData();

        // Spring (0 * 12) + Summer (1 * 12) + week 3 = 27
        expect($data['week_of_year'])->toBe(27);
    });
});
