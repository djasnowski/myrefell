<?php

namespace App\Services;

use App\Models\Barony;
use App\Models\Duchy;
use App\Models\Dungeon;
use App\Models\DungeonFloor;
use App\Models\DungeonLootStorage;
use App\Models\DungeonSession;
use App\Models\Kingdom;
use App\Models\Monster;
use App\Models\PlayerSkill;
use App\Models\Town;
use App\Models\User;
use App\Models\Village;
use Illuminate\Support\Facades\DB;

class DungeonService
{
    public function __construct(
        protected EnergyService $energyService,
        protected LootService $lootService,
        protected CombatService $combatService,
        protected InfirmaryService $infirmaryService
    ) {}

    /**
     * Get available dungeons for a player at their current location.
     * Dungeons are now filtered by the player's current kingdom.
     *
     * @return array<Dungeon>
     */
    public function getAvailableDungeons(User $player): array
    {
        $kingdom = $this->getPlayerKingdom($player);

        if (! $kingdom) {
            return [];
        }

        return Dungeon::query()
            ->where('kingdom_id', $kingdom->id)
            ->where('min_combat_level', '<=', $player->combat_level)
            ->with(['bossMonster', 'kingdom', 'floors.monsters.monster'])
            ->orderBy('min_combat_level')
            ->get()
            ->toArray();
    }

    /**
     * Get the kingdom for a player's current location.
     * Traverses up the location hierarchy to find the kingdom.
     */
    public function getPlayerKingdom(User $player): ?Kingdom
    {
        return match ($player->current_location_type) {
            'kingdom' => Kingdom::find($player->current_location_id),
            'duchy' => Duchy::find($player->current_location_id)?->kingdom,
            'barony' => Barony::find($player->current_location_id)?->kingdom,
            'town' => Town::find($player->current_location_id)?->barony?->kingdom,
            'village' => Village::find($player->current_location_id)?->barony?->kingdom,
            default => null,
        };
    }

    /**
     * Get the player's active dungeon session.
     */
    public function getActiveSession(User $player): ?DungeonSession
    {
        return DungeonSession::where('user_id', $player->id)
            ->where('status', DungeonSession::STATUS_ACTIVE)
            ->with(['dungeon', 'dungeon.floors', 'dungeon.bossMonster'])
            ->first();
    }

    /**
     * Enter a dungeon.
     */
    public function enterDungeon(User $player, int $dungeonId, int $attackStyleIndex = 0): array
    {
        // Check if player is traveling
        if ($player->isTraveling()) {
            return ['success' => false, 'message' => 'You cannot enter a dungeon while traveling.'];
        }

        // Check if player is in the infirmary
        if ($player->isInInfirmary()) {
            return ['success' => false, 'message' => 'You cannot enter a dungeon while recovering in the infirmary.'];
        }

        // Check if player is alive
        if (! $player->isAlive()) {
            return ['success' => false, 'message' => 'You are dead and cannot enter a dungeon.'];
        }

        // Check for active combat
        $activeCombat = $this->combatService->getActiveCombat($player);
        if ($activeCombat) {
            return ['success' => false, 'message' => 'You are in combat and cannot enter a dungeon.'];
        }

        // Check for existing dungeon session
        $existingSession = $this->getActiveSession($player);
        if ($existingSession) {
            return ['success' => false, 'message' => 'You are already in a dungeon.'];
        }

        // Find the dungeon
        $dungeon = Dungeon::with('floors')->find($dungeonId);
        if (! $dungeon) {
            return ['success' => false, 'message' => 'Dungeon not found.'];
        }

        // Check level requirement
        if (! $dungeon->canBeEnteredBy($player)) {
            return ['success' => false, 'message' => "You need combat level {$dungeon->min_combat_level} to enter this dungeon."];
        }

        // Check energy
        if (! $this->energyService->hasEnergy($player, $dungeon->energy_cost)) {
            return ['success' => false, 'message' => "You need {$dungeon->energy_cost} energy to enter this dungeon."];
        }

        // Resolve attack style config from weapon + index
        $weaponSubtype = $this->combatService->getPlayerWeaponSubtype($player);
        $styles = CombatService::WEAPON_ATTACK_STYLES[$weaponSubtype] ?? CombatService::WEAPON_ATTACK_STYLES['unarmed'];
        $attackStyleIndex = max(0, min($attackStyleIndex, count($styles) - 1));
        $styleConfig = $styles[$attackStyleIndex];
        $trainingStyle = $styleConfig['xp_skills'][0] ?? 'attack';

        return DB::transaction(function () use ($player, $dungeon, $trainingStyle, $attackStyleIndex) {
            // Consume energy
            $this->energyService->consumeEnergy($player, $dungeon->energy_cost);

            // Get first floor monster count
            $firstFloor = $dungeon->floors()->where('floor_number', 1)->first();
            $monsterCount = $firstFloor?->monster_count ?? 3;

            // Create session
            $session = DungeonSession::create([
                'user_id' => $player->id,
                'dungeon_id' => $dungeon->id,
                'current_floor' => 1,
                'monsters_defeated' => 0,
                'total_monsters_on_floor' => $monsterCount,
                'status' => DungeonSession::STATUS_ACTIVE,
                'xp_accumulated' => 0,
                'gold_accumulated' => 0,
                'loot_accumulated' => [],
                'training_style' => $trainingStyle,
                'attack_style_index' => $attackStyleIndex,
                'entry_location_type' => $player->current_location_type,
                'entry_location_id' => $player->current_location_id,
            ]);

            $session->load(['dungeon', 'dungeon.floors', 'dungeon.bossMonster']);

            return [
                'success' => true,
                'message' => "You enter {$dungeon->name}...",
                'data' => [
                    'session' => $session,
                ],
            ];
        });
    }

