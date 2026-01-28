<?php

use App\Models\Item;
use App\Models\PlayerInventory;
use App\Models\User;
use App\Services\InventoryService;

beforeEach(function () {
    $this->service = app(InventoryService::class);
});

describe('addItem', function () {
    test('adds a non-stackable item to empty inventory', function () {
        $user = User::factory()->create();
        $item = Item::create([
            'name' => 'Bronze Sword',
            'type' => 'weapon',
            'stackable' => false,
            'max_stack' => 1,
        ]);

        $result = $this->service->addItem($user, $item);

        expect($result)->toBeTrue();
        expect($user->inventory()->count())->toBe(1);
        expect($user->inventory()->first()->item_id)->toBe($item->id);
        expect($user->inventory()->first()->quantity)->toBe(1);
    });

    test('adds a stackable item to empty inventory', function () {
        $user = User::factory()->create();
        $item = Item::create([
            'name' => 'Raw Fish',
            'type' => 'resource',
            'stackable' => true,
            'max_stack' => 100,
        ]);

        $result = $this->service->addItem($user, $item, 25);

        expect($result)->toBeTrue();
        expect($user->inventory()->count())->toBe(1);
        expect($user->inventory()->first()->quantity)->toBe(25);
    });

    test('stacks with existing item when possible', function () {
        $user = User::factory()->create();
        $item = Item::create([
            'name' => 'Iron Ore',
            'type' => 'resource',
            'stackable' => true,
            'max_stack' => 100,
        ]);

        // Add initial stack
        PlayerInventory::create([
            'player_id' => $user->id,
            'item_id' => $item->id,
            'slot_number' => 0,
            'quantity' => 30,
        ]);

        $result = $this->service->addItem($user, $item, 20);

        expect($result)->toBeTrue();
        expect($user->inventory()->count())->toBe(1); // Still one stack
        expect($user->inventory()->first()->quantity)->toBe(50);
    });

    test('creates new stack when existing is full', function () {
        $user = User::factory()->create();
        $item = Item::create([
            'name' => 'Coal',
            'type' => 'resource',
            'stackable' => true,
            'max_stack' => 50,
        ]);

        // Add full stack
        PlayerInventory::create([
            'player_id' => $user->id,
            'item_id' => $item->id,
            'slot_number' => 0,
            'quantity' => 50,
        ]);

        $result = $this->service->addItem($user, $item, 30);

        expect($result)->toBeTrue();
        expect($user->inventory()->count())->toBe(2);
        expect($user->inventory()->sum('quantity'))->toBe(80);
    });

    test('fills existing stack then creates new one', function () {
        $user = User::factory()->create();
        $item = Item::create([
            'name' => 'Logs',
            'type' => 'resource',
            'stackable' => true,
            'max_stack' => 100,
        ]);

        // Add partial stack
        PlayerInventory::create([
            'player_id' => $user->id,
            'item_id' => $item->id,
            'slot_number' => 0,
            'quantity' => 80,
        ]);

        $result = $this->service->addItem($user, $item, 50);

        expect($result)->toBeTrue();
        expect($user->inventory()->count())->toBe(2);
        // First stack should be full (100), second has remainder (30)
        expect($user->inventory()->where('slot_number', 0)->first()->quantity)->toBe(100);
        expect($user->inventory()->where('slot_number', 1)->first()->quantity)->toBe(30);
    });

    test('returns false when inventory is full', function () {
        $user = User::factory()->create();
        $sword = Item::create([
            'name' => 'Sword',
            'type' => 'weapon',
            'stackable' => false,
            'max_stack' => 1,
        ]);
        $newItem = Item::create([
            'name' => 'Shield',
            'type' => 'armor',
            'stackable' => false,
            'max_stack' => 1,
        ]);

        // Fill all slots
        for ($i = 0; $i < PlayerInventory::MAX_SLOTS; $i++) {
            PlayerInventory::create([
                'player_id' => $user->id,
                'item_id' => $sword->id,
                'slot_number' => $i,
                'quantity' => 1,
            ]);
        }

        $result = $this->service->addItem($user, $newItem);

        expect($result)->toBeFalse();
        expect($user->inventory()->where('item_id', $newItem->id)->count())->toBe(0);
    });

    test('accepts item id instead of model', function () {
        $user = User::factory()->create();
        $item = Item::create([
            'name' => 'Potion',
            'type' => 'consumable',
            'stackable' => true,
            'max_stack' => 10,
        ]);

        $result = $this->service->addItem($user, $item->id, 5);

        expect($result)->toBeTrue();
        expect($user->inventory()->first()->item_id)->toBe($item->id);
    });

    test('returns false for invalid item id', function () {
        $user = User::factory()->create();

        $result = $this->service->addItem($user, 99999);

        expect($result)->toBeFalse();
    });
});

