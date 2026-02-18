<?php

use App\Models\CombatSession;
use App\Models\Item;
use App\Models\Monster;
use App\Models\PlayerInventory;
use App\Models\PlayerSkill;
use App\Models\User;
use App\Services\CombatService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function createCombatUser(array $overrides = []): User
{
    return User::factory()->create(array_merge([
        'hp' => 50,
        'max_hp' => 50,
        'energy' => 100,
        'max_energy' => 300,
        'current_location_id' => 1,
    ], $overrides));
}

function createTestMonster(array $overrides = []): Monster
{
    return Monster::create(array_merge([
        'name' => 'Test Goblin',
        'description' => 'A test goblin',
        'type' => 'goblinoid',
        'hp' => 20,
        'max_hp' => 20,
        'attack_level' => 5,
        'strength_level' => 5,
        'defense_level' => 10,
        'stab_defense' => 9,
        'slash_defense' => 10,
        'crush_defense' => 10,
        'combat_level' => 5,
        'attack_style' => 'melee',
        'xp_reward' => 30,
        'gold_drop_min' => 5,
        'gold_drop_max' => 15,
        'is_boss' => false,
    ], $overrides));
}

function equipWeapon(User $user, string $subtype, array $itemOverrides = []): PlayerInventory
{
    $item = Item::create(array_merge([
        'name' => "Test {$subtype}",
        'type' => 'weapon',
        'subtype' => $subtype,
        'equipment_slot' => 'weapon',
        'rarity' => 'common',
        'stackable' => false,
        'max_stack' => 1,
        'atk_bonus' => 5,
        'str_bonus' => 5,
        'def_bonus' => 0,
        'base_value' => 100,
    ], $itemOverrides));

    return PlayerInventory::create([
        'player_id' => $user->id,
        'item_id' => $item->id,
        'slot_number' => 1,
        'quantity' => 1,
        'is_equipped' => true,
    ]);
}

function ensureSkills(User $user): void
{
    foreach (['attack', 'strength', 'defense', 'hitpoints'] as $skill) {
        PlayerSkill::firstOrCreate([
            'player_id' => $user->id,
            'skill_name' => $skill,
        ], [
            'level' => 10,
            'xp' => 1154,
        ]);
    }
}

// ── Style Resolution ─────────────────────────────────────────────────

test('getAttackStyleConfig returns correct styles for each weapon subtype', function () {
    $service = app(CombatService::class);

    // Dagger — stab sword
    $style = $service->getAttackStyleConfig('dagger', 0);
    expect($style['name'])->toBe('Stab')
        ->and($style['attack_type'])->toBe('stab')
        ->and($style['weapon_style'])->toBe('accurate');

    $style = $service->getAttackStyleConfig('dagger', 2);
    expect($style['name'])->toBe('Slash')
        ->and($style['attack_type'])->toBe('slash')
        ->and($style['weapon_style'])->toBe('aggressive');

    // Mace — spiked
    $style = $service->getAttackStyleConfig('mace', 0);
    expect($style['name'])->toBe('Pound')
        ->and($style['attack_type'])->toBe('crush');

    $style = $service->getAttackStyleConfig('mace', 2);
    expect($style['name'])->toBe('Spike')
        ->and($style['attack_type'])->toBe('stab')
        ->and($style['weapon_style'])->toBe('controlled')
        ->and($style['xp_skills'])->toBe(['attack', 'strength', 'defense']);

    // Warhammer — blunt (only 3 styles)
    $style = $service->getAttackStyleConfig('warhammer', 0);
    expect($style['name'])->toBe('Pound');

    $style = $service->getAttackStyleConfig('warhammer', 1);
    expect($style['name'])->toBe('Pummel');

    // Unarmed
    $style = $service->getAttackStyleConfig('unarmed', 0);
    expect($style['name'])->toBe('Punch')
        ->and($style['attack_type'])->toBe('crush');

    $style = $service->getAttackStyleConfig('unarmed', 1);
    expect($style['name'])->toBe('Kick');
});

test('getAttackStyleConfig clamps out-of-bounds index to last style', function () {
    $service = app(CombatService::class);

    // Warhammer has 3 styles (0-2), index 5 should clamp to 2
    $style = $service->getAttackStyleConfig('warhammer', 5);
    expect($style['name'])->toBe('Block')
        ->and($style['weapon_style'])->toBe('defensive');
});

test('unknown weapon subtype falls back to unarmed styles', function () {
    $service = app(CombatService::class);

    $style = $service->getAttackStyleConfig('magic_wand', 0);
    expect($style['name'])->toBe('Punch')
        ->and($style['attack_type'])->toBe('crush');
});

