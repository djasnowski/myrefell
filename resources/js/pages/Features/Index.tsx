import { Head, Link, usePage } from '@inertiajs/react';
import {
    Anchor,
    ArrowLeft,
    Axe,
    Backpack,
    BarChart3,
    Building,
    Calendar,
    Castle,
    Church,
    ClipboardList,
    Coins,
    Crown,
    Dumbbell,
    Gavel,
    Hammer,
    Heart,
    HeartPulse,
    Home,
    Landmark,
    MapPin,
    Pickaxe,
    Scale,
    Scroll,
    Shield,
    Ship,
    Sparkles,
    Store,
    Swords,
    Target,
    Trees,
    Trophy,
    Users,
    Vote,
    Wheat,
    type LucideIcon,
} from 'lucide-react';
import { dashboard, login, register } from '@/routes';
import type { SharedData } from '@/types';

interface FeatureItem {
    icon: LucideIcon;
    name: string;
    description: string;
    details: string[];
}

interface FeatureCategory {
    title: string;
    icon: LucideIcon;
    description: string;
    items: FeatureItem[];
}

const categories: FeatureCategory[] = [
    {
        title: 'Character & Progression',
        icon: BarChart3,
        description: 'Train skills, manage your stats, and grow stronger over time.',
        items: [
            { icon: Dumbbell, name: 'Training Grounds', description: 'Train combat skills (Attack, Strength, Defense, Range, Hitpoints) at any settlement.', details: ['Choose which skill to focus on each session', 'Higher-tier training grounds unlock at larger settlements', 'Training consumes energy — rest at taverns to recover'] },
            { icon: BarChart3, name: 'Skills System', description: '12 skills to master: 5 combat, 7 trade. Gain XP through training, jobs, and activities.', details: ['Combat: Attack, Strength, Defense, Range, Hitpoints', 'Trade: Mining, Fishing, Woodcutting, Farming, Crafting, Cooking, Smithing', 'Unlock new abilities and job tiers as skills level up'] },
            { icon: Backpack, name: 'Inventory & Equipment', description: 'Manage items, equip weapons and armor, organize your belongings.', details: ['Equipment slots for head, body, legs, weapon, and shield', 'Carry weight limits based on Strength level', 'Drop items on death in dangerous zones'] },
            { icon: Heart, name: 'Health & Energy', description: 'Manage HP, energy for activities. Rest at taverns or visit healers when injured.', details: ['HP restores slowly over time or instantly via healers', 'Energy consumed by training, gathering, and combat', 'Food items restore energy on the go'] },
            { icon: ClipboardList, name: 'Daily Tasks', description: 'Complete 3 daily tasks for bonus gold, XP, and item rewards.', details: ['Tasks refresh every real-world day', 'Variety of objectives: combat, gathering, travel', 'Streak bonuses for consecutive days completed'] },
            { icon: Scroll, name: 'Quest System', description: 'Accept quests from NPCs, track objectives, earn rewards.', details: ['Story quests advance the world narrative', 'Side quests offer gold, items, and reputation', 'Some quests require specific skill levels to begin'] },
        ],
    },
    {
        title: 'Location Services',
        icon: MapPin,
        description: 'Every settlement offers services. What\'s available depends on where you are.',
        items: [
            { icon: Store, name: 'Market', description: 'Buy and sell goods. Player-driven economy with location-specific prices.', details: ['Prices vary between settlements based on supply', 'List items for sale or browse others\' listings', 'Trade goods across regions for profit'] },
            { icon: Landmark, name: 'Bank', description: 'Store gold safely. Your wealth stays where you deposit it.', details: ['Gold in the bank is safe from theft and death', 'Withdraw only at the branch where you deposited', 'Interest may accrue depending on the local economy'] },
            { icon: HeartPulse, name: 'Healer', description: 'Cure diseases, heal wounds. Different locations have different healers.', details: ['Instant HP restoration for a fee', 'Cure status effects like poison or disease', 'Higher-level healers available in towns and cities'] },
            { icon: Sparkles, name: 'Shrine', description: 'Pray to the gods, receive blessings that grant temporary bonuses.', details: ['Choose from multiple deities with different boons', 'Blessings last for a set number of in-game days', 'Rare divine events during religious festivals'] },
            { icon: Home, name: 'Tavern', description: 'Rest to restore energy, hear local rumors, socialize with travelers.', details: ['Full energy restoration after resting', 'Rumors hint at nearby quests and events', 'Social hub — meet other players'] },
            { icon: Ship, name: 'Port', description: 'Available at coastal settlements. Travel by sea to distant ports.', details: ['Fastest way to cross large distances', 'Ship schedules vary by settlement size', 'Risk of pirate encounters on some routes'] },
        ],
    },
    {
        title: 'Gathering & Crafting',
        icon: Pickaxe,
        description: 'Harvest resources and create items to sell or use.',
        items: [
            { icon: Pickaxe, name: 'Mining', description: 'Dig for ore, stone, gems, and coal in village mining spots.', details: ['Higher Mining skill unlocks rarer ores', 'Gem finds are random and valuable', 'Ore is essential for Smithing'] },
            { icon: Anchor, name: 'Fishing', description: 'Catch fish for cooking or selling. Different locations yield different catches.', details: ['Coastal, river, and lake fishing spots', 'Rare fish fetch high prices at market', 'Fishing skill affects catch rate and quality'] },
            { icon: Axe, name: 'Woodcutting', description: 'Chop trees for lumber. Essential for construction and crafting.', details: ['Different tree types yield different wood', 'Lumber used in building and furniture crafting', 'Higher skill means faster chopping'] },
            { icon: Wheat, name: 'Farming', description: 'Plant crops, tend fields, harvest when ready. Seasonal mechanics.', details: ['Crops grow in real time across seasons', 'Water and tend fields to maximize yield', 'Sell harvests at market or use in Cooking'] },
            { icon: Hammer, name: 'Crafting', description: 'Create weapons, armor, tools, and goods from raw materials.', details: ['Recipes unlock as your Crafting skill improves', 'Crafted items can be sold or equipped', 'Rare materials produce superior-quality gear'] },
        ],
    },
    {
        title: 'Combat & Adventure',
        icon: Swords,
        description: 'Fight monsters, explore dungeons, and prove your worth in battle.',
        items: [
            { icon: Swords, name: 'Combat System', description: 'Turn-based combat using Attack, Strength, Defense, Range, and Hitpoints.', details: ['Melee and ranged combat styles', 'Equipment bonuses affect hit chance and damage', 'Flee if the odds turn against you'] },
            { icon: Trees, name: 'Wilderness Encounters', description: 'Random encounters while traveling through the wild.', details: ['Bandits, wild animals, and wandering merchants', 'Encounter rate varies by region danger level', 'Some encounters offer unique quest hooks'] },
            { icon: Castle, name: 'Dungeons', description: 'Multi-floor dungeons with monsters, puzzles, and boss fights. Lose items on death.', details: ['Progressive difficulty as you descend', 'Boss loot includes rare and unique items', 'Death means losing carried items — bank valuables first'] },
            { icon: Trophy, name: 'Tournaments', description: 'Compete in arena brackets for glory, prizes, and renown.', details: ['Seasonal tournaments with leaderboards', 'Entry fees with prize pools', 'Winners earn titles and unique rewards'] },
            { icon: Target, name: 'Bounty Hunting', description: 'Track down wanted criminals for rewards. Risk and glory await.', details: ['Bounties posted by players and NPCs', 'Track targets across multiple regions', 'PvP combat when engaging a bounty target'] },
        ],
    },
    {
        title: 'Economy & Work',
        icon: Coins,
        description: 'Earn wages, run businesses, and trade across the realm.',
        items: [
            { icon: Coins, name: 'Jobs System', description: '100+ jobs across all settlements. Work for wages, gain XP, collect resources.', details: ['Job availability depends on settlement type and size', 'Higher skill levels unlock better-paying positions', 'Some jobs grant resources alongside wages'] },
            { icon: Store, name: 'Businesses', description: 'Own shops, taverns, or workshops. Hire employees, set prices, earn profits.', details: ['Purchase or build business properties', 'Hire NPCs or other players as workers', 'Manage supply chains and pricing strategy'] },
            { icon: Ship, name: 'Trade Caravans', description: 'Send goods between settlements. Profit from price differences.', details: ['Buy low in one region, sell high in another', 'Caravans can be raided by bandits or players', 'Larger caravans carry more but move slower'] },
            { icon: Scale, name: 'Tariffs', description: 'Rulers can set tariffs on trade passing through their territory.', details: ['Barons and kings set tariff rates', 'High tariffs discourage trade but raise revenue', 'Smuggling routes bypass tariffs at personal risk'] },
        ],
    },
    {
        title: 'Social & Dynasty',
        icon: Users,
        description: 'Build relationships, found dynasties, and leave a legacy.',
        items: [
            { icon: Users, name: 'Guilds', description: 'Join or found craft guilds. Work together, share resources, gain bonuses.', details: ['Guild halls provide shared storage and crafting', 'Guild quests and cooperative objectives', 'Rank up within the guild hierarchy'] },
            { icon: Crown, name: 'Dynasties', description: 'Found a dynasty, manage family members, plan succession.', details: ['Dynasty members share a family name and crest', 'Pool resources and political influence', 'Dynasty reputation persists across generations'] },
            { icon: Heart, name: 'Marriage', description: 'Propose marriage, form alliances, produce heirs.', details: ['Marriage proposals require mutual acceptance', 'Allied dynasties gain diplomatic bonuses', 'Heirs inherit skills and estate upon death'] },
            { icon: Scroll, name: 'Succession', description: 'Name heirs, manage inheritance. Your legacy continues after death.', details: ['Designate a primary heir for your titles', 'Distribute wealth and items among heirs', 'Contested successions can spark political drama'] },
            { icon: Sparkles, name: 'Religion', description: 'Follow or found religions. Religions grant bonuses and political influence.', details: ['Join an established faith or create your own', 'Religious leaders wield soft power', 'Holy days and festivals offer unique events'] },
        ],
    },
    {
        title: 'Governance & Politics',
        icon: Crown,
        description: 'Every position is player-held. From village elder to king.',
        items: [
            { icon: Home, name: 'Village Roles', description: 'Elder, Blacksmith, Healer, Merchant, and more. Each manages different aspects.', details: ['Elders set local policies and taxes', 'Specialists manage services like healing and trade', 'Roles are elected by village residents'] },
            { icon: Church, name: 'Town Government', description: 'Elected Mayor oversees guilds, markets, and town affairs.', details: ['Mayors manage town budgets and infrastructure', 'Appoint officials to handle specific duties', 'Town councils vote on major decisions'] },
            { icon: Castle, name: 'Barony', description: 'Baron collects taxes, judges crimes, raises militia.', details: ['Barons control multiple villages and towns', 'Set regional tax rates and trade policies', 'Raise militia to defend or expand territory'] },
            { icon: Crown, name: 'Kingdom', description: 'Elected King declares wars, sets realm taxes, rules the land.', details: ['Kings govern entire kingdoms with multiple baronies', 'Declare war, negotiate treaties, levy realm taxes', 'Royal decrees affect all subjects'] },
            { icon: Vote, name: 'Elections', description: 'All positions are elected. Campaign, vote, win or lose.', details: ['Regular election cycles for every office', 'Campaign by making promises and building reputation', 'Election results shape the political landscape'] },
            { icon: Gavel, name: 'No Confidence', description: 'Remove corrupt officials through no-confidence votes.', details: ['Any citizen can call a vote of no confidence', 'Requires majority support to succeed', 'Removed officials face a cooldown before re-election'] },
        ],
    },
    {
        title: 'Justice & Law',
        icon: Gavel,
        description: 'A full legal system with crimes, trials, and punishments.',
        items: [
            { icon: Scale, name: 'Crimes', description: 'Murder, theft, assault, tax evasion, treason, and more.', details: ['Crimes are tracked by the justice system', 'Evidence is gathered automatically and by accusers', 'Severity ranges from misdemeanor to capital offense'] },
            { icon: Gavel, name: 'Accusations', description: 'Accuse others of crimes. Provide evidence, wait for trial.', details: ['File formal accusations at the courthouse', 'Attach evidence and witness statements', 'False accusations carry their own penalties'] },
            { icon: Users, name: 'Trials', description: 'Judges hear cases, review evidence, render verdicts.', details: ['Appointed judges preside over cases', 'Defendants can present counter-evidence', 'Verdicts can be appealed to higher courts'] },
            { icon: Shield, name: 'Punishments', description: 'Fines, jail time, exile, execution. Severity matches the crime.', details: ['Fines deducted from bank or carried gold', 'Jail restricts actions for a set duration', 'Exile and execution for the most serious offenses'] },
            { icon: Coins, name: 'Bounties', description: 'Post bounties on criminals. Hunters collect rewards.', details: ['Bounties placed by players or the justice system', 'Hunters must capture or defeat the target', 'Rewards paid upon confirmation of capture'] },
        ],
    },
    {
        title: 'Warfare',
        icon: Shield,
        description: 'Raise armies, declare wars, and conquer territories.',
        items: [
            { icon: Shield, name: 'Armies', description: 'Raise and command armies. Recruit soldiers, supply equipment.', details: ['Recruit from your settlements\' population', 'Equip soldiers with weapons and armor', 'Army upkeep costs gold each day'] },
            { icon: Swords, name: 'Battles', description: 'Fight pitched battles. Tactics, terrain, and numbers matter.', details: ['Terrain advantages for defenders and ambushers', 'Morale affects army performance in combat', 'Decisive victories can end wars quickly'] },
            { icon: Castle, name: 'Sieges', description: 'Besiege castles and settlements. Starve them out or storm the walls.', details: ['Sieges take time — supplies dwindle for defenders', 'Assault the walls for a faster but bloodier resolution', 'Defenders can sally forth to break the siege'] },
            { icon: Scroll, name: 'Peace Treaties', description: 'Negotiate peace. Demand territory, gold, or prisoners.', details: ['Both sides propose terms for ending conflict', 'Territory, gold, and prisoner exchanges', 'Broken treaties carry severe diplomatic penalties'] },
        ],
    },
    {
        title: 'World & Travel',
        icon: MapPin,
        description: 'A persistent world with seasons, disasters, and realistic travel.',
        items: [
            { icon: MapPin, name: 'World Map', description: '4 kingdoms, multiple baronies, towns, and villages to explore.', details: ['Interactive map showing all settlements and regions', 'Fog of war clears as you explore', 'Map markers for quests, events, and points of interest'] },
            { icon: Trees, name: 'Wilderness', description: 'Travel between settlements through forests, mountains, and plains.', details: ['Travel time depends on distance and terrain', 'Dangerous regions have higher encounter rates', 'Discover hidden locations off the beaten path'] },
            { icon: Ship, name: 'Sea Travel', description: 'Board ships at ports for faster long-distance travel.', details: ['Scheduled departures to connected ports', 'Faster than overland for coastal destinations', 'Occasional sea events during voyages'] },
            { icon: Building, name: 'Settlement Founding', description: 'Petition for a charter, gather settlers, found new villages.', details: ['Requires a royal charter from the local king', 'Gather resources and settlers to establish the village', 'Founded villages grow over time with investment'] },
            { icon: Calendar, name: 'Seasons', description: '4 seasons affect farming, travel speed, and combat.', details: ['Spring and summer favor farming and travel', 'Autumn brings harvest festivals and trade fairs', 'Winter slows travel and limits crop growth'] },
        ],
    },
];

