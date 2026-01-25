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
    public const ENERGY_COST = 5;
    public const FLEE_SUCCESS_CHANCE = 50;
    public const RESPAWN_ENERGY_PERCENT = 25;

    public function __construct(
        protected EnergyService $energyService,
        protected LootService $lootService,
        protected InventoryService $inventoryService
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
            'castle' => \App\Models\Castle::find($player->current_location_id)?->kingdom?->biome,
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
    public function startCombat(User $player, int $monsterId, string $trainingStyle = 'attack'): array
    {
        // Check if player is traveling
        if ($player->isTraveling()) {
            return ['success' => false, 'message' => 'You cannot fight while traveling.'];
        }

        // Check if player is alive
        if (!$player->isAlive()) {
            return ['success' => false, 'message' => 'You are dead and cannot fight.'];
        }

        // Check for existing combat
        $existingCombat = $this->getActiveCombat($player);
        if ($existingCombat) {
            return ['success' => false, 'message' => 'You are already in combat.'];
        }

        // Check energy
        if (!$this->energyService->hasEnergy($player, self::ENERGY_COST)) {
            return ['success' => false, 'message' => 'You need ' . self::ENERGY_COST . ' energy to start combat.'];
        }

        // Find the monster
        $monster = Monster::find($monsterId);
        if (!$monster) {
            return ['success' => false, 'message' => 'Monster not found.'];
        }

        // Check combat level requirement
        if (!$monster->canBeAttackedBy($player)) {
            return ['success' => false, 'message' => "You need combat level {$monster->min_player_combat_level} to fight this monster."];
        }

        // Validate training style
        if (!in_array($trainingStyle, CombatSession::TRAINING_STYLES)) {
            $trainingStyle = 'attack';
        }

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
        if (!$session) {
            return ['success' => false, 'message' => 'You are not in combat.'];
        }

        return DB::transaction(function () use ($player, $session) {
            $logs = [];
            $monster = $session->monster;

            // Player attacks first
            $playerAttack = $this->calculatePlayerAttack($player, $monster);
            $session->monster_hp = max(0, $session->monster_hp - $playerAttack['damage']);

            $logs[] = CombatLog::create([
                'combat_session_id' => $session->id,
                'round' => $session->round,
                'actor' => CombatLog::ACTOR_PLAYER,
                'action' => CombatLog::ACTION_ATTACK,
                'hit' => $playerAttack['hit'],
                'damage' => $playerAttack['damage'],
                'player_hp_after' => $session->player_hp,
                'monster_hp_after' => $session->monster_hp,
            ]);

            // Check if monster is dead
            if ($session->isMonsterDead()) {
                return $this->handleVictory($player, $session, $logs);
            }

            // Monster attacks back
            $monsterAttack = $this->calculateMonsterAttack($player, $monster);
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
                'message' => 'Round ' . ($session->round - 1) . ' complete.',
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
        if (!$session) {
            return ['success' => false, 'message' => 'You are not in combat.'];
        }

        // Find the inventory slot
        $slot = PlayerInventory::where('id', $inventorySlotId)
            ->where('player_id', $player->id)
            ->with('item')
            ->first();

        if (!$slot) {
            return ['success' => false, 'message' => 'Item not found in your inventory.'];
        }

        $item = $slot->item;

        // Check if it's consumable food
        if ($item->type !== 'consumable' || $item->hp_bonus <= 0) {
            return ['success' => false, 'message' => 'This item cannot be eaten.'];
        }

        return DB::transaction(function () use ($player, $session, $slot, $item) {
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
            $monsterAttack = $this->calculateMonsterAttack($player, $monster);
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
        if (!$session) {
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

            $monsterAttack = $this->calculateMonsterAttack($player, $monster);
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
     * Calculate player's attack damage.
     */
    protected function calculatePlayerAttack(User $player, Monster $monster): array
    {
        $attackLevel = $player->getSkillLevel('attack');
        $strengthLevel = $player->getSkillLevel('strength');
        $equipment = $this->getPlayerEquipmentBonuses($player);

        // Hit chance: based on attack level vs monster defense
        $hitChance = 50 + ($attackLevel - $monster->defense_level) * 2 + $equipment['atk_bonus'];
        $hitChance = max(10, min(95, $hitChance)); // Clamp between 10% and 95%

        $hit = rand(1, 100) <= $hitChance;

        if (!$hit) {
            return ['hit' => false, 'damage' => 0];
        }

        // Damage calculation: strength level + equipment bonus
        $baseDamage = $strengthLevel + $equipment['str_bonus'];
        $maxHit = (int) floor($baseDamage * 0.5);
        $damage = rand(1, max(1, $maxHit));

        // Check weapon effectiveness
        $damage = $this->applyWeaponEffectiveness($player, $monster, $damage);

        return ['hit' => true, 'damage' => $damage];
    }

    /**
     * Calculate monster's attack damage.
     */
    protected function calculateMonsterAttack(User $player, Monster $monster): array
    {
        $defenseLevel = $player->getSkillLevel('defense');
        $equipment = $this->getPlayerEquipmentBonuses($player);

        // Hit chance
        $hitChance = 50 + ($monster->attack_level - $defenseLevel - $equipment['def_bonus']) * 2;
        $hitChance = max(10, min(95, $hitChance));

        $hit = rand(1, 100) <= $hitChance;

        if (!$hit) {
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

        if (!$weapon) {
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

        // Award XP based on training style
        $xpGained = $monster->xp_reward;
        $skill = $player->skills()->where('skill_name', $session->training_style)->first();

        if (!$skill) {
            $skill = PlayerSkill::create([
                'player_id' => $player->id,
                'skill_name' => $session->training_style,
                'level' => 5,
                'xp' => 0,
            ]);
        }

        $levelsGained = $skill->addXp($xpGained);

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
                    'xp' => $xpGained,
                    'skill' => $session->training_style,
                    'levels_gained' => $levelsGained,
                    'gold' => $loot['gold'],
                    'items' => $loot['items'],
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

        return [
            'success' => false,
            'message' => "Defeat! You were killed by {$monster->name}.",
            'data' => [
                'session' => $session,
                'logs' => $logs,
                'status' => 'defeat',
            ],
        ];
    }

    /**
     * Get combat information for display.
     */
    public function getCombatInfo(User $player): array
    {
        $session = $this->getActiveCombat($player);
        $equipment = $this->getPlayerEquipmentBonuses($player);

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
