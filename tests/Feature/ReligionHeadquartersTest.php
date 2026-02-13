<?php

use App\Http\Middleware\EnsurePlayerAtLocation;
use App\Models\Belief;
use App\Models\HqFeatureType;
use App\Models\Kingdom;
use App\Models\PlayerSkill;
use App\Models\Religion;
use App\Models\ReligionHeadquarters;
use App\Models\ReligionMember;
use App\Models\ReligionTreasury;
use App\Models\User;
use App\Models\Village;
use App\Services\ReligionHeadquartersService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// Helper to build location-scoped HQ URL
function hqUrl(string $locationType, int $locationId, int $religionId, string $path = ''): string
{
    $plural = match ($locationType) {
        'barony' => 'baronies',
        'duchy' => 'duchies',
        default => $locationType.'s',
    };

    return "/{$plural}/{$locationId}/religions/{$religionId}/headquarters{$path}";
}

beforeEach(function () {
    // Create a belief for cult creation
    Belief::create([
        'name' => 'Test Belief',
        'description' => 'A test belief',
        'icon' => 'star',
        'effects' => ['test_bonus' => 10],
        'type' => 'virtue',
    ]);

    // Create test villages for location-scoped routes (factory creates barony/duchy/kingdom)
    $this->testVillage = Village::factory()->create();
    $this->otherVillage = Village::factory()->create();

    // Disable location check middleware for HQ tests - we're testing HQ functionality, not location checks
    $this->withoutMiddleware(EnsurePlayerAtLocation::class);
});

it('creates treasury and headquarters when cult is created', function () {
    $user = User::factory()->create(['gold' => 10000]);

    $response = $this->actingAs($user)->post('/religions/create-cult', [
        'name' => 'Test Cult',
        'description' => 'A test cult',
        'belief_ids' => [Belief::first()->id],
    ]);

    $response->assertRedirect();

    $religion = Religion::where('name', 'Test Cult')->first();
    expect($religion)->not->toBeNull();

    // Treasury should be created
    $treasury = ReligionTreasury::where('religion_id', $religion->id)->first();
    expect($treasury)->not->toBeNull()
        ->and($treasury->balance)->toBe(0);

    // Headquarters should be created (but not built)
    $hq = ReligionHeadquarters::where('religion_id', $religion->id)->first();
    expect($hq)->not->toBeNull()
        ->and($hq->tier)->toBe(1)
        ->and($hq->location_type)->toBeNull();
});

it('allows prophet to build headquarters at their location', function () {
    $user = User::factory()->create([
        'gold' => 10000,
        'current_location_type' => 'village',
        'current_location_id' => $this->testVillage->id,
    ]);

    // Create religion with treasury and HQ
    $religion = Religion::create([
        'name' => 'Test Religion',
        'type' => 'cult',
        'founder_id' => $user->id,
        'is_public' => false,
        'member_limit' => 5,
    ]);

    ReligionMember::create([
        'user_id' => $user->id,
        'religion_id' => $religion->id,
        'rank' => 'prophet',
        'devotion' => 0,
        'joined_at' => now(),
    ]);

    ReligionTreasury::create([
        'religion_id' => $religion->id,
        'balance' => 0,
    ]);

    $hq = ReligionHeadquarters::create([
        'religion_id' => $religion->id,
        'tier' => 1,
    ]);

    $response = $this->actingAs($user)->post(hqUrl('village', $this->testVillage->id, $religion->id, '/build'), [
        'location_type' => 'village',
        'location_id' => $this->testVillage->id,
    ]);

    $response->assertRedirect();

    $hq->refresh();
    expect($hq->location_type)->toBe('village')
        ->and($hq->location_id)->toBe($this->testVillage->id)
        ->and($hq->name)->toBe('Test Religion Chapel');
});

it('allows members to donate to treasury', function () {
    $user = User::factory()->create(['gold' => 10000]);

    $religion = Religion::create([
        'name' => 'Test Religion',
        'type' => 'cult',
        'founder_id' => $user->id,
    ]);

    ReligionMember::create([
        'user_id' => $user->id,
        'religion_id' => $religion->id,
        'rank' => 'prophet',
        'devotion' => 0,
        'joined_at' => now(),
    ]);

    $treasury = ReligionTreasury::create([
        'religion_id' => $religion->id,
        'balance' => 0,
    ]);

    ReligionHeadquarters::create([
        'religion_id' => $religion->id,
        'tier' => 1,
        'location_type' => 'village',
        'location_id' => $this->testVillage->id,
    ]);

    $response = $this->actingAs($user)->post(hqUrl('village', $this->testVillage->id, $religion->id, '/donate'), [
        'amount' => 1000,
    ]);

    $response->assertRedirect();

    $user->refresh();
    $treasury->refresh();

    expect($user->gold)->toBe(9000)
        ->and($treasury->balance)->toBe(1000)
        ->and($treasury->total_collected)->toBe(1000);
});

