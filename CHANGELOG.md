# Recent Features Changelog

This document covers the 8 major features added in the latest update. Each section explains what the feature does, how to access it, and how to test it.

---

## 1. No Confidence Votes

**Commit:** `0f2cbfa` - Add no confidence votes system for challenging role holders

### What It Does
Allows residents to challenge role holders (Elders, Lords, Kings) through a democratic voting process. If the vote passes, the role holder is removed from their position.

### How to Access
- **URL:** `/no-confidence`
- **Start a vote:** From village/town/kingdom role pages, click "No Confidence Vote"

### How to Test
1. Go to a village where someone holds the Elder role: `/villages/{id}/roles`
2. As a resident, initiate a "No Confidence" vote against the Elder
3. The vote runs for 48 hours
4. Other residents can vote Yes/No at `/no-confidence/{id}`
5. If majority votes "Yes" and quorum is met, the role holder is removed

### Key Details
- 48-hour voting period
- Requires majority "Yes" votes to pass
- Only residents of that domain can vote
- Automatic role revocation if vote passes

---

## 2. Tax System

**Commit:** `3a66d3f` - Add tax system for economic flow between locations

### What It Does
Implements a full economic tax flow: Players → Villages → Castles → Kingdoms. Role holders receive salaries from their location's treasury.

### How to Access
- **Your taxes:** `/taxes`
- **Village treasury:** `/villages/{id}/taxes`
- **Castle treasury:** `/castles/{id}/taxes`
- **Kingdom treasury:** `/kingdoms/{id}/taxes`

### How to Test
1. View your tax history at `/taxes`
2. As a Lord, go to `/castles/{id}/taxes` and adjust the village tax rate
3. As a King, go to `/kingdoms/{id}/taxes` and adjust the castle tax rate
4. Wait for daily tax collection (or trigger manually via artisan)
5. Check treasury balances and salary payments

### Key Details
- Daily automatic tax collection via `CollectDailyTaxes` job
- Daily salary distribution via `DistributeSalaries` job
- Lords can set castle tax rates on villages
- Kings can set kingdom tax rates on castles
- Full audit trail of all treasury transactions

---

## 3. Chat System

**Commit:** `a1cf8ab` - Add chat system for location-based and private messaging

### What It Does
Location-based chat channels (village, castle, kingdom) and private player-to-player messaging with moderation tools.

### How to Access
- **Chat hub:** `/chat`
- **Village chat:** `/villages/{id}/chat`
- **Castle chat:** `/castles/{id}/chat`
- **Private messages:** `/chat/private/{username}`
- **Conversations:** `/chat/conversations`

### How to Test
1. Go to your home village's chat: `/villages/{id}/chat`
2. Send a message - it appears for all players in that village
3. Click on a player's name to start a private conversation
4. View all your private conversations at `/chat/conversations`
5. If you have moderation permissions, try deleting a message

### Key Details
- Polling-based real-time updates
- Location channels: village, castle, kingdom scope
- Private messaging between any two players
- Moderation tools for role holders with `moderate_chat` permission

---

## 4. NPC Crafting & Docket System

**Commit:** `017cbf0` - Add NPC crafting and docket system for player crafting orders

### What It Does
Two-part crafting system:
1. **NPC Shop:** Buy items instantly with gold (no materials, no XP)
2. **Player Docket:** Post crafting orders for other players to fulfill

### How to Access
- **Docket page:** `/docket`

### How to Test
1. Go to `/docket` and browse the NPC Shop tab
2. Purchase an item instantly with gold
3. Switch to "Place Order" tab and request a custom item
4. On another account, accept the order from "Available Orders"
5. Craft the item and complete the order to earn payment

### Key Details
- NPC crafting: instant items for gold (no XP earned)
- Player orders: set price, wait for crafter to accept
- 10-minute tardiness threshold for accepted orders
- Location stockpiles track materials

---

## 5. Combat System

**Commit:** `2d69192` - Add combat system for turn-based monster battles

### What It Does
Turn-based combat against monsters. Fight, eat food to heal, or flee. Earn XP, gold, and loot drops.

### How to Access
- **Combat page:** `/combat`

### How to Test
1. Go to `/combat` and browse available monsters
2. Select a training style (Attack/Strength/Defense) for XP focus
3. Click "Fight" on a monster appropriate for your level
4. In combat: Attack, Eat food from inventory, or Flee
5. Defeat the monster to earn XP, gold, and possible loot drops

