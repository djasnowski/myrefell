# Blessings and Beliefs System Analysis

This document analyzes the blessings and beliefs systems in MyRefell, documenting what they do and how they affect gameplay.

---

## Table of Contents
1. [Blessings System](#blessings-system)
2. [Beliefs System](#beliefs-system)
3. [Summary](#summary)

---

## Blessings System

### Overview
Blessings are temporary buffs that can be granted by priests or obtained through self-prayer at shrines. They provide various effects like XP bonuses, stat boosts, and special abilities.

### Database Structure

| Table | Purpose |
|-------|---------|
| `blessing_types` | Template definitions for all blessing types |
| `player_blessings` | Active blessings on players (tracks expiration, who granted it) |
| `blessing_requests` | Pending requests from players to priests |

### Available Blessing Types (16 total)

| Level | Name | Effects | Duration | Cost |
|-------|------|---------|----------|------|
| 1 | Strength | +5 attack bonus | 60 min | 25g |
| 1 | Protection | +5 defense bonus | 60 min | 25g |
| 1 | Harvest | +10% farming XP | 120 min | 30g |
| 1 | Fortune | +5% gold find | 60 min | 50g |
| 10 | Sea | +10% fishing yield & XP | 120 min | 50g |
| 10 | Forest | +10% woodcutting yield & XP | 120 min | 50g |
| 10 | Earth | +10% mining yield & XP | 120 min | 50g |
| 15 | **Haste** | **1.5s action cooldown** | 30 min | 1000g |
| 20 | Endurance | +20% energy regen | 240 min | 100g |
| 20 | Restoration | +25% HP regen | 180 min | 75g |
| 30 | Vitality | +20 max HP | 120 min | 150g |
| 30 | Swiftness | +25% travel speed | 120 min | 150g |
| 40 | Craftsman | +20% smithing & crafting XP | 120 min | 200g |
| 50 | Luck | +20% rare drop chance | 60 min | 300g |
| 60 | Warrior | +10 attack, defense, strength | 60 min | 500g |
| 70 | Wisdom | +15% all XP | 60 min | 750g |

### How Blessings Work

1. **Priest-Granted Blessings**
   - Full duration and effects
   - Costs gold + energy
   - Priest receives 50% of gold as offering
   - Priest gains Prayer XP (scaled by blessing level)
   - Requires priest to be at same location as target

2. **Self-Prayer at Shrines**
   - 75% of normal duration
   - 150% gold cost (50% penalty)
   - No priest needed

### Blessing Effects Implementation

All blessing effects are fully implemented:

| Effect | Where Applied | File |
|--------|---------------|------|
| `action_cooldown_seconds` | Action timing | `BlessingEffectService.php`, `ReligionService.php`, `JobService.php` |
| `attack_bonus` | Combat hit chance | `CombatService.php` |
| `defense_bonus` | Monster hit chance reduction | `CombatService.php` |
| `strength_bonus` | Combat damage | `CombatService.php` |
| `farming_xp_bonus` | Farming XP gains | `PlayerSkill.php` |
| `fishing_xp_bonus` | Fishing XP gains | `PlayerSkill.php` |
| `woodcutting_xp_bonus` | Woodcutting XP gains | `PlayerSkill.php` |
| `mining_xp_bonus` | Mining XP gains | `PlayerSkill.php` |
| `smithing_xp_bonus` | Smithing XP gains | `PlayerSkill.php` |
| `crafting_xp_bonus` | Crafting XP gains | `PlayerSkill.php` |
| `all_xp_bonus` | All skill XP gains | `PlayerSkill.php` |
| `gold_find_bonus` | Monster gold drops | `LootService.php` |
| `rare_drop_bonus` | Loot table drop chances | `MonsterLootTable.php` |
| `max_hp_bonus` | Max HP calculation | `User.php` |
| `travel_speed_bonus` | Travel time reduction | `TravelService.php` |
| `energy_regen_bonus` | Energy regeneration | `EnergyService.php` |
| `hp_regen_bonus` | HP regeneration | `HpService.php` |
| `fishing_yield_bonus` | Fishing quantity | `GatheringService.php` |
| `mining_yield_bonus` | Mining quantity | `GatheringService.php` |
| `woodcutting_yield_bonus` | Woodcutting quantity | `GatheringService.php` |

### Blessing System Status: ✅ FULLY IMPLEMENTED

---

## Beliefs System

### Overview
Beliefs are passive bonuses/penalties that define a religion's characteristics. Players join religions to gain these effects. Religions can have multiple beliefs that stack together.

### Database Structure

| Table | Purpose |
|-------|---------|
| `beliefs` | Predefined belief definitions with effects |
| `religion_beliefs` | Junction table linking religions to beliefs |
| `religions` | Religion definitions |
| `religion_members` | Player membership with rank and devotion |

### Available Beliefs (16 total)

#### Virtues (Pure Bonuses)
| Name | Effects |
|------|---------|
| Industriousness | +10% gathering XP |
| Martial Prowess | +10% combat XP |
| Craftsmanship | +10% crafting XP |
| Charity | +25% donation devotion |
| Vigilance | +15% daily task bonus |
| Temperance | +5% energy regen |
| Wisdom | +10% quest XP |
| Fortitude | +5 max HP |

#### Vices (Tradeoffs)
| Name | Effects |
|------|---------|
| Bloodlust | +20% combat XP, -10% crafting XP |
| Greed | +10% gold, +25% donation cost |
| Sloth | -10% energy costs, -5% XP |
| Pride | +10% combat/crafting XP, -15% devotion |

#### Neutral (Balanced)
| Name | Effects |
|------|---------|
| Asceticism | +20% devotion, -10% gold |
| Mysticism | +25% ritual devotion, +25% sacrifice devotion |
| Communion | +15% structure bonus |
| Pilgrimage | +100% pilgrimage devotion, +10% travel energy cost |

### How Beliefs Work

1. **Cults** - Player-created groups (max 5 members, max 2 beliefs)
2. **Religions** - Upgraded cults (15+ members, up to 5 beliefs, 100,000g to convert)
3. **Combined Effects** - All beliefs in a religion stack together

### Belief Effects Implementation

All belief effects are now fully implemented:

| Effect | Where Applied | File |
|--------|---------------|------|
| `gathering_xp_bonus` | Gathering skill XP | `PlayerSkill.php` |
| `combat_xp_bonus` | Combat skill XP | `PlayerSkill.php` |
| `crafting_xp_bonus` | Crafting skill XP | `PlayerSkill.php` |
| `crafting_xp_penalty` | Crafting skill XP (negative) | `PlayerSkill.php` |
| `xp_penalty` | All skill XP (negative) | `PlayerSkill.php` |
| `max_hp_bonus` | Max HP calculation | `User.php` |
| `energy_regen_bonus` | Energy regeneration | `EnergyService.php` |
| `energy_cost_reduction` | Energy consumption | `EnergyService.php` |
| `gold_bonus` | Gold drops | `LootService.php` |
| `gold_penalty` | Gold drops (negative) | `LootService.php` |
| `daily_task_bonus` | Daily task rewards | `DailyTaskService.php` |
| `quest_xp_bonus` | Quest XP rewards | `QuestService.php` |
| `devotion_bonus` | Devotion gains | `ReligionService.php` |
| `devotion_penalty` | Devotion gains (negative) | `ReligionService.php` |
| `donation_devotion_bonus` | Donation devotion | `ReligionService.php` |
| `ritual_devotion_bonus` | Ritual devotion | `ReligionService.php` |
| `sacrifice_devotion_bonus` | Sacrifice devotion | `ReligionService.php` |
| `pilgrimage_bonus` | Pilgrimage devotion | `ReligionService.php` |
| `travel_energy_penalty` | Travel energy cost | `TravelService.php` |
| `structure_bonus` | Structure devotion multiplier | (stored, display only) |
| `donation_cost_penalty` | Donation cost | (stored, display only) |

### Beliefs System Status: ✅ FULLY IMPLEMENTED

---

## Summary

### ✅ Blessings - Fully Functional

All 16 blessing types work and affect gameplay:

| Category | Effects |
|----------|---------|
| **Combat** | Attack, defense, strength bonuses |
| **XP Bonuses** | Skill-specific and global (Wisdom) |
| **Economy** | Gold find, rare drop chance |
| **Stats** | Max HP, HP regen, energy regen |
| **Utility** | Travel speed, action cooldown (Haste) |
| **Gathering** | Fishing, mining, woodcutting yields |

### ✅ Beliefs - Fully Functional

All 16 belief types work and affect gameplay:

| Category | Effects |
|----------|---------|
| **XP Bonuses** | Gathering, combat, crafting, quest XP |
| **XP Penalties** | Global XP penalty (Sloth), crafting penalty (Bloodlust) |
| **Economy** | Gold bonus/penalty |
| **Stats** | Max HP bonus |
| **Energy** | Regen bonus, cost reduction, travel cost penalty |
| **Devotion** | General, donation, ritual, sacrifice, pilgrimage bonuses/penalties |
| **Daily Tasks** | Reward bonus (Vigilance) |

---

## Implementation Files

### Core Services
- `app/Services/BlessingEffectService.php` - Retrieves active blessing effects for users
- `app/Services/BeliefEffectService.php` - Retrieves active belief effects from religion membership

### Blessing Integration
- `app/Models/PlayerSkill.php` - XP bonuses in `applyBlessingXpBonus()`
- `app/Services/CombatService.php` - Combat bonuses (attack, defense, strength)
- `app/Services/LootService.php` - Gold find and rare drop bonuses
- `app/Models/MonsterLootTable.php` - `rollDropWithBonus()` method
- `app/Models/User.php` - Max HP bonus in `getMaxHpAttribute()`
- `app/Services/TravelService.php` - Travel speed bonus
- `app/Services/EnergyService.php` - Energy regen bonus
- `app/Services/HpService.php` - HP regeneration with blessing bonus
- `app/Services/GatheringService.php` - Gathering yield bonuses
- `app/Jobs/RegenerateHp.php` - Scheduled HP regeneration job
- `routes/console.php` - HP regen scheduled every 5 minutes

### Belief Integration
- `app/Models/PlayerSkill.php` - XP bonuses/penalties in `applyBeliefXpBonus()`
- `app/Models/User.php` - Max HP bonus
- `app/Services/EnergyService.php` - Energy regen bonus, cost reduction
- `app/Services/LootService.php` - Gold bonus/penalty
- `app/Services/TravelService.php` - Travel energy penalty
- `app/Services/DailyTaskService.php` - Daily task bonus
- `app/Services/QuestService.php` - Quest XP bonus
- `app/Services/ReligionService.php` - All devotion modifiers

---

## Test Coverage

### Blessing Tests
- `tests/Feature/BlessingEffectServiceTest.php` - Tests for blessing effect retrieval and application

### Belief Tests
- `tests/Feature/BeliefEffectServiceTest.php` - Tests for belief effect retrieval and application
  - ✅ User with no religion gets empty effects
  - ✅ Specific effect returns 0.0 for non-religious users
  - ✅ XP bonuses apply correctly (20% bonus on 100 XP = 120 XP)
  - ✅ Max HP bonus applies correctly (+5 HP from Fortitude)
  - ✅ Multiple belief effects stack (10% + 20% = 30% combat XP)
