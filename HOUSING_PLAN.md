# Construction Skill & Player-Owned Houses

## Overview

Construction is a new **buyable skill** (level 1-99) that lets players build, upgrade, and customize a personal house. It is designed to be the **single largest gold sink in the game** — a true endgame investment.

The house is viewed from a **top-down 2D eagle-eye perspective** where players place rooms on a grid and fill each room with furniture at specific **hotspot** positions. Higher Construction levels unlock better rooms, better furniture, and more powerful utility.

### Economy Context

| Metric | Current Value |
|---|---|
| Richest player | ~520,000g |
| Average active player | ~15,000g |
| Total economy | ~2,850,000g |
| Biggest existing sink | Guild founding (50,000g) |

**Target costs:**
- Basic cottage (functional): ~75,000g (early game milestone)
- Mid-tier manor: ~1,500,000g (months of investment)
- 99 Construction (training only): **~25-50M** depending on plank type
- Maxed noble estate + 99 Construction: **~47-58M** (the ultimate endgame)
- Construction becomes THE reason to keep grinding gold long after everything else is bought
- Creates massive demand for logs, bars, and stone — boosting the entire economy

---

## The Construction Skill

### Training Loop

1. Enter your house in **Build Mode**
2. Click an empty hotspot in a room
3. Select furniture to build (requires Construction level + materials in inventory)
4. Hammering animation plays, furniture appears, XP awarded
5. To train, you can **build productively** (furnishing your house) or take on **Construction Contracts** from NPCs (repair buildings around settlements for XP + small gold reward)

**Unlike OSRS, we do NOT use the "build and destroy" loop.** Instead:
- Primary training: **Construction Contracts** — NPC-given tasks to repair settlement buildings. Available at any settlement. Costs materials, awards XP + small gold.
- Secondary training: **Building furniture in your house** — awards XP, but you keep the result.
- Tertiary training: **Community projects** — contribute materials/labor to town buildings for XP + reputation.

### XP Curve

Uses the existing formula: Level L → L+1 requires `L² × 60` XP.

| Level | Total XP | Milestone |
|---|---|---|
| 1 | 0 | Can build basic furniture |
| 10 | 33,000 | Unlock Kitchen, Bedroom |
| 20 | 154,000 | Unlock Workshop, hire servants |
| 30 | 396,000 | Unlock Study, Hearth Room |
| 40 | 780,000 | Unlock Forge, Chapel |
| 50 | 1,325,000 | Unlock Portal Room, Bath House |
| 60 | 2,046,000 | Unlock Trophy Hall, War Room |
| 70 | 2,955,000 | Unlock Restoration Pool |
| 80 | 4,062,000 | Unlock Jewellery Box, Occult Altar |
| 90 | 5,373,000 | Unlock Ornate Pool, Grand upgrades |
| 99 | 6,890,820 | Max level — all furniture available |

---

## Materials

### Plank Types (Primary Construction Material)

Planks are made at a **Sawmill** (new settlement service) from logs. The sawmill charges a fee per plank. Planks are the core expense — you burn through tens of thousands of them training to 99.

| Plank | Made From | Sawmill Fee | Market Value | Con Level | XP/Plank |
|---|---|---|---|---|---|
| **Plank** | Wood | 10g | ~20g | 1 | 25 |
| **Oak Plank** | Oak Wood | 40g | ~75g | 15 | 70 |
| **Willow Plank** | Willow Wood | 100g | ~180g | 30 | 150 |
| **Maple Plank** | Maple Wood* | 250g | ~450g | 45 | 300 |
| **Yew Plank** | Yew Wood* | 600g | ~1,000g | 60 | 550 |
| **Mahogany Plank** | Mahogany Wood* | 1,500g | ~2,500g | 75 | 900 |

*Maple Wood, Yew Wood, and Mahogany Wood are new items added to woodcutting at higher levels.

### New Woodcutting Resources

| Wood | Min Woodcutting Level | Weight | XP Bonus |
|---|---|---|---|
| Maple Wood | 35 | 15 | 75 |
| Yew Wood | 50 | 10 | 135 |
| Mahogany Wood | 65 | 5 | 200 |

### Stone & Premium Materials

| Material | Source | Market Value | Con Level | XP | Used For |
|---|---|---|---|---|---|
| **Limestone Brick** | New: mine limestone, craft into bricks | ~100g | 20 | 20 | Stone fireplaces, altars, walls |
| **Marble Block** | New: rare mining drop (level 50+) or buy from NPC | ~5,000g | 50 | 80 | High-end furniture, pools |
| **Gold Leaf** | Crafted from 3 Gold Bars + chisel | ~1,500g | 60 | 100 | Gilded furniture |
| **Cloth** | Already exists | ~15g | 1 | 5 | Curtains, beds, rugs |
| **Nails** | Already exist | ~5g | 1 | 0 | Required with basic planks |
| **Steel Bar** | Already exists | ~100g | 30 | 20 | Fixtures, ranges, hinges |
| **Mithril Bar** | Already exists | ~300g | 50 | 40 | Advanced fixtures |
| **Gold Bar** | Already exists | ~150g | 40 | 30 | Decorative items |
| **Magic Stone** | New: extremely rare dungeon drop or crafted from Oria Bar + Marble | ~15,000g | 85 | 200 | Top-tier furniture only |

