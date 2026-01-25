# Myrefell UI PRD - Frontend Implementation Guide

This document details the UI work needed for backend systems that are already implemented. All backend (migrations, models, services) is complete.

**Tech Stack:** React 19 + Inertia.js + Tailwind CSS + Lucide Icons

---

## UI Patterns Reference

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

### Design System
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

## Phase 3: Trade Caravans

### Status: Backend ✅ | UI ❌

### Models Available
- `TradeRoute` - Routes between settlements
- `Caravan` - Active trade expeditions
- `CaravanGoods` - Goods in transit
- `CaravanEvent` - Events during travel (bandits, weather)
- `TradeTariff` - Tariffs set by authorities
- `TariffCollection` - Revenue tracking

### Services Available
- `CaravanService` - createCaravan, loadGoods, dispatchCaravan, processTravel, processEvent

### Pages Needed

#### 1. Trade Routes Index (`/trade/routes`)
**File:** `resources/js/pages/Trade/Routes.tsx`
**Controller:** `TradeRouteController@index`

```
┌─────────────────────────────────────────────────────────┐
│ Trade Routes                              [Create Route] │
├─────────────────────────────────────────────────────────┤
│ ┌─────────────────────────────────────────────────────┐ │
│ │ Oakvale → Ironforge                                 │ │
│ │ Distance: 3 days | Danger: Low | Tariff: 5%        │ │
│ │ Active Caravans: 2                                  │ │
│ └─────────────────────────────────────────────────────┘ │
│ ┌─────────────────────────────────────────────────────┐ │
│ │ Ironforge → King's Landing                          │ │
│ │ Distance: 5 days | Danger: Medium | Tariff: 10%    │ │
│ │ Active Caravans: 0                                  │ │
│ └─────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────┘
```

**Props from controller:**
```php
return Inertia::render('Trade/Routes', [
    'routes' => TradeRoute::with(['originSettlement', 'destinationSettlement'])
        ->withCount('activeCaravans')
        ->get(),
    'can_create' => // ruler check
]);
```

#### 2. Caravan Management (`/trade/caravans`)
**File:** `resources/js/pages/Trade/Caravans.tsx`
**Controller:** `CaravanController@index`

```
┌─────────────────────────────────────────────────────────┐
│ My Caravans                                [New Caravan] │
├─────────────────────────────────────────────────────────┤
│ Active Caravans                                         │
│ ┌─────────────────────────────────────────────────────┐ │
│ │ 🚚 Caravan #12                        [Track] [Cancel] │
│ │ Route: Oakvale → Ironforge                          │ │
│ │ Status: In Transit (Day 2/3)                        │ │
│ │ Goods: 50 Iron Ore, 20 Wheat           Value: 500g │ │
│ │ ████████████░░░░░░░░ 67%                           │ │
│ └─────────────────────────────────────────────────────┘ │
│                                                         │
│ Completed Caravans                                      │
│ ┌─────────────────────────────────────────────────────┐ │
│ │ Caravan #11 - Arrived | Profit: +150g | 2 days ago │ │
│ └─────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────┘
```

**Props:**
```php
return Inertia::render('Trade/Caravans', [
    'active_caravans' => Caravan::where('owner_id', $user->id)
        ->whereIn('status', ['loading', 'in_transit'])
        ->with(['route', 'goods.item'])
        ->get(),
    'completed_caravans' => Caravan::where('owner_id', $user->id)
        ->where('status', 'arrived')
        ->latest()
        ->limit(10)
        ->get(),
    'available_routes' => TradeRoute::active()->get(),
]);
```

#### 3. Caravan Detail/Loading (`/trade/caravans/{id}`)
**File:** `resources/js/pages/Trade/CaravanShow.tsx`

- Load goods from inventory
- View current goods
- Dispatch button (if loading)
- Event log (if in transit)
- Unload goods (if arrived)

#### 4. Tariff Management (`/trade/tariffs`) - Rulers Only
**File:** `resources/js/pages/Trade/Tariffs.tsx`

- Set tariff rates for routes passing through territory
- View tariff revenue collected

