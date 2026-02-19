import { Head, Link, usePage } from "@inertiajs/react";
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogDescription,
} from "@/components/ui/dialog";
import {
    Anchor,
    Anvil,
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
    Gift,
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
import { pluralizeLocationType } from "@/lib/utils";
import type { BreadcrumbItem } from "@/types";

interface DashboardKingdom {
    id: number;
    name: string;
    description: string | null;
    biome: string;
    tax_rate: string;
    baronies_count: number;
    total_villages: number;
    total_towns: number;
    total_settlements: number;
    total_population: number;
    total_wealth: number;
    total_ports: number;
    player_count: number;
    king: {
        id: number;
        username: string;
        primary_title: string | null;
    } | null;
}

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

interface LocationKingdom {
    id: number;
    name: string;
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
        kingdom?: LocationKingdom | null;
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
    const { sidebar, showTutorial, kingdoms } = usePage<{
        sidebar: SidebarData;
        showTutorial: boolean;
        kingdoms: DashboardKingdom[];
    }>().props;
    const [tutorialOpen, setTutorialOpen] = useState(showTutorial);
    const [selectedKingdom, setSelectedKingdom] = useState<DashboardKingdom | null>(null);

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
            href: location
                ? `/${pluralizeLocationType(location.type)}/${location.id}/training`
                : "/villages",
            icon: Swords,
        },
        {
            title: "Gather Resources",
            description: "Mine, fish, or chop wood",
            href: location
                ? `/${pluralizeLocationType(location.type)}/${location.id}/gathering`
                : "/villages",
            icon: Pickaxe,
        },
        {
            title: "Visit Market",
            description: "Buy and sell goods",
            href: location
                ? `/${pluralizeLocationType(location.type)}/${location.id}/market`
                : "/villages",
            icon: Coins,
        },
        {
            title: "Find Work",
            description: "Apply for a job",
            href: location
                ? `/${pluralizeLocationType(location.type)}/${location.id}/jobs`
                : "/villages",
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
                    href: location
                        ? `/${pluralizeLocationType(location.type)}/${location.id}/training`
                        : "/villages",
                    icon: Swords,
                },
                { name: "Combat", href: "/combat", icon: Shield },
                features?.dungeon &&
                    location?.kingdom && {
                        name: "Dungeons",
                        href: `/kingdoms/${location.kingdom.id}/dungeons`,
                        icon: Sparkles,
                    },
            ].filter(Boolean),
        },
        {
            category: "Economy & Trade",
            color: "amber",
            items: [
                features?.bank && {
                    name: "Banking",
                    href: location
                        ? `/${pluralizeLocationType(location.type)}/${location.id}/bank`
                        : "/villages",
                    icon: Banknote,
                },
                features?.market && {
                    name: "Market",
                    href: location
                        ? `/${pluralizeLocationType(location.type)}/${location.id}/market`
                        : "/villages",
                    icon: Store,
                },
                features?.crafting && {
                    name: "Crafting",
                    href: location
                        ? `/${pluralizeLocationType(location.type)}/${location.id}/crafting`
                        : "/villages",
                    icon: Hammer,
                },
                features?.crafting && {
                    name: "Forge",
                    href: location
                        ? `/${pluralizeLocationType(location.type)}/${location.id}/forge`
                        : "/villages",
                    icon: Anvil,
                },
                { name: "Caravans", href: "/trade/caravans", icon: Truck },
                features?.jobs && {
                    name: "Jobs",
                    href: location
                        ? `/${pluralizeLocationType(location.type)}/${location.id}/jobs`
                        : "/villages",
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
                {
                    name: "Shrine",
                    href: location
                        ? `/${pluralizeLocationType(location.type)}/${location.id}/shrine`
                        : "/shrine",
                    icon: Church,
                },
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
                    href: location
                        ? `/${pluralizeLocationType(location.type)}/${location.id}/stables`
                        : "/towns",
                    icon: Gauge,
                },
                features?.port && {
                    name: "Sea Port",
                    href: location
                        ? `/${pluralizeLocationType(location.type)}/${location.id}/port`
                        : "/villages",
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
                        <div className="mt-3 grid grid-cols-2 gap-2 sm:grid-cols-4">
                            <div className="flex items-center gap-2 rounded-lg border border-red-500/30 bg-red-900/20 px-2 py-2">
                                <Heart className="h-5 w-5 shrink-0 text-red-400" />
                                <div className="min-w-0">
                                    <div className="font-pixel text-sm text-red-400">
                                        {player?.hp ?? 0}/{player?.max_hp ?? 10}
                                    </div>
                                    <div className="text-[10px] text-stone-400">HP</div>
                                </div>
                            </div>
                            <div className="flex items-center gap-2 rounded-lg border border-green-500/30 bg-green-900/20 px-2 py-2">
                                <Compass className="h-5 w-5 shrink-0 text-green-400" />
                                <div className="min-w-0">
                                    <div className="font-pixel text-sm text-green-400">
                                        {player?.energy ?? 0}/{player?.max_energy ?? 100}
                                    </div>
                                    <div className="text-[10px] text-stone-400">Energy</div>
                                </div>
                            </div>
                            <div className="flex items-center gap-2 rounded-lg border border-amber-500/30 bg-amber-900/20 px-2 py-2">
                                <Coins className="h-5 w-5 shrink-0 text-amber-400" />
                                <div className="min-w-0">
                                    <div className="font-pixel text-sm text-amber-400">
                                        {(player?.gold ?? 0).toLocaleString()}
                                    </div>
                                    <div className="text-[10px] text-stone-400">Gold</div>
                                </div>
                            </div>
                            <div className="flex items-center gap-2 rounded-lg border border-purple-500/30 bg-purple-900/20 px-2 py-2">
                                <Shield className="h-5 w-5 shrink-0 text-purple-400" />
                                <div className="min-w-0">
                                    <div className="font-pixel text-sm text-purple-400">
                                        Lv. {player?.combat_level ?? 1}
                                    </div>
                                    <div className="text-[10px] text-stone-400">Combat</div>
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

                {/* Kings of the Realm */}
                {kingdoms && kingdoms.length > 0 && (
                    <div>
                        <h2 className="mb-2 font-[Cinzel] text-sm font-bold text-stone-100">
                            Kings of the Realm
                        </h2>
                        <div className="grid grid-cols-2 gap-2 lg:grid-cols-4">
                            {kingdoms.map((kingdom) => {
                                const biomeColors: Record<
                                    string,
                                    { text: string; border: string; bg: string }
                                > = {
                                    plains: {
                                        text: "text-green-400",
                                        border: "border-green-600/40",
                                        bg: "bg-green-900/20",
                                    },
                                    forest: {
                                        text: "text-emerald-400",
                                        border: "border-emerald-600/40",
                                        bg: "bg-emerald-900/20",
                                    },
                                    tundra: {
                                        text: "text-cyan-400",
                                        border: "border-cyan-600/40",
                                        bg: "bg-cyan-900/20",
                                    },
                                    coastal: {
                                        text: "text-blue-400",
                                        border: "border-blue-600/40",
                                        bg: "bg-blue-900/20",
                                    },
                                    desert: {
                                        text: "text-amber-400",
                                        border: "border-amber-600/40",
                                        bg: "bg-amber-900/20",
                                    },
                                    volcano: {
                                        text: "text-red-400",
                                        border: "border-red-600/40",
                                        bg: "bg-red-900/20",
                                    },
                                    mountains: {
                                        text: "text-slate-400",
                                        border: "border-slate-600/40",
                                        bg: "bg-slate-900/20",
                                    },
                                    swamps: {
                                        text: "text-lime-400",
                                        border: "border-lime-600/40",
                                        bg: "bg-lime-900/20",
                                    },
                                    tropical: {
                                        text: "text-teal-400",
                                        border: "border-teal-600/40",
                                        bg: "bg-teal-900/20",
                                    },
                                };
                                const colors = biomeColors[kingdom.biome] || biomeColors.plains;

                                return (
                                    <button
                                        key={kingdom.id}
                                        type="button"
                                        onClick={() => setSelectedKingdom(kingdom)}
                                        className={`group rounded-lg border ${colors.border} ${colors.bg} p-3 text-left transition hover:border-stone-600`}
                                    >
                                        <div className="flex items-center gap-2">
                                            <Crown className={`h-4 w-4 shrink-0 ${colors.text}`} />
                                            <span className="truncate font-[Cinzel] text-sm font-bold text-stone-100 group-hover:text-amber-400">
                                                {kingdom.name}
                                            </span>
                                        </div>
                                        <div className="mt-1.5 text-xs text-stone-400">
                                            {kingdom.king ? (
                                                <span>
                                                    <span className="text-stone-300">
                                                        {kingdom.king.username}
                                                    </span>
                                                    {" — "}
                                                    <span className="capitalize">
                                                        {kingdom.king.primary_title ?? "King"}
                                                    </span>
                                                </span>
                                            ) : (
                                                <span className="italic text-stone-500">
                                                    Throne vacant
                                                </span>
                                            )}
                                        </div>
                                        <div className="mt-1.5 flex items-center gap-3 text-[10px] text-stone-500">
                                            <span>{kingdom.baronies_count} baronies</span>
                                            <span className="capitalize">{kingdom.biome}</span>
                                            <span>{kingdom.tax_rate}% tax</span>
                                        </div>
                                    </button>
                                );
                            })}
                        </div>
                    </div>
                )}

                {/* Kingdom Detail Modal */}
                <Dialog
                    open={!!selectedKingdom}
                    onOpenChange={(open) => !open && setSelectedKingdom(null)}
                >
                    <DialogContent className="gap-0 overflow-hidden border-stone-700 bg-stone-900 p-0 text-stone-100">
                        {selectedKingdom &&
                            (() => {
                                const biomeTheme: Record<
                                    string,
                                    { text: string; bg: string; border: string; accent: string }
                                > = {
                                    plains: {
                                        text: "text-green-400",
                                        bg: "bg-green-900/30",
                                        border: "border-green-600/30",
                                        accent: "from-green-900/40",
                                    },
                                    forest: {
                                        text: "text-emerald-400",
                                        bg: "bg-emerald-900/30",
                                        border: "border-emerald-600/30",
                                        accent: "from-emerald-900/40",
                                    },
                                    tundra: {
                                        text: "text-cyan-400",
                                        bg: "bg-cyan-900/30",
                                        border: "border-cyan-600/30",
                                        accent: "from-cyan-900/40",
                                    },
                                    coastal: {
                                        text: "text-blue-400",
                                        bg: "bg-blue-900/30",
                                        border: "border-blue-600/30",
                                        accent: "from-blue-900/40",
                                    },
                                    desert: {
                                        text: "text-amber-400",
                                        bg: "bg-amber-900/30",
                                        border: "border-amber-600/30",
                                        accent: "from-amber-900/40",
                                    },
                                    volcano: {
                                        text: "text-red-400",
                                        bg: "bg-red-900/30",
                                        border: "border-red-600/30",
                                        accent: "from-red-900/40",
                                    },
                                    mountains: {
                                        text: "text-slate-400",
                                        bg: "bg-slate-900/30",
                                        border: "border-slate-600/30",
                                        accent: "from-slate-900/40",
                                    },
                                    swamps: {
                                        text: "text-lime-400",
                                        bg: "bg-lime-900/30",
                                        border: "border-lime-600/30",
                                        accent: "from-lime-900/40",
                                    },
                                    tropical: {
                                        text: "text-teal-400",
                                        bg: "bg-teal-900/30",
                                        border: "border-teal-600/30",
                                        accent: "from-teal-900/40",
                                    },
                                };
                                const theme =
                                    biomeTheme[selectedKingdom.biome] || biomeTheme.plains;

                                return (
                                    <>
                                        {/* Header with gradient */}
                                        <div
                                            className={`bg-gradient-to-b ${theme.accent} to-transparent px-6 pt-6 pb-4`}
                                        >
                                            <DialogHeader>
                                                <DialogTitle className="flex items-center gap-2.5 font-[Cinzel] text-xl text-stone-100">
                                                    <div
                                                        className={`rounded-lg ${theme.bg} border ${theme.border} p-2`}
                                                    >
                                                        <Crown
                                                            className={`h-5 w-5 ${theme.text}`}
                                                        />
                                                    </div>
                                                    {selectedKingdom.name}
                                                </DialogTitle>
                                                <DialogDescription className="mt-1 leading-relaxed text-stone-400">
                                                    {selectedKingdom.description ||
                                                        "A kingdom in the realm of Refell."}
                                                </DialogDescription>
                                            </DialogHeader>
                                        </div>

                                        <div className="space-y-4 px-6 pb-6">
                                            {/* Ruler */}
                                            <div
                                                className={`flex items-center gap-3 rounded-lg border ${theme.border} ${theme.bg} p-3`}
                                            >
                                                <div className="rounded-full bg-stone-800 p-2">
                                                    <Crown className="h-4 w-4 text-amber-400" />
                                                </div>
                                                {selectedKingdom.king ? (
                                                    <div>
                                                        <div className="text-sm font-semibold text-stone-200">
                                                            {selectedKingdom.king.username}
                                                        </div>
                                                        <div className="text-xs capitalize text-stone-500">
                                                            {selectedKingdom.king.primary_title ??
                                                                "King"}{" "}
                                                            of {selectedKingdom.name}
                                                        </div>
                                                    </div>
                                                ) : (
                                                    <div>
                                                        <div className="text-sm font-semibold italic text-stone-500">
                                                            Throne Vacant
                                                        </div>
                                                        <div className="text-xs text-stone-600">
                                                            No ruler sits upon the throne
                                                        </div>
                                                    </div>
                                                )}
                                            </div>

                                            {/* Stats grid */}
                                            <div>
                                                <h4 className="mb-2 text-[10px] font-semibold uppercase tracking-widest text-stone-600">
                                                    Kingdom Statistics
                                                </h4>
                                                <div className="grid grid-cols-3 gap-2">
                                                    {[
                                                        {
                                                            label: "Biome",
                                                            value: selectedKingdom.biome,
                                                            capitalize: true,
                                                            color: theme.text,
                                                        },
                                                        {
                                                            label: "Tax Rate",
                                                            value: `${selectedKingdom.tax_rate}%`,
                                                        },
                                                        {
                                                            label: "Players",
                                                            value: selectedKingdom.player_count.toLocaleString(),
                                                        },
                                                        {
                                                            label: "Population",
                                                            value: selectedKingdom.total_population.toLocaleString(),
                                                        },
                                                        {
                                                            label: "Wealth",
                                                            value: selectedKingdom.total_wealth.toLocaleString(),
                                                        },
                                                        {
                                                            label: "Baronies",
                                                            value: selectedKingdom.baronies_count,
                                                        },
                                                        {
                                                            label: "Towns",
                                                            value: selectedKingdom.total_towns,
                                                        },
                                                        {
                                                            label: "Villages",
                                                            value: selectedKingdom.total_villages,
                                                        },
                                                        {
                                                            label: "Ports",
                                                            value: selectedKingdom.total_ports,
                                                        },
                                                    ].map((stat) => (
                                                        <div
                                                            key={stat.label}
                                                            className="rounded-lg border border-stone-800 bg-stone-800/40 px-3 py-2"
                                                        >
                                                            <div className="text-[10px] text-stone-500">
                                                                {stat.label}
                                                            </div>
                                                            <div
                                                                className={`font-pixel text-sm ${stat.color ?? "text-stone-200"} ${stat.capitalize ? "capitalize" : ""}`}
                                                            >
                                                                {stat.value}
                                                            </div>
                                                        </div>
                                                    ))}
                                                </div>
                                            </div>
                                        </div>
                                    </>
                                );
                            })()}
                    </DialogContent>
                </Dialog>

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
                                    <div className="grid grid-cols-5 gap-2">
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

                {/* Tips + Referral */}
                <div className="grid gap-4 lg:grid-cols-3">
                    {/* Tips */}
                    <div className="rounded-xl border border-amber-900/30 bg-amber-900/10 p-4 lg:col-span-2">
                        <div className="mb-2 flex items-center gap-2">
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

                    {/* Invite Friends */}
                    <Link
                        href="/referrals"
                        className="group flex items-center gap-3 rounded-xl border border-green-900/30 bg-green-900/10 p-4 transition hover:border-green-700/50 hover:bg-green-900/20"
                    >
                        <div className="rounded-lg bg-green-900/30 p-2.5">
                            <Gift className="h-6 w-6 text-green-400" />
                        </div>
                        <div className="flex-1">
                            <h3 className="font-[Cinzel] text-sm font-bold text-green-400">
                                Invite Friends
                            </h3>
                            <p className="text-xs text-stone-400">
                                Earn 250 gold for each friend who joins!
                            </p>
                        </div>
                        <ArrowRight className="h-4 w-4 text-green-400 opacity-0 transition group-hover:opacity-100" />
                    </Link>
                </div>
            </div>
        </AppLayout>
    );
}
