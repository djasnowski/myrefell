# Myrefell PRD - Medieval World MMO

## Vision

Myrefell is a persistent, browser-based massively multiplayer game set in a medieval mirror world. It combines **player-driven society** with a **living simulation** where NPCs are not static vendors but autonomous inhabitants who fill the gaps when players aren't present.

**The Dual Nature:**
- **You are one person** with combat stats (ATK, STR, DEF), skills to train, and equipment to wear
- **You shape society** through offices held, assets controlled, reputation earned, and influence wielded

Players begin as common subjects within a settlement and may rise through economic, military, political, or religious paths. There are no character classes. Power is asymmetric—only eligible players may vote, command armies, or rule. Access to authority is earned through social systems, not grind.

**Tech Stack:** Laravel 12 + React 19 + Inertia.js + PostgreSQL + Redis + Tailwind CSS

---

## Core Design Philosophy

### Player-Driven with Living NPCs

| Principle | Implementation |
|-----------|----------------|
| Players run institutions | Guilds, governments, churches are player-controlled |
| NPCs fill gaps intelligently | NPCs hold roles when no player wants them, but live full lives |
| NPCs are not immortal vendors | They age, marry, have children, migrate, and die |
| Emergent conflict | Wars, famines, heresies emerge from player and NPC decisions |
| Long-term consequences | Excommunication, exile, dynasty collapse are permanent |

### No Pay-to-Win

| Allowed | Forbidden |
|---------|-----------|
| Cosmetics | Stat boosts for money |
| Quality-of-life features | Exclusive power items |
| Subscription perks (convenience) | Skipping progression |

### Slow Information Flow

- **No global chat or feeds** - Information travels at the speed of messengers
- **Local knowledge only** - You know what happens in your settlement
- **Rumors spread** - News from distant lands may be delayed or wrong
- **Spies are valuable** - Intelligence gathering is a real profession

### Asymmetric Power

Not everyone can:
- Vote (must be freeman or higher class)
- Hold office (must meet requirements)
- Command armies (must be appointed)
- Own land (must be granted or purchased)

Power is earned through the social fabric, not accumulated through grinding.

---

## Part 1: The Individual (RPG Layer)

You are one person with a body, skills, and daily life.

### Combat Stats

Every player has three core combat attributes:

| Stat | Purpose | Training Methods |
|------|---------|------------------|
| **Attack (ATK)** | Damage dealt in combat | Training grounds, combat |
| **Strength (STR)** | Carry capacity, melee power | Manual labor, mining |
| **Defense (DEF)** | Damage reduction | Sparring, guard duty |

**Training Loop:**
- Visit training grounds daily (costs energy)
- Each training session grants stat XP
- Stats have no cap but diminishing returns
- Equipment multiplies base stats

### Skills (9 Implemented)

| Skill | Activities | Products |
|-------|------------|----------|
| Mining | Extract ore from mines | Iron, copper, gold ore |
| Smithing | Forge weapons and armor | Swords, helmets, tools |
| Woodcutting | Fell trees | Logs, planks |
| Crafting | General item creation | Furniture, tools |
| Fishing | Catch fish | Food, rare catches |
| Cooking | Prepare food | Meals with buffs |
| Foraging | Gather wild plants | Herbs, berries |
| Combat | Fight monsters/players | XP, loot |
| Riding | Handle horses | Faster travel |

**Skill Progression:**
- Perform activity → Gain XP → Level up
- Higher levels unlock better recipes/techniques
- No level caps, but XP requirements scale

### Health, Energy, and Death

| Resource | Regeneration | Purpose |
|----------|--------------|---------|
| HP | Healer visit or rest | Die at 0, respawn at home |
| Energy | 1 per 5 minutes (max 100) | Required for all actions |

**Death Consequences:**
- Respawn at home settlement
- Lose some carried gold (not banked)
- Equipment damaged but not lost
- Combat stats unaffected

