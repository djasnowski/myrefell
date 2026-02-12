<?php

namespace App\Services;

use App\Config\ConstructionConfig;
use App\Models\Barony;
use App\Models\DiseaseInfection;
use App\Models\GardenPlot;
use App\Models\HouseFurniture;
use App\Models\HousePortal;
use App\Models\HouseRoom;
use App\Models\HouseServant;
use App\Models\HouseStorage;
use App\Models\HouseTrophy;
use App\Models\Item;
use App\Models\Kingdom;
use App\Models\LocationActivityLog;
use App\Models\PlayerHouse;
use App\Models\PlayerSkill;
use App\Models\ReligionMember;
use App\Models\ReligiousAction;
use App\Models\Town;
use App\Models\User;
use App\Models\Village;
use Illuminate\Support\Facades\DB;

class HouseService
{
    public function __construct(
        protected InventoryService $inventoryService,
        protected BiomeService $biomeService,
        protected EnergyService $energyService,
        protected CookingService $cookingService,
        protected CraftingService $craftingService,
        protected DailyTaskService $dailyTaskService,
        protected DiseaseService $diseaseService
    ) {}

    /**
     * Resolve a house for a visitor (or owner). Returns null if access denied.
     */
    public function resolveHouseForVisitor(User $visitor, int $houseId): ?PlayerHouse
    {
        $house = PlayerHouse::find($houseId);

        if (! $house) {
            return null;
        }

        // Owner always has access
        if ($house->player_id === $visitor->id) {
            return $house;
        }

        // Check cache for approved entry
        $status = \Illuminate\Support\Facades\Cache::get("house_entry:{$house->player_id}:{$visitor->id}");

        if ($status === 'approved') {
            return $house;
        }

        // If house has no parlour, visitors can access freely
        $hasParlour = HouseRoom::where('player_house_id', $house->id)
            ->where('room_type', 'parlour')
            ->exists();

        if (! $hasParlour) {
            return $house;
        }

        return null;
    }

    /**
     * Check if a player can purchase a house.
     */
    public function canPurchaseHouse(User $user): array
    {
        if (PlayerHouse::where('player_id', $user->id)->exists()) {
            return ['can_purchase' => false, 'reason' => 'You already own a house.'];
        }

        $tier = ConstructionConfig::HOUSE_TIERS['cottage'];

        if ($user->title_tier < $tier['title_level']) {
            return ['can_purchase' => false, 'reason' => 'You need a higher title to purchase a house. Earn at least a Freeman title.'];
        }

        if ($user->gold < $tier['cost']) {
            return ['can_purchase' => false, 'reason' => 'Not enough gold. You need '.number_format($tier['cost']).' gold.'];
        }

        if (! $user->current_location_type || ! $user->current_location_id) {
            return ['can_purchase' => false, 'reason' => 'You must be at a location to purchase a house.'];
        }

        return ['can_purchase' => true, 'reason' => null];
    }

    /**
     * Purchase a house for the player.
     */
    public function purchaseHouse(User $user): array
    {
        $check = $this->canPurchaseHouse($user);
        if (! $check['can_purchase']) {
            return ['success' => false, 'message' => $check['reason']];
        }

        $tier = ConstructionConfig::HOUSE_TIERS['cottage'];

        return DB::transaction(function () use ($user, $tier) {
            $user->gold -= $tier['cost'];
            $user->save();

            $house = PlayerHouse::create([
                'player_id' => $user->id,
                'name' => 'My House',
                'tier' => 'cottage',
                'condition' => 100,
                'upkeep_due_at' => now()->addDays(7),
                'kingdom_id' => $user->current_kingdom_id,
                'location_type' => $user->current_location_type,
                'location_id' => $user->current_location_id,
            ]);

            return [
                'success' => true,
                'message' => 'You purchased a cottage for '.number_format($tier['cost']).' gold!',
                'house' => $house,
            ];
        });
    }

    /**
     * Get the player's house with rooms and furniture.
     */
    public function getHouse(User $user): ?PlayerHouse
    {
        return PlayerHouse::where('player_id', $user->id)
            ->with(['rooms.furniture', 'storage.item', 'kingdom'])
            ->first();
    }

    /**
     * Build a room in the player's house.
     */
    public function buildRoom(User $user, string $roomType, int $gridX, int $gridY): array
    {
        $house = PlayerHouse::where('player_id', $user->id)->first();
        if (! $house) {
            return ['success' => false, 'message' => 'You do not own a house.'];
        }

        $roomConfig = ConstructionConfig::ROOMS[$roomType] ?? null;
        if (! $roomConfig) {
            return ['success' => false, 'message' => 'Unknown room type.'];
        }

        $constructionLevel = $user->skills->where('skill_name', 'construction')->first()?->level ?? 1;
        if ($constructionLevel < $roomConfig['level']) {
            return ['success' => false, 'message' => 'You need Construction level '.$roomConfig['level'].' to build a '.$roomConfig['name'].'.'];
        }

        $gridSize = $house->getGridSize();
        if ($gridX < 0 || $gridX >= $gridSize || $gridY < 0 || $gridY >= $gridSize) {
            return ['success' => false, 'message' => 'Invalid grid position.'];
        }

        if (HouseRoom::where('player_house_id', $house->id)->where('grid_x', $gridX)->where('grid_y', $gridY)->exists()) {
            return ['success' => false, 'message' => 'A room already exists at this position.'];
        }

        if ($house->rooms()->count() >= $house->getMaxRooms()) {
            return ['success' => false, 'message' => 'Maximum rooms reached for your house tier.'];
        }

        if ($user->gold < $roomConfig['cost']) {
            return ['success' => false, 'message' => 'Not enough gold. You need '.number_format($roomConfig['cost']).' gold.'];
        }

        return DB::transaction(function () use ($user, $house, $roomType, $roomConfig, $gridX, $gridY) {
            $user->gold -= $roomConfig['cost'];
            $user->save();

            $room = HouseRoom::create([
                'player_house_id' => $house->id,
                'room_type' => $roomType,
                'grid_x' => $gridX,
                'grid_y' => $gridY,
            ]);

            return [
                'success' => true,
                'message' => 'Built a '.$roomConfig['name'].' for '.number_format($roomConfig['cost']).' gold!',
                'room' => $room,
            ];
        });
    }

