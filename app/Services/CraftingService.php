<?php

namespace App\Services;

use App\Models\Item;
use App\Models\LocationActivityLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CraftingService
{
    /**
     * Metal tiers for smithing recipes.
     */
    private const METAL_TIERS = [
        'Bronze' => ['base_level' => 1, 'bar_xp' => 12, 'item_xp' => 12, 'bar_energy' => 3, 'item_energy' => 2],
        'Iron' => ['base_level' => 15, 'bar_xp' => 25, 'item_xp' => 20, 'bar_energy' => 4, 'item_energy' => 3],
        'Steel' => ['base_level' => 30, 'bar_xp' => 40, 'item_xp' => 30, 'bar_energy' => 5, 'item_energy' => 3],
        'Mithril' => ['base_level' => 45, 'bar_xp' => 55, 'item_xp' => 45, 'bar_energy' => 5, 'item_energy' => 4],
        'Celestial' => ['base_level' => 60, 'bar_xp' => 75, 'item_xp' => 60, 'bar_energy' => 6, 'item_energy' => 5],
        'Oria' => ['base_level' => 75, 'bar_xp' => 100, 'item_xp' => 80, 'bar_energy' => 7, 'item_energy' => 6],
    ];

    /**
     * Smithable item templates.
     * Each has: bars required, level offset, output quantity (for stackables).
     */
    private const SMITHABLE_ITEMS = [
        // Weapons (11 types)
        'Dagger' => ['bars' => 1, 'offset' => 0, 'output' => 1],
        'Axe' => ['bars' => 1, 'offset' => 1, 'output' => 1],
        'Mace' => ['bars' => 1, 'offset' => 2, 'output' => 1],
        'Sword' => ['bars' => 1, 'offset' => 4, 'output' => 1],
        'Scimitar' => ['bars' => 2, 'offset' => 5, 'output' => 1],
        'Spear' => ['bars' => 1, 'offset' => 5, 'output' => 1],
        'Longsword' => ['bars' => 2, 'offset' => 6, 'output' => 1],
        'Warhammer' => ['bars' => 3, 'offset' => 9, 'output' => 1],
        'Battleaxe' => ['bars' => 3, 'offset' => 10, 'output' => 1],
        'Claws' => ['bars' => 2, 'offset' => 13, 'output' => 1],
        '2h Sword' => ['bars' => 3, 'offset' => 14, 'output' => 1],
        // Armor (8 types)
        'Medium Helm' => ['bars' => 1, 'offset' => 3, 'output' => 1],
        'Full Helm' => ['bars' => 2, 'offset' => 7, 'output' => 1],
        'Sq Shield' => ['bars' => 2, 'offset' => 8, 'output' => 1],
        'Chainbody' => ['bars' => 3, 'offset' => 11, 'output' => 1],
        'Kiteshield' => ['bars' => 3, 'offset' => 12, 'output' => 1],
        'Platelegs' => ['bars' => 3, 'offset' => 16, 'output' => 1],
        'Plateskirt' => ['bars' => 3, 'offset' => 16, 'output' => 1],
        'Platebody' => ['bars' => 5, 'offset' => 18, 'output' => 1],
        // Ammunition (4 types)
        'Dart Tips' => ['bars' => 1, 'offset' => 4, 'output' => 10],
        'Arrowtips' => ['bars' => 1, 'offset' => 5, 'output' => 15],
        'Javelin Tips' => ['bars' => 1, 'offset' => 6, 'output' => 5],
        'Throwing Knives' => ['bars' => 1, 'offset' => 7, 'output' => 5],
    ];

    /**
     * Base recipes (bars, tools, etc.).
     *
     * @var array<string, array<string, mixed>>|null
     */
    private static ?array $cachedRecipes = null;

    /**
     * Base crafting recipes.
     *
     * @return array<string, array<string, mixed>>
     */
    private const BASE_RECIPES = [
        // Bar smelting recipes
        'bronze_bar' => [
            'name' => 'Bronze Bar',
            'category' => 'smithing',
            'skill' => 'smithing',
            'required_level' => 1,
            'xp_reward' => 12,
            'energy_cost' => 3,
            'task_type' => 'smith',
            'materials' => [
                ['name' => 'Copper Ore', 'quantity' => 1],
                ['name' => 'Tin Ore', 'quantity' => 1],
            ],
            'output' => ['name' => 'Bronze Bar', 'quantity' => 1],
        ],
        'iron_bar' => [
            'name' => 'Iron Bar',
            'category' => 'smithing',
            'skill' => 'smithing',
            'required_level' => 15,
            'xp_reward' => 25,
            'energy_cost' => 4,
            'task_type' => 'smith',
            'materials' => [
                ['name' => 'Iron Ore', 'quantity' => 1],
                ['name' => 'Coal', 'quantity' => 1],
            ],
            'output' => ['name' => 'Iron Bar', 'quantity' => 1],
        ],
        'steel_bar' => [
            'name' => 'Steel Bar',
            'category' => 'smithing',
            'skill' => 'smithing',
            'required_level' => 30,
            'xp_reward' => 40,
            'energy_cost' => 5,
            'task_type' => 'smith',
            'materials' => [
                ['name' => 'Iron Ore', 'quantity' => 1],
                ['name' => 'Coal', 'quantity' => 2],
            ],
            'output' => ['name' => 'Steel Bar', 'quantity' => 1],
        ],
        'mithril_bar' => [
            'name' => 'Mithril Bar',
            'category' => 'smithing',
            'skill' => 'smithing',
            'required_level' => 45,
            'xp_reward' => 55,
            'energy_cost' => 5,
            'task_type' => 'smith',
            'materials' => [
                ['name' => 'Mithril Ore', 'quantity' => 1],
                ['name' => 'Coal', 'quantity' => 3],
            ],
            'output' => ['name' => 'Mithril Bar', 'quantity' => 1],
        ],
        'celestial_bar' => [
            'name' => 'Celestial Bar',
            'category' => 'smithing',
            'skill' => 'smithing',
            'required_level' => 60,
            'xp_reward' => 75,
            'energy_cost' => 6,
            'task_type' => 'smith',
            'materials' => [
                ['name' => 'Celestial Ore', 'quantity' => 1],
                ['name' => 'Coal', 'quantity' => 4],
            ],
            'output' => ['name' => 'Celestial Bar', 'quantity' => 1],
        ],
        'oria_bar' => [
            'name' => 'Oria Bar',
            'category' => 'smithing',
            'skill' => 'smithing',
            'required_level' => 75,
            'xp_reward' => 100,
            'energy_cost' => 7,
            'task_type' => 'smith',
            'materials' => [
                ['name' => 'Oria Ore', 'quantity' => 1],
                ['name' => 'Coal', 'quantity' => 5],
            ],
            'output' => ['name' => 'Oria Bar', 'quantity' => 1],
        ],
        'nails' => [
            'name' => 'Nails',
            'category' => 'smithing',
            'skill' => 'smithing',
            'required_level' => 5,
            'xp_reward' => 8,
            'energy_cost' => 2,
            'task_type' => 'smith',
            'materials' => [
                ['name' => 'Bronze Bar', 'quantity' => 1],
            ],
            'output' => ['name' => 'Nails', 'quantity' => 10],
        ],
        'bronze_pickaxe' => [
            'name' => 'Bronze Pickaxe',
            'category' => 'smithing',
            'skill' => 'smithing',
            'required_level' => 5,
            'xp_reward' => 15,
            'energy_cost' => 4,
            'task_type' => 'smith',
            'materials' => [
                ['name' => 'Bronze Bar', 'quantity' => 2],
                ['name' => 'Wood', 'quantity' => 1],
            ],
            'output' => ['name' => 'Bronze Pickaxe', 'quantity' => 1],
        ],
        'iron_pickaxe' => [
            'name' => 'Iron Pickaxe',
            'category' => 'smithing',
            'skill' => 'smithing',
            'required_level' => 20,
            'xp_reward' => 30,
            'energy_cost' => 5,
            'task_type' => 'smith',
            'materials' => [
                ['name' => 'Iron Bar', 'quantity' => 2],
                ['name' => 'Oak Wood', 'quantity' => 1],
            ],
            'output' => ['name' => 'Iron Pickaxe', 'quantity' => 1],
        ],
        'steel_pickaxe' => [
            'name' => 'Steel Pickaxe',
            'category' => 'smithing',
            'skill' => 'smithing',
            'required_level' => 35,
            'xp_reward' => 50,
            'energy_cost' => 6,
            'task_type' => 'smith',
            'materials' => [
                ['name' => 'Steel Bar', 'quantity' => 2],
                ['name' => 'Oak Wood', 'quantity' => 1],
            ],
            'output' => ['name' => 'Steel Pickaxe', 'quantity' => 1],
        ],
        'hammer' => [
            'name' => 'Hammer',
            'category' => 'smithing',
            'skill' => 'smithing',
            'required_level' => 1,
            'xp_reward' => 10,
            'energy_cost' => 3,
            'task_type' => 'smith',
            'materials' => [
                ['name' => 'Bronze Bar', 'quantity' => 1],
                ['name' => 'Wood', 'quantity' => 1],
            ],
            'output' => ['name' => 'Hammer', 'quantity' => 1],
        ],
        'fishing_rod' => [
            'name' => 'Fishing Rod',
            'category' => 'smithing',
            'skill' => 'smithing',
            'required_level' => 5,
            'xp_reward' => 12,
            'energy_cost' => 3,
            'task_type' => 'smith',
            'materials' => [
                ['name' => 'Willow Wood', 'quantity' => 1],
                ['name' => 'Thread', 'quantity' => 2],
            ],
            'output' => ['name' => 'Fishing Rod', 'quantity' => 1],
        ],
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
        // Crafting recipes
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
    ];

    /**
     * Get all recipes including dynamically generated smithing recipes.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function getRecipes(): array
    {
        if (self::$cachedRecipes !== null) {
            return self::$cachedRecipes;
        }

        $recipes = self::BASE_RECIPES;

        // Generate smithing recipes for all metal tiers
        foreach (self::METAL_TIERS as $metal => $tierData) {
            $barName = "{$metal} Bar";

            foreach (self::SMITHABLE_ITEMS as $itemName => $itemData) {
                $fullName = "{$metal} {$itemName}";
                $recipeId = strtolower(str_replace([' ', '-'], '_', $fullName));
                $level = $tierData['base_level'] + $itemData['offset'];
                $xpPerBar = $tierData['item_xp'];
                $energyPerBar = $tierData['item_energy'];

                $recipes[$recipeId] = [
                    'name' => $fullName,
                    'category' => 'smithing',
                    'skill' => 'smithing',
                    'required_level' => $level,
                    'xp_reward' => $xpPerBar * $itemData['bars'],
                    'energy_cost' => $energyPerBar * $itemData['bars'],
                    'task_type' => 'smith',
                    'materials' => [
                        ['name' => $barName, 'quantity' => $itemData['bars']],
                    ],
                    'output' => ['name' => $fullName, 'quantity' => $itemData['output']],
                ];
            }
        }

        self::$cachedRecipes = $recipes;

        return $recipes;
    }

    /**
     * Clear the cached recipes (for testing).
     */
    public static function clearRecipeCache(): void
    {
        self::$cachedRecipes = null;
    }

    /**
     * Location types that allow crafting.
     */
    public const VALID_LOCATIONS = ['village', 'barony', 'town'];

    public function __construct(
        protected InventoryService $inventoryService,
        protected DailyTaskService $dailyTaskService,
        protected TownBonusService $townBonusService
    ) {}

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
     */
    public function getAvailableRecipes(User $user): array
    {
        if (! $this->canCraft($user)) {
            return [];
        }

        $categories = [];

        foreach (self::getRecipes() as $id => $recipe) {
            $skillLevel = $user->getSkillLevel($recipe['skill']);

            // Check if player meets level requirement
            if ($skillLevel < $recipe['required_level']) {
                continue;
            }

            $category = $recipe['category'];
            if (! isset($categories[$category])) {
                $categories[$category] = [];
            }

            $categories[$category][] = $this->formatRecipe($id, $recipe, $user);
        }

        return $categories;
    }

    /**
     * Get all recipes regardless of level (for discovery view).
     */
    public function getAllRecipes(User $user): array
    {
        $categories = [];

        foreach (self::getRecipes() as $id => $recipe) {
            $category = $recipe['category'];
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
        $recipe = self::getRecipes()[$recipeId] ?? null;
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
        $recipe = self::getRecipes()[$recipeId] ?? null;

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
            $leveledUp = $skill->fresh()->level > $oldLevel;

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

        return [
            'can_craft' => true,
            'recipes' => $this->getAvailableRecipes($user),
            'all_recipes' => $this->getAllRecipes($user),
            'player_energy' => $user->energy,
            'max_energy' => $user->max_energy,
            'free_slots' => $this->inventoryService->freeSlots($user),
            'role_bonuses' => $bonuses,
        ];
    }
}
