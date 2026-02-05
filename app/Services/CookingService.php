<?php

namespace App\Services;

use App\Models\Item;
use App\Models\LocationActivityLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CookingService
{
    /**
     * Cooking recipes configuration.
     */
    public const RECIPES = [
        'flour' => [
            'name' => 'Flour',
            'required_level' => 1,
            'xp_reward' => 8,
            'energy_cost' => 2,
            'materials' => [
                ['name' => 'Grain', 'quantity' => 2],
            ],
            'output' => ['name' => 'Flour', 'quantity' => 1],
        ],
        'bread' => [
            'name' => 'Bread',
            'required_level' => 1,
            'xp_reward' => 15,
            'energy_cost' => 2,
            'materials' => [
                ['name' => 'Flour', 'quantity' => 1],
            ],
            'output' => ['name' => 'Bread', 'quantity' => 1],
        ],
        'cooked_shrimp' => [
            'name' => 'Cooked Shrimp',
            'required_level' => 1,
            'xp_reward' => 18,
            'energy_cost' => 2,
            'materials' => [
                ['name' => 'Raw Shrimp', 'quantity' => 1],
            ],
            'output' => ['name' => 'Cooked Shrimp', 'quantity' => 1],
        ],
        'cooked_chicken' => [
            'name' => 'Cooked Chicken',
            'required_level' => 1,
            'xp_reward' => 15,
            'energy_cost' => 2,
            'materials' => [
                ['name' => 'Raw Chicken', 'quantity' => 1],
            ],
            'output' => ['name' => 'Cooked Chicken', 'quantity' => 1],
        ],
        'cooked_sardine' => [
            'name' => 'Cooked Sardine',
            'required_level' => 5,
            'xp_reward' => 22,
            'energy_cost' => 2,
            'materials' => [
                ['name' => 'Raw Sardine', 'quantity' => 1],
            ],
            'output' => ['name' => 'Cooked Sardine', 'quantity' => 1],
        ],
        'cooked_trout' => [
            'name' => 'Cooked Trout',
            'required_level' => 10,
            'xp_reward' => 35,
            'energy_cost' => 3,
            'materials' => [
                ['name' => 'Raw Trout', 'quantity' => 1],
            ],
            'output' => ['name' => 'Cooked Trout', 'quantity' => 1],
        ],
        'cooked_salmon' => [
            'name' => 'Cooked Salmon',
            'required_level' => 20,
            'xp_reward' => 50,
            'energy_cost' => 3,
            'materials' => [
                ['name' => 'Raw Salmon', 'quantity' => 1],
            ],
            'output' => ['name' => 'Cooked Salmon', 'quantity' => 1],
        ],
        'cooked_lobster' => [
            'name' => 'Cooked Lobster',
            'required_level' => 30,
            'xp_reward' => 75,
            'energy_cost' => 4,
            'materials' => [
                ['name' => 'Raw Lobster', 'quantity' => 1],
            ],
            'output' => ['name' => 'Cooked Lobster', 'quantity' => 1],
        ],
        'cooked_swordfish' => [
            'name' => 'Cooked Swordfish',
            'required_level' => 40,
            'xp_reward' => 100,
            'energy_cost' => 4,
            'materials' => [
                ['name' => 'Raw Swordfish', 'quantity' => 1],
            ],
            'output' => ['name' => 'Cooked Swordfish', 'quantity' => 1],
        ],
        'cooked_meat' => [
            'name' => 'Cooked Meat',
            'required_level' => 5,
            'xp_reward' => 25,
            'energy_cost' => 2,
            'materials' => [
                ['name' => 'Raw Meat', 'quantity' => 1],
            ],
            'output' => ['name' => 'Cooked Meat', 'quantity' => 1],
        ],
        'meat_pie' => [
            'name' => 'Meat Pie',
            'required_level' => 15,
            'xp_reward' => 55,
            'energy_cost' => 4,
            'materials' => [
                ['name' => 'Flour', 'quantity' => 1],
                ['name' => 'Raw Meat', 'quantity' => 1],
            ],
            'output' => ['name' => 'Meat Pie', 'quantity' => 1],
        ],
    ];

    public function __construct(
        protected InventoryService $inventoryService,
        protected DailyTaskService $dailyTaskService
    ) {}

    /**
     * Get cooking info for the tavern.
     */
    public function getCookingInfo(User $user): array
    {
        $recipes = [];

        foreach (self::RECIPES as $id => $recipe) {
            $recipes[] = $this->formatRecipe($id, $recipe, $user);
        }

        return [
            'recipes' => $recipes,
            'cooking_level' => $user->getSkillLevel('cooking'),
        ];
    }

    /**
     * Format a recipe for display.
     */
    protected function formatRecipe(string $id, array $recipe, User $user): array
    {
        $skillLevel = $user->getSkillLevel('cooking');
        $isLocked = $skillLevel < $recipe['required_level'];

        // Get material availability
        $materials = [];
        $hasMaterials = true;
        foreach ($recipe['materials'] as $material) {
            $item = Item::where('name', $material['name'])->first();
            $playerHas = $item ? $this->inventoryService->countItem($user, $item) : 0;
            $hasEnough = $playerHas >= $material['quantity'];

            if (! $hasEnough) {
                $hasMaterials = false;
            }

            $materials[] = [
                'name' => $material['name'],
                'required' => $material['quantity'],
                'have' => $playerHas,
                'has_enough' => $hasEnough,
            ];
        }

        $canMake = ! $isLocked
            && $hasMaterials
            && $user->hasEnergy($recipe['energy_cost'])
            && $this->inventoryService->hasEmptySlot($user);

        return [
            'id' => $id,
            'name' => $recipe['name'],
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
     * Cook a recipe.
     */
    public function cook(User $user, string $recipeId, ?string $locationType = null, ?int $locationId = null): array
    {
        $recipe = self::RECIPES[$recipeId] ?? null;

        if (! $recipe) {
            return [
                'success' => false,
                'message' => 'Invalid recipe.',
            ];
        }

        $skillLevel = $user->getSkillLevel('cooking');
        if ($skillLevel < $recipe['required_level']) {
            return [
                'success' => false,
                'message' => "You need level {$recipe['required_level']} cooking to make this.",
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

            $quantity = $recipe['output']['quantity'];

            // Add output item
            $this->inventoryService->addItem($user, $outputItem, $quantity);

            // Award XP
            $xpAwarded = $recipe['xp_reward'];

            // Get or create the skill
            $skill = $user->skills()->where('skill_name', 'cooking')->first();

            if (! $skill) {
                $skill = $user->skills()->create([
                    'skill_name' => 'cooking',
                    'level' => 1,
                    'xp' => 0,
                ]);
            }

            $oldLevel = $skill->level;
            $skill->addXp($xpAwarded);
            $leveledUp = $skill->fresh()->level > $oldLevel;

            // Record daily task progress
            $this->dailyTaskService->recordProgress($user, 'cook', $outputItem->name, $quantity);

            // Log activity at location
            if ($locationType && $locationId) {
                try {
                    LocationActivityLog::log(
                        userId: $user->id,
                        locationType: $locationType,
                        locationId: $locationId,
                        activityType: LocationActivityLog::TYPE_CRAFTING,
                        description: "{$user->username} cooked {$quantity}x {$outputItem->name} at the tavern",
                        activitySubtype: $recipeId,
                        metadata: [
                            'item' => $outputItem->name,
                            'quantity' => $quantity,
                            'xp_gained' => $xpAwarded,
                            'leveled_up' => $leveledUp,
                        ]
                    );
                } catch (\Illuminate\Database\QueryException $e) {
                    // Table may not exist yet
                }
            }

            return [
                'success' => true,
                'message' => "Cooked {$quantity}x {$recipe['output']['name']}!",
                'item' => [
                    'name' => $outputItem->name,
                    'quantity' => $quantity,
                ],
                'xp_awarded' => $xpAwarded,
                'leveled_up' => $leveledUp,
                'energy_remaining' => $user->fresh()->energy,
            ];
        });
    }
}
