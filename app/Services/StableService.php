<?php

namespace App\Services;

use App\Config\LocationServices;
use App\Models\Horse;
use App\Models\Item;
use App\Models\LocationStockpile;
use App\Models\PlayerHorse;
use App\Models\PlayerRole;
use App\Models\Role;
use App\Models\StableStock;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class StableService
{
    public const REST_COST = 50;

    public const FOOD_ITEM_SLUG = 'grain';

    public const STAMINA_PER_FOOD = 20;

    /**
     * Get horses available for purchase at a location.
     */
    public function getAvailableHorses(string $locationType): \Illuminate\Database\Eloquent\Collection
    {
        return Horse::availableAt($locationType);
    }

    /**
     * Get horses available at a location with stock quantities.
     * Stock restocks every few hours based on StableStock::RESTOCK_HOURS.
     */
    public function getStableStock(string $locationType, int $maxItems = 10): array
    {
        $availableHorses = $this->getAvailableHorses($locationType);
        $stock = [];

        foreach ($availableHorses as $horse) {
            // Get or create stock record for this location/horse
            $stableStock = StableStock::firstOrCreate(
                [
                    'location_type' => $locationType,
                    'horse_id' => $horse->id,
                ],
                [
                    'quantity' => StableStock::getMaxQuantityForRarity($horse->rarity),
                    'max_quantity' => StableStock::getMaxQuantityForRarity($horse->rarity),
                    'last_restocked_at' => now(),
                ]
            );

            // Check if restock is needed
            if ($stableStock->needsRestock()) {
                $stableStock->restock();
            }

            $stock[] = [
                'horse' => $horse,
                'price' => $horse->base_price,
                'in_stock' => $stableStock->inStock(),
                'quantity' => $stableStock->quantity,
                'max_quantity' => $stableStock->max_quantity,
                'restocks_at' => $stableStock->last_restocked_at->addHours(StableStock::RESTOCK_HOURS),
            ];
        }

        return $stock;
    }

    /**
     * Purchase a horse for a user.
     */
    public function buyHorse(User $user, Horse $horse, int $price, ?string $customName = null): array
    {
        // Check if user has reached max horses
        if (! $user->canBuyMoreHorses()) {
            return [
                'success' => false,
                'message' => 'You already own the maximum number of horses ('.PlayerHorse::MAX_HORSES_PER_USER.').',
            ];
        }

        // Check if user can afford it
        if ($user->gold < $price) {
            return [
                'success' => false,
                'message' => "You don't have enough gold. You need {$price} gold.",
            ];
        }

        // Check if horse is available at user's location
        if (! $horse->isAvailableAt($user->current_location_type)) {
            return [
                'success' => false,
                'message' => 'This horse is not available at your current location.',
            ];
        }

        // Check if horse is in stock
        $stableStock = StableStock::where('location_type', $user->current_location_type)
            ->where('horse_id', $horse->id)
            ->first();

        if (! $stableStock || ! $stableStock->inStock()) {
            return [
                'success' => false,
                'message' => 'This horse is currently out of stock. Check back later!',
            ];
        }

        return DB::transaction(function () use ($user, $horse, $price, $customName, $stableStock) {
            // Deduct gold
            $user->decrement('gold', $price);

            // Decrement stock
            $stableStock->decrementStock();

            // Check if this is the user's first horse
            $isFirstHorse = ! $user->hasHorse();
            $sortOrder = PlayerHorse::where('user_id', $user->id)->max('sort_order') ?? 0;

            // Create player horse record with full stamina
            $playerHorse = PlayerHorse::create([
                'user_id' => $user->id,
                'horse_id' => $horse->id,
                'is_active' => $isFirstHorse, // First horse is automatically active
                'sort_order' => $sortOrder + 1,
                'custom_name' => $customName,
                'purchase_price' => $price,
                'stamina' => $horse->base_stamina,
                'max_stamina' => $horse->base_stamina,
                'is_stabled' => ! $isFirstHorse, // New horses go to stable unless it's the first
                'stabled_location_type' => $isFirstHorse ? null : $user->current_location_type,
                'stabled_location_id' => $isFirstHorse ? null : $user->current_location_id,
                'purchased_at' => now(),
            ]);

            $message = $isFirstHorse
                ? "You purchased a {$horse->name} for {$price} gold!"
                : "You purchased a {$horse->name} for {$price} gold! It has been stabled here.";

            return [
                'success' => true,
                'message' => $message,
                'horse' => $playerHorse->load('horse'),
            ];
        });
    }

    /**
     * Sell a specific horse.
     */
    public function sellHorse(User $user, ?int $playerHorseId = null): array
    {
        // If no ID provided, use active horse (backwards compatibility)
        if ($playerHorseId) {
            $playerHorse = PlayerHorse::where('id', $playerHorseId)
                ->where('user_id', $user->id)
                ->first();
        } else {
            $playerHorse = $user->activeHorse;
        }

        if (! $playerHorse) {
            return [
                'success' => false,
                'message' => "You don't own this horse.",
            ];
        }

        $sellPrice = $playerHorse->sell_price;
        $horseName = $playerHorse->display_name;
        $wasActive = $playerHorse->is_active;

        return DB::transaction(function () use ($user, $playerHorse, $sellPrice, $horseName, $wasActive) {
            // Add gold
            $user->increment('gold', $sellPrice);

            // Delete the horse record
            $playerHorse->delete();

            // If we sold the active horse, activate another one if available
            if ($wasActive) {
                $nextHorse = PlayerHorse::where('user_id', $user->id)
                    ->orderBy('sort_order')
                    ->first();
                if ($nextHorse) {
                    $nextHorse->makeActive();
                }
            }

            return [
                'success' => true,
                'message' => "You sold {$horseName} for {$sellPrice} gold.",
                'gold_received' => $sellPrice,
            ];
        });
    }

    /**
     * Rename a specific horse.
     */
    public function renameHorse(User $user, string $newName, ?int $playerHorseId = null): array
    {
        if ($playerHorseId) {
            $playerHorse = PlayerHorse::where('id', $playerHorseId)
                ->where('user_id', $user->id)
                ->first();
        } else {
            $playerHorse = $user->activeHorse;
        }

        if (! $playerHorse) {
            return [
                'success' => false,
                'message' => "You don't own this horse.",
            ];
        }

        $playerHorse->update(['custom_name' => $newName]);

        return [
            'success' => true,
            'message' => "Your horse is now named {$newName}.",
        ];
    }

    /**
     * Get user's active horse info.
     */
    public function getUserHorse(User $user): ?array
    {
        $playerHorse = $user->activeHorse()->with('horse')->first();

        if (! $playerHorse) {
            return null;
        }

        return $this->formatPlayerHorse($playerHorse);
    }

    /**
     * Get all of user's horses.
     */
    public function getUserHorses(User $user): Collection
    {
        return $user->horses()
            ->with('horse')
            ->get()
            ->map(fn ($horse) => $this->formatPlayerHorse($horse));
    }

    /**
     * Format a player horse for frontend.
     */
    protected function formatPlayerHorse(PlayerHorse $playerHorse): array
    {
        return [
            'id' => $playerHorse->id,
            'name' => $playerHorse->display_name,
            'type' => $playerHorse->horse->name,
            'speed_multiplier' => $playerHorse->speed_multiplier,
            'stamina' => $playerHorse->stamina,
            'max_stamina' => $playerHorse->max_stamina,
            'stamina_cost' => $playerHorse->stamina_cost,
            'is_active' => $playerHorse->is_active,
            'is_stabled' => $playerHorse->is_stabled,
            'stabled_location_type' => $playerHorse->stabled_location_type,
            'stabled_location_id' => $playerHorse->stabled_location_id,
            'sell_price' => $playerHorse->sell_price,
            'purchased_at' => $playerHorse->purchased_at,
        ];
    }

    /**
     * Get all horses stabled at a location (with owner info).
     */
    public function getHorsesStabledAt(string $locationType, int $locationId): Collection
    {
        return PlayerHorse::stabledAt($locationType, $locationId)
            ->with(['horse', 'user'])
            ->get()
            ->map(fn ($horse) => [
                'id' => $horse->id,
                'name' => $horse->display_name,
                'type' => $horse->horse->name,
                'speed_multiplier' => $horse->speed_multiplier,
                'stamina' => $horse->stamina,
                'max_stamina' => $horse->max_stamina,
                'owner_id' => $horse->user_id,
                'owner_name' => $horse->user->username,
            ]);
    }

    /**
     * Stable a specific horse at the user's current location.
     */
    public function stableHorse(User $user, ?int $playerHorseId = null): array
    {
        if ($playerHorseId) {
            $playerHorse = PlayerHorse::where('id', $playerHorseId)
                ->where('user_id', $user->id)
                ->first();
        } else {
            $playerHorse = $user->activeHorse;
        }

        if (! $playerHorse) {
            return [
                'success' => false,
                'message' => "You don't own this horse.",
            ];
        }

        if ($playerHorse->is_stabled) {
            return [
                'success' => false,
                'message' => 'This horse is already stabled.',
            ];
        }

        $playerHorse->stable($user->current_location_type, $user->current_location_id);

        return [
            'success' => true,
            'message' => 'Your horse has been stabled here.',
        ];
    }

    /**
     * Retrieve a specific horse from stable (must be at same location).
     */
    public function retrieveHorse(User $user, ?int $playerHorseId = null): array
    {
        if ($playerHorseId) {
            $playerHorse = PlayerHorse::where('id', $playerHorseId)
                ->where('user_id', $user->id)
                ->first();
        } else {
            $playerHorse = $user->activeHorse;
        }

        if (! $playerHorse) {
            return [
                'success' => false,
                'message' => "You don't own this horse.",
            ];
        }

        if (! $playerHorse->is_stabled) {
            return [
                'success' => false,
                'message' => 'This horse is already with you.',
            ];
        }

        // Check if user is at the stable location
        if ($playerHorse->stabled_location_type !== $user->current_location_type ||
            $playerHorse->stabled_location_id !== $user->current_location_id) {
            return [
                'success' => false,
                'message' => 'This horse is stabled elsewhere. Travel there to retrieve it.',
            ];
        }

        $playerHorse->retrieve();

        // Make this horse active if user has no active horse
        if (! $user->hasActiveHorse()) {
            $playerHorse->makeActive();
        }

        return [
            'success' => true,
            'message' => 'You retrieved your horse from the stable.',
        ];
    }

    /**
     * Switch which horse is active.
     */
    public function switchActiveHorse(User $user, int $playerHorseId): array
    {
        $playerHorse = PlayerHorse::where('id', $playerHorseId)
            ->where('user_id', $user->id)
            ->first();

        if (! $playerHorse) {
            return [
                'success' => false,
                'message' => "You don't own this horse.",
            ];
        }

        if ($playerHorse->is_active) {
            return [
                'success' => false,
                'message' => 'This horse is already your active horse.',
            ];
        }

        if ($playerHorse->is_stabled) {
            return [
                'success' => false,
                'message' => 'You must retrieve this horse from the stable first.',
            ];
        }

        // Stable the current active horse if there is one
        $currentActive = $user->activeHorse;
        if ($currentActive && ! $currentActive->is_stabled) {
            $currentActive->stable($user->current_location_type, $user->current_location_id);
        }

        $playerHorse->makeActive();

        return [
            'success' => true,
            'message' => "{$playerHorse->display_name} is now your active horse.",
        ];
    }

    /**
     * Rest a specific horse at stable (costs gold, restores stamina).
     */
    public function restHorse(User $user, ?int $playerHorseId = null): array
    {
        if ($playerHorseId) {
            $playerHorse = PlayerHorse::where('id', $playerHorseId)
                ->where('user_id', $user->id)
                ->first();
        } else {
            $playerHorse = $user->activeHorse;
        }

        if (! $playerHorse) {
            return [
                'success' => false,
                'message' => "You don't own this horse.",
            ];
        }

        // Horse can rest if stabled OR if user is at a location with stables
        $canRest = $playerHorse->is_stabled;
        if (! $canRest && $user->current_location_type) {
            $canRest = LocationServices::isServiceAvailable($user->current_location_type, 'stables');
        }

        if (! $canRest) {
            return [
                'success' => false,
                'message' => 'You must be at a stable to rest your horse.',
            ];
        }

        if ($playerHorse->stamina >= $playerHorse->max_stamina) {
            return [
                'success' => false,
                'message' => 'This horse is already fully rested.',
            ];
        }

        if ($user->gold < self::REST_COST) {
            return [
                'success' => false,
                'message' => 'You need '.self::REST_COST.' gold to rest your horse.',
            ];
        }

        return DB::transaction(function () use ($user, $playerHorse) {
            $user->decrement('gold', self::REST_COST);
            $playerHorse->fullyRest();

            return [
                'success' => true,
                'message' => "{$playerHorse->display_name} has been fed and rested. Stamina fully restored!",
            ];
        });
    }

    /**
     * Check if user is a stablemaster at the given location.
     */
    public function isStablemaster(User $user, string $locationType, int $locationId): bool
    {
        $stablemasterRoles = Role::where('slug', 'like', '%stablemaster%')->pluck('id');

        return PlayerRole::where('user_id', $user->id)
            ->whereIn('role_id', $stablemasterRoles)
            ->where('location_type', $locationType)
            ->where('location_id', $locationId)
            ->active()
            ->exists();
    }

    /**
     * Stablemaster feeds all horses at the stable.
     * Consumes food from location stockpile and restores stamina.
     */
    public function stablemasterFeedHorses(User $user, string $locationType, int $locationId): array
    {
        // Check if user is stablemaster at this location
        if (! $this->isStablemaster($user, $locationType, $locationId)) {
            return [
                'success' => false,
                'message' => 'You must be the Stablemaster to feed the horses.',
            ];
        }

        // Get all horses stabled here that need feeding
        $horses = PlayerHorse::stabledAt($locationType, $locationId)
            ->where('stamina', '<', DB::raw('max_stamina'))
            ->get();

        if ($horses->isEmpty()) {
            return [
                'success' => false,
                'message' => 'All horses here are already fully fed and rested.',
            ];
        }

        // Get food item
        $foodItem = Item::where('slug', self::FOOD_ITEM_SLUG)->first();
        if (! $foodItem) {
            return [
                'success' => false,
                'message' => 'No food available to feed horses.',
            ];
        }

        // Check location stockpile for food
        $stockpile = LocationStockpile::atLocation($locationType, $locationId)
            ->forItem($foodItem->id)
            ->first();

        if (! $stockpile || $stockpile->quantity <= 0) {
            return [
                'success' => false,
                'message' => 'The stable has no grain to feed the horses. Stock the granary!',
            ];
        }

        return DB::transaction(function () use ($horses, $stockpile) {
            $horsesRestored = 0;
            $foodUsed = 0;

            foreach ($horses as $horse) {
                if ($stockpile->quantity <= 0) {
                    break;
                }

                $staminaNeeded = $horse->max_stamina - $horse->stamina;
                $foodNeeded = (int) ceil($staminaNeeded / self::STAMINA_PER_FOOD);
                $foodToUse = min($foodNeeded, $stockpile->quantity);

                if ($foodToUse > 0) {
                    $staminaRestored = min($foodToUse * self::STAMINA_PER_FOOD, $staminaNeeded);
                    $horse->restoreStamina($staminaRestored);
                    $stockpile->decrement('quantity', $foodToUse);
                    $foodUsed += $foodToUse;
                    $horsesRestored++;
                }
            }

            return [
                'success' => true,
                'message' => "Fed {$horsesRestored} horses using {$foodUsed} grain. They're feeling much better!",
                'horses_fed' => $horsesRestored,
                'food_used' => $foodUsed,
            ];
        });
    }
}