### Material Cost Curve

This is why Construction is expensive — materials are consumed permanently, and the cost per build escalates dramatically:

| Tier | Primary Material | Cost per Build | Typical Furniture |
|---|---|---|---|
| Low (1-20) | Planks + Nails | 50-200g | Basic chairs, tables, beds |
| Mid (20-40) | Oak Planks + Steel | 500-2,000g | Oak furniture, stone fixtures |
| Mid-High (40-60) | Willow/Maple Planks + Gold | 2,000-8,000g | Quality furniture, altars |
| High (60-80) | Yew Planks + Marble | 8,000-30,000g | Premium furniture, pools |
| Elite (80-99) | Mahogany + Magic Stone | 25,000-100,000g | Gilded/Ornate everything |

---

## House Tiers

Your house tier is determined by **both** your Construction level and your social class/title. You must meet BOTH requirements.

| Tier | Con Level | Title Required | Purchase Cost | Grid Size | Max Rooms | Weekly Upkeep |
|---|---|---|---|---|---|---|
| **Cottage** | 1 | Peasant (2) | 25,000g | 3×3 | 3 | 100g |
| **House** | 20 | Yeoman (4) | 100,000g | 4×4 | 5 | 350g |
| **Manor** | 40 | Squire (5) | 500,000g | 5×5 | 8 | 1,000g |
| **Estate** | 60 | Baronet (7) | 1,500,000g | 6×6 | 11 | 3,000g |
| **Noble Estate** | 80 | Baron (8) | 5,000,000g | 7×7 | 15 | 8,000g |

**Upgrade path** — pay the difference in purchase cost when upgrading. All rooms and furniture carry over.

---

## Room Types

Each room requires a Construction level to build and a gold cost to create the room itself (separate from furniture costs).

### Room List

| Room | Con Level | Room Cost | Hotspots | Category |
|---|---|---|---|---|
| **Parlour** | 1 | 5,000g | 4 | Social |
| **Garden** | 1 | 5,000g | 4 | Utility (4 doors — hub room) |
| **Kitchen** | 5 | 15,000g | 5 | Crafting |
| **Bedroom** | 10 | 15,000g | 4 | Restoration |
| **Dining Room** | 15 | 25,000g | 4 | Social / Servant |
| **Workshop** | 20 | 50,000g | 5 | Crafting |
| **Cellar** | 25 | 40,000g | 3 | Storage (basement) |
| **Study** | 30 | 75,000g | 4 | Buff |
| **Hearth Room** | 30 | 50,000g | 3 | Restoration |
| **Forge** | 35 | 100,000g | 5 | Crafting |
| **Chapel** | 40 | 150,000g | 4 | Prayer / Buff |
| **Portal Chamber** | 45 | 200,000g | 3 | Teleportation |
| **Bath House** | 50 | 200,000g | 4 | Restoration |
| **Trophy Hall** | 55 | 150,000g | 5 | Prestige / Buff |
| **Garden (Superior)** | 60 | 300,000g | 5 | Restoration Pool / Teleports |
| **War Room** | 65 | 250,000g | 4 | Combat Buff |
| **Stable** | 50 | 100,000g | 3 | Horse storage |
| **Achievement Gallery** | 70 | 400,000g | 5 | Jewellery Box / Spellbook |
| **Servant Quarters** | 40 | 75,000g | 3 | Servant housing |
| **Wine Cellar** | 55 | 200,000g | 4 | Brewing (basement) |
| **Infirmary** | 70 | 300,000g | 4 | Healing |

---

## The Hotspot System

Every room has **3-5 fixed hotspot positions**. Each hotspot has a **category** (what type of furniture goes there) and **tiered options** (your Construction level determines what you can build).

In Build Mode, empty hotspots appear as ghostly outlines. Click one to see available furniture for that slot.

### Example: Kitchen (5 Hotspots)

**Stove Hotspot** (cooking station):
| Furniture | Con Level | Materials | XP | Effect |
|---|---|---|---|---|
| Firepit | 5 | 5 Planks, 3 Nails | 50 | Can cook basic food |
| Iron Stove | 20 | 3 Oak Planks, 2 Steel Bars | 150 | +5% cooking speed |
| Steel Range | 40 | 5 Willow Planks, 4 Steel Bars | 350 | +10% speed, -5% burn chance |
| Fancy Range | 60 | 4 Yew Planks, 2 Mithril Bars | 600 | +15% speed, -10% burn, +5% Cooking XP |

