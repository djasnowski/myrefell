# Myrefell - Product Requirements Document

## Project Overview

Myrefell is a persistent browser-based medieval game (PBBG) where players start as peasants and rise through feudal hierarchy via elections, economy, and politics.

**Tech Stack:** Laravel 12 + React 19 + Inertia.js + PostgreSQL + Redis + Tailwind CSS

---

## Implemented Features

### Core Systems (Complete)

| System | Status | Notes |
|--------|--------|-------|
| Authentication | Done | Register, login, email verify, 2FA |
| Player Stats | Done | HP, energy, gold, combat level |
| Inventory | Done | 28 slots, equipment, items |
| Skills | Done | 9 skills with XP tracking |
| Energy | Done | Regeneration every 5 minutes |

### World (Complete)

| System | Status | Notes |
|--------|--------|-------|
| Kingdoms | Done | 4 kingdoms with biomes |
| Towns | Done | Upgraded villages with mayors |
| Castles | Done | Military centers |
| Villages | Done | Population hubs |
| World Map | Done | Interactive map on dashboard |
| Ports | Done | Ship travel between kingdoms (5000 gold) |

### Gameplay (Complete)

| System | Status | Notes |
|--------|--------|-------|
| Gathering | Done | Mining, fishing, woodcutting, foraging |
| Crafting | Done | Smithing, cooking, crafting recipes |
| Travel | Done | Time-based movement between locations |
| Bank | Done | Per-location accounts, deposits, withdrawals |
| Healer | Done | HP restoration for gold |
| Quests | Done | Notice board, quest log, rewards |
| Daily Tasks | Done | 3 daily tasks with rewards |
| Elections | Done | Village roles, mayors, kings |

---

## Not Implemented (Priority Order)

### High Priority - Core Gameplay Loop

#### 1. Jobs System
Employment system for economic gameplay.

**Requirements:**
- Jobs model: name, description, location_type, energy_cost, base_wage, xp_reward
- PlayerJobs model: tracks who works what job where
- Jobs available: Cook, Cleaner, Stable Hand, Farmhand, Miner, Lumberjack
- Work action: costs energy, pays gold, awards XP
- Supervisor positions get % of workers under them

**Files to create:**
- database/migrations/create_jobs_table.php
- database/migrations/create_player_jobs_table.php
- app/Models/Job.php
- app/Models/PlayerJob.php
- app/Services/JobService.php
- app/Http/Controllers/JobController.php
- resources/js/pages/Jobs/Index.tsx
- database/seeders/JobSeeder.php

#### 2. Village Roles System
Elected/appointed positions with NPC fallbacks.

**Requirements:**
- Roles model: name, location_type, permissions (JSON), bonuses (JSON), salary
- PlayerRoles model: who holds what role where
- LocationNpcs model: NPC fallbacks when no player holds role
- Village roles: Elder, Blacksmith, Merchant, Guard Captain, Healer
- Castle roles: Lord, Steward, Marshal, Treasurer, Jailsman
- Kingdom roles: King, Chancellor, General, Royal Treasurer

**Files to create:**
- database/migrations/create_roles_table.php
- database/migrations/create_player_roles_table.php
- database/migrations/create_location_npcs_table.php
- app/Models/Role.php
- app/Models/PlayerRole.php
- app/Models/LocationNpc.php
- app/Services/RoleService.php
- app/Http/Controllers/RoleController.php
- resources/js/pages/Roles/Index.tsx
- database/seeders/RoleSeeder.php

#### 3. No Confidence Votes
Complete the election system.

**Requirements:**
- NoConfidenceVote model: target_player_id, initiated_by, votes_for, votes_against, status
- Any resident can initiate against role holder
- 48-hour voting period
- Majority required to remove
- On success, role becomes vacant (triggers new election or NPC)

**Files to create:**
- database/migrations/create_no_confidence_votes_table.php
- app/Models/NoConfidenceVote.php
- Add methods to ElectionService.php
- resources/js/pages/Elections/NoConfidence.tsx

### Medium Priority - Economy & Social

#### 4. Tax System
Economic flow between locations.

**Requirements:**
- Taxes model: payer, receiver, amount, status
- Tax rates configurable by Lord (castle) and King (kingdom)
- Flow: Players -> Villages -> Castles -> Kingdoms
- Daily tax collection job
- Salary distribution for role holders

**Files to create:**
- database/migrations/create_taxes_table.php
- app/Models/Tax.php
- app/Services/TaxService.php
- app/Jobs/CollectTaxes.php
- app/Jobs/DistributeSalaries.php

