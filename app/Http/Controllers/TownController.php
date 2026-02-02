<?php

namespace App\Http\Controllers;

use App\Config\LocationServices;
use App\Models\LocationActivityLog;
use App\Models\MigrationRequest;
use App\Models\PlayerRole;
use App\Models\Role;
use App\Models\Town;
use App\Services\MigrationService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TownController extends Controller
{
    public function __construct(
        protected MigrationService $migrationService
    ) {}

    /**
     * Display the towns index page.
     */
    public function index(): Response
    {
        return Inertia::render('Towns/Index');
    }

    /**
     * Display the specified town.
     */
    public function show(Request $request, Town $town): Response
    {
        $user = $request->user();
        $town->load(['barony.duchy.kingdom', 'barony.kingdom', 'mayor', 'visitors']);

        // Get mayor with legitimacy - check PlayerRole first, then fall back to town->mayor
        $mayor = null;
        $mayorRole = Role::where('slug', 'mayor')->first();
        $mayorAssignment = null;
        if ($mayorRole) {
            $mayorAssignment = PlayerRole::active()
                ->where('role_id', $mayorRole->id)
                ->where('location_type', 'town')
                ->where('location_id', $town->id)
                ->with('user')
                ->first();
        }

        if ($mayorAssignment?->user) {
            $mayor = [
                'id' => $mayorAssignment->user->id,
                'username' => $mayorAssignment->user->username,
                'primary_title' => $mayorAssignment->user->primary_title,
                'legitimacy' => $mayorAssignment->legitimacy ?? 50,
            ];
        } elseif ($town->mayor) {
            // Fallback to town->mayor_user_id relationship
            $mayor = [
                'id' => $town->mayor->id,
                'username' => $town->mayor->username,
                'primary_title' => $town->mayor->primary_title,
                'legitimacy' => 50,
            ];
        }

        // Get town roles with their holders
        $roles = $this->getTownRoles($town);

        // Get visitors (players currently in this town)
        $visitors = $town->visitors->take(12)->map(fn ($visitor) => [
            'id' => $visitor->id,
            'username' => $visitor->username,
            'combat_level' => $visitor->combat_level ?? 1,
        ]);

        // Check if user is currently in this town
        $isVisitor = $user->current_location_type === 'town' && $user->current_location_id === $town->id;

        // Check if user is a resident (home is this town)
        $isResident = $user->home_location_type === 'town' && $user->home_location_id === $town->id;

        // Check if user can migrate (not on cooldown)
        $canMigrate = $this->migrationService->canMigrate($user);

        // Check for pending migration request
        $hasPendingRequest = MigrationRequest::where('user_id', $user->id)
            ->pending()
            ->exists();

        // Get active disasters
        $disasters = $town->disasters()
            ->whereIn('status', ['active', 'ending'])
            ->get()
            ->map(fn ($disaster) => [
                'id' => $disaster->id,
                'type' => $disaster->type,
                'name' => $disaster->name,
                'severity' => $disaster->severity,
                'status' => $disaster->status,
                'started_at' => $disaster->started_at?->toDateTimeString(),
                'days_active' => $disaster->started_at?->diffInDays(now()) ?? 0,
                'buildings_damaged' => $disaster->buildings_damaged ?? 0,
                'casualties' => $disaster->casualties ?? 0,
            ]);

        // Get available services for this town
        $services = LocationServices::getServicesForLocation('town', $town->is_port ?? false);

        // Get recent activity at this location
        $recentActivity = $this->getRecentActivity($town);

        // Get pending migration requests for mayors
        $isMayor = $mayorAssignment?->user_id === $user->id || $town->mayor_user_id === $user->id;
        $pendingMigrations = [];
        if ($isMayor) {
            $pendingMigrations = MigrationRequest::pending()
                ->where('to_location_type', 'town')
                ->where('to_location_id', $town->id)
                ->whereNull('mayor_approved')
                ->with('user')
                ->get()
                ->map(fn ($request) => [
                    'id' => $request->id,
                    'user' => [
                        'id' => $request->user->id,
                        'username' => $request->user->username,
                    ],
                    'from_location' => $request->getOriginName(),
                    'created_at' => $request->created_at->diffForHumans(),
                ])
                ->toArray();
        }

        return Inertia::render('Towns/Show', [
            'town' => [
                'id' => $town->id,
                'name' => $town->name,
                'description' => $town->description,
                'biome' => $town->biome,
                'is_capital' => $town->is_capital,
                'is_port' => $town->is_port,
                'population' => $town->population,
                'wealth' => $town->wealth,
                'tax_rate' => $town->tax_rate,
                'visitor_count' => $town->visitors->count(),
                'coordinates' => [
                    'x' => $town->coordinates_x,
                    'y' => $town->coordinates_y,
                ],
                'barony' => $town->barony ? [
                    'id' => $town->barony->id,
                    'name' => $town->barony->name,
                    'biome' => $town->barony->biome,
                ] : null,
                'duchy' => $town->barony?->duchy ? [
                    'id' => $town->barony->duchy->id,
                    'name' => $town->barony->duchy->name,
                ] : null,
                'kingdom' => $town->barony?->kingdom ? [
                    'id' => $town->barony->kingdom->id,
                    'name' => $town->barony->kingdom->name,
                ] : null,
                'mayor' => $mayor,
            ],
            'services' => array_values(array_map(fn ($service, $id) => array_merge($service, ['id' => $id]), $services, array_keys($services))),
            'recent_activity' => $recentActivity,
            'roles' => $roles,
            'visitors' => $visitors,
            'is_visitor' => $isVisitor,
            'is_resident' => $isResident,
            'is_mayor' => $isMayor,
            'can_migrate' => $canMigrate,
            'has_pending_request' => $hasPendingRequest,
            'current_user_id' => $user->id,
            'disasters' => $disasters,
            'pending_migrations' => $pendingMigrations,
        ]);
    }

    /**
     * Get recent activity for a town.
     */
    protected function getRecentActivity(Town $town): array
    {
        try {
            return LocationActivityLog::atLocation('town', $town->id)
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
     * Get all town roles with their current holders.
     */
    protected function getTownRoles(Town $town): array
    {
        $roles = Role::where('location_type', 'town')
            ->orderBy('tier', 'desc')
            ->orderBy('salary', 'desc')
            ->get();

        return $roles->map(function ($role) use ($town) {
            $holder = PlayerRole::active()
                ->where('role_id', $role->id)
                ->where('location_type', 'town')
                ->where('location_id', $town->id)
                ->with('user')
                ->first();

            return [
                'id' => $role->id,
                'name' => $role->name,
                'slug' => $role->slug,
                'description' => $role->description,
                'tier' => $role->tier,
                'salary' => $role->salary,
                'is_elected' => $role->is_elected,
                'holder' => $holder?->user ? [
                    'id' => $holder->user->id,
                    'username' => $holder->user->username,
                    'legitimacy' => $holder->legitimacy ?? 50,
                    'appointed_at' => $holder->appointed_at?->diffForHumans(),
                ] : null,
            ];
        })->toArray();
    }

    /**
     * Display the town hall page.
     */
    public function hall(Request $request, Town $town): Response
    {
        $user = $request->user();
        $town->load(['barony.kingdom', 'mayor', 'elections' => function ($query) {
            $query->latest()->limit(5);
        }]);

        // Get mayor from PlayerRole first, fall back to town->mayor
        $mayorRole = Role::where('slug', 'mayor')->first();
        $mayorAssignment = $mayorRole ? PlayerRole::active()
            ->where('role_id', $mayorRole->id)
            ->where('location_type', 'town')
            ->where('location_id', $town->id)
            ->with('user')
            ->first() : null;

        $mayorData = null;
        if ($mayorAssignment?->user) {
            $mayorData = [
                'id' => $mayorAssignment->user->id,
                'username' => $mayorAssignment->user->username,
                'primary_title' => $mayorAssignment->user->primary_title,
            ];
        } elseif ($town->mayor) {
            $mayorData = [
                'id' => $town->mayor->id,
                'username' => $town->mayor->username,
                'primary_title' => $town->mayor->primary_title,
            ];
        }

        $isMayor = $mayorAssignment?->user_id === $user->id || $town->mayor_user_id === $user->id;

        // Get active election if any
        $activeElection = $town->elections()
            ->where('status', 'open')
            ->first();

        // Get recent elections
        $recentElections = $town->elections()
            ->whereIn('status', ['completed', 'cancelled', 'failed'])
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn ($election) => [
                'id' => $election->id,
                'position' => ucfirst($election->role ?? $election->election_type),
                'status' => $election->status,
                'started_at' => $election->created_at->toDateTimeString(),
                'ended_at' => $election->finalized_at?->toDateTimeString(),
            ]);

        return Inertia::render('Towns/Hall', [
            'town' => [
                'id' => $town->id,
                'name' => $town->name,
                'biome' => $town->biome,
                'is_capital' => $town->is_capital,
                'population' => $town->population,
                'wealth' => $town->wealth,
                'tax_rate' => $town->tax_rate,
                'barony' => $town->barony ? [
                    'id' => $town->barony->id,
                    'name' => $town->barony->name,
                ] : null,
                'kingdom' => $town->barony?->kingdom ? [
                    'id' => $town->barony->kingdom->id,
                    'name' => $town->barony->kingdom->name,
                ] : null,
                'mayor' => $mayorData,
            ],
            'active_election' => $activeElection ? [
                'id' => $activeElection->id,
                'position' => ucfirst($activeElection->role ?? $activeElection->election_type),
                'status' => $activeElection->status,
                'voting_ends_at' => $activeElection->voting_ends_at?->toDateTimeString(),
                'candidate_count' => $activeElection->candidates()->count(),
            ] : null,
            'recent_elections' => $recentElections,
            'can_start_election' => ! $activeElection && ! $isMayor,
            'is_mayor' => $isMayor,
        ]);
    }
}
