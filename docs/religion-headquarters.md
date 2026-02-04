# Religion Headquarters System

## Overview

Religion Headquarters (HQ) is where members can:
- **Pray at features** for temporary buffs (5-30 minutes)
- **Contribute** to construction projects
- **Donate** gold to the treasury

Members must **travel to the HQ location** to pray at features.

---

## HQ Tiers & Bonuses

These are **passive bonuses** that apply to all members at all times.

| Tier | Name | Gold Cost | Devotion Cost | Prayer Req | Build Time | Blessing Cost | Blessing Duration | Devotion Gain |
|------|------|-----------|---------------|------------|------------|---------------|-------------------|---------------|
| 1 | Chapel | Free | Free | 1 | - | - | - | - |
| 2 | Church | 100K | 5K | 15 | 2h | -5% | +10% | +5% |
| 3 | Temple | 500K | 25K | 30 | 6h | -10% | +20% | +10% |
| 4 | Cathedral | 2M | 100K | 50 | 12h | -15% | +30% | +20% |
| 5 | Grand Cathedral | 10M | 500K | 70 | 24h | -25% | +50% | +35% |
| 6 | Holy Sanctum | 50M | 2M | 90 | 48h | -40% | +75% | +50% |

---

## Feature Prayer System

Features are **prayer stations** that grant temporary buffs when prayed at.

### Prayer Costs

**Energy Cost (by HQ tier requirement):**
| Tier | Energy |
|------|--------|
| 1 | 25 |
| 2 | 30 |
| 3 | 35 |
| 4 | 40 |
| 5 | 45 |
| 6 | 50 |

**Devotion Cost (by feature level):**
| Level | Devotion |
|-------|----------|
| 1 | 50 |
| 2 | 100 |
| 3 | 200 |
| 4 | 400 |
| 5 | 800 |

**Buff Duration (by feature level):**
| Level | Duration |
|-------|----------|
| 1 | 5 minutes |
| 2 | 10 minutes |
| 3 | 15 minutes |
| 4 | 20 minutes |
| 5 | 30 minutes |

### Prayer Rules
- No cooldown between prayers
- Can have **unlimited** buffs active simultaneously
- Must be at HQ location to pray
- Buff can be refreshed by praying again (replaces existing)

---

## Feature Build Times

| Level | Build Time |
|-------|------------|
| 1 | 1 hour |
| 2 | 2 hours |
| 3 | 4 hours |
| 4 | 8 hours |
| 5 | 12 hours |

---

## Tier 1 - Chapel (2 features)

### Sacred Altar
*A consecrated altar that amplifies devotion gained from all religious activities.*

| Level | Devotion Bonus | Gold | Devotion |
|-------|----------------|------|----------|
| 1 | +5% | 10K | 1K |
| 2 | +10% | 50K | 5K |
| 3 | +15% | 150K | 15K |
| 4 | +22% | 400K | 40K |
| 5 | +30% | 1M | 100K |

### Offering Box
*A blessed collection box that increases gold deposited into the treasury from donations.*

| Level | Treasury Bonus | Gold | Devotion |
|-------|----------------|------|----------|
| 1 | +5% | 10K | 1K |
| 2 | +10% | 50K | 5K |
| 3 | +15% | 150K | 15K |
| 4 | +22% | 400K | 40K |
| 5 | +30% | 1M | 100K |

---

## Tier 2 - Church (3 features)

### Prayer Candles
*Blessed candles infused with sacred herbs that enhance your herblore knowledge.*

| Level | Herblore XP Bonus | Gold | Devotion |
|-------|-------------------|------|----------|
| 1 | +5% | 25K | 2.5K |
| 2 | +10% | 100K | 10K |
| 3 | +15% | 300K | 30K |
| 4 | +22% | 750K | 75K |
| 5 | +30% | 2M | 200K |

### Scripture Hall
*A hall of sacred texts that increases Prayer XP gained from all activities.*

| Level | Prayer XP Bonus | Gold | Devotion |
|-------|-----------------|------|----------|
| 1 | +5% | 30K | 3K |
| 2 | +10% | 120K | 12K |
| 3 | +15% | 350K | 35K |
| 4 | +22% | 900K | 90K |
| 5 | +30% | 2.5M | 250K |

