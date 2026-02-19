import { Head, Link, router, usePage } from "@inertiajs/react";
import {
    AlertTriangle,
    ArrowRight,
    Building2,
    Coins,
    Home,
    Loader2,
    MapPin,
    Mountain,
    Palmtree,
    Route,
    Settings,
    Shield,
    Skull,
    Snowflake,
    Sun,
    TreePine,
    Trees,
    Truck,
    Users,
    Waves,
    Wheat,
    type LucideIcon,
} from "lucide-react";
import { useState } from "react";
import { ActivityFeed } from "@/components/activity-feed";
import { PlayerList } from "@/components/widgets/player-list";
import { ServicesGrid } from "@/components/service-card";
import { Badge } from "@/components/ui/badge";
import { Card, CardHeader, CardTitle } from "@/components/ui/card";
import { RulerDisplay } from "@/components/ui/legitimacy-badge";
import AppLayout from "@/layouts/app-layout";
import { locationPath } from "@/lib/utils";
import type { BreadcrumbItem } from "@/types";

interface Village {
    id: number;
    name: string;
    biome: string;
    is_town?: boolean;
    is_hamlet?: boolean;
    population: number;
    wealth: number;
}

interface Town {
    id: number;
    name: string;
    biome: string;
    population: number;
    wealth: number;
}

interface Ruler {
    id: number;
    username: string;
    primary_title?: string | null;
    legitimacy?: number;
}

interface Barony {
    id: number;
    name: string;
    description: string;
    biome: string;
    tax_rate: number;
    is_capital: boolean;
    coordinates: {
        x: number;
        y: number;
    };
    kingdom: {
        id: number;
        name: string;
        biome: string;
    } | null;
    villages: Village[];
    towns: Town[];
    village_count: number;
    town_count: number;
    total_population: number;
    total_wealth: number;
    baron?: Ruler | null;
}

interface ServiceInfo {
    id: string;
    name: string;
    description: string;
    icon: string;
    route: string;
}

interface ActivityLogEntry {
    id: number;
    username: string;
    description: string;
    activity_type: string;
    subtype: string | null;
    metadata: Record<string, unknown> | null;
    created_at: string;
    time_ago: string;
}

interface TradeRouteInfo {
    id: number;
    name: string;
    origin: {
        type: string;
        id: number;
        name: string;
    };
    destination: {
        type: string;
        id: number;
        name: string;
    };
    distance: number;
    base_travel_days: number;
    danger_level: string;
}

interface Player {
    id: number;
    username: string;
    combat_level: number;
}

interface HouseEntry {
    name: string;
    tier_name: string;
    owner_username: string;
}

