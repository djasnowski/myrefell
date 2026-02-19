<?php

use App\Models\Kingdom;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $this->get(route('dashboard'))->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $this->actingAs($user = User::factory()->create());

    $this->get(route('dashboard'))->assertOk();
});

test('dashboard shows kingdoms with their kings', function () {
    $king = User::factory()->create(['username' => 'TestKing']);
    $kingdom = Kingdom::factory()->create([
        'name' => 'Testlands',
        'king_user_id' => $king->id,
    ]);

    $response = $this->actingAs(User::factory()->create())->get(route('dashboard'));

    $response->assertOk();
    $response->assertInertia(
        fn ($page) => $page
            ->component('dashboard')
            ->has('kingdoms')
            ->where('kingdoms.0.name', 'Testlands')
            ->where('kingdoms.0.king.username', 'TestKing')
    );
});
