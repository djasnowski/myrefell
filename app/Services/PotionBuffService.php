<?php

namespace App\Services;

use App\Models\Item;
use App\Models\PotionBuff;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PotionBuffService
{
    /**
     * Potion configurations: buff types, percentages, and durations.
     */
    public const POTIONS = [
        'Attack Potion' => [
            'buffs' => ['attack' => 10],
            'duration_minutes' => 5,
        ],
        'Strength Potion' => [
            'buffs' => ['strength' => 10],
            'duration_minutes' => 5,
        ],
        'Defense Potion' => [
            'buffs' => ['defense' => 10],
            'duration_minutes' => 5,
        ],
        'Accuracy Potion' => [
            'buffs' => ['attack' => 8],
            'duration_minutes' => 5,
        ],
        'Agility Potion' => [
            'buffs' => ['agility' => 10],
            'duration_minutes' => 5,
        ],
        'Super Attack Potion' => [
            'buffs' => ['attack' => 20],
            'duration_minutes' => 8,
        ],
        'Super Strength Potion' => [
            'buffs' => ['strength' => 20],
            'duration_minutes' => 8,
        ],
        'Super Defense Potion' => [
            'buffs' => ['defense' => 20],
            'duration_minutes' => 8,
        ],
        'Combat Potion' => [
            'buffs' => ['attack' => 15, 'strength' => 15],
            'duration_minutes' => 10,
        ],
        'Overload Potion' => [
            'buffs' => ['attack' => 25, 'strength' => 25, 'defense' => 25],
            'duration_minutes' => 12,
        ],
    ];

    public function __construct(
        protected InventoryService $inventoryService
    ) {}

    /**
     * Check if an item is a consumable buff potion.
     */
    public function isBuffPotion(Item $item): bool
    {
        return isset(self::POTIONS[$item->name]);
    }

    /**
     * Get potion info for display.
     */
    public function getPotionInfo(Item $item): ?array
    {
        $config = self::POTIONS[$item->name] ?? null;
        if (! $config) {
            return null;
        }

        return [
            'name' => $item->name,
            'buffs' => $config['buffs'],
            'duration_minutes' => $config['duration_minutes'],
        ];
    }

    /**
     * Consume a potion and apply its buffs.
     */
    public function consumePotion(User $user, int $inventorySlotId): array
    {
        $slot = $user->inventory()->where('id', $inventorySlotId)->with('item')->first();

        if (! $slot || ! $slot->item) {
            return [
                'success' => false,
                'message' => 'Item not found in inventory.',
            ];
        }

        $item = $slot->item;
        $config = self::POTIONS[$item->name] ?? null;

        if (! $config) {
            return [
                'success' => false,
                'message' => 'This item cannot be consumed as a buff potion.',
            ];
        }

        return DB::transaction(function () use ($user, $slot, $item, $config) {
            // Remove one potion from inventory
            if ($slot->quantity > 1) {
                $slot->decrement('quantity');
            } else {
                $slot->delete();
            }

            $appliedBuffs = [];
            $expiresAt = now()->addMinutes($config['duration_minutes']);

            // Apply each buff
            foreach ($config['buffs'] as $buffType => $bonusPercent) {
                // Check for existing buff of same type
                $existingBuff = PotionBuff::where('user_id', $user->id)
                    ->where('buff_type', $buffType)
                    ->active()
                    ->first();

                if ($existingBuff) {
                    // If new potion is stronger or lasts longer, replace it
                    if ($bonusPercent >= $existingBuff->bonus_percent) {
                        $existingBuff->update([
                            'bonus_percent' => $bonusPercent,
                            'expires_at' => $expiresAt,
                        ]);
                    }
                    // Otherwise keep existing stronger buff
                } else {
                    PotionBuff::create([
                        'user_id' => $user->id,
                        'buff_type' => $buffType,
                        'bonus_percent' => $bonusPercent,
                        'expires_at' => $expiresAt,
                    ]);
                }

                $appliedBuffs[] = "+{$bonusPercent}% ".ucfirst($buffType);
            }

            return [
                'success' => true,
                'message' => "You drink the {$item->name}.",
                'buffs_applied' => $appliedBuffs,
                'duration_minutes' => $config['duration_minutes'],
                'expires_at' => $expiresAt->toISOString(),
            ];
        });
    }

    /**
     * Get all active buffs for a user.
     */
    public function getActiveBuffs(User $user): array
    {
        return PotionBuff::where('user_id', $user->id)
            ->active()
            ->get()
            ->map(fn (PotionBuff $buff) => [
                'type' => $buff->buff_type,
                'bonus_percent' => $buff->bonus_percent,
                'expires_at' => $buff->expires_at->toISOString(),
                'minutes_remaining' => (int) now()->diffInMinutes($buff->expires_at, false),
            ])
            ->toArray();
    }

    /**
     * Get the bonus percentage for a specific buff type.
     */
    public function getBuffBonus(User $user, string $buffType): int
    {
        $buff = PotionBuff::where('user_id', $user->id)
            ->where('buff_type', $buffType)
            ->active()
            ->first();

        return $buff?->bonus_percent ?? 0;
    }

    /**
     * Get all buff bonuses as an array.
     */
    public function getAllBuffBonuses(User $user): array
    {
        $buffs = PotionBuff::where('user_id', $user->id)
            ->active()
            ->get();

        return [
            'attack' => $buffs->where('buff_type', 'attack')->first()?->bonus_percent ?? 0,
            'strength' => $buffs->where('buff_type', 'strength')->first()?->bonus_percent ?? 0,
            'defense' => $buffs->where('buff_type', 'defense')->first()?->bonus_percent ?? 0,
            'agility' => $buffs->where('buff_type', 'agility')->first()?->bonus_percent ?? 0,
        ];
    }

    /**
     * Apply attack buff to a base value.
     */
    public function applyAttackBuff(User $user, int $baseValue): int
    {
        $bonus = $this->getBuffBonus($user, 'attack');
        if ($bonus > 0) {
            return (int) ceil($baseValue * (1 + $bonus / 100));
        }

        return $baseValue;
    }

    /**
     * Apply strength buff to a base value.
     */
    public function applyStrengthBuff(User $user, int $baseValue): int
    {
        $bonus = $this->getBuffBonus($user, 'strength');
        if ($bonus > 0) {
            return (int) ceil($baseValue * (1 + $bonus / 100));
        }

        return $baseValue;
    }

    /**
     * Apply defense buff to a base value.
     */
    public function applyDefenseBuff(User $user, int $baseValue): int
    {
        $bonus = $this->getBuffBonus($user, 'defense');
        if ($bonus > 0) {
            return (int) ceil($baseValue * (1 + $bonus / 100));
        }

        return $baseValue;
    }

    /**
     * Clean up expired buffs (can be run periodically).
     */
    public function cleanupExpiredBuffs(): int
    {
        return PotionBuff::where('expires_at', '<=', now())->delete();
    }
}
