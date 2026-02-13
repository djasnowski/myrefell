<?php

use App\Jobs\ProcessActionQueue;
use App\Models\ActionQueue;
use App\Models\User;
use App\Models\Village;
use App\Services\ActionQueueService;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    User::query()->delete();
});

test('can start an action queue via API', function () {
    Queue::fake();

    $village = Village::factory()->create();
    $user = User::factory()->create([
        'current_location_type' => 'village',
        'current_location_id' => $village->id,
        'energy' => 100,
    ]);

    $response = $this->actingAs($user)->postJson('/action-queue/start', [
        'action_type' => 'train',
        'action_params' => ['exercise' => 'attack'],
        'total' => 5,
    ]);

    $response->assertSuccessful();
    $response->assertJson(['success' => true]);

    expect(ActionQueue::where('user_id', $user->id)->count())->toBe(1);

    $queue = ActionQueue::where('user_id', $user->id)->first();
    expect($queue->action_type)->toBe('train');
    expect($queue->action_params)->toBe(['exercise' => 'attack']);
    expect($queue->status)->toBe('active');
    expect($queue->total)->toBe(5);
    expect($queue->completed)->toBe(0);

    Queue::assertPushedOn('action-queue', ProcessActionQueue::class);
});

test('cannot start a second queue while one is active', function () {
    Queue::fake();

    $user = User::factory()->create(['energy' => 100]);

    ActionQueue::create([
        'user_id' => $user->id,
        'action_type' => 'train',
        'action_params' => ['exercise' => 'attack'],
        'status' => 'active',
        'total' => 5,
    ]);

    $response = $this->actingAs($user)->postJson('/action-queue/start', [
        'action_type' => 'train',
        'action_params' => ['exercise' => 'strength'],
        'total' => 5,
    ]);

    $response->assertStatus(422);
    $response->assertJson(['success' => false, 'message' => 'You already have an active queue running.']);
});

test('can cancel an active queue', function () {
    $user = User::factory()->create(['energy' => 100]);

    $queue = ActionQueue::create([
        'user_id' => $user->id,
        'action_type' => 'train',
        'action_params' => ['exercise' => 'attack'],
        'status' => 'active',
        'total' => 10,
        'completed' => 3,
    ]);

    $response = $this->actingAs($user)->postJson('/action-queue/cancel');

    $response->assertSuccessful();
    $response->assertJson(['success' => true]);

    $queue->refresh();
    expect($queue->status)->toBe('cancelled');
    expect($queue->stop_reason)->toBe('Cancelled by player.');
});

test('cancel returns error when no active queue exists', function () {
    $user = User::factory()->create(['energy' => 100]);

    $response = $this->actingAs($user)->postJson('/action-queue/cancel');

    $response->assertStatus(422);
    $response->assertJson(['success' => false]);
});

test('can dismiss a completed queue', function () {
    $user = User::factory()->create(['energy' => 100]);

    $queue = ActionQueue::create([
        'user_id' => $user->id,
        'action_type' => 'train',
        'action_params' => ['exercise' => 'attack'],
        'status' => 'completed',
        'total' => 5,
        'completed' => 5,
    ]);

    $response = $this->actingAs($user)->postJson('/action-queue/dismiss', [
        'queue_id' => $queue->id,
    ]);

    $response->assertSuccessful();

    $queue->refresh();
    expect($queue->dismissed_at)->not->toBeNull();
});

test('cannot dismiss another users queue', function () {
    $user = User::factory()->create(['energy' => 100]);
    $otherUser = User::factory()->create(['energy' => 100]);

    $queue = ActionQueue::create([
        'user_id' => $otherUser->id,
        'action_type' => 'train',
        'action_params' => ['exercise' => 'attack'],
        'status' => 'completed',
        'total' => 5,
        'completed' => 5,
    ]);

    $response = $this->actingAs($user)->postJson('/action-queue/dismiss', [
        'queue_id' => $queue->id,
    ]);

    $response->assertStatus(422);
    $response->assertJson(['success' => false]);
});