### Equipment

- **28 inventory slots** for carried items
- **Equipment slots:** Head, chest, legs, feet, main hand, off hand, ring x2
- Equipment provides stat bonuses
- Equipment degrades with use, requires repair

---

## Part 2: The Settlement (Where You Live)

Every player has a **home settlement** determining:
- Where you vote
- Where you pay taxes
- Where you respawn on death
- Your local community

### Settlement Hierarchy

```
Kingdom (ruled by elected King)
└── Barony (ruled by appointed/elected Baron)
    ├── Town (ruled by elected Mayor)
    │   └── Guild halls, markets, cathedrals
    └── Village (ruled by elected Elder)
        └── Hamlet (governed by parent village)
```

### Settlement Types

| Type | Population | Services | Governance |
|------|------------|----------|------------|
| **Kingdom** | 1000+ | Royal court, mint, army | Elected King |
| **Barony** | 200+ | Baron's court, militia | Baron (appointed or elected) |
| **Town** | 100+ | Guilds, market, cathedral | Elected Mayor |
| **Village** | 20+ | Bank, healer, jobs | Elected Elder |
| **Hamlet** | 5+ | Uses parent village services | Parent village Elder |
| **Independent** | Any | Self-governing | Elected Elder (no baron protection) |

### Residency and Migration

- You are born in or choose a home settlement
- Changing residence requires:
  - Migration request to new settlement
  - Approval from destination (Elder/Mayor)
  - Travel to the new location
- Some settlements may refuse migrants
- Criminals may be exiled (forced migration)

---

## Part 3: Governance (Player-Run Politics)

All positions of power are held by players when possible. NPCs serve as placeholders when no player holds a role.

### Feudal Governance (Default)

| Level | Ruler | How Obtained | Powers |
|-------|-------|--------------|--------|
| Kingdom | King | Election by Barons | Declare war, set kingdom tax, appoint ministers |
| Barony | Baron | Appointed by King or elected | Collect barony tax, raise militia, judge crimes |
| Town | Mayor | Election by residents | Set town policies, manage guilds, town treasury |
| Village | Elder | Election by residents | Local disputes, village treasury, approve migrants |

### Communal Governance (Alternative)

Settlements may adopt communal governance:
- No single ruler
- Decisions by council vote
- All freemen have equal say
- Slower but more democratic

### Ecclesiastical Holdings

Religious institutions can control territory:
- **Monastery:** Religious settlement, ruled by Abbot
- **Bishopric:** Town-level religious holding
- **Holy See:** If a religion grows large enough

These follow religious hierarchy, not feudal.

### Elections

- **Eligibility to vote:** Must be freeman or higher class
- **Eligibility to run:** Depends on position (residency, reputation, class)
- **Election cycle:** Monthly for villages/towns, yearly for kingdoms
- **No confidence votes:** Can remove officials mid-term

### Legitimacy System

Rulers have a **legitimacy score** (0-100) affecting their power:

| Factor | Effect |
|--------|--------|
| Election margin | +/- 20 based on victory margin |
| Time in office | +1 per month (max +20) |
| Successful policies | +5 to +15 |
| Won war | +10 |
| Lost war | -20 |
| Church support | +15 |
| Church opposition | -25 |
| Scandal | -10 to -30 |

**Low legitimacy consequences:**
- No confidence votes more likely
- Rebellion possible
- Reduced tax collection
- Desertion from armies

---

## Part 4: Social Classes

Feudal class structure affects rights and obligations.

### Class Hierarchy

| Class | Rights | Restrictions |
|-------|--------|--------------|
| **Serf** | Basic protection | Cannot vote, cannot leave land, limited property |
| **Freeman** | Vote, own property, travel | Must pay taxes, military service in war |
| **Burgher** | Vote, guild membership, trade | Higher taxes, town residence required |
| **Noble** | Vote, hold high office, own land | Knight service to king, noblesse oblige |
| **Clergy** | Vote in church matters, exempt from some laws | Cannot hold secular office, tithe obligations |

