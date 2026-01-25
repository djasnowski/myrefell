# Myrefell PRD v2 - Player-Driven Medieval MMO

## Vision

Myrefell is a persistent, browser-based massively multiplayer game set in a medieval mirror world. It functions as a medieval analogue to eRepublik: a player-driven society where thousands of players shape politics, economy, religion, and warfare over time.

Players begin as common subjects within a settlement and may rise through economic, military, political, or religious paths. **There are no character classes or traditional levels.** Progression is achieved through:
- Offices held
- Assets controlled
- Reputation earned
- Influence wielded

Power is asymmetric by design. Only eligible players may vote, command armies, or rule. Access to authority must be earned through social systems, not grind.

**But you are still one person.** You have combat stats (Attack, Strength, Defense), skills to train, equipment to wear, and places to go to become stronger. The RPG training loop exists alongside the political simulation.

**Tech Stack:** Laravel 12 + React 19 + Inertia.js + PostgreSQL + Redis + Tailwind CSS

---

## Core Design Philosophy

### Player-Driven, Not NPC-Driven

| Principle | Implementation |
|-----------|----------------|
| Players run institutions | Guilds, governments, churches are player-controlled |
| NPCs fill gaps | NPCs only hold roles when no player wants them |
| Emergent conflict | Wars, famines, heresies emerge from player decisions |
| Long-term consequences | Excommunication, exile, dynasty collapse are permanent |

### No Pay-to-Win

| Allowed | Forbidden |
|---------|-----------|
| Cosmetics | Stat boosts for money |
| Quality-of-life | Exclusive power items |
| Subscription perks (convenience) | Skipping progression |

### Slow Information Flow

- **No global chat or feeds** - Information travels at the speed of messengers
- **Local knowledge only** - You know what happens in your settlement
- **Rumors spread** - News from distant lands may be delayed or wrong
- **Spies are valuable** - Intelligence gathering is a real profession

### Asymmetric Power

Not everyone can:
- Vote (must be freeman or higher)
- Hold office (must meet requirements)
- Command armies (must be appointed)
- Own land (must be granted or purchased)

Power is earned through the social fabric, not accumulated through grinding.

---

## Part 1: The Individual (RPG Layer)

Despite the political simulation, **you are one person** with a body, skills, and daily life.

### Combat Stats

Every player has three core combat attributes:

| Stat | Purpose | Training |
|------|---------|----------|
| **Attack (ATK)** | Damage dealt in combat | Train at training grounds |
| **Strength (STR)** | Carry capacity, melee power | Manual labor, mining |
| **Defense (DEF)** | Damage reduction | Sparring, guard duty |

**Training Loop:**
- Visit training grounds daily (costs energy)
- Each training session grants small stat XP
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

**Death:**
- Respawn at home settlement
- Lose some carried gold (not banked)
- Equipment damaged but not lost
- Combat stats unaffected

### Equipment

- **28 inventory slots** for carried items
- **Equipment slots:** Head, chest, legs, feet, main hand, off hand, ring x2
- Equipment provides stat bonuses
- Equipment degrades with use, requires repair

### Daily Retention Loop

| Time | Activity | Reward |
|------|----------|--------|
| Daily | Train combat stats | +ATK/STR/DEF XP |
| Daily | Complete 3 daily tasks | Gold, items, XP |
| Daily | Work a job | Wages |
| Weekly | Attend religious service | Faith bonuses |
| Seasonal | Participate in festivals | Unique rewards |

---

## Part 2: The Settlement (Where You Live)

Every player has a **home settlement** where they are a resident. This determines:
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
| **Hamlet** | 5+ | Uses parent village | Parent village Elder |

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

All positions of power are held by players when possible. NPCs only serve as placeholders.

### Feudal Governance

| Level | Ruler | How Obtained | Powers |
|-------|-------|--------------|--------|
| Kingdom | King | Election by Barons | Declare war, set kingdom tax, appoint ministers |
| Barony | Baron | Appointed by King or elected | Collect barony tax, raise militia, judge crimes |
| Town | Mayor | Election by residents | Set town policies, manage guilds, town treasury |
| Village | Elder | Election by residents | Local disputes, village treasury, approve migrants |