    /**
     * Build furniture at a hotspot in a room.
     */
    public function buildFurniture(User $user, int $roomId, string $hotspotSlug, string $furnitureKey): array
    {
        $house = PlayerHouse::where('player_id', $user->id)->first();
        if (! $house) {
            return ['success' => false, 'message' => 'You do not own a house.'];
        }

        $room = HouseRoom::where('id', $roomId)->where('player_house_id', $house->id)->first();
        if (! $room) {
            return ['success' => false, 'message' => 'Room not found.'];
        }

        $roomConfig = ConstructionConfig::ROOMS[$room->room_type] ?? null;
        if (! $roomConfig) {
            return ['success' => false, 'message' => 'Invalid room type.'];
        }

        $hotspot = $roomConfig['hotspots'][$hotspotSlug] ?? null;
        if (! $hotspot) {
            return ['success' => false, 'message' => 'Invalid hotspot.'];
        }

        $furnitureConfig = $hotspot['options'][$furnitureKey] ?? null;
        if (! $furnitureConfig) {
            return ['success' => false, 'message' => 'Invalid furniture option.'];
        }

        $constructionLevel = $user->skills->where('skill_name', 'construction')->first()?->level ?? 1;
        if ($constructionLevel < $furnitureConfig['level']) {
            return ['success' => false, 'message' => 'You need Construction level '.$furnitureConfig['level'].' to build '.$furnitureConfig['name'].'.'];
        }

        // Check materials
        foreach ($furnitureConfig['materials'] as $materialName => $qty) {
            $item = Item::where('name', $materialName)->first();
            if (! $item || ! $this->inventoryService->hasItem($user, $item, $qty)) {
                $have = $item ? $this->inventoryService->countItem($user, $item) : 0;

                return ['success' => false, 'message' => 'Not enough '.$materialName.'. Need '.$qty.', have '.$have.'.'];
            }
        }

        return DB::transaction(function () use ($user, $room, $hotspotSlug, $furnitureKey, $furnitureConfig) {
            // Remove old furniture at this hotspot if any
            HouseFurniture::where('house_room_id', $room->id)
                ->where('hotspot_slug', $hotspotSlug)
                ->delete();

            // Consume materials
            foreach ($furnitureConfig['materials'] as $materialName => $qty) {
                $item = Item::where('name', $materialName)->first();
                $this->inventoryService->removeItem($user, $item, $qty);
            }

            // Build furniture
            HouseFurniture::create([
                'house_room_id' => $room->id,
                'hotspot_slug' => $hotspotSlug,
                'furniture_key' => $furnitureKey,
            ]);

            // Award Construction XP
            $skill = $user->skills->where('skill_name', 'construction')->first();
            $levelsGained = $skill->addXp($furnitureConfig['xp']);

            return [
                'success' => true,
                'message' => 'Built '.$furnitureConfig['name'].'!',
                'xp_awarded' => $furnitureConfig['xp'],
                'leveled_up' => $levelsGained > 0,
                'new_level' => $skill->level,
            ];
        });
    }

    /**
     * Demolish furniture at a hotspot (returns 50% materials).
     */
    public function demolishFurniture(User $user, int $roomId, string $hotspotSlug): array
    {
        $house = PlayerHouse::where('player_id', $user->id)->first();
        if (! $house) {
            return ['success' => false, 'message' => 'You do not own a house.'];
        }

        $room = HouseRoom::where('id', $roomId)->where('player_house_id', $house->id)->first();
        if (! $room) {
            return ['success' => false, 'message' => 'Room not found.'];
        }

        $furniture = HouseFurniture::where('house_room_id', $room->id)
            ->where('hotspot_slug', $hotspotSlug)
            ->first();

        if (! $furniture) {
            return ['success' => false, 'message' => 'No furniture at this hotspot.'];
        }

        $roomConfig = ConstructionConfig::ROOMS[$room->room_type] ?? null;
        $hotspot = $roomConfig['hotspots'][$hotspotSlug] ?? null;
        $furnitureConfig = $hotspot['options'][$furniture->furniture_key] ?? null;

        return DB::transaction(function () use ($user, $furniture, $furnitureConfig) {
            $returned = [];

            // Return 50% materials
            if ($furnitureConfig && isset($furnitureConfig['materials'])) {
                foreach ($furnitureConfig['materials'] as $materialName => $qty) {
                    $returnQty = (int) floor($qty / 2);
                    if ($returnQty > 0) {
                        $item = Item::where('name', $materialName)->first();
                        if ($item) {
                            $this->inventoryService->addItem($user, $item, $returnQty);
                            $returned[] = $returnQty.' '.$materialName;
                        }
                    }
                }
            }

            $furniture->delete();

            $message = 'Furniture demolished.';
            if (! empty($returned)) {
                $message .= ' Recovered: '.implode(', ', $returned).'.';
            }

            return ['success' => true, 'message' => $message];
        });
    }

    /**
     * Demolish an entire room, returning 50% of gold cost and 50% of furniture materials.
     */
    public function demolishRoom(User $user, int $roomId): array
    {
        $house = PlayerHouse::where('player_id', $user->id)->first();
        if (! $house) {
            return ['success' => false, 'message' => 'You do not own a house.'];
        }

        $room = HouseRoom::where('id', $roomId)->where('player_house_id', $house->id)->first();
        if (! $room) {
            return ['success' => false, 'message' => 'Room not found.'];
        }

        // Dependency checks
        if ($room->room_type === 'servant_quarters' && HouseServant::where('player_house_id', $house->id)->exists()) {
            return ['success' => false, 'message' => 'Dismiss your servant before demolishing the Servant Quarters.'];
        }

        if ($room->room_type === 'trophy_hall' && HouseTrophy::where('player_house_id', $house->id)->exists()) {
            return ['success' => false, 'message' => 'Remove all trophies before demolishing the Trophy Hall.'];
        }

        if ($room->room_type === 'garden' && GardenPlot::where('player_house_id', $house->id)->where('status', '!=', 'empty')->exists()) {
            return ['success' => false, 'message' => 'Clear all garden plots before demolishing the Garden.'];
        }

        if ($room->room_type === 'portal_chamber' && HousePortal::where('player_house_id', $house->id)->exists()) {
            return ['success' => false, 'message' => 'Remove all portal destinations before demolishing the Portal Chamber.'];
        }

        $roomConfig = ConstructionConfig::ROOMS[$room->room_type] ?? null;
        $goldReturned = $roomConfig ? (int) floor($roomConfig['cost'] / 2) : 0;

        return DB::transaction(function () use ($user, $house, $room, $roomConfig, $goldReturned) {
            $returned = [];

            // Return 50% of each furniture's materials
            $furniture = HouseFurniture::where('house_room_id', $room->id)->get();
            foreach ($furniture as $piece) {
                $hotspot = $roomConfig['hotspots'][$piece->hotspot_slug] ?? null;
                $furnitureConfig = $hotspot['options'][$piece->furniture_key] ?? null;

                if ($furnitureConfig && isset($furnitureConfig['materials'])) {
                    foreach ($furnitureConfig['materials'] as $materialName => $qty) {
                        $returnQty = (int) floor($qty / 2);
                        if ($returnQty > 0) {
                            $item = Item::where('name', $materialName)->first();
                            if ($item) {
                                $this->inventoryService->addItem($user, $item, $returnQty);
                                $returned[] = $returnQty.' '.$materialName;
                            }
                        }
                    }
                }
            }

            // Delete all furniture
            HouseFurniture::where('house_room_id', $room->id)->delete();

            // Delete any empty garden plots associated with this room
            if ($room->room_type === 'garden') {
                GardenPlot::where('player_house_id', $house->id)->where('status', 'empty')->delete();
            }

            // Return 50% of room gold cost
            $user->gold += $goldReturned;
            $user->save();

            // Delete the room
            $room->delete();

            $message = 'Room demolished. '.number_format($goldReturned).' gold returned.';
            if (! empty($returned)) {
                $message .= ' Materials recovered: '.implode(', ', $returned).'.';
            }

            return ['success' => true, 'message' => $message];
        });
    }

