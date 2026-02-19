<?php

use App\Models\Barony;
use App\Models\Kingdom;
use App\Models\Role;
use App\Models\User;
use App\Models\Village;
use App\Services\RoleService;

it('promotes user to noble when appointed as baron', function () {
    $kingdom = Kingdom::factory()->create();
    $barony = Barony::factory()->create(['kingdom_id' => $kingdom->id]);

    $role = Role::factory()->create([
        'slug' => 'baron',
        'name' => 'Baron',
        'location_type' => 'barony',
        'tier' => 5,
    ]);

    $user = User::factory()->create(['social_class' => 'burgher']);

    $service = app(RoleService::class);
    $result = $service->appointRole($user, $role, 'barony', $barony->id);

    expect($result['success'])->toBeTrue();
    expect($user->fresh()->social_class)->toBe('noble');
});

it('promotes user to noble when appointed as king', function () {
    $kingdom = Kingdom::factory()->create();

    $role = Role::factory()->create([
        'slug' => 'king',
        'name' => 'King',
        'location_type' => 'kingdom',
        'tier' => 7,
    ]);

    $user = User::factory()->create(['social_class' => 'freeman']);

    $service = app(RoleService::class);
    $result = $service->appointRole($user, $role, 'kingdom', $kingdom->id);

    expect($result['success'])->toBeTrue();
    expect($user->fresh()->social_class)->toBe('noble');
});

it('does not change social class for low tier roles', function () {
    $kingdom = Kingdom::factory()->create();
    $barony = Barony::factory()->create(['kingdom_id' => $kingdom->id]);
    $village = Village::factory()->create(['barony_id' => $barony->id]);

    $role = Role::factory()->create([
        'slug' => 'guard_captain',
        'name' => 'Guard Captain',
        'location_type' => 'village',
        'tier' => 3,
    ]);

    $user = User::factory()->create(['social_class' => 'freeman']);

    $service = app(RoleService::class);
    $result = $service->appointRole($user, $role, 'village', $village->id);

    expect($result['success'])->toBeTrue();
    expect($user->fresh()->social_class)->toBe('freeman');
});

it('records social class change in history', function () {
    $kingdom = Kingdom::factory()->create();

    $role = Role::factory()->create([
        'slug' => 'king',
        'name' => 'King',
        'location_type' => 'kingdom',
        'tier' => 7,
    ]);

    $user = User::factory()->create(['social_class' => 'burgher']);

    $service = app(RoleService::class);
    $service->appointRole($user, $role, 'kingdom', $kingdom->id);

    $history = \App\Models\SocialClassHistory::where('user_id', $user->id)->latest()->first();
    expect($history)->not->toBeNull();
    expect($history->old_class)->toBe('burgher');
    expect($history->new_class)->toBe('noble');
    expect($history->reason)->toContain('King');
});
