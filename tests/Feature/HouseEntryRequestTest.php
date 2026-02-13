<?php

use App\Models\HouseFurniture;
use App\Models\HouseRoom;
use App\Models\Kingdom;
use App\Models\PlayerHouse;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

function createHouseWithParlour(User $owner, Kingdom $kingdom): PlayerHouse
{
    $house = PlayerHouse::create([
        'player_id' => $owner->id,
        'name' => 'Test House',
        'tier' => 'cottage',
        'condition' => 100,
        'kingdom_id' => $kingdom->id,
        'location_type' => 'kingdom',
        'location_id' => $kingdom->id,
    ]);

    HouseRoom::create([
        'player_house_id' => $house->id,
        'room_type' => 'parlour',
        'grid_x' => 0,
        'grid_y' => 0,
    ]);

    return $house;
}

function createHouseWithoutParlour(User $owner, Kingdom $kingdom): PlayerHouse
{
    return PlayerHouse::create([
        'player_id' => $owner->id,
        'name' => 'Test House',
        'tier' => 'cottage',
        'condition' => 100,
        'kingdom_id' => $kingdom->id,
        'location_type' => 'kingdom',
        'location_id' => $kingdom->id,
    ]);
}

test('visiting house without parlour shows house directly', function () {
    $kingdom = Kingdom::factory()->create();
    $owner = User::factory()->create();
    $visitor = User::factory()->create();
    createHouseWithoutParlour($owner, $kingdom);

    $this->actingAs($visitor)
        ->get("/players/{$owner->username}/house")
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('House/Index')
            ->where('isVisiting', true)
            ->has('house')
            ->missing('awaitingEntry')
        );
});

test('visiting house with parlour shows awaiting entry screen', function () {
    $kingdom = Kingdom::factory()->create();
    $owner = User::factory()->create();
    $visitor = User::factory()->create();
    createHouseWithParlour($owner, $kingdom);

    $this->actingAs($visitor)
        ->get("/players/{$owner->username}/house")
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('House/Index')
            ->where('isVisiting', true)
            ->where('awaitingEntry', true)
            ->where('house', null)
        );

    expect(Cache::get("house_entry:{$owner->id}:{$visitor->id}"))->toBe('pending');
});

test('owner visiting their own house with parlour skips entry gate', function () {
    $kingdom = Kingdom::factory()->create();
    $owner = User::factory()->create();
    createHouseWithParlour($owner, $kingdom);

    $this->actingAs($owner)
        ->get("/players/{$owner->username}/house")
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('House/Index')
            ->where('isVisiting', true)
            ->has('house')
        );
});

test('approved visitor sees full house', function () {
    $kingdom = Kingdom::factory()->create();
    $owner = User::factory()->create();
    $visitor = User::factory()->create();
    createHouseWithParlour($owner, $kingdom);

    Cache::put("house_entry:{$owner->id}:{$visitor->id}", 'approved', 300);

    $this->actingAs($visitor)
        ->get("/players/{$owner->username}/house")
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('House/Index')
            ->where('isVisiting', true)
            ->has('house')
            ->missing('awaitingEntry')
        );
});

test('owner can accept entry request', function () {
    $owner = User::factory()->create();
    $visitor = User::factory()->create();

    Cache::put("house_entry:{$owner->id}:{$visitor->id}", 'pending', 120);
    Cache::put("house_entry_requests:{$owner->id}", [
        $visitor->id => ['username' => $visitor->username, 'requested_at' => now()->timestamp],
    ], 120);

    $this->actingAs($owner)
        ->post('/house/respond-entry', ['visitor_id' => $visitor->id, 'action' => 'accept'])
        ->assertRedirect();

    expect(Cache::get("house_entry:{$owner->id}:{$visitor->id}"))->toBe('approved');
    expect(Cache::get("house_entry_requests:{$owner->id}", []))->not->toHaveKey($visitor->id);
});

test('owner can deny entry request', function () {
    $owner = User::factory()->create();
    $visitor = User::factory()->create();

    Cache::put("house_entry:{$owner->id}:{$visitor->id}", 'pending', 120);
    Cache::put("house_entry_requests:{$owner->id}", [
        $visitor->id => ['username' => $visitor->username, 'requested_at' => now()->timestamp],
    ], 120);

    $this->actingAs($owner)
        ->post('/house/respond-entry', ['visitor_id' => $visitor->id, 'action' => 'deny'])
        ->assertRedirect();

    expect(Cache::get("house_entry:{$owner->id}:{$visitor->id}"))->toBe('denied');
});

test('entry status returns pending when request is pending', function () {
    $owner = User::factory()->create();
    $visitor = User::factory()->create();

    Cache::put("house_entry:{$owner->id}:{$visitor->id}", 'pending', 120);

    $this->actingAs($visitor)
        ->getJson("/house/entry-status/{$owner->username}")
        ->assertSuccessful()
        ->assertJson(['status' => 'pending']);
});