// ── Weapon Subtype Detection ─────────────────────────────────────────

test('getPlayerWeaponSubtype returns weapon subtype when equipped', function () {
    $user = createCombatUser();
    equipWeapon($user, 'dagger');

    $service = app(CombatService::class);
    expect($service->getPlayerWeaponSubtype($user))->toBe('dagger');
});

test('getPlayerWeaponSubtype returns unarmed when no weapon equipped', function () {
    $user = createCombatUser();

    $service = app(CombatService::class);
    expect($service->getPlayerWeaponSubtype($user))->toBe('unarmed');
});

// ── Weapon Speed ─────────────────────────────────────────────────────

test('getWeaponSpeed returns correct speed per subtype', function () {
    $service = app(CombatService::class);

    expect($service->getWeaponSpeed('dagger'))->toBe(4)
        ->and($service->getWeaponSpeed('scimitar'))->toBe(4)
        ->and($service->getWeaponSpeed('mace'))->toBe(5)
        ->and($service->getWeaponSpeed('battleaxe'))->toBe(6)
        ->and($service->getWeaponSpeed('2hsword'))->toBe(7)
        ->and($service->getWeaponSpeed('unarmed'))->toBe(4);
});

test('fast weapons produce two player attack logs per round', function () {
    $user = createCombatUser();
    ensureSkills($user);
    equipWeapon($user, 'dagger'); // speed 4 = 2 hits/round
    $monster = createTestMonster(['hp' => 200, 'max_hp' => 200, 'defense_level' => 1, 'stab_defense' => 1, 'slash_defense' => 1, 'crush_defense' => 1]);

    $service = app(CombatService::class);
    $result = $service->startCombat($user, $monster->id, 0);
    expect($result['success'])->toBeTrue();

    $result = $service->attack($user->fresh());
    expect($result['success'])->toBeTrue();

    // With a fast weapon, there should be 2 player attack logs + 1 monster attack log = 3 total
    $playerLogs = collect($result['data']['logs'])->filter(fn ($l) => $l->actor === 'player');
    expect($playerLogs)->toHaveCount(2);
});

test('normal speed weapons produce one player attack log per round', function () {
    $user = createCombatUser();
    ensureSkills($user);
    equipWeapon($user, 'mace'); // speed 5 = 1 hit/round
    $monster = createTestMonster(['hp' => 200, 'max_hp' => 200, 'defense_level' => 1, 'stab_defense' => 1, 'slash_defense' => 1, 'crush_defense' => 1]);

    $service = app(CombatService::class);
    $result = $service->startCombat($user, $monster->id, 0);
    expect($result['success'])->toBeTrue();

    $result = $service->attack($user->fresh());
    expect($result['success'])->toBeTrue();

    // Normal weapon: 1 player attack log + 1 monster attack log = 2 total
    $playerLogs = collect($result['data']['logs'])->filter(fn ($l) => $l->actor === 'player');
    expect($playerLogs)->toHaveCount(1);
});

// ── Typed Defense ────────────────────────────────────────────────────

test('stab attack uses monster stab_defense', function () {
    $user = createCombatUser();
    ensureSkills($user);
    equipWeapon($user, 'dagger'); // default style 0 = Stab (stab type)

    // Create monster with very low stab defense but high slash/crush
    $monster = createTestMonster([
        'hp' => 200,
        'max_hp' => 200,
        'defense_level' => 50,
        'stab_defense' => 1,
        'slash_defense' => 50,
        'crush_defense' => 50,
    ]);

    $service = app(CombatService::class);
    $result = $service->startCombat($user, $monster->id, 0); // Stab style
    expect($result['success'])->toBeTrue();

    // Run multiple rounds and verify we can hit the monster (stab_defense is only 1)
    $totalDamage = 0;
    for ($i = 0; $i < 20; $i++) {
        $result = $service->attack($user->fresh());
        if (! $result['success']) {
            break;
        }
        foreach ($result['data']['logs'] as $log) {
            if ($log->actor === 'player' && $log->damage > 0) {
                $totalDamage += $log->damage;
            }
        }
    }

    // With stab_defense of 1, we should land hits
    expect($totalDamage)->toBeGreaterThan(0);
});

// ── Stance Bonuses ───────────────────────────────────────────────────

