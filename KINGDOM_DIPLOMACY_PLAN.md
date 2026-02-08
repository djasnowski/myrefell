# Kingdom Diplomacy & Relations System

## What Already Exists

### War & Military (fully built)
- War declaration with 6 casus belli types (claim, conquest, rebellion, holy_war, defense, raid)
- War participants (allies, vassals), war goals, war scoring
- Battle and siege systems
- Peace treaties with truces (white peace, surrender, negotiated)
- Army system with morale, supplies, composition

### Economy & Trade (fully built)
- Kingdom-level tariffs via `TradeTariff`
- Cross-kingdom caravans with goods, routes, danger levels
- Tariff collection on caravan crossings
- Kingdom treasuries with transaction logging

### Government (fully built)
- Kingdom roles: king, chancellor, general, royal_treasurer, archbishop, spymaster, royal_herald, master_of_laws, lord_marshal, royal_steward
- Role-based salary system
- Elections and no-confidence votes

### Other Existing Systems
- `KingdomReligion` - religions can be state, tolerated, or banned per kingdom
- `MigrationRequest` - cross-kingdom migration with king approval
- `DynastyAlliance` - dynasty-level alliances (marriage, pact, blood_oath) - template for kingdom-level
- `PeaceTreaty` - includes truce mechanics preventing re-declaration of war

### What's Missing
- No kingdom-to-kingdom relations/reputation score
- No formal alliances between kingdoms (only dynasty-level)
- No trade agreements, NAPs, or embargoes
- No diplomatic actions (send gift, denounce, demand tribute)
- No consequences for unjustified war
- No casus belli generation from diplomatic events
- No coalition mechanics to prevent one kingdom from dominating

---

## Relations System

### Bilateral Relations Score

Each kingdom pair has a **bilateral** relations score from **-100 to +100**. Kingdom A's opinion of B can differ from B's opinion of A.

With 4 kingdoms, there are 6 unique pairs = 12 directional scores.

| Score Range | Status | Color | Effect |
|---|---|---|---|
| -100 to -50 | Hostile | Red | Cannot propose any treaties, embargo auto-considered |
| -50 to -20 | Unfriendly | Orange | Limited diplomacy, high tariffs |
| -20 to +20 | Neutral | Yellow | Standard interactions |
| +20 to +50 | Friendly | Light Green | Can propose treaties, trade bonuses |
| +50 to +100 | Allied | Green | Full diplomacy available, best trade rates |

### Relation Modifiers (Stack & Decay)

Each event creates a named modifier with a value and decay timer. Active modifiers are summed to produce the total relations score.

#### Positive Modifiers

| Event | Modifier | Decay |
|---|---|---|
| Signed NAP | +10 | Persists while active |
| Signed trade agreement | +15 | Persists while active |
| Royal marriage | +15 | Persists while active |
| Alliance formed | +20 | Persists while active |
| Maintained treaty (per game-week) | +1 (cap +10) | Resets if treaty ends |
| Sent gift of gold | +5 to +20 (scaled by amount) | 4 weeks |
| Honored alliance call to war | +20 | 8 weeks |
| Returned captured territory | +20 | 8 weeks |
| Mutual enemy (at war with same kingdom) | +10 | Persists while active |
| Shared state religion | +5 | Persists while active |

#### Negative Modifiers

| Event | Modifier | Decay |
|---|---|---|
| Broke a NAP | -40 | 12 weeks |
| Broke a trade agreement | -15 | 6 weeks |
| Broke a royal marriage (via war) | -30 | 10 weeks |
| Refused alliance call | -25 | 6 weeks |
| Declared war (with CB) | -15 | 6 weeks |
| Declared war (without CB) | -25 (target), -15 (all others) | 8 weeks |
| Broke a truce | -50 (target), -25 (all others) | 16 weeks |
| Trade embargo active | -20 | Persists while active |
| Raided territory | -10 per incident | 4 weeks |
| Denounced/insulted | -15 | 4 weeks |
| Spy caught in your kingdom | -20 | 6 weeks |
| Banned our state religion | -15 | Persists while active |
| Conquered territory from us | -20 per territory | 12 weeks |