### Routes Needed
```php
Route::prefix('trade')->group(function () {
    Route::get('routes', [TradeRouteController::class, 'index'])->name('trade.routes');
    Route::post('routes', [TradeRouteController::class, 'store'])->name('trade.routes.store');

    Route::get('caravans', [CaravanController::class, 'index'])->name('trade.caravans');
    Route::get('caravans/{caravan}', [CaravanController::class, 'show'])->name('trade.caravans.show');
    Route::post('caravans', [CaravanController::class, 'store'])->name('trade.caravans.store');
    Route::post('caravans/{caravan}/load', [CaravanController::class, 'loadGoods'])->name('trade.caravans.load');
    Route::post('caravans/{caravan}/dispatch', [CaravanController::class, 'dispatch'])->name('trade.caravans.dispatch');
    Route::post('caravans/{caravan}/unload', [CaravanController::class, 'unload'])->name('trade.caravans.unload');

    Route::get('tariffs', [TariffController::class, 'index'])->name('trade.tariffs');
    Route::post('tariffs', [TariffController::class, 'store'])->name('trade.tariffs.store');
});
```

---

## Phase 4: Political Systems

### Status: Backend ✅ | UI ⚠️ Partial

### Existing UI ✅
- `SocialClass/Index.tsx` - Class display, rights, manumission/ennoblement forms
- `SocialClass/ManumissionRequests.tsx` - Admin view for baron
- `SocialClass/EnnoblementRequests.tsx` - Admin view for king
- `Crime/Index.tsx` - Criminal record, jail status, bounties on player
- `Crime/BountyBoard.tsx` - Public bounty board

### UI Still Needed

#### 1. Crime Accusation Interface (`/crime/accuse`)
**File:** `resources/js/pages/Crime/Accuse.tsx`

```
┌─────────────────────────────────────────────────────────┐
│ File an Accusation                                      │
├─────────────────────────────────────────────────────────┤
│ Accused Player: [Search/Select Player____________]      │
│                                                         │
│ Crime Type: [Dropdown: Theft, Assault, Murder, etc.]   │
│                                                         │
│ Description:                                            │
│ ┌─────────────────────────────────────────────────────┐ │
│ │ Describe what happened...                           │ │
│ └─────────────────────────────────────────────────────┘ │
│                                                         │
│ Evidence (Optional):                                    │
│ ┌─────────────────────────────────────────────────────┐ │
│ │ Any proof you have...                               │ │
│ └─────────────────────────────────────────────────────┘ │
│                                                         │
│ ⚠️ False accusations are themselves a crime!           │
│                                                         │
│                              [Cancel] [File Accusation] │
└─────────────────────────────────────────────────────────┘
```

#### 2. Trial Viewer (`/crime/trials/{id}`)
**File:** `resources/js/pages/Crime/TrialShow.tsx`

```
┌─────────────────────────────────────────────────────────┐
│ Trial #42 - Murder of John Smith         Status: Active │
├─────────────────────────────────────────────────────────┤
│ Court: Baron's Court of Ironforge                       │
│ Judge: Baron Wilhelm                                    │
│ Scheduled: Spring 15, Year 3                           │
│                                                         │
│ ┌───────────────────┐  ┌───────────────────┐           │
│ │ ACCUSED           │  │ ACCUSER           │           │
│ │ PlayerName        │  │ VictimName        │           │
│ │ Plea: Not Guilty  │  │                   │           │
│ └───────────────────┘  └───────────────────┘           │
│                                                         │
│ Evidence Presented:                                     │
│ • Witness testimony from Guard#1                       │
│ • Bloody knife found in accused's inventory            │
│                                                         │
│ Defense Statement:                                      │
│ ┌─────────────────────────────────────────────────────┐ │
│ │ [Enter your defense if you're the accused...]       │ │
│ └─────────────────────────────────────────────────────┘ │
│                                        [Submit Defense] │
│                                                         │
│ [If judge] Verdict: [Not Guilty] [Guilty] [Dismissed]  │
└─────────────────────────────────────────────────────────┘
```

#### 3. Court Docket (`/crime/court`)
**File:** `resources/js/pages/Crime/Court.tsx`

- List of pending trials in your jurisdiction
- For judges: ability to schedule trials, render verdicts
- For citizens: view upcoming trials

#### 4. Legitimacy Display (Settlement Integration)