    /**
     * Fight the next monster in the dungeon.
     */
    public function fightMonster(User $player): array
    {
        $session = $this->getActiveSession($player);
        if (! $session) {
            return ['success' => false, 'message' => 'You are not in a dungeon.'];
        }

        if ($session->isFloorCleared()) {
            return ['success' => false, 'message' => 'This floor is already cleared. Proceed to the next floor.'];
        }

        // Check if player is alive
        if (! $player->isAlive()) {
            return $this->handleDeath($player, $session);
        }

        return DB::transaction(function () use ($player, $session) {
            $floor = $session->getCurrentFloor();
            if (! $floor) {
                return ['success' => false, 'message' => 'Floor data error.'];
            }

            // Get the monster for this encounter
            $monster = $this->getMonsterForFloor($session, $floor);
            if (! $monster) {
                return ['success' => false, 'message' => 'No monsters available on this floor.'];
            }

            // Simulate combat (simplified dungeon combat)
            $combatResult = $this->simulateCombat($player, $monster, $floor);

            if ($combatResult['player_won']) {
                // Award XP based on damage dealt (4 XP per damage, like regular combat)
                $xpGained = $combatResult['damage_dealt'] * CombatService::XP_PER_DAMAGE;
                $session->addXp($xpGained);

                // Award gold
                $goldGained = $monster->rollGoldDrop();
                $session->addGold($goldGained);

                // Roll for loot (with floor multiplier affecting chance)
                $loot = $this->rollDungeonLoot($player, $monster, $session, $floor);

                // Increment defeated count
                $session->increment('monsters_defeated');
                $session->refresh();

                $floorCleared = $session->isFloorCleared();
                $dungeonCompleted = $floorCleared && $session->isOnFinalFloor();

                if ($dungeonCompleted) {
                    return $this->completeDungeon($player, $session, $monster, $xpGained, $goldGained, $loot);
                }

                return [
                    'success' => true,
                    'message' => "You defeated {$monster->name}!",
                    'data' => [
                        'session' => $session->fresh(['dungeon', 'dungeon.floors']),
                        'combat' => $combatResult,
                        'rewards' => [
                            'xp' => $xpGained,
                            'gold' => $goldGained,
                            'items' => $loot,
                        ],
                        'floor_cleared' => $floorCleared,
                        'status' => 'active',
                    ],
                ];
            } else {
                // Player was defeated
                return $this->handleDeath($player, $session);
            }
        });
    }

