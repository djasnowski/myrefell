# Myrefell - Game Design & Technical Planning Document

## Overview

**Myrefell** is a persistent browser-based game (PBBG) set in a medieval world where players start as peasants in villages and can rise through the ranks to become lords, dukes, or even kings through democratic elections, economic power, and political maneuvering.

**Tech Stack:**
- **Backend:** Laravel (PHP 8.x)
- **Frontend:** React + Tailwind CSS
- **Database:** PostgreSQL
- **Deployment:** Laravel Forge
- **Real-time:** Polling for most features, WebSocket/SSE for combat only

---

## Core Philosophy

> "Land belongs to groups. Power belongs to players."

- Villages = social glue
- Castles = conflict engines
- Kingdoms = narrative arcs

---

## 1. World Hierarchy

```
Kingdom (4 at launch)
 └─ Duchy / Province (future, post-MVP)
     └─ Castle (10 at launch)
         └─ Town (upgraded villages)
             └─ Village (50 at launch)
                 └─ Player
```

### Biomes
All biomes represented:
- Forests
- Plains
- Mountains
- Swamps
- Desert
- Tundra/Snow
- Coastal
- **Volcano** (at least 1 kingdom located here)

---

## 2. Player System

### Account & Character
| Field | Details |
|-------|---------|
| Registration | Username + Email + Password |
| Verification | Email verification required |
| Characters | 1 character per account |
| Identity | Username-directed (username is primary) |
| Gender | Male / Female (chosen at creation) |

### Player Stats
Players have **skill levels** that combine with **gear bonuses** for total power.

| Stat | Formula / Value |
|------|-----------------|
| Combat Level | `floor((ATK + STR + DEF) / 3)` + gear bonuses |
| HP | Starts at 10, max 99 (scales with combat level or Defense) |
| Energy | Starts at 10, max 150 |

**Starting Stats:**
- All combat skills (ATK, STR, DEF) start at level 5
- Starting Combat Level: 5
- Starting HP: 10
- Starting Energy: 10

### Skills System (8 Skills)

Each skill has its own XP track. Max level: 99.

**XP Formula:**
```
XP required to advance from level L to L+1 = L² × 60
Total XP at level 99 ≈ 18,800,000
```

| Level | XP to Next | Total XP |
|-------|------------|----------|
| 1 → 2 | 60 | 0 |
| 9 → 10 | 5,400 | 17,160 |
| 49 → 50 | 144,060 | 2,345,640 |
| 98 → 99 | 576,240 | 18,227,160 |

#### Combat Skills
| Skill | Trains By | Where |
|-------|-----------|-------|
| Attack | Combat (select "Train Attack" mode) | Anywhere (combat) |
| Strength | Combat (select "Train Strength" mode) | Anywhere (combat) |
| Defense | Combat (select "Train Defense" mode) | Anywhere (combat) |

*Before combat, player selects which combat skill to train. XP goes to that skill.*

#### Gathering Skills
| Skill | Trains By | Where |
|-------|-----------|-------|
| Mining | Mining ore nodes | Anywhere (wilderness, mines) |
| Fishing | Fishing at water spots | Anywhere (rivers, lakes, coast) |

#### Production Skills
| Skill | Trains By | Where |
|-------|-----------|-------|
| Cooking | Cooking fish/food | Anywhere (campfire) or settlements (kitchen) |
| Smithing | Smelting ore, forging weapons/armor | **Forge only** (settlements) |
| Crafting | Leather work, jewelry, gem cutting | **Workshop only** (settlements) |

### NPC vs Player Production (Docket System)

Locations have NPC producers (Blacksmith for Smithing, Crafter for Crafting) with skill levels.

**Applies to both Smithing AND Crafting roles.**

| Producer Type | Speed | Cost | XP Gain |
|---------------|-------|------|---------|
| **NPC** | Instant | Set by authorities → goes to treasury | None |
| **Player (role)** | Queued (docket) | Cheaper or player-set | Player gains XP |

**Player Docket System (Blacksmith & Crafter):**
1. Customer submits order ("Forge Iron Sword" or "Craft Gold Ring")
2. Order appears in role holder's docket queue
3. Player clicks "Work" → costs energy → completes order
4. Customer gets item, producer gets paid + XP
5. **Tardiness: 10 minutes** - After 10 min, authorities notified
6. Authorities can relieve player and NPC steps back in

**Material Sources:**
- Customer provides materials (ore, leather, gems, etc.), OR
- Use location stockpile (costs gold to the customer)

**NPC Knowledge:**
- Locations train their NPC to higher levels over time
- If a player with higher skill takes the role, they replace NPC
- Player leaving → NPC steps back in

### Energy System
| Property | Value |
|----------|-------|
| Starting Energy | 10 |
| Maximum Energy | 150 |
| Regeneration | 1 energy per 5 minutes |
| On Death | Respawn at hospital, energy = 25% of previous |

**Energy Costs:**
- Training stats
- Doing jobs
- Traversing between locations
- Crafting

### Inventory
- **28 slots**
- Stackable items (quantity-based unless noted)
- Items have weight? (TBD or simple slot-based)

### Player Lifecycle
1. Register account
2. Verify email
3. Create character (choose name, appearance?)
4. Auto-assigned or choose starting village
5. Begin as Peasant/Freeman

---

## 3. Location Types

### Village
The foundation of society. Every player belongs to exactly one village.

**Characteristics:**
- Population hubs
- Low-risk zones
- Resource producers (wood, grain, hides, iron)
- Pay taxes to Castle (if pledged)

**Roles (elected/appointed):**
| Role | Responsibilities | Perks |
|------|------------------|-------|
| Elder | Leadership, sees everything, calls votes | Full visibility, admin powers |
| Blacksmith | Weapon/armor crafting services | Crafting bonuses |
| Farmer | Food production | Extra food to personal bank |
| Guard Captain | Village defense | Defense bonuses |
| Doctor | Healing services | Healing bonuses |
| Merchant | Shop/trade management | Sets sale prices, trade bonuses |

**Jobs (sign up for work):**
- Cook
- Cleaner
- Stable Hand
- Farmhand
- Miner
- Lumberjack
- etc.