**Larder Hotspot** (food storage):
| Furniture | Con Level | Materials | XP | Effect |
|---|---|---|---|---|
| Wooden Larder | 9 | 6 Planks, 4 Nails | 70 | Stores 20 food items |
| Oak Larder | 30 | 5 Oak Planks | 250 | Stores 50 food items |
| Teak Larder | 50 | 4 Willow Planks, 1 Gold Bar | 450 | Stores 100 food, preserves freshness |

**Shelf Hotspot** (decoration + utility):
| Furniture | Con Level | Materials | XP | Effect |
|---|---|---|---|---|
| Wooden Shelves | 6 | 4 Planks, 2 Nails | 45 | Decorative |
| Oak Shelves | 25 | 3 Oak Planks | 200 | Displays cooking trophies |
| Spice Rack | 45 | 4 Maple Planks, 2 Gold Bars | 400 | +3% food healing bonus |

**Sink Hotspot**:
| Furniture | Con Level | Materials | XP | Effect |
|---|---|---|---|---|
| Pump & Drain | 7 | 3 Planks, 1 Steel Bar | 60 | Water source |
| Sink | 35 | 3 Oak Planks, 2 Steel Bars | 300 | Faster food prep |

**Table Hotspot**:
| Furniture | Con Level | Materials | XP | Effect |
|---|---|---|---|---|
| Kitchen Table | 8 | 4 Planks, 2 Nails | 65 | Prep surface |
| Oak Table | 30 | 4 Oak Planks | 280 | +2% cooking XP |

### Example: Chapel (4 Hotspots)

**Altar Hotspot** (THE key unlock for Prayer training):
| Furniture | Con Level | Materials | XP | Effect |
|---|---|---|---|---|
| Wooden Altar | 40 | 6 Oak Planks, 2 Cloth | 400 | +50% Prayer XP when burning bones at home |
| Stone Altar | 55 | 4 Limestone Bricks, 3 Marble Blocks | 700 | +100% Prayer XP |
| Gilded Altar | 75 | 4 Marble Blocks, 2 Gold Leaf | 1,200 | +150% Prayer XP (best in game) |

**Incense Burner Hotspot** (x2 — two burner slots):
| Furniture | Con Level | Materials | XP | Effect |
|---|---|---|---|---|
| Wooden Burner | 41 | 3 Oak Planks | 350 | +25% Prayer XP each (stacks with altar) |
| Steel Burner | 55 | 2 Steel Bars, 2 Willow Planks | 600 | +50% Prayer XP each |
| Gold Burner | 75 | 2 Gold Leaf, 2 Yew Planks | 1,100 | +75% Prayer XP each |

*With Gilded Altar + 2 Gold Burners: +300% Prayer XP — the best Prayer training method in the game.*

**Icon Hotspot** (religious decoration):
| Furniture | Con Level | Materials | XP | Effect |
|---|---|---|---|---|
| Holy Symbol | 42 | 2 Oak Planks, 1 Silver Bar | 360 | +5% devotion gain |
| Icon of Faith | 60 | 2 Maple Planks, 1 Gold Bar | 650 | +10% devotion gain |
| Sacred Icon | 80 | 2 Marble Blocks, 1 Gold Leaf | 1,000 | +15% devotion, +1 blessing slot |

### Example: Superior Garden (5 Hotspots)

**Pool Hotspot** (THE most important utility unlock):
| Furniture | Con Level | Materials | XP | Effect |
|---|---|---|---|---|
| Restoration Pool | 60 | 5 Limestone Bricks, 3 Marble Blocks | 800 | Restores HP to full |
| Revitalisation Pool | 70 | 5 Marble Blocks, 2 Gold Leaf | 1,200 | + Restores energy to full |
| Rejuvenation Pool | 80 | 8 Marble Blocks, 3 Gold Leaf | 1,800 | + Cures poison/disease |
| Ornate Rejuv. Pool | 90 | 10 Marble Blocks, 5 Gold Leaf, 2 Magic Stone | 3,000 | Restores EVERYTHING — HP, energy, prayer, cures all |

**Teleport Hub Hotspot** (fast travel from home):
| Furniture | Con Level | Materials | XP | Effect |
|---|---|---|---|---|
| Spirit Tree | 60 | 5 Yew Planks, 3 Marble Blocks | 900 | Teleport to any kingdom capital |
| Fairy Ring | 65 | 3 Marble Blocks, 2 Magic Stone | 1,100 | Teleport to any settlement |
| Spirit Tree + Fairy Ring | 85 | 5 Magic Stone, 5 Gold Leaf | 2,500 | Both networks in one hotspot |

**Fence Hotspot**: Decorative boundary tiers

