# Myrefell PRD - Medieval World Simulation

## Vision

Myrefell is a persistent medieval world where players and NPCs live together as equals in a living simulation. Seasons change, economies fluctuate based on supply and demand, wars reshape borders, and families rise and fall across generations.

**Core Philosophy:** Balanced realism with themed casual gameplay. Fun over tedium, but key medieval elements create depth. NPCs are not static vendors but autonomous agents who marry, have children, age, die, trade, and migrate.

**Tech Stack:** Laravel 12 + React 19 + Inertia.js + PostgreSQL + Redis + Tailwind CSS

---

## PART 1: IMPLEMENTED FEATURES

### Core Player Systems

| Model | Service | Description |
|-------|---------|-------------|
| User | - | Player accounts with HP, energy, gold, combat level, home village |
| PlayerSkill | - | 9 skills with XP tracking and level progression |
| PlayerInventory | InventoryService | 28-slot inventory system with equipment slots |
| Item | - | Items with types, stats, requirements, stacking |
| PlayerTitle | - | Earned titles and achievements |
| BankAccount | BankService | Per-location bank accounts |
| BankTransaction | BankService | Deposit/withdrawal history |

### World & Geography

**Feudal Hierarchy:**
```
Kingdom (ruled by King)
└── Barony (ruled by Baron)
    ├── Town (ruled by Mayor) - urban centers with expanded services
    └── Village (ruled by Elder) - population hubs
        └── Hamlet - small settlements using parent village's services
```

| Model | Service | Description |
|-------|---------|-------------|
| Kingdom | - | 4 kingdoms with biomes, rulers, and treasuries |
| Barony | - | Administrative regions ruled by Barons, contain towns and villages |
| Town | - | Urban centers with mayors, belong to baronies |
| Village | - | Population hubs where players live, can have hamlets |
| - | PortService | Ship travel between kingdoms (5000 gold) |
| - | TravelService | Time-based movement between locations |

**Settlement Types:**
- **Barony:** Administrative region containing multiple settlements. Baron collects taxes and provides protection.
- **Town:** Larger settlement with mayor, expanded services (guilds, markets). Belongs to a barony.
- **Village:** Standard settlement with elder, basic services (bank, healer, jobs). Belongs to a barony.
- **Hamlet:** Small settlement governed by parent village's elder. Uses parent's bank/healer. No independent elections.
- **Independent Village:** Village with no barony allegiance. Self-governing but unprotected.

### Gathering & Crafting

| Model | Service | Description |
|-------|---------|-------------|
| - | GatheringService | Mining, fishing, woodcutting, foraging |
| - | CraftingService | Smithing, cooking, general crafting |
| CraftingOrder | DocketService | Player crafter queues and NPC instant crafting |
| LocationStockpile | DocketService | Materials available at locations |

### Quests & Daily Tasks

| Model | Service | Description |
|-------|---------|-------------|
| Quest | QuestService | Notice board quests with objectives |
| PlayerQuest | QuestService | Player quest progress tracking |
| DailyTask | DailyTaskService | 3 rotating daily tasks |
| PlayerDailyTask | DailyTaskService | Daily task completion tracking |

### Economy & Employment

| Model | Service | Description |
|-------|---------|-------------|
| EmploymentJob | JobService | Job types with wages and requirements |
| PlayerEmployment | JobService | Player job assignments and work history |
| TaxCollection | TaxService | Tax records from players to locations |
| LocationTreasury | TaxService | Village/barony/kingdom gold reserves |
| TreasuryTransaction | TaxService | Treasury income/expense records |
| SalaryPayment | TaxService | Role holder salary distribution |

### Governance & Politics

