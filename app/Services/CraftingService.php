<?php

namespace App\Services;

use App\Models\Item;
use App\Models\LocationActivityLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CraftingService
{
    /**
     * Metal tiers with base levels and bar recipes.
     */
    public const METAL_TIERS = [
        'Bronze' => ['base_level' => 1, 'ore1' => 'Copper Ore', 'ore2' => 'Tin Ore', 'coal' => 0, 'bar_xp' => 12, 'bar_energy' => 3],
        'Iron' => ['base_level' => 15, 'ore1' => 'Iron Ore', 'ore2' => null, 'coal' => 1, 'bar_xp' => 25, 'bar_energy' => 4],
        'Steel' => ['base_level' => 30, 'ore1' => 'Iron Ore', 'ore2' => null, 'coal' => 2, 'bar_xp' => 40, 'bar_energy' => 5],
        'Mithril' => ['base_level' => 45, 'ore1' => 'Mithril Ore', 'ore2' => null, 'coal' => 3, 'bar_xp' => 55, 'bar_energy' => 5],
        'Celestial' => ['base_level' => 60, 'ore1' => 'Celestial Ore', 'ore2' => null, 'coal' => 4, 'bar_xp' => 75, 'bar_energy' => 6],
        'Oria' => ['base_level' => 75, 'ore1' => 'Oria Ore', 'ore2' => null, 'coal' => 5, 'bar_xp' => 100, 'bar_energy' => 7],
    ];

    /**
     * Gem tiers for cutting uncut gems.
     */
    public const GEM_TIERS = [
        'Opal' => ['level' => 1, 'xp' => 15, 'energy' => 1],
        'Jade' => ['level' => 13, 'xp' => 20, 'energy' => 2],
        'Red Topaz' => ['level' => 25, 'xp' => 30, 'energy' => 2],
        'Sapphire' => ['level' => 35, 'xp' => 50, 'energy' => 3],
        'Emerald' => ['level' => 43, 'xp' => 65, 'energy' => 3],
        'Ruby' => ['level' => 55, 'xp' => 85, 'energy' => 4],
        'Diamond' => ['level' => 65, 'xp' => 110, 'energy' => 4],
        'Oria Stone' => ['level' => 75, 'xp' => 150, 'energy' => 5],
    ];

    /**
     * Jewelry tiers with gem, level requirements, xp, and gold bar costs.
     */
    public const JEWELRY_TIERS = [
        'Gold' => ['gem' => null, 'levels' => ['ring' => 5, 'necklace' => 6, 'bracelet' => 7, 'amulet' => 8], 'xp' => ['ring' => 15, 'necklace' => 20, 'bracelet' => 17, 'amulet' => 25], 'gold_bars' => 1],
        'Opal' => ['gem' => 'Opal', 'levels' => ['ring' => 1, 'necklace' => 2, 'bracelet' => 3, 'amulet' => 4], 'xp' => ['ring' => 10, 'necklace' => 15, 'bracelet' => 12, 'amulet' => 18], 'gold_bars' => 1],
        'Jade' => ['gem' => 'Jade', 'levels' => ['ring' => 8, 'necklace' => 10, 'bracelet' => 11, 'amulet' => 12], 'xp' => ['ring' => 20, 'necklace' => 25, 'bracelet' => 22, 'amulet' => 30], 'gold_bars' => 1],
        'Red Topaz' => ['gem' => 'Red Topaz', 'levels' => ['ring' => 14, 'necklace' => 16, 'bracelet' => 17, 'amulet' => 18], 'xp' => ['ring' => 30, 'necklace' => 35, 'bracelet' => 32, 'amulet' => 40], 'gold_bars' => 1],
        'Sapphire' => ['gem' => 'Sapphire', 'levels' => ['ring' => 20, 'necklace' => 22, 'bracelet' => 23, 'amulet' => 24], 'xp' => ['ring' => 50, 'necklace' => 55, 'bracelet' => 52, 'amulet' => 65], 'gold_bars' => 1],
        'Emerald' => ['gem' => 'Emerald', 'levels' => ['ring' => 27, 'necklace' => 29, 'bracelet' => 30, 'amulet' => 31], 'xp' => ['ring' => 65, 'necklace' => 70, 'bracelet' => 67, 'amulet' => 80], 'gold_bars' => 1],
        'Ruby' => ['gem' => 'Ruby', 'levels' => ['ring' => 34, 'necklace' => 40, 'bracelet' => 45, 'amulet' => 50], 'xp' => ['ring' => 85, 'necklace' => 95, 'bracelet' => 90, 'amulet' => 110], 'gold_bars' => 2],
        'Diamond' => ['gem' => 'Diamond', 'levels' => ['ring' => 43, 'necklace' => 55, 'bracelet' => 63, 'amulet' => 70], 'xp' => ['ring' => 110, 'necklace' => 120, 'bracelet' => 115, 'amulet' => 140], 'gold_bars' => 2],
        'Oria' => ['gem' => 'Oria Stone', 'levels' => ['ring' => 55, 'necklace' => 63, 'bracelet' => 70, 'amulet' => 75], 'xp' => ['ring' => 150, 'necklace' => 165, 'bracelet' => 157, 'amulet' => 200], 'gold_bars' => 3],
    ];

    /**
     * Jewelry types with mould requirements and energy costs.
     */
    public const JEWELRY_TYPES = [
        'ring' => ['mould' => 'Ring Mould', 'energy' => 3],
        'necklace' => ['mould' => 'Necklace Mould', 'energy' => 4],
        'bracelet' => ['mould' => 'Bracelet Mould', 'energy' => 3],
        'amulet' => ['mould' => 'Amulet Mould', 'energy' => 5],
    ];

    /**
     * Smithing item templates with bar cost and level offset from metal base.
     */
    public const SMITHING_ITEMS = [
        'Dagger' => ['bars' => 1, 'offset' => 0, 'xp_per_bar' => 12, 'energy_per_bar' => 2],
        'Axe' => ['bars' => 1, 'offset' => 1, 'xp_per_bar' => 12, 'energy_per_bar' => 2],
        'Mace' => ['bars' => 1, 'offset' => 2, 'xp_per_bar' => 12, 'energy_per_bar' => 2],
        'Medium Helm' => ['bars' => 1, 'offset' => 3, 'xp_per_bar' => 12, 'energy_per_bar' => 2],
        'Sword' => ['bars' => 1, 'offset' => 4, 'xp_per_bar' => 12, 'energy_per_bar' => 2],
        'Dart Tips' => ['bars' => 1, 'offset' => 4, 'xp_per_bar' => 12, 'energy_per_bar' => 2, 'output_qty' => 10],
        'Scimitar' => ['bars' => 2, 'offset' => 5, 'xp_per_bar' => 12, 'energy_per_bar' => 2],
        'Spear' => ['bars' => 1, 'offset' => 5, 'xp_per_bar' => 12, 'energy_per_bar' => 2],
        'Arrowtips' => ['bars' => 1, 'offset' => 5, 'xp_per_bar' => 12, 'energy_per_bar' => 2, 'output_qty' => 15],
        'Longsword' => ['bars' => 2, 'offset' => 6, 'xp_per_bar' => 12, 'energy_per_bar' => 2],
        'Javelin Tips' => ['bars' => 1, 'offset' => 6, 'xp_per_bar' => 12, 'energy_per_bar' => 2, 'output_qty' => 5],
        'Full Helm' => ['bars' => 2, 'offset' => 7, 'xp_per_bar' => 12, 'energy_per_bar' => 2],
        'Throwing Knives' => ['bars' => 1, 'offset' => 7, 'xp_per_bar' => 12, 'energy_per_bar' => 2, 'output_qty' => 5],
        'Sq Shield' => ['bars' => 2, 'offset' => 8, 'xp_per_bar' => 12, 'energy_per_bar' => 2],
        'Warhammer' => ['bars' => 3, 'offset' => 9, 'xp_per_bar' => 12, 'energy_per_bar' => 2],
        'Battleaxe' => ['bars' => 3, 'offset' => 10, 'xp_per_bar' => 12, 'energy_per_bar' => 2],
        'Chainbody' => ['bars' => 3, 'offset' => 11, 'xp_per_bar' => 12, 'energy_per_bar' => 2],
        'Kiteshield' => ['bars' => 3, 'offset' => 12, 'xp_per_bar' => 12, 'energy_per_bar' => 2],
        'Claws' => ['bars' => 2, 'offset' => 13, 'xp_per_bar' => 12, 'energy_per_bar' => 2],
        '2h Sword' => ['bars' => 3, 'offset' => 14, 'xp_per_bar' => 12, 'energy_per_bar' => 2],
        'Platelegs' => ['bars' => 3, 'offset' => 16, 'xp_per_bar' => 12, 'energy_per_bar' => 2],
        'Plateskirt' => ['bars' => 3, 'offset' => 16, 'xp_per_bar' => 12, 'energy_per_bar' => 2],
        'Platebody' => ['bars' => 5, 'offset' => 18, 'xp_per_bar' => 12, 'energy_per_bar' => 2],
    ];

    /**
     * Crafting recipes (non-smithing items).
     */
    public const RECIPES = [
        'fishing_net' => [
            'name' => 'Fishing Net',
            'category' => 'crafting',
            'skill' => 'crafting',
            'required_level' => 5,
            'xp_reward' => 15,
            'energy_cost' => 4,
            'task_type' => 'craft',
            'materials' => [
                ['name' => 'Thread', 'quantity' => 5],
            ],
            'output' => ['name' => 'Fishing Net', 'quantity' => 1],
        ],
        'fishing_rod' => [
            'name' => 'Fishing Rod',
            'category' => 'crafting',
            'skill' => 'crafting',
            'required_level' => 5,
            'xp_reward' => 12,
            'energy_cost' => 3,
            'task_type' => 'craft',
            'materials' => [
                ['name' => 'Willow Wood', 'quantity' => 1],
                ['name' => 'Thread', 'quantity' => 2],
            ],
            'output' => ['name' => 'Fishing Rod', 'quantity' => 1],
        ],
        'wooden_arrow' => [
            'name' => 'Wooden Arrow',
            'category' => 'crafting',
            'skill' => 'crafting',
            'required_level' => 1,
            'xp_reward' => 5,
            'energy_cost' => 1,
            'task_type' => 'craft',
            'materials' => [
                ['name' => 'Wood', 'quantity' => 1],
            ],
            'output' => ['name' => 'Wooden Arrow', 'quantity' => 15],
        ],
        'oak_plank' => [
            'name' => 'Oak Plank',
            'category' => 'crafting',
            'skill' => 'crafting',
            'required_level' => 10,
            'xp_reward' => 15,
            'energy_cost' => 2,
            'task_type' => 'craft',
            'materials' => [
                ['name' => 'Oak Wood', 'quantity' => 1],
            ],
            'output' => ['name' => 'Oak Plank', 'quantity' => 2],
        ],
        'thread' => [
            'name' => 'Thread',
            'category' => 'crafting',
            'skill' => 'crafting',
            'required_level' => 1,
            'xp_reward' => 5,
            'energy_cost' => 1,
            'task_type' => 'craft',
            'materials' => [
                ['name' => 'Flax', 'quantity' => 1],
            ],
            'output' => ['name' => 'Thread', 'quantity' => 1],
        ],
        'rope' => [
            'name' => 'Rope',
            'category' => 'crafting',
            'skill' => 'crafting',
            'required_level' => 5,
            'xp_reward' => 10,
            'energy_cost' => 2,
            'task_type' => 'craft',
            'materials' => [
                ['name' => 'Thread', 'quantity' => 3],
            ],
            'output' => ['name' => 'Rope', 'quantity' => 1],
        ],
        'torch' => [
            'name' => 'Torch',
            'category' => 'crafting',
            'skill' => 'crafting',
            'required_level' => 1,
            'xp_reward' => 8,
            'energy_cost' => 2,
            'task_type' => 'craft',
            'materials' => [
                ['name' => 'Wood', 'quantity' => 1],
                ['name' => 'Cloth', 'quantity' => 1],
            ],
            'output' => ['name' => 'Torch', 'quantity' => 1],
        ],
    ];

    /**
     * Location types that allow crafting.
     */
    public const VALID_LOCATIONS = ['village', 'barony', 'town', 'duchy', 'kingdom'];

    public function __construct(
        protected InventoryService $inventoryService,
        protected DailyTaskService $dailyTaskService,
        protected TownBonusService $townBonusService
    ) {}

    /**
     * Get all smelting recipes (ore → bars) for the Forge.
     */
    public static function getSmeltingRecipes(): array
    {
        $recipes = [];

        foreach (self::METAL_TIERS as $metal => $tier) {
            $slug = strtolower($metal).'_bar';
            $materials = [['name' => $tier['ore1'], 'quantity' => 1]];

            if ($tier['ore2']) {
                $materials[] = ['name' => $tier['ore2'], 'quantity' => 1];
            }

            if ($tier['coal'] > 0) {
                $materials[] = ['name' => 'Coal', 'quantity' => $tier['coal']];
            }

            $recipes[$slug] = [
                'name' => "{$metal} Bar",
                'category' => 'smelting',
                'skill' => 'smithing',
                'required_level' => $tier['base_level'],
                'xp_reward' => $tier['bar_xp'],
                'energy_cost' => $tier['bar_energy'],
                'task_type' => 'smelt',
                'materials' => $materials,
                'output' => ['name' => "{$metal} Bar", 'quantity' => 1],
            ];
        }

        // Add Gold Bar smelting recipe
        $recipes['gold_bar'] = [
            'name' => 'Gold Bar',
            'category' => 'smelting',
            'skill' => 'smithing',
            'required_level' => 40,
            'xp_reward' => 35,
            'energy_cost' => 4,
            'task_type' => 'smelt',
            'materials' => [['name' => 'Gold Ore', 'quantity' => 1]],
            'output' => ['name' => 'Gold Bar', 'quantity' => 1],
        ];

        return $recipes;
    }

    /**
     * Get all smithing recipes (bars → weapons/armor) for the Anvil.
     */
    public static function getSmithingRecipes(): array
    {
        $recipes = [];

        foreach (self::METAL_TIERS as $metal => $tier) {
            $barName = "{$metal} Bar";

            foreach (self::SMITHING_ITEMS as $itemType => $itemData) {
                $slug = strtolower($metal).'_'.strtolower(str_replace(' ', '_', $itemType));
                $level = $tier['base_level'] + $itemData['offset'];

                $recipes[$slug] = [
                    'name' => "{$metal} {$itemType}",
                    'category' => 'smithing',
                    'skill' => 'smithing',
                    'required_level' => $level,
                    'xp_reward' => $itemData['bars'] * $itemData['xp_per_bar'],
                    'energy_cost' => $itemData['bars'] * $itemData['energy_per_bar'],
                    'task_type' => 'smith',
                    'materials' => [['name' => $barName, 'quantity' => $itemData['bars']]],
                    'output' => [
                        'name' => "{$metal} {$itemType}",
                        'quantity' => $itemData['output_qty'] ?? 1,
                    ],
                ];
            }
        }

        return $recipes;
    }

    /**
     * Get all gem cutting recipes (uncut gem → cut gem).
     */
    public static function getGemCuttingRecipes(): array
    {
        $recipes = [];

        foreach (self::GEM_TIERS as $gem => $tier) {
            $slug = 'cut_'.strtolower(str_replace(' ', '_', $gem));

            $recipes[$slug] = [
                'name' => $gem,
                'category' => 'gem_cutting',
                'skill' => 'crafting',
                'required_level' => $tier['level'],
                'xp_reward' => $tier['xp'],
                'energy_cost' => $tier['energy'],
                'task_type' => 'craft',
                'required_tool' => 'Chisel',
                'materials' => [['name' => "Uncut {$gem}", 'quantity' => 1]],
                'output' => ['name' => $gem, 'quantity' => 1],
            ];
        }

        return $recipes;
    }

    /**
     * Get all jewelry crafting recipes.
     */
    public static function getJewelryRecipes(): array
    {
        $recipes = [];

        foreach (self::JEWELRY_TIERS as $tierName => $tier) {
            foreach (self::JEWELRY_TYPES as $type => $typeData) {
                // Build the item name
                if ($tier['gem'] === null) {
                    $itemName = 'Gold '.ucfirst($type);
                } else {
                    $itemName = "{$tierName} ".ucfirst($type);
                }

                $slug = strtolower(str_replace(' ', '_', $itemName));

                // Build materials list
                $materials = [['name' => 'Gold Bar', 'quantity' => $tier['gold_bars']]];

                // Add gem if required
                if ($tier['gem'] !== null) {
                    $materials[] = ['name' => $tier['gem'], 'quantity' => 1];
                }

                $recipes[$slug] = [
                    'name' => $itemName,
                    'category' => 'jewelry',
                    'skill' => 'crafting',
                    'required_level' => $tier['levels'][$type],
                    'xp_reward' => $tier['xp'][$type],
                    'energy_cost' => $typeData['energy'],
                    'task_type' => 'craft',
                    'required_tool' => $typeData['mould'],
                    'materials' => $materials,
                    'output' => ['name' => $itemName, 'quantity' => 1],
                ];
            }
        }

        return $recipes;
    }

    /**
     * Get all recipes including crafting, smelting, and smithing.
     */
    public static function getAllRecipeDefinitions(): array
    {
        return array_merge(
            self::RECIPES,
            self::getSmeltingRecipes(),
            self::getSmithingRecipes(),
            self::getGemCuttingRecipes(),
            self::getJewelryRecipes()
        );
    }

    /**
     * Check if user can access crafting at their current location.
     */
    public function canCraft(User $user): bool
    {
        if ($user->isTraveling()) {
            return false;
        }

        return in_array($user->current_location_type, self::VALID_LOCATIONS);
    }

    /**
     * Get available recipes grouped by category.
     *
     * @param  array|null  $allowedCategories  If set, only include these categories
     */
    public function getAvailableRecipes(User $user, ?array $allowedCategories = null): array
    {
        if (! $this->canCraft($user)) {
            return [];
        }

        $categories = [];

        foreach (self::getAllRecipeDefinitions() as $id => $recipe) {
            $category = $recipe['category'];

            // Filter by allowed categories if specified
            if ($allowedCategories !== null && ! in_array($category, $allowedCategories)) {
                continue;
            }

            $skillLevel = $user->getSkillLevel($recipe['skill']);

            // Check if player meets level requirement
            if ($skillLevel < $recipe['required_level']) {
                continue;
            }

            if (! isset($categories[$category])) {
                $categories[$category] = [];
            }

            $categories[$category][] = $this->formatRecipe($id, $recipe, $user);
        }

        return $categories;
    }

    /**
     * Get all recipes regardless of level (for discovery view).
     *
     * @param  array|null  $allowedCategories  If set, only include these categories
     */
    public function getAllRecipes(User $user, ?array $allowedCategories = null): array
    {
        $categories = [];

        foreach (self::getAllRecipeDefinitions() as $id => $recipe) {
            $category = $recipe['category'];

            // Filter by allowed categories if specified
            if ($allowedCategories !== null && ! in_array($category, $allowedCategories)) {
                continue;
            }

            if (! isset($categories[$category])) {
                $categories[$category] = [];
            }

            $categories[$category][] = $this->formatRecipe($id, $recipe, $user, true);
        }

        return $categories;
    }

    /**
     * Format a recipe for display.
     */
    protected function formatRecipe(string $id, array $recipe, User $user, bool $showLocked = false): array
    {
        $skillLevel = $user->getSkillLevel($recipe['skill']);
        $canMake = $this->canMakeRecipe($user, $id);
        $isLocked = $skillLevel < $recipe['required_level'];

        // Get material availability
        $materials = [];
        foreach ($recipe['materials'] as $material) {
            $item = Item::where('name', $material['name'])->first();
            $playerHas = $item ? $this->inventoryService->countItem($user, $item) : 0;

            $materials[] = [
                'name' => $material['name'],
                'required' => $material['quantity'],
                'have' => $playerHas,
                'has_enough' => $playerHas >= $material['quantity'],
            ];
        }

        // Get role bonuses for this recipe's category
        $bonusActivity = $this->townBonusService->getCraftingActivity($recipe['category']);
        $yieldBonus = $this->townBonusService->getYieldBonus($user, $bonusActivity);
        $contributionRate = $this->townBonusService->getContributionRate($user, $bonusActivity);

        return [
            'id' => $id,
            'name' => $recipe['name'],
            'category' => $recipe['category'],
            'skill' => $recipe['skill'],
            'required_level' => $recipe['required_level'],
            'xp_reward' => $recipe['xp_reward'],
            'energy_cost' => $recipe['energy_cost'],
            'materials' => $materials,
            'output' => $recipe['output'],
            'can_make' => $canMake,
            'is_locked' => $isLocked,
            'current_level' => $skillLevel,
            'yield_bonus' => $yieldBonus,
            'yield_bonus_percent' => round($yieldBonus * 100),
            'contribution_rate' => $contributionRate,
            'contribution_rate_percent' => round($contributionRate * 100),
        ];
    }

    /**
     * Check if player can make a recipe.
     */
    public function canMakeRecipe(User $user, string $recipeId): bool
    {
        $allRecipes = self::getAllRecipeDefinitions();
        $recipe = $allRecipes[$recipeId] ?? null;
        if (! $recipe) {
            return false;
        }

        // Check level
        $skillLevel = $user->getSkillLevel($recipe['skill']);
        if ($skillLevel < $recipe['required_level']) {
            return false;
        }

        // Check energy
        if (! $user->hasEnergy($recipe['energy_cost'])) {
            return false;
        }

        // Check materials
        foreach ($recipe['materials'] as $material) {
            $item = Item::where('name', $material['name'])->first();
            if (! $item || ! $this->inventoryService->hasItem($user, $item, $material['quantity'])) {
                return false;
            }
        }

        // Check inventory space
        if (! $this->inventoryService->hasEmptySlot($user)) {
            return false;
        }

        return true;
    }

    /**
     * Craft an item.
     */
    public function craft(User $user, string $recipeId, ?string $locationType = null, ?int $locationId = null): array
    {
        $allRecipes = self::getAllRecipeDefinitions();
        $recipe = $allRecipes[$recipeId] ?? null;

        if (! $recipe) {
            return [
                'success' => false,
                'message' => 'Invalid recipe.',
            ];
        }

        if (! $this->canCraft($user)) {
            return [
                'success' => false,
                'message' => 'You cannot craft here.',
            ];
        }

        $skillLevel = $user->getSkillLevel($recipe['skill']);
        if ($skillLevel < $recipe['required_level']) {
            return [
                'success' => false,
                'message' => "You need level {$recipe['required_level']} {$recipe['skill']} to craft this.",
            ];
        }

        if (! $user->hasEnergy($recipe['energy_cost'])) {
            return [
                'success' => false,
                'message' => "Not enough energy. Need {$recipe['energy_cost']} energy.",
            ];
        }

        // Verify materials
        foreach ($recipe['materials'] as $material) {
            $item = Item::where('name', $material['name'])->first();
            if (! $item || ! $this->inventoryService->hasItem($user, $item, $material['quantity'])) {
                return [
                    'success' => false,
                    'message' => "You don't have enough {$material['name']}.",
                ];
            }
        }

        // Get output item
        $outputItem = Item::where('name', $recipe['output']['name'])->first();
        if (! $outputItem) {
            return [
                'success' => false,
                'message' => 'Output item not found in database.',
            ];
        }

        if (! $this->inventoryService->hasEmptySlot($user)) {
            return [
                'success' => false,
                'message' => 'Your inventory is full.',
            ];
        }

        // Use provided location or fall back to user's current location
        $locationType = $locationType ?? $user->current_location_type;
        $locationId = $locationId ?? $user->current_location_id;

        return DB::transaction(function () use ($user, $recipe, $outputItem, $recipeId, $locationType, $locationId) {
            // Consume energy
            $user->consumeEnergy($recipe['energy_cost']);

            // Remove materials
            foreach ($recipe['materials'] as $material) {
                $item = Item::where('name', $material['name'])->first();
                $this->inventoryService->removeItem($user, $item, $material['quantity']);
            }

            // Calculate base quantity and apply town bonus
            $baseQuantity = $recipe['output']['quantity'];
            $bonusActivity = $this->townBonusService->getCraftingActivity($recipe['category']);
            $yieldBonus = $this->townBonusService->getYieldBonus($user, $bonusActivity);
            $bonusQuantity = $this->townBonusService->calculateBonusQuantity($yieldBonus, $baseQuantity);
            $totalQuantity = $baseQuantity + $bonusQuantity;

            // Add output item
            $this->inventoryService->addItem($user, $outputItem, $totalQuantity);

            // Calculate and contribute to town stockpile
            $contributionRate = $this->townBonusService->getContributionRate($user, $bonusActivity);
            $contribution = $this->townBonusService->calculateContribution($contributionRate, $totalQuantity);
            $contributedToStockpile = false;
            if ($contribution > 0) {
                $contributedToStockpile = $this->townBonusService->contributeToStockpile($user, $outputItem->id, $contribution);
            }

            // Award XP (scaled by total quantity)
            $xpAwarded = (int) ceil($recipe['xp_reward'] * ($totalQuantity / max(1, $baseQuantity)));

            // Get or create the skill
            $skill = $user->skills()->where('skill_name', $recipe['skill'])->first();

            if (! $skill) {
                $skill = $user->skills()->create([
                    'skill_name' => $recipe['skill'],
                    'level' => 1,
                    'xp' => 0,
                ]);
            }

            $oldLevel = $skill->level;
            $skill->addXp($xpAwarded);
            $newLevel = $skill->fresh()->level;
            $leveledUp = $newLevel > $oldLevel;

            // Record daily task progress
            if (isset($recipe['task_type'])) {
                $this->dailyTaskService->recordProgress(
                    $user,
                    $recipe['task_type'],
                    $outputItem->name,
                    $totalQuantity
                );
            }

            // Log activity at location
            if ($locationType && $locationId) {
                try {
                    LocationActivityLog::log(
                        userId: $user->id,
                        locationType: $locationType,
                        locationId: $locationId,
                        activityType: LocationActivityLog::TYPE_CRAFTING,
                        description: "{$user->username} crafted {$totalQuantity}x {$outputItem->name}",
                        activitySubtype: $recipeId,
                        metadata: [
                            'item' => $outputItem->name,
                            'quantity' => $totalQuantity,
                            'xp_gained' => $xpAwarded,
                            'skill' => $recipe['skill'],
                            'leveled_up' => $leveledUp,
                        ]
                    );
                } catch (\Illuminate\Database\QueryException $e) {
                    // Table may not exist yet
                }
            }

            $message = "Crafted {$totalQuantity}x {$recipe['output']['name']}!";
            if ($contributedToStockpile && $contribution > 0) {
                $message .= " ({$contribution} contributed to town stockpile)";
            }

            return [
                'success' => true,
                'message' => $message,
                'item' => [
                    'name' => $outputItem->name,
                    'quantity' => $totalQuantity,
                    'base_quantity' => $baseQuantity,
                    'bonus_quantity' => $bonusQuantity,
                ],
                'xp_awarded' => $xpAwarded,
                'skill' => $recipe['skill'],
                'leveled_up' => $leveledUp,
                'new_level' => $leveledUp ? $newLevel : null,
                'energy_remaining' => $user->fresh()->energy,
                'role_bonus' => $bonusQuantity > 0,
                'stockpile_contribution' => $contribution,
            ];
        });
    }

    /**
     * Get crafting info for the current location.
     */
    public function getCraftingInfo(User $user): ?array
    {
        if (! $this->canCraft($user)) {
            return null;
        }

        // Get role bonuses for all crafting categories
        $bonuses = $this->townBonusService->getBonusInfo($user);

        // Get crafting skill info for XP progress
        $craftingSkill = $user->skills()->where('skill_name', 'crafting')->first();
        $craftingLevel = $craftingSkill?->level ?? 1;
        $craftingXp = $craftingSkill?->xp ?? 0;
        $craftingXpProgress = $craftingSkill?->getXpProgress() ?? 0;
        $craftingXpToNext = $craftingSkill?->xpToNextLevel() ?? 60;

        // Workshop shows crafting, gem cutting, and jewelry (not smithing/smelting)
        $workshopCategories = ['crafting', 'gem_cutting', 'jewelry'];

        return [
            'can_craft' => true,
            'recipes' => $this->getAvailableRecipes($user, $workshopCategories),
            'all_recipes' => $this->getAllRecipes($user, $workshopCategories),
            'player_energy' => $user->energy,
            'max_energy' => $user->max_energy,
            'free_slots' => $this->inventoryService->freeSlots($user),
            'crafting_level' => $craftingLevel,
            'crafting_xp' => $craftingXp,
            'crafting_xp_progress' => $craftingXpProgress,
            'crafting_xp_to_next' => $craftingXpToNext,
            'role_bonuses' => $bonuses,
        ];
    }
}
