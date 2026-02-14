import { Head, Link, router } from "@inertiajs/react";
import {
    AlertTriangle,
    Banknote,
    Building2,
    Clock,
    Coins,
    Crown,
    Dumbbell,
    Flame,
    Gavel,
    Hammer,
    HeartPulse,
    Home,
    Loader2,
    MapPin,
    Pickaxe,
    ScrollText,
    Skull,
    Sparkles,
    Store,
    Users,
    Vote,
    Wheat,
    ArrowUpRight,
    Filter,
    Activity,
    Briefcase,
} from "lucide-react";
import { useState, useMemo } from "react";
import AppLayout from "@/layouts/app-layout";
import type { BreadcrumbItem } from "@/types";
import { cn } from "@/lib/utils";

interface Town {
    id: number;
    name: string;
    biome: string;
    is_capital: boolean;
    population: number;
    wealth: number;
    tax_rate: number;
    kingdom: { id: number; name: string } | null;
    barony: { id: number; name: string } | null;
    mayor: { id: number; username: string; primary_title: string | null } | null;
}

interface Election {
    id: number;
    position: string;
    status: string;
    voting_ends_at?: string;
    candidate_count?: number;
    started_at?: string;
    ended_at?: string;
}

interface Housing {
    has_house: boolean;
    house_name?: string;
    tier_name: string;
    condition?: number;
    can_purchase?: boolean;
    reason?: string | null;
    cost?: number;
    grid_size?: number;
    max_rooms?: number;
    storage_slots?: number;
}

interface ActivityLog {
    id: number;
    username: string | null;
    activity_type: string;
    description: string;
    subtype: string | null;
    metadata: Record<string, unknown> | null;
    created_at: string;
    time_ago: string;
}

interface Props {
    town: Town;
    active_election: Election | null;
    recent_elections: Election[];
    can_start_election: boolean;
    is_mayor: boolean;
    housing: Housing;
    activity_logs: ActivityLog[];
}

type TabType = "overview" | "activity";

// Activity type configuration
const activityConfig: Record<
    string,
    { icon: typeof Coins; color: string; bgColor: string; label: string; category: string }
> = {
    // Financial
    tax_collection: {
        icon: Coins,
        color: "text-yellow-400",
        bgColor: "bg-yellow-500/10",
        label: "Tax Collection",
        category: "financial",
    },
    salary_payment: {
        icon: Banknote,
        color: "text-green-400",
        bgColor: "bg-green-500/10",
        label: "Salary Paid",
        category: "financial",
    },
    salary_failed: {
        icon: AlertTriangle,
        color: "text-red-400",
        bgColor: "bg-red-500/10",
        label: "Salary Failed",
        category: "financial",
    },
    upstream_tax: {
        icon: ArrowUpRight,
        color: "text-amber-400",
        bgColor: "bg-amber-500/10",
        label: "Upstream Tax",
        category: "financial",
    },
    // Governance
    role_change: {
        icon: Crown,
        color: "text-purple-400",
        bgColor: "bg-purple-500/10",
        label: "Role Change",
        category: "governance",
    },
    // Disasters
    disaster: {
        icon: Flame,
        color: "text-red-500",
        bgColor: "bg-red-500/10",
        label: "Disaster",
        category: "disaster",
    },
    // Population
    migration: {
        icon: Users,
        color: "text-cyan-400",
        bgColor: "bg-cyan-500/10",
        label: "Migration",
        category: "population",
    },
    // Player activities
    training: {
        icon: Dumbbell,
        color: "text-red-400",
        bgColor: "bg-red-500/10",
        label: "Training",
        category: "player",
    },
    gathering: {
        icon: Pickaxe,
        color: "text-amber-400",
        bgColor: "bg-amber-500/10",
        label: "Gathering",
        category: "player",
    },
    crafting: {
        icon: Hammer,
        color: "text-orange-400",
        bgColor: "bg-orange-500/10",
        label: "Crafting",
        category: "player",
    },
    trading: {
        icon: Store,
        color: "text-green-400",
        bgColor: "bg-green-500/10",
        label: "Trading",
        category: "player",
    },
    healing: {
        icon: HeartPulse,
        color: "text-pink-400",
        bgColor: "bg-pink-500/10",
        label: "Healing",
        category: "player",
    },
    blessing: {
        icon: Sparkles,
        color: "text-purple-400",
        bgColor: "bg-purple-500/10",
        label: "Blessing",
        category: "player",
    },
    banking: {
        icon: Banknote,
        color: "text-yellow-400",
        bgColor: "bg-yellow-500/10",
        label: "Banking",
        category: "player",
    },
    working: {
        icon: Briefcase,
        color: "text-blue-400",
        bgColor: "bg-blue-500/10",
        label: "Working",
        category: "player",
    },
    farming: {
        icon: Wheat,
        color: "text-emerald-400",
        bgColor: "bg-emerald-500/10",
        label: "Farming",
        category: "player",
    },
    travel: {
        icon: MapPin,
        color: "text-cyan-400",
        bgColor: "bg-cyan-500/10",
        label: "Travel",
        category: "player",
    },
};

