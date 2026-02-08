# Land Ownership & Housing - Exploration Summary

## What Already Exists That We Can Build On

### Established patterns:
- **Building model** already has `owner_id`, housing category (Cottage, Manor House), and status tracking (planned -> under_construction -> operational -> damaged)
- **FarmPlot** provides a perfect ownership pattern: `user_id` + polymorphic `location_type`/`location_id`
- **PlayerBusiness** shows how to handle player-owned property with treasury, upkeep, and status
- **BankAccount** demonstrates per-location gold storage
- **DungeonLootStorage** shows temporary item storage with expiry
- **User model** already has `home_location_type` / `home_location_id` polymorphic fields
- **SocialClassService** already has `canOwnProperty()` which blocks Serfs from owning property
- **BlessingEffectService** already merges buffs from blessings, beliefs, and HQ features - housing buffs plug into this

### Storage patterns in place:
- PlayerInventory: slot-based (50 slots), stackable items, decay tracking
- LocationStockpile: quantity-based, per-location
- BusinessInventory: simple quantity-per-item
- BankAccount: gold-only, per-location

### Existing buff effect keys (housing buffs must use these same keys to stack naturally):
- Combat: `attack_bonus`, `strength_bonus`, `defense_bonus`, `all_combat_stats_bonus`
- HP/Energy: `max_hp_bonus`, `hp_regen_bonus`, `energy_regen_bonus`, `energy_recovery_bonus`
- XP: `all_xp_bonus`, `combat_xp_bonus`, `gathering_xp_bonus`, `crafting_xp_bonus`, `{skill}_xp_bonus`
- Gathering: `{activity}_yield_bonus` (fishing, mining, woodcutting, etc.)
- Gold: `gold_bonus`, `gold_find_bonus`, `rare_drop_bonus`
- Special: `action_cooldown_seconds`, `blessing_slots`

---

## Economy Scale (for pricing)

### Income Sources

| Income Source | Gold/Day |
|---|---|
| Jobs | 30-70g |
| Quests | 25-50g |
| Dungeons | 50-2,500g |
| New player starts with | 100g |

### Existing Gold Sinks

| Gold Sink | Cost |
|---|---|
| Guild founding | 50,000g |
| Horses | 3,000 - 500,000g |
| Guild weekly dues | 100g |
| Taxes | 0-50% daily |

---

## Social Class & Title Restrictions

Housing is gated by both social class and title tier. The game already enforces `canOwnProperty()` which blocks Serfs entirely.

### By Social Class

| Social Class | Rank | Can Own Property? | Max Housing Tier |
|---|---|---|---|
| **Serf** | 1 | No | None - cannot own property |
| **Freeman** | 2 | Yes | Cottage |
| **Burgher** | 3 | Yes | House |
| **Clergy** | 3 | Yes | House |
| **Noble** | 4 | Yes | Noble Estate |

### By Title Tier

Title tier further gates which housing a Noble can access. A Noble with only a Knight title shouldn't have a Noble Estate - that's for landed nobility.

| Title | Tier | Unlocks Housing |
|---|---|---|
| Peasant | 2 | Cottage |
| Freeman | 3 | Cottage |
| Yeoman | 4 | House |
| Squire | 5 | Manor |
| Knight | 6 | Manor |
| Baronet | 7 | Estate |
| Baron+ | 8+ | Noble Estate |

### Combined Requirements

The effective housing tier is the **minimum** of what your social class and title allow:

| Housing Tier | Social Class Required | Min Title Tier | Purchase Cost |
|---|---|---|---|
| **Cottage** | Freeman+ | Peasant (2) | 5,000g |
| **House** | Burgher+ | Yeoman (4) | 15,000g |
| **Manor** | Noble | Squire (5) | 50,000g |
| **Estate** | Noble | Baronet (7) | 150,000g |
| **Noble Estate** | Noble | Baron (8) | 300,000g |

### Examples

- **Freeman, Peasant title** -> Can buy a Cottage only
- **Burgher, Yeoman title** -> Can buy up to a House
- **Noble, Knight title** -> Can buy up to a Manor
- **Noble, Baron title** -> Can buy up to a Noble Estate
- **Serf** -> Cannot buy anything (blocked by `canOwnProperty()`)
- **Burgher, Knight title** -> Can buy up to a House (class caps at House despite Knight title)
- **Noble, Peasant title** -> Can buy a Cottage only (title caps despite Noble class)

---

## Proposed Housing Tiers

