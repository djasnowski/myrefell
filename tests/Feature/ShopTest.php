<?php

use App\Models\Barony;
use App\Models\Item;
use App\Models\Kingdom;
use App\Models\PlayerInventory;
use App\Models\Shop;
use App\Models\ShopItem;
use App\Models\User;
use App\Models\Village;
use App\Services\ShopService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    User::query()->delete();
});

test('can view shops page at a kingdom', function () {
    $kingdom = Kingdom::factory()->create();
    $shop = Shop::factory()->atLocation('kingdom', $kingdom->id)->create();

    $user = User::factory()->create([
        'current_location_type' => 'kingdom',
        'current_location_id' => $kingdom->id,
    ]);

    $this->actingAs($user)
        ->get("/kingdoms/{$kingdom->id}/shops")
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Shops/Index')
            ->has('shops', 1)
        );
});

test('can view a specific shop', function () {
    $kingdom = Kingdom::factory()->create();
    $item = Item::factory()->create();
    $shop = Shop::factory()->atLocation('kingdom', $kingdom->id)->create();
    ShopItem::factory()->create([
        'shop_id' => $shop->id,
        'item_id' => $item->id,
        'price' => 1000,
    ]);

    $user = User::factory()->create([
        'current_location_type' => 'kingdom',
        'current_location_id' => $kingdom->id,
    ]);

    $this->actingAs($user)
        ->get("/kingdoms/{$kingdom->id}/shops/{$shop->slug}")
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Shops/Show')
            ->has('shop')
            ->has('items', 1)
            ->has('player_gold')
        );
});

test('can buy an item from a shop', function () {
    $kingdom = Kingdom::factory()->create();
    $item = Item::factory()->create(['stackable' => true, 'max_stack' => 100]);
    $shop = Shop::factory()->atLocation('kingdom', $kingdom->id)->create();
    $shopItem = ShopItem::factory()->create([
        'shop_id' => $shop->id,
        'item_id' => $item->id,
        'price' => 1000,
    ]);

    $user = User::factory()->create([
        'current_location_type' => 'kingdom',
        'current_location_id' => $kingdom->id,
        'gold' => 5000,
    ]);

    $this->actingAs($user)
        ->postJson("/kingdoms/{$kingdom->id}/shops/{$shop->slug}/buy", [
            'shop_item_id' => $shopItem->id,
            'quantity' => 1,
        ])
        ->assertSuccessful()
        ->assertJson(['success' => true]);

    $user->refresh();
    expect($user->gold)->toBe(4000);
    expect($user->inventory()->where('item_id', $item->id)->exists())->toBeTrue();
});

test('cannot buy without enough gold', function () {
    $kingdom = Kingdom::factory()->create();
    $item = Item::factory()->create();
    $shop = Shop::factory()->atLocation('kingdom', $kingdom->id)->create();
    $shopItem = ShopItem::factory()->create([
        'shop_id' => $shop->id,
        'item_id' => $item->id,
        'price' => 10000,
    ]);

    $user = User::factory()->create([
        'current_location_type' => 'kingdom',
        'current_location_id' => $kingdom->id,
        'gold' => 500,
    ]);

    $this->actingAs($user)
        ->postJson("/kingdoms/{$kingdom->id}/shops/{$shop->slug}/buy", [
            'shop_item_id' => $shopItem->id,
            'quantity' => 1,
        ])
        ->assertStatus(422)
        ->assertJson(['success' => false]);

    $user->refresh();
    expect($user->gold)->toBe(500);
});

test('cannot buy out-of-stock items', function () {
    $kingdom = Kingdom::factory()->create();
    $item = Item::factory()->create();
    $shop = Shop::factory()->atLocation('kingdom', $kingdom->id)->create();
    $shopItem = ShopItem::factory()->create([
        'shop_id' => $shop->id,
        'item_id' => $item->id,
        'price' => 100,
        'stock_quantity' => 0,
        'max_stock' => 5,
    ]);

    $user = User::factory()->create([
        'current_location_type' => 'kingdom',
        'current_location_id' => $kingdom->id,
        'gold' => 5000,
    ]);

    $this->actingAs($user)
        ->postJson("/kingdoms/{$kingdom->id}/shops/{$shop->slug}/buy", [
            'shop_item_id' => $shopItem->id,
            'quantity' => 1,
        ])
        ->assertStatus(422)
        ->assertJson(['success' => false]);
});

test('cannot access shops while traveling', function () {
    $kingdom = Kingdom::factory()->create();
    $shop = Shop::factory()->atLocation('kingdom', $kingdom->id)->create();
    $item = Item::factory()->create();
    $shopItem = ShopItem::factory()->create([
        'shop_id' => $shop->id,
        'item_id' => $item->id,
        'price' => 100,
    ]);

    $user = User::factory()->create([
        'current_location_type' => 'kingdom',
        'current_location_id' => $kingdom->id,
        'gold' => 5000,
        'is_traveling' => true,
        'travel_arrives_at' => now()->addMinutes(10),
    ]);

    $service = app(ShopService::class);
    expect($service->canAccessShops($user))->toBeFalse();

    $result = $service->buyItem($user, $shopItem);
    expect($result['success'])->toBeFalse();
});

