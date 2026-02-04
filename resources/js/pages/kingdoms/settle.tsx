import { Head, Link, router } from "@inertiajs/react";
import {
    Anchor,
    Building,
    ChevronRight,
    Home,
    Loader2,
    MapPin,
    Mountain,
    Palmtree,
    Snowflake,
    Sun,
    TreePine,
    Trees,
    Users,
    Waves,
    Wheat,
    type LucideIcon,
} from "lucide-react";
import { useState } from "react";
import AppLayout from "@/layouts/app-layout";
import type { BreadcrumbItem } from "@/types";

interface Settlement {
    id: number;
    name: string;
    type: "town" | "village" | "hamlet";
    barony_name: string;
    biome: string;
    population: number;
    resident_count: number;
    is_port: boolean;
    is_home: boolean;
}

interface Kingdom {
    id: number;
    name: string;
    biome: string;
}

interface Props {
    kingdom: Kingdom;
    settlements: Settlement[];
    can_migrate: boolean;
    cooldown_ends_at: string | null;
    cooldown_remaining: string | null;
    has_pending_request: boolean;
    current_home_village_id: number | null;
}

const biomeConfig: Record<string, { icon: LucideIcon; color: string; bg: string; border: string }> =
    {
        plains: {
            icon: Wheat,
            color: "text-amber-400",
            bg: "bg-amber-900/20",
            border: "border-amber-600/50",
        },
        forest: {
            icon: Trees,
            color: "text-green-400",
            bg: "bg-green-900/20",
            border: "border-green-600/50",
        },
        mountains: {
            icon: Mountain,
            color: "text-stone-400",
            bg: "bg-stone-700/20",
            border: "border-stone-500/50",
        },
        desert: {
            icon: Sun,
            color: "text-yellow-400",
            bg: "bg-yellow-900/20",
            border: "border-yellow-600/50",
        },
        tundra: {
            icon: Snowflake,
            color: "text-cyan-400",
            bg: "bg-cyan-900/20",
            border: "border-cyan-600/50",
        },
        swamp: {
            icon: Waves,
            color: "text-emerald-400",
            bg: "bg-emerald-900/20",
            border: "border-emerald-600/50",
        },
        tropical: {
            icon: Palmtree,
            color: "text-lime-400",
            bg: "bg-lime-900/20",
            border: "border-lime-600/50",
        },
        taiga: {
            icon: TreePine,
            color: "text-teal-400",
            bg: "bg-teal-900/20",
            border: "border-teal-600/50",
        },
    };

const typeConfig: Record<string, { icon: LucideIcon; color: string; label: string }> = {
    town: { icon: Building, color: "text-purple-400", label: "Town" },
    village: { icon: Home, color: "text-amber-400", label: "Village" },
    hamlet: { icon: Home, color: "text-stone-400", label: "Hamlet" },
};

