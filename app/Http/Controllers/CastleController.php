<?php

namespace App\Http\Controllers;

use App\Models\Castle;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CastleController extends Controller
{
    /**
     * Display a listing of all castles.
     */
    public function index(): Response
    {
        $castles = Castle::with('kingdom')
            ->withCount('villages')
            ->orderBy('name')
            ->get()
            ->map(fn ($castle) => [
                'id' => $castle->id,
                'name' => $castle->name,
                'description' => $castle->description,
                'biome' => $castle->biome,
                'tax_rate' => $castle->tax_rate,
                'villages_count' => $castle->villages_count,
                'kingdom' => $castle->kingdom ? [
                    'id' => $castle->kingdom->id,
                    'name' => $castle->kingdom->name,
                ] : null,
                'is_capital' => $castle->isCapital(),
                'coordinates' => [
                    'x' => $castle->coordinates_x,
                    'y' => $castle->coordinates_y,
                ],
            ]);

        return Inertia::render('castles/index', [
            'castles' => $castles,
        ]);
    }

    /**
     * Display the specified castle.
     */
    public function show(Castle $castle): Response
    {
        $castle->load(['kingdom', 'villages']);

        return Inertia::render('castles/show', [
            'castle' => [
                'id' => $castle->id,
                'name' => $castle->name,
                'description' => $castle->description,
                'biome' => $castle->biome,
                'tax_rate' => $castle->tax_rate,
                'is_capital' => $castle->isCapital(),
                'coordinates' => [
                    'x' => $castle->coordinates_x,
                    'y' => $castle->coordinates_y,
                ],
                'kingdom' => $castle->kingdom ? [
                    'id' => $castle->kingdom->id,
                    'name' => $castle->kingdom->name,
                    'biome' => $castle->kingdom->biome,
                ] : null,
                'villages' => $castle->villages->map(fn ($village) => [
                    'id' => $village->id,
                    'name' => $village->name,
                    'biome' => $village->biome,
                    'is_town' => $village->is_town,
                    'population' => $village->population,
                ]),
                'village_count' => $castle->villages->count(),
            ],
        ]);
    }

    /**
     * Get villages under a castle (for AJAX requests).
     */
    public function villages(Castle $castle)
    {
        $villages = $castle->villages()
            ->orderBy('name')
            ->get()
            ->map(fn ($village) => [
                'id' => $village->id,
                'name' => $village->name,
                'biome' => $village->biome,
                'is_town' => $village->is_town,
                'population' => $village->population,
            ]);

        return response()->json([
            'castle_id' => $castle->id,
            'castle_name' => $castle->name,
            'villages' => $villages,
            'count' => $villages->count(),
        ]);
    }
}
