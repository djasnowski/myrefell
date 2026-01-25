<?php

namespace App\Http\Controllers;

use App\Models\Kingdom;
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
    public function show(Kingdom $kingdom): Response
    {
        $kingdom->load(['capitalTown', 'baronies.villages', 'baronies.towns']);

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
                'baronies' => $kingdom->baronies->map(fn ($barony) => [
                    'id' => $barony->id,
                    'name' => $barony->name,
                    'biome' => $barony->biome,
                    'is_capital' => $barony->isCapitalBarony(),
                    'village_count' => $barony->villages->count(),
                    'town_count' => $barony->towns->count(),
                ]),
                'barony_count' => $kingdom->baronies->count(),
                'total_villages' => $kingdom->baronies->sum(fn ($b) => $b->villages->count()),
                'total_towns' => $kingdom->baronies->sum(fn ($b) => $b->towns->count()),
            ],
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
