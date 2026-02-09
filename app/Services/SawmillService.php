<?php

namespace App\Services;

use App\Config\ConstructionConfig;
use App\Models\Item;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class SawmillService
{
    public function __construct(
        protected InventoryService $inventoryService
    ) {}

    /**
     * Get available plank recipes with player's current log counts.
     */
    public function getAvailablePlanks(User $user): array
    {
        $recipes = [];

        foreach (ConstructionConfig::PLANK_RECIPES as $plankName => $recipe) {
            $logItem = Item::where('name', $recipe['log'])->first();
            $plankItem = Item::where('name', $plankName)->first();

            $recipes[] = [
                'plank_name' => $plankName,
                'log_name' => $recipe['log'],
                'fee' => $recipe['fee'],
                'player_logs' => $logItem ? $this->inventoryService->countItem($user, $logItem) : 0,
                'plank_value' => $plankItem?->base_value ?? 0,
            ];
        }

        return $recipes;
    }

    /**
     * Convert logs into planks.
     */
    public function makePlanks(User $user, string $plankName, int $quantity): array
    {
        $recipe = ConstructionConfig::PLANK_RECIPES[$plankName] ?? null;
        if (! $recipe) {
            return ['success' => false, 'message' => 'Unknown plank type.'];
        }

        if ($quantity < 1) {
            return ['success' => false, 'message' => 'Invalid quantity.'];
        }

        $totalFee = $recipe['fee'] * $quantity;
        if ($user->gold < $totalFee) {
            return ['success' => false, 'message' => 'Not enough gold. You need '.number_format($totalFee).' gold.'];
        }

        $logItem = Item::where('name', $recipe['log'])->first();
        if (! $logItem) {
            return ['success' => false, 'message' => 'Log type not found.'];
        }

        $plankItem = Item::where('name', $plankName)->first();
        if (! $plankItem) {
            return ['success' => false, 'message' => 'Plank type not found.'];
        }

        if (! $this->inventoryService->hasItem($user, $logItem, $quantity)) {
            return ['success' => false, 'message' => 'Not enough '.$recipe['log'].'. You need '.$quantity.' but only have '.$this->inventoryService->countItem($user, $logItem).'.'];
        }

        return DB::transaction(function () use ($user, $logItem, $plankItem, $quantity, $totalFee, $plankName, $recipe) {
            $user->gold -= $totalFee;
            $user->save();

            $this->inventoryService->removeItem($user, $logItem, $quantity);
            $this->inventoryService->addItem($user, $plankItem, $quantity);

            return [
                'success' => true,
                'message' => 'Converted '.$quantity.' '.$recipe['log'].' into '.$quantity.' '.$plankName.' for '.number_format($totalFee).' gold.',
                'planks_made' => $quantity,
                'gold_spent' => $totalFee,
            ];
        });
    }
}