    /**
     * Get a monster for the current floor encounter.
     */
    protected function getMonsterForFloor(DungeonSession $session, DungeonFloor $floor): ?Monster
    {
        // On boss floor with all other monsters defeated, spawn boss (if one exists)
        if ($floor->is_boss_floor && $session->monsters_defeated === $session->total_monsters_on_floor - 1) {
            $boss = $session->dungeon->bossMonster;
            if ($boss) {
                return $boss;
            }
            // Fall through to regular monster if no boss configured
        }

        // Otherwise get random monster from floor spawn table
        return $floor->getRandomMonster();
    }

    /**
     * Simulate combat between player and monster.
     */
    protected function simulateCombat(User $player, Monster $monster, DungeonFloor $floor): array
    {
        $playerHp = $player->hp;
        $monsterHp = $monster->max_hp;
        $rounds = 0;
        $maxRounds = 50; // Safety limit
        $totalDamageDealt = 0;

        $equipment = $this->getPlayerEquipmentBonuses($player);
        $attackLevel = $player->getSkillLevel('attack');
        $strengthLevel = $player->getSkillLevel('strength');
        $defenseLevel = $player->getSkillLevel('defense');

        // Get attack style and weapon speed from the active session
        $session = $this->getActiveSession($player);
        $weaponSubtype = $this->combatService->getPlayerWeaponSubtype($player);
        $attackStyleIndex = $session?->attack_style_index ?? 0;
        $styleConfig = $this->combatService->getAttackStyleConfig($weaponSubtype, $attackStyleIndex);
        $stanceBonus = CombatService::STANCE_BONUSES[$styleConfig['weapon_style']] ?? ['attack' => 0, 'strength' => 0, 'defense' => 0];

        // Apply stance bonuses
        $effectiveAttack = $attackLevel + $stanceBonus['attack'];
        $effectiveStrength = $strengthLevel + $stanceBonus['strength'];
        $effectiveDefense = $defenseLevel + $stanceBonus['defense'];

        // Get typed monster defense
        $monsterDefense = match ($styleConfig['attack_type']) {
            'stab' => $monster->stab_defense ?: $monster->defense_level,
            'slash' => $monster->slash_defense ?: $monster->defense_level,
            'crush' => $monster->crush_defense ?: $monster->defense_level,
            default => $monster->defense_level,
        };

        // Weapon speed
        $speed = $this->combatService->getWeaponSpeed($weaponSubtype);
        $hitsPerRound = CombatService::SPEED_HITS[$speed] ?? 1;
        $damageMult = CombatService::SPEED_DAMAGE_MULT[$speed] ?? 1.0;

        while ($playerHp > 0 && $monsterHp > 0 && $rounds < $maxRounds) {
            $rounds++;

            // Player attacks (possibly multiple times for fast weapons)
            for ($hit = 0; $hit < $hitsPerRound && $monsterHp > 0; $hit++) {
                $playerHitChance = 50 + ($effectiveAttack - $monsterDefense) * 2 + $equipment['atk_bonus'];
                $playerHitChance = max(10, min(95, $playerHitChance));

                if (rand(1, 100) <= $playerHitChance) {
                    $baseDamage = $effectiveStrength + $equipment['str_bonus'];
                    $maxHit = (int) floor($baseDamage * 0.5);
                    $damage = rand(1, max(1, $maxHit));

                    // Apply slow weapon damage multiplier
                    if ($damageMult !== 1.0) {
                        $damage = (int) round($damage * $damageMult);
                    }

                    $monsterHp -= $damage;
                    $totalDamageDealt += $damage;
                }
            }

            if ($monsterHp <= 0) {
                break;
            }

            // Monster attacks
            $monsterHitChance = 50 + ($monster->attack_level - $effectiveDefense - $equipment['def_bonus']) * 2;
            $monsterHitChance = max(10, min(95, $monsterHitChance));

            if (rand(1, 100) <= $monsterHitChance) {
                $maxHit = (int) floor($monster->strength_level * 0.5);
                $damage = rand(1, max(1, $maxHit));
                $playerHp -= $damage;
            }
        }

        // Update player HP
        $player->hp = max(0, $playerHp);
        $player->save();

        // Cap damage dealt at monster's max HP (no overkill XP)
        $effectiveDamage = min($totalDamageDealt, $monster->max_hp);

        return [
            'player_won' => $monsterHp <= 0,
            'rounds' => $rounds,
            'player_hp_remaining' => max(0, $playerHp),
            'damage_taken' => $player->hp - max(0, $playerHp),
            'damage_dealt' => $effectiveDamage,
        ];
    }

