<?php

namespace App\Services;

use App\Models\DungeonLootStorage;
use App\Models\Kingdom;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DungeonLootService
{
    public function __construct(
        protected InventoryService $inventoryService
    ) {}

    /**
     * Get all loot stored for a player, optionally filtered by kingdom.
     */
    public function getPlayerLoot(User $player, ?Kingdom $kingdom = null): Collection
    {
        $query = DungeonLootStorage::query()
            ->forUser($player->id)
            ->notExpired()
            ->with(['item', 'kingdom']);

        if ($kingdom) {
            $query->inKingdom($kingdom->id);
        }

        return $query->orderBy('kingdom_id')->orderBy('expires_at')->get();
    }

    /**
     * Claim loot from storage and move to inventory.
     *
     * @return array{success: bool, message: string, quantity?: int}
     */
    public function claimLoot(User $player, int $storageId, ?int $quantity = null): array
    {
        $storage = DungeonLootStorage::query()
            ->forUser($player->id)
            ->notExpired()
            ->find($storageId);

        if (! $storage) {
            return ['success' => false, 'message' => 'Loot not found or has expired.'];
        }

        $claimQuantity = $quantity ?? $storage->quantity;
        $claimQuantity = min($claimQuantity, $storage->quantity);

        if ($claimQuantity <= 0) {
            return ['success' => false, 'message' => 'Invalid quantity.'];
        }

        // Calculate how many items can actually fit in inventory
        $item = $storage->item;
        $freeSlots = $this->inventoryService->freeSlots($player);
        $canFit = 0;

        if ($item->stackable) {
            // Space in existing partial stacks
            $existingSlots = $player->inventory()
                ->where('item_id', $item->id)
                ->where('quantity', '<', $item->max_stack)
                ->get();

            foreach ($existingSlots as $slot) {
                $canFit += $item->max_stack - $slot->quantity;
            }

            // Space in new slots
            $canFit += $freeSlots * $item->max_stack;
        } else {
            $canFit = $freeSlots;
        }

        if ($canFit <= 0) {
            return ['success' => false, 'message' => 'Your inventory is full. Free up some space first.'];
        }

        // Cap to what actually fits
        $claimQuantity = min($claimQuantity, $canFit);

        return DB::transaction(function () use ($player, $storage, $claimQuantity, $item) {
            // Add to inventory
            $added = $this->inventoryService->addItem($player, $item, $claimQuantity);

            if (! $added) {
                return ['success' => false, 'message' => 'Could not add items to inventory. It may be full.'];
            }

            // Update or remove storage record
            if ($claimQuantity >= $storage->quantity) {
                $storage->delete();
            } else {
                $storage->decrement('quantity', $claimQuantity);
            }

            $message = "Claimed {$claimQuantity}x {$item->name}.";
            if ($claimQuantity < $storage->quantity) {
                $remaining = $storage->quantity - $claimQuantity;
                $message .= " {$remaining}x left in storage (inventory full).";
            }

            return [
                'success' => true,
                'message' => $message,
                'quantity' => $claimQuantity,
            ];
        });
    }

    /**
     * Claim all loot from a specific kingdom.
     *
     * @return array{success: bool, message: string, claimed?: array<string, int>}
     */
    public function claimAllLoot(User $player, int $kingdomId): array
    {
        $lootEntries = DungeonLootStorage::query()
            ->forUser($player->id)
            ->inKingdom($kingdomId)
            ->notExpired()
            ->with('item')
            ->get();

        if ($lootEntries->isEmpty()) {
            return ['success' => false, 'message' => 'No loot to claim.'];
        }

        // Check inventory space - we need at least some free slots
        $freeSlots = $this->inventoryService->freeSlots($player);
        if ($freeSlots === 0) {
            return ['success' => false, 'message' => 'Your inventory is full. Free up some space first.'];
        }

        return DB::transaction(function () use ($player, $lootEntries) {
            $claimed = [];
            $failed = [];

            foreach ($lootEntries as $storage) {
                $item = $storage->item;

                // Calculate how many can fit
                $freeSlots = $this->inventoryService->freeSlots($player);
                $canFit = 0;

                if ($item->stackable) {
                    $existingSlots = $player->inventory()
                        ->where('item_id', $item->id)
                        ->where('quantity', '<', $item->max_stack)
                        ->get();

                    foreach ($existingSlots as $slot) {
                        $canFit += $item->max_stack - $slot->quantity;
                    }

                    $canFit += $freeSlots * $item->max_stack;
                } else {
                    $canFit = $freeSlots;
                }

                if ($canFit <= 0) {
                    $failed[$item->name] = $storage->quantity;

                    continue;
                }

                $claimQty = min($storage->quantity, $canFit);
                $added = $this->inventoryService->addItem($player, $item, $claimQty);

                if ($added) {
                    $claimed[$item->name] = ($claimed[$item->name] ?? 0) + $claimQty;

                    if ($claimQty >= $storage->quantity) {
                        $storage->delete();
                    } else {
                        $storage->decrement('quantity', $claimQty);
                        $failed[$item->name] = ($failed[$item->name] ?? 0) + ($storage->quantity - $claimQty);
                    }
                } else {
                    $failed[$item->name] = ($failed[$item->name] ?? 0) + $storage->quantity;
                }
            }

            if (empty($claimed)) {
                return ['success' => false, 'message' => 'Could not claim any items. Your inventory may be full.'];
            }

            $itemCount = array_sum($claimed);
            $typeCount = count($claimed);

            $message = "Claimed {$itemCount} items ({$typeCount} types).";
            if (! empty($failed)) {
                $failedCount = count($failed);
                $message .= " {$failedCount} item(s) could not be claimed due to insufficient inventory space.";
            }

            return [
                'success' => true,
                'message' => $message,
                'claimed' => $claimed,
                'failed' => $failed,
            ];
        });
    }

    /**
     * Clean up expired loot entries.
     */
    public static function cleanupExpiredLoot(): int
    {
        return DungeonLootStorage::where('expires_at', '<', now())->delete();
    }

    /**
     * Get total loot count for player across all kingdoms.
     */
    public function getTotalLootCount(User $player): int
    {
        return DungeonLootStorage::query()
            ->forUser($player->id)
            ->notExpired()
            ->sum('quantity');
    }
}
