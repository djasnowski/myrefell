<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorldState extends Model
{
    use HasFactory;

    protected $table = 'world_state';

    public const DAYS_PER_WEEK = 7;

    protected $fillable = [
        'current_year',
        'current_season',
        'current_week',
        'current_day',
        'last_tick_at',
    ];

    protected function casts(): array
    {
        return [
            'current_year' => 'integer',
            'current_week' => 'integer',
            'current_day' => 'integer',
            'last_tick_at' => 'datetime',
        ];
    }

    /**
     * Seasons in order.
     */
    public const SEASONS = ['spring', 'summer', 'autumn', 'winter'];

    /**
     * Weeks per season.
     */
    public const WEEKS_PER_SEASON = 13;

    /**
     * Total weeks per year.
     */
    public const WEEKS_PER_YEAR = 52;

    /**
     * Travel time modifiers by season.
     * < 1.0 = faster travel, > 1.0 = slower travel
     */
    public const SEASON_TRAVEL_MODIFIERS = [
        'spring' => 1.2,  // Muddy roads, slower travel
        'summer' => 0.9,  // Best weather, fastest travel
        'autumn' => 1.0,  // Fair weather, normal travel
        'winter' => 1.3,  // Frozen/difficult roads, slowest travel
    ];

    /**
     * Gathering yield modifiers by season.
     * These multiply the base yield of gathering activities.
     */
    public const SEASON_GATHERING_MODIFIERS = [
        'spring' => 0.8,  // Plants growing, fewer harvestable
        'summer' => 1.0,  // Normal yields
        'autumn' => 1.3,  // Harvest season, best yields
        'winter' => 0.5,  // Scarce resources
    ];

    /**
     * Get the singleton world state (creates if not exists).
     */
    public static function current(): self
    {
        $state = self::first();

        if (! $state) {
            $state = self::create([
                'current_year' => 1,
                'current_season' => 'spring',
                'current_week' => 1,
                'current_day' => 1,
                'last_tick_at' => now(),
            ]);
        }

        return $state;
    }

    /**
     * Get the current season's travel modifier.
     */
    public function getTravelModifier(): float
    {
        return self::SEASON_TRAVEL_MODIFIERS[$this->current_season] ?? 1.0;
    }

    /**
     * Get the current season's gathering modifier.
     */
    public function getGatheringModifier(): float
    {
        return self::SEASON_GATHERING_MODIFIERS[$this->current_season] ?? 1.0;
    }

    /**
     * Get the season index (0-3).
     */
    public function getSeasonIndex(): int
    {
        return array_search($this->current_season, self::SEASONS, true);
    }

    /**
     * Get the total week number within the year (1-52).
     */
    public function getTotalWeekOfYear(): int
    {
        return ($this->getSeasonIndex() * self::WEEKS_PER_SEASON) + $this->current_week;
    }

    /**
     * Get the total day number within the year (1-364).
     */
    public function getTotalDayOfYear(): int
    {
        return (($this->getTotalWeekOfYear() - 1) * self::DAYS_PER_WEEK) + $this->current_day;
    }

    /**
     * Check if it's currently spring.
     */
    public function isSpring(): bool
    {
        return $this->current_season === 'spring';
    }

    /**
     * Check if it's currently summer.
     */
    public function isSummer(): bool
    {
        return $this->current_season === 'summer';
    }

    /**
     * Check if it's currently autumn.
     */
    public function isAutumn(): bool
    {
        return $this->current_season === 'autumn';
    }

    /**
     * Check if it's currently winter.
     */
    public function isWinter(): bool
    {
        return $this->current_season === 'winter';
    }

    /**
     * Get the formatted date string (e.g., "Day 9, Week 2 of Spring, Year 1").
     */
    public function getFormattedDate(): string
    {
        $seasonName = ucfirst($this->current_season);

        return "Day {$this->getTotalDayOfYear()}, Week {$this->current_week} of {$seasonName}, Year {$this->current_year}";
    }

    /**
     * Get season description with effects.
     */
    public function getSeasonDescription(): string
    {
        return match ($this->current_season) {
            'spring' => 'Planting season. Muddy roads slow travel. New growth begins.',
            'summer' => 'Growing season. Clear skies make for fast travel. Drought risk.',
            'autumn' => 'Harvest season. Trade caravans active. Best gathering yields.',
            'winter' => 'Famine risk. Frozen roads slow travel. Resources are scarce.',
        };
    }
}