#### 5. Chat System
Location-based and private messaging.

**Requirements:**
- Messages model: sender, channel_type, channel_id, content
- Channel types: location (village/castle/kingdom), private (player-to-player)
- Polling-based (not WebSocket)
- Moderation tools for Elders/Lords/Kings

**Files to create:**
- database/migrations/create_messages_table.php
- app/Models/Message.php
- app/Services/ChatService.php
- app/Http/Controllers/ChatController.php
- resources/js/pages/Chat/Index.tsx
- resources/js/components/chat/ChatPanel.tsx

#### 6. NPC Crafting & Docket System
Player crafter queues and NPC instant crafting.

**Requirements:**
- CraftingOrders model: customer, crafter (nullable for NPC), recipe, status
- NPC crafting: instant, costs gold, no XP
- Player crafter docket: queued orders, 10-minute tardiness threshold
- LocationStockpiles model: materials available at location

**Files to create:**
- database/migrations/create_crafting_orders_table.php
- database/migrations/create_location_stockpiles_table.php
- app/Models/CraftingOrder.php
- app/Models/LocationStockpile.php
- app/Services/DocketService.php
- resources/js/pages/Crafting/Docket.tsx

### Lower Priority - Advanced Features

#### 7. Combat System
Turn-based monster combat.

**Requirements:**
- Monsters model: name, type, stats, biome, xp_reward, gold_drop
- MonsterLootTables model: drop chances per monster
- CombatSessions model: player vs monster state
- CombatLogs model: turn-by-turn record
- Training style selection (ATK/STR/DEF)
- Weapon effectiveness vs enemy types
- Death respawns at hospital with 25% energy

**Files to create:**
- database/migrations/create_monsters_table.php
- database/migrations/create_monster_loot_tables_table.php
- database/migrations/create_combat_sessions_table.php
- database/migrations/create_combat_logs_table.php
- app/Models/Monster.php, MonsterLootTable.php, CombatSession.php, CombatLog.php
- app/Services/CombatService.php
- app/Services/LootService.php
- app/Http/Controllers/CombatController.php
- resources/js/pages/Combat/Index.tsx
- database/seeders/MonsterSeeder.php

#### 8. Dungeons
Instanced multi-floor content.

**Requirements:**
- Dungeons model: name, min_combat_level, total_floors, location
- DungeonInstances model: player's current dungeon state
- Multiple monster fights per floor
- Eat food between rounds to restore HP
- Boss fight on final floor with best loot

**Files to create:**
- database/migrations/create_dungeons_table.php
- database/migrations/create_dungeon_instances_table.php
- app/Models/Dungeon.php
- app/Models/DungeonInstance.php
- app/Services/DungeonService.php
- resources/js/pages/Dungeon/Index.tsx
- database/seeders/DungeonSeeder.php

#### 9. Religion System
Cults and religions with beliefs.

**Requirements:**
- Religions model: name, type (cult/religion), founder, is_public
- Beliefs model: preset bonuses/penalties
- ReligionMembers model: ranks (Prophet, Priest, Follower)
- ReligiousStructures model: shrines, temples, cathedrals
- Cults: 5 players, free, secret, 2 beliefs
- Religions: 15 players, 100K gold, public, up to 5 beliefs
- State religion adoption and banning by kingdoms

#### 10. Charter System
Founding new settlements.

**Requirements:**
- Charters model: settlement_name, issuer, founder, tax_terms
- 1,000,000+ gold cost
- Location approval required
- Vulnerability window after founding
- Failed settlements become ruins

---

## Development Guidelines

### File Patterns

**Models:** `app/Models/{Name}.php`
**Services:** `app/Services/{Name}Service.php`
**Controllers:** `app/Http/Controllers/{Name}Controller.php`
**Pages:** `resources/js/pages/{Feature}/Index.tsx`
**Seeders:** `database/seeders/{Name}Seeder.php`

### Commands

```bash
# Run tests
sail artisan test

# Type check frontend
npm run build

# Format code
composer lint && npm run lint
```

### Coding Standards

- Use Inertia.js for all pages (not API routes)
- Services handle business logic, controllers are thin
- All money operations use BankService
- All XP operations go through skill models
- Use existing UI components from resources/js/components/ui/

---

## Current Sprint

**Next feature to implement:** Jobs System

This provides the economic foundation for:
- Player income beyond quests/daily tasks
- Village economy and employment
- Supervisor hierarchy
- Foundation for tax collection
