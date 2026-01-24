# Myrefell Implementation Progress

Based on PLANNING.md as of January 2026.

---

## Legend
- [x] Implemented
- [~] Partially Implemented
- [ ] Not Started

---

## Phase 1: Foundation
- [x] Laravel project setup with PostgreSQL
- [x] React + Tailwind setup with Vite (Inertia.js)
- [x] Authentication (register, login, email verify, 2FA)
- [x] Player model and basic stats (HP, energy, gold)
- [x] Database migrations for core entities

---

## Phase 2: World
- [x] Village, Castle, Kingdom models
- [x] Town model (upgraded villages)
- [x] Map with villages, castles, kingdoms (dashboard.tsx)
- [x] Location views (see where you are)
- [x] Resident lists (villages/residents)
- [x] Port system for cross-kingdom travel

**Models:** Kingdom, Castle, Town, Village

**Pages:**
- kingdoms/index, kingdoms/show
- castles/index, castles/show
- villages/index, villages/show, Villages/Residents
- Towns/Show, Towns/Hall
- Port/Index, Port/NotHere

---

## Phase 3: Player Basics
- [x] Energy system with regeneration (EnergyService)
- [x] Inventory system (28 slots) - PlayerInventory model
- [x] Items and equipment - Item model with stats
- [x] HP display
- [x] Gold/currency

**Services:** EnergyService, InventoryService

**Pages:** inventory.tsx

---

## Phase 4: Skills System
- [x] 8 skills with XP tracking (PlayerSkill model)
- [x] XP formula implementation (L² × 60)
- [x] Skill level calculations
- [~] Combat level derived from ATK + STR + DEF (formula exists, no combat yet)
- [~] Skill UI components (shown in gathering/crafting, no dedicated skills page)

**Models:** PlayerSkill

**Missing:**
- [ ] Dedicated Skills page/panel
- [ ] Combat skills actually used in combat

---

## Phase 5: Jobs & Economy
- [x] Bank system (BankService, BankController)
- [~] Basic trading (no market, just bank)
- [ ] Job system (employment jobs like Cook, Farmhand, etc.)
- [ ] Work action (costs energy, pays gold)

**Services:** BankService

**Pages:** Bank/Index, Bank/NotHere

**Missing:**
- [ ] Jobs model and employment system
- [ ] Job board UI
- [ ] Supervisor bonuses
- [ ] Wages system

---

## Phase 6: Combat
- [ ] Monster entities
- [ ] Turn-based combat engine
- [ ] Combat style selection (train ATK/STR/DEF)
- [ ] Weapon effectiveness
- [ ] Loot drops
- [ ] Death/respawn

**Status:** NOT STARTED

**Missing:**
- Monster model
- CombatSession model
- CombatService
- CombatController
- Combat UI pages
- Loot tables

---

## Phase 7: Gathering
- [x] Mining nodes
- [x] Fishing spots
- [x] Woodcutting
- [x] Foraging
- [x] Gathering XP rewards
- [~] Node respawning (basic cooldown system)

**Services:** GatheringService

**Pages:** Gathering/Index, Gathering/Activity, Gathering/NotAvailable

---

## Phase 8: Travel
- [x] Map navigation (dashboard with world map)
- [x] Travel time calculation
- [x] Energy cost for travel (removed - just time based now)
- [x] Travel cancellation
- [x] Arrival handling
- [x] Port/Ship travel for cross-kingdom (5000 gold)

**Services:** TravelService, PortService

**Pages:** Travel/Index, Port/Index

---

## Phase 9: Roles & Politics
- [x] Player titles system (PlayerTitle model)
- [~] Role system (elections grant titles, but no village roles like Blacksmith)
- [ ] NPC fallback for vacant roles
- [ ] Role permissions system

**Models:** PlayerTitle

**Missing:**
- [ ] Village roles (Elder, Blacksmith, Merchant, Guard Captain, Healer)
- [ ] Castle roles (Lord, Steward, Marshal, etc.)
- [ ] Kingdom roles (Chancellor, General, etc.)
- [ ] NPC role holders
- [ ] Role-based permissions

---

## Phase 10: Elections
- [x] Election creation
- [x] Nomination/candidacy period
- [x] Voting
- [x] Winner assignment
- [x] Mayor elections (towns)
- [x] King elections (kingdoms)
- [x] Village role elections
- [x] Self-appointment for small villages
- [ ] No confidence votes

**Models:** Election, ElectionCandidate, ElectionVote

**Services:** ElectionService

**Pages:** Elections/Index, Elections/Show, Towns/Hall

**Missing:**
- [ ] No confidence vote system
- [ ] Vote of no confidence UI

---

## Phase 11: Taxes & Allegiance
- [ ] Tax collection jobs
- [ ] Village → Castle tax flow
- [ ] Castle → Kingdom tax flow
- [ ] Allegiance switching (village switches castle)
- [ ] Castle switches kingdom

