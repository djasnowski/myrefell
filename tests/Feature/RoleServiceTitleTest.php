<?php

use App\Models\PlayerTitle;
use App\Models\Role;
use App\Models\User;
use App\Models\Village;
use App\Services\RoleService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    User::query()->delete();
});

test('claiming a tier 3 role grants freeman title', function () {
    $village = Village::factory()->create();
    $role = Role::factory()->create([
        'location_type' => 'village',
        'tier' => 3,
        'is_elected' => false,
        'slug' => 'guard-captain',
        'name' => 'Guard Captain',
    ]);

    $user = User::factory()->create([
        'home_location_type' => 'village',
        'home_location_id' => $village->id,
        'current_location_type' => 'village',
        'current_location_id' => $village->id,
        'primary_title' => 'peasant',
        'title_tier' => 2,
    ]);

    $service = app(RoleService::class);
    $result = $service->selfAppoint($user, $role, 'village', $village->id);

    expect($result['success'])->toBeTrue();

    $user->refresh();
    expect($user->primary_title)->toBe('freeman');
    expect($user->title_tier)->toBe(3);
});

test('claiming a tier 4 role grants yeoman title', function () {
    $village = Village::factory()->create();
    $role = Role::factory()->create([
        'location_type' => 'village',
        'tier' => 4,
        'is_elected' => false,
        'slug' => 'elder',
        'name' => 'Village Elder',
    ]);

    $user = User::factory()->create([
        'home_location_type' => 'village',
        'home_location_id' => $village->id,
        'current_location_type' => 'village',
        'current_location_id' => $village->id,
        'primary_title' => 'peasant',
        'title_tier' => 2,
    ]);

    $service = app(RoleService::class);
    $result = $service->selfAppoint($user, $role, 'village', $village->id);

    expect($result['success'])->toBeTrue();

    $user->refresh();
    expect($user->primary_title)->toBe('yeoman');
    expect($user->title_tier)->toBe(4);
});

test('claiming a tier 1 role does not change peasant title', function () {
    $village = Village::factory()->create();
    $role = Role::factory()->create([
        'location_type' => 'village',
        'tier' => 1,
        'is_elected' => false,
        'slug' => 'town-crier',
        'name' => 'Town Crier',
    ]);

    $user = User::factory()->create([
        'home_location_type' => 'village',
        'home_location_id' => $village->id,
        'current_location_type' => 'village',
        'current_location_id' => $village->id,
        'primary_title' => 'peasant',
        'title_tier' => 2,
    ]);

    $service = app(RoleService::class);
    $result = $service->selfAppoint($user, $role, 'village', $village->id);

    expect($result['success'])->toBeTrue();

    $user->refresh();
    expect($user->primary_title)->toBe('peasant');
    expect($user->title_tier)->toBe(2);
});

test('claiming a lower tier role does not downgrade title', function () {
    $village = Village::factory()->create();
    $role = Role::factory()->create([
        'location_type' => 'village',
        'tier' => 2,
        'is_elected' => false,
        'slug' => 'blacksmith',
        'name' => 'Blacksmith',
    ]);

    $user = User::factory()->create([
        'home_location_type' => 'village',
        'home_location_id' => $village->id,
        'current_location_type' => 'village',
        'current_location_id' => $village->id,
        'primary_title' => 'yeoman',
        'title_tier' => 4,
    ]);

    $service = app(RoleService::class);
    $result = $service->selfAppoint($user, $role, 'village', $village->id);

    expect($result['success'])->toBeTrue();

    $user->refresh();
    expect($user->primary_title)->toBe('yeoman');
    expect($user->title_tier)->toBe(4);
});

test('resigning from a role reverts title to peasant', function () {
    $village = Village::factory()->create();
    $role = Role::factory()->create([
        'location_type' => 'village',
        'tier' => 4,
        'is_elected' => false,
        'slug' => 'elder',
        'name' => 'Village Elder',
    ]);

    $user = User::factory()->create([
        'home_location_type' => 'village',
        'home_location_id' => $village->id,
        'current_location_type' => 'village',
        'current_location_id' => $village->id,
        'primary_title' => 'peasant',
        'title_tier' => 2,
    ]);

    $service = app(RoleService::class);
    $service->selfAppoint($user, $role, 'village', $village->id);

    $user->refresh();
    expect($user->primary_title)->toBe('yeoman');

    $playerRole = $user->playerRoles()->active()->first();
    $result = $service->resignFromRole($user, $playerRole);

    expect($result['success'])->toBeTrue();

    $user->refresh();
    expect($user->primary_title)->toBe('peasant');
    expect($user->title_tier)->toBe(2);
});