| Model | Service | Description |
|-------|---------|-------------|
| Role | RoleService | Village Elder, Blacksmith, Guard Captain, Baron, King, etc. |
| PlayerRole | RoleService | Who holds what role where |
| LocationNpc | RoleService | NPC fallbacks when no player holds role |
| Election | ElectionService | Election scheduling and management |
| ElectionCandidate | ElectionService | Candidacy declarations |
| ElectionVote | ElectionService | Vote tracking |
| NoConfidenceVote | ElectionService | Vote to remove role holders |
| NoConfidenceBallot | ElectionService | Individual no-confidence votes |

### Migration & Population

| Model | Service | Description |
|-------|---------|-------------|
| MigrationRequest | MigrationService | Player requests to change home village |
| - | BirthService | NPC birth and population management |

### Communication

| Model | Service | Description |
|-------|---------|-------------|
| Message | ChatService | Location-based and private messaging |

### Combat & Dungeons

| Model | Service | Description |
|-------|---------|-------------|
| Monster | CombatService | Creatures with stats, biomes, and loot tables |
| MonsterLootTable | LootService | Drop chances per monster |
| CombatSession | CombatService | Player vs monster combat state |
| CombatLog | CombatService | Turn-by-turn combat record |
| Dungeon | DungeonService | Multi-floor instanced content |
| DungeonFloor | DungeonService | Individual dungeon floors |
| DungeonFloorMonster | DungeonService | Monsters on each floor |
| DungeonSession | DungeonService | Player's active dungeon run |

### Religion & Faith

| Model | Service | Description |
|-------|---------|-------------|
| Religion | ReligionService | Cults (5 members, secret) and religions (15+, public) |
| Belief | ReligionService | Doctrine bonuses and penalties |
| ReligionMember | ReligionService | Prophet, Priest, Follower ranks |
| ReligiousStructure | ReligionService | Shrines, temples, cathedrals |
| KingdomReligion | ReligionService | State religion adoption/banning |
| ReligiousAction | ReligionService | Prayer, tithing, conversion |

### Settlements & Charters

| Model | Service | Description |
|-------|---------|-------------|
| Charter | CharterService | Settlement founding documents |
| CharterSignatory | CharterService | Charter co-signers |
| SettlementRuin | CharterService | Failed settlement locations |

### Horses & Travel

| Model | Service | Description |
|-------|---------|-------------|
| Horse | StableService | Horse breeds with stats |
| PlayerHorse | StableService | Player-owned horses |

### Services & Facilities

| Service | Description |
|---------|-------------|
| HealerService | HP restoration for gold |
| EnergyService | Energy regeneration (5 min intervals) |

---

## PART 2: MISSING SYSTEMS (Priority Order)

### Phase 1: Living World Foundation

#### 1. Calendar & Seasons System

The foundation for all time-based mechanics. Without a calendar, crops can't grow, NPCs can't age, and the world feels static.

**Core Mechanics:**
- 4 seasons x 12 weeks = 48-week year
- Time acceleration: 1 real day = 1 game week (configurable)
- Current date tracked globally, displayed in UI

**Seasonal Effects:**
- **Spring:** Planting season, muddy roads (+travel time), mild weather
- **Summer:** Growing season, fastest travel, drought risk
- **Autumn:** Harvest season, trade caravans active, fair weather
- **Winter:** Famine risk, frozen ports, combat penalties outdoors, disease spread

**Database Changes:**
- `world_state` table: current_year, current_season, current_week
- Season modifier tables for travel, agriculture, combat

**Integration Points:**
- TravelService: Apply seasonal travel time modifiers
- GatheringService: Seasonal resource availability
- Future agriculture: Planting and harvest windows

---

#### 2. NPC Lifecycle System

Transform NPCs from static quest-givers into living inhabitants who age, form relationships, and die.

**Age & Death:**
- NPCs have birth_year, die naturally at 50-80 years old
- Children (0-15), Adults (16-50), Elders (51+)
- Death creates role vacancies, triggers inheritance
- Weekly aging job updates all NPCs

**Marriage & Reproduction:**
- Unmarried adult NPCs seek partners in same location
- Marriage produces 1-4 children over time
- Children grow up and become workers
- Family names and dynasties tracked

