<?php

namespace App\Http\Controllers;

use App\Models\Barony;
use App\Models\Duchy;
use App\Models\Kingdom;
use App\Models\LocationActivityLog;
use App\Models\Town;
use App\Models\Village;
use App\Services\CookingService;
use App\Services\DiceGameService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TavernController extends Controller
{
    /**
     * Rest cooldown in seconds.
     */
    private const REST_COOLDOWN_SECONDS = 3;

    /**
     * Rest configuration by location type.
     */
    private const REST_CONFIG = [
        'village' => ['cost' => 10, 'energy' => 50],
        'town' => ['cost' => 15, 'energy' => 75],
        'barony' => ['cost' => 15, 'energy' => 75],
        'duchy' => ['cost' => 20, 'energy' => 100],
        'kingdom' => ['cost' => 25, 'energy' => 150],
    ];

    public function __construct(
        protected CookingService $cookingService,
        protected DiceGameService $diceGameService
    ) {}

    /**
     * Show the tavern page.
     */
    public function index(Request $request, ?Village $village = null, ?Town $town = null, ?Barony $barony = null, ?Duchy $duchy = null, ?Kingdom $kingdom = null): Response
    {
        $user = $request->user();

        $location = $village ?? $town ?? $barony ?? $duchy ?? $kingdom;
        $locationType = $this->getLocationType($location);

        // Get recent activity at this location (rumors/gossip)
        $recentActivity = [];
        if ($location && $locationType) {
            try {
                $recentActivity = LocationActivityLog::atLocation($locationType, $location->id)
                    ->recent(20)
                    ->with('user:id,username')
                    ->get()
                    ->map(fn ($log) => [
                        'id' => $log->id,
                        'username' => $log->user->username ?? 'Unknown',
                        'description' => $log->description,
                        'type' => $log->activity_type,
                        'subtype' => $log->activity_subtype,
                        'time_ago' => $log->created_at->diffForHumans(),
                    ]);
            } catch (\Illuminate\Database\QueryException $e) {
                $recentActivity = [];
            }
        }

        // Calculate rest cost and benefits based on location
        $restConfig = self::REST_CONFIG[$locationType] ?? self::REST_CONFIG['village'];
        $restCost = $restConfig['cost'];
        $energyRestored = min($restConfig['energy'], $user->max_energy - $user->energy);

        // Check rest cooldown
        $restCooldownEnds = null;
        $canRest = $user->gold >= $restCost && $user->energy < $user->max_energy;
        if ($user->last_rested_at) {
            $cooldownEnds = $user->last_rested_at->addSeconds(self::REST_COOLDOWN_SECONDS);
            if ($cooldownEnds->isFuture()) {
                $restCooldownEnds = $cooldownEnds->toIso8601String();
                $canRest = false;
            }
        }

        // Get cooking recipes
        $cookingInfo = $this->cookingService->getCookingInfo($user);

        // Get dice game info
        $canPlayDice = $this->diceGameService->canPlay($user);
        $tavernStats = $location && $locationType
            ? $this->diceGameService->getTavernStats($user, $locationType, $location->id)
            : ['wins' => 0, 'losses' => 0, 'total_profit' => 0];
        $recentGames = $this->diceGameService->getGameHistory($user, 5);

        return Inertia::render('Tavern/Index', [
            'location' => $location ? [
                'type' => $locationType,
                'id' => $location->id,
                'name' => $location->name,
            ] : null,
            'player' => [
                'energy' => $user->energy,
                'max_energy' => $user->max_energy,
                'gold' => $user->gold,
            ],
            'rest' => [
                'cost' => $restCost,
                'energy_restored' => $energyRestored,
                'can_rest' => $canRest,
                'cooldown_ends' => $restCooldownEnds,
            ],
            'recent_activity' => $recentActivity,
            'cooking' => $cookingInfo,
            'dice' => [
                'can_play' => $canPlayDice['can_play'],
                'cooldown_ends' => $canPlayDice['cooldown_ends'],
                'reason' => $canPlayDice['reason'],
                'min_wager' => DiceGameService::MIN_WAGER,
                'max_wager' => DiceGameService::MAX_WAGER,
                'games' => ['high_roll', 'hazard', 'doubles'],
                'recent_games' => $recentGames,
                'tavern_stats' => $tavernStats,
            ],
        ]);
    }

    /**
     * Rest at the tavern to restore energy.
     */
    public function rest(Request $request, ?Village $village = null, ?Town $town = null, ?Barony $barony = null, ?Duchy $duchy = null, ?Kingdom $kingdom = null)
    {
        $user = $request->user();
        $location = $village ?? $town ?? $barony ?? $duchy ?? $kingdom;
        $locationType = $this->getLocationType($location) ?? $user->current_location_type ?? 'village';

        $restConfig = self::REST_CONFIG[$locationType] ?? self::REST_CONFIG['village'];
        $restCost = $restConfig['cost'];
        $energyRestored = min($restConfig['energy'], $user->max_energy - $user->energy);

        // Check cooldown
        if ($user->last_rested_at) {
            $cooldownEnds = $user->last_rested_at->addSeconds(self::REST_COOLDOWN_SECONDS);
            if ($cooldownEnds->isFuture()) {
                return back()->withErrors(['error' => 'You need to wait before resting again.']);
            }
        }

        if ($user->gold < $restCost) {
            return back()->withErrors(['error' => "You need {$restCost}g to rest at the tavern."]);
        }

        if ($user->energy >= $user->max_energy) {
            return back()->withErrors(['error' => 'You are already fully rested.']);
        }

        $user->decrement('gold', $restCost);
        $user->increment('energy', $energyRestored);
        $user->update(['last_rested_at' => now()]);

        // Log activity
        if ($user->current_location_type && $user->current_location_id) {
            try {
                LocationActivityLog::log(
                    userId: $user->id,
                    locationType: $user->current_location_type,
                    locationId: $user->current_location_id,
                    activityType: LocationActivityLog::TYPE_REST,
                    description: "{$user->username} rested at the tavern",
                    metadata: ['energy_restored' => $energyRestored, 'gold_spent' => $restCost]
                );
            } catch (\Illuminate\Database\QueryException $e) {
                // Table may not exist
            }
        }

        return back()->with('success', "You rest at the tavern and recover {$energyRestored} energy.");
    }

    /**
     * Cook a recipe at the tavern.
     */
    public function cook(Request $request, ?Village $village = null, ?Town $town = null, ?Barony $barony = null, ?Duchy $duchy = null, ?Kingdom $kingdom = null): JsonResponse
    {
        $request->validate([
            'recipe' => 'required|string',
        ]);

        $location = $village ?? $town ?? $barony ?? $duchy ?? $kingdom;
        $locationType = $this->getLocationType($location);

        $user = $request->user();
        $result = $this->cookingService->cook(
            $user,
            $request->input('recipe'),
            $locationType ?? $user->current_location_type,
            $location?->id ?? $user->current_location_id
        );

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * Determine location type from model.
     */
    protected function getLocationType($location): ?string
    {
        return match (true) {
            $location instanceof Village => 'village',
            $location instanceof Town => 'town',
            $location instanceof Barony => 'barony',
            $location instanceof Duchy => 'duchy',
            $location instanceof Kingdom => 'kingdom',
            default => null,
        };
    }
}
