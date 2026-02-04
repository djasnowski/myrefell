<?php

use App\Models\Item;
use App\Models\PlayerInventory;
use App\Models\PlayerSkill;
use App\Models\Religion;
use App\Models\ReligionMember;
use App\Models\User;
use App\Services\ReligionService;

test('sacrifice requires bones', function () {
    $user = User::factory()->create();
    $religion = Religion::create([
        'name' => 'Test Religion',
        'description' => 'Test',
        'icon' => 'sun',
        'color' => '#fff',
        'type' => 'religion',
        'is_public' => true,
        'is_active' => true,
    ]);
    ReligionMember::create([
        'user_id' => $user->id,
        'religion_id' => $religion->id,
        'rank' => 'follower',
        'devotion' => 0,
        'joined_at' => now(),
    ]);

    $service = app(ReligionService::class);
    $result = $service->performAction($user, $religion->id, 'sacrifice');

    expect($result['success'])->toBeFalse();
    expect($result['message'])->toBe('You must select bones to sacrifice.');
});

test('sacrifice only accepts bone items', function () {
    $user = User::factory()->create();
    $religion = Religion::create([
        'name' => 'Test Religion 2',
        'description' => 'Test',
        'icon' => 'sun',
        'color' => '#fff',
        'type' => 'religion',
        'is_public' => true,
        'is_active' => true,
    ]);
    ReligionMember::create([
        'user_id' => $user->id,
        'religion_id' => $religion->id,
        'rank' => 'follower',
        'devotion' => 0,
        'joined_at' => now(),
    ]);

    // Create a non-bone item
    $item = Item::create([
        'name' => 'Test Sword',
        'description' => 'A test sword',
        'type' => 'weapon',
        'subtype' => 'sword',
        'base_value' => 10,
    ]);
    PlayerInventory::create([
        'player_id' => $user->id,
        'item_id' => $item->id,
        'quantity' => 1,
        'slot_number' => 1,
    ]);

    $service = app(ReligionService::class);
    $result = $service->performAction($user, $religion->id, 'sacrifice', null, 0, $item->id);

    expect($result['success'])->toBeFalse();
    expect($result['message'])->toBe('You can only sacrifice bones.');
});

test('sacrifice consumes bones and awards prayer xp', function () {
    $user = User::factory()->create();
    $religion = Religion::create([
        'name' => 'Test Religion 3',
        'description' => 'Test',
        'icon' => 'sun',
        'color' => '#fff',
        'type' => 'religion',
        'is_public' => true,
        'is_active' => true,
    ]);
    ReligionMember::create([
        'user_id' => $user->id,
        'religion_id' => $religion->id,
        'rank' => 'follower',
        'devotion' => 0,
        'joined_at' => now(),
    ]);

    // Create bones with 15 prayer XP
    $bones = Item::create([
        'name' => 'Test Bones',
        'description' => 'Test bones',
        'type' => 'misc',
        'subtype' => 'remains',
        'base_value' => 10,
        'prayer_bonus' => 15,
    ]);
    PlayerInventory::create([
        'player_id' => $user->id,
        'item_id' => $bones->id,
        'quantity' => 5,
        'slot_number' => 1,
    ]);

    $service = app(ReligionService::class);
    $result = $service->performAction($user, $religion->id, 'sacrifice', null, 0, $bones->id);

    expect($result['success'])->toBeTrue();
    expect($result['message'])->toContain('sacrificed Test Bones');
    expect($result['message'])->toContain('+15 Prayer XP');

    // Check bones were consumed
    $inventory = PlayerInventory::where('player_id', $user->id)
        ->where('item_id', $bones->id)
        ->first();
    expect($inventory->quantity)->toBe(4);

    // Check prayer skill was created and XP added
    $prayerSkill = PlayerSkill::where('player_id', $user->id)
        ->where('skill_name', 'prayer')
        ->first();
    expect($prayerSkill)->not->toBeNull();
    expect($prayerSkill->xp)->toBe(15);
});

test('sacrifice has no cooldown', function () {
    $user = User::factory()->create();
    $religion = Religion::create([
        'name' => 'Test Religion 4',
        'description' => 'Test',
        'icon' => 'sun',
        'color' => '#fff',
        'type' => 'religion',
        'is_public' => true,
        'is_active' => true,
    ]);
    ReligionMember::create([
        'user_id' => $user->id,
        'religion_id' => $religion->id,
        'rank' => 'follower',
        'devotion' => 0,
        'joined_at' => now(),
    ]);

    $bones = Item::create([
        'name' => 'Test Bones 2',
        'description' => 'Test bones',
        'type' => 'misc',
        'subtype' => 'remains',
        'base_value' => 5,
        'prayer_bonus' => 4,
    ]);
    PlayerInventory::create([
        'player_id' => $user->id,
        'item_id' => $bones->id,
        'quantity' => 10,
        'slot_number' => 1,
    ]);

    $service = app(ReligionService::class);

    // Sacrifice multiple times in succession
    $result1 = $service->performAction($user, $religion->id, 'sacrifice', null, 0, $bones->id);
    $result2 = $service->performAction($user, $religion->id, 'sacrifice', null, 0, $bones->id);
    $result3 = $service->performAction($user, $religion->id, 'sacrifice', null, 0, $bones->id);

    expect($result1['success'])->toBeTrue();
    expect($result2['success'])->toBeTrue();
    expect($result3['success'])->toBeTrue();

    // Check 3 bones were consumed
    $inventory = PlayerInventory::where('player_id', $user->id)
        ->where('item_id', $bones->id)
        ->first();
    expect($inventory->quantity)->toBe(7);
});