### Elections

- **Eligibility to vote:** Must be freeman or higher class
- **Eligibility to run:** Depends on position (residency, reputation, class)
- **Election cycle:** Monthly for villages/towns, yearly for kingdoms
- **No confidence votes:** Can remove officials mid-term

### Communal Governance (Alternative)

Some settlements may adopt **communal governance**:
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

### Legitimacy

Rulers have a **legitimacy score** affecting their power:

| Factor | Effect on Legitimacy |
|--------|---------------------|
| Election margin | Higher = more legitimate |
| Time in office | Builds over time |
| Successful policies | Increases legitimacy |
| Failed wars | Decreases legitimacy |
| Scandals | Decreases legitimacy |
| Church support | Major boost |
| Church opposition | Major penalty |

Low legitimacy → No confidence votes more likely → Rebellion possible

---

## Part 4: Economy (Scarcity-Based)

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

**Player-owned businesses:**
- Players can own workshops, farms, mines
- Hire other players or NPCs
- Keep profits after wages and taxes

### Player-Run Guilds

Guilds are **player-founded and player-run** economic organizations:

| Aspect | Description |
|--------|-------------|
| Founding | 5+ players, registration fee, town approval |
| Membership | Apprentice → Journeyman → Master |
| Powers | Set craft prices, control quality, political lobbying |
| Governance | Guildmaster elected by Masters |
| Monopoly | Guild can petition for exclusive craft rights in town |

**Guild Wars:** Competing guilds may sabotage, undercut, or politically attack each other.

### Markets and Trade

- **Local markets:** Each settlement has prices based on local supply/demand
- **No global auction house:** You must travel to trade
- **Merchant caravans:** NPCs and players move goods between settlements
- **Trade routes:** Established paths with varying safety
- **Tariffs:** Kingdoms/baronies can tax goods crossing borders

### Currency

- **Single currency for MVP:** Gold pieces
- **Future:** Kingdom-specific currencies with exchange rates

### Taxes

| Level | Tax Type | Flow |
|-------|----------|------|
| Village | Head tax | Residents → Village treasury |
| Barony | Land tax | Villages → Barony treasury |
| Kingdom | Crown tax | Baronies → Kingdom treasury |
| Church | Tithe | Faithful → Church treasury |

Tax rates set by respective rulers. High taxes → emigration. Low taxes → weak treasury.

---

## Part 5: Warfare (Logistics-Driven)

War is not real-time combat. It is a strategic, logistics-heavy system where preparation matters more than clicking fast.

### Declaring War

Only **Kings** can declare war. Requirements:
- Casus belli (justification) - or suffer legitimacy penalty
- War chest (gold reserves)
- Army raised

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
2. Each day: calculate casualties based on
   - Army size and composition
   - Commander skill
   - Terrain
   - Supply status
   - Morale
3. Army with 0 morale retreats or surrenders
4. Siege battles have separate mechanics (starvation, assault)

**No player skill in combat resolution** - it's about preparation and logistics.

### Supply Lines

- Armies consume food daily
- Supply comes from home territory
- Cut supply lines → army starves → morale collapses
- Foraging damages local economy and reputation

### War Goals

| Goal | Victory Condition |
|------|-------------------|
| Conquest | Occupy enemy capital for 30 days |
| Border adjustment | Occupy target barony for 30 days |
| Humiliation | Defeat enemy army, force tribute |
| Holy war | Conquer infidel territory |

### Peace Treaties

Wars end through:
- Negotiated peace (terms agreed)
- Total victory (one side achieves war goal)
- White peace (status quo, both exhausted)

---

## Part 6: Religion (Player-Founded Faiths)

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

## Part 7: Law and Crime

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

### Trial System

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

## Part 8: Information and Communication

**There is no global feed.** Information flows realistically.

### Communication Channels

| Channel | Range | Speed |
|---------|-------|-------|
| Local chat | Same settlement | Instant |
| Messenger | Adjacent settlements | Hours |
| Courier | Distant settlements | Days |
| Rumor | Spreads organically | Variable |

### What You Know