### Class Mobility

**Serfs can earn freedom through:**
- Baron's decree
- Military service
- Exceptional service
- Purchasing freedom (expensive)

**Freemen can earn nobility through:**
- Royal decree
- Exceptional military service
- Marriage into nobility
- Purchasing title (very expensive)

### Feudal Obligations

| Class | Obligation |
|-------|------------|
| Serf | Labor days to baron |
| Freeman | Military service in war |
| Noble | Knight service to king |
| Clergy | Spiritual duties |

Failure to meet obligations has consequences (fines, loss of status, imprisonment).

---

## Part 5: Economy (Scarcity-Based)

The economy is built on real scarcity. Resources are finite, labor is limited, and prices emerge from supply and demand.

### Resource Flow

```
Raw Materials (mining, farming, foraging)
    ↓
Refinement (smelting, milling, tanning)
    ↓
Crafting (smithing, tailoring, cooking)
    ↓
Consumption (equipment wear, food eaten, buildings decay)
```

### Employment

Players and NPCs work jobs for wages:

| Job Type | Location | Payment |
|----------|----------|---------|
| Laborer | Farms, mines | Daily wage |
| Craftsman | Workshops | Per item + wage |
| Guard | Settlements | Salary from treasury |
| Merchant | Markets | Profit from trade |
| Clerk | Government | Salary from treasury |

### Player-Owned Businesses

Players can own economic enterprises:
- Workshops (smithy, bakery, tannery)
- Farms and fields
- Mines
- Merchant caravans

**Business mechanics:**
- Hire other players or NPCs
- Pay wages from revenue
- Pay taxes to local authority
- Keep profits after expenses

### Player-Run Guilds

Guilds are **player-founded and player-run** economic organizations:

| Aspect | Description |
|--------|-------------|
| Founding | 5+ players, registration fee, town approval |
| Membership | Apprentice (0-2 yr) → Journeyman (2-5 yr) → Master (5+ yr) |
| Powers | Set craft prices, control quality, political lobbying |
| Governance | Guildmaster elected by Masters |
| Monopoly | Guild can petition for exclusive craft rights in town |

**Guild conflicts:** Competing guilds may sabotage, undercut, or politically attack each other.

### Markets and Trade

- **Local markets:** Each settlement has prices based on local supply/demand
- **No global auction house:** You must travel to trade
- **Merchant caravans:** NPCs and players move goods between settlements
- **Trade routes:** Established paths with varying safety
- **Tariffs:** Kingdoms/baronies can tax goods crossing borders

### Dynamic Pricing

| Factor | Effect on Price |
|--------|-----------------|
| Local supply high | Price decreases |
| Local supply low | Price increases |
| Regional demand | Affects base price |
| Season | Some goods seasonal |
| War | Disrupts trade, raises prices |

### Taxes

| Level | Tax Type | Flow |
|-------|----------|------|
| Village | Head tax | Residents → Village treasury |
| Barony | Land tax | Villages → Barony treasury |
| Kingdom | Crown tax | Baronies → Kingdom treasury |
| Church | Tithe | Faithful → Church treasury |

Tax rates set by respective rulers. High taxes → emigration. Low taxes → weak treasury.

---

## Part 6: Warfare (Logistics-Driven)

War is strategic and logistics-heavy. Preparation matters more than clicking fast.

### Declaring War

Only **Kings** can declare war. Requirements:
- Casus belli (justification) or suffer legitimacy penalty
- War chest (gold reserves)
- Army raised

### War Goals

| Goal | Victory Condition |
|------|-------------------|
| Conquest | Occupy enemy capital for 30 days |
| Border adjustment | Occupy target barony for 30 days |
| Humiliation | Defeat enemy army, force tribute |
| Holy war | Conquer infidel territory |