**Jobs & Migration:**
- NPCs hold jobs (same system as players)
- Unemployed NPCs may migrate to locations with jobs
- NPCs with family ties less likely to migrate
- Population naturally balances across settlements

**Personality System:**
- Traits: Greedy, Generous, Ambitious, Content, Aggressive, Peaceful
- Traits affect: prices, quest rewards, marriage choices, migration

**Database Changes:**
- `npcs` table: name, birth_year, death_year, gender, home_village_id, spouse_id, personality_traits
- `npc_families` table: npc_id, parent1_id, parent2_id
- `npc_jobs` table: mirrors player employment

---

#### 3. Food & Agriculture System

Create meaningful resource scarcity through food production and consumption.

**Food Consumption (Simplified):**
- Each person (player + NPC) consumes 1 food unit per week
- Locations have granaries storing food
- When food runs out: starvation penalties, emigration, death

**Crop System:**
- Fields belong to villages
- Crops: Wheat (spring plant, autumn harvest), Barley, Vegetables, Fruits
- Planting requires seeds + labor, harvesting requires labor
- Yield affected by: season, weather, labor invested

**Livestock:**
- Animals: Cattle (meat, leather), Sheep (wool, meat), Pigs (meat), Chickens (eggs, meat)
- Breeding: animals reproduce over time
- Products: milk, eggs, wool harvested regularly
- Slaughter: converts animal to meat

**Storage & Decay:**
- Granaries have capacity limits
- Food decays over time (faster in summer)
- Preserved food (salted meat, dried fruit) lasts longer

**Database Changes:**
- `granaries` table: location_id, capacity, current_stock
- `crops` table: field_id, crop_type, planted_week, status
- `fields` table: village_id, size, soil_quality
- `livestock` table: location_id, animal_type, count, health

---

### Phase 2: Economic Depth

#### 4. Dynamic Markets & Trade

Replace fixed prices with supply/demand economics.

**Supply & Demand:**
- Each location tracks item quantities (supply)
- Base price modified by local supply (more = cheaper)
- Regional demand affects prices (frontier needs weapons)
- Prices update daily based on transactions

**Merchant NPCs:**
- NPC merchants run trade caravans between locations
- Buy low in surplus areas, sell high in deficit areas
- Caravans can be robbed (crime system)
- Merchant guilds protect trade routes

**Trade Routes:**
- Established paths between major settlements
- Road quality affects travel time and safety
- Trade agreements between kingdoms reduce tariffs
- War disrupts trade routes

**Black Markets:**
- Illegal goods (stolen items, contraband)
- Higher prices, no taxes
- Risk of arrest

**Database Changes:**
- `market_prices` table: location_id, item_id, base_price, current_price, supply, demand
- `trade_caravans` table: merchant_id, origin, destination, cargo, status
- `trade_agreements` table: kingdom1_id, kingdom2_id, tariff_rate

---

#### 5. Guild System

Professional organizations controlling crafts and trade.

**Guild Structure:**
- Apprentice (0-2 years): learns craft, reduced wages
- Journeyman (2-5 years): full wages, can travel
- Master (5+ years): can teach, own shop, vote in guild

**Guild Types:**
- Blacksmiths Guild, Merchants Guild, Weavers Guild, etc.
- Each guild based in a town or city
- Multiple guilds can exist for same profession

**Guild Powers:**
- Set prices for guild services (monopoly)
- Control who can practice the craft
- Collect dues from members
- Political influence in towns

**Guild Politics:**
- Guildmaster elected by masters
- Guild wars: competing guilds sabotage each other
- Non-guild workers undercut prices but risk punishment

**Database Changes:**
- `guilds` table: name, profession, home_location_id, treasury
- `guild_members` table: player_id/npc_id, guild_id, rank, joined_date
- `guild_rules` table: guild_id, rule_type, value

---

#### 6. Banking & Currency