Jobs pay fixed wages. Supervisory positions (e.g., Master Cook) get % of workers under them.

### Town (Upgraded Village)
Villages can upgrade to Towns when thresholds are met.

**Requirements:**
- Population threshold
- Wealth threshold
- Triggered by Elder or Lord

**Additional Features:**
- Walls (defense bonus)
- Markets (better trading)
- Guild Halls (specialization)
- Player-run economies

### Castle
Military and authority centers. Players don't "live" in castles - they serve them.

**Paths to Castle Service:**
- **Knighted** - Combat prestige
- **Appointed** - Admin/logistics role
- **Garrisoned** - Soldier contract

**Castle Controls:**
- Multiple villages/towns
- Local law
- Tax rates
- Military actions

**Roles:**
| Role | Responsibilities | Perks |
|------|------------------|-------|
| Lord | Leadership, elected | Full control, daily salary |
| Steward | Economy/resources | Economic bonuses |
| Marshal | Military coordination | Combat bonuses |
| Treasurer | Taxes, finances | Financial oversight |
| Master Builder | Construction/upgrades | Building bonuses |
| Quartermaster | Supplies, storage | Storage bonuses |
| Chaplain | Morale, ceremonies | Morale buffs |
| Jailsman | Manages jail cells, prisoners | Arrest powers, interrogation |

**Castle Features:**
- Jail cells for prisoners (arrested players, religious persecution, criminals)

### Kingdom
Top-level political entity. Players belong to a kingdom through their village/castle.

**Kingdom Powers:**
- Wage wars
- Set global laws
- Control currency
- Cultural/religious influence

**Roles:**
| Role | Responsibilities | Perks |
|------|------------------|-------|
| King/Queen | Supreme leader, elected | Full kingdom control, daily salary |
| Chancellor | Diplomacy with other kingdoms | Diplomatic bonuses |
| General | Commands all castle marshals | Military coordination |
| Royal Treasurer | Kingdom-wide economy | Economic oversight |
| High Judge | Resolves disputes | Judicial powers |
| Master of Coin | Trade deals between kingdoms | Trade bonuses |

---

## 4. Allegiance & Politics

### Tax Flow
```
Players → Villages → Castles → Kingdoms
```

Tax rates configurable by Lord (castle) and King (kingdom).

### Switching Allegiance

**Village switches Castle:**
- Elder holds a vote
- Requires majority approval
- Consequence: Loses share of castle's daily wins
- Cooldown period applies

**Castle switches Kingdom:**
- Lord decides via "Roundtable of the Minds" meeting
- Castle-wide notification
- Cooldown period applies

### Elections

| Property | Value |
|----------|-------|
| Voting Eligibility | All residents of that location |
| Term Length | Indefinite |
| Removal | Vote of no confidence |
| Vacant Roles | NPC takes over |

**Election Process:**
1. Nomination period opens
2. Players declare candidacy
3. Voting period
4. Winner takes office
5. Vote of no confidence can be called anytime

---

## 5. Combat System

### Turn-Based Combat
Combat is the only real-time feature (back-and-forth turns).

**Flow:**
1. Player encounters monster (in wilderness/dungeon)
2. **Select training mode:** Attack, Strength, or Defense (determines which skill gains XP)
3. Combat screen loads
4. Turn-based: Player acts → Monster acts → repeat
5. Actions: Attack (basic attack only, no special abilities)
6. Combat ends when HP reaches 0

**Outcomes:**
- **Victory:** Collect loot (items, resources, gold), gain XP in selected combat skill
- **Defeat:** Respawn at location's hospital, energy = 25%

### Dungeons
- **Instanced** - Each player/party gets their own instance
- Located throughout the map
- Higher level monsters, better loot

**Dungeon Flow:**
1. Enter dungeon → instanced for you
2. Fight through multiple rounds/floors
3. Between rounds: Option to eat food (restore HP)
4. Multiple monster fights per floor
5. Final floor: **Boss fight** for best loot
6. Exit with loot or die and respawn outside

**Boss Fights:**
- Stronger than regular monsters
- Better loot drops (rare items, high-value resources)
- One boss per dungeon at the final floor

### Monster Levels
Monster level calculated from combined stats:
```
Monster Level = f(ATK, STR, DEF)
```
Starts at level 1, scales upward.

### Weapon Effectiveness
Weapons have type classifications affecting damage vs enemy types:

| Weapon Type | Effective Against | Weak Against |
|-------------|-------------------|--------------|
| Slashing (Scimitar) | Mammals, Unarmored | Armored, Undead |
| Piercing (Spear) | Lightly Armored | Heavy Armor |
| Blunt (Mace) | Armored, Undead | Agile |
| etc. | ... | ... |

### Loot Tables
Monsters drop:
- Weapons (Iron Dagger, Bronze Sword, etc.)
- Resources (Bronze Ore, Iron Ore, Leather, etc.)
- Gold
- Rare items (based on monster level)

---

## 6. Economy

### Currency
**Gold only** (single currency, simple)

### Income Sources
- Job wages (fixed per completion)
- Supervisory bonuses (% of workers)
- Role salaries (Castle/Kingdom positions)
- Trading (selling items/resources)
- Loot from combat

### Crafting
- **Anyone can craft** (no role restriction)
- Costs resources + gold
- Recipes unlock by level? (TBD)
- Higher quality materials = better results

### Trading
- Trade within villages/castles
- Merchant role sets shop prices
- Player-to-player trading
- Market system in Towns

---

## 7. Traversal & Map

### Movement
- Moving between locations costs **both time AND energy**
- Travel can be cancelled mid-journey
- Travel time based on distance
- Energy cost based on distance

### Map Structure
- Fixed map at launch
- 50 villages, 10 castles, 4 kingdoms
- All biomes represented
- Wilderness areas between settlements (where monsters roam)
- Dungeons and special locations

### Points of Interest
- Villages
- Towns
- Castles
- Kingdoms (capitals)
- Dungeons
- Wilderness zones
- Mines
- Ports
- Special locations (ruins, temples, etc.)

---

## 8. Settlement Founding (Charter System)

Founding new settlements is available but extremely expensive (1,000,000+ gold).

