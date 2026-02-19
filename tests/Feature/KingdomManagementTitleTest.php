<?php

use App\Models\Barony;
use App\Models\Kingdom;
use App\Models\PlayerTitle;
use App\Models\TitleType;
use App\Models\User;
use App\Models\Village;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

function createKingWithTitleSetup(): array
{
    $kingdom = Kingdom::factory()->create();
    $barony = Barony::factory()->for($kingdom)->create();
    $village = Village::factory()->for($barony)->create();

    $king = User::factory()->create([
        'current_kingdom_id' => $kingdom->id,
        'home_village_id' => $village->id,
    ]);
    $kingdom->update(['king_user_id' => $king->id]);

    // Give the king a king title so TitleService::canGrantTitle works
    $kingTitleType = TitleType::create([
        'name' => 'King',
        'slug' => 'king',
        'tier' => 14,
        'category' => TitleType::CATEGORY_ROYALTY,
        'is_landed' => true,
        'domain_type' => 'kingdom',
        'granted_by' => 'emperor',
        'progression_type' => 'special',
        'requires_ceremony' => false,
        'is_active' => true,
        'prestige_bonus' => 300,
        'style_of_address' => 'Your Majesty',
        'description' => 'Ruler of a kingdom.',
    ]);

    PlayerTitle::create([
        'user_id' => $king->id,
        'title_type_id' => $kingTitleType->id,
        'title' => 'king',
        'tier' => 14,
        'domain_type' => 'kingdom',
        'domain_id' => $kingdom->id,
        'acquisition_method' => 'appointment',
        'granted_by_user_id' => $king->id,
        'is_active' => true,
        'granted_at' => now(),
    ]);

    $king->update(['primary_title' => 'king', 'title_tier' => 14]);

    // Create a grantable title type (Knight - granted_by includes 'king')
    $knightTitleType = TitleType::create([
        'name' => 'Knight',
        'slug' => 'knight',
        'tier' => 6,
        'category' => TitleType::CATEGORY_MINOR_NOBILITY,
        'is_landed' => false,
        'domain_type' => 'barony',
        'limit_per_domain' => 10,
        'granted_by' => 'baronet,baron,viscount,count,marquess,duke,prince,king',
        'progression_type' => 'petition',
        'requires_ceremony' => false,
        'is_active' => true,
        'prestige_bonus' => 25,
        'style_of_address' => 'Sir',
        'description' => 'A warrior of noble rank.',
    ]);

    // Create a title the king cannot grant (Emperor - granted_by is null)
    $emperorTitleType = TitleType::create([
        'name' => 'Emperor',
        'slug' => 'emperor',
        'tier' => 15,
        'category' => TitleType::CATEGORY_ROYALTY,
        'is_landed' => true,
        'granted_by' => null,
        'progression_type' => 'special',
        'requires_ceremony' => true,
        'is_active' => true,
        'prestige_bonus' => 500,
        'style_of_address' => 'Your Imperial Majesty',
        'description' => 'Ruler of multiple kingdoms.',
    ]);

    return [
        'kingdom' => $kingdom,
        'barony' => $barony,
        'village' => $village,
        'king' => $king,
        'kingTitleType' => $kingTitleType,
        'knightTitleType' => $knightTitleType,
        'emperorTitleType' => $emperorTitleType,
    ];
}

test('management page includes title data in props', function () {
    $setup = createKingWithTitleSetup();
    $kingdom = $setup['kingdom'];
    $king = $setup['king'];

    // Create a subject in the kingdom
    $subject = User::factory()->create([
        'current_kingdom_id' => $kingdom->id,
        'home_village_id' => $setup['village']->id,
    ]);

    $response = $this->actingAs($king)->get("/kingdoms/{$kingdom->id}/management");

    $response->assertOk();
    $response->assertInertia(
        fn ($page) => $page
            ->component('kingdoms/management')
            ->has('grantable_titles')
            ->has('kingdom_subjects')
            ->has('titled_players')
    );
});

test('grantable titles only includes titles the king can grant', function () {
    $setup = createKingWithTitleSetup();
    $kingdom = $setup['kingdom'];
    $king = $setup['king'];

    $response = $this->actingAs($king)->get("/kingdoms/{$kingdom->id}/management");

    $response->assertOk();
    $response->assertInertia(function ($page) {
        $page->has('grantable_titles');
        $titles = $page->toArray()['props']['grantable_titles'];

        // Knight should be grantable (granted_by includes 'king')
        $titleSlugs = array_column($titles, 'slug');
        expect($titleSlugs)->toContain('knight');

        // Emperor should NOT be grantable (granted_by is null)
        expect($titleSlugs)->not->toContain('emperor');

        // King title should NOT be grantable (granted_by is 'emperor', not 'king')
        expect($titleSlugs)->not->toContain('king');
    });
});