it('allows prophet to start hq upgrade', function () {
    $user = User::factory()->create(['gold' => 10000]);

    // Prophet needs level 15 Prayer to upgrade to Church (tier 2)
    PlayerSkill::create([
        'player_id' => $user->id,
        'skill_name' => 'prayer',
        'level' => 15,
        'xp' => 0,
    ]);

    $religion = Religion::create([
        'name' => 'Test Religion',
        'type' => 'cult',
        'founder_id' => $user->id,
    ]);

    ReligionMember::create([
        'user_id' => $user->id,
        'religion_id' => $religion->id,
        'rank' => 'prophet',
        'devotion' => 0,
        'joined_at' => now(),
    ]);

    ReligionTreasury::create([
        'religion_id' => $religion->id,
        'balance' => 0,
    ]);

    $hq = ReligionHeadquarters::create([
        'religion_id' => $religion->id,
        'tier' => 1,
        'location_type' => 'village',
        'location_id' => $this->testVillage->id,
    ]);

    $response = $this->actingAs($user)->post(hqUrl('village', $this->testVillage->id, $religion->id, '/upgrade'), []);

    $response->assertRedirect();

    expect($hq->activeHqUpgrade()->exists())->toBeTrue();

    $project = $hq->activeHqUpgrade;
    expect($project->project_type)->toBe('hq_upgrade')
        ->and($project->target_level)->toBe(2)
        ->and($project->gold_required)->toBe(100000); // Updated cost
});

it('allows members to contribute to projects', function () {
    $user = User::factory()->create(['gold' => 50000]);

    // Prophet needs level 15 Prayer to upgrade to Church (tier 2)
    PlayerSkill::create([
        'player_id' => $user->id,
        'skill_name' => 'prayer',
        'level' => 15,
        'xp' => 0,
    ]);

    $religion = Religion::create([
        'name' => 'Test Religion',
        'type' => 'cult',
        'founder_id' => $user->id,
    ]);

    $membership = ReligionMember::create([
        'user_id' => $user->id,
        'religion_id' => $religion->id,
        'rank' => 'prophet',
        'devotion' => 5000,
        'joined_at' => now(),
    ]);

    ReligionTreasury::create([
        'religion_id' => $religion->id,
        'balance' => 0,
    ]);

    $hq = ReligionHeadquarters::create([
        'religion_id' => $religion->id,
        'tier' => 1,
        'location_type' => 'village',
        'location_id' => $this->testVillage->id,
    ]);

    // Start upgrade project
    $this->actingAs($user)->post(hqUrl('village', $this->testVillage->id, $religion->id, '/upgrade'), []);

    $project = $hq->fresh()->activeHqUpgrade;

    // Contribute
    $response = $this->actingAs($user)->post(
        hqUrl('village', $this->testVillage->id, $religion->id, "/projects/{$project->id}/contribute"),
        [
            'gold' => 10000,
            'devotion' => 500,
        ]
    );

    $response->assertRedirect();

    $project->refresh();
    $user->refresh();
    $membership->refresh();

    expect($project->gold_invested)->toBe(10000)
        ->and($project->devotion_invested)->toBe(500)
        ->and($user->gold)->toBe(40000)
        ->and($membership->devotion)->toBe(4500);
});

