<?php

namespace App\Models;

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
     */
    public function addXp(int $amount): int
    {
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