test('entry status returns approved after owner accepts', function () {
    $owner = User::factory()->create();
    $visitor = User::factory()->create();

    Cache::put("house_entry:{$owner->id}:{$visitor->id}", 'approved', 300);

    $this->actingAs($visitor)
        ->getJson("/house/entry-status/{$owner->username}")
        ->assertSuccessful()
        ->assertJson(['status' => 'approved']);
});

test('entry status returns denied after owner denies', function () {
    $owner = User::factory()->create();
    $visitor = User::factory()->create();

    Cache::put("house_entry:{$owner->id}:{$visitor->id}", 'denied', 30);

    $this->actingAs($visitor)
        ->getJson("/house/entry-status/{$owner->username}")
        ->assertSuccessful()
        ->assertJson(['status' => 'denied']);
});

test('entry status returns expired when cache key missing', function () {
    $owner = User::factory()->create();
    $visitor = User::factory()->create();

    $this->actingAs($visitor)
        ->getJson("/house/entry-status/{$owner->username}")
        ->assertSuccessful()
        ->assertJson(['status' => 'expired']);
});

test('visitors endpoint includes pending requests for owner', function () {
    $kingdom = Kingdom::factory()->create();
    $owner = User::factory()->create();
    $visitor = User::factory()->create();
    createHouseWithParlour($owner, $kingdom);

    Cache::put("house_entry:{$owner->id}:{$visitor->id}", 'pending', 120);
    Cache::put("house_entry_requests:{$owner->id}", [
        $visitor->id => ['username' => $visitor->username, 'requested_at' => now()->timestamp],
    ], 120);

    $response = $this->actingAs($owner)
        ->getJson('/house/visitors')
        ->assertSuccessful();

    $data = $response->json();
    expect($data)->toHaveKey('pending_requests');
    expect($data['pending_requests'])->toHaveCount(1);
    expect($data['pending_requests'][0]['username'])->toBe($visitor->username);
    expect($data['pending_requests'][0]['visitor_id'])->toBe($visitor->id);
});

test('respond-entry validates action parameter', function () {
    $owner = User::factory()->create();

    $this->actingAs($owner)
        ->post('/house/respond-entry', ['visitor_id' => 1, 'action' => 'invalid'])
        ->assertSessionHasErrors('action');
});

test('unauthenticated visitor is redirected to login', function () {
    $kingdom = Kingdom::factory()->create();
    $owner = User::factory()->create();
    createHouseWithParlour($owner, $kingdom);

    $this->get("/players/{$owner->username}/house")
        ->assertRedirect('/login');
});

// --- Kick All Visitors ---

test('owner can kick all visitors', function () {
    $owner = User::factory()->create();
    $visitor1 = User::factory()->create();
    $visitor2 = User::factory()->create();

    // Set up approved visitors and pending requests
    Cache::put("house_entry:{$owner->id}:{$visitor1->id}", 'approved', 300);
    Cache::put("house_visitors:{$owner->id}", [
        $visitor1->id => ['username' => $visitor1->username, 'at' => now()->timestamp],
    ], 300);
    Cache::put("house_entry:{$owner->id}:{$visitor2->id}", 'pending', 120);
    Cache::put("house_entry_requests:{$owner->id}", [
        $visitor2->id => ['username' => $visitor2->username, 'requested_at' => now()->timestamp],
    ], 120);

    $this->actingAs($owner)
        ->post('/house/kick-all')
        ->assertRedirect()
        ->assertSessionHas('success');

    expect(Cache::get("house_entry:{$owner->id}:{$visitor1->id}"))->toBe('kicked');
    expect(Cache::get("house_entry:{$owner->id}:{$visitor2->id}"))->toBe('kicked');
    expect(Cache::get("house_visitors:{$owner->id}"))->toBeNull();
    expect(Cache::get("house_entry_requests:{$owner->id}"))->toBeNull();
});

test('entry status returns kicked after owner kicks', function () {
    $owner = User::factory()->create();
    $visitor = User::factory()->create();

    Cache::put("house_entry:{$owner->id}:{$visitor->id}", 'kicked', 30);

    $this->actingAs($visitor)
        ->getJson("/house/entry-status/{$owner->username}")
        ->assertSuccessful()
        ->assertJson(['status' => 'kicked']);
});

test('kick all with no visitors returns success message', function () {
    $owner = User::factory()->create();

    $this->actingAs($owner)
        ->post('/house/kick-all')
        ->assertRedirect()
        ->assertSessionHas('success', 'No visitors to kick.');
});

// --- Cancel Entry Request ---

test('visitor can cancel their own pending entry request', function () {
    $owner = User::factory()->create();
    $visitor = User::factory()->create();

    Cache::put("house_entry:{$owner->id}:{$visitor->id}", 'pending', 120);
    Cache::put("house_entry_requests:{$owner->id}", [
        $visitor->id => ['username' => $visitor->username, 'requested_at' => now()->timestamp],
    ], 120);

    $this->actingAs($visitor)
        ->post('/house/cancel-entry', ['username' => $owner->username])
        ->assertRedirect();

    expect(Cache::get("house_entry:{$owner->id}:{$visitor->id}"))->toBeNull();
    expect(Cache::get("house_entry_requests:{$owner->id}"))->toBeNull();
});

