import { Head, Link, usePage } from "@inertiajs/react";
import {
    Anchor,
    ArrowRight,
    Backpack,
    BarChart3,
    Banknote,
    Briefcase,
    Calendar,
    Church,
    Coins,
    Compass,
    Crown,
    Gavel,
    Hammer,
    Heart,
    Home,
    Gauge,
    Lightbulb,
    Map,
    MapPin,
    Pickaxe,
    ScrollText,
    Shield,
    Sparkles,
    Store,
    Swords,
    Truck,
    Users,
    UsersRound,
    Vote,
} from "lucide-react";
import { useState } from "react";
import TutorialModal from "@/components/tutorial-modal";
import HealthStatusWidget from "@/components/widgets/health-status-widget";
import AppLayout from "@/layouts/app-layout";
import type { BreadcrumbItem } from "@/types";

interface LocationFeatures {
    market: boolean;
    bank: boolean;
    training: boolean;
    jobs: boolean;
    tavern: boolean;
    crafting: boolean;
    port: boolean;
    dungeon: boolean;
    guilds: boolean;
    elections: boolean;
    stables: boolean;
}

// Helper to pluralize location types correctly
function pluralizeLocationType(type: string): string {
    const plurals: Record<string, string> = {
        village: "villages",
        town: "towns",
        barony: "baronies",
        duchy: "duchies",
        kingdom: "kingdoms",
    };
    return plurals[type] || `${type}s`;
}

interface DiseaseInfection {
    id: number;
    disease_name: string;
    status: "incubating" | "symptomatic" | "recovering";
    severity: string;
    days_infected: number;
    is_treated: boolean;
}

interface SidebarData {
    player: {
        id: number;
        username: string;
        gender: string;
        hp: number;
        max_hp: number;
        energy: number;
        max_energy: number;
        gold: number;
        combat_level: number;
        primary_title: string | null;
        title_tier: number | null;
    };
    location: {
        type: string;
        id: number | null;
        name: string;
        biome: string;
        is_port?: boolean;
        features?: LocationFeatures;
    } | null;
    home_village: {
        id: number;
        name: string;
        barony: { id: number; name: string } | null;
        kingdom: { id: number; name: string } | null;
    } | null;
    health?: {
        infection: DiseaseInfection | null;
        is_healthy: boolean;
    };
}

