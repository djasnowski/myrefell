<?php

namespace App\Services;

use App\Models\BusinessEmployee;
use App\Models\BusinessProductionOrder;
use App\Models\BusinessType;
use App\Models\Item;
use App\Models\LocationNpc;
use App\Models\PlayerBusiness;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BusinessService
{
    /**
     * Maximum businesses a player can own.
     */
    public const MAX_BUSINESSES_PER_PLAYER = 3;

    /**
     * Get available business types at a location.
     */
    public function getAvailableBusinessTypes(User $user, string $locationType, int $locationId): Collection
    {
        return BusinessType::where('is_active', true)
            ->where('location_type', $locationType)
            ->get()
            ->filter(fn ($type) => $type->playerMeetsRequirements($user))
            ->map(fn ($type) => $this->formatBusinessType($type, $locationType, $locationId))
            ->values();
    }

    /**
     * Get all businesses owned by a player.
     */
    public function getPlayerBusinesses(User $user): Collection
    {
        return PlayerBusiness::where('user_id', $user->id)
            ->with(['businessType', 'activeEmployees'])
            ->get()
            ->map(fn ($business) => $this->formatBusiness($business));
    }

    /**
     * Get businesses at a specific location.
     */
    public function getBusinessesAtLocation(string $locationType, int $locationId): Collection
    {
        return PlayerBusiness::where('location_type', $locationType)
            ->where('location_id', $locationId)
            ->where('status', 'active')
            ->with(['businessType', 'owner'])
            ->get()
            ->map(fn ($business) => $this->formatBusiness($business));
    }

    /**
     * Get a single business with all details.
     */
    public function getBusinessDetails(PlayerBusiness $business): array
    {
        $business->load(['businessType', 'activeEmployees.user', 'activeEmployees.npc', 'inventory.item', 'productionOrders.item']);

        return [
            ...$this->formatBusiness($business),
            'employees' => $business->activeEmployees->map(fn ($e) => $this->formatEmployee($e)),
            'inventory' => $business->inventory->map(fn ($inv) => [
                'item_id' => $inv->item_id,
                'item_name' => $inv->item->name,
                'quantity' => $inv->quantity,
                'value' => $inv->item->base_value * $inv->quantity,
            ]),
            'production_orders' => $business->productionOrders
                ->whereIn('status', ['pending', 'in_progress'])
                ->map(fn ($order) => [
                    'id' => $order->id,
                    'item_id' => $order->item_id,
                    'item_name' => $order->item->name,
                    'quantity' => $order->quantity,
                    'quantity_completed' => $order->quantity_completed,
                    'status' => $order->status,
                    'completion_percentage' => $order->completion_percentage,
                ]),
            'recent_transactions' => $business->transactions()
                ->orderByDesc('created_at')
                ->limit(10)
                ->get()
                ->map(fn ($t) => [
                    'type' => $t->type,
                    'type_display' => $t->type_display,
                    'amount' => $t->amount,
                    'description' => $t->description,
                    'created_at' => $t->created_at->toISOString(),
                ]),
        ];
    }

    /**
     * Establish a new business.
     */
    public function establishBusiness(User $user, BusinessType $businessType, string $name, string $locationType, int $locationId): array
    {
        // Check ownership limit
        $currentCount = PlayerBusiness::where('user_id', $user->id)
            ->whereIn('status', ['active', 'suspended'])
            ->count();

        if ($currentCount >= self::MAX_BUSINESSES_PER_PLAYER) {
            return [
                'success' => false,
                'message' => 'You already own the maximum number of businesses ('.self::MAX_BUSINESSES_PER_PLAYER.').',
            ];
        }

        // Check player is at location
        if ($user->current_location_type !== $locationType || $user->current_location_id !== $locationId) {
            return [
                'success' => false,
                'message' => 'You must be at the location to establish a business.',
            ];
        }

        // Check business type is valid for location
        if ($businessType->location_type !== $locationType) {
            return [
                'success' => false,
                'message' => 'This type of business cannot be established here.',
            ];
        }

        // Check requirements
        if (! $businessType->playerMeetsRequirements($user)) {
            return [
                'success' => false,
                'message' => "You need {$businessType->primary_skill} level {$businessType->required_skill_level} to own this business.",
            ];
        }

        // Check gold
        if ($user->gold < $businessType->purchase_cost) {
            return [
                'success' => false,
                'message' => "You need {$businessType->purchase_cost} gold to establish this business. You have {$user->gold}.",
            ];
        }

        return DB::transaction(function () use ($user, $businessType, $name, $locationType, $locationId) {
            // Deduct gold
            $user->decrement('gold', $businessType->purchase_cost);

            // Create business
            $business = PlayerBusiness::create([
                'user_id' => $user->id,
                'business_type_id' => $businessType->id,
                'name' => $name,
                'location_type' => $locationType,
                'location_id' => $locationId,
                'status' => 'active',
                'treasury' => 0,
                'reputation' => 50,
                'established_at' => now(),
            ]);

            return [
                'success' => true,
                'message' => "You have established {$name}!",
                'business_id' => $business->id,
            ];
        });
    }

    /**
     * Close a business permanently.
     */
    public function closeBusiness(User $user, PlayerBusiness $business): array
    {
        if ($business->user_id !== $user->id) {
            return [
                'success' => false,
                'message' => 'This business does not belong to you.',
            ];
        }

        if ($business->status === 'closed') {
            return [
                'success' => false,
                'message' => 'This business is already closed.',
            ];
        }

        return DB::transaction(function () use ($user, $business) {
            // Transfer remaining treasury to player
            if ($business->treasury > 0) {
                $user->increment('gold', $business->treasury);
            }

            // Fire all employees
            $business->activeEmployees()->update(['status' => 'fired']);

            // Close business
            $business->update(['status' => 'closed', 'treasury' => 0]);

            return [
                'success' => true,
                'message' => "You have closed {$business->name}. Any remaining funds have been transferred to you.",
                'gold_returned' => $business->treasury,
            ];
        });
    }

    /**
     * Deposit gold into business treasury.
     */
    public function depositGold(User $user, PlayerBusiness $business, int $amount): array
    {
        if ($business->user_id !== $user->id) {
            return [
                'success' => false,
                'message' => 'This business does not belong to you.',
            ];
        }

        if (! $business->isActive()) {
            return [
                'success' => false,
                'message' => 'This business is not active.',
            ];
        }

        if ($amount <= 0) {
            return [
                'success' => false,
                'message' => 'Amount must be positive.',
            ];
        }

        if ($user->gold < $amount) {
            return [
                'success' => false,
                'message' => "You don't have enough gold. You have {$user->gold}.",
            ];
        }

        return DB::transaction(function () use ($user, $business, $amount) {
            $user->decrement('gold', $amount);
            $business->deposit($amount, 'Owner deposit', $user->id);

            return [
                'success' => true,
                'message' => "Deposited {$amount} gold into {$business->name}.",
                'new_treasury' => $business->fresh()->treasury,
            ];
        });
    }

    /**
     * Withdraw gold from business treasury.
     */
    public function withdrawGold(User $user, PlayerBusiness $business, int $amount): array
    {
        if ($business->user_id !== $user->id) {
            return [
                'success' => false,
                'message' => 'This business does not belong to you.',
            ];
        }

        if (! $business->isActive()) {
            return [
                'success' => false,
                'message' => 'This business is not active.',
            ];
        }

        if ($amount <= 0) {
            return [
                'success' => false,
                'message' => 'Amount must be positive.',
            ];
        }

        if ($business->treasury < $amount) {
            return [
                'success' => false,
                'message' => "Business treasury only has {$business->treasury} gold.",
            ];
        }

        return DB::transaction(function () use ($user, $business, $amount) {
            $business->withdraw($amount, 'withdrawal', 'Owner withdrawal', $user->id);
            $user->increment('gold', $amount);

            return [
                'success' => true,
                'message' => "Withdrew {$amount} gold from {$business->name}.",
                'new_treasury' => $business->fresh()->treasury,
            ];
        });
    }

    /**
     * Hire an NPC employee.
     */
    public function hireNpc(User $user, PlayerBusiness $business, LocationNpc $npc, int $dailyWage): array
    {
        if ($business->user_id !== $user->id) {
            return [
                'success' => false,
                'message' => 'This business does not belong to you.',
            ];
        }

        if (! $business->isActive()) {
            return [
                'success' => false,
                'message' => 'This business is not active.',
            ];
        }

        if (! $business->canHireMore()) {
            return [
                'success' => false,
                'message' => 'This business has reached its employee limit.',
            ];
        }

        // Check NPC is at same location
        if ($npc->location_type !== $business->location_type || $npc->location_id !== $business->location_id) {
            return [
                'success' => false,
                'message' => 'This person is not at the business location.',
            ];
        }

        // Check NPC is not already employed
        $alreadyEmployed = BusinessEmployee::where('location_npc_id', $npc->id)
            ->where('status', 'employed')
            ->exists();

        if ($alreadyEmployed) {
            return [
                'success' => false,
                'message' => "{$npc->name} is already employed elsewhere.",
            ];
        }

        // Calculate skill level based on NPC
        $skillLevel = rand(1, 50); // NPCs have variable skill levels

        $employee = BusinessEmployee::create([
            'player_business_id' => $business->id,
            'location_npc_id' => $npc->id,
            'role' => 'worker',
            'daily_wage' => $dailyWage,
            'skill_level' => $skillLevel,
            'status' => 'employed',
            'hired_at' => now(),
        ]);

        return [
            'success' => true,
            'message' => "Hired {$npc->name} for {$dailyWage} gold per day.",
            'employee_id' => $employee->id,
        ];
    }

    /**
     * Fire an employee.
     */
    public function fireEmployee(User $user, PlayerBusiness $business, BusinessEmployee $employee): array
    {
        if ($business->user_id !== $user->id) {
            return [
                'success' => false,
                'message' => 'This business does not belong to you.',
            ];
        }

        if ($employee->player_business_id !== $business->id) {
            return [
                'success' => false,
                'message' => 'This employee does not work at this business.',
            ];
        }

        if (! $employee->isEmployed()) {
            return [
                'success' => false,
                'message' => 'This employee is not currently employed.',
            ];
        }

        $employeeName = $employee->name;
        $employee->update(['status' => 'fired']);

        return [
            'success' => true,
            'message' => "You have fired {$employeeName}.",
        ];
    }

    /**
     * Add stock to business inventory from player.
     */
    public function addStock(User $user, PlayerBusiness $business, int $itemId, int $quantity): array
    {
        if ($business->user_id !== $user->id) {
            return [
                'success' => false,
                'message' => 'This business does not belong to you.',
            ];
        }

        if (! $business->isActive()) {
            return [
                'success' => false,
                'message' => 'This business is not active.',
            ];
        }

        // Check player has item
        $playerInv = $user->inventory()->where('item_id', $itemId)->first();
        if (! $playerInv || $playerInv->quantity < $quantity) {
            return [
                'success' => false,
                'message' => "You don't have enough of this item.",
            ];
        }

        $item = Item::find($itemId);

        return DB::transaction(function () use ($user, $business, $playerInv, $item, $quantity) {
            // Remove from player inventory
            $playerInv->decrement('quantity', $quantity);
            if ($playerInv->quantity <= 0) {
                $playerInv->delete();
            }

            // Add to business inventory
            $business->addInventory($item->id, $quantity);

            return [
                'success' => true,
                'message' => "Added {$quantity}x {$item->name} to {$business->name} inventory.",
            ];
        });
    }

    /**
     * Remove stock from business inventory to player.
     */
    public function removeStock(User $user, PlayerBusiness $business, int $itemId, int $quantity): array
    {
        if ($business->user_id !== $user->id) {
            return [
                'success' => false,
                'message' => 'This business does not belong to you.',
            ];
        }

        if (! $business->isActive()) {
            return [
                'success' => false,
                'message' => 'This business is not active.',
            ];
        }

        // Check business has item
        if (! $business->hasInventory($itemId, $quantity)) {
            return [
                'success' => false,
                'message' => "Business doesn't have enough of this item.",
            ];
        }

        $item = Item::find($itemId);

        return DB::transaction(function () use ($user, $business, $item, $quantity) {
            // Remove from business
            $business->removeInventory($item->id, $quantity);

            // Add to player inventory
            $playerInv = $user->inventory()->firstOrCreate(
                ['item_id' => $item->id],
                ['slot_number' => $this->findFreeSlot($user), 'quantity' => 0]
            );
            $playerInv->increment('quantity', $quantity);

            return [
                'success' => true,
                'message' => "Removed {$quantity}x {$item->name} from {$business->name} inventory.",
            ];
        });
    }

    /**
     * Find a free inventory slot for player.
     */
    protected function findFreeSlot(User $user): int
    {
        $usedSlots = $user->inventory()->pluck('slot_number')->toArray();
        for ($i = 0; $i < 28; $i++) {
            if (! in_array($i, $usedSlots)) {
                return $i;
            }
        }

        return 0;
    }

    /**
     * Process weekly upkeep for all businesses.
     * Called by scheduler.
     */
    public function processWeeklyUpkeep(): int
    {
        $processed = 0;

        PlayerBusiness::where('status', 'active')
            ->with('businessType')
            ->chunk(100, function ($businesses) use (&$processed) {
                foreach ($businesses as $business) {
                    $this->processBusinessUpkeep($business);
                    $processed++;
                }
            });

        return $processed;
    }

    /**
     * Process upkeep for a single business.
     */
    protected function processBusinessUpkeep(PlayerBusiness $business): void
    {
        $upkeep = $business->businessType->weekly_upkeep;

        if ($upkeep <= 0) {
            return;
        }

        if ($business->treasury >= $upkeep) {
            $business->withdraw($upkeep, 'upkeep', 'Weekly upkeep');
        } else {
            // Not enough funds - suspend business
            $business->update(['status' => 'suspended']);
        }

        $business->update(['last_upkeep_at' => now()]);
    }

    /**
     * Pay wages to all employees.
     * Called by scheduler.
     */
    public function payAllWages(): int
    {
        $paid = 0;

        BusinessEmployee::where('status', 'employed')
            ->with('business')
            ->chunk(100, function ($employees) use (&$paid) {
                foreach ($employees as $employee) {
                    if ($this->payEmployeeWage($employee)) {
                        $paid++;
                    }
                }
            });

        return $paid;
    }

    /**
     * Pay wage to a single employee.
     */
    protected function payEmployeeWage(BusinessEmployee $employee): bool
    {
        $business = $employee->business;

        if (! $business->isActive()) {
            return false;
        }

        $wage = $employee->daily_wage;

        if ($business->treasury >= $wage) {
            $business->withdraw($wage, 'wage', "Wage payment to {$employee->name}", $employee->user_id);
            $employee->update(['last_paid_at' => now()]);

            return true;
        }

        return false;
    }

    /**
     * Format a business type for display.
     */
    protected function formatBusinessType(BusinessType $type, string $locationType, int $locationId): array
    {
        return [
            'id' => $type->id,
            'name' => $type->name,
            'icon' => $type->icon,
            'description' => $type->description,
            'category' => $type->category,
            'category_display' => $type->category_display,
            'location_type' => $type->location_type,
            'purchase_cost' => $type->purchase_cost,
            'weekly_upkeep' => $type->weekly_upkeep,
            'max_employees' => $type->max_employees,
            'primary_skill' => $type->primary_skill,
            'required_skill_level' => $type->required_skill_level,
            'produces' => $type->produces,
            'existing_count' => $type->countAtLocation($locationType, $locationId),
        ];
    }

    /**
     * Format a business for display.
     */
    protected function formatBusiness(PlayerBusiness $business): array
    {
        return [
            'id' => $business->id,
            'name' => $business->name,
            'type_name' => $business->businessType->name,
            'type_icon' => $business->businessType->icon,
            'category' => $business->businessType->category,
            'location_type' => $business->location_type,
            'location_id' => $business->location_id,
            'location_name' => $business->location_name,
            'status' => $business->status,
            'treasury' => $business->treasury,
            'total_revenue' => $business->total_revenue,
            'total_expenses' => $business->total_expenses,
            'reputation' => $business->reputation,
            'employee_count' => $business->employee_count,
            'max_employees' => $business->businessType->max_employees,
            'weekly_upkeep' => $business->businessType->weekly_upkeep,
            'established_at' => $business->established_at->toISOString(),
            'owner_id' => $business->user_id,
            'owner_name' => $business->owner->name ?? 'Unknown',
        ];
    }

    /**
     * Format an employee for display.
     */
    protected function formatEmployee(BusinessEmployee $employee): array
    {
        return [
            'id' => $employee->id,
            'name' => $employee->name,
            'is_player' => $employee->isPlayer(),
            'role' => $employee->role,
            'daily_wage' => $employee->daily_wage,
            'skill_level' => $employee->skill_level,
            'efficiency' => $employee->efficiency,
            'hired_at' => $employee->hired_at->toISOString(),
            'last_paid_at' => $employee->last_paid_at?->toISOString(),
        ];
    }

    /**
     * Seed default business types.
     */
    public static function seedDefaultBusinessTypes(): void
    {
        $types = [
            // Production businesses
            [
                'name' => 'Smithy',
                'icon' => 'hammer',
                'description' => 'Forge weapons, armor, and tools from raw materials.',
                'category' => 'production',
                'location_type' => 'village',
                'purchase_cost' => 500,
                'weekly_upkeep' => 25,
                'max_employees' => 3,
                'primary_skill' => 'smithing',
                'required_skill_level' => 15,
                'produces' => ['weapons', 'armor', 'tools'],
            ],
            [
                'name' => 'Bakery',
                'icon' => 'croissant',
                'description' => 'Bake bread and pastries for the community.',
                'category' => 'production',
                'location_type' => 'village',
                'purchase_cost' => 300,
                'weekly_upkeep' => 15,
                'max_employees' => 2,
                'primary_skill' => 'cooking',
                'required_skill_level' => 10,
                'produces' => ['food'],
            ],
            [
                'name' => 'Carpentry Workshop',
                'icon' => 'axe',
                'description' => 'Craft wooden items, furniture, and building materials.',
                'category' => 'production',
                'location_type' => 'village',
                'purchase_cost' => 400,
                'weekly_upkeep' => 20,
                'max_employees' => 3,
                'primary_skill' => 'woodcutting',
                'required_skill_level' => 12,
                'produces' => ['furniture', 'tools'],
            ],
            // Service businesses
            [
                'name' => 'Inn',
                'icon' => 'bed',
                'description' => 'Provide lodging and meals to travelers.',
                'category' => 'service',
                'location_type' => 'village',
                'purchase_cost' => 600,
                'weekly_upkeep' => 30,
                'max_employees' => 4,
                'primary_skill' => 'cooking',
                'required_skill_level' => 8,
                'produces' => ['lodging', 'food'],
            ],
            [
                'name' => 'General Store',
                'icon' => 'store',
                'description' => 'Buy and sell various goods.',
                'category' => 'service',
                'location_type' => 'village',
                'purchase_cost' => 450,
                'weekly_upkeep' => 20,
                'max_employees' => 2,
                'primary_skill' => null,
                'required_skill_level' => 0,
                'produces' => null,
            ],
            // Extraction businesses
            [
                'name' => 'Small Mine',
                'icon' => 'pickaxe',
                'description' => 'Extract ore and minerals from the earth.',
                'category' => 'extraction',
                'location_type' => 'village',
                'purchase_cost' => 800,
                'weekly_upkeep' => 40,
                'max_employees' => 5,
                'primary_skill' => 'mining',
                'required_skill_level' => 20,
                'produces' => ['ore', 'minerals'],
            ],
            [
                'name' => 'Farm',
                'icon' => 'wheat',
                'description' => 'Grow crops and raise livestock.',
                'category' => 'extraction',
                'location_type' => 'village',
                'purchase_cost' => 350,
                'weekly_upkeep' => 15,
                'max_employees' => 4,
                'primary_skill' => 'foraging',
                'required_skill_level' => 8,
                'produces' => ['food', 'materials'],
            ],
            // Town businesses
            [
                'name' => 'Large Smithy',
                'icon' => 'hammer',
                'description' => 'A well-equipped forge for advanced metalwork.',
                'category' => 'production',
                'location_type' => 'town',
                'purchase_cost' => 1000,
                'weekly_upkeep' => 50,
                'max_employees' => 5,
                'primary_skill' => 'smithing',
                'required_skill_level' => 25,
                'produces' => ['weapons', 'armor', 'tools'],
            ],
            [
                'name' => 'Trading House',
                'icon' => 'building',
                'description' => 'A large establishment for wholesale trade.',
                'category' => 'service',
                'location_type' => 'town',
                'purchase_cost' => 1200,
                'weekly_upkeep' => 60,
                'max_employees' => 4,
                'primary_skill' => null,
                'required_skill_level' => 0,
                'produces' => null,
            ],
        ];

        foreach ($types as $typeData) {
            BusinessType::updateOrCreate(
                ['name' => $typeData['name'], 'location_type' => $typeData['location_type']],
                $typeData
            );
        }
    }
}