const categoryLabels: Record<string, { label: string; color: string }> = {
    all: { label: "All Activity", color: "text-stone-300" },
    financial: { label: "Financial", color: "text-yellow-400" },
    governance: { label: "Governance", color: "text-purple-400" },
    disaster: { label: "Disasters", color: "text-red-400" },
    population: { label: "Population", color: "text-cyan-400" },
    player: { label: "Player Activity", color: "text-blue-400" },
};

function ActivityItem({ log }: { log: ActivityLog }) {
    const config = activityConfig[log.activity_type] || {
        icon: Activity,
        color: "text-stone-400",
        bgColor: "bg-stone-500/10",
        label: log.activity_type,
        category: "other",
    };
    const Icon = config.icon;
    const isSystemEvent = !log.username;

    return (
        <div
            className={cn(
                "group flex items-start gap-3 rounded-lg border border-stone-700/50 p-3 transition-all hover:border-stone-600",
                config.bgColor,
            )}
        >
            <div
                className={cn(
                    "flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-stone-700/50 bg-stone-800/80",
                    config.color,
                )}
            >
                <Icon className="h-4 w-4" />
            </div>
            <div className="min-w-0 flex-1">
                <div className="flex items-start justify-between gap-2">
                    <div className="min-w-0 flex-1">
                        {isSystemEvent ? (
                            <p className="text-sm leading-snug text-stone-300">{log.description}</p>
                        ) : (
                            <p className="text-sm leading-snug text-stone-300">
                                <span className="font-medium text-stone-100">{log.username}</span>{" "}
                                {log.description.replace(log.username + " ", "")}
                            </p>
                        )}
                        {log.metadata && (
                            <div className="mt-1 flex flex-wrap gap-2">
                                {log.metadata.amount && (
                                    <span className="inline-flex items-center gap-1 rounded bg-yellow-500/20 px-1.5 py-0.5 font-pixel text-[10px] text-yellow-300">
                                        <Coins className="h-3 w-3" />
                                        {(log.metadata.amount as number).toLocaleString()}g
                                    </span>
                                )}
                                {log.metadata.role && (
                                    <span className="inline-flex items-center gap-1 rounded bg-purple-500/20 px-1.5 py-0.5 font-pixel text-[10px] text-purple-300">
                                        <Crown className="h-3 w-3" />
                                        {log.metadata.role as string}
                                    </span>
                                )}
                                {log.metadata.xp_gained && (
                                    <span className="rounded bg-blue-500/20 px-1.5 py-0.5 font-pixel text-[10px] text-blue-300">
                                        +{log.metadata.xp_gained as number} XP
                                    </span>
                                )}
                            </div>
                        )}
                    </div>
                    <span className="shrink-0 font-pixel text-[10px] text-stone-500">
                        {log.time_ago}
                    </span>
                </div>
            </div>
        </div>
    );
}

