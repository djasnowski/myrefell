# Myrefell

A persistent browser-based medieval game (PBBG) where players start as peasants and rise through a feudal hierarchy to become knights, lords, or kings through democratic elections, economic power, and political maneuvering.

> **"Land belongs to groups. Power belongs to players."**

## Tech Stack

| Component | Technology |
|-----------|------------|
| Backend | Laravel 12 (PHP 8.2+) |
| Frontend | React 19 + TypeScript |
| Full-Stack | Inertia.js |
| Styling | Tailwind CSS 4 + Radix UI |
| Database | PostgreSQL 18 |
| Cache/Sessions | Redis |
| Authentication | Laravel Fortify (with 2FA) |
| Build Tool | Vite 7 |
| Testing | Pest 4 |
| DevOps | Docker Compose / Laravel Sail |

## Features

### Player System
- **Stats**: HP, Energy, Combat Level, Gold, Titles
- **9 Skills**: Attack, Strength, Defense, Mining, Fishing, Woodcutting, Cooking, Smithing, Crafting
- **Equipment**: 9 slots (head, chest, legs, feet, hands, weapon, shield, ring, amulet)
- **28-slot inventory** with stackable items
- **Energy system** with regeneration (1 point every 5 minutes)

### World Hierarchy
```
Kingdoms
  └── Towns (upgraded villages with markets)
        └── Castles (military centers)
              └── Villages (population hubs)
                    └── Players
```

- **8 Biomes**: Forests, Plains, Mountains, Swamps, Desert, Tundra, Coastal, Volcano

### Political System
- **Democratic elections** for village roles, mayors, and kings
- **Electable roles**: Elder, Blacksmith, Merchant, Guard Captain, Healer
- **Vote of no-confidence** to remove leaders
- **Title progression**: Peasant → Knight → Lord → King

### Religion & Cults

> "Religions create loyalty that competes with political loyalty."

Non-territorial power structures that overlay the political hierarchy. Players may have to choose between their King and their God.

**Cults** (Secret):
- Founded by 5+ players for free
- Hidden membership, can infiltrate governments
- 2 beliefs with powerful dark bonuses
- High-risk, high-reward gameplay
- Can upgrade to a public Religion

**Religions** (Public):
- Require 15+ followers and 100,000 gold to upgrade from cult
- Up to 5 beliefs with bonuses and penalties
- Build structures: Shrines (50K), Temples (500K), Cathedrals (5M gold)
- Religious ranks: Prophet → High Priest → Priest → Acolyte → Follower

**Beliefs** provide bonuses with tradeoffs:
- Combat: Blood Oath (+15% damage, -10% healing), Warrior's Path (+10% combat XP, -10% crafting)
- Economy: Merchant Faith (+10% trade profits, -5% combat), Ascetic Vow (+15% crafting XP, max 1K gold)
- Dark/Cult-only: Shadow Pact (invisible while traveling, -25% HP), Death Cult (keep 50% gold on death)

**Political Integration**:
- Kingdoms can adopt a state religion for tax bonuses and happiness
- Kingdoms can ban religions, forcing followers underground or into exile
- Castle jails hold persecuted followers and criminals
- Faith points earned through prayer, donations, conversions, and pilgrimages

### Economy
- **Bank accounts** per location with transaction tracking
- **Tax flow** from villages up to kingdoms
- **Role salaries** for elected positions
- **Crafting and trading** systems

### Gameplay
- **Daily Tasks**: Combat, gathering, crafting, and service tasks with gold/XP rewards
- **Quests**: Accept up to 5 quests with various objectives and rewards
- **Gathering**: Mining, fishing, woodcutting in the wilderness
- **Crafting**: Smithing, cooking, and general crafting with recipes
- **Travel**: Move between locations with energy cost and travel time
- **Healing**: Village healers and castle/town infirmaries

## Requirements

- PHP 8.2+
- Node.js 20+
- PostgreSQL 18+
- Redis
- Composer
- npm

## Installation

### Using Docker (Recommended)

```bash
# Clone the repository
git clone git@github.com:djasnowski/myrefell.git
cd myrefell

# Copy environment file
cp .env.example .env

# Start containers
docker compose up -d

# Install dependencies and setup
docker compose exec app composer setup
```

### Manual Setup

```bash
# Clone the repository
git clone git@github.com:djasnowski/myrefell.git
cd myrefell

# Install PHP dependencies
composer install

# Install Node dependencies
npm install

# Copy environment file and generate key
cp .env.example .env
php artisan key:generate

# Configure your database in .env, then run migrations
php artisan migrate

# Build frontend assets
npm run build
```

## Development

```bash
# Run the full development stack (server, queue, logs, vite)
composer dev

# Or run components individually:
php artisan serve          # Laravel server
npm run dev                # Vite dev server
php artisan queue:listen   # Queue worker
php artisan pail           # Log viewer
```

### Available Commands

```bash
# Development
composer dev              # Full dev environment with hot reload
composer dev:ssr          # Dev with server-side rendering

# Code Quality
composer lint             # Format PHP code with Pint
composer test:lint        # Check PHP formatting
npm run lint              # Fix JS/TS with ESLint
npm run format            # Format with Prettier
npm run format:check      # Check Prettier formatting
npm run types             # TypeScript type checking

# Testing
composer test             # Run all tests
php artisan test          # Run PHP tests only

# Build
npm run build             # Production build
npm run build:ssr         # Production build with SSR
```

## Project Structure

```
app/
├── Actions/Fortify/      # Auth actions (registration, password reset)
├── Http/Controllers/     # Request handlers
├── Models/               # Eloquent models (User, Village, Kingdom, etc.)
├── Services/             # Business logic (Energy, Travel, Quest, etc.)
└── Jobs/                 # Background jobs (elections, energy regen)

resources/js/
├── components/           # Reusable React components
│   └── ui/              # Base UI components (buttons, cards, etc.)
├── hooks/               # Custom React hooks
├── layouts/             # Page layouts (app, auth, settings)
├── lib/                 # Utilities and helpers
├── pages/               # Inertia page components
└── types/               # TypeScript type definitions

database/
├── migrations/          # Database schema
├── factories/           # Model factories for testing
└── seeders/             # Development data seeders
```

## Testing

```bash
# Run all tests with linting
composer test

# Run tests only
php artisan test

# Run specific test file
php artisan test tests/Feature/Auth/AuthenticationTest.php
```

## Docker Services

| Service | Port | Description |
|---------|------|-------------|
| app | 80 | Laravel application |
| pgsql | 5432 | PostgreSQL database |
| redis | 6379 | Cache and sessions |
| mailpit | 8025 | Email testing UI |

## Environment Variables

Key environment variables to configure:

```env
APP_URL=http://localhost
DB_CONNECTION=pgsql
DB_HOST=pgsql
DB_DATABASE=myrefell
CACHE_STORE=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=database
```

## License

MIT