### Army Composition

| Unit | Source | Cost | Strength |
|------|--------|------|----------|
| Levy | Conscript peasants | Food only | Weak, low morale |
| Men-at-Arms | Hire freemen | Wages | Moderate |
| Knights | Noble obligation | Equipment | Strong, high morale |
| Mercenaries | Hire companies | High wages | Variable, no loyalty |

**Player Soldiers:**
- Players can volunteer or be conscripted
- Your personal ATK/STR/DEF affects unit strength
- Death in battle = normal death (respawn at home)
- Desertion is a crime

### Battle Resolution

Battles resolve in **daily ticks**, not real-time:

1. Armies meet at location
2. Each day: calculate casualties based on:
   - Army size and composition
   - Commander skill
   - Terrain advantages
   - Supply status
   - Morale
3. Army with 0 morale retreats or surrenders
4. Siege battles use separate mechanics (starvation, assault)

### Supply Lines

- Armies consume food daily
- Supply comes from home territory
- Cut supply lines → army starves → morale collapses
- Foraging damages local economy and reputation

### Siege Warfare

- Armies can besiege fortified towns and barony seats
- Sieges take weeks/months
- Options: starve them out or assault
- Siege equipment: rams, ladders, catapults
- Defenders can sally forth

### Peace Treaties

Wars end through:
- **Negotiated peace:** Terms agreed by both sides
- **Total victory:** One side achieves war goal
- **White peace:** Status quo, both sides exhausted

---

## Part 7: Religion (Player-Founded Faiths)

Religion is a parallel power structure to feudal governance.

### Religious Organizations

| Type | Size | Secrecy | Beliefs |
|------|------|---------|---------|
| Cult | 5+ members | Secret | 2 beliefs |
| Religion | 15+ members | Public | Up to 5 beliefs |
| State Religion | Kingdom adoption | Public | Full political power |

### Religious Hierarchy

```
Prophet/Founder (lifetime position)
└── High Priest (elected by Priests)
    └── Priests (appointed by High Priest)
        └── Faithful (any member)
```

### Beliefs and Bonuses

Religions choose **beliefs** that grant bonuses/penalties:

| Belief | Bonus | Penalty |
|--------|-------|---------|
| Martial | +10% combat stats | -10% farming |
| Pacifist | +10% trade income | Cannot declare holy war |
| Ascetic | +10% faith gain | -10% gold income |
| Prosperity | +10% gold income | -10% faith gain |

### Religious Actions

| Action | Effect | Cost |
|--------|--------|------|
| Prayer | Gain faith points | Energy |
| Tithe | Donate to church | Gold |
| Pilgrimage | Visit holy site | Travel + time |
| Conversion | Change religion | Reputation |
| Excommunication | Exile from faith | Priest action |

### Church vs State

- Kings may adopt a **state religion** (bonuses for faithful, penalties for heathens)
- Kings may **ban religions** (practicing is a crime)
- Churches may **excommunicate** rulers (legitimacy penalty)
- Religious wars can reshape the map

---

## Part 8: Law and Crime

Every settlement has laws. Breaking them has consequences.

### Crime Types

| Crime | Severity | Detection |
|-------|----------|-----------|
| Theft | Minor | Witnesses, missing items |
| Assault | Moderate | Witnesses, victim report |
| Murder | Major | Body found, witnesses |
| Treason | Capital | Investigation |
| Heresy | Variable | Inquisition |
| Desertion | Major | Army records |

### Detection and Accusation

- Crimes require **witnesses** or **evidence**
- Players can accuse other players
- False accusations are themselves a crime
- Guards may investigate based on suspicion

### Court Hierarchy

| Court | Jurisdiction | Judge |
|-------|--------------|-------|
| Village court | Minor crimes | Elder |
| Baron's court | Moderate crimes | Baron |
| Royal court | Major crimes, appeals | King |
| Church court | Religious crimes | High Priest |