    /**
     * Check if a player can upgrade their house to the next tier.
     */
    public function canUpgradeHouse(User $user, string $targetTier): array
    {
        $house = PlayerHouse::where('player_id', $user->id)->first();
        if (! $house) {
            return ['can_upgrade' => false, 'reason' => 'You do not own a house.'];
        }

        $targetConfig = ConstructionConfig::HOUSE_TIERS[$targetTier] ?? null;
        if (! $targetConfig) {
            return ['can_upgrade' => false, 'reason' => 'Unknown house tier.'];
        }

        // Ensure the target is the next tier up
        $tierOrder = array_keys(ConstructionConfig::HOUSE_TIERS);
        $currentIndex = array_search($house->tier, $tierOrder);
        $targetIndex = array_search($targetTier, $tierOrder);

        if ($targetIndex !== $currentIndex + 1) {
            return ['can_upgrade' => false, 'reason' => 'You can only upgrade to the next tier.'];
        }

        // Check construction level
        $constructionLevel = $user->skills->where('skill_name', 'construction')->first()?->level ?? 1;
        if ($constructionLevel < $targetConfig['level']) {
            return ['can_upgrade' => false, 'reason' => 'You need Construction level '.$targetConfig['level'].' to upgrade to a '.$targetConfig['name'].'.'];
        }

        // Check title tier
        if ($user->title_tier < $targetConfig['title_level']) {
            return ['can_upgrade' => false, 'reason' => 'You need a higher title to upgrade your house.'];
        }

        // Cost is the difference between tiers
        $currentConfig = ConstructionConfig::HOUSE_TIERS[$house->tier];
        $upgradeCost = $targetConfig['cost'] - $currentConfig['cost'];

        if ($user->gold < $upgradeCost) {
            return ['can_upgrade' => false, 'reason' => 'Not enough gold. You need '.number_format($upgradeCost).' gold to upgrade.'];
        }

        return ['can_upgrade' => true, 'reason' => null, 'cost' => $upgradeCost];
    }

    /**
     * Upgrade a player's house to the next tier.
     */
    public function upgradeHouse(User $user, string $targetTier): array
    {
        $check = $this->canUpgradeHouse($user, $targetTier);
        if (! $check['can_upgrade']) {
            return ['success' => false, 'message' => $check['reason']];
        }

        $targetConfig = ConstructionConfig::HOUSE_TIERS[$targetTier];

        return DB::transaction(function () use ($user, $targetTier, $targetConfig, $check) {
            $user->gold -= $check['cost'];
            $user->save();

            $house = PlayerHouse::where('player_id', $user->id)->first();
            $house->tier = $targetTier;
            $house->save();

            return [
                'success' => true,
                'message' => 'Your house has been upgraded to a '.$targetConfig['name'].'!',
                'tier' => $targetTier,
            ];
        });
    }

    /**
     * Deposit an item from inventory into house storage.
     */
    public function depositItem(User $user, string $itemName, int $quantity): array
    {
        $house = PlayerHouse::where('player_id', $user->id)->first();
        if (! $house) {
            return ['success' => false, 'message' => 'You do not own a house.'];
        }

        if ($house->areStorageDisabled()) {
            return ['success' => false, 'message' => 'House storage is disabled due to poor condition. Repair your house first.'];
        }

        if ($quantity < 1) {
            return ['success' => false, 'message' => 'Invalid quantity.'];
        }

        $item = Item::where('name', $itemName)->first();
        if (! $item) {
            return ['success' => false, 'message' => 'Item not found.'];
        }

        if (! $this->inventoryService->hasItem($user, $item, $quantity)) {
            return ['success' => false, 'message' => 'You do not have enough '.$itemName.'.'];
        }

        // Check if this would use a new slot (only matters if item isn't already stored)
        $existsInStorage = HouseStorage::where('player_house_id', $house->id)
            ->where('item_id', $item->id)
            ->exists();

        if (! $existsInStorage) {
            $storageUsed = $house->getStorageUsed();
            $storageCapacity = $house->getStorageCapacity();
            if ($storageUsed >= $storageCapacity) {
                return ['success' => false, 'message' => 'No storage slots available. You have used all '.$storageCapacity.' slots.'];
            }
        }

        return DB::transaction(function () use ($user, $house, $item, $quantity, $itemName) {
            $this->inventoryService->removeItem($user, $item, $quantity);

            $storage = HouseStorage::where('player_house_id', $house->id)
                ->where('item_id', $item->id)
                ->first();

            if ($storage) {
                $storage->increment('quantity', $quantity);
            } else {
                $slotNumber = $this->findEmptyStorageSlot($house);
                $storage = HouseStorage::create([
                    'player_house_id' => $house->id,
                    'item_id' => $item->id,
                    'slot_number' => $slotNumber,
                    'quantity' => $quantity,
                ]);
            }

            return ['success' => true, 'message' => 'Stored '.$quantity.' '.$itemName.'.'];
        });
    }

    /**
     * Withdraw an item from house storage to inventory.
     */
    public function withdrawItem(User $user, string $itemName, int $quantity): array
    {
        $house = PlayerHouse::where('player_id', $user->id)->first();
        if (! $house) {
            return ['success' => false, 'message' => 'You do not own a house.'];
        }

        if ($house->areStorageDisabled()) {
            return ['success' => false, 'message' => 'House storage is disabled due to poor condition. Repair your house first.'];
        }

        if ($quantity < 1) {
            return ['success' => false, 'message' => 'Invalid quantity.'];
        }

        $item = Item::where('name', $itemName)->first();
        if (! $item) {
            return ['success' => false, 'message' => 'Item not found.'];
        }

        $storage = HouseStorage::where('player_house_id', $house->id)
            ->where('item_id', $item->id)
            ->first();

        if (! $storage || $storage->quantity < $quantity) {
            return ['success' => false, 'message' => 'Not enough '.$itemName.' in storage.'];
        }

        // Calculate how many items can actually fit in inventory
        $freeSlots = $this->inventoryService->freeSlots($user);
        if ($item->stackable) {
            // Account for space in existing partial stacks
            $partialStackRoom = $user->inventory()
                ->where('item_id', $item->id)
                ->where('quantity', '<', $item->max_stack)
                ->get()
                ->sum(fn ($slot) => $item->max_stack - $slot->quantity);

            $canFit = $partialStackRoom + ($freeSlots * $item->max_stack);
        } else {
            $canFit = $freeSlots;
        }

        $actualQuantity = min($quantity, $canFit);

        if ($actualQuantity <= 0) {
            return ['success' => false, 'message' => 'Not enough inventory space.'];
        }

        return DB::transaction(function () use ($user, $item, $storage, $actualQuantity, $quantity, $itemName) {
            $this->inventoryService->addItem($user, $item, $actualQuantity);

            $storage->decrement('quantity', $actualQuantity);
            if ($storage->quantity <= 0) {
                $storage->delete();
            }

            if ($actualQuantity < $quantity) {
                return ['success' => true, 'message' => 'Withdrew '.$actualQuantity.' '.$itemName.' (inventory full, '.($quantity - $actualQuantity).' left in storage).'];
            }

            return ['success' => true, 'message' => 'Withdrew '.$actualQuantity.' '.$itemName.'.'];
        });
    }