test('stance bonuses match expected values', function () {
    expect(CombatService::STANCE_BONUSES['accurate'])->toBe(['attack' => 3, 'strength' => 0, 'defense' => 0])
        ->and(CombatService::STANCE_BONUSES['aggressive'])->toBe(['attack' => 0, 'strength' => 3, 'defense' => 0])
        ->and(CombatService::STANCE_BONUSES['defensive'])->toBe(['attack' => 0, 'strength' => 0, 'defense' => 3])
        ->and(CombatService::STANCE_BONUSES['controlled'])->toBe(['attack' => 1, 'strength' => 1, 'defense' => 1]);
});

// ── XP Distribution ──────────────────────────────────────────────────

test('accurate style awards XP to attack skill only', function () {
    $user = createCombatUser();
    ensureSkills($user);
    equipWeapon($user, 'dagger'); // style 0 = Stab (accurate, xp: attack)

    $attackXpBefore = PlayerSkill::where('player_id', $user->id)->where('skill_name', 'attack')->value('xp');
    $strengthXpBefore = PlayerSkill::where('player_id', $user->id)->where('skill_name', 'strength')->value('xp');

    $monster = createTestMonster(['hp' => 200, 'max_hp' => 200, 'defense_level' => 1, 'stab_defense' => 1, 'slash_defense' => 1, 'crush_defense' => 1]);

    $service = app(CombatService::class);
    $service->startCombat($user, $monster->id, 0); // Stab = accurate

    // Attack several rounds to accumulate damage/XP
    for ($i = 0; $i < 5; $i++) {
        $service->attack($user->fresh());
    }

    $attackXpAfter = PlayerSkill::where('player_id', $user->id)->where('skill_name', 'attack')->value('xp');
    $strengthXpAfter = PlayerSkill::where('player_id', $user->id)->where('skill_name', 'strength')->value('xp');

    // Attack XP should have increased
    expect($attackXpAfter)->toBeGreaterThan($attackXpBefore);
    // Strength XP should NOT have increased
    expect($strengthXpAfter)->toBe($strengthXpBefore);
});

test('aggressive style awards XP to strength skill only', function () {
    $user = createCombatUser();
    ensureSkills($user);
    equipWeapon($user, 'dagger'); // style 1 = Lunge (aggressive, xp: strength)

    $attackXpBefore = PlayerSkill::where('player_id', $user->id)->where('skill_name', 'attack')->value('xp');
    $strengthXpBefore = PlayerSkill::where('player_id', $user->id)->where('skill_name', 'strength')->value('xp');

    $monster = createTestMonster(['hp' => 200, 'max_hp' => 200, 'defense_level' => 1, 'stab_defense' => 1, 'slash_defense' => 1, 'crush_defense' => 1]);

    $service = app(CombatService::class);
    $service->startCombat($user, $monster->id, 1); // Lunge = aggressive

    for ($i = 0; $i < 5; $i++) {
        $service->attack($user->fresh());
    }

    $attackXpAfter = PlayerSkill::where('player_id', $user->id)->where('skill_name', 'attack')->value('xp');
    $strengthXpAfter = PlayerSkill::where('player_id', $user->id)->where('skill_name', 'strength')->value('xp');

    // Strength XP should increase
    expect($strengthXpAfter)->toBeGreaterThan($strengthXpBefore);
    // Attack XP should NOT
    expect($attackXpAfter)->toBe($attackXpBefore);
});

test('controlled style splits XP across attack, strength, and defense', function () {
    $user = createCombatUser();
    ensureSkills($user);
    equipWeapon($user, 'mace'); // style 2 = Spike (controlled, xp: attack+strength+defense)

    $xpBefore = [];
    foreach (['attack', 'strength', 'defense'] as $skill) {
        $xpBefore[$skill] = PlayerSkill::where('player_id', $user->id)->where('skill_name', $skill)->value('xp');
    }

    $monster = createTestMonster(['hp' => 200, 'max_hp' => 200, 'defense_level' => 1, 'stab_defense' => 1, 'slash_defense' => 1, 'crush_defense' => 1]);

    $service = app(CombatService::class);
    $service->startCombat($user, $monster->id, 2); // Spike = controlled

    for ($i = 0; $i < 10; $i++) {
        $service->attack($user->fresh());
    }

    $xpAfter = [];
    foreach (['attack', 'strength', 'defense'] as $skill) {
        $xpAfter[$skill] = PlayerSkill::where('player_id', $user->id)->where('skill_name', $skill)->value('xp');
    }

    // All three should have gained XP
    expect($xpAfter['attack'])->toBeGreaterThan($xpBefore['attack'])
        ->and($xpAfter['strength'])->toBeGreaterThan($xpBefore['strength'])
        ->and($xpAfter['defense'])->toBeGreaterThan($xpBefore['defense']);
});