describe('removeItem', function () {
    test('removes quantity from single stack', function () {
        $user = User::factory()->create();
        $item = Item::create([
            'name' => 'Bread',
            'type' => 'consumable',
            'stackable' => true,
            'max_stack' => 100,
        ]);

        PlayerInventory::create([
            'player_id' => $user->id,
            'item_id' => $item->id,
            'slot_number' => 0,
            'quantity' => 50,
        ]);

        $result = $this->service->removeItem($user, $item, 20);

        expect($result)->toBeTrue();
        expect($user->inventory()->first()->quantity)->toBe(30);
    });

    test('deletes slot when emptied', function () {
        $user = User::factory()->create();
        $item = Item::create([
            'name' => 'Apple',
            'type' => 'consumable',
            'stackable' => true,
            'max_stack' => 100,
        ]);

        PlayerInventory::create([
            'player_id' => $user->id,
            'item_id' => $item->id,
            'slot_number' => 0,
            'quantity' => 10,
        ]);

        $result = $this->service->removeItem($user, $item, 10);

        expect($result)->toBeTrue();
        expect($user->inventory()->count())->toBe(0);
    });

    test('removes from multiple stacks smallest first', function () {
        $user = User::factory()->create();
        $item = Item::create([
            'name' => 'Feather',
            'type' => 'resource',
            'stackable' => true,
            'max_stack' => 100,
        ]);

        PlayerInventory::create([
            'player_id' => $user->id,
            'item_id' => $item->id,
            'slot_number' => 0,
            'quantity' => 30, // Larger
        ]);
        PlayerInventory::create([
            'player_id' => $user->id,
            'item_id' => $item->id,
            'slot_number' => 1,
            'quantity' => 10, // Smaller - removed first
        ]);

        $result = $this->service->removeItem($user, $item, 15);

        expect($result)->toBeTrue();
        // Small stack deleted, 5 removed from large stack
        expect($user->inventory()->count())->toBe(1);
        expect($user->inventory()->first()->quantity)->toBe(25);
    });

    test('returns false when not enough items', function () {
        $user = User::factory()->create();
        $item = Item::create([
            'name' => 'Gold Bar',
            'type' => 'resource',
            'stackable' => true,
            'max_stack' => 100,
        ]);

        PlayerInventory::create([
            'player_id' => $user->id,
            'item_id' => $item->id,
            'slot_number' => 0,
            'quantity' => 5,
        ]);

        $result = $this->service->removeItem($user, $item, 10);

        expect($result)->toBeFalse();
    });

    test('returns false when item not in inventory', function () {
        $user = User::factory()->create();
        $item = Item::create([
            'name' => 'Diamond',
            'type' => 'resource',
            'stackable' => true,
            'max_stack' => 100,
        ]);

        $result = $this->service->removeItem($user, $item, 1);

        expect($result)->toBeFalse();
    });

    test('accepts item id instead of model', function () {
        $user = User::factory()->create();
        $item = Item::create([
            'name' => 'Herb',
            'type' => 'resource',
            'stackable' => true,
            'max_stack' => 100,
        ]);

        PlayerInventory::create([
            'player_id' => $user->id,
            'item_id' => $item->id,
            'slot_number' => 0,
            'quantity' => 20,
        ]);

        $result = $this->service->removeItem($user, $item->id, 5);

        expect($result)->toBeTrue();
        expect($user->inventory()->first()->quantity)->toBe(15);
    });
});

