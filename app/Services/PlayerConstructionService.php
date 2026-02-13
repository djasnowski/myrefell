<?php

namespace App\Services;

use App\Config\ConstructionConfig;
use App\Models\Item;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PlayerConstructionService
{
    public function __construct(
        protected InventoryService $inventoryService,
        protected EnergyService $energyService
    ) {}

    /**
     * Check if the player can train construction.
     */
    public function canTrain(User $user): bool
    {
        return ! $user->isTraveling() && ! $user->isInInfirmary();
    }

    /**
     * Get the player's construction level.
     */
    public function getConstructionLevel(User $user): int
    {
        return $user->skills->where('skill_name', 'construction')->first()?->level ?? 1;
    }

    /**
     * Get available contract tiers for the player.
     */
    public function getAvailableContracts(User $user): array
    {
        $level = $this->getConstructionLevel($user);
        $contracts = [];

        foreach (ConstructionConfig::CONTRACT_TIERS as $tierId => $tier) {
            $plankItem = Item::where('name', $tier['plank_type'])->first();
            $playerPlanks = $plankItem ? $this->inventoryService->countItem($user, $plankItem) : 0;

            $contracts[] = [
                'id' => $tierId,
                'name' => $tier['name'],
                'level' => $tier['level'],
                'is_unlocked' => $level >= $tier['level'],
                'plank_type' => $tier['plank_type'],
                'planks_min' => $tier['planks'][0],
                'planks_max' => $tier['planks'][1],
                'xp_min' => $tier['xp'][0],
                'xp_max' => $tier['xp'][1],
                'gold_min' => $tier['gold'][0],
                'gold_max' => $tier['gold'][1],
                'energy_cost' => $tier['energy'],
                'player_planks' => $playerPlanks,
                'has_enough_planks' => $playerPlanks >= $tier['planks'][0],
                'has_enough_energy' => $user->energy >= $tier['energy'],
            ];
        }

        return $contracts;
    }

    /**
     * Complete a construction contract.
     */
    public function doContract(User $user, string $tierId): array
    {
        if (! $this->canTrain($user)) {
            return ['success' => false, 'message' => 'You cannot train right now.'];
        }

        $tier = ConstructionConfig::CONTRACT_TIERS[$tierId] ?? null;
        if (! $tier) {
            return ['success' => false, 'message' => 'Unknown contract tier.'];
        }

        $level = $this->getConstructionLevel($user);
        if ($level < $tier['level']) {
            return ['success' => false, 'message' => 'You need Construction level '.$tier['level'].' for this contract.'];
        }

        if (! $this->energyService->consumeEnergy($user, $tier['energy'])) {
            return ['success' => false, 'message' => 'Not enough energy.'];
        }

        // Determine random plank cost, XP, and gold within range
        $plankCost = rand($tier['planks'][0], $tier['planks'][1]);
        $xpReward = rand($tier['xp'][0], $tier['xp'][1]);
        $goldReward = rand($tier['gold'][0], $tier['gold'][1]);

        $plankItem = Item::where('name', $tier['plank_type'])->first();
        if (! $plankItem) {
            return ['success' => false, 'message' => 'Plank type not found.'];
        }

        if (! $this->inventoryService->hasItem($user, $plankItem, $plankCost)) {
            return ['success' => false, 'message' => 'Not enough '.$tier['plank_type'].'. You need '.$plankCost.'.'];
        }

        return DB::transaction(function () use ($user, $plankItem, $plankCost, $xpReward, $goldReward, $tier) {
            $this->inventoryService->removeItem($user, $plankItem, $plankCost);

            $user->gold += $goldReward;
            $user->save();

            $skill = $user->skills->where('skill_name', 'construction')->first();
            $levelsGained = $skill->addXp($xpReward);

            return [
                'success' => true,
                'message' => 'Contract completed! Used '.$plankCost.' '.$tier['plank_type'].'.',
                'xp_awarded' => $xpReward,
                'gold_awarded' => $goldReward,
                'planks_used' => $plankCost,
                'leveled_up' => $levelsGained > 0,
                'new_level' => $skill->level,
                'energy_remaining' => $user->energy,
            ];
        });
    }
}