- **Your settlement:** Full information
- **Adjacent settlements:** Delayed news (1 day)
- **Same barony:** Delayed news (2-3 days)
- **Same kingdom:** Weekly summaries
- **Other kingdoms:** Rumors only (may be false)

### Espionage

Spies can:
- Gather intelligence faster
- Verify rumors
- Plant false information
- Assassinate (high risk)

Spies are player characters with cover identities.

---

## Part 9: Succession and Dynasties

### Player Dynasties

Players can:
- Marry (other players or NPCs)
- Have children (NPC children grow up over time)
- Designate heirs
- Build dynasty reputation

### Succession Rules

When a ruler dies:

| Rule | Heir Selection |
|------|----------------|
| Primogeniture | Eldest child |
| Elective | Voted by peers |
| Appointment | Named successor |
| Seniority | Oldest family member |

### Disputed Succession

If succession is unclear:
- Multiple claimants may emerge
- Civil war possible
- External powers may intervene
- Legitimacy crisis until resolved

### Dynasty Reputation

| Factor | Effect |
|--------|--------|
| Offices held | +Reputation |
| Battles won | +Reputation |
| Scandals | -Reputation |
| Excommunication | -Reputation |
| Marriages | Reputation merges |

High dynasty reputation → easier elections, better marriages, more legitimacy

---

## Part 10: Retention Loops

### Daily Loop (5-10 minutes)

1. **Train** - Visit training grounds, gain combat stat XP
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

## Part 11: Implemented Features (Current State)

### Models (52 total)

**Core Player:**
User, PlayerSkill, PlayerInventory, Item, PlayerTitle, BankAccount, BankTransaction

**World:**
Kingdom, Barony, Town, Village

**Economy:**
EmploymentJob, PlayerEmployment, TaxCollection, LocationTreasury, TreasuryTransaction, SalaryPayment, CraftingOrder, LocationStockpile

**Governance:**
Role, PlayerRole, LocationNpc, Election, ElectionCandidate, ElectionVote, NoConfidenceVote, NoConfidenceBallot, MigrationRequest

**Combat:**
Monster, MonsterLootTable, CombatSession, CombatLog, Dungeon, DungeonFloor, DungeonFloorMonster, DungeonSession

**Religion:**
Religion, Belief, ReligionMember, ReligiousStructure, KingdomReligion, ReligiousAction

**Other:**
Quest, PlayerQuest, DailyTask, PlayerDailyTask, Message, Charter, CharterSignatory, SettlementRuin, Horse, PlayerHorse

### Services (24 total)

EnergyService, InventoryService, BirthService, BankService, HealerService, GatheringService, CraftingService, DailyTaskService, QuestService, PortService, JobService, RoleService, MigrationService, ElectionService, TaxService, ChatService, DocketService, LootService, CombatService, DungeonService, ReligionService, CharterService, TravelService, StableService

---

## Part 12: MVP Priorities

### Phase 1: Core Loop (Immediate)

**Goal:** A playable daily loop where you train, work, and interact locally.

| Feature | Status | Priority |
|---------|--------|----------|
| Combat stat training | Needed | Critical |
| Daily tasks | Done | - |
| Jobs and wages | Done | - |
| Local chat | Done | - |
| Settlement residency | Done | - |

**New work needed:**
- Add ATK/STR/DEF stats to User model
- Create training grounds location action
- Training action: costs energy, grants stat XP

### Phase 2: Economic Foundation

**Goal:** Scarcity-based economy with player businesses.

| Feature | Status | Priority |
|---------|--------|----------|
| Gathering skills | Done | - |
| Crafting | Done | - |
| Markets with supply/demand | Needed | High |
| Player-owned businesses | Needed | High |
| Resource consumption/decay | Needed | Medium |

### Phase 3: Political Depth

**Goal:** Meaningful elections and governance.

| Feature | Status | Priority |
|---------|--------|----------|
| Elections | Done | - |
| Roles and powers | Done | - |
| Legitimacy system | Needed | High |
| Laws and crime | Needed | High |
| Tax policy control | Partial | Medium |

### Phase 4: Warfare

**Goal:** Strategic, logistics-driven conflict.

