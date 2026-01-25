# PRD-ALPHA: Missing UI Implementation Tasks

This document tracks all UI pages that need to be built. Backend exists for all features.

**Tech Stack:** React 19 + Inertia.js + Tailwind CSS + Lucide Icons

**Design System:**
- Font: `font-pixel` for headers and labels
- Colors: Stone/amber/green/red/purple palette on dark backgrounds
- Cards: `rounded-xl border-2 border-{color}-500/50 bg-{color}-900/20 p-4`
- Buttons: `rounded border-2 border-{color}-600/50 bg-{color}-900/20 px-4 py-2 font-pixel text-xs`
- Status badges: `rounded px-1.5 py-0.5 font-pixel text-[10px]`

---

## Phase 3: Trade System

### Task 3.1: Caravan Detail Page
**Route:** `GET /trade/caravans/{caravan}`
**File:** `resources/js/pages/Trade/CaravanShow.tsx`
**Controller:** `CaravanController@show`

**Wireframe:**
```
┌─────────────────────────────────────────────────────────────┐
│ Caravan #12                          Status: Loading        │
│ Route: Oakvale → Ironforge (3 days)                        │
├─────────────────────────────────────────────────────────────┤
│ Goods Loaded                                    Total: 450g │
│ ┌─────────────────────────────────────────────────────────┐ │
│ │ Iron Ore      x50     @5g     250g          [Remove]   │ │
│ │ Wheat         x20     @10g    200g          [Remove]   │ │
│ └─────────────────────────────────────────────────────────┘ │
│                                                             │
│ Load More Goods                                             │
│ ┌─────────────────────────────────────────────────────────┐ │
│ │ [Select Item ▼]  Qty: [___]  Available: 100  [Add]    │ │
│ └─────────────────────────────────────────────────────────┘ │
│                                                             │
│              [Cancel Caravan]  [Dispatch Caravan →]         │
├─────────────────────────────────────────────────────────────┤
│ << If in_transit: >>                                        │
│ Progress: ████████████░░░░░░░░ 67% (Day 2 of 3)            │
│                                                             │
│ Event Log                                                   │
│ • Day 2: Clear skies, good progress                        │
│ • Day 1: Departed Oakvale                                  │
├─────────────────────────────────────────────────────────────┤
│ << If arrived: >>                                           │
│ ✓ Arrived at Ironforge!                                    │
│                                          [Unload Goods]     │
└─────────────────────────────────────────────────────────────┘
```

**Features:**
- [x] Display caravan status (loading, in_transit, arrived)
- [x] Show goods currently loaded with quantities and values
- [x] Load goods form (select from inventory, quantity picker)
- [x] Dispatch button (if status=loading)
- [x] Progress bar for in-transit caravans
- [x] Event log showing bandit attacks, weather, etc.
- [x] Unload goods button (if status=arrived)
- [x] Cancel/disband caravan option

**Props needed:**
```php
return Inertia::render('Trade/CaravanShow', [
    'caravan' => $caravan->load(['route.originSettlement', 'route.destinationSettlement', 'goods.item', 'events']),
    'inventory' => $user->inventory()->with('item')->get(),
]);
```

---

### Task 3.2: Tariff Management Page
**Route:** `GET /trade/tariffs`
**File:** `resources/js/pages/Trade/Tariffs.tsx`
**Controller:** `TariffController@index`

**Wireframe:**
```
┌─────────────────────────────────────────────────────────────┐
│ Tariff Management                     Your Territory: Oakvale│
├─────────────────────────────────────────────────────────────┤
│ Trade Routes Through Your Territory                         │
│ ┌─────────────────────────────────────────────────────────┐ │
│ │ Millbrook → Ironforge                                   │ │
│ │ Current Tariff: [15%___▼]              Revenue: 250g   │ │
│ │ Caravans this week: 5                    [Update]      │ │
│ ├─────────────────────────────────────────────────────────┤ │
│ │ Riverside → King's Landing                              │ │
│ │ Current Tariff: [10%___▼]              Revenue: 180g   │ │
│ │ Caravans this week: 3                    [Update]      │ │
│ └─────────────────────────────────────────────────────────┘ │
│                                                             │
│ Revenue Summary                                             │
│ ┌─────────────────────────────────────────────────────────┐ │
│ │ This Week: 430g  |  This Month: 1,850g  |  Total: 12,500g│
│ └─────────────────────────────────────────────────────────┘ │
│                                                             │
│ ⚠️ High tariffs (>25%) may cause merchants to avoid your   │
│    territory or use alternate routes.                      │
└─────────────────────────────────────────────────────────────┘
```

**Features:**
- [ ] List routes passing through player's territory
- [ ] Set tariff rate (0-50%) per route
- [ ] View tariff revenue collected
- [ ] Revenue history/summary

**Props needed:**
```php
return Inertia::render('Trade/Tariffs', [
    'routes' => TradeRoute::throughTerritory($user->ruledSettlement)->get(),
    'tariffs' => TradeTariff::where('authority_id', $user->id)->get(),
    'revenue' => TariffCollection::where('collector_id', $user->id)
        ->selectRaw('SUM(amount) as total, DATE(created_at) as date')
        ->groupBy('date')
        ->get(),
    'can_manage' => $user->isRuler(),
]);
```

---

## Phase 5: Warfare System

### Task 5.1: Army Detail Page
**Route:** `GET /warfare/armies/{army}`
**File:** `resources/js/pages/Warfare/ArmyShow.tsx`
**Controller:** `ArmyController@show`

**Wireframe:**
```
┌─────────────────────────────────────────────────────────────┐
│ ⚔️ Northern Host                    [Rename] [Disband Army] │
│ Commander: You | Location: Oakvale | Status: Encamped       │
├─────────────────────────────────────────────────────────────┤
│ Morale: ████████████████░░░░ 85%                           │
│ Supplies: ████████████░░░░░░░░ 60% (12 days remaining)     │
├─────────────────────────────────────────────────────────────┤
│ Unit Composition                              Total: 310    │
│ ┌───────────────┬────────┬────────┬────────┬─────────────┐ │
│ │ Unit Type     │ Count  │ Attack │ Defense│ Action      │ │
│ ├───────────────┼────────┼────────┼────────┼─────────────┤ │
│ │ Levy          │ 200    │ 200    │ 200    │ [Recruit +] │ │
│ │ Men-at-Arms   │ 50     │ 150    │ 100    │ [Recruit +] │ │
│ │ Knights       │ 10     │ 100    │ 80     │ [Recruit +] │ │
│ │ Archers       │ 30     │ 120    │ 30     │ [Recruit +] │ │
│ │ Cavalry       │ 20     │ 160    │ 60     │ [Recruit +] │ │
│ └───────────────┴────────┴────────┴────────┴─────────────┘ │
│ Total Combat Power: Attack 730 | Defense 470               │
├─────────────────────────────────────────────────────────────┤
│ Movement Orders                                             │
│ ┌─────────────────────────────────────────────────────────┐ │
│ │ March to: [Select Destination ▼]        [Begin March]  │ │
│ │ Nearby: Ironforge (2 days), Millbrook (1 day)         │ │
│ └─────────────────────────────────────────────────────────┘ │
├─────────────────────────────────────────────────────────────┤
│ Supply Line                                                 │
│ Source: Oakvale | Status: Active | Route: Safe             │
│                                      [Change Supply Source] │
├─────────────────────────────────────────────────────────────┤
│ Battle History                                              │
│ • Battle of Ironforge - Victory (3 days ago)               │
│ • Skirmish at River Crossing - Draw (7 days ago)           │
└─────────────────────────────────────────────────────────────┘
```