**Tree Hotspot**: Decorative trees

**Fountain Hotspot**: Decorative → functional (minor HP regen while in garden)

### Example: Achievement Gallery (5 Hotspots)

**Jewellery Box Hotspot** (unlimited teleports):
| Furniture | Con Level | Materials | XP | Effect |
|---|---|---|---|---|
| Basic Jewellery Box | 70 | 5 Yew Planks, 3 Gold Bars, 1 Gold Leaf | 1,000 | 5 teleport destinations |
| Fancy Jewellery Box | 80 | 5 Mahogany Planks, 5 Gold Bars, 2 Gold Leaf | 1,500 | 12 teleport destinations |
| Ornate Jewellery Box | 91 | 5 Mahogany Planks, 5 Gold Leaf, 3 Magic Stone | 2,800 | ALL teleport destinations |

**Occult Altar Hotspot** (switch prayer books/spellbooks):
| Furniture | Con Level | Materials | XP | Effect |
|---|---|---|---|---|
| Ancient Altar | 80 | 5 Marble Blocks, 3 Gold Leaf, 2 Magic Stone | 2,000 | Switch between religion prayer styles from home |

**Boss Display Hotspot**: Mount boss trophies for visual prestige

**Adventure Log Hotspot**: Track achievements and milestones

**Mounted Display Hotspot**: Show rare items on wall

### Example: Bedroom (4 Hotspots)

**Bed Hotspot** (energy regen):
| Furniture | Con Level | Materials | XP | Effect |
|---|---|---|---|---|
| Straw Bed | 10 | 5 Planks, 2 Cloth | 80 | +5% energy regen |
| Wooden Bed | 20 | 4 Oak Planks, 3 Cloth | 200 | +10% energy regen |
| Large Bed | 35 | 5 Willow Planks, 4 Cloth | 350 | +15% energy regen |
| Feather Bed | 50 | 4 Maple Planks, 5 Cloth, 1 Gold Bar | 550 | +20% energy regen |
| Four-Poster Bed | 65 | 5 Yew Planks, 4 Cloth, 2 Gold Bars | 850 | +30% energy regen |
| Royal Bed | 85 | 5 Mahogany Planks, 5 Cloth, 2 Gold Leaf | 1,500 | +40% energy regen |

**Wardrobe Hotspot** (outfit storage):
| Furniture | Con Level | Materials | XP | Effect |
|---|---|---|---|---|
| Wooden Wardrobe | 12 | 4 Planks, 3 Nails | 90 | Store 5 equipment sets |
| Oak Wardrobe | 30 | 4 Oak Planks | 260 | Store 10 equipment sets |
| Gilded Wardrobe | 60 | 4 Yew Planks, 1 Gold Leaf | 750 | Store 20 sets, quick-swap |

**Dresser Hotspot**: Decorative tiers

**Rug Hotspot**: Decorative tiers (Brown → Patterned → Opulent)

### All Remaining Rooms — Hotspot Summaries

**Parlour** (4 hotspots): Chair, Bookcase, Fireplace, Rug

**Garden** (4 hotspots): Centrepiece (flowers → fountain), Tree, Boundary (fence → hedge → wall), Exit Portal

**Dining Room** (4 hotspots): Table (seats 2→4→8), Bench/Chairs, Bell-Pull (summon servant), Wall Decoration

**Workshop** (5 hotspots): Workbench (craft at home), Repair Bench (repair equipment), Tool Rack, Heraldry Stand (create banners/crests), Whetstone (+attack bonus when sharpening)

**Cellar** (3 hotspots): Storage Crates (extra home storage +50→+200), Shelving (display items), Lighting (torch → lantern → chandelier)

**Study** (4 hotspots): Lectern (create teleport tablets), Globe (world map access from home), Bookcase (stores books/scrolls), Telescope (weather prediction — farming bonus)

**Hearth Room** (3 hotspots): Fireplace (clay → stone → marble, +3→+8 max HP), Armchair, Rug

**Forge** (5 hotspots): Anvil (smith at home), Furnace (smelt at home), Quench Trough, Bellows (+smithing speed), Tool Storage

**Portal Chamber** (3 hotspots): Portal Frame ×3 (each holds 1 teleport destination, costs runes to configure)

**Bath House** (4 hotspots): Bath (cold → heated → royal), Drain, Towel Rack, Steam Vent (+recovery speed per tier)

**Trophy Hall** (5 hotspots): Display ×3 (mount monster trophies, +1 stat each), Pedestal (mount boss trophy, +2 stats), Lighting

**War Room** (4 hotspots): Map Table (+3%→+8%→+12% combat XP), Weapon Rack (+1→+3 attack), Armor Stand (+1→+3 defense), Banner (displays guild/kingdom crest)

**Stable** (3 hotspots): Stall ×2 (1 horse each, +10% rest speed per tier), Feed Trough (auto-feed horses)

