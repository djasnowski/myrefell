<?php

namespace App\Services;

use App\Models\Item;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CraftingService
{
    /**
     * Crafting recipes configuration.
     * Each recipe has required materials and the output.
     */
    public const RECIPES = [
        // Smithing recipes
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

        // Cooking recipes
        'cooked_shrimp' => [
            'name' => 'Cooked Shrimp',
            'category' => 'cooking',
            'skill' => 'cooking',
            'required_level' => 1,
            'xp_reward' => 10,
            'energy_cost' => 2,
            'task_type' => 'cook',
            'materials' => [
                ['name' => 'Raw Shrimp', 'quantity' => 1],
            ],
            'output' => ['name' => 'Cooked Shrimp', 'quantity' => 1],
        ],
        'cooked_trout' => [
            'name' => 'Cooked Trout',
            'category' => 'cooking',
            'skill' => 'cooking',
            'required_level' => 10,
            'xp_reward' => 20,
            'energy_cost' => 3,
            'task_type' => 'cook',
            'materials' => [
                ['name' => 'Raw Trout', 'quantity' => 1],
            ],
            'output' => ['name' => 'Cooked Trout', 'quantity' => 1],
        ],
        'cooked_salmon' => [
            'name' => 'Cooked Salmon',
            'category' => 'cooking',
            'skill' => 'cooking',
            'required_level' => 20,
            'xp_reward' => 30,
            'energy_cost' => 3,
            'task_type' => 'cook',
            'materials' => [
                ['name' => 'Raw Salmon', 'quantity' => 1],
            ],
            'output' => ['name' => 'Cooked Salmon', 'quantity' => 1],
        ],
        'bread' => [
            'name' => 'Bread',
            'category' => 'cooking',
            'skill' => 'cooking',
            'required_level' => 1,
            'xp_reward' => 8,
            'energy_cost' => 2,
            'task_type' => 'cook',
            'materials' => [
                ['name' => 'Flour', 'quantity' => 1],
            ],
            'output' => ['name' => 'Bread', 'quantity' => 1],
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
     * Location types that allow crafting.
     */
    public const VALID_LOCATIONS = ['village', 'castle', 'town'];

    public function __construct(
        protected InventoryService $inventoryService,
        protected DailyTaskService $dailyTaskService
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

        foreach (self::RECIPES as $id => $recipe) {
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
    public function craft(User $user, string $recipeId): array
    {
        $recipe = self::RECIPES[$recipeId] ?? null;

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

        return DB::transaction(function () use ($user, $recipe, $outputItem) {
            // Consume energy
            $user->consumeEnergy($recipe['energy_cost']);

            // Remove materials
            foreach ($recipe['materials'] as $material) {
                $item = Item::where('name', $material['name'])->first();
                $this->inventoryService->removeItem($user, $item, $material['quantity']);
            }

            // Add output item
            $this->inventoryService->addItem($user, $outputItem, $recipe['output']['quantity']);

            // Award XP
            $skill = $user->skills()->where('skill_name', $recipe['skill'])->first();
            $leveledUp = false;

            if ($skill) {
                $oldLevel = $skill->level;
                $skill->addXp($recipe['xp_reward']);
                $leveledUp = $skill->fresh()->level > $oldLevel;
            }

            // Record daily task progress
            if (isset($recipe['task_type'])) {
                $this->dailyTaskService->recordProgress(
                    $user,
                    $recipe['task_type'],
                    $outputItem->name,
                    $recipe['output']['quantity']
                );
            }

            return [
                'success' => true,
                'message' => "Crafted {$recipe['output']['quantity']}x {$recipe['output']['name']}!",
                'item' => [
                    'name' => $outputItem->name,
                    'quantity' => $recipe['output']['quantity'],
                ],
                'xp_awarded' => $recipe['xp_reward'],
                'skill' => $recipe['skill'],
                'leveled_up' => $leveledUp,
                'energy_remaining' => $user->fresh()->energy,
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

        return [
            'can_craft' => true,
            'recipes' => $this->getAvailableRecipes($user),
            'all_recipes' => $this->getAllRecipes($user),
            'player_energy' => $user->energy,
            'max_energy' => $user->max_energy,
            'free_slots' => $this->inventoryService->freeSlots($user),
        ];
    }
}
