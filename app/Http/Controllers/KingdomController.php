<?php

namespace App\Http\Controllers;

use App\Config\ConstructionConfig;
use App\Models\Kingdom;
use App\Models\MigrationRequest;
use App\Models\NoConfidenceVote;
use App\Models\PlayerHouse;
use App\Models\PlayerMail;
use App\Models\PlayerRole;
use App\Models\PlayerTitle;
use App\Models\Role;
use App\Models\TitleType;
use App\Models\User;
use App\Services\MigrationService;
use App\Services\TitleService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class KingdomController extends Controller
{
    /**
     * Display a listing of all kingdoms.
     */
    public function index(): Response
    {
        $kingdoms = Kingdom::with('capitalTown')
            ->withCount('baronies')
            ->orderBy('name')
            ->get()
            ->map(fn ($kingdom) => [
                'id' => $kingdom->id,
                'name' => $kingdom->name,
                'description' => $kingdom->description,
                'biome' => $kingdom->biome,
                'tax_rate' => $kingdom->tax_rate,
                'baronies_count' => $kingdom->baronies_count,
                'capital' => $kingdom->capitalTown ? [
                    'id' => $kingdom->capitalTown->id,
                    'name' => $kingdom->capitalTown->name,
                ] : null,
                'coordinates' => [
                    'x' => $kingdom->coordinates_x,
                    'y' => $kingdom->coordinates_y,
                ],
            ]);

        return Inertia::render('kingdoms/index', [
            'kingdoms' => $kingdoms,
        ]);
    }

    /**
     * Display the specified kingdom.
     */
    public function show(Request $request, Kingdom $kingdom, MigrationService $migrationService): Response
    {
        $kingdom->load(['capitalTown', 'baronies.villages', 'baronies.towns', 'baronies.baron', 'king', 'visitors', 'residents']);
        $user = $request->user();

        // Batch-load all active authority role assignments within this kingdom
        $authorityRoleSlugs = ['elder', 'baron', 'mayor', 'king'];
        $authorityRoleIds = Role::whereIn('slug', $authorityRoleSlugs)->pluck('id', 'slug');

        // Collect all location IDs by type for batch query
        $allVillageIds = $kingdom->baronies->flatMap(fn ($b) => $b->villages->pluck('id'))->all();
        $allTownIds = $kingdom->baronies->flatMap(fn ($b) => $b->towns->pluck('id'))->all();
        $allBaronyIds = $kingdom->baronies->pluck('id')->all();

        // Query all active authority assignments at once
        $roleAssignments = PlayerRole::active()
            ->whereIn('role_id', $authorityRoleIds->values())
            ->with('user:id,username')
            ->where(function ($q) use ($allVillageIds, $allTownIds, $allBaronyIds, $kingdom) {
                $q->where(function ($sq) use ($allVillageIds) {
                    $sq->where('location_type', 'village')->whereIn('location_id', $allVillageIds);
                })->orWhere(function ($sq) use ($allTownIds) {
                    $sq->where('location_type', 'town')->whereIn('location_id', $allTownIds);
                })->orWhere(function ($sq) use ($allBaronyIds) {
                    $sq->where('location_type', 'barony')->whereIn('location_id', $allBaronyIds);
                })->orWhere(function ($sq) use ($kingdom) {
                    $sq->where('location_type', 'kingdom')->where('location_id', $kingdom->id);
                });
            })
            ->get();

        // Index assignments by "location_type:location_id:role_slug"
        $roleMap = [];
        $roleSlugById = $authorityRoleIds->flip();
        foreach ($roleAssignments as $assignment) {
            $slug = $roleSlugById[$assignment->role_id] ?? null;
            if ($slug) {
                $key = "{$assignment->location_type}:{$assignment->location_id}:{$slug}";
                $roleMap[$key] = [
                    'username' => $assignment->user?->username,
                    'player_role_id' => $assignment->id,
                ];
            }
        }

        // Get visitors (players currently in this kingdom)
        $visitors = $kingdom->visitors->take(12)->map(fn ($visitor) => [
            'id' => $visitor->id,
            'username' => $visitor->username,
            'combat_level' => $visitor->combat_level ?? 1,
        ]);

        // Check if user is currently in this kingdom
        $isVisitor = $user->current_location_type === 'kingdom' && $user->current_location_id === $kingdom->id;

        // Get residents with home set to this kingdom
        $kingdomResidents = $kingdom->residents->take(12)->map(fn ($resident) => [
            'id' => $resident->id,
            'username' => $resident->username,
            'combat_level' => $resident->combat_level ?? 1,
        ]);

        // Get the king's role assignment for legitimacy
        $king = null;
        if ($kingdom->king) {
            $kingRole = Role::where('slug', 'king')->first();
            $kingAssignment = null;
            if ($kingRole) {
                $kingAssignment = PlayerRole::active()
                    ->where('role_id', $kingRole->id)
                    ->where('location_type', 'kingdom')
                    ->where('location_id', $kingdom->id)
                    ->first();
            }

            $king = [
                'id' => $kingdom->king->id,
                'username' => $kingdom->king->username,
                'primary_title' => $kingdom->king->primary_title,
                'legitimacy' => $kingAssignment?->legitimacy ?? 50,
            ];
        }

        // Aggregate statistics across all baronies
        $totalVillagePopulation = 0;
        $totalTownPopulation = 0;
        $totalVillageWealth = 0;
        $totalTownWealth = 0;
        $totalPorts = 0;

        // Collect all village IDs for player count
        $villageIds = [];

        // Build enhanced barony data with hierarchy
        $baroniesData = $kingdom->baronies->map(function ($barony) use (&$totalVillagePopulation, &$totalTownPopulation, &$totalVillageWealth, &$totalTownWealth, &$totalPorts, &$villageIds, $roleMap) {
            $baronyVillagePopulation = $barony->villages->sum('population');
            $baronyTownPopulation = $barony->towns->sum('population');
            $baronyVillageWealth = $barony->villages->sum('wealth');
            $baronyTownWealth = $barony->towns->sum('wealth');
            $baronyPorts = $barony->villages->where('is_port', true)->count() + $barony->towns->where('is_port', true)->count();

            $totalVillagePopulation += $baronyVillagePopulation;
            $totalTownPopulation += $baronyTownPopulation;
            $totalVillageWealth += $baronyVillageWealth;
            $totalTownWealth += $baronyTownWealth;
            $totalPorts += $baronyPorts;

            // Collect village IDs
            foreach ($barony->villages as $village) {
                $villageIds[] = $village->id;
            }

            return [
                'id' => $barony->id,
                'name' => $barony->name,
                'biome' => $barony->biome,
                'is_capital' => $barony->isCapitalBarony(),
                'village_count' => $barony->villages->count(),
                'town_count' => $barony->towns->count(),
                'population' => $baronyVillagePopulation + $baronyTownPopulation,
                'wealth' => $baronyVillageWealth + $baronyTownWealth,
                'baron' => $barony->baron ? [
                    'id' => $barony->baron->id,
                    'username' => $barony->baron->username,
                ] : null,
                'baron_name' => $roleMap["barony:{$barony->id}:baron"]['username'] ?? null,
                'baron_player_role_id' => $roleMap["barony:{$barony->id}:baron"]['player_role_id'] ?? null,
                // Hierarchy data - settlements within the barony
                'settlements' => [
                    ...$barony->towns->map(fn ($town) => [
                        'id' => $town->id,
                        'name' => $town->name,
                        'type' => 'town',
                        'is_capital' => $town->is_capital,
                        'is_port' => $town->is_port,
                        'population' => $town->population,
                        'ruler' => $roleMap["town:{$town->id}:mayor"]['username'] ?? null,
                        'ruler_player_role_id' => $roleMap["town:{$town->id}:mayor"]['player_role_id'] ?? null,
                        'ruler_title' => 'Mayor',
                    ]),
                    ...$barony->villages->map(fn ($village) => [
                        'id' => $village->id,
                        'name' => $village->name,
                        'type' => $village->isHamlet() ? 'hamlet' : 'village',
                        'is_port' => $village->is_port,
                        'population' => $village->population,
                        'ruler' => $roleMap["village:{$village->id}:elder"]['username'] ?? null,
                        'ruler_player_role_id' => $roleMap["village:{$village->id}:elder"]['player_role_id'] ?? null,
                        'ruler_title' => 'Elder',
                    ]),
                ],
            ];
        });

        // Count players living in this kingdom
        $playerCount = \App\Models\User::whereIn('home_village_id', $villageIds)->count();

        // Get houses at this location
        $houses = PlayerHouse::where('location_type', 'kingdom')
            ->where('location_id', $kingdom->id)
            ->with('player:id,username')
            ->get()
            ->map(fn ($house) => [
                'name' => $house->name,
                'tier_name' => ConstructionConfig::HOUSE_TIERS[$house->tier]['name'] ?? ucfirst($house->tier),
                'owner_username' => $house->player->username,
            ]);

        // Check if user is a resident of this kingdom (settled directly in kingdom)
        $isResident = $user->home_location_type === 'kingdom' && $user->home_location_id === $kingdom->id;

        // Check for pending migration request
        $hasPendingRequest = MigrationRequest::where('user_id', $user->id)
            ->pending()
            ->exists();

        // Get active no-confidence vote
        $activeNoConfidenceVote = NoConfidenceVote::where('domain_type', 'kingdom')
            ->where('domain_id', $kingdom->id)
            ->whereIn('status', [NoConfidenceVote::STATUS_PENDING, NoConfidenceVote::STATUS_OPEN])
            ->with('targetPlayer:id,username')
            ->first();

        return Inertia::render('kingdoms/show', [
            'kingdom' => [
                'id' => $kingdom->id,
                'name' => $kingdom->name,
                'description' => $kingdom->description,
                'biome' => $kingdom->biome,
                'tax_rate' => $kingdom->tax_rate,
                'coordinates' => [
                    'x' => $kingdom->coordinates_x,
                    'y' => $kingdom->coordinates_y,
                ],
                'capital' => $kingdom->capitalTown ? [
                    'id' => $kingdom->capitalTown->id,
                    'name' => $kingdom->capitalTown->name,
                    'biome' => $kingdom->capitalTown->biome,
                ] : null,
                'baronies' => $baroniesData,
                'barony_count' => $kingdom->baronies->count(),
                'total_villages' => $kingdom->baronies->sum(fn ($b) => $b->villages->count()),
                'total_towns' => $kingdom->baronies->sum(fn ($b) => $b->towns->count()),
                'total_population' => $totalVillagePopulation + $totalTownPopulation,
                'total_wealth' => $totalVillageWealth + $totalTownWealth,
                'total_ports' => $totalPorts,
                'player_count' => $playerCount,
                'king' => $king,
            ],
            'visitors' => $visitors,
            'visitor_count' => $kingdom->visitors->count(),
            'residents_list' => $kingdomResidents,
            'resident_count' => $kingdom->residents->count(),
            'current_user_id' => $user->id,
            'is_visitor' => $isVisitor,
            'is_resident' => $isResident,
            ...$migrationService->getMigrationCooldownInfo($user),
            'has_pending_request' => $hasPendingRequest,
            'houses' => $houses,
            'is_king' => $kingdom->king_user_id === $user->id,
            'active_no_confidence_vote' => $activeNoConfidenceVote ? [
                'id' => $activeNoConfidenceVote->id,
                'target_role' => $activeNoConfidenceVote->target_role,
                'target_player' => [
                    'id' => $activeNoConfidenceVote->targetPlayer->id,
                    'username' => $activeNoConfidenceVote->targetPlayer->username,
                ],
                'status' => $activeNoConfidenceVote->status,
                'voting_ends_at' => $activeNoConfidenceVote->voting_ends_at?->toIso8601String(),
                'votes_for' => $activeNoConfidenceVote->votes_for,
                'votes_against' => $activeNoConfidenceVote->votes_against,
                'quorum_required' => $activeNoConfidenceVote->quorum_required,
            ] : null,
        ]);
    }

    /**
     * Display the royal management page (King only).
     */
    public function management(Request $request, Kingdom $kingdom, TitleService $titleService): Response
    {
        $user = $request->user();

        if ($kingdom->king_user_id !== $user->id) {
            abort(403, 'Only the King may access Royal Management.');
        }

        $kingdom->load(['baronies.villages', 'baronies.towns']);

        // Collect all location IDs
        $allVillageIds = $kingdom->baronies->flatMap(fn ($b) => $b->villages->pluck('id'))->all();
        $allTownIds = $kingdom->baronies->flatMap(fn ($b) => $b->towns->pluck('id'))->all();
        $allBaronyIds = $kingdom->baronies->pluck('id')->all();

        // Authority role map for hierarchy display
        $authorityRoleSlugs = ['elder', 'baron', 'mayor', 'king'];
        $authorityRoleIds = Role::whereIn('slug', $authorityRoleSlugs)->pluck('id', 'slug');

        $roleAssignments = PlayerRole::active()
            ->whereIn('role_id', $authorityRoleIds->values())
            ->with('user:id,username,last_active_at')
            ->where(function ($q) use ($allVillageIds, $allTownIds, $allBaronyIds, $kingdom) {
                $q->where(function ($sq) use ($allVillageIds) {
                    $sq->where('location_type', 'village')->whereIn('location_id', $allVillageIds);
                })->orWhere(function ($sq) use ($allTownIds) {
                    $sq->where('location_type', 'town')->whereIn('location_id', $allTownIds);
                })->orWhere(function ($sq) use ($allBaronyIds) {
                    $sq->where('location_type', 'barony')->whereIn('location_id', $allBaronyIds);
                })->orWhere(function ($sq) use ($kingdom) {
                    $sq->where('location_type', 'kingdom')->where('location_id', $kingdom->id);
                });
            })
            ->get();

        $roleSlugById = $authorityRoleIds->flip();
        $roleMap = [];
        foreach ($roleAssignments as $assignment) {
            $slug = $roleSlugById[$assignment->role_id] ?? null;
            if ($slug) {
                $key = "{$assignment->location_type}:{$assignment->location_id}:{$slug}";
                $roleMap[$key] = [
                    'username' => $assignment->user?->username,
                    'last_active_at' => $assignment->user?->last_active_at?->toIso8601String(),
                ];
            }
        }

        // Build barony hierarchy data
        $baroniesData = $kingdom->baronies->map(fn ($barony) => [
            'id' => $barony->id,
            'name' => $barony->name,
            'is_capital' => $barony->isCapitalBarony(),
            'baron_name' => $roleMap["barony:{$barony->id}:baron"]['username'] ?? null,
            'baron_last_active' => $roleMap["barony:{$barony->id}:baron"]['last_active_at'] ?? null,
            'settlements' => [
                ...$barony->towns->map(fn ($town) => [
                    'id' => $town->id,
                    'name' => $town->name,
                    'type' => 'town',
                    'is_capital' => $town->is_capital,
                    'is_port' => $town->is_port,
                    'population' => $town->population,
                    'ruler' => $roleMap["town:{$town->id}:mayor"]['username'] ?? null,
                    'ruler_last_active' => $roleMap["town:{$town->id}:mayor"]['last_active_at'] ?? null,
                    'ruler_title' => 'Mayor',
                ]),
                ...$barony->villages->map(fn ($village) => [
                    'id' => $village->id,
                    'name' => $village->name,
                    'type' => $village->isHamlet() ? 'hamlet' : 'village',
                    'is_port' => $village->is_port,
                    'population' => $village->population,
                    'ruler' => $roleMap["village:{$village->id}:elder"]['username'] ?? null,
                    'ruler_last_active' => $roleMap["village:{$village->id}:elder"]['last_active_at'] ?? null,
                    'ruler_title' => 'Elder',
                ]),
            ],
        ]);

        // Build location name lookup
        $locationNames = [];
        $locationNames["kingdom:{$kingdom->id}"] = $kingdom->name;
        foreach ($kingdom->baronies as $barony) {
            $locationNames["barony:{$barony->id}"] = $barony->name;
            foreach ($barony->towns as $town) {
                $locationNames["town:{$town->id}"] = $town->name;
            }
            foreach ($barony->villages as $village) {
                $locationNames["village:{$village->id}"] = $village->name;
            }
        }

        // Query ALL active role assignments in this kingdom
        $allRoleAssignments = PlayerRole::active()
            ->with(['user:id,username,last_active_at', 'role:id,name,slug,tier'])
            ->where(function ($q) use ($allVillageIds, $allTownIds, $allBaronyIds, $kingdom) {
                $q->where(function ($sq) use ($allVillageIds) {
                    $sq->where('location_type', 'village')->whereIn('location_id', $allVillageIds);
                })->orWhere(function ($sq) use ($allTownIds) {
                    $sq->where('location_type', 'town')->whereIn('location_id', $allTownIds);
                })->orWhere(function ($sq) use ($allBaronyIds) {
                    $sq->where('location_type', 'barony')->whereIn('location_id', $allBaronyIds);
                })->orWhere(function ($sq) use ($kingdom) {
                    $sq->where('location_type', 'kingdom')->where('location_id', $kingdom->id);
                });
            })
            ->get();

        $roleHolders = $allRoleAssignments->map(fn ($assignment) => [
            'player_role_id' => $assignment->id,
            'username' => $assignment->user?->username,
            'user_id' => $assignment->user_id,
            'role_name' => $assignment->role?->name,
            'role_slug' => $assignment->role?->slug,
            'role_tier' => $assignment->role?->tier ?? 0,
            'location_type' => $assignment->location_type,
            'location_id' => $assignment->location_id,
            'location_name' => $locationNames["{$assignment->location_type}:{$assignment->location_id}"] ?? 'Unknown',
            'last_active_at' => $assignment->user?->last_active_at?->toIso8601String(),
        ])->values()->all();

        // Title management data
        $grantableTitles = $titleService->getGrantableTitles($user)
            ->map(fn ($titleType) => [
                'id' => $titleType->id,
                'name' => $titleType->name,
                'slug' => $titleType->slug,
                'tier' => $titleType->tier,
                'category' => $titleType->category,
                'description' => $titleType->description,
                'style_of_address' => $titleType->style_of_address,
                'requires_ceremony' => $titleType->requires_ceremony,
                'domain_type' => $titleType->domain_type,
            ])->values()->all();

        // Subjects: people settled anywhere in this kingdom (village, town, barony, or kingdom itself)
        $settledScope = function ($q) use ($allVillageIds, $allTownIds, $allBaronyIds, $kingdom) {
            $q->whereIn('home_village_id', $allVillageIds)
                ->orWhere(function ($sq) use ($allVillageIds) {
                    $sq->where('home_location_type', 'village')
                        ->whereIn('home_location_id', $allVillageIds);
                })
                ->orWhere(function ($sq) use ($allTownIds) {
                    $sq->where('home_location_type', 'town')
                        ->whereIn('home_location_id', $allTownIds);
                })
                ->orWhere(function ($sq) use ($allBaronyIds) {
                    $sq->where('home_location_type', 'barony')
                        ->whereIn('home_location_id', $allBaronyIds);
                })
                ->orWhere(function ($sq) use ($kingdom) {
                    $sq->where('home_location_type', 'kingdom')
                        ->where('home_location_id', $kingdom->id);
                });
        };

        $kingdomSubjects = User::where($settledScope)
            ->whereNull('banned_at')
            ->select('id', 'username', 'primary_title', 'title_tier', 'last_active_at')
            ->orderBy('username')
            ->limit(100)
            ->get()
            ->map(fn ($subject) => [
                'id' => $subject->id,
                'username' => $subject->username,
                'primary_title' => $subject->primary_title,
                'title_tier' => $subject->title_tier,
            ])->values()->all();

        // Build a slug→TitleType lookup for player titles missing title_type_id
        $titleTypeLookup = TitleType::active()->get()->keyBy('slug');

        $titledPlayers = PlayerTitle::where('is_active', true)
            ->whereNull('revoked_at')
            ->whereHas('user', $settledScope)
            ->with(['user:id,username,last_active_at', 'titleType:id,name,slug,tier,category,style_of_address', 'grantedBy:id,username'])
            ->orderByDesc('tier')
            ->get()
            ->map(function ($pt) use ($titleTypeLookup) {
                $tt = $pt->titleType ?? $titleTypeLookup->get($pt->title);

                return [
                    'id' => $pt->id,
                    'user_id' => $pt->user_id,
                    'username' => $pt->user?->username,
                    'title_name' => $tt?->name ?? ucfirst($pt->title),
                    'title_tier' => $pt->tier,
                    'category' => $tt?->category,
                    'style_of_address' => $tt?->style_of_address,
                    'granted_by' => $pt->grantedBy?->username,
                    'granted_at' => $pt->granted_at?->format('M j, Y'),
                    'acquisition_method' => $pt->acquisition_method,
                    'last_active_at' => $pt->user?->last_active_at?->toIso8601String(),
                ];
            })->values()->all();

        $baronRoleId = Role::where('slug', 'baron')->value('id');

        return Inertia::render('kingdoms/management', [
            'kingdom' => [
                'id' => $kingdom->id,
                'name' => $kingdom->name,
                'baronies' => $baroniesData,
            ],
            'role_holders' => $roleHolders,
            'mail_cost' => PlayerMail::MAIL_COST,
            'current_user_id' => $user->id,
            'grantable_titles' => $grantableTitles,
            'kingdom_subjects' => $kingdomSubjects,
            'titled_players' => $titledPlayers,
            'baron_role_id' => $baronRoleId,
        ]);
    }

    /**
     * Get baronies in a kingdom (for AJAX requests).
     */
    public function baronies(Kingdom $kingdom)
    {
        $baronies = $kingdom->baronies()
            ->withCount(['villages', 'towns'])
            ->orderBy('name')
            ->get()
            ->map(fn ($barony) => [
                'id' => $barony->id,
                'name' => $barony->name,
                'biome' => $barony->biome,
                'is_capital' => $barony->isCapitalBarony(),
                'villages_count' => $barony->villages_count,
                'towns_count' => $barony->towns_count,
            ]);

        return response()->json([
            'kingdom_id' => $kingdom->id,
            'kingdom_name' => $kingdom->name,
            'baronies' => $baronies,
            'count' => $baronies->count(),
        ]);
    }
}