### Reputation Tags

Certain actions give kingdoms visible **reputation tags** that affect all diplomatic interactions:

| Tag | Trigger | Duration | Effect |
|---|---|---|---|
| **Oathbreaker** | Broke a NAP or alliance | 8 weeks | All kingdoms require +15 higher relations to sign treaties |
| **Truce Breaker** | Broke a post-war truce | 16 weeks | All kingdoms get free CB, +20 relations required for treaties |
| **Unjust Aggressor** | Declared war without CB | 8 weeks | All kingdoms get -15 opinion, coalition eligible |
| **Reliable Ally** | Honored 3+ alliance calls | Permanent until broken | -5 relations requirement for treaties |
| **Generous** | Sent 3+ gifts in 8 weeks | 8 weeks | +5 bonus to all relations |

---

## Diplomatic Actions

### Tier 1: Basic Diplomacy (always available)

**Improve Relations**
- Assign the Royal Herald (kingdom role) to improve relations with a target kingdom
- +2 relations per game-week while assigned, capped at +20 from this source
- Can only target one kingdom at a time
- Requires: Royal Herald role filled

**Send Gift**
- Send gold from kingdom treasury to another kingdom
- Relations boost scales with amount:
  - 1,000-5,000g: +5
  - 5,001-15,000g: +10
  - 15,001-50,000g: +15
  - 50,001+: +20
- Cooldown: 2 weeks per target

**Denounce**
- Publicly denounce another king
- -15 relations with target
- +5 relations with kingdoms also at war with target
- Cooldown: 4 weeks
- Visible to all kingdoms

### Tier 2: Treaties (require minimum relations)

**Non-Aggression Pact (NAP)**
- Both kingdoms agree not to attack each other
- Requires: +10 relations
- Duration: 26 game-weeks (half a year)
- Auto-renews unless cancelled (2-week notice period)
- Breaking penalty: -40 with target, -20 with all others, "Oathbreaker" tag

**Trade Agreement**
- Both kingdoms gain economic benefits from cross-kingdom trade
- Requires: Active NAP + 15 relations
- Effects:
  - +15% tariff revenue from caravans between the two kingdoms
  - Reduced danger level on trade routes between kingdoms (-1 tier)
  - Access to each other's unique regional resources at markets
- Breaking: -15 relations, lose trade bonuses immediately

**Military Access**
- Allow another kingdom's armies to move through your territory
- Requires: +10 relations
- One-directional (they can pass through yours, not vice versa unless mutual)
- Can be revoked with 1-week notice

### Tier 3: Deep Diplomacy (require higher relations)

**Royal Marriage**
- King arranges marriage between royal family members (ties into Dynasty system)
- Requires: Trade Agreement + 25 relations
- Effects:
  - +15 persistent relations bonus
  - Enables alliance proposal
  - Creates dynasty link between kingdoms
- Breaking (by declaring war): -30 relations, target gets free "Broken Marriage" casus belli

**Defensive Alliance**
- If one ally is attacked, the other is called to join the defensive war
- Requires: Royal Marriage + 35 relations
- Only triggers on **defensive** wars (when your ally is attacked)
- Refusing an alliance call: alliance breaks, -25 relations, "Unreliable Ally" modifier
- **Limit: Each kingdom can only have ONE alliance at a time**
  - With 4 kingdoms, this forces either 2v1v1 or 2v2 dynamics
  - Always leaves at least one kingdom as the "swing vote"

**Full Military Alliance**
- Both kingdoms obligated to join ALL wars (offensive and defensive)
- Requires: Defensive Alliance maintained for 13+ game-weeks + 50 relations
- Refusing any call: alliance broken, -25 relations
- Much more commitment, much more powerful

### Tier 4: Hostile Diplomacy

**Trade Embargo**
- Cut off all trade with target kingdom
- Effects:
  - Caravans between kingdoms blocked
  - -20 relations with target
  - Hurts both economies, but hurts the more trade-dependent kingdom more
