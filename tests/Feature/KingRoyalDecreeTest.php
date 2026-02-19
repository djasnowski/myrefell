<?php

use App\Models\Barony;
use App\Models\Kingdom;
use App\Models\LocationActivityLog;
use App\Models\PlayerRole;
use App\Models\Role;
use App\Models\User;
use App\Models\Village;
use App\Services\RoleService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    User::query()->delete();

    $this->kingdom = Kingdom::factory()->create();
    $this->barony = Barony::factory()->create(['kingdom_id' => $this->kingdom->id]);
    $this->village = Village::factory()->create(['barony_id' => $this->barony->id]);

    $this->kingRole = Role::factory()->create([
        'name' => 'King',
        'slug' => 'king',
        'location_type' => 'kingdom',
        'tier' => 7,
        'permissions' => ['rule_kingdom', 'appoint_roles', 'remove_roles', 'set_kingdom_taxes'],
    ]);

    $this->baronRole = Role::factory()->create([
        'name' => 'Baron',
        'slug' => 'baron',
        'location_type' => 'barony',
        'tier' => 5,
        'permissions' => ['rule_barony', 'appoint_roles', 'remove_roles'],
    ]);

    $this->elderRole = Role::factory()->create([
        'name' => 'Elder',
        'slug' => 'elder',
        'location_type' => 'village',
        'tier' => 4,
        'permissions' => ['approve_migration'],
    ]);

    $this->chancellorRole = Role::factory()->create([
        'name' => 'Chancellor',
        'slug' => 'chancellor',
        'location_type' => 'kingdom',
        'tier' => 6,
        'permissions' => [],
    ]);

    // Create the King
    $this->king = User::factory()->create();
    $this->kingdom->update(['king_user_id' => $this->king->id]);
    $this->kingPlayerRole = PlayerRole::factory()->create([
        'user_id' => $this->king->id,
        'role_id' => $this->kingRole->id,
        'location_type' => 'kingdom',
        'location_id' => $this->kingdom->id,
        'status' => 'active',
    ]);
});

