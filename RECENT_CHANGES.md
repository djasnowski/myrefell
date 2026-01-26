# Recent Changes (Last 15 Commits)

This document summarizes the new features added in the most recent 15 commits.

---

## Trade & Economy

### Caravan Detail Page (`36f3f77`)
Full caravan management system for transporting goods between settlements.

**Features:**
- Display caravan status: loading, in_transit, arrived
- Load/unload goods with inventory selection and quantity picker
- Remove goods while loading
- Dispatch button with route selection
- Progress bar for in-transit caravans
- Event log showing bandit attacks, weather delays, etc.
- Cancel/disband caravan option

**Routes:** `GET /trade/caravans/{caravan}`

---

### Tariff Management Page (`ec96b69`)
Control trade tariffs on routes passing through your territory.

**Features:**
- Display routes passing through player's territory
- Revenue summary (weekly, monthly, total)
- Set tariff rates (0-50%) per item or general
- Real-time revenue tracking

**Routes:** `GET /trade/tariffs`, `POST /trade/tariffs`, `PUT /trade/tariffs/{tariff}`

---

## Warfare System

### Army Detail Page (`fcc6f05`)
Complete army management interface.

**Features:**
- Army header with name, commander, location, status
- Morale and supplies progress bars
- Unit composition table with attack/defense stats
- Recruit soldiers form (when at settlement)
- Movement orders with nearby settlements
- Supply line status display
- Battle history list
- Disband army button

**Routes:** `GET /warfare/armies/{army}`, `POST /warfare/armies/{army}/recruit`, `POST /warfare/armies/{army}/move`

---

### War Detail Page (`4245157`)
Comprehensive war tracking and management.

**Features:**
- War name, casus belli, start date display
- Attacker vs Defender blocks with war scores
- War score progress bar visualization
- Participant lists with contribution scores
- War goals with completion status
- Active sieges list with links
- Battle history with casualties
- Peace offer button for war leaders
- War statistics summary

**Routes:** `GET /warfare/wars/{war}`

---

### Battle Viewer Page (`cde1675`)
Detailed battle analysis and replay.

**Features:**
- Battle name, location, terrain type display
- Status indicators (ongoing, attacker/defender victory, draw)
- Attacker vs Defender force comparison with troop counts
- Initial strength, remaining strength, and casualties
- Morale tracking with visual progress bars
- Terrain and weather modifier display
- Day-by-day battle log with casualty details
- Commander names for both sides
- Link back to parent war
- Participating armies list with outcomes

**Routes:** `GET /warfare/battles/{battle}`

---

### Declare War Page (`51bdd53`)
Interface for declaring war on other realms.

**Features:**
- Target selection
- Casus belli selection (claim, conquest, etc.)
- War goal definition
- Declaration confirmation

**Routes:** `GET /warfare/declare`, `POST /warfare/declare`

---

### Peace Negotiation Page (`c013e73`)
End wars through diplomatic negotiation.

**Features:**
- War score display showing leverage
- Territory transfer selection
- Gold payment slider
- Truce duration options
- Acceptance likelihood calculation
- Send peace offer functionality

**Routes:** `GET /warfare/wars/{war}/peace`, `POST /warfare/wars/{war}/peace`

---

## Dynasty & Marriage

### Dynasty Overview Page (`a27721f`)
Central hub for dynasty management.

**Features:**
- Dynasty overview: name, motto, prestige, rank
- Stats display (members count, generations, founded date)
- Leadership section (current head, heir apparent)
- Living members list with status badges
- Recent events timeline with prestige changes
- Found dynasty form for players without a dynasty
- Edit motto functionality for dynasty heads

**Routes:** `GET /dynasty`, `POST /dynasty/found`, `PUT /dynasty/update`

---

### Dynasty Family Tree Page (`3a17edd`)
Visual representation of your dynasty's lineage.

**Features:**
- Visual tree layout by generation
- Member cards with key info
- Marriage connections displayed
- Zoom controls for large trees
- Living/deceased filter
- Click-to-select detail panel

**Routes:** `GET /dynasty/tree`

---

### Succession Settings Page (`3ea57de`)
Configure how titles and leadership pass down.

**Features:**
- Succession law selection (primogeniture, ultimogeniture, etc.)
- Heir designation
- Inheritance rules configuration

**Routes:** `GET /dynasty/succession`, `PUT /dynasty/succession`

---

### Marriage Proposals Page (`38e88bf`)
Manage incoming and outgoing marriage proposals.

**Features:**
- Incoming proposals section with accept/reject
- Outgoing proposals section with withdraw option
- Marriage history display
- Proposal details with dowry information

**Routes:** `GET /dynasty/proposals`, `POST /dynasty/proposals/{id}/accept`, `POST /dynasty/proposals/{id}/reject`, `DELETE /dynasty/proposals/{id}`

---

### Propose Marriage Page (`f2e5488`)
Create new marriage proposals between dynasties.

**Features:**
- Dynasty member selection (unmarried, age 14+)
- Candidate search with filters (dynasty, gender, age range)
- Dowry amount slider and input
- Message field for proposal
- Alliance preview when both parties selected
- Form validation and submission

**Routes:** `GET /dynasty/proposals/create`, `POST /dynasty/proposals`

---

## Events & Festivals

### Festival Detail Page (`492675f`)
Full festival participation interface.

**Features:**
- Festival name, type, category, and location display
- Date range with progress bar for active festivals
- Activities list with completion status
- Festival bonuses display
- Tournaments section with registration
- Participants list with role breakdown
- Join as attendee/performer/vendor options
- Leave festival button
- Status-appropriate displays (scheduled/active/completed)

**Routes:** `GET /events/festivals/{festival}`, `POST /events/festivals/{festival}/leave`

---

### Tournament Bracket Page (`3810b10`)
Visual tournament competition tracking.

**Features:**
- Tournament info (name, prize, rules)
- Competitor list with standings
- Visual bracket display showing matchups per round
- Registration button for open tournaments
- Withdraw option for registered competitors
- Match results and progression

**Routes:** `GET /events/tournaments/{tournament}`, `POST /events/tournaments/{tournament}/withdraw`

---

## Buildings

### Building Construction Page (`0a1cdf4`)
Settlement building management.

**Features:**
- Display existing buildings with condition indicators
- Construction projects with progress tracking
- Available building types with resource requirements
- Start new construction
- Repair damaged buildings
- Cancel in-progress construction

**Routes:** `GET /buildings`, `POST /buildings`, `POST /buildings/{building}/repair`, `DELETE /buildings/{building}`

---

## Summary

| Category | Pages Added | Key Features |
|----------|-------------|--------------|
| **Trade** | 2 | Caravans, Tariffs |
| **Warfare** | 5 | Armies, Wars, Battles, Declarations, Peace |
| **Dynasty** | 5 | Overview, Tree, Succession, Proposals, Marriage |
| **Events** | 2 | Festivals, Tournaments |
| **Buildings** | 1 | Construction, Repair |

**Total: 15 new pages/features**
