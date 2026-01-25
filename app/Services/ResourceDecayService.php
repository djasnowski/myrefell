<?php

namespace App\Services;

use App\Models\Item;
use App\Models\LocationStockpile;
use App\Models\PlayerInventory;
use App\Models\WorldState;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ResourceDecayService
{
    /**
     * Decay modifier for summer (faster decay due to heat).
     */
    public const SUMMER_DECAY_MODIFIER = 1.5;

    /**
     * Decay modifier for winter (slower decay due to cold).
     */
    public const WINTER_DECAY_MODIFIER = 0.5;

    /**
     * Process weekly resource decay for all stockpiles and player inventories.
     * Called each game week (each real day).
     *
     * @return array{stockpiles_processed: int, inventory_processed: int, items_decayed: int, items_spoiled: int, items_destroyed: int}
     */
    public function processWeeklyDecay(): array
    {
        $worldState = WorldState::current();
        $currentWeek = $worldState->getTotalWeekOfYear();
        $currentYear = $worldState->current_year;
        $season = $worldState->current_season;

        $results = [
            'stockpiles_processed' => 0,
            'inventory_processed' => 0,
            'items_decayed' => 0,
            'items_spoiled' => 0,
            'items_destroyed' => 0,
        ];

        // Get the seasonal decay modifier
        $decayModifier = $this->getSeasonalDecayModifier($season);

        // Process location stockpiles
        $stockpileResults = $this->processStockpileDecay($decayModifier);
        $results['stockpiles_processed'] = $stockpileResults['processed'];
        $results['items_decayed'] += $stockpileResults['decayed'];
        $results['items_spoiled'] += $stockpileResults['spoiled'];
        $results['items_destroyed'] += $stockpileResults['destroyed'];

        // Process player inventories
        $inventoryResults = $this->processInventoryDecay($decayModifier);
        $results['inventory_processed'] = $inventoryResults['processed'];
        $results['items_decayed'] += $inventoryResults['decayed'];
        $results['items_spoiled'] += $inventoryResults['spoiled'];
        $results['items_destroyed'] += $inventoryResults['destroyed'];

        Log::info('Weekly resource decay processed', [
            'year' => $currentYear,
            'week' => $currentWeek,
            'season' => $season,
            'decay_modifier' => $decayModifier,
            'stockpiles_processed' => $results['stockpiles_processed'],
            'inventory_processed' => $results['inventory_processed'],
            'items_decayed' => $results['items_decayed'],
            'items_spoiled' => $results['items_spoiled'],
            'items_destroyed' => $results['items_destroyed'],
        ]);

        return $results;
    }

    /**
     * Get the seasonal decay modifier.
     */
    protected function getSeasonalDecayModifier(string $season): float
    {
        return match ($season) {
            'summer' => self::SUMMER_DECAY_MODIFIER,
            'winter' => self::WINTER_DECAY_MODIFIER,
            default => 1.0,
        };
    }

    /**
     * Process decay for all location stockpiles.
     *
     * @return array{processed: int, decayed: int, spoiled: int, destroyed: int}
     */
    protected function processStockpileDecay(float $decayModifier): array
    {
        $results = [
            'processed' => 0,
            'decayed' => 0,
            'spoiled' => 0,
            'destroyed' => 0,
        ];

        // Get all stockpiles with perishable items
        $stockpiles = LocationStockpile::withPerishableItems()
            ->where('quantity', '>', 0)
            ->get();

        foreach ($stockpiles as $stockpile) {
            $stockpileResults = $this->processStockpileEntry($stockpile, $decayModifier);
            $results['processed']++;
            $results['decayed'] += $stockpileResults['decayed'];
            $results['spoiled'] += $stockpileResults['spoiled'];
            $results['destroyed'] += $stockpileResults['destroyed'];
        }

        return $results;
    }

    /**
     * Process decay for a single stockpile entry.
     *
     * @return array{decayed: int, spoiled: int, destroyed: int}
     */
    protected function processStockpileEntry(LocationStockpile $stockpile, float $decayModifier): array
    {
        return DB::transaction(function () use ($stockpile, $decayModifier) {
            $results = [
                'decayed' => 0,
                'spoiled' => 0,
                'destroyed' => 0,
            ];

            $item = $stockpile->item;
            if (! $item || ! $item->isPerishable()) {
                return $results;
            }

            // Increment weeks stored
            $stockpile->increment('weeks_stored');
            $stockpile->last_decay_at = now();
            $stockpile->save();

            // Check for spoilage (item transforms into another item)
            if ($item->spoilsAfterTime() && $stockpile->weeks_stored >= $item->spoil_after_weeks) {
                $this->handleStockpileSpoilage($stockpile, $item);
                $results['spoiled'] = $stockpile->quantity;

                return $results;
            }

            // Apply decay (reduce quantity)
            if ($item->decaysOverTime()) {
                $decayAmount = $this->calculateDecayAmount($stockpile->quantity, $item->decay_rate_per_week, $decayModifier);
                if ($decayAmount > 0) {
                    $stockpile->decrement('quantity', $decayAmount);
                    $results['decayed'] = $decayAmount;

                    // Check if completely destroyed
                    if ($stockpile->quantity <= 0) {
                        $stockpile->delete();
                        $results['destroyed'] = 1;
                    }

                    Log::debug('Stockpile item decayed', [
                        'location_type' => $stockpile->location_type,
                        'location_id' => $stockpile->location_id,
                        'item' => $item->name,
                        'decay_amount' => $decayAmount,
                        'remaining' => $stockpile->quantity,
                    ]);
                }
            }

            return $results;
        });
    }

    /**
     * Handle stockpile spoilage (transform item into spoiled version).
     */
    protected function handleStockpileSpoilage(LocationStockpile $stockpile, Item $originalItem): void
    {
        $spoiledItem = $originalItem->getSpoiledItem();

        if ($spoiledItem) {
            // Transform into spoiled item
            $quantity = $stockpile->quantity;

            // Get or create stockpile for spoiled item
            $spoiledStockpile = LocationStockpile::getOrCreate(
                $stockpile->location_type,
                $stockpile->location_id,
                $spoiledItem->id
            );
            $spoiledStockpile->addQuantity($quantity);

            Log::info('Stockpile item spoiled and transformed', [
                'location_type' => $stockpile->location_type,
                'location_id' => $stockpile->location_id,
                'original_item' => $originalItem->name,
                'spoiled_item' => $spoiledItem->name,
                'quantity' => $quantity,
            ]);
        } else {
            Log::info('Stockpile item spoiled and destroyed', [
                'location_type' => $stockpile->location_type,
                'location_id' => $stockpile->location_id,
                'item' => $originalItem->name,
                'quantity' => $stockpile->quantity,
            ]);
        }

        // Delete the original stockpile entry
        $stockpile->delete();
    }

    /**
     * Process decay for all player inventories.
     *
     * @return array{processed: int, decayed: int, spoiled: int, destroyed: int}
     */
    protected function processInventoryDecay(float $decayModifier): array
    {
        $results = [
            'processed' => 0,
            'decayed' => 0,
            'spoiled' => 0,
            'destroyed' => 0,
        ];

        // Get all inventory slots with perishable items
        $inventorySlots = PlayerInventory::withPerishableItems()
            ->where('quantity', '>', 0)
            ->get();

        foreach ($inventorySlots as $slot) {
            $slotResults = $this->processInventorySlot($slot, $decayModifier);
            $results['processed']++;
            $results['decayed'] += $slotResults['decayed'];
            $results['spoiled'] += $slotResults['spoiled'];
            $results['destroyed'] += $slotResults['destroyed'];
        }

        return $results;
    }

    /**
     * Process decay for a single inventory slot.
     *
     * @return array{decayed: int, spoiled: int, destroyed: int}
     */
    protected function processInventorySlot(PlayerInventory $slot, float $decayModifier): array
    {
        return DB::transaction(function () use ($slot, $decayModifier) {
            $results = [
                'decayed' => 0,
                'spoiled' => 0,
                'destroyed' => 0,
            ];

            $item = $slot->item;
            if (! $item || ! $item->isPerishable()) {
                return $results;
            }

            // Increment weeks stored
            $slot->increment('weeks_stored');
            $slot->last_decay_at = now();
            $slot->save();

            // Check for spoilage (item transforms into another item)
            if ($item->spoilsAfterTime() && $slot->weeks_stored >= $item->spoil_after_weeks) {
                $this->handleInventorySpoilage($slot, $item);
                $results['spoiled'] = $slot->quantity;

                return $results;
            }

            // Apply decay (reduce quantity)
            if ($item->decaysOverTime()) {
                $decayAmount = $this->calculateDecayAmount($slot->quantity, $item->decay_rate_per_week, $decayModifier);
                if ($decayAmount > 0) {
                    $slot->decrement('quantity', $decayAmount);
                    $results['decayed'] = $decayAmount;

                    // Check if completely destroyed
                    if ($slot->quantity <= 0) {
                        $slot->delete();
                        $results['destroyed'] = 1;
                    }

                    Log::debug('Player inventory item decayed', [
                        'player_id' => $slot->player_id,
                        'item' => $item->name,
                        'decay_amount' => $decayAmount,
                        'remaining' => $slot->quantity,
                    ]);
                }
            }

            return $results;
        });
    }

    /**
     * Handle inventory spoilage (transform item into spoiled version).
     */
    protected function handleInventorySpoilage(PlayerInventory $slot, Item $originalItem): void
    {
        $spoiledItem = $originalItem->getSpoiledItem();

        if ($spoiledItem) {
            // Transform into spoiled item in the same slot
            $slot->item_id = $spoiledItem->id;
            $slot->weeks_stored = 0;
            $slot->save();

            Log::info('Player inventory item spoiled and transformed', [
                'player_id' => $slot->player_id,
                'original_item' => $originalItem->name,
                'spoiled_item' => $spoiledItem->name,
                'quantity' => $slot->quantity,
            ]);
        } else {
            // No spoiled version, destroy the item
            Log::info('Player inventory item spoiled and destroyed', [
                'player_id' => $slot->player_id,
                'item' => $originalItem->name,
                'quantity' => $slot->quantity,
            ]);
            $slot->delete();
        }
    }

    /**
     * Calculate the decay amount based on quantity, rate, and modifier.
     */
    protected function calculateDecayAmount(int $quantity, int $decayRatePerWeek, float $modifier): int
    {
        // Apply modifier and round down
        $baseDecay = (int) floor($decayRatePerWeek * $modifier);

        // Never decay more than the available quantity
        return min($baseDecay, $quantity);
    }

    /**
     * Get decay statistics for a location's stockpile.
     */
    public function getLocationDecayStats(string $locationType, int $locationId): array
    {
        $stockpiles = LocationStockpile::withPerishableItems()
            ->atLocation($locationType, $locationId)
            ->where('quantity', '>', 0)
            ->with('item')
            ->get();

        $stats = [];
        foreach ($stockpiles as $stockpile) {
            $item = $stockpile->item;
            $weeksUntilSpoil = null;
            if ($item->spoil_after_weeks !== null) {
                $weeksUntilSpoil = max(0, $item->spoil_after_weeks - $stockpile->weeks_stored);
            }

            $stats[] = [
                'item_name' => $item->name,
                'quantity' => $stockpile->quantity,
                'weeks_stored' => $stockpile->weeks_stored,
                'decay_rate_per_week' => $item->decay_rate_per_week,
                'spoil_after_weeks' => $item->spoil_after_weeks,
                'weeks_until_spoil' => $weeksUntilSpoil,
                'decays_into' => $item->decays_into,
            ];
        }

        return $stats;
    }

    /**
     * Get decay statistics for a player's inventory.
     */
    public function getPlayerDecayStats(int $playerId): array
    {
        $slots = PlayerInventory::withPerishableItems()
            ->where('player_id', $playerId)
            ->where('quantity', '>', 0)
            ->with('item')
            ->get();

        $stats = [];
        foreach ($slots as $slot) {
            $item = $slot->item;
            $weeksUntilSpoil = null;
            if ($item->spoil_after_weeks !== null) {
                $weeksUntilSpoil = max(0, $item->spoil_after_weeks - $slot->weeks_stored);
            }

            $stats[] = [
                'item_name' => $item->name,
                'quantity' => $slot->quantity,
                'slot_number' => $slot->slot_number,
                'weeks_stored' => $slot->weeks_stored,
                'decay_rate_per_week' => $item->decay_rate_per_week,
                'spoil_after_weeks' => $item->spoil_after_weeks,
                'weeks_until_spoil' => $weeksUntilSpoil,
                'decays_into' => $item->decays_into,
            ];
        }

        return $stats;
    }
}
