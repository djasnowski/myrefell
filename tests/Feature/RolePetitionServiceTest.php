<?php

use App\Models\Barony;
use App\Models\Kingdom;
use App\Models\PlayerRole;
use App\Models\Role;
use App\Models\RolePetition;
use App\Models\User;
use App\Models\Village;
use App\Services\RolePetitionService;

/**
 * Helper: create a village with elder (authority) + a guard_captain role + holder.
 */
function setupVillageWithRoles(): array
{
    $kingdom = Kingdom::factory()->create();
    $barony = Barony::factory()->create(['kingdom_id' => $kingdom->id]);
    $village = Village::factory()->create(['barony_id' => $barony->id]);

    $elderRole = Role::factory()->create([
        'slug' => 'elder',
        'name' => 'Elder',
        'location_type' => 'village',
    ]);

    $guardRole = Role::factory()->create([
        'slug' => 'guard_captain',
        'name' => 'Guard Captain',
        'location_type' => 'village',
    ]);

    $baronRole = Role::factory()->create([
        'slug' => 'baron',
        'name' => 'Baron',
        'location_type' => 'barony',
    ]);

    $kingRole = Role::factory()->create([
        'slug' => 'king',
        'name' => 'King',
        'location_type' => 'kingdom',
    ]);

    // Elder (authority for village roles)
    $elder = User::factory()->create([
        'current_location_type' => 'village',
        'current_location_id' => $village->id,
        'home_location_type' => 'village',
        'home_location_id' => $village->id,
    ]);
    $elderPlayerRole = PlayerRole::factory()->create([
        'user_id' => $elder->id,
        'role_id' => $elderRole->id,
        'location_type' => 'village',
        'location_id' => $village->id,
        'status' => 'active',
    ]);

    // Guard captain (target for petitions)
    $guardCaptain = User::factory()->create([
        'current_location_type' => 'village',
        'current_location_id' => $village->id,
        'home_location_type' => 'village',
        'home_location_id' => $village->id,
    ]);
    $guardPlayerRole = PlayerRole::factory()->create([
        'user_id' => $guardCaptain->id,
        'role_id' => $guardRole->id,
        'location_type' => 'village',
        'location_id' => $village->id,
        'status' => 'active',
    ]);

    // Baron (authority for elder)
    $baron = User::factory()->create([
        'current_location_type' => 'barony',
        'current_location_id' => $barony->id,
        'home_location_type' => 'barony',
        'home_location_id' => $barony->id,
    ]);
    $baronPlayerRole = PlayerRole::factory()->create([
        'user_id' => $baron->id,
        'role_id' => $baronRole->id,
        'location_type' => 'barony',
        'location_id' => $barony->id,
        'status' => 'active',
    ]);

    // King (authority for baron)
    $king = User::factory()->create([
        'current_location_type' => 'kingdom',
        'current_location_id' => $kingdom->id,
        'home_location_type' => 'kingdom',
        'home_location_id' => $kingdom->id,
    ]);
    $kingPlayerRole = PlayerRole::factory()->create([
        'user_id' => $king->id,
        'role_id' => $kingRole->id,
        'location_type' => 'kingdom',
        'location_id' => $kingdom->id,
        'status' => 'active',
    ]);

    // Petitioner (resident at village)
    $petitioner = User::factory()->create([
        'current_location_type' => 'village',
        'current_location_id' => $village->id,
        'home_location_type' => 'village',
        'home_location_id' => $village->id,
    ]);

    return compact(
        'kingdom', 'barony', 'village',
        'elderRole', 'guardRole', 'baronRole', 'kingRole',
        'elder', 'elderPlayerRole',
        'guardCaptain', 'guardPlayerRole',
        'baron', 'baronPlayerRole',
        'king', 'kingPlayerRole',
        'petitioner',
    );
}

test('petition against village guard goes to elder', function () {
    $data = setupVillageWithRoles();

    $service = app(RolePetitionService::class);
    $result = $service->createPetition(
        $data['petitioner'],
        $data['guardPlayerRole'],
        'Neglecting duties',
    );

    expect($result['success'])->toBeTrue();
    expect($result['petition']->authority_user_id)->toBe($data['elder']->id);
    expect($result['petition']->authority_role_slug)->toBe('elder');
});

