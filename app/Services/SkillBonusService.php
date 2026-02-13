<?php

namespace App\Services;

use App\Models\PlayerActiveBelief;
use App\Models\PlayerBlessing;
use App\Models\PlayerFeatureBuff;
use App\Models\ReligionMember;
use App\Models\User;

class SkillBonusService
{
    /**
     * Mapping of effect keys to skill names.
     */
    protected const EFFECT_SKILL_MAP = [
        // Combat stats
        'attack_bonus' => 'attack',
        'strength_bonus' => 'strength',
        'defense_bonus' => 'defense',
        'all_combat_stats_bonus' => ['attack', 'strength', 'defense'],

        // Gathering yields
        'fishing_yield_bonus' => 'fishing',
        'mining_yield_bonus' => 'mining',
        'woodcutting_yield_bonus' => 'woodcutting',
        'herblore_yield_bonus' => 'herblore',
        'farming_yield_bonus' => 'farming',

        // XP bonuses
        'combat_xp_bonus' => ['attack', 'strength', 'defense', 'hitpoints'],
        'gathering_xp_bonus' => ['fishing', 'mining', 'woodcutting', 'herblore', 'farming'],
        'crafting_xp_bonus' => ['smithing', 'crafting', 'cooking'],
        'smithing_xp_bonus' => 'smithing',
        'cooking_xp_bonus' => 'cooking',
        'prayer_xp_bonus' => 'prayer',
        'thieving_xp_bonus' => 'thieving',

        // Flat bonuses
        'prayer_bonus' => 'prayer',
        'max_hp_bonus' => 'hitpoints',

        // Penalties
        'xp_penalty' => 'all',
        'crafting_xp_penalty' => ['smithing', 'crafting', 'cooking'],
        'defense_penalty' => 'defense',
    ];

    /**
     * Potion buff types mapped to skills.
     */
    protected const POTION_SKILL_MAP = [
        'attack' => 'attack',
        'strength' => 'strength',
        'defense' => 'defense',
        'agility' => 'agility',
    ];

    public function __construct(
        protected PotionBuffService $potionBuffService,
        protected HouseBuffService $houseBuffService
    ) {}

    /**
     * Get all skill bonuses with detailed breakdown by source.
     *
     * @return array<string, array{total: int, sources: array<array{source: string, type: string, value: int|float, is_percent: bool}>}>
     */
    public function getSkillBonuses(User $user): array
    {
        $bonuses = [];
        $allSkills = [
            'attack', 'strength', 'defense', 'hitpoints', 'range', 'prayer',
            'agility', 'thieving', 'farming', 'herblore', 'mining', 'fishing',
            'woodcutting', 'cooking', 'smithing', 'crafting', 'construction',
        ];

        // Initialize all skills
        foreach ($allSkills as $skill) {
            $bonuses[$skill] = [
                'flat_bonus' => 0,
                'percent_bonus' => 0,
                'sources' => [],
            ];
        }

        // 1. Get blessing effects with source details
        $this->addBlessingBonuses($user, $bonuses);

        // 2. Get HQ prayer buff effects
        $this->addHqPrayerBonuses($user, $bonuses);

        // 3. Get belief effects (religion + cult)
        $this->addBeliefBonuses($user, $bonuses);

        // 4. Get potion buffs
        $this->addPotionBonuses($user, $bonuses);

        // 5. Get house furniture buffs
        $this->addHouseBuffBonuses($user, $bonuses);

        return $bonuses;
    }

    /**
     * Add blessing bonuses with source details.
     */
    protected function addBlessingBonuses(User $user, array &$bonuses): void
    {
        $blessings = PlayerBlessing::where('user_id', $user->id)
            ->active()
            ->with('blessingType')
            ->get();

        foreach ($blessings as $blessing) {
            if (! $blessing->blessingType?->effects) {
                continue;
            }

            $sourceName = $blessing->blessingType->name;

            foreach ($blessing->blessingType->effects as $effectKey => $value) {
                $skills = $this->getSkillsForEffect($effectKey);

                foreach ($skills as $skill) {
                    if (! isset($bonuses[$skill])) {
                        continue;
                    }

                    $isPercent = $this->isPercentBonus($effectKey);

                    if ($isPercent) {
                        $bonuses[$skill]['percent_bonus'] += $value;
                    } else {
                        $bonuses[$skill]['flat_bonus'] += $value;
                    }

                    $bonuses[$skill]['sources'][] = [
                        'source' => $sourceName,
                        'type' => 'blessing',
                        'value' => $value,
                        'is_percent' => $isPercent,
                        'effect' => $this->formatEffectName($effectKey),
                    ];
                }
            }
        }
    }