it('starts construction timer when requirements are met', function () {
    $user = User::factory()->create(['gold' => 200000]);

    // Prophet needs level 15 Prayer to upgrade to Church (tier 2)
    PlayerSkill::create([
        'player_id' => $user->id,
        'skill_name' => 'prayer',
        'level' => 15,
        'xp' => 0,
    ]);

    $religion = Religion::create([
        'name' => 'Test Religion',
        'type' => 'cult',
        'founder_id' => $user->id,
    ]);

    $membership = ReligionMember::create([
        'user_id' => $user->id,
        'religion_id' => $religion->id,
        'rank' => 'prophet',
        'devotion' => 10000,
        'joined_at' => now(),
    ]);

    ReligionTreasury::create([
        'religion_id' => $religion->id,
        'balance' => 0,
    ]);

    $hq = ReligionHeadquarters::create([
        'religion_id' => $religion->id,
        'tier' => 1,
        'location_type' => 'village',
        'location_id' => $this->testVillage->id,
    ]);

    // Start upgrade project
    $this->actingAs($user)->post(hqUrl('village', $this->testVillage->id, $religion->id, '/upgrade'), []);
    $project = $hq->fresh()->activeHqUpgrade;

    // Contribute full amount (100,000 gold, 5,000 devotion for tier 2)
    $this->actingAs($user)->post(
        hqUrl('village', $this->testVillage->id, $religion->id, "/projects/{$project->id}/contribute"),
        [
            'gold' => 100000,
            'devotion' => 5000,
        ]
    );

    $project->refresh();

    // Project should be in "constructing" status with a timer
    expect($project->status)->toBe('constructing')
        ->and($project->construction_ends_at)->not->toBeNull()
        ->and($project->progress)->toBe(100);

    // HQ should NOT be upgraded yet
    $hq->refresh();
    expect($hq->tier)->toBe(1);
});

it('completes project when construction timer expires', function () {
    $user = User::factory()->create(['gold' => 200000]);

    // Prophet needs level 15 Prayer to upgrade to Church (tier 2)
    PlayerSkill::create([
        'player_id' => $user->id,
        'skill_name' => 'prayer',
        'level' => 15,
        'xp' => 0,
    ]);

    $religion = Religion::create([
        'name' => 'Test Religion',
        'type' => 'cult',
        'founder_id' => $user->id,
    ]);

    ReligionMember::create([
        'user_id' => $user->id,
        'religion_id' => $religion->id,
        'rank' => 'prophet',
        'devotion' => 10000,
        'joined_at' => now(),
    ]);

    ReligionTreasury::create([
        'religion_id' => $religion->id,
        'balance' => 0,
    ]);

    $hq = ReligionHeadquarters::create([
        'religion_id' => $religion->id,
        'tier' => 1,
        'location_type' => 'village',
        'location_id' => $this->testVillage->id,
    ]);

    // Start upgrade project
    $this->actingAs($user)->post(hqUrl('village', $this->testVillage->id, $religion->id, '/upgrade'), []);
    $project = $hq->fresh()->activeHqUpgrade;

    // Contribute full amount
    $this->actingAs($user)->post(
        hqUrl('village', $this->testVillage->id, $religion->id, "/projects/{$project->id}/contribute"),
        [
            'gold' => 100000,
            'devotion' => 5000,
        ]
    );

    $project->refresh();
    expect($project->status)->toBe('constructing');

    // Simulate time passing - set construction_ends_at to the past
    $project->update(['construction_ends_at' => now()->subMinute()]);

    // Run the completion command
    $this->artisan('hq:complete-construction');

    $hq->refresh();
    $project->refresh();

    expect($project->status)->toBe('completed')
        ->and($hq->tier)->toBe(2)
        ->and($hq->tier_name)->toBe('Church');
});

it('applies devotion gain modifier from hq', function () {
    $user = User::factory()->create(['gold' => 10000]);

    $religion = Religion::create([
        'name' => 'Test Religion',
        'type' => 'cult',
        'founder_id' => $user->id,
    ]);

    ReligionMember::create([
        'user_id' => $user->id,
        'religion_id' => $religion->id,
        'rank' => 'prophet',
        'devotion' => 0,
        'joined_at' => now(),
    ]);

    ReligionTreasury::create([
        'religion_id' => $religion->id,
        'balance' => 0,
    ]);

    // Create a tier 2 HQ (should have 5% devotion bonus)
    ReligionHeadquarters::create([
        'religion_id' => $religion->id,
        'tier' => 2,
        'location_type' => 'village',
        'location_id' => $this->testVillage->id,
    ]);

    $hqService = app(ReligionHeadquartersService::class);
    $modifier = $hqService->getDevotionGainModifier($user);

    expect($modifier)->toBe(1.05);
});