### Meditation Garden
*A peaceful garden that increases energy recovery rate for all members.*

| Level | Energy Recovery Bonus | Gold | Devotion |
|-------|----------------------|------|----------|
| 1 | +2 | 35K | 3.5K |
| 2 | +4 | 140K | 14K |
| 3 | +6 | 400K | 40K |
| 4 | +9 | 1M | 100K |
| 5 | +12 | 2.8M | 280K |

---

## Tier 3 - Temple (4 features)

### Tome of Blessings
*An ancient tome that allows members to maintain additional active blessings.*

| Level | Extra Blessing Slots | Gold | Devotion |
|-------|---------------------|------|----------|
| 1 | +1 | 100K | 10K |
| 2 | +1 | 400K | 40K |
| 3 | +2 | 1.2M | 120K |
| 4 | +2 | 3M | 300K |
| 5 | +3 | 8M | 800K |

### Prophet's Sanctum
*A private sanctum that increases the duration of blessings you grant to others.*

| Level | Blessing Duration Bonus | Gold | Devotion |
|-------|------------------------|------|----------|
| 1 | +5% | 80K | 8K |
| 2 | +8% | 300K | 30K |
| 3 | +12% | 900K | 90K |
| 4 | +16% | 2.5M | 250K |
| 5 | +20% | 6M | 600K |

### Blessed Vault
*A sacred vault blessed by the divine that increases ore yield when mining.*

| Level | Mining Yield Bonus | Gold | Devotion |
|-------|-------------------|------|----------|
| 1 | +5% | 150K | 15K |
| 2 | +10% | 500K | 50K |
| 3 | +15% | 1.5M | 150K |
| 4 | +22% | 4M | 400K |
| 5 | +30% | 10M | 1M |

### Relic Chamber
*A secure chamber for holy relics that increases your chance to land critical hits on monsters.*

| Level | Monster Crit Chance | Gold | Devotion |
|-------|---------------------|------|----------|
| 1 | +3% | 120K | 12K |
| 2 | +6% | 450K | 45K |
| 3 | +10% | 1.3M | 130K |
| 4 | +15% | 3.5M | 350K |
| 5 | +20% | 9M | 900K |

---

## Tier 4 - Cathedral (4 features)

### Divine Font
*A mystical font that reduces the gold cost of all blessings for members.*

| Level | Blessing Cost Reduction | Gold | Devotion |
|-------|------------------------|------|----------|
| 1 | -10% | 250K | 25K |
| 2 | -18% | 900K | 90K |
| 3 | -25% | 2.5M | 250K |
| 4 | -32% | 6M | 600K |
| 5 | -40% | 15M | 1.5M |

### Healing Springs
*Sacred springs that provide passive HP regeneration to members while they are online.*

| Level | HP Regen Per Minute | Gold | Devotion |
|-------|---------------------|------|----------|
| 1 | +1 | 300K | 30K |
| 2 | +2 | 1M | 100K |
| 3 | +3 | 3M | 300K |
| 4 | +5 | 7.5M | 750K |
| 5 | +8 | 18M | 1.8M |

### Training Grounds
*Consecrated grounds where members gain increased XP from all combat activities.*

| Level | Combat XP Bonus | Gold | Devotion |
|-------|-----------------|------|----------|
| 1 | +3% | 350K | 35K |
| 2 | +6% | 1.2M | 120K |
| 3 | +10% | 3.5M | 350K |
| 4 | +15% | 8.5M | 850K |
| 5 | +20% | 20M | 2M |

### Reliquary of Saints
*Houses sacred remains that provide defense bonuses to all members.*

| Level | Defense Bonus | Gold | Devotion |
|-------|---------------|------|----------|
| 1 | +2% | 280K | 28K |
| 2 | +4% | 950K | 95K |
| 3 | +7% | 2.8M | 280K |
| 4 | +10% | 7M | 700K |
| 5 | +15% | 17M | 1.7M |

---

## Tier 5 - Grand Cathedral (4 features)

### Eternal Flame
*A never-dying flame that empowers your attacks with divine fury.*

