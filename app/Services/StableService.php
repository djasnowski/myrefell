<?php

namespace App\Services;

use App\Models\Horse;
use App\Models\PlayerHorse;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class StableService
{
    /**
     * Get horses available for purchase at a location.
     */
    public function getAvailableHorses(string $locationType): \Illuminate\Database\Eloquent\Collection
    {
        return Horse::availableAt($locationType);
    }

    /**
     * Get a random selection of horses available at a location (simulating stock).
     */
    public function getStableStock(string $locationType, int $maxItems = 3): array
    {
        $availableHorses = $this->getAvailableHorses($locationType);
        $stock = [];

        foreach ($availableHorses as $horse) {
            // Use rarity to determine if horse is in stock
            if (random_int(1, 100) <= $horse->rarity) {
                $stock[] = [
                    'horse' => $horse,
                    'price' => $horse->getPriceWithVariance(15),
                    'in_stock' => true,
                ];
            } else {
                $stock[] = [
                    'horse' => $horse,
                    'price' => $horse->base_price,
                    'in_stock' => false,
                ];
            }
        }

        return $stock;
    }

    /**
     * Purchase a horse for a user.
     */
    public function buyHorse(User $user, Horse $horse, int $price, ?string $customName = null): array
    {
        // Check if user already has a horse
        if ($user->hasHorse()) {
            return [
                'success' => false,
                'message' => 'You already own a horse. Sell it first before buying another.',
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
        if (!$horse->isAvailableAt($user->current_location_type)) {
            return [
                'success' => false,
                'message' => 'This horse is not available at your current location.',
            ];
        }

        return DB::transaction(function () use ($user, $horse, $price, $customName) {
            // Deduct gold
            $user->decrement('gold', $price);

            // Create player horse record
            $playerHorse = PlayerHorse::create([
                'user_id' => $user->id,
                'horse_id' => $horse->id,
                'custom_name' => $customName,
                'purchase_price' => $price,
                'purchased_at' => now(),
            ]);

            return [
                'success' => true,
                'message' => "You purchased a {$horse->name} for {$price} gold!",
                'horse' => $playerHorse->load('horse'),
            ];
        });
    }

    /**
     * Sell the user's horse.
     */
    public function sellHorse(User $user): array
    {
        $playerHorse = $user->horse;

        if (!$playerHorse) {
            return [
                'success' => false,
                'message' => "You don't own a horse.",
            ];
        }

        $sellPrice = $playerHorse->sell_price;
        $horseName = $playerHorse->display_name;

        return DB::transaction(function () use ($user, $playerHorse, $sellPrice, $horseName) {
            // Add gold
            $user->increment('gold', $sellPrice);

            // Delete the horse record
            $playerHorse->delete();

            return [
                'success' => true,
                'message' => "You sold {$horseName} for {$sellPrice} gold.",
                'gold_received' => $sellPrice,
            ];
        });
    }

    /**
     * Rename the user's horse.
     */
    public function renameHorse(User $user, string $newName): array
    {
        $playerHorse = $user->horse;

        if (!$playerHorse) {
            return [
                'success' => false,
                'message' => "You don't own a horse.",
            ];
        }

        $playerHorse->update(['custom_name' => $newName]);

        return [
            'success' => true,
            'message' => "Your horse is now named {$newName}.",
        ];
    }

    /**
     * Get user's current horse info.
     */
    public function getUserHorse(User $user): ?array
    {
        $playerHorse = $user->horse()->with('horse')->first();

        if (!$playerHorse) {
            return null;
        }

        return [
            'id' => $playerHorse->id,
            'name' => $playerHorse->display_name,
            'type' => $playerHorse->horse->name,
            'speed_multiplier' => $playerHorse->speed_multiplier,
            'sell_price' => $playerHorse->sell_price,
            'purchased_at' => $playerHorse->purchased_at,
        ];
    }
}
