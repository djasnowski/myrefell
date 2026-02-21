<?php

use App\Models\Barony;
use App\Models\Kingdom;
use App\Models\PlayerRole;
use App\Models\Role;
use App\Models\User;
use App\Services\RoleService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    User::query()->delete();

    $this->kingdom = Kingdom::factory()->create();
    $this->barony = Barony::factory()->create(['kingdom_id' => $this->kingdom->id]);

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
        'max_per_location' => 1,
        'permissions' => ['rule_barony', 'appoint_roles', 'remove_roles'],
    ]);

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

test('king can appoint a baron to a vacant barony via POST /roles/appoint', function () {
    $candidate = User::factory()->create();

    $response = $this->actingAs($this->king)->post('/roles/appoint', [
        'user_id' => $candidate->id,
        'role_id' => $this->baronRole->id,
        'location_type' => 'barony',
        'location_id' => $this->barony->id,
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    expect(PlayerRole::where('user_id', $candidate->id)
        ->where('role_id', $this->baronRole->id)
        ->where('location_type', 'barony')
        ->where('location_id', $this->barony->id)
        ->active()
        ->exists()
    )->toBeTrue();
});

test('non-king cannot appoint a baron', function () {
    $randomUser = User::factory()->create();
    $candidate = User::factory()->create();

    $response = $this->actingAs($randomUser)->post('/roles/appoint', [
        'user_id' => $candidate->id,
        'role_id' => $this->baronRole->id,
        'location_type' => 'barony',
        'location_id' => $this->barony->id,
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('error');

    expect(PlayerRole::where('user_id', $candidate->id)
        ->where('role_id', $this->baronRole->id)
        ->active()
        ->exists()
    )->toBeFalse();
});

test('king cannot appoint a baron to a barony outside their kingdom', function () {
    $otherKingdom = Kingdom::factory()->create();
    $otherBarony = Barony::factory()->create(['kingdom_id' => $otherKingdom->id]);
    $candidate = User::factory()->create();

    $response = $this->actingAs($this->king)->post('/roles/appoint', [
        'user_id' => $candidate->id,
        'role_id' => $this->baronRole->id,
        'location_type' => 'barony',
        'location_id' => $otherBarony->id,
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('error');

    expect(PlayerRole::where('user_id', $candidate->id)
        ->where('role_id', $this->baronRole->id)
        ->active()
        ->exists()
    )->toBeFalse();
});

test('appointing a baron to a barony that already has one fails', function () {
    $existingBaron = User::factory()->create();
    PlayerRole::factory()->create([
        'user_id' => $existingBaron->id,
        'role_id' => $this->baronRole->id,
        'location_type' => 'barony',
        'location_id' => $this->barony->id,
        'status' => 'active',
    ]);

    $candidate = User::factory()->create();

    $response = $this->actingAs($this->king)->post('/roles/appoint', [
        'user_id' => $candidate->id,
        'role_id' => $this->baronRole->id,
        'location_type' => 'barony',
        'location_id' => $this->barony->id,
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('error');

    expect(PlayerRole::where('user_id', $candidate->id)
        ->where('role_id', $this->baronRole->id)
        ->active()
        ->exists()
    )->toBeFalse();
});

test('hasHierarchicalAppointmentAuthority returns true for king appointing in their kingdom barony', function () {
    $roleService = app(RoleService::class);

    expect($roleService->hasHierarchicalAppointmentAuthority(
        $this->king,
        'barony',
        $this->barony->id,
    ))->toBeTrue();
});

test('hasHierarchicalAppointmentAuthority returns false for king appointing in another kingdoms barony', function () {
    $otherKingdom = Kingdom::factory()->create();
    $otherBarony = Barony::factory()->create(['kingdom_id' => $otherKingdom->id]);

    $roleService = app(RoleService::class);

    expect($roleService->hasHierarchicalAppointmentAuthority(
        $this->king,
        'barony',
        $otherBarony->id,
    ))->toBeFalse();
});
