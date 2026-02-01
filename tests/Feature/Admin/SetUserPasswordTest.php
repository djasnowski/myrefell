<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('admin can set user password', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();
    $oldPasswordHash = $user->password;

    $this->actingAs($admin)
        ->put("/admin/users/{$user->id}/password", [
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ])
        ->assertRedirect();

    $user->refresh();
    expect($user->password)->not->toBe($oldPasswordHash);
    expect(Hash::check('newpassword123', $user->password))->toBeTrue();
});

test('password must be at least 8 characters', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();

    $this->actingAs($admin)
        ->put("/admin/users/{$user->id}/password", [
            'password' => 'short',
            'password_confirmation' => 'short',
        ])
        ->assertSessionHasErrors('password');
});

test('password confirmation must match', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();

    $this->actingAs($admin)
        ->put("/admin/users/{$user->id}/password", [
            'password' => 'newpassword123',
            'password_confirmation' => 'differentpassword',
        ])
        ->assertSessionHasErrors('password');
});

test('non-admin cannot set user password', function () {
    $regularUser = User::factory()->create();
    $targetUser = User::factory()->create();

    $this->actingAs($regularUser)
        ->put("/admin/users/{$targetUser->id}/password", [
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ])
        ->assertForbidden();
});

test('unauthenticated user cannot set password', function () {
    $user = User::factory()->create();

    $this->put("/admin/users/{$user->id}/password", [
        'password' => 'newpassword123',
        'password_confirmation' => 'newpassword123',
    ])
        ->assertRedirect('/sign-in');
});