Expand banking to include loans, multiple currencies, and monetary policy.

**Loans & Debt:**
- Banks offer loans at interest
- Collateral required for large loans
- Defaulting on loans: property seized, reputation damage
- Money lenders offer riskier loans at higher rates

**Multiple Currencies:**
- Each kingdom mints own currency
- Exchange rates fluctuate based on kingdom wealth
- Currency exchange at banks and money changers
- Counterfeit coins as crime

**Inflation & Deflation:**
- Total gold supply affects prices
- Kingdom can mint new currency (causes inflation)
- Gold leaving kingdom causes deflation
- Price controls by kings (black markets emerge)

**Database Changes:**
- `loans` table: lender_id, borrower_id, principal, interest_rate, due_date, status
- `currencies` table: kingdom_id, name, exchange_rate_to_gold
- `money_supply` table: kingdom_id, total_minted, total_destroyed

---

### Phase 3: Conflict & Power

#### 7. Warfare System

Organized conflict between factions.

**Army Composition:**
- Soldiers recruited from population (reduces workers)
- Unit types: Peasant Levy, Men-at-Arms, Knights, Archers
- Equipment affects combat effectiveness
- Morale affects willingness to fight

**Battle Mechanics:**
- Simplified tactical combat (not real-time strategy)
- Terrain advantages (defending hills, forests)
- Commander skill affects outcome
- Casualties distributed among units

**Siege Warfare:**
- Armies can besiege fortified towns and barony seats
- Sieges take weeks/months
- Starvation vs assault options
- Siege equipment (rams, ladders, catapults)

**Supply & Logistics:**
- Armies consume food daily
- Supply lines from home territory
- Foraging damages local economy
- Mercenaries cost gold, no loyalty

**Database Changes:**
- `armies` table: owner_id, location_id, status, morale
- `army_units` table: army_id, unit_type, count, equipment
- `battles` table: attacker_army_id, defender_army_id, location, outcome
- `sieges` table: army_id, target_location_id, start_date, status

---

#### 8. Crime & Justice System

Law, order, and punishment in the medieval world.

**Crime Types:**
- Theft: stealing from players, NPCs, locations
- Assault: attacking players/NPCs outside combat areas
- Murder: killing players/NPCs
- Treason: acting against your baron/king
- Heresy: religious crimes (if state religion exists)

**Law Enforcement:**
- Guards patrol locations
- Witnesses can report crimes
- Reputation as criminal tracked
- Bounties placed on wanted criminals

**Justice System:**
- Arrest by guards
- Trial by baron (barony) or elder (village)
- Punishments: fines, jail time, exile, execution
- Escape from prison possible

**Bounty Hunting:**
- Players can become bounty hunters
- Collect bounties by capturing/killing wanted criminals
- Bounty hunter guild for coordination

**Database Changes:**
- `crimes` table: criminal_id, crime_type, victim_id, location_id, witnessed, status
- `bounties` table: criminal_id, amount, posted_by, status
- `prisoners` table: player_id, location_id, release_date, crime_id

---

#### 9. Espionage & Intrigue

Covert operations and political manipulation.

**Spy Network:**
- Hire spies to gather intelligence
- Plant spies in enemy locations
- Counter-intelligence to catch spies
- Information value: troop counts, treasury, alliances

**Assassination:**
- Hire assassins through black market
- Success based on target's guards, awareness
- Failed attempts create scandal
- Assassination of rulers triggers succession crisis

**Sabotage:**
- Destroy supplies, equipment, buildings
- Poison wells or food stores
- Spread false information
- Frame others for crimes

**Political Blackmail:**
- Gather dirt on rivals
- Use leverage to influence votes
- Expose secrets to damage reputation
- Counter: pay to suppress, kill the witness

**Database Changes:**
- `spies` table: owner_id, location_id, cover_identity, loyalty
- `intelligence` table: spy_id, target_id, info_type, info_value, date
- `assassination_contracts` table: target_id, contractor_id, assassin_id, status