test('petition against elder goes to baron', function () {
    $data = setupVillageWithRoles();

    // Petitioner needs to be at village and reside there
    $service = app(RolePetitionService::class);
    $result = $service->createPetition(
        $data['petitioner'],
        $data['elderPlayerRole'],
        'Corrupt leadership',
    );

    expect($result['success'])->toBeTrue();
    expect($result['petition']->authority_user_id)->toBe($data['baron']->id);
    expect($result['petition']->authority_role_slug)->toBe('baron');
});

test('petition against baron goes to king', function () {
    $data = setupVillageWithRoles();

    // Petitioner must be at barony and reside there
    $petitioner = User::factory()->create([
        'current_location_type' => 'barony',
        'current_location_id' => $data['barony']->id,
        'home_location_type' => 'village',
        'home_location_id' => $data['village']->id,
    ]);

    $service = app(RolePetitionService::class);
    $result = $service->createPetition(
        $petitioner,
        $data['baronPlayerRole'],
        'Tyrant baron',
    );

    expect($result['success'])->toBeTrue();
    expect($result['petition']->authority_user_id)->toBe($data['king']->id);
    expect($result['petition']->authority_role_slug)->toBe('king');
});

test('cannot petition against king', function () {
    $data = setupVillageWithRoles();

    $petitioner = User::factory()->create([
        'current_location_type' => 'kingdom',
        'current_location_id' => $data['kingdom']->id,
        'home_location_type' => 'kingdom',
        'home_location_id' => $data['kingdom']->id,
    ]);

    $service = app(RolePetitionService::class);
    $result = $service->createPetition(
        $petitioner,
        $data['kingPlayerRole'],
        'Bad king',
    );

    expect($result['success'])->toBeFalse();
    expect($result['message'])->toContain('no-confidence');
});

test('authority approves petition and removes role holder', function () {
    $data = setupVillageWithRoles();

    $service = app(RolePetitionService::class);
    $createResult = $service->createPetition(
        $data['petitioner'],
        $data['guardPlayerRole'],
        'Neglecting duties',
    );

    $petition = $createResult['petition'];
    $approveResult = $service->approvePetition($data['elder'], $petition, 'Agreed');

    expect($approveResult['success'])->toBeTrue();
    expect($petition->fresh()->status)->toBe(RolePetition::STATUS_APPROVED);
    expect($data['guardPlayerRole']->fresh()->status)->not->toBe('active');
});

test('authority approves with appointment gives petitioner the role', function () {
    $data = setupVillageWithRoles();

    $service = app(RolePetitionService::class);
    $createResult = $service->createPetition(
        $data['petitioner'],
        $data['guardPlayerRole'],
        'I can do better',
        true, // request appointment
    );

    $petition = $createResult['petition'];
    $approveResult = $service->approvePetition($data['elder'], $petition, 'Appointed');

    expect($approveResult['success'])->toBeTrue();

    // Petitioner should now hold the guard captain role
    $newRole = PlayerRole::where('user_id', $data['petitioner']->id)
        ->where('role_id', $data['guardRole']->id)
        ->active()
        ->first();
    expect($newRole)->not->toBeNull();
});

test('authority denies petition with response', function () {
    $data = setupVillageWithRoles();

    $service = app(RolePetitionService::class);
    $createResult = $service->createPetition(
        $data['petitioner'],
        $data['guardPlayerRole'],
        'Some complaint',
    );

    $petition = $createResult['petition'];
    $denyResult = $service->denyPetition($data['elder'], $petition, 'No grounds for removal');

    expect($denyResult['success'])->toBeTrue();
    expect($petition->fresh()->status)->toBe(RolePetition::STATUS_DENIED);
    expect($petition->fresh()->response_message)->toBe('No grounds for removal');
    // Guard captain should still be active
    expect($data['guardPlayerRole']->fresh()->status)->toBe('active');
});

