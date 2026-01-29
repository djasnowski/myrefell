<?php

use App\Models\User;
use App\Models\UserBan;

test('admin can ban a regular user', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create(['is_admin' => false]);

    $this->actingAs($admin)
        ->post("/admin/users/{$user->id}/ban", [
            'reason' => 'Violation of community guidelines',
        ])
        ->assertRedirect();

    $user->refresh();
    expect($user->isBanned())->toBeTrue();
    expect($user->banned_at)->not->toBeNull();

    $ban = UserBan::where('user_id', $user->id)->first();
    expect($ban)->not->toBeNull();
    expect($ban->reason)->toBe('Violation of community guidelines');
    expect($ban->banned_by)->toBe($admin->id);
    expect($ban->unbanned_at)->toBeNull();
});

test('admin cannot ban themselves', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post("/admin/users/{$admin->id}/ban", [
            'reason' => 'Self ban attempt',
        ])
        ->assertRedirect()
        ->assertSessionHas('error');

    $admin->refresh();
    expect($admin->isBanned())->toBeFalse();
});

test('admin cannot ban other admins', function () {
    $admin1 = User::factory()->admin()->create();
    $admin2 = User::factory()->admin()->create();

    $this->actingAs($admin1)
        ->post("/admin/users/{$admin2->id}/ban", [
            'reason' => 'Admin ban attempt',
        ])
        ->assertRedirect()
        ->assertSessionHas('error');

    $admin2->refresh();
    expect($admin2->isBanned())->toBeFalse();
});

test('admin cannot ban already banned user', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->banned()->create();

    $this->actingAs($admin)
        ->post("/admin/users/{$user->id}/ban", [
            'reason' => 'Duplicate ban attempt',
        ])
        ->assertRedirect()
        ->assertSessionHas('error');
});

test('ban requires a reason', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create(['is_admin' => false]);

    $this->actingAs($admin)
        ->post("/admin/users/{$user->id}/ban", [
            'reason' => '',
        ])
        ->assertSessionHasErrors('reason');

    $user->refresh();
    expect($user->isBanned())->toBeFalse();
});

test('ban reason must be at least 5 characters', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create(['is_admin' => false]);

    $this->actingAs($admin)
        ->post("/admin/users/{$user->id}/ban", [
            'reason' => 'bad',
        ])
        ->assertSessionHasErrors('reason');

    $user->refresh();
    expect($user->isBanned())->toBeFalse();
});

test('admin can unban a banned user', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->banned()->create();

    // Create an existing ban record
    $ban = UserBan::factory()->create([
        'user_id' => $user->id,
        'banned_by' => $admin->id,
        'reason' => 'Test ban',
        'banned_at' => now()->subDay(),
    ]);

    $this->actingAs($admin)
        ->post("/admin/users/{$user->id}/unban", [
            'reason' => 'Appealed and resolved',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    $user->refresh();
    expect($user->isBanned())->toBeFalse();
    expect($user->banned_at)->toBeNull();

    $ban->refresh();
    expect($ban->unbanned_at)->not->toBeNull();
    expect($ban->unbanned_by)->toBe($admin->id);
    expect($ban->unban_reason)->toBe('Appealed and resolved');
});

test('admin cannot unban a non-banned user', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create(['banned_at' => null]);

    $this->actingAs($admin)
        ->post("/admin/users/{$user->id}/unban", [
            'reason' => 'Invalid unban attempt',
        ])
        ->assertRedirect()
        ->assertSessionHas('error');
});

test('unban reason is optional', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->banned()->create();

    UserBan::factory()->create([
        'user_id' => $user->id,
        'banned_by' => $admin->id,
        'reason' => 'Test ban',
        'banned_at' => now()->subDay(),
    ]);

    $this->actingAs($admin)
        ->post("/admin/users/{$user->id}/unban", [])
        ->assertRedirect()
        ->assertSessionHas('success');

    $user->refresh();
    expect($user->isBanned())->toBeFalse();
});

test('ban creates audit trail record', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create(['is_admin' => false]);

    $this->actingAs($admin)
        ->post("/admin/users/{$user->id}/ban", [
            'reason' => 'Creating audit trail',
        ]);

    expect(UserBan::where('user_id', $user->id)->count())->toBe(1);

    $ban = UserBan::where('user_id', $user->id)->first();
    expect($ban->banned_by)->toBe($admin->id);
    expect($ban->reason)->toBe('Creating audit trail');
    expect($ban->banned_at)->not->toBeNull();
});

test('multiple bans create multiple records', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create(['is_admin' => false]);

    // First ban
    $this->actingAs($admin)
        ->post("/admin/users/{$user->id}/ban", ['reason' => 'First offense']);

    // Unban
    UserBan::factory()->create([
        'user_id' => $user->id,
        'banned_by' => $admin->id,
        'banned_at' => now()->subDay(),
    ]);
    $this->actingAs($admin)
        ->post("/admin/users/{$user->id}/unban", ['reason' => 'Appeal accepted']);

    // Second ban
    $user->update(['banned_at' => null]);
    $this->actingAs($admin)
        ->post("/admin/users/{$user->id}/ban", ['reason' => 'Second offense']);

    expect(UserBan::where('user_id', $user->id)->count())->toBe(3);
});
