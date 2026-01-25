<?php

namespace App\Services;

use App\Models\Monster;
use App\Models\User;

class LootService
{
    public function __construct(
        protected InventoryService $inventoryService
    ) {}

    /**
     * Roll for loot from a monster and give it to the player.
     *
     * @return array{gold: int, items: array<array{name: string, quantity: int}>}
     */
    public function rollAndGiveLoot(User $player, Monster $monster): array
    {
        $rewards = [
            'gold' => 0,
            'items' => [],
        ];

        // Roll gold drop
        $gold = $monster->rollGoldDrop();
        if ($gold > 0) {
            $player->increment('gold', $gold);
            $rewards['gold'] = $gold;
        }

        // Roll for each loot table entry
        foreach ($monster->lootTable as $lootEntry) {
            $quantity = $lootEntry->rollDrop();
            if ($quantity > 0) {
                $item = $lootEntry->item;
                $added = $this->inventoryService->addItem($player, $item, $quantity);

                if ($added) {
                    $rewards['items'][] = [
                        'name' => $item->name,
                        'quantity' => $quantity,
                    ];
                }
            }
        }

        return $rewards;
    }
}
