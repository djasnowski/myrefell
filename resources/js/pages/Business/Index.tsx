import { Head, router, usePage } from "@inertiajs/react";
import {
    Axe,
    Bed,
    Building,
    Coins,
    Hammer,
    Loader2,
    Pickaxe,
    Store,
    Users,
    Wheat,
} from "lucide-react";
import { useState } from "react";
import AppLayout from "@/layouts/app-layout";
import type { BreadcrumbItem } from "@/types";

interface BusinessType {
    id: number;
    name: string;
    icon: string;
    description: string;
    category: string;
    category_display: string;
    location_type: string;
    purchase_cost: number;
    weekly_upkeep: number;
    max_employees: number;
    primary_skill: string | null;
    required_skill_level: number;
    produces: string[] | null;
    existing_count: number;
}

interface Business {
    id: number;
    name: string;
    type_name: string;
    type_icon: string;
    category: string;
    location_type: string;
    location_id: number;
    location_name: string;
    status: string;
    treasury: number;
    total_revenue: number;
    total_expenses: number;
    reputation: number;
    employee_count: number;
    max_employees: number;
    weekly_upkeep: number;
    established_at: string;
    owner_id: number;
    owner_name: string;
}

interface Npc {
    id: number;
    name: string;
}

interface PageProps {
    location_type: string;
    location_id: number;
    location_name: string;
    available_types: BusinessType[];
    local_businesses: Business[];
    my_businesses: Business[];
    available_npcs: Npc[];
    max_businesses: number;
    player: {
        gold: number;
    };
    [key: string]: unknown;
}

const iconMap: Record<string, typeof Store> = {
    hammer: Hammer,
    croissant: Wheat,
    axe: Axe,
    bed: Bed,
    store: Store,
    pickaxe: Pickaxe,
    wheat: Wheat,
    building: Building,
};

const locationPaths: Record<string, string> = {
    village: "villages",
    barony: "baronies",
    town: "towns",
    duchy: "duchies",
    kingdom: "kingdoms",
};

const categoryColors: Record<string, string> = {
    production: "border-blue-500/50 bg-blue-900/20",
    service: "border-purple-500/50 bg-purple-900/20",
    extraction: "border-amber-500/50 bg-amber-900/20",
};

function BusinessTypeCard({
    type,
    onEstablish,
    loading,
    canEstablish,
    playerGold,
}: {
    type: BusinessType;
    onEstablish: (name: string) => void;
    loading: boolean;
    canEstablish: boolean;
    playerGold: number;
}) {
    const Icon = iconMap[type.icon] || Store;
    const [name, setName] = useState(`${type.name}`);
    const canAfford = playerGold >= type.purchase_cost;

    return (
        <div
            className={`rounded-xl border-2 ${categoryColors[type.category] || "border-stone-600/50 bg-stone-800/50"} p-4`}
        >
            <div className="mb-3 flex items-start justify-between">
                <div className="flex items-center gap-2">
                    <div className="rounded-lg bg-stone-800/50 p-2">
                        <Icon className="h-5 w-5 text-stone-300" />
                    </div>
                    <div>
                        <h3 className="font-pixel text-sm text-amber-300">{type.name}</h3>
                        <span className="font-pixel text-[10px] text-stone-400">
                            {type.category_display}
                        </span>
                    </div>
                </div>
            </div>

            <p className="mb-3 text-sm text-stone-300">{type.description}</p>

            <div className="mb-3 grid grid-cols-2 gap-2 rounded-lg bg-stone-800/50 p-2">
                <div className="flex items-center gap-1">
                    <span className="font-pixel text-[10px] text-stone-400">Cost:</span>
                    <span
                        className={`font-pixel text-xs ${canAfford ? "text-amber-300" : "text-red-400"}`}
                    >
                        {type.purchase_cost}g
                    </span>
                </div>
                <div className="flex items-center gap-1">
                    <span className="font-pixel text-[10px] text-stone-400">Upkeep:</span>
                    <span className="font-pixel text-xs text-stone-300">
                        {type.weekly_upkeep}g/week
                    </span>
                </div>
                <div className="flex items-center gap-1">
                    <Users className="h-3 w-3 text-stone-400" />
                    <span className="font-pixel text-xs text-stone-300">
                        Max {type.max_employees}
                    </span>
                </div>
                {type.primary_skill && (
                    <div className="flex items-center gap-1">
                        <span className="font-pixel text-[10px] text-stone-400">Requires:</span>
                        <span className="font-pixel text-xs text-emerald-300">
                            {type.primary_skill} Lv.{type.required_skill_level}
                        </span>
                    </div>
                )}
            </div>

            {canEstablish && (
                <div className="mb-3">
                    <input
                        type="text"
                        value={name}
                        onChange={(e) => setName(e.target.value)}
                        placeholder="Business name..."
                        className="w-full rounded-lg border-2 border-stone-600/50 bg-stone-800/50 px-3 py-2 font-pixel text-xs text-stone-200 placeholder-stone-500 focus:border-amber-500 focus:outline-none"
                    />
                </div>
            )}

            <button
                onClick={() => onEstablish(name)}
                disabled={loading || !canEstablish || !canAfford || !name.trim()}
                className="flex w-full items-center justify-center gap-2 rounded-lg border-2 border-green-600 bg-green-900/30 px-4 py-2 font-pixel text-xs text-green-300 transition hover:bg-green-800/50 disabled:cursor-not-allowed disabled:opacity-50"
            >
                {loading ? <Loader2 className="h-4 w-4 animate-spin" /> : "Establish Business"}
            </button>
        </div>
    );
}