**Add to existing settlement pages** (`villages/show.tsx`, `baronies/show.tsx`, `kingdoms/show.tsx`):

```tsx
// In ruler info section
{ruler && (
    <div className="flex items-center gap-2">
        <Crown className="h-4 w-4" />
        <span>{ruler.name}</span>
        <LegitimacyBadge legitimacy={ruler.legitimacy} />
    </div>
)}

// LegitimacyBadge component
function LegitimacyBadge({ legitimacy }: { legitimacy: number }) {
    const color = legitimacy >= 70 ? 'green' : legitimacy >= 40 ? 'yellow' : 'red';
    return (
        <span className={`rounded px-1.5 py-0.5 text-[10px] bg-${color}-900/50 text-${color}-300`}>
            {legitimacy}% Legitimacy
        </span>
    );
}
```

### Routes Needed
```php
Route::prefix('crime')->group(function () {
    // Existing routes...
    Route::get('accuse', [CrimeController::class, 'accuseForm'])->name('crime.accuse');
    Route::post('accuse', [CrimeController::class, 'fileAccusation'])->name('crime.accuse.store');

    Route::get('court', [CourtController::class, 'index'])->name('crime.court');
    Route::get('trials/{trial}', [TrialController::class, 'show'])->name('crime.trials.show');
    Route::post('trials/{trial}/defense', [TrialController::class, 'submitDefense'])->name('crime.trials.defense');
    Route::post('trials/{trial}/verdict', [TrialController::class, 'renderVerdict'])->name('crime.trials.verdict');
});
```

---

## Phase 5: Warfare

### Status: Backend ✅ | UI ❌

### Models Available
- `Army`, `ArmyUnit` - Military forces
- `War`, `WarParticipant`, `WarGoal` - Conflicts
- `Battle`, `BattleParticipant` - Engagements
- `Siege` - Prolonged attacks
- `SupplyLine` - Logistics
- `PeaceTreaty` - War endings
- `MercenaryCompany` - Hired armies

### Pages Needed

#### 1. Army Management (`/warfare/armies`)
**File:** `resources/js/pages/Warfare/Armies.tsx`

```
┌─────────────────────────────────────────────────────────┐
│ Military Forces                           [Raise Army]  │
├─────────────────────────────────────────────────────────┤
│ Your Armies                                             │
│ ┌─────────────────────────────────────────────────────┐ │
│ │ ⚔️ Northern Host                    [Manage] [Move] │ │
│ │ Commander: You | Location: Oakvale                  │ │
│ │ Status: Encamped | Morale: 85% | Supplies: 12 days │ │
│ │ ┌─────────────────────────────────────────────────┐ │ │
│ │ │ Levy: 200    │ Men-at-Arms: 50   │ Knights: 10 │ │ │
│ │ │ Archers: 30  │ Cavalry: 20       │             │ │ │
│ │ └─────────────────────────────────────────────────┘ │ │
│ │ Total: 310 soldiers | Attack: 850 | Defense: 720   │ │
│ └─────────────────────────────────────────────────────┘ │
│                                                         │
│ Mercenary Companies Available                           │
│ ┌─────────────────────────────────────────────────────┐ │
│ │ 🏴 Iron Company            Reputation: Good  [Hire] │ │
│ │ Specialization: Infantry | 150 soldiers             │ │
│ │ Cost: 1000g hire + 100g/day                        │ │
│ └─────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────┘
```

#### 2. Army Detail (`/warfare/armies/{id}`)
**File:** `resources/js/pages/Warfare/ArmyShow.tsx`

- Unit composition with recruit buttons
- Supply line management
- Movement orders
- Disband option

#### 3. Wars Overview (`/warfare/wars`)
**File:** `resources/js/pages/Warfare/Wars.tsx`

