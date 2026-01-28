<?php

namespace App\Services;

use App\Models\LocationStockpile;
use App\Models\PlayerRole;
use App\Models\Role;
use App\Models\User;

class TownBonusService
{
    /**
     * Role bonuses configuration.
     * Maps role slugs to the activities they boost and bonus amounts.
     */
    public const ROLE_BONUSES = [
        // Village roles
        'miner' => [
            'activities' => ['mining'],
            'yield_bonus' => 0.10, // 10% bonus yield
            'contribution_rate' => 0.05, // 5% goes to stockpile
        ],
        'blacksmith' => [
            'activities' => ['smithing'],
            'yield_bonus' => 0.10,
            'contribution_rate' => 0.05,
        ],
        'fisherman' => [
            'activities' => ['fishing'],
            'yield_bonus' => 0.10,
            'contribution_rate' => 0.05,
        ],
        'baker' => [
            'activities' => ['cooking'],
            'yield_bonus' => 0.10,
            'contribution_rate' => 0.05,
        ],
        'butcher' => [
            'activities' => ['cooking'],
            'yield_bonus' => 0.05,
            'contribution_rate' => 0.03,
        ],
        'forester' => [
            'activities' => ['woodcutting'],
            'yield_bonus' => 0.10,
            'contribution_rate' => 0.05,
        ],
        'hunter' => [
            'activities' => ['hunting'],
            'yield_bonus' => 0.10,
            'contribution_rate' => 0.05,
        ],

        // Town roles - higher bonuses
        'master_miner' => [
            'activities' => ['mining'],
            'yield_bonus' => 0.20,
            'contribution_rate' => 0.08,
        ],
        'master_blacksmith' => [
            'activities' => ['smithing'],
            'yield_bonus' => 0.15,
            'contribution_rate' => 0.06,
        ],
        'weaponsmith' => [
            'activities' => ['smithing'],
            'yield_bonus' => 0.10,
            'contribution_rate' => 0.05,
        ],
        'armorsmith' => [
            'activities' => ['smithing'],
            'yield_bonus' => 0.10,
            'contribution_rate' => 0.05,
        ],
        'master_fisher' => [
            'activities' => ['fishing'],
            'yield_bonus' => 0.20,
            'contribution_rate' => 0.08,
        ],
        'head_chef' => [
            'activities' => ['cooking'],
            'yield_bonus' => 0.20,
            'contribution_rate' => 0.08,
        ],
        'alchemist' => [
            'activities' => ['alchemy'],
            'yield_bonus' => 0.15,
            'contribution_rate' => 0.06,
        ],
        'tanner' => [
            'activities' => ['crafting'],
            'yield_bonus' => 0.10,
            'contribution_rate' => 0.05,
        ],
        'brewmaster' => [
            'activities' => ['brewing'],
            'yield_bonus' => 0.15,
            'contribution_rate' => 0.06,
        ],
        'master_carpenter' => [
            'activities' => ['woodcutting', 'crafting'],
            'yield_bonus' => 0.15,
            'contribution_rate' => 0.06,
        ],

        // Barony roles
        'master_cook' => [
            'activities' => ['cooking'],
            'yield_bonus' => 0.15,
            'contribution_rate' => 0.06,
        ],

        // Duchy roles
        'duchy_chef' => [
            'activities' => ['cooking'],
            'yield_bonus' => 0.10,
            'contribution_rate' => 0.04,
        ],

        // Kingdom roles
        'royal_chef' => [
            'activities' => ['cooking'],
            'yield_bonus' => 0.05,
            'contribution_rate' => 0.02,
        ],
    ];

    /**
     * Map crafting categories to activity types.
     */
    public const CRAFT_CATEGORY_MAP = [
        'smithing' => 'smithing',
        'cooking' => 'cooking',
        'crafting' => 'crafting',
        'alchemy' => 'alchemy',
        'brewing' => 'brewing',
    ];

    /**
     * Get the total yield bonus for an activity at the user's location.
     */
    public function getYieldBonus(User $user, string $activity): float
    {
        $filledRoles = $this->getFilledRolesAtLocation($user);

        $totalBonus = 0.0;

        foreach ($filledRoles as $roleSlug) {
            $config = self::ROLE_BONUSES[$roleSlug] ?? null;
            if ($config && in_array($activity, $config['activities'])) {
                $totalBonus += $config['yield_bonus'];
            }
        }

        // Cap at 50% bonus maximum
        return min($totalBonus, 0.50);
    }

