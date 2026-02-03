<?php

namespace App\Services;

use App\Models\Item;
use App\Models\LocationNpc;
use App\Models\LocationStockpile;
use App\Models\Town;
use App\Models\User;
use App\Models\Village;
use App\Models\WorldState;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FoodConsumptionService
{
    /**
     * Default food item name used for adding food to stockpiles (e.g., from farming).
     */
    public const FOOD_ITEM_NAME = 'Grain';

    /**
     * Subtypes that are considered food for consumption purposes.
     */
    public const FOOD_SUBTYPES = ['food', 'crop', 'grain'];

    /**
     * Baseline: Number of people fed by 1 food item with food_value of 1.
     * Used as a reference point - actual feeding depends on item's food_value.
     */
    public const PEOPLE_FED_PER_FOOD = 4;

    /**
     * Maximum weeks without food before death.
     */
    public const MAX_WEEKS_WITHOUT_FOOD = 4;

    /**
     * Weeks without food before emigration chance increases.
     */
    public const WEEKS_BEFORE_EMIGRATION = 2;

    /**
     * Base chance of emigration per week when starving (percentage).
     */
    public const EMIGRATION_CHANCE_PER_WEEK = 10;

    /**
     * Process weekly food consumption for all villages and towns.
     * Called each game week (each real day).
     *
     * @return array{villages_processed: int, towns_processed: int, food_consumed: int, npcs_starving: int, npcs_died: int, npcs_emigrated: int, players_starving: int, players_penalized: int}
     */
    public function processWeeklyConsumption(): array
    {
        $worldState = WorldState::current();
        $currentWeek = $worldState->getTotalWeekOfYear();
        $currentYear = $worldState->current_year;

        $results = [
            'villages_processed' => 0,
            'towns_processed' => 0,
            'food_consumed' => 0,
            'npcs_starving' => 0,
            'npcs_died' => 0,
            'npcs_emigrated' => 0,
            'players_starving' => 0,
            'players_penalized' => 0,
        ];

        // Process each village
        $villages = Village::all();
        foreach ($villages as $village) {
            $villageResults = $this->processVillageConsumption($village, $currentYear);

            $results['villages_processed']++;
            $results['food_consumed'] += $villageResults['food_consumed'];
            $results['npcs_starving'] += $villageResults['npcs_starving'];
            $results['npcs_died'] += $villageResults['npcs_died'];
            $results['npcs_emigrated'] += $villageResults['npcs_emigrated'];
            $results['players_starving'] += $villageResults['players_starving'];
            $results['players_penalized'] += $villageResults['players_penalized'];
        }

        // Process each town
        $towns = Town::all();
        foreach ($towns as $town) {
            $townResults = $this->processTownConsumption($town, $currentYear);

            $results['towns_processed']++;
            $results['food_consumed'] += $townResults['food_consumed'];
            $results['npcs_starving'] += $townResults['npcs_starving'];
            $results['npcs_died'] += $townResults['npcs_died'];
            $results['npcs_emigrated'] += $townResults['npcs_emigrated'];
            $results['players_starving'] += $townResults['players_starving'];
            $results['players_penalized'] += $townResults['players_penalized'];
        }

        Log::info('Weekly food consumption processed', [
            'year' => $currentYear,
            'week' => $currentWeek,
            'villages_processed' => $results['villages_processed'],
            'towns_processed' => $results['towns_processed'],
            'food_consumed' => $results['food_consumed'],
            'npcs_starving' => $results['npcs_starving'],
            'npcs_died' => $results['npcs_died'],
            'npcs_emigrated' => $results['npcs_emigrated'],
            'players_starving' => $results['players_starving'],
            'players_penalized' => $results['players_penalized'],
        ]);

        return $results;
    }

    /**
     * Process food consumption for a single village.
     *
     * @return array{food_consumed: int, npcs_starving: int, npcs_died: int, npcs_emigrated: int, players_starving: int, players_penalized: int}
     */
    protected function processVillageConsumption(Village $village, int $currentYear): array
    {
        return DB::transaction(function () use ($village, $currentYear) {
            $results = [
                'food_consumed' => 0,
                'npcs_starving' => 0,
                'npcs_died' => 0,
                'npcs_emigrated' => 0,
                'players_starving' => 0,
                'players_penalized' => 0,
            ];

            // Count population: living NPCs at this location + players residing here
            $npcCount = LocationNpc::alive()
                ->atLocation('village', $village->id)
                ->count();

            $playerCount = User::where('home_village_id', $village->id)->count();

            $totalPopulation = $npcCount + $playerCount;

            if ($totalPopulation === 0) {
                return $results;
            }

            // Consume food from multiple stockpiles until everyone is fed
            $consumptionResult = $this->consumeFoodFromStockpiles('village', $village->id, $totalPopulation);
            $results['food_consumed'] = $consumptionResult['food_consumed'];

            // If there's a shortage (not everyone was fed), apply starvation effects
            if ($consumptionResult['people_unfed'] > 0) {
                $npcResults = $this->applyNpcStarvation('village', $village, $currentYear);
                $results['npcs_starving'] = $npcResults['starving'];
                $results['npcs_died'] = $npcResults['died'];
                $results['npcs_emigrated'] = $npcResults['emigrated'];

                $playerResults = $this->applyPlayerStarvationAtLocation('village', $village->id);
                $results['players_starving'] = $playerResults['starving'];
                $results['players_penalized'] = $playerResults['penalized'];
            } else {
                // Food is sufficient, reset starvation counters
                $this->resetStarvationAtLocation('village', $village->id);
            }

            return $results;
        });
    }

    /**
     * Process food consumption for a single town.
     *
     * @return array{food_consumed: int, npcs_starving: int, npcs_died: int, npcs_emigrated: int, players_starving: int, players_penalized: int}
     */
    protected function processTownConsumption(Town $town, int $currentYear): array
    {
        return DB::transaction(function () use ($town, $currentYear) {
            $results = [
                'food_consumed' => 0,
                'npcs_starving' => 0,
                'npcs_died' => 0,
                'npcs_emigrated' => 0,
                'players_starving' => 0,
                'players_penalized' => 0,
            ];

            // Count population: living NPCs at this location + players with home town
            $npcCount = LocationNpc::alive()
                ->atLocation('town', $town->id)
                ->count();

            // Players residing at town (visiting doesn't count for food consumption)
            $playerCount = User::where('current_location_type', 'town')
                ->where('current_location_id', $town->id)
                ->where('home_village_id', null) // Players who live in the town
                ->count();

            $totalPopulation = $npcCount + $playerCount;

            if ($totalPopulation === 0) {
                return $results;
            }

            // Consume food from multiple stockpiles until everyone is fed
            $consumptionResult = $this->consumeFoodFromStockpiles('town', $town->id, $totalPopulation);
            $results['food_consumed'] = $consumptionResult['food_consumed'];

            // If there's a shortage (not everyone was fed), apply starvation effects
            if ($consumptionResult['people_unfed'] > 0) {
                $npcResults = $this->applyNpcStarvation('town', $town, $currentYear);
                $results['npcs_starving'] = $npcResults['starving'];
                $results['npcs_died'] = $npcResults['died'];
                $results['npcs_emigrated'] = $npcResults['emigrated'];

                $playerResults = $this->applyPlayerStarvationAtLocation('town', $town->id);
                $results['players_starving'] = $playerResults['starving'];
                $results['players_penalized'] = $playerResults['penalized'];
            } else {
                // Food is sufficient, reset starvation counters
                $this->resetStarvationAtLocation('town', $town->id);
            }

            return $results;
        });
    }

    /**
     * Get all food stockpiles at a location, ordered by food_value ASC (cheap food first).
     *
     * @return Collection<LocationStockpile>
     */
    protected function getFoodStockpiles(string $locationType, int $locationId): Collection
    {
        return LocationStockpile::atLocation($locationType, $locationId)
            ->where('quantity', '>', 0)
            ->whereHas('item', function ($query) {
                $query->whereIn('subtype', self::FOOD_SUBTYPES)
                    ->where('food_value', '>', 0);
            })
            ->with('item')
            ->get()
            ->sortBy(fn ($stockpile) => $stockpile->item->food_value);
    }

    /**
     * Consume food from multiple stockpiles to feed the population.
     * Consumes cheap food first (lowest food_value).
     *
     * @return array{food_consumed: int, people_fed: int, people_unfed: int}
     */
    protected function consumeFoodFromStockpiles(string $locationType, int $locationId, int $population): array
    {
        $result = [
            'food_consumed' => 0,
            'people_fed' => 0,
            'people_unfed' => $population,
        ];

        // Get all food stockpiles ordered by food_value (cheap food first)
        $stockpiles = $this->getFoodStockpiles($locationType, $locationId);

        foreach ($stockpiles as $stockpile) {
            if ($result['people_unfed'] <= 0) {
                break;
            }

            $foodValue = $stockpile->item->food_value;
            $available = $stockpile->quantity;

            // Each unit of this food feeds food_value people
            // Calculate how many units needed to feed remaining people
            $unitsNeeded = (int) ceil($result['people_unfed'] / $foodValue);
            $unitsToConsume = min($unitsNeeded, $available);

            if ($unitsToConsume > 0) {
                $stockpile->removeQuantity($unitsToConsume);
                $result['food_consumed'] += $unitsToConsume;

                $peopleFedFromThis = min($unitsToConsume * $foodValue, $result['people_unfed']);
                $result['people_fed'] += $peopleFedFromThis;
                $result['people_unfed'] -= $peopleFedFromThis;
            }
        }

        return $result;
    }

    /**
     * Apply starvation effects to NPCs at a location.
     *
     * @param  Village|Town  $location
     * @return array{starving: int, died: int, emigrated: int}
     */
    protected function applyNpcStarvation(string $locationType, $location, int $currentYear): array
    {
        $results = ['starving' => 0, 'died' => 0, 'emigrated' => 0];

        // Get living NPCs at this location
        $npcs = LocationNpc::alive()
            ->atLocation($locationType, $location->id)
            ->get();

        foreach ($npcs as $npc) {
            // Increment weeks without food
            $npc->increment('weeks_without_food');
            $results['starving']++;

            // Check for emigration first (after 2+ weeks, 10% chance per week)
            if ($npc->weeks_without_food >= self::WEEKS_BEFORE_EMIGRATION) {
                if (rand(1, 100) <= self::EMIGRATION_CHANCE_PER_WEEK) {
                    if ($this->processNpcEmigration($npc, $locationType, $location)) {
                        $results['emigrated']++;

                        continue; // NPC left, don't check for death
                    }
                }
            }

            // Check for death by starvation
            if ($npc->weeks_without_food >= self::MAX_WEEKS_WITHOUT_FOOD) {
                $this->handleNpcStarvationDeath($npc, $currentYear);
                $results['died']++;
            }
        }

        return $results;
    }

    /**
     * Process NPC emigration to a nearby location with food.
     *
     * @param  Village|Town  $fromLocation
     */
    protected function processNpcEmigration(LocationNpc $npc, string $fromType, $fromLocation): bool
    {
        $destination = $this->findEmigrationDestination($fromType, $fromLocation);

        if (! $destination) {
            return false;
        }

        // Move NPC to new location
        $npc->update([
            'location_type' => $destination['type'],
            'location_id' => $destination['id'],
            'weeks_without_food' => 0,
        ]);

        Log::info('NPC emigrated due to starvation', [
            'npc_id' => $npc->id,
            'npc_name' => $npc->npc_name,
            'from_type' => $fromType,
            'from_id' => $fromLocation->id,
            'from_name' => $fromLocation->name,
            'to_type' => $destination['type'],
            'to_id' => $destination['id'],
            'to_name' => $destination['name'],
        ]);

        return true;
    }

    /**
     * Calculate weeks of food available at a location based on all food stockpiles.
     */
    protected function calculateWeeksOfFood(string $locationType, int $locationId): int
    {
        $foodStockpiles = $this->getFoodStockpiles($locationType, $locationId);
        $totalFoodPoints = $foodStockpiles->sum(fn ($stockpile) => $stockpile->quantity * $stockpile->item->food_value);

        $npcCount = LocationNpc::alive()->atLocation($locationType, $locationId)->count();
        $population = max(1, $npcCount);

        return $population > 0 ? (int) floor($totalFoodPoints / $population) : 0;
    }

    /**
     * Find a suitable emigration destination for an NPC.
     *
     * @param  Village|Town  $fromLocation
     * @return array{type: string, id: int, name: string}|null
     */
    protected function findEmigrationDestination(string $fromType, $fromLocation): ?array
    {
        // Get the barony of the current location
        $baronyId = $fromLocation->barony_id;

        // First, search villages in the same barony with food
        $villagesWithFood = Village::where('id', '!=', $fromType === 'village' ? $fromLocation->id : 0)
            ->when($baronyId, fn ($q) => $q->where('barony_id', $baronyId))
            ->get()
            ->filter(function ($village) {
                // Only consider as destination if has at least 10 weeks of food
                return $this->calculateWeeksOfFood('village', $village->id) >= 10;
            });

        if ($villagesWithFood->isNotEmpty()) {
            // Pick the closest (by coordinates) or random
            $village = $this->findClosestLocation($villagesWithFood, $fromLocation);

            return [
                'type' => 'village',
                'id' => $village->id,
                'name' => $village->name,
            ];
        }

        // Next, search towns in the same barony
        $townsWithFood = Town::where('id', '!=', $fromType === 'town' ? $fromLocation->id : 0)
            ->when($baronyId, fn ($q) => $q->where('barony_id', $baronyId))
            ->get()
            ->filter(function ($town) {
                return $this->calculateWeeksOfFood('town', $town->id) >= 10;
            });

        if ($townsWithFood->isNotEmpty()) {
            $town = $this->findClosestLocation($townsWithFood, $fromLocation);

            return [
                'type' => 'town',
                'id' => $town->id,
                'name' => $town->name,
            ];
        }

        // Expand search to all villages/towns outside barony
        $allVillagesWithFood = Village::where('id', '!=', $fromType === 'village' ? $fromLocation->id : 0)
            ->where('barony_id', '!=', $baronyId)
            ->get()
            ->filter(function ($village) {
                return $this->calculateWeeksOfFood('village', $village->id) >= 10;
            });

        if ($allVillagesWithFood->isNotEmpty()) {
            $village = $this->findClosestLocation($allVillagesWithFood, $fromLocation);

            return [
                'type' => 'village',
                'id' => $village->id,
                'name' => $village->name,
            ];
        }

        return null;
    }

    /**
     * Find the closest location from a collection based on coordinates.
     *
     * @param  \Illuminate\Support\Collection  $locations
     * @param  Village|Town  $fromLocation
     * @return Village|Town
     */
    protected function findClosestLocation($locations, $fromLocation)
    {
        if ($fromLocation->coordinates_x === null || $fromLocation->coordinates_y === null) {
            return $locations->first();
        }

        return $locations->sortBy(function ($location) use ($fromLocation) {
            if ($location->coordinates_x === null || $location->coordinates_y === null) {
                return PHP_INT_MAX;
            }

            return pow($location->coordinates_x - $fromLocation->coordinates_x, 2)
                 + pow($location->coordinates_y - $fromLocation->coordinates_y, 2);
        })->first();
    }

    /**
     * Apply starvation effects to players at a location.
     *
     * @return array{starving: int, penalized: int}
     */
    protected function applyPlayerStarvationAtLocation(string $locationType, int $locationId): array
    {
        $results = ['starving' => 0, 'penalized' => 0];

        // Get players residing at this location
        $players = $locationType === 'village'
            ? User::where('home_village_id', $locationId)->get()
            : User::where('current_location_type', 'town')
                ->where('current_location_id', $locationId)
                ->where('home_village_id', null)
                ->get();

        foreach ($players as $player) {
            // Increment weeks without food
            $player->increment('weeks_without_food');
            $results['starving']++;

            // Apply energy penalty based on starvation level
            $penalty = $this->calculateStarvationPenalty($player->weeks_without_food);
            if ($penalty > 0) {
                $this->applyPlayerPenalty($player, $penalty);
                $results['penalized']++;
            }
        }

        return $results;
    }

    /**
     * Calculate energy penalty based on weeks without food.
     */
    protected function calculateStarvationPenalty(int $weeksWithoutFood): int
    {
        if ($weeksWithoutFood < 1) {
            return 0;
        }

        // Progressive penalty: 10 energy per week of starvation
        return min($weeksWithoutFood * 10, 50);
    }

    /**
     * Apply starvation penalty to a player.
     */
    protected function applyPlayerPenalty(User $player, int $energyPenalty): void
    {
        // Reduce max energy temporarily (represents weakness from hunger)
        $newEnergy = max(0, $player->energy - $energyPenalty);
        $player->update(['energy' => $newEnergy]);

        Log::info('Player received starvation penalty', [
            'player_id' => $player->id,
            'weeks_without_food' => $player->weeks_without_food,
            'energy_lost' => $energyPenalty,
        ]);
    }

    /**
     * Handle NPC death from starvation.
     */
    protected function handleNpcStarvationDeath(LocationNpc $npc, int $currentYear): void
    {
        $npc->die($currentYear);

        Log::info('NPC died from starvation', [
            'npc_id' => $npc->id,
            'npc_name' => $npc->npc_name,
            'location_type' => $npc->location_type,
            'location_id' => $npc->location_id,
            'weeks_without_food' => $npc->weeks_without_food,
        ]);
    }

    /**
     * Reset starvation counters when food is available at a location.
     */
    protected function resetStarvationAtLocation(string $locationType, int $locationId): void
    {
        // Reset NPC starvation counters
        LocationNpc::alive()
            ->atLocation($locationType, $locationId)
            ->where('weeks_without_food', '>', 0)
            ->update(['weeks_without_food' => 0]);

        // Reset player starvation counters
        if ($locationType === 'village') {
            User::where('home_village_id', $locationId)
                ->where('weeks_without_food', '>', 0)
                ->update(['weeks_without_food' => 0]);
        } else {
            User::where('current_location_type', 'town')
                ->where('current_location_id', $locationId)
                ->where('home_village_id', null)
                ->where('weeks_without_food', '>', 0)
                ->update(['weeks_without_food' => 0]);
        }
    }

    /**
     * Get food statistics for a village.
     * Calculates total food points from all food stockpiles (quantity * food_value).
     */
    public function getVillageFoodStats(Village $village): array
    {
        $npcCount = LocationNpc::alive()
            ->atLocation('village', $village->id)
            ->count();

        // Fall back to village population field if no actual NPCs exist
        if ($npcCount === 0 && $village->population > 0) {
            $npcCount = $village->population;
        }

        $playerCount = User::where('home_village_id', $village->id)->count();

        $totalPopulation = $npcCount + $playerCount;

        // Calculate total "food points" from all food stockpiles
        // Food points = sum of (stockpile.quantity * item.food_value)
        $foodStockpiles = $this->getFoodStockpiles('village', $village->id);
        $totalFoodPoints = $foodStockpiles->sum(fn ($stockpile) => $stockpile->quantity * $stockpile->item->food_value);
        $totalFoodUnits = $foodStockpiles->sum('quantity');

        // Calculate average food value (default to 4 if no food available)
        $avgFoodValue = $totalFoodUnits > 0 ? $totalFoodPoints / $totalFoodUnits : self::PEOPLE_FED_PER_FOOD;

        // Estimated food units needed per week = population / average food value
        $foodNeededPerWeek = $totalPopulation > 0 ? (int) ceil($totalPopulation / $avgFoodValue) : 0;

        // Weeks of food = total food points / population
        $weeksOfFood = $totalPopulation > 0 ? floor($totalFoodPoints / $totalPopulation) : 0;

        $starvingNpcs = LocationNpc::alive()
            ->atLocation('village', $village->id)
            ->where('weeks_without_food', '>', 0)
            ->count();

        $starvingPlayers = User::where('home_village_id', $village->id)
            ->where('weeks_without_food', '>', 0)
            ->count();

        // Build detailed breakdown of food items
        $foodBreakdown = $foodStockpiles->map(fn ($stockpile) => [
            'name' => $stockpile->item->name,
            'quantity' => $stockpile->quantity,
            'food_value' => $stockpile->item->food_value,
            'total_points' => $stockpile->quantity * $stockpile->item->food_value,
        ])->sortByDesc('total_points')->values()->all();

        return [
            'food_available' => $totalFoodUnits,
            'food_points' => $totalFoodPoints,
            'food_needed_per_week' => $foodNeededPerWeek,
            'weeks_of_food' => $weeksOfFood,
            'granary_capacity' => $village->granary_capacity ?? 500,
            'population' => $totalPopulation,
            'npc_count' => $npcCount,
            'player_count' => $playerCount,
            'starving_npcs' => $starvingNpcs,
            'starving_players' => $starvingPlayers,
            'food_breakdown' => $foodBreakdown,
        ];
    }

    /**
     * Get food statistics for a town.
     * Calculates total food points from all food stockpiles (quantity * food_value).
     */
    public function getTownFoodStats(Town $town): array
    {
        $npcCount = LocationNpc::alive()
            ->atLocation('town', $town->id)
            ->count();

        // Fall back to town population field if no actual NPCs exist
        if ($npcCount === 0 && $town->population > 0) {
            $npcCount = $town->population;
        }

        $playerCount = User::where('current_location_type', 'town')
            ->where('current_location_id', $town->id)
            ->where('home_village_id', null)
            ->count();

        $totalPopulation = $npcCount + $playerCount;

        // Calculate total "food points" from all food stockpiles
        // Food points = sum of (stockpile.quantity * item.food_value)
        $foodStockpiles = $this->getFoodStockpiles('town', $town->id);
        $totalFoodPoints = $foodStockpiles->sum(fn ($stockpile) => $stockpile->quantity * $stockpile->item->food_value);
        $totalFoodUnits = $foodStockpiles->sum('quantity');

        // Calculate average food value (default to 4 if no food available)
        $avgFoodValue = $totalFoodUnits > 0 ? $totalFoodPoints / $totalFoodUnits : self::PEOPLE_FED_PER_FOOD;

        // Estimated food units needed per week = population / average food value
        $foodNeededPerWeek = $totalPopulation > 0 ? (int) ceil($totalPopulation / $avgFoodValue) : 0;

        // Weeks of food = total food points / population
        $weeksOfFood = $totalPopulation > 0 ? floor($totalFoodPoints / $totalPopulation) : 0;

        $starvingNpcs = LocationNpc::alive()
            ->atLocation('town', $town->id)
            ->where('weeks_without_food', '>', 0)
            ->count();

        $starvingPlayers = User::where('current_location_type', 'town')
            ->where('current_location_id', $town->id)
            ->where('home_village_id', null)
            ->where('weeks_without_food', '>', 0)
            ->count();

        // Build detailed breakdown of food items
        $foodBreakdown = $foodStockpiles->map(fn ($stockpile) => [
            'name' => $stockpile->item->name,
            'quantity' => $stockpile->quantity,
            'food_value' => $stockpile->item->food_value,
            'total_points' => $stockpile->quantity * $stockpile->item->food_value,
        ])->sortByDesc('total_points')->values()->all();

        return [
            'food_available' => $totalFoodUnits,
            'food_points' => $totalFoodPoints,
            'food_needed_per_week' => $foodNeededPerWeek,
            'weeks_of_food' => $weeksOfFood,
            'granary_capacity' => $town->granary_capacity ?? 1000,
            'population' => $totalPopulation,
            'npc_count' => $npcCount,
            'player_count' => $playerCount,
            'starving_npcs' => $starvingNpcs,
            'starving_players' => $starvingPlayers,
            'food_breakdown' => $foodBreakdown,
        ];
    }

    /**
     * Add food to a village's stockpile.
     *
     * @return int Amount actually added (may be less if granary is full)
     */
    public function addFoodToVillage(Village $village, int $amount): int
    {
        $grainItem = Item::where('name', self::FOOD_ITEM_NAME)->first();
        if (! $grainItem) {
            return 0;
        }

        $stockpile = LocationStockpile::getOrCreate('village', $village->id, $grainItem->id);

        // Respect granary capacity
        $capacity = $village->granary_capacity ?? 500;
        $maxAdd = max(0, $capacity - $stockpile->quantity);
        $actualAdd = min($amount, $maxAdd);

        if ($actualAdd > 0) {
            $stockpile->addQuantity($actualAdd);
        }

        return $actualAdd;
    }

    /**
     * Add food to a town's stockpile.
     *
     * @return int Amount actually added (may be less if granary is full)
     */
    public function addFoodToTown(Town $town, int $amount): int
    {
        $grainItem = Item::where('name', self::FOOD_ITEM_NAME)->first();
        if (! $grainItem) {
            return 0;
        }

        $stockpile = LocationStockpile::getOrCreate('town', $town->id, $grainItem->id);

        // Respect granary capacity
        $capacity = $town->granary_capacity ?? 1000;
        $maxAdd = max(0, $capacity - $stockpile->quantity);
        $actualAdd = min($amount, $maxAdd);

        if ($actualAdd > 0) {
            $stockpile->addQuantity($actualAdd);
        }

        return $actualAdd;
    }

    /**
     * Add food to a location's stockpile (village or town).
     *
     * @return int Amount actually added (may be less if granary is full)
     */
    public function addFoodToLocation(string $locationType, int $locationId, int $amount): int
    {
        if ($locationType === 'village') {
            $village = Village::find($locationId);

            return $village ? $this->addFoodToVillage($village, $amount) : 0;
        } elseif ($locationType === 'town') {
            $town = Town::find($locationId);

            return $town ? $this->addFoodToTown($town, $amount) : 0;
        }

        return 0;
    }

    /**
     * Initialize food stockpiles for all villages with some starting food.
     */
    public function initializeVillageFoodSupplies(int $weeksOfFood = 12): int
    {
        $grainItem = Item::where('name', self::FOOD_ITEM_NAME)->first();
        if (! $grainItem) {
            Log::warning('FoodConsumption: Cannot initialize - Grain item not found');

            return 0;
        }

        $initialized = 0;
        $villages = Village::all();

        foreach ($villages as $village) {
            // Calculate population
            $npcCount = LocationNpc::alive()
                ->atLocation('village', $village->id)
                ->count();
            $playerCount = User::where('home_village_id', $village->id)->count();
            $totalPopulation = max(1, $npcCount + $playerCount);

            // Give enough food for the specified weeks
            $foodAmount = (int) ceil($totalPopulation / self::PEOPLE_FED_PER_FOOD) * $weeksOfFood;

            $stockpile = LocationStockpile::getOrCreate('village', $village->id, $grainItem->id);

            // Only initialize if stockpile is empty
            if ($stockpile->quantity === 0) {
                $capacity = $village->granary_capacity ?? 500;
                $stockpile->addQuantity(min($foodAmount, $capacity));
                $initialized++;
            }
        }

        Log::info('Initialized village food supplies', [
            'villages_initialized' => $initialized,
            'weeks_of_food' => $weeksOfFood,
        ]);

        return $initialized;
    }
}