    /**
     * Add HQ prayer buff bonuses.
     */
    protected function addHqPrayerBonuses(User $user, array &$bonuses): void
    {
        $prayerBuffs = PlayerFeatureBuff::where('user_id', $user->id)
            ->active()
            ->get();

        foreach ($prayerBuffs as $buff) {
            if (! $buff->effects) {
                continue;
            }

            $sourceName = $buff->feature_name ?? 'HQ Prayer';

            foreach ($buff->effects as $effectKey => $value) {
                $skills = $this->getSkillsForEffect($effectKey);

                foreach ($skills as $skill) {
                    if (! isset($bonuses[$skill])) {
                        continue;
                    }

                    $isPercent = $this->isPercentBonus($effectKey);

                    if ($isPercent) {
                        $bonuses[$skill]['percent_bonus'] += $value;
                    } else {
                        $bonuses[$skill]['flat_bonus'] += $value;
                    }

                    $bonuses[$skill]['sources'][] = [
                        'source' => $sourceName,
                        'type' => 'hq_prayer',
                        'value' => $value,
                        'is_percent' => $isPercent,
                        'effect' => $this->formatEffectName($effectKey),
                    ];
                }
            }
        }
    }

    /**
     * Add belief bonuses (from religion membership and activated beliefs).
     */
    protected function addBeliefBonuses(User $user, array &$bonuses): void
    {
        // Permanent religion membership beliefs
        $memberships = ReligionMember::where('user_id', $user->id)
            ->with('religion.beliefs')
            ->get();

        foreach ($memberships as $membership) {
            $religion = $membership->religion;
            if (! $religion?->is_active) {
                continue;
            }

            foreach ($religion->beliefs as $belief) {
                if (! $belief->effects || $belief->cult_only) {
                    continue;
                }

                $sourceName = $religion->name.' - '.$belief->name;

                foreach ($belief->effects as $effectKey => $value) {
                    $skills = $this->getSkillsForEffect($effectKey);

                    foreach ($skills as $skill) {
                        if (! isset($bonuses[$skill])) {
                            continue;
                        }

                        $isPercent = $this->isPercentBonus($effectKey);

                        if ($isPercent) {
                            $bonuses[$skill]['percent_bonus'] += $value;
                        } else {
                            $bonuses[$skill]['flat_bonus'] += $value;
                        }

                        $bonuses[$skill]['sources'][] = [
                            'source' => $sourceName,
                            'type' => 'belief',
                            'value' => $value,
                            'is_percent' => $isPercent,
                            'effect' => $this->formatEffectName($effectKey),
                        ];
                    }
                }
            }
        }

        // Activated beliefs (cult Forbidden Arts)
        $activeBeliefs = PlayerActiveBelief::where('user_id', $user->id)
            ->active()
            ->with('belief')
            ->get();

        foreach ($activeBeliefs as $activeBelief) {
            $belief = $activeBelief->belief;
            if (! $belief?->effects) {
                continue;
            }

            $sourceName = $belief->name.' (Active)';

            foreach ($belief->effects as $effectKey => $value) {
                $skills = $this->getSkillsForEffect($effectKey);

                foreach ($skills as $skill) {
                    if (! isset($bonuses[$skill])) {
                        continue;
                    }

                    $isPercent = $this->isPercentBonus($effectKey);

                    if ($isPercent) {
                        $bonuses[$skill]['percent_bonus'] += $value;
                    } else {
                        $bonuses[$skill]['flat_bonus'] += $value;
                    }

                    $bonuses[$skill]['sources'][] = [
                        'source' => $sourceName,
                        'type' => 'active_belief',
                        'value' => $value,
                        'is_percent' => $isPercent,
                        'effect' => $this->formatEffectName($effectKey),
                    ];
                }
            }
        }
    }

    /**
     * Add potion buff bonuses.
     */
    protected function addPotionBonuses(User $user, array &$bonuses): void
    {
        $activeBuffs = $this->potionBuffService->getActiveBuffs($user);

        foreach ($activeBuffs as $buff) {
            $skill = self::POTION_SKILL_MAP[$buff['type']] ?? null;

            if (! $skill || ! isset($bonuses[$skill])) {
                continue;
            }

            $bonuses[$skill]['percent_bonus'] += $buff['bonus_percent'];
            $bonuses[$skill]['sources'][] = [
                'source' => ucfirst($buff['type']).' Potion',
                'type' => 'potion',
                'value' => $buff['bonus_percent'],
                'is_percent' => true,
                'effect' => '+'.$buff['bonus_percent'].'% '.ucfirst($buff['type']),
                'expires_at' => $buff['expires_at'],
                'minutes_remaining' => $buff['minutes_remaining'],
            ];
        }
    }