---

### Phase 4: Social & Family

#### 10. Marriage & Dynasty

Formalized family system for players and NPCs.

**Marriage:**
- Players can marry other players or NPCs
- Dowry negotiations (gold, land, titles)
- Marriage contracts with terms
- Divorce possible (costs reputation)

**Children & Inheritance:**
- Married couples can have children
- Children inherit on parent death
- Inheritance rules: primogeniture, gavelkind, etc.
- Orphans become wards of the location

**Dynasty Reputation:**
- Family reputation tracked across generations
- Noble families have higher starting reputation
- Scandals damage family reputation
- Reputation affects marriage prospects, political power

**Succession:**
- When ruler dies, succession rules determine heir
- Disputed succession can cause civil war
- Regency for underage heirs

**Database Changes:**
- `marriages` table: spouse1_id, spouse2_id, marriage_date, dowry_paid, contract_terms
- `player_children` table: parent1_id, parent2_id, child_id, birth_date
- `dynasties` table: name, founder_id, reputation, coat_of_arms
- `dynasty_members` table: dynasty_id, member_id, branch

---

#### 11. Social Classes & Serfdom

Feudal class structure affecting rights and obligations.

**Class System:**
- Serf: bound to land, cannot leave without permission, limited rights
- Freeman: can move freely, own property, pay taxes
- Burgher: town dweller, guild member, higher taxes but more rights
- Noble: owns land, military obligations, political power
- Clergy: religious positions, exempt from some laws

**Class Mobility:**
- Serfs can earn freedom (manumission) through:
  - Baron's decree
  - Military service
  - Exceptional service
  - Purchasing freedom
- Freemen can earn nobility through:
  - Royal decree
  - Exceptional military service
  - Marriage into nobility
  - Purchasing title

**Feudal Obligations:**
- Serfs owe labor days to baron
- Freemen owe military service in war
- Nobles owe knight service to king
- Failure to meet obligations has consequences

**Database Changes:**
- `social_class` column on users/npcs
- `manumission_requests` table: serf_id, baron_id, reason, status
- `feudal_obligations` table: player_id, baron_id, obligation_type, fulfilled

---

#### 12. Festivals & Events

Regular world events that bring communities together.

**Seasonal Festivals:**
- Spring: Planting Festival (fertility, new beginnings)
- Summer: Midsummer Fair (trade, celebration)
- Autumn: Harvest Festival (thanksgiving, feasting)
- Winter: Midwinter Feast (community, survival)

**Tournaments:**
- Jousting competitions at towns and barony seats
- Melee tournaments for combat
- Archery contests
- Prizes, fame, and spouse-finding

**Religious Holidays:**
- Holy days based on kingdom's religion
- Pilgrimages to religious sites
- Fasting periods affecting food consumption
- Tithe requirements

**Royal Events:**
- Coronations when new king
- Royal weddings (political alliances)
- Royal funerals (succession transitions)
- Declarations of war or peace

**Database Changes:**
- `festivals` table: name, type, season, location_type
- `festival_instances` table: festival_id, location_id, start_date, status
- `tournament_entries` table: festival_instance_id, player_id, event_type, result

---

### Phase 5: World Events

#### 13. Disease & Plague

Health threats that spread through populations.

**Disease Types:**
- Common Illness: minor penalties, spreads slowly
- Plague: major penalties, spreads fast, high mortality
- Famine Sickness: from malnutrition
- Battle Wounds: from combat, can become infected

**Spread Mechanics:**
- Diseases spread through proximity
- Trade caravans can carry disease
- Rats and poor sanitation increase spread
- Quarantine reduces spread

**Treatment:**
- Healers can treat illness (better than nothing)
- Rest speeds recovery
- Some diseases have no cure
- Survivors may become immune

**Epidemic Events:**
- Periodic plague outbreaks
- Affected regions see population drop
- Economic disruption
- Mass graves, abandoned settlements