describe('hasItem', function () {
    test('returns true when player has enough', function () {
        $user = User::factory()->create();
        $item = Item::create([
            'name' => 'Stone',
            'type' => 'resource',
            'stackable' => true,
            'max_stack' => 100,
        ]);

        PlayerInventory::create([
            'player_id' => $user->id,
            'item_id' => $item->id,
            'slot_number' => 0,
            'quantity' => 50,
        ]);

        expect($this->service->hasItem($user, $item, 30))->toBeTrue();
        expect($this->service->hasItem($user, $item, 50))->toBeTrue();
    });

    test('returns false when player does not have enough', function () {
        $user = User::factory()->create();
        $item = Item::create([
            'name' => 'Copper',
            'type' => 'resource',
            'stackable' => true,
            'max_stack' => 100,
        ]);

        PlayerInventory::create([
            'player_id' => $user->id,
            'item_id' => $item->id,
            'slot_number' => 0,
            'quantity' => 10,
        ]);

        expect($this->service->hasItem($user, $item, 20))->toBeFalse();
    });

    test('counts across multiple stacks', function () {
        $user = User::factory()->create();
        $item = Item::create([
            'name' => 'Clay',
            'type' => 'resource',
            'stackable' => true,
            'max_stack' => 50,
        ]);

        PlayerInventory::create([
            'player_id' => $user->id,
            'item_id' => $item->id,
            'slot_number' => 0,
            'quantity' => 50,
        ]);
        PlayerInventory::create([
            'player_id' => $user->id,
            'item_id' => $item->id,
            'slot_number' => 1,
            'quantity' => 30,
        ]);

        expect($this->service->hasItem($user, $item, 80))->toBeTrue();
        expect($this->service->hasItem($user, $item, 81))->toBeFalse();
    });

    test('returns false when item not in inventory', function () {
        $user = User::factory()->create();
        $item = Item::create([
            'name' => 'Ruby',
            'type' => 'resource',
            'stackable' => true,
            'max_stack' => 100,
        ]);

        expect($this->service->hasItem($user, $item, 1))->toBeFalse();
    });
});

describe('countItem', function () {
    test('returns total quantity across all stacks', function () {
        $user = User::factory()->create();
        $item = Item::create([
            'name' => 'Leather',
            'type' => 'resource',
            'stackable' => true,
            'max_stack' => 100,
        ]);

        PlayerInventory::create([
            'player_id' => $user->id,
            'item_id' => $item->id,
            'slot_number' => 0,
            'quantity' => 45,
        ]);
        PlayerInventory::create([
            'player_id' => $user->id,
            'item_id' => $item->id,
            'slot_number' => 1,
            'quantity' => 30,
        ]);

        expect($this->service->countItem($user, $item))->toBe(75);
    });

    test('returns zero when item not in inventory', function () {
        $user = User::factory()->create();
        $item = Item::create([
            'name' => 'Emerald',
            'type' => 'resource',
            'stackable' => true,
            'max_stack' => 100,
        ]);

        expect($this->service->countItem($user, $item))->toBe(0);
    });

    test('accepts item id instead of model', function () {
        $user = User::factory()->create();
        $item = Item::create([
            'name' => 'Silk',
            'type' => 'resource',
            'stackable' => true,
            'max_stack' => 100,
        ]);

        PlayerInventory::create([
            'player_id' => $user->id,
            'item_id' => $item->id,
            'slot_number' => 0,
            'quantity' => 25,
        ]);

        expect($this->service->countItem($user, $item->id))->toBe(25);
    });
});

describe('findEmptySlot', function () {
    test('returns first empty slot number', function () {
        $user = User::factory()->create();

        expect($this->service->findEmptySlot($user))->toBe(0);
    });

    test('returns next available slot', function () {
        $user = User::factory()->create();
        $item = Item::create([
            'name' => 'Stick',
            'type' => 'resource',
            'stackable' => true,
            'max_stack' => 100,
        ]);

        PlayerInventory::create([
            'player_id' => $user->id,
            'item_id' => $item->id,
            'slot_number' => 0,
            'quantity' => 1,
        ]);
        PlayerInventory::create([
            'player_id' => $user->id,
            'item_id' => $item->id,
            'slot_number' => 1,
            'quantity' => 1,
        ]);

        expect($this->service->findEmptySlot($user))->toBe(2);
    });

    test('finds gap in slot numbers', function () {
        $user = User::factory()->create();
        $item = Item::create([
            'name' => 'String',
            'type' => 'resource',
            'stackable' => true,
            'max_stack' => 100,
        ]);

        PlayerInventory::create([
            'player_id' => $user->id,
            'item_id' => $item->id,
            'slot_number' => 0,
            'quantity' => 1,
        ]);
        PlayerInventory::create([
            'player_id' => $user->id,
            'item_id' => $item->id,
            'slot_number' => 2, // Gap at slot 1
            'quantity' => 1,
        ]);

        expect($this->service->findEmptySlot($user))->toBe(1);
    });

    test('returns null when inventory is full', function () {
        $user = User::factory()->create();
        $item = Item::create([
            'name' => 'Filler',
            'type' => 'resource',
            'stackable' => false,
            'max_stack' => 1,
        ]);

        for ($i = 0; $i < PlayerInventory::MAX_SLOTS; $i++) {
            PlayerInventory::create([
                'player_id' => $user->id,
                'item_id' => $item->id,
                'slot_number' => $i,
                'quantity' => 1,
            ]);
        }

        expect($this->service->findEmptySlot($user))->toBeNull();
    });
});