export default function KingdomSettle({
    kingdom,
    settlements,
    can_migrate,
    cooldown_remaining,
    has_pending_request,
    current_home_village_id,
}: Props) {
    const [loading, setLoading] = useState<number | null>(null);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: "Kingdoms", href: "/kingdoms" },
        { title: kingdom.name, href: `/kingdoms/${kingdom.id}` },
        { title: "Settle", href: `/kingdoms/${kingdom.id}/settle` },
    ];

    const handleSettle = (settlementId: number) => {
        setLoading(settlementId);
        router.post(
            `/migration/request/${settlementId}`,
            {},
            {
                preserveScroll: true,
                onSuccess: () => {
                    router.reload();
                },
                onFinish: () => setLoading(null),
            },
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Settle in ${kingdom.name}`} />
            <div className="flex flex-col gap-6 p-6">
                {/* Header */}
                <div className="rounded-xl border-2 border-teal-600/50 bg-teal-900/20 p-6">
                    <div className="flex items-center gap-4">
                        <div className="rounded-xl bg-teal-900/50 border border-teal-600/50 p-4">
                            <Home className="h-12 w-12 text-teal-400" />
                        </div>
                        <div>
                            <h1 className="font-[Cinzel] text-3xl font-bold text-stone-100">
                                Settle in {kingdom.name}
                            </h1>
                            <p className="mt-1 text-stone-400">Choose a settlement to call home</p>
                        </div>
                    </div>
                </div>

                {/* Status Messages */}
                {has_pending_request && (
                    <div className="rounded-xl border border-amber-600/30 bg-amber-900/10 p-4">
                        <div className="flex items-center gap-3">
                            <Loader2 className="h-5 w-5 animate-spin text-amber-400" />
                            <div>
                                <p className="font-pixel text-amber-300">
                                    Migration Request Pending
                                </p>
                                <p className="text-xs text-stone-400">
                                    You already have a pending migration request.{" "}
                                    <Link
                                        href="/migration"
                                        className="text-amber-400 hover:underline"
                                    >
                                        View status
                                    </Link>
                                </p>
                            </div>
                        </div>
                    </div>
                )}

                {!can_migrate && !has_pending_request && (
                    <div className="rounded-xl border border-red-600/30 bg-red-900/10 p-4">
                        <p className="font-pixel text-red-300">Migration Cooldown Active</p>
                        <p className="text-xs text-stone-400">
                            {cooldown_remaining
                                ? `You can migrate again ${cooldown_remaining}`
                                : "You must wait before you can move again."}
                        </p>
                    </div>
                )}

                {/* Settlements Grid */}
                <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                    {settlements.map((settlement) => {
                        const biome = biomeConfig[settlement.biome] || biomeConfig.plains;
                        const type = typeConfig[settlement.type] || typeConfig.village;
                        const TypeIcon = type.icon;
                        const BiomeIcon = biome.icon;
                        const canSettle =
                            can_migrate && !has_pending_request && !settlement.is_home;

                        return (
                            <div
                                key={`${settlement.type}-${settlement.id}`}
                                className={`rounded-xl border-2 p-4 transition ${
                                    settlement.is_home
                                        ? "border-green-600/50 bg-green-900/20"
                                        : `${biome.border} ${biome.bg}`
                                }`}
                            >
                                <div className="mb-3 flex items-start justify-between">
                                    <div className="flex items-center gap-3">
                                        <div
                                            className={`rounded-lg ${biome.bg} border ${biome.border} p-2`}
                                        >
                                            <TypeIcon className={`h-6 w-6 ${type.color}`} />
                                        </div>
                                        <div>
                                            <Link
                                                href={
                                                    settlement.type === "town"
                                                        ? `/towns/${settlement.id}`
                                                        : `/villages/${settlement.id}`
                                                }
                                                className="font-pixel text-lg text-stone-100 hover:text-amber-300"
                                            >
                                                {settlement.name}
                                            </Link>
                                            <div className="flex items-center gap-2 text-xs text-stone-500">
                                                <span className={type.color}>{type.label}</span>
                                                <span>in {settlement.barony_name}</span>
                                            </div>
                                        </div>
                                    </div>
                                    {settlement.is_home && (
                                        <span className="rounded-full bg-green-800/50 px-2 py-0.5 font-pixel text-[10px] text-green-300">
                                            Your Home
                                        </span>
                                    )}
                                </div>

                                {/* Stats */}
                                <div className="mb-3 grid grid-cols-3 gap-2">
                                    <div className="rounded-lg bg-stone-800/50 p-2 text-center">
                                        <Users className="mx-auto mb-1 h-4 w-4 text-blue-400" />
                                        <div className="font-pixel text-sm text-stone-100">
                                            {settlement.resident_count}
                                        </div>
                                        <div className="text-[10px] text-stone-500">Players</div>
                                    </div>
                                    <div className="rounded-lg bg-stone-800/50 p-2 text-center">
                                        <Users className="mx-auto mb-1 h-4 w-4 text-stone-400" />
                                        <div className="font-pixel text-sm text-stone-100">
                                            {settlement.population}
                                        </div>
                                        <div className="text-[10px] text-stone-500">NPCs</div>
                                    </div>
                                    <div className="rounded-lg bg-stone-800/50 p-2 text-center">
                                        <BiomeIcon
                                            className={`mx-auto mb-1 h-4 w-4 ${biome.color}`}
                                        />
                                        <div
                                            className={`font-pixel text-sm capitalize ${biome.color}`}
                                        >
                                            {settlement.biome}
                                        </div>
                                        <div className="text-[10px] text-stone-500">Biome</div>
                                    </div>
                                </div>

                                {/* Port badge */}
                                {settlement.is_port && (
                                    <div className="mb-3 flex items-center gap-1 text-xs text-blue-400">
                                        <Anchor className="h-3 w-3" />
                                        <span>Port Settlement</span>
                                    </div>
                                )}

                                {/* Action Button */}
                                {settlement.is_home ? (
                                    <div className="rounded-lg bg-green-900/30 py-2 text-center">
                                        <span className="font-pixel text-xs text-green-300">
                                            You live here
                                        </span>
                                    </div>
                                ) : (
                                    <button
                                        onClick={() => handleSettle(settlement.id)}
                                        disabled={!canSettle || loading !== null}
                                        className={`flex w-full items-center justify-center gap-2 rounded-lg py-2 font-pixel text-xs transition ${
                                            canSettle && loading === null
                                                ? "border-2 border-teal-500/50 bg-teal-900/30 text-teal-300 hover:bg-teal-800/40"
                                                : "cursor-not-allowed border border-stone-700 bg-stone-800/30 text-stone-500"
                                        }`}
                                    >
                                        {loading === settlement.id ? (
                                            <>
                                                <Loader2 className="h-3 w-3 animate-spin" />
                                                Requesting...
                                            </>
                                        ) : (
                                            <>
                                                <MapPin className="h-3 w-3" />
                                                {settlement.resident_count === 0
                                                    ? "Settle Here"
                                                    : "Request to Settle"}
                                            </>
                                        )}
                                    </button>
                                )}
                            </div>
                        );
                    })}
                </div>

                {settlements.length === 0 && (
                    <div className="flex flex-1 items-center justify-center py-12">
                        <div className="text-center">
                            <Home className="mx-auto h-16 w-16 text-stone-600" />
                            <p className="mt-4 font-pixel text-lg text-stone-500">
                                No settlements found
                            </p>
                            <p className="text-sm text-stone-600">
                                This kingdom has no settlements yet.
                            </p>
                        </div>
                    </div>
                )}

                {/* Back Link */}
                <Link
                    href={`/kingdoms/${kingdom.id}`}
                    className="flex items-center gap-2 text-stone-400 hover:text-stone-200"
                >
                    <ChevronRight className="h-4 w-4 rotate-180" />
                    <span className="font-pixel text-sm">Back to {kingdom.name}</span>
                </Link>
            </div>
        </AppLayout>
    );
}