**Database Changes:**
- `diseases` table: name, severity, spread_rate, mortality_rate, duration
- `infections` table: patient_id, disease_id, start_date, status
- `epidemics` table: disease_id, origin_location, affected_locations, start_date

---

#### 14. Natural Disasters

Environmental events that disrupt normal life.

**Weather System:**
- Daily weather affects activities
- Seasonal weather patterns
- Extreme weather: storms, heat waves, cold snaps

**Disaster Types:**
- Drought: crop failure, water shortage
- Flood: destroys buildings, drowns livestock
- Fire: spreads through buildings, forests
- Earthquake: damages buildings, kills people (rare)

**Impact:**
- Building damage requiring repair
- Crop destruction
- Population loss
- Economic disruption

**Preparation & Response:**
- Granary reserves buffer famine
- Stone buildings resist fire
- Levees protect from floods
- Community labor for repairs

**Database Changes:**
- `weather` table: location_id, date, weather_type, severity
- `disasters` table: type, location_id, date, severity, damage_assessment
- `disaster_damage` table: disaster_id, building_id, damage_amount

---

#### 15. Infrastructure & Construction

Building and maintaining the physical world.

**Building Types:**
- Housing: homes, mansions, hovels
- Economic: mills, forges, markets, warehouses
- Military: walls, towers, barracks, armories
- Religious: shrines, temples, cathedrals
- Infrastructure: roads, bridges, wells, sewers

**Construction:**
- Requires materials (wood, stone, iron)
- Requires labor (workers assigned)
- Takes time (weeks to months)
- Skilled builders complete faster

**Maintenance & Decay:**
- Buildings decay over time without maintenance
- Damaged buildings function poorly
- Abandoned buildings become ruins
- Repair requires materials and labor

**Defense Fortifications:**
- Walls protect settlements from attack
- Towers provide archer positions
- Gates can be opened or closed
- Siege damage requires repair

**Database Changes:**
- `buildings` table: location_id, building_type, construction_date, condition, capacity
- `construction_projects` table: location_id, building_type, materials_needed, labor_needed, progress
- `building_maintenance` table: building_id, last_maintenance, next_maintenance_due

---

## PART 3: DEVELOPMENT PRIORITIES

### Immediate (Next Sprint)

**Calendar System** - Foundation for all time-based mechanics
- Create world_state table with current date
- Add season modifier system
- Integrate with TravelService for seasonal effects
- Display current date/season in UI

**Basic NPC Lifecycle** - Make NPCs feel alive
- Add birth_year, death_year to NPCs
- Weekly aging job
- Death creates vacancies, triggers replacement
- Simple reproduction when population drops

**Food Production/Consumption (Simplified)**
- Granary storage per location
- Weekly food consumption per person
- Starvation penalties when food runs out
- Basic harvest based on season

### Short-term

- Dynamic market prices with supply/demand
- Marriage and inheritance basics
- Seasonal effects on gathering and crafting
- NPC personality traits affecting prices/behavior

### Medium-term

- Warfare and army system
- Guild system with progression
- Crime and justice
- Disease spread basics

### Long-term

- Full NPC autonomy with migration
- Natural disasters
- Infrastructure and construction
- Multiple currencies and banking expansion

---

## PART 4: TECHNICAL PATTERNS

### File Conventions

| Type | Location | Naming |
|------|----------|--------|
| Models | `app/Models/` | `{Name}.php` |
| Services | `app/Services/` | `{Name}Service.php` |
| Controllers | `app/Http/Controllers/` | `{Name}Controller.php` |
| Pages | `resources/js/pages/` | `{Feature}/Index.tsx` |
| Seeders | `database/seeders/` | `{Name}Seeder.php` |
| Migrations | `database/migrations/` | `{date}_create_{table}_table.php` |
| Jobs | `app/Jobs/` | `{Action}{Noun}.php` |