### Eligibility
- Must obtain a Charter from Castle Lord or Kingdom authority
- Founder cannot be banned/exiled
- Must meet rank/favor requirements

### Location Rules
- Valid tile/region (unoccupied, not protected)
- Inside issuer's controllable territory
- Distance constraints from existing settlements
- Viability requirements (water access, food, or strategic value)

### Costs
| Resource | Amount |
|----------|--------|
| Gold | 1,000,000+ |
| Wood | TBD |
| Stone | TBD |
| Tools | TBD |
| Food (buffer) | TBD |

### Charter Object
```
Charter {
  settlement_name: string (unique)
  location: coordinates
  issuer: Castle/Kingdom ID
  founder: Player ID
  tax_terms: { rate, cadence }
  protection_terms: { promises, conditions }
  duration: date (optional)
  revocation_rules: conditions
}
```

### Settlement Creation
On successful founding:
1. Create settlement (Tier: Outpost/Hamlet)
2. Place starter structures (storage + housing)
3. Add starter population (founding group + NPCs)
4. Set allegiance to issuer's domain
5. Begin **Vulnerability Window** (reduced defenses, limited expansion)

### Failure States
Settlement can fail if:
- Resources run out before stabilization
- Population drops below minimum
- Charter revoked

Failed settlements become **Ruins** (can be resettled later).

### Anti-Exploit Measures
- Cap on settlements per domain/time window
- Name uniqueness + profanity filter
- Distance rules prevent spam clusters
- Cooldowns for founders and issuers
- Escrow system prevents "found then refund"

### Audit Trail
All charter/founding events logged immutably:
- Who issued
- Who founded
- Where
- When
- Terms

---

## 9. Chat System

### Channels
- **Location-based:** Village, Castle, Kingdom chat
- **Allegiance-based:** Faction/loyalty channels
- **Private messages:** Player-to-player

### Implementation
- Polling-based (not real-time WebSocket for MVP)
- Message history stored
- Moderation tools for Elders/Lords/Kings

---

## 10. Database Schema

### Core Entities

```
players
├── id (PK)
├── username (unique)
├── email (unique)
├── email_verified_at
├── password
├── gender (male/female)
├── current_village_id (FK)
├── current_location_type (village/town/castle/kingdom/wilderness/dungeon)
├── current_location_id
├── hp
├── max_hp
├── energy
├── max_energy
├── gold
├── is_traveling
├── travel_destination_type
├── travel_destination_id
├── travel_started_at
├── travel_arrives_at
├── created_at
├── updated_at
└── deleted_at

player_skills
├── id (PK)
├── player_id (FK)
├── skill_name (attack/strength/defense/mining/fishing/cooking/smithing/crafting)
├── level (1-99, combat skills start at 5, others start at 1)
├── xp (current total XP)
├── created_at
└── updated_at

location_npcs
├── id (PK)
├── location_type (village/town/castle)
├── location_id
├── npc_type (blacksmith/crafter/doctor/etc.)
├── skill_level (1-99)
├── price_modifier (decimal, e.g., 1.0 = base price)
├── is_active (boolean - false if player has taken over)
└── created_at

crafting_orders
├── id (PK)
├── location_type
├── location_id
├── customer_player_id (FK)
├── crafter_player_id (FK, nullable - null if NPC)
├── recipe_id (FK)
├── status (pending/in_progress/completed/cancelled)
├── materials_source (customer/stockpile)
├── gold_cost
├── created_at
├── assigned_at
├── completed_at
├── due_at
└── updated_at

crafting_recipes
├── id (PK)
├── name
├── skill_type (smithing/crafting/cooking)
├── required_level
├── xp_reward
├── result_item_id (FK)
├── result_quantity
├── base_gold_cost
└── created_at

recipe_materials
├── id (PK)
├── recipe_id (FK)
├── item_id (FK)
├── quantity_required
└── created_at

location_stockpiles
├── id (PK)
├── location_type
├── location_id
├── item_id (FK)
├── quantity
├── price_per_unit
└── updated_at

gathering_nodes
├── id (PK)
├── type (ore/fish/tree)
├── subtype (iron_ore/coal/salmon/oak/etc.)
├── location_x
├── location_y
├── biome
├── required_level
├── xp_per_action
├── respawn_seconds
├── current_resources
├── max_resources
└── last_harvested_at

kingdoms
├── id (PK)
├── name (unique)
├── description
├── biome
├── capital_castle_id (FK)
├── tax_rate
├── created_at
└── updated_at

castles
├── id (PK)
├── name (unique)
├── description
├── kingdom_id (FK, nullable)
├── biome
├── tax_rate
├── coordinates_x
├── coordinates_y
├── created_at
└── updated_at

villages
├── id (PK)
├── name (unique)
├── description
├── castle_id (FK, nullable)
├── is_town (boolean)
├── population
├── wealth
├── biome
├── coordinates_x
├── coordinates_y
├── created_at
└── updated_at

roles
├── id (PK)
├── name (elder, blacksmith, lord, king, etc.)
├── location_type (village/castle/kingdom)
├── permissions (JSON)
├── bonuses (JSON)
├── salary (for castle/kingdom roles)
└── created_at

player_roles
├── id (PK)
├── player_id (FK)
├── role_id (FK)
├── location_type
├── location_id
├── appointed_at
├── appointed_by (FK, nullable)
└── created_at

jobs
├── id (PK)
├── name
├── description
├── location_type
├── energy_cost
├── base_wage
├── xp_reward
└── created_at

player_jobs
├── id (PK)
├── player_id (FK)
├── job_id (FK)
├── location_type
├── location_id
├── is_supervisor
├── started_at
└── created_at

items
├── id (PK)
├── name
├── description
├── type (weapon/armor/resource/consumable/misc)
├── subtype (sword/axe/helmet/ore/etc.)
├── rarity (common/uncommon/rare/epic/legendary)
├── stackable (boolean)
├── max_stack
├── atk_bonus
├── str_bonus
├── def_bonus
├── hp_bonus
├── effectiveness_type (slashing/piercing/blunt/etc.)
├── effective_against (JSON array)
├── weak_against (JSON array)
├── base_value
└── created_at

player_inventory
├── id (PK)
├── player_id (FK)
├── item_id (FK)
├── slot_number (1-28)
├── quantity
├── is_equipped
└── created_at

monsters
├── id (PK)
├── name
├── description
├── type (mammal/undead/armored/beast/etc.)
├── base_hp
├── base_atk
├── base_str
├── base_def
├── level
├── biome (where it spawns)
├── xp_reward
├── gold_drop_min
├── gold_drop_max
└── created_at

monster_loot_tables
├── id (PK)
├── monster_id (FK)
├── item_id (FK)
├── drop_chance (0.0-1.0)
├── quantity_min
├── quantity_max
└── created_at

combat_sessions
├── id (PK)
├── player_id (FK)
├── monster_id (FK)
├── monster_current_hp
├── player_current_hp
├── turn_number
├── training_style (attack/strength/defense)
├── status (active/victory/defeat/fled)
├── is_dungeon (boolean)
├── dungeon_instance_id (FK, nullable)
├── started_at
├── ended_at
└── created_at

dungeon_instances
├── id (PK)
├── dungeon_id (FK)
├── player_id (FK)
├── status (active/completed/abandoned)
├── floor_number
├── created_at
└── completed_at

dungeons
├── id (PK)
├── name
├── description
├── min_combat_level
├── biome
├── total_floors
├── location_x
├── location_y
└── created_at

combat_logs
├── id (PK)
├── combat_session_id (FK)
├── turn_number
├── actor (player/monster)
├── action (attack/defend/item/flee)
├── damage_dealt
├── damage_received
├── notes
└── created_at

elections
├── id (PK)
├── role_id (FK)
├── location_type
├── location_id
├── status (nomination/voting/completed/cancelled)
├── nomination_starts_at
├── nomination_ends_at
├── voting_starts_at
├── voting_ends_at
├── winner_player_id (FK, nullable)
└── created_at

election_candidates
├── id (PK)
├── election_id (FK)
├── player_id (FK)
├── platform (text)
├── nominated_at
└── created_at

election_votes
├── id (PK)
├── election_id (FK)
├── voter_player_id (FK)
├── candidate_player_id (FK)
├── voted_at
└── created_at

no_confidence_votes
├── id (PK)
├── role_id (FK)
├── location_type
├── location_id
├── target_player_id (FK)
├── initiated_by_player_id (FK)
├── status (active/passed/failed)
├── votes_for
├── votes_against
├── started_at
├── ends_at
└── created_at

charters
├── id (PK)
├── settlement_name
├── issuer_type (castle/kingdom)
├── issuer_id
├── founder_player_id (FK)
├── location_x
├── location_y
├── tax_rate
├── tax_cadence
├── protection_terms (JSON)
├── revocation_rules (JSON)
├── status (pending/active/revoked/expired)
├── issued_at
├── expires_at
└── created_at

taxes
├── id (PK)
├── payer_type (player/village/castle)
├── payer_id
├── receiver_type (village/castle/kingdom)
├── receiver_id
├── amount
├── due_at
├── paid_at
├── status (pending/paid/overdue)
└── created_at

messages
├── id (PK)
├── sender_player_id (FK)
├── channel_type (location/allegiance/private)
├── channel_id
├── recipient_player_id (FK, nullable, for PMs)
├── content
├── sent_at
└── created_at

travel_log
├── id (PK)
├── player_id (FK)
├── from_type
├── from_id
├── to_type
├── to_id
├── started_at
├── arrived_at
├── cancelled_at
└── created_at

banks
├── id (PK)
├── owner_type (player/village/castle/kingdom)
├── owner_id
├── gold_balance
└── updated_at

bank_transactions
├── id (PK)
├── bank_id (FK)
├── transaction_type (deposit/withdraw/tax/salary/trade)
├── amount
├── description
├── created_at
└── related_entity_type
└── related_entity_id
```