**Trial process:**
1. Accusation filed
2. Evidence presented
3. Defense presented
4. Judge renders verdict
5. Punishment assigned

### Punishments

| Punishment | Effect |
|------------|--------|
| Fine | Pay gold to treasury |
| Jail | Cannot act for X days |
| Exile | Forced migration, banned from return |
| Outlawry | Anyone can kill without penalty |
| Execution | Character death (can create new character) |
| Excommunication | Banned from religion, social penalties |

### Bounty System

- Victims or authorities can post **bounties**
- Bounty hunters can capture/kill outlaws
- Captured criminals delivered to court
- Killed outlaws: bounty paid, no trial

---

## Part 9: Information Flow

**There is no global feed.** Information flows realistically.

### Communication Channels

| Channel | Range | Speed |
|---------|-------|-------|
| Local chat | Same settlement | Instant |
| Messenger | Adjacent settlements | Hours |
| Courier | Distant settlements | Days |
| Rumor | Spreads organically | Variable, may be false |

### What You Know

| Location | Information Quality |
|----------|---------------------|
| Your settlement | Full, real-time |
| Adjacent settlements | Delayed 1 day |
| Same barony | Delayed 2-3 days |
| Same kingdom | Weekly summaries |
| Other kingdoms | Rumors only (may be false) |

### Espionage

Spies can:
- Gather intelligence faster
- Verify rumors
- Plant false information
- Assassinate (high risk)

Spies are player characters with cover identities.

---

## Part 10: NPCs (Living Inhabitants)

NPCs are not static vendors. They live full lives and fill gaps when players aren't present.

### NPC Lifecycle

| Stage | Age | Behavior |
|-------|-----|----------|
| Child | 0-15 | Dependent, learning |
| Adult | 16-50 | Working, marrying, having children |
| Elder | 51+ | Reduced productivity, wisdom bonuses |
| Death | 50-80 | Natural death, creates vacancies |

### NPC Families

- Unmarried adult NPCs seek partners in same location
- Marriage produces 1-4 children over time
- Children grow up and become workers
- Family names and dynasties tracked
- Inheritance passes to children

### NPC Jobs and Migration

- NPCs hold jobs (same system as players)
- Unemployed NPCs may migrate to locations with jobs
- NPCs with family ties less likely to migrate
- Population naturally balances across settlements

### NPC Personalities

| Trait | Effect |
|-------|--------|
| Greedy | Higher prices, more tax evasion |
| Generous | Lower prices, more charity |
| Ambitious | Seeks higher positions |
| Content | Stays in current role |
| Aggressive | More likely to commit crimes |
| Peaceful | Avoids conflict |

Traits affect: prices, quest rewards, marriage choices, migration decisions, crime rates.

### NPC Role-Holding

When no player holds a role (Elder, Baron, etc.):
- An eligible NPC fills the position
- NPC makes decisions based on personality
- Players can challenge NPCs for positions via election
- NPC rulers have legitimacy like player rulers

---

## Part 11: The Living World

Time passes and the world changes.

### Calendar System

- **4 seasons × 12 weeks = 48-week year**
- **Time acceleration:** 1 real day = 1 game week (configurable)
- Current date tracked globally, displayed in UI

### Seasonal Effects

| Season | Effects |
|--------|---------|
| **Spring** | Planting season, muddy roads (+travel time), mild weather |
| **Summer** | Growing season, fastest travel, drought risk |
| **Autumn** | Harvest season, trade caravans active, fair weather |
| **Winter** | Famine risk, frozen ports, combat penalties outdoors, disease spread |

### Food and Agriculture

**Food Consumption:**
- Each person (player + NPC) consumes 1 food unit per week
- Locations have granaries storing food
- When food runs out: starvation penalties, emigration, death

