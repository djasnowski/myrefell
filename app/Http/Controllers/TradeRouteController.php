<?php

namespace App\Http\Controllers;

use App\Models\Barony;
use App\Models\Caravan;
use App\Models\Kingdom;
use App\Models\Town;
use App\Models\TradeRoute;
use App\Models\Village;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TradeRouteController extends Controller
{
    /**
     * Display a listing of trade routes.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        // Get all active trade routes with their locations
        $routes = TradeRoute::active()
            ->withCount(['caravans' => function ($query) {
                $query->whereIn('status', [
                    Caravan::STATUS_PREPARING,
                    Caravan::STATUS_TRAVELING,
                    Caravan::STATUS_RETURNING,
                ]);
            }])
            ->get()
            ->map(function ($route) {
                $origin = $route->origin;
                $destination = $route->destination;

                return [
                    'id' => $route->id,
                    'name' => $route->name,
                    'origin' => [
                        'type' => $route->origin_type,
                        'id' => $route->origin_id,
                        'name' => $origin?->name ?? 'Unknown',
                    ],
                    'destination' => [
                        'type' => $route->destination_type,
                        'id' => $route->destination_id,
                        'name' => $destination?->name ?? 'Unknown',
                    ],
                    'distance' => $route->distance,
                    'base_travel_days' => $route->base_travel_days,
                    'danger_level' => $route->danger_level,
                    'bandit_chance' => $route->effective_bandit_chance,
                    'active_caravans_count' => $route->caravans_count,
                    'notes' => $route->notes,
                ];
            });

        // Check if user can create routes (must be baron or king)
        $canCreate = $this->canManageRoutes($user);

        return Inertia::render('Trade/Routes', [
            'routes' => $routes->toArray(),
            'can_create' => $canCreate,
        ]);
    }

    /**
     * Check if user can manage trade routes.
     */
    private function canManageRoutes($user): bool
    {
        // Check if user is a baron
        $isBaronOfAny = Barony::where('baron_user_id', $user->id)->exists();
        if ($isBaronOfAny) {
            return true;
        }

        // Check if user is a king
        $isKingOfAny = Kingdom::where('king_user_id', $user->id)->exists();
        if ($isKingOfAny) {
            return true;
        }

        return false;
    }

    /**
     * Store a newly created trade route.
     */
    public function store(Request $request)
    {
        $user = $request->user();

        if (! $this->canManageRoutes($user)) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to create trade routes.',
            ], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'origin_type' => 'required|in:village,town',
            'origin_id' => 'required|integer',
            'destination_type' => 'required|in:village,town',
            'destination_id' => 'required|integer',
            'danger_level' => 'required|in:safe,moderate,dangerous,perilous',
        ]);

        // Validate origin exists
        $origin = match ($validated['origin_type']) {
            'village' => Village::find($validated['origin_id']),
            'town' => Town::find($validated['origin_id']),
            default => null,
        };

        if (! $origin) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid origin location.',
            ], 422);
        }

        // Validate destination exists
        $destination = match ($validated['destination_type']) {
            'village' => Village::find($validated['destination_id']),
            'town' => Town::find($validated['destination_id']),
            default => null,
        };

        if (! $destination) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid destination location.',
            ], 422);
        }

        // Check that origin and destination are different
        if ($validated['origin_type'] === $validated['destination_type']
            && $validated['origin_id'] === $validated['destination_id']) {
            return response()->json([
                'success' => false,
                'message' => 'Origin and destination must be different.',
            ], 422);
        }

        // Calculate approximate distance (simplified - could be enhanced with actual map data)
        $distance = rand(50, 200);
        $baseTravelDays = max(1, (int) ($distance / 50));

        // Set bandit chance based on danger level
        $banditChance = match ($validated['danger_level']) {
            'safe' => 5,
            'moderate' => 15,
            'dangerous' => 30,
            'perilous' => 50,
            default => 10,
        };

        $route = TradeRoute::create([
            'name' => $validated['name'],
            'origin_type' => $validated['origin_type'],
            'origin_id' => $validated['origin_id'],
            'destination_type' => $validated['destination_type'],
            'destination_id' => $validated['destination_id'],
            'distance' => $distance,
            'base_travel_days' => $baseTravelDays,
            'danger_level' => $validated['danger_level'],
            'bandit_chance' => $banditChance,
            'is_active' => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Trade route created successfully.',
            'route_id' => $route->id,
        ]);
    }
}