**Servant Quarters** (3 hotspots): Bed (required to hire servant), Wardrobe (servant appearance), Bell (faster servant response)

**Wine Cellar** (4 hotspots): Barrel Rack (5→10→20 barrels), Grape Press, Aging Shelf (faster aging), Tasting Table (preview wine stats)

**Infirmary** (4 hotspots): Sick Bed (HP regen +15%→+25%), Medicine Cabinet (auto-cure minor → all disease), Herb Shelf (store healing supplies), Altar of Healing (prayer heals bonus)

---

## Total Cost to Max a House

### Cottage (Bare Minimum Functional — Early Game Goal)

| Cost | Amount |
|---|---|
| Purchase cottage | 25,000g |
| Build 3 rooms (Parlour, Kitchen, Bedroom) | ~35,000g |
| Basic furniture (cheapest tier per hotspot) | ~15,000g |
| **Total** | **~75,000g** |

*Achievable by a dedicated player after a few weeks. Your first home.*

### Mid-Game House (Level 40-50, Manor)

| Cost | Amount |
|---|---|
| Upgrade to Manor | 500,000g |
| Build 8 rooms | ~600,000g |
| Mid-tier furniture (oak/willow) | ~400,000g |
| **Total** | **~1,500,000g** |

*A serious investment. Months of saving for most players.*

### Endgame Noble Estate (Level 80+, Fully Maxed)

| Cost | Amount |
|---|---|
| Noble Estate purchase | 5,000,000g |
| Build all 15 rooms | ~2,500,000g |
| Best-in-slot furniture (yew/mahogany) | ~3,000,000g |
| Premium materials (marble, gold leaf, magic stone) | ~2,000,000g |
| Ornate Pool + Gilded Altar + Ornate Jewellery Box | ~1,500,000g |
| Servant wages (ongoing) | ~2,000g/week |
| **Total** | **~14,000,000g** |

*A crown jewel. Only the wealthiest players will achieve this.*

### Training to Level 99 — Cost Breakdown

Total XP to 99: **19,112,940 XP**. Cost depends heavily on which planks you use.

#### Budget Path (~38M) — Use Cheapest Appropriate Plank Per Range

| Level Range | Plank Type | XP/Plank | Planks Needed | Cost Per Plank | Total Cost | GP/XP |
|---|---|---|---|---|---|---|
| 1-20 | Plank | 25 | 5,928 | 20g | 118,560g | 0.80 |
| 20-40 | Oak Plank | 70 | 15,489 | 75g | 1,161,643g | 1.07 |
| 40-55 | Willow Plank | 150 | 13,366 | 180g | 2,405,880g | 1.20 |
| 55-70 | Maple Plank | 300 | 11,588 | 450g | 5,214,600g | 1.50 |
| 70-85 | Yew Plank | 550 | 9,733 | 1,000g | 9,732,545g | 1.82 |
| 85-99 | Mahogany Plank | 900 | 7,829 | 2,500g | 19,573,167g | 2.78 |
| **Total** | | | **63,933** | | **~38,200,000g** | |

#### Fast Path (~49M) — Use Premium Planks for Speed

| Level Range | Plank Type | XP/Plank | Planks Needed | Cost Per Plank | Total Cost | GP/XP |
|---|---|---|---|---|---|---|
| 1-20 | Oak Plank | 70 | 2,117 | 75g | 158,786g | 1.07 |
| 20-40 | Maple Plank | 300 | 3,614 | 450g | 1,626,300g | 1.50 |
| 40-60 | Yew Plank | 550 | 5,419 | 1,000g | 5,418,545g | 1.82 |
| 60-99 | Mahogany Plank | 900 | 16,556 | 2,500g | 41,389,833g | 2.78 |
| **Total** | | | **27,706** | | **~48,600,000g** | |

#### Extreme Paths

| Method | Planks | Total Cost | Notes |
|---|---|---|---|
| All Basic Planks | 764,518 | **~15,300,000g** | Cheapest possible but absurdly slow |
| All Mahogany | 21,237 | **~53,100,000g** | Fastest possible, maximum flex |

#### Summary

| Path | Cost | Speed |
|---|---|---|
| All basic planks (extreme budget) | ~15M | Extremely slow |
| **Budget (tiered)** | **~25-38M** | **Moderate** |
| **Fast (premium planks)** | **~38-49M** | **Fast** |
| All mahogany (extreme speed) | ~53M | Fastest |

*Chopping your own wood and using the sawmill saves ~40% vs buying planks on the market, but costs enormous amounts of time. The economy will naturally balance as woodcutters sell to builders.*

### Grand Total (99 Construction + Maxed Noble Estate)

