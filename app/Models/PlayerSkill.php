<?php

namespace App\Models;

use App\Services\BeliefEffectService;
use App\Services\BlessingEffectService;
use App\Services\ReferralService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlayerSkill extends Model
{
    use HasFactory;

    public const SKILLS = [
        'attack',
        'strength',
        'defense',
        'hitpoints',
        'range',
        'prayer',
        'farming',
        'mining',
        'fishing',
        'woodcutting',
        'cooking',
        'smithing',
        'crafting',
        'thieving',
        'herblore',
        'agility',
        'construction',
    ];

    public const COMBAT_SKILLS = ['attack', 'strength', 'defense', 'range', 'hitpoints', 'prayer'];

    public const MAX_LEVEL = 99;

    protected $fillable = [
        'player_id',
        'skill_name',
        'level',
        'xp',
    ];

    protected function casts(): array
    {
        return [
            'level' => 'integer',
            'xp' => 'integer',
        ];
    }

    /**
     * Get the player this skill belongs to.
     */
    public function player(): BelongsTo
    {
        return $this->belongsTo(User::class, 'player_id');
    }

    /**
     * Calculate XP required to reach a specific level.
     * Formula: XP required from level L to L+1 = L² × 60
     */
    public static function xpForLevel(int $level): int
    {
        if ($level < 1) {
            return 0;
        }

        $totalXp = 0;
        for ($l = 1; $l < $level; $l++) {
            $totalXp += ($l * $l) * 60;
        }

        return $totalXp;
    }

    /**
     * Calculate level from total XP.
     */
    public static function levelFromXp(int $xp): int
    {
        $level = 1;
        $totalXp = 0;

        while ($level < self::MAX_LEVEL) {
            $xpNeeded = ($level * $level) * 60;
            if ($totalXp + $xpNeeded > $xp) {
                break;
            }
            $totalXp += $xpNeeded;
            $level++;
        }

        return $level;
    }

    /**
     * Add XP to this skill and handle level ups.
     * Applies blessing, belief, and role XP bonuses automatically unless skipBonuses is true.
     *
     * @param  int  $amount  The base XP amount
     * @param  bool  $skipBonuses  If true, skip automatic bonus application (for services that calculate bonuses manually)
     */
    public function addXp(int $amount, bool $skipBonuses = false): int
    {
        if (! $skipBonuses) {
            // Apply blessing XP bonuses
            $amount = $this->applyBlessingXpBonus($amount);

            // Apply belief XP bonuses (from religion membership)
            $amount = $this->applyBeliefXpBonus($amount);

            // Apply role XP bonuses (from town/location roles)
            $amount = $this->applyRoleXpBonus($amount);
        }

        $this->xp += $amount;
        $newLevel = self::levelFromXp($this->xp);

        $levelsGained = $newLevel - $this->level;
        if ($levelsGained > 0) {
            $this->level = min($newLevel, self::MAX_LEVEL);

            // Check referral qualification when combat level might have changed
            if (in_array($this->skill_name, ['attack', 'strength', 'defense'])) {
                $this->checkReferralQualification();
            }
        }

        $this->save();

        return $levelsGained;
    }

    /**
     * Apply blessing XP bonuses to the XP amount.
     */
    protected function applyBlessingXpBonus(int $amount): int
    {
        $user = $this->player;
        if (! $user) {
            return $amount;
        }

        $blessingService = app(BlessingEffectService::class);

        // Check for all_xp_bonus first (Blessing of Wisdom)
        $allXpBonus = $blessingService->getEffect($user, 'all_xp_bonus');

        // Check for skill-specific XP bonuses
        $skillBonus = match ($this->skill_name) {
            'farming' => $blessingService->getEffect($user, 'farming_xp_bonus'),
            'fishing' => $blessingService->getEffect($user, 'fishing_xp_bonus'),
            'woodcutting' => $blessingService->getEffect($user, 'woodcutting_xp_bonus'),
            'mining' => $blessingService->getEffect($user, 'mining_xp_bonus'),
            'smithing' => $blessingService->getEffect($user, 'smithing_xp_bonus'),
            'crafting' => $blessingService->getEffect($user, 'crafting_xp_bonus'),
            'prayer' => $blessingService->getEffect($user, 'prayer_xp_bonus'),
            'herblore' => $blessingService->getEffect($user, 'herblore_xp_bonus'),
            default => 0,
        };

        // Check for combat XP bonus (from HQ prayer) for combat skills
        $combatXpBonus = 0;
        if (in_array($this->skill_name, ['attack', 'strength', 'defense', 'hitpoints', 'range'])) {
            $combatXpBonus = $blessingService->getEffect($user, 'combat_xp_bonus');
            $combatXpBonus += $blessingService->getEffect($user, 'all_combat_xp_bonus');
        }

        $totalBonus = $allXpBonus + $skillBonus + $combatXpBonus;

        if ($totalBonus > 0) {
            $amount = (int) ceil($amount * (1 + $totalBonus / 100));
        }

        return $amount;
    }

    /**
     * Apply belief XP bonuses from religion membership.
     */
    protected function applyBeliefXpBonus(int $amount): int
    {
        $user = $this->player;
        if (! $user) {
            return $amount;
        }

        $beliefService = app(BeliefEffectService::class);

        // Check for global XP penalty (Sloth belief)
        $xpPenalty = $beliefService->getEffect($user, 'xp_penalty');

        // Check for skill-category XP bonuses
        $categoryBonus = 0;

        // Gathering skills: mining, fishing, woodcutting, farming, herblore
        if (in_array($this->skill_name, ['mining', 'fishing', 'woodcutting', 'farming', 'herblore'])) {
            $categoryBonus += $beliefService->getEffect($user, 'gathering_xp_bonus');
        }

        // Combat skills: attack, strength, defense, hitpoints, range
        if (in_array($this->skill_name, ['attack', 'strength', 'defense', 'hitpoints', 'range'])) {
            $categoryBonus += $beliefService->getEffect($user, 'combat_xp_bonus');
        }

        // Crafting skills: crafting, smithing, cooking
        if (in_array($this->skill_name, ['crafting', 'smithing', 'cooking'])) {
            $categoryBonus += $beliefService->getEffect($user, 'crafting_xp_bonus');
            // Also check for crafting penalty (Bloodlust belief)
            $categoryBonus += $beliefService->getEffect($user, 'crafting_xp_penalty');
        }

        $totalModifier = $categoryBonus + $xpPenalty;

        if ($totalModifier != 0) {
            $amount = (int) ceil($amount * (1 + $totalModifier / 100));
            // Ensure at least 1 XP
            $amount = max(1, $amount);
        }

        return $amount;
    }

    /**
     * Apply role XP bonuses from town/location roles.
     */
    protected function applyRoleXpBonus(int $amount): int
    {
        $user = $this->player;
        if (! $user) {
            return $amount;
        }

        // Get user's active roles
        $activeRoles = PlayerRole::where('user_id', $user->id)
            ->active()
            ->with('role')
            ->get();

        if ($activeRoles->isEmpty()) {
            return $amount;
        }

        // Map skill names to bonus keys
        $bonusKey = $this->skill_name.'_xp_bonus';

        $totalBonus = 0;
        foreach ($activeRoles as $playerRole) {
            $role = $playerRole->role;
            if ($role && $role->bonuses) {
                $totalBonus += $role->bonuses[$bonusKey] ?? 0;
            }
        }

        if ($totalBonus > 0) {
            $amount = (int) ceil($amount * (1 + $totalBonus / 100));
        }

        return $amount;
    }

    /**
     * Check if the player qualifies for referral reward after leveling up.
     */
    protected function checkReferralQualification(): void
    {
        $user = $this->player;
        if (! $user) {
            return;
        }

        $referralService = app(ReferralService::class);

        // Check level 2 qualification
        if ($user->combat_level >= ReferralService::REQUIRED_LEVEL) {
            $referralService->processQualification($user);
        }

        // Check bonus level rewards
        if ($user->combat_level >= ReferralService::BONUS_LEVEL) {
            $referralService->processBonusReward($user);
        }
    }

    /**
     * Get XP progress to next level (0-100%).
     */
    public function getProgressAttribute(): float
    {
        return $this->getXpProgress();
    }

    /**
     * Get XP progress to next level as percentage.
     */
    public function getXpProgress(): float
    {
        if ($this->level >= self::MAX_LEVEL) {
            return 100.0;
        }

        $currentLevelXp = self::xpForLevel($this->level);
        $nextLevelXp = self::xpForLevel($this->level + 1);
        $xpIntoLevel = $this->xp - $currentLevelXp;
        $xpNeeded = $nextLevelXp - $currentLevelXp;

        return ($xpIntoLevel / $xpNeeded) * 100;
    }

    /**
     * Get XP needed to reach next level.
     */
    public function xpToNextLevel(): int
    {
        if ($this->level >= self::MAX_LEVEL) {
            return 0;
        }

        $nextLevelXp = self::xpForLevel($this->level + 1);

        return $nextLevelXp - $this->xp;
    }

    /**
     * Check if this is a combat skill.
     */
    public function isCombatSkill(): bool
    {
        return in_array($this->skill_name, self::COMBAT_SKILLS);
    }
}