```
┌─────────────────────────────────────────────────────────┐
│ Active Wars                                             │
├─────────────────────────────────────────────────────────┤
│ ┌─────────────────────────────────────────────────────┐ │
│ │ War for the Northern Reaches              [Details] │ │
│ │ Northland vs Southron Kingdom                       │ │
│ │ Casus Belli: Conquest                               │ │
│ │ ┌─────────────────────────────────────────────────┐ │ │
│ │ │ ATTACKERS        vs        DEFENDERS            │ │ │
│ │ │ War Score: 45              War Score: 30        │ │ │
│ │ │ ███████████░░░░░░░░░░░░░░░░░░░░░░░░███████     │ │ │
│ │ └─────────────────────────────────────────────────┘ │ │
│ │ Duration: 45 days | Battles: 3 | Active Sieges: 1  │ │
│ └─────────────────────────────────────────────────────┘ │
│                                                         │
│ Your Participation: Defender (Ally)                     │
│ Contribution Score: 150                                 │
└─────────────────────────────────────────────────────────┘
```

#### 4. War Detail (`/warfare/wars/{id}`)
**File:** `resources/js/pages/Warfare/WarShow.tsx`

- War goals and progress
- Participant list
- Battle history
- Active sieges
- Peace offer button (for war leaders)

#### 5. Battle Viewer (`/warfare/battles/{id}`)
**File:** `resources/js/pages/Warfare/BattleShow.tsx`

```
┌─────────────────────────────────────────────────────────┐
│ Battle of Ironforge               Day 3 | Status: Ongoing│
├─────────────────────────────────────────────────────────┤
│ ┌─────────────────────┐    ┌─────────────────────┐     │
│ │ ATTACKERS           │ VS │ DEFENDERS           │     │
│ │ Initial: 500        │    │ Initial: 400        │     │
│ │ Remaining: 380      │    │ Remaining: 320      │     │
│ │ Casualties: 120     │    │ Casualties: 80      │     │
│ │ Morale: 65%         │    │ Morale: 72%         │     │
│ └─────────────────────┘    └─────────────────────┘     │
│                                                         │
│ Battle Log                                              │
│ ┌─────────────────────────────────────────────────────┐ │
│ │ Day 3: Heavy fighting continues. Attackers lost 45, │ │
│ │        Defenders lost 30.                           │ │
│ │ Day 2: Attackers launched assault. 50 vs 35 lost.  │ │
│ │ Day 1: Armies engaged near Ironforge.              │ │
│ └─────────────────────────────────────────────────────┘ │
│                                                         │
│ Terrain: Fortified (Defender +50% bonus)               │
└─────────────────────────────────────────────────────────┘
```

#### 6. Siege Interface (`/warfare/sieges/{id}`)
**File:** `resources/js/pages/Warfare/SiegeShow.tsx`

```
┌─────────────────────────────────────────────────────────┐
│ Siege of Castle Ironhold            Day 15 | Active     │
├─────────────────────────────────────────────────────────┤
│ Fortification: ████████████████░░░░░░░░░░░░░ 65%       │
│ Garrison Supplies: ██████████░░░░░░░░░░░░░░░ 40%       │
│ Garrison Morale: █████████████████░░░░░░░░░░ 70%       │
│                                                         │
│ Siege Equipment                                         │
│ • Battering Ram x2                                      │
│ • Trebuchet x1           [Build More Equipment]        │
│ • Siege Tower x1                                        │
│                                                         │
│ [🗡️ Launch Assault] (Difficulty: 65%)                   │
│ [⏳ Continue Siege]                                      │
│ [🏳️ Lift Siege]                                         │
│                                                         │
│ Siege Log                                               │
│ Day 15: Trebuchet damaged walls. Fortification -5%     │
│ Day 10: Defenders ran low on supplies.                 │
└─────────────────────────────────────────────────────────┘
```

#### 7. Declare War (`/warfare/declare`)
**File:** `resources/js/pages/Warfare/DeclareWar.tsx`

- Select target (kingdom/barony)
- Choose casus belli
- Set war goals
- Review legitimacy impact

#### 8. Peace Negotiation (`/warfare/wars/{id}/peace`)
**File:** `resources/js/pages/Warfare/PeaceNegotiation.tsx`

- Territory changes
- Gold payment
- Truce duration
- Send/accept/reject peace offers

