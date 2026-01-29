<?php

use App\Models\User;

test('guests cannot access admin dashboard', function () {
    $this->get('/admin')->assertRedirect('/login');
});

test('non-admin users get 403 on admin dashboard', function () {
    $user = User::factory()->create(['is_admin' => false]);

    $this->actingAs($user)
        ->get('/admin')
        ->assertForbidden();
});

test('admin users can access admin dashboard', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get('/admin')
        ->assertOk();
});

test('non-admin users get 403 on admin users list', function () {
    $user = User::factory()->create(['is_admin' => false]);

    $this->actingAs($user)
        ->get('/admin/users')
        ->assertForbidden();
});

test('admin users can access admin users list', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get('/admin/users')
        ->assertOk();
});

test('non-admin users get 403 when viewing a user', function () {
    $user = User::factory()->create(['is_admin' => false]);
    $targetUser = User::factory()->create();

    $this->actingAs($user)
        ->get("/admin/users/{$targetUser->id}")
        ->assertForbidden();
});

test('admin users can view user details', function () {
    $admin = User::factory()->admin()->create();
    $targetUser = User::factory()->create();

    $this->actingAs($admin)
        ->get("/admin/users/{$targetUser->id}")
        ->assertOk();
});