function BusinessCard({ business, isOwner }: { business: Business; isOwner: boolean }) {
    const Icon = iconMap[business.type_icon] || Store;
    const statusColors: Record<string, string> = {
        active: "text-green-300 bg-green-800/50",
        suspended: "text-amber-300 bg-amber-800/50",
        closed: "text-red-300 bg-red-800/50",
    };

    return (
        <div className="rounded-xl border-2 border-stone-600/50 bg-stone-800/50 p-4">
            <div className="mb-3 flex items-start justify-between">
                <div className="flex items-center gap-2">
                    <div className="rounded-lg bg-stone-700/50 p-2">
                        <Icon className="h-5 w-5 text-amber-300" />
                    </div>
                    <div>
                        <h3 className="font-pixel text-sm text-amber-300">{business.name}</h3>
                        <span className="font-pixel text-[10px] text-stone-400">
                            {business.type_name}
                        </span>
                    </div>
                </div>
                <div className={`rounded-lg px-2 py-1 ${statusColors[business.status]}`}>
                    <span className="font-pixel text-[10px]">{business.status}</span>
                </div>
            </div>

            <div className="mb-3 grid grid-cols-2 gap-2 rounded-lg bg-stone-900/50 p-2">
                <div>
                    <span className="font-pixel text-[10px] text-stone-400">Owner</span>
                    <p className="font-pixel text-xs text-stone-200">{business.owner_name}</p>
                </div>
                <div>
                    <span className="font-pixel text-[10px] text-stone-400">Employees</span>
                    <p className="font-pixel text-xs text-stone-200">
                        {business.employee_count}/{business.max_employees}
                    </p>
                </div>
                {isOwner && (
                    <>
                        <div>
                            <span className="font-pixel text-[10px] text-stone-400">Treasury</span>
                            <p className="font-pixel text-xs text-amber-300">
                                {business.treasury}g
                            </p>
                        </div>
                        <div>
                            <span className="font-pixel text-[10px] text-stone-400">
                                Reputation
                            </span>
                            <p className="font-pixel text-xs text-stone-200">
                                {business.reputation}/100
                            </p>
                        </div>
                    </>
                )}
            </div>

            {isOwner && (
                <button
                    onClick={() => router.get(`/businesses/${business.id}`)}
                    className="flex w-full items-center justify-center gap-2 rounded-lg border-2 border-amber-500 bg-amber-900/30 px-4 py-2 font-pixel text-xs text-amber-300 transition hover:bg-amber-800/50"
                >
                    Manage Business
                </button>
            )}
        </div>
    );
}

