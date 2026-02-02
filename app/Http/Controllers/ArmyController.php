<?php

namespace App\Http\Controllers;

use App\Models\Army;
use App\Models\ArmyUnit;
use App\Models\Battle;
use App\Models\MercenaryCompany;
use App\Models\Town;
use App\Models\Village;
use App\Services\ArmyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ArmyController extends Controller
{
    const ARMY_CREATION_COST = 500;

    const ARMY_RENAME_COST = 25000;

    const MAX_ARMIES_PER_PLAYER = 3;

    public function __construct(
        protected ArmyService $armyService
    ) {}

    /**
     * Display a listing of the user's armies.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        // Get user's active armies
        $activeArmies = Army::where('owner_type', 'player')
            ->where('owner_id', $user->id)
            ->where('status', '!=', Army::STATUS_DISBANDED)
            ->with(['commander', 'units'])
            ->get()
            ->map(fn ($army) => $this->mapArmy($army));

        // Get disbanded armies (history)
        $disbandedArmies = Army::where('owner_type', 'player')
            ->where('owner_id', $user->id)
            ->where('status', Army::STATUS_DISBANDED)
            ->latest('updated_at')
            ->limit(10)
            ->get()
            ->map(fn ($army) => $this->mapArmy($army, false));

        // Get available mercenary companies
        $mercenaryCompanies = MercenaryCompany::available()
            ->with('army.units')
            ->get()
            ->map(fn ($company) => $this->mapMercenary($company));

        // Get player's current location for raising armies
        $locationName = match ($user->current_location_type) {
            'village' => Village::find($user->current_location_id)?->name,
            'town' => Town::find($user->current_location_id)?->name,
            default => null,
        };
        $currentLocation = [
            'type' => $user->current_location_type,
            'id' => $user->current_location_id,
            'name' => $locationName ?? 'Unknown',
        ];

        // Get unit type info for recruiting
        $unitTypes = $this->getUnitTypeInfo();

        return Inertia::render('Warfare/Armies', [
            'active_armies' => $activeArmies->toArray(),
            'disbanded_armies' => $disbandedArmies->toArray(),
            'mercenary_companies' => $mercenaryCompanies->toArray(),
            'current_location' => $currentLocation,
            'army_creation_cost' => self::ARMY_CREATION_COST,
            'army_rename_cost' => self::ARMY_RENAME_COST,
            'max_armies' => self::MAX_ARMIES_PER_PLAYER,
            'unit_types' => $unitTypes,
            'recruitment_costs' => ArmyService::RECRUITMENT_COSTS,
        ]);
    }

    /**
     * Map army to array for frontend.
     */
    private function mapArmy(Army $army, bool $includeUnits = true): array
    {
        $data = [
            'id' => $army->id,
            'name' => $army->name,
            'status' => $army->status,
            'morale' => $army->morale,
            'supplies' => $army->supplies,
            'daily_supply_cost' => $army->daily_supply_cost,
            'gold_upkeep' => $army->gold_upkeep,
            'total_troops' => $army->total_troops,
            'total_attack' => $army->total_attack,
            'total_defense' => $army->total_defense,
            'commander' => $army->commander ? [
                'id' => $army->commander->id,
                'name' => $army->commander->username,
            ] : null,
            'location' => [
                'type' => $army->location_type,
                'id' => $army->location_id,
                'name' => $army->location?->name ?? ($army->location_type === 'field' ? 'In the field' : 'Unknown'),
            ],
            'mustered_at' => $army->mustered_at?->toISOString(),
            'treasury' => $army->treasury,
            'can_rename' => $army->canRename(),
            'next_rename_at' => $army->nextRenameAt()?->toISOString(),
        ];

        if ($includeUnits) {
            $data['units'] = $army->units->map(fn ($unit) => [
                'id' => $unit->id,
                'unit_type' => $unit->unit_type,
                'count' => $unit->count,
                'max_count' => $unit->max_count,
                'attack' => $unit->attack,
                'defense' => $unit->defense,
                'status' => $unit->status,
                'total_attack' => $unit->total_attack,
                'total_defense' => $unit->total_defense,
            ])->toArray();
            $data['composition'] = $army->composition ?? [];
        }

        return $data;
    }

    /**
     * Map mercenary company to array for frontend.
     */
    private function mapMercenary(MercenaryCompany $company): array
    {
        return [
            'id' => $company->id,
            'name' => $company->name,
            'reputation' => $company->reputation,
            'specialization' => $company->specialization,
            'hire_cost' => $company->hire_cost,
            'daily_cost' => $company->daily_cost,
            'soldier_count' => $company->army?->total_troops ?? 0,
            'total_attack' => $company->army?->total_attack ?? 0,
            'total_defense' => $company->army?->total_defense ?? 0,
        ];
    }

    /**
     * Get unit type info for UI.
     */
    private function getUnitTypeInfo(): array
    {
        return [
            ArmyUnit::TYPE_LEVY => [
                'name' => 'Levy',
                'description' => 'Untrained peasant soldiers. Cheap but weak.',
                'stats' => ArmyUnit::getBaseStats(ArmyUnit::TYPE_LEVY),
            ],
            ArmyUnit::TYPE_MILITIA => [
                'name' => 'Militia',
                'description' => 'Part-time soldiers with basic training.',
                'stats' => ArmyUnit::getBaseStats(ArmyUnit::TYPE_MILITIA),
            ],
            ArmyUnit::TYPE_MEN_AT_ARMS => [
                'name' => 'Men-at-Arms',
                'description' => 'Professional soldiers with good equipment.',
                'stats' => ArmyUnit::getBaseStats(ArmyUnit::TYPE_MEN_AT_ARMS),
            ],
            ArmyUnit::TYPE_KNIGHTS => [
                'name' => 'Knights',
                'description' => 'Elite armored warriors. Very powerful.',
                'stats' => ArmyUnit::getBaseStats(ArmyUnit::TYPE_KNIGHTS),
            ],
            ArmyUnit::TYPE_ARCHERS => [
                'name' => 'Archers',
                'description' => 'Ranged infantry with good attack.',
                'stats' => ArmyUnit::getBaseStats(ArmyUnit::TYPE_ARCHERS),
            ],
            ArmyUnit::TYPE_CROSSBOWMEN => [
                'name' => 'Crossbowmen',
                'description' => 'Heavy ranged infantry with armor-piercing bolts.',
                'stats' => ArmyUnit::getBaseStats(ArmyUnit::TYPE_CROSSBOWMEN),
            ],
            ArmyUnit::TYPE_CAVALRY => [
                'name' => 'Cavalry',
                'description' => 'Mounted soldiers with high mobility.',
                'stats' => ArmyUnit::getBaseStats(ArmyUnit::TYPE_CAVALRY),
            ],
            ArmyUnit::TYPE_SIEGE_ENGINEERS => [
                'name' => 'Siege Engineers',
                'description' => 'Specialists for siege warfare.',
                'stats' => ArmyUnit::getBaseStats(ArmyUnit::TYPE_SIEGE_ENGINEERS),
            ],
        ];
    }

    /**
     * Display the specified army.
     */
    public function show(Request $request, Army $army): Response
    {
        $user = $request->user();

        // Check ownership
        if ($army->owner_type !== 'player' || $army->owner_id !== $user->id) {
            abort(403, 'You do not own this army.');
        }

        // Load relationships
        $army->load(['commander', 'units', 'supplyLines']);

        // Get battle history
        $battleHistory = Battle::whereHas('participants', fn ($q) => $q->where('army_id', $army->id))
            ->with(['participants' => fn ($q) => $q->where('army_id', $army->id)])
            ->latest('started_at')
            ->limit(5)
            ->get()
            ->map(fn ($battle) => [
                'id' => $battle->id,
                'name' => $battle->name,
                'status' => $battle->status,
                'outcome' => $battle->participants->first()?->outcome,
                'casualties' => $battle->participants->first()?->casualties ?? 0,
                'started_at' => $battle->started_at?->toISOString(),
                'ended_at' => $battle->ended_at?->toISOString(),
            ]);

        // Get nearby settlements for movement
        $nearbySettlements = $this->getNearbySettlements($army);

        // Get available recruits (only if at a settlement)
        $canRecruit = in_array($army->location_type, ['village', 'town']);

        return Inertia::render('Warfare/ArmyShow', [
            'army' => $this->mapArmyDetail($army),
            'supply_line' => $army->supplyLines->first() ? $this->mapSupplyLine($army->supplyLines->first()) : null,
            'battle_history' => $battleHistory->toArray(),
            'nearby_settlements' => $nearbySettlements,
            'unit_types' => $this->getUnitTypeInfo(),
            'recruitment_costs' => ArmyService::RECRUITMENT_COSTS,
            'can_recruit' => $canRecruit,
        ]);
    }

    /**
     * Map army to detailed array for show page.
     */
    private function mapArmyDetail(Army $army): array
    {
        $data = $this->mapArmy($army);
        $data['supplies_days_remaining'] = $army->daily_supply_cost > 0
            ? (int) floor($army->supplies / $army->daily_supply_cost)
            : $army->supplies;

        return $data;
    }

    /**
     * Map supply line to array.
     */
    private function mapSupplyLine($supplyLine): array
    {
        return [
            'id' => $supplyLine->id,
            'source' => [
                'type' => $supplyLine->source_type,
                'id' => $supplyLine->source_id,
                'name' => $supplyLine->source?->name ?? 'Unknown',
            ],
            'status' => $supplyLine->status,
            'supply_rate' => $supplyLine->supply_rate,
            'effective_rate' => $supplyLine->effective_supply_rate,
            'distance' => $supplyLine->distance,
            'safety' => $supplyLine->safety,
        ];
    }

    /**
     * Get nearby settlements for army movement.
     */
    private function getNearbySettlements(Army $army): array
    {
        $settlements = [];

        // Get current location coordinates
        $currentX = null;
        $currentY = null;

        if ($army->location_type === 'village') {
            $location = Village::find($army->location_id);
            $currentX = $location?->coordinates_x;
            $currentY = $location?->coordinates_y;
        } elseif ($army->location_type === 'town') {
            $location = Town::find($army->location_id);
            $currentX = $location?->coordinates_x;
            $currentY = $location?->coordinates_y;
        }

        // If we have coordinates, find nearby settlements
        if ($currentX !== null && $currentY !== null) {
            // Get nearby villages (within 5 units)
            $villages = Village::whereNotNull('coordinates_x')
                ->whereNotNull('coordinates_y')
                ->where(function ($query) use ($army) {
                    $query->where('id', '!=', $army->location_id)
                        ->orWhere(fn ($q) => $q->where($army->location_type, '!=', 'village'));
                })
                ->get()
                ->map(function ($village) use ($currentX, $currentY) {
                    $distance = sqrt(pow($village->coordinates_x - $currentX, 2) + pow($village->coordinates_y - $currentY, 2));

                    return [
                        'type' => 'village',
                        'id' => $village->id,
                        'name' => $village->name,
                        'distance' => round($distance, 1),
                        'travel_days' => max(1, (int) ceil($distance / 2)),
                    ];
                })
                ->filter(fn ($s) => $s['distance'] <= 10)
                ->sortBy('distance')
                ->take(5)
                ->values();

            // Get nearby towns (within 5 units)
            $towns = Town::whereNotNull('coordinates_x')
                ->whereNotNull('coordinates_y')
                ->where(function ($query) use ($army) {
                    $query->where('id', '!=', $army->location_id)
                        ->orWhere(fn ($q) => $q->where($army->location_type, '!=', 'town'));
                })
                ->get()
                ->map(function ($town) use ($currentX, $currentY) {
                    $distance = sqrt(pow($town->coordinates_x - $currentX, 2) + pow($town->coordinates_y - $currentY, 2));

                    return [
                        'type' => 'town',
                        'id' => $town->id,
                        'name' => $town->name,
                        'distance' => round($distance, 1),
                        'travel_days' => max(1, (int) ceil($distance / 2)),
                    ];
                })
                ->filter(fn ($s) => $s['distance'] <= 10)
                ->sortBy('distance')
                ->take(5)
                ->values();

            $settlements = $villages->merge($towns)->sortBy('distance')->take(8)->values()->toArray();
        } else {
            // Fallback: just get some villages and towns
            $villages = Village::limit(4)->get()->map(fn ($v) => [
                'type' => 'village',
                'id' => $v->id,
                'name' => $v->name,
                'distance' => null,
                'travel_days' => 2,
            ]);

            $towns = Town::limit(4)->get()->map(fn ($t) => [
                'type' => 'town',
                'id' => $t->id,
                'name' => $t->name,
                'distance' => null,
                'travel_days' => 2,
            ]);

            $settlements = $villages->merge($towns)->take(8)->toArray();
        }

        return $settlements;
    }

    /**
     * Store a newly created army.
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => 'required|string|max:100',
        ]);

        // Check army limit
        $activeArmyCount = Army::where('owner_type', 'player')
            ->where('owner_id', $user->id)
            ->where('status', '!=', Army::STATUS_DISBANDED)
            ->count();

        if ($activeArmyCount >= self::MAX_ARMIES_PER_PLAYER) {
            return response()->json([
                'success' => false,
                'message' => 'You can only have '.self::MAX_ARMIES_PER_PLAYER.' armies at a time.',
            ], 400);
        }

        // Check if player has enough gold
        if ($user->gold < self::ARMY_CREATION_COST) {
            return response()->json([
                'success' => false,
                'message' => 'Not enough gold. You need '.self::ARMY_CREATION_COST.'g to raise an army.',
            ], 400);
        }

        // Deduct gold and create army
        $user->decrement('gold', self::ARMY_CREATION_COST);

        $army = $this->armyService->raiseArmy(
            name: $validated['name'],
            ownerType: 'player',
            ownerId: $user->id,
            locationType: $user->current_location_type,
            locationId: $user->current_location_id,
            commanderId: $user->id
        );

        return response()->json([
            'success' => true,
            'message' => "Army '{$army->name}' has been raised!",
            'army' => $this->mapArmy($army),
        ]);
    }

    /**
     * Disband an army.
     */
    public function disband(Request $request, Army $army): JsonResponse
    {
        $user = $request->user();

        // Check ownership
        if ($army->owner_type !== 'player' || $army->owner_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'You do not own this army.',
            ], 403);
        }

        // Cannot disband while in battle
        if ($army->status === Army::STATUS_IN_BATTLE) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot disband an army while in battle.',
            ], 400);
        }

        $this->armyService->disbandArmy($army);

        return response()->json([
            'success' => true,
            'message' => "Army '{$army->name}' has been disbanded.",
        ]);
    }

    /**
     * Rename an army.
     */
    public function rename(Request $request, Army $army): JsonResponse
    {
        $user = $request->user();

        // Check ownership
        if ($army->owner_type !== 'player' || $army->owner_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'You do not own this army.',
            ], 403);
        }

        // Check cooldown
        if (! $army->canRename()) {
            $nextRename = $army->nextRenameAt();

            return response()->json([
                'success' => false,
                'message' => 'You can only rename an army once every 3 months. Next rename available: '.$nextRename->diffForHumans(),
            ], 400);
        }

        // Check gold
        if ($user->gold < self::ARMY_RENAME_COST) {
            return response()->json([
                'success' => false,
                'message' => 'Not enough gold. Renaming costs '.number_format(self::ARMY_RENAME_COST).'g.',
            ], 400);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:100',
        ]);

        // Deduct gold
        $user->decrement('gold', self::ARMY_RENAME_COST);

        $oldName = $army->name;
        $army->update([
            'name' => $validated['name'],
            'last_renamed_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => "Army renamed from '{$oldName}' to '{$army->name}' for ".number_format(self::ARMY_RENAME_COST).'g.',
        ]);
    }

    /**
     * Deposit gold into army treasury.
     */
    public function deposit(Request $request, Army $army): JsonResponse
    {
        $user = $request->user();

        // Check ownership
        if ($army->owner_type !== 'player' || $army->owner_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'You do not own this army.',
            ], 403);
        }

        $validated = $request->validate([
            'amount' => 'required|integer|min:1',
        ]);

        $amount = $validated['amount'];

        // Check player has enough gold
        if ($user->gold < $amount) {
            return response()->json([
                'success' => false,
                'message' => 'Not enough gold.',
            ], 400);
        }

        // Transfer gold
        $user->decrement('gold', $amount);
        $army->increment('treasury', $amount);

        return response()->json([
            'success' => true,
            'message' => 'Deposited '.number_format($amount).'g into army treasury.',
            'treasury' => $army->fresh()->treasury,
        ]);
    }

    /**
     * Withdraw gold from army treasury.
     */
    public function withdraw(Request $request, Army $army): JsonResponse
    {
        $user = $request->user();

        // Check ownership
        if ($army->owner_type !== 'player' || $army->owner_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'You do not own this army.',
            ], 403);
        }

        $validated = $request->validate([
            'amount' => 'required|integer|min:1',
        ]);

        $amount = $validated['amount'];

        // Check treasury has enough gold
        if ($army->treasury < $amount) {
            return response()->json([
                'success' => false,
                'message' => 'Not enough gold in treasury.',
            ], 400);
        }

        // Transfer gold
        $army->decrement('treasury', $amount);
        $user->increment('gold', $amount);

        return response()->json([
            'success' => true,
            'message' => 'Withdrew '.number_format($amount).'g from army treasury.',
            'treasury' => $army->fresh()->treasury,
        ]);
    }

    /**
     * Hire a mercenary company.
     */
    public function hireMercenary(Request $request, MercenaryCompany $company): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'contract_days' => 'integer|min:7|max:90',
        ]);

        $contractDays = $validated['contract_days'] ?? 30;

        // Check availability
        if (! $company->is_available) {
            return response()->json([
                'success' => false,
                'message' => 'This mercenary company is not available for hire.',
            ], 400);
        }

        // Check gold
        if ($user->gold < $company->hire_cost) {
            return response()->json([
                'success' => false,
                'message' => "Not enough gold. You need {$company->hire_cost}g to hire this company.",
            ], 400);
        }

        // Deduct gold and hire
        $user->decrement('gold', $company->hire_cost);

        $this->armyService->hireMercenaries(
            company: $company,
            hirer: $user,
            hirerType: 'player',
            contractDays: $contractDays
        );

        return response()->json([
            'success' => true,
            'message' => "Hired {$company->name} for {$contractDays} days!",
        ]);
    }

    /**
     * Recruit soldiers into an army.
     */
    public function recruit(Request $request, Army $army): JsonResponse
    {
        $user = $request->user();

        // Check ownership
        if ($army->owner_type !== 'player' || $army->owner_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'You do not own this army.',
            ], 403);
        }

        // Must be at a settlement to recruit
        if (! in_array($army->location_type, ['village', 'town'])) {
            return response()->json([
                'success' => false,
                'message' => 'Army must be at a settlement to recruit soldiers.',
            ], 400);
        }

        // Cannot recruit while in battle
        if ($army->status === Army::STATUS_IN_BATTLE) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot recruit while in battle.',
            ], 400);
        }

        $validated = $request->validate([
            'unit_type' => 'required|string|in:'.implode(',', [
                ArmyUnit::TYPE_LEVY,
                ArmyUnit::TYPE_MILITIA,
                ArmyUnit::TYPE_MEN_AT_ARMS,
                ArmyUnit::TYPE_KNIGHTS,
                ArmyUnit::TYPE_ARCHERS,
                ArmyUnit::TYPE_CROSSBOWMEN,
                ArmyUnit::TYPE_CAVALRY,
                ArmyUnit::TYPE_SIEGE_ENGINEERS,
            ]),
            'count' => 'required|integer|min:1|max:100',
        ]);

        $cost = $this->armyService->getRecruitmentCost($validated['unit_type'], $validated['count']);

        if ($user->gold < $cost) {
            return response()->json([
                'success' => false,
                'message' => "Not enough gold. You need {$cost}g to recruit these soldiers.",
            ], 400);
        }

        $user->decrement('gold', $cost);
        $this->armyService->recruitUnit($army, $validated['unit_type'], $validated['count']);

        $unitName = $this->getUnitTypeInfo()[$validated['unit_type']]['name'] ?? $validated['unit_type'];

        return response()->json([
            'success' => true,
            'message' => "Recruited {$validated['count']} {$unitName} for {$cost}g.",
        ]);
    }

    /**
     * Move an army to a new location.
     */
    public function move(Request $request, Army $army): JsonResponse
    {
        $user = $request->user();

        // Check ownership
        if ($army->owner_type !== 'player' || $army->owner_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'You do not own this army.',
            ], 403);
        }

        // Cannot move while in battle
        if ($army->status === Army::STATUS_IN_BATTLE) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot move army while in battle.',
            ], 400);
        }

        // Cannot move while already marching
        if ($army->status === Army::STATUS_MARCHING) {
            return response()->json([
                'success' => false,
                'message' => 'Army is already marching.',
            ], 400);
        }

        $validated = $request->validate([
            'location_type' => 'required|string|in:village,town',
            'location_id' => 'required|integer',
        ]);

        // Verify destination exists
        $destination = match ($validated['location_type']) {
            'village' => Village::find($validated['location_id']),
            'town' => Town::find($validated['location_id']),
            default => null,
        };

        if (! $destination) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid destination.',
            ], 400);
        }

        $this->armyService->moveArmy($army, $validated['location_type'], $validated['location_id']);

        return response()->json([
            'success' => true,
            'message' => "Army is now marching to {$destination->name}.",
        ]);
    }
}