    /**
     * Add house furniture buff bonuses.
     */
    protected function addHouseBuffBonuses(User $user, array &$bonuses): void
    {
        $sources = $this->houseBuffService->getHouseBuffSources($user);

        foreach ($sources as $source) {
            $effectKey = $source['effect_key'];
            $value = $source['value'];
            $skills = $this->getSkillsForEffect($effectKey);

            foreach ($skills as $skill) {
                if (! isset($bonuses[$skill])) {
                    continue;
                }

                $isPercent = $this->isPercentBonus($effectKey);

                if ($isPercent) {
                    $bonuses[$skill]['percent_bonus'] += $value;
                } else {
                    $bonuses[$skill]['flat_bonus'] += $value;
                }

                $bonuses[$skill]['sources'][] = [
                    'source' => $source['source'],
                    'type' => 'house',
                    'value' => $value,
                    'is_percent' => $isPercent,
                    'effect' => $this->formatEffectName($effectKey),
                ];
            }
        }
    }

    /**
     * Get skills affected by an effect key.
     *
     * @return string[]
     */
    protected function getSkillsForEffect(string $effectKey): array
    {
        $mapping = self::EFFECT_SKILL_MAP[$effectKey] ?? null;

        if ($mapping === null) {
            return [];
        }

        if ($mapping === 'all') {
            return [
                'attack', 'strength', 'defense', 'hitpoints', 'range', 'prayer',
                'agility', 'thieving', 'farming', 'herblore', 'mining', 'fishing',
                'woodcutting', 'cooking', 'smithing', 'crafting', 'construction',
            ];
        }

        return is_array($mapping) ? $mapping : [$mapping];
    }

    /**
     * Check if an effect is a percentage bonus vs flat bonus.
     */
    protected function isPercentBonus(string $effectKey): bool
    {
        // XP bonuses and yield bonuses are percentages
        return str_contains($effectKey, '_xp_') ||
               str_contains($effectKey, '_yield_') ||
               str_contains($effectKey, '_penalty') ||
               str_contains($effectKey, '_regen_');
    }

    /**
     * Format effect name for display.
     */
    protected function formatEffectName(string $effectKey): string
    {
        return ucwords(str_replace('_', ' ', $effectKey));
    }

    /**
     * Get all active buffs for sidebar display (blessings, HQ prayers, beliefs, potions).
     *
     * @return array<array{name: string, type: string, effects: array, expires_at: ?string, minutes_remaining: ?int}>
     */
    public function getAllActiveBuffs(User $user): array
    {
        $buffs = [];

        // 1. Active blessings (temporary)
        $blessings = PlayerBlessing::where('user_id', $user->id)
            ->active()
            ->with('blessingType')
            ->get();

        foreach ($blessings as $blessing) {
            if (! $blessing->blessingType?->effects) {
                continue;
            }

            $effects = [];
            foreach ($blessing->blessingType->effects as $key => $value) {
                $effects[] = [
                    'key' => $key,
                    'value' => $value,
                    'label' => $this->formatEffectLabel($key, $value),
                ];
            }

            $buffs[] = [
                'name' => $blessing->blessingType->name,
                'type' => 'blessing',
                'effects' => $effects,
                'expires_at' => $blessing->expires_at?->toISOString(),
                'minutes_remaining' => $blessing->expires_at ? (int) now()->diffInMinutes($blessing->expires_at, false) : null,
            ];
        }

        // 2. HQ prayer buffs (temporary)
        $prayerBuffs = PlayerFeatureBuff::where('user_id', $user->id)
            ->active()
            ->get();

        foreach ($prayerBuffs as $buff) {
            if (! $buff->effects) {
                continue;
            }

            $effects = [];
            foreach ($buff->effects as $key => $value) {
                $effects[] = [
                    'key' => $key,
                    'value' => $value,
                    'label' => $this->formatEffectLabel($key, $value),
                ];
            }

            $buffs[] = [
                'name' => $buff->feature_name ?? 'HQ Prayer',
                'type' => 'hq_prayer',
                'effects' => $effects,
                'expires_at' => $buff->expires_at?->toISOString(),
                'minutes_remaining' => $buff->expires_at ? (int) now()->diffInMinutes($buff->expires_at, false) : null,
            ];
        }

        // 3. Religion beliefs (permanent while member)
        $memberships = ReligionMember::where('user_id', $user->id)
            ->with('religion.beliefs')
            ->get();

        foreach ($memberships as $membership) {
            $religion = $membership->religion;
            if (! $religion?->is_active) {
                continue;
            }

            foreach ($religion->beliefs as $belief) {
                if (! $belief->effects || $belief->cult_only) {
                    continue;
                }

                $effects = [];
                foreach ($belief->effects as $key => $value) {
                    $effects[] = [
                        'key' => $key,
                        'value' => $value,
                        'label' => $this->formatEffectLabel($key, $value),
                    ];
                }

                $buffs[] = [
                    'name' => $belief->name,
                    'type' => 'belief',
                    'religion' => $religion->name,
                    'effects' => $effects,
                    'expires_at' => null,
                    'minutes_remaining' => null,
                ];
            }
        }

        // 4. Activated beliefs (temporary cult abilities)
        $activeBeliefs = PlayerActiveBelief::where('user_id', $user->id)
            ->active()
            ->with('belief')
            ->get();

        foreach ($activeBeliefs as $activeBelief) {
            $belief = $activeBelief->belief;
            if (! $belief?->effects) {
                continue;
            }

            $effects = [];
            foreach ($belief->effects as $key => $value) {
                $effects[] = [
                    'key' => $key,
                    'value' => $value,
                    'label' => $this->formatEffectLabel($key, $value),
                ];
            }

            $buffs[] = [
                'name' => $belief->name,
                'type' => 'active_belief',
                'effects' => $effects,
                'expires_at' => $activeBelief->expires_at?->toISOString(),
                'minutes_remaining' => $activeBelief->expires_at ? (int) now()->diffInMinutes($activeBelief->expires_at, false) : null,
            ];
        }

        // 5. Potion buffs (temporary)
        $potionBuffs = $this->potionBuffService->getActiveBuffs($user);

        foreach ($potionBuffs as $buff) {
            $buffs[] = [
                'name' => ucfirst($buff['type']).' Potion',
                'type' => 'potion',
                'effects' => [
                    [
                        'key' => $buff['type'].'_bonus',
                        'value' => $buff['bonus_percent'],
                        'label' => '+'.$buff['bonus_percent'].'% '.ucfirst($buff['type']),
                    ],
                ],
                'expires_at' => $buff['expires_at'],
                'minutes_remaining' => $buff['minutes_remaining'],
            ];
        }

        // 6. House furniture buffs (permanent while furniture exists)
        $houseSources = $this->houseBuffService->getHouseBuffSources($user);
        if (! empty($houseSources)) {
            $houseEffects = [];
            foreach ($houseSources as $source) {
                $houseEffects[] = [
                    'key' => $source['effect_key'],
                    'value' => $source['value'],
                    'label' => $this->formatEffectLabel($source['effect_key'], $source['value']),
                ];
            }

            $buffs[] = [
                'name' => 'House Furniture',
                'type' => 'house',
                'effects' => $houseEffects,
                'expires_at' => null,
                'minutes_remaining' => null,
            ];
        }

        return $buffs;
    }