### Coding Standards

- Use Inertia.js for all pages (not API routes)
- Services handle business logic, controllers are thin
- All money operations use BankService
- All XP operations go through skill models
- Use existing UI components from `resources/js/components/ui/`
- Scheduled jobs handle time-based updates (aging, decay, etc.)

### Commands

```bash
# Run tests
sail artisan test

# Type check frontend
npm run build

# Format code
composer lint && npm run lint

# Run scheduled jobs
sail artisan schedule:run
```

### Testing Requirements

- Feature tests for all new endpoints
- Unit tests for service methods
- Factory patterns for all models
- Seeders for development data

---

## PART 5: DESIGN PRINCIPLES

### Simulation Depth

**What makes a good simulation feature:**
- Emergent gameplay from simple rules
- Player choices have meaningful consequences
- NPCs and players follow same rules
- Systems interconnect (economy affects war affects politics)

**Avoiding tedium:**
- Automate routine tasks (tax collection, food distribution)
- Weekly/daily cycles, not hourly micromanagement
- Meaningful decisions, not grinding
- AI handles what players don't want to do

### NPC Autonomy

**NPCs should:**
- Pursue their own goals (wealth, family, power)
- React to world events (flee war, seek food)
- Remember interactions with players
- Form relationships with each other
- Die and be replaced naturally

**NPCs should NOT:**
- Be immortal quest dispensers
- Have infinite resources
- Act irrationally for player convenience
- Break the rules players must follow

### Economic Balance

**Healthy economy indicators:**
- Gold flows between players, NPCs, and locations
- Multiple ways to earn and spend
- Regional variation in prices
- Scarcity creates meaningful trade

**Red flags:**
- Infinite gold sources
- Items with no gold sinks
- Single optimal strategy
- Hyperinflation or deflation

---

## Appendix A: Model Reference

### Complete Model List (52 models)

**Core Player:**
User, PlayerSkill, PlayerInventory, Item, PlayerTitle, BankAccount, BankTransaction

**World:**
Kingdom, Barony, Town, Village

**Quests & Tasks:**
Quest, PlayerQuest, DailyTask, PlayerDailyTask

**Economy:**
EmploymentJob, PlayerEmployment, TaxCollection, LocationTreasury, TreasuryTransaction, SalaryPayment

**Governance:**
Role, PlayerRole, LocationNpc, Election, ElectionCandidate, ElectionVote, NoConfidenceVote, NoConfidenceBallot

**Population:**
MigrationRequest

**Communication:**
Message

**Crafting:**
CraftingOrder, LocationStockpile

**Combat:**
Monster, MonsterLootTable, CombatSession, CombatLog

**Dungeons:**
Dungeon, DungeonFloor, DungeonFloorMonster, DungeonSession

**Religion:**
Religion, Belief, ReligionMember, ReligiousStructure, KingdomReligion, ReligiousAction

**Settlements:**
Charter, CharterSignatory, SettlementRuin

**Horses:**
Horse, PlayerHorse

---

## Appendix B: Service Reference

### Complete Service List (24 services)

| Service | Responsibility |
|---------|----------------|
| EnergyService | Energy regeneration |
| InventoryService | Item management |
| BirthService | NPC population |
| BankService | Gold transactions |
| HealerService | HP restoration |
| GatheringService | Resource collection |
| CraftingService | Item creation |
| DailyTaskService | Daily task management |
| QuestService | Quest management |
| PortService | Sea travel |
| JobService | Employment |
| RoleService | Political roles |
| MigrationService | Population movement |
| ElectionService | Elections and voting |
| TaxService | Tax collection |
| ChatService | Messaging |
| DocketService | Crafting orders |
| LootService | Drop tables |
| CombatService | Monster combat |
| DungeonService | Dungeon runs |
| ReligionService | Faith system |
| CharterService | Settlement founding |
| TravelService | Land travel |
| StableService | Horse management |
