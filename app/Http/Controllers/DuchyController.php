<?php

namespace App\Http\Controllers;

use App\Models\Duchy;
use App\Models\PlayerRole;
use App\Models\Role;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DuchyController extends Controller
{
    /**
     * Display a listing of all duchies.
     */
    public function index(): Response
    {
        $duchies = Duchy::with(['kingdom', 'baronies'])
            ->withCount(['baronies'])
            ->orderBy('name')
            ->get()
            ->map(fn ($duchy) => [
                'id' => $duchy->id,
                'name' => $duchy->name,
                'description' => $duchy->description,
                'biome' => $duchy->biome,
                'tax_rate' => $duchy->tax_rate,
                'baronies_count' => $duchy->baronies_count,
                'kingdom' => $duchy->kingdom ? [
                    'id' => $duchy->kingdom->id,
                    'name' => $duchy->kingdom->name,
                ] : null,
                'coordinates' => [
                    'x' => $duchy->coordinates_x,
                    'y' => $duchy->coordinates_y,
                ],
            ]);

        return Inertia::render('Duchies/Index', [
            'duchies' => $duchies,
        ]);
    }

    /**
     * Display the specified duchy.
     */
    public function show(Request $request, Duchy $duchy): Response
    {
        $duchy->load(['kingdom', 'baronies.villages', 'baronies.towns', 'duke']);
        $user = $request->user();

        // Get the duke's role assignment for legitimacy
        $duke = null;
        if ($duchy->duke) {
            $dukeRole = Role::where('slug', 'duke')->first();
            $dukeAssignment = null;
            if ($dukeRole) {
                $dukeAssignment = PlayerRole::active()
                    ->where('role_id', $dukeRole->id)
                    ->where('location_type', 'duchy')
                    ->where('location_id', $duchy->id)
                    ->first();
            }

            $duke = [
                'id' => $duchy->duke->id,
                'username' => $duchy->duke->username,
                'primary_title' => $duchy->duke->primary_title,
                'legitimacy' => $dukeAssignment?->legitimacy ?? 50,
            ];
        }

        // Get duchy roles with their holders
        $duchyRoles = $this->getDuchyRoles($duchy);

        return Inertia::render('Duchies/Show', [
            'duchy' => [
                'id' => $duchy->id,
                'name' => $duchy->name,
                'description' => $duchy->description,
                'biome' => $duchy->biome,
                'tax_rate' => $duchy->tax_rate,
                'coordinates' => [
                    'x' => $duchy->coordinates_x,
                    'y' => $duchy->coordinates_y,
                ],
                'kingdom' => $duchy->kingdom ? [
                    'id' => $duchy->kingdom->id,
                    'name' => $duchy->kingdom->name,
                    'biome' => $duchy->kingdom->biome,
                ] : null,
                'baronies' => $duchy->baronies->map(fn ($barony) => [
                    'id' => $barony->id,
                    'name' => $barony->name,
                    'biome' => $barony->biome,
                    'village_count' => $barony->villages->count(),
                    'town_count' => $barony->towns->count(),
                ]),
                'barony_count' => $duchy->baronies->count(),
                'duke' => $duke,
            ],
            'roles' => $duchyRoles,
            'current_user_id' => $user->id,
            'is_duke' => $duchy->duke_user_id === $user->id,
        ]);
    }

    /**
     * Get all duchy roles with their current holders.
     */
    protected function getDuchyRoles(Duchy $duchy): array
    {
        $roles = Role::where('location_type', 'duchy')
            ->orderBy('tier', 'desc')
            ->orderBy('salary', 'desc')
            ->get();

        return $roles->map(function ($role) use ($duchy) {
            $holder = PlayerRole::active()
                ->where('role_id', $role->id)
                ->where('location_type', 'duchy')
                ->where('location_id', $duchy->id)
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
     * Get baronies under a duchy.
     */
    public function baronies(Duchy $duchy)
    {
        $baronies = $duchy->baronies()
            ->withCount(['villages', 'towns'])
            ->orderBy('name')
            ->get()
            ->map(fn ($barony) => [
                'id' => $barony->id,
                'name' => $barony->name,
                'biome' => $barony->biome,
                'villages_count' => $barony->villages_count,
                'towns_count' => $barony->towns_count,
            ]);

        return response()->json([
            'duchy_id' => $duchy->id,
            'duchy_name' => $duchy->name,
            'baronies' => $baronies,
            'count' => $baronies->count(),
        ]);
    }
}