export default function Features({ canRegister = true }: { canRegister?: boolean }) {
    const { auth } = usePage<SharedData>().props;

    return (
        <>
            <Head title="Game Features - Myrefell">
                <link rel="preconnect" href="https://fonts.bunny.net" />
                <link href="https://fonts.bunny.net/css?family=cinzel:400,700&family=inter:400,500,600" rel="stylesheet" />
            </Head>

            <div className="relative min-h-screen bg-background text-foreground">
                {/* Background */}
                <div className="fixed inset-0 bg-background">
                    <div className="absolute inset-0 bg-[radial-gradient(ellipse_at_top,_var(--tw-gradient-stops))] from-primary/10 via-background to-background" />
                </div>

                {/* Navigation */}
                <nav className="fixed top-0 z-50 w-full border-b border-border/50 bg-background/90 backdrop-blur-sm">
                    <div className="mx-auto flex max-w-7xl items-center justify-between px-6 py-4">
                        <div className="flex items-center gap-4">
                            <Link href="/" className="flex items-center gap-2 text-muted-foreground hover:text-primary transition">
                                <ArrowLeft className="h-4 w-4" />
                                <span className="text-sm">Back</span>
                            </Link>
                            <div className="h-4 w-px bg-border" />
                            <Link href="/" className="flex items-center gap-2">
                                <Crown className="h-6 w-6 text-primary" />
                                <span className="font-[Cinzel] text-xl font-bold tracking-wide text-primary">Myrefell</span>
                            </Link>
                        </div>
                        <div className="flex items-center gap-4">
                            {auth.user ? (
                                <Link
                                    href={dashboard()}
                                    className="rounded-lg bg-primary px-6 py-2 font-semibold text-primary-foreground transition hover:bg-primary/90"
                                >
                                    Enter World
                                </Link>
                            ) : (
                                <>
                                    <Link
                                        href={login()}
                                        className="px-4 py-2 text-muted-foreground transition hover:text-primary"
                                    >
                                        Sign In
                                    </Link>
                                    {canRegister && (
                                        <Link
                                            href={register()}
                                            className="rounded-lg bg-primary px-6 py-2 font-semibold text-primary-foreground transition hover:bg-primary/90"
                                        >
                                            Play Now
                                        </Link>
                                    )}
                                </>
                            )}
                        </div>
                    </div>
                </nav>

                {/* Header */}
                <section className="relative pt-32 pb-16">
                    <div className="mx-auto max-w-7xl px-6 text-center">
                        <h1 className="mb-4 font-[Cinzel] text-4xl font-bold text-foreground md:text-5xl">
                            Game <span className="text-primary">Features</span>
                        </h1>
                        <p className="mx-auto max-w-2xl text-lg text-muted-foreground">
                            A comprehensive overview of everything you can do in Myrefell.
                            From training combat skills to ruling kingdoms.
                        </p>
                    </div>
                </section>

                {/* Quick Jump Navigation */}
                <section className="relative pb-12">
                    <div className="mx-auto max-w-7xl px-6">
                        <div className="flex flex-wrap justify-center gap-2">
                            {categories.map((category) => (
                                <a
                                    key={category.title}
                                    href={`#${category.title.toLowerCase().replace(/[^a-z0-9]/g, '-')}`}
                                    className="inline-flex items-center gap-1.5 rounded-full border border-border/50 bg-card/50 px-3 py-1.5 text-sm text-muted-foreground transition hover:border-primary/50 hover:text-primary"
                                >
                                    <category.icon className="h-3.5 w-3.5" />
                                    {category.title}
                                </a>
                            ))}
                        </div>
                    </div>
                </section>

                {/* Categories */}
                <section className="relative pb-24">
                    <div className="mx-auto max-w-7xl px-6">
                        <div className="space-y-16">
                            {categories.map((category, categoryIndex) => (
                                <div
                                    key={category.title}
                                    id={category.title.toLowerCase().replace(/[^a-z0-9]/g, '-')}
                                    className="scroll-mt-24"
                                >
                                    {/* Category Header */}
                                    <div className="mb-8 flex items-center gap-4">
                                        <div className="rounded-xl bg-primary/10 p-3.5 shadow-lg shadow-primary/10">
                                            <category.icon className="h-7 w-7 text-primary" />
                                        </div>
                                        <div>
                                            <h2 className="font-[Cinzel] text-2xl font-bold text-foreground">
                                                {category.title}
                                            </h2>
                                            <p className="text-muted-foreground">{category.description}</p>
                                            <div className="mt-2 h-0.5 w-12 rounded-full bg-gradient-to-r from-primary/60 to-transparent" />
                                        </div>
                                    </div>

                                    {/* Feature Items Grid */}
                                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                                        {category.items.map((item, itemIndex) => (
                                            <div
                                                key={item.name}
                                                className="group animate-in fade-in slide-in-from-bottom-4 fill-mode-both rounded-xl border border-border/50 bg-card/50 p-5 transition-all duration-300 hover:-translate-y-1 hover:border-primary/40 hover:shadow-lg hover:shadow-primary/5"
                                                style={{ animationDelay: `${itemIndex * 75}ms` }}
                                            >
                                                <div className="mb-3 flex items-center gap-3">
                                                    <div className="rounded-lg bg-primary/10 p-2">
                                                        <item.icon className="h-4 w-4 text-primary" />
                                                    </div>
                                                    <h3 className="font-semibold text-foreground">{item.name}</h3>
                                                </div>
                                                <p className="text-sm text-muted-foreground">{item.description}</p>
                                                {item.details.length > 0 && (
                                                    <ul className="mt-3 space-y-1 border-t border-border/30 pt-3">
                                                        {item.details.map((detail) => (
                                                            <li key={detail} className="flex items-start gap-2 text-sm text-muted-foreground/80">
                                                                <span className="mt-1.5 h-1 w-1 shrink-0 rounded-full bg-primary/40" />
                                                                {detail}
                                                            </li>
                                                        ))}
                                                    </ul>
                                                )}
                                            </div>
                                        ))}
                                    </div>

                                    {/* Separator */}
                                    {categoryIndex < categories.length - 1 && (
                                        <div className="mt-16 flex items-center gap-3">
                                            <div className="flex-1 h-px bg-gradient-to-r from-transparent via-border to-transparent" />
                                            <div className="w-1.5 h-1.5 rotate-45 bg-primary/30" />
                                            <div className="flex-1 h-px bg-gradient-to-r from-transparent via-border to-transparent" />
                                        </div>
                                    )}
                                </div>
                            ))}
                        </div>
                    </div>
                </section>

                {/* CTA Section */}
                <section className="relative border-t border-border/50 py-16">
                    <div className="mx-auto max-w-4xl px-6 text-center">
                        <h2 className="mb-4 font-[Cinzel] text-2xl font-bold text-foreground">
                            Ready to Begin?
                        </h2>
                        <p className="mb-6 text-muted-foreground">
                            Start as a peasant. Train your skills. Climb the social ladder. Shape history.
                        </p>
                        {auth.user ? (
                            <Link
                                href={dashboard()}
                                className="inline-flex items-center gap-2 rounded-lg bg-primary px-8 py-3 font-semibold text-primary-foreground transition hover:bg-primary/90"
                            >
                                <MapPin className="h-5 w-5" />
                                Enter Myrefell
                            </Link>
                        ) : (
                            <Link
                                href={register()}
                                className="inline-flex items-center gap-2 rounded-lg bg-primary px-8 py-3 font-semibold text-primary-foreground transition hover:bg-primary/90"
                            >
                                <Scroll className="h-5 w-5" />
                                Create Your Character
                            </Link>
                        )}
                    </div>
                </section>

                {/* Footer */}
                <footer className="relative border-t border-border/50 py-8">
                    <div className="mx-auto max-w-7xl px-6">
                        <div className="flex flex-col items-center justify-between gap-4 md:flex-row">
                            <div className="flex items-center gap-2">
                                <Crown className="h-5 w-5 text-primary" />
                                <span className="font-[Cinzel] text-sm font-bold text-muted-foreground">Myrefell</span>
                            </div>
                            <div className="flex items-center gap-4 text-xs text-muted-foreground">
                                <Link href="/terms" className="transition hover:text-primary">Terms of Service</Link>
                                <span>&middot;</span>
                                <Link href="/privacy" className="transition hover:text-primary">Privacy Policy</Link>
                                <span>&middot;</span>
                                <Link href="/rules" className="transition hover:text-primary">Game Rules</Link>
                            </div>
                        </div>
                    </div>
                </footer>
            </div>
        </>
    );
}