---

## 11. Laravel Architecture

### Directory Structure

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── Auth/
│   │   │   ├── RegisterController.php
│   │   │   ├── LoginController.php
│   │   │   └── VerificationController.php
│   │   ├── PlayerController.php
│   │   ├── VillageController.php
│   │   ├── CastleController.php
│   │   ├── KingdomController.php
│   │   ├── CombatController.php
│   │   ├── InventoryController.php
│   │   ├── JobController.php
│   │   ├── TradeController.php
│   │   ├── CraftingController.php
│   │   ├── SkillController.php
│   │   ├── GatheringController.php
│   │   ├── TravelController.php
│   │   ├── ElectionController.php
│   │   ├── CharterController.php
│   │   ├── ChatController.php
│   │   ├── RoleController.php
│   │   ├── ReligionController.php
│   │   └── JailController.php
│   ├── Middleware/
│   │   ├── EnsureEmailVerified.php
│   │   ├── EnsureNotTraveling.php
│   │   └── EnsureHasEnergy.php
│   └── Requests/
│       └── (Form requests for validation)
├── Models/
│   ├── Player.php
│   ├── Kingdom.php
│   ├── Castle.php
│   ├── Village.php
│   ├── Role.php
│   ├── Job.php
│   ├── Item.php
│   ├── Monster.php
│   ├── CombatSession.php
│   ├── Election.php
│   ├── Charter.php
│   ├── Message.php
│   ├── Bank.php
│   ├── Religion.php
│   ├── Belief.php
│   ├── ReligiousStructure.php
│   ├── HolySite.php
│   └── Prisoner.php
├── Services/
│   ├── CombatService.php
│   ├── EnergyService.php
│   ├── TravelService.php
│   ├── TaxService.php
│   ├── ElectionService.php
│   ├── CharterService.php
│   ├── CraftingService.php
│   ├── SkillService.php
│   ├── GatheringService.php
│   ├── DocketService.php
│   ├── ReligionService.php
│   ├── FaithService.php
│   ├── JailService.php
│   └── LootService.php
├── Jobs/
│   ├── RegenerateEnergy.php
│   ├── CollectTaxes.php
│   ├── ProcessElection.php
│   ├── CompleteTravels.php
│   └── CheckSettlementViability.php
├── Events/
│   ├── CombatTurnCompleted.php
│   ├── ElectionCompleted.php
│   ├── PlayerLeveledUp.php
│   └── SettlementFounded.php
└── Listeners/
    └── (Event listeners)