test('combat always awards hitpoints XP regardless of style', function () {
    $user = createCombatUser();
    ensureSkills($user);

    $hpXpBefore = PlayerSkill::where('player_id', $user->id)->where('skill_name', 'hitpoints')->value('xp');

    $monster = createTestMonster(['hp' => 200, 'max_hp' => 200, 'defense_level' => 1, 'stab_defense' => 1, 'slash_defense' => 1, 'crush_defense' => 1]);

    $service = app(CombatService::class);
    $service->startCombat($user, $monster->id, 0);

    for ($i = 0; $i < 5; $i++) {
        $service->attack($user->fresh());
    }

    $hpXpAfter = PlayerSkill::where('player_id', $user->id)->where('skill_name', 'hitpoints')->value('xp');
    expect($hpXpAfter)->toBeGreaterThan($hpXpBefore);
});

// ── Session Storage ──────────────────────────────────────────────────

test('startCombat stores attack_style_index on session', function () {
    $user = createCombatUser();
    ensureSkills($user);
    equipWeapon($user, 'mace');
    $monster = createTestMonster();

    $service = app(CombatService::class);
    $result = $service->startCombat($user, $monster->id, 2); // Spike

    expect($result['success'])->toBeTrue();

    $session = CombatSession::where('user_id', $user->id)->first();
    expect($session->attack_style_index)->toBe(2)
        ->and($session->training_style)->toBe('attack'); // Spike: controlled, first xp_skill is 'attack'
});

test('startCombat defaults to index 0 when no style specified', function () {
    $user = createCombatUser();
    ensureSkills($user);
    $monster = createTestMonster();

    $service = app(CombatService::class);
    $result = $service->startCombat($user, $monster->id);

    expect($result['success'])->toBeTrue();

    $session = CombatSession::where('user_id', $user->id)->first();
    expect($session->attack_style_index)->toBe(0);
});

// ── getCombatInfo ────────────────────────────────────────────────────

test('getCombatInfo includes weapon subtype and attack styles', function () {
    $user = createCombatUser();
    equipWeapon($user, 'scimitar');

    $service = app(CombatService::class);
    $info = $service->getCombatInfo($user);

    expect($info['weapon_subtype'])->toBe('scimitar')
        ->and($info['weapon_speed'])->toBe(4)
        ->and($info['available_attack_styles'])->toHaveCount(4)
        ->and($info['available_attack_styles'][0]['name'])->toBe('Chop');
});

test('getCombatInfo returns unarmed styles when no weapon', function () {
    $user = createCombatUser();

    $service = app(CombatService::class);
    $info = $service->getCombatInfo($user);

    expect($info['weapon_subtype'])->toBe('unarmed')
        ->and($info['weapon_speed'])->toBe(4)
        ->and($info['available_attack_styles'])->toHaveCount(3)
        ->and($info['available_attack_styles'][0]['name'])->toBe('Punch');
});

// ── Speed Constants ──────────────────────────────────────────────────

test('speed constants are consistent', function () {
    // Every weapon subtype in WEAPON_ATTACK_STYLES should have a speed
    foreach (array_keys(CombatService::WEAPON_ATTACK_STYLES) as $subtype) {
        expect(CombatService::WEAPON_SPEED)->toHaveKey($subtype);
    }

    // Every speed value should have hits and damage mult entries
    foreach (CombatService::WEAPON_SPEED as $subtype => $speed) {
        expect(CombatService::SPEED_HITS)->toHaveKey($speed);
        expect(CombatService::SPEED_DAMAGE_MULT)->toHaveKey($speed);
    }
});

// ── Controller Integration ───────────────────────────────────────────

test('combat start endpoint accepts attack_style_index', function () {
    $user = createCombatUser();
    ensureSkills($user);
    $monster = createTestMonster();

    $response = $this->actingAs($user)->postJson('/combat/start', [
        'monster_id' => $monster->id,
        'attack_style_index' => 1,
    ]);

    $response->assertSuccessful();

    $session = CombatSession::where('user_id', $user->id)->first();
    expect($session->attack_style_index)->toBe(1);
});

test('combat start endpoint defaults attack_style_index to 0', function () {
    $user = createCombatUser();
    ensureSkills($user);
    $monster = createTestMonster();

    $response = $this->actingAs($user)->postJson('/combat/start', [
        'monster_id' => $monster->id,
    ]);

    $response->assertSuccessful();

    $session = CombatSession::where('user_id', $user->id)->first();
    expect($session->attack_style_index)->toBe(0);
});
