<?php

namespace App\Services;

use App\Models\Kingdom;
use App\Models\User;
use Carbon\Carbon;

class BiomeService
{
    /**
     * Attunement levels: time required (hours) => bonus percentage
     */
    public const ATTUNEMENT_LEVELS = [
        1 => ['hours' => 0.5, 'bonus' => 10],  // 30 minutes
        2 => ['hours' => 2, 'bonus' => 20],    // 2 hours
        3 => ['hours' => 4, 'bonus' => 30],    // 4 hours
    ];

    /**
     * Biome bonuses configuration.
     * Each biome grants bonuses to specific skills/activities.
     */
    public const BIOME_BONUSES = [
        'plains' => [
            'skills' => ['farming', 'herblore'],
            'description' => 'Fertile lands boost farming and herb gathering',
        ],
        'tundra' => [
            'skills' => ['mining', 'fishing'],
            'description' => 'Frozen depths reveal rich ore veins and cold-water fish',
        ],
        'coastal' => [
            'skills' => ['fishing', 'woodcutting'],
            'description' => 'Ocean bounty and sturdy coastal timber',
        ],
        'volcano' => [
            'skills' => ['smithing', 'smelting'],
            'description' => 'Volcanic heat enhances metalworking',
        ],
        'desert' => [
            'skills' => ['thieving', 'trading'],
            'description' => 'Ancient secrets and bustling trade routes',
        ],
    ];

    /**
     * Get the kingdom for a given location.
     */
    public function getKingdomForLocation(string $locationType, int $locationId): ?Kingdom
    {
        return match ($locationType) {
            'kingdom' => Kingdom::find($locationId),
            'duchy' => \App\Models\Duchy::find($locationId)?->kingdom,
            'barony' => \App\Models\Barony::find($locationId)?->kingdom,
            'town' => \App\Models\Town::find($locationId)?->barony?->kingdom,
            'village' => \App\Models\Village::find($locationId)?->barony?->kingdom,
            default => null,
        };
    }

    /**
     * Update player's kingdom tracking when they arrive at a new location.
     */
    public function updatePlayerKingdom(User $player): void
    {
        $kingdom = $this->getKingdomForLocation(
            $player->current_location_type,
            $player->current_location_id
        );

        $newKingdomId = $kingdom?->id;

        // If kingdom changed, reset the arrival time
        if ($player->current_kingdom_id !== $newKingdomId) {
            $player->current_kingdom_id = $newKingdomId;
            $player->kingdom_arrived_at = $newKingdomId ? now() : null;
            $player->save();
        }
    }

    /**
     * Get the player's current attunement level (1-3).
     */
    public function getAttunementLevel(User $player): int
    {
        if (! $player->current_kingdom_id || ! $player->kingdom_arrived_at) {
            return 0;
        }

        $hoursInKingdom = Carbon::parse($player->kingdom_arrived_at)->diffInHours(now());

        $level = 0;
        foreach (self::ATTUNEMENT_LEVELS as $lvl => $config) {
            if ($hoursInKingdom >= $config['hours']) {
                $level = $lvl;
            }
        }

        return $level;
    }

    /**
     * Get the bonus percentage for the player's current attunement.
     */
    public function getAttunementBonus(User $player): int
    {
        $level = $this->getAttunementLevel($player);

        return $level > 0 ? self::ATTUNEMENT_LEVELS[$level]['bonus'] : 0;
    }

    /**
     * Get the current kingdom's biome for a player.
     */
    public function getPlayerBiome(User $player): ?string
    {
        if (! $player->current_kingdom_id) {
            return null;
        }

        $kingdom = Kingdom::find($player->current_kingdom_id);

        return $kingdom?->biome;
    }

    /**
     * Check if a skill benefits from the player's current biome.
     */
    public function skillBenefitsFromBiome(User $player, string $skill): bool
    {
        $biome = $this->getPlayerBiome($player);

        if (! $biome || ! isset(self::BIOME_BONUSES[$biome])) {
            return false;
        }

        return in_array($skill, self::BIOME_BONUSES[$biome]['skills']);
    }

    /**
     * Get the biome bonus for a specific skill.
     * Returns 0 if the skill doesn't benefit from the current biome.
     */
    public function getBiomeBonusForSkill(User $player, string $skill): int
    {
        if (! $this->skillBenefitsFromBiome($player, $skill)) {
            return 0;
        }

        return $this->getAttunementBonus($player);
    }

    /**
     * Apply biome bonus to a value (XP, yield, etc.).
     */
    public function applyBiomeBonus(User $player, string $skill, int|float $value): int|float
    {
        $bonus = $this->getBiomeBonusForSkill($player, $skill);

        if ($bonus <= 0) {
            return $value;
        }

        return $value * (1 + $bonus / 100);
    }

    /**
     * Get full attunement info for display in UI.
     */
    public function getAttunementInfo(User $player): array
    {
        $kingdom = $player->current_kingdom_id
            ? Kingdom::find($player->current_kingdom_id)
            : null;

        $biome = $kingdom?->biome;
        $level = $this->getAttunementLevel($player);
        $bonus = $this->getAttunementBonus($player);
        $hoursInKingdom = $player->kingdom_arrived_at
            ? Carbon::parse($player->kingdom_arrived_at)->diffInHours(now())
            : 0;

        // Calculate time until next level
        $nextLevel = null;
        $hoursUntilNext = null;
        if ($level < 3 && $player->kingdom_arrived_at) {
            $nextLevelNum = $level + 1;
            $hoursRequired = self::ATTUNEMENT_LEVELS[$nextLevelNum]['hours'];
            $hoursUntilNext = max(0, $hoursRequired - $hoursInKingdom);
            $nextLevel = [
                'level' => $nextLevelNum,
                'bonus' => self::ATTUNEMENT_LEVELS[$nextLevelNum]['bonus'],
                'hours_remaining' => $hoursUntilNext,
            ];
        }

        return [
            'kingdom_id' => $kingdom?->id,
            'kingdom_name' => $kingdom?->name,
            'biome' => $biome,
            'biome_description' => $biome ? (self::BIOME_BONUSES[$biome]['description'] ?? null) : null,
            'biome_skills' => $biome ? (self::BIOME_BONUSES[$biome]['skills'] ?? []) : [],
            'attunement_level' => $level,
            'attunement_bonus' => $bonus,
            'hours_in_kingdom' => $hoursInKingdom,
            'arrived_at' => $player->kingdom_arrived_at?->toISOString(),
            'next_level' => $nextLevel,
            'max_level' => 3,
        ];
    }
}
