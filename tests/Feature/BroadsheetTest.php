<?php

use App\Models\Broadsheet;
use App\Models\BroadsheetComment;
use App\Models\BroadsheetReaction;
use App\Models\BroadsheetView;
use App\Models\User;
use App\Models\Village;
use App\Services\BroadsheetService;

function createUserAtVillage(): array
{
    $village = Village::factory()->create();

    $user = User::factory()->create([
        'gold' => 500,
        'home_location_type' => 'village',
        'home_location_id' => $village->id,
        'home_village_id' => $village->id,
        'current_location_type' => 'village',
        'current_location_id' => $village->id,
    ]);

    return [$user, $village];
}

function buildLocationData(Village $village): array
{
    $village->loadMissing('barony.kingdom');

    return [
        'type' => 'village',
        'id' => $village->id,
        'name' => $village->name,
        'barony_id' => $village->barony_id,
        'barony_name' => $village->barony->name,
        'kingdom_id' => $village->barony->kingdom_id,
        'kingdom_name' => $village->barony->kingdom->name,
    ];
}

// ==================== PUBLISH TESTS ====================

test('publishing a broadsheet costs 50g at a village', function () {
    [$user, $village] = createUserAtVillage();
    $service = app(BroadsheetService::class);
    $locationData = buildLocationData($village);

    $result = $service->publish($user, [
        'title' => 'Test Broadsheet',
        'content' => [['type' => 'paragraph', 'children' => [['text' => 'Hello world']]]],
        'plain_text' => 'Hello world',
    ], $locationData);

    expect($result['success'])->toBeTrue();
    expect($result['message'])->toContain('50g');
    expect($user->fresh()->gold)->toBe(450);
    expect(Broadsheet::count())->toBe(1);
});

test('cannot publish without enough gold', function () {
    [$user, $village] = createUserAtVillage();
    $user->update(['gold' => 10]);
    $service = app(BroadsheetService::class);
    $locationData = buildLocationData($village);

    $result = $service->publish($user, [
        'title' => 'Test',
        'content' => [['type' => 'paragraph', 'children' => [['text' => 'Hello']]]],
        'plain_text' => 'Hello',
    ], $locationData);

    expect($result['success'])->toBeFalse();
    expect($result['message'])->toContain('50g');
    expect(Broadsheet::count())->toBe(0);
});

test('cannot publish more than once per day', function () {
    [$user, $village] = createUserAtVillage();
    $service = app(BroadsheetService::class);
    $locationData = buildLocationData($village);

    $first = $service->publish($user, [
        'title' => 'First',
        'content' => [['type' => 'paragraph', 'children' => [['text' => 'First']]]],
        'plain_text' => 'First',
    ], $locationData);

    expect($first['success'])->toBeTrue();

    $second = $service->publish($user, [
        'title' => 'Second',
        'content' => [['type' => 'paragraph', 'children' => [['text' => 'Second']]]],
        'plain_text' => 'Second',
    ], $locationData);

    expect($second['success'])->toBeFalse();
    expect($second['message'])->toContain('already published');
    expect(Broadsheet::count())->toBe(1);
});

test('deleting a broadsheet allows publishing again the same day', function () {
    [$user, $village] = createUserAtVillage();
    $service = app(BroadsheetService::class);
    $locationData = buildLocationData($village);

    $first = $service->publish($user, [
        'title' => 'First',
        'content' => [['type' => 'paragraph', 'children' => [['text' => 'First']]]],
        'plain_text' => 'First',
    ], $locationData);

    expect($first['success'])->toBeTrue();

    $service->delete($user, $first['broadsheet']);

    $user->refresh();

    $second = $service->publish($user, [
        'title' => 'Second',
        'content' => [['type' => 'paragraph', 'children' => [['text' => 'Second']]]],
        'plain_text' => 'Second',
    ], $locationData);

    expect($second['success'])->toBeTrue();
    expect(Broadsheet::count())->toBe(1);
    expect(Broadsheet::first()->title)->toBe('Second');
});

