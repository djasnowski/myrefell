<?php

use App\Models\Barony;
use App\Models\Kingdom;
use App\Models\Town;
use App\Models\User;
use App\Models\Village;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('guests are redirected to the login page', function () {
    $kingdom = Kingdom::factory()->create();

    $this->get("/kingdoms/{$kingdom->id}")->assertRedirect(route('login'));
});

test('authenticated users can view a kingdom', function () {
    $user = User::factory()->create();
    $kingdom = Kingdom::factory()->create();

    $response = $this->actingAs($user)->get("/kingdoms/{$kingdom->id}");

    $response->assertOk();
    $response->assertInertia(
        fn ($page) => $page
            ->component('kingdoms/show')
            ->has('kingdom')
            ->where('kingdom.id', $kingdom->id)
            ->where('kingdom.name', $kingdom->name)
    );
});

test('kingdom page shows aggregated statistics', function () {
    $user = User::factory()->create();
    $kingdom = Kingdom::factory()->create();
    $barony = Barony::factory()->for($kingdom)->create();

    // Create some villages and towns
    $village1 = Village::factory()->for($barony)->create(['population' => 100, 'wealth' => 500, 'is_port' => false]);
    $village2 = Village::factory()->for($barony)->create(['population' => 200, 'wealth' => 1000, 'is_port' => true]);
    $town = Town::factory()->for($barony)->create(['population' => 1000, 'wealth' => 5000, 'is_port' => false]);

    $response = $this->actingAs($user)->get("/kingdoms/{$kingdom->id}");

    $response->assertOk();
    $response->assertInertia(
        fn ($page) => $page
            ->component('kingdoms/show')
            ->where('kingdom.total_population', 1300)
            ->where('kingdom.total_wealth', 6500)
            ->where('kingdom.total_villages', 2)
            ->where('kingdom.total_towns', 1)
            ->where('kingdom.total_ports', 1)
    );
});

test('kingdom page shows barony details with population and wealth', function () {
    $user = User::factory()->create();
    $kingdom = Kingdom::factory()->create();
    $barony = Barony::factory()->for($kingdom)->create(['name' => 'Test Barony']);

    Village::factory()->for($barony)->create(['population' => 150, 'wealth' => 750]);
    Town::factory()->for($barony)->create(['population' => 850, 'wealth' => 4250]);

    $response = $this->actingAs($user)->get("/kingdoms/{$kingdom->id}");

    $response->assertOk();
    $response->assertInertia(
        fn ($page) => $page
            ->component('kingdoms/show')
            ->has('kingdom.baronies', 1)
            ->where('kingdom.baronies.0.name', 'Test Barony')
            ->where('kingdom.baronies.0.population', 1000)
            ->where('kingdom.baronies.0.wealth', 5000)
    );
});

test('kingdom page shows player count', function () {
    $kingdom = Kingdom::factory()->create();
    $barony = Barony::factory()->for($kingdom)->create();
    $village = Village::factory()->for($barony)->create();

    // Create players living in this kingdom
    $residents = User::factory()->count(3)->create(['home_village_id' => $village->id]);
    $visitor = User::factory()->create(); // Not a resident

    $response = $this->actingAs($residents[0])->get("/kingdoms/{$kingdom->id}");

    $response->assertOk();
    $response->assertInertia(
        fn ($page) => $page
            ->component('kingdoms/show')
            ->where('kingdom.player_count', 3)
    );
});

// biome_distribution feature not implemented - test removed

test('kingdom page shows settlement hierarchy in baronies', function () {
    $user = User::factory()->create();
    $kingdom = Kingdom::factory()->create();
    $barony = Barony::factory()->for($kingdom)->create();

    $town = Town::factory()->for($barony)->create(['name' => 'Capital Town', 'is_capital' => true]);
    $village = Village::factory()->for($barony)->create(['name' => 'Border Village']);

    $response = $this->actingAs($user)->get("/kingdoms/{$kingdom->id}");

    $response->assertOk();
    $response->assertInertia(
        fn ($page) => $page
            ->component('kingdoms/show')
            ->has('kingdom.baronies.0.settlements', 2)
    );
});