test('king can remove a baron in their kingdom', function () {
    $baron = User::factory()->create();
    $baronPlayerRole = PlayerRole::factory()->create([
        'user_id' => $baron->id,
        'role_id' => $this->baronRole->id,
        'location_type' => 'barony',
        'location_id' => $this->barony->id,
        'status' => 'active',
    ]);

    $response = $this->actingAs($this->king)->post("/roles/{$baronPlayerRole->id}/remove", [
        'reason' => 'Incompetence',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $baronPlayerRole->refresh();
    expect($baronPlayerRole->status)->toBe('removed');
});

test('king can remove an elder in a village within their kingdom', function () {
    $elder = User::factory()->create();
    $elderPlayerRole = PlayerRole::factory()->create([
        'user_id' => $elder->id,
        'role_id' => $this->elderRole->id,
        'location_type' => 'village',
        'location_id' => $this->village->id,
        'status' => 'active',
    ]);

    $response = $this->actingAs($this->king)->post("/roles/{$elderPlayerRole->id}/remove");

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $elderPlayerRole->refresh();
    expect($elderPlayerRole->status)->toBe('removed');
});

test('king can remove a kingdom-level appointed role', function () {
    $chancellor = User::factory()->create();
    $chancellorPlayerRole = PlayerRole::factory()->create([
        'user_id' => $chancellor->id,
        'role_id' => $this->chancellorRole->id,
        'location_type' => 'kingdom',
        'location_id' => $this->kingdom->id,
        'status' => 'active',
    ]);

    $response = $this->actingAs($this->king)->post("/roles/{$chancellorPlayerRole->id}/remove");

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $chancellorPlayerRole->refresh();
    expect($chancellorPlayerRole->status)->toBe('removed');
});

test('king cannot remove roles in a different kingdom', function () {
    $otherKingdom = Kingdom::factory()->create();
    $otherBarony = Barony::factory()->create(['kingdom_id' => $otherKingdom->id]);

    $otherBaron = User::factory()->create();
    $otherBaronRole = PlayerRole::factory()->create([
        'user_id' => $otherBaron->id,
        'role_id' => $this->baronRole->id,
        'location_type' => 'barony',
        'location_id' => $otherBarony->id,
        'status' => 'active',
    ]);

    $response = $this->actingAs($this->king)->post("/roles/{$otherBaronRole->id}/remove");

    $response->assertRedirect();
    $response->assertSessionHas('error');

    $otherBaronRole->refresh();
    expect($otherBaronRole->status)->toBe('active');
});

test('king cannot remove themselves', function () {
    $response = $this->actingAs($this->king)->post("/roles/{$this->kingPlayerRole->id}/remove");

    $response->assertRedirect();
    $response->assertSessionHas('error', 'You cannot remove yourself from a role. Use resign instead.');

    $this->kingPlayerRole->refresh();
    expect($this->kingPlayerRole->status)->toBe('active');
});

test('non-king cannot use hierarchical removal', function () {
    $randomUser = User::factory()->create();

    $baron = User::factory()->create();
    $baronPlayerRole = PlayerRole::factory()->create([
        'user_id' => $baron->id,
        'role_id' => $this->baronRole->id,
        'location_type' => 'barony',
        'location_id' => $this->barony->id,
        'status' => 'active',
    ]);

    $response = $this->actingAs($randomUser)->post("/roles/{$baronPlayerRole->id}/remove");

    $response->assertRedirect();
    $response->assertSessionHas('error');

    $baronPlayerRole->refresh();
    expect($baronPlayerRole->status)->toBe('active');
});

test('activity log contains royal decree subtype for king removal of baron', function () {
    $baron = User::factory()->create();
    $baronPlayerRole = PlayerRole::factory()->create([
        'user_id' => $baron->id,
        'role_id' => $this->baronRole->id,
        'location_type' => 'barony',
        'location_id' => $this->barony->id,
        'status' => 'active',
    ]);

    $this->actingAs($this->king)->post("/roles/{$baronPlayerRole->id}/remove");

    // Check log at target location (barony)
    $baronyLog = LocationActivityLog::where('location_type', 'barony')
        ->where('location_id', $this->barony->id)
        ->where('activity_subtype', 'royal_decree')
        ->first();

    expect($baronyLog)->not->toBeNull();
    expect($baronyLog->description)->toContain('King');
    expect($baronyLog->description)->toContain($this->king->username);
    expect($baronyLog->metadata['royal_decree'])->toBeTrue();

    // Check log at kingdom level too
    $kingdomLog = LocationActivityLog::where('location_type', 'kingdom')
        ->where('location_id', $this->kingdom->id)
        ->where('activity_subtype', 'royal_decree')
        ->first();

    expect($kingdomLog)->not->toBeNull();
    expect($kingdomLog->description)->toContain('King');
});

test('activity log is created at both target location and kingdom level', function () {
    $elder = User::factory()->create();
    $elderPlayerRole = PlayerRole::factory()->create([
        'user_id' => $elder->id,
        'role_id' => $this->elderRole->id,
        'location_type' => 'village',
        'location_id' => $this->village->id,
        'status' => 'active',
    ]);

    $this->actingAs($this->king)->post("/roles/{$elderPlayerRole->id}/remove");

    // Log at village level
    $villageLog = LocationActivityLog::where('location_type', 'village')
        ->where('location_id', $this->village->id)
        ->where('activity_subtype', 'royal_decree')
        ->first();

    expect($villageLog)->not->toBeNull();

    // Log at kingdom level
    $kingdomLog = LocationActivityLog::where('location_type', 'kingdom')
        ->where('location_id', $this->kingdom->id)
        ->where('activity_subtype', 'royal_decree')
        ->first();

    expect($kingdomLog)->not->toBeNull();
});

test('baron can remove village roles in their barony via hierarchical authority', function () {
    $baronUser = User::factory()->create();
    PlayerRole::factory()->create([
        'user_id' => $baronUser->id,
        'role_id' => $this->baronRole->id,
        'location_type' => 'barony',
        'location_id' => $this->barony->id,
        'status' => 'active',
    ]);

    $elder = User::factory()->create();
    $elderPlayerRole = PlayerRole::factory()->create([
        'user_id' => $elder->id,
        'role_id' => $this->elderRole->id,
        'location_type' => 'village',
        'location_id' => $this->village->id,
        'status' => 'active',
    ]);

    $response = $this->actingAs($baronUser)->post("/roles/{$elderPlayerRole->id}/remove");

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $elderPlayerRole->refresh();
    expect($elderPlayerRole->status)->toBe('removed');
});

test('hasHierarchicalRemovalAuthority returns true for king removing baron', function () {
    $baron = User::factory()->create();
    $baronPlayerRole = PlayerRole::factory()->create([
        'user_id' => $baron->id,
        'role_id' => $this->baronRole->id,
        'location_type' => 'barony',
        'location_id' => $this->barony->id,
        'status' => 'active',
    ]);

    $service = app(RoleService::class);
    expect($service->hasHierarchicalRemovalAuthority($this->king, $baronPlayerRole))->toBeTrue();
});

test('hasHierarchicalRemovalAuthority returns false for different kingdom', function () {
    $otherKingdom = Kingdom::factory()->create();
    $otherBarony = Barony::factory()->create(['kingdom_id' => $otherKingdom->id]);

    $otherBaron = User::factory()->create();
    $otherBaronRole = PlayerRole::factory()->create([
        'user_id' => $otherBaron->id,
        'role_id' => $this->baronRole->id,
        'location_type' => 'barony',
        'location_id' => $otherBarony->id,
        'status' => 'active',
    ]);

    $service = app(RoleService::class);
    expect($service->hasHierarchicalRemovalAuthority($this->king, $otherBaronRole))->toBeFalse();
});
