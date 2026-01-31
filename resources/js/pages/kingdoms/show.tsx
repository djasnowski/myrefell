import { Head, Link } from "@inertiajs/react";
import {
    Anchor,
    Briefcase,
    Building,
    ChevronDown,
    ChevronRight,
    Coins,
    Crown,
    Dumbbell,
    Home,
    MapPin,
    Mountain,
    Palmtree,
    Percent,
    ScrollText,
    Shield,
    Snowflake,
    Sparkles,
    Sun,
    TreePine,
    Trees,
    Users,
    Vote,
    Waves,
    Wheat,
    type LucideIcon,
} from "lucide-react";
import { useState } from "react";
import { Bar, BarChart, CartesianGrid, ResponsiveContainer, Tooltip, XAxis, YAxis } from "recharts";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { RulerDisplay } from "@/components/ui/legitimacy-badge";
import AppLayout from "@/layouts/app-layout";
import type { BreadcrumbItem } from "@/types";

interface Settlement {
    id: number;
    name: string;
    type: "town" | "village" | "hamlet";
    is_capital?: boolean;
    is_port?: boolean;
    population: number;
}

interface Barony {
    id: number;
    name: string;
    biome: string;
    is_capital: boolean;
    village_count: number;
    town_count: number;
    population: number;
    wealth: number;
    baron: {
        id: number;
        username: string;
    } | null;
    settlements: Settlement[];
}

interface Ruler {
    id: number;
    username: string;
    primary_title?: string | null;
    legitimacy?: number;
}

interface Kingdom {
    id: number;
    name: string;
    description: string;
    biome: string;
    tax_rate: number;
    coordinates: {
        x: number;
        y: number;
    };
    capital: {
        id: number;
        name: string;
        biome: string;
    } | null;
    baronies: Barony[];
    barony_count: number;
    total_villages: number;
    total_towns: number;
    total_population: number;
    total_wealth: number;
    total_ports: number;
    player_count: number;
    king?: Ruler | null;
}

interface Props {
    kingdom: Kingdom;
    current_user_id: number;
}

const biomeConfig: Record<string, { icon: LucideIcon; color: string; bg: string; border: string }> =
    {
        plains: {
            icon: Wheat,
            color: "text-green-400",
            bg: "bg-green-900/30",
            border: "border-green-600/50",
        },
        forest: {
            icon: Trees,
            color: "text-emerald-400",
            bg: "bg-emerald-900/30",
            border: "border-emerald-600/50",
        },
        tundra: {
            icon: Snowflake,
            color: "text-cyan-400",
            bg: "bg-cyan-900/30",
            border: "border-cyan-600/50",
        },
        coastal: {
            icon: Waves,
            color: "text-blue-400",
            bg: "bg-blue-900/30",
            border: "border-blue-600/50",
        },
        desert: {
            icon: Sun,
            color: "text-amber-400",
            bg: "bg-amber-900/30",
            border: "border-amber-600/50",
        },
        volcano: {
            icon: Mountain,
            color: "text-red-400",
            bg: "bg-red-900/30",
            border: "border-red-600/50",
        },
        mountains: {
            icon: Mountain,
            color: "text-slate-400",
            bg: "bg-slate-900/30",
            border: "border-slate-600/50",
        },
        swamps: {
            icon: TreePine,
            color: "text-lime-400",
            bg: "bg-lime-900/30",
            border: "border-lime-600/50",
        },
        tropical: {
            icon: Palmtree,
            color: "text-teal-400",
            bg: "bg-teal-900/30",
            border: "border-teal-600/50",
        },
    };

