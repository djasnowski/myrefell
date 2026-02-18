<?php

namespace App\Services;

use App\Models\CombatLog;
use App\Models\CombatSession;
use App\Models\Item;
use App\Models\Monster;
use App\Models\PlayerInventory;
use App\Models\PlayerSkill;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CombatService
{
    public const ENERGY_COST = 1;

    public const FLEE_SUCCESS_CHANCE = 50;

    public const RESPAWN_ENERGY_PERCENT = 25;

    public const XP_PER_DAMAGE = 4;

    /**
     * Attack styles per weapon subtype.
     * Each style: [name, attack_type, weapon_style, xp_skills]
     * weapon_style: accurate/aggressive/controlled/defensive
     * xp_skills: array of skills that receive XP
     */
    public const WEAPON_ATTACK_STYLES = [
        'dagger' => [
            ['name' => 'Stab', 'attack_type' => 'stab', 'weapon_style' => 'accurate', 'xp_skills' => ['attack']],
            ['name' => 'Lunge', 'attack_type' => 'stab', 'weapon_style' => 'aggressive', 'xp_skills' => ['strength']],
            ['name' => 'Slash', 'attack_type' => 'slash', 'weapon_style' => 'aggressive', 'xp_skills' => ['strength']],
            ['name' => 'Block', 'attack_type' => 'stab', 'weapon_style' => 'defensive', 'xp_skills' => ['defense']],
        ],
        'sword' => [
            ['name' => 'Stab', 'attack_type' => 'stab', 'weapon_style' => 'accurate', 'xp_skills' => ['attack']],
            ['name' => 'Lunge', 'attack_type' => 'stab', 'weapon_style' => 'aggressive', 'xp_skills' => ['strength']],
            ['name' => 'Slash', 'attack_type' => 'slash', 'weapon_style' => 'aggressive', 'xp_skills' => ['strength']],
            ['name' => 'Block', 'attack_type' => 'stab', 'weapon_style' => 'defensive', 'xp_skills' => ['defense']],
        ],
        'scimitar' => [
            ['name' => 'Chop', 'attack_type' => 'slash', 'weapon_style' => 'accurate', 'xp_skills' => ['attack']],
            ['name' => 'Slash', 'attack_type' => 'slash', 'weapon_style' => 'aggressive', 'xp_skills' => ['strength']],
            ['name' => 'Lunge', 'attack_type' => 'stab', 'weapon_style' => 'controlled', 'xp_skills' => ['attack', 'strength', 'defense']],
            ['name' => 'Block', 'attack_type' => 'slash', 'weapon_style' => 'defensive', 'xp_skills' => ['defense']],
        ],
        'longsword' => [
            ['name' => 'Chop', 'attack_type' => 'slash', 'weapon_style' => 'accurate', 'xp_skills' => ['attack']],
            ['name' => 'Slash', 'attack_type' => 'slash', 'weapon_style' => 'aggressive', 'xp_skills' => ['strength']],
            ['name' => 'Lunge', 'attack_type' => 'stab', 'weapon_style' => 'controlled', 'xp_skills' => ['attack', 'strength', 'defense']],
            ['name' => 'Block', 'attack_type' => 'slash', 'weapon_style' => 'defensive', 'xp_skills' => ['defense']],
        ],
        'axe' => [
            ['name' => 'Chop', 'attack_type' => 'slash', 'weapon_style' => 'accurate', 'xp_skills' => ['attack']],
            ['name' => 'Hack', 'attack_type' => 'slash', 'weapon_style' => 'aggressive', 'xp_skills' => ['strength']],
            ['name' => 'Smash', 'attack_type' => 'crush', 'weapon_style' => 'aggressive', 'xp_skills' => ['strength']],
            ['name' => 'Block', 'attack_type' => 'slash', 'weapon_style' => 'defensive', 'xp_skills' => ['defense']],
        ],
        'battleaxe' => [
            ['name' => 'Chop', 'attack_type' => 'slash', 'weapon_style' => 'accurate', 'xp_skills' => ['attack']],
            ['name' => 'Hack', 'attack_type' => 'slash', 'weapon_style' => 'aggressive', 'xp_skills' => ['strength']],
            ['name' => 'Smash', 'attack_type' => 'crush', 'weapon_style' => 'aggressive', 'xp_skills' => ['strength']],
            ['name' => 'Block', 'attack_type' => 'slash', 'weapon_style' => 'defensive', 'xp_skills' => ['defense']],
        ],
        '2hsword' => [
            ['name' => 'Chop', 'attack_type' => 'slash', 'weapon_style' => 'accurate', 'xp_skills' => ['attack']],
            ['name' => 'Slash', 'attack_type' => 'slash', 'weapon_style' => 'aggressive', 'xp_skills' => ['strength']],
            ['name' => 'Smash', 'attack_type' => 'crush', 'weapon_style' => 'aggressive', 'xp_skills' => ['strength']],
            ['name' => 'Block', 'attack_type' => 'slash', 'weapon_style' => 'defensive', 'xp_skills' => ['defense']],
        ],
        'mace' => [
            ['name' => 'Pound', 'attack_type' => 'crush', 'weapon_style' => 'accurate', 'xp_skills' => ['attack']],
            ['name' => 'Pummel', 'attack_type' => 'crush', 'weapon_style' => 'aggressive', 'xp_skills' => ['strength']],
            ['name' => 'Spike', 'attack_type' => 'stab', 'weapon_style' => 'controlled', 'xp_skills' => ['attack', 'strength', 'defense']],
            ['name' => 'Block', 'attack_type' => 'crush', 'weapon_style' => 'defensive', 'xp_skills' => ['defense']],
        ],
        'warhammer' => [
            ['name' => 'Pound', 'attack_type' => 'crush', 'weapon_style' => 'accurate', 'xp_skills' => ['attack']],
            ['name' => 'Pummel', 'attack_type' => 'crush', 'weapon_style' => 'aggressive', 'xp_skills' => ['strength']],
            ['name' => 'Block', 'attack_type' => 'crush', 'weapon_style' => 'defensive', 'xp_skills' => ['defense']],
        ],
        'spear' => [
            ['name' => 'Lunge', 'attack_type' => 'stab', 'weapon_style' => 'controlled', 'xp_skills' => ['attack', 'strength', 'defense']],
            ['name' => 'Swipe', 'attack_type' => 'slash', 'weapon_style' => 'controlled', 'xp_skills' => ['attack', 'strength', 'defense']],
            ['name' => 'Pound', 'attack_type' => 'crush', 'weapon_style' => 'controlled', 'xp_skills' => ['attack', 'strength', 'defense']],
            ['name' => 'Block', 'attack_type' => 'stab', 'weapon_style' => 'defensive', 'xp_skills' => ['defense']],
        ],
        'claws' => [
            ['name' => 'Chop', 'attack_type' => 'slash', 'weapon_style' => 'accurate', 'xp_skills' => ['attack']],
            ['name' => 'Slash', 'attack_type' => 'slash', 'weapon_style' => 'aggressive', 'xp_skills' => ['strength']],
            ['name' => 'Lunge', 'attack_type' => 'stab', 'weapon_style' => 'controlled', 'xp_skills' => ['attack', 'strength', 'defense']],
            ['name' => 'Block', 'attack_type' => 'slash', 'weapon_style' => 'defensive', 'xp_skills' => ['defense']],
        ],
        'throwing' => [
            ['name' => 'Accurate', 'attack_type' => 'stab', 'weapon_style' => 'accurate', 'xp_skills' => ['attack']],
            ['name' => 'Rapid', 'attack_type' => 'stab', 'weapon_style' => 'aggressive', 'xp_skills' => ['strength']],
            ['name' => 'Longrange', 'attack_type' => 'stab', 'weapon_style' => 'defensive', 'xp_skills' => ['defense']],
        ],
        'unarmed' => [
            ['name' => 'Punch', 'attack_type' => 'crush', 'weapon_style' => 'accurate', 'xp_skills' => ['attack']],
            ['name' => 'Kick', 'attack_type' => 'crush', 'weapon_style' => 'aggressive', 'xp_skills' => ['strength']],
            ['name' => 'Block', 'attack_type' => 'crush', 'weapon_style' => 'defensive', 'xp_skills' => ['defense']],
        ],
    ];

    /**
     * Stance bonuses (invisible level boosts).
     */
    public const STANCE_BONUSES = [
        'accurate' => ['attack' => 3, 'strength' => 0, 'defense' => 0],
        'aggressive' => ['attack' => 0, 'strength' => 3, 'defense' => 0],
        'defensive' => ['attack' => 0, 'strength' => 0, 'defense' => 3],
        'controlled' => ['attack' => 1, 'strength' => 1, 'defense' => 1],
    ];

    /**
     * Weapon speed per subtype (lower = faster, OSRS-style).
     */
    public const WEAPON_SPEED = [
        'dagger' => 4, 'claws' => 4, 'scimitar' => 4, 'sword' => 4,
        'mace' => 5, 'axe' => 5, 'longsword' => 5, 'spear' => 5, 'throwing' => 5,
        'battleaxe' => 6, 'warhammer' => 6,
        '2hsword' => 7,
        'unarmed' => 4,
    ];

    /**
     * Number of hits per combat round based on weapon speed.
     */
    public const SPEED_HITS = [4 => 2, 5 => 1, 6 => 1, 7 => 1];

    /**
     * Damage multiplier for slow weapons to compensate for fewer attacks.
     */
    public const SPEED_DAMAGE_MULT = [4 => 1.0, 5 => 1.0, 6 => 1.15, 7 => 1.3];

    public function __construct(
        protected EnergyService $energyService,
        protected LootService $lootService,
        protected InventoryService $inventoryService,
        protected BlessingEffectService $blessingEffectService,
        protected BeliefEffectService $beliefEffectService,
        protected InfirmaryService $infirmaryService,
        protected PotionBuffService $potionBuffService
    ) {}

    /**
     * Get available monsters at the player's current location.
     *
     * @return array<Monster>
     */
    public function getAvailableMonsters(User $player): array
    {
        // Get player's current location biome
        $biome = $this->getLocationBiome($player);

        return Monster::query()
            ->where('min_player_combat_level', '<=', $player->combat_level)
            ->where(function ($query) use ($biome) {
                $query->where('biome', $biome)
                    ->orWhereNull('biome');
            })
            ->orderBy('combat_level')
            ->get()
            ->toArray();
    }

    /**
     * Get the biome for a player's current location.
     */
    protected function getLocationBiome(User $player): ?string
    {
        return match ($player->current_location_type) {
            'village' => \App\Models\Village::find($player->current_location_id)?->biome,
            'barony' => \App\Models\Barony::find($player->current_location_id)?->biome,
            'town' => \App\Models\Town::find($player->current_location_id)?->biome,
            'kingdom' => \App\Models\Kingdom::find($player->current_location_id)?->biome,
            default => null,
        };
    }

    /**
     * Check if player has an active combat session.
     */
    public function getActiveCombat(User $player): ?CombatSession
    {
        return CombatSession::where('user_id', $player->id)
            ->where('status', CombatSession::STATUS_ACTIVE)
            ->with(['monster', 'logs'])
            ->first();
    }

    /**
     * Start a new combat session.
     */
    public function startCombat(User $player, int $monsterId, int $attackStyleIndex = 0): array
    {
        // Check if player is traveling
        if ($player->isTraveling()) {
            return ['success' => false, 'message' => 'You cannot fight while traveling.'];
        }

        // Check if player is in the infirmary
        if ($player->isInInfirmary()) {
            return ['success' => false, 'message' => 'You cannot fight while recovering in the infirmary.'];
        }

        // Check if player is alive
        if (! $player->isAlive()) {
            return ['success' => false, 'message' => 'You are dead and cannot fight.'];
        }

        // Check for existing combat
        $existingCombat = $this->getActiveCombat($player);
        if ($existingCombat) {
            return ['success' => false, 'message' => 'You are already in combat.'];
        }

        // Check energy
        if (! $this->energyService->hasEnergy($player, self::ENERGY_COST)) {
            return ['success' => false, 'message' => 'You need '.self::ENERGY_COST.' energy to start combat.'];
        }

        // Find the monster
        $monster = Monster::find($monsterId);
        if (! $monster) {
            return ['success' => false, 'message' => 'Monster not found.'];
        }

        // Check combat level requirement
        if (! $monster->canBeAttackedBy($player)) {
            return ['success' => false, 'message' => "You need combat level {$monster->min_player_combat_level} to fight this monster."];
        }

        // Resolve attack style config from weapon + index
        $weaponSubtype = $this->getPlayerWeaponSubtype($player);
        $styles = self::WEAPON_ATTACK_STYLES[$weaponSubtype] ?? self::WEAPON_ATTACK_STYLES['unarmed'];
        $attackStyleIndex = max(0, min($attackStyleIndex, count($styles) - 1));
        $styleConfig = $styles[$attackStyleIndex];

        // Derive training_style from XP skills (first skill for non-controlled, or 'attack' for controlled)
        $trainingStyle = $styleConfig['xp_skills'][0] ?? 'attack';

        // Consume energy
        $this->energyService->consumeEnergy($player, self::ENERGY_COST);

        // Create combat session
        $session = CombatSession::create([
            'user_id' => $player->id,
            'monster_id' => $monster->id,
            'player_hp' => $player->hp,
            'monster_hp' => $monster->max_hp,
            'round' => 1,
            'training_style' => $trainingStyle,
            'attack_style_index' => $attackStyleIndex,
            'status' => CombatSession::STATUS_ACTIVE,
            'location_type' => $player->current_location_type,
            'location_id' => $player->current_location_id,
        ]);

        $session->load('monster');

        return [
            'success' => true,
            'message' => "Combat started against {$monster->name}!",
            'data' => [
                'session' => $session,
            ],
        ];
    }

    /**
     * Perform an attack action.
     */
    public function attack(User $player): array
    {
        $session = $this->getActiveCombat($player);
        if (! $session) {
            return ['success' => false, 'message' => 'You are not in combat.'];
        }

        return DB::transaction(function () use ($player, $session) {
            $logs = [];
            $monster = $session->monster;

            // Determine weapon speed for multi-hit
            $weaponSubtype = $this->getPlayerWeaponSubtype($player);
            $speed = $this->getWeaponSpeed($weaponSubtype);
            $hitsPerRound = self::SPEED_HITS[$speed] ?? 1;
            $damageMult = self::SPEED_DAMAGE_MULT[$speed] ?? 1.0;

            // Player attacks (possibly multiple times for fast weapons)
            for ($hit = 0; $hit < $hitsPerRound; $hit++) {
                $playerAttack = $this->calculatePlayerAttack($player, $monster, $session->round, $session);

                // Apply slow weapon damage multiplier
                if ($damageMult !== 1.0 && $playerAttack['damage'] > 0) {
                    $playerAttack['damage'] = (int) round($playerAttack['damage'] * $damageMult);
                }

                $session->monster_hp = max(0, $session->monster_hp - $playerAttack['damage']);

                // Award XP immediately based on damage dealt
                $xpThisHit = $playerAttack['damage'] * self::XP_PER_DAMAGE;
                if ($xpThisHit > 0) {
                    $session->xp_gained += $xpThisHit;
                    $this->awardCombatXp($player, $session, $xpThisHit);
                }

                // Apply Soul Siphon HP leech (cult belief)
                $hpLeech = $this->beliefEffectService->getEffect($player, 'combat_hp_leech');
                $hpHealed = 0;
                if ($hpLeech > 0 && $playerAttack['damage'] > 0) {
                    $hpHealed = (int) ceil($playerAttack['damage'] * $hpLeech / 100);
                    $session->player_hp = min($player->max_hp, $session->player_hp + $hpHealed);
                }

                $logs[] = CombatLog::create([
                    'combat_session_id' => $session->id,
                    'round' => $session->round,
                    'actor' => CombatLog::ACTOR_PLAYER,
                    'action' => CombatLog::ACTION_ATTACK,
                    'hit' => $playerAttack['hit'],
                    'damage' => $playerAttack['damage'],
                    'player_hp_after' => $session->player_hp,
                    'monster_hp_after' => $session->monster_hp,
                    'xp_gained' => $xpThisHit,
                    'hp_restored' => $hpHealed,
                ]);

                // Check if monster is dead after each hit
                if ($session->isMonsterDead()) {
                    return $this->handleVictory($player, $session, $logs);
                }
            }

            // Monster attacks back (once per round regardless of player speed)
            $monsterAttack = $this->calculateMonsterAttack($player, $monster, $session);
            $session->player_hp = max(0, $session->player_hp - $monsterAttack['damage']);

            // Sync player HP
            $player->hp = $session->player_hp;
            $player->save();

            $logs[] = CombatLog::create([
                'combat_session_id' => $session->id,
                'round' => $session->round,
                'actor' => CombatLog::ACTOR_MONSTER,
                'action' => CombatLog::ACTION_ATTACK,
                'hit' => $monsterAttack['hit'],
                'damage' => $monsterAttack['damage'],
                'player_hp_after' => $session->player_hp,
                'monster_hp_after' => $session->monster_hp,
            ]);

            // Check if player is dead
            if ($session->isPlayerDead()) {
                return $this->handleDefeat($player, $session, $logs);
            }

            // Continue combat
            $session->round++;
            $session->save();

            return [
                'success' => true,
                'message' => 'Round '.($session->round - 1).' complete.',
                'data' => [
                    'session' => $session->fresh(['monster', 'logs']),
                    'logs' => $logs,
                    'status' => 'active',
                ],
            ];
        });
    }

    /**
     * Eat food during combat.
     */
    public function eat(User $player, int $inventorySlotId): array
    {
        $session = $this->getActiveCombat($player);
        if (! $session) {
            return ['success' => false, 'message' => 'You are not in combat.'];
        }

        // Find the inventory slot
        $slot = PlayerInventory::where('id', $inventorySlotId)
            ->where('player_id', $player->id)
            ->with('item')
            ->first();

        if (! $slot) {
            return ['success' => false, 'message' => 'Item not found in your inventory.'];
        }

        $item = $slot->item;

        // Check if it's consumable food
        if ($item->type !== 'consumable' || $item->hp_bonus <= 0) {
            return ['success' => false, 'message' => 'This item cannot be eaten.'];
        }

        return DB::transaction(function () use ($player, $session, $item) {
            $logs = [];
            $monster = $session->monster;

            // Calculate HP restored
            $hpRestored = min($item->hp_bonus, $player->max_hp - $session->player_hp);
            $session->player_hp = min($player->max_hp, $session->player_hp + $item->hp_bonus);

            // Remove item from inventory
            $this->inventoryService->removeItem($player, $item, 1);

            $logs[] = CombatLog::create([
                'combat_session_id' => $session->id,
                'round' => $session->round,
                'actor' => CombatLog::ACTOR_PLAYER,
                'action' => CombatLog::ACTION_EAT,
                'hit' => true,
                'damage' => 0,
                'player_hp_after' => $session->player_hp,
                'monster_hp_after' => $session->monster_hp,
                'item_id' => $item->id,
                'hp_restored' => $hpRestored,
            ]);

            // Sync player HP
            $player->hp = $session->player_hp;
            $player->save();

            // Monster attacks back
            $monsterAttack = $this->calculateMonsterAttack($player, $monster, $session);
            $session->player_hp = max(0, $session->player_hp - $monsterAttack['damage']);

            // Sync player HP
            $player->hp = $session->player_hp;
            $player->save();

            $logs[] = CombatLog::create([
                'combat_session_id' => $session->id,
                'round' => $session->round,
                'actor' => CombatLog::ACTOR_MONSTER,
                'action' => CombatLog::ACTION_ATTACK,
                'hit' => $monsterAttack['hit'],
                'damage' => $monsterAttack['damage'],
                'player_hp_after' => $session->player_hp,
                'monster_hp_after' => $session->monster_hp,
            ]);

            // Check if player is dead
            if ($session->isPlayerDead()) {
                return $this->handleDefeat($player, $session, $logs);
            }

            $session->round++;
            $session->save();

            return [
                'success' => true,
                'message' => "You ate {$item->name} and restored {$hpRestored} HP.",
                'data' => [
                    'session' => $session->fresh(['monster', 'logs']),
                    'logs' => $logs,
                    'status' => 'active',
                ],
            ];
        });
    }

    /**
     * Attempt to flee from combat.
     */
    public function flee(User $player): array
    {
        $session = $this->getActiveCombat($player);
        if (! $session) {
            return ['success' => false, 'message' => 'You are not in combat.'];
        }

        return DB::transaction(function () use ($player, $session) {
            $logs = [];
            $monster = $session->monster;

            // Roll for flee success
            $fleeRoll = rand(1, 100);
            $fleeSuccess = $fleeRoll <= self::FLEE_SUCCESS_CHANCE;

            if ($fleeSuccess) {
                $session->status = CombatSession::STATUS_FLED;
                $session->save();

                $logs[] = CombatLog::create([
                    'combat_session_id' => $session->id,
                    'round' => $session->round,
                    'actor' => CombatLog::ACTOR_PLAYER,
                    'action' => CombatLog::ACTION_FLEE,
                    'hit' => true,
                    'damage' => 0,
                    'player_hp_after' => $session->player_hp,
                    'monster_hp_after' => $session->monster_hp,
                ]);

                return [
                    'success' => true,
                    'message' => 'You successfully fled from combat!',
                    'data' => [
                        'session' => $session,
                        'logs' => $logs,
                        'status' => 'fled',
                    ],
                ];
            }

            // Flee failed - monster gets a free attack
            $logs[] = CombatLog::create([
                'combat_session_id' => $session->id,
                'round' => $session->round,
                'actor' => CombatLog::ACTOR_PLAYER,
                'action' => CombatLog::ACTION_FLEE,
                'hit' => false,
                'damage' => 0,
                'player_hp_after' => $session->player_hp,
                'monster_hp_after' => $session->monster_hp,
            ]);

            $monsterAttack = $this->calculateMonsterAttack($player, $monster, $session);
            $session->player_hp = max(0, $session->player_hp - $monsterAttack['damage']);

            $player->hp = $session->player_hp;
            $player->save();

            $logs[] = CombatLog::create([
                'combat_session_id' => $session->id,
                'round' => $session->round,
                'actor' => CombatLog::ACTOR_MONSTER,
                'action' => CombatLog::ACTION_ATTACK,
                'hit' => $monsterAttack['hit'],
                'damage' => $monsterAttack['damage'],
                'player_hp_after' => $session->player_hp,
                'monster_hp_after' => $session->monster_hp,
            ]);

            if ($session->isPlayerDead()) {
                return $this->handleDefeat($player, $session, $logs);
            }

            $session->round++;
            $session->save();

            return [
                'success' => false,
                'message' => 'You failed to flee! The monster attacks!',
                'data' => [
                    'session' => $session->fresh(['monster', 'logs']),
                    'logs' => $logs,
                    'status' => 'active',
                ],
            ];
        });
    }

    /**
     * Get the equipped weapon's subtype, or 'unarmed' if none.
     */
    public function getPlayerWeaponSubtype(User $player): string
    {
        $weapon = $player->inventory()
            ->where('is_equipped', true)
            ->whereHas('item', fn ($q) => $q->where('equipment_slot', 'weapon'))
            ->with('item')
            ->first();

        if (! $weapon || ! $weapon->item->subtype) {
            return 'unarmed';
        }

        return $weapon->item->subtype;
    }

    /**
     * Get the attack style config for a weapon subtype and style index.
     */
    public function getAttackStyleConfig(string $weaponSubtype, int $styleIndex): array
    {
        $styles = self::WEAPON_ATTACK_STYLES[$weaponSubtype] ?? self::WEAPON_ATTACK_STYLES['unarmed'];

        return $styles[min($styleIndex, count($styles) - 1)];
    }

    /**
     * Get the monster's defense value for a specific attack type.
     */
    protected function getMonsterDefenseForType(Monster $monster, string $attackType): int
    {
        return match ($attackType) {
            'stab' => $monster->stab_defense ?: $monster->defense_level,
            'slash' => $monster->slash_defense ?: $monster->defense_level,
            'crush' => $monster->crush_defense ?: $monster->defense_level,
            default => $monster->defense_level,
        };
    }

    /**
     * Get weapon speed for a subtype.
     */
    public function getWeaponSpeed(string $weaponSubtype): int
    {
        return self::WEAPON_SPEED[$weaponSubtype] ?? 5;
    }

    /**
     * Calculate player's attack damage.
     */
    protected function calculatePlayerAttack(User $player, Monster $monster, int $round = 1, ?CombatSession $session = null): array
    {
        $attackLevel = $player->getSkillLevel('attack');
        $strengthLevel = $player->getSkillLevel('strength');
        $equipment = $this->getPlayerEquipmentBonuses($player);

        // Get blessing bonuses
        $attackBonus = (int) $this->blessingEffectService->getEffect($player, 'attack_bonus');
        $strengthBonus = (int) $this->blessingEffectService->getEffect($player, 'strength_bonus');

        // Apply all combat stats bonus (from HQ prayer)
        $allCombatBonus = (int) $this->blessingEffectService->getEffect($player, 'all_combat_stats_bonus');
        $attackBonus += $allCombatBonus;
        $strengthBonus += $allCombatBonus;

        // Get belief bonuses
        $attackBonus += (int) $this->beliefEffectService->getEffect($player, 'attack_bonus');
        $strengthBonus += (int) $this->beliefEffectService->getEffect($player, 'strength_bonus');

        // Apply stance bonuses
        $stanceBonus = ['attack' => 0, 'strength' => 0, 'defense' => 0];
        if ($session) {
            $styleConfig = $this->getAttackStyleConfig(
                $this->getPlayerWeaponSubtype($player),
                $session->attack_style_index
            );
            $stanceBonus = self::STANCE_BONUSES[$styleConfig['weapon_style']] ?? $stanceBonus;
        }

        // Apply potion buffs (percentage boost to base levels + bonuses)
        $effectiveAttack = $attackLevel + $attackBonus + $stanceBonus['attack'];
        $effectiveStrength = $strengthLevel + $strengthBonus + $stanceBonus['strength'];
        $effectiveAttack = $this->potionBuffService->applyAttackBuff($player, $effectiveAttack);
        $effectiveStrength = $this->potionBuffService->applyStrengthBuff($player, $effectiveStrength);

        // Determine monster defense based on attack type
        $monsterDefense = $monster->defense_level;
        if ($session) {
            $styleConfig = $styleConfig ?? $this->getAttackStyleConfig(
                $this->getPlayerWeaponSubtype($player),
                $session->attack_style_index
            );
            $monsterDefense = $this->getMonsterDefenseForType($monster, $styleConfig['attack_type']);
        }

        // Hit chance: based on effective attack (with buffs) vs monster defense
        $hitChance = 50 + ($effectiveAttack - $monsterDefense) * 2 + $equipment['atk_bonus'];
        $hitChance = max(10, min(95, $hitChance)); // Clamp between 10% and 95%

        $hit = rand(1, 100) <= $hitChance;

        if (! $hit) {
            return ['hit' => false, 'damage' => 0];
        }

        // Damage calculation: effective strength (with buffs) + equipment bonus
        $baseDamage = $effectiveStrength + $equipment['str_bonus'];
        $maxHit = (int) floor($baseDamage * 0.5);
        $damage = rand(1, max(1, $maxHit));

        // Check weapon effectiveness
        $damage = $this->applyWeaponEffectiveness($player, $monster, $damage);

        // Apply first-strike damage bonus (Assassin's Creed cult belief) - only on round 1
        if ($round === 1) {
            $firstStrikeBonus = $this->beliefEffectService->getEffect($player, 'first_strike_damage_bonus');
            if ($firstStrikeBonus > 0) {
                $damage = (int) ceil($damage * (1 + $firstStrikeBonus / 100));
            }
        }

        // Check for critical hit against monsters (from HQ prayer buff)
        $critChance = (int) $this->blessingEffectService->getEffect($player, 'monster_crit_chance');
        $isCrit = $critChance > 0 && rand(1, 100) <= $critChance;
        if ($isCrit) {
            $damage = (int) floor($damage * 1.5); // 50% bonus damage on crit
        }

        return ['hit' => true, 'damage' => $damage, 'crit' => $isCrit];
    }

    /**
     * Calculate monster's attack damage.
     */
    protected function calculateMonsterAttack(User $player, Monster $monster, ?CombatSession $session = null): array
    {
        // Agility dodge chance: 0.2% per level (max 10% at level 50+)
        $agilityLevel = $player->getSkillLevel('agility');
        $dodgeChance = min(10, $agilityLevel * 0.2);

        if (rand(1, 100) <= $dodgeChance) {
            return ['hit' => false, 'damage' => 0, 'dodged' => true];
        }

        $defenseLevel = $player->getSkillLevel('defense');
        $equipment = $this->getPlayerEquipmentBonuses($player);

        // Get blessing defense bonus
        $defenseBonus = (int) $this->blessingEffectService->getEffect($player, 'defense_bonus');

        // Apply all combat stats bonus (from HQ prayer)
        $defenseBonus += (int) $this->blessingEffectService->getEffect($player, 'all_combat_stats_bonus');

        // Get belief defense bonus
        $defenseBonus += (int) $this->beliefEffectService->getEffect($player, 'defense_bonus');

        // Apply defense penalty from cult beliefs (Assassin's Creed)
        $defensePenalty = $this->beliefEffectService->getEffect($player, 'defense_penalty');
        if ($defensePenalty < 0) {
            // Penalty is stored as negative, so this reduces effective defense
            $defenseBonus += (int) $defensePenalty;
        }

        // Apply stance defense bonus
        if ($session) {
            $styleConfig = $this->getAttackStyleConfig(
                $this->getPlayerWeaponSubtype($player),
                $session->attack_style_index
            );
            $stanceBonus = self::STANCE_BONUSES[$styleConfig['weapon_style']] ?? [];
            $defenseBonus += $stanceBonus['defense'] ?? 0;
        }

        // Apply potion buff to defense (includes all bonuses)
        $effectiveDefense = $this->potionBuffService->applyDefenseBuff($player, $defenseLevel + $defenseBonus);

        // Hit chance (effective defense reduces monster's chance to hit)
        $hitChance = 50 + ($monster->attack_level - $effectiveDefense - $equipment['def_bonus']) * 2;
        $hitChance = max(10, min(95, $hitChance));

        $hit = rand(1, 100) <= $hitChance;

        if (! $hit) {
            return ['hit' => false, 'damage' => 0];
        }

        $baseDamage = $monster->strength_level;
        $maxHit = (int) floor($baseDamage * 0.5);
        $damage = rand(1, max(1, $maxHit));

        return ['hit' => true, 'damage' => $damage];
    }

    /**
     * Get total equipment bonuses for player.
     */
    protected function getPlayerEquipmentBonuses(User $player): array
    {
        $bonuses = [
            'atk_bonus' => 0,
            'str_bonus' => 0,
            'def_bonus' => 0,
            'hp_bonus' => 0,
        ];

        $equippedItems = $player->inventory()
            ->where('is_equipped', true)
            ->with('item')
            ->get();

        foreach ($equippedItems as $slot) {
            $item = $slot->item;
            $bonuses['atk_bonus'] += $item->atk_bonus;
            $bonuses['str_bonus'] += $item->str_bonus;
            $bonuses['def_bonus'] += $item->def_bonus;
            $bonuses['hp_bonus'] += $item->hp_bonus;
        }

        return $bonuses;
    }

    /**
     * Apply weapon effectiveness modifiers.
     */
    protected function applyWeaponEffectiveness(User $player, Monster $monster, int $damage): int
    {
        $weapon = $player->inventory()
            ->where('is_equipped', true)
            ->whereHas('item', fn ($q) => $q->where('equipment_slot', 'weapon'))
            ->with('item')
            ->first();

        if (! $weapon) {
            return $damage;
        }

        $item = $weapon->item;

        // Check if effective against this monster type
        if ($item->effective_against && in_array($monster->type, $item->effective_against)) {
            return (int) floor($damage * 1.5); // 50% bonus
        }

        // Check if weak against this monster type
        if ($item->weak_against && in_array($monster->type, $item->weak_against)) {
            return (int) floor($damage * 0.5); // 50% penalty
        }

        return $damage;
    }

    /**
     * Handle player victory.
     */
    protected function handleVictory(User $player, CombatSession $session, array $logs): array
    {
        $monster = $session->monster;

        // Update session status
        $session->status = CombatSession::STATUS_VICTORY;
        $session->save();

        // XP was already awarded per hit during combat
        // Get attack style info for the victory screen
        $weaponSubtype = $this->getPlayerWeaponSubtype($player);
        $styleConfig = $this->getAttackStyleConfig($weaponSubtype, $session->attack_style_index);
        $xpSkills = $styleConfig['xp_skills'];

        $skill = $player->skills()->where('skill_name', $xpSkills[0])->first();

        // Roll for loot
        $loot = $this->lootService->rollAndGiveLoot($player, $monster);

        return [
            'success' => true,
            'message' => "Victory! You defeated {$monster->name}!",
            'data' => [
                'session' => $session,
                'logs' => $logs,
                'status' => 'victory',
                'rewards' => [
                    'xp' => $session->xp_gained,
                    'skill' => $session->training_style,
                    'xp_skills' => $xpSkills,
                    'current_level' => $skill?->level ?? 1,
                    'gold' => $loot['gold'],
                    'items' => $loot['items'],
                    'attack_style' => $styleConfig['name'],
                ],
            ],
        ];
    }

    /**
     * Handle player defeat.
     */
    protected function handleDefeat(User $player, CombatSession $session, array $logs): array
    {
        $monster = $session->monster;

        // Update session status
        $session->status = CombatSession::STATUS_DEFEAT;
        $session->save();

        // Player dies - set HP to 0 and reduce energy
        $player->hp = 0;
        $player->save();

        $this->energyService->setEnergyOnDeath($player);

        // Admit player to infirmary
        $this->infirmaryService->admitPlayer($player);

        // XP was already awarded per hit during combat
        return [
            'success' => false,
            'message' => "Defeat! You were killed by {$monster->name}. You've been taken to the infirmary.",
            'data' => [
                'session' => $session,
                'logs' => $logs,
                'status' => 'defeat',
                'xp_earned' => $session->xp_gained,
                'skill' => $session->training_style,
                'infirmary' => $this->infirmaryService->getInfirmaryStatus($player->fresh()),
            ],
        ];
    }

    /**
     * Award XP to combat skill(s) and hitpoints.
     * HP XP is always 1/3 of the combat XP earned.
     * For controlled stance, XP is split evenly across attack/strength/defense.
     */
    protected function awardCombatXp(User $player, CombatSession $session, int $xp): void
    {
        // Apply belief combat XP bonus (Martial Prowess, Bloodlust, Pride)
        $combatXpBonus = $this->beliefEffectService->getEffect($player, 'combat_xp_bonus');
        if ($combatXpBonus != 0) {
            $xp = (int) ceil($xp * (1 + $combatXpBonus / 100));
        }

        // Apply general XP penalty (Sloth belief)
        $xpPenalty = $this->beliefEffectService->getEffect($player, 'xp_penalty');
        if ($xpPenalty != 0) {
            $xp = (int) ceil($xp * (1 + $xpPenalty / 100));
        }

        // Determine XP skills from attack style config
        $weaponSubtype = $this->getPlayerWeaponSubtype($player);
        $styleConfig = $this->getAttackStyleConfig($weaponSubtype, $session->attack_style_index);
        $xpSkills = $styleConfig['xp_skills'];

        if (count($xpSkills) > 1) {
            // Controlled: split XP evenly across skills
            $xpPerSkill = (int) floor($xp / count($xpSkills));
            foreach ($xpSkills as $skill) {
                if ($xpPerSkill > 0) {
                    $this->addXpToSkill($player, $skill, $xpPerSkill);
                }
            }
        } else {
            // Single skill: award full XP
            $this->addXpToSkill($player, $xpSkills[0], $xp);
        }

        // Award HP XP (1/3 of combat XP, floored per hit like OSRS)
        $hpXp = (int) floor($xp / 3);
        if ($hpXp > 0) {
            $this->addXpToSkill($player, 'hitpoints', $hpXp);
        }
    }

    /**
     * Add XP to a specific skill.
     */
    protected function addXpToSkill(User $player, string $skillName, int $xp): void
    {
        $skill = $player->skills()->where('skill_name', $skillName)->first();

        if (! $skill) {
            $skill = PlayerSkill::create([
                'player_id' => $player->id,
                'skill_name' => $skillName,
                'level' => 1,
                'xp' => 0,
            ]);
        }

        $skill->addXp($xp);
    }

    /**
     * Get combat information for display.
     */
    public function getCombatInfo(User $player): array
    {
        $session = $this->getActiveCombat($player);
        $equipment = $this->getPlayerEquipmentBonuses($player);
        $weaponSubtype = $this->getPlayerWeaponSubtype($player);
        $attackStyles = self::WEAPON_ATTACK_STYLES[$weaponSubtype] ?? self::WEAPON_ATTACK_STYLES['unarmed'];
        $weaponSpeed = $this->getWeaponSpeed($weaponSubtype);

        return [
            'in_combat' => $session !== null,
            'session' => $session,
            'player_stats' => [
                'hp' => $player->hp,
                'max_hp' => $player->max_hp,
                'combat_level' => $player->combat_level,
                'attack' => $player->getSkillLevel('attack'),
                'strength' => $player->getSkillLevel('strength'),
                'defense' => $player->getSkillLevel('defense'),
            ],
            'equipment' => $equipment,
            'energy' => [
                'current' => $player->energy,
                'cost' => self::ENERGY_COST,
            ],
            'weapon_subtype' => $weaponSubtype,
            'weapon_speed' => $weaponSpeed,
            'available_attack_styles' => $attackStyles,
        ];
    }

    /**
     * Get food items available for eating during combat.
     */
    public function getAvailableFood(User $player): array
    {
        return $player->inventory()
            ->with('item')
            ->whereHas('item', fn ($q) => $q->where('type', 'consumable')->where('hp_bonus', '>', 0))
            ->get()
            ->map(fn ($slot) => [
                'id' => $slot->id,
                'name' => $slot->item->name,
                'hp_bonus' => $slot->item->hp_bonus,
                'quantity' => $slot->quantity,
            ])
            ->toArray();
    }
}