| Tier | Purchase | Weekly Upkeep | Max Rooms |
|---|---|---|---|
| **Cottage** | 5,000g | 50g | 2 |
| **House** | 15,000g | 150g | 4 |
| **Manor** | 50,000g | 400g | 6 |
| **Estate** | 150,000g | 800g | 9 |
| **Noble Estate** | 300,000g | 1,500g | 12 |

### Upgrade Path

Players can upgrade their existing house to the next tier (paying the difference) when they meet the class/title requirements. All built rooms and features carry over to the upgrade:

| Upgrade | Cost (Difference) | Requires |
|---|---|---|
| Cottage -> House | 10,000g | Burgher+ & Yeoman+ |
| House -> Manor | 35,000g | Noble & Squire+ |
| Manor -> Estate | 100,000g | Noble & Baronet+ |
| Estate -> Noble Estate | 150,000g | Noble & Baron+ |

---

## Home Storage (Bank-Style)

Every house comes with built-in storage that works like a bank - items stack by type with no slot limit, just a total capacity. This is separate from the player's 50-slot inventory backpack.

### Storage Capacity by House Tier

| Tier | Storage Capacity |
|---|---|
| **Cottage** | 200 items |
| **House** | 300 items |
| **Manor** | 400 items |
| **Estate** | 450 items |
| **Noble Estate** | 500 items |

### How It Works

- **Bank-style stacking**: All items of the same type stack together regardless of the item's normal max_stack. Storage shows item type + total quantity (e.g., "Iron Ore x247").
- **Capacity = total items stored**, not number of unique types. Storing 200 Iron Ore counts as 200 toward the cap.
- **Deposit/withdraw** when at home location. Transfer items between inventory and home storage.
- **No decay** on stored items - your home preserves everything.
- **Protected from theft** - unlike carrying gold, home storage is safe.
- **Survives disasters** - stored items are not affected by building damage.
- **Accessible only at home** - must travel to your home location to deposit/withdraw.

### Storage Upgrades

Additional storage can be unlocked by building the **Cellar** room:

| Cellar Tier | Bonus Storage | Total with Noble Estate |
|---|---|---|
| Small Cellar | +50 | 550 |
| Cellar | +100 | 600 |
| Deep Cellar | +150 | 650 |
| Grand Cellar (Noble Estate) | +200 | 700 |

---

## Room System (OSRS-Inspired)

Each house tier grants room slots. Players choose which rooms to build, each costing gold and materials. Rooms persist through house upgrades. Each room provides specific utility, buffs, or quality-of-life improvements.

### Available Rooms

#### Crafting Rooms

**Kitchen** (available at: Cottage+)
- Build cost: 2,000g + 15 Planks
- Cook food at home without traveling to a tavern
- **Buff: +5% Cooking XP** when cooking at home (`cooking_xp_bonus: 5`)
- Upgrade: Basic Stove -> Iron Range (+8% XP) -> Steel Range (+12% XP)
- Higher tiers reduce burn chance

**Workshop** (available at: House+)
- Build cost: 5,000g + 20 Planks + 10 Iron Bars
- Craft items at home (general crafting recipes)
- **Buff: +5% Crafting XP** when crafting at home (`crafting_xp_bonus: 5`)
- Upgrade: Basic Bench -> Sturdy Bench (+8% XP) -> Master Bench (+12% XP, unlocks advanced recipes)

**Forge** (available at: Manor+)
- Build cost: 15,000g + 30 Steel Bars + 50 Coal
- Smelt ore and smith items at home
- **Buff: +5% Smithing XP** when smithing at home (`smithing_xp_bonus: 5`)
- Upgrade: Basic Anvil -> Iron Forge (+8% XP) -> Master Forge (+12% XP, +5% chance to save a bar)

**Apothecary Lab** (available at: Manor+)
- Build cost: 12,000g + 20 Herbs (assorted) + 10 Glass Vials
- Brew potions at home
- **Buff: +5% Herblore XP** at home (`herblore_xp_bonus: 5`)
- Upgrade: Basic Table -> Alchemist's Table (+8% XP) -> Master Lab (+12% XP, +10% chance for double potion)

#### Restoration Rooms

**Bedroom** (available at: Cottage+)
- Build cost: 1,500g + 10 Planks
- Rest at home to restore energy faster
- **Buff: +10% energy regeneration** (`energy_regen_bonus: 10`)
- Upgrade: Straw Bed -> Wooden Bed (+15%) -> Feather Bed (+25%) -> Royal Bed (+40%, Estate+)

