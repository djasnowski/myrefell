<?php

namespace App\Services;

use App\Jobs\AgeNpcs;
use App\Jobs\ProcessFoodConsumption;
use App\Jobs\ProcessNpcReproduction;
use App\Models\WorldState;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CalendarService
{
    /**
     * Real-time interval between world ticks.
     * Default: 1 day = 1 game week.
     * This value is in seconds (86400 = 24 hours).
     */
    public const TICK_INTERVAL_SECONDS = 86400;

    /**
     * Get the current world state.
     */
    public function getCurrentState(): WorldState
    {
        return WorldState::current();
    }

    /**
     * Advance the world by one week.
     * Returns the updated world state.
     */
    public function advanceWeek(): WorldState
    {
        return DB::transaction(function () {
            $state = WorldState::current();

            $oldDate = $state->getFormattedDate();

            $state->current_week++;

            // Check if we need to advance to next season
            if ($state->current_week > WorldState::WEEKS_PER_SEASON) {
                $state->current_week = 1;

                $seasonIndex = $state->getSeasonIndex();
                $nextSeasonIndex = ($seasonIndex + 1) % 4;

                // If wrapping from winter to spring, advance year
                if ($nextSeasonIndex === 0) {
                    $state->current_year++;
                    Log::info("World time: Year {$state->current_year} has begun!");

                    // Trigger NPC lifecycle events on new year
                    AgeNpcs::dispatch();
                    ProcessNpcReproduction::dispatch();
                }

                $state->current_season = WorldState::SEASONS[$nextSeasonIndex];
                Log::info("World time: Season changed to {$state->current_season}");
            }

            $state->last_tick_at = now();
            $state->save();

            // Process food consumption every week
            ProcessFoodConsumption::dispatch();

            Log::info("World time advanced: {$oldDate} -> {$state->getFormattedDate()}");

            return $state;
        });
    }

    /**
     * Check if it's time for a world tick based on last tick time.
     */
    public function shouldTick(): bool
    {
        $state = WorldState::current();

        if (! $state->last_tick_at) {
            return true;
        }

        return $state->last_tick_at->diffInSeconds(now()) >= self::TICK_INTERVAL_SECONDS;
    }

    /**
     * Process a world tick if enough time has passed.
     * Returns true if a tick was processed.
     */
    public function processTick(): bool
    {
        if (! $this->shouldTick()) {
            return false;
        }

        $this->advanceWeek();

        return true;
    }

    /**
     * Force advance to a specific date (for testing/admin).
     */
    public function setDate(int $year, string $season, int $week): WorldState
    {
        if ($year < 1) {
            throw new \InvalidArgumentException('Year must be at least 1.');
        }

        if (! in_array($season, WorldState::SEASONS, true)) {
            throw new \InvalidArgumentException('Invalid season. Must be: '.implode(', ', WorldState::SEASONS));
        }

        if ($week < 1 || $week > WorldState::WEEKS_PER_SEASON) {
            throw new \InvalidArgumentException('Week must be between 1 and '.WorldState::WEEKS_PER_SEASON);
        }

        return DB::transaction(function () use ($year, $season, $week) {
            $state = WorldState::current();

            $state->current_year = $year;
            $state->current_season = $season;
            $state->current_week = $week;
            $state->last_tick_at = now();
            $state->save();

            Log::info("World time set to: {$state->getFormattedDate()}");

            return $state;
        });
    }

    /**
     * Get the current travel time modifier based on season.
     */
    public function getTravelModifier(): float
    {
        return WorldState::current()->getTravelModifier();
    }

    /**
     * Get the current gathering yield modifier based on season.
     */
    public function getGatheringModifier(): float
    {
        return WorldState::current()->getGatheringModifier();
    }

    /**
     * Get the full calendar state for display.
     */
    public function getCalendarData(): array
    {
        $state = WorldState::current();

        return [
            'year' => $state->current_year,
            'season' => $state->current_season,
            'week' => $state->current_week,
            'week_of_year' => $state->getTotalWeekOfYear(),
            'formatted_date' => $state->getFormattedDate(),
            'season_description' => $state->getSeasonDescription(),
            'travel_modifier' => $state->getTravelModifier(),
            'gathering_modifier' => $state->getGatheringModifier(),
            'last_tick_at' => $state->last_tick_at?->toIso8601String(),
        ];
    }
}