```

### Key Services

**CombatService.php**
- `startCombat(Player, Monster)`
- `processPlayerTurn(CombatSession, action, itemId?)`
- `processMonsterTurn(CombatSession)`
- `calculateDamage(attacker, defender)`
- `applyWeaponEffectiveness(weapon, monsterType)`
- `endCombat(CombatSession, outcome)`
- `distributeLoot(Player, Monster)`

**EnergyService.php**
- `hasEnergy(Player, amount)`
- `consumeEnergy(Player, amount)`
- `regenerateEnergy(Player)`
- `setEnergyOnDeath(Player)`

**TravelService.php**
- `calculateTravelTime(from, to)`
- `startTravel(Player, destination)`
- `cancelTravel(Player)`
- `completeTravel(Player)`

**TaxService.php**
- `calculateTax(entity, rate)`
- `collectVillageTaxes(Castle)`
- `collectCastleTaxes(Kingdom)`
- `distributeSalaries(location)`

**SkillService.php**
- `getSkillLevel(Player, skillName)`
- `addXp(Player, skillName, amount)`
- `calculateXpForLevel(level)`
- `calculateLevelFromXp(xp)`
- `getCombatLevel(Player)` - Formula: `floor((ATK + STR + DEF) / 3)`

**GatheringService.php**
- `canGather(Player, GatheringNode)`
- `gather(Player, GatheringNode)`
- `calculateGatherSuccess(playerLevel, nodeLevel)`
- `respawnNode(GatheringNode)`

**DocketService.php**
- `createOrder(customer, recipe, materialsSource)`
- `assignToCrafter(Order, Player)`
- `completeOrder(Order, crafter)`
- `checkTardiness(Order)` - Flag overdue orders
- `notifyAuthorities(Order)` - Alert lord/elder of tardy crafter
- `reassignToNpc(Order)`

---

## 12. API Endpoints

### Authentication
```
POST   /api/register
POST   /api/login
POST   /api/logout
POST   /api/email/verify/{id}/{hash}
POST   /api/email/resend
GET    /api/user
```

### Player
```
GET    /api/player
GET    /api/player/stats
GET    /api/player/inventory
POST   /api/player/equip/{itemId}
POST   /api/player/unequip/{slot}
GET    /api/player/bank
```

### Locations
```
GET    /api/villages
GET    /api/villages/{id}
GET    /api/villages/{id}/residents
GET    /api/villages/{id}/jobs
GET    /api/villages/{id}/roles

GET    /api/castles
GET    /api/castles/{id}
GET    /api/castles/{id}/villages

GET    /api/kingdoms
GET    /api/kingdoms/{id}
GET    /api/kingdoms/{id}/castles
```

### Jobs & Roles
```
GET    /api/jobs
POST   /api/jobs/{id}/signup
POST   /api/jobs/{id}/work
POST   /api/jobs/{id}/quit

GET    /api/roles
POST   /api/roles/{id}/nominate
```

### Skills & Gathering
```
GET    /api/skills                          # Get player's skill levels
GET    /api/skills/{skill}                  # Get specific skill details

GET    /api/gathering/nodes                 # Get nearby gathering nodes
POST   /api/gathering/mine/{nodeId}         # Mine ore node
POST   /api/gathering/fish/{nodeId}         # Fish at fishing spot
```

### Combat
```
POST   /api/combat/start                    # Requires: training_style (attack/strength/defense)
GET    /api/combat/session
POST   /api/combat/action
POST   /api/combat/flee

GET    /api/dungeons
GET    /api/dungeons/{id}
POST   /api/dungeons/{id}/enter
POST   /api/dungeons/instance/next-floor
POST   /api/dungeons/instance/leave
```

### Travel
```
GET    /api/map
GET    /api/travel/time?to={locationId}&toType={type}
POST   /api/travel/start
POST   /api/travel/cancel
GET    /api/travel/status
```

### Elections
```
GET    /api/elections
GET    /api/elections/{id}
POST   /api/elections/{id}/nominate
POST   /api/elections/{id}/vote
POST   /api/elections/no-confidence
```

### Trading & Crafting
```
GET    /api/market
POST   /api/market/list
POST   /api/market/buy/{listingId}
POST   /api/trade/offer
POST   /api/trade/accept/{offerId}

GET    /api/crafting/recipes
POST   /api/crafting/craft                  # Personal crafting (if you have skill)

# NPC Crafting
POST   /api/crafting/npc/order              # Order from NPC (instant, costs gold)

# Player Crafter Docket System
GET    /api/docket                          # View your docket (if you're a crafter role)
GET    /api/docket/orders                   # Orders waiting for you
POST   /api/docket/orders/{id}/work         # Complete an order (costs energy, gains XP)
POST   /api/docket/orders/{id}/decline      # Decline an order

