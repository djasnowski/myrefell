<?php

namespace App\Services;

use App\Models\Item;
use App\Models\LocationStockpile;
use App\Models\MarketPrice;
use App\Models\MarketTransaction;
use App\Models\User;
use App\Models\Village;
use App\Models\WorldState;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class MarketService
{
    /**
     * Valid location types for markets.
     * Note: Hamlets use their parent village's market.
     */
    public const VALID_LOCATIONS = ['village', 'barony', 'town', 'kingdom'];

    /**
     * Seasonal price modifiers for different item categories.
     * > 1.0 = price increase, < 1.0 = price decrease
     */
    public const SEASON_PRICE_MODIFIERS = [
        'spring' => [
            'resource' => 1.1,   // Resources slightly scarcer
            'consumable' => 1.15, // Food more expensive (winter stores depleted)
            'weapon' => 1.0,
            'armor' => 1.0,
            'tool' => 1.05,     // Tools in demand for planting
            'misc' => 1.0,
        ],
        'summer' => [
            'resource' => 0.95, // Resources abundant
            'consumable' => 0.9, // Food cheaper (growing season)
            'weapon' => 1.0,
            'armor' => 1.0,
            'tool' => 0.95,
            'misc' => 1.0,
        ],
        'autumn' => [
            'resource' => 0.85, // Resources most abundant (harvest)
            'consumable' => 0.8, // Food cheapest (harvest)
            'weapon' => 1.0,
            'armor' => 1.0,
            'tool' => 1.0,
            'misc' => 1.0,
        ],
        'winter' => [
            'resource' => 1.2,  // Resources scarce
            'consumable' => 1.3, // Food expensive (scarcity)
            'weapon' => 1.05,   // Weapons in demand (raids)
            'armor' => 1.05,
            'tool' => 1.0,
            'misc' => 1.0,
        ],
    ];

    /**
     * Supply level thresholds for price adjustments.
     */
    public const SUPPLY_LOW = 10;

    public const SUPPLY_MEDIUM = 50;

    public const SUPPLY_HIGH = 100;

    public function __construct(
        protected InventoryService $inventoryService
    ) {}

    /**
     * Check if user can access market at their current location.
     */
    public function canAccessMarket(User $user): bool
    {
        if ($user->isTraveling() || $user->isInInfirmary()) {
            return false;
        }

        $locationType = $user->current_location_type;

        if (! in_array($locationType, self::VALID_LOCATIONS)) {
            return false;
        }

        return true;
    }

    /**
     * Get market info for user's current location.
     */
    public function getMarketInfo(User $user): ?array
    {
        if (! $this->canAccessMarket($user)) {
            return null;
        }

        $locationType = $user->current_location_type;
        $locationId = $user->current_location_id;

        // For hamlets, use parent village's market
        if ($locationType === 'village') {
            $village = Village::find($locationId);
            if ($village && $village->isHamlet()) {
                $serviceProvider = $village->getServiceProvider();
                $locationId = $serviceProvider->id;
            }
        }

        $location = $this->resolveLocation($locationType, $locationId);

        return [
            'location_type' => $locationType,
            'location_id' => $locationId,
            'location_name' => $location?->name ?? 'Unknown',
            'gold_on_hand' => $user->gold,
        ];
    }

    /**
     * Get all market prices for a location (only items in stock).
     */
    public function getMarketPrices(string $locationType, int $locationId): Collection
    {
        $worldState = WorldState::current();

        // Get stockpiles with items in stock at this location
        $stockpiles = LocationStockpile::atLocation($locationType, $locationId)
            ->where('quantity', '>', 0)
            ->with('item')
            ->get();

        return $stockpiles
            ->filter(fn ($stockpile) => $stockpile->item && in_array($stockpile->item->type, ['resource', 'consumable', 'tool', 'misc', 'weapon', 'armor']))
            ->map(function ($stockpile) use ($locationType, $locationId, $worldState) {
                $item = $stockpile->item;
                $marketPrice = MarketPrice::getOrCreate($locationType, $locationId, $item);

                // Update price with current modifiers
                $this->updatePrice($marketPrice, $worldState);

                return [
                    'item_id' => $item->id,
                    'item_name' => $item->name,
                    'item_type' => $item->type,
                    'item_description' => $item->description,
                    'base_price' => $marketPrice->base_price,
                    'buy_price' => $marketPrice->buy_price,
                    'sell_price' => $marketPrice->sell_price,
                    'current_price' => $marketPrice->current_price,
                    'supply_quantity' => $stockpile->quantity,
                    'demand_level' => $marketPrice->demand_level,
                    'seasonal_modifier' => $marketPrice->seasonal_modifier,
                    'supply_modifier' => $marketPrice->supply_modifier,
                ];
            });
    }

    /**
     * Get items the player can sell (from their inventory).
     */
    public function getSellableItems(User $user, string $locationType, int $locationId): Collection
    {
        $worldState = WorldState::current();

        return $user->inventory()
            ->where('is_equipped', false)
            ->with('item')
            ->get()
            ->filter(function ($slot) {
                // Can sell resources, consumables, tools, misc, weapons, armor
                return in_array($slot->item->type, ['resource', 'consumable', 'tool', 'misc', 'weapon', 'armor']);
            })
            ->groupBy('item_id')
            ->map(function ($slots) use ($locationType, $locationId, $worldState) {
                $firstSlot = $slots->first();
                $item = $firstSlot->item;
                $marketPrice = MarketPrice::getOrCreate($locationType, $locationId, $item);
                $this->updatePrice($marketPrice, $worldState);

                // Sum quantities across all slots with this item
                $totalQuantity = $slots->sum('quantity');

                return [
                    'inventory_ids' => $slots->pluck('id')->toArray(),
                    'item_id' => $item->id,
                    'item_name' => $item->name,
                    'item_type' => $item->type,
                    'quantity' => $totalQuantity,
                    'sell_price' => $marketPrice->sell_price,
                ];
            })
            ->values();
    }

    /**
     * Update a market price based on current conditions.
     */
    public function updatePrice(MarketPrice $marketPrice, ?WorldState $worldState = null): void
    {
        $worldState = $worldState ?? WorldState::current();

        // Get seasonal modifier for item type
        $item = $marketPrice->item;
        $itemType = $item->type ?? 'misc';
        $season = $worldState->current_season;

        $seasonalModifier = self::SEASON_PRICE_MODIFIERS[$season][$itemType] ?? 1.0;

        // Get supply modifier
        $supplyModifier = $this->calculateSupplyModifier($marketPrice);

        // Calculate new price
        $newPrice = (int) round($marketPrice->base_price * $seasonalModifier * $supplyModifier);
        $newPrice = max(1, $newPrice); // Minimum price of 1

        $marketPrice->update([
            'current_price' => $newPrice,
            'seasonal_modifier' => $seasonalModifier,
            'supply_modifier' => $supplyModifier,
            'last_updated_at' => now(),
        ]);
    }

    /**
     * Calculate supply modifier based on local supply.
     */
    protected function calculateSupplyModifier(MarketPrice $marketPrice): float
    {
        $stockpile = LocationStockpile::atLocation(
            $marketPrice->location_type,
            $marketPrice->location_id
        )->forItem($marketPrice->item_id)->first();

        $supply = $stockpile?->quantity ?? 0;

        return $this->calculateSupplyModifierForQuantity($supply);
    }

    /**
     * Calculate supply modifier for a given supply quantity.
     */
    protected function calculateSupplyModifierForQuantity(int $supply): float
    {
        // Low supply = higher prices, high supply = lower prices
        if ($supply <= self::SUPPLY_LOW) {
            return 1.3; // 30% price increase
        } elseif ($supply <= self::SUPPLY_MEDIUM) {
            return 1.0 + (0.3 * (self::SUPPLY_MEDIUM - $supply) / (self::SUPPLY_MEDIUM - self::SUPPLY_LOW));
        } elseif ($supply <= self::SUPPLY_HIGH) {
            return 1.0 - (0.2 * ($supply - self::SUPPLY_MEDIUM) / (self::SUPPLY_HIGH - self::SUPPLY_MEDIUM));
        } else {
            return 0.7; // 30% price decrease for abundant supply
        }
    }

    /**
     * Calculate sell price accounting for the supply increase from the sale.
     * This prevents arbitrage by pricing items as if they're already in the market.
     */
    public function calculateSellPriceWithSupplyIncrease(MarketPrice $marketPrice, int $quantity): int
    {
        $stockpile = LocationStockpile::atLocation(
            $marketPrice->location_type,
            $marketPrice->location_id
        )->forItem($marketPrice->item_id)->first();

        $currentSupply = $stockpile?->quantity ?? 0;
        $projectedSupply = $currentSupply + $quantity;

        // Calculate what the price would be AFTER adding the items
        $worldState = WorldState::current();
        $item = $marketPrice->item;
        $itemType = $item->type ?? 'misc';
        $season = $worldState->current_season;

        $seasonalModifier = self::SEASON_PRICE_MODIFIERS[$season][$itemType] ?? 1.0;
        $projectedSupplyModifier = $this->calculateSupplyModifierForQuantity($projectedSupply);

        $projectedPrice = (int) round($marketPrice->base_price * $seasonalModifier * $projectedSupplyModifier);
        $projectedPrice = max(1, $projectedPrice);

        // Sell price is 80% of the projected price (same as getSellPriceAttribute)
        return max(1, (int) floor($projectedPrice * 0.8));
    }

    /**
     * Get a sell quote for an item showing the actual gold the user will receive.
     */
    public function getSellQuote(User $user, int $itemId, int $quantity): array
    {
        if (! $this->canAccessMarket($user)) {
            return [
                'success' => false,
                'message' => 'You cannot access a market here.',
            ];
        }

        $item = Item::find($itemId);
        if (! $item) {
            return [
                'success' => false,
                'message' => 'Item not found.',
            ];
        }

        $locationType = $user->current_location_type;
        $locationId = $user->current_location_id;

        // Handle hamlet -> parent village
        if ($locationType === 'village') {
            $village = Village::find($locationId);
            if ($village && $village->isHamlet()) {
                $serviceProvider = $village->getServiceProvider();
                $locationId = $serviceProvider->id;
            }
        }

        $marketPrice = MarketPrice::getOrCreate($locationType, $locationId, $item);
        $this->updatePrice($marketPrice);

        // Calculate the actual sell price per unit based on projected supply
        $sellPricePerUnit = $this->calculateSellPriceWithSupplyIncrease($marketPrice, $quantity);
        $totalGold = $sellPricePerUnit * $quantity;

        return [
            'success' => true,
            'item_id' => $itemId,
            'quantity' => $quantity,
            'price_per_unit' => $sellPricePerUnit,
            'total_gold' => $totalGold,
            'current_display_price' => $marketPrice->sell_price,
        ];
    }

    /**
     * Buy an item from the market.
     */
    public function buyItem(User $user, int $itemId, int $quantity): array
    {
        if (! $this->canAccessMarket($user)) {
            return [
                'success' => false,
                'message' => 'You cannot access a market here.',
            ];
        }

        if ($quantity <= 0) {
            return [
                'success' => false,
                'message' => 'Quantity must be greater than zero.',
            ];
        }

        $item = Item::find($itemId);
        if (! $item) {
            return [
                'success' => false,
                'message' => 'Item not found.',
            ];
        }

        $locationType = $user->current_location_type;
        $locationId = $user->current_location_id;

        // Handle hamlet -> parent village
        if ($locationType === 'village') {
            $village = Village::find($locationId);
            if ($village && $village->isHamlet()) {
                $serviceProvider = $village->getServiceProvider();
                $locationId = $serviceProvider->id;
            }
        }

        // Check stockpile has enough
        $stockpile = LocationStockpile::getOrCreate($locationType, $locationId, $item->id);
        if (! $stockpile->hasQuantity($quantity)) {
            return [
                'success' => false,
                'message' => 'Not enough stock available.',
            ];
        }

        // Get market price
        $marketPrice = MarketPrice::getOrCreate($locationType, $locationId, $item);
        $this->updatePrice($marketPrice);

        $totalCost = $marketPrice->buy_price * $quantity;

        // Check if player has enough gold
        if ($user->gold < $totalCost) {
            return [
                'success' => false,
                'message' => 'You don\'t have enough gold.',
            ];
        }

        // Check inventory space
        $slotsNeeded = $this->inventoryService->slotsNeededForItem($user, $item, $quantity);
        $freeSlots = $this->inventoryService->freeSlots($user);

        if ($slotsNeeded > $freeSlots) {
            return [
                'success' => false,
                'message' => 'You don\'t have enough inventory space.',
            ];
        }

        return DB::transaction(function () use ($user, $item, $quantity, $marketPrice, $stockpile, $totalCost, $locationType, $locationId) {
            // Deduct gold
            $user->decrement('gold', $totalCost);

            // Remove from stockpile
            $stockpile->removeQuantity($quantity);

            // Add to player inventory - if this fails, the transaction will roll back
            if (! $this->inventoryService->addItem($user, $item, $quantity)) {
                throw new \RuntimeException('Failed to add items to inventory.');
            }

            // Record transaction
            MarketTransaction::create([
                'user_id' => $user->id,
                'location_type' => $locationType,
                'location_id' => $locationId,
                'item_id' => $item->id,
                'type' => MarketTransaction::TYPE_BUY,
                'quantity' => $quantity,
                'price_per_unit' => $marketPrice->buy_price,
                'total_gold' => $totalCost,
            ]);

            // Increase demand level
            $marketPrice->increment('demand_level', min(5, 100 - $marketPrice->demand_level));

            // Update price after transaction
            $this->updatePrice($marketPrice);

            return [
                'success' => true,
                'message' => "Bought {$quantity}x {$item->name} for {$totalCost} gold.",
                'gold_remaining' => $user->fresh()->gold,
            ];
        });
    }

    /**
     * Sell an item to the market.
     */
    public function sellItem(User $user, int $itemId, int $quantity): array
    {
        if (! $this->canAccessMarket($user)) {
            return [
                'success' => false,
                'message' => 'You cannot access a market here.',
            ];
        }

        if ($quantity <= 0) {
            return [
                'success' => false,
                'message' => 'Quantity must be greater than zero.',
            ];
        }

        $item = Item::find($itemId);
        if (! $item) {
            return [
                'success' => false,
                'message' => 'Item not found.',
            ];
        }

        // Check player has the item (excluding equipped items)
        if (! $this->inventoryService->hasItem($user, $item, $quantity, excludeEquipped: true)) {
            return [
                'success' => false,
                'message' => 'You don\'t have enough of this item (equipped items cannot be sold).',
            ];
        }

        $locationType = $user->current_location_type;
        $locationId = $user->current_location_id;

        // Handle hamlet -> parent village
        if ($locationType === 'village') {
            $village = Village::find($locationId);
            if ($village && $village->isHamlet()) {
                $serviceProvider = $village->getServiceProvider();
                $locationId = $serviceProvider->id;
            }
        }

        // Get market price
        $marketPrice = MarketPrice::getOrCreate($locationType, $locationId, $item);
        $this->updatePrice($marketPrice);

        // Calculate sell price based on post-sale supply level (prevents arbitrage)
        $sellPrice = $this->calculateSellPriceWithSupplyIncrease($marketPrice, $quantity);
        $totalGold = $sellPrice * $quantity;

        return DB::transaction(function () use ($user, $item, $quantity, $marketPrice, $sellPrice, $totalGold, $locationType, $locationId) {
            // Remove from inventory
            $this->inventoryService->removeItem($user, $item, $quantity);

            // Add gold to player
            $user->increment('gold', $totalGold);

            // Add to stockpile
            $stockpile = LocationStockpile::getOrCreate($locationType, $locationId, $item->id);
            $stockpile->addQuantity($quantity);

            // Record transaction
            MarketTransaction::create([
                'user_id' => $user->id,
                'location_type' => $locationType,
                'location_id' => $locationId,
                'item_id' => $item->id,
                'type' => MarketTransaction::TYPE_SELL,
                'quantity' => $quantity,
                'price_per_unit' => $sellPrice,
                'total_gold' => $totalGold,
            ]);

            // Decrease demand level
            $marketPrice->decrement('demand_level', max(5, $marketPrice->demand_level));

            // Update price after transaction
            $this->updatePrice($marketPrice);

            return [
                'success' => true,
                'message' => "Sold {$quantity}x {$item->name} for {$totalGold} gold.",
                'gold_on_hand' => $user->fresh()->gold,
            ];
        });
    }

    /**
     * Get recent market transactions for a user.
     */
    public function getRecentTransactions(User $user, int $limit = 10): Collection
    {
        return MarketTransaction::forUser($user->id)
            ->with('item')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->map(fn ($tx) => [
                'id' => $tx->id,
                'type' => $tx->type,
                'item_name' => $tx->item->name,
                'quantity' => $tx->quantity,
                'price_per_unit' => $tx->price_per_unit,
                'total_gold' => $tx->total_gold,
                'created_at' => $tx->created_at->toISOString(),
                'formatted_date' => $tx->created_at->format('M j, g:i A'),
            ]);
    }

    /**
     * Resolve a location model by type and ID.
     */
    protected function resolveLocation(string $type, int $id): ?object
    {
        $modelClass = match ($type) {
            'village' => \App\Models\Village::class,
            'barony' => \App\Models\Barony::class,
            'town' => \App\Models\Town::class,
            'kingdom' => \App\Models\Kingdom::class,
            default => null,
        };

        if (! $modelClass) {
            return null;
        }

        return $modelClass::find($id);
    }

    /**
     * Refresh all market prices at a location (called periodically).
     */
    public function refreshLocationPrices(string $locationType, int $locationId): void
    {
        $worldState = WorldState::current();

        MarketPrice::atLocation($locationType, $locationId)
            ->each(function ($marketPrice) use ($worldState) {
                $this->updatePrice($marketPrice, $worldState);
            });
    }
}
