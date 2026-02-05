<?php

use App\Models\Religion;
use App\Models\ReligionInvite;
use App\Models\ReligionMember;
use App\Models\ReligionTreasury;
use App\Models\User;
use App\Services\ReligionInviteService;

test('prophet can send invite', function () {
    $prophet = User::factory()->create();
    $invitee = User::factory()->create();
    $religion = Religion::create([
        'name' => 'Test Religion Invite',
        'description' => 'Test',
        'icon' => 'sun',
        'color' => '#fff',
        'type' => 'cult',
        'is_public' => false,
        'is_active' => true,
    ]);
    ReligionMember::create([
        'user_id' => $prophet->id,
        'religion_id' => $religion->id,
        'rank' => 'prophet',
        'devotion' => 0,
        'joined_at' => now(),
    ]);
    ReligionTreasury::create([
        'religion_id' => $religion->id,
        'balance' => 0,
        'total_collected' => 0,
        'total_distributed' => 0,
    ]);

    $service = app(ReligionInviteService::class);
    $result = $service->sendInvite($prophet, $religion->id, $invitee->id, 'Join us!');

    expect($result['success'])->toBeTrue();
    expect($result['message'])->toContain('invited');

    $invite = ReligionInvite::where('religion_id', $religion->id)
        ->where('invited_user_id', $invitee->id)
        ->first();
    expect($invite)->not->toBeNull();
    expect($invite->status)->toBe('pending');
    expect($invite->message)->toBe('Join us!');
});

test('followers cannot send invites', function () {
    $follower = User::factory()->create();
    $invitee = User::factory()->create();
    $religion = Religion::create([
        'name' => 'Test Religion Follower Invite',
        'description' => 'Test',
        'icon' => 'sun',
        'color' => '#fff',
        'type' => 'cult',
        'is_public' => false,
        'is_active' => true,
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
        'total_collected' => 0,
        'total_distributed' => 0,
    ]);

    $service = app(ReligionInviteService::class);
    $result = $service->sendInvite($follower, $religion->id, $invitee->id);

    expect($result['success'])->toBeFalse();
    expect($result['message'])->toContain('Only priests and prophets');
});

test('cannot invite existing member', function () {
    $prophet = User::factory()->create();
    $existingMember = User::factory()->create();
    $religion = Religion::create([
        'name' => 'Test Religion Existing Member',
        'description' => 'Test',
        'icon' => 'sun',
        'color' => '#fff',
        'type' => 'cult',
        'is_public' => false,
        'is_active' => true,
    ]);
    ReligionMember::create([
        'user_id' => $prophet->id,
        'religion_id' => $religion->id,
        'rank' => 'prophet',
        'devotion' => 0,
        'joined_at' => now(),
    ]);
    ReligionMember::create([
        'user_id' => $existingMember->id,
        'religion_id' => $religion->id,
        'rank' => 'follower',
        'devotion' => 0,
        'joined_at' => now(),
    ]);
    ReligionTreasury::create([
        'religion_id' => $religion->id,
        'balance' => 0,
        'total_collected' => 0,
        'total_distributed' => 0,
    ]);

    $service = app(ReligionInviteService::class);
    $result = $service->sendInvite($prophet, $religion->id, $existingMember->id);

    expect($result['success'])->toBeFalse();
    expect($result['message'])->toContain('already a member');
});

test('user can accept invite', function () {
    $prophet = User::factory()->create();
    $invitee = User::factory()->create();
    $religion = Religion::create([
        'name' => 'Test Religion Accept',
        'description' => 'Test',
        'icon' => 'sun',
        'color' => '#fff',
        'type' => 'cult',
        'is_public' => false,
        'is_active' => true,
    ]);
    ReligionMember::create([
        'user_id' => $prophet->id,
        'religion_id' => $religion->id,
        'rank' => 'prophet',
        'devotion' => 0,
        'joined_at' => now(),
    ]);
    ReligionTreasury::create([
        'religion_id' => $religion->id,
        'balance' => 0,
        'total_collected' => 0,
        'total_distributed' => 0,
    ]);

    $invite = ReligionInvite::create([
        'religion_id' => $religion->id,
        'invited_by_user_id' => $prophet->id,
        'invited_user_id' => $invitee->id,
        'status' => 'pending',
        'expires_at' => now()->addDays(7),
    ]);

    $service = app(ReligionInviteService::class);
    $result = $service->acceptInvite($invitee, $invite->id);

    expect($result['success'])->toBeTrue();
    expect($result['message'])->toContain('joined');

    $invite->refresh();
    expect($invite->status)->toBe('accepted');

    $membership = ReligionMember::where('user_id', $invitee->id)
        ->where('religion_id', $religion->id)
        ->first();
    expect($membership)->not->toBeNull();
    expect($membership->rank)->toBe('follower');
});

