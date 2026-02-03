<?php

namespace App\Services;

use App\Models\Monster;
use App\Models\User;

class LootService
{
    public function __construct(
        protected InventoryService $inventoryService,
        protected BlessingEffectService $blessingEffectService,
        protected BeliefEffectService $beliefEffectService
    ) {}

    /**
     * Roll for loot from a monster and give it to the player.
     * Applies blessing bonuses for gold find and rare drops.
     *
     * @return array{gold: int, items: array<array{name: string, quantity: int}>}
     */
    public function rollAndGiveLoot(User $player, Monster $monster): array
    {
        $rewards = [
            'gold' => 0,
            'items' => [],
        ];

        // Roll gold drop with blessing and belief bonuses
        $gold = $monster->rollGoldDrop();
        if ($gold > 0) {
            // Apply gold find bonus from blessings
            $goldBonus = $this->blessingEffectService->getEffect($player, 'gold_find_bonus');

            // Apply gold bonus from beliefs (Greed belief)
            $goldBonus += $this->beliefEffectService->getEffect($player, 'gold_bonus');

            // Apply gold penalty from beliefs (Asceticism belief)
            $goldBonus += $this->beliefEffectService->getEffect($player, 'gold_penalty');

            if ($goldBonus != 0) {
                $gold = (int) ceil($gold * (1 + $goldBonus / 100));
                $gold = max(1, $gold); // Minimum 1 gold
            }

            $player->increment('gold', $gold);
            $rewards['gold'] = $gold;
        }

        // Get rare drop bonus for loot rolls
        $rareDropBonus = $this->blessingEffectService->getEffect($player, 'rare_drop_bonus');

        // Roll for each loot table entry
        foreach ($monster->lootTable as $lootEntry) {
            $quantity = $lootEntry->rollDropWithBonus($rareDropBonus);
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
