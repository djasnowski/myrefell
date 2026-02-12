<?php

namespace App\Http\Controllers;

use App\Config\ConstructionConfig;
use App\Config\LocationServices;
use App\Models\Disaster;
use App\Models\LocationActivityLog;
use App\Models\MigrationRequest;
use App\Models\PlayerHouse;
use App\Models\PlayerRole;
use App\Models\Role;
use App\Models\Village;
use App\Services\MigrationService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class VillageController extends Controller
{
    /**
     * Redirect to player's current location or home village.
     * No global village directory - information is situational.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        // Redirect to current location if it's a village
        if ($user->current_location_type === 'village' && $user->current_location_id) {
            return redirect()->route('villages.show', $user->current_location_id);
        }

        // Otherwise redirect to home village
        if ($user->home_village_id) {
            return redirect()->route('villages.show', $user->home_village_id);
        }

        // Fallback to travel/map
        return redirect()->route('travel.index');
    }

    /**
     * Display the specified village.
     */
    public function show(Request $request, Village $village, MigrationService $migrationService): Response
    {
        $village->load(['barony.kingdom', 'residents', 'parentVillage']);
        $user = $request->user();

        $isResident = $user->home_village_id === $village->id;
        $hasPendingRequest = MigrationRequest::where('user_id', $user->id)
            ->pending()
            ->exists();

        // Count pending migration requests the user can approve
        $pendingMigrationRequests = $migrationService->getPendingRequestsForApprover($user)->count();

        // Get the village elder (primary ruler) with legitimacy
        $elderRole = Role::where('slug', 'elder')->first();
        $elder = null;
        if ($elderRole) {
            $elderAssignment = PlayerRole::active()
                ->where('role_id', $elderRole->id)
                ->where('location_type', 'village')
                ->where('location_id', $village->id)
                ->with('user')
                ->first();

            if ($elderAssignment && $elderAssignment->user) {
                $elder = [
                    'id' => $elderAssignment->user->id,
                    'username' => $elderAssignment->user->username,
                    'primary_title' => $elderAssignment->user->primary_title,
                    'legitimacy' => $elderAssignment->legitimacy ?? 50,
                ];
            }
        }

        // Get houses at this location
        $houses = PlayerHouse::where('location_type', 'village')
            ->where('location_id', $village->id)
            ->with('player:id,username')
            ->get()
            ->map(fn ($house) => [
                'name' => $house->name,
                'tier_name' => ConstructionConfig::HOUSE_TIERS[$house->tier]['name'] ?? ucfirst($house->tier),
                'owner_username' => $house->player->username,
            ]);

        // Get available services for this village
        $services = LocationServices::getServicesForLocation('village', $village->is_port ?? false);

        // Get recent activity at this location
        $recentActivity = $this->getRecentActivity($village);

        return Inertia::render('villages/show', [
            'village' => [
                'id' => $village->id,
                'name' => $village->name,
                'description' => $village->description,
                'biome' => $village->biome,
                'is_port' => $village->is_port ?? false,
                'population' => $village->population,
                'wealth' => $village->wealth,
                'is_hamlet' => $village->isHamlet(),
                'coordinates' => [
                    'x' => $village->coordinates_x,
                    'y' => $village->coordinates_y,
                ],
                'barony' => $village->barony ? [
                    'id' => $village->barony->id,
                    'name' => $village->barony->name,
                    'biome' => $village->barony->biome,
                ] : null,
                'kingdom' => $village->barony?->kingdom ? [
                    'id' => $village->barony->kingdom->id,
                    'name' => $village->barony->kingdom->name,
                ] : null,
                'parent_village' => $village->parentVillage ? [
                    'id' => $village->parentVillage->id,
                    'name' => $village->parentVillage->name,
                ] : null,
                'residents' => $village->residents->map(fn ($resident) => [
                    'id' => $resident->id,
                    'username' => $resident->username,
                    'combat_level' => $resident->combat_level,
                ]),
                'resident_count' => $village->residents->count(),
                'elder' => $elder,
            ],
            'services' => array_values(array_map(fn ($service, $id) => array_merge($service, ['id' => $id]), $services, array_keys($services))),
            'recent_activity' => $recentActivity,
            'is_resident' => $isResident,
            ...array_merge(
                $migrationService->getMigrationCooldownInfo($user),
                ['can_migrate' => $migrationService->canMigrate($user)
                    && $user->current_location_type === 'village'
                    && $user->current_location_id === $village->id]
            ),
            'has_pending_request' => $hasPendingRequest,
            'current_user_id' => $user->id,
            'disasters' => $this->getActiveDisasters($village),
            'pending_migration_requests' => $pendingMigrationRequests,
            'houses' => $houses,
        ]);
    }

    /**
     * Get recent activity for a village.
     */
    protected function getRecentActivity(Village $village): array
    {
        try {
            return LocationActivityLog::atLocation('village', $village->id)
                ->recent(15)
                ->with('user:id,username')
                ->get()
                ->map(fn ($log) => [
                    'id' => $log->id,
                    'username' => $log->user->username ?? 'Unknown',
                    'description' => $log->description,
                    'activity_type' => $log->activity_type,
                    'subtype' => $log->activity_subtype,
                    'metadata' => $log->metadata,
                    'created_at' => $log->created_at->toIso8601String(),
                    'time_ago' => $log->created_at->diffForHumans(),
                ])
                ->toArray();
        } catch (\Illuminate\Database\QueryException $e) {
            // Table may not exist yet
            return [];
        }
    }

    /**
     * Get active disasters for a location.
     */
    protected function getActiveDisasters(Village $village): array
    {
        try {
            $disasters = Disaster::active()
                ->where('location_type', 'village')
                ->where('location_id', $village->id)
                ->with('disasterType')
                ->get();

            return $disasters->map(fn ($disaster) => [
                'id' => $disaster->id,
                'type' => $disaster->disasterType?->slug ?? 'unknown',
                'name' => $disaster->disasterType?->name ?? 'Unknown Disaster',
                'severity' => $disaster->severity,
                'status' => $disaster->status,
                'started_at' => $disaster->started_at?->diffForHumans(),
                'days_active' => $disaster->started_at ? now()->diffInDays($disaster->started_at) + 1 : 1,
                'buildings_damaged' => $disaster->buildings_damaged,
                'casualties' => $disaster->casualties,
            ])->toArray();
        } catch (\Illuminate\Database\QueryException $e) {
            // Table may not exist yet
            return [];
        }
    }

    /**
     * Display residents of a village.
     */
    public function residents(Village $village): Response
    {
        $residents = $village->residents()
            ->orderBy('username')
            ->get()
            ->map(fn ($resident) => [
                'id' => $resident->id,
                'username' => $resident->username,
                'combat_level' => $resident->combat_level,
                'gender' => $resident->gender,
                'primary_title' => $resident->primary_title,
                'title_tier' => $resident->title_tier,
            ]);

        return Inertia::render('Villages/Residents', [
            'village' => [
                'id' => $village->id,
                'name' => $village->name,
            ],
            'residents' => $residents,
            'count' => $residents->count(),
        ]);
    }
}