### Key Details
- 18 monsters from rats to elder dragons
- Training style determines XP distribution
- Weapon type bonuses against certain monster types
- Death reduces energy; respawn at home village
- Loot tables with varying drop rates

---

## 6. Dungeon System

**Commit:** `c5304f2` - Add dungeon system for multi-floor instanced content

### What It Does
Multi-floor dungeon exploration with progressive difficulty. Fight through floors, accumulate rewards, and defeat the final boss.

### How to Access
- **Dungeons list:** `/dungeons`
- **View dungeon:** `/dungeons/{id}`

### How to Test
1. Go to `/dungeons` and find a dungeon matching your level
2. Click "Enter" to start an instanced session
3. Fight monsters on each floor
4. Use "Eat" to consume food between fights
5. Click "Next Floor" after clearing a floor
6. Complete the final boss floor to claim all rewards
7. Or "Abandon" to forfeit accumulated rewards

### Key Details
- 8 dungeons across 4 difficulty tiers (easy, medium, hard, nightmare)
- Multi-floor structure with boss on final floor
- XP and loot multipliers increase per floor
- Progress tracked per session
- Must complete or abandon - can't leave mid-dungeon

---

## 7. Religion System

**Commit:** `59630ae` - Add religion system for cults, religions, and beliefs

### What It Does
Create cults (small, secret groups) that can grow into full religions with temples, beliefs, and kingdom-level status.

### How to Access
- **Religions list:** `/religions`
- **View religion:** `/religions/{id}`
- **Structures:** `/religions/structures`

### How to Test
1. Go to `/religions` and click "Create Cult"
2. Name your cult and select 2 beliefs (virtues/vices with bonuses/penalties)
3. Recruit up to 5 members
4. Once you have 15+ members and 100K gold, convert to a Religion
5. Build shrines/temples/cathedrals at locations
6. Perform religious actions: prayer, donation, ritual, sacrifice, pilgrimage
7. As a King, set religions as state religion, tolerated, or banned

### Key Details
- **Cults:** 5 member max, free, secret by default, 2 beliefs
- **Religions:** 15+ members, 100K gold to convert, public, up to 5 beliefs
- **Ranks:** Prophet (founder), Priest (officers), Follower (members)
- **Structures:** Shrines, Temples, Cathedrals (devotion multipliers)
- **Kingdom status:** State religion, tolerated, or banned

---

## 8. Charter System

**Commit:** `d61339f` - Add charter system for founding new settlements

### What It Does
Found new villages, towns, or castles through a charter process requiring gold, signatures, and royal approval.

### How to Access
- **Charters list:** `/charters`
- **Kingdom charters:** `/kingdoms/{id}/charters`
- **View charter:** `/charters/{id}`

### How to Test
1. Go to `/charters` and click "Create Charter"
2. Choose settlement type:
   - Village: 1M gold, 10 signatures
   - Town: 2.5M gold, 25 signatures
   - Castle: 5M gold, 50 signatures
3. Pay the gold and set the location
4. Share the charter for others to sign
5. Once signatures are met, submit for royal approval
6. King approves or rejects with reason
7. If approved, click "Found Settlement"
8. New settlement has 14-day vulnerability window

### Key Details
- Gold costs: 1M / 2.5M / 5M for village/town/castle
- Signature requirements: 10 / 25 / 50 respectively
- Royal approval required (King can reject with reason)
- 14-day vulnerability window after founding
- Failed/abandoned settlements become Ruins
- Ruins can be reclaimed at reduced cost

---

## Quick Reference URLs

| Feature | Main URL | Additional URLs |
|---------|----------|-----------------|
| No Confidence | `/no-confidence` | `/no-confidence/{id}` |
| Taxes | `/taxes` | `/villages/{id}/taxes`, `/castles/{id}/taxes` |
| Chat | `/chat` | `/villages/{id}/chat`, `/chat/private/{user}` |
| Docket | `/docket` | - |
| Combat | `/combat` | - |
| Dungeons | `/dungeons` | `/dungeons/{id}` |
| Religions | `/religions` | `/religions/{id}`, `/religions/structures` |
| Charters | `/charters` | `/charters/{id}`, `/kingdoms/{id}/charters` |

---

## Running Database Migrations

After pulling these changes, run:

```bash
./vendor/bin/sail artisan migrate
./vendor/bin/sail artisan db:seed
```

Or for a fresh start:

```bash
./vendor/bin/sail artisan migrate:fresh --seed
```
