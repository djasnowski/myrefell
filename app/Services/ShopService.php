<?php

namespace App\Services;

use App\Models\Barony;
use App\Models\Duchy;
use App\Models\Shop;
use App\Models\ShopItem;
use App\Models\Town;
use App\Models\User;
use App\Models\Village;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ShopService
{
    public function __construct(
        protected InventoryService $inventoryService
    ) {}

    /**
     * Check if the user can access shops.
     */
    public function canAccessShops(User $user): bool
    {
        if ($user->isTraveling()) {
            return false;
        }

        if ($user->isInInfirmary()) {
            return false;
        }

        return true;
    }

    /**
     * Get all shops at the user's current location, including parent kingdom shops.
     */
    public function getShopsAtLocation(string $locationType, int $locationId): Collection
    {
        $shops = Shop::active()
            ->atLocation($locationType, $locationId)
            ->get();

        // Also include kingdom-level shops for child locations
        $kingdomId = $this->resolveKingdomId($locationType, $locationId);
        if ($kingdomId && $locationType !== 'kingdom') {
            $kingdomShops = Shop::active()
                ->atLocation('kingdom', $kingdomId)
                ->get();
            $shops = $shops->merge($kingdomShops);
        }

        return $shops;
    }

    /**
     * Get a shop with its items formatted for the frontend.
     */
    public function getShopWithItems(Shop $shop, User $user): array
    {
        $shop->load(['items' => function ($query) {
            $query->active()->with('item')->orderBy('sort_order');
        }]);

        // Check restocking for limited items
        foreach ($shop->items as $shopItem) {
            if ($shopItem->needsRestock()) {
                $shopItem->restock();
            }
        }

        return [
            'shop' => [
                'id' => $shop->id,
                'name' => $shop->name,
                'slug' => $shop->slug,
                'npc_name' => $shop->npc_name,
                'npc_description' => $shop->npc_description,
                'description' => $shop->description,
                'icon' => $shop->icon,
            ],
            'items' => $shop->items->map(fn (ShopItem $shopItem) => [
                'id' => $shopItem->id,
                'item_name' => $shopItem->item->name,
                'item_description' => $shopItem->item->description,
                'item_type' => $shopItem->item->type,
                'item_rarity' => $shopItem->item->rarity,
                'item_stackable' => $shopItem->item->stackable,
                'item_max_stack' => $shopItem->item->max_stack,
                'price' => $shopItem->price,
                'in_stock' => $shopItem->inStock(),
                'stock_quantity' => $shopItem->stock_quantity,
                'max_stock' => $shopItem->max_stock,
            ]),
            'player_gold' => $user->gold,
            'inventory_free_slots' => $this->inventoryService->freeSlots($user),
            'inventory_max_slots' => \App\Models\PlayerInventory::MAX_SLOTS,
        ];
    }

    /**
     * Buy an item from a shop.
     */
    public function buyItem(User $user, ShopItem $shopItem, int $quantity = 1): array
    {
        if (! $this->canAccessShops($user)) {
            return [
                'success' => false,
                'message' => 'You cannot access shops right now.',
            ];
        }

        // Verify the shop is at the user's location (or parent kingdom)
        $shop = $shopItem->shop;
        if (! $this->isShopAccessible($shop, $user)) {
            return [
                'success' => false,
                'message' => 'This shop is not at your current location.',
            ];
        }

        if (! $shopItem->is_active) {
            return [
                'success' => false,
                'message' => 'This item is no longer available.',
            ];
        }

        // Check stock
        if (! $shopItem->inStock()) {
            return [
                'success' => false,
                'message' => 'This item is out of stock.',
            ];
        }

        if ($shopItem->stock_quantity !== null && $shopItem->stock_quantity < $quantity) {
            return [
                'success' => false,
                'message' => "Only {$shopItem->stock_quantity} left in stock.",
            ];
        }

        $totalCost = $shopItem->price * $quantity;

        // Check gold
        if ($user->gold < $totalCost) {
            return [
                'success' => false,
                'message' => "You don't have enough gold. You need ".number_format($totalCost).' gold.',
            ];
        }

        // Check inventory space before committing
        $item = $shopItem->item;
        $slotsNeeded = $this->inventoryService->slotsNeededForItem($user, $item, $quantity);
        $freeSlots = $this->inventoryService->freeSlots($user);

        if ($slotsNeeded > $freeSlots) {
            if ($freeSlots <= 0) {
                return [
                    'success' => false,
                    'message' => 'Your inventory is full. Free up space before purchasing.',
                ];
            }

            return [
                'success' => false,
                'message' => "You need {$slotsNeeded} free inventory ".($slotsNeeded === 1 ? 'slot' : 'slots')." but only have {$freeSlots}. Try buying fewer or free up space.",
            ];
        }

        return DB::transaction(function () use ($user, $shopItem, $quantity, $totalCost) {
            // Deduct gold
            $user->decrement('gold', $totalCost);

            // Decrement stock if limited
            if ($shopItem->stock_quantity !== null) {
                $shopItem->decrement('stock_quantity', $quantity);
            }

            // Add item to inventory
            $added = $this->inventoryService->addItem($user, $shopItem->item_id, $quantity);

            if (! $added) {
                throw new \RuntimeException('Inventory full');
            }

            $itemName = $shopItem->item->name;
            $message = $quantity > 1
                ? "You purchased {$quantity}x {$itemName} for ".number_format($totalCost).' gold.'
                : "You purchased {$itemName} for ".number_format($totalCost).' gold.';

            return [
                'success' => true,
                'message' => $message,
            ];
        });
    }

    /**
     * Check if a shop is accessible to the user at their current location.
     */
    protected function isShopAccessible(Shop $shop, User $user): bool
    {
        // Direct match
        if ($shop->location_type === $user->current_location_type
            && $shop->location_id === $user->current_location_id) {
            return true;
        }

        // Kingdom shop accessible from child locations
        if ($shop->location_type === 'kingdom') {
            $kingdomId = $this->resolveKingdomId($user->current_location_type, $user->current_location_id);

            return $kingdomId === $shop->location_id;
        }

        return false;
    }

    /**
     * Resolve the kingdom ID for a given location.
     */
    protected function resolveKingdomId(string $locationType, int $locationId): ?int
    {
        return match ($locationType) {
            'kingdom' => $locationId,
            'duchy' => Duchy::find($locationId)?->kingdom_id,
            'barony' => Barony::find($locationId)?->kingdom_id,
            'town' => Town::find($locationId)?->barony?->kingdom_id,
            'village' => Village::find($locationId)?->barony?->kingdom_id,
            default => null,
        };
    }
}
