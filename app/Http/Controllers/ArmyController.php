<?php

namespace App\Http\Controllers;

use App\Models\Army;
use App\Models\ArmyUnit;
use App\Models\MercenaryCompany;
use App\Services\ArmyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ArmyController extends Controller
{
    const ARMY_CREATION_COST = 500;

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
        $currentLocation = [
            'type' => $user->location_type,
            'id' => $user->location_id,
            'name' => $user->location?->name ?? 'Unknown',
        ];

        // Get unit type info for recruiting
        $unitTypes = $this->getUnitTypeInfo();

        return Inertia::render('Warfare/Armies', [
            'active_armies' => $activeArmies->toArray(),
            'disbanded_armies' => $disbandedArmies->toArray(),
            'mercenary_companies' => $mercenaryCompanies->toArray(),
            'current_location' => $currentLocation,
            'army_creation_cost' => self::ARMY_CREATION_COST,
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
     * Store a newly created army.
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => 'required|string|max:100',
        ]);

        // Check if player has enough gold
        if ($user->gold < self::ARMY_CREATION_COST) {
            return response()->json([
                'success' => false,
                'message' => 'Not enough gold. You need ' . self::ARMY_CREATION_COST . 'g to raise an army.',
            ], 400);
        }

        // Deduct gold and create army
        $user->decrement('gold', self::ARMY_CREATION_COST);

        $army = $this->armyService->raiseArmy(
            name: $validated['name'],
            ownerType: 'player',
            ownerId: $user->id,
            locationType: $user->location_type,
            locationId: $user->location_id,
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
        if (!$company->is_available) {
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
}