POST   /api/crafting/order                  # Submit order to player crafter
GET    /api/crafting/order/{id}/status      # Check order status
```

### Chat
```
GET    /api/chat/{channelType}/{channelId}
POST   /api/chat/{channelType}/{channelId}
GET    /api/chat/private/{playerId}
POST   /api/chat/private/{playerId}
```

### Charters
```
GET    /api/charters
POST   /api/charters/request
GET    /api/charters/{id}
POST   /api/charters/{id}/approve
POST   /api/charters/{id}/found
```

---

## 13. React Architecture

### Directory Structure

```
src/
├── components/
│   ├── common/
│   │   ├── Button.tsx
│   │   ├── Card.tsx
│   │   ├── Modal.tsx
│   │   ├── ProgressBar.tsx (HP, Energy, XP)
│   │   ├── Navbar.tsx
│   │   ├── Sidebar.tsx
│   │   └── Loading.tsx
│   ├── player/
│   │   ├── PlayerStats.tsx
│   │   ├── Inventory.tsx
│   │   ├── InventorySlot.tsx
│   │   ├── EquipmentPanel.tsx
│   │   └── BankPanel.tsx
│   ├── location/
│   │   ├── VillageView.tsx
│   │   ├── TownView.tsx
│   │   ├── CastleView.tsx
│   │   ├── KingdomView.tsx
│   │   ├── WildernessView.tsx
│   │   ├── DungeonView.tsx
│   │   ├── LocationHeader.tsx
│   │   ├── ResidentsList.tsx
│   │   └── RolesList.tsx
│   ├── combat/
│   │   ├── CombatScreen.tsx
│   │   ├── MonsterDisplay.tsx
│   │   ├── CombatActions.tsx
│   │   ├── CombatLog.tsx
│   │   └── LootModal.tsx
│   ├── jobs/
│   │   ├── JobBoard.tsx
│   │   ├── JobCard.tsx
│   │   └── WorkButton.tsx
│   ├── elections/
│   │   ├── ElectionPanel.tsx
│   │   ├── CandidateCard.tsx
│   │   ├── VotingBooth.tsx
│   │   └── NoConfidenceModal.tsx
│   ├── travel/
│   │   ├── MapView.tsx
│   │   ├── TravelPanel.tsx
│   │   └── TravelProgress.tsx
│   ├── chat/
│   │   ├── ChatPanel.tsx
│   │   ├── ChatMessage.tsx
│   │   └── ChatInput.tsx
│   ├── trade/
│   │   ├── MarketView.tsx
│   │   ├── TradeOffer.tsx
│   │   └── ListingCard.tsx
│   ├── crafting/
│   │   ├── CraftingPanel.tsx
│   │   ├── RecipeCard.tsx
│   │   ├── CraftingResult.tsx
│   │   ├── DocketPanel.tsx
│   │   └── OrderCard.tsx
│   ├── skills/
│   │   ├── SkillsPanel.tsx
│   │   ├── SkillCard.tsx
│   │   ├── SkillProgressBar.tsx
│   │   └── CombatStyleSelector.tsx
│   └── gathering/
│       ├── GatheringPanel.tsx
│       ├── NodeCard.tsx
│       └── GatheringAction.tsx
├── pages/
│   ├── auth/
│   │   ├── Login.tsx
│   │   ├── Register.tsx
│   │   └── VerifyEmail.tsx
│   ├── Dashboard.tsx
│   ├── Village.tsx
│   ├── Castle.tsx
│   ├── Kingdom.tsx
│   ├── Combat.tsx
│   ├── Map.tsx
│   ├── Inventory.tsx
│   ├── Jobs.tsx
│   ├── Elections.tsx
│   ├── Market.tsx
│   ├── Crafting.tsx
│   ├── Skills.tsx
│   ├── Gathering.tsx
│   ├── Dungeon.tsx
│   ├── Religion.tsx
│   ├── Jail.tsx
│   └── Profile.tsx
├── hooks/
│   ├── usePlayer.ts
│   ├── useEnergy.ts
│   ├── useCombat.ts
│   ├── useLocation.ts
│   ├── usePolling.ts
│   ├── useSkills.ts
│   ├── useGathering.ts
│   ├── useDocket.ts
│   ├── useReligion.ts
│   ├── useFaith.ts
│   └── useChat.ts
├── services/
│   ├── api.ts (axios instance)
│   ├── auth.ts
│   ├── player.ts
│   ├── combat.ts
│   ├── locations.ts
│   ├── jobs.ts
│   ├── elections.ts
│   └── chat.ts
├── context/
│   ├── AuthContext.tsx
│   ├── PlayerContext.tsx
│   └── GameContext.tsx
├── types/
│   ├── player.ts
│   ├── location.ts
│   ├── combat.ts
│   ├── item.ts
│   └── election.ts
└── utils/
    ├── formatters.ts
    ├── calculations.ts
    └── constants.ts
```

### State Management
- **React Context** for global state (auth, player, current location)
- **React Query** or SWR for server state and caching
- Local component state for UI-specific state

---

## 14. Background Jobs (Laravel Scheduler)

```php
// app/Console/Kernel.php