### Routes Needed
```php
Route::prefix('warfare')->group(function () {
    // Armies
    Route::get('armies', [ArmyController::class, 'index'])->name('warfare.armies');
    Route::get('armies/{army}', [ArmyController::class, 'show'])->name('warfare.armies.show');
    Route::post('armies', [ArmyController::class, 'store'])->name('warfare.armies.store');
    Route::post('armies/{army}/recruit', [ArmyController::class, 'recruit'])->name('warfare.armies.recruit');
    Route::post('armies/{army}/move', [ArmyController::class, 'move'])->name('warfare.armies.move');
    Route::post('armies/{army}/disband', [ArmyController::class, 'disband'])->name('warfare.armies.disband');

    // Wars
    Route::get('wars', [WarController::class, 'index'])->name('warfare.wars');
    Route::get('wars/{war}', [WarController::class, 'show'])->name('warfare.wars.show');
    Route::get('declare', [WarController::class, 'declareForm'])->name('warfare.declare');
    Route::post('declare', [WarController::class, 'declare'])->name('warfare.declare.store');
    Route::get('wars/{war}/peace', [WarController::class, 'peaceForm'])->name('warfare.peace');
    Route::post('wars/{war}/peace', [WarController::class, 'offerPeace'])->name('warfare.peace.store');

    // Battles
    Route::get('battles/{battle}', [BattleController::class, 'show'])->name('warfare.battles.show');

    // Sieges
    Route::get('sieges/{siege}', [SiegeController::class, 'show'])->name('warfare.sieges.show');
    Route::post('sieges/{siege}/assault', [SiegeController::class, 'assault'])->name('warfare.sieges.assault');
    Route::post('sieges/{siege}/lift', [SiegeController::class, 'lift'])->name('warfare.sieges.lift');

    // Mercenaries
    Route::get('mercenaries', [MercenaryController::class, 'index'])->name('warfare.mercenaries');
    Route::post('mercenaries/{company}/hire', [MercenaryController::class, 'hire'])->name('warfare.mercenaries.hire');
});
```

---

## Phase 6: World Events

### Status: Backend ✅ | UI ❌

### Models Available
- `FestivalType`, `Festival`, `FestivalParticipant`
- `TournamentType`, `Tournament`, `TournamentCompetitor`, `TournamentMatch`
- `RoyalEvent`
- `DiseaseType`, `DiseaseOutbreak`, `DiseaseInfection`, `DiseaseImmunity`, `QuarantineOrder`
- `DisasterType`, `Disaster`, `BuildingDamage`
- `BuildingType`, `Building`, `ConstructionProject`

### Pages Needed

#### 1. Events Calendar (`/events`)
**File:** `resources/js/pages/Events/Index.tsx`

```
┌─────────────────────────────────────────────────────────┐
│ Events Calendar                        Spring, Year 3   │
├─────────────────────────────────────────────────────────┤
│ Upcoming Events                                         │
│ ┌─────────────────────────────────────────────────────┐ │
│ │ 🎪 Spring Festival - Oakvale         [Participate] │ │
│ │ Starts: Spring 20 | Duration: 3 days               │ │
│ │ Activities: Dance, Feast, Games                    │ │
│ └─────────────────────────────────────────────────────┘ │
│ ┌─────────────────────────────────────────────────────┐ │
│ │ ⚔️ Grand Tournament - King's Landing   [Register]  │ │
│ │ Starts: Summer 1 | Type: Melee Combat              │ │
│ │ Prize: 500g + Champion Title | Entry: 50g          │ │
│ │ Registered: 12 competitors                         │ │
│ └─────────────────────────────────────────────────────┘ │
│ ┌─────────────────────────────────────────────────────┐ │
│ │ 👑 Royal Wedding - King's Landing                  │ │
│ │ Date: Summer 5 | King Arthur & Lady Guinevere     │ │
│ └─────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────┘
```

#### 2. Festival Detail (`/events/festivals/{id}`)
**File:** `resources/js/pages/Events/FestivalShow.tsx`

- Festival activities
- Participation rewards
- Current participants

#### 3. Tournament Bracket (`/events/tournaments/{id}`)
**File:** `resources/js/pages/Events/TournamentShow.tsx`