| Component | Budget Path | Fast Path |
|---|---|---|
| Training to 99 | ~38,200,000g | ~48,600,000g |
| Maxed Noble Estate (house + rooms + furniture) | ~14,000,000g | ~14,000,000g |
| Overlap (XP earned from house furniture) | ~-5,000,000g | ~-5,000,000g |
| **Grand Total** | **~47,200,000g** | **~57,600,000g** |

At current economy scale (richest player: 520k, total economy: 2.85M), a maxed house represents **~100x the richest player's wealth** and **~17x the entire economy**. This ensures Construction remains the ultimate long-term aspirational goal that:
- Creates massive demand for logs, bars, and stone (boosting gatherers and smiths)
- Acts as the single largest gold sink, counteracting inflation for years
- Provides incremental value at every stage — you don't need 99 to benefit
- The "max house" becomes the ultimate status symbol
- Players naturally specialize: woodcutters sell to builders, creating a real player economy

---

## The Servant System

Servants automate material fetching from your bank/storage, dramatically speeding up building.

| Servant | Con Level | Hire Cost | Weekly Wage | Carry Capacity | Fetch Speed |
|---|---|---|---|---|---|
| **Handyman** | 20 | 5,000g | 100g | 6 items | 60 seconds |
| **Maid** | 30 | 15,000g | 250g | 10 items | 30 seconds |
| **Butler** | 45 | 50,000g | 500g | 16 items | 15 seconds |
| **Head Butler** | 60 | 150,000g | 1,000g | 24 items | 8 seconds |

**Requirements**: Must have a Servant Quarters room with a bed built. Can only hire ONE servant at a time.

**Services**:
- Fetch materials from home storage
- Take logs to sawmill (returns as planks)
- Serve food to restore energy while building
- Greet visiting players

---

## Construction Contracts

The main training method (alternative to furnishing your own house).

### How They Work

1. Visit a **Construction Foreman** NPC at any settlement
2. Accept a contract (repair a building, build new furniture for an NPC)
3. Travel to the marked building in the settlement
4. Use your materials to complete the repair/build
5. Return to foreman for XP reward + small gold tip

### Contract Tiers

| Tier | Con Level | Materials Per Contract | XP Reward | Gold Tip | Energy Cost |
|---|---|---|---|---|---|
| Beginner | 1 | 3-5 Planks | 50-80 XP | 10-20g | 3 |
| Apprentice | 20 | 4-6 Oak Planks | 120-180 XP | 25-50g | 4 |
| Journeyman | 40 | 5-8 Willow/Maple Planks | 250-400 XP | 50-100g | 5 |
| Expert | 60 | 6-10 Yew Planks | 500-750 XP | 100-200g | 6 |
| Master | 80 | 8-12 Mahogany Planks | 900-1,200 XP | 200-400g | 7 |

Contracts are **cheaper but slower** than building elite furniture in your own house. They're the "budget" training path.

---

## Key Utility Unlocks (Why Players Train Construction)

These are the rewards that make Construction worth the investment:

| Con Level | Unlock | Why It Matters |
|---|---|---|
| 5 | Kitchen | Cook at home |
| 10 | Bedroom (energy regen) | Passive energy boost |
| 20 | Workshop + Servants | Craft at home, automate fetching |
| 30 | Study (teleport tablets) | Create portable teleports |
| 35 | Forge | Smith at home |
| 40 | **Chapel** | Start of home Prayer training |
| 45 | Portal Chamber | 3 permanent teleport destinations from home |
| 50 | **Bath House** | Cure diseases at home |
| 55 | Trophy Hall | Permanent combat stat boosts from mounted trophies |
| 60 | **Restoration Pool** | Full HP restore at home |
| 65 | War Room | Permanent combat XP + stat bonuses |
| 70 | **Revitalisation Pool** | + Full energy restore |
| 70 | **Achievement Gallery** | Jewellery box + occult altar |
| 75 | **Gilded Altar** | +300% Prayer XP (with burners) — BEST Prayer training |
| 80 | **Rejuvenation Pool** | + Cure poison/disease |
| 85 | Fairy Ring (Superior Garden) | Teleport to ANY settlement from home |
| 90 | **Ornate Rejuvenation Pool** | Restore EVERYTHING — HP, energy, prayer, cure all |
| 91 | **Ornate Jewellery Box** | Unlimited teleports to ALL destinations |

### The "Max House" Meta

The endgame house serves as an all-in-one hub:
- **Ornate Pool**: Full restore between any activity (never visit a healer again)
- **Ornate Jewellery Box**: Teleport anywhere without carrying jewelry
- **Fairy Ring**: Access every settlement instantly
- **Gilded Altar + Gold Burners**: Best Prayer training in the game
- **Forge + Workshop + Kitchen**: Craft everything at home
- **Trophy Hall**: Permanent +20 to combat stats over time
- **Wine Cellar**: Brew powerful temporary buff potions
- **Storage**: 500-700 item capacity (with cellar upgrades)