export default function Dashboard() {
    const { sidebar, showTutorial } = usePage<{ sidebar: SidebarData; showTutorial: boolean }>()
        .props;
    const [tutorialOpen, setTutorialOpen] = useState(showTutorial);

    const player = sidebar?.player;
    const location = sidebar?.location;
    const homeVillage = sidebar?.home_village;
    const health = sidebar?.health;

    const breadcrumbs: BreadcrumbItem[] = [
        { title: "Dashboard", href: "/dashboard" },
        ...(location
            ? [
                  {
                      title: location.name,
                      href: `/${pluralizeLocationType(location.type)}/${location.id}`,
                  },
              ]
            : []),
    ];

    const quickActions = [
        {
            title: "World Map",
            description: "Travel the realm",
            icon: Map,
            href: "/travel",
            color: "amber",
        },
        {
            title: "Skills",
            description: "View your levels",
            icon: BarChart3,
            href: "/skills",
            color: "blue",
        },
        {
            title: "Inventory",
            description: "Your items",
            icon: Backpack,
            href: "/inventory",
            color: "emerald",
        },
        {
            title: "Quests",
            description: "Active quests",
            icon: ScrollText,
            href: "/quests",
            color: "purple",
        },
    ];

    const gettingStarted = [
        {
            title: "Train Combat",
            description: "Improve Attack, Strength, Defense",
            href: location ? `/${location.type}s/${location.id}/training` : "/villages",
            icon: Swords,
        },
        {
            title: "Gather Resources",
            description: "Mine, fish, or chop wood",
            href: location?.type === "village" ? `/villages/${location.id}/gathering` : "/villages",
            icon: Pickaxe,
        },
        {
            title: "Visit Market",
            description: "Buy and sell goods",
            href: location ? `/${location.type}s/${location.id}/market` : "/villages",
            icon: Coins,
        },
        {
            title: "Find Work",
            description: "Apply for a job",
            href: location ? `/${location.type}s/${location.id}/jobs` : "/villages",
            icon: Briefcase,
        },
    ];

    // What you can do - organized by category, filtered by location features
    const features = location?.features;

    const gameFeatures = [
        {
            category: "Combat & Adventure",
            color: "red",
            items: [
                features?.training && {
                    name: "Training",
                    href: location ? `/${location.type}s/${location.id}/training` : "/villages",
                    icon: Swords,
                },
                { name: "Combat", href: "/combat", icon: Shield },
                features?.dungeon && { name: "Dungeons", href: "/dungeons", icon: Sparkles },
            ].filter(Boolean),
        },
        {
            category: "Economy & Trade",
            color: "amber",
            items: [
                features?.bank && {
                    name: "Banking",
                    href: location ? `/${location.type}s/${location.id}/bank` : "/villages",
                    icon: Banknote,
                },
                features?.market && {
                    name: "Market",
                    href: location ? `/${location.type}s/${location.id}/market` : "/villages",
                    icon: Store,
                },
                features?.crafting && {
                    name: "Crafting",
                    href: location ? `/${location.type}s/${location.id}/crafting` : "/villages",
                    icon: Hammer,
                },
                { name: "Caravans", href: "/trade/caravans", icon: Truck },
                features?.jobs && {
                    name: "Jobs",
                    href: location ? `/${location.type}s/${location.id}/jobs` : "/villages",
                    icon: Briefcase,
                },
            ].filter(Boolean),
        },
        {
            category: "Social & Family",
            color: "pink",
            items: [
                { name: "Dynasty", href: "/dynasty", icon: Crown },
                { name: "Marriage", href: "/dynasty/proposals", icon: Heart },
                features?.guilds && { name: "Guilds", href: "/guilds", icon: UsersRound },
                { name: "Religion", href: "/religions", icon: Church },
            ].filter(Boolean),
        },
        {
            category: "Politics & Law",
            color: "blue",
            items: [
                features?.elections && { name: "Elections", href: "/elections", icon: Vote },
                { name: "Roles", href: "/roles", icon: Crown },
                { name: "Crime & Law", href: "/crime", icon: Gavel },
                { name: "Social Class", href: "/social-class", icon: Users },
            ].filter(Boolean),
        },
        {
            category: "Warfare",
            color: "orange",
            items: [
                { name: "Armies", href: "/warfare/armies", icon: Shield },
                { name: "Wars", href: "/warfare/wars", icon: Swords },
            ],
        },
        {
            category: "World & Travel",
            color: "green",
            items: [
                { name: "Travel", href: "/travel", icon: Map },
                features?.stables && {
                    name: "Stables",
                    href: location ? `/${location.type}s/${location.id}/stables` : "/towns",
                    icon: Gauge,
                },
                features?.port && {
                    name: "Sea Port",
                    href: location ? `/${location.type}s/${location.id}/port` : "/villages",
                    icon: Anchor,
                },
                { name: "Calendar", href: "/calendar", icon: Calendar },
                { name: "Events", href: "/events", icon: Sparkles },
            ].filter(Boolean),
        },
    ].filter((category) => category.items.length > 0) as Array<{
        category: string;
        color: string;
        items: Array<{ name: string; href: string; icon: typeof Swords }>;
    }>;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Getting Started" />

            {/* Tutorial Modal */}
            {tutorialOpen && (
                <TutorialModal
                    playerName={player?.username ?? "Traveler"}
                    location={location}
                    onClose={() => setTutorialOpen(false)}
                />
            )}

            <div className="space-y-6 p-6">
                {/* Health Alert */}
                {health?.infection && <HealthStatusWidget infection={health.infection} />}

                {/* Welcome Header with Location */}
                <div className="grid gap-4 lg:grid-cols-3">
                    {/* Welcome */}
                    <div className="rounded-xl border border-stone-800 bg-stone-900/50 p-4 lg:col-span-2">
                        <div className="flex items-start gap-3">
                            <div className="rounded-lg bg-amber-900/50 p-2">
                                <Crown className="h-6 w-6 text-amber-400" />
                            </div>
                            <div className="flex-1">
                                <h1 className="font-[Cinzel] text-xl font-bold text-stone-100">
                                    Welcome, {player?.username ?? "Traveler"}
                                </h1>
                                <p className="text-sm text-stone-400">
                                    You are a {player?.primary_title ?? "peasant"}. Your journey
                                    begins here.
                                </p>
                            </div>
                        </div>

                        {/* Current Status */}
                        <div className="mt-3 grid grid-cols-4 gap-2">
                            <div className="rounded-lg border border-stone-800 bg-stone-900/30 p-2 text-center">
                                <div className="flex items-center justify-center gap-1 text-xs text-stone-500">
                                    <Heart className="h-3 w-3 text-red-400" />
                                    HP
                                </div>
                                <div className="text-sm font-semibold text-stone-100">
                                    {player?.hp ?? 0}/{player?.max_hp ?? 10}
                                </div>
                            </div>
                            <div className="rounded-lg border border-stone-800 bg-stone-900/30 p-2 text-center">
                                <div className="flex items-center justify-center gap-1 text-xs text-stone-500">
                                    <Compass className="h-3 w-3 text-blue-400" />
                                    Energy
                                </div>
                                <div className="text-sm font-semibold text-stone-100">
                                    {player?.energy ?? 0}/{player?.max_energy ?? 100}
                                </div>
                            </div>
                            <div className="rounded-lg border border-stone-800 bg-stone-900/30 p-2 text-center">
                                <div className="flex items-center justify-center gap-1 text-xs text-stone-500">
                                    <Coins className="h-3 w-3 text-amber-400" />
                                    Gold
                                </div>
                                <div className="text-sm font-semibold text-stone-100">
                                    {(player?.gold ?? 0).toLocaleString()}
                                </div>
                            </div>
                            <div className="rounded-lg border border-stone-800 bg-stone-900/30 p-2 text-center">
                                <div className="flex items-center justify-center gap-1 text-xs text-stone-500">
                                    <Shield className="h-3 w-3 text-purple-400" />
                                    Combat
                                </div>
                                <div className="text-sm font-semibold text-stone-100">
                                    Lv. {player?.combat_level ?? 1}
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Your Location - Top Right */}
                    {location && (
                        <div className="rounded-xl border border-stone-800 bg-stone-900/50 p-4">
                            <div className="flex items-center gap-3">
                                <div className="rounded-lg bg-green-900/30 p-2">
                                    <MapPin className="h-5 w-5 text-green-400" />
                                </div>
                                <div className="min-w-0 flex-1">
                                    <h3 className="font-[Cinzel] text-sm font-bold text-stone-100">
                                        {location.name}
                                    </h3>
                                    <p className="text-xs capitalize text-stone-500">
                                        {location.type} • {location.biome}
                                    </p>
                                </div>
                            </div>
                            <Link
                                href={`/${pluralizeLocationType(location.type)}/${location.id}`}
                                className="mt-3 flex w-full items-center justify-center gap-2 rounded-lg bg-stone-800 px-3 py-1.5 text-sm font-medium text-stone-300 transition hover:bg-stone-700"
                            >
                                Explore <ArrowRight className="h-3 w-3" />
                            </Link>
                            {homeVillage && homeVillage.id !== location.id && (
                                <div className="mt-2 flex items-center gap-1 text-xs text-stone-500">
                                    <Home className="h-3 w-3" />
                                    Home:{" "}
                                    <Link
                                        href={`/villages/${homeVillage.id}`}
                                        className="text-amber-400 hover:underline"
                                    >
                                        {homeVillage.name}
                                    </Link>
                                </div>
                            )}
                        </div>
                    )}
                </div>

                {/* Quick Actions + Getting Started side by side */}
                <div className="grid gap-4 lg:grid-cols-2">
                    {/* Quick Actions */}
                    <div>
                        <h2 className="mb-2 font-[Cinzel] text-sm font-bold text-stone-100">
                            Quick Actions
                        </h2>
                        <div className="grid grid-cols-2 gap-2">
                            {quickActions.map((action) => (
                                <Link
                                    key={action.title}
                                    href={action.href}
                                    className="group flex items-center gap-2 rounded-lg border border-stone-800 bg-stone-900/50 p-2 transition hover:border-stone-700"
                                >
                                    <div className={`rounded bg-${action.color}-900/30 p-1.5`}>
                                        <action.icon
                                            className={`h-4 w-4 text-${action.color}-400`}
                                        />
                                    </div>
                                    <div className="min-w-0 flex-1">
                                        <div className="text-sm font-medium text-stone-100 group-hover:text-amber-400">
                                            {action.title}
                                        </div>
                                        <div className="truncate text-xs text-stone-500">
                                            {action.description}
                                        </div>
                                    </div>
                                </Link>
                            ))}
                        </div>
                    </div>

                    {/* Getting Started */}
                    <div>
                        <h2 className="mb-2 font-[Cinzel] text-sm font-bold text-stone-100">
                            Getting Started
                        </h2>
                        <div className="grid grid-cols-2 gap-2">
                            {gettingStarted.map((step, index) => (
                                <Link
                                    key={step.title}
                                    href={step.href}
                                    className="group flex items-center gap-2 rounded-lg border border-stone-800 bg-stone-900/50 p-2 transition hover:border-stone-700"
                                >
                                    <div className="flex h-5 w-5 flex-shrink-0 items-center justify-center rounded-full bg-stone-800 text-xs font-semibold text-stone-500">
                                        {index + 1}
                                    </div>
                                    <div className="min-w-0 flex-1">
                                        <div className="text-sm font-medium text-stone-100 group-hover:text-amber-400">
                                            {step.title}
                                        </div>
                                        <div className="truncate text-xs text-stone-500">
                                            {step.description}
                                        </div>
                                    </div>
                                </Link>
                            ))}
                        </div>
                    </div>
                </div>

                {/* What You Can Do - Game Features */}
                <div>
                    <h2 className="mb-3 font-[Cinzel] text-sm font-bold text-stone-100">
                        What You Can Do
                    </h2>
                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        {gameFeatures.map((category) => {
                            const colorClasses: Record<
                                string,
                                { border: string; bg: string; text: string; iconBg: string }
                            > = {
                                red: {
                                    border: "border-red-900/50",
                                    bg: "bg-red-900/10",
                                    text: "text-red-400",
                                    iconBg: "bg-red-900/30",
                                },
                                amber: {
                                    border: "border-amber-900/50",
                                    bg: "bg-amber-900/10",
                                    text: "text-amber-400",
                                    iconBg: "bg-amber-900/30",
                                },
                                pink: {
                                    border: "border-pink-900/50",
                                    bg: "bg-pink-900/10",
                                    text: "text-pink-400",
                                    iconBg: "bg-pink-900/30",
                                },
                                blue: {
                                    border: "border-blue-900/50",
                                    bg: "bg-blue-900/10",
                                    text: "text-blue-400",
                                    iconBg: "bg-blue-900/30",
                                },
                                orange: {
                                    border: "border-orange-900/50",
                                    bg: "bg-orange-900/10",
                                    text: "text-orange-400",
                                    iconBg: "bg-orange-900/30",
                                },
                                green: {
                                    border: "border-green-900/50",
                                    bg: "bg-green-900/10",
                                    text: "text-green-400",
                                    iconBg: "bg-green-900/30",
                                },
                            };
                            const colors = colorClasses[category.color] || colorClasses.amber;

                            return (
                                <div
                                    key={category.category}
                                    className={`rounded-xl border ${colors.border} ${colors.bg} p-4`}
                                >
                                    <h3
                                        className={`mb-3 font-[Cinzel] text-sm font-semibold ${colors.text}`}
                                    >
                                        {category.category}
                                    </h3>
                                    <div className="grid grid-cols-3 gap-2">
                                        {category.items.map((item) => (
                                            <Link
                                                key={item.name}
                                                href={item.href}
                                                className="group flex flex-col items-center gap-1 rounded-lg border border-stone-800 bg-stone-900/50 p-3 transition hover:border-stone-600 hover:bg-stone-800/80"
                                            >
                                                <div className={`rounded-lg ${colors.iconBg} p-2`}>
                                                    <item.icon
                                                        className={`h-5 w-5 ${colors.text} group-hover:scale-110 transition-transform`}
                                                    />
                                                </div>
                                                <span className="text-center text-xs text-stone-300 group-hover:text-white">
                                                    {item.name}
                                                </span>
                                            </Link>
                                        ))}
                                    </div>
                                </div>
                            );
                        })}
                    </div>
                </div>

                {/* Tips */}
                <div className="rounded-xl border border-amber-900/30 bg-amber-900/10 p-4">
                    <div className="flex items-center gap-2 mb-2">
                        <Lightbulb className="h-4 w-4 text-amber-400" />
                        <h3 className="font-[Cinzel] text-sm font-bold text-amber-400">Tips</h3>
                    </div>
                    <div className="grid gap-x-6 gap-y-1 text-xs text-stone-400 sm:grid-cols-2">
                        <div>
                            • Energy regenerates over time - use it for training and gathering
                        </div>
                        <div>• Complete daily tasks for bonus rewards</div>
                        <div>• Check the notice board for quests</div>
                        <div>• Travel costs energy - plan journeys carefully</div>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
