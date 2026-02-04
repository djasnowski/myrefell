<?php

namespace App\Http\Controllers;

use App\Models\Kingdom;
use App\Models\MigrationRequest;
use App\Models\PlayerRole;
use App\Models\Role;
use App\Services\MigrationService;
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
        $kingdom->load(['capitalTown', 'baronies.villages', 'baronies.towns', 'baronies.baron', 'king']);
        $user = $request->user();

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
        $baroniesData = $kingdom->baronies->map(function ($barony) use (&$totalVillagePopulation, &$totalTownPopulation, &$totalVillageWealth, &$totalTownWealth, &$totalPorts, &$villageIds) {
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
                // Hierarchy data - settlements within the barony
                'settlements' => [
                    ...$barony->towns->map(fn ($town) => [
                        'id' => $town->id,
                        'name' => $town->name,
                        'type' => 'town',
                        'is_capital' => $town->is_capital,
                        'is_port' => $town->is_port,
                        'population' => $town->population,
                    ]),
                    ...$barony->villages->map(fn ($village) => [
                        'id' => $village->id,
                        'name' => $village->name,
                        'type' => $village->isHamlet() ? 'hamlet' : 'village',
                        'is_port' => $village->is_port,
                        'population' => $village->population,
                    ]),
                ],
            ];
        });

        // Count players living in this kingdom
        $playerCount = \App\Models\User::whereIn('home_village_id', $villageIds)->count();

        // Check if user is a resident of this kingdom (settled directly in kingdom)
        $isResident = $user->home_location_type === 'kingdom' && $user->home_location_id === $kingdom->id;

        // Check for pending migration request
        $hasPendingRequest = MigrationRequest::where('user_id', $user->id)
            ->pending()
            ->exists();

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
            'current_user_id' => $user->id,
            'is_resident' => $isResident,
            ...$migrationService->getMigrationCooldownInfo($user),
            'has_pending_request' => $hasPendingRequest,
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

    /**
     * Display settlements available for settling in this kingdom.
     */
    public function settle(Request $request, Kingdom $kingdom, MigrationService $migrationService): Response
    {
        $user = $request->user();
        $kingdom->load(['baronies.villages', 'baronies.towns']);

        // Build list of all settlements
        $settlements = [];
        foreach ($kingdom->baronies as $barony) {
            // Add towns (towns don't have residents - players settle in villages)
            foreach ($barony->towns as $town) {
                $settlements[] = [
                    'id' => $town->id,
                    'name' => $town->name,
                    'type' => 'town',
                    'barony_name' => $barony->name,
                    'biome' => $town->biome ?? $barony->biome,
                    'population' => $town->population,
                    'resident_count' => 0,
                    'is_port' => $town->is_port,
                    'is_home' => false,
                ];
            }

            // Add villages
            foreach ($barony->villages as $village) {
                $settlements[] = [
                    'id' => $village->id,
                    'name' => $village->name,
                    'type' => $village->isHamlet() ? 'hamlet' : 'village',
                    'barony_name' => $barony->name,
                    'biome' => $village->biome ?? $barony->biome,
                    'population' => $village->population,
                    'resident_count' => $village->residents()->count(),
                    'is_port' => $village->is_port,
                    'is_home' => $user->home_village_id === $village->id,
                ];
            }
        }

        // Sort by type (towns first) then name
        usort($settlements, function ($a, $b) {
            $typeOrder = ['town' => 0, 'village' => 1, 'hamlet' => 2];
            $typeCompare = ($typeOrder[$a['type']] ?? 99) <=> ($typeOrder[$b['type']] ?? 99);
            if ($typeCompare !== 0) {
                return $typeCompare;
            }

            return strcmp($a['name'], $b['name']);
        });

        // Check for pending migration request
        $hasPendingRequest = MigrationRequest::where('user_id', $user->id)
            ->pending()
            ->exists();

        return Inertia::render('kingdoms/settle', [
            'kingdom' => [
                'id' => $kingdom->id,
                'name' => $kingdom->name,
                'biome' => $kingdom->biome,
            ],
            'settlements' => $settlements,
            ...$migrationService->getMigrationCooldownInfo($user),
            'has_pending_request' => $hasPendingRequest,
            'current_home_village_id' => $user->home_village_id,
        ]);
    }
}