**Hearth Room** (available at: House+)
- Build cost: 3,000g + 15 Planks + 5 Iron Bars
- Warm fireplace that provides comfort
- **Buff: +3 max HP** (`max_hp_bonus: 3`)
- Upgrade: Stone Hearth (+3 HP) -> Brick Fireplace (+5 HP) -> Grand Fireplace (+8 HP, +5% HP regen)

**Bath House** (available at: Manor+)
- Build cost: 15,000g + 30 Stone + 10 Iron Bars + 5 Bronze Bars
- Bathe at home to accelerate disease recovery and gain temporary buffs
- **Buff: +50% disease recovery speed**, cure minor diseases when bathing
- Upgrade: Cold Bath (cure minor, +50% recovery) -> Heated Baths (cure moderate, +75% recovery, +3 max HP buff for 24hrs) -> Royal Baths (cure ALL diseases, full HP restore, +5 max HP buff for 24hrs, +10% energy regen buff for 24hrs)
- Royal Baths effectively replaces the need for a healer for most situations

**Infirmary** (available at: Estate+)
- Build cost: 25,000g + 10 Herbs + 5 Mithril Bars
- Permanent healing station at home
- **Buff: +20% HP regen** (`hp_regen_bonus: 20`)
- Upgrade: Sick Bed -> Apothecary Ward (auto-cure poison on arrival home) -> Royal Infirmary (full HP restore on arrival, cure all, +25% HP regen)

#### Buff Rooms

**Study** (available at: House+)
- Build cost: 5,000g + 20 Planks
- Scholarly pursuits grant passive XP bonus
- **Buff: +3% all XP** (`all_xp_bonus: 3`)
- Upgrade: Writing Desk (+3%) -> Scholar's Study (+5%) -> Grand Library (+8%, Manor+)
- Grand Library also grants +1 active blessing slot (`blessing_slots: 1`)

**Chapel** (available at: Manor+)
- Build cost: 20,000g + 30 Limestone + 10 Gold Bars
- Pray at home for enhanced prayer training
- **Buff: +10% Prayer XP** (`prayer_xp_bonus: 10`)
- Upgrade: Wooden Altar (+10%) -> Stone Altar (+15%) -> Gilded Altar (+25%, requires 50 Prayer)
- Gilded Altar also provides +5% devotion gain for religion activities

**War Room** (available at: Estate+)
- Build cost: 30,000g + 20 Steel Bars + 10 Planks
- Strategic planning provides combat edge
- **Buff: +5% all combat XP** (`all_combat_xp_bonus: 5`)
- Upgrade: Map Table (+5%) -> Tactical Room (+8%, +2 attack bonus) -> Command Center (+12%, +3 attack bonus, +3 defense bonus)

#### Prestige Rooms

**Trophy Hall** (available at: Manor+)
- Build cost: 10,000g + 15 Planks + 5 Gold Bars
- Mount trophies from defeated high-level monsters
- **1% chance** on killing a high-level monster (level 50+) to receive a trophy drop
- Each trophy records: monster name, date killed, location killed
- Trophies are displayed on the player's profile page for others to see
- **Each mounted trophy grants a permanent micro-buff:**
  - Combat monster trophy: +1 attack or +1 strength
  - Beast trophy: +1 defense
  - Undead trophy: +1 prayer bonus
  - Boss trophy: +2 to a random combat stat
- Cap of 10 trophies at base, 15 at Stone Pedestals, 20 at Gilded Gallery
- Upgrade: Wooden Displays (10 trophies) -> Stone Pedestals (15 trophies) -> Gilded Gallery (20 trophies, Noble Estate)
- Trophies are permanent and carry through house upgrades

**Stable** (available at: Manor+)
- Build cost: 8,000g + 25 Planks + 10 Iron Bars
- Store horses at home instead of paying town stable fees
- **Buff: Horses rest 20% faster** at home
- Upgrade: Small Stable (1 horse) -> Stable (2 horses) -> Grand Stable (4 horses, Estate+)

**Garden** (available at: House+)
- Build cost: 3,000g + 5 Seeds (assorted)
- Personal farm plots tied to your house
- **Buff: +5% Farming XP** on home plots (`farming_xp_bonus: 5`)
- Upgrade: Small Garden (1 plot) -> Garden (2 plots) -> Grand Garden (3 plots, Manor+)
- Crops in home garden decay 25% slower

#### Utility Rooms