test('broadsheet is posted to the current location', function () {
    [$user, $village] = createUserAtVillage();
    $service = app(BroadsheetService::class);
    $locationData = buildLocationData($village);

    $result = $service->publish($user, [
        'title' => 'Test',
        'content' => [['type' => 'paragraph', 'children' => [['text' => 'Hello']]]],
        'plain_text' => 'Hello',
    ], $locationData);

    $broadsheet = $result['broadsheet'];

    expect($broadsheet->location_type)->toBe('village');
    expect($broadsheet->location_id)->toBe($village->id);
    expect($broadsheet->barony_id)->not->toBeNull();
    expect($broadsheet->kingdom_id)->not->toBeNull();
    expect($broadsheet->location_name)->not->toBeNull();
});

// ==================== REACTION TESTS ====================

test('can endorse a broadsheet', function () {
    $user = User::factory()->create();
    $broadsheet = Broadsheet::factory()->create();
    $service = app(BroadsheetService::class);

    $result = $service->react($user, $broadsheet, 'endorse');

    expect($result['success'])->toBeTrue();
    expect($broadsheet->fresh()->endorse_count)->toBe(1);
    expect(BroadsheetReaction::count())->toBe(1);
});

test('can denounce a broadsheet', function () {
    $user = User::factory()->create();
    $broadsheet = Broadsheet::factory()->create();
    $service = app(BroadsheetService::class);

    $result = $service->react($user, $broadsheet, 'denounce');

    expect($result['success'])->toBeTrue();
    expect($broadsheet->fresh()->denounce_count)->toBe(1);
});

test('toggling same reaction removes it', function () {
    $user = User::factory()->create();
    $broadsheet = Broadsheet::factory()->create();
    $service = app(BroadsheetService::class);

    $service->react($user, $broadsheet, 'endorse');
    expect($broadsheet->fresh()->endorse_count)->toBe(1);

    $service->react($user, $broadsheet, 'endorse');
    expect($broadsheet->fresh()->endorse_count)->toBe(0);
    expect(BroadsheetReaction::count())->toBe(0);
});

test('switching reaction updates both counts', function () {
    $user = User::factory()->create();
    $broadsheet = Broadsheet::factory()->create();
    $service = app(BroadsheetService::class);

    $service->react($user, $broadsheet, 'endorse');
    expect($broadsheet->fresh()->endorse_count)->toBe(1);

    $service->react($user, $broadsheet, 'denounce');
    $fresh = $broadsheet->fresh();
    expect($fresh->endorse_count)->toBe(0);
    expect($fresh->denounce_count)->toBe(1);
});

// ==================== COMMENT TESTS ====================

test('can add a comment', function () {
    $user = User::factory()->create();
    $broadsheet = Broadsheet::factory()->create();
    $service = app(BroadsheetService::class);

    $result = $service->comment($user, $broadsheet, 'Great article!');

    expect($result['success'])->toBeTrue();
    expect($broadsheet->fresh()->comment_count)->toBe(1);
    expect(BroadsheetComment::count())->toBe(1);
});

test('can reply to a top-level comment', function () {
    $user = User::factory()->create();
    $broadsheet = Broadsheet::factory()->create();
    $comment = BroadsheetComment::factory()->create(['broadsheet_id' => $broadsheet->id]);
    $broadsheet->increment('comment_count');
    $service = app(BroadsheetService::class);

    $result = $service->comment($user, $broadsheet, 'I agree!', $comment->id);

    expect($result['success'])->toBeTrue();
    expect($broadsheet->fresh()->comment_count)->toBe(2);
});

test('cannot reply to a reply (only one level of nesting)', function () {
    $broadsheet = Broadsheet::factory()->create();
    $parent = BroadsheetComment::factory()->create(['broadsheet_id' => $broadsheet->id]);
    $reply = BroadsheetComment::factory()->create([
        'broadsheet_id' => $broadsheet->id,
        'parent_id' => $parent->id,
    ]);

    $user = User::factory()->create();
    $service = app(BroadsheetService::class);

    $result = $service->comment($user, $broadsheet, 'Nested reply', $reply->id);

    expect($result['success'])->toBeFalse();
    expect($result['message'])->toContain('top-level');
});

test('can delete own comment', function () {
    $user = User::factory()->create();
    $broadsheet = Broadsheet::factory()->create(['comment_count' => 1]);
    $comment = BroadsheetComment::factory()->create([
        'broadsheet_id' => $broadsheet->id,
        'user_id' => $user->id,
    ]);

    $service = app(BroadsheetService::class);
    $result = $service->deleteComment($user, $comment);

    expect($result['success'])->toBeTrue();
    expect($broadsheet->fresh()->comment_count)->toBe(0);
    expect(BroadsheetComment::count())->toBe(0);
});

