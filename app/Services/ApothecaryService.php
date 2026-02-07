<?php

namespace App\Services;

use App\Models\Item;
use App\Models\LocationActivityLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ApothecaryService
{
    /**
     * Location types that allow brewing.
     */
    public const VALID_LOCATIONS = ['village', 'barony', 'town', 'duchy', 'kingdom'];

    /**
     * Potion recipes configuration.
     */
    public const RECIPES = [
        // Basic potions (Level 1-20)
        'minor_health_potion' => [
            'name' => 'Minor Health Potion',
            'category' => 'restoration',
            'skill' => 'herblore',
            'required_level' => 1,
            'xp_reward' => 10,
            'energy_cost' => 3,
            'task_type' => 'brew',
            'materials' => [
                ['name' => 'Herb', 'quantity' => 2],
                ['name' => 'Vial', 'quantity' => 1],
            ],
            'output' => ['name' => 'Minor Health Potion', 'quantity' => 1],
        ],
        'weak_antidote' => [
            'name' => 'Weak Antidote',
            'category' => 'restoration',
            'skill' => 'herblore',
            'required_level' => 5,
            'xp_reward' => 15,
            'energy_cost' => 4,
            'task_type' => 'brew',
            'materials' => [
                ['name' => 'Healing Herb', 'quantity' => 2],
                ['name' => 'Vial', 'quantity' => 1],
            ],
            'output' => ['name' => 'Antidote', 'quantity' => 1],
        ],
        'energy_tonic' => [
            'name' => 'Energy Tonic',
            'category' => 'restoration',
            'skill' => 'herblore',
            'required_level' => 10,
            'xp_reward' => 20,
            'energy_cost' => 5,
            'task_type' => 'brew',
            'materials' => [
                ['name' => 'Healing Herb', 'quantity' => 3],
                ['name' => 'Sunblossom', 'quantity' => 1],
                ['name' => 'Vial', 'quantity' => 1],
            ],
            'output' => ['name' => 'Energy Tonic', 'quantity' => 1],
        ],

        // Combat potions (Level 15-35)
        'attack_potion' => [
            'name' => 'Attack Potion',
            'category' => 'combat',
            'skill' => 'herblore',
            'required_level' => 15,
            'xp_reward' => 25,
            'energy_cost' => 5,
            'task_type' => 'brew',
            'materials' => [
                ['name' => 'Bloodroot', 'quantity' => 1],
                ['name' => 'Nightshade', 'quantity' => 1],
                ['name' => 'Vial', 'quantity' => 1],
            ],
            'output' => ['name' => 'Attack Potion', 'quantity' => 1],
        ],
        'strength_potion' => [
            'name' => 'Strength Potion',
            'category' => 'combat',
            'skill' => 'herblore',
            'required_level' => 18,
            'xp_reward' => 28,
            'energy_cost' => 5,
            'task_type' => 'brew',
            'materials' => [
                ['name' => 'Bloodroot', 'quantity' => 2],
                ['name' => 'Stoneroot', 'quantity' => 1],
                ['name' => 'Vial', 'quantity' => 1],
            ],
            'output' => ['name' => 'Strength Potion', 'quantity' => 1],
        ],
        'defense_potion' => [
            'name' => 'Defense Potion',
            'category' => 'combat',
            'skill' => 'herblore',
            'required_level' => 20,
            'xp_reward' => 30,
            'energy_cost' => 5,
            'task_type' => 'brew',
            'materials' => [
                ['name' => 'Stoneroot', 'quantity' => 2],
                ['name' => 'Ironbark', 'quantity' => 1],
                ['name' => 'Vial', 'quantity' => 1],
            ],
            'output' => ['name' => 'Defense Potion', 'quantity' => 1],
        ],
        'accuracy_potion' => [
            'name' => 'Accuracy Potion',
            'category' => 'combat',
            'skill' => 'herblore',
            'required_level' => 22,
            'xp_reward' => 32,
            'energy_cost' => 5,
            'task_type' => 'brew',
            'materials' => [
                ['name' => 'Moonpetal', 'quantity' => 2],
                ['name' => 'Hawkeye Leaf', 'quantity' => 1],
                ['name' => 'Vial', 'quantity' => 1],
            ],
            'output' => ['name' => 'Accuracy Potion', 'quantity' => 1],
        ],
        'agility_potion' => [
            'name' => 'Agility Potion',
            'category' => 'combat',
            'skill' => 'herblore',
            'required_level' => 25,
            'xp_reward' => 35,
            'energy_cost' => 6,
            'task_type' => 'brew',
            'materials' => [
                ['name' => 'Swiftfoot Moss', 'quantity' => 2],
                ['name' => 'Windweed', 'quantity' => 1],
                ['name' => 'Vial', 'quantity' => 1],
            ],
            'output' => ['name' => 'Agility Potion', 'quantity' => 1],
        ],

        // Intermediate potions (Level 25-45)
        'health_potion' => [
            'name' => 'Health Potion',
            'category' => 'restoration',
            'skill' => 'herblore',
            'required_level' => 25,
            'xp_reward' => 35,
            'energy_cost' => 6,
            'task_type' => 'brew',
            'materials' => [
                ['name' => 'Healing Herb', 'quantity' => 3],
                ['name' => 'Moonpetal', 'quantity' => 2],
                ['name' => 'Crystal Vial', 'quantity' => 1],
            ],
            'output' => ['name' => 'Health Potion', 'quantity' => 1],
        ],
        'super_attack_potion' => [
            'name' => 'Super Attack Potion',
            'category' => 'combat',
            'skill' => 'herblore',
            'required_level' => 30,
            'xp_reward' => 45,
            'energy_cost' => 7,
            'task_type' => 'brew',
            'materials' => [
                ['name' => 'Attack Potion', 'quantity' => 1],
                ['name' => 'Ghostcap Mushroom', 'quantity' => 1],
                ['name' => 'Venom Sac', 'quantity' => 1],
            ],
            'output' => ['name' => 'Super Attack Potion', 'quantity' => 1],
        ],
        'super_strength_potion' => [
            'name' => 'Super Strength Potion',
            'category' => 'combat',
            'skill' => 'herblore',
            'required_level' => 33,
            'xp_reward' => 48,
            'energy_cost' => 7,
            'task_type' => 'brew',
            'materials' => [
                ['name' => 'Strength Potion', 'quantity' => 1],
                ['name' => 'Ghostcap Mushroom', 'quantity' => 1],
                ['name' => 'Giant Essence', 'quantity' => 1],
            ],
            'output' => ['name' => 'Super Strength Potion', 'quantity' => 1],
        ],
        'super_defense_potion' => [
            'name' => 'Super Defense Potion',
            'category' => 'combat',
            'skill' => 'herblore',
            'required_level' => 36,
            'xp_reward' => 50,
            'energy_cost' => 7,
            'task_type' => 'brew',
            'materials' => [
                ['name' => 'Defense Potion', 'quantity' => 1],
                ['name' => 'Ghostcap Mushroom', 'quantity' => 1],
                ['name' => 'Turtle Shell Powder', 'quantity' => 1],
            ],
            'output' => ['name' => 'Super Defense Potion', 'quantity' => 1],
        ],
        'prayer_potion' => [
            'name' => 'Prayer Potion',
            'category' => 'spiritual',
            'skill' => 'herblore',
            'required_level' => 38,
            'xp_reward' => 55,
            'energy_cost' => 7,
            'task_type' => 'brew',
            'materials' => [
                ['name' => 'Moonpetal', 'quantity' => 2],
                ['name' => 'Holy Water', 'quantity' => 1],
                ['name' => 'Crystal Vial', 'quantity' => 1],
            ],
            'output' => ['name' => 'Prayer Potion', 'quantity' => 1],
        ],

        // Advanced potions (Level 45-65)
        'greater_health_potion' => [
            'name' => 'Greater Health Potion',
            'category' => 'restoration',
            'skill' => 'herblore',
            'required_level' => 45,
            'xp_reward' => 65,
            'energy_cost' => 8,
            'task_type' => 'brew',
            'materials' => [
                ['name' => 'Health Potion', 'quantity' => 1],
                ['name' => 'Dragonvine', 'quantity' => 1],
                ['name' => 'Phoenix Feather', 'quantity' => 1],
            ],
            'output' => ['name' => 'Greater Health Potion', 'quantity' => 1],
        ],
        'combat_potion' => [
            'name' => 'Combat Potion',
            'category' => 'combat',
            'skill' => 'herblore',
            'required_level' => 50,
            'xp_reward' => 75,
            'energy_cost' => 9,
            'task_type' => 'brew',
            'materials' => [
                ['name' => 'Super Attack Potion', 'quantity' => 1],
                ['name' => 'Super Strength Potion', 'quantity' => 1],
                ['name' => 'Dragonvine', 'quantity' => 1],
            ],
            'output' => ['name' => 'Combat Potion', 'quantity' => 1],
        ],
        'energy_elixir' => [
            'name' => 'Energy Elixir',
            'category' => 'restoration',
            'skill' => 'herblore',
            'required_level' => 55,
            'xp_reward' => 85,
            'energy_cost' => 10,
            'task_type' => 'brew',
            'materials' => [
                ['name' => 'Dragonvine', 'quantity' => 2],
                ['name' => 'Starlight Essence', 'quantity' => 1],
                ['name' => 'Crystal Vial', 'quantity' => 1],
            ],
            'output' => ['name' => 'Energy Elixir', 'quantity' => 1],
        ],

        // Master potions (Level 65+)
        'overload_potion' => [
            'name' => 'Overload Potion',
            'category' => 'combat',
            'skill' => 'herblore',
            'required_level' => 65,
            'xp_reward' => 100,
            'energy_cost' => 12,
            'task_type' => 'brew',
            'materials' => [
                ['name' => 'Combat Potion', 'quantity' => 1],
                ['name' => 'Super Defense Potion', 'quantity' => 1],
                ['name' => 'Dragonvine', 'quantity' => 2],
                ['name' => 'Void Essence', 'quantity' => 1],
            ],
            'output' => ['name' => 'Overload Potion', 'quantity' => 1],
        ],
        'elixir_of_life' => [
            'name' => 'Elixir of Life',
            'category' => 'restoration',
            'skill' => 'herblore',
            'required_level' => 75,
            'xp_reward' => 150,
            'energy_cost' => 15,
            'task_type' => 'brew',
            'materials' => [
                ['name' => 'Greater Health Potion', 'quantity' => 2],
                ['name' => 'Dragonvine', 'quantity' => 3],
                ['name' => 'Phoenix Feather', 'quantity' => 2],
                ['name' => 'Unicorn Tears', 'quantity' => 1],
            ],
            'output' => ['name' => 'Elixir of Life', 'quantity' => 1],
        ],
    ];

    public function __construct(
        protected InventoryService $inventoryService,
        protected DailyTaskService $dailyTaskService,
        protected TownBonusService $townBonusService
    ) {}

    /**
     * Check if user can access apothecary at their current location.
     */
    public function canBrew(User $user): bool
    {
        if ($user->isTraveling() || $user->isInInfirmary()) {
            return false;
        }

        return in_array($user->current_location_type, self::VALID_LOCATIONS);
    }

    /**
     * Get available recipes grouped by category.
     */
    public function getAvailableRecipes(User $user): array
    {
        if (! $this->canBrew($user)) {
            return [];
        }

        $categories = [];

        foreach (self::RECIPES as $id => $recipe) {
            $skillLevel = $user->getSkillLevel($recipe['skill']);

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

        foreach (self::RECIPES as $id => $recipe) {
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

        $yieldBonus = $this->townBonusService->getYieldBonus($user, 'herblore');
        $contributionRate = $this->townBonusService->getContributionRate($user, 'herblore');

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
        $recipe = self::RECIPES[$recipeId] ?? null;
        if (! $recipe) {
            return false;
        }

        $skillLevel = $user->getSkillLevel($recipe['skill']);
        if ($skillLevel < $recipe['required_level']) {
            return false;
        }

        if (! $user->hasEnergy($recipe['energy_cost'])) {
            return false;
        }

        foreach ($recipe['materials'] as $material) {
            $item = Item::where('name', $material['name'])->first();
            if (! $item || ! $this->inventoryService->hasItem($user, $item, $material['quantity'])) {
                return false;
            }
        }

        if (! $this->inventoryService->hasEmptySlot($user)) {
            return false;
        }

        return true;
    }

    /**
     * Brew a potion.
     */
    public function brew(User $user, string $recipeId, ?string $locationType = null, ?int $locationId = null): array
    {
        $recipe = self::RECIPES[$recipeId] ?? null;

        if (! $recipe) {
            return [
                'success' => false,
                'message' => 'Invalid recipe.',
            ];
        }

        if (! $this->canBrew($user)) {
            return [
                'success' => false,
                'message' => 'You cannot brew potions here.',
            ];
        }

        $skillLevel = $user->getSkillLevel($recipe['skill']);
        if ($skillLevel < $recipe['required_level']) {
            return [
                'success' => false,
                'message' => "You need level {$recipe['required_level']} herblore to brew this.",
            ];
        }

        if (! $user->hasEnergy($recipe['energy_cost'])) {
            return [
                'success' => false,
                'message' => "Not enough energy. Need {$recipe['energy_cost']} energy.",
            ];
        }

        foreach ($recipe['materials'] as $material) {
            $item = Item::where('name', $material['name'])->first();
            if (! $item || ! $this->inventoryService->hasItem($user, $item, $material['quantity'])) {
                return [
                    'success' => false,
                    'message' => "You don't have enough {$material['name']}.",
                ];
            }
        }

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

        $locationType = $locationType ?? $user->current_location_type;
        $locationId = $locationId ?? $user->current_location_id;

        return DB::transaction(function () use ($user, $recipe, $outputItem, $locationType, $locationId) {
            $user->consumeEnergy($recipe['energy_cost']);

            foreach ($recipe['materials'] as $material) {
                $item = Item::where('name', $material['name'])->first();
                $this->inventoryService->removeItem($user, $item, $material['quantity']);
            }

            $baseQuantity = $recipe['output']['quantity'];
            $yieldBonus = $this->townBonusService->getYieldBonus($user, 'herblore');
            $bonusQuantity = $this->townBonusService->calculateBonusQuantity($yieldBonus, $baseQuantity);
            $totalQuantity = $baseQuantity + $bonusQuantity;

            $this->inventoryService->addItem($user, $outputItem, $totalQuantity);

            $contributionRate = $this->townBonusService->getContributionRate($user, 'herblore');
            $contribution = $this->townBonusService->calculateContribution($contributionRate, $totalQuantity);
            $contributedToStockpile = false;
            if ($contribution > 0) {
                $contributedToStockpile = $this->townBonusService->contributeToStockpile($user, $outputItem->id, $contribution);
            }

            $xpAwarded = (int) ceil($recipe['xp_reward'] * ($totalQuantity / max(1, $baseQuantity)));

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

            if (isset($recipe['task_type'])) {
                $this->dailyTaskService->recordProgress(
                    $user,
                    $recipe['task_type'],
                    $outputItem->name,
                    $totalQuantity
                );
            }

            if ($locationType && $locationId) {
                try {
                    LocationActivityLog::log(
                        userId: $user->id,
                        locationType: $locationType,
                        locationId: $locationId,
                        activityType: LocationActivityLog::TYPE_CRAFTING,
                        description: "{$user->username} brewed {$totalQuantity}x {$outputItem->name}",
                        activitySubtype: 'apothecary',
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

            $message = "Brewed {$totalQuantity}x {$recipe['output']['name']}!";
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
     * Get brewing info for the current location.
     */
    public function getBrewingInfo(User $user): ?array
    {
        if (! $this->canBrew($user)) {
            return null;
        }

        $bonuses = $this->townBonusService->getBonusInfo($user);

        // Get herblore skill info for XP progress
        $herbloreSkill = $user->skills()->where('skill_name', 'herblore')->first();
        $herbloreLevel = $herbloreSkill?->level ?? 1;
        $herbloreXp = $herbloreSkill?->xp ?? 0;
        $herbloreXpProgress = $herbloreSkill?->getXpProgress() ?? 0;
        $herbloreXpToNext = $herbloreSkill?->xpToNextLevel() ?? 60;

        // Get herbs in inventory
        $herbsInInventory = $this->getHerbsInInventory($user);

        return [
            'can_brew' => true,
            'recipes' => $this->getAvailableRecipes($user),
            'all_recipes' => $this->getAllRecipes($user),
            'player_energy' => $user->energy,
            'max_energy' => $user->max_energy,
            'free_slots' => $this->inventoryService->freeSlots($user),
            'herblore_level' => $herbloreLevel,
            'herblore_xp' => $herbloreXp,
            'herblore_xp_progress' => $herbloreXpProgress,
            'herblore_xp_to_next' => $herbloreXpToNext,
            'herbs_in_inventory' => $herbsInInventory,
            'role_bonuses' => $bonuses,
        ];
    }

    /**
     * Get herbs and vials in the player's inventory.
     */
    protected function getHerbsInInventory(User $user): array
    {
        // List of herb items used in apothecary recipes
        $herbNames = [
            'Herb', 'Healing Herb', 'Sunblossom', 'Stoneroot', 'Moonpetal',
            'Nightshade', 'Ironbark', 'Bloodroot', 'Hawkeye Leaf', 'Swiftfoot Moss',
            'Ghostcap Mushroom', 'Windweed', 'Dragonvine', 'Starlight Essence',
        ];

        // List of vials and containers
        $vialNames = [
            'Vial', 'Crystal Vial', 'Holy Water',
        ];

        // List of monster ingredients
        $monsterIngredients = [
            'Venom Sac', 'Giant Essence', 'Turtle Shell Powder',
            'Phoenix Feather', 'Void Essence', 'Unicorn Tears',
        ];

        // Potions that can be used as ingredients in higher-tier recipes
        $potionIngredients = [
            'Attack Potion', 'Strength Potion', 'Defense Potion',
            'Super Attack Potion', 'Super Strength Potion', 'Super Defense Potion',
            'Health Potion', 'Greater Health Potion', 'Combat Potion',
        ];

        $allIngredients = array_merge($herbNames, $vialNames, $monsterIngredients, $potionIngredients);

        // Get all ingredient items
        $items = Item::whereIn('name', $allIngredients)->get();

        $ingredientsInInventory = [];

        foreach ($items as $item) {
            $quantity = $this->inventoryService->countItem($user, $item);
            if ($quantity > 0) {
                // Determine the type for styling
                $type = 'herb';
                if (in_array($item->name, $vialNames)) {
                    $type = 'vial';
                } elseif (in_array($item->name, $monsterIngredients)) {
                    $type = 'monster';
                } elseif (in_array($item->name, $potionIngredients)) {
                    $type = 'potion';
                }

                $ingredientsInInventory[] = [
                    'name' => $item->name,
                    'quantity' => $quantity,
                    'type' => $type,
                ];
            }
        }

        return $ingredientsInInventory;
    }
}
