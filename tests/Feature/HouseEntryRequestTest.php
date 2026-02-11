<?php

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

test('unauthenticated visitor visiting house with parlour sees house directly', function () {
    $kingdom = Kingdom::factory()->create();
    $owner = User::factory()->create();
    createHouseWithParlour($owner, $kingdom);

    $this->get("/players/{$owner->username}/house")
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('House/Index')
            ->where('isVisiting', true)
            ->has('house')
        );
});