**Features:**
- [x] Army name, commander, location
- [x] Unit composition table (levy, men-at-arms, knights, archers, cavalry)
- [x] Recruit more units form (if at settlement)
- [x] Morale and supply status bars
- [x] Supply line info (source, status, days remaining)
- [x] Movement orders (select destination, march)
- [x] Disband army button
- [x] Battle history list

**Props needed:**
```php
return Inertia::render('Warfare/ArmyShow', [
    'army' => $army->load(['units', 'commander', 'location']),
    'supply_line' => $army->supplyLine,
    'available_recruits' => $this->armyService->getAvailableRecruits($army->location),
    'nearby_settlements' => $this->getReachableSettlements($army),
    'battle_history' => $army->battles()->latest()->limit(5)->get(),
]);
```

---

### Task 5.2: War Detail Page
**Route:** `GET /warfare/wars/{war}`
**File:** `resources/js/pages/Warfare/WarShow.tsx`
**Controller:** `WarController@show`

**Wireframe:**
```
┌─────────────────────────────────────────────────────────────┐
│ War for the Northern Reaches              Started: Spring 5 │
│ Casus Belli: Conquest                                       │
├─────────────────────────────────────────────────────────────┤
│ ┌─────────────────────────┐    ┌─────────────────────────┐ │
│ │      ATTACKERS          │ VS │      DEFENDERS          │ │
│ │ Northland Kingdom       │    │ Southron Kingdom        │ │
│ │ War Score: 45           │    │ War Score: 30           │ │
│ │ ████████████░░░░░░░░░░░ │    │ ████████░░░░░░░░░░░░░░░ │ │
│ │                         │    │                         │ │
│ │ Allies:                 │    │ Allies:                 │ │
│ │ • Eastmarch (+15)       │    │ • Westhold (+20)        │ │
│ └─────────────────────────┘    └─────────────────────────┘ │
│                                                             │
│ Your Role: Defender (Ally) | Contribution: 150 pts         │
├─────────────────────────────────────────────────────────────┤
│ War Goals                                                   │
│ ┌─────────────────────────────────────────────────────────┐ │
│ │ ☐ Conquer Barony of Ironhold        Progress: 60%      │ │
│ │ ☐ Win 3 major battles               Progress: 1/3      │ │
│ └─────────────────────────────────────────────────────────┘ │
├─────────────────────────────────────────────────────────────┤
│ Active Engagements                                          │
│ ⚔️ Battle of Ironforge (Day 3, Ongoing)         [View →]   │
│ 🏰 Siege of Castle Ironhold (Day 15)            [View →]   │
├─────────────────────────────────────────────────────────────┤
│ Recent Battles                                              │
│ • Battle of the River - Attacker Victory (5 days ago)      │
│ • Skirmish at Millbrook - Defender Victory (8 days ago)    │
├─────────────────────────────────────────────────────────────┤
│ [Offer Peace Treaty]  (Only if war leader)                 │
└─────────────────────────────────────────────────────────────┘
```

**Features:**
- [x] War name, casus belli, start date
- [x] Attacker vs Defender blocks with war scores
- [x] War score progress bar
- [x] Participant list (primary + allies) with contribution scores
- [x] War goals list with completion status
- [x] Active battles list with links
- [x] Active sieges list with links
- [x] Recent battle results
- [x] Peace offer button (for war leaders)

**Props needed:**
```php
return Inertia::render('Warfare/WarShow', [
    'war' => $war->load(['goals', 'participants.faction', 'attacker', 'defender']),
    'battles' => $war->battles()->latest()->limit(10)->get(),
    'sieges' => $war->sieges()->where('status', 'active')->get(),
    'can_offer_peace' => $war->isLeader($user),
    'player_participation' => $war->participants()->where('user_id', $user->id)->first(),
]);
```

---

### Task 5.3: Battle Viewer Page
**Route:** `GET /warfare/battles/{battle}`
**File:** `resources/js/pages/Warfare/BattleShow.tsx`
**Controller:** `BattleController@show`

**Wireframe:**
```
┌─────────────────────────────────────────────────────────────┐
│ Battle of Ironforge               Day 3 | Status: Ongoing   │
│ Part of: War for the Northern Reaches          [View War →] │
├─────────────────────────────────────────────────────────────┤
│ Terrain: Fortified (Defender +50% defense)                  │
├─────────────────────────────────────────────────────────────┤
│ ┌─────────────────────────┐    ┌─────────────────────────┐ │
│ │      ATTACKERS          │ VS │      DEFENDERS          │ │
│ │ Commander: Lord Stark   │    │ Commander: Baron Smith  │ │
│ │                         │    │                         │ │
│ │ Initial: 500            │    │ Initial: 400            │ │
│ │ Remaining: 380          │    │ Remaining: 320          │ │
│ │ Casualties: 120 (24%)   │    │ Casualties: 80 (20%)    │ │
│ │                         │    │                         │ │
│ │ Morale:                 │    │ Morale:                 │ │
│ │ ████████████░░░░ 65%    │    │ ██████████████░░ 72%    │ │
│ └─────────────────────────┘    └─────────────────────────┘ │
├─────────────────────────────────────────────────────────────┤
│ Battle Log                                                  │
│ ┌─────────────────────────────────────────────────────────┐ │
│ │ Day 3: Heavy fighting continues. Attackers push forward │ │
│ │        but defenders hold the walls.                    │ │
│ │        Attackers: -45 | Defenders: -30                 │ │
│ │ Day 2: Attackers launched assault on eastern gate.      │ │
│ │        Attackers: -50 | Defenders: -35                 │ │
│ │ Day 1: Armies engage near the walls of Ironforge.       │ │
│ │        Attackers: -25 | Defenders: -15                 │ │
│ └─────────────────────────────────────────────────────────┘ │
├─────────────────────────────────────────────────────────────┤
│ Participating Armies                                        │
│ Attackers: Northern Host (310), Eastern Levy (190)         │
│ Defenders: Ironforge Garrison (250), Relief Force (150)    │
└─────────────────────────────────────────────────────────────┘
```

**Features:**
- [ ] Battle name, location, terrain type
- [ ] Status (ongoing, attacker_victory, defender_victory)
- [ ] Attacker vs Defender force comparison
- [ ] Initial strength, current strength, casualties
- [ ] Morale bars for both sides
- [ ] Terrain modifier display
- [ ] Day-by-day battle log
- [ ] Commander names
- [ ] Link back to war

**Props needed:**
```php
return Inertia::render('Warfare/BattleShow', [
    'battle' => $battle->load(['participants.army', 'war', 'location']),
    'logs' => $battle->logs ?? [], // JSON field or related table
]);
```

---

### Task 5.4: Declare War Page
**Route:** `GET /warfare/declare`
**File:** `resources/js/pages/Warfare/DeclareWar.tsx`
**Controller:** `WarController@declareForm`

