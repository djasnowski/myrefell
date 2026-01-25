<?php

namespace App\Services;

use App\Models\Item;
use App\Models\LocationNpc;
use App\Models\LocationStockpile;
use App\Models\User;
use App\Models\Village;
use App\Models\WorldState;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FoodConsumptionService
{
    /**
     * Food item name used for village stockpiles.
     */
    public const FOOD_ITEM_NAME = 'Grain';

    /**
     * Food consumed per person per week.
     */
    public const FOOD_PER_PERSON_PER_WEEK = 1;

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
     * Process weekly food consumption for all villages.
     * Called each game week (each real day).
     *
     * @return array{villages_processed: int, food_consumed: int, npcs_starving: int, npcs_died: int, players_starving: int, players_penalized: int}
     */
    public function processWeeklyConsumption(): array
    {
        $worldState = WorldState::current();
        $currentWeek = $worldState->getTotalWeekOfYear();
        $currentYear = $worldState->current_year;

        $results = [
            'villages_processed' => 0,
            'food_consumed' => 0,
            'npcs_starving' => 0,
            'npcs_died' => 0,
            'players_starving' => 0,
            'players_penalized' => 0,
        ];

        // Get the grain item ID
        $grainItem = Item::where('name', self::FOOD_ITEM_NAME)->first();
        if (! $grainItem) {
            Log::warning('FoodConsumption: Grain item not found, skipping food consumption');

            return $results;
        }

        // Process each village
        $villages = Village::all();
        foreach ($villages as $village) {
            $villageResults = $this->processVillageConsumption($village, $grainItem->id, $currentYear);

            $results['villages_processed']++;
            $results['food_consumed'] += $villageResults['food_consumed'];
            $results['npcs_starving'] += $villageResults['npcs_starving'];
            $results['npcs_died'] += $villageResults['npcs_died'];
            $results['players_starving'] += $villageResults['players_starving'];
            $results['players_penalized'] += $villageResults['players_penalized'];
        }

        Log::info('Weekly food consumption processed', [
            'year' => $currentYear,
            'week' => $currentWeek,
            'villages_processed' => $results['villages_processed'],
            'food_consumed' => $results['food_consumed'],
            'npcs_starving' => $results['npcs_starving'],
            'npcs_died' => $results['npcs_died'],
            'players_starving' => $results['players_starving'],
            'players_penalized' => $results['players_penalized'],
        ]);

        return $results;
    }

    /**
     * Process food consumption for a single village.
     *
     * @return array{food_consumed: int, npcs_starving: int, npcs_died: int, players_starving: int, players_penalized: int}
     */
    protected function processVillageConsumption(Village $village, int $grainItemId, int $currentYear): array
    {
        return DB::transaction(function () use ($village, $grainItemId, $currentYear) {
            $results = [
                'food_consumed' => 0,
                'npcs_starving' => 0,
                'npcs_died' => 0,
                'players_starving' => 0,
                'players_penalized' => 0,
            ];

            // Get or create the food stockpile for this village
            $stockpile = LocationStockpile::getOrCreate('village', $village->id, $grainItemId);

            // Count population: living NPCs at this location + players residing here
            $npcCount = LocationNpc::alive()
                ->atLocation('village', $village->id)
                ->count();

            $playerCount = User::where('home_village_id', $village->id)->count();

            $totalPopulation = $npcCount + $playerCount;
            $foodNeeded = $totalPopulation * self::FOOD_PER_PERSON_PER_WEEK;

            // Calculate how much food can be consumed
            $foodAvailable = $stockpile->quantity;
            $foodToConsume = min($foodNeeded, $foodAvailable);
            $foodShortage = $foodNeeded - $foodToConsume;

            // Consume available food
            if ($foodToConsume > 0) {
                $stockpile->removeQuantity($foodToConsume);
                $results['food_consumed'] = $foodToConsume;
            }

            // If there's a shortage, apply starvation effects
            if ($foodShortage > 0) {
                $npcResults = $this->applyNpcStarvation($village, $currentYear, $foodShortage, $totalPopulation);
                $results['npcs_starving'] = $npcResults['starving'];
                $results['npcs_died'] = $npcResults['died'];

                $playerResults = $this->applyPlayerStarvation($village);
                $results['players_starving'] = $playerResults['starving'];
                $results['players_penalized'] = $playerResults['penalized'];
            } else {
                // Food is sufficient, reset starvation counters
                $this->resetStarvation($village);
            }

            return $results;
        });
    }

    /**
     * Apply starvation effects to NPCs in a village.
     *
     * @return array{starving: int, died: int}
     */
    protected function applyNpcStarvation(Village $village, int $currentYear, int $shortage, int $totalPopulation): array
    {
        $results = ['starving' => 0, 'died' => 0];

        // Get living NPCs at this location
        $npcs = LocationNpc::alive()
            ->atLocation('village', $village->id)
            ->get();

        foreach ($npcs as $npc) {
            // Increment weeks without food
            $npc->increment('weeks_without_food');
            $results['starving']++;

            // Check for death by starvation
            if ($npc->weeks_without_food >= self::MAX_WEEKS_WITHOUT_FOOD) {
                $this->handleNpcStarvationDeath($npc, $currentYear);
                $results['died']++;
            }
        }

        return $results;
    }

    /**
     * Apply starvation effects to players in a village.
     *
     * @return array{starving: int, penalized: int}
     */
    protected function applyPlayerStarvation(Village $village): array
    {
        $results = ['starving' => 0, 'penalized' => 0];

        // Get players residing in this village
        $players = User::where('home_village_id', $village->id)->get();

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
     * Reset starvation counters when food is available.
     */
    protected function resetStarvation(Village $village): void
    {
        // Reset NPC starvation counters
        LocationNpc::alive()
            ->atLocation('village', $village->id)
            ->where('weeks_without_food', '>', 0)
            ->update(['weeks_without_food' => 0]);

        // Reset player starvation counters
        User::where('home_village_id', $village->id)
            ->where('weeks_without_food', '>', 0)
            ->update(['weeks_without_food' => 0]);
    }

    /**
     * Get food statistics for a village.
     */
    public function getVillageFoodStats(Village $village): array
    {
        $grainItem = Item::where('name', self::FOOD_ITEM_NAME)->first();
        if (! $grainItem) {
            return [
                'food_available' => 0,
                'food_needed_per_week' => 0,
                'weeks_of_food' => 0,
                'population' => 0,
                'starving_npcs' => 0,
                'starving_players' => 0,
            ];
        }

        $stockpile = LocationStockpile::atLocation('village', $village->id)
            ->forItem($grainItem->id)
            ->first();

        $npcCount = LocationNpc::alive()
            ->atLocation('village', $village->id)
            ->count();

        $playerCount = User::where('home_village_id', $village->id)->count();

        $totalPopulation = $npcCount + $playerCount;
        $foodPerWeek = $totalPopulation * self::FOOD_PER_PERSON_PER_WEEK;
        $foodAvailable = $stockpile?->quantity ?? 0;

        $starvingNpcs = LocationNpc::alive()
            ->atLocation('village', $village->id)
            ->where('weeks_without_food', '>', 0)
            ->count();

        $starvingPlayers = User::where('home_village_id', $village->id)
            ->where('weeks_without_food', '>', 0)
            ->count();

        return [
            'food_available' => $foodAvailable,
            'food_needed_per_week' => $foodPerWeek,
            'weeks_of_food' => $foodPerWeek > 0 ? floor($foodAvailable / $foodPerWeek) : 0,
            'granary_capacity' => $village->granary_capacity ?? 500,
            'population' => $totalPopulation,
            'npc_count' => $npcCount,
            'player_count' => $playerCount,
            'starving_npcs' => $starvingNpcs,
            'starving_players' => $starvingPlayers,
        ];
    }

    /**
     * Add food to a village's stockpile.
     */
    public function addFoodToVillage(Village $village, int $amount): bool
    {
        $grainItem = Item::where('name', self::FOOD_ITEM_NAME)->first();
        if (! $grainItem) {
            return false;
        }

        $stockpile = LocationStockpile::getOrCreate('village', $village->id, $grainItem->id);

        // Respect granary capacity
        $capacity = $village->granary_capacity ?? 500;
        $maxAdd = max(0, $capacity - $stockpile->quantity);
        $actualAdd = min($amount, $maxAdd);

        if ($actualAdd > 0) {
            $stockpile->addQuantity($actualAdd);

            return true;
        }

        return false;
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
            $foodAmount = $totalPopulation * self::FOOD_PER_PERSON_PER_WEEK * $weeksOfFood;

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