test('service returns latest visible queue', function () {
    $user = User::factory()->create(['energy' => 100]);
    $service = app(ActionQueueService::class);

    // No queue
    expect($service->getLatestQueue($user))->toBeNull();

    // Create completed queue
    $queue = ActionQueue::create([
        'user_id' => $user->id,
        'action_type' => 'train',
        'action_params' => ['exercise' => 'attack'],
        'status' => 'completed',
        'total' => 5,
        'completed' => 5,
    ]);

    expect($service->getLatestQueue($user))->not->toBeNull();
    expect($service->getLatestQueue($user)->id)->toBe($queue->id);

    // Dismiss it
    $queue->update(['dismissed_at' => now()]);
    expect($service->getLatestQueue($user))->toBeNull();
});

test('cleanup stale queues marks old active queues as failed', function () {
    $user = User::factory()->create(['energy' => 100]);
    $service = app(ActionQueueService::class);

    // Active queue updated recently — should not be cleaned
    $fresh = ActionQueue::create([
        'user_id' => $user->id,
        'action_type' => 'train',
        'action_params' => ['exercise' => 'attack'],
        'status' => 'active',
        'total' => 10,
        'completed' => 2,
    ]);

    $cleaned = $service->cleanupStaleQueues();
    expect($cleaned)->toBe(0);
    $fresh->refresh();
    expect($fresh->status)->toBe('active');

    // Make it stale (use query builder to bypass Eloquent timestamp touching)
    ActionQueue::where('id', $fresh->id)->update(['updated_at' => now()->subMinutes(10)]);

    $cleaned = $service->cleanupStaleQueues();
    expect($cleaned)->toBe(1);
    $fresh->refresh();
    expect($fresh->status)->toBe('failed');
    expect($fresh->stop_reason)->toContain('timed out');
});

test('process action queue job stops if queue is not active', function () {
    $user = User::factory()->create(['energy' => 100]);

    $queue = ActionQueue::create([
        'user_id' => $user->id,
        'action_type' => 'train',
        'action_params' => ['exercise' => 'attack'],
        'status' => 'cancelled',
        'total' => 10,
        'completed' => 3,
    ]);

    Queue::fake();

    $job = new ProcessActionQueue($queue->id);
    $job->handle(
        app(\App\Services\CraftingService::class),
        app(\App\Services\GatheringService::class),
        app(\App\Services\TrainingService::class),
        app(\App\Services\AgilityService::class),
    );

    // Should not dispatch another job
    Queue::assertNothingPushed();

    // Queue status should remain unchanged
    $queue->refresh();
    expect($queue->status)->toBe('cancelled');
});

test('process action queue job stops if user is traveling', function () {
    $user = User::factory()->create([
        'energy' => 100,
        'is_traveling' => true,
        'travel_arrives_at' => now()->addMinutes(5),
    ]);

    $queue = ActionQueue::create([
        'user_id' => $user->id,
        'action_type' => 'train',
        'action_params' => ['exercise' => 'attack'],
        'status' => 'active',
        'total' => 10,
        'completed' => 0,
    ]);

    Queue::fake();

    $job = new ProcessActionQueue($queue->id);
    $job->handle(
        app(\App\Services\CraftingService::class),
        app(\App\Services\GatheringService::class),
        app(\App\Services\TrainingService::class),
        app(\App\Services\AgilityService::class),
    );

    Queue::assertNothingPushed();

    $queue->refresh();
    expect($queue->status)->toBe('cancelled');
    expect($queue->stop_reason)->toContain('traveling');
});

test('process action queue job stops if user is in infirmary', function () {
    $user = User::factory()->create([
        'energy' => 100,
        'is_in_infirmary' => true,
        'infirmary_heals_at' => now()->addMinutes(5),
    ]);

    $queue = ActionQueue::create([
        'user_id' => $user->id,
        'action_type' => 'train',
        'action_params' => ['exercise' => 'attack'],
        'status' => 'active',
        'total' => 10,
        'completed' => 0,
    ]);

    Queue::fake();

    $job = new ProcessActionQueue($queue->id);
    $job->handle(
        app(\App\Services\CraftingService::class),
        app(\App\Services\GatheringService::class),
        app(\App\Services\TrainingService::class),
        app(\App\Services\AgilityService::class),
    );

    Queue::assertNothingPushed();

    $queue->refresh();
    expect($queue->status)->toBe('cancelled');
    expect($queue->stop_reason)->toContain('infirmary');
});