export default function BusinessIndex() {
    const {
        location_type,
        location_id,
        location_name,
        available_types,
        local_businesses,
        my_businesses,
        max_businesses,
        player,
    } = usePage<PageProps>().props;

    const [establishLoading, setEstablishLoading] = useState<number | null>(null);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: "Dashboard", href: "/dashboard" },
        {
            title: location_name,
            href: `/${locationPaths[location_type] || location_type + "s"}/${location_id}`,
        },
        { title: "Businesses", href: "#" },
    ];

    const myActiveBusinesses = my_businesses.filter((b) => b.status !== "closed");
    const canEstablishMore = myActiveBusinesses.length < max_businesses;

    const handleEstablish = (typeId: number, name: string) => {
        setEstablishLoading(typeId);
        router.post(
            "/businesses/establish",
            {
                business_type_id: typeId,
                name: name,
                location_type: location_type,
                location_id: location_id,
            },
            {
                preserveScroll: true,
                onFinish: () => setEstablishLoading(null),
            },
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Businesses - ${location_name}`} />
            <div className="flex h-full flex-1 flex-col overflow-hidden p-4">
                {/* Header */}
                <div className="mb-6 flex items-center justify-between">
                    <div>
                        <h1 className="font-pixel text-2xl text-amber-400">Businesses</h1>
                        <p className="font-pixel text-sm text-stone-400">
                            Establish or manage businesses in {location_name}
                        </p>
                    </div>
                    <div className="flex items-center gap-4">
                        <div className="flex items-center gap-2 rounded-lg border-2 border-stone-600/50 bg-stone-800/50 px-4 py-2">
                            <Coins className="h-4 w-4 text-amber-400" />
                            <span className="font-pixel text-sm text-amber-300">
                                {player.gold}g
                            </span>
                        </div>
                        <div className="rounded-lg border-2 border-stone-600/50 bg-stone-800/50 px-4 py-2">
                            <span className="font-pixel text-xs text-stone-400">Owned:</span>
                            <span className="ml-2 font-pixel text-sm text-amber-300">
                                {myActiveBusinesses.length}/{max_businesses}
                            </span>
                        </div>
                    </div>
                </div>

                {/* My Businesses Here */}
                {my_businesses.filter(
                    (b) => b.location_type === location_type && b.location_id === location_id,
                ).length > 0 && (
                    <div className="mb-6">
                        <h2 className="mb-3 font-pixel text-lg text-green-400">
                            Your Businesses Here
                        </h2>
                        <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                            {my_businesses
                                .filter(
                                    (b) =>
                                        b.location_type === location_type &&
                                        b.location_id === location_id,
                                )
                                .map((business) => (
                                    <BusinessCard
                                        key={business.id}
                                        business={business}
                                        isOwner={true}
                                    />
                                ))}
                        </div>
                    </div>
                )}

                {/* Local Businesses (other owners) */}
                {local_businesses.filter((b) => !my_businesses.some((mb) => mb.id === b.id))
                    .length > 0 && (
                    <div className="mb-6">
                        <h2 className="mb-3 font-pixel text-lg text-stone-300">Other Businesses</h2>
                        <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                            {local_businesses
                                .filter((b) => !my_businesses.some((mb) => mb.id === b.id))
                                .map((business) => (
                                    <BusinessCard
                                        key={business.id}
                                        business={business}
                                        isOwner={false}
                                    />
                                ))}
                        </div>
                    </div>
                )}

                {/* Establish New Business */}
                <div>
                    <h2 className="mb-3 font-pixel text-lg text-amber-300">Establish a Business</h2>
                    {!canEstablishMore && (
                        <div className="mb-4 rounded-lg border-2 border-amber-600/50 bg-amber-900/20 p-3">
                            <p className="font-pixel text-xs text-amber-300">
                                You own the maximum number of businesses ({max_businesses}). Close
                                one to establish another.
                            </p>
                        </div>
                    )}
                    {available_types.length > 0 ? (
                        <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                            {available_types.map((type) => (
                                <BusinessTypeCard
                                    key={type.id}
                                    type={type}
                                    onEstablish={(name) => handleEstablish(type.id, name)}
                                    loading={establishLoading === type.id}
                                    canEstablish={canEstablishMore}
                                    playerGold={player.gold}
                                />
                            ))}
                        </div>
                    ) : (
                        <div className="flex flex-1 items-center justify-center py-12">
                            <div className="text-center">
                                <Store className="mx-auto h-16 w-16 text-stone-600" />
                                <p className="mt-3 font-pixel text-base text-stone-500">
                                    No business types available
                                </p>
                                <p className="font-pixel text-xs text-stone-600">
                                    You may need higher skill levels or be at a different location.
                                </p>
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
