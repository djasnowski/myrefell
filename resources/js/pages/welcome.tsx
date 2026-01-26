import { Head, Link, usePage } from '@inertiajs/react';
import {
    Anchor,
    Baby,
    Building,
    Calendar,
    Castle,
    Church,
    CloudRain,
    Coins,
    Crown,
    Eye,
    Hammer,
    Heart,
    HeartPulse,
    Home,
    Mail,
    MapPin,
    Mountain,
    PartyPopper,
    Pickaxe,
    Scale,
    Scroll,
    Shield,
    Ship,
    Skull,
    Snowflake,
    Sun,
    Swords,
    Trees,
    Trophy,
    Users,
    Vote,
    Wheat,
} from 'lucide-react';
import { dashboard, login, register } from '@/routes';
import type { SharedData } from '@/types';

export default function Welcome({ canRegister = true }: { canRegister?: boolean }) {
    const { auth } = usePage<SharedData>().props;

    return (
        <>
            <Head title="Myrefell - Medieval World MMO">
                <link rel="preconnect" href="https://fonts.bunny.net" />
                <link href="https://fonts.bunny.net/css?family=cinzel:400,700&family=inter:400,500,600" rel="stylesheet" />
            </Head>

            <div className="min-h-screen bg-stone-950 text-stone-100">
                {/* Navigation */}
                <nav className="fixed top-0 z-50 w-full border-b border-stone-800/50 bg-stone-950/90 backdrop-blur-sm">
                    <div className="mx-auto flex max-w-7xl items-center justify-between px-6 py-4">
                        <Link href="/" className="flex items-center gap-2">
                            <Crown className="h-8 w-8 text-amber-500" />
                            <span className="font-[Cinzel] text-2xl font-bold tracking-wide text-amber-500">Myrefell</span>
                        </Link>
                        <div className="flex items-center gap-4">
                            {auth.user ? (
                                <Link
                                    href={dashboard()}
                                    className="rounded-lg bg-amber-600 px-6 py-2 font-semibold text-stone-950 transition hover:bg-amber-500"
                                >
                                    Enter World
                                </Link>
                            ) : (
                                <>
                                    <Link
                                        href={login()}
                                        className="px-4 py-2 text-stone-300 transition hover:text-amber-400"
                                    >
                                        Sign In
                                    </Link>
                                    {canRegister && (
                                        <Link
                                            href={register()}
                                            className="rounded-lg bg-amber-600 px-6 py-2 font-semibold text-stone-950 transition hover:bg-amber-500"
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
                <section className="relative flex min-h-screen items-center justify-center overflow-hidden pt-20">
                    {/* Background Pattern */}
                    <div className="absolute inset-0 bg-[url('data:image/svg+xml,%3Csvg%20width%3D%2260%22%20height%3D%2260%22%20viewBox%3D%220%200%2060%2060%22%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%3E%3Cpath%20d%3D%22M30%200L60%2030L30%2060L0%2030z%22%20fill%3D%22none%22%20stroke%3D%22%23292524%22%20stroke-width%3D%220.5%22%2F%3E%3C%2Fsvg%3E')] opacity-30" />

                    {/* Gradient Overlay */}
                    <div className="absolute inset-0 bg-gradient-to-b from-stone-950 via-transparent to-stone-950" />

                    <div className="relative z-10 mx-auto max-w-5xl px-6 text-center">
                        <div className="mb-6 inline-flex items-center gap-2 rounded-full border border-amber-600/30 bg-amber-900/20 px-4 py-2 text-sm text-amber-400">
                            <Crown className="h-4 w-4" />
                            <span>A Living Medieval World</span>
                        </div>

                        <h1 className="mb-6 font-[Cinzel] text-5xl font-bold leading-tight text-stone-100 md:text-7xl">
                            Rise from Peasant
                            <br />
                            <span className="text-amber-500">to King</span>
                        </h1>

                        <p className="mx-auto mb-10 max-w-2xl text-lg leading-relaxed text-stone-400 md:text-xl">
                            In Myrefell, you are one person in a persistent medieval world. Train your combat skills,
                            master crafts, climb the social ladder, and shape history through politics, war, and faith.
                        </p>

                        <div className="flex flex-col items-center justify-center gap-4 sm:flex-row">
                            {auth.user ? (
                                <Link
                                    href={dashboard()}
                                    className="inline-flex items-center gap-2 rounded-lg bg-amber-600 px-8 py-4 text-lg font-semibold text-stone-950 transition hover:bg-amber-500"
                                >
                                    <MapPin className="h-5 w-5" />
                                    Enter the World
                                </Link>
                            ) : (
                                <>
                                    <Link
                                        href={register()}
                                        className="inline-flex items-center gap-2 rounded-lg bg-amber-600 px-8 py-4 text-lg font-semibold text-stone-950 transition hover:bg-amber-500"
                                    >
                                        <Scroll className="h-5 w-5" />
                                        Create Your Character
                                    </Link>
                                    <Link
                                        href={login()}
                                        className="inline-flex items-center gap-2 rounded-lg border-2 border-stone-700 px-8 py-4 text-lg font-semibold text-stone-300 transition hover:border-amber-600 hover:text-amber-400"
                                    >
                                        Continue Your Story
                                    </Link>
                                </>
                            )}
                        </div>

                        {/* Quick Stats */}
                        <div className="mt-16 grid grid-cols-2 gap-4 md:grid-cols-4">
                            <div className="rounded-xl border border-stone-800 bg-stone-900/50 p-4">
                                <div className="text-3xl font-bold text-amber-500">4</div>
                                <div className="text-sm text-stone-500">Kingdoms</div>
                            </div>
                            <div className="rounded-xl border border-stone-800 bg-stone-900/50 p-4">
                                <div className="text-3xl font-bold text-amber-500">9</div>
                                <div className="text-sm text-stone-500">Skills to Master</div>
                            </div>
                            <div className="rounded-xl border border-stone-800 bg-stone-900/50 p-4">
                                <div className="text-3xl font-bold text-amber-500">5</div>
                                <div className="text-sm text-stone-500">Social Classes</div>
                            </div>
                            <div className="rounded-xl border border-stone-800 bg-stone-900/50 p-4">
                                <div className="text-3xl font-bold text-amber-500">&infin;</div>
                                <div className="text-sm text-stone-500">Possibilities</div>
                            </div>
                        </div>
                    </div>

                    {/* Scroll Indicator */}
                    <div className="absolute bottom-8 left-1/2 -translate-x-1/2 animate-bounce">
                        <div className="h-12 w-6 rounded-full border-2 border-stone-700">
                            <div className="mx-auto mt-2 h-3 w-1 rounded-full bg-amber-500" />
                        </div>
                    </div>
                </section>

                {/* The Dual Nature */}
                <section className="border-y border-stone-800 bg-stone-900/30 py-24">
                    <div className="mx-auto max-w-7xl px-6">
                        <div className="mb-16 text-center">
                            <h2 className="mb-4 font-[Cinzel] text-3xl font-bold text-stone-100 md:text-4xl">
                                One Person, <span className="text-amber-500">Infinite Paths</span>
                            </h2>
                            <p className="mx-auto max-w-2xl text-stone-400">
                                You are both an individual with a body to train and a citizen who shapes society.
                            </p>
                        </div>

                        <div className="grid gap-8 md:grid-cols-2">
                            {/* The Individual */}
                            <div className="rounded-2xl border border-stone-800 bg-stone-900/50 p-8">
                                <div className="mb-6 flex items-center gap-3">
                                    <div className="rounded-lg bg-red-900/50 p-3">
                                        <Swords className="h-6 w-6 text-red-400" />
                                    </div>
                                    <h3 className="font-[Cinzel] text-2xl font-bold">The Individual</h3>
                                </div>
                                <p className="mb-6 text-stone-400">
                                    Train your body, master crafts, and equip yourself for adventure.
                                </p>
                                <ul className="space-y-3">
                                    <li className="flex items-center gap-3 text-stone-300">
                                        <Shield className="h-5 w-5 text-stone-600" />
                                        <span>3 combat skills: Attack, Strength, Defense</span>
                                    </li>
                                    <li className="flex items-center gap-3 text-stone-300">
                                        <Hammer className="h-5 w-5 text-stone-600" />
                                        <span>6 trade skills: Mining, Fishing, Smithing, more</span>
                                    </li>
                                    <li className="flex items-center gap-3 text-stone-300">
                                        <Heart className="h-5 w-5 text-stone-600" />
                                        <span>Health, energy, equipment, and inventory</span>
                                    </li>
                                </ul>
                            </div>

                            {/* The Citizen */}
                            <div className="rounded-2xl border border-stone-800 bg-stone-900/50 p-8">
                                <div className="mb-6 flex items-center gap-3">
                                    <div className="rounded-lg bg-amber-900/50 p-3">
                                        <Crown className="h-6 w-6 text-amber-400" />
                                    </div>
                                    <h3 className="font-[Cinzel] text-2xl font-bold">The Citizen</h3>
                                </div>
                                <p className="mb-6 text-stone-400">
                                    Vote, hold office, own property, and shape your community.
                                </p>
                                <ul className="space-y-3">
                                    <li className="flex items-center gap-3 text-stone-300">
                                        <Vote className="h-5 w-5 text-stone-600" />
                                        <span>Democratic elections for all positions</span>
                                    </li>
                                    <li className="flex items-center gap-3 text-stone-300">
                                        <Scale className="h-5 w-5 text-stone-600" />
                                        <span>Courts, laws, and justice systems</span>
                                    </li>
                                    <li className="flex items-center gap-3 text-stone-300">
                                        <Users className="h-5 w-5 text-stone-600" />
                                        <span>Guilds, businesses, and trade</span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </section>

                {/* World Hierarchy */}
                <section className="py-24">
                    <div className="mx-auto max-w-7xl px-6">
                        <div className="mb-16 text-center">
                            <h2 className="mb-4 font-[Cinzel] text-3xl font-bold text-stone-100 md:text-4xl">
                                A <span className="text-amber-500">Living World</span> to Explore
                            </h2>
                            <p className="mx-auto max-w-2xl text-stone-400">
                                From humble hamlets to mighty kingdoms, every settlement is player-governed with real consequences.
                            </p>
                        </div>

                        <div className="grid gap-6 md:grid-cols-2 lg:grid-cols-4">
                            {[
                                { icon: Crown, name: 'Kingdom', ruler: 'Elected King', color: 'amber', desc: 'Declare wars, set taxes, rule the realm' },
                                { icon: Castle, name: 'Barony', ruler: 'Baron', color: 'purple', desc: 'Collect taxes, raise militia, judge crimes' },
                                { icon: Church, name: 'Town', ruler: 'Elected Mayor', color: 'blue', desc: 'Manage guilds, markets, town affairs' },
                                { icon: Home, name: 'Village', ruler: 'Elected Elder', color: 'green', desc: 'Local disputes, approve migrants' },
                            ].map((loc) => (
                                <div
                                    key={loc.name}
                                    className="group rounded-xl border border-stone-800 bg-stone-900/30 p-6 transition hover:border-stone-700"
                                >
                                    <div className={`mb-4 inline-flex rounded-lg bg-${loc.color}-900/30 p-3`}>
                                        <loc.icon className={`h-6 w-6 text-${loc.color}-400`} />
                                    </div>
                                    <h3 className="mb-1 font-[Cinzel] text-xl font-bold text-stone-100">{loc.name}</h3>
                                    <p className={`mb-2 text-sm text-${loc.color}-400`}>{loc.ruler}</p>
                                    <p className="text-sm text-stone-500">{loc.desc}</p>
                                </div>
                            ))}
                        </div>
                    </div>
                </section>

                {/* Paths to Power */}
                <section className="border-y border-stone-800 bg-stone-900/30 py-24">
                    <div className="mx-auto max-w-7xl px-6">
                        <div className="mb-16 text-center">
                            <h2 className="mb-4 font-[Cinzel] text-3xl font-bold text-stone-100 md:text-4xl">
                                Choose Your <span className="text-amber-500">Path to Power</span>
                            </h2>
                            <p className="mx-auto max-w-2xl text-stone-400">
                                There are no classes in Myrefell. Forge your own destiny through the path that calls to you.
                            </p>
                        </div>

                        <div className="grid gap-6 md:grid-cols-2 lg:grid-cols-4">
                            {[
                                {
                                    icon: Coins,
                                    title: 'Economic Magnate',
                                    desc: 'Own businesses, run caravans, set tariffs, and dominate trade.',
                                    color: 'yellow',
                                },
                                {
                                    icon: Swords,
                                    title: 'Military Commander',
                                    desc: 'Raise armies, declare wars, siege castles, and conquer kingdoms.',
                                    color: 'red',
                                },
                                {
                                    icon: Crown,
                                    title: 'Political Leader',
                                    desc: 'Win elections, hold office, climb from Elder to King.',
                                    color: 'amber',
                                },
                                {
                                    icon: Church,
                                    title: 'Religious Prophet',
                                    desc: 'Found a cult, grow it into a religion, become spiritual leader.',
                                    color: 'purple',
                                },
                                {
                                    icon: Skull,
                                    title: 'Criminal Mastermind',
                                    desc: 'Build an underworld empire and control the shadows.',
                                    color: 'stone',
                                },
                                {
                                    icon: Users,
                                    title: 'Dynasty Builder',
                                    desc: 'Marry strategically, raise heirs, build a lasting legacy.',
                                    color: 'emerald',
                                },
                                {
                                    icon: Ship,
                                    title: 'Trade Baron',
                                    desc: 'Send caravans across the realm and profit from tariffs.',
                                    color: 'blue',
                                },
                                {
                                    icon: Trophy,
                                    title: 'Tournament Champion',
                                    desc: 'Compete in tournaments, win glory, and earn renown.',
                                    color: 'orange',
                                },
                            ].map((path) => (
                                <div
                                    key={path.title}
                                    className="rounded-xl border border-stone-800 bg-stone-900/50 p-6 transition hover:border-stone-700"
                                >
                                    <div className={`mb-4 inline-flex rounded-lg bg-${path.color}-900/30 p-3`}>
                                        <path.icon className={`h-6 w-6 text-${path.color}-400`} />
                                    </div>
                                    <h3 className="mb-2 font-[Cinzel] text-lg font-bold text-stone-100">{path.title}</h3>
                                    <p className="text-sm text-stone-400">{path.desc}</p>
                                </div>
                            ))}
                        </div>
                    </div>
                </section>

                {/* Living Simulation */}
                <section className="py-24">
                    <div className="mx-auto max-w-7xl px-6">
                        <div className="mb-16 text-center">
                            <h2 className="mb-4 font-[Cinzel] text-3xl font-bold text-stone-100 md:text-4xl">
                                A World That <span className="text-amber-500">Lives Without You</span>
                            </h2>
                            <p className="mx-auto max-w-2xl text-stone-400">
                                Unlike other games, Myrefell's world doesn't pause. NPCs live full lives, information travels realistically, and seasons change the landscape.
                            </p>
                        </div>

                        <div className="grid gap-8 md:grid-cols-3">
                            {/* Living NPCs */}
                            <div className="rounded-2xl border border-stone-800 bg-stone-900/50 p-8">
                                <div className="mb-6 flex items-center gap-3">
                                    <div className="rounded-lg bg-emerald-900/50 p-3">
                                        <Users className="h-6 w-6 text-emerald-400" />
                                    </div>
                                    <h3 className="font-[Cinzel] text-xl font-bold">Living NPCs</h3>
                                </div>
                                <p className="mb-6 text-stone-400">
                                    NPCs aren't static vendors. They're people with lives of their own.
                                </p>
                                <ul className="space-y-3">
                                    <li className="flex items-center gap-3 text-stone-300">
                                        <Baby className="h-5 w-5 text-stone-600" />
                                        <span>Born, age, marry, have children, die</span>
                                    </li>
                                    <li className="flex items-center gap-3 text-stone-300">
                                        <Hammer className="h-5 w-5 text-stone-600" />
                                        <span>Hold jobs and fill vacant roles</span>
                                    </li>
                                    <li className="flex items-center gap-3 text-stone-300">
                                        <MapPin className="h-5 w-5 text-stone-600" />
                                        <span>Migrate to find work or family</span>
                                    </li>
                                </ul>
                            </div>

                            {/* Information Flow */}
                            <div className="rounded-2xl border border-stone-800 bg-stone-900/50 p-8">
                                <div className="mb-6 flex items-center gap-3">
                                    <div className="rounded-lg bg-blue-900/50 p-3">
                                        <Mail className="h-6 w-6 text-blue-400" />
                                    </div>
                                    <h3 className="font-[Cinzel] text-xl font-bold">Slow Information</h3>
                                </div>
                                <p className="mb-6 text-stone-400">
                                    No global chat. News travels at the speed of messengers.
                                </p>
                                <ul className="space-y-3">
                                    <li className="flex items-center gap-3 text-stone-300">
                                        <Users className="h-5 w-5 text-stone-600" />
                                        <span>Local chat only in your settlement</span>
                                    </li>
                                    <li className="flex items-center gap-3 text-stone-300">
                                        <Scroll className="h-5 w-5 text-stone-600" />
                                        <span>Distant news may be delayed or false</span>
                                    </li>
                                    <li className="flex items-center gap-3 text-stone-300">
                                        <Eye className="h-5 w-5 text-stone-600" />
                                        <span>Spies gather intelligence faster</span>
                                    </li>
                                </ul>
                            </div>

                            {/* Dynamic World */}
                            <div className="rounded-2xl border border-stone-800 bg-stone-900/50 p-8">
                                <div className="mb-6 flex items-center gap-3">
                                    <div className="rounded-lg bg-purple-900/50 p-3">
                                        <Calendar className="h-6 w-6 text-purple-400" />
                                    </div>
                                    <h3 className="font-[Cinzel] text-xl font-bold">Dynamic World</h3>
                                </div>
                                <p className="mb-6 text-stone-400">
                                    Seasons change, disasters strike, diseases spread.
                                </p>
                                <ul className="space-y-3">
                                    <li className="flex items-center gap-3 text-stone-300">
                                        <Sun className="h-5 w-5 text-stone-600" />
                                        <span>4 seasons affect travel, farming, combat</span>
                                    </li>
                                    <li className="flex items-center gap-3 text-stone-300">
                                        <CloudRain className="h-5 w-5 text-stone-600" />
                                        <span>Droughts, floods, fires, earthquakes</span>
                                    </li>
                                    <li className="flex items-center gap-3 text-stone-300">
                                        <HeartPulse className="h-5 w-5 text-stone-600" />
                                        <span>Plagues spread through trade routes</span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </section>

                {/* Skills & Activities */}
                <section className="py-24">
                    <div className="mx-auto max-w-7xl px-6">
                        <div className="mb-16 text-center">
                            <h2 className="mb-4 font-[Cinzel] text-3xl font-bold text-stone-100 md:text-4xl">
                                Master <span className="text-amber-500">9 Skills</span>
                            </h2>
                            <p className="mx-auto max-w-2xl text-stone-400">
                                Every skill opens new opportunities. Mine ore, forge weapons, catch fish, and craft your legacy.
                            </p>
                        </div>

                        <div className="grid grid-cols-3 gap-4 md:grid-cols-5 lg:grid-cols-9">
                            {[
                                { icon: Swords, name: 'Attack', color: 'red' },
                                { icon: Shield, name: 'Strength', color: 'orange' },
                                { icon: Heart, name: 'Defense', color: 'pink' },
                                { icon: Pickaxe, name: 'Mining', color: 'stone' },
                                { icon: Anchor, name: 'Fishing', color: 'blue' },
                                { icon: Trees, name: 'Woodcutting', color: 'green' },
                                { icon: Wheat, name: 'Cooking', color: 'yellow' },
                                { icon: Hammer, name: 'Smithing', color: 'orange' },
                                { icon: Hammer, name: 'Crafting', color: 'amber' },
                            ].map((skill) => (
                                <div
                                    key={skill.name}
                                    className="flex flex-col items-center rounded-xl border border-stone-800 bg-stone-900/30 p-4 transition hover:border-stone-700"
                                >
                                    <div className={`mb-2 rounded-lg bg-${skill.color}-900/30 p-2`}>
                                        <skill.icon className={`h-5 w-5 text-${skill.color}-400`} />
                                    </div>
                                    <span className="text-xs text-stone-400">{skill.name}</span>
                                </div>
                            ))}
                        </div>
                    </div>
                </section>

                {/* Social Classes */}
                <section className="border-y border-stone-800 bg-stone-900/30 py-24">
                    <div className="mx-auto max-w-7xl px-6">
                        <div className="mb-16 text-center">
                            <h2 className="mb-4 font-[Cinzel] text-3xl font-bold text-stone-100 md:text-4xl">
                                Climb the <span className="text-amber-500">Social Ladder</span>
                            </h2>
                            <p className="mx-auto max-w-2xl text-stone-400">
                                Your class determines your rights. Earn freedom, gain citizenship, or achieve nobility through deeds.
                            </p>
                        </div>

                        <div className="flex flex-wrap justify-center gap-4">
                            {[
                                { name: 'Serf', desc: 'Bound to the land', rights: 'Basic protection only' },
                                { name: 'Freeman', desc: 'Free citizen', rights: 'Vote, own property, travel' },
                                { name: 'Burgher', desc: 'Town citizen', rights: 'Guild membership, trade' },
                                { name: 'Noble', desc: 'Aristocracy', rights: 'Hold high office, own land' },
                                { name: 'Clergy', desc: 'Religious order', rights: 'Church authority' },
                            ].map((cls, i) => (
                                <div
                                    key={cls.name}
                                    className="w-40 rounded-xl border border-stone-800 bg-stone-900/50 p-4 text-center"
                                >
                                    <div className="mb-2 text-2xl font-bold text-amber-500">{i + 1}</div>
                                    <h3 className="font-[Cinzel] font-bold text-stone-100">{cls.name}</h3>
                                    <p className="text-xs text-amber-400">{cls.desc}</p>
                                    <p className="mt-2 text-xs text-stone-500">{cls.rights}</p>
                                </div>
                            ))}
                        </div>
                    </div>
                </section>

                {/* Features Grid */}
                <section className="py-24">
                    <div className="mx-auto max-w-7xl px-6">
                        <div className="mb-16 text-center">
                            <h2 className="mb-4 font-[Cinzel] text-3xl font-bold text-stone-100 md:text-4xl">
                                What Makes Myrefell <span className="text-amber-500">Different</span>
                            </h2>
                        </div>

                        <div className="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                            {[
                                {
                                    title: 'Full Warfare System',
                                    desc: 'Raise armies, declare wars, siege settlements, fight battles. Negotiate peace with territory transfers and gold demands.',
                                },
                                {
                                    title: 'Dynasty & Marriage',
                                    desc: 'Found dynasties, arrange marriages, manage succession. Build a family tree that spans generations.',
                                },
                                {
                                    title: 'Trade Caravans',
                                    desc: 'Send caravans across the realm, set tariffs on routes through your territory, profit from trade.',
                                },
                                {
                                    title: 'Festivals & Tournaments',
                                    desc: 'Attend festivals as performer or vendor, compete in tournament brackets for glory and prizes.',
                                },
                                {
                                    title: 'Building Construction',
                                    desc: 'Construct buildings in your settlements, manage repairs, expand your infrastructure.',
                                },
                                {
                                    title: 'Real Elections',
                                    desc: 'Every government position is elected by players. Campaign for office or stage a no-confidence vote.',
                                },
                                {
                                    title: 'Meaningful Law',
                                    desc: 'Commit crimes, face trials, receive punishments. Courts with judges, evidence, and verdicts.',
                                },
                                {
                                    title: 'Player-Founded Religions',
                                    desc: 'Start a cult, grow it into a religion, define its beliefs. Religions grant bonuses and political power.',
                                },
                                {
                                    title: 'No Pay-to-Win',
                                    desc: 'Cosmetics only. No stat boosts, no power items, no skipping progression. Your success is earned.',
                                },
                            ].map((feature) => (
                                <div
                                    key={feature.title}
                                    className="rounded-xl border border-stone-800 bg-stone-900/30 p-6"
                                >
                                    <h3 className="mb-2 font-[Cinzel] text-lg font-bold text-amber-400">{feature.title}</h3>
                                    <p className="text-sm text-stone-400">{feature.desc}</p>
                                </div>
                            ))}
                        </div>
                    </div>
                </section>

                {/* Daily Loop */}
                <section className="border-y border-stone-800 bg-stone-900/30 py-24">
                    <div className="mx-auto max-w-7xl px-6">
                        <div className="mb-16 text-center">
                            <h2 className="mb-4 font-[Cinzel] text-3xl font-bold text-stone-100 md:text-4xl">
                                Your <span className="text-amber-500">Daily Life</span>
                            </h2>
                            <p className="mx-auto max-w-2xl text-stone-400">
                                A few minutes each day to train, work, and engage with your community.
                            </p>
                        </div>

                        <div className="mx-auto max-w-3xl">
                            <div className="space-y-4">
                                {[
                                    { time: 'Morning', activity: 'Train your combat stats at the training grounds', icon: Swords },
                                    { time: 'Midday', activity: 'Work your job for wages or run your business', icon: Coins },
                                    { time: 'Afternoon', activity: 'Complete 3 daily tasks for bonus rewards', icon: Scroll },
                                    { time: 'Evening', activity: 'Check local news, chat with neighbors, vote in elections', icon: Users },
                                ].map((item, i) => (
                                    <div
                                        key={item.time}
                                        className="flex items-center gap-4 rounded-xl border border-stone-800 bg-stone-900/50 p-4"
                                    >
                                        <div className="flex h-10 w-10 items-center justify-center rounded-full bg-amber-900/30 text-lg font-bold text-amber-500">
                                            {i + 1}
                                        </div>
                                        <div className="flex-1">
                                            <div className="text-sm text-amber-400">{item.time}</div>
                                            <div className="text-stone-300">{item.activity}</div>
                                        </div>
                                        <item.icon className="h-5 w-5 text-stone-600" />
                                    </div>
                                ))}
                            </div>
                        </div>
                    </div>
                </section>

                {/* CTA Section */}
                <section className="py-24">
                    <div className="mx-auto max-w-4xl px-6 text-center">
                        <Crown className="mx-auto mb-6 h-16 w-16 text-amber-500" />
                        <h2 className="mb-4 font-[Cinzel] text-3xl font-bold text-stone-100 md:text-4xl">
                            Your Story Begins Now
                        </h2>
                        <p className="mb-8 text-lg text-stone-400">
                            Every king started as a peasant. Every dynasty began with one person.
                            <br />
                            What will your legacy be?
                        </p>
                        {auth.user ? (
                            <Link
                                href={dashboard()}
                                className="inline-flex items-center gap-2 rounded-lg bg-amber-600 px-10 py-4 text-xl font-semibold text-stone-950 transition hover:bg-amber-500"
                            >
                                <MapPin className="h-6 w-6" />
                                Enter Myrefell
                            </Link>
                        ) : (
                            <Link
                                href={register()}
                                className="inline-flex items-center gap-2 rounded-lg bg-amber-600 px-10 py-4 text-xl font-semibold text-stone-950 transition hover:bg-amber-500"
                            >
                                <Scroll className="h-6 w-6" />
                                Create Your Character
                            </Link>
                        )}
                    </div>
                </section>

                {/* Footer */}
                <footer className="border-t border-stone-800 py-12">
                    <div className="mx-auto max-w-7xl px-6">
                        <div className="flex flex-col items-center justify-between gap-4 md:flex-row">
                            <div className="flex items-center gap-2">
                                <Crown className="h-6 w-6 text-amber-500" />
                                <span className="font-[Cinzel] text-lg font-bold text-stone-400">Myrefell</span>
                            </div>
                            <p className="text-sm text-stone-600">
                                A medieval world where your choices matter.
                            </p>
                        </div>
                    </div>
                </footer>
            </div>
        </>
    );
}