test('cannot delete another users comment', function () {
    $user = User::factory()->create();
    $comment = BroadsheetComment::factory()->create();

    $service = app(BroadsheetService::class);
    $result = $service->deleteComment($user, $comment);

    expect($result['success'])->toBeFalse();
    expect($result['message'])->toContain('your own');
});

test('deleting a parent comment cascades reply count', function () {
    $user = User::factory()->create();
    $broadsheet = Broadsheet::factory()->create(['comment_count' => 3]);
    $parent = BroadsheetComment::factory()->create([
        'broadsheet_id' => $broadsheet->id,
        'user_id' => $user->id,
    ]);
    BroadsheetComment::factory()->count(2)->create([
        'broadsheet_id' => $broadsheet->id,
        'parent_id' => $parent->id,
    ]);

    $service = app(BroadsheetService::class);
    $result = $service->deleteComment($user, $parent);

    expect($result['success'])->toBeTrue();
    expect($broadsheet->fresh()->comment_count)->toBe(0);
    expect(BroadsheetComment::count())->toBe(0);
});

// ==================== VIEW TESTS ====================

test('viewing a broadsheet records a unique view', function () {
    $user = User::factory()->create();
    $broadsheet = Broadsheet::factory()->create();
    $service = app(BroadsheetService::class);

    $service->recordView($user, $broadsheet);

    expect($broadsheet->fresh()->view_count)->toBe(1);
    expect(BroadsheetView::count())->toBe(1);
});

test('viewing twice only counts once', function () {
    $user = User::factory()->create();
    $broadsheet = Broadsheet::factory()->create();
    $service = app(BroadsheetService::class);

    $service->recordView($user, $broadsheet);
    $service->recordView($user, $broadsheet);

    expect($broadsheet->fresh()->view_count)->toBe(1);
    expect(BroadsheetView::count())->toBe(1);
});

test('author viewing own broadsheet does not count', function () {
    $broadsheet = Broadsheet::factory()->create();
    $author = User::find($broadsheet->author_id);
    $service = app(BroadsheetService::class);

    $service->recordView($author, $broadsheet);

    expect($broadsheet->fresh()->view_count)->toBe(0);
    expect(BroadsheetView::count())->toBe(0);
});

// ==================== DELETE TESTS ====================

test('author can delete their broadsheet', function () {
    $broadsheet = Broadsheet::factory()->create();
    $author = User::find($broadsheet->author_id);
    $service = app(BroadsheetService::class);

    $result = $service->delete($author, $broadsheet);

    expect($result['success'])->toBeTrue();
    expect(Broadsheet::count())->toBe(0);
});

test('non-author cannot delete broadsheet', function () {
    $broadsheet = Broadsheet::factory()->create();
    $stranger = User::factory()->create();
    $service = app(BroadsheetService::class);

    $result = $service->delete($stranger, $broadsheet);

    expect($result['success'])->toBeFalse();
    expect(Broadsheet::count())->toBe(1);
});

// ==================== QUERY TESTS ====================

test('local broadsheets returns only same location', function () {
    $village = Village::factory()->create();
    Broadsheet::factory()->count(3)->create([
        'location_type' => 'village',
        'location_id' => $village->id,
    ]);
    Broadsheet::factory()->create(); // Different location

    $service = app(BroadsheetService::class);
    $results = $service->getLocalBroadsheets('village', $village->id);

    expect($results->total())->toBe(3);
});

test('barony broadsheets requires 5+ endorsements', function () {
    $village = Village::factory()->create();
    $baronyId = $village->barony_id;

    Broadsheet::factory()->create(['barony_id' => $baronyId, 'endorse_count' => 4]);
    Broadsheet::factory()->create(['barony_id' => $baronyId, 'endorse_count' => 5]);
    Broadsheet::factory()->create(['barony_id' => $baronyId, 'endorse_count' => 10]);

    $service = app(BroadsheetService::class);
    $results = $service->getBaronyBroadsheets($baronyId);

    expect($results->total())->toBe(2);
});