    /**
     * Get portal data for a player's house.
     *
     * @return array<int, array{slot: int, furniture_key: string|null, destination: array|null}>
     */
    public function getPortals(User $user, ?PlayerHouse $house = null): array
    {
        if (! $house) {
            $house = PlayerHouse::where('player_id', $user->id)
                ->with(['rooms.furniture', 'portals'])
                ->first();
        } else {
            $house->load(['rooms.furniture', 'portals']);
        }

        if (! $house) {
            return [];
        }

        $portalRoom = $house->rooms->where('room_type', 'portal_chamber')->first();
        $portals = [];

        for ($slot = 1; $slot <= 3; $slot++) {
            $hotspotSlug = 'portal_'.$slot;
            $furniture = $portalRoom?->furniture->where('hotspot_slug', $hotspotSlug)->first();
            $portalRecord = $house->portals->where('portal_slot', $slot)->first();

            $portals[] = [
                'slot' => $slot,
                'furniture_key' => $furniture?->furniture_key,
                'furniture_name' => $furniture ? (ConstructionConfig::ROOMS['portal_chamber']['hotspots'][$hotspotSlug]['options'][$furniture->furniture_key]['name'] ?? null) : null,
                'destination' => $portalRecord ? [
                    'type' => $portalRecord->destination_type,
                    'id' => $portalRecord->destination_id,
                    'name' => $portalRecord->destination_name,
                ] : null,
                'set_cost' => $furniture ? (ConstructionConfig::PORTAL_CONFIG[$furniture->furniture_key]['set_cost'] ?? 5000) : null,
            ];
        }

        return $portals;
    }

    /**
     * Set a portal's destination.
     */
    public function setPortalDestination(User $user, int $slot, string $destType, int $destId): array
    {
        if ($slot < 1 || $slot > 3) {
            return ['success' => false, 'message' => 'Invalid portal slot.'];
        }

        $house = PlayerHouse::where('player_id', $user->id)
            ->with('rooms.furniture')
            ->first();

        if (! $house) {
            return ['success' => false, 'message' => 'You do not own a house.'];
        }

        $portalRoom = $house->rooms->where('room_type', 'portal_chamber')->first();
        if (! $portalRoom) {
            return ['success' => false, 'message' => 'You do not have a Portal Chamber.'];
        }

        $hotspotSlug = 'portal_'.$slot;
        $furniture = $portalRoom->furniture->where('hotspot_slug', $hotspotSlug)->first();
        if (! $furniture) {
            return ['success' => false, 'message' => 'No portal built at slot '.$slot.'.'];
        }

        // Validate destination
        $destination = $this->resolveDestination($destType, $destId);
        if (! $destination) {
            return ['success' => false, 'message' => 'Invalid destination.'];
        }

        // Check gold cost
        $setCost = ConstructionConfig::PORTAL_CONFIG[$furniture->furniture_key]['set_cost'] ?? 5000;
        if ($user->gold < $setCost) {
            return ['success' => false, 'message' => 'Not enough gold. Setting a portal costs '.number_format($setCost).' gold.'];
        }

        return DB::transaction(function () use ($user, $house, $slot, $destType, $destId, $destination, $setCost) {
            $user->gold -= $setCost;
            $user->save();

            HousePortal::updateOrCreate(
                ['player_house_id' => $house->id, 'portal_slot' => $slot],
                [
                    'destination_type' => $destType,
                    'destination_id' => $destId,
                    'destination_name' => $destination->name,
                ],
            );

            return [
                'success' => true,
                'message' => 'Portal '.$slot.' set to '.$destination->name.' for '.number_format($setCost).' gold.',
            ];
        });
    }

    /**
     * Teleport via a configured portal.
     */
    public function teleportFromPortal(User $user, int $slot, ?PlayerHouse $house = null): array
    {
        if ($slot < 1 || $slot > 3) {
            return ['success' => false, 'message' => 'Invalid portal slot.'];
        }

        if (! $house) {
            $houseForCheck = PlayerHouse::where('player_id', $user->id)->first();
            if ($houseForCheck && $houseForCheck->arePortalsDisabled()) {
                return ['success' => false, 'message' => 'Portals are disabled due to poor house condition. Repair your house first.'];
            }
        } else {
            if ($house->arePortalsDisabled()) {
                return ['success' => false, 'message' => 'Portals are disabled due to poor house condition.'];
            }
        }

        if ($user->isTraveling()) {
            return ['success' => false, 'message' => 'You cannot teleport while traveling.'];
        }

        if ($user->isInInfirmary()) {
            return ['success' => false, 'message' => 'You cannot teleport while in the infirmary.'];
        }

        if (! $house) {
            $house = PlayerHouse::where('player_id', $user->id)
                ->with(['rooms.furniture', 'portals'])
                ->first();
        } else {
            $house->load(['rooms.furniture', 'portals']);
        }

        if (! $house) {
            return ['success' => false, 'message' => 'You do not own a house.'];
        }

        $portalRoom = $house->rooms->where('room_type', 'portal_chamber')->first();
        if (! $portalRoom) {
            return ['success' => false, 'message' => 'You do not have a Portal Chamber.'];
        }

        $hotspotSlug = 'portal_'.$slot;
        $furniture = $portalRoom->furniture->where('hotspot_slug', $hotspotSlug)->first();
        if (! $furniture) {
            return ['success' => false, 'message' => 'No portal built at slot '.$slot.'.'];
        }

        $portal = $house->portals->where('portal_slot', $slot)->first();
        if (! $portal) {
            return ['success' => false, 'message' => 'Portal '.$slot.' has no destination configured.'];
        }

        // Check energy
        $energyCost = 5;
        if (! $this->energyService->hasEnergy($user, $energyCost)) {
            return ['success' => false, 'message' => 'Not enough energy. Teleporting costs '.$energyCost.' energy.'];
        }

        // Verify destination still exists
        $destination = $this->resolveDestination($portal->destination_type, $portal->destination_id);
        if (! $destination) {
            return ['success' => false, 'message' => 'The portal destination no longer exists.'];
        }

        return DB::transaction(function () use ($user, $portal, $destination, $energyCost) {
            $this->energyService->consumeEnergy($user, $energyCost);

            $user->current_location_type = $portal->destination_type;
            $user->current_location_id = $portal->destination_id;
            $user->save();

            $this->biomeService->updatePlayerKingdom($user);

            return [
                'success' => true,
                'message' => 'Teleported to '.$destination->name.'!',
            ];
        });
    }