interface Props {
    barony: Barony;
    services: ServiceInfo[];
    trade_routes: TradeRouteInfo[];
    recent_activity: ActivityLogEntry[];
    visitors: Player[];
    visitor_count: number;
    residents: Player[];
    resident_count: number;
    current_user_id: number;
    is_baron: boolean;
    is_visitor: boolean;
    is_resident: boolean;
    can_migrate: boolean;
    cooldown_ends_at: string | null;
    cooldown_remaining: string | null;
    has_pending_request: boolean;
    houses?: HouseEntry[];
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

export default function BaronyShow({
    barony,
    services,
    trade_routes,
    recent_activity,
    visitors = [],
    visitor_count = 0,
    residents = [],
    resident_count = 0,
    current_user_id,
    is_baron,
    is_visitor,
    is_resident,
    can_migrate,
    cooldown_remaining,
    has_pending_request,
    houses = [],
}: Props) {
    const { flash } = usePage<{ flash?: { success?: string; error?: string } }>().props;
    const [loading, setLoading] = useState(false);

    const handleRequestMigration = () => {
        setLoading(true);
        router.post(
            `/migration/request-barony/${barony.id}`,
            {},
            {
                onSuccess: () => {
                    router.reload();
                },
                onFinish: () => setLoading(false),
            },
        );
    };

    const biome = biomeConfig[barony.biome] || biomeConfig.plains;
    const BiomeIcon = biome.icon;

    const breadcrumbs: BreadcrumbItem[] = [
        { title: "Dashboard", href: "/dashboard" },
        { title: "Baronies", href: "/baronies" },
        { title: barony.name, href: `/baronies/${barony.id}` },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={barony.name} />
            <div className="flex flex-col gap-6 p-6">
                {/* Hero Header */}
                <div className={`rounded-xl border-2 ${biome.border} ${biome.bg} p-4 sm:p-6`}>
                    <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:gap-4">
                        <div
                            className={`hidden rounded-xl ${biome.bg} border ${biome.border} p-3 sm:block sm:p-4`}
                        >
                            <BiomeIcon className={`h-10 w-10 sm:h-12 sm:w-12 ${biome.color}`} />
                        </div>
                        <div className="flex-1">
                            <div className="flex flex-wrap items-center gap-2 sm:gap-3">
                                <h1 className="font-[Cinzel] text-2xl font-bold text-stone-100 sm:text-3xl">
                                    {barony.name}
                                </h1>
                                {barony.is_capital && (
                                    <span className="rounded-full bg-amber-900/50 px-2 py-0.5 text-xs text-amber-300">
                                        Capital
                                    </span>
                                )}
                                <Badge
                                    className={`${biome.bg} border ${biome.border} ${biome.color}`}
                                >
                                    {barony.biome}
                                </Badge>
                            </div>
                            <p className="mt-2 text-sm text-stone-400 sm:text-base">
                                {barony.description}
                            </p>

                            {/* Hierarchy */}
                            <div className="mt-3 flex items-center gap-2 text-sm">
                                {barony.kingdom && (
                                    <>
                                        <Link
                                            href={`/kingdoms/${barony.kingdom.id}`}
                                            className="text-amber-400 hover:underline"
                                        >
                                            {barony.kingdom.name}
                                        </Link>
                                        <span className="text-stone-600">›</span>
                                    </>
                                )}
                                <span className="text-stone-500">{barony.name}</span>
                            </div>
                        </div>

                        {/* Home/Visitor badge */}
                        {is_resident ? (
                            <div className="mt-3 flex items-center gap-2 rounded-lg border border-green-600/50 bg-green-900/30 px-3 py-2 sm:mt-0">
                                <Home className="h-4 w-4 text-green-400" />
                                <span className="font-pixel text-xs text-green-400">Your Home</span>
                            </div>
                        ) : is_visitor ? (
                            <div className="mt-3 flex items-center gap-2 rounded-lg border border-blue-600/50 bg-blue-900/30 px-3 py-2 sm:mt-0">
                                <MapPin className="h-4 w-4 text-blue-400" />
                                <span className="font-pixel text-xs text-blue-400">
                                    You Are Here
                                </span>
                            </div>
                        ) : null}
                    </div>
                </div>

                {/* Stats Row */}
                <div className="grid grid-cols-2 gap-2 sm:grid-cols-3 sm:gap-3 lg:grid-cols-6">
                    <div className="rounded-lg border border-stone-700 bg-stone-800/50 p-3 text-center sm:p-4">
                        <Users className="mx-auto mb-1 h-5 w-5 text-blue-400 sm:mb-2 sm:h-6 sm:w-6" />
                        <div className="font-pixel text-base text-stone-100 sm:text-2xl">
                            {barony.total_population.toLocaleString()}
                        </div>
                        <div className="text-[10px] text-stone-500 sm:text-xs">Population</div>
                    </div>
                    <div className="rounded-lg border border-stone-700 bg-stone-800/50 p-3 text-center sm:p-4">
                        <Coins className="mx-auto mb-1 h-5 w-5 text-amber-400 sm:mb-2 sm:h-6 sm:w-6" />
                        <div className="font-pixel text-base text-amber-300 sm:text-2xl">
                            {barony.total_wealth.toLocaleString()}
                        </div>
                        <div className="text-[10px] text-stone-500 sm:text-xs">Wealth</div>
                    </div>
                    <div className="rounded-lg border border-stone-700 bg-stone-800/50 p-3 text-center sm:p-4">
                        <Home className="mx-auto mb-1 h-5 w-5 text-green-400 sm:mb-2 sm:h-6 sm:w-6" />
                        <div className="font-pixel text-lg text-stone-100 sm:text-2xl">
                            {barony.village_count}
                        </div>
                        <div className="text-[10px] text-stone-500 sm:text-xs">Villages</div>
                    </div>
                    <div className="rounded-lg border border-stone-700 bg-stone-800/50 p-3 text-center sm:p-4">
                        <Building2 className="mx-auto mb-1 h-5 w-5 text-purple-400 sm:mb-2 sm:h-6 sm:w-6" />
                        <div className="font-pixel text-lg text-stone-100 sm:text-2xl">
                            {barony.town_count}
                        </div>
                        <div className="text-[10px] text-stone-500 sm:text-xs">Towns</div>
                    </div>
                    <div className="rounded-lg border border-stone-700 bg-stone-800/50 p-3 text-center sm:p-4">
                        <Coins className="mx-auto mb-1 h-5 w-5 text-stone-400 sm:mb-2 sm:h-6 sm:w-6" />
                        <div className="font-pixel text-lg text-stone-100 sm:text-2xl">
                            {barony.tax_rate}%
                        </div>
                        <div className="text-[10px] text-stone-500 sm:text-xs">Tax Rate</div>
                    </div>
                    <div className="rounded-lg border border-stone-700 bg-stone-800/50 p-3 text-center sm:p-4">
                        <MapPin className="mx-auto mb-1 h-5 w-5 text-stone-400 sm:mb-2 sm:h-6 sm:w-6" />
                        <div className="font-pixel text-sm text-stone-300 sm:text-lg">
                            {barony.coordinates.x}, {barony.coordinates.y}
                        </div>
                        <div className="text-[10px] text-stone-500 sm:text-xs">Coordinates</div>
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

                {/* Services Grid */}
                {services && services.length > 0 && (
                    <ServicesGrid
                        services={services}
                        locationType="barony"
                        locationId={barony.id}
                    />
                )}

                {/* Baron / Ruler */}
                <RulerDisplay
                    ruler={barony.baron}
                    title="Baron"
                    isCurrentUser={barony.baron?.id === current_user_id}
                />

                {/* Quick Actions */}
                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <Link
                        href={`/baronies/${barony.id}/roles`}
                        className="flex items-center gap-4 rounded-xl border border-stone-600/30 bg-stone-800/30 p-4 transition hover:bg-stone-800/50"
                    >
                        <Shield className="h-8 w-8 text-amber-400" />
                        <div>
                            <div className="font-pixel text-stone-200">Barony Roles</div>
                            <div className="text-xs text-stone-500">
                                View officials and positions
                            </div>
                        </div>
                    </Link>

                    <Link
                        href="/trade/caravans"
                        className="flex items-center gap-4 rounded-xl border border-stone-600/30 bg-stone-800/30 p-4 transition hover:bg-stone-800/50"
                    >
                        <Truck className="h-8 w-8 text-orange-400" />
                        <div>
                            <div className="font-pixel text-stone-200">My Caravans</div>
                            <div className="text-xs text-stone-500">Manage your trade caravans</div>
                        </div>
                    </Link>

                    {/* Trade Routes Section */}
                    <div className="rounded-xl border border-emerald-600/30 bg-emerald-900/10 p-4">
                        <div className="mb-3 flex items-center justify-between">
                            <div className="flex items-center gap-2">
                                <Route className="h-5 w-5 text-emerald-400" />
                                <span className="font-pixel text-emerald-300">Trade Routes</span>
                                <span className="text-xs text-stone-500">
                                    ({trade_routes.length})
                                </span>
                            </div>
                            {is_baron && (
                                <Link
                                    href={`/baronies/${barony.id}/trade-routes`}
                                    className="flex items-center gap-1 rounded border border-emerald-600/50 bg-emerald-900/30 px-2 py-1 text-xs text-emerald-300 transition hover:bg-emerald-800/50"
                                >
                                    <Settings className="h-3 w-3" />
                                    Manage
                                </Link>
                            )}
                        </div>
                        {trade_routes.length > 0 ? (
                            <div className="space-y-2">
                                {trade_routes.slice(0, 4).map((route) => (
                                    <div
                                        key={route.id}
                                        className="flex items-center justify-between rounded border border-stone-700/50 bg-stone-800/30 px-3 py-2 text-sm"
                                    >
                                        <div className="flex items-center gap-2">
                                            <Link
                                                href={locationPath(
                                                    route.origin.type,
                                                    route.origin.id,
                                                )}
                                                className="text-stone-300 hover:text-amber-400"
                                            >
                                                {route.origin.name}
                                            </Link>
                                            <ArrowRight className="h-3 w-3 text-stone-600" />
                                            <Link
                                                href={locationPath(
                                                    route.destination.type,
                                                    route.destination.id,
                                                )}
                                                className="text-stone-300 hover:text-amber-400"
                                            >
                                                {route.destination.name}
                                            </Link>
                                        </div>
                                        <div className="flex items-center gap-2">
                                            <span className="text-xs text-stone-500">
                                                {route.base_travel_days}d
                                            </span>
                                            {route.danger_level === "dangerous" && (
                                                <AlertTriangle className="h-3 w-3 text-amber-400" />
                                            )}
                                            {route.danger_level === "perilous" && (
                                                <Skull className="h-3 w-3 text-red-400" />
                                            )}
                                        </div>
                                    </div>
                                ))}
                                {trade_routes.length > 4 && (
                                    <div className="text-center text-xs text-stone-500">
                                        +{trade_routes.length - 4} more routes
                                    </div>
                                )}
                            </div>
                        ) : (
                            <p className="text-center text-xs text-stone-500">
                                No trade routes established
                            </p>
                        )}
                    </div>
                </div>

                {/* Recent Activity */}
                {recent_activity && recent_activity.length > 0 && (
                    <ActivityFeed
                        activities={recent_activity}
                        title="Recent Activity"
                        emptyMessage="No recent activity in this barony"
                        maxHeight="250px"
                    />
                )}

                {/* Visitors */}
                <PlayerList
                    title="Visitors"
                    players={visitors}
                    totalCount={visitor_count}
                    currentUserId={current_user_id}
                    youLabelClass="text-blue-400"
                />

                {/* Residents */}
                <PlayerList
                    title="Residents"
                    players={residents}
                    totalCount={resident_count}
                    currentUserId={current_user_id}
                    youLabelClass="text-green-400"
                />

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
                                    <Home className="h-6 w-6 text-blue-400" />
                                )}
                                <div>
                                    <span className="font-pixel text-lg text-blue-300">
                                        {barony.baron ? "Request to Settle" : "Settle Here"}
                                    </span>
                                    <p className="text-xs text-stone-400">
                                        {barony.baron
                                            ? "The baron must approve your request"
                                            : "Make this barony your new home"}
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

