<?php

namespace App\Http\Controllers;

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
        $barony->load(['kingdom', 'villages', 'towns', 'baron']);
        $user = $request->user();

        // Get the baron's role assignment for legitimacy
        $baron = null;
        if ($barony->baron) {
            $baronRole = Role::where('slug', 'baron')->first();
            $baronAssignment = null;
            if ($baronRole) {
                $baronAssignment = PlayerRole::active()
                    ->where('role_id', $baronRole->id)
                    ->where('location_type', 'barony')
                    ->where('location_id', $barony->id)
                    ->first();
            }

            $baron = [
                'id' => $barony->baron->id,
                'username' => $barony->baron->username,
                'primary_title' => $barony->baron->primary_title,
                'legitimacy' => $baronAssignment?->legitimacy ?? 50,
            ];
        }

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
                    'is_hamlet' => $village->isHamlet(),
                ]),
                'towns' => $barony->towns->map(fn ($town) => [
                    'id' => $town->id,
                    'name' => $town->name,
                    'biome' => $town->biome,
                    'population' => $town->population,
                ]),
                'village_count' => $barony->villages->count(),
                'town_count' => $barony->towns->count(),
                'baron' => $baron,
            ],
            'current_user_id' => $user->id,
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