**Wireframe:**
```
┌─────────────────────────────────────────────────────────────┐
│ Declare War                                                 │
├─────────────────────────────────────────────────────────────┤
│ Target                                                      │
│ [Select Kingdom or Barony ▼_________________________]       │
│                                                             │
│ Target Info: Southron Kingdom                               │
│ Ruler: King Edward | Military: ~2,500 soldiers             │
│ Allies: Westhold, Coastal Reach                            │
├─────────────────────────────────────────────────────────────┤
│ Casus Belli (Justification)                                 │
│ ○ Conquest - Take territory by force                       │
│   └─ Legitimacy impact: -20                                │
│ ○ Claim Pressed - You have a legal claim to territory      │
│   └─ Legitimacy impact: -5                                 │
│ ○ Holy War - Religious differences                         │
│   └─ Legitimacy impact: +10 (with believers)               │
│ ● Retaliation - They attacked your ally                    │
│   └─ Legitimacy impact: 0                                  │
├─────────────────────────────────────────────────────────────┤
│ War Goals                                                   │
│ ☑ Conquer Barony of Ironhold                               │
│ ☐ Conquer Barony of Millbrook                              │
│ ☐ Enforce tribute payments                                 │
├─────────────────────────────────────────────────────────────┤
│ Your Forces                                                 │
│ Total soldiers: 1,200 | Estimated chance: 45%              │
│                                                             │
│ Potential Allies Who May Join                               │
│ • Eastmarch (+500 soldiers) - likely to join               │
│ • Northern Isles (+300 soldiers) - may join                │
├─────────────────────────────────────────────────────────────┤
│ ⚠️ Warning: This will begin active warfare. Your           │
│    legitimacy will change by -5. Truces will be broken.    │
│                                                             │
│                        [Cancel]  [Declare War]              │
└─────────────────────────────────────────────────────────────┘
```

**Features:**
- [ ] Target selection (kingdom or barony dropdown)
- [ ] Casus belli selection with descriptions
- [ ] War goal selection based on casus belli
- [ ] Legitimacy impact preview
- [ ] Allied kingdoms who might join
- [ ] Enemy strength estimate
- [ ] Confirm declaration button

**Props needed:**
```php
return Inertia::render('Warfare/DeclareWar', [
    'potential_targets' => $this->warService->getValidTargets($user),
    'casus_belli_types' => WarGoal::CASUS_BELLI_TYPES,
    'player_armies' => Army::where('commander_id', $user->id)->with('units')->get(),
    'legitimacy' => $user->legitimacy,
    'potential_allies' => $this->warService->getPotentialAllies($user),
]);
```

---

### Task 5.5: Peace Negotiation Page
**Route:** `GET /warfare/wars/{war}/peace`
**File:** `resources/js/pages/Warfare/PeaceNegotiation.tsx`
**Controller:** `WarController@peaceForm`

**Wireframe:**
```
┌─────────────────────────────────────────────────────────────┐
│ Peace Negotiation                                           │
│ War for the Northern Reaches                                │
├─────────────────────────────────────────────────────────────┤
│ Current War Score                                           │
│ Attackers: 45  ████████████████░░░░░░░░░░░░░  Defenders: 30 │
│ (You are winning)                                           │
├─────────────────────────────────────────────────────────────┤
│ Pending Peace Offers                                        │
│ ┌─────────────────────────────────────────────────────────┐ │
│ │ From: King Edward (Defender)                           │ │
│ │ Terms: Cede Ironhold, Pay 500g                        │ │
│ │ Truce: 2 years                                        │ │
│ │                            [Accept]  [Reject]  [Counter]│ │
│ └─────────────────────────────────────────────────────────┘ │
├─────────────────────────────────────────────────────────────┤
│ Create Peace Offer                                          │
│                                                             │
│ Territory Changes                                           │
│ ☑ They cede: Barony of Ironhold                            │
│ ☐ They cede: Barony of Millbrook                           │
│ ☐ You cede: (none available based on war score)            │
│                                                             │
│ Gold Payment                                                │
│ They pay you: [____500____] gold                           │
│ ◀━━━━━━━━━━━━━━━●━━━━━━━━▶ (0 - 2000)                      │
│                                                             │
│ Truce Duration                                              │
│ [2 years ▼]                                                │
│                                                             │
│ Acceptance Likelihood: 65% (Likely)                        │
│ └─ War score favors you (+20%)                             │
│ └─ Terms are moderate (+15%)                               │
│ └─ Enemy war exhaustion high (+30%)                        │
│                                                             │
│                              [Cancel]  [Send Peace Offer]   │
└─────────────────────────────────────────────────────────────┘
```

**Features:**
- [ ] Current war score display
- [ ] Territory changes selector (cede provinces)
- [ ] Gold payment slider
- [ ] Truce duration selector
- [ ] Calculate acceptance likelihood
- [ ] Send peace offer button
- [ ] View incoming peace offers
- [ ] Accept/reject/counter offer buttons

**Props needed:**
```php
return Inertia::render('Warfare/PeaceNegotiation', [
    'war' => $war->load('participants'),
    'war_score' => $this->warService->calculateWarScore($war),
    'territories' => $this->warService->getTransferableTerritories($war, $user),
    'pending_offers' => PeaceTreaty::where('war_id', $war->id)
        ->where('status', 'pending')
        ->get(),
    'is_war_leader' => $war->isLeader($user),
]);
```

---

## Phase 6: World Events

### Task 6.1: Festival Detail Page
**Route:** `GET /events/festivals/{festival}`
**File:** `resources/js/pages/Events/FestivalShow.tsx`
**Controller:** `FestivalController@show`

**Wireframe:**
```
┌─────────────────────────────────────────────────────────────┐
│ 🎪 Spring Festival                         Location: Oakvale │
│ "Celebrating the return of warmth and new beginnings"       │
├─────────────────────────────────────────────────────────────┤
│ Duration: Spring 20 - Spring 23 (3 days)                    │
│ Status: Day 2 of 3 | Participants: 45                       │
├─────────────────────────────────────────────────────────────┤
│ Your Status: Participating ✓                                │
│ Rewards Earned: 50 gold, +10 happiness      [Leave Festival]│
├─────────────────────────────────────────────────────────────┤
│ Activities                                                  │
│ ┌─────────────────────────────────────────────────────────┐ │
│ │ 💃 Dance Contest                          [Participate] │ │
│ │ Test your rhythm! Win gold and prestige.                │ │
│ │ Reward: 25g + 5 prestige | Cooldown: 1 day             │ │
│ ├─────────────────────────────────────────────────────────┤ │
│ │ 🍖 Feast                                  [Join Feast]  │ │
│ │ Enjoy food and drink with fellow villagers.            │ │
│ │ Reward: +20 energy, +5 happiness | Cooldown: 12 hours  │ │
│ ├─────────────────────────────────────────────────────────┤ │
│ │ 🎲 Games of Chance                        [Play Games]  │ │
│ │ Try your luck at dice and cards.                       │ │
│ │ Entry: 10g | Potential win: 50g | Cooldown: 1 hour     │ │
│ └─────────────────────────────────────────────────────────┘ │
├─────────────────────────────────────────────────────────────┤
│ Participants                                                │
│ PlayerOne, PlayerTwo, NPC_Miller, NPC_Baker, +41 more      │
└─────────────────────────────────────────────────────────────┘
```

**Features:**
- [x] Festival name, type, location
- [x] Date range (start/end)
- [x] Description and activities available
- [x] Participation rewards
- [x] Current participants list
- [x] Join/leave festival button
- [x] Activity participation buttons
- [x] Your rewards earned

