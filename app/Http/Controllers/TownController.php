<?php

namespace App\Http\Controllers;

use App\Models\Town;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TownController extends Controller
{
    /**
     * Display a listing of all towns.
     */
    public function index(): Response
    {
        $towns = Town::with('barony.kingdom')
            ->orderBy('name')
            ->get()
            ->map(fn ($town) => [
                'id' => $town->id,
                'name' => $town->name,
                'description' => $town->description,
                'biome' => $town->biome,
                'population' => $town->population,
                'barony' => $town->barony ? [
                    'id' => $town->barony->id,
                    'name' => $town->barony->name,
                ] : null,
                'kingdom' => $town->barony?->kingdom ? [
                    'id' => $town->barony->kingdom->id,
                    'name' => $town->barony->kingdom->name,
                ] : null,
            ]);

        return Inertia::render('Towns/Index', [
            'towns' => $towns,
        ]);
    }

    /**
     * Display the specified town.
     */
    public function show(Town $town): Response
    {
        $town->load(['barony.kingdom', 'mayor']);

        return Inertia::render('Towns/Show', [
            'town' => [
                'id' => $town->id,
                'name' => $town->name,
                'description' => $town->description,
                'biome' => $town->biome,
                'population' => $town->population,
                'wealth' => $town->wealth,
                'tax_rate' => $town->tax_rate,
                'coordinates' => [
                    'x' => $town->coordinates_x,
                    'y' => $town->coordinates_y,
                ],
                'barony' => $town->barony ? [
                    'id' => $town->barony->id,
                    'name' => $town->barony->name,
                ] : null,
                'kingdom' => $town->barony?->kingdom ? [
                    'id' => $town->barony->kingdom->id,
                    'name' => $town->barony->kingdom->name,
                ] : null,
                'mayor' => $town->mayor ? [
                    'id' => $town->mayor->id,
                    'username' => $town->mayor->username,
                ] : null,
            ],
            'services' => [
                ['name' => 'Bank', 'href' => "/towns/{$town->id}/bank", 'description' => 'Deposit and withdraw gold'],
                ['name' => 'Infirmary', 'href' => "/towns/{$town->id}/infirmary", 'description' => 'Heal your wounds'],
                ['name' => 'Town Hall', 'href' => "/towns/{$town->id}/hall", 'description' => 'Civic affairs and governance'],
            ],
        ]);
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
                'mayor' => $town->mayor ? [
                    'id' => $town->mayor->id,
                    'username' => $town->mayor->username,
                    'primary_title' => $town->mayor->primary_title,
                ] : null,
            ],
            'active_election' => $activeElection ? [
                'id' => $activeElection->id,
                'position' => ucfirst($activeElection->role ?? $activeElection->election_type),
                'status' => $activeElection->status,
                'voting_ends_at' => $activeElection->voting_ends_at?->toDateTimeString(),
                'candidate_count' => $activeElection->candidates()->count(),
            ] : null,
            'recent_elections' => $recentElections,
            'can_start_election' => !$activeElection && $town->mayor_user_id !== $user->id,
            'is_mayor' => $town->mayor_user_id === $user->id,
        ]);
    }
}
