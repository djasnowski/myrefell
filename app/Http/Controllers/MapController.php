<?php

namespace App\Http\Controllers;

use App\Models\Castle;
use App\Models\Kingdom;
use App\Models\Town;
use App\Models\Village;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MapController extends Controller
{
    /**
     * Display the world map as the dashboard.
     */
    public function index(Request $request): Response
    {
        return Inertia::render('dashboard', [
            'map_data' => $this->getMapData($request->user()),
        ]);
    }

    /**
     * Get all map data including locations and player position.
     */
    protected function getMapData($user): array
    {
        return [
            'kingdoms' => $this->getKingdoms(),
            'towns' => $this->getTowns(),
            'castles' => $this->getCastles(),
            'villages' => $this->getVillages(),
            'player' => $this->getPlayerLocation($user),
            'bounds' => $this->calculateBounds(),
        ];
    }

    /**
     * Get all kingdoms with their coordinates.
     */
    protected function getKingdoms(): array
    {
        return Kingdom::all()->map(fn ($kingdom) => [
            'id' => $kingdom->id,
            'name' => $kingdom->name,
            'biome' => $kingdom->biome,
            'coordinates_x' => $kingdom->coordinates_x,
            'coordinates_y' => $kingdom->coordinates_y,
            'description' => $kingdom->description,
        ])->toArray();
    }

    /**
     * Get all towns with their coordinates.
     */
    protected function getTowns(): array
    {
        return Town::with('kingdom')->get()->map(fn ($town) => [
            'id' => $town->id,
            'name' => $town->name,
            'biome' => $town->biome,
            'coordinates_x' => $town->coordinates_x,
            'coordinates_y' => $town->coordinates_y,
            'kingdom_id' => $town->kingdom_id,
            'kingdom_name' => $town->kingdom?->name,
            'is_capital' => $town->is_capital,
            'population' => $town->population,
        ])->toArray();
    }

    /**
     * Get all castles with their coordinates.
     */
    protected function getCastles(): array
    {
        return Castle::with('town.kingdom')->get()->map(fn ($castle) => [
            'id' => $castle->id,
            'name' => $castle->name,
            'biome' => $castle->biome,
            'coordinates_x' => $castle->coordinates_x,
            'coordinates_y' => $castle->coordinates_y,
            'kingdom_id' => $castle->kingdom_id,
            'kingdom_name' => $castle->town?->kingdom?->name,
            'town_id' => $castle->town_id,
            'town_name' => $castle->town?->name,
        ])->toArray();
    }

    /**
     * Get all villages with their coordinates.
     */
    protected function getVillages(): array
    {
        return Village::with('castle.town.kingdom')->get()->map(fn ($village) => [
            'id' => $village->id,
            'name' => $village->name,
            'biome' => $village->biome,
            'coordinates_x' => $village->coordinates_x,
            'coordinates_y' => $village->coordinates_y,
            'castle_id' => $village->castle_id,
            'castle_name' => $village->castle?->name,
            'kingdom_name' => $village->castle?->town?->kingdom?->name,
            'population' => $village->population,
            'is_port' => $village->is_port,
        ])->toArray();
    }

    /**
     * Get player's current location for the map.
     */
    protected function getPlayerLocation($user): array
    {
        $homeVillage = $user->homeVillage;

        // Default to home village if no current location
        $locationType = $user->current_location_type ?? 'village';
        $locationId = $user->current_location_id ?? $homeVillage?->id;

        $coordinates = $this->getLocationCoordinates($locationType, $locationId);

        return [
            'location_type' => $locationType,
            'location_id' => $locationId,
            'coordinates_x' => $coordinates['x'],
            'coordinates_y' => $coordinates['y'],
            'home_village_id' => $homeVillage?->id,
            'home_village_x' => $homeVillage?->coordinates_x ?? 0,
            'home_village_y' => $homeVillage?->coordinates_y ?? 0,
            'is_traveling' => $user->is_traveling,
        ];
    }

    /**
     * Get coordinates for a location.
     */
    protected function getLocationCoordinates(string $type, ?int $id): array
    {
        if ($type === 'wilderness' || ! $id) {
            return ['x' => 0, 'y' => 0];
        }

        $location = match ($type) {
            'village' => Village::find($id),
            'castle' => Castle::find($id),
            'town' => Town::find($id),
            'kingdom' => Kingdom::find($id),
            default => null,
        };

        return [
            'x' => $location?->coordinates_x ?? 0,
            'y' => $location?->coordinates_y ?? 0,
        ];
    }

    /**
     * Calculate the bounds of the map based on all locations.
     */
    protected function calculateBounds(): array
    {
        $allCoords = collect();

        // Collect all coordinates
        Kingdom::all()->each(fn ($k) => $allCoords->push(['x' => $k->coordinates_x, 'y' => $k->coordinates_y]));
        Town::all()->each(fn ($t) => $allCoords->push(['x' => $t->coordinates_x, 'y' => $t->coordinates_y]));
        Castle::all()->each(fn ($c) => $allCoords->push(['x' => $c->coordinates_x, 'y' => $c->coordinates_y]));
        Village::all()->each(fn ($v) => $allCoords->push(['x' => $v->coordinates_x, 'y' => $v->coordinates_y]));

        if ($allCoords->isEmpty()) {
            return [
                'min_x' => -500,
                'max_x' => 500,
                'min_y' => -500,
                'max_y' => 500,
            ];
        }

        $minX = $allCoords->min('x') ?? -500;
        $maxX = $allCoords->max('x') ?? 500;
        $minY = $allCoords->min('y') ?? -500;
        $maxY = $allCoords->max('y') ?? 500;

        // Add padding
        $padding = 50;

        return [
            'min_x' => $minX - $padding,
            'max_x' => $maxX + $padding,
            'min_y' => $minY - $padding,
            'max_y' => $maxY + $padding,
        ];
    }
}