**Status:** NOT STARTED

---

## Phase 12: Crafting & Docket
- [x] Crafting recipes
- [x] Personal crafting (if you have skill)
- [ ] NPC crafting (instant, gold cost)
- [ ] Player crafter docket system
- [ ] Order submission and completion
- [ ] Tardiness monitoring

**Services:** CraftingService

**Pages:** Crafting/Index, Crafting/NotAvailable

**Missing:**
- [ ] NPC crafters at locations
- [ ] Docket/order queue system
- [ ] DocketService

---

## Phase 13: Advanced Features
- [ ] Chat system
- [ ] Town upgrades (village → town)
- [ ] Dungeons (instanced)
- [ ] Charter system for new settlements

**Status:** NOT STARTED

---

## Phase 14: Religion System
- [ ] Cult founding
- [ ] Belief system with preset list
- [ ] Faith points
- [ ] Religion upgrade
- [ ] Religious structures
- [ ] Religious ranks
- [ ] State religion adoption
- [ ] Religion banning
- [ ] Jail system for prisoners

**Status:** NOT STARTED

---

## Additional Features Implemented (Not in Original Plan)

### Quests
- [x] Quest model
- [x] PlayerQuest tracking
- [x] Notice board at villages
- [x] Quest log
- [x] Quest accept/abandon/claim

**Services:** QuestService

**Pages:** Quests/NoticeBoard, Quests/QuestLog, Quests/NotHere

### Daily Tasks
- [x] DailyTask model
- [x] PlayerDailyTask tracking
- [x] Daily task completion/claiming

**Services:** DailyTaskService

**Pages:** DailyTasks/Index

### Healer/Infirmary
- [x] Healing service at villages/castles/towns
- [x] HP restoration for gold

**Services:** HealerService

**Pages:** Healer/Index, Healer/NotHere

---

## Database Tables Summary

### Implemented
| Table | Status |
|-------|--------|
| users | [x] Complete |
| kingdoms | [x] Complete |
| towns | [x] Complete |
| castles | [x] Complete |
| villages | [x] Complete |
| items | [x] Complete |
| player_inventory | [x] Complete |
| player_skills | [x] Complete |
| player_titles | [x] Complete |
| elections | [x] Complete |
| election_candidates | [x] Complete |
| election_votes | [x] Complete |
| bank_accounts | [x] Complete |
| daily_tasks | [x] Complete |
| quests | [x] Complete |

### Not Implemented
| Table | Status |
|-------|--------|
| monsters | [ ] Not started |
| monster_loot_tables | [ ] Not started |
| combat_sessions | [ ] Not started |
| combat_logs | [ ] Not started |
| dungeons | [ ] Not started |
| dungeon_instances | [ ] Not started |
| gathering_nodes | [ ] Not started (hardcoded) |
| roles | [ ] Not started |
| player_roles | [ ] Not started |
| jobs | [ ] Not started |
| player_jobs | [ ] Not started |
| crafting_orders | [ ] Not started |
| location_npcs | [ ] Not started |
| location_stockpiles | [ ] Not started |
| no_confidence_votes | [ ] Not started |
| charters | [ ] Not started |
| taxes | [ ] Not started |
| messages | [ ] Not started |
| travel_log | [ ] Not started |
| religions | [ ] Not started |
| religion_beliefs | [ ] Not started |
| beliefs | [ ] Not started |
| religion_members | [ ] Not started |
| religious_structures | [ ] Not started |
| faith_transactions | [ ] Not started |
| holy_sites | [ ] Not started |
| state_religions | [ ] Not started |
| banned_religions | [ ] Not started |
| prisoners | [ ] Not started |

---

## Services Summary

### Implemented
- EnergyService
- InventoryService
- BankService
- HealerService
- GatheringService
- CraftingService
- QuestService
- DailyTaskService
- TravelService
- PortService
- ElectionService
- BirthService (player creation)

### Not Implemented
- CombatService
- LootService
- TaxService
- CharterService
- DocketService
- SkillService (XP adding is inline)
- ReligionService
- FaithService
- JailService

---

## Priority Recommendations

### High Priority (Core Gameplay Loop)
1. **Combat System** - Central to game, affects skills, loot, progression
2. **Monster/Loot Tables** - Required for combat
3. **Skills Page** - Players need to see their progress

### Medium Priority (Economy & Social)
4. **Jobs System** - Employment, wages, economy
5. **Village Roles** - Blacksmith, Healer roles with NPCs
6. **Tax System** - Economic flow between locations

### Lower Priority (Advanced Features)
7. **Dungeons** - Instanced content
8. **Chat System** - Social features
9. **Religion System** - Complex overlay system
10. **Charter System** - Settlement founding

---

*Last updated: January 24, 2026*
