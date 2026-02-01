<?php

use App\Models\User;
use App\Models\UserServiceFavorite;

beforeEach(function () {
    UserServiceFavorite::query()->delete();
    User::query()->delete();
});

test('user can add a service to favorites', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/services/favorites/toggle', ['service_id' => 'training'])
        ->assertRedirect();

    expect(UserServiceFavorite::where('user_id', $user->id)->where('service_id', 'training')->exists())->toBeTrue();
});

test('user can remove a service from favorites', function () {
    $user = User::factory()->create();

    // Add favorite first
    UserServiceFavorite::create([
        'user_id' => $user->id,
        'service_id' => 'training',
        'sort_order' => 1,
    ]);

    // Toggle should remove it
    $this->actingAs($user)
        ->post('/services/favorites/toggle', ['service_id' => 'training'])
        ->assertRedirect();

    expect(UserServiceFavorite::where('user_id', $user->id)->where('service_id', 'training')->exists())->toBeFalse();
});

test('invalid service_id is rejected', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/services/favorites/toggle', ['service_id' => 'nonexistent_service'])
        ->assertSessionHas('error', 'Invalid service.');

    expect(UserServiceFavorite::where('user_id', $user->id)->count())->toBe(0);
});

test('service_id is required', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/services/favorites/toggle', [])
        ->assertSessionHasErrors('service_id');
});

test('favorites are sorted by sort_order', function () {
    $user = User::factory()->create();

    // Add favorites in specific order
    UserServiceFavorite::create([
        'user_id' => $user->id,
        'service_id' => 'market',
        'sort_order' => 2,
    ]);

    UserServiceFavorite::create([
        'user_id' => $user->id,
        'service_id' => 'training',
        'sort_order' => 1,
    ]);

    $favorites = $user->serviceFavorites()->pluck('service_id')->toArray();

    expect($favorites)->toBe(['training', 'market']);
});

test('new favorites get incremented sort_order', function () {
    $user = User::factory()->create();

    // Add first favorite
    $this->actingAs($user)
        ->post('/services/favorites/toggle', ['service_id' => 'training']);

    // Add second favorite
    $this->actingAs($user)
        ->post('/services/favorites/toggle', ['service_id' => 'market']);

    $trainingFavorite = UserServiceFavorite::where('user_id', $user->id)->where('service_id', 'training')->first();
    $marketFavorite = UserServiceFavorite::where('user_id', $user->id)->where('service_id', 'market')->first();

    expect($trainingFavorite->sort_order)->toBe(1);
    expect($marketFavorite->sort_order)->toBe(2);
});

test('user cannot favorite same service twice', function () {
    $user = User::factory()->create();

    // Add favorite
    $this->actingAs($user)
        ->post('/services/favorites/toggle', ['service_id' => 'training']);

    // Try to add again (should toggle off, not create duplicate)
    $this->actingAs($user)
        ->post('/services/favorites/toggle', ['service_id' => 'training']);

    expect(UserServiceFavorite::where('user_id', $user->id)->where('service_id', 'training')->count())->toBe(0);
});

test('unauthenticated user cannot toggle favorites', function () {
    $this->post('/services/favorites/toggle', ['service_id' => 'training'])
        ->assertRedirect('/login');
});
