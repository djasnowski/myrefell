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
        $kingdoms = Kingdom::with('capitalCastle')
            ->withCount('castles')
            ->orderBy('name')
            ->get()
            ->map(fn ($kingdom) => [
                'id' => $kingdom->id,
                'name' => $kingdom->name,
                'description' => $kingdom->description,
                'biome' => $kingdom->biome,
                'tax_rate' => $kingdom->tax_rate,
                'castles_count' => $kingdom->castles_count,
                'capital' => $kingdom->capitalCastle ? [
                    'id' => $kingdom->capitalCastle->id,
                    'name' => $kingdom->capitalCastle->name,
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
        $kingdom->load(['capitalCastle', 'castles.villages']);

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
                'capital' => $kingdom->capitalCastle ? [
                    'id' => $kingdom->capitalCastle->id,
                    'name' => $kingdom->capitalCastle->name,
                    'biome' => $kingdom->capitalCastle->biome,
                ] : null,
                'castles' => $kingdom->castles->map(fn ($castle) => [
                    'id' => $castle->id,
                    'name' => $castle->name,
                    'biome' => $castle->biome,
                    'is_capital' => $castle->isCapital(),
                    'village_count' => $castle->villages->count(),
                ]),
                'castle_count' => $kingdom->castles->count(),
                'total_villages' => $kingdom->castles->sum(fn ($c) => $c->villages->count()),
            ],
        ]);
    }

    /**
     * Get castles in a kingdom (for AJAX requests).
     */
    public function castles(Kingdom $kingdom)
    {
        $castles = $kingdom->castles()
            ->withCount('villages')
            ->orderBy('name')
            ->get()
            ->map(fn ($castle) => [
                'id' => $castle->id,
                'name' => $castle->name,
                'biome' => $castle->biome,
                'is_capital' => $castle->isCapital(),
                'villages_count' => $castle->villages_count,
            ]);

        return response()->json([
            'kingdom_id' => $kingdom->id,
            'kingdom_name' => $kingdom->name,
            'castles' => $castles,
            'count' => $castles->count(),
        ]);
    }
}