    /**
     * Format an effect key and value into a human-readable label.
     */
    protected function formatEffectLabel(string $key, float $value): string
    {
        $prefix = $value > 0 ? '+' : '';
        $isPercent = $this->isPercentBonus($key);
        $suffix = $isPercent ? '%' : '';

        $name = match ($key) {
            'attack_bonus' => 'Attack',
            'strength_bonus' => 'Strength',
            'defense_bonus' => 'Defense',
            'all_combat_stats_bonus' => 'All Combat Stats',
            'combat_xp_bonus' => 'Combat XP',
            'gathering_xp_bonus' => 'Gathering XP',
            'crafting_xp_bonus' => 'Crafting XP',
            'thieving_xp_bonus' => 'Thieving XP',
            'fishing_yield_bonus' => 'Fishing Yield',
            'mining_yield_bonus' => 'Mining Yield',
            'woodcutting_yield_bonus' => 'Woodcutting Yield',
            'herblore_yield_bonus' => 'Herblore Yield',
            'xp_penalty' => 'All XP',
            'defense_penalty' => 'Defense',
            'crafting_xp_penalty' => 'Crafting XP',
            'monster_crit_chance' => 'Crit Chance',
            'action_cooldown_seconds' => 'Action Speed',
            'gold_bonus' => 'Gold',
            'gold_drop_bonus' => 'Gold Drops',
            'rare_drop_bonus' => 'Rare Drops',
            'energy_regen_bonus' => 'Energy Regen',
            'hp_regen_bonus' => 'HP Regen',
            'smithing_xp_bonus' => 'Smithing XP',
            'cooking_xp_bonus' => 'Cooking XP',
            'prayer_xp_bonus' => 'Prayer XP',
            'prayer_bonus' => 'Prayer',
            'farming_yield_bonus' => 'Farming Yield',
            'max_hp_bonus' => 'Max HP',
            'smithing_speed_bonus' => 'Smithing Speed',
            'combat_hp_leech' => 'Life Steal',
            'first_strike_damage_bonus' => 'First Strike Damage',
            default => $this->formatEffectName($key),
        };

        return $prefix.$value.$suffix.' '.$name;
    }
}
