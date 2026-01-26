<?php

namespace App\Http\Controllers;

use App\Models\Building;
use App\Models\BuildingType;
use App\Models\ConstructionProject;
use App\Models\PlayerInventory;
use App\Models\Town;
use App\Models\Village;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BuildingController extends Controller
{
    /**
     * Display building construction page for current location.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $locationType = $user->current_location_type;
        $locationId = $user->current_location_id;

        // Get location
        $location = match ($locationType) {
            'village' => Village::find($locationId),
            'town' => Town::find($locationId),
            default => null,
        };

        if (!$location) {
            return Inertia::render('Buildings/Index', [
                'location' => null,
                'buildings' => [],
                'projects' => [],
                'available_types' => [],
                'resources' => [],
                'can_build' => false,
            ]);
        }

        // Get existing buildings at this location
        $buildings = Building::atLocation($locationType, $locationId)
            ->with('buildingType')
            ->get()
            ->map(fn ($building) => $this->mapBuilding($building));

        // Get active construction projects
        $projects = ConstructionProject::whereHas('building', fn ($q) =>
            $q->where('location_type', $locationType)
              ->where('location_id', $locationId)
        )
            ->active()
            ->with(['building.buildingType', 'manager'])
            ->get()
            ->map(fn ($project) => $this->mapProject($project));

        // Get building types not yet built here
        $existingTypeIds = Building::atLocation($locationType, $locationId)
            ->pluck('building_type_id');

        $availableTypes = BuildingType::whereNotIn('id', $existingTypeIds)
            ->orderBy('name')
            ->get()
            ->map(fn ($type) => $this->mapBuildingType($type));

        // Get player's resources (items in inventory)
        $resources = $this->getPlayerResources($user);

        // Check if user can build (must be ruler or have permission)
        $canBuild = $this->canBuildAt($user, $locationType, $locationId, $location);

        return Inertia::render('Buildings/Index', [
            'location' => [
                'type' => $locationType,
                'id' => $locationId,
                'name' => $location->name,
            ],
            'buildings' => $buildings->toArray(),
            'projects' => $projects->toArray(),
            'available_types' => $availableTypes->toArray(),
            'resources' => $resources,
            'can_build' => $canBuild,
            'player' => [
                'gold' => $user->gold,
            ],
        ]);
    }

    /**
     * Start construction of a new building.
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'building_type_id' => 'required|integer|exists:building_types,id',
            'location_type' => 'required|in:village,town',
            'location_id' => 'required|integer',
        ]);

        // Get location
        $location = match ($validated['location_type']) {
            'village' => Village::find($validated['location_id']),
            'town' => Town::find($validated['location_id']),
            default => null,
        };

        if (!$location) {
            return response()->json([
                'success' => false,
                'message' => 'Location not found.',
            ], 404);
        }

        // Check permission
        if (!$this->canBuildAt($user, $validated['location_type'], $validated['location_id'], $location)) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to build here.',
            ], 403);
        }

        $buildingType = BuildingType::find($validated['building_type_id']);

        // Check if building type already exists at location
        $existing = Building::atLocation($validated['location_type'], $validated['location_id'])
            ->where('building_type_id', $buildingType->id)
            ->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'A ' . $buildingType->name . ' already exists at this location.',
            ], 422);
        }

        // Check resources
        $requirements = $buildingType->construction_requirements ?? [];
        $playerResources = $this->getPlayerResources($user);
        $missingResources = [];

        foreach ($requirements as $resource => $amount) {
            $hasAmount = $playerResources[$resource] ?? 0;
            if ($hasAmount < $amount) {
                $missingResources[$resource] = $amount - $hasAmount;
            }
        }

        if (!empty($missingResources)) {
            return response()->json([
                'success' => false,
                'message' => 'You lack the required resources to start construction.',
                'missing' => $missingResources,
            ], 422);
        }

        // Deduct resources from player inventory
        foreach ($requirements as $resource => $amount) {
            $this->deductResource($user, $resource, $amount);
        }

        // Create the building (planned status)
        $building = Building::create([
            'building_type_id' => $buildingType->id,
            'location_type' => $validated['location_type'],
            'location_id' => $validated['location_id'],
            'name' => $buildingType->name,
            'status' => Building::STATUS_UNDER_CONSTRUCTION,
            'condition' => 0,
            'construction_progress' => 0,
            'owner_id' => $user->id,
            'built_by_user_id' => $user->id,
            'construction_started_at' => now(),
        ]);

        // Create construction project
        ConstructionProject::create([
            'building_id' => $building->id,
            'project_type' => 'construction',
            'status' => 'in_progress',
            'progress' => 0,
            'labor_invested' => 0,
            'labor_required' => $buildingType->construction_labor,
            'materials_invested' => $requirements,
            'materials_required' => $requirements,
            'gold_invested' => 0,
            'gold_required' => 0,
            'managed_by_user_id' => $user->id,
            'started_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Construction of ' . $buildingType->name . ' has begun!',
            'building' => $this->mapBuilding($building->load('buildingType')),
        ]);
    }

    /**
     * Repair a damaged building.
     */
    public function repair(Request $request, Building $building): JsonResponse
    {
        $user = $request->user();

        // Check permission
        $location = $building->location;
        if (!$this->canBuildAt($user, $building->location_type, $building->location_id, $location)) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to repair buildings here.',
            ], 403);
        }

        // Check if building needs repair
        if (!$building->needsRepair()) {
            return response()->json([
                'success' => false,
                'message' => 'This building does not need repairs.',
            ], 422);
        }

        // Check if there's already an active repair project
        $existingProject = ConstructionProject::where('building_id', $building->id)
            ->where('project_type', 'repair')
            ->active()
            ->first();

        if ($existingProject) {
            return response()->json([
                'success' => false,
                'message' => 'Repairs are already in progress.',
            ], 422);
        }

        // Calculate repair costs (proportional to damage)
        $damagePercent = 100 - $building->condition;
        $buildingType = $building->buildingType;
        $baseRequirements = $buildingType->construction_requirements ?? [];
        $repairRequirements = [];

        foreach ($baseRequirements as $resource => $amount) {
            $repairRequirements[$resource] = (int) ceil($amount * ($damagePercent / 100) * 0.5);
        }

        // Check resources
        $playerResources = $this->getPlayerResources($user);
        $missingResources = [];

        foreach ($repairRequirements as $resource => $amount) {
            $hasAmount = $playerResources[$resource] ?? 0;
            if ($hasAmount < $amount) {
                $missingResources[$resource] = $amount - $hasAmount;
            }
        }

        if (!empty($missingResources)) {
            return response()->json([
                'success' => false,
                'message' => 'You lack the required resources for repairs.',
                'missing' => $missingResources,
            ], 422);
        }

        // Deduct resources
        foreach ($repairRequirements as $resource => $amount) {
            $this->deductResource($user, $resource, $amount);
        }

        // Create repair project
        $laborRequired = (int) ceil($buildingType->construction_labor * ($damagePercent / 100) * 0.5);

        ConstructionProject::create([
            'building_id' => $building->id,
            'project_type' => 'repair',
            'status' => 'in_progress',
            'progress' => 0,
            'labor_invested' => 0,
            'labor_required' => max(1, $laborRequired),
            'materials_invested' => $repairRequirements,
            'materials_required' => $repairRequirements,
            'gold_invested' => 0,
            'gold_required' => 0,
            'managed_by_user_id' => $user->id,
            'started_at' => now(),
        ]);

        // Update building status
        if ($building->status === Building::STATUS_DAMAGED) {
            $building->update(['status' => Building::STATUS_UNDER_CONSTRUCTION]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Repair work has begun on ' . $building->name . '!',
        ]);
    }

    /**
     * Cancel a construction project.
     */
    public function cancel(Request $request, ConstructionProject $project): JsonResponse
    {
        $user = $request->user();
        $building = $project->building;

        // Check permission
        $location = $building->location;
        if (!$this->canBuildAt($user, $building->location_type, $building->location_id, $location)) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to cancel this project.',
            ], 403);
        }

        // Check if project is cancellable
        if (!in_array($project->status, ['pending', 'in_progress'])) {
            return response()->json([
                'success' => false,
                'message' => 'This project cannot be cancelled.',
            ], 422);
        }

        // Return 50% of materials (simulating waste)
        $materialsInvested = $project->materials_invested ?? [];
        foreach ($materialsInvested as $resource => $amount) {
            $returnAmount = (int) floor($amount * 0.5);
            if ($returnAmount > 0) {
                $this->addResource($user, $resource, $returnAmount);
            }
        }

        // Update project
        $project->update([
            'status' => 'cancelled',
            'completed_at' => now(),
        ]);

        // If this was a new construction, delete the building
        if ($project->project_type === 'construction' && $building->construction_progress == 0) {
            $building->delete();
        } else {
            // For repairs, just mark back as damaged
            if ($project->project_type === 'repair') {
                $building->update(['status' => Building::STATUS_DAMAGED]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Construction project cancelled. Some materials have been salvaged.',
        ]);
    }

    /**
     * Check if user can build at a location.
     */
    private function canBuildAt($user, string $locationType, int $locationId, $location): bool
    {
        // Admin can always build
        if ($user->isAdmin()) {
            return true;
        }

        // Check if user is the ruler of the location
        if ($locationType === 'village' && $location) {
            return $location->leader_user_id === $user->id;
        }

        if ($locationType === 'town' && $location) {
            return $location->mayor_user_id === $user->id;
        }

        return false;
    }

    /**
     * Get player's resource counts from inventory.
     */
    private function getPlayerResources($user): array
    {
        $inventory = PlayerInventory::where('player_id', $user->id)
            ->with('item')
            ->get();

        $resources = [];
        foreach ($inventory as $slot) {
            if ($slot->item && $slot->item->type === 'resource') {
                $resourceName = strtolower($slot->item->name);
                $resources[$resourceName] = ($resources[$resourceName] ?? 0) + $slot->quantity;
            }
        }

        return $resources;
    }

    /**
     * Deduct a resource from player inventory.
     */
    private function deductResource($user, string $resourceName, int $amount): void
    {
        $remaining = $amount;

        $inventorySlots = PlayerInventory::where('player_id', $user->id)
            ->whereHas('item', fn ($q) => $q->whereRaw('LOWER(name) = ?', [strtolower($resourceName)]))
            ->where('quantity', '>', 0)
            ->get();

        foreach ($inventorySlots as $slot) {
            if ($remaining <= 0) {
                break;
            }

            $toDeduct = min($slot->quantity, $remaining);
            $slot->decrement('quantity', $toDeduct);
            $remaining -= $toDeduct;

            // Remove empty slots
            if ($slot->quantity <= 0) {
                $slot->delete();
            }
        }
    }

    /**
     * Add a resource to player inventory.
     */
    private function addResource($user, string $resourceName, int $amount): void
    {
        // Find the item
        $item = \App\Models\Item::whereRaw('LOWER(name) = ?', [strtolower($resourceName)])->first();
        if (!$item) {
            return;
        }

        // Find existing slot or create new one
        $slot = PlayerInventory::where('player_id', $user->id)
            ->where('item_id', $item->id)
            ->first();

        if ($slot) {
            $slot->increment('quantity', $amount);
        } else {
            PlayerInventory::create([
                'player_id' => $user->id,
                'item_id' => $item->id,
                'quantity' => $amount,
                'slot' => PlayerInventory::where('player_id', $user->id)->max('slot') + 1,
            ]);
        }
    }

    /**
     * Map a building to array format.
     */
    private function mapBuilding(Building $building): array
    {
        return [
            'id' => $building->id,
            'name' => $building->name,
            'type' => [
                'id' => $building->buildingType->id,
                'name' => $building->buildingType->name,
                'category' => $building->buildingType->category,
                'description' => $building->buildingType->description,
                'is_fortification' => $building->buildingType->is_fortification,
                'bonuses' => $building->buildingType->bonuses,
                'maintenance_cost' => $building->buildingType->maintenance_cost,
            ],
            'status' => $building->status,
            'condition' => $building->condition,
            'construction_progress' => $building->construction_progress,
            'needs_repair' => $building->needsRepair(),
            'is_operational' => $building->isOperational(),
            'completed_at' => $building->completed_at?->toISOString(),
        ];
    }

    /**
     * Map a construction project to array format.
     */
    private function mapProject(ConstructionProject $project): array
    {
        // Estimate days remaining based on progress and typical completion
        $daysRemaining = null;
        if ($project->progress > 0 && $project->labor_required > 0) {
            $laborPerDay = max(1, $project->labor_invested / max(1, now()->diffInDays($project->started_at)));
            $remainingLabor = $project->labor_required - $project->labor_invested;
            $daysRemaining = (int) ceil($remainingLabor / $laborPerDay);
        } elseif ($project->labor_required > 0) {
            // Assume 1 labor unit per day as default
            $daysRemaining = $project->labor_required - $project->labor_invested;
        }

        return [
            'id' => $project->id,
            'building' => [
                'id' => $project->building->id,
                'name' => $project->building->name,
                'type_name' => $project->building->buildingType->name,
            ],
            'project_type' => $project->project_type,
            'status' => $project->status,
            'progress' => $project->progress,
            'labor_invested' => $project->labor_invested,
            'labor_required' => $project->labor_required,
            'materials_invested' => $project->materials_invested,
            'materials_required' => $project->materials_required,
            'days_remaining' => $daysRemaining,
            'started_at' => $project->started_at?->toISOString(),
            'manager' => $project->manager ? [
                'id' => $project->manager->id,
                'username' => $project->manager->username,
            ] : null,
        ];
    }

    /**
     * Map a building type to array format.
     */
    private function mapBuildingType(BuildingType $type): array
    {
        return [
            'id' => $type->id,
            'name' => $type->name,
            'slug' => $type->slug,
            'description' => $type->description,
            'category' => $type->category,
            'construction_requirements' => $type->construction_requirements ?? [],
            'construction_days' => $type->construction_days,
            'construction_labor' => $type->construction_labor,
            'maintenance_cost' => $type->maintenance_cost,
            'capacity' => $type->capacity,
            'bonuses' => $type->bonuses ?? [],
            'is_fortification' => $type->is_fortification,
        ];
    }
}