test('process action queue job executes training and chains next job', function () {
    $village = Village::factory()->create();
    $user = User::factory()->create([
        'current_location_type' => 'village',
        'current_location_id' => $village->id,
        'energy' => 100,
    ]);

    $queue = ActionQueue::create([
        'user_id' => $user->id,
        'action_type' => 'train',
        'action_params' => ['exercise' => 'attack'],
        'status' => 'active',
        'total' => 5,
        'completed' => 0,
        'total_xp' => 0,
    ]);

    Queue::fake();

    $job = new ProcessActionQueue($queue->id);
    $job->handle(
        app(\App\Services\CraftingService::class),
        app(\App\Services\GatheringService::class),
        app(\App\Services\TrainingService::class),
        app(\App\Services\AgilityService::class),
    );

    $queue->refresh();
    expect($queue->completed)->toBe(1);
    expect($queue->total_xp)->toBeGreaterThan(0);
    expect($queue->status)->toBe('active');

    // Should dispatch next job
    Queue::assertPushedOn('action-queue', ProcessActionQueue::class);
});

test('process action queue job marks queue as completed when total is reached', function () {
    $village = Village::factory()->create();
    $user = User::factory()->create([
        'current_location_type' => 'village',
        'current_location_id' => $village->id,
        'energy' => 100,
    ]);

    $queue = ActionQueue::create([
        'user_id' => $user->id,
        'action_type' => 'train',
        'action_params' => ['exercise' => 'attack'],
        'status' => 'active',
        'total' => 1,
        'completed' => 0,
        'total_xp' => 0,
    ]);

    Queue::fake();

    $job = new ProcessActionQueue($queue->id);
    $job->handle(
        app(\App\Services\CraftingService::class),
        app(\App\Services\GatheringService::class),
        app(\App\Services\TrainingService::class),
        app(\App\Services\AgilityService::class),
    );

    $queue->refresh();
    expect($queue->completed)->toBe(1);
    expect($queue->status)->toBe('completed');

    // Should NOT dispatch next job since queue is completed
    Queue::assertNothingPushed();
});

test('process action queue job fails when energy runs out', function () {
    $village = Village::factory()->create();
    $user = User::factory()->create([
        'current_location_type' => 'village',
        'current_location_id' => $village->id,
        'energy' => 0,
    ]);

    $queue = ActionQueue::create([
        'user_id' => $user->id,
        'action_type' => 'train',
        'action_params' => ['exercise' => 'attack'],
        'status' => 'active',
        'total' => 5,
        'completed' => 0,
    ]);

    Queue::fake();

    $job = new ProcessActionQueue($queue->id);
    $job->handle(
        app(\App\Services\CraftingService::class),
        app(\App\Services\GatheringService::class),
        app(\App\Services\TrainingService::class),
        app(\App\Services\AgilityService::class),
    );

    $queue->refresh();
    expect($queue->status)->toBe('failed');
    expect($queue->stop_reason)->not->toBeNull();

    Queue::assertNothingPushed();
});

test('start endpoint validates action type', function () {
    $user = User::factory()->create(['energy' => 100]);

    $response = $this->actingAs($user)->postJson('/action-queue/start', [
        'action_type' => 'invalid',
        'action_params' => ['foo' => 'bar'],
        'total' => 5,
    ]);

    $response->assertStatus(422);
});

test('action queue model isInfinite returns true for total of 0', function () {
    $queue = new ActionQueue(['total' => 0]);
    expect($queue->isInfinite())->toBeTrue();

    $queue = new ActionQueue(['total' => 5]);
    expect($queue->isInfinite())->toBeFalse();
});

test('guests cannot access action queue endpoints', function () {
    $this->postJson('/action-queue/start', [
        'action_type' => 'train',
        'action_params' => ['exercise' => 'attack'],
        'total' => 5,
    ])->assertUnauthorized();

    $this->postJson('/action-queue/cancel')->assertUnauthorized();

    $this->postJson('/action-queue/dismiss', [
        'queue_id' => 1,
    ])->assertUnauthorized();
});
