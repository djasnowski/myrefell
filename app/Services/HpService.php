<?php

namespace App\Services;

use App\Models\User;

class HpService
{
    /**
     * HP regeneration rate in minutes.
     */
    public const REGEN_MINUTES = 5;

    /**
     * Base HP regenerated per tick (percentage of max HP).
     */
    public const REGEN_PERCENT = 5;

    public function __construct(
        protected BlessingEffectService $blessingEffectService
    ) {}

    /**
     * Regenerate HP for a single player.
     * Returns the amount of HP regenerated.
     */
    public function regenerateHp(User $player): int
    {
        if ($player->hp >= $player->max_hp) {
            return 0;
        }

        // Dead players don't regenerate HP
        if ($player->hp <= 0) {
            return 0;
        }

        // Base regen: 5% of max HP
        $baseRegen = (int) ceil($player->max_hp * self::REGEN_PERCENT / 100);

        // Apply blessing HP regen bonus (e.g., 25 = +25% more HP regen)
        $hpRegenBonus = $this->blessingEffectService->getEffect($player, 'hp_regen_bonus');
        if ($hpRegenBonus > 0) {
            $baseRegen = (int) ceil($baseRegen * (1 + $hpRegenBonus / 100));
        }

        // Apply hitpoints skill bonus: +1 HP per 10 hitpoints levels
        $hitpointsLevel = $player->getSkillLevel('hitpoints');
        $skillBonus = (int) floor($hitpointsLevel / 10);
        $totalRegen = $baseRegen + $skillBonus;

        // Cap at max HP
        $newHp = min($player->hp + $totalRegen, $player->max_hp);
        $actualRegen = $newHp - $player->hp;

        if ($actualRegen > 0) {
            $player->hp = $newHp;
            $player->save();
        }

        return $actualRegen;
    }

    /**
     * Regenerate HP for all players who are alive and not at max HP.
     * Returns the number of players affected.
     */
    public function regenerateAllPlayers(): int
    {
        // Get all players who need HP regen (alive but not at max HP)
        // We need to process individually to apply blessing bonuses
        $players = User::where('hp', '>', 0)
            ->whereRaw('hp < (SELECT COALESCE(level, 10) FROM player_skills WHERE player_skills.player_id = users.id AND player_skills.skill_name = \'hitpoints\')')
            ->get();

        $affected = 0;

        foreach ($players as $player) {
            $regen = $this->regenerateHp($player);
            if ($regen > 0) {
                $affected++;
            }
        }

        return $affected;
    }

    /**
     * Get HP regeneration info for display.
     */
    public function getRegenInfo(User $player): array
    {
        $atMax = $player->hp >= $player->max_hp;
        $baseRegen = (int) ceil($player->max_hp * self::REGEN_PERCENT / 100);

        // Calculate bonus regen from blessings
        $hpRegenBonus = $this->blessingEffectService->getEffect($player, 'hp_regen_bonus');
        $blessingBonus = $hpRegenBonus > 0 ? (int) ceil($baseRegen * $hpRegenBonus / 100) : 0;

        // Calculate skill bonus
        $hitpointsLevel = $player->getSkillLevel('hitpoints');
        $skillBonus = (int) floor($hitpointsLevel / 10);

        return [
            'current' => $player->hp,
            'max' => $player->max_hp,
            'at_max' => $atMax,
            'regen_rate_minutes' => self::REGEN_MINUTES,
            'base_regen' => $baseRegen,
            'blessing_bonus' => $blessingBonus,
            'skill_bonus' => $skillBonus,
            'total_regen' => $baseRegen + $blessingBonus + $skillBonus,
        ];
    }
}
