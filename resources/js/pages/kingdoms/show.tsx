import { Head, Link, router, usePage } from "@inertiajs/react";
import {
    Anchor,
    Anvil,
    Beer,
    Briefcase,
    Building,
    ChevronDown,
    ChevronRight,
    Coins,
    Crown,
    Dumbbell,
    Hammer,
    HeartPulse,
    Home,
    Loader2,
    MapPin,
    Mountain,
    Palmtree,
    Percent,
    ScrollText,
    Shield,
    Snowflake,
    Sparkles,
    Store,
    Sun,
    Swords,
    TreePine,
    Trees,
    Users,
    Vote,
    Waves,
    Wheat,
    type LucideIcon,
} from "lucide-react";
import { useState } from "react";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { RulerDisplay } from "@/components/ui/legitimacy-badge";
import NoConfidenceBanner from "@/components/widgets/no-confidence-banner";
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

interface HouseEntry {
    name: string;
    tier_name: string;
    owner_username: string;
}

interface Props {
    kingdom: Kingdom;
    current_user_id: number;
    is_resident: boolean;
    can_migrate: boolean;
    cooldown_ends_at: string | null;
    cooldown_remaining: string | null;
    has_pending_request: boolean;
    houses?: HouseEntry[];
    active_no_confidence_vote?: {
        id: number;
        target_role: string;
        target_player: { id: number; username: string };
        status: string;
        voting_ends_at: string | null;
        votes_for: number;
        votes_against: number;
        quorum_required: number;
    } | null;
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

export default function KingdomShow({
    kingdom,
    current_user_id,
    is_resident,
    can_migrate,
    cooldown_remaining,
    has_pending_request,
    houses = [],
    active_no_confidence_vote,
}: Props) {
    const { flash } = usePage<{ flash?: { success?: string; error?: string } }>().props;
    const [loading, setLoading] = useState(false);

    const handleRequestMigration = () => {
        setLoading(true);
        router.post(
            `/migration/request-kingdom/${kingdom.id}`,
            {},
            {
                onSuccess: () => {
                    router.reload();
                },
                onFinish: () => setLoading(false),
            },
        );
    };

    const biome = biomeConfig[kingdom.biome] || biomeConfig.plains;
    const BiomeIcon = biome.icon;

    const breadcrumbs: BreadcrumbItem[] = [
        { title: "Dashboard", href: "/dashboard" },
        { title: "Kingdoms", href: "/kingdoms" },
        { title: kingdom.name, href: `/kingdoms/${kingdom.id}` },
    ];

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

                        {/* Home badge */}
                        {is_resident && (
                            <div className="flex items-center gap-2 rounded-lg border border-green-600/50 bg-green-900/30 px-3 py-2">
                                <Home className="h-4 w-4 text-green-400" />
                                <span className="font-pixel text-xs text-green-400">Your Home</span>
                            </div>
                        )}
                    </div>
                </div>

                {/* Flash Messages */}
                {flash?.success && (
                    <div className="rounded-lg border border-green-600/50 bg-green-900/20 px-4 py-3">
                        <p className="font-pixel text-sm text-green-300">{flash.success}</p>
                    </div>
                )}
                {flash?.error && (
                    <div className="rounded-lg border border-red-600/50 bg-red-900/20 px-4 py-3">
                        <p className="font-pixel text-sm text-red-300">{flash.error}</p>
                    </div>
                )}

                {/* No-Confidence Vote Banner */}
                {active_no_confidence_vote && (
                    <NoConfidenceBanner vote={active_no_confidence_vote} />
                )}

                {/* Stats Row */}
                <div className="grid grid-cols-2 gap-3 sm:grid-cols-4">
                    <div className="rounded-lg border border-stone-700 bg-stone-800/50 p-3 text-center">
                        <div className="flex items-center justify-center gap-2">
                            <Users className="h-5 w-5 text-blue-400" />
                            <div className="font-pixel text-xl text-stone-100">
                                {kingdom.player_count}
                            </div>
                        </div>
                        <div className="text-xs text-stone-500">Players</div>
                    </div>
                    <div className="rounded-lg border border-stone-700 bg-stone-800/50 p-3 text-center">
                        <div className="flex items-center justify-center gap-2">
                            <Users className="h-5 w-5 text-stone-400" />
                            <div className="font-pixel text-xl text-stone-100">
                                {kingdom.total_population.toLocaleString()}
                            </div>
                        </div>
                        <div className="text-xs text-stone-500">Population</div>
                    </div>
                    <div className="rounded-lg border border-stone-700 bg-stone-800/50 p-3 text-center">
                        <div className="flex items-center justify-center gap-2">
                            <Coins className="h-5 w-5 text-amber-400" />
                            <div className="font-pixel text-xl text-amber-300">
                                {kingdom.total_wealth.toLocaleString()}
                            </div>
                        </div>
                        <div className="text-xs text-stone-500">Total Wealth</div>
                    </div>
                    <div className="rounded-lg border border-stone-700 bg-stone-800/50 p-3 text-center">
                        <div className="flex items-center justify-center gap-2">
                            <Percent className="h-5 w-5 text-red-400" />
                            <div className="font-pixel text-xl text-stone-100">
                                {kingdom.tax_rate}%
                            </div>
                        </div>
                        <div className="text-xs text-stone-500">Tax Rate</div>
                    </div>
                    <div className="rounded-lg border border-stone-700 bg-stone-800/50 p-3 text-center">
                        <div className="flex items-center justify-center gap-2">
                            <Shield className="h-5 w-5 text-purple-400" />
                            <div className="font-pixel text-xl text-stone-100">
                                {kingdom.barony_count}
                            </div>
                        </div>
                        <div className="text-xs text-stone-500">Baronies</div>
                    </div>
                    <div className="rounded-lg border border-stone-700 bg-stone-800/50 p-3 text-center">
                        <div className="flex items-center justify-center gap-2">
                            <Building className="h-5 w-5 text-purple-400" />
                            <div className="font-pixel text-xl text-stone-100">
                                {kingdom.total_towns}
                            </div>
                        </div>
                        <div className="text-xs text-stone-500">Towns</div>
                    </div>
                    <div className="rounded-lg border border-stone-700 bg-stone-800/50 p-3 text-center">
                        <div className="flex items-center justify-center gap-2">
                            <Home className="h-5 w-5 text-stone-400" />
                            <div className="font-pixel text-xl text-stone-100">
                                {kingdom.total_villages}
                            </div>
                        </div>
                        <div className="text-xs text-stone-500">Villages</div>
                    </div>
                    <div className="rounded-lg border border-stone-700 bg-stone-800/50 p-3 text-center">
                        <div className="flex items-center justify-center gap-2">
                            <Anchor className="h-5 w-5 text-blue-400" />
                            <div className="font-pixel text-xl text-stone-100">
                                {kingdom.total_ports}
                            </div>
                        </div>
                        <div className="text-xs text-stone-500">Ports</div>
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
                            href={`/kingdoms/${kingdom.id}/dungeons`}
                            className="flex items-center gap-3 rounded-lg border-2 border-violet-600/50 bg-violet-900/20 p-4 transition hover:bg-violet-800/30"
                        >
                            <Swords className="h-8 w-8 text-violet-400" />
                            <div>
                                <span className="font-pixel text-sm text-violet-300">Dungeons</span>
                                <p className="text-xs text-stone-500">Explore dangerous depths</p>
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
                            href={`/kingdoms/${kingdom.id}/crafting`}
                            className="flex items-center gap-3 rounded-lg border-2 border-emerald-600/50 bg-emerald-900/20 p-4 transition hover:bg-emerald-800/30"
                        >
                            <Hammer className="h-8 w-8 text-emerald-400" />
                            <div>
                                <span className="font-pixel text-sm text-emerald-300">
                                    Workshop
                                </span>
                                <p className="text-xs text-stone-500">Royal workshops</p>
                            </div>
                        </Link>
                        <Link
                            href={`/kingdoms/${kingdom.id}/forge`}
                            className="flex items-center gap-3 rounded-lg border-2 border-rose-600/50 bg-rose-900/20 p-4 transition hover:bg-rose-800/30"
                        >
                            <Anvil className="h-8 w-8 text-rose-400" />
                            <div>
                                <span className="font-pixel text-sm text-rose-300">Forge</span>
                                <p className="text-xs text-stone-500">Royal smithy</p>
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
                            href={`/kingdoms/${kingdom.id}/tavern`}
                            className="flex items-center gap-3 rounded-lg border-2 border-amber-600/50 bg-amber-900/20 p-4 transition hover:bg-amber-800/30"
                        >
                            <Beer className="h-8 w-8 text-amber-400" />
                            <div>
                                <span className="font-pixel text-sm text-amber-300">Tavern</span>
                                <p className="text-xs text-stone-500">Royal inn</p>
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
                        <Link
                            href={`/kingdoms/${kingdom.id}/market`}
                            className="flex items-center gap-3 rounded-lg border-2 border-green-600/50 bg-green-900/20 p-4 transition hover:bg-green-800/30"
                        >
                            <Store className="h-8 w-8 text-green-400" />
                            <div>
                                <span className="font-pixel text-sm text-green-300">Market</span>
                                <p className="text-xs text-stone-500">Buy and sell goods</p>
                            </div>
                        </Link>
                    </div>
                </div>

                {/* Migration / Settlement */}
                {!is_resident && (
                    <div>
                        {has_pending_request ? (
                            <div className="rounded-xl border border-amber-600/30 bg-amber-900/10 p-4">
                                <div className="flex items-center gap-3">
                                    <Loader2 className="h-5 w-5 animate-spin text-amber-400" />
                                    <div>
                                        <p className="font-pixel text-amber-300">
                                            Migration Request Pending
                                        </p>
                                        <Link
                                            href="/migration"
                                            className="text-xs text-stone-400 hover:underline"
                                        >
                                            View your request status
                                        </Link>
                                    </div>
                                </div>
                            </div>
                        ) : can_migrate ? (
                            <button
                                onClick={handleRequestMigration}
                                disabled={loading}
                                className="flex w-full items-center justify-center gap-3 rounded-xl border-2 border-blue-500/50 bg-blue-900/20 p-4 transition hover:bg-blue-900/40 disabled:opacity-50"
                            >
                                {loading ? (
                                    <Loader2 className="h-6 w-6 animate-spin text-blue-400" />
                                ) : (
                                    <Crown className="h-6 w-6 text-blue-400" />
                                )}
                                <div>
                                    <span className="font-pixel text-lg text-blue-300">
                                        {kingdom.king
                                            ? "Request to Settle in Kingdom"
                                            : "Settle in Kingdom"}
                                    </span>
                                    <p className="text-xs text-stone-400">
                                        {kingdom.king
                                            ? "The king must approve your request to settle directly under the crown"
                                            : "Settle directly under the crown's domain"}
                                    </p>
                                </div>
                            </button>
                        ) : (
                            <div className="rounded-xl border border-stone-700 bg-stone-800/30 p-4 text-center">
                                <p className="font-pixel text-stone-500">Migration on Cooldown</p>
                                <p className="text-xs text-stone-600">
                                    {cooldown_remaining
                                        ? `You can migrate again ${cooldown_remaining}`
                                        : "You must wait before you can move again"}
                                </p>
                            </div>
                        )}
                    </div>
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

                {/* Houses */}
                {houses.length > 0 && (
                    <div>
                        <h2 className="mb-3 font-pixel text-sm text-stone-400">
                            Houses ({houses.length})
                        </h2>
                        <div className="grid gap-2 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4">
                            {houses.map((house, i) => (
                                <Link
                                    key={i}
                                    href={`/players/${house.owner_username}/house`}
                                    className="flex items-center gap-3 rounded-lg border border-stone-700 bg-stone-800/30 p-3 transition hover:bg-stone-800/50"
                                >
                                    <Home className="h-5 w-5 shrink-0 text-amber-400" />
                                    <div className="min-w-0">
                                        <div className="truncate font-pixel text-sm text-stone-200">
                                            {house.tier_name}
                                        </div>
                                        <div className="text-xs text-stone-500">
                                            {house.owner_username}
                                        </div>
                                    </div>
                                </Link>
                            ))}
                        </div>
                    </div>
                )}

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
