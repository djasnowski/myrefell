<?php

use App\Models\Accusation;
use App\Models\Barony;
use App\Models\Charter;
use App\Models\Kingdom;
use App\Models\ManumissionRequest;
use App\Models\MigrationRequest;
use App\Models\PlayerRole;
use App\Models\Role;
use App\Models\RolePetition;
use App\Models\User;
use App\Models\Village;

it('returns empty state when user has no active role', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/roles/duties')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Roles/Duties')
            ->where('role', null)
            ->where('categories', [])
        );
});

it('shows migration and accusation categories for elder', function () {
    $kingdom = Kingdom::factory()->create();
    $barony = Barony::factory()->create(['kingdom_id' => $kingdom->id]);
    $village = Village::factory()->create(['barony_id' => $barony->id]);

    $elderRole = Role::factory()->create([
        'slug' => 'elder',
        'name' => 'Elder',
        'location_type' => 'village',
    ]);

    $user = User::factory()->create();
    PlayerRole::factory()->create([
        'user_id' => $user->id,
        'role_id' => $elderRole->id,
        'location_type' => 'village',
        'location_id' => $village->id,
        'status' => 'active',
    ]);

    // Create a pending migration request at this village
    $migrant = User::factory()->create();
    $fromVillage = Village::factory()->create(['barony_id' => $barony->id]);
    MigrationRequest::create([
        'user_id' => $migrant->id,
        'from_village_id' => $fromVillage->id,
        'to_village_id' => $village->id,
        'status' => 'pending',
    ]);

    // Create a pending accusation at this village
    $accuser = User::factory()->create();
    $accused = User::factory()->create();
    $crimeType = \App\Models\CrimeType::create([
        'slug' => 'theft',
        'name' => 'Theft',
        'description' => 'Stealing',
        'severity' => 'minor',
        'court_level' => 'village',
    ]);
    Accusation::create([
        'accuser_id' => $accuser->id,
        'accused_id' => $accused->id,
        'crime_type_id' => $crimeType->id,
        'location_type' => 'village',
        'location_id' => $village->id,
        'accusation_text' => 'Test accusation',
        'status' => 'pending',
    ]);

    $this->actingAs($user)
        ->get('/roles/duties')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Roles/Duties')
            ->where('role.slug', 'elder')
            ->where('role.location_name', $village->name)
            ->has('categories', 3)
            ->where('categories.0.key', 'migrations')
            ->where('categories.0.count', 1)
            ->where('categories.1.key', 'accusations')
            ->where('categories.1.count', 1)
            ->where('categories.2.key', 'petitions')
            ->where('categories.2.count', 0)
        );
});

it('shows charter and ennoblement categories for king', function () {
    $kingdom = Kingdom::factory()->create();

    $kingRole = Role::factory()->create([
        'slug' => 'king',
        'name' => 'King',
        'location_type' => 'kingdom',
    ]);

    $user = User::factory()->create();
    PlayerRole::factory()->create([
        'user_id' => $user->id,
        'role_id' => $kingRole->id,
        'location_type' => 'kingdom',
        'location_id' => $kingdom->id,
        'status' => 'active',
    ]);

    // Create a pending charter
    $founder = User::factory()->create();
    Charter::create([
        'settlement_name' => 'New Town',
        'settlement_type' => 'village',
        'kingdom_id' => $kingdom->id,
        'founder_id' => $founder->id,
        'status' => 'pending',
    ]);

    $this->actingAs($user)
        ->get('/roles/duties')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Roles/Duties')
            ->where('role.slug', 'king')
            ->has('categories', 3)
            ->where('categories.0.key', 'charters')
            ->where('categories.0.count', 1)
            ->where('categories.1.key', 'ennoblements')
            ->where('categories.2.key', 'petitions')
        );
});

it('shows petition count for authority roles', function () {
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

    $elder = User::factory()->create();
    $elderPlayerRole = PlayerRole::factory()->create([
        'user_id' => $elder->id,
        'role_id' => $elderRole->id,
        'location_type' => 'village',
        'location_id' => $village->id,
        'status' => 'active',
    ]);

    $guard = User::factory()->create();
    $guardPlayerRole = PlayerRole::factory()->create([
        'user_id' => $guard->id,
        'role_id' => $guardRole->id,
        'location_type' => 'village',
        'location_id' => $village->id,
        'status' => 'active',
    ]);

    // Create a pending petition for the elder to review
    $petitioner = User::factory()->create();
    RolePetition::create([
        'petitioner_id' => $petitioner->id,
        'target_player_role_id' => $guardPlayerRole->id,
        'authority_user_id' => $elder->id,
        'authority_role_slug' => 'elder',
        'location_type' => 'village',
        'location_id' => $village->id,
        'status' => 'pending',
        'petition_reason' => 'Test petition',
        'expires_at' => now()->addDays(7),
    ]);

    $this->actingAs($elder)
        ->get('/roles/duties')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->where('categories.2.key', 'petitions')
            ->where('categories.2.count', 1)
        );
});

it('shows baron duties including manumission requests', function () {
    $kingdom = Kingdom::factory()->create();
    $barony = Barony::factory()->create(['kingdom_id' => $kingdom->id]);
    $village = Village::factory()->create(['barony_id' => $barony->id]);

    $baronRole = Role::factory()->create([
        'slug' => 'baron',
        'name' => 'Baron',
        'location_type' => 'barony',
    ]);

    $user = User::factory()->create();
    PlayerRole::factory()->create([
        'user_id' => $user->id,
        'role_id' => $baronRole->id,
        'location_type' => 'barony',
        'location_id' => $barony->id,
        'status' => 'active',
    ]);

    // Create a pending manumission request
    $serf = User::factory()->create();
    ManumissionRequest::create([
        'serf_id' => $serf->id,
        'baron_id' => $user->id,
        'barony_id' => $barony->id,
        'request_type' => 'gold',
        'status' => 'pending',
        'gold_offered' => 100,
    ]);

    $this->actingAs($user)
        ->get('/roles/duties')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->where('role.slug', 'baron')
            ->has('categories', 4)
            ->where('categories.0.key', 'migrations')
            ->where('categories.1.key', 'manumissions')
            ->where('categories.1.count', 1)
            ->where('categories.2.key', 'accusations')
            ->where('categories.3.key', 'petitions')
        );
});

it('requires authentication', function () {
    $this->get('/roles/duties')
        ->assertRedirect('/login');
});