test('user can decline invite', function () {
    $prophet = User::factory()->create();
    $invitee = User::factory()->create();
    $religion = Religion::create([
        'name' => 'Test Religion Decline',
        'description' => 'Test',
        'icon' => 'sun',
        'color' => '#fff',
        'type' => 'cult',
        'is_public' => false,
        'is_active' => true,
    ]);
    ReligionMember::create([
        'user_id' => $prophet->id,
        'religion_id' => $religion->id,
        'rank' => 'prophet',
        'devotion' => 0,
        'joined_at' => now(),
    ]);
    ReligionTreasury::create([
        'religion_id' => $religion->id,
        'balance' => 0,
        'total_collected' => 0,
        'total_distributed' => 0,
    ]);

    $invite = ReligionInvite::create([
        'religion_id' => $religion->id,
        'invited_by_user_id' => $prophet->id,
        'invited_user_id' => $invitee->id,
        'status' => 'pending',
        'expires_at' => now()->addDays(7),
    ]);

    $service = app(ReligionInviteService::class);
    $result = $service->declineInvite($invitee, $invite->id);

    expect($result['success'])->toBeTrue();
    expect($result['message'])->toContain('declined');

    $invite->refresh();
    expect($invite->status)->toBe('declined');

    $membership = ReligionMember::where('user_id', $invitee->id)
        ->where('religion_id', $religion->id)
        ->first();
    expect($membership)->toBeNull();
});

test('cannot accept expired invite', function () {
    $prophet = User::factory()->create();
    $invitee = User::factory()->create();
    $religion = Religion::create([
        'name' => 'Test Religion Expired',
        'description' => 'Test',
        'icon' => 'sun',
        'color' => '#fff',
        'type' => 'cult',
        'is_public' => false,
        'is_active' => true,
    ]);
    ReligionMember::create([
        'user_id' => $prophet->id,
        'religion_id' => $religion->id,
        'rank' => 'prophet',
        'devotion' => 0,
        'joined_at' => now(),
    ]);
    ReligionTreasury::create([
        'religion_id' => $religion->id,
        'balance' => 0,
        'total_collected' => 0,
        'total_distributed' => 0,
    ]);

    $invite = ReligionInvite::create([
        'religion_id' => $religion->id,
        'invited_by_user_id' => $prophet->id,
        'invited_user_id' => $invitee->id,
        'status' => 'pending',
        'expires_at' => now()->subDay(), // Expired yesterday
    ]);

    $service = app(ReligionInviteService::class);
    $result = $service->acceptInvite($invitee, $invite->id);

    expect($result['success'])->toBeFalse();
    expect($result['message'])->toContain('expired');
});

test('accepting invite while in another religion auto-leaves and joins', function () {
    $prophet = User::factory()->create();
    $invitee = User::factory()->create();
    $religion1 = Religion::create([
        'name' => 'Test Religion 1',
        'description' => 'Test',
        'icon' => 'sun',
        'color' => '#fff',
        'type' => 'cult',
        'is_public' => false,
        'is_active' => true,
    ]);
    $religion2 = Religion::create([
        'name' => 'Test Religion 2',
        'description' => 'Test',
        'icon' => 'moon',
        'color' => '#000',
        'type' => 'cult',
        'is_public' => false,
        'is_active' => true,
    ]);

    ReligionMember::create([
        'user_id' => $prophet->id,
        'religion_id' => $religion1->id,
        'rank' => 'prophet',
        'devotion' => 0,
        'joined_at' => now(),
    ]);
    ReligionTreasury::create([
        'religion_id' => $religion1->id,
        'balance' => 0,
        'total_collected' => 0,
        'total_distributed' => 0,
    ]);
    ReligionTreasury::create([
        'religion_id' => $religion2->id,
        'balance' => 0,
        'total_collected' => 0,
        'total_distributed' => 0,
    ]);

    // Invitee is already in religion2
    ReligionMember::create([
        'user_id' => $invitee->id,
        'religion_id' => $religion2->id,
        'rank' => 'follower',
        'devotion' => 0,
        'joined_at' => now(),
    ]);

    $invite = ReligionInvite::create([
        'religion_id' => $religion1->id,
        'invited_by_user_id' => $prophet->id,
        'invited_user_id' => $invitee->id,
        'status' => 'pending',
        'expires_at' => now()->addDays(7),
    ]);

    $service = app(ReligionInviteService::class);
    $result = $service->acceptInvite($invitee, $invite->id);

    // Should succeed - auto-leaves old religion and joins new one
    expect($result['success'])->toBeTrue();

    // Verify invitee is now in religion1
    $membership = ReligionMember::where('user_id', $invitee->id)->first();
    expect($membership)->not->toBeNull();
    expect($membership->religion_id)->toBe($religion1->id);

    // Verify invitee is no longer in religion2
    $oldMembership = ReligionMember::where('user_id', $invitee->id)
        ->where('religion_id', $religion2->id)
        ->first();
    expect($oldMembership)->toBeNull();
});