function HierarchyTree({ baronies }: { baronies: Barony[] }) {
    const [expandedBaronies, setExpandedBaronies] = useState<Set<number>>(new Set());

    const toggleBarony = (id: number) => {
        setExpandedBaronies((prev) => {
            const next = new Set(prev);
            if (next.has(id)) {
                next.delete(id);
            } else {
                next.add(id);
            }
            return next;
        });
    };

    const expandAll = () => {
        setExpandedBaronies(new Set(baronies.map((b) => b.id)));
    };

    const collapseAll = () => {
        setExpandedBaronies(new Set());
    };

    return (
        <div className="space-y-2">
            <div className="mb-3 flex items-center justify-between">
                <h3 className="font-pixel text-sm text-stone-400">Kingdom Hierarchy</h3>
                <div className="flex gap-2">
                    <button
                        onClick={expandAll}
                        className="rounded px-2 py-1 text-xs text-stone-400 transition hover:bg-stone-700 hover:text-stone-200"
                    >
                        Expand All
                    </button>
                    <button
                        onClick={collapseAll}
                        className="rounded px-2 py-1 text-xs text-stone-400 transition hover:bg-stone-700 hover:text-stone-200"
                    >
                        Collapse All
                    </button>
                </div>
            </div>
            <div className="rounded-lg border border-stone-700 bg-stone-800/30 p-4">
                {baronies.map((barony, idx) => {
                    const isExpanded = expandedBaronies.has(barony.id);
                    const isLast = idx === baronies.length - 1;
                    const sortedSettlements = [...barony.settlements].sort((a, b) => {
                        if (a.type === "town" && b.type !== "town") return -1;
                        if (a.type !== "town" && b.type === "town") return 1;
                        if (a.is_capital) return -1;
                        if (b.is_capital) return 1;
                        return a.name.localeCompare(b.name);
                    });

                    return (
                        <div key={barony.id} className={!isLast ? "mb-2" : ""}>
                            <button
                                onClick={() => toggleBarony(barony.id)}
                                className="group flex w-full items-center gap-2 rounded p-1 text-left transition hover:bg-stone-700/50"
                            >
                                {isExpanded ? (
                                    <ChevronDown className="h-4 w-4 text-stone-500" />
                                ) : (
                                    <ChevronRight className="h-4 w-4 text-stone-500" />
                                )}
                                <Shield className="h-4 w-4 text-amber-400" />
                                <Link
                                    href={`/baronies/${barony.id}`}
                                    className="font-medium text-stone-200 hover:text-amber-400 hover:underline"
                                    onClick={(e) => e.stopPropagation()}
                                >
                                    {barony.name}
                                </Link>
                                {barony.is_capital && (
                                    <span className="text-xs text-amber-500">(Capital Region)</span>
                                )}
                                <span className="ml-auto text-xs text-stone-500">
                                    {barony.settlements.length} settlements
                                </span>
                            </button>
                            {isExpanded && sortedSettlements.length > 0 && (
                                <div className="ml-6 mt-1 space-y-1 border-l border-stone-700 pl-4">
                                    {sortedSettlements.map((settlement, sIdx) => {
                                        const isSettlementLast =
                                            sIdx === sortedSettlements.length - 1;
                                        return (
                                            <div
                                                key={`${settlement.type}-${settlement.id}`}
                                                className={`flex items-center gap-2 text-sm ${!isSettlementLast ? "pb-1" : ""}`}
                                            >
                                                {settlement.type === "town" ? (
                                                    <Building className="h-3 w-3 text-purple-400" />
                                                ) : settlement.type === "hamlet" ? (
                                                    <Home className="h-3 w-3 text-stone-500" />
                                                ) : (
                                                    <Home className="h-3 w-3 text-stone-400" />
                                                )}
                                                <Link
                                                    href={
                                                        settlement.type === "town"
                                                            ? `/towns/${settlement.id}`
                                                            : `/villages/${settlement.id}`
                                                    }
                                                    className="text-stone-300 hover:text-amber-400 hover:underline"
                                                >
                                                    {settlement.name}
                                                </Link>
                                                {settlement.is_capital && (
                                                    <Crown
                                                        className="h-3 w-3 text-amber-400"
                                                        title="Kingdom Capital"
                                                    />
                                                )}
                                                {settlement.is_port && (
                                                    <Anchor
                                                        className="h-3 w-3 text-blue-400"
                                                        title="Port"
                                                    />
                                                )}
                                                <span className="ml-auto text-xs text-stone-500">
                                                    {settlement.population.toLocaleString()} pop
                                                </span>
                                            </div>
                                        );
                                    })}
                                </div>
                            )}
                        </div>
                    );
                })}
            </div>
        </div>
    );
}