test('petitioner can withdraw petition', function () {
    $data = setupVillageWithRoles();

    $service = app(RolePetitionService::class);
    $createResult = $service->createPetition(
        $data['petitioner'],
        $data['guardPlayerRole'],
        'Changed my mind later',
    );

    $petition = $createResult['petition'];
    $withdrawResult = $service->withdrawPetition($data['petitioner'], $petition);

    expect($withdrawResult['success'])->toBeTrue();
    expect($petition->fresh()->status)->toBe(RolePetition::STATUS_WITHDRAWN);
});

test('cannot create duplicate pending petition', function () {
    $data = setupVillageWithRoles();

    $service = app(RolePetitionService::class);
    $service->createPetition(
        $data['petitioner'],
        $data['guardPlayerRole'],
        'First petition',
    );

    $result = $service->createPetition(
        $data['petitioner'],
        $data['guardPlayerRole'],
        'Duplicate petition',
    );

    expect($result['success'])->toBeFalse();
    expect($result['message'])->toContain('pending petition');
});

test('petition expires after 7 days', function () {
    $data = setupVillageWithRoles();

    $service = app(RolePetitionService::class);
    $result = $service->createPetition(
        $data['petitioner'],
        $data['guardPlayerRole'],
        'Test reason',
    );

    expect($result['petition']->expires_at)->not->toBeNull();

    // Fast-forward: set expires_at in the past
    $result['petition']->update(['expires_at' => now()->subDay()]);

    expect($result['petition']->fresh()->hasExpired())->toBeTrue();

    // Authority cannot approve an expired petition
    $approveResult = $service->approvePetition($data['elder'], $result['petition']->fresh());
    expect($approveResult['success'])->toBeFalse();
    expect($approveResult['message'])->toContain('expired');
});

test('pending petitions counted in authority pending count', function () {
    $data = setupVillageWithRoles();

    $service = app(RolePetitionService::class);

    expect($service->getPendingCountForAuthority($data['elder']->id))->toBe(0);

    $service->createPetition(
        $data['petitioner'],
        $data['guardPlayerRole'],
        'First',
    );

    expect($service->getPendingCountForAuthority($data['elder']->id))->toBe(1);

    // Create another petitioner
    $petitioner2 = User::factory()->create([
        'current_location_type' => 'village',
        'current_location_id' => $data['village']->id,
        'home_location_type' => 'village',
        'home_location_id' => $data['village']->id,
    ]);
    $service->createPetition(
        $petitioner2,
        $data['guardPlayerRole'],
        'Second',
    );

    expect($service->getPendingCountForAuthority($data['elder']->id))->toBe(2);
});

test('cannot petition if not at location', function () {
    $data = setupVillageWithRoles();
    $otherVillage = Village::factory()->create();

    $awayPetitioner = User::factory()->create([
        'current_location_type' => 'village',
        'current_location_id' => $otherVillage->id,
        'home_location_type' => 'village',
        'home_location_id' => $data['village']->id,
    ]);

    $service = app(RolePetitionService::class);
    $result = $service->createPetition(
        $awayPetitioner,
        $data['guardPlayerRole'],
        'Not at location',
    );

    expect($result['success'])->toBeFalse();
    expect($result['message'])->toContain('at this location');
});

test('cannot petition if not a resident', function () {
    $data = setupVillageWithRoles();
    $otherVillage = Village::factory()->create();

    $nonResident = User::factory()->create([
        'current_location_type' => 'village',
        'current_location_id' => $data['village']->id,
        'home_location_type' => 'village',
        'home_location_id' => $otherVillage->id,
    ]);

    $service = app(RolePetitionService::class);
    $result = $service->createPetition(
        $nonResident,
        $data['guardPlayerRole'],
        'Not a resident',
    );

    expect($result['success'])->toBeFalse();
    expect($result['message'])->toContain('resident');
});

test('cannot petition against yourself', function () {
    $data = setupVillageWithRoles();

    $service = app(RolePetitionService::class);
    $result = $service->createPetition(
        $data['guardCaptain'],
        $data['guardPlayerRole'],
        'Self petition',
    );

    expect($result['success'])->toBeFalse();
    expect($result['message'])->toContain('yourself');
});