```
┌─────────────────────────────────────────────────────────┐
│ Grand Melee Tournament               Status: Round 2    │
├─────────────────────────────────────────────────────────┤
│ Bracket                                                 │
│        Round 1           Round 2          Finals        │
│   ┌─────────────┐                                       │
│   │ Player A ✓  │─┐                                     │
│   │ Player B    │ ├──┌─────────────┐                   │
│   └─────────────┘   │ Player A ✓  │─┐                  │
│   ┌─────────────┐   │ Player C    │ │                  │
│   │ Player C ✓  │─┘ └─────────────┘ │  ┌─────────────┐ │
│   │ Player D    │                   ├──│ ?????????? │ │
│   └─────────────┘                   │  └─────────────┘ │
│   ┌─────────────┐   ┌─────────────┐ │                  │
│   │ Player E    │─┐ │ Player F    │─┘                  │
│   │ Player F ✓  │ ├─│ (awaiting)  │                    │
│   └─────────────┘   └─────────────┘                    │
│   ┌─────────────┐                                       │
│   │ Player G ✓  │─┘                                     │
│   │ Player H    │                                       │
│   └─────────────┘                                       │
│                                                         │
│ Your Status: Competing | Next Match: vs Player F       │
└─────────────────────────────────────────────────────────┘
```

#### 4. Health Status (Add to Player Profile/Dashboard)
**File:** Modify `resources/js/pages/dashboard.tsx`

```tsx
// Health widget
{player.disease_status && (
    <div className="rounded-lg border border-red-500/50 bg-red-900/20 p-3">
        <div className="flex items-center gap-2">
            <Thermometer className="h-4 w-4 text-red-400" />
            <span className="font-pixel text-sm text-red-300">
                Infected: {player.disease_status.name}
            </span>
        </div>
        <p className="text-xs text-stone-400">
            Severity: {player.disease_status.severity}% |
            Day {player.disease_status.day_infected}
        </p>
        <Link href="/healer" className="text-xs text-amber-300">
            Visit Healer →
        </Link>
    </div>
)}
```

#### 5. Settlement Disasters (Add to Settlement Pages)
**File:** Modify `resources/js/pages/villages/show.tsx` etc.

```tsx
// Active disasters section
{active_disasters.length > 0 && (
    <div className="rounded-lg border border-red-500/50 bg-red-900/20 p-3">
        <h3 className="font-pixel text-amber-300">⚠️ Active Disasters</h3>
        {active_disasters.map(disaster => (
            <div key={disaster.id}>
                <span className="text-red-300">{disaster.type.name}</span>
                <span className="text-xs text-stone-400">
                    Severity: {disaster.severity}% |
                    Day {disaster.days_active}/{disaster.type.duration_days}
                </span>
            </div>
        ))}
    </div>
)}
```

#### 6. Construction Interface (`/buildings`)
**File:** `resources/js/pages/Buildings/Index.tsx`

```
┌─────────────────────────────────────────────────────────┐
│ Buildings - Oakvale                    [Start Project]  │
├─────────────────────────────────────────────────────────┤
│ Existing Buildings                                      │
│ ┌─────────────────────────────────────────────────────┐ │
│ │ 🏠 Cottage x5           Condition: 85%   [Repair]  │ │
│ │ 🏭 Smithy x1            Condition: 100%            │ │
│ │ 🏪 Market x1            Condition: 70%   [Repair]  │ │
│ │ 🏰 Palisade Wall        Condition: 50%   [Repair]  │ │
│ └─────────────────────────────────────────────────────┘ │
│                                                         │
│ Under Construction                                      │
│ ┌─────────────────────────────────────────────────────┐ │
│ │ 🏗️ Granary              Progress: 60%              │ │
│ │ ████████████████░░░░░░░░░░░░░░                     │ │
│ │ Workers: 5 | Days remaining: ~4                    │ │
│ └─────────────────────────────────────────────────────┘ │
│                                                         │
│ Available to Build                                      │
│ ┌─────────────────────────────────────────────────────┐ │
│ │ Chapel          Stone: 40, Wood: 30    14 days    │ │
│ │ Well            Stone: 30              5 days     │ │
│ │ Stone Wall      Stone: 200, Iron: 20   30 days    │ │
│ └─────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────┘
```

