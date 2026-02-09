<?php

namespace App\Services;

use App\Config\ConstructionConfig;
use App\Models\Barony;
use App\Models\HouseFurniture;
use App\Models\HousePortal;
use App\Models\HouseRoom;
use App\Models\HouseStorage;
use App\Models\Item;
use App\Models\Kingdom;
use App\Models\PlayerHouse;
use App\Models\Town;
use App\Models\User;
use App\Models\Village;
use Illuminate\Support\Facades\DB;

class HouseService
{
    public function __construct(
        protected InventoryService $inventoryService,
        protected BiomeService $biomeService,
        protected EnergyService $energyService
    ) {}

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

        if (! $user->current_kingdom_id) {
            return ['can_purchase' => false, 'reason' => 'You must be in a kingdom to purchase a house.'];
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
                'kingdom_id' => $user->current_kingdom_id,
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

        $storageUsed = $house->getStorageUsed();
        $storageCapacity = $house->getStorageCapacity();
        if ($storageUsed + $quantity > $storageCapacity) {
            $canStore = $storageCapacity - $storageUsed;

            return ['success' => false, 'message' => 'Not enough storage space. Can store '.$canStore.' more items.'];
        }

        return DB::transaction(function () use ($user, $house, $item, $quantity, $itemName) {
            $this->inventoryService->removeItem($user, $item, $quantity);

            $storage = HouseStorage::firstOrCreate(
                ['player_house_id' => $house->id, 'item_id' => $item->id],
                ['quantity' => 0],
            );
            $storage->increment('quantity', $quantity);

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

        return DB::transaction(function () use ($user, $item, $storage, $quantity, $itemName) {
            if (! $this->inventoryService->addItem($user, $item, $quantity)) {
                return ['success' => false, 'message' => 'Not enough inventory space.'];
            }

            $storage->decrement('quantity', $quantity);
            if ($storage->quantity <= 0) {
                $storage->delete();
            }

            return ['success' => true, 'message' => 'Withdrew '.$quantity.' '.$itemName.'.'];
        });
    }

    /**
     * Get portal data for a player's house.
     *
     * @return array<int, array{slot: int, furniture_key: string|null, destination: array|null}>
     */
    public function getPortals(User $user): array
    {
        $house = PlayerHouse::where('player_id', $user->id)
            ->with(['rooms.furniture', 'portals'])
            ->first();

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
    public function teleportFromPortal(User $user, int $slot): array
    {
        if ($slot < 1 || $slot > 3) {
            return ['success' => false, 'message' => 'Invalid portal slot.'];
        }

        if ($user->isTraveling()) {
            return ['success' => false, 'message' => 'You cannot teleport while traveling.'];
        }

        if ($user->isInInfirmary()) {
            return ['success' => false, 'message' => 'You cannot teleport while in the infirmary.'];
        }

        $house = PlayerHouse::where('player_id', $user->id)
            ->with(['rooms.furniture', 'portals'])
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

        return $destinations;
    }
}
