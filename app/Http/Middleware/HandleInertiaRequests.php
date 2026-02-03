<?php

namespace App\Http\Middleware;

use App\Services\BeliefEffectService;
use App\Services\BlessingEffectService;
use App\Services\EnergyService;
use App\Services\OnlinePlayersService;
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
        protected TravelService $travelService,
        protected OnlinePlayersService $onlinePlayersService,
        protected BlessingEffectService $blessingEffectService,
        protected BeliefEffectService $beliefEffectService
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

        // Track online status
        if ($player) {
            $this->onlinePlayersService->markOnline($player->id);
        }

        // Check if admin is impersonating
        $impersonating = null;
        if ($player && app('impersonate')->isImpersonating()) {
            $impersonatorId = app('impersonate')->getImpersonatorId();
            $impersonator = \App\Models\User::find($impersonatorId);
            $impersonating = [
                'impersonator_username' => $impersonator?->username,
                'leave_url' => route('impersonate.leave'),
            ];
        }

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $player,
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'result' => fn () => $request->session()->get('result'),
                'dice_result' => fn () => $request->session()->get('dice_result'),
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'sidebar' => $player ? $this->getSidebarData($player) : null,
            'online_count' => $this->onlinePlayersService->getOnlineCount(),
            'impersonating' => $impersonating,
        ];
    }

    /**
     * Get sidebar data for the player.
     */
    protected function getSidebarData($player): array
    {
        // Refresh to get latest values (e.g., energy updated by scheduler)
        $player->refresh();
        $player->load(['skills', 'homeVillage.barony.kingdom']);

        // Get active role
        $activeRole = \App\Models\PlayerRole::where('user_id', $player->id)
            ->active()
            ->with('role')
            ->first();

        // Get active employment
        $activeEmployment = \App\Models\PlayerEmployment::where('user_id', $player->id)
            ->where('status', 'employed')
            ->with('job')
            ->first();

        return [
            'player' => [
                'id' => $player->id,
                'username' => $player->username,
                'gender' => $player->gender,
                'hp' => $player->hp,
                'max_hp' => $player->max_hp,
                'base_max_hp' => $player->getSkillLevel('hitpoints'),
                'hp_bonuses' => $this->getHpBonuses($player),
                'energy' => $player->energy,
                'max_energy' => $player->max_energy,
                'gold' => $player->gold,
                'combat_level' => $player->combat_level,
                'primary_title' => $player->primary_title,
                'title_tier' => $player->title_tier,
                'social_class' => $player->social_class,
                'is_admin' => $player->username === 'dan',
                'role' => $activeRole ? [
                    'name' => $activeRole->role->name,
                    'slug' => $activeRole->role->slug,
                    'icon' => $activeRole->role->icon ?? null,
                    'location_name' => $activeRole->location_name,
                    'location_type' => $activeRole->location_type,
                    'location_id' => $activeRole->location_id,
                    'pending_count' => $this->getRolePendingCount($activeRole),
                ] : null,
                'job' => $activeEmployment ? [
                    'name' => $activeEmployment->job->name,
                    'icon' => $activeEmployment->job->icon ?? null,
                    'wage' => $activeEmployment->job->base_wage,
                ] : null,
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
            'health' => $this->getHealthData($player),
            'farm' => $this->getFarmData($player),
            'favorites' => $this->getServiceFavorites($player),
            'can_play_minigame' => \App\Models\MinigamePlay::canPlayToday($player->id),
        ];
    }

    /**
     * Get player health data including active disease infections.
     */
    protected function getHealthData($player): array
    {
        $infection = null;

        try {
            $activeInfection = \App\Models\DiseaseInfection::where('user_id', $player->id)
                ->active()
                ->with('diseaseType')
                ->first();

            if ($activeInfection) {
                $infection = [
                    'id' => $activeInfection->id,
                    'disease_name' => $activeInfection->diseaseType?->name ?? 'Unknown Disease',
                    'status' => $activeInfection->status,
                    'severity' => $activeInfection->severity_modifier > 1.5 ? 'severe' : ($activeInfection->severity_modifier > 1 ? 'moderate' : 'mild'),
                    'days_infected' => $activeInfection->days_infected,
                    'is_treated' => $activeInfection->is_treated,
                ];
            }
        } catch (\Illuminate\Database\QueryException $e) {
            // Table may not exist yet
        }

        return [
            'infection' => $infection,
            'is_healthy' => $infection === null,
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

        // Check for active roles at current location
        if ($player->current_location_type && $player->current_location_id) {
            $hasRoleAtLocation = \App\Models\PlayerRole::where('user_id', $player->id)
                ->where('location_type', $player->current_location_type)
                ->where('location_id', $player->current_location_id)
                ->where('status', 'active')
                ->exists();

            $context['has_role_at_location'] = $hasRoleAtLocation;
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
            'duchy' => \App\Models\Duchy::class,
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

        $locationType = $player->current_location_type;
        $isTownOrVillage = in_array($locationType, ['village', 'town']);
        $isTown = $locationType === 'town';
        $isNotVillage = $locationType !== 'village';

        return [
            'type' => $locationType,
            'id' => $location->id,
            'name' => $location->name,
            'biome' => $location->biome ?? 'unknown',
            'is_port' => $location->is_port ?? false,
            // Location features - what's available here
            'features' => [
                'market' => $isTownOrVillage,
                'bank' => $isTown, // Only towns have banks
                'training' => $isTownOrVillage,
                'jobs' => $isTownOrVillage,
                'tavern' => $isTownOrVillage,
                'crafting' => $isTownOrVillage,
                'port' => $location->is_port ?? false,
                'dungeon' => $this->hasDungeonNearby($location, $locationType),
                'guilds' => $isTown,
                'elections' => $isTownOrVillage,
                'stables' => $isNotVillage, // Towns, baronies, duchies, kingdoms have stables
            ],
        ];
    }

    /**
     * Check if there's a dungeon accessible from this location.
     * Dungeons are biome-based, so we check if there's a dungeon matching the location's biome.
     */
    protected function hasDungeonNearby($location, string $locationType): bool
    {
        if (! in_array($locationType, ['village', 'town'])) {
            return false;
        }

        $biome = $location->biome ?? null;
        if (! $biome) {
            return false;
        }

        try {
            return \App\Models\Dungeon::where('biome', $biome)->exists();
        } catch (\Illuminate\Database\QueryException $e) {
            // Table may not exist yet
            return false;
        }
    }

    /**
     * Get farm data for the player at their current location.
     * Farming only shows if player has crops planted at this location.
     */
    protected function getFarmData($player): ?array
    {
        // FarmPlot model may not exist yet
        if (! class_exists(\App\Models\FarmPlot::class)) {
            return null;
        }

        if (! $player->current_location_type || ! $player->current_location_id) {
            return null;
        }

        try {
            $plots = \App\Models\FarmPlot::where('user_id', $player->id)
                ->where('location_type', $player->current_location_type)
                ->where('location_id', $player->current_location_id)
                ->get();

            if ($plots->isEmpty()) {
                return null;
            }

            $cropsReady = $plots->filter(fn ($plot) => $plot->isReadyToHarvest())->count();

            return [
                'has_crops' => true,
                'crops_ready' => $cropsReady,
                'total_plots' => $plots->count(),
            ];
        } catch (\Throwable $e) {
            // Model or table may not exist yet
            return null;
        }
    }

    /**
     * Get pending action count for a player's role.
     */
    protected function getRolePendingCount(\App\Models\PlayerRole $playerRole): int
    {
        $count = 0;
        $slug = $playerRole->role->slug;
        $locationType = $playerRole->location_type;
        $locationId = $playerRole->location_id;

        try {
            // Village Elder - migration requests, accusations
            if ($slug === 'elder') {
                $count += \App\Models\MigrationRequest::where('status', 'pending')
                    ->where('to_village_id', $locationId)
                    ->whereNull('elder_decided_at')
                    ->count();

                $count += \App\Models\Accusation::where('status', 'pending')
                    ->where('location_type', 'village')
                    ->where('location_id', $locationId)
                    ->count();
            }

            // Blacksmith - crafting orders
            if (in_array($slug, ['blacksmith', 'master_blacksmith', 'weaponsmith', 'armorsmith'])) {
                $count += \App\Models\CraftingOrder::where('status', 'pending')
                    ->where('location_type', $locationType)
                    ->where('location_id', $locationId)
                    ->count();
            }

            // Guard Captain - accusations, bounties
            if (in_array($slug, ['guard_captain', 'town_guard_captain'])) {
                $count += \App\Models\Accusation::where('status', 'pending')
                    ->where('location_type', $locationType)
                    ->where('location_id', $locationId)
                    ->count();
            }

            // Baron - migration requests (barony level), manumission requests
            if ($slug === 'baron') {
                // Get all villages in this barony
                $villageIds = \App\Models\Village::where('barony_id', $locationId)->pluck('id');

                $count += \App\Models\MigrationRequest::where('status', 'pending')
                    ->whereIn('to_village_id', $villageIds)
                    ->whereNotNull('elder_decided_at')
                    ->whereNull('baron_decided_at')
                    ->count();

                $count += \App\Models\ManumissionRequest::where('status', 'pending')
                    ->where('barony_id', $locationId)
                    ->count();

                $count += \App\Models\Accusation::where('status', 'pending')
                    ->where('location_type', 'barony')
                    ->where('location_id', $locationId)
                    ->count();
            }

            // Jailsman - prisoners needing attention
            if ($slug === 'jailsman') {
                $count += \App\Models\Punishment::where('status', 'pending')
                    ->where('location_type', $locationType)
                    ->where('location_id', $locationId)
                    ->count();
            }

            // Mayor - town matters
            if ($slug === 'mayor') {
                $count += \App\Models\Accusation::where('status', 'pending')
                    ->where('location_type', 'town')
                    ->where('location_id', $locationId)
                    ->count();
            }

            // Magistrate - accusations, trials
            if ($slug === 'magistrate') {
                $count += \App\Models\Accusation::where('status', 'pending')
                    ->where('location_type', 'town')
                    ->where('location_id', $locationId)
                    ->count();

                $count += \App\Models\Trial::whereIn('status', ['scheduled', 'in_progress', 'awaiting_verdict'])
                    ->where('location_type', 'town')
                    ->where('location_id', $locationId)
                    ->count();
            }

            // Healer/Priest - blessings (if applicable)
            if (in_array($slug, ['healer', 'priest', 'court_chaplain', 'high_priest', 'archbishop'])) {
                // Check for blessing requests if the model exists
                if (class_exists(\App\Models\BlessingRequest::class)) {
                    $count += \App\Models\BlessingRequest::where('status', 'pending')
                        ->where('location_type', $locationType)
                        ->where('location_id', $locationId)
                        ->count();
                }
            }

            // King - kingdom-wide matters
            if ($slug === 'king') {
                $count += \App\Models\Charter::where('status', 'pending')->count();

                if (class_exists(\App\Models\EnnoblementRequest::class)) {
                    $count += \App\Models\EnnoblementRequest::where('status', 'pending')
                        ->where('kingdom_id', $locationId)
                        ->count();
                }
            }

        } catch (\Throwable $e) {
            // Tables may not exist yet
            return 0;
        }

        return $count;
    }

    /**
     * Get service favorites for the player.
     *
     * @return array<int, array{service_id: string, name: string, icon: string, route: string}>
     */
    protected function getServiceFavorites($player): array
    {
        try {
            $favorites = \App\Models\UserServiceFavorite::where('user_id', $player->id)
                ->orderBy('sort_order')
                ->pluck('service_id')
                ->toArray();

            if (empty($favorites)) {
                return [];
            }

            $services = \App\Config\LocationServices::SERVICES;
            $result = [];

            foreach ($favorites as $serviceId) {
                if (isset($services[$serviceId])) {
                    $result[] = [
                        'service_id' => $serviceId,
                        'name' => $services[$serviceId]['name'],
                        'icon' => $services[$serviceId]['icon'],
                        'route' => $services[$serviceId]['route'],
                    ];
                }
            }

            return $result;
        } catch (\Throwable $e) {
            // Table may not exist yet
            return [];
        }
    }

    /**
     * Get HP bonuses breakdown for the player.
     *
     * @return array<int, array{source: string, amount: int}>
     */
    protected function getHpBonuses($player): array
    {
        $bonuses = [];

        $blessingBonus = (int) $this->blessingEffectService->getEffect($player, 'max_hp_bonus');
        if ($blessingBonus > 0) {
            $bonuses[] = [
                'source' => 'Blessing',
                'amount' => $blessingBonus,
            ];
        }

        $beliefBonus = (int) $this->beliefEffectService->getEffect($player, 'max_hp_bonus');
        if ($beliefBonus > 0) {
            $bonuses[] = [
                'source' => 'Belief',
                'amount' => $beliefBonus,
            ];
        }

        return $bonuses;
    }
}