**Props needed:**
```php
return Inertia::render('Events/FestivalShow', [
    'festival' => $festival->load(['type', 'location']),
    'participants' => $festival->participants()->with('user')->limit(50)->get(),
    'is_participating' => $festival->participants()->where('user_id', $user->id)->exists(),
    'activities' => $festival->type->activities,
    'player_rewards' => $festival->participants()->where('user_id', $user->id)->first()?->rewards,
]);
```

---

### Task 6.2: Tournament Bracket Page
**Route:** `GET /events/tournaments/{tournament}`
**File:** `resources/js/pages/Events/TournamentShow.tsx`
**Controller:** `TournamentController@show`

**Wireframe:**
```
┌─────────────────────────────────────────────────────────────┐
│ ⚔️ Grand Melee Tournament              Status: Round 2      │
│ Location: King's Landing | Type: Melee Combat              │
├─────────────────────────────────────────────────────────────┤
│ Prize: 500g + Champion Title | Entry Fee: 50g              │
│ Registered: 8 / 16 competitors                             │
│ Registration closes: Summer 1                              │
│                                         [Register] [Withdraw]│
├─────────────────────────────────────────────────────────────┤
│ Tournament Bracket                                          │
│                                                             │
│     Round 1              Round 2            Finals          │
│ ┌─────────────┐                                             │
│ │ Player A ✓  │─┐                                          │
│ │ Player B    │ ├──┌─────────────┐                         │
│ └─────────────┘   │ Player A ✓  │─┐                        │
│ ┌─────────────┐   │ Player C    │ │                        │
│ │ Player C ✓  │─┘ └─────────────┘ │   ┌─────────────┐      │
│ │ Player D    │                   ├───│ ??????????  │      │
│ └─────────────┘                   │   └─────────────┘      │
│ ┌─────────────┐   ┌─────────────┐ │                        │
│ │ Player E    │─┐ │ Player F    │─┘                        │
│ │ Player F ✓  │ ├─│ (awaiting)  │                          │
│ └─────────────┘   └─────────────┘                          │
│ ┌─────────────┐                                             │
│ │ Player G ✓  │─┘                                          │
│ │ Player H    │                                             │
│ └─────────────┘                                             │
├─────────────────────────────────────────────────────────────┤
│ Your Status: Competing                                      │
│ Next Match: vs Player F (Round 2)                          │
│ Your Record: 1 Win, 0 Losses                               │
├─────────────────────────────────────────────────────────────┤
│ Competitors                                                 │
│ ┌──────────────┬───────┬───────┬────────┐                  │
│ │ Name         │ ATK   │ DEF   │ Record │                  │
│ ├──────────────┼───────┼───────┼────────┤                  │
│ │ Player A     │ 45    │ 38    │ 2-0    │                  │
│ │ You          │ 42    │ 40    │ 1-0    │                  │
│ │ Player F     │ 50    │ 35    │ 1-0    │                  │
│ └──────────────┴───────┴───────┴────────┘                  │
└─────────────────────────────────────────────────────────────┘
```

**Features:**
- [ ] Tournament name, type (melee, joust, archery)
- [ ] Prize pool and entry fee
- [ ] Registration status and deadline
- [ ] Competitor list with stats
- [ ] Visual bracket display (rounds)
- [ ] Match results with winners highlighted
- [ ] Your next match info
- [ ] Register button (if not registered)
- [ ] Withdraw button (if registered, before start)

**Props needed:**
```php
return Inertia::render('Events/TournamentShow', [
    'tournament' => $tournament->load('type'),
    'competitors' => TournamentCompetitor::where('tournament_id', $tournament->id)
        ->with('user')
        ->get(),
    'matches' => TournamentMatch::where('tournament_id', $tournament->id)
        ->orderBy('round')
        ->get()
        ->groupBy('round'),
    'is_registered' => $tournament->competitors()->where('user_id', $user->id)->exists(),
    'player_matches' => TournamentMatch::where('tournament_id', $tournament->id)
        ->where(fn($q) => $q->where('competitor1_id', $user->id)->orWhere('competitor2_id', $user->id))
        ->get(),
]);
```

---

### Task 6.3: Building Construction Page
**Route:** `GET /buildings`
**File:** `resources/js/pages/Buildings/Index.tsx`
**Controller:** `BuildingController@index`

**Wireframe:**
```
┌─────────────────────────────────────────────────────────────┐
│ Buildings - Oakvale                      [Start New Project]│
├─────────────────────────────────────────────────────────────┤
│ Existing Buildings                                          │
│ ┌─────────────────────────────────────────────────────────┐ │
│ │ 🏠 Cottage x5              Condition: ████████░░ 85%   │ │
│ │                                              [Repair]   │ │
│ ├─────────────────────────────────────────────────────────┤ │
│ │ 🏭 Smithy x1               Condition: ██████████ 100%  │ │
│ │                                                        │ │
│ ├─────────────────────────────────────────────────────────┤ │
│ │ 🏪 Market x1               Condition: ███████░░░ 70%   │ │
│ │ Produces: +10% trade income          [Repair] (30 stone)│ │
│ ├─────────────────────────────────────────────────────────┤ │
│ │ 🏰 Palisade Wall           Condition: █████░░░░░ 50%   │ │
│ │ Defense: +100                        [Repair] (50 wood) │ │
│ └─────────────────────────────────────────────────────────┘ │
├─────────────────────────────────────────────────────────────┤
│ Under Construction                                          │
│ ┌─────────────────────────────────────────────────────────┐ │
│ │ 🏗️ Granary                                              │ │
│ │ Progress: ████████████████░░░░░░░░░░░░░░ 60%           │ │
│ │ Workers: 5 | Days remaining: ~4        [Cancel Project] │ │
│ └─────────────────────────────────────────────────────────┘ │
├─────────────────────────────────────────────────────────────┤
│ Available to Build                                          │
│ ┌──────────────────┬─────────────────────┬────────┬───────┐│
│ │ Building         │ Resources Required  │ Time   │ Action││
│ ├──────────────────┼─────────────────────┼────────┼───────┤│
│ │ Chapel           │ Stone 40, Wood 30   │ 14 days│ [Build]│
│ │ Well             │ Stone 30            │ 5 days │ [Build]│
│ │ Stone Wall       │ Stone 200, Iron 20  │ 30 days│ [Build]│
│ │ Tavern           │ Wood 50, Stone 20   │ 10 days│ [Build]│
│ └──────────────────┴─────────────────────┴────────┴───────┘│
│                                                             │
│ Your Resources: Stone 150, Wood 200, Iron 30               │
└─────────────────────────────────────────────────────────────┘
```

**Features:**
- [ ] Current location's existing buildings with conditions
- [ ] Repair button for damaged buildings
- [ ] Under construction section with progress bars
- [ ] Available buildings to construct
- [ ] Resource requirements display
- [ ] Construction time estimate
- [ ] Start construction button
- [ ] Cancel project option

**Props needed:**
```php
return Inertia::render('Buildings/Index', [
    'buildings' => Building::where('location_type', $locationType)
        ->where('location_id', $locationId)
        ->with('type')
        ->get(),
    'projects' => ConstructionProject::where('location_type', $locationType)
        ->where('location_id', $locationId)
        ->where('status', 'in_progress')
        ->with('buildingType')
        ->get(),
    'available_types' => BuildingType::whereNotIn('id',
        Building::where('location_type', $locationType)
            ->where('location_id', $locationId)
            ->pluck('building_type_id')
    )->get(),
    'resources' => $user->getResources(),
    'can_build' => $user->canBuildAt($locationType, $locationId),
]);
```