                {/* Settlements */}
                <div className="grid gap-6 lg:grid-cols-2">
                    {/* Villages */}
                    <div>
                        <h2 className="mb-4 flex items-center gap-2 font-pixel text-lg text-stone-300">
                            <Home className="h-5 w-5 text-green-400" />
                            Villages ({barony.village_count})
                        </h2>
                        <div className="space-y-2">
                            {barony.villages.map((village) => (
                                <Link key={village.id} href={`/villages/${village.id}`}>
                                    <Card className="cursor-pointer transition-shadow hover:shadow-lg">
                                        <CardHeader className="p-4">
                                            <div className="flex items-center justify-between">
                                                <CardTitle className="text-base">
                                                    {village.name}
                                                    {village.is_hamlet && (
                                                        <span className="ml-2 text-xs text-stone-500">
                                                            (Hamlet)
                                                        </span>
                                                    )}
                                                </CardTitle>
                                                <Badge
                                                    className={
                                                        biomeConfig[village.biome]?.bg ||
                                                        "bg-stone-700"
                                                    }
                                                    variant="secondary"
                                                >
                                                    {village.biome}
                                                </Badge>
                                            </div>
                                            <div className="mt-1 flex items-center gap-4 text-xs text-stone-500">
                                                <span className="flex items-center gap-1">
                                                    <Users className="h-3 w-3" />
                                                    {village.population.toLocaleString()}
                                                </span>
                                                <span className="flex items-center gap-1">
                                                    <Coins className="h-3 w-3 text-amber-400" />
                                                    {village.wealth.toLocaleString()}
                                                </span>
                                            </div>
                                        </CardHeader>
                                    </Card>
                                </Link>
                            ))}
                            {barony.villages.length === 0 && (
                                <p className="text-center text-sm text-stone-500">
                                    No villages in this barony
                                </p>
                            )}
                        </div>
                    </div>

