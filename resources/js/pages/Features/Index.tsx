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
            { icon: Dumbbell, name: 'Training Grounds', description: 'Train combat skills (Attack, Strength, Defense, Range, Hitpoints) at any settlement.' },
            { icon: BarChart3, name: 'Skills System', description: '12 skills to master: 5 combat, 7 trade. Gain XP through training, jobs, and activities.' },
            { icon: Backpack, name: 'Inventory & Equipment', description: 'Manage items, equip weapons and armor, organize your belongings.' },
            { icon: Heart, name: 'Health & Energy', description: 'Manage HP, energy for activities. Rest at taverns or visit healers when injured.' },
            { icon: ClipboardList, name: 'Daily Tasks', description: 'Complete 3 daily tasks for bonus gold, XP, and item rewards.' },
            { icon: Scroll, name: 'Quest System', description: 'Accept quests from NPCs, track objectives, earn rewards.' },
        ],
    },
    {
        title: 'Location Services',
        icon: MapPin,
        description: 'Every settlement offers services. What\'s available depends on where you are.',
        items: [
            { icon: Store, name: 'Market', description: 'Buy and sell goods. Player-driven economy with location-specific prices.' },
            { icon: Landmark, name: 'Bank', description: 'Store gold safely. Your wealth stays where you deposit it.' },
            { icon: HeartPulse, name: 'Healer', description: 'Cure diseases, heal wounds. Different locations have different healers.' },
            { icon: Sparkles, name: 'Shrine', description: 'Pray to the gods, receive blessings that grant temporary bonuses.' },
            { icon: Home, name: 'Tavern', description: 'Rest to restore energy, hear local rumors, socialize with travelers.' },
            { icon: Ship, name: 'Port', description: 'Available at coastal settlements. Travel by sea to distant ports.' },
        ],
    },
    {
        title: 'Gathering & Crafting',
        icon: Pickaxe,
        description: 'Harvest resources and create items to sell or use.',
        items: [
            { icon: Pickaxe, name: 'Mining', description: 'Dig for ore, stone, gems, and coal in village mining spots.' },
            { icon: Anchor, name: 'Fishing', description: 'Catch fish for cooking or selling. Different locations yield different catches.' },
            { icon: Axe, name: 'Woodcutting', description: 'Chop trees for lumber. Essential for construction and crafting.' },
            { icon: Wheat, name: 'Farming', description: 'Plant crops, tend fields, harvest when ready. Seasonal mechanics.' },
            { icon: Hammer, name: 'Crafting', description: 'Create weapons, armor, tools, and goods from raw materials.' },
        ],
    },
    {
        title: 'Combat & Adventure',
        icon: Swords,
        description: 'Fight monsters, explore dungeons, and prove your worth in battle.',
        items: [
            { icon: Swords, name: 'Combat System', description: 'Turn-based combat using Attack, Strength, Defense, Range, and Hitpoints.' },
            { icon: Trees, name: 'Wilderness Encounters', description: 'Random encounters while traveling through the wild.' },
            { icon: Castle, name: 'Dungeons', description: 'Multi-floor dungeons with monsters, puzzles, and boss fights. Lose items on death.' },
            { icon: Trophy, name: 'Tournaments', description: 'Compete in arena brackets for glory, prizes, and renown.' },
        ],
    },
    {
        title: 'Economy & Work',
        icon: Coins,
        description: 'Earn wages, run businesses, and trade across the realm.',
        items: [
            { icon: Coins, name: 'Jobs System', description: '100+ jobs across all settlements. Work for wages, gain XP, collect resources.' },
            { icon: Store, name: 'Businesses', description: 'Own shops, taverns, or workshops. Hire employees, set prices, earn profits.' },
            { icon: Ship, name: 'Trade Caravans', description: 'Send goods between settlements. Profit from price differences.' },
            { icon: Scale, name: 'Tariffs', description: 'Rulers can set tariffs on trade passing through their territory.' },
        ],
    },
    {
        title: 'Social & Dynasty',
        icon: Users,
        description: 'Build relationships, found dynasties, and leave a legacy.',
        items: [
            { icon: Users, name: 'Guilds', description: 'Join or found craft guilds. Work together, share resources, gain bonuses.' },
            { icon: Crown, name: 'Dynasties', description: 'Found a dynasty, manage family members, plan succession.' },
            { icon: Heart, name: 'Marriage', description: 'Propose marriage, form alliances, produce heirs.' },
            { icon: Scroll, name: 'Succession', description: 'Name heirs, manage inheritance. Your legacy continues after death.' },
            { icon: Sparkles, name: 'Religion', description: 'Follow or found religions. Religions grant bonuses and political influence.' },
        ],
    },
    {
        title: 'Governance & Politics',
        icon: Crown,
        description: 'Every position is player-held. From village elder to king.',
        items: [
            { icon: Home, name: 'Village Roles', description: 'Elder, Blacksmith, Healer, Merchant, and more. Each manages different aspects.' },
            { icon: Church, name: 'Town Government', description: 'Elected Mayor oversees guilds, markets, and town affairs.' },
            { icon: Castle, name: 'Barony', description: 'Baron collects taxes, judges crimes, raises militia.' },
            { icon: Crown, name: 'Kingdom', description: 'Elected King declares wars, sets realm taxes, rules the land.' },
            { icon: Vote, name: 'Elections', description: 'All positions are elected. Campaign, vote, win or lose.' },
            { icon: Gavel, name: 'No Confidence', description: 'Remove corrupt officials through no-confidence votes.' },
        ],
    },
    {
        title: 'Justice & Law',
        icon: Gavel,
        description: 'A full legal system with crimes, trials, and punishments.',
        items: [
            { icon: Scale, name: 'Crimes', description: 'Murder, theft, assault, tax evasion, treason, and more.' },
            { icon: Gavel, name: 'Accusations', description: 'Accuse others of crimes. Provide evidence, wait for trial.' },
            { icon: Users, name: 'Trials', description: 'Judges hear cases, review evidence, render verdicts.' },
            { icon: Shield, name: 'Punishments', description: 'Fines, jail time, exile, execution. Severity matches the crime.' },
            { icon: Coins, name: 'Bounties', description: 'Post bounties on criminals. Hunters collect rewards.' },
        ],
    },
    {
        title: 'Warfare',
        icon: Shield,
        description: 'Raise armies, declare wars, and conquer territories.',
        items: [
            { icon: Shield, name: 'Armies', description: 'Raise and command armies. Recruit soldiers, supply equipment.' },
            { icon: Swords, name: 'Battles', description: 'Fight pitched battles. Tactics, terrain, and numbers matter.' },
            { icon: Castle, name: 'Sieges', description: 'Besiege castles and settlements. Starve them out or storm the walls.' },
            { icon: Scroll, name: 'Peace Treaties', description: 'Negotiate peace. Demand territory, gold, or prisoners.' },
        ],
    },
    {
        title: 'World & Travel',
        icon: MapPin,
        description: 'A persistent world with seasons, disasters, and realistic travel.',
        items: [
            { icon: MapPin, name: 'World Map', description: '4 kingdoms, multiple baronies, towns, and villages to explore.' },
            { icon: Trees, name: 'Wilderness', description: 'Travel between settlements through forests, mountains, and plains.' },
            { icon: Ship, name: 'Sea Travel', description: 'Board ships at ports for faster long-distance travel.' },
            { icon: Building, name: 'Settlement Founding', description: 'Petition for a charter, gather settlers, found new villages.' },
            { icon: Calendar, name: 'Seasons', description: '4 seasons affect farming, travel speed, and combat.' },
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
                                        <div className="rounded-lg bg-primary/10 p-3">
                                            <category.icon className="h-6 w-6 text-primary" />
                                        </div>
                                        <div>
                                            <h2 className="font-[Cinzel] text-2xl font-bold text-foreground">
                                                {category.title}
                                            </h2>
                                            <p className="text-muted-foreground">{category.description}</p>
                                        </div>
                                    </div>

                                    {/* Feature Items Grid */}
                                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                                        {category.items.map((item) => (
                                            <div
                                                key={item.name}
                                                className="rounded-xl border border-border/50 bg-card/50 p-5 transition hover:border-primary/30"
                                            >
                                                <div className="mb-3 flex items-center gap-3">
                                                    <div className="rounded-lg bg-primary/10 p-2">
                                                        <item.icon className="h-4 w-4 text-primary" />
                                                    </div>
                                                    <h3 className="font-semibold text-foreground">{item.name}</h3>
                                                </div>
                                                <p className="text-sm text-muted-foreground">{item.description}</p>
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
                            <p className="text-xs text-muted-foreground">
                                A medieval world where your choices matter.
                            </p>
                        </div>
                    </div>
                </footer>
            </div>
        </>
    );
}