---

## Adjacency Bonuses

When rooms are placed next to compatible rooms on the grid, both get a bonus. This creates spatial strategy in house layout.

| Room A | Room B | Bonus |
|---|---|---|
| Kitchen | Cellar | +3% Cooking XP |
| Forge | Workshop | +3% Smithing XP |
| Chapel | Study | +3% Prayer XP |
| Bedroom | Hearth Room | +5% energy regen |
| Garden | Kitchen | Larder capacity +25% |
| Apothecary Lab | Garden | +3% Herblore XP |
| War Room | Trophy Hall | +1 attack bonus |
| Stable | Garden | Horse rest +15% faster |
| Wine Cellar | Cellar | +2 barrel capacity |
| Infirmary | Chapel | +5% HP regen |
| Study | Achievement Gallery | +2% all XP |
| Bedroom | Bath House | +5% disease recovery |

---

## Home Storage

Every house has built-in storage (bank-style stacking, no slot limit, just total capacity).

| Tier | Base Storage | With Grand Cellar |
|---|---|---|
| Cottage | 100 items | 300 |
| House | 200 items | 400 |
| Manor | 300 items | 500 |
| Estate | 400 items | 600 |
| Noble Estate | 500 items | 700 |

### Cellar Storage Upgrades (via hotspot)

| Furniture | Con Level | Bonus Storage |
|---|---|---|
| Small Crates | 25 | +50 |
| Storage Crates | 40 | +100 |
| Reinforced Crates | 55 | +150 |
| Grand Vault | 75 | +200 |

---

## Missed Upkeep Consequences

1. **1 week missed** — Warning notification
2. **2 weeks missed** — House condition degrades (-10/week), passive buffs deactivate
3. **4 weeks missed** — House becomes "abandoned", storage inaccessible
4. **8 weeks missed** — House is lost. Storage items sent to settlement stockpile. Rooms/furniture gone. Must rebuild.

---

## UI Design — Top-Down 2D House View

When a player enters their house, they see a **bird's-eye 2D view** of their property rendered as a grid-based floorplan.

### Layout Grid

Grid size scales with house tier:

| Tier | Grid Size | Visual Style |
|---|---|---|
| Cottage | 3×3 | Thatched roof, dirt path, small fence |
| House | 4×4 | Stone walls, wooden floor, garden border |
| Manor | 5×5 | Polished stone, courtyard, hedgerows |
| Estate | 6×6 | Marble accents, fountain, iron gates |
| Noble Estate | 7×7 | Grand marble, statues, ornate gardens |

### Grid Cell States

- **Empty** — dark stone/dirt tile, dashed border. Hover shows `+` and "Build Room".
- **Built room** — filled tile with pixel-art icon (anvil for Forge, bed for Bedroom, etc.), room name, upgrade tier pips, buff indicator badge.
- **Locked** — greyed out with lock icon. Tooltip: "Upgrade to [tier] to unlock."

### Build Mode vs Normal Mode

**Normal Mode** (default):
- Click a room to see its details panel and use room features (cook, pray, craft, etc.)
- Rooms with interactive features show a subtle "pulse" animation

**Build Mode** (toggle button):
- Empty hotspots appear as ghostly outlines inside rooms
- Click a hotspot to see furniture options, costs, and effects
- Drag rooms to rearrange (adjacency bonuses recalculate live)
- Empty grid cells show "Build Room" prompt

### Room Detail Panel (Right Side / Bottom Sheet on Mobile)

When clicking a built room:
- Room name, description, Construction level
- **Hotspot list** — each hotspot shows:
  - Current furniture (or "Empty — click to build")
  - Tier indicator (e.g., "Tier 2/4")
  - Active effect (e.g., "+10% energy regen")
  - Upgrade available? (gold highlight if yes)
- **Room actions** (Cook, Pray, Craft, Store, etc.)
- **Adjacency bonuses** active for this room
- Demolish button (returns 50% materials)

### Hotspot Build Interface

When clicking an empty hotspot:
- List of available furniture for this slot
- Each option shows:
  - Name and pixel icon
  - Construction level requirement (green if met, red if not)
  - Materials required (green if in inventory, red if missing)
  - XP awarded
  - Effect/buff granted
- "Build" button (disabled if requirements not met)
- Estimated cost summary at bottom

### House Overview Bar (Top)

Fixed bar showing:
- House name (editable, e.g., "Chief's Manor")
- Tier badge
- Condition meter (100% = pristine)
- Storage (used / max)
- Weekly upkeep
- Active buff count (compact icons)
- "Build Mode" toggle
- "Upgrade House" button (if eligible)

### Visiting Other Players

- Same top-down view, read-only
- Can see rooms, furniture, trophies
- Cannot use features or rearrange
- "Guest" badge in corner

### Visual Style