    /**
     * Resolve a destination model by type and ID.
     */
    protected function resolveDestination(string $type, int $id): ?object
    {
        return match ($type) {
            'village', 'hamlet' => Village::find($id),
            'barony' => Barony::find($id),
            'town' => Town::find($id),
            'kingdom' => Kingdom::find($id),
            default => null,
        };
    }

    /**
     * Pay house upkeep to extend the due date by 7 days.
     */
    public function payUpkeep(User $user): array
    {
        $house = PlayerHouse::where('player_id', $user->id)->first();
        if (! $house) {
            return ['success' => false, 'message' => 'You do not own a house.'];
        }

        $cost = $house->getUpkeepCost();

        if ($user->gold < $cost) {
            return ['success' => false, 'message' => 'Not enough gold. Upkeep costs '.number_format($cost).' gold.'];
        }

        return DB::transaction(function () use ($user, $house, $cost) {
            $user->gold -= $cost;
            $user->save();

            $house->upkeep_due_at = now()->addDays(7);
            $house->save();

            return [
                'success' => true,
                'message' => 'Upkeep paid! Next payment due in 7 days.',
            ];
        });
    }

    /**
     * Repair a house back to full condition.
     */
    public function repairHouse(User $user): array
    {
        $house = PlayerHouse::where('player_id', $user->id)->first();
        if (! $house) {
            return ['success' => false, 'message' => 'You do not own a house.'];
        }

        if ($house->condition >= 100) {
            return ['success' => false, 'message' => 'Your house is already in perfect condition.'];
        }

        $cost = $house->getRepairCost();

        if ($user->gold < $cost) {
            return ['success' => false, 'message' => 'Not enough gold. Repairs cost '.number_format($cost).' gold.'];
        }

        return DB::transaction(function () use ($user, $house, $cost) {
            $user->gold -= $cost;
            $user->save();

            $house->condition = 100;
            $house->save();

            return [
                'success' => true,
                'message' => 'House repaired to full condition for '.number_format($cost).' gold!',
            ];
        });
    }

    /**
     * Process upkeep degradation for all overdue houses.
     *
     * @return array{processed: int, degraded: int, abandoned: int}
     */
    public function processUpkeepDegradation(): array
    {
        $houses = PlayerHouse::where('upkeep_due_at', '<', now())
            ->where('condition', '>', 0)
            ->get();

        $degraded = 0;
        $abandoned = 0;

        foreach ($houses as $house) {
            $newCondition = max(0, $house->condition - 10);
            $house->condition = $newCondition;
            $house->save();
            $degraded++;

            if ($newCondition <= 0) {
                $house->delete();
                $abandoned++;
            }
        }

        return [
            'processed' => $houses->count(),
            'degraded' => $degraded,
            'abandoned' => $abandoned,
        ];
    }

    /**
     * Get all available destinations for portal configuration.
     *
     * @return array<array{type: string, id: int, name: string}>
     */
    public function getAvailableDestinations(): array
    {
        $destinations = [];

        foreach (Village::orderBy('name')->get(['id', 'name']) as $v) {
            $destinations[] = ['type' => 'village', 'id' => $v->id, 'name' => $v->name];
        }

        foreach (Barony::orderBy('name')->get(['id', 'name']) as $b) {
            $destinations[] = ['type' => 'barony', 'id' => $b->id, 'name' => $b->name];
        }

        foreach (Town::orderBy('name')->get(['id', 'name']) as $t) {
            $destinations[] = ['type' => 'town', 'id' => $t->id, 'name' => $t->name];
        }

        foreach (Kingdom::orderBy('name')->get(['id', 'name']) as $k) {
            $destinations[] = ['type' => 'kingdom', 'id' => $k->id, 'name' => $k->name];
        }

        return $destinations;
    }

    /**
     * Get kitchen cooking data for a player's house.
     */
    public function getKitchenData(User $user, ?PlayerHouse $house = null): ?array
    {
        if (! $house) {
            $house = PlayerHouse::where('player_id', $user->id)
                ->with('rooms.furniture')
                ->first();
        } else {
            $house->load('rooms.furniture');
        }

        if (! $house) {
            return null;
        }

        $kitchenRoom = $house->rooms->where('room_type', 'kitchen')->first();
        if (! $kitchenRoom) {
            return null;
        }

        $stoveFurniture = $kitchenRoom->furniture->where('hotspot_slug', 'stove')->first();
        if (! $stoveFurniture) {
            return null;
        }

        $stoveConfig = ConstructionConfig::ROOMS['kitchen']['hotspots']['stove']['options'][$stoveFurniture->furniture_key] ?? null;
        if (! $stoveConfig) {
            return null;
        }

        $burnReduction = $stoveConfig['effect']['burn_reduction'] ?? 0;
        $cookingLevel = $user->getSkillLevel('cooking');
        $baseBurn = max(5, 50 - $burnReduction);
        $burnChance = round($baseBurn * max(0.2, 1 - $cookingLevel / 99));

        $cookingInfo = $this->cookingService->getCookingInfo($user);

        return [
            'burn_chance' => $burnChance,
            'stove_name' => $stoveConfig['name'],
            'recipes' => $cookingInfo['recipes'],
            'cooking_level' => $cookingInfo['cooking_level'],
        ];
    }

    /**
     * Cook a recipe at the player's home kitchen.
     */
    public function cookAtHome(User $user, string $recipeId, ?PlayerHouse $house = null): array
    {
        $kitchenData = $this->getKitchenData($user, $house);
        if (! $kitchenData) {
            return ['success' => false, 'message' => 'You need a kitchen with a stove to cook at home.'];
        }

        if (! $house) {
            $house = PlayerHouse::where('player_id', $user->id)->first();
        }

        return $this->cookingService->cook(
            $user,
            $recipeId,
            'house',
            $house->id,
            $kitchenData['burn_chance']
        );
    }

    /**
     * Get bedroom rest data for a player's house.
     */
    public function getBedroomData(User $user, ?PlayerHouse $house = null): ?array
    {
        if (! $house) {
            $house = PlayerHouse::where('player_id', $user->id)
                ->with('rooms.furniture')
                ->first();
        } else {
            $house->load('rooms.furniture');
        }

        if (! $house) {
            return null;
        }

        $bedroomRoom = $house->rooms->where('room_type', 'bedroom')->first();
        if (! $bedroomRoom) {
            return null;
        }

        $bedFurniture = $bedroomRoom->furniture->where('hotspot_slug', 'bed')->first();
        if (! $bedFurniture) {
            return null;
        }

        $bedConfig = ConstructionConfig::ROOMS['bedroom']['hotspots']['bed']['options'][$bedFurniture->furniture_key] ?? null;
        if (! $bedConfig) {
            return null;
        }

        $bonus = $bedConfig['effect']['energy_regen_bonus'] ?? 5;
        $energyRestored = $bonus * 6;

        return [
            'bed_name' => $bedConfig['name'],
            'energy_restored' => $energyRestored,
        ];
    }

