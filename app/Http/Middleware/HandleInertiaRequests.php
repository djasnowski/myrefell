<?php

namespace App\Http\Middleware;

use App\Services\EnergyService;
use App\Services\TravelService;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    public function __construct(
        protected EnergyService $energyService,
        protected TravelService $travelService
    ) {}

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $player = $request->user();

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $player,
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'sidebar' => $player ? $this->getSidebarData($player) : null,
        ];
    }

    /**
     * Get sidebar data for the player.
     */
    protected function getSidebarData($player): array
    {
        $player->load(['skills', 'homeVillage.castle.town.kingdom']);

        return [
            'player' => [
                'id' => $player->id,
                'username' => $player->username,
                'gender' => $player->gender,
                'hp' => $player->hp,
                'max_hp' => $player->max_hp,
                'energy' => $player->energy,
                'max_energy' => $player->max_energy,
                'gold' => $player->gold,
                'combat_level' => $player->combat_level,
                'primary_title' => $player->primary_title,
                'title_tier' => $player->title_tier,
            ],
            'energy_info' => $this->energyService->getRegenInfo($player),
            'skills' => $player->skills->map(fn ($skill) => [
                'name' => $skill->skill_name,
                'level' => $skill->level,
                'xp' => $skill->xp,
                'xp_to_next' => $skill->xpToNextLevel(),
                'xp_progress' => $skill->getXpProgress(),
            ]),
            'location' => $this->getLocationData($player),
            'home_village' => $player->homeVillage ? [
                'id' => $player->homeVillage->id,
                'name' => $player->homeVillage->name,
                'resident_count' => $player->homeVillage->residents()->count(),
                'castle' => $player->homeVillage->castle ? [
                    'id' => $player->homeVillage->castle->id,
                    'name' => $player->homeVillage->castle->name,
                ] : null,
                'town' => $player->homeVillage->castle?->town ? [
                    'id' => $player->homeVillage->castle->town->id,
                    'name' => $player->homeVillage->castle->town->name,
                ] : null,
                'kingdom' => $player->homeVillage->castle?->town?->kingdom ? [
                    'id' => $player->homeVillage->castle->town->kingdom->id,
                    'name' => $player->homeVillage->castle->town->kingdom->name,
                ] : null,
            ] : null,
            'travel' => $this->travelService->getTravelStatus($player),
            'nearby_destinations' => $this->travelService->getAvailableDestinations($player),
        ];
    }

    /**
     * Get current location data.
     */
    protected function getLocationData($player): ?array
    {
        if (! $player->current_location_type || ! $player->current_location_id) {
            // Default to home village
            if ($player->homeVillage) {
                return [
                    'type' => 'village',
                    'id' => $player->homeVillage->id,
                    'name' => $player->homeVillage->name,
                    'biome' => $player->homeVillage->biome,
                ];
            }

            return null;
        }

        $modelClass = match ($player->current_location_type) {
            'village' => \App\Models\Village::class,
            'castle' => \App\Models\Castle::class,
            'town' => \App\Models\Town::class,
            'kingdom' => \App\Models\Kingdom::class,
            'wilderness' => null,
            default => null,
        };

        if ($player->current_location_type === 'wilderness') {
            return [
                'type' => 'wilderness',
                'id' => null,
                'name' => 'The Wilderness',
                'biome' => 'wilderness',
            ];
        }

        if (! $modelClass) {
            return null;
        }

        $location = $modelClass::find($player->current_location_id);

        if (! $location) {
            return null;
        }

        return [
            'type' => $player->current_location_type,
            'id' => $location->id,
            'name' => $location->name,
            'biome' => $location->biome ?? 'unknown',
            'is_port' => $location->is_port ?? false,
        ];
    }
}
