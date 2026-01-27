<?php

namespace App\Http\Controllers;

use App\Models\Barony;
use App\Models\DiseaseInfection;
use App\Models\DiseaseImmunity;
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
        $user = $request->user();

        return Inertia::render('Travel/Map', [
            'map_data' => $this->getMapData($user),
            'health_data' => $this->getHealthData($user),
        ]);
    }

    /**
     * Get all map data including locations and player position.
     */
    protected function getMapData($user): array
    {
        return [
            'kingdoms' => $this->getKingdoms(),
            'baronies' => $this->getBaronies(),
            'towns' => $this->getTowns(),
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
     * Get all baronies with their coordinates.
     */
    protected function getBaronies(): array
    {
        return Barony::with('kingdom')->get()->map(fn ($barony) => [
            'id' => $barony->id,
            'name' => $barony->name,
            'biome' => $barony->biome,
            'coordinates_x' => $barony->coordinates_x,
            'coordinates_y' => $barony->coordinates_y,
            'kingdom_id' => $barony->kingdom_id,
            'kingdom_name' => $barony->kingdom?->name,
            'is_capital' => $barony->isCapitalBarony(),
        ])->toArray();
    }

    /**
     * Get all towns with their coordinates.
     */
    protected function getTowns(): array
    {
        return Town::with('barony.kingdom')->get()->map(fn ($town) => [
            'id' => $town->id,
            'name' => $town->name,
            'biome' => $town->biome,
            'coordinates_x' => $town->coordinates_x,
            'coordinates_y' => $town->coordinates_y,
            'barony_id' => $town->barony_id,
            'barony_name' => $town->barony?->name,
            'kingdom_name' => $town->barony?->kingdom?->name,
            'population' => $town->population,
        ])->toArray();
    }

    /**
     * Get all villages with their coordinates.
     */
    protected function getVillages(): array
    {
        return Village::with('barony.kingdom')->get()->map(fn ($village) => [
            'id' => $village->id,
            'name' => $village->name,
            'biome' => $village->biome,
            'coordinates_x' => $village->coordinates_x,
            'coordinates_y' => $village->coordinates_y,
            'barony_id' => $village->barony_id,
            'barony_name' => $village->barony?->name,
            'kingdom_name' => $village->barony?->kingdom?->name,
            'population' => $village->population,
            'is_port' => $village->is_port,
            'is_hamlet' => $village->isHamlet(),
            'parent_village_id' => $village->parent_village_id,
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
            'barony' => Barony::find($id),
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
        Barony::all()->each(fn ($b) => $allCoords->push(['x' => $b->coordinates_x, 'y' => $b->coordinates_y]));
        Town::all()->each(fn ($t) => $allCoords->push(['x' => $t->coordinates_x, 'y' => $t->coordinates_y]));
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

    /**
     * Get player's health data including disease infections and immunities.
     */
    protected function getHealthData($user): array
    {
        // Get active disease infections
        $infections = DiseaseInfection::where('user_id', $user->id)
            ->active()
            ->with('diseaseType')
            ->get()
            ->map(fn ($infection) => [
                'id' => $infection->id,
                'status' => $infection->status,
                'days_infected' => $infection->days_infected,
                'days_symptomatic' => $infection->days_symptomatic,
                'is_treated' => $infection->is_treated,
                'disease_type' => [
                    'id' => $infection->diseaseType->id,
                    'name' => $infection->diseaseType->name,
                    'severity' => $infection->diseaseType->severity,
                    'symptoms' => $infection->diseaseType->symptoms ?? [],
                ],
            ])
            ->toArray();

        // Get active immunities
        $immunities = DiseaseImmunity::where('user_id', $user->id)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->with('diseaseType')
            ->get()
            ->map(fn ($immunity) => [
                'id' => $immunity->id,
                'immunity_type' => $immunity->immunity_type,
                'expires_at' => $immunity->expires_at?->toIso8601String(),
                'disease_type' => [
                    'id' => $immunity->diseaseType->id,
                    'name' => $immunity->diseaseType->name,
                ],
            ])
            ->toArray();

        // Build healer path based on current location
        $healerPath = $this->getHealerPath($user);

        return [
            'infections' => $infections,
            'immunities' => $immunities,
            'healer_path' => $healerPath,
        ];
    }

    /**
     * Get the path to the healer based on current location.
     */
    protected function getHealerPath($user): ?string
    {
        $locationType = $user->current_location_type;
        $locationId = $user->current_location_id;

        if (!$locationType || !$locationId) {
            return null;
        }

        return match ($locationType) {
            'village' => "/villages/{$locationId}/healer",
            'barony' => "/baronies/{$locationId}/infirmary",
            'town' => "/towns/{$locationId}/infirmary",
            default => null,
        };
    }
}