test('cancelling entry preserves other visitors requests', function () {
    $owner = User::factory()->create();
    $visitor1 = User::factory()->create();
    $visitor2 = User::factory()->create();

    Cache::put("house_entry:{$owner->id}:{$visitor1->id}", 'pending', 120);
    Cache::put("house_entry:{$owner->id}:{$visitor2->id}", 'pending', 120);
    Cache::put("house_entry_requests:{$owner->id}", [
        $visitor1->id => ['username' => $visitor1->username, 'requested_at' => now()->timestamp],
        $visitor2->id => ['username' => $visitor2->username, 'requested_at' => now()->timestamp],
    ], 120);

    $this->actingAs($visitor1)
        ->post('/house/cancel-entry', ['username' => $owner->username])
        ->assertRedirect();

    expect(Cache::get("house_entry:{$owner->id}:{$visitor1->id}"))->toBeNull();
    expect(Cache::get("house_entry:{$owner->id}:{$visitor2->id}"))->toBe('pending');
    expect(Cache::get("house_entry_requests:{$owner->id}"))->toHaveKey($visitor2->id);
});

// --- Visitor Amenity Access ---

test('approved visitor sees amenity data on house visit', function () {
    $kingdom = Kingdom::factory()->create();
    $owner = User::factory()->create();
    $visitor = User::factory()->create();
    $house = createHouseWithParlour($owner, $kingdom);

    // Add a kitchen with stove
    $kitchen = HouseRoom::create([
        'player_house_id' => $house->id,
        'room_type' => 'kitchen',
        'grid_x' => 1,
        'grid_y' => 0,
    ]);
    HouseFurniture::create([
        'house_room_id' => $kitchen->id,
        'hotspot_slug' => 'stove',
        'furniture_key' => 'firepit',
    ]);

    Cache::put("house_entry:{$owner->id}:{$visitor->id}", 'approved', 300);

    $this->actingAs($visitor)
        ->get("/players/{$owner->username}/house")
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('House/Index')
            ->where('isVisiting', true)
            ->has('house')
            ->has('visitingHouseId')
            ->has('kitchen')
        );
});

test('visitor without parlour sees amenity data', function () {
    $kingdom = Kingdom::factory()->create();
    $owner = User::factory()->create();
    $visitor = User::factory()->create();
    $house = createHouseWithoutParlour($owner, $kingdom);

    // Add a bedroom with bed
    $bedroom = HouseRoom::create([
        'player_house_id' => $house->id,
        'room_type' => 'bedroom',
        'grid_x' => 0,
        'grid_y' => 0,
    ]);
    HouseFurniture::create([
        'house_room_id' => $bedroom->id,
        'hotspot_slug' => 'bed',
        'furniture_key' => 'straw_bed',
    ]);

    $this->actingAs($visitor)
        ->get("/players/{$owner->username}/house")
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('House/Index')
            ->where('isVisiting', true)
            ->has('visitingHouseId')
            ->has('bedroom')
        );
});

test('visitor can rest at visited house bedroom', function () {
    $kingdom = Kingdom::factory()->create();
    $owner = User::factory()->create();
    $visitor = User::factory()->create(['energy' => 50, 'max_energy' => 100]);
    $house = createHouseWithoutParlour($owner, $kingdom);

    $bedroom = HouseRoom::create([
        'player_house_id' => $house->id,
        'room_type' => 'bedroom',
        'grid_x' => 0,
        'grid_y' => 0,
    ]);
    HouseFurniture::create([
        'house_room_id' => $bedroom->id,
        'hotspot_slug' => 'bed',
        'furniture_key' => 'straw_bed',
    ]);

    $this->actingAs($visitor)
        ->post('/house/rest', ['house_id' => $house->id])
        ->assertRedirect()
        ->assertSessionHas('success');

    $visitor->refresh();
    expect($visitor->energy)->toBeGreaterThan(50);
});

test('visitor cannot rest at house they have no access to', function () {
    $kingdom = Kingdom::factory()->create();
    $owner = User::factory()->create();
    $visitor = User::factory()->create();
    $house = createHouseWithParlour($owner, $kingdom);

    // No approved entry
    $this->actingAs($visitor)
        ->post('/house/rest', ['house_id' => $house->id])
        ->assertRedirect()
        ->assertSessionHas('error', 'You do not have access to this house.');
});

test('kicked visitor cannot use amenities', function () {
    $kingdom = Kingdom::factory()->create();
    $owner = User::factory()->create();
    $visitor = User::factory()->create();
    $house = createHouseWithParlour($owner, $kingdom);

    Cache::put("house_entry:{$owner->id}:{$visitor->id}", 'kicked', 30);

    $this->actingAs($visitor)
        ->post('/house/rest', ['house_id' => $house->id])
        ->assertRedirect()
        ->assertSessionHas('error', 'You do not have access to this house.');
});