**Crop System:**
- Fields belong to villages
- Crops: Wheat (spring plant, autumn harvest), Barley, Vegetables, Fruits
- Planting requires seeds + labor
- Harvesting requires labor
- Yield affected by: season, weather, labor invested

**Livestock:**
- Animals: Cattle, Sheep, Pigs, Chickens
- Breeding: animals reproduce over time
- Products: milk, eggs, wool harvested regularly
- Slaughter: converts animal to meat

**Storage:**
- Granaries have capacity limits
- Food decays over time (faster in summer)
- Preserved food (salted meat, dried fruit) lasts longer

### Weather System

- Daily weather affects activities
- Seasonal weather patterns
- Extreme weather: storms, heat waves, cold snaps

### Natural Disasters

| Disaster | Effects |
|----------|---------|
| Drought | Crop failure, water shortage |
| Flood | Destroys buildings, drowns livestock |
| Fire | Spreads through buildings, forests |
| Earthquake | Damages buildings, kills people (rare) |

**Preparation:** Granary reserves buffer famine, stone buildings resist fire, levees protect from floods.

### Disease and Plague

| Type | Severity | Spread |
|------|----------|--------|
| Common Illness | Minor penalties | Slow |
| Plague | Major penalties, high mortality | Fast |
| Famine Sickness | From malnutrition | N/A |

**Spread mechanics:**
- Diseases spread through proximity
- Trade caravans can carry disease
- Rats and poor sanitation increase spread
- Quarantine reduces spread

**Treatment:**
- Healers can treat illness
- Rest speeds recovery
- Survivors may become immune

---

## Part 12: Marriage and Dynasties

### Marriage

- Players can marry other players or NPCs
- Dowry negotiations (gold, land, titles)
- Marriage contracts with terms
- Divorce possible (costs reputation)

### Children and Inheritance

- Married couples can have children
- Children inherit on parent death
- Inheritance rules vary by culture:
  - **Primogeniture:** Eldest child inherits
  - **Gavelkind:** Split among children
  - **Elective:** Voted by peers
- Orphans become wards of the location

### Succession

When a ruler dies:
- Succession rules determine heir
- Disputed succession can cause civil war
- Regency for underage heirs
- External powers may intervene

### Dynasty Reputation

| Factor | Effect |
|--------|--------|
| Offices held | +Reputation |
| Battles won | +Reputation |
| Scandals | -Reputation |
| Excommunication | -Reputation |
| Strategic marriages | Reputation merges |

High dynasty reputation → easier elections, better marriages, more legitimacy.

---

## Part 13: Festivals and Events

### Seasonal Festivals

| Season | Festival | Activities |
|--------|----------|------------|
| Spring | Planting Festival | Fertility rites, new beginnings |
| Summer | Midsummer Fair | Trade, celebration, tournaments |
| Autumn | Harvest Festival | Thanksgiving, feasting |
| Winter | Midwinter Feast | Community gathering, survival |

### Tournaments

- Jousting competitions
- Melee tournaments (uses ATK/STR/DEF)
- Archery contests
- Prizes, fame, and spouse-finding

### Religious Holidays

- Holy days based on kingdom's religion
- Pilgrimages to religious sites
- Fasting periods affecting food consumption
- Tithe requirements

### Royal Events

- Coronations when new king
- Royal weddings (political alliances)
- Royal funerals (succession transitions)
- Declarations of war or peace

---

## Part 14: Infrastructure and Construction

### Building Types

| Category | Examples |
|----------|----------|
| Housing | Homes, mansions, hovels |
| Economic | Mills, forges, markets, warehouses |
| Military | Walls, towers, barracks, armories |
| Religious | Shrines, temples, cathedrals |
| Infrastructure | Roads, bridges, wells, sewers |

### Construction

- Requires materials (wood, stone, iron)
- Requires labor (workers assigned)
- Takes time (weeks to months)
- Skilled builders complete faster

### Maintenance and Decay

