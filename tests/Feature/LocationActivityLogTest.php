<?php

use App\Models\LocationActivityLog;
use App\Models\User;
use App\Models\Village;

beforeEach(function () {
    $this->village = Village::factory()->create();
});

test('can log player activity with user', function () {
    $user = User::factory()->create();

    $log = LocationActivityLog::log(
        $user->id,
        'village',
        $this->village->id,
        LocationActivityLog::TYPE_TRAINING,
        "{$user->username} trained Attack",
        'attack_training',
        ['xp_gained' => 15]
    );

    expect($log)->toBeInstanceOf(LocationActivityLog::class)
        ->and($log->user_id)->toBe($user->id)
        ->and($log->location_type)->toBe('village')
        ->and($log->location_id)->toBe($this->village->id)
        ->and($log->activity_type)->toBe('training')
        ->and($log->metadata)->toBe(['xp_gained' => 15]);
});

test('can log system event without user', function () {
    $log = LocationActivityLog::logSystemEvent(
        'village',
        $this->village->id,
        LocationActivityLog::TYPE_TAX_COLLECTION,
        'Collected 500g in taxes from 10 residents',
        null,
        ['amount' => 500, 'residents_taxed' => 10]
    );

    expect($log)->toBeInstanceOf(LocationActivityLog::class)
        ->and($log->user_id)->toBeNull()
        ->and($log->location_type)->toBe('village')
        ->and($log->location_id)->toBe($this->village->id)
        ->and($log->activity_type)->toBe('tax_collection')
        ->and($log->metadata)->toBe(['amount' => 500, 'residents_taxed' => 10]);
});

test('can log role change event', function () {
    $log = LocationActivityLog::logSystemEvent(
        'village',
        $this->village->id,
        LocationActivityLog::TYPE_ROLE_CHANGE,
        'Dan claimed the Elder role',
        'self_appointed',
        ['role' => 'Elder', 'username' => 'Dan']
    );

    expect($log)->toBeInstanceOf(LocationActivityLog::class)
        ->and($log->user_id)->toBeNull()
        ->and($log->activity_type)->toBe('role_change')
        ->and($log->activity_subtype)->toBe('self_appointed');
});

test('can log salary payment event', function () {
    $log = LocationActivityLog::logSystemEvent(
        'village',
        $this->village->id,
        LocationActivityLog::TYPE_SALARY_PAYMENT,
        'Paid 50g salary to Elder Dan',
        null,
        ['role' => 'Elder', 'username' => 'Dan', 'amount' => 50]
    );

    expect($log)->toBeInstanceOf(LocationActivityLog::class)
        ->and($log->activity_type)->toBe('salary_payment');
});

test('can log salary failed event', function () {
    $log = LocationActivityLog::logSystemEvent(
        'village',
        $this->village->id,
        LocationActivityLog::TYPE_SALARY_FAILED,
        'Failed to pay Elder salary to Dan (insufficient funds)',
        null,
        ['role' => 'Elder', 'username' => 'Dan', 'salary' => 50, 'treasury_balance' => 25]
    );

    expect($log)->toBeInstanceOf(LocationActivityLog::class)
        ->and($log->activity_type)->toBe('salary_failed');
});

test('atLocation scope works', function () {
    // Create activities at multiple locations
    LocationActivityLog::logSystemEvent('village', $this->village->id, LocationActivityLog::TYPE_TAX_COLLECTION, 'Test 1');
    LocationActivityLog::logSystemEvent('village', $this->village->id, LocationActivityLog::TYPE_TAX_COLLECTION, 'Test 2');
    LocationActivityLog::logSystemEvent('village', $this->village->id + 1, LocationActivityLog::TYPE_TAX_COLLECTION, 'Other village');

    $logs = LocationActivityLog::atLocation('village', $this->village->id)->get();

    expect($logs)->toHaveCount(2)
        ->and($logs->pluck('description')->toArray())->toContain('Test 1', 'Test 2');
});
