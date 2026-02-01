<?php

namespace App\Http\Controllers;

use App\Models\Barony;
use App\Models\Duchy;
use App\Models\Kingdom;
use App\Models\LocationActivityLog;
use App\Models\Town;
use App\Models\Village;
use App\Services\CookingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TavernController extends Controller
{
    /**
     * Rest configuration by location type.
     */
    private const REST_CONFIG = [
        'village' => ['cost' => 10, 'energy' => 50],
        'town' => ['cost' => 15, 'energy' => 75],
        'barony' => ['cost' => 15, 'energy' => 75],
        'duchy' => ['cost' => 20, 'energy' => 100],
        'kingdom' => ['cost' => 25, 'energy' => 150],
    ];

    public function __construct(
        protected CookingService $cookingService
    ) {}

    /**
     * Show the tavern page.
     */
    public function index(Request $request, ?Village $village = null, ?Town $town = null, ?Barony $barony = null, ?Duchy $duchy = null, ?Kingdom $kingdom = null): Response
    {
        $user = $request->user();

        $location = $village ?? $town ?? $barony ?? $duchy ?? $kingdom;
        $locationType = $this->getLocationType($location);

        // Get recent activity at this location (rumors/gossip)
        $recentActivity = [];
        if ($location && $locationType) {
            try {
                $recentActivity = LocationActivityLog::atLocation($locationType, $location->id)
                    ->recent(20)
                    ->with('user:id,username')
                    ->get()
                    ->map(fn ($log) => [
                        'id' => $log->id,
                        'username' => $log->user->username ?? 'Unknown',
                        'description' => $log->description,
                        'type' => $log->activity_type,
                        'subtype' => $log->activity_subtype,
                        'time_ago' => $log->created_at->diffForHumans(),
                    ]);
            } catch (\Illuminate\Database\QueryException $e) {
                $recentActivity = [];
            }
        }

        // Calculate rest cost and benefits based on location
        $restConfig = self::REST_CONFIG[$locationType] ?? self::REST_CONFIG['village'];
        $restCost = $restConfig['cost'];
        $energyRestored = min($restConfig['energy'], $user->max_energy - $user->energy);

        // Get cooking recipes
        $cookingInfo = $this->cookingService->getCookingInfo($user);

        return Inertia::render('Tavern/Index', [
            'location' => $location ? [
                'type' => $locationType,
                'id' => $location->id,
                'name' => $location->name,
            ] : null,
            'player' => [
                'energy' => $user->energy,
                'max_energy' => $user->max_energy,
                'gold' => $user->gold,
            ],
            'rest' => [
                'cost' => $restCost,
                'energy_restored' => $energyRestored,
                'can_rest' => $user->gold >= $restCost && $user->energy < $user->max_energy,
            ],
            'recent_activity' => $recentActivity,
            'cooking' => $cookingInfo,
        ]);
    }

    /**
     * Rest at the tavern to restore energy.
     */
    public function rest(Request $request, ?Village $village = null, ?Town $town = null, ?Barony $barony = null, ?Duchy $duchy = null, ?Kingdom $kingdom = null)
    {
        $user = $request->user();
        $location = $village ?? $town ?? $barony ?? $duchy ?? $kingdom;
        $locationType = $this->getLocationType($location) ?? $user->current_location_type ?? 'village';

        $restConfig = self::REST_CONFIG[$locationType] ?? self::REST_CONFIG['village'];
        $restCost = $restConfig['cost'];
        $energyRestored = min($restConfig['energy'], $user->max_energy - $user->energy);

        if ($user->gold < $restCost) {
            return back()->withErrors(['error' => "You need {$restCost}g to rest at the tavern."]);
        }

        if ($user->energy >= $user->max_energy) {
            return back()->withErrors(['error' => 'You are already fully rested.']);
        }

        $user->decrement('gold', $restCost);
        $user->increment('energy', $energyRestored);

        // Log activity
        if ($user->current_location_type && $user->current_location_id) {
            try {
                LocationActivityLog::log(
                    userId: $user->id,
                    locationType: $user->current_location_type,
                    locationId: $user->current_location_id,
                    activityType: LocationActivityLog::TYPE_REST,
                    description: "{$user->username} rested at the tavern",
                    metadata: ['energy_restored' => $energyRestored, 'gold_spent' => $restCost]
                );
            } catch (\Illuminate\Database\QueryException $e) {
                // Table may not exist
            }
        }

        return back()->with('success', "You rest at the tavern and recover {$energyRestored} energy.");
    }

    /**
     * Cook a recipe at the tavern.
     */
    public function cook(Request $request, ?Village $village = null, ?Town $town = null, ?Barony $barony = null, ?Duchy $duchy = null, ?Kingdom $kingdom = null): JsonResponse
    {
        $request->validate([
            'recipe' => 'required|string',
        ]);

        $location = $village ?? $town ?? $barony ?? $duchy ?? $kingdom;
        $locationType = $this->getLocationType($location);

        $user = $request->user();
        $result = $this->cookingService->cook(
            $user,
            $request->input('recipe'),
            $locationType ?? $user->current_location_type,
            $location?->id ?? $user->current_location_id
        );

        return response()->json($result, $result['success'] ? 200 : 422);
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
            $location instanceof Duchy => 'duchy',
            $location instanceof Kingdom => 'kingdom',
            default => null,
        };
    }
}
