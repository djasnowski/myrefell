<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class HealerService
{
    /**
     * Base cost per HP to heal.
     */
    public const COST_PER_HP = 2;

    /**
     * Location types that have healers.
     * Note: Hamlets use their parent village's healer.
     */
    public const VALID_LOCATIONS = ['village', 'barony', 'town'];

    /**
     * Check if user can access a healer at their current location.
     */
    public function canAccessHealer(User $user): bool
    {
        if ($user->isTraveling()) {
            return false;
        }

        return in_array($user->current_location_type, self::VALID_LOCATIONS);
    }

    /**
     * Get the healing options available.
     */
    public function getHealingOptions(User $user): array
    {
        $missingHp = $user->max_hp - $user->hp;

        if ($missingHp <= 0) {
            return [];
        }

        $options = [];

        // Heal 25%
        $heal25 = (int) ceil($missingHp * 0.25);
        if ($heal25 > 0) {
            $options[] = [
                'id' => 'heal_25',
                'label' => 'Minor Healing',
                'description' => 'Restore 25% of missing health',
                'hp_restored' => $heal25,
                'cost' => $this->calculateCost($heal25),
            ];
        }

        // Heal 50%
        $heal50 = (int) ceil($missingHp * 0.5);
        if ($heal50 > 0) {
            $options[] = [
                'id' => 'heal_50',
                'label' => 'Standard Healing',
                'description' => 'Restore 50% of missing health',
                'hp_restored' => $heal50,
                'cost' => $this->calculateCost($heal50),
            ];
        }

        // Heal 100%
        if ($missingHp > 0) {
            $options[] = [
                'id' => 'heal_full',
                'label' => 'Full Recovery',
                'description' => 'Restore all missing health',
                'hp_restored' => $missingHp,
                'cost' => $this->calculateCost($missingHp),
            ];
        }

        return $options;
    }

    /**
     * Calculate healing cost.
     */
    public function calculateCost(int $hpToRestore): int
    {
        return $hpToRestore * self::COST_PER_HP;
    }

    /**
     * Heal the player.
     */
    public function heal(User $user, int $amount): array
    {
        if (! $this->canAccessHealer($user)) {
            return [
                'success' => false,
                'message' => 'You cannot access a healer here.',
            ];
        }

        $missingHp = $user->max_hp - $user->hp;

        if ($missingHp <= 0) {
            return [
                'success' => false,
                'message' => 'You are already at full health.',
            ];
        }

        // Clamp amount to missing HP
        $hpToRestore = min($amount, $missingHp);
        $cost = $this->calculateCost($hpToRestore);

        if ($user->gold < $cost) {
            return [
                'success' => false,
                'message' => "You don't have enough gold. Need {$cost} gold.",
            ];
        }

        return DB::transaction(function () use ($user, $hpToRestore, $cost) {
            $user->decrement('gold', $cost);
            $user->increment('hp', $hpToRestore);

            return [
                'success' => true,
                'message' => "Restored {$hpToRestore} HP for {$cost} gold.",
                'hp_restored' => $hpToRestore,
                'cost' => $cost,
                'new_hp' => $user->fresh()->hp,
                'gold_remaining' => $user->fresh()->gold,
            ];
        });
    }

    /**
     * Heal using a predefined option.
     */
    public function healByOption(User $user, string $optionId): array
    {
        $options = $this->getHealingOptions($user);
        $option = collect($options)->firstWhere('id', $optionId);

        if (! $option) {
            return [
                'success' => false,
                'message' => 'Invalid healing option.',
            ];
        }

        return $this->heal($user, $option['hp_restored']);
    }

    /**
     * Get healer info for the current location.
     */
    public function getHealerInfo(User $user): ?array
    {
        if (! $this->canAccessHealer($user)) {
            return null;
        }

        $location = $this->resolveLocation($user->current_location_type, $user->current_location_id);
        $healerName = $this->getHealerName($user->current_location_type);

        return [
            'location_type' => $user->current_location_type,
            'location_id' => $user->current_location_id,
            'location_name' => $location?->name ?? 'Unknown',
            'healer_name' => $healerName,
            'healer_title' => $this->getHealerTitle($user->current_location_type),
            'hp' => $user->hp,
            'max_hp' => $user->max_hp,
            'missing_hp' => $user->max_hp - $user->hp,
            'gold' => $user->gold,
            'cost_per_hp' => self::COST_PER_HP,
            'options' => $this->getHealingOptions($user),
        ];
    }

    /**
     * Get healer name based on location.
     */
    protected function getHealerName(string $locationType): string
    {
        return match ($locationType) {
            'village' => 'Old Marta',
            'barony' => 'Sir Edmund',
            'town' => 'Sister Agnes',
            default => 'The Healer',
        };
    }

    /**
     * Get healer title based on location.
     */
    protected function getHealerTitle(string $locationType): string
    {
        return match ($locationType) {
            'village' => 'Village Healer',
            'barony' => 'Baronial Physician',
            'town' => 'Infirmary Matron',
            default => 'Healer',
        };
    }

    /**
     * Resolve a location model.
     */
    protected function resolveLocation(string $type, int $id): ?object
    {
        $modelClass = match ($type) {
            'village' => \App\Models\Village::class,
            'barony' => \App\Models\Barony::class,
            'town' => \App\Models\Town::class,
            default => null,
        };

        return $modelClass ? $modelClass::find($id) : null;
    }
}