test('kingdom broadsheets requires 15+ endorsements and ordered by endorsements', function () {
    $village = Village::factory()->create();
    $kingdomId = $village->barony->kingdom_id;

    Broadsheet::factory()->create(['kingdom_id' => $kingdomId, 'endorse_count' => 14]);
    $mid = Broadsheet::factory()->create(['kingdom_id' => $kingdomId, 'endorse_count' => 15]);
    $high = Broadsheet::factory()->create(['kingdom_id' => $kingdomId, 'endorse_count' => 20]);

    $service = app(BroadsheetService::class);
    $results = $service->getKingdomBroadsheets($kingdomId);

    expect($results->total())->toBe(2);
    expect($results->first()->id)->toBe($high->id);
});

// ==================== ROUTE TESTS ====================

test('notice board index page loads at village', function () {
    [$user, $village] = createUserAtVillage();

    $this->actingAs($user)
        ->get("/villages/{$village->id}/notice-board")
        ->assertOk();
});

test('broadsheet show page loads at village', function () {
    [$user, $village] = createUserAtVillage();
    $broadsheet = Broadsheet::factory()->create([
        'location_type' => 'village',
        'location_id' => $village->id,
    ]);

    $this->actingAs($user)
        ->get("/villages/{$village->id}/notice-board/{$broadsheet->id}")
        ->assertOk();
});

test('global broadsheet show route loads', function () {
    [$user, $village] = createUserAtVillage();
    $broadsheet = Broadsheet::factory()->create();

    $this->actingAs($user)
        ->get("/broadsheets/{$broadsheet->id}")
        ->assertOk();
});

test('store broadsheet endpoint validates input', function () {
    [$user, $village] = createUserAtVillage();

    $this->actingAs($user)
        ->post("/villages/{$village->id}/notice-board", [])
        ->assertSessionHasErrors(['title', 'content', 'plain_text']);
});

test('store broadsheet endpoint creates broadsheet', function () {
    [$user, $village] = createUserAtVillage();

    $this->actingAs($user)
        ->post("/villages/{$village->id}/notice-board", [
            'title' => 'Test Title',
            'content' => [['type' => 'paragraph', 'children' => [['text' => 'Test content']]]],
            'plain_text' => 'Test content',
        ])
        ->assertRedirect();

    expect(Broadsheet::count())->toBe(1);
    expect($user->fresh()->gold)->toBe(450);
});

test('react endpoint toggles reaction', function () {
    [$user, $village] = createUserAtVillage();
    $broadsheet = Broadsheet::factory()->create();

    $this->actingAs($user)
        ->post("/villages/{$village->id}/notice-board/{$broadsheet->id}/react", ['type' => 'endorse'])
        ->assertRedirect();

    expect($broadsheet->fresh()->endorse_count)->toBe(1);
});

test('comment endpoint posts comment', function () {
    [$user, $village] = createUserAtVillage();
    $broadsheet = Broadsheet::factory()->create();

    $this->actingAs($user)
        ->post("/villages/{$village->id}/notice-board/{$broadsheet->id}/comments", ['body' => 'Nice!'])
        ->assertRedirect();

    expect($broadsheet->fresh()->comment_count)->toBe(1);
});

test('delete broadsheet redirects back', function () {
    [$user, $village] = createUserAtVillage();
    $broadsheet = Broadsheet::factory()->create(['author_id' => $user->id]);

    $this->actingAs($user)
        ->delete("/villages/{$village->id}/notice-board/{$broadsheet->id}")
        ->assertRedirect();

    expect(Broadsheet::count())->toBe(0);
});

test('delete comment endpoint works', function () {
    [$user, $village] = createUserAtVillage();
    $broadsheet = Broadsheet::factory()->create(['comment_count' => 1]);
    $comment = BroadsheetComment::factory()->create([
        'broadsheet_id' => $broadsheet->id,
        'user_id' => $user->id,
    ]);

    $this->actingAs($user)
        ->delete("/villages/{$village->id}/notice-board/{$comment->id}/delete-comment")
        ->assertRedirect();

    expect(BroadsheetComment::count())->toBe(0);
});

// ==================== PUBLISH COST TESTS ====================

test('publish cost varies by location type', function () {
    $service = app(BroadsheetService::class);

    expect($service->getPublishCost('village'))->toBe(50);
    expect($service->getPublishCost('town'))->toBe(50);
    expect($service->getPublishCost('barony'))->toBe(100);
    expect($service->getPublishCost('duchy'))->toBe(150);
    expect($service->getPublishCost('kingdom'))->toBe(200);
});