- Can be lifted at any time

**Demand Tribute**
- Demand a one-time gold payment from another kingdom
- If refused: generates a valid casus belli ("Refused Tribute")
- If accepted: gold transfers, +5 relations (shows submission), minor prestige loss for payer
- Amount can be set by the demanding king
- Cooldown: 8 weeks per target

**Fabricate Claim**
- Spend gold + time to fabricate a territorial claim on a specific barony
- Cost: 10,000g + 4 game-weeks
- Requires: Spymaster role filled
- On success: generates "Fabricated Claim" casus belli for that territory
- Risk: 15% chance of discovery per week during fabrication
  - If discovered: -20 relations with target, fabrication cancelled, gold lost

**Support Rebels**
- Secretly fund dissidents in another kingdom
- Cost: 5,000g per game-week
- Effect: Increases unrest/rebellion chance in target kingdom
- If discovered: -25 relations, generates CB for the target kingdom against you
- Requires: Spymaster role filled

---

## Treaty Hierarchy

Treaties build on each other in a clear progression:

```
NAP (+10 relations)
  └── Trade Agreement (+15 relations)
        └── Royal Marriage (+25 relations)
              └── Defensive Alliance (+35 relations)
                    └── Full Military Alliance (+50 relations, +13 weeks)
```

Breaking a lower treaty automatically breaks all higher treaties. Declaring war on a trade partner breaks the NAP, trade agreement, and everything above.

---

## Casus Belli System

### Justified vs Unjustified War

You can ALWAYS declare war, but unjustified war has severe consequences:

**With valid CB:**
- -15 relations with target only
- No reputation penalty
- Other kingdoms stay neutral unless allied

**Without valid CB:**
- -25 relations with target
- -15 relations with ALL other kingdoms
- "Unjust Aggressor" tag for 8 weeks
- Your own kingdom suffers unrest (NPC morale drops)
- Other kingdoms eligible to form a coalition against you

### CB Sources

| Casus Belli | How Obtained | War Goal | Expires |
|---|---|---|---|
| Fabricated Claim | Spymaster fabrication (10,000g, 4 weeks) | Seize specific barony | 26 weeks |
| Broken Treaty | Other kingdom broke NAP/alliance | Punitive war, demand reparations | 12 weeks |
| Broken Marriage | Other kingdom broke royal marriage by declaring war | Punitive war, demand reparations | 12 weeks |
| Refused Tribute | Demanded tribute was refused | Force tribute / seize territory | 8 weeks |
| Territorial Reconquest | Lost territory in previous war | Reclaim lost territory | Never (permanent) |
| Border Raid Response | Kingdom raided your territory | Punitive / retaliatory war | 8 weeks |
| Alliance Call | Allied kingdom called you to join their war | Join existing war | 4 weeks |
| Holy War | Target kingdom banned your state religion | Religious conquest | Persists while banned |
| Rebellion Suppression | Vassal kingdom rebelled | Force submission | Never |
| Spy Discovery | Caught enemy spies operating in your kingdom | Punitive war | 8 weeks |

---

## Aggressive Expansion & Coalition Mechanic

### Aggressive Expansion (AE) Score

Each kingdom has a visible **Aggressive Expansion** score (0-100) that tracks how aggressively they've been expanding.

| Action | AE Gained |
|---|---|
| Conquer a barony | +15 |
| Conquer a town | +10 |
| Win a war (any) | +5 |
| Declare war without CB | +10 |
| Break a truce | +15 |
| Raid another kingdom | +3 |

**AE Decay:** -2 per game-week (natural cooldown)

### Coalition Formation

When a kingdom's AE reaches **50+**, other kingdoms can form a **Coalition** against them.

- Any kingdom with negative relations toward the aggressor can join
- Coalition members agree to jointly declare war if the aggressor attacks ANY coalition member
- Coalition war goal: Force the aggressor to return conquered territory + pay reparations
- Coalition dissolves when AE drops below 30 or after a coalition war ends

