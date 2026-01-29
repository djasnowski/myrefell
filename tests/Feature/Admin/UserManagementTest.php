<?php

use App\Models\User;

test('admin can search users by username', function () {
    $admin = User::factory()->admin()->create();
    $user1 = User::factory()->create(['username' => 'testuser123']);
    $user2 = User::factory()->create(['username' => 'anotheruser']);

    $this->actingAs($admin)
        ->get('/admin/users?search=testuser')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Admin/Users/Index')
            ->has('users.data', 1)
        );
});

test('admin can search users by email', function () {
    $admin = User::factory()->admin()->create();
    $user1 = User::factory()->create(['email' => 'unique@example.com']);
    $user2 = User::factory()->create(['email' => 'different@test.com']);

    $this->actingAs($admin)
        ->get('/admin/users?search=unique@example')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Admin/Users/Index')
            ->has('users.data', 1)
        );
});

test('admin can filter users by banned status', function () {
    $admin = User::factory()->admin()->create();
    User::factory()->create(['banned_at' => null]);
    User::factory()->banned()->create();

    $this->actingAs($admin)
        ->get('/admin/users?banned=true')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Admin/Users/Index')
            ->has('users.data', 1)
        );
});

test('admin can filter users by admin status', function () {
    $admin1 = User::factory()->admin()->create();
    $admin2 = User::factory()->admin()->create();
    User::factory()->create(['is_admin' => false]);

    $this->actingAs($admin1)
        ->get('/admin/users?admin=true')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Admin/Users/Index')
            ->has('users.data', 2)
        );
});

test('admin can update user information', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create([
        'username' => 'oldusername',
        'email' => 'old@example.com',
    ]);

    $this->actingAs($admin)
        ->put("/admin/users/{$user->id}", [
            'username' => 'newusername',
            'email' => 'new@example.com',
        ])
        ->assertRedirect();

    $user->refresh();
    expect($user->username)->toBe('newusername');
    expect($user->email)->toBe('new@example.com');
});

test('admin cannot update to duplicate username', function () {
    $admin = User::factory()->admin()->create();
    $existingUser = User::factory()->create(['username' => 'existingname']);
    $user = User::factory()->create(['username' => 'originalname']);

    $this->actingAs($admin)
        ->put("/admin/users/{$user->id}", [
            'username' => 'existingname',
            'email' => $user->email,
        ])
        ->assertSessionHasErrors('username');
});

test('admin cannot update to duplicate email', function () {
    $admin = User::factory()->admin()->create();
    $existingUser = User::factory()->create(['email' => 'existing@example.com']);
    $user = User::factory()->create(['email' => 'original@example.com']);

    $this->actingAs($admin)
        ->put("/admin/users/{$user->id}", [
            'username' => $user->username,
            'email' => 'existing@example.com',
        ])
        ->assertSessionHasErrors('email');
});