export default function TownHall({
    town,
    active_election,
    recent_elections,
    can_start_election,
    is_mayor,
    housing,
    activity_logs,
}: Props) {
    const [starting, setStarting] = useState(false);
    const [purchasing, setPurchasing] = useState(false);
    const [activeTab, setActiveTab] = useState<TabType>("overview");
    const [categoryFilter, setCategoryFilter] = useState<string>("all");

    const filteredLogs = useMemo(() => {
        if (categoryFilter === "all") return activity_logs;
        return activity_logs.filter((log) => {
            const config = activityConfig[log.activity_type];
            return config?.category === categoryFilter;
        });
    }, [activity_logs, categoryFilter]);

    // Count activities by category
    const categoryCounts = useMemo(() => {
        const counts: Record<string, number> = { all: activity_logs.length };
        activity_logs.forEach((log) => {
            const config = activityConfig[log.activity_type];
            if (config) {
                counts[config.category] = (counts[config.category] || 0) + 1;
            }
        });
        return counts;
    }, [activity_logs]);

    const handlePurchaseHouse = () => {
        setPurchasing(true);
        router.post(
            "/house/purchase",
            {},
            {
                preserveScroll: true,
                onSuccess: () => router.reload(),
                onFinish: () => setPurchasing(false),
            },
        );
    };

    const breadcrumbs: BreadcrumbItem[] = [
        { title: "Dashboard", href: "/dashboard" },
        { title: town.name, href: `/towns/${town.id}` },
        { title: "Town Hall", href: `/towns/${town.id}/hall` },
    ];

    const handleStartElection = () => {
        setStarting(true);
        router.post(
            `/towns/${town.id}/elections/mayor`,
            {},
            {
                onFinish: () => setStarting(false),
            },
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Town Hall - ${town.name}`} />
            <div className="flex h-full flex-1 flex-col overflow-auto p-4">
                {/* Header */}
                <div className="mb-6 flex items-center gap-3">
                    <div className="rounded-lg bg-blue-900/30 p-3">
                        <Building2 className="h-8 w-8 text-blue-400" />
                    </div>
                    <div>
                        <h1 className="font-pixel text-2xl text-blue-400">Town Hall</h1>
                        <div className="flex items-center gap-1 text-stone-400">
                            <span className="font-pixel text-xs">{town.name}</span>
                            {town.is_capital && (
                                <span className="ml-2 flex items-center gap-1 rounded-full bg-amber-900/50 px-2 py-0.5 font-pixel text-[10px] text-amber-400">
                                    <Crown className="h-3 w-3" />
                                    Capital
                                </span>
                            )}
                        </div>
                    </div>
                </div>

                {/* Tabs */}
                <div className="mx-auto mb-4 w-full max-w-4xl">
                    <div className="flex gap-1 rounded-lg border border-stone-700 bg-stone-800/50 p-1">
                        <button
                            onClick={() => setActiveTab("overview")}
                            className={cn(
                                "flex items-center gap-2 rounded-md px-4 py-2 font-pixel text-xs transition-all",
                                activeTab === "overview"
                                    ? "bg-stone-700 text-stone-100"
                                    : "text-stone-400 hover:bg-stone-700/50 hover:text-stone-300",
                            )}
                        >
                            <Gavel className="h-4 w-4" />
                            Overview
                        </button>
                        <button
                            onClick={() => setActiveTab("activity")}
                            className={cn(
                                "flex items-center gap-2 rounded-md px-4 py-2 font-pixel text-xs transition-all",
                                activeTab === "activity"
                                    ? "bg-stone-700 text-stone-100"
                                    : "text-stone-400 hover:bg-stone-700/50 hover:text-stone-300",
                            )}
                        >
                            <Activity className="h-4 w-4" />
                            Town Ledger
                            {activity_logs.length > 0 && (
                                <span className="rounded-full bg-blue-500/20 px-2 py-0.5 font-pixel text-[10px] text-blue-400">
                                    {activity_logs.length}
                                </span>
                            )}
                        </button>
                    </div>
                </div>

                <div className="mx-auto w-full max-w-4xl">
                    {activeTab === "overview" ? (
                        <>
                            <div className="grid gap-4 lg:grid-cols-2">
                                {/* Town Governance */}
                                <div className="rounded-xl border-2 border-stone-700 bg-stone-800/50 p-4">
                                    <h2 className="mb-4 flex items-center gap-2 font-pixel text-sm text-stone-300">
                                        <Gavel className="h-4 w-4 text-blue-400" />
                                        Town Governance
                                    </h2>

                                    {/* Mayor */}
                                    <div className="mb-4 rounded-lg border border-stone-700 bg-stone-800/50 p-4">
                                        <div className="flex items-center justify-between">
                                            <div>
                                                <div className="font-pixel text-[10px] text-stone-500">
                                                    Mayor
                                                </div>
                                                {town.mayor ? (
                                                    <div className="flex items-center gap-2">
                                                        <Crown className="h-4 w-4 text-amber-400" />
                                                        <span className="font-pixel text-sm text-stone-200">
                                                            {town.mayor.username}
                                                        </span>
                                                        {town.mayor.primary_title && (
                                                            <span className="font-pixel text-[10px] text-stone-500">
                                                                ({town.mayor.primary_title})
                                                            </span>
                                                        )}
                                                    </div>
                                                ) : (
                                                    <span className="font-pixel text-sm text-stone-500">
                                                        Position Vacant
                                                    </span>
                                                )}
                                            </div>
                                            {is_mayor && (
                                                <span className="rounded bg-green-900/50 px-2 py-1 font-pixel text-[10px] text-green-400">
                                                    This is you!
                                                </span>
                                            )}
                                        </div>
                                    </div>

                                    {/* Town Stats */}
                                    <div className="grid grid-cols-2 gap-3">
                                        <div className="rounded-lg border border-stone-700 bg-stone-800/30 p-3">
                                            <div className="flex items-center gap-2">
                                                <Users className="h-4 w-4 text-stone-500" />
                                                <div>
                                                    <div className="font-pixel text-[10px] text-stone-500">
                                                        Population
                                                    </div>
                                                    <div className="font-pixel text-sm text-stone-300">
                                                        {town.population.toLocaleString()}
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div className="rounded-lg border border-stone-700 bg-stone-800/30 p-3">
                                            <div className="flex items-center gap-2">
                                                <Coins className="h-4 w-4 text-yellow-400" />
                                                <div>
                                                    <div className="font-pixel text-[10px] text-stone-500">
                                                        Treasury
                                                    </div>
                                                    <div className="font-pixel text-sm text-yellow-400">
                                                        {town.wealth.toLocaleString()}
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div className="rounded-lg border border-stone-700 bg-stone-800/30 p-3">
                                            <div className="flex items-center gap-2">
                                                <ScrollText className="h-4 w-4 text-stone-500" />
                                                <div>
                                                    <div className="font-pixel text-[10px] text-stone-500">
                                                        Tax Rate
                                                    </div>
                                                    <div className="font-pixel text-sm text-stone-300">
                                                        {(town.tax_rate * 100).toFixed(0)}%
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div className="rounded-lg border border-stone-700 bg-stone-800/30 p-3">
                                            <div className="flex items-center gap-2">
                                                <Crown className="h-4 w-4 text-amber-400" />
                                                <div>
                                                    <div className="font-pixel text-[10px] text-stone-500">
                                                        Kingdom
                                                    </div>
                                                    {town.kingdom ? (
                                                        <Link
                                                            href={`/kingdoms/${town.kingdom.id}`}
                                                            className="font-pixel text-sm text-amber-400 hover:underline"
                                                        >
                                                            {town.kingdom.name}
                                                        </Link>
                                                    ) : (
                                                        <span className="font-pixel text-sm text-stone-500">
                                                            Independent
                                                        </span>
                                                    )}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {/* Elections */}
                                <div className="rounded-xl border-2 border-stone-700 bg-stone-800/50 p-4">
                                    <h2 className="mb-4 flex items-center gap-2 font-pixel text-sm text-stone-300">
                                        <Vote className="h-4 w-4 text-purple-400" />
                                        Elections
                                    </h2>

                                    {/* Active Election */}
                                    {active_election ? (
                                        <div className="mb-4 rounded-lg border-2 border-purple-600/50 bg-purple-900/20 p-4">
                                            <div className="mb-2 flex items-center justify-between">
                                                <span className="font-pixel text-xs text-purple-400">
                                                    Active Election
                                                </span>
                                                <span className="rounded bg-purple-900/50 px-2 py-0.5 font-pixel text-[10px] text-purple-300">
                                                    {active_election.status}
                                                </span>
                                            </div>
                                            <div className="mb-2 font-pixel text-sm text-stone-200">
                                                {active_election.position} Election
                                            </div>
                                            <div className="mb-3 flex items-center gap-4 font-pixel text-[10px] text-stone-500">
                                                <span>
                                                    {active_election.candidate_count} candidates
                                                </span>
                                                {active_election.voting_ends_at && (
                                                    <span className="flex items-center gap-1">
                                                        <Clock className="h-3 w-3" />
                                                        Ends:{" "}
                                                        {new Date(
                                                            active_election.voting_ends_at,
                                                        ).toLocaleDateString()}
                                                    </span>
                                                )}
                                            </div>
                                            <Link
                                                href={`/elections/${active_election.id}`}
                                                className="block w-full rounded-lg bg-purple-600 py-2 text-center font-pixel text-xs text-white transition hover:bg-purple-500"
                                            >
                                                View Election
                                            </Link>
                                        </div>
                                    ) : (
                                        <div className="mb-4 rounded-lg border border-stone-700 bg-stone-800/30 p-4 text-center">
                                            <Vote className="mx-auto mb-2 h-8 w-8 text-stone-600" />
                                            <div className="mb-1 font-pixel text-xs text-stone-400">
                                                No Active Election
                                            </div>
                                            <div className="font-pixel text-[10px] text-stone-600">
                                                The town is at peace... for now.
                                            </div>
                                        </div>
                                    )}

                                    {/* Start Election Button */}
                                    {can_start_election && !active_election && (
                                        <button
                                            onClick={handleStartElection}
                                            disabled={starting}
                                            className="mb-4 w-full rounded-lg border-2 border-purple-600/50 bg-purple-900/20 py-2 font-pixel text-xs text-purple-400 transition hover:bg-purple-900/40 disabled:opacity-50"
                                        >
                                            {starting ? "Starting..." : "Start Mayoral Election"}
                                        </button>
                                    )}

                                    {/* Recent Elections */}
                                    {recent_elections.length > 0 && (
                                        <div>
                                            <div className="mb-2 font-pixel text-[10px] text-stone-500">
                                                Recent Elections
                                            </div>
                                            <div className="space-y-2">
                                                {recent_elections.map((election) => (
                                                    <Link
                                                        key={election.id}
                                                        href={`/elections/${election.id}`}
                                                        className="flex items-center justify-between rounded-lg border border-stone-700 bg-stone-800/30 p-2 transition hover:bg-stone-700/50"
                                                    >
                                                        <div>
                                                            <div className="font-pixel text-xs text-stone-300">
                                                                {election.position}
                                                            </div>
                                                            <div className="font-pixel text-[10px] text-stone-500">
                                                                {election.started_at}
                                                            </div>
                                                        </div>
                                                        <span
                                                            className={`rounded px-2 py-0.5 font-pixel text-[10px] ${
                                                                election.status === "completed"
                                                                    ? "bg-green-900/50 text-green-400"
                                                                    : "bg-red-900/50 text-red-400"
                                                            }`}
                                                        >
                                                            {election.status}
                                                        </span>
                                                    </Link>
                                                ))}
                                            </div>
                                        </div>
                                    )}
                                </div>
                            </div>

                            {/* Housing */}
                            <div className="mt-4 rounded-xl border-2 border-stone-700 bg-stone-800/50 p-4">
                                <h2 className="mb-4 flex items-center gap-2 font-pixel text-sm text-stone-300">
                                    <Home className="h-4 w-4 text-amber-400" />
                                    Housing
                                </h2>

                                {housing.has_house ? (
                                    <div className="rounded-lg border border-stone-700 bg-stone-800/30 p-4">
                                        <div className="flex items-center justify-between">
                                            <div>
                                                <div className="font-pixel text-sm text-stone-200">
                                                    {housing.house_name}
                                                </div>
                                                <div className="mt-1 font-pixel text-[10px] text-stone-500">
                                                    {housing.tier_name} &bull; Condition:{" "}
                                                    {housing.condition}%
                                                </div>
                                            </div>
                                            <Link
                                                href={`/towns/${town.id}/house`}
                                                className="rounded-lg bg-amber-900/50 px-4 py-2 font-pixel text-xs text-amber-300 transition hover:bg-amber-800/50"
                                            >
                                                Manage House
                                            </Link>
                                        </div>
                                    </div>
                                ) : (
                                    <div className="rounded-lg border border-stone-700 bg-stone-800/30 p-4">
                                        <div className="mb-3 text-center">
                                            <Home className="mx-auto mb-2 h-8 w-8 text-amber-400/50" />
                                            <div className="font-pixel text-sm text-stone-300">
                                                Purchase a Housing Plot
                                            </div>
                                            <div className="mt-1 font-pixel text-[10px] text-stone-500">
                                                Acquire land to build a {housing.tier_name}. Build
                                                rooms, craft furniture, and store your valuables.
                                            </div>
                                        </div>

                                        <div className="mb-3 rounded-md border border-stone-600/50 bg-stone-900/50 p-3">
                                            <div className="flex items-center justify-between">
                                                <div>
                                                    <div className="font-pixel text-xs text-stone-300">
                                                        {housing.tier_name}
                                                    </div>
                                                    <div className="font-pixel text-[10px] text-stone-500">
                                                        {housing.grid_size}x{housing.grid_size} grid
                                                        &bull; {housing.max_rooms} rooms &bull;{" "}
                                                        {housing.storage_slots} storage
                                                    </div>
                                                </div>
                                                <div className="flex items-center gap-1.5">
                                                    <Coins className="h-4 w-4 text-yellow-400" />
                                                    <span className="font-pixel text-sm text-yellow-300">
                                                        {housing.cost?.toLocaleString()}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>

                                        {housing.reason && (
                                            <div className="mb-3 text-center font-pixel text-[10px] text-red-400">
                                                {housing.reason}
                                            </div>
                                        )}

                                        <button
                                            onClick={handlePurchaseHouse}
                                            disabled={purchasing || !housing.can_purchase}
                                            className="w-full rounded-lg border border-amber-600/50 bg-amber-900/50 py-2 font-pixel text-xs text-amber-300 transition hover:bg-amber-800/50 disabled:cursor-not-allowed disabled:opacity-40"
                                        >
                                            {purchasing ? (
                                                <Loader2 className="mx-auto h-4 w-4 animate-spin" />
                                            ) : (
                                                "Purchase Housing Plot"
                                            )}
                                        </button>
                                    </div>
                                )}
                            </div>

                            {/* Town Decrees / Laws - Future Feature */}
                            <div className="mt-4 rounded-xl border-2 border-stone-700 bg-stone-800/50 p-4">
                                <h2 className="mb-4 flex items-center gap-2 font-pixel text-sm text-stone-300">
                                    <ScrollText className="h-4 w-4 text-amber-400" />
                                    Town Decrees
                                </h2>
                                <div className="py-8 text-center">
                                    <ScrollText className="mx-auto mb-2 h-8 w-8 text-stone-600" />
                                    <div className="font-pixel text-xs text-stone-500">
                                        No decrees have been issued.
                                    </div>
                                    <div className="font-pixel text-[10px] text-stone-600">
                                        The mayor may issue decrees that affect the town.
                                    </div>
                                </div>
                            </div>

                            {/* Town Chronicle - Historical Events */}
                            <div className="mt-4 rounded-xl border-2 border-stone-700 bg-stone-800/50 p-4">
                                <h2 className="mb-4 flex items-center gap-2 font-pixel text-sm text-stone-300">
                                    <ScrollText className="h-4 w-4 text-stone-400" />
                                    Town Chronicle
                                </h2>
                                <div className="space-y-3">
                                    <div className="rounded-lg border border-stone-700/50 bg-stone-900/30 p-3">
                                        <div className="mb-2 flex items-center gap-2">
                                            <Skull className="h-4 w-4 text-stone-500" />
                                            <span className="font-pixel text-[10px] text-stone-500">
                                                Year 847
                                            </span>
                                        </div>
                                        <p className="font-pixel text-[10px] italic leading-relaxed text-stone-400">
                                            "The Sweating Sickness claimed twelve souls before the
                                            quarantine was lifted. The old healer says it spreads
                                            through the air itself. May the gods spare us from
                                            another outbreak."
                                        </p>
                                    </div>
                                    <div className="rounded-lg border border-stone-700/50 bg-stone-900/30 p-3">
                                        <div className="mb-2 flex items-center gap-2">
                                            <Flame className="h-4 w-4 text-orange-500/50" />
                                            <span className="font-pixel text-[10px] text-stone-500">
                                                Year 842
                                            </span>
                                        </div>
                                        <p className="font-pixel text-[10px] italic leading-relaxed text-stone-400">
                                            "Fire took the old granary and three houses on the
                                            eastern road. The bucket brigade saved what they could.
                                            We rebuilt, as we always do."
                                        </p>
                                    </div>
                                    <div className="rounded-lg border border-stone-700/50 bg-stone-900/30 p-3">
                                        <div className="mb-2 flex items-center gap-2">
                                            <AlertTriangle className="h-4 w-4 text-blue-500/50" />
                                            <span className="font-pixel text-[10px] text-stone-500">
                                                Year 839
                                            </span>
                                        </div>
                                        <p className="font-pixel text-[10px] italic leading-relaxed text-stone-400">
                                            "The spring floods washed out the lower fields and
                                            damaged the mill. Some say it was the worst flooding in
                                            living memory. The river takes what it will."
                                        </p>
                                    </div>
                                </div>
                                <div className="mt-3 text-center">
                                    <span className="font-pixel text-[9px] text-stone-600">
                                        May the chronicle remain quiet in the days ahead...
                                    </span>
                                </div>
                            </div>
                        </>
                    ) : (
                        /* Activity Tab */
                        <div className="rounded-xl border-2 border-stone-700 bg-stone-800/50">
                            {/* Header */}
                            <div className="border-b border-stone-700 p-4">
                                <div className="flex items-center justify-between">
                                    <div className="flex items-center gap-2">
                                        <ScrollText className="h-5 w-5 text-amber-400" />
                                        <h2 className="font-pixel text-sm text-stone-200">
                                            Town Ledger
                                        </h2>
                                    </div>
                                    <div className="font-pixel text-[10px] text-stone-500">
                                        Official records of {town.name}
                                    </div>
                                </div>
                            </div>

                            {/* Filters */}
                            <div className="border-b border-stone-700/50 bg-stone-800/30 px-4 py-3">
                                <div className="flex items-center gap-2">
                                    <Filter className="h-4 w-4 text-stone-500" />
                                    <div className="flex flex-wrap gap-2">
                                        {Object.entries(categoryLabels).map(
                                            ([key, { label, color }]) => {
                                                const count = categoryCounts[key] || 0;
                                                if (key !== "all" && count === 0) return null;
                                                return (
                                                    <button
                                                        key={key}
                                                        onClick={() => setCategoryFilter(key)}
                                                        className={cn(
                                                            "flex items-center gap-1.5 rounded-full border px-3 py-1 font-pixel text-[10px] transition-all",
                                                            categoryFilter === key
                                                                ? "border-stone-500 bg-stone-700 text-stone-100"
                                                                : "border-stone-700 bg-stone-800/50 text-stone-400 hover:border-stone-600 hover:text-stone-300",
                                                        )}
                                                    >
                                                        <span className={color}>{label}</span>
                                                        <span className="rounded-full bg-stone-600/50 px-1.5 text-stone-400">
                                                            {count}
                                                        </span>
                                                    </button>
                                                );
                                            },
                                        )}
                                    </div>
                                </div>
                            </div>

                            {/* Activity List */}
                            <div className="max-h-[600px] overflow-y-auto p-4">
                                {filteredLogs.length === 0 ? (
                                    <div className="py-12 text-center">
                                        <Activity className="mx-auto mb-3 h-12 w-12 text-stone-600" />
                                        <div className="font-pixel text-sm text-stone-400">
                                            No activity recorded
                                        </div>
                                        <div className="mt-1 font-pixel text-[10px] text-stone-600">
                                            The ledger awaits its first entry...
                                        </div>
                                    </div>
                                ) : (
                                    <div className="space-y-2">
                                        {filteredLogs.map((log) => (
                                            <ActivityItem key={log.id} log={log} />
                                        ))}
                                    </div>
                                )}
                            </div>

                            {/* Footer */}
                            {filteredLogs.length > 0 && (
                                <div className="border-t border-stone-700/50 px-4 py-3">
                                    <div className="text-center font-pixel text-[10px] text-stone-600">
                                        Showing {filteredLogs.length} of {activity_logs.length}{" "}
                                        entries
                                    </div>
                                </div>
                            )}
                        </div>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
