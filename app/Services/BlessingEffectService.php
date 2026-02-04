<?php

namespace App\Services;

use App\Models\PlayerBlessing;
use App\Models\PlayerFeatureBuff;
use App\Models\User;

class BlessingEffectService
{
    /**
     * Get the action cooldown override from Blessing of Haste (in seconds).
     * Returns null if no haste blessing is active.
     */
    public function getActionCooldownSeconds(User $user): ?float
    {
        $hasteBlessing = PlayerBlessing::where('user_id', $user->id)
            ->active()
            ->whereHas('blessingType', function ($query) {
                $query->whereNotNull('effects->action_cooldown_seconds');
            })
            ->with('blessingType')
            ->first();

        if ($hasteBlessing && isset($hasteBlessing->blessingType->effects['action_cooldown_seconds'])) {
            return (float) $hasteBlessing->blessingType->effects['action_cooldown_seconds'];
        }

        return null;
    }

    /**
     * Check if user has haste blessing active.
     */
    public function hasHasteBlessing(User $user): bool
    {
        return $this->getActionCooldownSeconds($user) !== null;
    }

    /**
     * Get all active blessing effects for a user as a merged array.
     * Includes both shrine/priest blessings AND HQ prayer buffs.
     */
    public function getActiveEffects(User $user): array
    {
        $effects = [];

        // Get effects from active blessings
        $blessings = PlayerBlessing::where('user_id', $user->id)
            ->active()
            ->with('blessingType')
            ->get();

        foreach ($blessings as $blessing) {
            if ($blessing->blessingType && $blessing->blessingType->effects) {
                foreach ($blessing->blessingType->effects as $key => $value) {
                    // Stack effects of the same type
                    if (isset($effects[$key])) {
                        $effects[$key] += $value;
                    } else {
                        $effects[$key] = $value;
                    }
                }
            }
        }

        // Get effects from active HQ prayer buffs
        $prayerBuffs = PlayerFeatureBuff::where('user_id', $user->id)
            ->active()
            ->get();

        foreach ($prayerBuffs as $buff) {
            if ($buff->effects) {
                foreach ($buff->effects as $key => $value) {
                    // Stack effects of the same type
                    if (isset($effects[$key])) {
                        $effects[$key] += $value;
                    } else {
                        $effects[$key] = $value;
                    }
                }
            }
        }

        return $effects;
    }

    /**
     * Get a specific effect value for a user.
     */
    public function getEffect(User $user, string $effectKey): float
    {
        $effects = $this->getActiveEffects($user);

        return $effects[$effectKey] ?? 0;
    }

    /**
     * Get the maximum number of blessings a user can have active.
     * Base limit is 2, can be increased by blessing_slots effect.
     */
    public function getMaxBlessingSlots(User $user): int
    {
        $baseSlots = 2;
        $bonusSlots = (int) $this->getEffect($user, 'blessing_slots');

        return $baseSlots + $bonusSlots;
    }

    /**
     * Get the current number of active blessings for a user.
     */
    public function getActiveBlessingCount(User $user): int
    {
        return PlayerBlessing::where('user_id', $user->id)
            ->active()
            ->count();
    }

    /**
     * Check if a user can receive another blessing.
     */
    public function canReceiveBlessing(User $user): bool
    {
        return $this->getActiveBlessingCount($user) < $this->getMaxBlessingSlots($user);
    }
}