---

## Phase 7: Dynasty System

### Task 7.1: Dynasty Overview Page
**Route:** `GET /dynasty`
**File:** `resources/js/pages/Dynasty/Index.tsx`
**Controller:** `DynastyController@index`

**Wireframe:**
```
┌─────────────────────────────────────────────────────────────┐
│ House Stark                              [Edit] [View Crest]│
│ "Winter is Coming"                                          │
├─────────────────────────────────────────────────────────────┤
│ ┌─────────────┐  Prestige: 1,250 (Rank: Notable)           │
│ │             │  Members: 12 (7 living)                    │
│ │   CREST     │  Generations: 4                            │
│ │             │  Founded: Year 1                           │
│ └─────────────┘                                             │
│                                                             │
│ Head of House: Lord Eddard Stark (You)                     │
│ Heir Apparent: Robb Stark (son, age 17)                    │
├─────────────────────────────────────────────────────────────┤
│ Quick Links                                                 │
│ [👪 Family Tree] [📜 History] [🤝 Alliances] [⚖️ Succession]│
│ [💍 Marriage Proposals]                                     │
├─────────────────────────────────────────────────────────────┤
│ Living Members                                              │
│ ┌─────────────────────────────────────────────────────────┐ │
│ │ 👤 Eddard Stark (You)     Head | Age 42 | Married      │ │
│ │ 👤 Catelyn Stark          Spouse | Age 38              │ │
│ │ 👤 Robb Stark             Heir | Age 17 | Single       │ │
│ │ 👤 Sansa Stark            Daughter | Age 14            │ │
│ │ 👤 Arya Stark             Daughter | Age 11            │ │
│ │ 👤 Bran Stark             Son | Age 10                 │ │
│ │ 👤 Rickon Stark           Son | Age 6                  │ │
│ └─────────────────────────────────────────────────────────┘ │
├─────────────────────────────────────────────────────────────┤
│ Recent Events                                               │
│ • Robb Stark came of age (Spring, Year 3)                  │
│ • Alliance formed with House Tully (Winter, Year 2)        │
└─────────────────────────────────────────────────────────────┘

─── OR if no dynasty: ───

┌─────────────────────────────────────────────────────────────┐
│ Found a Dynasty                                             │
├─────────────────────────────────────────────────────────────┤
│ You have not yet founded a dynasty. A dynasty allows you to:│
│ • Pass on titles and wealth to heirs                       │
│ • Form alliances through marriage                          │
│ • Build lasting prestige and legacy                        │
│                                                             │
│ Requirements:                                               │
│ ✓ Be a Freeman or higher social class                      │
│ ✓ Have 100 gold                                            │
│ ✓ Own property or hold a title                             │
│                                                             │
│ Dynasty Name: [________________]                           │
│ Motto: [________________________________]                   │
│                                                             │
│                                    [Found Dynasty] (100g)   │
└─────────────────────────────────────────────────────────────┘
```

**Features:**
- [ ] Dynasty name, motto, crest display
- [ ] Prestige score and rank
- [ ] Member count, generations, founding date
- [ ] Current head and heir
- [ ] Quick links to tree, history, alliances
- [ ] Living members list with ages and status
- [ ] Found dynasty button (if no dynasty)
- [ ] Edit dynasty button (if head)

**Props needed:**
```php
return Inertia::render('Dynasty/Index', [
    'dynasty' => $user->dynasty?->load('members'),
    'members' => $user->dynasty?->members()->where('is_alive', true)->get(),
    'head' => $user->dynasty?->head,
    'heir' => $user->dynasty ? $this->dynastyService->calculateHeir($user->dynasty) : null,
    'is_head' => $user->dynasty?->head_id === $user->id,
    'can_found' => !$user->dynasty && $user->canFoundDynasty(),
    'recent_events' => $user->dynasty?->events()->latest()->limit(5)->get(),
]);
```

---

### Task 7.2: Family Tree Page
**Route:** `GET /dynasty/tree`
**File:** `resources/js/pages/Dynasty/Tree.tsx`
**Controller:** `DynastyController@tree`

**Wireframe:**
```
┌─────────────────────────────────────────────────────────────┐
│ Family Tree - House Stark            [Filter ▼] [Zoom +/-] │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│                    Generation 1                             │
│                   ┌───────────┐                             │
│                   │ Rickard   │                             │
│                   │ Stark †   │                             │
│                   │ 210-258   │                             │
│                   └─────┬─────┘                             │
│                         │                                   │
│          ┌──────────────┼──────────────┐                   │
│          │              │              │                   │
│    Generation 2         │              │                   │
│   ┌───────────┐   ┌───────────┐   ┌───────────┐           │
│   │ Brandon   │   │ Eddard ♔  │═══│ Catelyn   │           │
│   │ Stark †   │   │ Stark     │   │ Tully     │           │
│   │ 232-258   │   │ b. 235    │   │ b. 237    │           │
│   └───────────┘   └─────┬─────┘   └───────────┘           │
│                         │                                   │
│    ┌────────┬───────┬───┴───┬────────┬────────┐           │
│    │        │       │       │        │        │           │
│   Generation 3                                             │
│ ┌──────┐ ┌──────┐ ┌──────┐ ┌──────┐ ┌──────┐ ┌──────┐    │
│ │Robb ★│ │Sansa │ │Arya  │ │Bran  │ │Rickon│ │Jon   │    │
│ │ 17   │ │ 14   │ │ 11   │ │ 10   │ │ 6    │ │ 17 † │    │
│ └──────┘ └──────┘ └──────┘ └──────┘ └──────┘ └──────┘    │
│                                                             │
│ Legend: ♔ = Head | ★ = Heir | † = Deceased | ═ = Marriage │
├─────────────────────────────────────────────────────────────┤
│ Click any member for details                               │
└─────────────────────────────────────────────────────────────┘
```

**Features:**
- [ ] Visual tree layout (parents, siblings, children)
- [ ] Member cards with name, birth/death, spouse
- [ ] Marriage lines connecting families
- [ ] Generation labels
- [ ] Click member for detail popup
- [ ] Zoom/pan controls for large trees
- [ ] Highlight player's position
- [ ] Filter by living/deceased

**Props needed:**
```php
return Inertia::render('Dynasty/Tree', [
    'dynasty' => $user->dynasty,
    'members' => DynastyMember::where('dynasty_id', $user->dynasty_id)
        ->with(['father', 'mother', 'spouse'])
        ->get(),
    'marriages' => Marriage::whereHas('partners', fn($q) =>
        $q->whereIn('dynasty_member_id', $user->dynasty->members->pluck('id'))
    )->with('partners')->get(),
    'player_member' => $user->dynastyMember,
]);
```

---

### Task 7.3: Marriage Proposals Page
**Route:** `GET /dynasty/proposals`
**File:** `resources/js/pages/Dynasty/Proposals.tsx`
**Controller:** `MarriageController@proposals`