describe('hasEmptySlot', function () {
    test('returns true when inventory has space', function () {
        $user = User::factory()->create();

        expect($this->service->hasEmptySlot($user))->toBeTrue();
    });

    test('returns false when inventory is full', function () {
        $user = User::factory()->create();
        $item = Item::create([
            'name' => 'Block',
            'type' => 'resource',
            'stackable' => false,
            'max_stack' => 1,
        ]);

        for ($i = 0; $i < PlayerInventory::MAX_SLOTS; $i++) {
            PlayerInventory::create([
                'player_id' => $user->id,
                'item_id' => $item->id,
                'slot_number' => $i,
                'quantity' => 1,
            ]);
        }

        expect($this->service->hasEmptySlot($user))->toBeFalse();
    });
});

describe('freeSlots', function () {
    test('returns all slots for empty inventory', function () {
        $user = User::factory()->create();

        expect($this->service->freeSlots($user))->toBe(PlayerInventory::MAX_SLOTS);
    });

    test('returns correct count with some items', function () {
        $user = User::factory()->create();
        $item = Item::create([
            'name' => 'Bone',
            'type' => 'resource',
            'stackable' => true,
            'max_stack' => 100,
        ]);

        PlayerInventory::create([
            'player_id' => $user->id,
            'item_id' => $item->id,
            'slot_number' => 0,
            'quantity' => 50,
        ]);
        PlayerInventory::create([
            'player_id' => $user->id,
            'item_id' => $item->id,
            'slot_number' => 1,
            'quantity' => 25,
        ]);

        expect($this->service->freeSlots($user))->toBe(PlayerInventory::MAX_SLOTS - 2);
    });

    test('returns zero when inventory is full', function () {
        $user = User::factory()->create();
        $item = Item::create([
            'name' => 'Pebble',
            'type' => 'resource',
            'stackable' => false,
            'max_stack' => 1,
        ]);

        for ($i = 0; $i < PlayerInventory::MAX_SLOTS; $i++) {
            PlayerInventory::create([
                'player_id' => $user->id,
                'item_id' => $item->id,
                'slot_number' => $i,
                'quantity' => 1,
            ]);
        }

        expect($this->service->freeSlots($user))->toBe(0);
    });
});

describe('giveStarterKit', function () {
    test('gives starter items to player', function () {
        $user = User::factory()->create();

        // Create the starter items that the service expects
        Item::create(['name' => 'Bronze Dagger', 'type' => 'weapon', 'stackable' => false, 'max_stack' => 1]);
        Item::create(['name' => 'Wooden Shield', 'type' => 'armor', 'stackable' => false, 'max_stack' => 1]);
        Item::create(['name' => 'Leather Vest', 'type' => 'armor', 'stackable' => false, 'max_stack' => 1]);
        Item::create(['name' => 'Bread', 'type' => 'consumable', 'stackable' => true, 'max_stack' => 100]);
        Item::create(['name' => 'Bronze Pickaxe', 'type' => 'tool', 'stackable' => false, 'max_stack' => 1]);
        Item::create(['name' => 'Fishing Rod', 'type' => 'tool', 'stackable' => false, 'max_stack' => 1]);

        $this->service->giveStarterKit($user);

        // Should have 6 different item slots
        expect($user->inventory()->count())->toBe(6);

        // Check bread quantity
        $bread = Item::where('name', 'Bread')->first();
        expect($user->inventory()->where('item_id', $bread->id)->first()->quantity)->toBe(10);
    });

    test('skips items that do not exist', function () {
        $user = User::factory()->create();

        // Only create some starter items
        Item::create(['name' => 'Bronze Dagger', 'type' => 'weapon', 'stackable' => false, 'max_stack' => 1]);
        Item::create(['name' => 'Bread', 'type' => 'consumable', 'stackable' => true, 'max_stack' => 100]);

        $this->service->giveStarterKit($user);

        // Should only have 2 items
        expect($user->inventory()->count())->toBe(2);
    });
});