                    {/* Towns */}
                    <div>
                        <h2 className="mb-4 flex items-center gap-2 font-pixel text-lg text-stone-300">
                            <Building2 className="h-5 w-5 text-purple-400" />
                            Towns ({barony.town_count})
                        </h2>
                        <div className="space-y-2">
                            {barony.towns.map((town) => (
                                <Link key={town.id} href={`/towns/${town.id}`}>
                                    <Card className="cursor-pointer transition-shadow hover:shadow-lg">
                                        <CardHeader className="p-4">
                                            <div className="flex items-center justify-between">
                                                <CardTitle className="text-base">
                                                    {town.name}
                                                </CardTitle>
                                                <Badge
                                                    className={
                                                        biomeConfig[town.biome]?.bg ||
                                                        "bg-stone-700"
                                                    }
                                                    variant="secondary"
                                                >
                                                    {town.biome}
                                                </Badge>
                                            </div>
                                            <div className="mt-1 flex items-center gap-4 text-xs text-stone-500">
                                                <span className="flex items-center gap-1">
                                                    <Users className="h-3 w-3" />
                                                    {town.population.toLocaleString()}
                                                </span>
                                                <span className="flex items-center gap-1">
                                                    <Coins className="h-3 w-3 text-amber-400" />
                                                    {town.wealth.toLocaleString()}
                                                </span>
                                            </div>
                                        </CardHeader>
                                    </Card>
                                </Link>
                            ))}
                            {barony.towns.length === 0 && (
                                <p className="text-center text-sm text-stone-500">
                                    No towns in this barony
                                </p>
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