    /**
     * Rest at the player's home bedroom (free, no gold cost).
     */
    public function restAtHome(User $user, ?PlayerHouse $house = null): array
    {
        $bedroomData = $this->getBedroomData($user, $house);
        if (! $bedroomData) {
            return ['success' => false, 'message' => 'You need a bedroom with a bed to rest at home.'];
        }

        // Check cooldown (3 seconds, same as tavern)
        if ($user->last_rested_at) {
            $cooldownEnds = $user->last_rested_at->addSeconds(3);
            if ($cooldownEnds->isFuture()) {
                return ['success' => false, 'message' => 'You need to wait before resting again.'];
            }
        }

        // Check for hearth room HP bonus
        $hearthData = $this->getHearthData($user, $house);
        $canRestoreHp = $hearthData && $user->hp < $user->max_hp;

        if ($user->energy >= $user->max_energy && ! $canRestoreHp) {
            return ['success' => false, 'message' => 'You are already fully rested.'];
        }

        $energyRestored = min($bedroomData['energy_restored'], $user->max_energy - $user->energy);

        $user->energy += $energyRestored;
        $user->last_rested_at = now();

        $hpRestored = 0;
        if ($canRestoreHp) {
            $hpRestored = min($hearthData['hp_restore_amount'], $user->max_hp - $user->hp);
            $user->hp += $hpRestored;
        }

        $user->save();

        // Log activity - use the passed house for location context
        if (! $house) {
            $house = PlayerHouse::where('player_id', $user->id)->first();
        }
        if ($house) {
            try {
                LocationActivityLog::log(
                    userId: $user->id,
                    locationType: $house->location_type,
                    locationId: $house->location_id,
                    activityType: LocationActivityLog::TYPE_REST,
                    description: "{$user->username} rested at home",
                    metadata: ['energy_restored' => $energyRestored, 'hp_restored' => $hpRestored]
                );
            } catch (\Illuminate\Database\QueryException $e) {
                // Table may not exist
            }
        }

        $message = "You rest in your {$bedroomData['bed_name']} and recover {$energyRestored} energy.";
        if ($hpRestored > 0) {
            $message .= " The warm fire restores {$hpRestored} HP.";
        }

        return [
            'success' => true,
            'message' => $message,
            'hp_restored' => $hpRestored,
        ];
    }

    /**
     * Get workshop crafting data for a player's house.
     */
    public function getWorkshopData(User $user, ?PlayerHouse $house = null): ?array
    {
        if (! $house) {
            $house = PlayerHouse::where('player_id', $user->id)
                ->with('rooms.furniture')
                ->first();
        } else {
            $house->load('rooms.furniture');
        }

        if (! $house) {
            return null;
        }

        $workshopRoom = $house->rooms->where('room_type', 'workshop')->first();
        if (! $workshopRoom) {
            return null;
        }

        $workbenchFurniture = $workshopRoom->furniture->where('hotspot_slug', 'workbench')->first();
        if (! $workbenchFurniture) {
            return null;
        }

        $workbenchConfig = ConstructionConfig::ROOMS['workshop']['hotspots']['workbench']['options'][$workbenchFurniture->furniture_key] ?? null;
        if (! $workbenchConfig) {
            return null;
        }

        $xpBonus = $workbenchConfig['effect']['crafting_xp_bonus'] ?? 0;
        $categories = ['crafting', 'fletching', 'gem_cutting', 'jewelry'];
        $recipes = $this->craftingService->getHomeRecipes($user, $categories);

        return [
            'workbench_name' => $workbenchConfig['name'],
            'xp_bonus' => $xpBonus,
            'recipes' => $recipes,
            'crafting_level' => $user->getSkillLevel('crafting'),
        ];
    }

    /**
     * Craft at the player's workshop.
     */
    public function craftAtWorkshop(User $user, string $recipeId, ?PlayerHouse $house = null): array
    {
        $workshopData = $this->getWorkshopData($user, $house);
        if (! $workshopData) {
            return ['success' => false, 'message' => 'You need a workshop with a workbench to craft at home.'];
        }

        return $this->craftingService->craftAtHome(
            $user,
            $recipeId,
            ['crafting', 'fletching', 'gem_cutting', 'jewelry'],
            $workshopData['xp_bonus']
        );
    }

    /**
     * Get forge smelting/smithing data for a player's house.
     */
    public function getForgeData(User $user, ?PlayerHouse $house = null): ?array
    {
        if (! $house) {
            $house = PlayerHouse::where('player_id', $user->id)
                ->with('rooms.furniture')
                ->first();
        } else {
            $house->load('rooms.furniture');
        }

        if (! $house) {
            return null;
        }

        $forgeRoom = $house->rooms->where('room_type', 'forge')->first();
        if (! $forgeRoom) {
            return null;
        }

        $anvilFurniture = $forgeRoom->furniture->where('hotspot_slug', 'anvil')->first();
        if (! $anvilFurniture) {
            return null;
        }

        $anvilConfig = ConstructionConfig::ROOMS['forge']['hotspots']['anvil']['options'][$anvilFurniture->furniture_key] ?? null;
        if (! $anvilConfig) {
            return null;
        }

        $xpBonus = $anvilConfig['effect']['smithing_xp_bonus'] ?? 0;
        $categories = ['smelting', 'smithing'];
        $recipes = $this->craftingService->getHomeRecipes($user, $categories);

        return [
            'anvil_name' => $anvilConfig['name'],
            'xp_bonus' => $xpBonus,
            'recipes' => $recipes,
            'smithing_level' => $user->getSkillLevel('smithing'),
        ];
    }

    /**
     * Craft at the player's forge.
     */
    public function craftAtForge(User $user, string $recipeId, ?PlayerHouse $house = null): array
    {
        $forgeData = $this->getForgeData($user, $house);
        if (! $forgeData) {
            return ['success' => false, 'message' => 'You need a forge with an anvil to smelt or smith at home.'];
        }

        return $this->craftingService->craftAtHome(
            $user,
            $recipeId,
            ['smelting', 'smithing'],
            $forgeData['xp_bonus']
        );
    }

