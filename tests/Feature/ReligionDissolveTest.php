<?php

use App\Models\Religion;
use App\Models\ReligionMember;
use App\Models\User;
use App\Services\ReligionService;

test('only prophet can dissolve religion', function () {
    $prophet = User::factory()->create();
    $follower = User::factory()->create();

    $religion = Religion::create([
        'name' => 'Test Religion',
        'description' => 'Test',
        'icon' => 'sun',
        'color' => '#fff',
        'type' => 'cult',
        'is_public' => true,
        'is_active' => true,
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

    $service = app(ReligionService::class);

    // Follower cannot dissolve
    $result = $service->dissolveReligion($follower, $religion->id);
    expect($result['success'])->toBeFalse();
    expect($result['message'])->toBe('Only the prophet can dissolve the religion.');
});

test('dissolving with members requires successor selection', function () {
    $prophet = User::factory()->create();
    $follower = User::factory()->create();

    $religion = Religion::create([
        'name' => 'Test Religion 2',
        'description' => 'Test',
        'icon' => 'sun',
        'color' => '#fff',
        'type' => 'cult',
        'is_public' => true,
        'is_active' => true,
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
        'devotion' => 100,
        'joined_at' => now(),
    ]);

    $service = app(ReligionService::class);

    // Dissolving without selecting successor should fail
    $result = $service->dissolveReligion($prophet, $religion->id);
    expect($result['success'])->toBeFalse();
    expect($result['requires_successor'])->toBeTrue();
    expect($result['members'])->toHaveCount(1);
});

test('prophet can transfer leadership to successor', function () {
    $prophet = User::factory()->create();
    $follower = User::factory()->create();

    $religion = Religion::create([
        'name' => 'Test Religion 3',
        'description' => 'Test',
        'icon' => 'sun',
        'color' => '#fff',
        'type' => 'cult',
        'is_public' => true,
        'is_active' => true,
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
        'devotion' => 100,
        'joined_at' => now(),
    ]);

    $service = app(ReligionService::class);

    // Transfer to successor
    $result = $service->dissolveReligion($prophet, $religion->id, $follower->id);
    expect($result['success'])->toBeTrue();
    expect($result['message'])->toContain('passed leadership');

    // Prophet should no longer be a member
    expect(ReligionMember::where('user_id', $prophet->id)->where('religion_id', $religion->id)->exists())->toBeFalse();

    // Follower should now be prophet
    $newProphet = ReligionMember::where('user_id', $follower->id)->where('religion_id', $religion->id)->first();
    expect($newProphet->rank)->toBe('prophet');
});

test('solo prophet can fully dissolve religion', function () {
    $prophet = User::factory()->create();

    $religion = Religion::create([
        'name' => 'Solo Religion',
        'description' => 'Test',
        'icon' => 'sun',
        'color' => '#fff',
        'type' => 'cult',
        'is_public' => true,
        'is_active' => true,
        'founder_id' => $prophet->id,
    ]);

    ReligionMember::create([
        'user_id' => $prophet->id,
        'religion_id' => $religion->id,
        'rank' => 'prophet',
        'devotion' => 0,
        'joined_at' => now(),
    ]);

    $service = app(ReligionService::class);

    // Dissolve solo religion
    $result = $service->dissolveReligion($prophet, $religion->id);
    expect($result['success'])->toBeTrue();
    expect($result['message'])->toContain('dissolved');

    // Religion should be deleted
    expect(Religion::find($religion->id))->toBeNull();

    // Membership should be gone
    expect(ReligionMember::where('religion_id', $religion->id)->exists())->toBeFalse();
});

test('get potential successors returns members sorted by rank and devotion', function () {
    $prophet = User::factory()->create();
    $priest = User::factory()->create();
    $follower1 = User::factory()->create();
    $follower2 = User::factory()->create();

    $religion = Religion::create([
        'name' => 'Test Religion Successors',
        'description' => 'Test',
        'icon' => 'sun',
        'color' => '#fff',
        'type' => 'cult',
        'is_public' => true,
        'is_active' => true,
        'founder_id' => $prophet->id,
        'member_limit' => 10,
    ]);

    ReligionMember::create([
        'user_id' => $prophet->id,
        'religion_id' => $religion->id,
        'rank' => 'prophet',
        'devotion' => 0,
        'joined_at' => now(),
    ]);

    ReligionMember::create([
        'user_id' => $priest->id,
        'religion_id' => $religion->id,
        'rank' => 'priest',
        'devotion' => 500,
        'joined_at' => now(),
    ]);

    ReligionMember::create([
        'user_id' => $follower1->id,
        'religion_id' => $religion->id,
        'rank' => 'follower',
        'devotion' => 200,
        'joined_at' => now(),
    ]);

    ReligionMember::create([
        'user_id' => $follower2->id,
        'religion_id' => $religion->id,
        'rank' => 'follower',
        'devotion' => 800,
        'joined_at' => now(),
    ]);

    $service = app(ReligionService::class);
    $successors = $service->getPotentialSuccessors($prophet, $religion->id);

    expect($successors)->toHaveCount(3);
    // Priest should be first (higher rank)
    expect($successors[0]['rank'])->toBe('priest');
    // Among followers, higher devotion should come first
    expect($successors[1]['devotion'])->toBe(800);
    expect($successors[2]['devotion'])->toBe(200);
});