it('applies blessing cost modifier from hq', function () {
    $user = User::factory()->create(['gold' => 10000]);

    $religion = Religion::create([
        'name' => 'Test Religion',
        'type' => 'cult',
        'founder_id' => $user->id,
    ]);

    ReligionMember::create([
        'user_id' => $user->id,
        'religion_id' => $religion->id,
        'rank' => 'prophet',
        'devotion' => 0,
        'joined_at' => now(),
    ]);

    ReligionTreasury::create([
        'religion_id' => $religion->id,
        'balance' => 0,
    ]);

    // Create a tier 3 HQ (should have 10% blessing cost reduction)
    ReligionHeadquarters::create([
        'religion_id' => $religion->id,
        'tier' => 3,
        'location_type' => 'village',
        'location_id' => $this->testVillage->id,
    ]);

    $hqService = app(ReligionHeadquartersService::class);
    $modifier = $hqService->getBlessingCostModifier($user);

    expect($modifier)->toBe(0.90);
});

it('prevents non-prophets from starting upgrades', function () {
    $prophet = User::factory()->create(['gold' => 10000]);
    $follower = User::factory()->create(['gold' => 10000]);

    $religion = Religion::create([
        'name' => 'Test Religion',
        'type' => 'cult',
        'founder_id' => $prophet->id,
    ]);

    ReligionMember::create([
        'user_id' => $prophet->id,
        'religion_id' => $religion->id,
        'rank' => 'prophet',
        'devotion' => 0,
        'joined_at' => now(),
    ]);

    ReligionMember::create([
        'user_id' => $follower->id,
        'religion_id' => $religion->id,
        'rank' => 'follower',
        'devotion' => 0,
        'joined_at' => now(),
    ]);

    ReligionTreasury::create([
        'religion_id' => $religion->id,
        'balance' => 0,
    ]);

    ReligionHeadquarters::create([
        'religion_id' => $religion->id,
        'tier' => 1,
        'location_type' => 'village',
        'location_id' => $this->testVillage->id,
    ]);

    $response = $this->actingAs($follower)->post(hqUrl('village', $this->testVillage->id, $religion->id, '/upgrade'), []);

    $response->assertRedirect();
    $response->assertSessionHasErrors('error');
});

it('caps contribution at what is needed and returns overage', function () {
    $user = User::factory()->create(['gold' => 200000]);

    PlayerSkill::create([
        'player_id' => $user->id,
        'skill_name' => 'prayer',
        'level' => 15,
        'xp' => 0,
    ]);

    $religion = Religion::create([
        'name' => 'Test Religion',
        'type' => 'cult',
        'founder_id' => $user->id,
    ]);

    $membership = ReligionMember::create([
        'user_id' => $user->id,
        'religion_id' => $religion->id,
        'rank' => 'prophet',
        'devotion' => 10000,
        'joined_at' => now(),
    ]);

    ReligionTreasury::create([
        'religion_id' => $religion->id,
        'balance' => 0,
    ]);

    $hq = ReligionHeadquarters::create([
        'religion_id' => $religion->id,
        'tier' => 1,
        'location_type' => 'village',
        'location_id' => $this->testVillage->id,
    ]);

    // Start upgrade project (requires 100,000 gold, 5,000 devotion for tier 2)
    $this->actingAs($user)->post(hqUrl('village', $this->testVillage->id, $religion->id, '/upgrade'), []);
    $project = $hq->fresh()->activeHqUpgrade;

    // Try to contribute MORE than needed
    // Project needs 100,000 gold and 5,000 devotion
    // User tries to donate 150,000 gold and 8,000 devotion
    $this->actingAs($user)->post(
        hqUrl('village', $this->testVillage->id, $religion->id, "/projects/{$project->id}/contribute"),
        [
            'gold' => 150000,
            'devotion' => 8000,
        ]
    );

    $user->refresh();
    $membership->refresh();
    $project->refresh();

    // Only the required amount should have been taken
    expect($project->gold_invested)->toBe(100000)
        ->and($project->devotion_invested)->toBe(5000)
        // Player should have been charged only what was needed
        // Started with 200,000 gold, only 100,000 needed
        ->and($user->gold)->toBe(100000)
        // Started with 10,000 devotion, only 5,000 needed
        ->and($membership->devotion)->toBe(5000);
});