protected function schedule(Schedule $schedule)
{
    // Energy regeneration - every 5 minutes
    $schedule->job(new RegenerateEnergy)->everyFiveMinutes();

    // Tax collection - daily
    $schedule->job(new CollectTaxes)->daily();

    // Process completed travels - every minute
    $schedule->job(new CompleteTravels)->everyMinute();

    // Check election deadlines - every hour
    $schedule->job(new ProcessElections)->hourly();

    // Settlement viability checks - daily
    $schedule->job(new CheckSettlementViability)->daily();

    // Salary distribution - daily
    $schedule->job(new DistributeSalaries)->daily();

    // NPC role filling - hourly
    $schedule->job(new FillVacantRoles)->hourly();
}
```

---

## 15. MVP Milestones

### Phase 1: Foundation
- [ ] Laravel project setup with PostgreSQL
- [ ] React + Tailwind setup with Vite
- [ ] Authentication (register, login, email verify)
- [ ] Player model and basic stats
- [ ] Database migrations for core entities

### Phase 2: World
- [ ] Village, Castle, Kingdom models
- [ ] Map with 50 villages, 10 castles, 4 kingdoms
- [ ] Location views (see where you are)
- [ ] Resident lists

### Phase 3: Player Basics
- [ ] Energy system with regeneration
- [ ] Inventory system (28 slots)
- [ ] Items and equipment
- [ ] HP display

### Phase 4: Skills System
- [ ] 8 skills with XP tracking
- [ ] XP formula implementation (L² × 60)
- [ ] Skill level calculations
- [ ] Combat level derived from ATK + STR + DEF
- [ ] Skill UI components

### Phase 5: Jobs & Economy
- [ ] Job system
- [ ] Work action (costs energy, pays gold)
- [ ] Bank system
- [ ] Basic trading

### Phase 6: Combat
- [ ] Monster entities
- [ ] Turn-based combat engine
- [ ] Combat style selection (train ATK/STR/DEF)
- [ ] Weapon effectiveness
- [ ] Loot drops
- [ ] Death/respawn

### Phase 7: Gathering
- [ ] Mining nodes in wilderness
- [ ] Fishing spots
- [ ] Gathering XP rewards
- [ ] Node respawning

### Phase 8: Travel
- [ ] Map navigation
- [ ] Travel time + energy cost calculation
- [ ] Travel cancellation
- [ ] Arrival handling

### Phase 9: Roles & Politics
- [ ] Role system
- [ ] Role assignment
- [ ] NPC fallback for vacant roles
- [ ] Basic permissions

### Phase 10: Elections
- [ ] Election creation
- [ ] Nomination period
- [ ] Voting
- [ ] Winner assignment
- [ ] No confidence votes

### Phase 11: Taxes & Allegiance
- [ ] Tax collection jobs
- [ ] Village → Castle flow
- [ ] Castle → Kingdom flow
- [ ] Allegiance switching

### Phase 12: Crafting & Docket
- [ ] Crafting recipes
- [ ] NPC crafting (instant, gold cost)
- [ ] Player crafter docket system
- [ ] Order submission and completion
- [ ] Tardiness monitoring

### Phase 13: Advanced Features
- [ ] Chat system
- [ ] Town upgrades
- [ ] Dungeons (instanced)
- [ ] Charter system for new settlements

---

## 16. Open Questions / TBD

### Resolved
- ~~Character creation~~ → Male/Female gender choice
- ~~Skill/ability system~~ → 8 skills with XP tracks, basic attack only in combat
- ~~Dungeons~~ → Instanced, multi-floor, eat between rounds, boss at end
- ~~Crafting recipes~~ → Level-locked based on skill level
- ~~Travel energy cost~~ → Both time AND energy
- ~~PvP duels~~ → Deferred (no PvP for now)
- ~~Training~~ → Combat skill training via style selection
- ~~Guilds/Clans~~ → Deferred to post-MVP
- ~~Boss mechanics~~ → Final floor of dungeon, best loot drops
- ~~Tardiness threshold~~ → 10 minutes, then authorities notified
- ~~Combat level formula~~ → `floor((ATK + STR + DEF) / 3)`, starts at 5

### Still Open
*All major systems now defined. Minor details to be decided during implementation.*

### Recently Resolved
- ~~Religion/culture system~~ → Full religion system with cults, temples, beliefs (see Section 18)
- ~~Seasons/weather~~ → No gameplay effect (visual only if any)
- ~~HP scaling~~ → Tied to ATK/DEF/STR levels

---

## 17. Technical Decisions Summary

| Decision | Choice |
|----------|--------|
| Backend | Laravel (Controllers + API) |
| Frontend | React SPA calling Laravel API |
| Database | PostgreSQL |
| Styling | Tailwind CSS |
| Real-time | Polling (combat uses short polling) |
| Auth | Session-based with Sanctum |
| Deployment | Laravel Forge |
| Mobile | Responsive web design |
| State | React Context + React Query |

---

## 18. Religion System

> "Religions create loyalty that competes with political loyalty."

Religions are **non-territorial power structures** that overlay villages, castles, and kingdoms. A player may have to choose between their King and their God.

### Cult vs Religion

| Type | Followers | Cost to Found | Beliefs | Structures | Visibility |
|------|-----------|---------------|---------|------------|------------|
| **Cult** | 5+ players | Free | 2 | None (secret) | Hidden |
| **Religion** | 15+ players | 100,000 gold | Up to 5 | Shrines, Temples, Cathedrals | Public |

### Cult Characteristics
- **Secretive** - Hidden membership
- **Powerful** - Unique dark bonuses
- **Dangerous** - High-risk, high-reward
- **Illegal rituals** - Can be hunted
- **Infiltrate governments** - Members can hold political roles secretly
- Can go public → become Religion

### Founding

**Cult:**
- 5 players agree to form
- No gold cost
- Choose 2 beliefs from preset list
- Operates in secret

**Religion (upgrade from cult):**
- 15+ followers
- 100,000 gold
- Founder decides to go public OR cult members vote
- Must request permission from location to build first structure

### Religious Ranks

| Rank | Role |
|------|------|
| Prophet/Founder | Supreme leader, sets doctrine |
| High Priest | Second in command, leads rituals |
| Priest | Conducts ceremonies, converts followers |
| Acolyte | Junior clergy, assists priests |
| Follower | Regular member |

**Succession (if founder quits):**
1. Next in rank hierarchy takes over
2. If no ranked members, oldest member by join date

### Beliefs (Preset List from Medieval Lore)

Beliefs provide bonuses AND penalties. Founder picks from predefined list.

**Combat-focused:**
| Belief | Bonus | Penalty |
|--------|-------|---------|
| Blood Oath | +15% damage vs humans | -10% healing received |
| Pacifist Creed | +20% XP from gathering | -20% combat XP |
| Warrior's Path | +10% combat XP | -10% crafting XP |

**Economy-focused:**
| Belief | Bonus | Penalty |
|--------|-------|---------|
| Merchant Faith | +10% trade profits | -5% combat stats |
| Ascetic Vow | +15% crafting XP | Cannot own more than 1,000 gold |
| Laborer's Blessing | +10% job wages | -5% combat XP |

**Settlement-focused:**
| Belief | Bonus | Penalty |
|--------|-------|---------|
| Oak Faith | +10% wood yield | -5% mining yield |
| Forge God | +15% smithing speed | -10% farming output |
| Harvest Lord | +15% food production | -10% combat stats |

**Dark/Cult-only:**
| Belief | Bonus | Penalty |
|--------|-------|---------|
| Shadow Pact | Invisible to other players while traveling | -25% max HP |
| Death Cult | Keep 50% gold on death | -20% max energy |
| Blood Sacrifice | +25% damage | Lose 5 HP per combat |

**Adding beliefs later:** Yes, but must be ratified by membership vote.

### Faith Points

**Earning Faith:**
| Action | Faith Gained |
|--------|--------------|
| Praying (daily, costs energy) | 5-10 |
| Converting a new follower | 25 |
| Donating gold | 1 per 100 gold |
| Building structures | 100-1000 |
| Participating in rituals | 20-50 |
| Pilgrimages (visiting holy sites) | 50 |
| Sacrifices (items destroyed) | Varies by item value |

**Using Faith (Individual):**
- Personal buffs (temporary, costs faith)
- Reduce death penalty (costs faith)

**Using Faith (Pooled/Religion-wide):**
- Build structures (shrines, temples, cathedrals)
- Expand influence radius
- Declare holy wars

### Religious Structures

| Structure | Gold Cost | Faith Cost | Requirements | Effect |
|-----------|-----------|------------|--------------|--------|
| Shrine | 50,000 | 500 | 5 followers in location | Small buff to followers nearby |
| Temple | 500,000 | 2,500 | 20 followers + village/castle approval | Settlement-wide buff |
| Cathedral | 5,000,000 | 10,000 | 100 followers + kingdom approval | Kingdom-wide buff, unlocks grand rituals |

**Building requires:** Location approval (Elder/Lord/King must permit)

### Political Interactions

**State Religion:**
When a kingdom adopts a religion as official:

*Benefits:*
- Tax bonus from religious followers
- Happiness boost in kingdom
- Free temple construction
- Religious leaders get political roles

*Costs:*
- Must enforce beliefs kingdom-wide

**Banning a Religion:**
When a King bans a religion, multiple effects trigger:

| Effect | Description |
|--------|-------------|
| Expulsion | Followers must leave kingdom or convert |
| Underground | Religion becomes a cult in that kingdom |
| Persecution | Followers get debuffs, can be **arrested** (Jailsman role) |
| Holy War | May trigger religious conflict |

### Jail System (Related)

Castles have **jail cells** for:
- Criminal players
- Persecuted religious followers
- Arrested cult members

**Jailsman role** manages prisoners.

### Schisms & Leaving

- Players can leave a religion at any time
- No formal schism mechanic (just leave and start new cult if desired)
- Leaving may incur cooldown before joining another religion

### Database Schema (Religion Tables)

```
religions
├── id (PK)
├── name (unique)
├── type (cult/religion)
├── founder_player_id (FK)
├── description
├── is_public (boolean)
├── founded_at
├── upgraded_at (cult → religion)
├── total_faith_pool
├── influence_radius
└── created_at