export default function KingdomShow({ kingdom, current_user_id }: Props) {
    const biome = biomeConfig[kingdom.biome] || biomeConfig.plains;
    const BiomeIcon = biome.icon;

    const breadcrumbs: BreadcrumbItem[] = [
        { title: "Dashboard", href: "/dashboard" },
        { title: "Kingdoms", href: "/kingdoms" },
        { title: kingdom.name, href: `/kingdoms/${kingdom.id}` },
    ];

    // Prepare chart data
    const baronyPopulationData = kingdom.baronies
        .map((b) => ({
            name: b.name.length > 12 ? b.name.slice(0, 10) + "..." : b.name,
            population: b.population,
            villages: b.village_count,
            towns: b.town_count,
        }))
        .sort((a, b) => b.population - a.population);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={kingdom.name} />
            <div className="flex flex-col gap-6 p-6">
                {/* Hero Header */}
                <div className={`rounded-xl border-2 ${biome.border} ${biome.bg} p-6`}>
                    <div className="flex items-start gap-4">
                        <div className={`rounded-xl ${biome.bg} border ${biome.border} p-4`}>
                            <BiomeIcon className={`h-12 w-12 ${biome.color}`} />
                        </div>
                        <div className="flex-1">
                            <div className="flex items-center gap-3">
                                <h1 className="font-[Cinzel] text-3xl font-bold text-stone-100">
                                    {kingdom.name}
                                </h1>
                                <span
                                    className={`rounded-full ${biome.bg} border ${biome.border} px-3 py-0.5 text-xs capitalize ${biome.color}`}
                                >
                                    {kingdom.biome}
                                </span>
                            </div>
                            <p className="mt-2 text-stone-400">{kingdom.description}</p>

                            {/* Capital link */}
                            {kingdom.capital && (
                                <div className="mt-3 flex items-center gap-2 text-sm">
                                    <Crown className="h-4 w-4 text-amber-400" />
                                    <span className="text-stone-500">Capital:</span>
                                    <Link
                                        href={`/towns/${kingdom.capital.id}`}
                                        className="text-amber-400 hover:underline"
                                    >
                                        {kingdom.capital.name}
                                    </Link>
                                </div>
                            )}
                        </div>
                    </div>
                </div>

                {/* Stats Row */}
                <div className="grid grid-cols-2 gap-3 sm:grid-cols-4 lg:grid-cols-8">
                    <div className="rounded-lg border border-stone-700 bg-stone-800/50 p-4 text-center">
                        <Users className="mx-auto mb-2 h-6 w-6 text-blue-400" />
                        <div className="font-pixel text-2xl text-stone-100">
                            {kingdom.player_count}
                        </div>
                        <div className="text-xs text-stone-500">Players</div>
                    </div>
                    <div className="rounded-lg border border-stone-700 bg-stone-800/50 p-4 text-center">
                        <Users className="mx-auto mb-2 h-6 w-6 text-stone-400" />
                        <div className="font-pixel text-2xl text-stone-100">
                            {kingdom.total_population.toLocaleString()}
                        </div>
                        <div className="text-xs text-stone-500">Population</div>
                    </div>
                    <div className="rounded-lg border border-stone-700 bg-stone-800/50 p-4 text-center">
                        <Coins className="mx-auto mb-2 h-6 w-6 text-amber-400" />
                        <div className="font-pixel text-2xl text-amber-300">
                            {kingdom.total_wealth.toLocaleString()}
                        </div>
                        <div className="text-xs text-stone-500">Total Wealth</div>
                    </div>
                    <div className="rounded-lg border border-stone-700 bg-stone-800/50 p-4 text-center">
                        <Shield className="mx-auto mb-2 h-6 w-6 text-purple-400" />
                        <div className="font-pixel text-2xl text-stone-100">
                            {kingdom.barony_count}
                        </div>
                        <div className="text-xs text-stone-500">Baronies</div>
                    </div>
                    <div className="rounded-lg border border-stone-700 bg-stone-800/50 p-4 text-center">
                        <Building className="mx-auto mb-2 h-6 w-6 text-purple-400" />
                        <div className="font-pixel text-2xl text-stone-100">
                            {kingdom.total_towns}
                        </div>
                        <div className="text-xs text-stone-500">Towns</div>
                    </div>
                    <div className="rounded-lg border border-stone-700 bg-stone-800/50 p-4 text-center">
                        <Home className="mx-auto mb-2 h-6 w-6 text-stone-400" />
                        <div className="font-pixel text-2xl text-stone-100">
                            {kingdom.total_villages}
                        </div>
                        <div className="text-xs text-stone-500">Villages</div>
                    </div>
                    <div className="rounded-lg border border-stone-700 bg-stone-800/50 p-4 text-center">
                        <Anchor className="mx-auto mb-2 h-6 w-6 text-blue-400" />
                        <div className="font-pixel text-2xl text-stone-100">
                            {kingdom.total_ports}
                        </div>
                        <div className="text-xs text-stone-500">Ports</div>
                    </div>
                    <div className="rounded-lg border border-stone-700 bg-stone-800/50 p-4 text-center">
                        <Percent className="mx-auto mb-2 h-6 w-6 text-red-400" />
                        <div className="font-pixel text-2xl text-stone-100">
                            {kingdom.tax_rate}%
                        </div>
                        <div className="text-xs text-stone-500">Tax Rate</div>
                    </div>
                </div>

                {/* King / Ruler */}
                <RulerDisplay
                    ruler={kingdom.king}
                    title="King"
                    isCurrentUser={kingdom.king?.id === current_user_id}
                />

                {/* Services & Quick Actions */}
                <div>
                    <h2 className="mb-4 font-pixel text-lg text-stone-300">Kingdom Services</h2>
                    <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                        <Link
                            href={`/kingdoms/${kingdom.id}/roles`}
                            className="flex items-center gap-3 rounded-lg border-2 border-amber-600/50 bg-amber-900/20 p-4 transition hover:bg-amber-800/30"
                        >
                            <Shield className="h-8 w-8 text-amber-400" />
                            <div>
                                <span className="font-pixel text-sm text-amber-300">Roles</span>
                                <p className="text-xs text-stone-500">Officials & positions</p>
                            </div>
                        </Link>
                        <Link
                            href={`/kingdoms/${kingdom.id}/jobs`}
                            className="flex items-center gap-3 rounded-lg border-2 border-blue-600/50 bg-blue-900/20 p-4 transition hover:bg-blue-800/30"
                        >
                            <Briefcase className="h-8 w-8 text-blue-400" />
                            <div>
                                <span className="font-pixel text-sm text-blue-300">Jobs</span>
                                <p className="text-xs text-stone-500">Employment opportunities</p>
                            </div>
                        </Link>
                        <Link
                            href={`/kingdoms/${kingdom.id}/taxes`}
                            className="flex items-center gap-3 rounded-lg border-2 border-red-600/50 bg-red-900/20 p-4 transition hover:bg-red-800/30"
                        >
                            <Coins className="h-8 w-8 text-red-400" />
                            <div>
                                <span className="font-pixel text-sm text-red-300">Taxes</span>
                                <p className="text-xs text-stone-500">Tax rates & treasury</p>
                            </div>
                        </Link>
                        <Link
                            href={`/kingdoms/${kingdom.id}/charters`}
                            className="flex items-center gap-3 rounded-lg border-2 border-purple-600/50 bg-purple-900/20 p-4 transition hover:bg-purple-800/30"
                        >
                            <ScrollText className="h-8 w-8 text-purple-400" />
                            <div>
                                <span className="font-pixel text-sm text-purple-300">Charters</span>
                                <p className="text-xs text-stone-500">Found new settlements</p>
                            </div>
                        </Link>
                        <Link
                            href={`/kingdoms/${kingdom.id}/training`}
                            className="flex items-center gap-3 rounded-lg border-2 border-orange-600/50 bg-orange-900/20 p-4 transition hover:bg-orange-800/30"
                        >
                            <Dumbbell className="h-8 w-8 text-orange-400" />
                            <div>
                                <span className="font-pixel text-sm text-orange-300">Training</span>
                                <p className="text-xs text-stone-500">Royal training grounds</p>
                            </div>
                        </Link>
                        <Link
                            href={`/kingdoms/${kingdom.id}/shrine`}
                            className="flex items-center gap-3 rounded-lg border-2 border-cyan-600/50 bg-cyan-900/20 p-4 transition hover:bg-cyan-800/30"
                        >
                            <Sparkles className="h-8 w-8 text-cyan-400" />
                            <div>
                                <span className="font-pixel text-sm text-cyan-300">Shrine</span>
                                <p className="text-xs text-stone-500">Royal cathedral</p>
                            </div>
                        </Link>
                        <Link
                            href={`/kingdoms/${kingdom.id}/stables`}
                            className="flex items-center gap-3 rounded-lg border-2 border-yellow-600/50 bg-yellow-900/20 p-4 transition hover:bg-yellow-800/30"
                        >
                            <Crown className="h-8 w-8 text-yellow-400" />
                            <div>
                                <span className="font-pixel text-sm text-yellow-300">Stables</span>
                                <p className="text-xs text-stone-500">Royal stables</p>
                            </div>
                        </Link>
                        <Link
                            href="/elections"
                            className="flex items-center gap-3 rounded-lg border-2 border-green-600/50 bg-green-900/20 p-4 transition hover:bg-green-800/30"
                        >
                            <Vote className="h-8 w-8 text-green-400" />
                            <div>
                                <span className="font-pixel text-sm text-green-300">Elections</span>
                                <p className="text-xs text-stone-500">Political affairs</p>
                            </div>
                        </Link>
                    </div>
                </div>

                {/* Population Chart */}
                {kingdom.baronies.length > 0 && (
                    <Card className="border-stone-700 bg-stone-900/50">
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-stone-100">
                                <Users className="h-5 w-5 text-blue-400" />
                                Population by Barony
                            </CardTitle>
                            <CardDescription className="text-stone-400">
                                NPC population distribution across baronies
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="h-[250px]">
                                <ResponsiveContainer width="100%" height="100%">
                                    <BarChart data={baronyPopulationData} layout="vertical">
                                        <CartesianGrid strokeDasharray="3 3" stroke="#44403c" />
                                        <XAxis type="number" stroke="#a8a29e" fontSize={12} />
                                        <YAxis
                                            type="category"
                                            dataKey="name"
                                            stroke="#a8a29e"
                                            fontSize={11}
                                            width={80}
                                        />
                                        <Tooltip
                                            contentStyle={{
                                                backgroundColor: "#1c1917",
                                                border: "1px solid #44403c",
                                                borderRadius: "8px",
                                            }}
                                            labelStyle={{ color: "#e7e5e4" }}
                                            itemStyle={{ color: "#60a5fa" }}
                                            formatter={(value: number) => [
                                                value.toLocaleString(),
                                                "Population",
                                            ]}
                                        />
                                        <Bar
                                            dataKey="population"
                                            fill="#60a5fa"
                                            radius={[0, 4, 4, 0]}
                                        />
                                    </BarChart>
                                </ResponsiveContainer>
                            </div>
                        </CardContent>
                    </Card>
                )}

                {/* Hierarchy Tree */}
                {kingdom.baronies.length > 0 && <HierarchyTree baronies={kingdom.baronies} />}

                {/* Baronies Grid */}
                <div>
                    <h2 className="mb-4 text-xl font-semibold">Baronies</h2>
                    <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                        {kingdom.baronies.map((barony) => {
                            const baronyBiome = biomeConfig[barony.biome] || biomeConfig.plains;

                            return (
                                <Link key={barony.id} href={`/baronies/${barony.id}`}>
                                    <Card className="h-full cursor-pointer border-stone-700 transition-shadow hover:border-amber-600/50 hover:shadow-lg">
                                        <CardHeader className="pb-2">
                                            <div className="flex items-center justify-between">
                                                <CardTitle className="text-base">
                                                    {barony.name}
                                                    {barony.is_capital && (
                                                        <span className="ml-2 text-xs text-amber-600 dark:text-amber-400">
                                                            (Capital)
                                                        </span>
                                                    )}
                                                </CardTitle>
                                                <span
                                                    className={`rounded-full ${baronyBiome.bg} border ${baronyBiome.border} px-2 py-0.5 text-xs capitalize ${baronyBiome.color}`}
                                                >
                                                    {barony.biome}
                                                </span>
                                            </div>
                                            {barony.baron ? (
                                                <CardDescription className="text-stone-400">
                                                    Baron: {barony.baron.username}
                                                </CardDescription>
                                            ) : (
                                                <CardDescription className="text-stone-500 italic">
                                                    Baron: Vacant
                                                </CardDescription>
                                            )}
                                        </CardHeader>
                                        <CardContent>
                                            <div className="grid grid-cols-2 gap-2 text-sm">
                                                <div className="flex items-center gap-2 text-stone-400">
                                                    <Users className="h-4 w-4" />
                                                    <span>
                                                        {barony.population.toLocaleString()} pop
                                                    </span>
                                                </div>
                                                <div className="flex items-center gap-2 text-amber-400">
                                                    <Coins className="h-4 w-4" />
                                                    <span>{barony.wealth.toLocaleString()}</span>
                                                </div>
                                                <div className="flex items-center gap-2 text-stone-500">
                                                    <Building className="h-4 w-4" />
                                                    <span>
                                                        {barony.town_count}{" "}
                                                        {barony.town_count === 1 ? "town" : "towns"}
                                                    </span>
                                                </div>
                                                <div className="flex items-center gap-2 text-stone-500">
                                                    <Home className="h-4 w-4" />
                                                    <span>
                                                        {barony.village_count}{" "}
                                                        {barony.village_count === 1
                                                            ? "village"
                                                            : "villages"}
                                                    </span>
                                                </div>
                                            </div>
                                        </CardContent>
                                    </Card>
                                </Link>
                            );
                        })}
                    </div>
                </div>

                {/* Coordinates */}
                <div className="flex items-center justify-center gap-2 text-sm text-stone-500">
                    <MapPin className="h-4 w-4" />
                    <span>
                        Kingdom center: ({kingdom.coordinates.x}, {kingdom.coordinates.y})
                    </span>
                </div>
            </div>
        </AppLayout>
    );
}