- Buildings decay over time without maintenance
- Damaged buildings function poorly
- Abandoned buildings become ruins
- Repair requires materials and labor

### Fortifications

- Walls protect settlements from attack
- Towers provide archer positions
- Gates can be opened or closed
- Siege damage requires repair

---

## Part 15: Retention Loops

### Daily Loop (5-10 minutes)

1. **Train** - Visit training grounds, gain ATK/STR/DEF XP
2. **Work** - Complete job for wages
3. **Tasks** - Do 3 daily tasks for rewards
4. **Socialize** - Check local chat, settlement news

### Weekly Loop (30-60 minutes)

1. **Religious service** - Gain faith, community bonding
2. **Market day** - Best prices, most traders
3. **Guild meeting** - If guild member
4. **Settlement politics** - Vote, campaign, debate

### Monthly Loop

1. **Elections** - Village/town elections
2. **Tax day** - Pay taxes, see treasury report
3. **Festivals** - Seasonal events with unique activities

### Long-term Goals

| Path | Goal | Prestige |
|------|------|----------|
| Economic | Own a business empire | Wealthy merchant |
| Military | Command armies | War hero |
| Political | Become King | Ruler of realm |
| Religious | Found a religion | Prophet |
| Criminal | Run the underworld | Crime lord |
| Dynastic | Multi-generational legacy | Great house |

---

## Part 16: Implemented Systems

### Models (115+)

**Core Player:**
User, PlayerSkill, PlayerInventory, Item, PlayerTitle, BankAccount, BankTransaction

**World:**
Kingdom, Barony, Town, Village

**Economy:**
EmploymentJob, PlayerEmployment, TaxCollection, LocationTreasury, TreasuryTransaction, SalaryPayment, CraftingOrder, LocationStockpile, MarketPrice, MarketTransaction, BusinessType, PlayerBusiness, BusinessEmployee, BusinessInventory, BusinessTransaction, BusinessProductionOrder, Guild, GuildMember, GuildBenefit, GuildActivity, GuildElection, GuildElectionCandidate, GuildElectionVote, GuildPriceControl

**Trade Caravans:**
TradeRoute, Caravan, CaravanGoods, CaravanEvent, TradeTariff, TariffCollection

**Governance:**
Role, PlayerRole, LocationNpc, Election, ElectionCandidate, ElectionVote, NoConfidenceVote, NoConfidenceBallot, MigrationRequest, LegitimacyEvent

**Social Class:**
ManumissionRequest, EnnoblementRequest, SocialClassHistory

**Crime & Law:**
CrimeType, Crime, CrimeWitness, Accusation, Trial, Punishment, Bounty, JailInmate, Outlaw, Exile

**Combat:**
Monster, MonsterLootTable, CombatSession, CombatLog, Dungeon, DungeonFloor, DungeonFloorMonster, DungeonSession

**Warfare:**
Army, ArmyUnit, War, WarParticipant, WarGoal, Battle, BattleParticipant, Siege, SupplyLine, PeaceTreaty, MercenaryCompany

**Religion:**
Religion, Belief, ReligionMember, ReligiousStructure, KingdomReligion, ReligiousAction

**Festivals & Events:**
FestivalType, Festival, FestivalParticipant, TournamentType, Tournament, TournamentCompetitor, TournamentMatch, RoyalEvent

**Disease & Health:**
DiseaseType, DiseaseOutbreak, DiseaseInfection, DiseaseImmunity, QuarantineOrder

**Disasters & Infrastructure:**
DisasterType, Disaster, BuildingDamage, BuildingType, Building, ConstructionProject

**Marriage & Dynasties:**
Dynasty, DynastyMember, DynastyEvent, DynastyAlliance, Marriage, MarriageProposal, Birth, SuccessionRule, InheritanceClaim

**Other:**
Quest, PlayerQuest, DailyTask, PlayerDailyTask, Message, Charter, CharterSignatory, SettlementRuin, Horse, PlayerHorse, WorldState