### Routes Needed
```php
Route::prefix('events')->group(function () {
    Route::get('/', [EventController::class, 'index'])->name('events.index');
    Route::get('festivals/{festival}', [FestivalController::class, 'show'])->name('events.festivals.show');
    Route::post('festivals/{festival}/participate', [FestivalController::class, 'participate'])->name('events.festivals.participate');
    Route::get('tournaments/{tournament}', [TournamentController::class, 'show'])->name('events.tournaments.show');
    Route::post('tournaments/{tournament}/register', [TournamentController::class, 'register'])->name('events.tournaments.register');
});

Route::prefix('buildings')->group(function () {
    Route::get('/', [BuildingController::class, 'index'])->name('buildings.index');
    Route::post('/', [BuildingController::class, 'startConstruction'])->name('buildings.store');
    Route::post('{building}/repair', [BuildingController::class, 'repair'])->name('buildings.repair');
    Route::get('projects/{project}', [BuildingController::class, 'projectShow'])->name('buildings.projects.show');
});
```

---

## Phase 7: Marriage and Dynasties

### Status: Backend ✅ | UI ❌

### Models Available
- `Dynasty`, `DynastyMember`, `DynastyEvent`, `DynastyAlliance`
- `Marriage`, `MarriageProposal`, `Birth`
- `SuccessionRule`, `InheritanceClaim`

### Pages Needed

#### 1. Dynasty Overview (`/dynasty`)
**File:** `resources/js/pages/Dynasty/Index.tsx`

```
┌─────────────────────────────────────────────────────────┐
│ House Stark                              [Edit Dynasty] │
│ "Winter is Coming"                                      │
├─────────────────────────────────────────────────────────┤
│ ┌─────────────┐                                         │
│ │  🏠 CREST   │  Prestige: 1,250 | Members: 12         │
│ │             │  Generations: 4  | Founded: Year 1      │
│ └─────────────┘  Head: Lord Eddard Stark               │
│                  Heir: Robb Stark                       │
│                                                         │
│ [👪 Family Tree] [📜 History] [🤝 Alliances] [⚖️ Claims] │
├─────────────────────────────────────────────────────────┤
│ Living Members                                          │
│ ┌─────────────────────────────────────────────────────┐ │
│ │ 👤 Eddard Stark (You)    Head | Age 42 | Married   │ │
│ │ 👤 Catelyn Stark         Spouse | Age 38           │ │
│ │ 👤 Robb Stark           Heir | Age 17 | Single    │ │
│ │ 👤 Sansa Stark          Daughter | Age 14         │ │
│ │ 👤 Arya Stark           Daughter | Age 11         │ │
│ │ 👤 Bran Stark           Son | Age 10              │ │
│ │ 👤 Rickon Stark         Son | Age 6               │ │
│ └─────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────┘
```

#### 2. Family Tree (`/dynasty/tree`)
**File:** `resources/js/pages/Dynasty/Tree.tsx`

Visual family tree showing:
- Parents, grandparents
- Siblings
- Children, grandchildren
- Marriages connecting families

#### 3. Marriage Proposals (`/dynasty/proposals`)
**File:** `resources/js/pages/Dynasty/Proposals.tsx`

```
┌─────────────────────────────────────────────────────────┐
│ Marriage Proposals                    [Make Proposal]   │
├─────────────────────────────────────────────────────────┤
│ Incoming Proposals                                      │
│ ┌─────────────────────────────────────────────────────┐ │
│ │ Lord Bolton proposes marriage:                      │ │
│ │ Ramsay Bolton → Sansa Stark                        │ │
│ │ Dowry offered: 500g                                │ │
│ │ Expires: 5 days                    [Accept] [Reject] │
│ └─────────────────────────────────────────────────────┘ │
│                                                         │
│ Outgoing Proposals                                      │
│ ┌─────────────────────────────────────────────────────┐ │
│ │ You proposed:                        Status: Pending │
│ │ Robb Stark → Margaery Tyrell                       │ │
│ │ Dowry offered: 1000g              [Withdraw]       │ │
│ └─────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────┘
```

#### 4. Make Proposal (`/dynasty/proposals/create`)
**File:** `resources/js/pages/Dynasty/ProposeMarriage.tsx`

- Select dynasty member to marry off
- Search for eligible candidates
- Set dowry amount
- Add message
- Review alliance implications

#### 5. Succession Settings (`/dynasty/succession`)
**File:** `resources/js/pages/Dynasty/Succession.tsx`