**This is the key balancing mechanic for 4 kingdoms.** If one kingdom conquers too much, the other three unite against it. This prevents runaway domination and creates a natural "expansion cooldown."

---

## Kingdom Council & Diplomacy Roles

Certain kingdom roles directly affect diplomatic capabilities:

| Role | Diplomatic Function |
|---|---|
| **King** | Signs/breaks all treaties, declares war, final authority |
| **Chancellor** | Can propose treaties on king's behalf, +5% treaty acceptance chance |
| **Royal Herald** | Assigned to improve relations (+2/week), announces denouncements |
| **Spymaster** | Fabricates claims, runs espionage, discovers enemy spies |
| **Royal Treasurer** | Manages tribute payments, gift amounts, trade agreement economics |
| **General** | Called when alliance triggers, manages military access |
| **Archbishop** | Religious diplomacy - influences holy war CB, religion negotiations |

**Empty roles = penalties:**
- No Chancellor: -5 to all outgoing treaty proposals
- No Royal Herald: Cannot use "Improve Relations" action
- No Spymaster: Cannot fabricate claims or run counter-intelligence
- No Treasurer: -10% trade agreement revenue

---

## Diplomatic Visibility

**All treaties are PUBLIC.** Every player can see:
- Which kingdoms have NAPs, trade agreements, marriages, alliances
- Current relations scores between all kingdom pairs
- Active reputation tags (Oathbreaker, Truce Breaker, etc.)
- Aggressive Expansion scores
- Active coalitions

This creates political intrigue as players watch alliance patterns shift and predict who will side with whom.

**Espionage results are PRIVATE.** Only the spymaster and king see:
- Enemy army sizes (from scouting)
- Treasury estimates (from infiltration)
- Fabrication progress
- Counter-intelligence reports

---

## Example Scenarios (4 Kingdoms: A, B, C, D)

### Scenario 1: The Cold War
- A and B sign a defensive alliance via royal marriage
- C and D, threatened, sign their own defensive alliance
- Result: 2v2 standoff. Neither side wants to attack because the ally will join. Trade agreements between A-C and B-D create economic ties that cross the alliance lines. Breaking trade to go to war costs everyone money.