    /**
     * Get player equipment bonuses.
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
     * Roll for dungeon loot (stored until completion).
     */
    protected function rollDungeonLoot(User $player, Monster $monster, DungeonSession $session, DungeonFloor $floor): array
    {
        $items = [];

        foreach ($monster->lootTable as $lootEntry) {
            // Apply floor loot multiplier to chance
            $adjustedChance = min(100, $lootEntry->drop_chance * $floor->loot_multiplier);

            if (rand(1, 100) <= $adjustedChance) {
                $quantity = rand($lootEntry->quantity_min, $lootEntry->quantity_max);
                if ($quantity > 0) {
                    $session->addLoot($lootEntry->item_id, $quantity);
                    $items[] = [
                        'name' => $lootEntry->item->name,
                        'quantity' => $quantity,
                    ];
                }
            }
        }

        return $items;
    }

    /**
     * Proceed to the next floor.
     */
    public function nextFloor(User $player): array
    {
        $session = $this->getActiveSession($player);
        if (! $session) {
            return ['success' => false, 'message' => 'You are not in a dungeon.'];
        }

        if (! $session->isFloorCleared()) {
            return ['success' => false, 'message' => 'You must defeat all monsters on this floor first.'];
        }

        if ($session->isOnFinalFloor()) {
            return ['success' => false, 'message' => 'You are on the final floor. Complete the dungeon.'];
        }

        return DB::transaction(function () use ($session) {
            $nextFloorNumber = $session->current_floor + 1;
            $nextFloor = $session->dungeon->floors()->where('floor_number', $nextFloorNumber)->first();

            if (! $nextFloor) {
                return ['success' => false, 'message' => 'Next floor not found.'];
            }

            $session->current_floor = $nextFloorNumber;
            $session->monsters_defeated = 0;
            $session->total_monsters_on_floor = $nextFloor->monster_count;
            $session->save();

            return [
                'success' => true,
                'message' => "You descend to {$nextFloor->display_name}...",
                'data' => [
                    'session' => $session->fresh(['dungeon', 'dungeon.floors']),
                    'floor' => $nextFloor,
                ],
            ];
        });
    }

    /**
     * Complete the dungeon successfully.
     */
    protected function completeDungeon(User $player, DungeonSession $session, Monster $lastMonster, int $xpGained, int $goldGained, array $loot): array
    {
        $dungeon = $session->dungeon;

        // Add completion bonus XP and gold
        $bonusXp = $dungeon->xp_reward_base;
        $bonusGold = $dungeon->rollGoldReward();
        $session->addXp($bonusXp);
        $session->addGold($bonusGold);

        // Mark as completed
        $session->status = DungeonSession::STATUS_COMPLETED;
        $session->save();

        // Award accumulated XP to skill
        $this->awardAccumulatedRewards($player, $session);

        // Get all loot item names for the completion summary
        $lootItems = [];
        $allLoot = $session->loot_accumulated ?? [];
        if (! empty($allLoot)) {
            $items = \App\Models\Item::whereIn('id', array_keys($allLoot))->get()->keyBy('id');
            foreach ($allLoot as $itemId => $quantity) {
                if ($item = $items->get($itemId)) {
                    $lootItems[] = ['name' => $item->name, 'quantity' => $quantity];
                }
            }
        }

        return [
            'success' => true,
            'message' => "Dungeon Complete! You conquered {$dungeon->name}!",
            'data' => [
                'session' => $session,
                'status' => 'completed',
                'combat_rewards' => [
                    'xp' => $xpGained,
                    'gold' => $goldGained,
                    'items' => $loot,
                ],
                'completion_bonus' => [
                    'xp' => $bonusXp,
                    'gold' => $bonusGold,
                ],
                'total_rewards' => [
                    'xp' => $session->xp_accumulated,
                    'gold' => $session->gold_accumulated,
                    'skill' => $session->training_style,
                ],
                'loot_items' => $lootItems,
            ],
        ];
    }

