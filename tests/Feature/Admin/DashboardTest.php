<?php

use App\Models\User;
use App\Services\AdminAnalyticsService;

test('dashboard shows correct stats', function () {
    // Create some test data
    $admin = User::factory()->admin()->create();
    User::factory()->count(5)->create(['is_admin' => false]);
    User::factory()->banned()->create();
    User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get('/admin')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Admin/Dashboard')
            ->has('stats')
            ->where('stats.totalUsers', 8) // 5 regular + 1 banned + 2 admins
            ->where('stats.bannedUsers', 1)
            ->where('stats.adminUsers', 2)
        );
});

test('dashboard shows registration trend data', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get('/admin')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Admin/Dashboard')
            ->has('registrationTrend')
            ->has('activeUsersTrend')
        );
});

test('analytics service returns correct total users count', function () {
    User::factory()->count(10)->create();

    $service = app(AdminAnalyticsService::class);

    expect($service->getTotalUsers())->toBe(10);
});

test('analytics service returns correct banned users count', function () {
    User::factory()->count(5)->create(['banned_at' => null]);
    User::factory()->count(3)->banned()->create();

    $service = app(AdminAnalyticsService::class);

    expect($service->getBannedUsersCount())->toBe(3);
});

test('analytics service returns correct admin users count', function () {
    User::factory()->count(5)->create(['is_admin' => false]);
    User::factory()->count(2)->admin()->create();

    $service = app(AdminAnalyticsService::class);

    expect($service->getAdminUsersCount())->toBe(2);
});

test('analytics service returns correct new users today count', function () {
    // Create users from yesterday
    User::factory()->count(3)->create([
        'created_at' => now()->subDay(),
    ]);

    // Create users today
    User::factory()->count(5)->create([
        'created_at' => now(),
    ]);

    $service = app(AdminAnalyticsService::class);

    expect($service->getNewUsersToday())->toBe(5);
});

test('analytics service returns registration trend with 30 days', function () {
    $admin = User::factory()->admin()->create();

    $service = app(AdminAnalyticsService::class);
    $trend = $service->getRegistrationTrend(30);

    expect($trend)->toHaveCount(30);
    expect($trend->first())->toHaveKeys(['date', 'count']);
});