it('allows prophet to start feature construction', function () {
    // Seed feature types
    $this->artisan('db:seed', ['--class' => 'HqFeatureTypeSeeder']);

    $user = User::factory()->create(['gold' => 10000]);

    $religion = Religion::create([
        'name' => 'Test Religion',
        'type' => 'cult',
        'founder_id' => $user->id,
    ]);

    ReligionMember::create([
        'user_id' => $user->id,
        'religion_id' => $religion->id,
        'rank' => 'prophet',
        'devotion' => 0,
        'joined_at' => now(),
    ]);

    ReligionTreasury::create([
        'religion_id' => $religion->id,
        'balance' => 0,
    ]);

    $hq = ReligionHeadquarters::create([
        'religion_id' => $religion->id,
        'tier' => 1,
        'location_type' => 'village',
        'location_id' => $this->testVillage->id,
    ]);

    // Get a feature type that's available at tier 1
    $featureType = HqFeatureType::where('min_hq_tier', 1)->first();

    $response = $this->actingAs($user)->post(hqUrl('village', $this->testVillage->id, $religion->id, '/features'), [
        'feature_type_id' => $featureType->id,
    ]);

    $response->assertRedirect();

    $project = $hq->fresh()->activeProjects()->where('hq_feature_type_id', $featureType->id)->first();
    expect($project)->not->toBeNull()
        ->and($project->project_type)->toBe('feature_build')
        ->and($project->hq_feature_type_id)->toBe($featureType->id);
});

it('allows members to pray at features for temporary buffs', function () {
    // Seed feature types
    $this->artisan('db:seed', ['--class' => 'HqFeatureTypeSeeder']);

    $user = User::factory()->create([
        'gold' => 10000,
        'energy' => 100,
        'current_location_type' => 'village',
        'current_location_id' => $this->testVillage->id,
    ]);

    $religion = Religion::create([
        'name' => 'Test Religion',
        'type' => 'cult',
        'founder_id' => $user->id,
    ]);

    $membership = ReligionMember::create([
        'user_id' => $user->id,
        'religion_id' => $religion->id,
        'rank' => 'prophet',
        'devotion' => 500,
        'joined_at' => now(),
    ]);

    ReligionTreasury::create([
        'religion_id' => $religion->id,
        'balance' => 0,
    ]);

    $hq = ReligionHeadquarters::create([
        'religion_id' => $religion->id,
        'tier' => 1,
        'location_type' => 'village',
        'location_id' => $this->testVillage->id,
    ]);

    // Create a built feature
    $featureType = HqFeatureType::where('min_hq_tier', 1)->first();
    $feature = \App\Models\ReligionHqFeature::create([
        'religion_hq_id' => $hq->id,
        'hq_feature_type_id' => $featureType->id,
        'level' => 1,
    ]);

    $response = $this->actingAs($user)->post(hqUrl('village', $this->testVillage->id, $religion->id, "/features/{$feature->id}/pray"), []);

    $response->assertRedirect();

    $user->refresh();
    $membership->refresh();

    // Check costs were deducted
    expect($user->energy)->toBe(75) // 100 - 25 (tier 1 cost)
        ->and($membership->devotion)->toBe(450); // 500 - 50 (level 1 cost)

    // Check buff was created
    $buff = \App\Models\PlayerFeatureBuff::where('user_id', $user->id)
        ->where('religion_hq_feature_id', $feature->id)
        ->first();

    expect($buff)->not->toBeNull()
        ->and($buff->expires_at)->toBeGreaterThan(now())
        ->and($buff->effects)->toBeArray();
});

it('requires player to be at HQ to pray', function () {
    // Seed feature types
    $this->artisan('db:seed', ['--class' => 'HqFeatureTypeSeeder']);

    $user = User::factory()->create([
        'gold' => 10000,
        'energy' => 100,
        'current_location_type' => 'village',
        'current_location_id' => $this->otherVillage->id, // Different location than HQ
    ]);

    $religion = Religion::create([
        'name' => 'Test Religion',
        'type' => 'cult',
        'founder_id' => $user->id,
    ]);

    ReligionMember::create([
        'user_id' => $user->id,
        'religion_id' => $religion->id,
        'rank' => 'prophet',
        'devotion' => 500,
        'joined_at' => now(),
    ]);

    ReligionTreasury::create([
        'religion_id' => $religion->id,
        'balance' => 0,
    ]);

    $hq = ReligionHeadquarters::create([
        'religion_id' => $religion->id,
        'tier' => 1,
        'location_type' => 'village',
        'location_id' => $this->testVillage->id, // HQ at village 1
    ]);

    // Create a built feature
    $featureType = HqFeatureType::where('min_hq_tier', 1)->first();
    $feature = \App\Models\ReligionHqFeature::create([
        'religion_hq_id' => $hq->id,
        'hq_feature_type_id' => $featureType->id,
        'level' => 1,
    ]);

    $response = $this->actingAs($user)->post(hqUrl('village', $this->testVillage->id, $religion->id, "/features/{$feature->id}/pray"), []);

    $response->assertRedirect();
    $response->assertSessionHasErrors('error');
});
