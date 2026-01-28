<?php

namespace App\Http\Controllers;

use App\Config\LocationServices;
use App\Models\Barony;
use App\Models\LocationActivityLog;
use App\Models\Town;
use App\Models\Village;
use App\Services\CraftingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CraftingController extends Controller
{
    public function __construct(
        protected CraftingService $craftingService
    ) {}

    /**
     * Legacy index - redirects to location-scoped route.
     */
    public function legacyIndex(Request $request): Response|RedirectResponse
    {
        $user = $request->user();

        // Redirect to location-scoped route if possible
        if ($user->current_location_type && $user->current_location_id) {
            $routeName = LocationServices::getServiceRoute($user->current_location_type, 'crafting');
            if ($routeName && \Route::has($routeName)) {
                return redirect()->route($routeName, [$user->current_location_type => $user->current_location_id]);
            }
        }

        // Fall back to original behavior
        return $this->renderIndex($request->user(), null, null);
    }

    /**
     * Show the crafting page (location-scoped).
     */
    public function index(Request $request, Village|Town|Barony $village = null, Town $town = null, Barony $barony = null): Response
    {
        $location = $village ?? $town ?? $barony;
        $locationType = $this->getLocationType($location);

        return $this->renderIndex($request->user(), $location, $locationType);
    }

    /**
     * Render the crafting index page.
     */
    protected function renderIndex($user, $location, ?string $locationType): Response
    {
        if (! $this->craftingService->canCraft($user)) {
            return Inertia::render('Crafting/NotAvailable', [
                'message' => 'You cannot access crafting at your current location.',
            ]);
        }

        $info = $this->craftingService->getCraftingInfo($user);

        $data = [
            'crafting_info' => $info,
        ];

        // Add location context if available
        if ($location && $locationType) {
            $data['location'] = [
                'type' => $locationType,
                'id' => $location->id,
                'name' => $location->name,
            ];

            // Get recent crafting activity at this location
            try {
                $data['recent_activity'] = LocationActivityLog::atLocation($locationType, $location->id)
                    ->ofType(LocationActivityLog::TYPE_CRAFTING)
                    ->recent(10)
                    ->with('user:id,username')
                    ->get()
                    ->map(fn ($log) => [
                        'id' => $log->id,
                        'username' => $log->user->username ?? 'Unknown',
                        'description' => $log->description,
                        'subtype' => $log->activity_subtype,
                        'metadata' => $log->metadata,
                        'created_at' => $log->created_at->toIso8601String(),
                        'time_ago' => $log->created_at->diffForHumans(),
                    ]);
            } catch (\Illuminate\Database\QueryException $e) {
                $data['recent_activity'] = [];
            }
        }

        return Inertia::render('Crafting/Index', $data);
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
        $result = $this->craftingService->craft(
            $user,
            $request->input('recipe'),
            $user->current_location_type,
            $user->current_location_id
        );

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

    /**
     * Determine location type from model.
     */
    protected function getLocationType($location): ?string
    {
        return match (true) {
            $location instanceof Village => 'village',
            $location instanceof Town => 'town',
            $location instanceof Barony => 'barony',
            default => null,
        };
    }
}