| Feature | Status | Priority |
|---------|--------|----------|
| Army recruitment | Needed | High |
| Battle resolution | Needed | High |
| Supply lines | Needed | Medium |
| Siege mechanics | Needed | Medium |
| Peace treaties | Needed | Medium |

### Phase 5: Living World

**Goal:** Time passes, things change.

| Feature | Status | Priority |
|---------|--------|----------|
| Calendar/seasons | Needed | High |
| Food/agriculture | Needed | High |
| NPC lifecycle | Needed | Medium |
| Disasters/events | Needed | Low |

---

## Part 13: Technical Standards

### File Conventions

| Type | Location | Naming |
|------|----------|--------|
| Models | `app/Models/` | `{Name}.php` |
| Services | `app/Services/` | `{Name}Service.php` |
| Controllers | `app/Http/Controllers/` | `{Name}Controller.php` |
| Pages | `resources/js/pages/` | `{Feature}/Index.tsx` |
| Jobs | `app/Jobs/` | `{Action}{Noun}.php` |

### Coding Standards

- Inertia.js for all pages (no separate API)
- Services handle business logic
- All money through BankService
- All XP through skill models
- Scheduled jobs for time-based updates

### Commands

```bash
sail artisan test          # Run tests
npm run build              # Type check frontend
composer lint && npm run lint  # Format code
sail artisan schedule:run  # Run scheduled jobs
```

---

## Part 14: What Makes This Different

### vs. Traditional MMOs

| Traditional MMO | Myrefell |
|-----------------|----------|
| Kill monsters to level | Train stats daily, gain power through society |
| Fixed NPC quest givers | Player-run institutions |
| Global auction house | Local markets, trade routes |
| Guilds are social clubs | Guilds control economies |
| PvP is opt-in arenas | War is political, affects everyone |

### vs. Strategy Games

| Strategy Game | Myrefell |
|---------------|----------|
| You control a nation | You are one person |
| Abstract population | Each person matters |
| Instant actions | Time-delayed consequences |
| Single player focus | Massively multiplayer |

### vs. eRepublik

| eRepublik | Myrefell |
|-----------|----------|
| Modern/military theme | Medieval theme |
| Abstract economy | Crafting and gathering |
| Simple combat | RPG stat training |
| Countries exist | Player-founded settlements possible |

---

## Appendix: Data Model Sketches

### Combat Stats (Add to User)

```php
// Add to users table
$table->integer('attack_xp')->default(0);
$table->integer('strength_xp')->default(0);
$table->integer('defense_xp')->default(0);

// Computed levels from XP (same formula as skills)
public function getAttackLevelAttribute(): int
{
    return $this->calculateLevel($this->attack_xp);
}
```

### Training Action

```php
// TrainingService.php
public function train(User $user, string $stat): void
{
    $this->energyService->consume($user, 10);

    $xpGain = 10; // Base XP
    $xpGain *= $user->getTrainingMultiplier(); // Equipment bonus

    $user->increment("{$stat}_xp", $xpGain);
}
```

### Legitimacy System

```php
// Add to rulers (kings, barons, etc.)
$table->integer('legitimacy')->default(50); // 0-100

// Factors affecting legitimacy
- Election margin: +/- 20
- Time in office: +1 per month (max +20)
- Won war: +10
- Lost war: -20
- Church support: +15
- Church opposition: -25
- Scandal: -10 to -30
```

### Army Model

```php
Schema::create('armies', function (Blueprint $table) {
    $table->id();
    $table->foreignId('owner_id'); // Kingdom or player
    $table->string('owner_type'); // polymorphic
    $table->foreignId('commander_id')->nullable(); // Player commanding
    $table->foreignId('location_id');
    $table->string('location_type');
    $table->enum('status', ['mustering', 'marching', 'besieging', 'fighting', 'disbanded']);
    $table->integer('morale')->default(100);
    $table->integer('supplies')->default(100); // Days of food
    $table->timestamps();
});

Schema::create('army_units', function (Blueprint $table) {
    $table->id();
    $table->foreignId('army_id');
    $table->enum('unit_type', ['levy', 'men_at_arms', 'knights', 'archers', 'mercenary']);
    $table->integer('count');
    $table->integer('equipment_quality')->default(50); // 0-100
    $table->timestamps();
});
```