| Level | Attack Bonus | Gold | Devotion |
|-------|--------------|------|----------|
| 1 | +3% | 1M | 100K |
| 2 | +6% | 4M | 400K |
| 3 | +10% | 12M | 1.2M |
| 4 | +15% | 30M | 3M |
| 5 | +20% | 75M | 7.5M |

### Grand Library
*Ancient texts reveal secrets of raw physical power, increasing your strength.*

| Level | Strength Bonus | Gold | Devotion |
|-------|----------------|------|----------|
| 1 | +3% | 1.5M | 150K |
| 2 | +6% | 5.5M | 550K |
| 3 | +10% | 15M | 1.5M |
| 4 | +15% | 40M | 4M |
| 5 | +20% | 100M | 10M |

### Divine Treasury
*A blessed treasury that increases gold drops from all monster kills for members.*

| Level | Gold Drop Bonus | Gold | Devotion |
|-------|-----------------|------|----------|
| 1 | +5% | 2M | 200K |
| 2 | +10% | 7M | 700K |
| 3 | +15% | 20M | 2M |
| 4 | +22% | 50M | 5M |
| 5 | +30% | 120M | 12M |

### Sanctuary of Peace
*A sacred space that increases your maximum HP through divine protection.*

| Level | Max HP Bonus | Gold | Devotion |
|-------|--------------|------|----------|
| 1 | +5% | 1.8M | 180K |
| 2 | +10% | 6M | 600K |
| 3 | +15% | 18M | 1.8M |
| 4 | +22% | 45M | 4.5M |
| 5 | +30% | 110M | 11M |

---

## Tier 6 - Holy Sanctum (5 features)

### Celestial Altar
*An altar touched by divine power that grants a chance for double devotion gains.*

| Level | Double Devotion Chance | Gold | Devotion |
|-------|------------------------|------|----------|
| 1 | 5% | 5M | 500K |
| 2 | 10% | 20M | 2M |
| 3 | 15% | 60M | 6M |
| 4 | 22% | 150M | 15M |
| 5 | 30% | 400M | 40M |

### Hall of Legends
*A hall commemorating legendary members, increasing your chance to find rare loot from monsters.*

| Level | Rare Loot Drop Bonus | Gold | Devotion |
|-------|---------------------|------|----------|
| 1 | +5% | 8M | 800K |
| 2 | +10% | 30M | 3M |
| 3 | +15% | 90M | 9M |
| 4 | +22% | 220M | 22M |
| 5 | +30% | 550M | 55M |

### Paradise Gardens
*Heavenly gardens that restore your energy when you pray here.*

| Level | Energy Restore | Gold | Devotion |
|-------|---------------|------|----------|
| 1 | +25 | 6M | 600K |
| 2 | +50 | 25M | 2.5M |
| 3 | +100 | 75M | 7.5M |
| 4 | +150 | 180M | 18M |
| 5 | +200 | 450M | 45M |

### Vault of Ages
*An ancient vault containing relics of legendary warriors, boosting all combat XP gains.*

| Level | All Combat XP Bonus | Gold | Devotion |
|-------|---------------------|------|----------|
| 1 | +5% | 10M | 1M |
| 2 | +10% | 40M | 4M |
| 3 | +15% | 120M | 12M |
| 4 | +22% | 300M | 30M |
| 5 | +30% | 750M | 75M |

### Divine Armory
*A blessed armory that grants all combat stat bonuses to members.*

| Level | All Combat Stats Bonus | Gold | Devotion |
|-------|------------------------|------|----------|
| 1 | +2% | 12M | 1.2M |
| 2 | +4% | 45M | 4.5M |
| 3 | +6% | 130M | 13M |
| 4 | +9% | 320M | 32M |
| 5 | +12% | 800M | 80M |

---

## Summary

- **6 HQ Tiers**: Chapel → Church → Temple → Cathedral → Grand Cathedral → Holy Sanctum
- **22 Features Total**: 2 + 3 + 4 + 4 + 4 + 5 per tier
- **5 Levels per Feature**: Each feature can be upgraded 5 times
- **Categories**: Altar, Library, Vault, Garden, Sanctum, Reliquary, Training
- **Prayer Buffs**: 5-30 minutes based on level
- **No Cooldowns**: Pray again immediately when buff expires
- **Stackable**: All buffs can be active simultaneously