    /**
     * Get chapel prayer data for a player's house.
     */
    public function getChapelData(User $user, ?PlayerHouse $house = null): ?array
    {
        if (! $house) {
            $house = PlayerHouse::where('player_id', $user->id)
                ->with('rooms.furniture')
                ->first();
        } else {
            $house->load('rooms.furniture');
        }

        if (! $house) {
            return null;
        }

        $chapelRoom = $house->rooms->where('room_type', 'chapel')->first();
        if (! $chapelRoom) {
            return null;
        }

        $altarFurniture = $chapelRoom->furniture->where('hotspot_slug', 'altar')->first();
        if (! $altarFurniture) {
            return null;
        }

        $altarConfig = ConstructionConfig::ROOMS['chapel']['hotspots']['altar']['options'][$altarFurniture->furniture_key] ?? null;
        if (! $altarConfig) {
            return null;
        }

        // Sum prayer_xp_bonus from all chapel furniture
        $totalPrayerXpBonus = 0;
        foreach ($chapelRoom->furniture as $furniture) {
            $hotspot = ConstructionConfig::ROOMS['chapel']['hotspots'][$furniture->hotspot_slug] ?? null;
            if (! $hotspot) {
                continue;
            }
            $config = $hotspot['options'][$furniture->furniture_key] ?? null;
            if ($config && isset($config['effect']['prayer_xp_bonus'])) {
                $totalPrayerXpBonus += $config['effect']['prayer_xp_bonus'];
            }
        }

        // Check religion membership
        $membership = ReligionMember::where('user_id', $user->id)->first();

        // Check cooldown
        $canPray = true;
        $cooldownRemaining = 0;
        $energyCost = 5;

        if (! $membership) {
            $canPray = false;
        } else {
            if (! $user->hasEnergy($energyCost)) {
                $canPray = false;
            }

            $lastPrayer = ReligiousAction::where('user_id', $user->id)
                ->where('religion_id', $membership->religion_id)
                ->where('action_type', ReligiousAction::ACTION_PRAYER)
                ->latest()
                ->first();

            if ($lastPrayer) {
                $availableAt = $lastPrayer->created_at->addMinutes(5);
                if ($availableAt->isFuture()) {
                    $canPray = false;
                    $cooldownRemaining = (int) now()->diffInSeconds($availableAt);
                }
            }
        }

        return [
            'altar_name' => $altarConfig['name'],
            'prayer_xp_bonus' => $totalPrayerXpBonus,
            'religion' => $membership ? $membership->religion->name : null,
            'religion_id' => $membership?->religion_id,
            'prayer_level' => $user->getSkillLevel('prayer'),
            'energy_cost' => $energyCost,
            'can_pray' => $canPray,
            'cooldown_remaining' => $cooldownRemaining,
        ];
    }

    /**
     * Pray at the player's home chapel.
     */
    public function prayAtHome(User $user, ?PlayerHouse $house = null): array
    {
        if ($user->isTraveling()) {
            return ['success' => false, 'message' => 'You cannot pray while traveling.'];
        }

        if ($user->isInInfirmary()) {
            return ['success' => false, 'message' => 'You cannot pray while in the infirmary.'];
        }

        if (! $house) {
            $house = PlayerHouse::where('player_id', $user->id)
                ->with('rooms.furniture')
                ->first();
        } else {
            $house->load('rooms.furniture');
        }

        if (! $house) {
            return ['success' => false, 'message' => 'You do not own a house.'];
        }

        $chapelRoom = $house->rooms->where('room_type', 'chapel')->first();
        if (! $chapelRoom) {
            return ['success' => false, 'message' => 'You need a chapel to pray at home.'];
        }

        $altarFurniture = $chapelRoom->furniture->where('hotspot_slug', 'altar')->first();
        if (! $altarFurniture) {
            return ['success' => false, 'message' => 'You need an altar in your chapel to pray.'];
        }

        $membership = ReligionMember::where('user_id', $user->id)->first();
        if (! $membership) {
            return ['success' => false, 'message' => 'You must join a religion to pray.'];
        }

        $energyCost = 5;
        if (! $user->hasEnergy($energyCost)) {
            return ['success' => false, 'message' => "Not enough energy. Need {$energyCost} energy."];
        }

        // Check cooldown (5 minutes)
        $lastPrayer = ReligiousAction::where('user_id', $user->id)
            ->where('religion_id', $membership->religion_id)
            ->where('action_type', ReligiousAction::ACTION_PRAYER)
            ->latest()
            ->first();

        if ($lastPrayer) {
            $availableAt = $lastPrayer->created_at->addMinutes(5);
            if ($availableAt->isFuture()) {
                $remaining = $availableAt->diffForHumans();

                return ['success' => false, 'message' => "You can pray again {$remaining}."];
            }
        }

        // Sum prayer_xp_bonus from chapel furniture
        $totalPrayerXpBonus = 0;
        foreach ($chapelRoom->furniture as $furniture) {
            $hotspot = ConstructionConfig::ROOMS['chapel']['hotspots'][$furniture->hotspot_slug] ?? null;
            if (! $hotspot) {
                continue;
            }
            $config = $hotspot['options'][$furniture->furniture_key] ?? null;
            if ($config && isset($config['effect']['prayer_xp_bonus'])) {
                $totalPrayerXpBonus += $config['effect']['prayer_xp_bonus'];
            }
        }

        return DB::transaction(function () use ($user, $membership, $energyCost, $totalPrayerXpBonus) {
            $user->consumeEnergy($energyCost);

            // Base rewards (same as shrine prayer)
            $devotionGained = 10;
            $basePrayerXp = 5;

            // Apply chapel furniture prayer XP bonus
            $prayerXpGained = (int) ceil($basePrayerXp * (1 + $totalPrayerXpBonus / 100));

            // Create action record (for cooldown tracking)
            ReligiousAction::create([
                'user_id' => $user->id,
                'religion_id' => $membership->religion_id,
                'action_type' => ReligiousAction::ACTION_PRAYER,
                'devotion_gained' => $devotionGained,
                'gold_spent' => 0,
            ]);

            // Add devotion to membership
            $membership->addDevotion($devotionGained);

            // Award prayer XP
            $prayerSkill = PlayerSkill::firstOrCreate(
                ['player_id' => $user->id, 'skill_name' => 'prayer'],
                ['level' => 1, 'xp' => 0]
            );
            $prayerSkill->addXp($prayerXpGained);

            // Record daily task progress
            $this->dailyTaskService->recordProgress($user, 'pray');

            return [
                'success' => true,
                'message' => "You pray at your altar and gain {$devotionGained} devotion. (+{$prayerXpGained} Prayer XP)",
                'devotion_gained' => $devotionGained,
                'prayer_xp_gained' => $prayerXpGained,
            ];
        });
    }

