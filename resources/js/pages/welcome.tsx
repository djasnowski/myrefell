import { Head, Link, usePage } from "@inertiajs/react";
import {
    Anchor,
    Baby,
    Calendar,
    Castle,
    Church,
    CloudRain,
    Coins,
    Crown,
    Eye,
    Flame,
    FlaskConical,
    Footprints,
    Hammer,
    Heart,
    HeartPulse,
    Home,
    Mail,
    MapPin,
    Pickaxe,
    Scale,
    Scroll,
    Shield,
    Ship,
    Skull,
    Sparkles,
    Sun,
    Swords,
    Target,
    Trees,
    Trophy,
    Users,
    Vote,
    Wheat,
} from "lucide-react";
import { dashboard, login, register } from "@/routes";
import type { SharedData } from "@/types";

function DecorativeDivider() {
    return (
        <div className="flex items-center gap-3 w-full my-8">
            <div className="flex-1 h-px bg-gradient-to-r from-transparent via-border to-transparent" />
            <div className="w-2 h-2 rotate-45 border border-primary/40 bg-primary/10" />
            <div className="flex-1 h-px bg-gradient-to-r from-transparent via-border to-transparent" />
        </div>
    );
}

function FramedCard({
    children,
    className = "",
}: {
    children: React.ReactNode;
    className?: string;
}) {
    return (
        <div className="relative">
            {/* Corner decorations */}
            <div className="absolute -top-2 -left-2 w-6 h-6 border-t-2 border-l-2 border-primary/60" />
            <div className="absolute -top-2 -right-2 w-6 h-6 border-t-2 border-r-2 border-primary/60" />
            <div className="absolute -bottom-2 -left-2 w-6 h-6 border-b-2 border-l-2 border-primary/60" />
            <div className="absolute -bottom-2 -right-2 w-6 h-6 border-b-2 border-r-2 border-primary/60" />
            <div
                className={`relative bg-card/80 backdrop-blur-sm border border-border/50 p-8 shadow-lg shadow-primary/5 ${className}`}
            >
                {children}
            </div>
        </div>
    );
}