**Wireframe:**
```
┌─────────────────────────────────────────────────────────────┐
│ Marriage Proposals                       [Make New Proposal]│
├─────────────────────────────────────────────────────────────┤
│ Incoming Proposals (2)                                      │
│ ┌─────────────────────────────────────────────────────────┐ │
│ │ From: House Bolton                     Received: 3d ago │ │
│ │ Proposed Match: Ramsay Bolton → Sansa Stark            │ │
│ │ Dowry Offered: 500g                                    │ │
│ │ Message: "A union to strengthen the North"             │ │
│ │                                                        │ │
│ │ Alliance Implications: +Relations with Bolton          │ │
│ │                                                        │ │
│ │                              [Accept]  [Reject]        │ │
│ ├─────────────────────────────────────────────────────────┤ │
│ │ From: House Tyrell                     Received: 5d ago │ │
│ │ Proposed Match: Loras Tyrell → Sansa Stark            │ │
│ │ Dowry Offered: 1000g                                   │ │
│ │                              [Accept]  [Reject]        │ │
│ └─────────────────────────────────────────────────────────┘ │
├─────────────────────────────────────────────────────────────┤
│ Outgoing Proposals (1)                                      │
│ ┌─────────────────────────────────────────────────────────┐ │
│ │ To: House Tully                        Status: Pending  │ │
│ │ Proposed Match: Robb Stark → Roslin Frey              │ │
│ │ Dowry Offered: 800g                                    │ │
│ │ Sent: 2 days ago                                       │ │
│ │                                             [Withdraw]  │ │
│ └─────────────────────────────────────────────────────────┘ │
├─────────────────────────────────────────────────────────────┤
│ Recent Marriages                                            │
│ • Eddard Stark & Catelyn Tully (Year 1)                   │
│ • Brandon Stark & Ashara Dayne † (Year 0)                 │
└─────────────────────────────────────────────────────────────┘
```

**Features:**
- [ ] Incoming proposals section
- [ ] Proposer, proposed match, dowry offered
- [ ] Accept/reject buttons with confirmation
- [ ] Outgoing proposals section
- [ ] Status (pending, accepted, rejected)
- [ ] Withdraw button for pending
- [ ] Make new proposal link
- [ ] Marriage history section

**Props needed:**
```php
return Inertia::render('Dynasty/Proposals', [
    'incoming' => MarriageProposal::whereHas('targetMember', fn($q) =>
        $q->where('dynasty_id', $user->dynasty_id)
    )->where('status', 'pending')->with(['proposer', 'proposerMember', 'targetMember'])->get(),
    'outgoing' => MarriageProposal::where('proposer_id', $user->id)
        ->with(['targetMember.dynasty', 'proposerMember'])
        ->get(),
    'marriages' => Marriage::whereHas('partners.member', fn($q) =>
        $q->where('dynasty_id', $user->dynasty_id)
    )->latest()->limit(10)->get(),
    'can_propose' => $user->dynasty->members()->where('is_married', false)->where('age', '>=', 16)->exists(),
]);
```

---

### Task 7.4: Propose Marriage Page
**Route:** `GET /dynasty/proposals/create`
**File:** `resources/js/pages/Dynasty/ProposeMarriage.tsx`
**Controller:** `MarriageController@proposeForm`

**Wireframe:**
```
┌─────────────────────────────────────────────────────────────┐
│ Propose Marriage                                            │
├─────────────────────────────────────────────────────────────┤
│ Select Dynasty Member to Marry                              │
│ ┌─────────────────────────────────────────────────────────┐ │
│ │ ○ Robb Stark (son, age 17, single)                     │ │
│ │ ● Sansa Stark (daughter, age 14, single)               │ │
│ │ ○ Arya Stark (daughter, age 11, single)                │ │
│ └─────────────────────────────────────────────────────────┘ │
├─────────────────────────────────────────────────────────────┤
│ Search for Partner                                          │
│ [Search by name...____________________]                     │
│                                                             │
│ Filters: [Kingdom ▼] [Dynasty ▼] [Age 16-30 ▼] [Class ▼]   │
│                                                             │
│ Eligible Candidates                                         │
│ ┌─────────────────────────────────────────────────────────┐ │
│ │ ○ Margaery Tyrell | House Tyrell | Age 16 | Highgarden │ │
│ │   Traits: Charismatic, Beautiful                       │ │
│ │   Would bring: +Alliance with Reach, +500 prestige     │ │
│ ├─────────────────────────────────────────────────────────┤ │
│ │ ● Myrcella Baratheon | House Baratheon | Age 14 | KL   │ │
│ │   Traits: Kind, Gentle                                 │ │
│ │   Would bring: +Royal connection, +800 prestige        │ │
│ └─────────────────────────────────────────────────────────┘ │
├─────────────────────────────────────────────────────────────┤
│ Proposal Details                                            │
│                                                             │
│ Dowry Amount: [____500____] gold (You have: 2000g)         │
│ ◀━━━━━━━━━●━━━━━━━━━━━━━━━▶                                │
│                                                             │
│ Message to House Baratheon:                                │
│ ┌─────────────────────────────────────────────────────────┐ │
│ │ We propose a union between our houses...               │ │
│ └─────────────────────────────────────────────────────────┘ │
├─────────────────────────────────────────────────────────────┤
│ Alliance Preview                                            │
│ • Marriage alliance with House Baratheon                   │
│ • +15% relations with the Crown                           │
│ • Combined prestige boost: +800                           │
│                                                             │
│                           [Cancel]  [Send Proposal]         │
└─────────────────────────────────────────────────────────────┘
```

**Features:**
- [ ] Select dynasty member to marry off
- [ ] Search eligible candidates (age, unmarried)
- [ ] Filter by dynasty, kingdom, class
- [ ] View candidate stats/traits
- [ ] Set dowry amount
- [ ] Add message to proposal
- [ ] Preview alliance implications
- [ ] Submit proposal button

**Props needed:**
```php
return Inertia::render('Dynasty/ProposeMarriage', [
    'eligible_members' => $user->dynasty->members()
        ->where('is_married', false)
        ->where('age', '>=', 14)
        ->where('is_alive', true)
        ->get(),
    'candidates' => DynastyMember::where('dynasty_id', '!=', $user->dynasty_id)
        ->where('is_married', false)
        ->where('age', '>=', 14)
        ->where('is_alive', true)
        ->with('dynasty')
        ->paginate(20),
    'player_gold' => $user->gold,
]);
```

---

### Task 7.5: Succession Settings Page
**Route:** `GET /dynasty/succession`
**File:** `resources/js/pages/Dynasty/Succession.tsx`
**Controller:** `SuccessionController@index`

**Wireframe:**
```
┌─────────────────────────────────────────────────────────────┐
│ Succession Rules - House Stark                              │
├─────────────────────────────────────────────────────────────┤
│ Current Rules                                               │
│ ┌─────────────────────────────────────────────────────────┐ │
│ │ Succession Type: Primogeniture                          │ │
│ │ (Eldest child inherits)                                │ │
│ │                                                        │ │
│ │ Gender Law: Agnatic-Cognatic                           │ │
│ │ (Males inherit first, females if no males)             │ │
│ │                                                        │ │
│ │ Bastards: Not eligible                                 │ │
│ │                                                        │ │
│ │ Minimum Age: 16                                        │ │
│ └─────────────────────────────────────────────────────────┘ │
│                                      [Change Rules] (-200 prestige)│
├─────────────────────────────────────────────────────────────┤
│ Current Line of Succession                                  │
│ ┌─────────────────────────────────────────────────────────┐ │
│ │ 1. ★ Robb Stark (son, age 17)              [Disinherit]│ │
│ │ 2.   Bran Stark (son, age 10)              [Disinherit]│ │
│ │ 3.   Rickon Stark (son, age 6)             [Disinherit]│ │
│ │ 4.   Sansa Stark (daughter, age 14)        [Disinherit]│ │
│ │ 5.   Arya Stark (daughter, age 11)         [Disinherit]│ │
│ └─────────────────────────────────────────────────────────┘ │
│ ★ = Current Heir                                           │
├─────────────────────────────────────────────────────────────┤
│ << Change Rules Modal >>                                    │
│ ┌─────────────────────────────────────────────────────────┐ │
│ │ Succession Type:                                        │ │
│ │ ○ Primogeniture (eldest inherits)                      │ │
│ │ ○ Ultimogeniture (youngest inherits)                   │ │
│ │ ○ Seniority (oldest living member)                     │ │
│ │ ○ Elective (members vote)                              │ │
│ │                                                        │ │
│ │ Gender Law:                                            │ │
│ │ ○ Agnatic (males only)                                 │ │
│ │ ○ Agnatic-Cognatic (males first)                       │ │
│ │ ○ Absolute (equal)                                     │ │
│ │                                                        │ │
│ │ Cost: 200 prestige (You have: 1,250)                  │ │
│ │                              [Cancel]  [Confirm Change]│ │
│ └─────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────┘
```

