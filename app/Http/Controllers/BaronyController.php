<?php

namespace App\Http\Controllers;

use App\Config\ConstructionConfig;
use App\Config\LocationServices;
use App\Models\Barony;
use App\Models\LocationActivityLog;
use App\Models\MigrationRequest;
use App\Models\PlayerHouse;
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
        $barony->load(['kingdom', 'villages', 'towns', 'visitors', 'residents']);
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

        // Get visitors (players currently in this barony)
        $visitors = $barony->visitors->take(12)->map(fn ($visitor) => [
            'id' => $visitor->id,
            'username' => $visitor->username,
            'combat_level' => $visitor->combat_level ?? 1,
        ]);

        // Check if user is currently in this barony
        $isVisitor = $user->current_location_type === 'barony' && $user->current_location_id === $barony->id;

        // Get residents with home set to this barony
        $residents = $barony->residents->take(12)->map(fn ($resident) => [
            'id' => $resident->id,
            'username' => $resident->username,
            'combat_level' => $resident->combat_level ?? 1,
        ]);

        // Check if current user is the baron
        $isBaron = $baron && $baron['id'] === $user->id;

        // Check if current user is king of this barony's kingdom
        $isKing = PlayerRole::where('user_id', $user->id)
            ->whereHas('role', fn ($q) => $q->where('slug', 'king'))
            ->where('location_type', 'kingdom')
            ->where('location_id', $barony->kingdom_id)
            ->active()
            ->exists();

        // Check if user is a resident of this barony (settled directly in barony)
        $isResident = $user->home_location_type === 'barony' && $user->home_location_id === $barony->id;

        // Check for pending migration request
        $hasPendingRequest = MigrationRequest::where('user_id', $user->id)
            ->pending()
            ->exists();

        // Calculate aggregated stats
        $totalPopulation = $barony->villages->sum('population') + $barony->towns->sum('population');
        $totalWealth = $barony->villages->sum('wealth') + $barony->towns->sum('wealth');

        // Get houses at this location
        $houses = PlayerHouse::where('location_type', 'barony')
            ->where('location_id', $barony->id)
            ->with('player:id,username')
            ->get()
            ->map(fn ($house) => [
                'name' => $house->name,
                'tier_name' => ConstructionConfig::HOUSE_TIERS[$house->tier]['name'] ?? ucfirst($house->tier),
                'owner_username' => $house->player->username,
            ]);

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

        // Get hierarchical activity - barony plus all villages and towns within it
        $recentActivity = LocationActivityLog::where(function ($query) use ($barony, $villageIds, $townIds) {
            // Barony-level events
            $query->where(function ($q) use ($barony) {
                $q->where('location_type', 'barony')
                    ->where('location_id', $barony->id);
            });
            // Village events within this barony
            if (! empty($villageIds)) {
                $query->orWhere(function ($q) use ($villageIds) {
                    $q->where('location_type', 'village')
                        ->whereIn('location_id', $villageIds);
                });
            }
            // Town events within this barony
            if (! empty($townIds)) {
                $query->orWhere(function ($q) use ($townIds) {
                    $q->where('location_type', 'town')
                        ->whereIn('location_id', $townIds);
                });
            }
        })
            ->with('user:id,username')
            ->orderByDesc('created_at')
            ->limit(20)
            ->get()
            ->map(fn ($log) => [
                'id' => $log->id,
                'username' => $log->user?->username,
                'activity_type' => $log->activity_type,
                'description' => $log->description,
                'subtype' => $log->activity_subtype,
                'metadata' => $log->metadata,
                'created_at' => $log->created_at->toISOString(),
                'time_ago' => $log->created_at->diffForHumans(short: true),
            ]);

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
            'visitors' => $visitors,
            'visitor_count' => $barony->visitors->count(),
            'residents' => $residents,
            'resident_count' => $barony->residents->count(),
            'current_user_id' => $user->id,
            'is_baron' => $isBaron,
            'can_manage_routes' => $isBaron || $isKing,
            'is_visitor' => $isVisitor,
            'is_resident' => $isResident,
            ...$migrationService->getMigrationCooldownInfo($user),
            'has_pending_request' => $hasPendingRequest,
            'houses' => $houses,
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
