<?php

namespace App\Http\Controllers;

use App\Config\LocationServices;
use App\Models\Barony;
use App\Models\MigrationRequest;
use App\Models\PlayerRole;
use App\Models\Role;
use App\Models\TradeRoute;
use App\Services\MigrationService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BaronyController extends Controller
{
    /**
     * Display a listing of all baronies.
     */
    public function index(): Response
    {
        $baronies = Barony::with('kingdom')
            ->withCount(['villages', 'towns'])
            ->orderBy('name')
            ->get()
            ->map(fn ($barony) => [
                'id' => $barony->id,
                'name' => $barony->name,
                'description' => $barony->description,
                'biome' => $barony->biome,
                'tax_rate' => $barony->tax_rate,
                'villages_count' => $barony->villages_count,
                'towns_count' => $barony->towns_count,
                'kingdom' => $barony->kingdom ? [
                    'id' => $barony->kingdom->id,
                    'name' => $barony->kingdom->name,
                ] : null,
                'is_capital' => $barony->isCapitalBarony(),
                'coordinates' => [
                    'x' => $barony->coordinates_x,
                    'y' => $barony->coordinates_y,
                ],
            ]);

        return Inertia::render('baronies/index', [
            'baronies' => $baronies,
        ]);
    }

    /**
     * Display the specified barony.
     */
    public function show(Request $request, Barony $barony, MigrationService $migrationService): Response
    {
        $barony->load(['kingdom', 'villages', 'towns']);
        $user = $request->user();

        // Get the baron from player_roles table (the authoritative source)
        $baron = null;
        $baronRole = Role::where('slug', 'baron')->first();
        if ($baronRole) {
            $baronAssignment = PlayerRole::active()
                ->where('role_id', $baronRole->id)
                ->where('location_type', 'barony')
                ->where('location_id', $barony->id)
                ->with('user')
                ->first();

            if ($baronAssignment) {
                $baron = [
                    'id' => $baronAssignment->user->id,
                    'username' => $baronAssignment->user->username,
                    'primary_title' => $baronAssignment->user->primary_title,
                    'legitimacy' => $baronAssignment->legitimacy ?? 50,
                ];
            }
        }

        // Check if current user is the baron
        $isBaron = $baron && $baron['id'] === $user->id;

        // Check if user is a resident of this barony (settled directly in barony)
        $isResident = $user->home_location_type === 'barony' && $user->home_location_id === $barony->id;

        // Check for pending migration request
        $hasPendingRequest = MigrationRequest::where('user_id', $user->id)
            ->pending()
            ->exists();

        // Calculate aggregated stats
        $totalPopulation = $barony->villages->sum('population') + $barony->towns->sum('population');
        $totalWealth = $barony->villages->sum('wealth') + $barony->towns->sum('wealth');

        // Get services available at barony level
        $services = LocationServices::getServicesForLocation('barony');

        // Get trade routes involving settlements in this barony
        $villageIds = $barony->villages->pluck('id')->toArray();
        $townIds = $barony->towns->pluck('id')->toArray();

        $tradeRoutes = TradeRoute::active()
            ->where(function ($query) use ($villageIds, $townIds) {
                $query->where(function ($q) use ($villageIds) {
                    $q->where('origin_type', 'village')
                        ->whereIn('origin_id', $villageIds);
                })->orWhere(function ($q) use ($townIds) {
                    $q->where('origin_type', 'town')
                        ->whereIn('origin_id', $townIds);
                })->orWhere(function ($q) use ($villageIds) {
                    $q->where('destination_type', 'village')
                        ->whereIn('destination_id', $villageIds);
                })->orWhere(function ($q) use ($townIds) {
                    $q->where('destination_type', 'town')
                        ->whereIn('destination_id', $townIds);
                });
            })
            ->get()
            ->map(fn ($route) => [
                'id' => $route->id,
                'name' => $route->name,
                'origin' => [
                    'type' => $route->origin_type,
                    'id' => $route->origin_id,
                    'name' => $route->origin?->name ?? 'Unknown',
                ],
                'destination' => [
                    'type' => $route->destination_type,
                    'id' => $route->destination_id,
                    'name' => $route->destination?->name ?? 'Unknown',
                ],
                'distance' => $route->distance,
                'base_travel_days' => $route->base_travel_days,
                'danger_level' => $route->danger_level,
            ]);

        // TODO: Activity logging not yet implemented
        $recentActivity = collect([]);

        return Inertia::render('baronies/show', [
            'barony' => [
                'id' => $barony->id,
                'name' => $barony->name,
                'description' => $barony->description,
                'biome' => $barony->biome,
                'tax_rate' => $barony->tax_rate,
                'is_capital' => $barony->isCapitalBarony(),
                'coordinates' => [
                    'x' => $barony->coordinates_x,
                    'y' => $barony->coordinates_y,
                ],
                'kingdom' => $barony->kingdom ? [
                    'id' => $barony->kingdom->id,
                    'name' => $barony->kingdom->name,
                    'biome' => $barony->kingdom->biome,
                ] : null,
                'villages' => $barony->villages->map(fn ($village) => [
                    'id' => $village->id,
                    'name' => $village->name,
                    'biome' => $village->biome,
                    'population' => $village->population,
                    'wealth' => $village->wealth,
                    'is_hamlet' => $village->isHamlet(),
                ]),
                'towns' => $barony->towns->map(fn ($town) => [
                    'id' => $town->id,
                    'name' => $town->name,
                    'biome' => $town->biome,
                    'population' => $town->population,
                    'wealth' => $town->wealth,
                ]),
                'village_count' => $barony->villages->count(),
                'town_count' => $barony->towns->count(),
                'total_population' => $totalPopulation,
                'total_wealth' => $totalWealth,
                'baron' => $baron,
            ],
            'services' => array_values(array_map(fn ($service, $id) => array_merge($service, ['id' => $id]), $services, array_keys($services))),
            'trade_routes' => $tradeRoutes,
            'recent_activity' => $recentActivity,
            'current_user_id' => $user->id,
            'is_baron' => $isBaron,
            'is_resident' => $isResident,
            'can_migrate' => $migrationService->canMigrate($user),
            'has_pending_request' => $hasPendingRequest,
        ]);
    }

    /**
     * Get villages under a barony (for AJAX requests).
     */
    public function villages(Barony $barony)
    {
        $villages = $barony->villages()
            ->orderBy('name')
            ->get()
            ->map(fn ($village) => [
                'id' => $village->id,
                'name' => $village->name,
                'biome' => $village->biome,
                'population' => $village->population,
                'is_hamlet' => $village->isHamlet(),
            ]);

        return response()->json([
            'barony_id' => $barony->id,
            'barony_name' => $barony->name,
            'villages' => $villages,
            'count' => $villages->count(),
        ]);
    }

    /**
     * Get towns under a barony (for AJAX requests).
     */
    public function towns(Barony $barony)
    {
        $towns = $barony->towns()
            ->orderBy('name')
            ->get()
            ->map(fn ($town) => [
                'id' => $town->id,
                'name' => $town->name,
                'biome' => $town->biome,
                'population' => $town->population,
            ]);

        return response()->json([
            'barony_id' => $barony->id,
            'barony_name' => $barony->name,
            'towns' => $towns,
            'count' => $towns->count(),
        ]);
    }
}
