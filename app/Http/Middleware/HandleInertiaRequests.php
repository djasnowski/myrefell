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
        $player->load(['skills', 'homeVillage.barony.kingdom']);

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
                'barony' => $player->homeVillage->barony ? [
                    'id' => $player->homeVillage->barony->id,
                    'name' => $player->homeVillage->barony->name,
                ] : null,
                'kingdom' => $player->homeVillage->barony?->kingdom ? [
                    'id' => $player->homeVillage->barony->kingdom->id,
                    'name' => $player->homeVillage->barony->kingdom->name,
                ] : null,
            ] : null,
            'travel' => $this->travelService->getTravelStatus($player),
            'nearby_destinations' => $this->travelService->getAvailableDestinations($player),
            'context' => $this->getPlayerContext($player),
        ];
    }

    /**
     * Get player context for conditional sidebar items.
     */
    protected function getPlayerContext($player): array
    {
        $context = [];

        // Check for dynasty membership
        $dynastyMember = \App\Models\DynastyMember::where('user_id', $player->id)
            ->with('dynasty')
            ->first();
        if ($dynastyMember?->dynasty) {
            $context['dynasty'] = [
                'id' => $dynastyMember->dynasty->id,
                'name' => $dynastyMember->dynasty->name,
            ];
        }

        // Check for guild membership (if in table, they're a member)
        $guildMember = \App\Models\GuildMember::where('user_id', $player->id)
            ->with('guild')
            ->first();
        if ($guildMember?->guild) {
            $context['guild'] = [
                'id' => $guildMember->guild->id,
                'name' => $guildMember->guild->name,
            ];
        }

        // Check for business ownership
        $business = \App\Models\PlayerBusiness::where('user_id', $player->id)->first();
        if ($business) {
            $context['business'] = [
                'id' => $business->id,
                'name' => $business->name,
            ];
        }

        // Check for religion membership
        $religionMember = \App\Models\ReligionMember::where('user_id', $player->id)
            ->with('religion')
            ->first();
        if ($religionMember?->religion) {
            $context['religion'] = [
                'id' => $religionMember->religion->id,
                'name' => $religionMember->religion->name,
            ];
        }

        // Check for army command
        $army = \App\Models\Army::where('commander_id', $player->id)->first();
        if ($army) {
            $context['army'] = [
                'id' => $army->id,
                'name' => $army->name,
            ];
        }

        return $context;
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
            'barony' => \App\Models\Barony::class,
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