### Services (38+)

**Core:**
EnergyService, InventoryService, BirthService, BankService, HealerService, GatheringService, CraftingService, DailyTaskService, QuestService, PortService, JobService, RoleService, MigrationService, ElectionService, TaxService, ChatService, DocketService, LootService, CombatService, DungeonService, ReligionService, CharterService, TravelService, StableService, SocialClassService, CrimeService

**Advanced Systems:**
LegitimacyService, CaravanService, FestivalService, DiseaseService, DisasterService, ArmyService, WarService, BattleService, SiegeService, DynastyService, MarriageService

---

## Part 17: Technical Standards

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

- Inertia.js for all pages (no separate API)
- Services handle business logic, controllers are thin
- All money operations through BankService
- All XP operations through skill/stat models
- Scheduled jobs for time-based updates (aging, decay, seasons)
- Use existing UI components from `resources/js/components/ui/`

### Commands

```bash
sail artisan test              # Run tests
npm run build                  # Type check frontend
composer lint && npm run lint  # Format code
sail artisan schedule:run      # Run scheduled jobs
```

### Testing Requirements

- Feature tests for all new endpoints
- Unit tests for service methods
- Factory patterns for all models
- Seeders for development data

---

## Part 18: UI Design System

### File Structure
```
resources/js/
├── pages/{Feature}/Index.tsx      # Main feature page
├── pages/{Feature}/Show.tsx       # Detail view
├── pages/{Feature}/NotHere.tsx    # Location-gated fallback
├── components/ui/                 # Reusable UI components
└── layouts/app-layout.tsx         # Main layout wrapper
```

### Page Template
```tsx
import { Head, usePage } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

interface PageProps {
    // Define props from controller
    [key: string]: unknown;
}

export default function FeaturePage() {
    const { ...props } = usePage<PageProps>().props;

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Feature', href: '#' },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Feature" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-y-auto p-4">
                {/* Content */}
            </div>
        </AppLayout>
    );
}
```

### Visual Design

- **Font:** `font-pixel` for headers and labels
- **Colors:** Stone/amber/green/red/purple palette on dark backgrounds
- **Cards:** `rounded-xl border-2 border-{color}-500/50 bg-{color}-900/20 p-4`
- **Buttons:** `rounded border-2 border-{color}-600/50 bg-{color}-900/20 px-4 py-2 font-pixel text-xs`
- **Status badges:** `rounded px-1.5 py-0.5 font-pixel text-[10px]`

### Available UI Components
- `alert`, `avatar`, `badge`, `breadcrumb`, `button`, `card`
- `checkbox`, `collapsible`, `dialog`, `dropdown-menu`
- `icon`, `input`, `label`, `select`, `separator`
- `sheet`, `sidebar`, `skeleton`, `spinner`, `textarea`, `toggle`, `tooltip`

---

## Part 19: What Makes Myrefell Different

### vs. Traditional MMOs

| Traditional MMO | Myrefell |
|-----------------|----------|
| Kill monsters to level | Train stats daily, gain power through society |
| Fixed NPC quest givers | Player-run institutions, NPCs fill gaps |
| Global auction house | Local markets, trade routes |
| Guilds are social clubs | Guilds control economies |
| PvP is opt-in arenas | War is political, affects everyone |

### vs. Strategy Games

| Strategy Game | Myrefell |
|---------------|----------|
| You control a nation | You are one person |
| Abstract population | Each person (player/NPC) matters |
| Instant actions | Time-delayed consequences |
| Single player focus | Massively multiplayer |

### vs. eRepublik

| eRepublik | Myrefell |
|-----------|----------|
| Modern/military theme | Medieval theme |
| Abstract economy | Crafting, gathering, agriculture |
| Simple combat clicks | RPG stat training |
| Countries pre-exist | Player-founded settlements possible |
| NPCs don't exist | NPCs live full lives |