- Pixel-art medieval aesthetic matching the game
- Warm torch-light glow on occupied rooms
- Subtle ambient particles (steam in bath house, sparks in forge, dust in workshop)
- Surrounding border changes with tier (wooden fence → stone wall → iron gate → marble pillars)
- Adjacent rooms with bonuses show a subtle glowing connection line

### Responsive Design

- **Desktop**: Full grid visible, panels slide from right
- **Mobile**: Pinch-to-zoom grid, panels slide up from bottom

---

## Social Class & Title Restrictions

Housing is gated by **both** Construction level and social class/title.

| Housing Tier | Social Class | Min Title | Con Level | Purchase |
|---|---|---|---|---|
| Cottage | Freeman+ | Peasant (2) | 1 | 25,000g |
| House | Burgher+ | Yeoman (4) | 20 | 100,000g |
| Manor | Noble | Squire (5) | 40 | 500,000g |
| Estate | Noble | Baronet (7) | 60 | 1,500,000g |
| Noble Estate | Noble | Baron (8) | 80 | 5,000,000g |

Serfs cannot own property (blocked by existing `canOwnProperty()`).

---

## New Items Required

### New Wood Types (Woodcutting)
- Maple Wood (level 35)
- Yew Wood (level 50)
- Mahogany Wood (level 65)

### New Planks (Sawmill)
- Willow Plank
- Maple Plank
- Yew Plank
- Mahogany Plank

### New Stone/Premium Materials
- Limestone (new mining resource, level 20)
- Limestone Brick (crafted from limestone)
- Marble Block (rare mining drop level 50+, or buy from NPC)
- Gold Leaf (crafted: Gold Bar + chisel, level 60 Crafting)
- Magic Stone (Oria Bar + Marble Block, level 85 Crafting, or rare dungeon drop)

---

## Architecture (Backend)

### New Models
- **`PlayerHouse`** — ownership, tier, condition, location, grid layout, name
- **`HouseRoom`** — which rooms are built, grid position (x,y), rotation
- **`HouseRoomType`** — seeded: defines available rooms, costs, hotspot slots
- **`HouseFurniture`** — built furniture in each hotspot (room_id, hotspot_slug, furniture_tier)
- **`HouseFurnitureType`** — seeded: defines all furniture options per hotspot, materials, XP, effects
- **`HouseStorage`** — bank-style (item_id + quantity), capacity from house tier + cellar
- **`HouseTrophy`** — mounted trophies with monster info, buff type
- **`HouseWineBarrel`** — wine type, started_at, ready_at, quality
- **`ConstructionContract`** — active contract tracking
- **`HouseTransaction`** — purchase, upkeep, upgrade, repair history

### New Services
- **`ConstructionService`** — skill training, XP calculation, contracts
- **`HouseService`** — buy, upgrade, build rooms, place furniture, maintain
- **`HouseBuffService`** — calculates all active buffs from furniture + trophies + adjacency
- **`SawmillService`** — convert logs to planks
- **`WineService`** — brewing, aging, consumption

### New Controller
- **`HouseController`** — routes for house management, building, storage, trophies, wine
- **`ConstructionController`** — routes for contracts, sawmill, skill training

### New Settlement Service
- **Sawmill** — new service at settlements for converting logs to planks

### Integration Points
- `SkillBonusService` / `BlessingEffectService` — merge in housing passive buffs
- `EnergyService` — bedroom energy regen bonus
- `CombatService` — war room + trophy combat buffs
- `GatheringService` / `CraftingService` — at-home crafting bonuses
- `ReligionService` — chapel prayer XP multiplier for bone burning
- `TravelService` — portal chamber + fairy ring teleportation
- `HealerService` / `DiseaseService` — bath house + infirmary alternatives
- `LootService` — 1% trophy drop on level 50+ monster kills
- `SocialClassService::canOwnProperty()` — already exists
- Existing `PlayerSkill` model — add 'construction' to skill list

---

## Implementation Priority

### Phase 1: Core Skill + Basic House
- Construction skill (training via contracts)
- Sawmill service
- New wood types + planks
- PlayerHouse model + purchase flow
- Basic grid UI (build mode)
- Cottage with Parlour, Kitchen, Bedroom
- Home storage

### Phase 2: Mid-Game Rooms + Furniture
- Workshop, Study, Hearth Room, Forge, Chapel
- Full hotspot system with tiered furniture
- Servant system
- Adjacency bonuses
- Portal Chamber

### Phase 3: Endgame Content
- Superior Garden (restoration pools)
- Achievement Gallery (jewellery box, occult altar)
- Trophy Hall + trophy drops
- War Room
- Bath House + Infirmary
- Wine Cellar

### Phase 4: Polish
- Visiting other players' houses
- Community construction projects
- House leaderboards / showcases
- Upkeep degradation system