**Servant Quarters** (available at: Estate+)
- Build cost: 15,000g + 20 Planks
- Hire an NPC servant (requires weekly wage on top of house upkeep)
- Servant can: sell items at market, fetch items from bank, tend garden
- Upgrade: Maid (basic tasks, 50g/wk wage) -> Butler (faster, market access, 100g/wk) -> Head Servant (all tasks, auto-tend garden, 200g/wk, Noble Estate)

**Cellar** (available at: House+)
- Build cost: 4,000g + 20 Stone
- Expands home storage capacity (see Storage section above)
- Underground storage immune to disaster damage even if house is damaged
- Upgrade: Small Cellar (+50 storage) -> Cellar (+100) -> Deep Cellar (+150, Manor+) -> Grand Cellar (+200, Noble Estate)

**Wine Cellar** (upgrade from Cellar, available at: Estate+)
- Upgrade cost: 20,000g + 10 Oak Barrels + 20 Grapes
- Unlocks wine brewing and aging using the Cooking skill
- Wines age over game-weeks, improving in quality and buff strength
- Aged wines provide temporary buffs when consumed (like potions but home-brewed):

| Wine | Aging Time | Buff | Duration |
|---|---|---|---|
| Peasant's Red | 1 week | +5% energy regen | 1 hour |
| Barony White | 2 weeks | +3 max HP, +5% energy regen | 2 hours |
| Lord's Reserve | 4 weeks | +5 max HP, +10% energy regen, +3% all XP | 3 hours |
| Vintage Royal | 8 weeks | +8 max HP, +15% energy regen, +5% all XP, +5% combat XP | 4 hours |
| Noble's Legacy | 16 weeks | +10 max HP, +20% energy regen, +8% all XP, +8% combat XP, +2 attack | 6 hours |