test('kingdom subjects includes only players settled in the kingdom', function () {
    $setup = createKingWithTitleSetup();
    $kingdom = $setup['kingdom'];
    $king = $setup['king'];

    // Settled in a village within the kingdom
    $settledVillage = User::factory()->create([
        'home_village_id' => $setup['village']->id,
    ]);

    // Settled in the kingdom itself via home_location
    $settledKingdom = User::factory()->create([
        'home_location_type' => 'kingdom',
        'home_location_id' => $kingdom->id,
        'home_village_id' => null,
    ]);

    // Only visiting, not settled
    $visitor = User::factory()->create([
        'current_kingdom_id' => $kingdom->id,
        'home_village_id' => null,
        'home_location_type' => null,
        'home_location_id' => null,
    ]);

    // Not in this kingdom at all
    $outsider = User::factory()->create([
        'home_village_id' => null,
        'home_location_type' => null,
        'home_location_id' => null,
    ]);

    $response = $this->actingAs($king)->get("/kingdoms/{$kingdom->id}/management");

    $response->assertOk();
    $response->assertInertia(function ($page) use ($settledVillage, $settledKingdom, $visitor, $outsider) {
        $subjects = $page->toArray()['props']['kingdom_subjects'];
        $subjectIds = array_column($subjects, 'id');
        expect($subjectIds)->toContain($settledVillage->id);
        expect($subjectIds)->toContain($settledKingdom->id);
        expect($subjectIds)->not->toContain($visitor->id);
        expect($subjectIds)->not->toContain($outsider->id);
    });
});

test('titled players shows active titles for kingdom members', function () {
    $setup = createKingWithTitleSetup();
    $kingdom = $setup['kingdom'];
    $king = $setup['king'];

    $subject = User::factory()->create([
        'current_kingdom_id' => $kingdom->id,
        'home_village_id' => $setup['village']->id,
    ]);

    PlayerTitle::create([
        'user_id' => $subject->id,
        'title_type_id' => $setup['knightTitleType']->id,
        'title' => 'knight',
        'tier' => 6,
        'domain_type' => 'kingdom',
        'domain_id' => $kingdom->id,
        'acquisition_method' => 'appointment',
        'granted_by_user_id' => $king->id,
        'is_active' => true,
        'granted_at' => now(),
    ]);

    $response = $this->actingAs($king)->get("/kingdoms/{$kingdom->id}/management");

    $response->assertOk();
    $response->assertInertia(function ($page) use ($subject) {
        $titled = $page->toArray()['props']['titled_players'];
        $userIds = array_column($titled, 'user_id');
        expect($userIds)->toContain($subject->id);
    });
});

test('non-king cannot access management page', function () {
    $setup = createKingWithTitleSetup();
    $kingdom = $setup['kingdom'];
    $regularUser = User::factory()->create();

    $response = $this->actingAs($regularUser)->get("/kingdoms/{$kingdom->id}/management");

    $response->assertForbidden();
});

test('king can grant a title via POST /titles/grant', function () {
    $setup = createKingWithTitleSetup();
    $kingdom = $setup['kingdom'];
    $king = $setup['king'];

    $subject = User::factory()->create([
        'current_kingdom_id' => $kingdom->id,
    ]);

    $response = $this->actingAs($king)->post('/titles/grant', [
        'recipient_id' => $subject->id,
        'title_type_id' => $setup['knightTitleType']->id,
        'domain_type' => 'kingdom',
        'domain_id' => $kingdom->id,
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('player_titles', [
        'user_id' => $subject->id,
        'title_type_id' => $setup['knightTitleType']->id,
        'is_active' => true,
    ]);
});

test('king cannot grant a title they lack authority for', function () {
    $setup = createKingWithTitleSetup();
    $kingdom = $setup['kingdom'];
    $king = $setup['king'];

    $subject = User::factory()->create([
        'current_kingdom_id' => $kingdom->id,
    ]);

    $response = $this->actingAs($king)->post('/titles/grant', [
        'recipient_id' => $subject->id,
        'title_type_id' => $setup['emperorTitleType']->id,
        'domain_type' => 'kingdom',
        'domain_id' => $kingdom->id,
    ]);

    $response->assertRedirect();
    $response->assertSessionHasErrors('grant');

    $this->assertDatabaseMissing('player_titles', [
        'user_id' => $subject->id,
        'title_type_id' => $setup['emperorTitleType']->id,
    ]);
});

test('granting same title twice to same user fails', function () {
    $setup = createKingWithTitleSetup();
    $kingdom = $setup['kingdom'];
    $king = $setup['king'];

    $subject = User::factory()->create([
        'current_kingdom_id' => $kingdom->id,
    ]);

    // First grant succeeds
    $this->actingAs($king)->post('/titles/grant', [
        'recipient_id' => $subject->id,
        'title_type_id' => $setup['knightTitleType']->id,
        'domain_type' => 'kingdom',
        'domain_id' => $kingdom->id,
    ])->assertSessionHas('success');

    // Second grant fails
    $this->actingAs($king)->post('/titles/grant', [
        'recipient_id' => $subject->id,
        'title_type_id' => $setup['knightTitleType']->id,
        'domain_type' => 'kingdom',
        'domain_id' => $kingdom->id,
    ])->assertSessionHasErrors('grant');
});