religion_beliefs
├── id (PK)
├── religion_id (FK)
├── belief_id (FK)
├── added_at
├── ratified_by_vote (boolean)
└── created_at

beliefs
├── id (PK)
├── name
├── description
├── category (combat/economy/settlement/dark)
├── bonus_type
├── bonus_value
├── penalty_type
├── penalty_value
├── cult_only (boolean)
└── created_at

religion_members
├── id (PK)
├── religion_id (FK)
├── player_id (FK)
├── rank (prophet/high_priest/priest/acolyte/follower)
├── faith_contributed
├── joined_at
├── is_hidden (for cults)
└── created_at

religious_structures
├── id (PK)
├── religion_id (FK)
├── type (shrine/temple/cathedral)
├── location_type (village/castle/kingdom)
├── location_id
├── built_at
├── is_active
└── created_at

faith_transactions
├── id (PK)
├── religion_id (FK)
├── player_id (FK, nullable)
├── type (prayer/conversion/donation/structure/ritual/pilgrimage/sacrifice)
├── amount
├── description
└── created_at

holy_sites
├── id (PK)
├── name
├── religion_id (FK, nullable - can be neutral)
├── location_x
├── location_y
├── faith_reward
└── created_at

state_religions
├── id (PK)
├── kingdom_id (FK)
├── religion_id (FK)
├── adopted_at
├── is_enforced
└── created_at

banned_religions
├── id (PK)
├── kingdom_id (FK)
├── religion_id (FK)
├── banned_at
├── ban_effects (JSON: expelled, underground, persecution, holy_war)
└── created_at

prisoners
├── id (PK)
├── player_id (FK)
├── castle_id (FK)
├── jailsman_player_id (FK)
├── reason (crime/religious_persecution/other)
├── arrested_at
├── release_at
├── released_at
└── created_at
```

### API Endpoints (Religion)

```
# Religions
GET    /api/religions
GET    /api/religions/{id}
POST   /api/religions/cult/found              # Found a cult
POST   /api/religions/{id}/upgrade            # Upgrade cult to religion
POST   /api/religions/{id}/join
POST   /api/religions/{id}/leave

# Membership & Ranks
GET    /api/religions/{id}/members
POST   /api/religions/{id}/promote/{playerId}
POST   /api/religions/{id}/demote/{playerId}

# Beliefs
GET    /api/beliefs                           # Get all available beliefs
POST   /api/religions/{id}/beliefs/add
POST   /api/religions/{id}/beliefs/ratify/{beliefId}

# Faith
GET    /api/religions/{id}/faith
POST   /api/religions/{id}/pray
POST   /api/religions/{id}/donate
POST   /api/religions/{id}/sacrifice

# Structures
GET    /api/religions/{id}/structures
POST   /api/religions/{id}/structures/request # Request to build
POST   /api/religions/{id}/structures/build

# Political
POST   /api/kingdoms/{id}/religion/adopt
POST   /api/kingdoms/{id}/religion/ban
GET    /api/religions/{id}/holy-sites

# Jail
GET    /api/castles/{id}/jail
POST   /api/castles/{id}/jail/arrest/{playerId}
POST   /api/castles/{id}/jail/release/{prisonerId}
```

### React Components (Religion)

```
├── religion/
│   ├── ReligionPanel.tsx
│   ├── ReligionCard.tsx
│   ├── BeliefsList.tsx
│   ├── BeliefCard.tsx
│   ├── MembersList.tsx
│   ├── RankBadge.tsx
│   ├── FaithDisplay.tsx
│   ├── PrayButton.tsx
│   ├── StructureCard.tsx
│   ├── FoundCultModal.tsx
│   ├── UpgradeReligionModal.tsx
│   └── HolySiteCard.tsx
├── jail/
│   ├── JailPanel.tsx
│   ├── PrisonerCard.tsx
│   └── ArrestModal.tsx
```

---

## 19. MVP Milestones (Updated)

Added Phase 14 for Religion System:

### Phase 14: Religion System
- [ ] Cult founding (5 players, free, secret)
- [ ] Belief system with preset list
- [ ] Faith points (earning and spending)
- [ ] Religion upgrade (100K gold, 15 players)
- [ ] Religious structures (shrines, temples, cathedrals)
- [ ] Religious ranks and succession
- [ ] State religion adoption
- [ ] Religion banning and persecution
- [ ] Jail system for prisoners

---

*This document is a living specification. Update as decisions are made.*