export default function Welcome({ canRegister = true }: { canRegister?: boolean }) {
    const { auth, online_count } = usePage<SharedData>().props;

    return (
        <>
            <Head title="Myrefell - Medieval World MMO">
                <meta
                    name="description"
                    content="Rise from peasant to king in Myrefell, a persistent medieval world MMO. Train 12 skills, climb the social ladder, wage wars, found dynasties, and shape history through politics and faith."
                />
                <link rel="canonical" href="https://myrefell.com/" />
                <link rel="preconnect" href="https://fonts.bunny.net" />
                <link
                    href="https://fonts.bunny.net/css?family=cinzel:400,700&family=inter:400,500,600"
                    rel="stylesheet"
                />
            </Head>

            <div className="relative min-h-screen bg-background text-foreground overflow-hidden">
                {/* Decorative background */}
                <div className="fixed inset-0 bg-background">
                    <div className="absolute inset-0 bg-[radial-gradient(ellipse_at_top,_var(--tw-gradient-stops))] from-primary/10 via-background to-background" />
                    <div className="absolute inset-0 bg-[radial-gradient(ellipse_at_bottom_right,_var(--tw-gradient-stops))] from-accent/5 via-transparent to-transparent" />
                </div>

                {/* Subtle grid pattern overlay */}
                <div
                    className="fixed inset-0 opacity-[0.03]"
                    style={{
                        backgroundImage: `
                            linear-gradient(var(--primary) 1px, transparent 1px),
                            linear-gradient(90deg, var(--primary) 1px, transparent 1px)
                        `,
                        backgroundSize: "40px 40px",
                    }}
                />

                {/* Navigation */}
                <nav className="fixed top-0 z-50 w-full border-b border-border/50 bg-background/90 backdrop-blur-sm">
                    <div className="mx-auto flex max-w-7xl items-center justify-between px-6 py-4">
                        <Link href="/" className="flex items-center gap-2">
                            <Crown className="h-8 w-8 text-primary" />
                            <span className="font-[Cinzel] text-2xl font-bold tracking-wide text-primary">
                                Myrefell
                            </span>
                        </Link>
                        <div className="flex items-center gap-4">
                            <Link
                                href="/features"
                                className="px-4 py-2 text-muted-foreground transition hover:text-primary"
                            >
                                Features
                            </Link>
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
                                            Begin Your Journey
                                        </Link>
                                    )}
                                </>
                            )}
                        </div>
                    </div>
                </nav>

                {/* Hero Section */}
                <section className="relative flex min-h-screen items-center justify-center pt-20">
                    <div className="relative z-10 mx-auto max-w-5xl px-6 text-center">
                        <div className="mb-6 inline-flex items-center gap-2 rounded-full border border-primary/30 bg-primary/10 px-4 py-2 text-sm text-primary">
                            <Crown className="h-4 w-4" />
                            <span>A Living Medieval World</span>
                        </div>

                        <h1 className="mb-6 font-[Cinzel] text-5xl font-bold leading-tight text-foreground md:text-7xl">
                            Rise from Peasant
                            <br />
                            <span className="text-primary">to King</span>
                        </h1>

                        <p className="mx-auto mb-10 max-w-2xl text-lg leading-relaxed text-muted-foreground md:text-xl">
                            In Myrefell, you are one person in a persistent medieval world. Train
                            your combat skills, master crafts, climb the social ladder, and shape
                            history through politics, war, and faith.
                        </p>

                        <div className="flex flex-col items-center justify-center gap-4 sm:flex-row">
                            {auth.user ? (
                                <Link
                                    href={dashboard()}
                                    className="inline-flex items-center gap-2 rounded-lg bg-primary px-8 py-4 text-lg font-semibold text-primary-foreground transition hover:bg-primary/90"
                                >
                                    <MapPin className="h-5 w-5" />
                                    Enter the World
                                </Link>
                            ) : (
                                <>
                                    <Link
                                        href={register()}
                                        className="inline-flex items-center gap-2 rounded-lg bg-primary px-8 py-4 text-lg font-semibold text-primary-foreground transition hover:bg-primary/90"
                                    >
                                        <Scroll className="h-5 w-5" />
                                        Create Your Character
                                    </Link>
                                    <Link
                                        href={login()}
                                        className="inline-flex items-center gap-2 rounded-lg border-2 border-border px-8 py-4 text-lg font-semibold text-muted-foreground transition hover:border-primary hover:text-primary"
                                    >
                                        Continue Your Story
                                    </Link>
                                </>
                            )}
                        </div>

                        {/* Quick Stats */}
                        <div className="mt-16 grid grid-cols-2 gap-4 md:grid-cols-4">
                            {[
                                { value: "4", label: "Kingdoms" },
                                { value: "16", label: "Skills to Master" },
                                { value: "5", label: "Social Classes" },
                                { value: "∞", label: "Possibilities" },
                            ].map((stat) => (
                                <div
                                    key={stat.label}
                                    className="rounded-xl border border-border/50 bg-card/50 backdrop-blur-sm p-4"
                                >
                                    <div className="text-3xl font-bold text-primary">
                                        {stat.value}
                                    </div>
                                    <div className="text-sm text-muted-foreground">
                                        {stat.label}
                                    </div>
                                </div>
                            ))}
                        </div>

                        {/* Players Online */}
                        {online_count !== undefined && online_count > 0 && (
                            <div className="mt-6 flex justify-center">
                                <div className="inline-flex items-center gap-3 rounded-xl border border-green-500/30 bg-green-900/20 backdrop-blur-sm px-6 py-3">
                                    <span className="relative flex h-3 w-3">
                                        <span className="absolute inline-flex h-full w-full animate-ping rounded-full bg-green-400 opacity-75"></span>
                                        <span className="relative inline-flex h-3 w-3 rounded-full bg-green-500"></span>
                                    </span>
                                    <span className="font-pixel text-lg text-green-300">
                                        {online_count} {online_count === 1 ? "player" : "players"}{" "}
                                        online
                                    </span>
                                </div>
                            </div>
                        )}
                    </div>

                    {/* Scroll Indicator */}
                    <div className="absolute bottom-8 left-1/2 -translate-x-1/2 animate-bounce">
                        <div className="h-12 w-6 rounded-full border-2 border-border">
                            <div className="mx-auto mt-2 h-3 w-1 rounded-full bg-primary" />
                        </div>
                    </div>
                </section>

                {/* The Dual Nature */}
                <section className="relative border-y border-border/50 bg-card/30 backdrop-blur-sm py-24">
                    <div className="mx-auto max-w-7xl px-6">
                        <div className="mb-16 text-center">
                            <h2 className="mb-4 font-[Cinzel] text-3xl font-bold text-foreground md:text-4xl">
                                One Person, <span className="text-primary">Infinite Paths</span>
                            </h2>
                            <p className="mx-auto max-w-2xl text-muted-foreground">
                                You are both an individual with a body to train and a citizen who
                                shapes society.
                            </p>
                        </div>

                        <div className="grid gap-8 md:grid-cols-2">
                            {/* The Individual */}
                            <FramedCard>
                                <div className="mb-6 flex items-center gap-3">
                                    <div className="rounded-lg bg-destructive/20 p-3">
                                        <Swords className="h-6 w-6 text-destructive" />
                                    </div>
                                    <h3 className="font-[Cinzel] text-2xl font-bold">
                                        The Individual
                                    </h3>
                                </div>
                                <p className="mb-6 text-muted-foreground">
                                    Train your body, master crafts, and equip yourself for
                                    adventure.
                                </p>
                                <ul className="space-y-3">
                                    <li className="flex items-center gap-3 text-foreground/80">
                                        <Shield className="h-5 w-5 text-muted-foreground" />
                                        <span>
                                            5 combat skills: Attack, Strength, Defense, Range,
                                            Hitpoints
                                        </span>
                                    </li>
                                    <li className="flex items-center gap-3 text-foreground/80">
                                        <Hammer className="h-5 w-5 text-muted-foreground" />
                                        <span>
                                            7 trade skills: Farming, Mining, Fishing, and more
                                        </span>
                                    </li>
                                    <li className="flex items-center gap-3 text-foreground/80">
                                        <Heart className="h-5 w-5 text-muted-foreground" />
                                        <span>Health, energy, equipment, and inventory</span>
                                    </li>
                                </ul>
                            </FramedCard>

                            {/* The Citizen */}
                            <FramedCard>
                                <div className="mb-6 flex items-center gap-3">
                                    <div className="rounded-lg bg-primary/20 p-3">
                                        <Crown className="h-6 w-6 text-primary" />
                                    </div>
                                    <h3 className="font-[Cinzel] text-2xl font-bold">
                                        The Citizen
                                    </h3>
                                </div>
                                <p className="mb-6 text-muted-foreground">
                                    Vote, hold office, own property, and shape your community.
                                </p>
                                <ul className="space-y-3">
                                    <li className="flex items-center gap-3 text-foreground/80">
                                        <Vote className="h-5 w-5 text-muted-foreground" />
                                        <span>Democratic elections for all positions</span>
                                    </li>
                                    <li className="flex items-center gap-3 text-foreground/80">
                                        <Scale className="h-5 w-5 text-muted-foreground" />
                                        <span>Courts, laws, and justice systems</span>
                                    </li>
                                    <li className="flex items-center gap-3 text-foreground/80">
                                        <Users className="h-5 w-5 text-muted-foreground" />
                                        <span>Guilds, businesses, and trade</span>
                                    </li>
                                </ul>
                            </FramedCard>
                        </div>
                    </div>
                </section>

                {/* World Hierarchy */}
                <section className="relative py-24">
                    <div className="mx-auto max-w-7xl px-6">
                        <div className="mb-16 text-center">
                            <h2 className="mb-4 font-[Cinzel] text-3xl font-bold text-foreground md:text-4xl">
                                A <span className="text-primary">Living World</span> to Explore
                            </h2>
                            <p className="mx-auto max-w-2xl text-muted-foreground">
                                From humble hamlets to mighty kingdoms, every settlement is
                                player-governed with real consequences.
                            </p>
                        </div>

                        <div className="grid gap-6 md:grid-cols-2 lg:grid-cols-4">
                            {[
                                {
                                    icon: Crown,
                                    name: "Kingdom",
                                    ruler: "Elected King",
                                    desc: "Declare wars, set taxes, rule the realm",
                                },
                                {
                                    icon: Castle,
                                    name: "Barony",
                                    ruler: "Baron",
                                    desc: "Collect taxes, raise militia, judge crimes",
                                },
                                {
                                    icon: Church,
                                    name: "Town",
                                    ruler: "Elected Mayor",
                                    desc: "Manage guilds, markets, town affairs",
                                },
                                {
                                    icon: Home,
                                    name: "Village",
                                    ruler: "Elected Elder",
                                    desc: "Local disputes, approve migrants",
                                },
                            ].map((loc) => (
                                <div
                                    key={loc.name}
                                    className="group rounded-xl border border-border/50 bg-card/50 backdrop-blur-sm p-6 transition hover:border-primary/50"
                                >
                                    <div className="mb-4 inline-flex rounded-lg bg-primary/10 p-3">
                                        <loc.icon className="h-6 w-6 text-primary" />
                                    </div>
                                    <h3 className="mb-1 font-[Cinzel] text-xl font-bold text-foreground">
                                        {loc.name}
                                    </h3>
                                    <p className="mb-2 text-sm text-primary">{loc.ruler}</p>
                                    <p className="text-sm text-muted-foreground">{loc.desc}</p>
                                </div>
                            ))}
                        </div>
                    </div>
                </section>

                <DecorativeDivider />

                {/* Paths to Power */}
                <section className="relative py-24">
                    <div className="mx-auto max-w-7xl px-6">
                        <div className="mb-16 text-center">
                            <h2 className="mb-4 font-[Cinzel] text-3xl font-bold text-foreground md:text-4xl">
                                Choose Your <span className="text-primary">Path to Power</span>
                            </h2>
                            <p className="mx-auto max-w-2xl text-muted-foreground">
                                There are no classes in Myrefell. Forge your own destiny through the
                                path that calls to you.
                            </p>
                        </div>

                        <div className="grid gap-6 md:grid-cols-2 lg:grid-cols-4">
                            {[
                                {
                                    icon: Coins,
                                    title: "Economic Magnate",
                                    desc: "Own businesses, run caravans, set tariffs, and dominate trade.",
                                },
                                {
                                    icon: Swords,
                                    title: "Military Commander",
                                    desc: "Raise armies, declare wars, siege castles, and conquer kingdoms.",
                                },
                                {
                                    icon: Crown,
                                    title: "Political Leader",
                                    desc: "Win elections, hold office, climb from Elder to King.",
                                },
                                {
                                    icon: Church,
                                    title: "Religious Prophet",
                                    desc: "Found a cult, grow it into a religion, become spiritual leader.",
                                },
                                {
                                    icon: Skull,
                                    title: "Criminal Mastermind",
                                    desc: "Build an underworld empire and control the shadows.",
                                },
                                {
                                    icon: Users,
                                    title: "Dynasty Builder",
                                    desc: "Marry strategically, raise heirs, build a lasting legacy.",
                                },
                                {
                                    icon: Ship,
                                    title: "Trade Baron",
                                    desc: "Send caravans across the realm and profit from tariffs.",
                                },
                                {
                                    icon: Trophy,
                                    title: "Tournament Champion",
                                    desc: "Compete in tournaments, win glory, and earn renown.",
                                },
                            ].map((path) => (
                                <div
                                    key={path.title}
                                    className="rounded-xl border border-border/50 bg-card/50 backdrop-blur-sm p-6 transition hover:border-primary/50"
                                >
                                    <div className="mb-4 inline-flex rounded-lg bg-primary/10 p-3">
                                        <path.icon className="h-6 w-6 text-primary" />
                                    </div>
                                    <h3 className="mb-2 font-[Cinzel] text-lg font-bold text-foreground">
                                        {path.title}
                                    </h3>
                                    <p className="text-sm text-muted-foreground">{path.desc}</p>
                                </div>
                            ))}
                        </div>
                    </div>
                </section>

                {/* Living Simulation */}
                <section className="relative border-y border-border/50 bg-card/30 backdrop-blur-sm py-24">
                    <div className="mx-auto max-w-7xl px-6">
                        <div className="mb-16 text-center">
                            <h2 className="mb-4 font-[Cinzel] text-3xl font-bold text-foreground md:text-4xl">
                                A World That <span className="text-primary">Lives Without You</span>
                            </h2>
                            <p className="mx-auto max-w-2xl text-muted-foreground">
                                Unlike other games, Myrefell's world doesn't pause. NPCs live full
                                lives, information travels realistically, and seasons change the
                                landscape.
                            </p>
                        </div>

                        <div className="grid gap-8 md:grid-cols-3">
                            {/* Living NPCs */}
                            <FramedCard>
                                <div className="mb-6 flex items-center gap-3">
                                    <div className="rounded-lg bg-accent/20 p-3">
                                        <Users className="h-6 w-6 text-accent-foreground" />
                                    </div>
                                    <h3 className="font-[Cinzel] text-xl font-bold">Living NPCs</h3>
                                </div>
                                <p className="mb-6 text-muted-foreground">
                                    NPCs aren't static vendors. They're people with lives of their
                                    own.
                                </p>
                                <ul className="space-y-3">
                                    <li className="flex items-center gap-3 text-foreground/80">
                                        <Baby className="h-5 w-5 text-muted-foreground" />
                                        <span>Born, age, marry, have children, die</span>
                                    </li>
                                    <li className="flex items-center gap-3 text-foreground/80">
                                        <Hammer className="h-5 w-5 text-muted-foreground" />
                                        <span>Hold jobs and fill vacant roles</span>
                                    </li>
                                    <li className="flex items-center gap-3 text-foreground/80">
                                        <MapPin className="h-5 w-5 text-muted-foreground" />
                                        <span>Migrate to find work or family</span>
                                    </li>
                                </ul>
                            </FramedCard>

                            {/* Information Flow */}
                            <FramedCard>
                                <div className="mb-6 flex items-center gap-3">
                                    <div className="rounded-lg bg-accent/20 p-3">
                                        <Mail className="h-6 w-6 text-accent-foreground" />
                                    </div>
                                    <h3 className="font-[Cinzel] text-xl font-bold">
                                        Slow Information
                                    </h3>
                                </div>
                                <p className="mb-6 text-muted-foreground">
                                    No global chat. News travels at the speed of messengers.
                                </p>
                                <ul className="space-y-3">
                                    <li className="flex items-center gap-3 text-foreground/80">
                                        <Users className="h-5 w-5 text-muted-foreground" />
                                        <span>Local chat only in your settlement</span>
                                    </li>
                                    <li className="flex items-center gap-3 text-foreground/80">
                                        <Scroll className="h-5 w-5 text-muted-foreground" />
                                        <span>Distant news may be delayed or false</span>
                                    </li>
                                    <li className="flex items-center gap-3 text-foreground/80">
                                        <Eye className="h-5 w-5 text-muted-foreground" />
                                        <span>Spies gather intelligence faster</span>
                                    </li>
                                </ul>
                            </FramedCard>

                            {/* Dynamic World */}
                            <FramedCard>
                                <div className="mb-6 flex items-center gap-3">
                                    <div className="rounded-lg bg-accent/20 p-3">
                                        <Calendar className="h-6 w-6 text-accent-foreground" />
                                    </div>
                                    <h3 className="font-[Cinzel] text-xl font-bold">
                                        Dynamic World
                                    </h3>
                                </div>
                                <p className="mb-6 text-muted-foreground">
                                    Seasons change, disasters strike, diseases spread.
                                </p>
                                <ul className="space-y-3">
                                    <li className="flex items-center gap-3 text-foreground/80">
                                        <Sun className="h-5 w-5 text-muted-foreground" />
                                        <span>Seasons affect travel, farming, combat</span>
                                    </li>
                                    <li className="flex items-center gap-3 text-foreground/80">
                                        <CloudRain className="h-5 w-5 text-muted-foreground" />
                                        <span>Droughts, floods, fires, earthquakes</span>
                                    </li>
                                    <li className="flex items-center gap-3 text-foreground/80">
                                        <HeartPulse className="h-5 w-5 text-muted-foreground" />
                                        <span>Plagues spread through trade routes</span>
                                    </li>
                                </ul>
                            </FramedCard>
                        </div>
                    </div>
                </section>

                {/* Skills & Activities */}
                <section className="relative py-24">
                    <div className="mx-auto max-w-7xl px-6">
                        <div className="mb-16 text-center">
                            <h2 className="mb-4 font-[Cinzel] text-3xl font-bold text-foreground md:text-4xl">
                                Master <span className="text-primary">16 Skills</span>
                            </h2>
                            <p className="mx-auto max-w-2xl text-muted-foreground">
                                Every skill opens new opportunities. Train combat, mine ore, forge
                                weapons, and craft your legacy.
                            </p>
                        </div>

                        <div className="grid grid-cols-4 gap-4 md:grid-cols-4 lg:grid-cols-8">
                            {[
                                { icon: Swords, name: "Attack" },
                                { icon: Shield, name: "Strength" },
                                { icon: Heart, name: "Defense" },
                                { icon: HeartPulse, name: "Hitpoints" },
                                { icon: Target, name: "Range" },
                                { icon: Sparkles, name: "Prayer" },
                                { icon: Wheat, name: "Farming" },
                                { icon: Pickaxe, name: "Mining" },
                                { icon: Anchor, name: "Fishing" },
                                { icon: Trees, name: "Woodcutting" },
                                { icon: Flame, name: "Cooking" },
                                { icon: Hammer, name: "Smithing" },
                                { icon: Hammer, name: "Crafting" },
                                { icon: FlaskConical, name: "Herblore" },
                                { icon: Eye, name: "Thieving" },
                                { icon: Footprints, name: "Agility" },
                            ].map((skill) => (
                                <div
                                    key={skill.name}
                                    className="flex flex-col items-center rounded-xl border border-border/50 bg-card/50 backdrop-blur-sm p-4 transition hover:border-primary/50"
                                >
                                    <div className="mb-2 rounded-lg bg-primary/10 p-2">
                                        <skill.icon className="h-5 w-5 text-primary" />
                                    </div>
                                    <span className="text-xs text-muted-foreground">
                                        {skill.name}
                                    </span>
                                </div>
                            ))}
                        </div>
                    </div>
                </section>

                {/* Social Classes */}
                <section className="relative border-y border-border/50 bg-card/30 backdrop-blur-sm py-24">
                    <div className="mx-auto max-w-7xl px-6">
                        <div className="mb-16 text-center">
                            <h2 className="mb-4 font-[Cinzel] text-3xl font-bold text-foreground md:text-4xl">
                                Climb the <span className="text-primary">Social Ladder</span>
                            </h2>
                            <p className="mx-auto max-w-2xl text-muted-foreground">
                                Your class determines your rights. Earn freedom, gain citizenship,
                                or achieve nobility through deeds.
                            </p>
                        </div>

                        <div className="flex flex-wrap justify-center gap-4">
                            {[
                                {
                                    name: "Serf",
                                    desc: "Bound to the land",
                                    rights: "Basic protection only",
                                },
                                {
                                    name: "Freeman",
                                    desc: "Free citizen",
                                    rights: "Vote, own property, travel",
                                },
                                {
                                    name: "Burgher",
                                    desc: "Town citizen",
                                    rights: "Guild membership, trade",
                                },
                                {
                                    name: "Noble",
                                    desc: "Aristocracy",
                                    rights: "Hold high office, own land",
                                },
                                {
                                    name: "Clergy",
                                    desc: "Religious order",
                                    rights: "Church authority",
                                },
                            ].map((cls, i) => (
                                <div
                                    key={cls.name}
                                    className="w-40 rounded-xl border border-border/50 bg-card/50 backdrop-blur-sm p-4 text-center"
                                >
                                    <div className="mb-2 text-2xl font-bold text-primary">
                                        {i + 1}
                                    </div>
                                    <h3 className="font-[Cinzel] font-bold text-foreground">
                                        {cls.name}
                                    </h3>
                                    <p className="text-xs text-primary">{cls.desc}</p>
                                    <p className="mt-2 text-xs text-muted-foreground">
                                        {cls.rights}
                                    </p>
                                </div>
                            ))}
                        </div>
                    </div>
                </section>

                {/* Features Grid */}
                <section className="relative py-24">
                    <div className="mx-auto max-w-7xl px-6">
                        <div className="mb-16 text-center">
                            <h2 className="mb-4 font-[Cinzel] text-3xl font-bold text-foreground md:text-4xl">
                                What Makes Myrefell <span className="text-primary">Different</span>
                            </h2>
                        </div>

                        <div className="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                            {[
                                {
                                    title: "Full Warfare System",
                                    desc: "Raise armies, declare wars, siege settlements, fight battles. Negotiate peace with territory transfers and gold demands.",
                                },
                                {
                                    title: "Dynasty & Marriage",
                                    desc: "Found dynasties, arrange marriages, manage succession. Build a family tree that spans generations.",
                                },
                                {
                                    title: "Trade Caravans",
                                    desc: "Send caravans across the realm, set tariffs on routes through your territory, profit from trade.",
                                },
                                {
                                    title: "Festivals & Tournaments",
                                    desc: "Attend festivals as performer or vendor, compete in tournament brackets for glory and prizes.",
                                },
                                {
                                    title: "Building Construction",
                                    desc: "Construct buildings in your settlements, manage repairs, expand your infrastructure.",
                                },
                                {
                                    title: "Real Elections",
                                    desc: "Every government position is elected by players. Campaign for office or stage a no-confidence vote.",
                                },
                                {
                                    title: "Meaningful Law",
                                    desc: "Commit crimes, face trials, receive punishments. Courts with judges, evidence, and verdicts.",
                                },
                                {
                                    title: "Player-Founded Religions",
                                    desc: "Start a cult, grow it into a religion, define its beliefs. Religions grant bonuses and political power.",
                                },
                                {
                                    title: "Dungeons & Bosses",
                                    desc: "Explore multi-floor dungeons, fight monsters and bosses for loot. Die and lose everything you found.",
                                },
                                {
                                    title: "Horses & Travel",
                                    desc: "Buy and stable horses to travel faster. Manage their stamina or leave them at the stable to rest.",
                                },
                                {
                                    title: "Local Banking",
                                    desc: "Your wealth is where you leave it. Gold stored in one town stays there until you return.",
                                },
                                {
                                    title: "Political Legitimacy",
                                    desc: "Rulers gain and lose legitimacy. Scandals, lost wars, and church opposition can trigger rebellion.",
                                },
                                {
                                    title: "Food & Famine",
                                    desc: "Villages must maintain food stockpiles. Shortages cause starvation, emigration, and death.",
                                },
                                {
                                    title: "Found Settlements",
                                    desc: "Petition the King for a charter, gather signatories, and found your own village from nothing.",
                                },
                                {
                                    title: "Earn Your Legacy",
                                    desc: "Every title, every conquest, every alliance — built through your own skill, strategy, and determination.",
                                },
                            ].map((feature) => (
                                <div
                                    key={feature.title}
                                    className="rounded-xl border border-border/50 bg-card/50 backdrop-blur-sm p-6"
                                >
                                    <h3 className="mb-2 font-[Cinzel] text-lg font-bold text-primary">
                                        {feature.title}
                                    </h3>
                                    <p className="text-sm text-muted-foreground">{feature.desc}</p>
                                </div>
                            ))}
                        </div>
                    </div>
                </section>

                {/* Daily Loop */}
                <section className="relative border-y border-border/50 bg-card/30 backdrop-blur-sm py-24">
                    <div className="mx-auto max-w-7xl px-6">
                        <div className="mb-16 text-center">
                            <h2 className="mb-4 font-[Cinzel] text-3xl font-bold text-foreground md:text-4xl">
                                Your <span className="text-primary">Daily Life</span>
                            </h2>
                            <p className="mx-auto max-w-2xl text-muted-foreground">
                                A few minutes each day to train, work, and engage with your
                                community.
                            </p>
                        </div>

                        <div className="mx-auto max-w-3xl">
                            <div className="space-y-4">
                                {[
                                    {
                                        time: "Morning",
                                        activity: "Train your combat stats at the training grounds",
                                        icon: Swords,
                                    },
                                    {
                                        time: "Midday",
                                        activity: "Work your job for wages or run your business",
                                        icon: Coins,
                                    },
                                    {
                                        time: "Afternoon",
                                        activity: "Complete 3 daily tasks for bonus rewards",
                                        icon: Scroll,
                                    },
                                    {
                                        time: "Evening",
                                        activity:
                                            "Check local news, chat with neighbors, vote in elections",
                                        icon: Users,
                                    },
                                ].map((item, i) => (
                                    <div
                                        key={item.time}
                                        className="flex items-center gap-4 rounded-xl border border-border/50 bg-card/50 backdrop-blur-sm p-4"
                                    >
                                        <div className="flex h-10 w-10 items-center justify-center rounded-full bg-primary/20 text-lg font-bold text-primary">
                                            {i + 1}
                                        </div>
                                        <div className="flex-1">
                                            <div className="text-sm text-primary">{item.time}</div>
                                            <div className="text-foreground/80">
                                                {item.activity}
                                            </div>
                                        </div>
                                        <item.icon className="h-5 w-5 text-muted-foreground" />
                                    </div>
                                ))}
                            </div>
                        </div>
                    </div>
                </section>

                {/* CTA Section */}
                <section className="relative py-24">
                    <div className="mx-auto max-w-4xl px-6 text-center">
                        <Crown className="mx-auto mb-6 h-16 w-16 text-primary" />
                        <h2 className="mb-4 font-[Cinzel] text-3xl font-bold text-foreground md:text-4xl">
                            Your Story Begins Now
                        </h2>
                        <p className="mb-8 text-lg text-muted-foreground">
                            Every king started as a peasant. Every dynasty began with one person.
                            <br />
                            What will your legacy be?
                        </p>
                        {auth.user ? (
                            <Link
                                href={dashboard()}
                                className="inline-flex items-center gap-2 rounded-lg bg-primary px-10 py-4 text-xl font-semibold text-primary-foreground transition hover:bg-primary/90"
                            >
                                <MapPin className="h-6 w-6" />
                                Enter Myrefell
                            </Link>
                        ) : (
                            <Link
                                href={register()}
                                className="inline-flex items-center gap-2 rounded-lg bg-primary px-10 py-4 text-xl font-semibold text-primary-foreground transition hover:bg-primary/90"
                            >
                                <Scroll className="h-6 w-6" />
                                Create Your Character
                            </Link>
                        )}

                        {/* Bottom decorative element */}
                        <div className="mt-12 flex items-center justify-center gap-2 text-muted-foreground/50">
                            <div className="w-8 h-px bg-gradient-to-r from-transparent to-border" />
                            <div className="w-1.5 h-1.5 rotate-45 bg-primary/30" />
                            <div className="w-8 h-px bg-gradient-to-l from-transparent to-border" />
                        </div>
                    </div>
                </section>

                {/* Footer */}
                <footer className="relative border-t-2 border-primary/30 bg-card/60 backdrop-blur-sm pt-16 pb-8">
                    <div className="mx-auto max-w-7xl px-6">
                        <div className="grid gap-10 md:grid-cols-2 lg:grid-cols-4 mb-12">
                            {/* Brand */}
                            <div>
                                <div className="flex items-center gap-2 mb-4">
                                    <Crown className="h-7 w-7 text-primary" />
                                    <span className="font-[Cinzel] text-xl font-bold text-foreground">
                                        Myrefell
                                    </span>
                                </div>
                                <p className="text-sm text-muted-foreground leading-relaxed mb-4">
                                    A persistent medieval world where every choice matters. Rise
                                    from peasant to king through skill, strategy, and diplomacy.
                                </p>
                                <p className="text-xs text-muted-foreground/60">
                                    Your legacy is earned.
                                </p>
                            </div>

                            {/* Game */}
                            <div>
                                <h4 className="font-[Cinzel] font-bold text-foreground mb-4">
                                    Game
                                </h4>
                                <ul className="space-y-2">
                                    <li>
                                        <Link
                                            href="/features"
                                            className="text-sm text-muted-foreground transition hover:text-primary"
                                        >
                                            Features
                                        </Link>
                                    </li>
                                    <li>
                                        <Link
                                            href={register()}
                                            className="text-sm text-muted-foreground transition hover:text-primary"
                                        >
                                            Create a Character
                                        </Link>
                                    </li>
                                    <li>
                                        <Link
                                            href={login()}
                                            className="text-sm text-muted-foreground transition hover:text-primary"
                                        >
                                            Sign In
                                        </Link>
                                    </li>
                                </ul>
                            </div>

                            {/* Support */}
                            <div>
                                <h4 className="font-[Cinzel] font-bold text-foreground mb-4">
                                    Support
                                </h4>
                                <ul className="space-y-2">
                                    <li className="flex items-center gap-2">
                                        <Mail className="h-4 w-4 text-muted-foreground" />
                                        <a
                                            href="mailto:support@myrefell.com"
                                            className="text-sm text-muted-foreground transition hover:text-primary"
                                        >
                                            support@myrefell.com
                                        </a>
                                    </li>
                                    <li className="flex items-center gap-2">
                                        <Shield className="h-4 w-4 text-muted-foreground" />
                                        <a
                                            href="mailto:abuse@myrefell.com"
                                            className="text-sm text-muted-foreground transition hover:text-primary"
                                        >
                                            Report Cheating
                                        </a>
                                    </li>
                                    <li className="flex items-center gap-2">
                                        <Scale className="h-4 w-4 text-muted-foreground" />
                                        <a
                                            href="mailto:appeals@myrefell.com"
                                            className="text-sm text-muted-foreground transition hover:text-primary"
                                        >
                                            Ban Appeals
                                        </a>
                                    </li>
                                </ul>
                            </div>

                            {/* Legal */}
                            <div>
                                <h4 className="font-[Cinzel] font-bold text-foreground mb-4">
                                    Legal
                                </h4>
                                <ul className="space-y-2">
                                    <li>
                                        <Link
                                            href="/terms"
                                            className="text-sm text-muted-foreground transition hover:text-primary"
                                        >
                                            Terms of Service
                                        </Link>
                                    </li>
                                    <li>
                                        <Link
                                            href="/privacy"
                                            className="text-sm text-muted-foreground transition hover:text-primary"
                                        >
                                            Privacy Policy
                                        </Link>
                                    </li>
                                    <li>
                                        <Link
                                            href="/rules"
                                            className="text-sm text-muted-foreground transition hover:text-primary"
                                        >
                                            Game Rules
                                        </Link>
                                    </li>
                                </ul>
                            </div>
                        </div>

                        {/* Divider */}
                        <div className="flex items-center gap-3 w-full mb-6">
                            <div className="flex-1 h-px bg-gradient-to-r from-transparent via-border to-transparent" />
                            <div className="w-1.5 h-1.5 rotate-45 bg-primary/30" />
                            <div className="flex-1 h-px bg-gradient-to-r from-transparent via-border to-transparent" />
                        </div>

                        {/* Bottom bar */}
                        <div className="flex flex-col items-center justify-between gap-3 md:flex-row text-xs text-muted-foreground/60">
                            <p>&copy; {new Date().getFullYear()} Myrefell. All rights reserved.</p>
                            <p>Made with dedication for the medieval RPG community.</p>
                        </div>
                    </div>
                </footer>
            </div>
        </>
    );
}
