import { Head, router, usePage } from "@inertiajs/react";
import {
    Axe,
    Bed,
    Building,
    Coins,
    Hammer,
    MapPin,
    Pickaxe,
    Store,
    Users,
    Wheat,
} from "lucide-react";
import AppLayout from "@/layouts/app-layout";
import type { BreadcrumbItem } from "@/types";

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

interface PageProps {
    businesses: Business[];
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

const statusColors: Record<string, string> = {
    active: "text-green-300 bg-green-800/50 border-green-600/50",
    suspended: "text-amber-300 bg-amber-800/50 border-amber-600/50",
    closed: "text-red-300 bg-red-800/50 border-red-600/50",
};

function BusinessCard({ business }: { business: Business }) {
    const Icon = iconMap[business.type_icon] || Store;

    return (
        <div
            onClick={() => router.get(`/businesses/${business.id}`)}
            className="cursor-pointer rounded-xl border-2 border-stone-600/50 bg-stone-800/50 p-4 transition hover:border-amber-500/50 hover:bg-stone-800/80"
        >
            <div className="mb-3 flex items-start justify-between">
                <div className="flex items-center gap-3">
                    <div className="rounded-lg bg-stone-700/50 p-2">
                        <Icon className="h-6 w-6 text-amber-300" />
                    </div>
                    <div>
                        <h3 className="font-pixel text-sm text-amber-300">{business.name}</h3>
                        <span className="font-pixel text-[10px] text-stone-400">
                            {business.type_name}
                        </span>
                    </div>
                </div>
                <div className={`rounded-lg border px-2 py-1 ${statusColors[business.status]}`}>
                    <span className="font-pixel text-[10px]">{business.status}</span>
                </div>
            </div>

            <div className="mb-3 flex items-center gap-1 text-stone-400">
                <MapPin className="h-3 w-3" />
                <span className="font-pixel text-[10px]">{business.location_name}</span>
            </div>

            <div className="grid grid-cols-2 gap-2 rounded-lg bg-stone-900/50 p-2">
                <div className="flex items-center gap-2">
                    <Coins className="h-4 w-4 text-amber-400" />
                    <div>
                        <span className="font-pixel text-[10px] text-stone-400">Treasury</span>
                        <p className="font-pixel text-xs text-amber-300">{business.treasury}g</p>
                    </div>
                </div>
                <div className="flex items-center gap-2">
                    <Users className="h-4 w-4 text-stone-400" />
                    <div>
                        <span className="font-pixel text-[10px] text-stone-400">Employees</span>
                        <p className="font-pixel text-xs text-stone-200">
                            {business.employee_count}/{business.max_employees}
                        </p>
                    </div>
                </div>
                <div>
                    <span className="font-pixel text-[10px] text-stone-400">Reputation</span>
                    <p className="font-pixel text-xs text-stone-200">{business.reputation}/100</p>
                </div>
                <div>
                    <span className="font-pixel text-[10px] text-stone-400">Upkeep</span>
                    <p className="font-pixel text-xs text-red-300">
                        {business.weekly_upkeep}g/week
                    </p>
                </div>
            </div>

            <div className="mt-3 flex justify-between text-[10px]">
                <span className="font-pixel text-green-400">
                    Revenue: {business.total_revenue}g
                </span>
                <span className="font-pixel text-red-400">
                    Expenses: {business.total_expenses}g
                </span>
            </div>
        </div>
    );
}

export default function MyBusinesses() {
    const { businesses, max_businesses, player } = usePage<PageProps>().props;

    const breadcrumbs: BreadcrumbItem[] = [
        { title: "Dashboard", href: "/dashboard" },
        { title: "My Businesses", href: "#" },
    ];

    const activeBusinesses = businesses.filter((b) => b.status !== "closed");
    const closedBusinesses = businesses.filter((b) => b.status === "closed");

    const totalTreasury = activeBusinesses.reduce((sum, b) => sum + b.treasury, 0);
    const totalUpkeep = activeBusinesses.reduce((sum, b) => sum + b.weekly_upkeep, 0);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="My Businesses" />
            <div className="flex h-full flex-1 flex-col overflow-auto p-4">
                {/* Header */}
                <div className="mb-6 flex items-center justify-between">
                    <div>
                        <h1 className="font-pixel text-2xl text-amber-400">My Businesses</h1>
                        <p className="font-pixel text-sm text-stone-400">
                            Manage your business empire
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
                                {activeBusinesses.length}/{max_businesses}
                            </span>
                        </div>
                    </div>
                </div>

                {/* Summary */}
                {activeBusinesses.length > 0 && (
                    <div className="mb-6 grid grid-cols-2 gap-4 md:grid-cols-4">
                        <div className="rounded-xl border-2 border-stone-600/50 bg-stone-800/50 p-4 text-center">
                            <span className="font-pixel text-[10px] text-stone-400">
                                Total Treasury
                            </span>
                            <p className="font-pixel text-xl text-amber-300">{totalTreasury}g</p>
                        </div>
                        <div className="rounded-xl border-2 border-stone-600/50 bg-stone-800/50 p-4 text-center">
                            <span className="font-pixel text-[10px] text-stone-400">
                                Weekly Upkeep
                            </span>
                            <p className="font-pixel text-xl text-red-300">{totalUpkeep}g</p>
                        </div>
                        <div className="rounded-xl border-2 border-stone-600/50 bg-stone-800/50 p-4 text-center">
                            <span className="font-pixel text-[10px] text-stone-400">
                                Active Businesses
                            </span>
                            <p className="font-pixel text-xl text-green-300">
                                {activeBusinesses.filter((b) => b.status === "active").length}
                            </p>
                        </div>
                        <div className="rounded-xl border-2 border-stone-600/50 bg-stone-800/50 p-4 text-center">
                            <span className="font-pixel text-[10px] text-stone-400">Suspended</span>
                            <p className="font-pixel text-xl text-amber-300">
                                {activeBusinesses.filter((b) => b.status === "suspended").length}
                            </p>
                        </div>
                    </div>
                )}

                {/* Active Businesses */}
                {activeBusinesses.length > 0 ? (
                    <div className="mb-6">
                        <h2 className="mb-3 font-pixel text-lg text-stone-300">
                            Active Businesses
                        </h2>
                        <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                            {activeBusinesses.map((business) => (
                                <BusinessCard key={business.id} business={business} />
                            ))}
                        </div>
                    </div>
                ) : (
                    <div className="flex flex-1 items-center justify-center py-12">
                        <div className="text-center">
                            <Store className="mx-auto h-16 w-16 text-stone-600" />
                            <p className="mt-3 font-pixel text-base text-stone-500">
                                No businesses owned
                            </p>
                            <p className="font-pixel text-xs text-stone-600">
                                Visit a village or town to establish your first business.
                            </p>
                        </div>
                    </div>
                )}

                {/* Closed Businesses */}
                {closedBusinesses.length > 0 && (
                    <div>
                        <h2 className="mb-3 font-pixel text-lg text-stone-500">
                            Closed Businesses
                        </h2>
                        <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                            {closedBusinesses.map((business) => (
                                <div
                                    key={business.id}
                                    className="rounded-xl border-2 border-stone-700/30 bg-stone-900/30 p-4 opacity-60"
                                >
                                    <div className="flex items-center gap-3">
                                        <Store className="h-6 w-6 text-stone-600" />
                                        <div>
                                            <h3 className="font-pixel text-sm text-stone-500">
                                                {business.name}
                                            </h3>
                                            <span className="font-pixel text-[10px] text-stone-600">
                                                {business.type_name}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
