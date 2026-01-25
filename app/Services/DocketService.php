<?php

namespace App\Services;

use App\Models\CraftingOrder;
use App\Models\Item;
use App\Models\LocationStockpile;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DocketService
{
    /**
     * Valid location types for docket system.
     */
    public const VALID_LOCATIONS = ['village', 'barony', 'town'];

    /**
     * NPC crafting markup percentage (costs more than player crafting).
     */
    public const NPC_MARKUP = 1.5;

    /**
     * Player crafter payment percentage (of base gold cost).
     */
    public const PLAYER_PAYMENT_PERCENT = 0.3;

    /**
     * Order expiration time in hours.
     */
    public const ORDER_EXPIRATION_HOURS = 24;

    public function __construct(
        protected CraftingService $craftingService,
        protected InventoryService $inventoryService,
        protected DailyTaskService $dailyTaskService
    ) {}

    /**
     * Check if user can access the docket at their current location.
     */
    public function canAccessDocket(User $user): bool
    {
        if ($user->isTraveling()) {
            return false;
        }

        return in_array($user->current_location_type, self::VALID_LOCATIONS);
    }

    /**
     * Get docket information for a user at their current location.
     */
    public function getDocketInfo(User $user): ?array
    {
        if (! $this->canAccessDocket($user)) {
            return null;
        }

        $locationType = $user->current_location_type;
        $locationId = $user->current_location_id;

        // Get pending orders at this location (available for player crafters)
        $pendingOrders = CraftingOrder::atLocation($locationType, $locationId)
            ->pending()
            ->where('fulfillment_type', CraftingOrder::FULFILLMENT_PLAYER)
            ->with('customer')
            ->orderBy('created_at', 'asc')
            ->get();

        // Get user's accepted orders (as crafter)
        $myAcceptedOrders = CraftingOrder::forCrafter($user->id)
            ->accepted()
            ->with('customer')
            ->orderBy('due_at', 'asc')
            ->get();

        // Get user's placed orders (as customer)
        $myPlacedOrders = CraftingOrder::forCustomer($user->id)
            ->whereIn('status', [CraftingOrder::STATUS_PENDING, CraftingOrder::STATUS_ACCEPTED])
            ->with('crafter')
            ->orderBy('created_at', 'desc')
            ->get();

        // Get available recipes for NPC crafting
        $npcRecipes = $this->getNpcCraftingRecipes($user);

        return [
            'can_access' => true,
            'location_type' => $locationType,
            'location_id' => $locationId,
            'pending_orders' => $this->formatOrders($pendingOrders),
            'my_accepted_orders' => $this->formatOrders($myAcceptedOrders),
            'my_placed_orders' => $this->formatOrders($myPlacedOrders),
            'npc_recipes' => $npcRecipes,
            'player_gold' => $user->gold,
        ];
    }

    /**
     * Get recipes available for NPC instant crafting.
     */
    protected function getNpcCraftingRecipes(User $user): array
    {
        $recipes = [];

        foreach (CraftingService::RECIPES as $id => $recipe) {
            $goldCost = $this->calculateNpcCost($recipe);

            $recipes[] = [
                'id' => $id,
                'name' => $recipe['name'],
                'category' => $recipe['category'],
                'output' => $recipe['output'],
                'gold_cost' => $goldCost,
                'can_afford' => $user->gold >= $goldCost,
                'materials' => array_map(fn ($m) => [
                    'name' => $m['name'],
                    'quantity' => $m['quantity'],
                ], $recipe['materials']),
            ];
        }

        return $recipes;
    }

    /**
     * Calculate NPC crafting cost based on recipe.
     */
    protected function calculateNpcCost(array $recipe): int
    {
        // Base cost from materials value + energy cost + XP value
        $baseCost = 0;

        foreach ($recipe['materials'] as $material) {
            $item = Item::where('name', $material['name'])->first();
            if ($item) {
                $baseCost += ($item->base_value ?? 10) * $material['quantity'];
            } else {
                $baseCost += 10 * $material['quantity'];
            }
        }

        // Add energy and XP value
        $baseCost += $recipe['energy_cost'] * 5;
        $baseCost += $recipe['xp_reward'] * 2;

        // Apply NPC markup
        return (int) ceil($baseCost * self::NPC_MARKUP);
    }

    /**
     * Calculate player crafting cost (lower than NPC).
     */
    protected function calculatePlayerCost(array $recipe): int
    {
        $baseCost = 0;

        foreach ($recipe['materials'] as $material) {
            $item = Item::where('name', $material['name'])->first();
            if ($item) {
                $baseCost += ($item->base_value ?? 10) * $material['quantity'];
            } else {
                $baseCost += 10 * $material['quantity'];
            }
        }

        $baseCost += $recipe['energy_cost'] * 5;

        return $baseCost;
    }

    /**
     * Place an order for NPC instant crafting.
     */
    public function placeNpcOrder(User $user, string $recipeId, int $quantity = 1): array
    {
        $recipe = CraftingService::RECIPES[$recipeId] ?? null;

        if (! $recipe) {
            return ['success' => false, 'message' => 'Invalid recipe.'];
        }

        if (! $this->canAccessDocket($user)) {
            return ['success' => false, 'message' => 'You cannot access crafting services here.'];
        }

        $totalCost = $this->calculateNpcCost($recipe) * $quantity;

        if ($user->gold < $totalCost) {
            return ['success' => false, 'message' => "Not enough gold. Need {$totalCost} gold."];
        }

        // Check inventory space
        if (! $this->inventoryService->hasEmptySlot($user)) {
            return ['success' => false, 'message' => 'Your inventory is full.'];
        }

        // Get output item
        $outputItem = Item::where('name', $recipe['output']['name'])->first();
        if (! $outputItem) {
            return ['success' => false, 'message' => 'Output item not found.'];
        }

        return DB::transaction(function () use ($user, $recipe, $recipeId, $quantity, $totalCost, $outputItem) {
            // Deduct gold
            $user->decrement('gold', $totalCost);

            // Create completed order record
            CraftingOrder::create([
                'customer_id' => $user->id,
                'crafter_id' => null,
                'recipe_id' => $recipeId,
                'quantity' => $quantity,
                'location_type' => $user->current_location_type,
                'location_id' => $user->current_location_id,
                'gold_cost' => $totalCost,
                'crafter_payment' => 0,
                'status' => CraftingOrder::STATUS_COMPLETED,
                'fulfillment_type' => CraftingOrder::FULFILLMENT_NPC,
                'completed_at' => now(),
            ]);

            // Give item to player
            $totalQuantity = $recipe['output']['quantity'] * $quantity;
            $this->inventoryService->addItem($user, $outputItem, $totalQuantity);

            return [
                'success' => true,
                'message' => "Purchased {$totalQuantity}x {$recipe['output']['name']} for {$totalCost} gold!",
                'item' => [
                    'name' => $outputItem->name,
                    'quantity' => $totalQuantity,
                ],
                'gold_spent' => $totalCost,
                'gold_remaining' => $user->fresh()->gold,
            ];
        });
    }

    /**
     * Place an order for player crafting (goes to docket).
     */
    public function placePlayerOrder(User $user, string $recipeId, int $quantity = 1): array
    {
        $recipe = CraftingService::RECIPES[$recipeId] ?? null;

        if (! $recipe) {
            return ['success' => false, 'message' => 'Invalid recipe.'];
        }

        if (! $this->canAccessDocket($user)) {
            return ['success' => false, 'message' => 'You cannot access crafting services here.'];
        }

        $goldCost = $this->calculatePlayerCost($recipe) * $quantity;
        $crafterPayment = (int) ceil($goldCost * self::PLAYER_PAYMENT_PERCENT);

        if ($user->gold < $goldCost) {
            return ['success' => false, 'message' => "Not enough gold. Need {$goldCost} gold."];
        }

        return DB::transaction(function () use ($user, $recipe, $recipeId, $quantity, $goldCost, $crafterPayment) {
            // Deduct gold from customer
            $user->decrement('gold', $goldCost);

            // Create pending order
            $order = CraftingOrder::create([
                'customer_id' => $user->id,
                'crafter_id' => null,
                'recipe_id' => $recipeId,
                'quantity' => $quantity,
                'location_type' => $user->current_location_type,
                'location_id' => $user->current_location_id,
                'gold_cost' => $goldCost,
                'crafter_payment' => $crafterPayment,
                'status' => CraftingOrder::STATUS_PENDING,
                'fulfillment_type' => CraftingOrder::FULFILLMENT_PLAYER,
                'expires_at' => now()->addHours(self::ORDER_EXPIRATION_HOURS),
            ]);

            return [
                'success' => true,
                'message' => "Order placed for {$quantity}x {$recipe['output']['name']}. Waiting for a crafter.",
                'order_id' => $order->id,
                'gold_spent' => $goldCost,
                'gold_remaining' => $user->fresh()->gold,
            ];
        });
    }

    /**
     * Accept a crafting order (as a player crafter).
     */
    public function acceptOrder(User $user, CraftingOrder $order): array
    {
        if (! $order->isPending()) {
            return ['success' => false, 'message' => 'This order is no longer available.'];
        }

        if ($order->customer_id === $user->id) {
            return ['success' => false, 'message' => 'You cannot accept your own order.'];
        }

        // Check if crafter is at the right location
        if ($user->current_location_type !== $order->location_type ||
            $user->current_location_id !== $order->location_id) {
            return ['success' => false, 'message' => 'You must be at the order location to accept it.'];
        }

        $recipe = CraftingService::RECIPES[$order->recipe_id] ?? null;
        if (! $recipe) {
            return ['success' => false, 'message' => 'Invalid recipe for this order.'];
        }

        // Check if crafter has required skill level
        $skillLevel = $user->getSkillLevel($recipe['skill']);
        if ($skillLevel < $recipe['required_level']) {
            return [
                'success' => false,
                'message' => "You need level {$recipe['required_level']} {$recipe['skill']} to craft this.",
            ];
        }

        return DB::transaction(function () use ($user, $order) {
            $order->update([
                'crafter_id' => $user->id,
                'status' => CraftingOrder::STATUS_ACCEPTED,
                'accepted_at' => now(),
                'due_at' => now()->addMinutes(CraftingOrder::TARDINESS_THRESHOLD_MINUTES),
            ]);

            return [
                'success' => true,
                'message' => 'Order accepted! You have 10 minutes to complete it.',
                'order_id' => $order->id,
                'due_at' => $order->fresh()->due_at->toIso8601String(),
            ];
        });
    }

    /**
     * Complete a crafting order (as a player crafter).
     */
    public function completeOrder(User $user, CraftingOrder $order): array
    {
        if (! $order->isAccepted()) {
            return ['success' => false, 'message' => 'This order cannot be completed.'];
        }

        if ($order->crafter_id !== $user->id) {
            return ['success' => false, 'message' => 'This is not your order to complete.'];
        }

        $recipe = CraftingService::RECIPES[$order->recipe_id] ?? null;
        if (! $recipe) {
            return ['success' => false, 'message' => 'Invalid recipe for this order.'];
        }

        // Check crafter has materials
        foreach ($recipe['materials'] as $material) {
            $item = Item::where('name', $material['name'])->first();
            $neededQty = $material['quantity'] * $order->quantity;
            if (! $item || ! $this->inventoryService->hasItem($user, $item, $neededQty)) {
                return [
                    'success' => false,
                    'message' => "You don't have enough {$material['name']}.",
                ];
            }
        }

        // Check crafter has energy
        $energyCost = $recipe['energy_cost'] * $order->quantity;
        if (! $user->hasEnergy($energyCost)) {
            return [
                'success' => false,
                'message' => "Not enough energy. Need {$energyCost} energy.",
            ];
        }

        // Get output item
        $outputItem = Item::where('name', $recipe['output']['name'])->first();
        if (! $outputItem) {
            return ['success' => false, 'message' => 'Output item not found.'];
        }

        // Check customer has inventory space
        $customer = $order->customer;
        if (! $this->inventoryService->hasEmptySlot($customer)) {
            return ['success' => false, 'message' => "Customer's inventory is full. They need to make space."];
        }

        return DB::transaction(function () use ($user, $order, $recipe, $outputItem) {
            $customer = $order->customer;
            $energyCost = $recipe['energy_cost'] * $order->quantity;

            // Consume crafter's materials
            foreach ($recipe['materials'] as $material) {
                $item = Item::where('name', $material['name'])->first();
                $neededQty = $material['quantity'] * $order->quantity;
                $this->inventoryService->removeItem($user, $item, $neededQty);
            }

            // Consume crafter's energy
            $user->consumeEnergy($energyCost);

            // Award XP to crafter
            $xpReward = $recipe['xp_reward'] * $order->quantity;
            $skill = $user->skills()->where('skill_name', $recipe['skill'])->first();
            $leveledUp = false;

            if ($skill) {
                $oldLevel = $skill->level;
                $skill->addXp($xpReward);
                $leveledUp = $skill->fresh()->level > $oldLevel;
            }

            // Give item to customer
            $totalQuantity = $recipe['output']['quantity'] * $order->quantity;
            $this->inventoryService->addItem($customer, $outputItem, $totalQuantity);

            // Pay crafter
            $user->increment('gold', $order->crafter_payment);

            // Update order status
            $order->update([
                'status' => CraftingOrder::STATUS_COMPLETED,
                'completed_at' => now(),
            ]);

            // Record daily task progress for crafter
            if (isset($recipe['task_type'])) {
                $this->dailyTaskService->recordProgress(
                    $user,
                    $recipe['task_type'],
                    $outputItem->name,
                    $totalQuantity
                );
            }

            return [
                'success' => true,
                'message' => "Completed order! Earned {$order->crafter_payment} gold and {$xpReward} XP.",
                'gold_earned' => $order->crafter_payment,
                'xp_earned' => $xpReward,
                'skill' => $recipe['skill'],
                'leveled_up' => $leveledUp,
                'energy_remaining' => $user->fresh()->energy,
            ];
        });
    }

    /**
     * Cancel a crafting order (as the customer).
     */
    public function cancelOrder(User $user, CraftingOrder $order): array
    {
        if ($order->customer_id !== $user->id) {
            return ['success' => false, 'message' => 'This is not your order.'];
        }

        if (! $order->isPending()) {
            return ['success' => false, 'message' => 'This order cannot be cancelled (already accepted or completed).'];
        }

        return DB::transaction(function () use ($user, $order) {
            // Refund gold (minus small cancellation fee)
            $refund = (int) ceil($order->gold_cost * 0.9);
            $user->increment('gold', $refund);

            $order->update([
                'status' => CraftingOrder::STATUS_CANCELLED,
            ]);

            return [
                'success' => true,
                'message' => "Order cancelled. Refunded {$refund} gold (10% cancellation fee).",
                'gold_refunded' => $refund,
                'gold_remaining' => $user->fresh()->gold,
            ];
        });
    }

    /**
     * Abandon an accepted order (as the crafter).
     */
    public function abandonOrder(User $user, CraftingOrder $order): array
    {
        if ($order->crafter_id !== $user->id) {
            return ['success' => false, 'message' => 'This is not your order.'];
        }

        if (! $order->isAccepted()) {
            return ['success' => false, 'message' => 'This order cannot be abandoned.'];
        }

        return DB::transaction(function () use ($order) {
            // Return to pending status
            $order->update([
                'crafter_id' => null,
                'status' => CraftingOrder::STATUS_PENDING,
                'accepted_at' => null,
                'due_at' => null,
            ]);

            return [
                'success' => true,
                'message' => 'Order abandoned. It is now available for other crafters.',
            ];
        });
    }

    /**
     * Format orders for display.
     */
    protected function formatOrders($orders): array
    {
        return $orders->map(function ($order) {
            $recipe = CraftingService::RECIPES[$order->recipe_id] ?? null;

            return [
                'id' => $order->id,
                'recipe_id' => $order->recipe_id,
                'recipe_name' => $recipe['name'] ?? 'Unknown',
                'category' => $recipe['category'] ?? 'unknown',
                'quantity' => $order->quantity,
                'output' => $recipe ? [
                    'name' => $recipe['output']['name'],
                    'quantity' => $recipe['output']['quantity'] * $order->quantity,
                ] : null,
                'materials' => $recipe ? array_map(fn ($m) => [
                    'name' => $m['name'],
                    'quantity' => $m['quantity'] * $order->quantity,
                ], $recipe['materials']) : [],
                'gold_cost' => $order->gold_cost,
                'crafter_payment' => $order->crafter_payment,
                'status' => $order->status,
                'fulfillment_type' => $order->fulfillment_type,
                'customer' => $order->customer ? [
                    'id' => $order->customer->id,
                    'username' => $order->customer->username,
                ] : null,
                'crafter' => $order->crafter ? [
                    'id' => $order->crafter->id,
                    'username' => $order->crafter->username,
                ] : null,
                'is_tardy' => $order->isTardy(),
                'minutes_until_due' => $order->minutes_until_due,
                'accepted_at' => $order->accepted_at?->toIso8601String(),
                'due_at' => $order->due_at?->toIso8601String(),
                'created_at' => $order->created_at->toIso8601String(),
            ];
        })->toArray();
    }

    /**
     * Expire stale orders.
     */
    public function expireStaleOrders(): int
    {
        $expiredCount = CraftingOrder::pending()
            ->where('expires_at', '<', now())
            ->update(['status' => CraftingOrder::STATUS_EXPIRED]);

        // Refund expired orders
        $expiredOrders = CraftingOrder::where('status', CraftingOrder::STATUS_EXPIRED)
            ->whereNull('completed_at')
            ->get();

        foreach ($expiredOrders as $order) {
            DB::transaction(function () use ($order) {
                $customer = $order->customer;
                if ($customer) {
                    // Full refund for expired orders
                    $customer->increment('gold', $order->gold_cost);
                }
                $order->update(['completed_at' => now()]);
            });
        }

        return $expiredCount;
    }
}