**Features:**
- [ ] Current succession type (primogeniture, etc.)
- [ ] Gender law setting
- [ ] Bastard inheritance setting
- [ ] Minimum age setting
- [ ] Current line of succession (ordered list)
- [ ] Change rules button (prestige cost)
- [ ] Disinherit member button (prestige cost)

**Props needed:**
```php
return Inertia::render('Dynasty/Succession', [
    'rules' => SuccessionRule::where('dynasty_id', $user->dynasty_id)->first(),
    'succession_line' => $this->dynastyService->getSuccessionLine($user->dynasty),
    'available_rules' => SuccessionRule::TYPES,
    'gender_laws' => SuccessionRule::GENDER_LAWS,
    'prestige' => $user->dynasty->prestige,
    'change_cost' => 200,
    'disinherit_cost' => 100,
]);
```

---

### Task 7.6: Dynasty History Page
**Route:** `GET /dynasty/history`
**File:** `resources/js/pages/Dynasty/History.tsx`
**Controller:** `DynastyController@history`

**Wireframe:**
```
┌─────────────────────────────────────────────────────────────┐
│ Dynasty History - House Stark                               │
├─────────────────────────────────────────────────────────────┤
│ Filter: [All Events ▼]  [All Time ▼]           Total: 47   │
├─────────────────────────────────────────────────────────────┤
│ Timeline                                                    │
│ ┌─────────────────────────────────────────────────────────┐ │
│ │ Spring 15, Year 3                            +50 prestige│
│ │ 🎂 BIRTH                                                │ │
│ │ Arya Stark gave birth to a son.                        │ │
│ ├─────────────────────────────────────────────────────────┤ │
│ │ Winter 3, Year 3                             +100 prestige│
│ │ ⚔️ VICTORY                                              │ │
│ │ Robb Stark won the Battle of Riverrun.                 │ │
│ ├─────────────────────────────────────────────────────────┤ │
│ │ Fall 20, Year 2                              +200 prestige│
│ │ 💍 MARRIAGE                                             │ │
│ │ Robb Stark married Jeyne Westerling of House Westerling.│
│ ├─────────────────────────────────────────────────────────┤ │
│ │ Summer 1, Year 2                             -50 prestige│
│ │ 💀 DEATH                                                │ │
│ │ Brandon Stark died in a hunting accident. Age 28.      │ │
│ ├─────────────────────────────────────────────────────────┤ │
│ │ Spring 1, Year 1                             +500 prestige│
│ │ 🏰 FOUNDING                                             │ │
│ │ Eddard Stark founded House Stark.                      │ │
│ └─────────────────────────────────────────────────────────┘ │
│                                                             │
│                     [Load More Events]                      │
├─────────────────────────────────────────────────────────────┤
│ Statistics                                                  │
│ Total Prestige Gained: +2,450 | Lost: -350 | Net: +2,100   │
│ Births: 12 | Deaths: 5 | Marriages: 4 | Victories: 3       │
└─────────────────────────────────────────────────────────────┘
```

**Features:**
- [ ] Timeline view of dynasty events
- [ ] Filter by event type (birth, death, marriage, succession)
- [ ] Event cards with date, description, people involved
- [ ] Prestige changes per event
- [ ] Statistics summary
- [ ] Pagination for long histories

**Props needed:**
```php
return Inertia::render('Dynasty/History', [
    'events' => DynastyEvent::where('dynasty_id', $user->dynasty_id)
        ->with('member')
        ->latest('game_date')
        ->paginate(20),
    'event_types' => DynastyEvent::TYPES,
    'stats' => [
        'total_gained' => DynastyEvent::where('dynasty_id', $user->dynasty_id)
            ->where('prestige_change', '>', 0)->sum('prestige_change'),
        'total_lost' => abs(DynastyEvent::where('dynasty_id', $user->dynasty_id)
            ->where('prestige_change', '<', 0)->sum('prestige_change')),
        'births' => DynastyEvent::where('dynasty_id', $user->dynasty_id)
            ->where('type', 'birth')->count(),
        'deaths' => DynastyEvent::where('dynasty_id', $user->dynasty_id)
            ->where('type', 'death')->count(),
    ],
]);
```

---

### Task 7.7: Dynasty Alliances Page
**Route:** `GET /dynasty/alliances`
**File:** `resources/js/pages/Dynasty/Alliances.tsx`
**Controller:** `DynastyController@alliances`

**Wireframe:**
```
┌─────────────────────────────────────────────────────────────┐
│ Dynasty Alliances                        [Propose Alliance] │
├─────────────────────────────────────────────────────────────┤
│ Active Alliances (3)                                        │
│ ┌─────────────────────────────────────────────────────────┐ │
│ │ 💍 Marriage Alliance with House Tully                   │ │
│ │ Through: Eddard Stark & Catelyn Tully                  │ │
│ │ Since: Year 1 | Status: Strong                         │ │
│ │ Benefits: +20% trade, Mutual defense pact              │ │
│ │                               [View Details] [Break] ⚠️│ │
│ ├─────────────────────────────────────────────────────────┤ │
│ │ 🤝 Non-Aggression Pact with House Arryn                │ │
│ │ Signed: Year 2 | Expires: Year 7                       │ │
│ │ Terms: No hostile actions, shared border patrol        │ │
│ │                               [View Details] [Break] ⚠️│ │
│ ├─────────────────────────────────────────────────────────┤ │
│ │ 🩸 Blood Oath with House Reed                          │ │
│ │ Sworn: Year 1 | Status: Eternal                        │ │
│ │ Terms: Mutual defense, shared enemies                  │ │
│ │                               [View Details]            │ │
│ │ ⚠️ Blood oaths cannot be broken without severe penalty │ │
│ └─────────────────────────────────────────────────────────┘ │
├─────────────────────────────────────────────────────────────┤
│ Incoming Alliance Requests (1)                              │
│ ┌─────────────────────────────────────────────────────────┐ │
│ │ House Manderly proposes: Non-Aggression Pact           │ │
│ │ Duration: 5 years                                      │ │
│ │ Terms: No hostile actions                              │ │
│ │                              [Accept]  [Reject]        │ │
│ └─────────────────────────────────────────────────────────┘ │
├─────────────────────────────────────────────────────────────┤
│ ⚠️ Breaking alliances costs prestige and damages reputation│
│    Marriage alliances require divorce (additional cost)    │
└─────────────────────────────────────────────────────────────┘
```