```
┌─────────────────────────────────────────────────────────┐
│ Succession Rules                                        │
├─────────────────────────────────────────────────────────┤
│ Current Rules                                           │
│ ┌─────────────────────────────────────────────────────┐ │
│ │ Type: Primogeniture (eldest child inherits)         │ │
│ │ Gender Law: Agnatic-Cognatic (males first)         │ │
│ │ Bastards: Not allowed                              │ │
│ │ Minimum Age: 16                                    │ │
│ └─────────────────────────────────────────────────────┘ │
│                                                         │
│ Current Line of Succession                              │
│ 1. Robb Stark (son, age 17)                            │
│ 2. Bran Stark (son, age 10)                            │
│ 3. Rickon Stark (son, age 6)                           │
│ 4. Sansa Stark (daughter, age 14)                      │
│ 5. Arya Stark (daughter, age 11)                       │
│                                                         │
│ [Change Succession Rules] (costs prestige)             │
│ [Disinherit Member] (costs prestige)                   │
└─────────────────────────────────────────────────────────┘
```

#### 6. Dynasty History (`/dynasty/history`)
**File:** `resources/js/pages/Dynasty/History.tsx`

Chronicle of dynasty events:
- Births, deaths
- Marriages
- Successions
- Achievements and scandals
- Alliance formations

#### 7. Dynasty Alliances (`/dynasty/alliances`)
**File:** `resources/js/pages/Dynasty/Alliances.tsx`

- Active alliances with other dynasties
- Alliance type (marriage, pact, blood oath)
- Terms and expiration
- Option to break alliance (prestige cost)

### Routes Needed
```php
Route::prefix('dynasty')->group(function () {
    Route::get('/', [DynastyController::class, 'index'])->name('dynasty.index');
    Route::post('/', [DynastyController::class, 'found'])->name('dynasty.found');
    Route::put('/', [DynastyController::class, 'update'])->name('dynasty.update');

    Route::get('tree', [DynastyController::class, 'tree'])->name('dynasty.tree');
    Route::get('history', [DynastyController::class, 'history'])->name('dynasty.history');
    Route::get('alliances', [DynastyController::class, 'alliances'])->name('dynasty.alliances');

    Route::get('succession', [SuccessionController::class, 'index'])->name('dynasty.succession');
    Route::put('succession', [SuccessionController::class, 'update'])->name('dynasty.succession.update');
    Route::post('disinherit/{member}', [SuccessionController::class, 'disinherit'])->name('dynasty.disinherit');

    Route::get('proposals', [MarriageController::class, 'proposals'])->name('dynasty.proposals');
    Route::get('proposals/create', [MarriageController::class, 'proposeForm'])->name('dynasty.proposals.create');
    Route::post('proposals', [MarriageController::class, 'propose'])->name('dynasty.proposals.store');
    Route::post('proposals/{proposal}/accept', [MarriageController::class, 'accept'])->name('dynasty.proposals.accept');
    Route::post('proposals/{proposal}/reject', [MarriageController::class, 'reject'])->name('dynasty.proposals.reject');
    Route::post('proposals/{proposal}/withdraw', [MarriageController::class, 'withdraw'])->name('dynasty.proposals.withdraw');
});
```

---

## Implementation Priority

### Wave 1: Essential UI (Phase 3 + 4 gaps)
1. Trade Routes/Caravans - Economic gameplay
2. Crime Accusation/Trial - Complete judicial system
3. Legitimacy display - Already have backend

### Wave 2: Warfare (Phase 5)
1. Army Management - Core warfare
2. Wars/Battles - Conflict resolution
3. Sieges - Extended warfare
4. Mercenaries - Economic warfare

### Wave 3: World Events (Phase 6)
1. Events Calendar - Festivals/Tournaments
2. Health/Disease widgets - Player status
3. Building Construction - Settlement development
4. Disaster notifications - World dynamics

### Wave 4: Dynasty (Phase 7)
1. Dynasty Overview - Family management
2. Marriage Proposals - Social mechanics
3. Family Tree - Visualization
4. Succession - Inheritance rules

---

## Testing Checklist

For each new page:
- [ ] Page renders without errors
- [ ] Breadcrumbs correct
- [ ] Mobile responsive
- [ ] Forms validate input
- [ ] Loading states for actions
- [ ] Error handling for failed requests
- [ ] Empty states when no data
- [ ] Links to related pages work
