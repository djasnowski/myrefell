import { Head, Link, router, usePage } from "@inertiajs/react";
import {
    Anchor,
    Banknote,
    Briefcase,
    Building2,
    Church,
    Coins,
    Crown,
    Gavel,
    Home,
    Loader2,
    MapPin,
    MessageCircle,
    Mountain,
    Palmtree,
    ScrollText,
    Shield,
    Snowflake,
    Store,
    Sun,
    Swords,
    TreePine,
    Trees,
    Users,
    Waves,
    Wheat,
    type LucideIcon,
} from "lucide-react";
import { useState } from "react";
import { ActivityFeed } from "@/components/activity-feed";
import { ServicesGrid } from "@/components/service-card";
import DisasterWidget from "@/components/widgets/disaster-widget";
import { LegitimacyDisplay } from "@/components/widgets/legitimacy-badge";
import AppLayout from "@/layouts/app-layout";
import type { BreadcrumbItem } from "@/types";

interface Visitor {
    id: number;
    username: string;
    combat_level: number;
}

interface Ruler {
    id: number;
    username: string;
    primary_title?: string | null;
    legitimacy?: number;
}

interface Role {
    id: number;
    name: string;
    slug: string;
    description: string;
    tier: number;
    salary: number;
    is_elected: boolean;
    holder: {
        id: number;
        username: string;
        legitimacy: number;
        appointed_at: string;
    } | null;
}

interface Town {
    id: number;
    name: string;
    description: string;
    biome: string;
    is_capital?: boolean;
    is_port?: boolean;
    population: number;
    wealth: number;
    tax_rate: number;
    visitor_count: number;
    coordinates: { x: number; y: number };
    kingdom: { id: number; name: string } | null;
    duchy: { id: number; name: string } | null;
    barony: { id: number; name: string; biome: string } | null;
    mayor: Ruler | null;
}