### Scenario 2: The Backstab
- A and B are allied. A asks B to join a war against C.
- B refuses the call (doesn't want to fight C, who they have a trade agreement with)
- Alliance breaks. A gets "Unreliable Ally" tag. A is now isolated.
- C and D see the opportunity and form their own alliance.
- A must now either reconcile with B or face a 1v3 situation.

### Scenario 3: The Expansion Check
- Kingdom A conquers two baronies from D. AE reaches 55.
- B and C form a coalition against A.
- A must stop expanding and wait for AE to decay, or face a 1v3 coalition war.
- Meanwhile, D uses the breathing room to rebuild.

### Scenario 4: The Spy Game
- A's spymaster fabricates a claim on one of C's baronies (takes 4 weeks, costs 10,000g)
- C's spymaster discovers the fabrication (15% chance per week)
- C denounces A publicly. Relations drop. C gets a free CB against A for espionage.
- Now A must decide: abandon the claim, or go to war before C retaliates.

### Scenario 5: The Religious Conflict
- Kingdom A declares Religion X as state religion
- Kingdom B bans Religion X
- A automatically gets a "Holy War" CB against B
- A's archbishop pressures the king to act. B's minority Religion X members face persecution.
- C and D must choose sides or stay neutral.

---

## Architecture Approach

### New Models

**`KingdomRelation`**
- `kingdom_id`, `target_kingdom_id` (bilateral pair)
- `score` (integer, -100 to 100)
- Unique constraint on (kingdom_id, target_kingdom_id)

**`KingdomRelationModifier`**
- `kingdom_relation_id`, `source` (event type string)
- `value` (integer, positive or negative)
- `description` (human-readable)
- `expires_at` (nullable - null = permanent/persists while condition active)
- `source_type`, `source_id` (polymorphic - links to treaty, war, gift, etc.)

**`KingdomTreaty`**
- `kingdom_a_id`, `kingdom_b_id`
- `type` (nap, trade_agreement, military_access, royal_marriage, defensive_alliance, full_alliance)
- `status` (proposed, active, cancelled, broken)
- `proposed_by_kingdom_id`, `proposed_at`, `accepted_at`, `ended_at`
- `terms` (json - treaty-specific details)
- `auto_renew` (boolean)

**`KingdomReputationTag`**
- `kingdom_id`, `tag` (oathbreaker, truce_breaker, unjust_aggressor, reliable_ally, generous)
- `expires_at`
- `source_description`

**`DiplomaticAction`**
- `acting_kingdom_id`, `target_kingdom_id`
- `action_type` (improve_relations, send_gift, denounce, demand_tribute, fabricate_claim, support_rebels)
- `details` (json), `result` (json)
- `acting_user_id` (which player/role performed it)
- `created_at`

**`FabricatedClaim`**
- `kingdom_id`, `target_barony_id`
- `spymaster_user_id`
- `progress` (0-100), `gold_invested`
- `status` (in_progress, completed, discovered, expired)
- `discovered_at`, `completed_at`, `expires_at`

### New Service

**`KingdomDiplomacyService`**
- `getRelation(Kingdom, Kingdom)` - get current bilateral score
- `addModifier(Kingdom, Kingdom, source, value, expiresAt)` - add relation modifier
- `recalculateRelations(Kingdom, Kingdom)` - sum active modifiers
- `proposeTreaty(Kingdom, Kingdom, type, terms)` - propose a treaty
- `acceptTreaty(KingdomTreaty)` - accept and apply effects
- `breakTreaty(KingdomTreaty, Kingdom)` - break with penalties
- `canProposeTreaty(Kingdom, Kingdom, type)` - check relations threshold + prerequisites
- `sendGift(Kingdom, Kingdom, amount)` - gift gold from treasury
- `denounce(Kingdom, Kingdom)` - public denouncement
- `demandTribute(Kingdom, Kingdom, amount)` - demand payment
- `fabricateClaim(Kingdom, Barony)` - start fabrication
- `checkFabricationDiscovery(FabricatedClaim)` - 15% weekly check
- `formCoalition(Kingdom[])` - form coalition against aggressor
- `getAggressiveExpansion(Kingdom)` - calculate current AE
- `decayModifiers()` - scheduled: remove expired modifiers
- `decayAggressiveExpansion()` - scheduled: -2 AE per week

### New Controller

**`KingdomDiplomacyController`**
- Treaty management (propose, accept, reject, break)
- Diplomatic actions (gift, denounce, demand, fabricate)
- Relations overview page (all 4 kingdoms' relations at a glance)
- Treaty history log
- Espionage management (for spymaster)

### Scheduled Jobs

- **Weekly relation decay** - expire old modifiers, recalculate scores
- **Weekly AE decay** - reduce aggressive expansion by 2
- **Weekly fabrication progress** - advance claim fabrication, check for discovery
- **Treaty auto-renewal** - check and renew treaties with auto_renew enabled

### Integration Points

- `WarService::declareWar()` - check for CB, apply relation modifiers, break treaties, update AE
- `WarService::offerPeace()` - create truce modifiers, territorial change modifiers
- `CaravanService` - apply trade agreement bonuses to tariff calculations
- `MigrationService` - relations affect migration approval (hostile kingdoms auto-deny)
- `KingdomReligion` - shared/opposed religion creates automatic modifiers
- `TreasuryService` - gift/tribute payments from kingdom treasury
- `RoleService` - empty diplomatic roles create penalties
- `DynastyService` - royal marriages create both dynasty alliance AND kingdom treaty

### Frontend Pages

- **Diplomacy Overview** - 4-kingdom relations map/grid showing all scores, treaties, tags
- **Kingdom Relations Detail** - bilateral view with modifier breakdown, treaty options
- **Treaty Management** - propose, view active, history
- **Espionage Panel** - fabrication progress, spy missions (spymaster only)
- **Diplomatic Log** - chronological feed of all diplomatic events across all kingdoms