test('cannot petition against inactive role', function () {
    $data = setupVillageWithRoles();

    $data['guardPlayerRole']->update(['status' => 'resigned']);

    $service = app(RolePetitionService::class);
    $result = $service->createPetition(
        $data['petitioner'],
        $data['guardPlayerRole'],
        'Inactive role',
    );

    expect($result['success'])->toBeFalse();
    expect($result['message'])->toContain('no longer active');
});

test('unauthorized user cannot approve petition', function () {
    $data = setupVillageWithRoles();

    $service = app(RolePetitionService::class);
    $createResult = $service->createPetition(
        $data['petitioner'],
        $data['guardPlayerRole'],
        'Test',
    );

    $randomUser = User::factory()->create();
    $approveResult = $service->approvePetition($randomUser, $createResult['petition']);

    expect($approveResult['success'])->toBeFalse();
    expect($approveResult['message'])->toContain('not authorized');
});

test('unauthorized user cannot deny petition', function () {
    $data = setupVillageWithRoles();

    $service = app(RolePetitionService::class);
    $createResult = $service->createPetition(
        $data['petitioner'],
        $data['guardPlayerRole'],
        'Test',
    );

    $randomUser = User::factory()->create();
    $denyResult = $service->denyPetition($randomUser, $createResult['petition']);

    expect($denyResult['success'])->toBeFalse();
    expect($denyResult['message'])->toContain('not authorized');
});

test('other user cannot withdraw petition', function () {
    $data = setupVillageWithRoles();

    $service = app(RolePetitionService::class);
    $createResult = $service->createPetition(
        $data['petitioner'],
        $data['guardPlayerRole'],
        'Test',
    );

    $randomUser = User::factory()->create();
    $withdrawResult = $service->withdrawPetition($randomUser, $createResult['petition']);

    expect($withdrawResult['success'])->toBeFalse();
    expect($withdrawResult['message'])->toContain('not your petition');
});

test('fails when authority position is vacant', function () {
    $village = Village::factory()->create();

    $guardRole = Role::factory()->create([
        'slug' => 'guard_captain',
        'name' => 'Guard Captain',
        'location_type' => 'village',
    ]);

    // Create elder role but don't assign anyone to it
    Role::factory()->create([
        'slug' => 'elder',
        'name' => 'Elder',
        'location_type' => 'village',
    ]);

    $holder = User::factory()->create([
        'current_location_type' => 'village',
        'current_location_id' => $village->id,
        'home_location_type' => 'village',
        'home_location_id' => $village->id,
    ]);
    $guardPlayerRole = PlayerRole::factory()->create([
        'user_id' => $holder->id,
        'role_id' => $guardRole->id,
        'location_type' => 'village',
        'location_id' => $village->id,
        'status' => 'active',
    ]);

    $petitioner = User::factory()->create([
        'current_location_type' => 'village',
        'current_location_id' => $village->id,
        'home_location_type' => 'village',
        'home_location_id' => $village->id,
    ]);

    $service = app(RolePetitionService::class);
    $result = $service->createPetition($petitioner, $guardPlayerRole, 'No elder to review');

    expect($result['success'])->toBeFalse();
    expect($result['message'])->toContain('vacant');
});

test('can petition again after previous petition was denied', function () {
    $data = setupVillageWithRoles();

    $service = app(RolePetitionService::class);

    // First petition - denied
    $first = $service->createPetition(
        $data['petitioner'],
        $data['guardPlayerRole'],
        'First attempt',
    );
    $service->denyPetition($data['elder'], $first['petition']);

    // Second petition should succeed since first is no longer pending
    $second = $service->createPetition(
        $data['petitioner'],
        $data['guardPlayerRole'],
        'Second attempt',
    );

    expect($second['success'])->toBeTrue();
});

test('cannot approve already resolved petition', function () {
    $data = setupVillageWithRoles();

    $service = app(RolePetitionService::class);
    $createResult = $service->createPetition(
        $data['petitioner'],
        $data['guardPlayerRole'],
        'Test',
    );

    $petition = $createResult['petition'];
    $service->denyPetition($data['elder'], $petition);

    // Try to approve the already-denied petition
    $approveResult = $service->approvePetition($data['elder'], $petition->fresh());
    expect($approveResult['success'])->toBeFalse();
    expect($approveResult['message'])->toContain('no longer pending');
});