    /**
     * Award accumulated rewards to the player.
     * XP and gold are awarded directly, but loot goes to dungeon loot storage.
     */
    protected function awardAccumulatedRewards(User $player, DungeonSession $session): void
    {
        $xp = $session->xp_accumulated;

        // Determine XP skills from attack style config
        $weaponSubtype = $this->combatService->getPlayerWeaponSubtype($player);
        $styleConfig = $this->combatService->getAttackStyleConfig($weaponSubtype, $session->attack_style_index ?? 0);
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

        // Award HP XP (1/3 of combat XP, floored like OSRS)
        $hpXp = (int) floor($xp / 3);
        if ($hpXp > 0) {
            $this->addXpToSkill($player, 'hitpoints', $hpXp);
        }

        // Award gold directly
        $player->increment('gold', $session->gold_accumulated);

        // Store loot in dungeon loot storage (instead of inventory)
        $kingdom = $session->dungeon->kingdom;
        if ($kingdom) {
            $loot = $session->loot_accumulated ?? [];
            foreach ($loot as $itemId => $quantity) {
                DungeonLootStorage::addLoot($player->id, $kingdom->id, (int) $itemId, $quantity);
            }
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
     * Handle player death in dungeon.
     */
    protected function handleDeath(User $player, DungeonSession $session): array
    {
        $session->status = DungeonSession::STATUS_FAILED;
        $session->save();

        // Player loses accumulated rewards
        $this->energyService->setEnergyOnDeath($player);

        // Admit player to infirmary
        $this->infirmaryService->admitPlayer($player);

        return [
            'success' => false,
            'message' => "You died in the dungeon. All accumulated rewards are lost. You've been taken to the infirmary.",
            'data' => [
                'session' => $session,
                'status' => 'failed',
                'infirmary' => $this->infirmaryService->getInfirmaryStatus($player->fresh()),
            ],
        ];
    }

    /**
     * Abandon the dungeon (player choice).
     */
    public function abandonDungeon(User $player): array
    {
        $session = $this->getActiveSession($player);
        if (! $session) {
            return ['success' => false, 'message' => 'You are not in a dungeon.'];
        }

        $session->status = DungeonSession::STATUS_ABANDONED;
        $session->save();

        // Player keeps nothing on abandon
        return [
            'success' => true,
            'message' => 'You fled the dungeon. All accumulated rewards are lost.',
            'data' => [
                'session' => $session,
                'status' => 'abandoned',
            ],
        ];
    }

    /**
     * Eat food during dungeon exploration.
     */
    public function eatFood(User $player, int $inventorySlotId): array
    {
        $session = $this->getActiveSession($player);
        if (! $session) {
            return ['success' => false, 'message' => 'You are not in a dungeon.'];
        }

        // Find the inventory slot
        $slot = $player->inventory()
            ->where('id', $inventorySlotId)
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

        return DB::transaction(function () use ($player, $session, $slot, $item) {
            // Calculate HP restored
            $hpRestored = min($item->hp_bonus, $player->max_hp - $player->hp);
            $player->hp = min($player->max_hp, $player->hp + $item->hp_bonus);
            $player->save();

            // Remove item from inventory
            if ($slot->quantity > 1) {
                $slot->decrement('quantity');
            } else {
                $slot->delete();
            }

            return [
                'success' => true,
                'message' => "You ate {$item->name} and restored {$hpRestored} HP.",
                'data' => [
                    'session' => $session,
                    'hp_restored' => $hpRestored,
                    'current_hp' => $player->hp,
                ],
            ];
        });
    }

    /**
     * Get dungeon info for display.
     */
    public function getDungeonInfo(User $player): array
    {
        $session = $this->getActiveSession($player);
        $equipment = $this->getPlayerEquipmentBonuses($player);

        return [
            'in_dungeon' => $session !== null,
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
            ],
        ];
    }

    /**
     * Get food items available for eating in dungeon.
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
