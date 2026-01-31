import { Head, Link } from "@inertiajs/react";
import {
    AlertTriangle,
    ArrowRight,
    Building2,
    Coins,
    Home,
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
import { ActivityFeed } from "@/components/activity-feed";
import { ServicesGrid } from "@/components/service-card";
import { Badge } from "@/components/ui/badge";
import { Card, CardHeader, CardTitle } from "@/components/ui/card";
import { RulerDisplay } from "@/components/ui/legitimacy-badge";
import AppLayout from "@/layouts/app-layout";
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

interface Props {
    barony: Barony;
    services: ServiceInfo[];
    trade_routes: TradeRouteInfo[];
    recent_activity: ActivityLogEntry[];
    current_user_id: number;
    is_baron: boolean;
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
    current_user_id,
    is_baron,
}: Props) {
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
                <div className={`rounded-xl border-2 ${biome.border} ${biome.bg} p-6`}>
                    <div className="flex items-start gap-4">
                        <div className={`rounded-xl ${biome.bg} border ${biome.border} p-4`}>
                            <BiomeIcon className={`h-12 w-12 ${biome.color}`} />
                        </div>
                        <div className="flex-1">
                            <div className="flex items-center gap-3">
                                <h1 className="font-[Cinzel] text-3xl font-bold text-stone-100">
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
                            <p className="mt-2 text-stone-400">{barony.description}</p>

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
                    </div>
                </div>

                {/* Stats Row */}
                <div className="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
                    <div className="rounded-lg border border-stone-700 bg-stone-800/50 p-4 text-center">
                        <Users className="mx-auto mb-2 h-6 w-6 text-blue-400" />
                        <div className="font-pixel text-2xl text-stone-100">
                            {barony.total_population.toLocaleString()}
                        </div>
                        <div className="text-xs text-stone-500">Population</div>
                    </div>
                    <div className="rounded-lg border border-stone-700 bg-stone-800/50 p-4 text-center">
                        <Coins className="mx-auto mb-2 h-6 w-6 text-amber-400" />
                        <div className="font-pixel text-2xl text-amber-300">
                            {barony.total_wealth.toLocaleString()}
                        </div>
                        <div className="text-xs text-stone-500">Total Wealth</div>
                    </div>
                    <div className="rounded-lg border border-stone-700 bg-stone-800/50 p-4 text-center">
                        <Home className="mx-auto mb-2 h-6 w-6 text-green-400" />
                        <div className="font-pixel text-2xl text-stone-100">
                            {barony.village_count}
                        </div>
                        <div className="text-xs text-stone-500">Villages</div>
                    </div>
                    <div className="rounded-lg border border-stone-700 bg-stone-800/50 p-4 text-center">
                        <Building2 className="mx-auto mb-2 h-6 w-6 text-purple-400" />
                        <div className="font-pixel text-2xl text-stone-100">
                            {barony.town_count}
                        </div>
                        <div className="text-xs text-stone-500">Towns</div>
                    </div>
                    <div className="rounded-lg border border-stone-700 bg-stone-800/50 p-4 text-center">
                        <Coins className="mx-auto mb-2 h-6 w-6 text-stone-400" />
                        <div className="font-pixel text-2xl text-stone-100">{barony.tax_rate}%</div>
                        <div className="text-xs text-stone-500">Tax Rate</div>
                    </div>
                    <div className="rounded-lg border border-stone-700 bg-stone-800/50 p-4 text-center">
                        <MapPin className="mx-auto mb-2 h-6 w-6 text-stone-400" />
                        <div className="font-pixel text-lg text-stone-300">
                            {barony.coordinates.x}, {barony.coordinates.y}
                        </div>
                        <div className="text-xs text-stone-500">Coordinates</div>
                    </div>
                </div>

                {/* Services Grid */}
                {services && services.length > 0 && (
                    <div>
                        <h2 className="mb-3 font-pixel text-sm text-stone-400">Services</h2>
                        <ServicesGrid
                            services={services}
                            locationType="barony"
                            locationId={barony.id}
                        />
                    </div>
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
                                                href={`/${route.origin.type}s/${route.origin.id}`}
                                                className="text-stone-300 hover:text-amber-400"
                                            >
                                                {route.origin.name}
                                            </Link>
                                            <ArrowRight className="h-3 w-3 text-stone-600" />
                                            <Link
                                                href={`/${route.destination.type}s/${route.destination.id}`}
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
