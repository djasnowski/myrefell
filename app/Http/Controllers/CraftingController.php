<?php

namespace App\Http\Controllers;

use App\Services\CraftingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CraftingController extends Controller
{
    public function __construct(
        protected CraftingService $craftingService
    ) {}

    /**
     * Show the crafting page.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        if (! $this->craftingService->canCraft($user)) {
            return Inertia::render('Crafting/NotAvailable', [
                'message' => 'You cannot access crafting at your current location.',
            ]);
        }

        $info = $this->craftingService->getCraftingInfo($user);

        return Inertia::render('Crafting/Index', [
            'crafting_info' => $info,
        ]);
    }

    /**
     * Craft an item.
     */
    public function craft(Request $request): JsonResponse
    {
        $request->validate([
            'recipe' => 'required|string',
        ]);

        $user = $request->user();
        $result = $this->craftingService->craft($user, $request->input('recipe'));

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * Get recipe details.
     */
    public function recipe(Request $request, string $recipeId): JsonResponse
    {
        $user = $request->user();
        $recipes = $this->craftingService->getAllRecipes($user);

        // Find recipe across categories
        foreach ($recipes as $category => $categoryRecipes) {
            foreach ($categoryRecipes as $recipe) {
                if ($recipe['id'] === $recipeId) {
                    return response()->json($recipe);
                }
            }
        }

        return response()->json(['error' => 'Recipe not found'], 404);
    }
}