test('kingdom-level shops visible from child village', function () {
    $kingdom = Kingdom::factory()->create();
    $barony = Barony::factory()->create(['kingdom_id' => $kingdom->id]);
    $village = Village::factory()->create(['barony_id' => $barony->id]);

    $shop = Shop::factory()->atLocation('kingdom', $kingdom->id)->create();

    $service = app(ShopService::class);
    $shops = $service->getShopsAtLocation('village', $village->id);

    expect($shops)->toHaveCount(1);
    expect($shops->first()->id)->toBe($shop->id);
});

test('limited stock decrements on purchase', function () {
    $kingdom = Kingdom::factory()->create();
    $item = Item::factory()->create(['stackable' => true, 'max_stack' => 100]);
    $shop = Shop::factory()->atLocation('kingdom', $kingdom->id)->create();
    $shopItem = ShopItem::factory()->create([
        'shop_id' => $shop->id,
        'item_id' => $item->id,
        'price' => 100,
        'stock_quantity' => 5,
        'max_stock' => 5,
    ]);

    $user = User::factory()->create([
        'current_location_type' => 'kingdom',
        'current_location_id' => $kingdom->id,
        'gold' => 5000,
    ]);

    $this->actingAs($user)
        ->postJson("/kingdoms/{$kingdom->id}/shops/{$shop->slug}/buy", [
            'shop_item_id' => $shopItem->id,
            'quantity' => 2,
        ])
        ->assertSuccessful();

    $shopItem->refresh();
    expect($shopItem->stock_quantity)->toBe(3);
});

test('can buy multiple quantity', function () {
    $kingdom = Kingdom::factory()->create();
    $item = Item::factory()->create(['stackable' => true, 'max_stack' => 100]);
    $shop = Shop::factory()->atLocation('kingdom', $kingdom->id)->create();
    $shopItem = ShopItem::factory()->create([
        'shop_id' => $shop->id,
        'item_id' => $item->id,
        'price' => 500,
    ]);

    $user = User::factory()->create([
        'current_location_type' => 'kingdom',
        'current_location_id' => $kingdom->id,
        'gold' => 5000,
    ]);

    $this->actingAs($user)
        ->postJson("/kingdoms/{$kingdom->id}/shops/{$shop->slug}/buy", [
            'shop_item_id' => $shopItem->id,
            'quantity' => 3,
        ])
        ->assertSuccessful();

    $user->refresh();
    expect($user->gold)->toBe(3500);
});

test('cannot buy when inventory is completely full', function () {
    $kingdom = Kingdom::factory()->create();
    $item = Item::factory()->create(['stackable' => false]);
    $shop = Shop::factory()->atLocation('kingdom', $kingdom->id)->create();
    $shopItem = ShopItem::factory()->create([
        'shop_id' => $shop->id,
        'item_id' => $item->id,
        'price' => 100,
    ]);

    $user = User::factory()->create([
        'current_location_type' => 'kingdom',
        'current_location_id' => $kingdom->id,
        'gold' => 50000,
    ]);

    // Fill all inventory slots
    $filler = Item::factory()->create(['stackable' => false]);
    for ($i = 0; $i < PlayerInventory::MAX_SLOTS; $i++) {
        PlayerInventory::create([
            'player_id' => $user->id,
            'item_id' => $filler->id,
            'slot_number' => $i,
            'quantity' => 1,
        ]);
    }

    $this->actingAs($user)
        ->postJson("/kingdoms/{$kingdom->id}/shops/{$shop->slug}/buy", [
            'shop_item_id' => $shopItem->id,
            'quantity' => 1,
        ])
        ->assertStatus(422)
        ->assertJson(['success' => false]);

    $user->refresh();
    expect($user->gold)->toBe(50000);
});

test('cannot buy more non-stackable items than free slots', function () {
    $kingdom = Kingdom::factory()->create();
    $item = Item::factory()->create(['stackable' => false]);
    $shop = Shop::factory()->atLocation('kingdom', $kingdom->id)->create();
    $shopItem = ShopItem::factory()->create([
        'shop_id' => $shop->id,
        'item_id' => $item->id,
        'price' => 100,
    ]);

    $user = User::factory()->create([
        'current_location_type' => 'kingdom',
        'current_location_id' => $kingdom->id,
        'gold' => 50000,
    ]);

    // Fill all but 3 slots
    $filler = Item::factory()->create(['stackable' => false]);
    for ($i = 0; $i < PlayerInventory::MAX_SLOTS - 3; $i++) {
        PlayerInventory::create([
            'player_id' => $user->id,
            'item_id' => $filler->id,
            'slot_number' => $i,
            'quantity' => 1,
        ]);
    }

    // Try to buy 4 — should fail
    $response = $this->actingAs($user)
        ->postJson("/kingdoms/{$kingdom->id}/shops/{$shop->slug}/buy", [
            'shop_item_id' => $shopItem->id,
            'quantity' => 4,
        ])
        ->assertStatus(422)
        ->assertJson(['success' => false]);

    expect($response->json('message'))->toContain('4')
        ->toContain('3');

    $user->refresh();
    expect($user->gold)->toBe(50000);

    // Buy 3 — should succeed
    $this->actingAs($user)
        ->postJson("/kingdoms/{$kingdom->id}/shops/{$shop->slug}/buy", [
            'shop_item_id' => $shopItem->id,
            'quantity' => 3,
        ])
        ->assertSuccessful();

    $user->refresh();
    expect($user->gold)->toBe(49700);
});
