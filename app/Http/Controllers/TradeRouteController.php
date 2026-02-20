<?php

namespace App\Http\Controllers;

use App\Models\Barony;
use App\Models\Caravan;
use App\Models\PlayerRole;
use App\Models\Role;
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
        // Get baron and king role IDs
        $rulerRoles = Role::whereIn('slug', ['baron', 'king'])->pluck('id');

        if ($rulerRoles->isEmpty()) {
            return false;
        }

        // Check if user holds any baron or king role
        return PlayerRole::where('user_id', $user->id)
            ->whereIn('role_id', $rulerRoles)
            ->active()
            ->exists();
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

    /**
     * Display trade routes for a specific barony.
     */
    public function baronyTradeRoutes(Request $request, Barony $barony): Response
    {
        $user = $request->user();

        // Check if user is baron of this barony
        $baronRole = Role::where('slug', 'baron')->first();
        $isBaron = $baronRole && PlayerRole::where('user_id', $user->id)
            ->where('role_id', $baronRole->id)
            ->where('location_type', 'barony')
            ->where('location_id', $barony->id)
            ->active()
            ->exists();

        if (! $isBaron) {
            abort(403, 'You must be the Baron of this barony to manage its trade routes.');
        }

        // Get villages in this barony for the dropdown
        $villages = $barony->villages()->orderBy('name')->get()->map(fn ($v) => [
            'id' => $v->id,
            'name' => $v->name,
            'type' => 'village',
        ]);

        // Get towns in this barony
        $towns = $barony->towns()->orderBy('name')->get()->map(fn ($t) => [
            'id' => $t->id,
            'name' => $t->name,
            'type' => 'town',
        ]);

        // Get all villages and towns (for destination - can route to other baronies)
        $allVillages = Village::orderBy('name')->get()->map(fn ($v) => [
            'id' => $v->id,
            'name' => $v->name,
            'type' => 'village',
            'barony_id' => $v->barony_id,
        ]);

        $allTowns = Town::orderBy('name')->get()->map(fn ($t) => [
            'id' => $t->id,
            'name' => $t->name,
            'type' => 'town',
            'barony_id' => $t->barony_id,
        ]);

        // Get trade routes that originate from or go to this barony's settlements
        $baronyVillageIds = $barony->villages->pluck('id')->toArray();
        $baronyTownIds = $barony->towns->pluck('id')->toArray();

        $routes = TradeRoute::active()
            ->where(function ($query) use ($baronyVillageIds, $baronyTownIds) {
                $query->where(function ($q) use ($baronyVillageIds) {
                    $q->where('origin_type', 'village')
                        ->whereIn('origin_id', $baronyVillageIds);
                })->orWhere(function ($q) use ($baronyTownIds) {
                    $q->where('origin_type', 'town')
                        ->whereIn('origin_id', $baronyTownIds);
                })->orWhere(function ($q) use ($baronyVillageIds) {
                    $q->where('destination_type', 'village')
                        ->whereIn('destination_id', $baronyVillageIds);
                })->orWhere(function ($q) use ($baronyTownIds) {
                    $q->where('destination_type', 'town')
                        ->whereIn('destination_id', $baronyTownIds);
                });
            })
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

        return Inertia::render('Trade/BaronyRoutes', [
            'barony' => [
                'id' => $barony->id,
                'name' => $barony->name,
            ],
            'routes' => $routes->toArray(),
            'barony_locations' => $villages->merge($towns)->toArray(),
            'all_locations' => $allVillages->merge($allTowns)->toArray(),
        ]);
    }

    /**
     * Store a new trade route for a barony.
     */
    public function storeBaronyRoute(Request $request, Barony $barony)
    {
        $user = $request->user();

        // Check if user is baron of this barony
        $baronRole = Role::where('slug', 'baron')->first();
        $isBaron = $baronRole && PlayerRole::where('user_id', $user->id)
            ->where('role_id', $baronRole->id)
            ->where('location_type', 'barony')
            ->where('location_id', $barony->id)
            ->active()
            ->exists();

        if (! $isBaron) {
            return response()->json([
                'success' => false,
                'message' => 'You must be the Baron of this barony to create trade routes.',
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

        // Validate origin exists and belongs to this barony
        $origin = match ($validated['origin_type']) {
            'village' => Village::where('barony_id', $barony->id)->find($validated['origin_id']),
            'town' => Town::where('barony_id', $barony->id)->find($validated['origin_id']),
            default => null,
        };

        if (! $origin) {
            return response()->json([
                'success' => false,
                'message' => 'Origin must be a settlement within your barony.',
            ], 422);
        }

        // Validate destination exists (can be any settlement)
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

        // Calculate approximate distance based on coordinates if available
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

    /**
     * Update a trade route for a barony.
     */
    public function updateBaronyRoute(Request $request, Barony $barony, TradeRoute $tradeRoute)
    {
        $user = $request->user();

        // Check if user is baron of this barony
        $baronRole = Role::where('slug', 'baron')->first();
        $isBaron = $baronRole && PlayerRole::where('user_id', $user->id)
            ->where('role_id', $baronRole->id)
            ->where('location_type', 'barony')
            ->where('location_id', $barony->id)
            ->active()
            ->exists();

        if (! $isBaron) {
            return response()->json([
                'success' => false,
                'message' => 'You must be the Baron of this barony to update trade routes.',
            ], 403);
        }

        // Verify the route belongs to this barony (origin is within barony)
        if (! $this->routeBelongsToBarony($tradeRoute, $barony)) {
            return response()->json([
                'success' => false,
                'message' => 'This trade route does not belong to your barony.',
            ], 403);
        }

        // Block editing if there are active caravans on the route
        $activeCaravans = $tradeRoute->caravans()
            ->whereIn('status', [
                Caravan::STATUS_PREPARING,
                Caravan::STATUS_TRAVELING,
                Caravan::STATUS_RETURNING,
            ])
            ->count();

        if ($activeCaravans > 0) {
            return response()->json([
                'success' => false,
                'message' => "Cannot edit this route while {$activeCaravans} caravan(s) are active on it.",
            ], 422);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'danger_level' => 'required|in:safe,moderate,dangerous,perilous',
            'notes' => 'nullable|string|max:500',
        ]);

        $banditChance = match ($validated['danger_level']) {
            'safe' => 5,
            'moderate' => 15,
            'dangerous' => 30,
            'perilous' => 50,
            default => 10,
        };

        $tradeRoute->update([
            'name' => $validated['name'],
            'danger_level' => $validated['danger_level'],
            'bandit_chance' => $banditChance,
            'notes' => $validated['notes'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Trade route updated successfully.',
        ]);
    }

    /**
     * Deactivate a trade route for a barony.
     */
    public function deleteBaronyRoute(Request $request, Barony $barony, TradeRoute $tradeRoute)
    {
        $user = $request->user();

        // Check if user is baron of this barony
        $baronRole = Role::where('slug', 'baron')->first();
        $isBaron = $baronRole && PlayerRole::where('user_id', $user->id)
            ->where('role_id', $baronRole->id)
            ->where('location_type', 'barony')
            ->where('location_id', $barony->id)
            ->active()
            ->exists();

        if (! $isBaron) {
            return response()->json([
                'success' => false,
                'message' => 'You must be the Baron of this barony to delete trade routes.',
            ], 403);
        }

        // Verify the route belongs to this barony
        if (! $this->routeBelongsToBarony($tradeRoute, $barony)) {
            return response()->json([
                'success' => false,
                'message' => 'This trade route does not belong to your barony.',
            ], 403);
        }

        // Block deletion if there are active caravans on the route
        $activeCaravans = $tradeRoute->caravans()
            ->whereIn('status', [
                Caravan::STATUS_PREPARING,
                Caravan::STATUS_TRAVELING,
                Caravan::STATUS_RETURNING,
            ])
            ->count();

        if ($activeCaravans > 0) {
            return response()->json([
                'success' => false,
                'message' => "Cannot delete this route while {$activeCaravans} caravan(s) are active on it.",
            ], 422);
        }

        $tradeRoute->update(['is_active' => false]);

        return response()->json([
            'success' => true,
            'message' => 'Trade route has been removed.',
        ]);
    }

    /**
     * Check if a trade route's origin belongs to the given barony.
     */
    private function routeBelongsToBarony(TradeRoute $tradeRoute, Barony $barony): bool
    {
        $baronyVillageIds = $barony->villages->pluck('id')->toArray();
        $baronyTownIds = $barony->towns->pluck('id')->toArray();

        return ($tradeRoute->origin_type === 'village' && in_array($tradeRoute->origin_id, $baronyVillageIds))
            || ($tradeRoute->origin_type === 'town' && in_array($tradeRoute->origin_id, $baronyTownIds));
    }
}