**Features:**
- [ ] Active alliances list
- [ ] Alliance type (marriage, pact, blood oath)
- [ ] Allied dynasty info
- [ ] Terms and expiration date
- [ ] Break alliance button (prestige cost warning)
- [ ] Propose new alliance button
- [ ] Alliance request inbox
- [ ] Accept/reject requests

**Props needed:**
```php
return Inertia::render('Dynasty/Alliances', [
    'alliances' => DynastyAlliance::where('dynasty1_id', $user->dynasty_id)
        ->orWhere('dynasty2_id', $user->dynasty_id)
        ->with(['dynasty1', 'dynasty2'])
        ->get(),
    'requests' => DynastyAlliance::where('target_dynasty_id', $user->dynasty_id)
        ->where('status', 'pending')
        ->with('proposer')
        ->get(),
    'potential_allies' => Dynasty::whereNotIn('id',
        DynastyAlliance::where('dynasty1_id', $user->dynasty_id)
            ->orWhere('dynasty2_id', $user->dynasty_id)
            ->pluck('dynasty1_id', 'dynasty2_id')->flatten()
    )->where('id', '!=', $user->dynasty_id)->get(),
    'break_costs' => [
        'pact' => 100,
        'marriage' => 300,
        'blood_oath' => 1000,
    ],
]);
```

---

## Implementation Order

### Wave 1: Core Detail Pages (5 tasks)
1. Task 5.1: Army Detail - needed for warfare gameplay
2. Task 5.2: War Detail - needed to understand conflicts
3. Task 3.1: Caravan Detail - needed to manage trade
4. Task 6.1: Festival Detail - events are time-sensitive
5. Task 6.2: Tournament Bracket - events are time-sensitive

### Wave 2: Dynasty Foundation (3 tasks)
6. Task 7.1: Dynasty Overview - entry point for dynasty
7. Task 7.3: Marriage Proposals - core dynasty mechanic
8. Task 7.5: Succession Settings - important for inheritance

### Wave 3: Advanced Features (5 tasks)
9. Task 5.3: Battle Viewer - detailed combat info
10. Task 5.4: Declare War - initiate conflicts
11. Task 5.5: Peace Negotiation - end conflicts
12. Task 7.2: Family Tree - visualization
13. Task 7.4: Propose Marriage - create marriages

### Wave 4: Completion (4 tasks)
14. Task 3.2: Tariff Management - ruler feature
15. Task 6.3: Building Construction - settlement development
16. Task 7.6: Dynasty History - chronicle
17. Task 7.7: Dynasty Alliances - diplomacy

---

## Routes to Add

```php
// Trade
Route::get('trade/caravans/{caravan}', [CaravanController::class, 'show'])->name('trade.caravans.show');
Route::get('trade/tariffs', [TariffController::class, 'index'])->name('trade.tariffs');
Route::post('trade/tariffs', [TariffController::class, 'store'])->name('trade.tariffs.store');

// Warfare
Route::get('warfare/armies/{army}', [ArmyController::class, 'show'])->name('warfare.armies.show');
Route::post('warfare/armies/{army}/recruit', [ArmyController::class, 'recruit'])->name('warfare.armies.recruit');
Route::post('warfare/armies/{army}/move', [ArmyController::class, 'move'])->name('warfare.armies.move');
Route::get('warfare/wars/{war}', [WarController::class, 'show'])->name('warfare.wars.show');
Route::get('warfare/battles/{battle}', [BattleController::class, 'show'])->name('warfare.battles.show');
Route::get('warfare/declare', [WarController::class, 'declareForm'])->name('warfare.declare');
Route::post('warfare/declare', [WarController::class, 'declare'])->name('warfare.declare.store');
Route::get('warfare/wars/{war}/peace', [WarController::class, 'peaceForm'])->name('warfare.peace');
Route::post('warfare/wars/{war}/peace', [WarController::class, 'offerPeace'])->name('warfare.peace.store');
Route::post('warfare/wars/{war}/peace/{treaty}/respond', [WarController::class, 'respondToPeace'])->name('warfare.peace.respond');

// Events
Route::get('events/festivals/{festival}', [FestivalController::class, 'show'])->name('events.festivals.show');
Route::get('events/tournaments/{tournament}', [TournamentController::class, 'show'])->name('events.tournaments.show');
Route::post('events/tournaments/{tournament}/withdraw', [TournamentController::class, 'withdraw'])->name('events.tournaments.withdraw');
Route::get('buildings', [BuildingController::class, 'index'])->name('buildings.index');
Route::post('buildings', [BuildingController::class, 'startConstruction'])->name('buildings.store');
Route::post('buildings/{building}/repair', [BuildingController::class, 'repair'])->name('buildings.repair');

// Dynasty
Route::get('dynasty', [DynastyController::class, 'index'])->name('dynasty.index');
Route::post('dynasty', [DynastyController::class, 'found'])->name('dynasty.found');
Route::put('dynasty', [DynastyController::class, 'update'])->name('dynasty.update');
Route::get('dynasty/tree', [DynastyController::class, 'tree'])->name('dynasty.tree');
Route::get('dynasty/history', [DynastyController::class, 'history'])->name('dynasty.history');
Route::get('dynasty/alliances', [DynastyController::class, 'alliances'])->name('dynasty.alliances');
Route::post('dynasty/alliances/{alliance}/break', [DynastyController::class, 'breakAlliance'])->name('dynasty.alliances.break');
Route::get('dynasty/succession', [SuccessionController::class, 'index'])->name('dynasty.succession');
Route::put('dynasty/succession', [SuccessionController::class, 'update'])->name('dynasty.succession.update');
Route::post('dynasty/disinherit/{member}', [SuccessionController::class, 'disinherit'])->name('dynasty.disinherit');
Route::get('dynasty/proposals', [MarriageController::class, 'proposals'])->name('dynasty.proposals');
Route::get('dynasty/proposals/create', [MarriageController::class, 'proposeForm'])->name('dynasty.proposals.create');
Route::post('dynasty/proposals', [MarriageController::class, 'propose'])->name('dynasty.proposals.store');
Route::post('dynasty/proposals/{proposal}/accept', [MarriageController::class, 'accept'])->name('dynasty.proposals.accept');
Route::post('dynasty/proposals/{proposal}/reject', [MarriageController::class, 'reject'])->name('dynasty.proposals.reject');
Route::post('dynasty/proposals/{proposal}/withdraw', [MarriageController::class, 'withdraw'])->name('dynasty.proposals.withdraw');
```

---

## Controllers to Create

1. `TariffController` - tariff management
2. `FestivalController` - festival details
3. `TournamentController` - tournament brackets
4. `BuildingController` - construction
5. `DynastyController` - dynasty management
6. `SuccessionController` - succession rules
7. `MarriageController` - marriage proposals

Existing controllers to extend:
- `CaravanController` - add show()
- `ArmyController` - add show(), recruit(), move()
- `WarController` - add show(), declareForm(), declare(), peaceForm(), offerPeace()
- `BattleController` - add show()

---

## Total: 17 Tasks
- Phase 3 (Trade): 2 tasks
- Phase 5 (Warfare): 5 tasks
- Phase 6 (Events): 3 tasks
- Phase 7 (Dynasty): 7 tasks