- Wine storage: 5 barrels at base, 10 at upgraded Wine Cellar, 20 at Grand Wine Cellar
- Each barrel holds one batch of wine that ages independently
- Wines are consumable items - once drunk, the barrel is freed for a new batch
- Upgrade: Wine Cellar (5 barrels) -> Aged Wine Cellar (10 barrels, better grape yield) -> Grand Wine Cellar (20 barrels, exclusive Noble's Legacy recipe, Noble Estate)

---

## Room Unlock Summary by House Tier

### Cottage (2 rooms)
| Room | Build Cost | Key Benefit |
|---|---|---|
| Bedroom | 1,500g | +10% energy regen |
| Kitchen | 2,000g | Cook at home, +5% Cooking XP |

### House (4 rooms) - unlocks everything above plus:
| Room | Build Cost | Key Benefit |
|---|---|---|
| Workshop | 5,000g | Craft at home, +5% Crafting XP |
| Study | 5,000g | +3% all XP |
| Hearth Room | 3,000g | +3 max HP |
| Garden | 3,000g | Personal farm plot, +5% Farming XP |
| Cellar | 4,000g | +50-100 storage, disaster-proof |

### Manor (6 rooms) - unlocks everything above plus:
| Room | Build Cost | Key Benefit |
|---|---|---|
| Forge | 15,000g | Smith at home, +5% Smithing XP |
| Apothecary Lab | 12,000g | Brew at home, +5% Herblore XP |
| Chapel | 20,000g | +10% Prayer XP |
| Trophy Hall | 10,000g | Mount monster trophies for permanent stat buffs |
| Stable | 8,000g | Store horses at home |
| Bath House | 15,000g | Cure diseases, +50% recovery speed |

### Estate (9 rooms) - unlocks everything above plus:
| Room | Build Cost | Key Benefit |
|---|---|---|
| Infirmary | 25,000g | +20% HP regen, auto-heal |
| War Room | 30,000g | +5% combat XP, combat stat bonuses |
| Servant Quarters | 15,000g | NPC servant for automation |
| Wine Cellar | 20,000g | Brew wines for temporary buffs (Cellar upgrade) |

### Noble Estate (12 rooms) - unlocks highest upgrades for all rooms
- Royal Bed (+40% energy regen)
- Grand Library (+8% all XP, +1 blessing slot)
- Gilded Altar (+25% Prayer XP)
- Command Center (+12% combat XP, +3 atk, +3 def)
- Gilded Gallery (20 trophy slots)
- Grand Stable (4 horses)
- Royal Baths (cure all, full restore, +5 HP buff, +10% energy buff)
- Royal Infirmary (full HP on arrival, +25% HP regen)
- Grand Wine Cellar (20 barrels, Noble's Legacy recipe)
- Grand Cellar (+200 storage)
- Head Servant (auto-tend garden, market access)
- Grand Garden (3 farm plots)

---

## Buff Summary

### Passive Buffs (always active while player owns the room)

| Room | Effect Key | Base | Upgraded |
|---|---|---|---|
| Bedroom | `energy_regen_bonus` | +10% | +40% (Royal Bed) |
| Hearth Room | `max_hp_bonus` | +3 | +8 (Grand Fireplace) |
| Study | `all_xp_bonus` | +3% | +8% (Grand Library) |
| Study (Grand) | `blessing_slots` | - | +1 |
| Chapel | `prayer_xp_bonus` | +10% | +25% (Gilded Altar) |
| War Room | `all_combat_xp_bonus` | +5% | +12% (Command Center) |
| War Room (upgraded) | `attack_bonus` | - | +3 |
| War Room (upgraded) | `defense_bonus` | - | +3 |
| Infirmary | `hp_regen_bonus` | +20% | +25% (Royal Infirmary) |
| Trophy Hall | various combat stats | +1 per trophy | up to +20 trophies |

### Active-at-Home Buffs (only apply when crafting/training at your house)

| Room | Effect Key | Base | Upgraded |
|---|---|---|---|
| Kitchen | `cooking_xp_bonus` | +5% | +12% (Steel Range) |
| Workshop | `crafting_xp_bonus` | +5% | +12% (Master Bench) |
| Forge | `smithing_xp_bonus` | +5% | +12% (Master Forge) |
| Apothecary Lab | `herblore_xp_bonus` | +5% | +12% (Master Lab) |
| Garden | `farming_xp_bonus` | +5% | +5% |

### Temporary Buffs (activated by using a room feature)

| Room | Action | Buff | Duration |
|---|---|---|---|
| Bath House | Bathe | Cure disease + recovery speed | Passive while owned |
| Royal Baths | Bathe | Full restore + +5 max HP + +10% energy regen | 24 hours |
| Royal Infirmary | Arrive home | Full HP restore, cure all | Instant |
| Wine Cellar | Drink wine | Varies by wine type (see wine table) | 1-6 hours |

### Design Decision: Passive vs At-Home vs Temporary

- **Passive buffs** (Bedroom, Study, War Room, Chapel, Trophy Hall) are always active regardless of where the player is. These represent lasting benefits of having a comfortable home, education, and preparation. This makes housing worth investing in even for players who mostly adventure.
- **Crafting buffs** (Kitchen, Workshop, Forge, Lab) only apply when the player is physically at their house and using the room. This incentivizes players to return home to craft rather than using public facilities, but doesn't make public forges/taverns obsolete.
- **Temporary buffs** (Bath House, Wine Cellar) require the player to visit home and use the feature. The buff then lasts for a set duration even after leaving home. This creates a gameplay loop of returning home periodically to "refresh" buffs before heading out.
- **Restoration buffs** (Infirmary, Hearth) are passive - they represent the long-term health benefits of a good home.

---

## Trophy Hall Deep Dive

### Trophy Drop Mechanic

- **1% chance** to receive a trophy when killing a monster level 50+
- Trophy is a special non-stackable item that drops into inventory
- Trophy records: monster name, monster level, date killed, location
- Trophies cannot be traded or sold - they are soulbound
- Trophies must be brought home and mounted in the Trophy Hall

### Trophy Types & Buffs

| Monster Type | Trophy Name Example | Buff Per Trophy |
|---|---|---|
| Beast | "Dire Wolf Head" | +1 defense |
| Goblinoid | "Goblin Warlord Banner" | +1 attack |
| Undead | "Lich's Skull" | +1 prayer bonus |
| Humanoid | "Bandit Lord's Blade" | +1 strength |
| Dragon | "Dragon Scale" | +2 defense |
| Demon | "Demon Horn" | +2 attack |
| Dungeon Boss | "Heart of [Boss Name]" | +2 to random combat stat |

### Trophy Display on Profile

Other players visiting your profile page can see:
- Trophy name
- "Slain on [date] at [location]"
- The buff it provides
- A visual representation (icon or small image)

This creates a prestige/bragging system - high-level players can show off their kills. The permanent micro-buffs make Trophy Hall one of the most valuable long-term rooms.

---

## Room Upgrade Materials

Each room has 3-4 upgrade tiers. Upgrading requires gold + materials + sometimes a skill level:

### Example Upgrade Paths

**Bedroom:**
| Tier | Cost | Materials | Skill Req | Buff |
|---|---|---|---|---|
| Straw Bed | (included in build) | - | - | +10% energy regen |
| Wooden Bed | 2,000g | 10 Planks | 15 Crafting | +15% energy regen |
| Feather Bed | 8,000g | 20 Planks, 10 Cloth | 30 Crafting | +25% energy regen |
| Royal Bed | 25,000g | 20 Mahogany, 5 Gold Bars, 10 Silk | 50 Crafting | +40% energy regen |

**Study:**
| Tier | Cost | Materials | Skill Req | Buff |
|---|---|---|---|---|
| Writing Desk | (included in build) | - | - | +3% all XP |
| Scholar's Study | 8,000g | 15 Planks, 10 Leather | 25 Crafting | +5% all XP |
| Grand Library | 30,000g | 30 Mahogany, 20 Leather, 5 Gold Bars | 45 Crafting | +8% all XP, +1 blessing slot |

**Chapel:**
| Tier | Cost | Materials | Skill Req | Buff |
|---|---|---|---|---|
| Wooden Altar | (included in build) | - | - | +10% Prayer XP |
| Stone Altar | 15,000g | 30 Limestone, 5 Silver Bars | 30 Prayer | +15% Prayer XP |
| Gilded Altar | 50,000g | 20 Marble, 10 Gold Bars | 50 Prayer | +25% Prayer XP, +5% devotion |

**Bath House:**
| Tier | Cost | Materials | Skill Req | Buff |
|---|---|---|---|---|
| Cold Bath | (included in build) | - | - | Cure minor disease, +50% recovery |
| Heated Baths | 12,000g | 20 Stone, 10 Iron Bars, 5 Coal | 25 Crafting | Cure moderate, +75% recovery, +3 HP (24hr) |
| Royal Baths | 40,000g | 30 Marble, 10 Gold Bars, 5 Mithril Bars | 45 Crafting | Cure all, full restore, +5 HP (24hr), +10% energy (24hr) |

---

## Missed Upkeep Consequences

1. **1 week missed** - Warning notification
2. **2 weeks missed** - House condition starts degrading (-10/week), passive buffs deactivate
3. **4 weeks missed** - House becomes "abandoned", storage items remain but inaccessible
4. **8 weeks missed** - House is lost, storage items dropped to location stockpile, rooms and upgrades are gone

---

## Architecture Approach

### New Models:
- **`PlayerHouse`** - ownership, tier, condition, location, social class/title checks
- **`HouseStorage`** - bank-style storage (item_id + quantity, no slots), capacity based on house tier + cellar
- **`HouseRoom`** - which rooms are built in a house, with upgrade tier
- **`HouseRoomType`** - defines available rooms, costs, requirements, buff effects (seeded)
- **`HouseTrophy`** - mounted trophies with monster info, date, location, buff type
- **`HouseWineBarrel`** - wine brewing/aging tracker (wine type, started_at, ready_at, quality)
- **`HouseTransaction`** - purchase, upkeep, upgrade, repair history

### New Service:
- **`HouseService`** - buy, upgrade house, build rooms, maintain, transfer items, class/title validation
- **`HouseBuffService`** - calculates active buffs from all built rooms + trophies, integrates with BlessingEffectService
- **`WineService`** - wine brewing, aging, consumption, buff application

### New Controller:
- **`HouseController`** - routes for house management, room building, storage access, trophy mounting, wine management

### Scheduled Job:
- **Weekly maintenance job** - hooks into existing scheduled job system, deducts upkeep, degrades condition on missed payments, deactivates buffs when degraded
- **Wine aging job** - updates wine barrel status as wines mature

### Integration Points:
- `BlessingEffectService::getActiveEffects()` - merge in housing buffs (passive ones always, crafting ones when at home)
- `SocialClassService::canOwnProperty()` - already exists, gates Serfs out
- `User::getSocialClassRank()` - check class tier for housing eligibility
- `User::highestTitle()` - check title tier for housing eligibility
- `DisasterService` - existing disaster damage applies to player houses
- `FarmPlot` - Garden room creates farm plots tied to house
- `GatheringService` / `CraftingService` - check for at-home crafting buffs
- `CombatService` - War Room buffs + Trophy buffs feed into existing combat calculations
- `CombatService` (monster kill) - 1% trophy drop chance on level 50+ monsters
- `HealerService` - Bath House / Infirmary provide alternative healing
- `DiseaseService` - Bath House accelerates disease recovery

The polymorphic location pattern and Building model infrastructure means we're not starting from scratch. The buff system slots directly into the existing `BlessingEffectService` using the same effect keys that blessings, beliefs, and HQ features already use.