    /**
     * Get the total contribution rate for an activity at the user's location.
     */
    public function getContributionRate(User $user, string $activity): float
    {
        $filledRoles = $this->getFilledRolesAtLocation($user);

        $totalRate = 0.0;

        foreach ($filledRoles as $roleSlug) {
            $config = self::ROLE_BONUSES[$roleSlug] ?? null;
            if ($config && in_array($activity, $config['activities'])) {
                $totalRate += $config['contribution_rate'];
            }
        }

        // Cap at 20% contribution maximum
        return min($totalRate, 0.20);
    }

    /**
     * Get all filled role slugs at the user's current location.
     */
    protected function getFilledRolesAtLocation(User $user): array
    {
        $locationType = $user->current_location_type;
        $locationId = $user->current_location_id;

        $filledRoles = PlayerRole::where('location_type', $locationType)
            ->where('location_id', $locationId)
            ->where('status', 'active')
            ->with('role')
            ->get()
            ->pluck('role.slug')
            ->filter()
            ->toArray();

        return $filledRoles;
    }

    /**
     * Calculate bonus quantity based on yield bonus.
     * Returns additional quantity to award.
     */
    public function calculateBonusQuantity(float $yieldBonus, int $baseQuantity): int
    {
        if ($yieldBonus <= 0) {
            return 0;
        }

        // Each 10% bonus gives a 10% chance of +1 quantity
        // E.g., 30% bonus = 30% chance of +1, 60% bonus = 30% chance of +1, 30% chance of +2
        $bonusQuantity = 0;
        $remainingBonus = $yieldBonus;

        while ($remainingBonus > 0) {
            $chance = min($remainingBonus, 1.0) * 100;
            if (mt_rand(1, 100) <= $chance) {
                $bonusQuantity++;
            }
            $remainingBonus -= 1.0;
        }

        return $bonusQuantity;
    }

    /**
     * Calculate contribution to stockpile.
     * Returns quantity to contribute (can be 0).
     */
    public function calculateContribution(float $contributionRate, int $totalQuantity): int
    {
        if ($contributionRate <= 0 || $totalQuantity <= 0) {
            return 0;
        }

        // Contribution is based on probability
        // E.g., 10% rate with 3 items = 30% chance of contributing 1
        $expectedContribution = $contributionRate * $totalQuantity;

        // Always contribute the floor amount
        $guaranteed = (int) floor($expectedContribution);

        // Chance for one more based on remainder
        $remainder = $expectedContribution - $guaranteed;
        $extra = (mt_rand(1, 100) <= ($remainder * 100)) ? 1 : 0;

        return $guaranteed + $extra;
    }

    /**
     * Contribute items to the location stockpile.
     */
    public function contributeToStockpile(User $user, int $itemId, int $quantity): bool
    {
        if ($quantity <= 0) {
            return false;
        }

        $locationType = $user->current_location_type;
        $locationId = $user->current_location_id;

        // Only contribute to villages, towns, and baronies
        if (!in_array($locationType, ['village', 'town', 'barony'])) {
            return false;
        }

        $stockpile = LocationStockpile::getOrCreate($locationType, $locationId, $itemId);
        $stockpile->addQuantity($quantity);

        return true;
    }

    /**
     * Get bonus information for display purposes.
     */
    public function getBonusInfo(User $user): array
    {
        $filledRoles = $this->getFilledRolesAtLocation($user);

        $bonuses = [];

        foreach (self::ROLE_BONUSES as $roleSlug => $config) {
            if (in_array($roleSlug, $filledRoles)) {
                foreach ($config['activities'] as $activity) {
                    if (!isset($bonuses[$activity])) {
                        $bonuses[$activity] = [
                            'yield_bonus' => 0,
                            'contribution_rate' => 0,
                            'roles' => [],
                        ];
                    }

                    $bonuses[$activity]['yield_bonus'] += $config['yield_bonus'];
                    $bonuses[$activity]['contribution_rate'] += $config['contribution_rate'];
                    $bonuses[$activity]['roles'][] = $roleSlug;
                }
            }
        }

        // Apply caps
        foreach ($bonuses as $activity => &$data) {
            $data['yield_bonus'] = min($data['yield_bonus'], 0.50);
            $data['contribution_rate'] = min($data['contribution_rate'], 0.20);
            $data['yield_bonus_percent'] = round($data['yield_bonus'] * 100);
            $data['contribution_rate_percent'] = round($data['contribution_rate'] * 100);
        }

        return $bonuses;
    }

    /**
     * Get the activity type for a crafting category.
     */
    public function getCraftingActivity(string $category): string
    {
        return self::CRAFT_CATEGORY_MAP[$category] ?? $category;
    }
}
