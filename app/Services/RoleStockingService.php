<?php

namespace App\Services;

use App\Models\Item;
use App\Models\LocationStockpile;
use App\Models\PlayerRole;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class RoleStockingService
{
    /**
     * Items that each role can stock in the market.
     * Role slug => array of item names
     */
    public const ROLE_STOCK_ITEMS = [
        'miner' => [
            'Copper Ore',
            'Tin Ore',
            'Iron Ore',
            'Coal',
            'Gold Ore',
            'Mithril Ore',
        ],
        'blacksmith' => [
            'Bronze Bar',
            'Iron Bar',
            'Steel Bar',
            'Bronze Pickaxe',
            'Iron Pickaxe',
            'Steel Pickaxe',
            'Hammer',
            'Nails',
            'Fishing Rod',
            'Bronze Dagger',
            'Iron Dagger',
            'Bronze Sword',
            'Iron Sword',
            'Steel Sword',
            'Bronze Axe',
            'Iron Battleaxe',
        ],
        'fisherman' => [
            'Raw Trout',
            'Raw Salmon',
            'Raw Lobster',
            'Fishing Rod',
            'Fishing Net',
        ],
        'baker' => [
            'Flour',
            'Bread',
            'Meat Pie',
        ],
        'butcher' => [
            'Raw Meat',
            'Cooked Meat',
        ],
        'master_farmer' => [
            'Grain',
        ],
        'innkeeper' => [
            'Cooked Trout',
            'Cooked Salmon',
            'Cooked Lobster',
            'Cooked Meat',
            'Bread',
            'Meat Pie',
        ],
        'forester' => [
            'Wood',
            'Oak Wood',
            'Willow Wood',
        ],
        'hunter' => [
            'Raw Meat',
            'Leather',
            'Bones',
        ],
        'brewer' => [
            // Future: Ale, Mead, etc.
        ],
        // Merchant can stock anything (special role)
        'merchant' => '*',

        // Barony roles
        'physician' => [
            'Bandage',
            'Medicine',
            'Antidote',
            'Healing Potion',
        ],
        'master_cook' => [
            'Cooked Meat',
            'Cooked Trout',
            'Cooked Salmon',
            'Cooked Lobster',
            'Meat Pie',
            'Bread',
            'Feast Platter',
        ],

        // Town roles
        'town_physician' => [
            'Bandage',
            'Medicine',
            'Antidote',
            'Healing Potion',
        ],
        'head_chef' => [
            'Cooked Meat',
            'Cooked Trout',
            'Cooked Salmon',
            'Cooked Lobster',
            'Meat Pie',
            'Bread',
            'Feast Platter',
        ],
        'master_blacksmith' => [
            'Bronze Bar',
            'Iron Bar',
            'Steel Bar',
            'Bronze Pickaxe',
            'Iron Pickaxe',
            'Steel Pickaxe',
            'Hammer',
            'Nails',
        ],
        'weaponsmith' => [
            'Bronze Dagger',
            'Iron Dagger',
            'Bronze Sword',
            'Iron Sword',
            'Steel Sword',
            'Bronze Axe',
            'Iron Battleaxe',
        ],
        'armorsmith' => [
            'Bronze Helm',
            'Iron Helm',
            'Bronze Platebody',
            'Iron Platebody',
            'Bronze Platelegs',
            'Iron Platelegs',
        ],
        'alchemist' => [
            'Minor Health Potion',
            'Health Potion',
            'Greater Health Potion',
            'Energy Elixir',
            'Antidote',
        ],
        'tanner' => [
            'Leather',
        ],
        'master_miner' => [
            'Copper Ore',
            'Tin Ore',
            'Iron Ore',
            'Coal',
            'Gold Ore',
            'Mithril Ore',
        ],
        'master_fisher' => [
            'Raw Trout',
            'Raw Salmon',
            'Raw Lobster',
        ],
        'brewmaster' => [
            // Future: Ale, Wine, Mead, etc.
        ],

        // Duchy roles
        'duchy_physician' => [
            'Bandage',
            'Medicine',
            'Antidote',
            'Healing Potion',
        ],
        'duchy_chef' => [
            'Cooked Meat',
            'Cooked Trout',
            'Cooked Salmon',
            'Cooked Lobster',
            'Meat Pie',
            'Bread',
            'Feast Platter',
        ],

        // Kingdom roles
        'royal_physician' => [
            'Bandage',
            'Medicine',
            'Antidote',
            'Healing Potion',
            'Greater Health Potion',
        ],
        'royal_chef' => [
            'Cooked Meat',
            'Cooked Trout',
            'Cooked Salmon',
            'Cooked Lobster',
            'Meat Pie',
            'Bread',
            'Feast Platter',
        ],
    ];

    public function __construct(
        protected InventoryService $inventoryService
    ) {}

    /**
     * Get items a player can stock based on their active roles at a location.
     */
    public function getStockableItems(User $user): Collection
    {
        $locationType = $user->current_location_type;
        $locationId = $user->current_location_id;

        // Get user's active roles at this location
        $activeRoles = PlayerRole::where('user_id', $user->id)
            ->where('location_type', $locationType)
            ->where('location_id', $locationId)
            ->where('status', 'active')
            ->with('role')
            ->get();

        if ($activeRoles->isEmpty()) {
            return collect();
        }

        // Collect all stockable item names for their roles
        $stockableItemNames = [];
        $isMerchant = false;

        foreach ($activeRoles as $playerRole) {
            $roleSlug = $playerRole->role->slug;
            $roleItems = self::ROLE_STOCK_ITEMS[$roleSlug] ?? [];

            if ($roleItems === '*') {
                $isMerchant = true;
                break;
            }

            $stockableItemNames = array_merge($stockableItemNames, $roleItems);
        }

        $stockableItemNames = array_unique($stockableItemNames);

        // Get items from player's inventory that they can stock
        $inventory = $user->inventory()->with('item')->get();

        return $inventory->filter(function ($slot) use ($stockableItemNames, $isMerchant) {
            if (!$slot->item) {
                return false;
            }

            // Merchant can stock any resource, consumable, tool, or misc
            if ($isMerchant) {
                return in_array($slot->item->type, ['resource', 'consumable', 'tool', 'misc']);
            }

            return in_array($slot->item->name, $stockableItemNames);
        })->map(function ($slot) {
            return [
                'inventory_id' => $slot->id,
                'item_id' => $slot->item->id,
                'item_name' => $slot->item->name,
                'item_type' => $slot->item->type,
                'quantity' => $slot->quantity,
                'base_value' => $slot->item->base_value,
                'slot_number' => $slot->slot_number,
            ];
        })->values();
    }

    /**
     * Stock an item from player's inventory to the location stockpile.
     */
    public function stockItem(User $user, int $itemId, int $quantity): array
    {
        $locationType = $user->current_location_type;
        $locationId = $user->current_location_id;

        // Verify user has an active role at this location
        $activeRoles = PlayerRole::where('user_id', $user->id)
            ->where('location_type', $locationType)
            ->where('location_id', $locationId)
            ->where('status', 'active')
            ->with('role')
            ->get();

        if ($activeRoles->isEmpty()) {
            return [
                'success' => false,
                'message' => 'You must hold a role at this location to stock the market.',
            ];
        }

        $item = Item::find($itemId);
        if (!$item) {
            return [
                'success' => false,
                'message' => 'Item not found.',
            ];
        }

        // Check if any of their roles allow stocking this item
        $canStock = false;
        foreach ($activeRoles as $playerRole) {
            $roleSlug = $playerRole->role->slug;
            $roleItems = self::ROLE_STOCK_ITEMS[$roleSlug] ?? [];

            if ($roleItems === '*') {
                // Merchant can stock anything marketable
                if (in_array($item->type, ['resource', 'consumable', 'tool', 'misc'])) {
                    $canStock = true;
                    break;
                }
            } elseif (in_array($item->name, $roleItems)) {
                $canStock = true;
                break;
            }
        }

        if (!$canStock) {
            return [
                'success' => false,
                'message' => 'Your role does not allow stocking this item.',
            ];
        }

        // Check player has the item
        if (!$this->inventoryService->hasItem($user, $item, $quantity)) {
            return [
                'success' => false,
                'message' => 'You don\'t have enough of this item.',
            ];
        }

        return DB::transaction(function () use ($user, $item, $quantity, $locationType, $locationId) {
            // Remove from player inventory
            $this->inventoryService->removeItem($user, $item, $quantity);

            // Add to location stockpile
            $stockpile = LocationStockpile::getOrCreate($locationType, $locationId, $item->id);
            $stockpile->addQuantity($quantity);

            return [
                'success' => true,
                'message' => "Stocked {$quantity}x {$item->name} in the market.",
                'new_stockpile_quantity' => $stockpile->fresh()->quantity,
            ];
        });
    }

    /**
     * Get the current stockpile for items a role holder manages.
     */
    public function getManagedStockpile(User $user): Collection
    {
        $locationType = $user->current_location_type;
        $locationId = $user->current_location_id;

        // Get user's active roles at this location
        $activeRoles = PlayerRole::where('user_id', $user->id)
            ->where('location_type', $locationType)
            ->where('location_id', $locationId)
            ->where('status', 'active')
            ->with('role')
            ->get();

        if ($activeRoles->isEmpty()) {
            return collect();
        }

        // Collect all stockable item names for their roles
        $stockableItemNames = [];
        $isMerchant = false;

        foreach ($activeRoles as $playerRole) {
            $roleSlug = $playerRole->role->slug;
            $roleItems = self::ROLE_STOCK_ITEMS[$roleSlug] ?? [];

            if ($roleItems === '*') {
                $isMerchant = true;
                break;
            }

            $stockableItemNames = array_merge($stockableItemNames, $roleItems);
        }

        $stockableItemNames = array_unique($stockableItemNames);

        // Get stockpile items
        $query = LocationStockpile::atLocation($locationType, $locationId)
            ->where('quantity', '>', 0)
            ->with('item');

        if (!$isMerchant) {
            $itemIds = Item::whereIn('name', $stockableItemNames)->pluck('id');
            $query->whereIn('item_id', $itemIds);
        }

        return $query->get()->map(function ($stockpile) {
            return [
                'item_id' => $stockpile->item_id,
                'item_name' => $stockpile->item->name,
                'item_type' => $stockpile->item->type,
                'quantity' => $stockpile->quantity,
                'base_value' => $stockpile->item->base_value,
            ];
        });
    }

    /**
     * Get roles that can stock a specific item.
     */
    public static function getRolesForItem(string $itemName): array
    {
        $roles = [];

        foreach (self::ROLE_STOCK_ITEMS as $roleSlug => $items) {
            if ($items === '*' || in_array($itemName, $items)) {
                $roles[] = $roleSlug;
            }
        }

        return $roles;
    }
}
