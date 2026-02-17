<?php

namespace App\Services;

use App\Models\Item;
use App\Models\PlayerInventory;
use App\Models\User;

class InventoryService
{
    /**
     * Add an item to a player's inventory.
     *
     * @return bool True if item was added successfully
     */
    public function addItem(User $player, Item|int $item, int $quantity = 1): bool
    {
        $item = $item instanceof Item ? $item : Item::find($item);

        if (! $item) {
            return false;
        }

        // If item is stackable, try to stack with existing items first
        if ($item->stackable) {
            $existingSlot = $player->inventory()
                ->where('item_id', $item->id)
                ->where('quantity', '<', $item->max_stack)
                ->first();

            if ($existingSlot) {
                $canAdd = min($quantity, $item->max_stack - $existingSlot->quantity);
                $existingSlot->increment('quantity', $canAdd);
                $quantity -= $canAdd;

                if ($quantity <= 0) {
                    return true;
                }
            }
        }

        // Find empty slots for remaining items
        while ($quantity > 0) {
            $emptySlot = $this->findEmptySlot($player);

            if ($emptySlot === null) {
                return false; // Inventory full
            }

            $addQuantity = $item->stackable ? min($quantity, $item->max_stack) : 1;

            PlayerInventory::create([
                'player_id' => $player->id,
                'item_id' => $item->id,
                'slot_number' => $emptySlot,
                'quantity' => $addQuantity,
                'is_equipped' => false,
            ]);

            $quantity -= $addQuantity;
        }

        return true;
    }

    /**
     * Remove an item from a player's inventory.
     * Does not remove equipped items.
     *
     * @return bool True if item was removed successfully
     */
    public function removeItem(User $player, Item|int $item, int $quantity = 1): bool
    {
        $itemId = $item instanceof Item ? $item->id : $item;

        $slots = $player->inventory()
            ->where('item_id', $itemId)
            ->where('is_equipped', false)
            ->orderBy('quantity', 'asc')
            ->get();

        $remaining = $quantity;

        foreach ($slots as $slot) {
            if ($remaining <= 0) {
                break;
            }

            if ($slot->quantity <= $remaining) {
                $remaining -= $slot->quantity;
                $slot->delete();
            } else {
                $slot->decrement('quantity', $remaining);
                $remaining = 0;
            }
        }

        return $remaining === 0;
    }

    /**
     * Check if player has a specific item.
     */
    public function hasItem(User $player, Item|int $item, int $quantity = 1, bool $excludeEquipped = false): bool
    {
        $itemId = $item instanceof Item ? $item->id : $item;

        $query = $player->inventory()
            ->where('item_id', $itemId);

        if ($excludeEquipped) {
            $query->where('is_equipped', false);
        }

        $total = $query->sum('quantity');

        return $total >= $quantity;
    }

    /**
     * Count how many of an item the player has.
     */
    public function countItem(User $player, Item|int $item): int
    {
        $itemId = $item instanceof Item ? $item->id : $item;

        return $player->inventory()
            ->where('item_id', $itemId)
            ->sum('quantity');
    }

    /**
     * Find an empty inventory slot.
     *
     * @return int|null The slot number, or null if inventory is full
     */
    public function findEmptySlot(User $player): ?int
    {
        $usedSlots = $player->inventory()
            ->pluck('slot_number')
            ->toArray();

        for ($i = 0; $i < PlayerInventory::MAX_SLOTS; $i++) {
            if (! in_array($i, $usedSlots)) {
                return $i;
            }
        }

        return null;
    }

    /**
     * Check if player has any empty inventory slots.
     */
    public function hasEmptySlot(User $player): bool
    {
        return $this->findEmptySlot($player) !== null;
    }

    /**
     * Calculate how many empty inventory slots are needed to hold a given quantity of an item,
     * accounting for existing partial stacks.
     */
    public function slotsNeededForItem(User $player, Item $item, int $quantity): int
    {
        $remaining = $quantity;

        // If stackable, account for space in existing partial stacks
        if ($item->stackable) {
            $existingSlots = $player->inventory()
                ->where('item_id', $item->id)
                ->where('quantity', '<', $item->max_stack)
                ->get();

            foreach ($existingSlots as $slot) {
                $canAdd = $item->max_stack - $slot->quantity;
                $remaining -= $canAdd;

                if ($remaining <= 0) {
                    return 0;
                }
            }

            // Remaining items need new slots, each holding up to max_stack
            return (int) ceil($remaining / $item->max_stack);
        }

        // Non-stackable: each item needs its own slot
        return $remaining;
    }

    /**
     * Get the number of free inventory slots.
     */
    public function freeSlots(User $player): int
    {
        $usedSlots = $player->inventory()->count();

        return PlayerInventory::MAX_SLOTS - $usedSlots;
    }

    /**
     * Get a summary of all resource and misc items in the player's inventory.
     *
     * @return array<int, array{name: string, quantity: int}>
     */
    public function getInventorySummary(User $player): array
    {
        return $player->inventory()
            ->join('items', 'player_inventory.item_id', '=', 'items.id')
            ->whereIn('items.type', ['resource', 'misc'])
            ->groupBy('items.id', 'items.name')
            ->orderBy('items.name')
            ->selectRaw('items.name, SUM(player_inventory.quantity) as quantity')
            ->get()
            ->map(fn ($row) => ['name' => $row->name, 'quantity' => (int) $row->quantity])
            ->values()
            ->toArray();
    }

    /**
     * Give starter items to a new player.
     */
    public function giveStarterKit(User $player): void
    {
        $starterItems = [
            'Bronze Dagger' => 1,
            'Wooden Shield' => 1,
            'Leather Vest' => 1,
            'Bread' => 10,
            'Bronze Pickaxe' => 1,
            'Fishing Rod' => 1,
        ];

        foreach ($starterItems as $itemName => $quantity) {
            $item = Item::where('name', $itemName)->first();
            if ($item) {
                $this->addItem($player, $item, $quantity);
            }
        }
    }
}
