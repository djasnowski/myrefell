<?php

namespace App\Services;

use App\Models\PlayerBlessing;
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
     */
    public function getActiveEffects(User $user): array
    {
        $blessings = PlayerBlessing::where('user_id', $user->id)
            ->active()
            ->with('blessingType')
            ->get();

        $effects = [];

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
}
