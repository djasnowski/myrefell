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

        // Check if inventory has space
        if (! $this->inventoryService->hasEmptySlot($player)) {
            // Check if item can stack with existing
            $existingSlot = $player->inventory()
                ->where('item_id', $storage->item_id)
                ->where('quantity', '<', $storage->item->max_stack ?? 1)
                ->first();

            if (! $existingSlot) {
                return ['success' => false, 'message' => 'Your inventory is full. Free up some space first.'];
            }
        }

        return DB::transaction(function () use ($player, $storage, $claimQuantity) {
            // Add to inventory
            $added = $this->inventoryService->addItem($player, $storage->item, $claimQuantity);

            if (! $added) {
                return ['success' => false, 'message' => 'Could not add items to inventory. It may be full.'];
            }

            // Update or remove storage record
            if ($claimQuantity >= $storage->quantity) {
                $storage->delete();
            } else {
                $storage->decrement('quantity', $claimQuantity);
            }

            return [
                'success' => true,
                'message' => "Claimed {$claimQuantity}x {$storage->item->name}.",
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
                $added = $this->inventoryService->addItem($player, $storage->item, $storage->quantity);

                if ($added) {
                    $claimed[$storage->item->name] = $storage->quantity;
                    $storage->delete();
                } else {
                    $failed[$storage->item->name] = $storage->quantity;
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