    /**
     * Get hearth room data for a player's house.
     */
    public function getHearthData(User $user, ?PlayerHouse $house = null): ?array
    {
        if (! $house) {
            $house = PlayerHouse::where('player_id', $user->id)
                ->with('rooms.furniture')
                ->first();
        } else {
            $house->load('rooms.furniture');
        }

        if (! $house) {
            return null;
        }

        $hearthRoom = $house->rooms->where('room_type', 'hearth_room')->first();
        if (! $hearthRoom) {
            return null;
        }

        $fireplaceFurniture = $hearthRoom->furniture->where('hotspot_slug', 'fireplace')->first();
        if (! $fireplaceFurniture) {
            return null;
        }

        $fireplaceConfig = ConstructionConfig::ROOMS['hearth_room']['hotspots']['fireplace']['options'][$fireplaceFurniture->furniture_key] ?? null;
        if (! $fireplaceConfig) {
            return null;
        }

        // Stone fireplace: 15% HP restore, Marble fireplace: 35% HP restore
        $hpRestorePercent = $fireplaceFurniture->furniture_key === 'marble_fireplace' ? 35 : 15;
        $hpRestoreAmount = (int) floor($user->max_hp * $hpRestorePercent / 100);

        return [
            'fireplace_name' => $fireplaceConfig['name'],
            'hp_restore_percent' => $hpRestorePercent,
            'hp_restore_amount' => $hpRestoreAmount,
            'max_hp_bonus' => $fireplaceConfig['effect']['max_hp_bonus'] ?? 0,
        ];
    }

    /**
     * Get pool data for a player's superior garden.
     */
    public function getPoolData(User $user, ?PlayerHouse $house = null): ?array
    {
        if (! $house) {
            $house = PlayerHouse::where('player_id', $user->id)
                ->with('rooms.furniture')
                ->first();
        } else {
            $house->load('rooms.furniture');
        }

        if (! $house) {
            return null;
        }

        $gardenRoom = $house->rooms->where('room_type', 'superior_garden')->first();
        if (! $gardenRoom) {
            return null;
        }

        $poolFurniture = $gardenRoom->furniture->where('hotspot_slug', 'pool')->first();
        if (! $poolFurniture) {
            return null;
        }

        $poolConfig = ConstructionConfig::ROOMS['superior_garden']['hotspots']['pool']['options'][$poolFurniture->furniture_key] ?? null;
        if (! $poolConfig) {
            return null;
        }

        $effect = $poolConfig['effect'] ?? [];
        $cooldownRemaining = 0;

        if ($user->last_pool_used_at) {
            $cooldownEnds = $user->last_pool_used_at->addSeconds(60);
            if ($cooldownEnds->isFuture()) {
                $cooldownRemaining = (int) now()->diffInSeconds($cooldownEnds);
            }
        }

        return [
            'pool_name' => $poolConfig['name'],
            'restore_hp' => ! empty($effect['restore_hp']),
            'restore_energy' => ! empty($effect['restore_energy']),
            'cure_disease' => ! empty($effect['cure_disease']),
            'restore_all' => ! empty($effect['restore_all']),
            'cooldown_remaining' => $cooldownRemaining,
        ];
    }

    /**
     * Use the restoration pool in the superior garden.
     */
    public function usePool(User $user, ?PlayerHouse $house = null): array
    {
        if ($user->isTraveling()) {
            return ['success' => false, 'message' => 'You cannot use the pool while traveling.'];
        }

        if ($user->isInInfirmary()) {
            return ['success' => false, 'message' => 'You cannot use the pool while in the infirmary.'];
        }

        if (! $house) {
            $house = PlayerHouse::where('player_id', $user->id)
                ->with('rooms.furniture')
                ->first();
        } else {
            $house->load('rooms.furniture');
        }

        if (! $house) {
            return ['success' => false, 'message' => 'You do not own a house.'];
        }

        if ($house->areBuffsDisabled()) {
            return ['success' => false, 'message' => 'House amenities are disabled due to poor condition. Repair your house first.'];
        }

        $gardenRoom = $house->rooms->where('room_type', 'superior_garden')->first();
        if (! $gardenRoom) {
            return ['success' => false, 'message' => 'You need a Superior Garden with a pool.'];
        }

        $poolFurniture = $gardenRoom->furniture->where('hotspot_slug', 'pool')->first();
        if (! $poolFurniture) {
            return ['success' => false, 'message' => 'You need to build a pool in your Superior Garden.'];
        }

        $poolConfig = ConstructionConfig::ROOMS['superior_garden']['hotspots']['pool']['options'][$poolFurniture->furniture_key] ?? null;
        if (! $poolConfig) {
            return ['success' => false, 'message' => 'Invalid pool configuration.'];
        }

        // Check cooldown
        if ($user->last_pool_used_at) {
            $cooldownEnds = $user->last_pool_used_at->addSeconds(60);
            if ($cooldownEnds->isFuture()) {
                $remaining = (int) now()->diffInSeconds($cooldownEnds);

                return ['success' => false, 'message' => "You must wait {$remaining} seconds before using the pool again."];
            }
        }

        $effect = $poolConfig['effect'] ?? [];
        $restoreHp = ! empty($effect['restore_hp']);
        $restoreEnergy = ! empty($effect['restore_energy']);
        $cureDisease = ! empty($effect['cure_disease']);

        // Check if user actually needs restoration
        $needsHp = $restoreHp && $user->hp < $user->max_hp;
        $needsEnergy = $restoreEnergy && $user->energy < $user->max_energy;
        $hasActiveInfections = $cureDisease && DiseaseInfection::where('user_id', $user->id)->active()->exists();

        if (! $needsHp && ! $needsEnergy && ! $hasActiveInfections) {
            return ['success' => false, 'message' => 'You are already at full health.'];
        }

        $restoredParts = [];

        if ($needsHp) {
            $hpRestored = $user->max_hp - $user->hp;
            $user->hp = $user->max_hp;
            $restoredParts[] = "restored {$hpRestored} HP";
        }

        if ($needsEnergy) {
            $energyRestored = $user->max_energy - $user->energy;
            $user->energy = $user->max_energy;
            $restoredParts[] = "restored {$energyRestored} energy";
        }

        if ($hasActiveInfections) {
            DiseaseInfection::where('user_id', $user->id)
                ->active()
                ->update([
                    'status' => DiseaseInfection::STATUS_RECOVERED,
                    'recovered_at' => now(),
                ]);
            $restoredParts[] = 'cured all infections';
        }

        $user->last_pool_used_at = now();
        $user->save();

        // Log activity
        try {
            LocationActivityLog::log(
                userId: $user->id,
                locationType: $house->location_type,
                locationId: $house->location_id,
                activityType: LocationActivityLog::TYPE_REST,
                description: "{$user->username} used the restoration pool",
                metadata: ['pool_name' => $poolConfig['name']]
            );
        } catch (\Illuminate\Database\QueryException $e) {
            // Table may not exist
        }

        $message = "You enter the {$poolConfig['name']} and feel rejuvenated. ".ucfirst(implode(', ', $restoredParts)).'.';

        return ['success' => true, 'message' => $message];
    }

    protected function findEmptyStorageSlot(PlayerHouse $house): int
    {
        $usedSlots = HouseStorage::where('player_house_id', $house->id)
            ->pluck('slot_number')
            ->toArray();

        $capacity = $house->getStorageCapacity();

        for ($i = 0; $i < $capacity; $i++) {
            if (! in_array($i, $usedSlots)) {
                return $i;
            }
        }

        return count($usedSlots);
    }
}
