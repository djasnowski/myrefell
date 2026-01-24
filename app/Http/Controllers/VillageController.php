<?php

namespace App\Http\Controllers;

use App\Models\MigrationRequest;
use App\Models\Village;
use App\Services\MigrationService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class VillageController extends Controller
{
    /**
     * Display a listing of all villages.
     */
    public function index(): Response
    {
        $villages = Village::with('castle.kingdom')
            ->orderBy('name')
            ->get()
            ->map(fn ($village) => [
                'id' => $village->id,
                'name' => $village->name,
                'description' => $village->description,
                'biome' => $village->biome,
                'is_town' => $village->is_town,
                'population' => $village->population,
                'castle' => $village->castle ? [
                    'id' => $village->castle->id,
                    'name' => $village->castle->name,
                ] : null,
                'kingdom' => $village->castle?->kingdom ? [
                    'id' => $village->castle->kingdom->id,
                    'name' => $village->castle->kingdom->name,
                ] : null,
                'coordinates' => [
                    'x' => $village->coordinates_x,
                    'y' => $village->coordinates_y,
                ],
            ]);

        return Inertia::render('villages/index', [
            'villages' => $villages,
        ]);
    }

    /**
     * Display the specified village.
     */
    public function show(Request $request, Village $village, MigrationService $migrationService): Response
    {
        $village->load(['castle.kingdom', 'residents']);
        $user = $request->user();

        $isResident = $user->home_village_id === $village->id;
        $hasPendingRequest = MigrationRequest::where('user_id', $user->id)
            ->pending()
            ->exists();

        return Inertia::render('villages/show', [
            'village' => [
                'id' => $village->id,
                'name' => $village->name,
                'description' => $village->description,
                'biome' => $village->biome,
                'is_town' => $village->is_town,
                'population' => $village->population,
                'wealth' => $village->wealth,
                'coordinates' => [
                    'x' => $village->coordinates_x,
                    'y' => $village->coordinates_y,
                ],
                'castle' => $village->castle ? [
                    'id' => $village->castle->id,
                    'name' => $village->castle->name,
                    'biome' => $village->castle->biome,
                ] : null,
                'kingdom' => $village->castle?->kingdom ? [
                    'id' => $village->castle->kingdom->id,
                    'name' => $village->castle->kingdom->name,
                ] : null,
                'residents' => $village->residents->map(fn ($resident) => [
                    'id' => $resident->id,
                    'username' => $resident->username,
                    'combat_level' => $resident->combat_level,
                ]),
                'resident_count' => $village->residents->count(),
            ],
            'is_resident' => $isResident,
            'can_migrate' => $migrationService->canMigrate($user),
            'has_pending_request' => $hasPendingRequest,
        ]);
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