interface Disaster {
    id: number;
    type: string;
    name: string;
    severity: "minor" | "moderate" | "severe" | "catastrophic";
    status: "active" | "ending";
    started_at: string;
    days_active: number;
    buildings_damaged: number;
    casualties: number;
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

interface Props {
    town: Town;
    services: ServiceInfo[];
    recent_activity: ActivityLogEntry[];
    roles: Role[];
    visitors: Visitor[];
    is_visitor: boolean;
    is_resident: boolean;
    is_mayor: boolean;
    can_migrate: boolean;
    has_pending_request: boolean;
    current_user_id: number;
    disasters?: Disaster[];
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

export default function TownShow({
    town,
    services,
    recent_activity,
    roles,
    visitors,
    is_visitor,
    is_resident,
    is_mayor,
    can_migrate,
    has_pending_request,
    current_user_id,
    disasters = [],
}: Props) {
    const { flash } = usePage<{ flash?: { success?: string; error?: string } }>().props;
    const [loading, setLoading] = useState(false);

    const handleRequestMigration = () => {
        setLoading(true);
        router.post(
            `/migration/request-town/${town.id}`,
            {},
            {
                onSuccess: () => {
                    router.reload();
                },
                onFinish: () => setLoading(false),
            },
        );
    };

    const biome = biomeConfig[town.biome] || biomeConfig.plains;
    const BiomeIcon = biome.icon;

    const breadcrumbs: BreadcrumbItem[] = [
        { title: "Dashboard", href: "/dashboard" },
        { title: "Towns", href: "/towns" },
        { title: town.name, href: `/towns/${town.id}` },
    ];

    // Filter to show only key roles (tier 3+) in the summary
    const keyRoles = roles.filter((r) => r.tier >= 3).slice(0, 4);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={town.name} />
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
                                    {town.name}
                                </h1>
                                {town.is_capital && (
                                    <span className="flex items-center gap-1 rounded-full bg-amber-900/50 px-2 py-0.5 text-xs text-amber-400">
                                        <Crown className="h-3 w-3" />
                                        Capital
                                    </span>
                                )}
                                {town.is_port && (
                                    <span className="flex items-center gap-1 rounded-full bg-blue-900/50 px-2 py-0.5 text-xs text-blue-400">
                                        <Anchor className="h-3 w-3" />
                                        Port
                                    </span>
                                )}
                                <span
                                    className={`rounded-full ${biome.bg} border ${biome.border} px-2 py-0.5 text-xs capitalize sm:px-3 ${biome.color}`}
                                >
                                    {town.biome}
                                </span>
                            </div>
                            <p className="mt-2 text-sm text-stone-400 sm:text-base">
                                {town.description}
                            </p>

                            {/* Hierarchy */}
                            <div className="mt-3 flex items-center gap-2 text-sm">
                                {town.kingdom && (
                                    <>
                                        <Link
                                            href={`/kingdoms/${town.kingdom.id}`}
                                            className="text-amber-400 hover:underline"
                                        >
                                            {town.kingdom.name}
                                        </Link>
                                        <span className="text-stone-600">›</span>
                                    </>
                                )}
                                {town.duchy && (
                                    <>
                                        <Link
                                            href={`/duchies/${town.duchy.id}`}
                                            className="text-purple-400 hover:underline"
                                        >
                                            {town.duchy.name}
                                        </Link>
                                        <span className="text-stone-600">›</span>
                                    </>
                                )}
                                {town.barony && (
                                    <>
                                        <Link
                                            href={`/baronies/${town.barony.id}`}
                                            className="text-stone-300 hover:underline"
                                        >
                                            {town.barony.name}
                                        </Link>
                                        <span className="text-stone-600">›</span>
                                    </>
                                )}
                                <span className="text-stone-500">{town.name}</span>
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

                {/* Mayor Actions */}
                {is_mayor && (
                    <div className="flex items-center gap-3 rounded-lg border border-amber-600/30 bg-amber-900/10 px-4 py-3">
                        <Gavel className="h-5 w-5 text-amber-400" />
                        <div className="flex-1">
                            <div className="font-pixel text-sm text-amber-300">
                                You are the Mayor
                            </div>
                            <div className="text-xs text-stone-400">
                                Manage town affairs, set tax rates, appoint officials
                            </div>
                        </div>
                        <div className="flex gap-2">
                            <Link
                                href={`/towns/${town.id}/roles`}
                                className="flex items-center gap-1 rounded border border-stone-600 bg-stone-800 px-3 py-1.5 font-pixel text-xs text-stone-300 transition hover:bg-stone-700"
                            >
                                <Briefcase className="h-3 w-3" />
                                Roles
                            </Link>
                            <Link
                                href={`/towns/${town.id}/treasury`}
                                className="flex items-center gap-1 rounded border border-stone-600 bg-stone-800 px-3 py-1.5 font-pixel text-xs text-stone-300 transition hover:bg-stone-700"
                            >
                                <Banknote className="h-3 w-3" />
                                Treasury
                            </Link>
                        </div>
                    </div>
                )}

                {/* Town Quick Info */}
                <div className="flex flex-wrap gap-2">
                    {town.tax_rate > 0 && (
                        <span className="flex items-center gap-1 rounded-full border border-stone-700 bg-stone-800/50 px-3 py-1 text-xs text-stone-400">
                            <Store className="h-3 w-3" />
                            Tax: {town.tax_rate}%
                        </span>
                    )}
                    {town.barony && (
                        <Link
                            href={`/baronies/${town.barony.id}`}
                            className="flex items-center gap-1 rounded-full border border-stone-700 bg-stone-800/50 px-3 py-1 text-xs text-stone-400 transition hover:text-stone-300"
                        >
                            <Building2 className="h-3 w-3" />
                            {town.barony.name}
                        </Link>
                    )}
                    {services.some((s) => s.id === "church" || s.id === "shrine") && (
                        <span className="flex items-center gap-1 rounded-full border border-stone-700 bg-stone-800/50 px-3 py-1 text-xs text-stone-400">
                            <Church className="h-3 w-3" />
                            Has Shrine
                        </span>
                    )}
                    {services.some((s) => s.id === "market") && (
                        <span className="flex items-center gap-1 rounded-full border border-stone-700 bg-stone-800/50 px-3 py-1 text-xs text-stone-400">
                            <ScrollText className="h-3 w-3" />
                            Market Open
                        </span>
                    )}
                </div>

                {/* Chat Link */}
                <Link
                    href={`/chat?location=town-${town.id}`}
                    className="flex items-center gap-2 rounded-lg border border-stone-700 bg-stone-800/30 px-4 py-2 text-sm text-stone-400 transition hover:bg-stone-800/50"
                >
                    <MessageCircle className="h-4 w-4" />
                    Town Chat
                </Link>

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

                {/* Disaster Alert */}
                {disasters.length > 0 && <DisasterWidget disasters={disasters} />}

                {/* Stats Row */}
                <div className="grid grid-cols-2 gap-2 sm:grid-cols-4 sm:gap-3">
                    <div className="rounded-lg border border-stone-700 bg-stone-800/50 p-3 text-center sm:p-4">
                        <Users className="mx-auto mb-1 h-5 w-5 text-blue-400 sm:mb-2 sm:h-6 sm:w-6" />
                        <div className="font-pixel text-lg text-stone-100 sm:text-2xl">
                            {town.visitor_count}
                        </div>
                        <div className="text-[10px] text-stone-500 sm:text-xs">Visitors</div>
                    </div>
                    <div className="rounded-lg border border-stone-700 bg-stone-800/50 p-3 text-center sm:p-4">
                        <Users className="mx-auto mb-1 h-5 w-5 text-stone-400 sm:mb-2 sm:h-6 sm:w-6" />
                        <div className="font-pixel text-lg text-stone-100 sm:text-2xl">
                            {town.population.toLocaleString()}
                        </div>
                        <div className="text-[10px] text-stone-500 sm:text-xs">NPCs</div>
                    </div>
                    <div className="rounded-lg border border-stone-700 bg-stone-800/50 p-3 text-center sm:p-4">
                        <Coins className="mx-auto mb-1 h-5 w-5 text-amber-400 sm:mb-2 sm:h-6 sm:w-6" />
                        <div className="font-pixel text-lg text-amber-300 sm:text-2xl">
                            {town.wealth.toLocaleString()}
                        </div>
                        <div className="text-[10px] text-stone-500 sm:text-xs">Treasury</div>
                    </div>
                    <div className="rounded-lg border border-stone-700 bg-stone-800/50 p-3 text-center sm:p-4">
                        <MapPin className="mx-auto mb-1 h-5 w-5 text-stone-400 sm:mb-2 sm:h-6 sm:w-6" />
                        <div className="font-pixel text-sm text-stone-300 sm:text-lg">
                            {town.coordinates.x}, {town.coordinates.y}
                        </div>
                        <div className="text-[10px] text-stone-500 sm:text-xs">Coordinates</div>
                    </div>
                </div>

                {/* Services Grid */}
                {services && services.length > 0 && (
                    <ServicesGrid
                        services={services}
                        locationType="town"
                        locationId={town.id}
                        isPort={town.is_port}
                    />
                )}

                {/* Recent Activity */}
                {recent_activity && recent_activity.length > 0 && (
                    <div>
                        <ActivityFeed
                            activities={recent_activity}
                            title="Recent Activity"
                            emptyMessage="No recent activity in this town"
                            maxHeight="250px"
                        />
                    </div>
                )}

                {/* Leadership Section */}
                <div className="grid gap-4 md:grid-cols-2">
                    {/* Mayor */}
                    <div className="rounded-xl border border-amber-600/30 bg-amber-900/10 p-4">
                        <div className="mb-3 flex items-center gap-2">
                            <Crown className="h-5 w-5 text-amber-400" />
                            <h3 className="font-pixel text-sm text-amber-400">Mayor</h3>
                        </div>
                        {town.mayor ? (
                            <div className="space-y-3">
                                <div className="flex items-center justify-between">
                                    <div>
                                        <div className="font-pixel text-lg text-stone-100">
                                            {town.mayor.username}
                                            {town.mayor.id === current_user_id && (
                                                <span className="ml-2 text-xs text-amber-400">
                                                    (You)
                                                </span>
                                            )}
                                        </div>
                                        {town.mayor.primary_title && (
                                            <div className="text-xs capitalize text-stone-500">
                                                {town.mayor.primary_title}
                                            </div>
                                        )}
                                    </div>
                                </div>
                                {town.mayor.legitimacy !== undefined && (
                                    <LegitimacyDisplay
                                        legitimacy={town.mayor.legitimacy}
                                        roleName="Mayor"
                                    />
                                )}
                            </div>
                        ) : (
                            <div className="text-stone-500">
                                <p className="font-pixel">Position Vacant</p>
                                <p className="mt-1 text-xs">No mayor has been elected</p>
                            </div>
                        )}
                    </div>

                    {/* Roles Link */}
                    <Link
                        href={`/towns/${town.id}/roles`}
                        className="flex items-center justify-between rounded-xl border border-stone-600/30 bg-stone-800/30 p-4 transition hover:bg-stone-800/50"
                    >
                        <div className="flex items-center gap-3">
                            <Shield className="h-8 w-8 text-stone-400" />
                            <div>
                                <div className="font-pixel text-stone-200">Town Officials</div>
                                <div className="text-xs text-stone-500">
                                    View all {roles.length} positions
                                </div>
                            </div>
                        </div>
                        <span className="text-stone-500">›</span>
                    </Link>
                </div>

                {/* Key Roles Preview */}
                {keyRoles.length > 0 && (
                    <div>
                        <h2 className="mb-3 font-pixel text-sm text-stone-400">Key Officials</h2>
                        <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                            {keyRoles.map((role) => (
                                <div
                                    key={role.id}
                                    className={`rounded-lg border p-3 ${
                                        role.holder
                                            ? "border-amber-600/30 bg-amber-900/10"
                                            : "border-stone-700 bg-stone-800/30"
                                    }`}
                                >
                                    <div className="flex items-center justify-between">
                                        <span className="font-pixel text-xs text-stone-400">
                                            {role.name}
                                        </span>
                                        <span className="rounded bg-stone-700 px-1.5 py-0.5 text-[10px] text-stone-400">
                                            T{role.tier}
                                        </span>
                                    </div>
                                    {role.holder ? (
                                        <div className="mt-2">
                                            <div className="font-pixel text-sm text-stone-200">
                                                {role.holder.username}
                                            </div>
                                            <div className="text-[10px] text-stone-500">
                                                {role.holder.appointed_at}
                                            </div>
                                        </div>
                                    ) : (
                                        <div className="mt-2 font-pixel text-xs text-stone-600 italic">
                                            Vacant
                                        </div>
                                    )}
                                </div>
                            ))}
                        </div>
                    </div>
                )}

                {/* Visitors */}
                {visitors.length > 0 && (
                    <div>
                        <div className="mb-3 flex items-center justify-between">
                            <h2 className="font-pixel text-sm text-stone-400">
                                Visitors ({town.visitor_count})
                            </h2>
                        </div>
                        <div className="grid gap-2 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4">
                            {visitors.map((visitor) => (
                                <div
                                    key={visitor.id}
                                    className="flex items-center gap-3 rounded-lg border border-stone-700 bg-stone-800/30 p-3"
                                >
                                    <div className="flex h-10 w-10 items-center justify-center rounded-full bg-stone-700">
                                        <Users className="h-5 w-5 text-stone-400" />
                                    </div>
                                    <div>
                                        <div className="font-pixel text-sm text-stone-200">
                                            {visitor.username}
                                            {visitor.id === current_user_id && (
                                                <span className="ml-1 text-xs text-blue-400">
                                                    (You)
                                                </span>
                                            )}
                                        </div>
                                        <div className="flex items-center gap-1 text-xs text-stone-500">
                                            <Swords className="h-3 w-3" />
                                            Combat Lv. {visitor.combat_level}
                                        </div>
                                    </div>
                                </div>
                            ))}
                        </div>
                        {town.visitor_count > 12 && (
                            <p className="mt-2 text-center text-xs text-stone-500">
                                +{town.visitor_count - 12} more visitors
                            </p>
                        )}
                    </div>
                )}

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
                                        {town.mayor ? "Request to Settle" : "Settle Here"}
                                    </span>
                                    <p className="text-xs text-stone-400">
                                        {town.mayor
                                            ? "The town mayor must approve your request"
                                            : "Make this town your new home"}
                                    </p>
                                </div>
                            </button>
                        ) : (
                            <div className="rounded-xl border border-stone-700 bg-stone-800/30 p-4 text-center">
                                <p className="font-pixel text-stone-500">Migration on Cooldown</p>
                                <p className="text-xs text-stone-600">
                                    You must wait before you can move again
                                </p>
                            </div>
                        )}
                    </div>
                )}

                {/* Port indicator */}
                {town.is_port && (
                    <Link
                        href={`/towns/${town.id}/port`}
                        className="flex items-center gap-4 rounded-xl border border-blue-600/30 bg-blue-900/10 p-4 transition hover:bg-blue-900/20"
                    >
                        <Anchor className="h-8 w-8 text-blue-400" />
                        <div>
                            <div className="font-pixel text-blue-300">Harbor</div>
                            <div className="text-xs text-stone-400">
                                Book passage to distant lands
                            </div>
                        </div>
                    </Link>
                )}
            </div>
        </AppLayout>
    );
}
