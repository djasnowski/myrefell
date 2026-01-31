<?php

namespace App\Http\Controllers;

use App\Config\LocationServices;
use App\Models\Barony;
use App\Models\PlayerRole;
use App\Models\Role;
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
    public function show(Request $request, Barony $barony): Response
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

        // Calculate aggregated stats
        $totalPopulation = $barony->villages->sum('population') + $barony->towns->sum('population');
        $totalWealth = $barony->villages->sum('wealth') + $barony->towns->sum('wealth');

        // Get services available at barony level
        $services = LocationServices::getServicesForLocation('barony');

        // Get recent activity for this barony (from all villages/towns in the barony)
        $villageIds = $barony->villages->pluck('id')->toArray();
        $townIds = $barony->towns->pluck('id')->toArray();

        $recentActivity = ActivityLog::query()
            ->where(function ($query) use ($villageIds, $townIds, $barony) {
                $query->where(function ($q) use ($villageIds) {
                    $q->where('location_type', 'village')
                        ->whereIn('location_id', $villageIds);
                })->orWhere(function ($q) use ($townIds) {
                    $q->where('location_type', 'town')
                        ->whereIn('location_id', $townIds);
                })->orWhere(function ($q) use ($barony) {
                    $q->where('location_type', 'barony')
                        ->where('location_id', $barony->id);
                });
            })
            ->with('user:id,username')
            ->latest()
            ->limit(10)
            ->get()
            ->map(fn ($log) => [
                'id' => $log->id,
                'username' => $log->user->username ?? 'Unknown',
                'description' => $log->description,
                'activity_type' => $log->activity_type,
                'subtype' => $log->subtype,
                'metadata' => $log->metadata,
                'created_at' => $log->created_at->toIso8601String(),
                'time_ago' => $log->created_at->diffForHumans(),
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
            'recent_activity' => $recentActivity,
            'current_user_id' => $user->id,
            'is_baron' => $isBaron,
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
