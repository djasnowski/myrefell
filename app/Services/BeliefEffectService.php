<?php

namespace App\Services;

use App\Models\PlayerActiveBelief;
use App\Models\ReligionMember;
use App\Models\User;

class BeliefEffectService
{
    /**
     * Get all active belief effects for a user from their religion memberships
     * AND temporarily activated beliefs (cult Forbidden Arts).
     * Effects from multiple sources stack.
     */
    public function getActiveEffects(User $user): array
    {
        $effects = [];

        // Get permanent effects from religion membership beliefs (regular religions)
        $memberships = ReligionMember::where('user_id', $user->id)
            ->with('religion.beliefs')
            ->get();

        foreach ($memberships as $membership) {
            $religion = $membership->religion;
            if (! $religion || ! $religion->is_active) {
                continue;
            }

            // Only get non-cult-only beliefs from permanent religion membership
            foreach ($religion->beliefs as $belief) {
                if (! $belief->effects || $belief->cult_only) {
                    continue;
                }

                foreach ($belief->effects as $key => $value) {
                    $effects[$key] = ($effects[$key] ?? 0) + $value;
                }
            }
        }

        // Get effects from activated beliefs (including cult Forbidden Arts)
        $activeBeliefs = PlayerActiveBelief::where('user_id', $user->id)
            ->active()
            ->with('belief')
            ->get();

        foreach ($activeBeliefs as $activeBelief) {
            $belief = $activeBelief->belief;
            if (! $belief || ! $belief->effects) {
                continue;
            }

            foreach ($belief->effects as $key => $value) {
                $effects[$key] = ($effects[$key] ?? 0) + $value;
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
     * Check if user has any active religion membership.
     */
    public function hasReligion(User $user): bool
    {
        return ReligionMember::where('user_id', $user->id)->exists();
    }

    /**
     * Get combined effects summary for display.
     */
    public function getEffectsSummary(User $user): array
    {
        $effects = $this->getActiveEffects($user);

        $bonuses = [];
        $penalties = [];

        foreach ($effects as $key => $value) {
            if ($value > 0) {
                $bonuses[$key] = $value;
            } elseif ($value < 0) {
                $penalties[$key] = $value;
            }
        }

        return [
            'bonuses' => $bonuses,
            'penalties' => $penalties,
            'all' => $effects,
        ];
    }
}
