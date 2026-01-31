import { Head, Link, router, usePage } from "@inertiajs/react";
import {
    ArrowRight,
    Box,
    Clock,
    Coins,
    Eye,
    MapPin,
    Package,
    Plus,
    Shield,
    Truck,
    XCircle,
} from "lucide-react";
import { useState } from "react";
import AppLayout from "@/layouts/app-layout";
import type { BreadcrumbItem } from "@/types";

interface Location {
    type: string;
    id: number;
    name: string;
}

interface CaravanGoods {
    id: number;
    item_id: number;
    item_name: string;
    quantity: number;
    purchase_price: number;
    total_value: number;
}

interface TradeRoute {
    id: number;
    name: string;
    danger_level: string;
}

interface Caravan {
    id: number;
    name: string;
    status: "preparing" | "traveling" | "arrived" | "returning" | "disbanded" | "destroyed";
    capacity: number;
    guards: number;
    gold_carried: number;
    travel_progress: number;
    travel_total: number;
    travel_progress_percent: number;
    departed_at: string | null;
    arrived_at: string | null;
    current_location: Location;
    destination: Location;
    route: TradeRoute | null;
    goods?: CaravanGoods[];
    total_goods?: number;
    remaining_capacity?: number;
    goods_value?: number;
}

interface AvailableRoute {
    id: number;
    name: string;
    origin: Location;
    destination: Location;
    base_travel_days: number;
    danger_level: string;
}

interface PageProps {
    active_caravans: Caravan[];
    arrived_caravans: Caravan[];
    completed_caravans: Caravan[];
    available_routes: AvailableRoute[];
    current_location: Location;
    caravan_cost: number;
    guard_cost: number;
    base_capacity: number;
    [key: string]: unknown;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: "Dashboard", href: "/dashboard" },
    { title: "Trade Routes", href: "/trade/routes" },
    { title: "My Caravans", href: "/trade/caravans" },
];

const statusColors: Record<string, { bg: string; text: string; label: string }> = {
    preparing: { bg: "bg-blue-900/30", text: "text-blue-400", label: "Loading" },
    traveling: { bg: "bg-amber-900/30", text: "text-amber-400", label: "In Transit" },
    arrived: { bg: "bg-green-900/30", text: "text-green-400", label: "Arrived" },
    returning: { bg: "bg-purple-900/30", text: "text-purple-400", label: "Returning" },
    disbanded: { bg: "bg-stone-900/30", text: "text-stone-400", label: "Disbanded" },
    destroyed: { bg: "bg-red-900/30", text: "text-red-400", label: "Destroyed" },
};

const dangerColors: Record<string, string> = {
    safe: "text-green-400",
    moderate: "text-yellow-400",
    dangerous: "text-orange-400",
    perilous: "text-red-400",
};

export default function Caravans() {
    const {
        active_caravans,
        arrived_caravans,
        completed_caravans,
        available_routes,
        current_location,
        caravan_cost,
        guard_cost,
        base_capacity,
    } = usePage<PageProps>().props;

    const [showCreateForm, setShowCreateForm] = useState(false);
    const [formData, setFormData] = useState({
        name: "",
        guards: 0,
    });
    const [isCreating, setIsCreating] = useState(false);
    const [isDisbanding, setIsDisbanding] = useState<number | null>(null);
    const [error, setError] = useState<string | null>(null);
    const [success, setSuccess] = useState<string | null>(null);

    const totalCost = caravan_cost + formData.guards * guard_cost;

    const createCaravan = async () => {
        if (!formData.name.trim()) {
            setError("Please enter a caravan name.");
            return;
        }

        setIsCreating(true);
        setError(null);

        try {
            const response = await fetch("/trade/caravans", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN":
                        document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')
                            ?.content || "",
                },
                body: JSON.stringify(formData),
            });

            const data = await response.json();
            if (data.success) {
                setSuccess(data.message);
                setShowCreateForm(false);
                setFormData({ name: "", guards: 0 });
                router.reload();
            } else {
                setError(data.message);
            }
        } catch {
            setError("Failed to create caravan");
        } finally {
            setIsCreating(false);
        }
    };

    const disbandCaravan = async (caravanId: number) => {
        setIsDisbanding(caravanId);
        setError(null);

        try {
            const response = await fetch(`/trade/caravans/${caravanId}/disband`, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN":
                        document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')
                            ?.content || "",
                },
            });

            const data = await response.json();
            if (data.success) {
                setSuccess(data.message);
                router.reload();
            } else {
                setError(data.message);
            }
        } catch {
            setError("Failed to disband caravan");
        } finally {
            setIsDisbanding(null);
        }
    };

    const renderCaravanCard = (caravan: Caravan, showActions: boolean = true) => {
        const status = statusColors[caravan.status] || statusColors.preparing;
        const isActive = ["preparing", "traveling", "returning"].includes(caravan.status);

        return (
            <div
                key={caravan.id}
                className="rounded-xl border-2 border-stone-600/50 bg-stone-800/30 p-4"
            >
                {/* Header */}
                <div className="mb-3 flex items-start justify-between">
                    <div className="flex items-center gap-2">
                        <Truck className="h-5 w-5 text-amber-400" />
                        <h3 className="font-pixel text-base text-white">{caravan.name}</h3>
                    </div>
                    <span
                        className={`rounded px-2 py-1 font-pixel text-[10px] ${status.bg} ${status.text}`}
                    >
                        {status.label}
                    </span>
                </div>

                {/* Route Info */}
                <div className="mb-3 flex items-center justify-between rounded-lg bg-stone-900/50 p-2">
                    <div className="flex items-center gap-1">
                        <MapPin className="h-3 w-3 text-green-400" />
                        <span className="font-pixel text-xs text-white">
                            {caravan.current_location.name}
                        </span>
                    </div>
                    <ArrowRight className="h-4 w-4 text-stone-500" />
                    <div className="flex items-center gap-1">
                        <MapPin className="h-3 w-3 text-red-400" />
                        <span className="font-pixel text-xs text-white">
                            {caravan.destination.name}
                        </span>
                    </div>
                </div>

                {/* Progress Bar (for traveling) */}
                {caravan.status === "traveling" && (
                    <div className="mb-3">
                        <div className="mb-1 flex justify-between font-pixel text-[10px] text-stone-400">
                            <span>
                                Day {caravan.travel_progress}/{caravan.travel_total}
                            </span>
                            <span>{caravan.travel_progress_percent}%</span>
                        </div>
                        <div className="h-2 w-full overflow-hidden rounded-full bg-stone-700">
                            <div
                                className="h-full bg-amber-500 transition-all"
                                style={{ width: `${caravan.travel_progress_percent}%` }}
                            />
                        </div>
                    </div>
                )}

                {/* Stats */}
                <div className="mb-3 grid grid-cols-3 gap-2 font-pixel text-xs">
                    <div className="flex items-center gap-1">
                        <Shield className="h-3 w-3 text-blue-400" />
                        <span className="text-stone-400">Guards:</span>
                        <span className="text-white">{caravan.guards}</span>
                    </div>
                    <div className="flex items-center gap-1">
                        <Coins className="h-3 w-3 text-yellow-400" />
                        <span className="text-stone-400">Gold:</span>
                        <span className="text-yellow-300">{caravan.gold_carried}</span>
                    </div>
                    <div className="flex items-center gap-1">
                        <Box className="h-3 w-3 text-purple-400" />
                        <span className="text-stone-400">Capacity:</span>
                        <span className="text-white">
                            {caravan.total_goods ?? 0}/{caravan.capacity}
                        </span>
                    </div>
                </div>

                {/* Goods (if any) */}
                {caravan.goods && caravan.goods.length > 0 && (
                    <div className="mb-3 rounded-lg bg-stone-900/30 p-2">
                        <div className="mb-1 flex items-center gap-1 font-pixel text-[10px] text-stone-400">
                            <Package className="h-3 w-3" />
                            <span>Cargo:</span>
                        </div>
                        <div className="flex flex-wrap gap-1">
                            {caravan.goods.map((goods) => (
                                <span
                                    key={goods.id}
                                    className="rounded bg-stone-700/50 px-2 py-0.5 font-pixel text-[10px] text-stone-300"
                                >
                                    {goods.quantity}x {goods.item_name}
                                </span>
                            ))}
                        </div>
                        <div className="mt-1 font-pixel text-[10px] text-amber-400">
                            Total Value: {caravan.goods_value}g
                        </div>
                    </div>
                )}

                {/* Route danger (if has route) */}
                {caravan.route && (
                    <div className="mb-3 flex items-center gap-2 font-pixel text-xs">
                        <span className="text-stone-400">Route:</span>
                        <span className="text-white">{caravan.route.name}</span>
                        <span
                            className={`capitalize ${dangerColors[caravan.route.danger_level] || "text-stone-400"}`}
                        >
                            ({caravan.route.danger_level})
                        </span>
                    </div>
                )}

                {/* Actions */}
                {showActions && (
                    <div className="flex gap-2">
                        <Link
                            href={`/trade/caravans/${caravan.id}`}
                            className="flex flex-1 items-center justify-center gap-1 rounded border border-amber-600/50 bg-amber-900/20 px-3 py-1.5 font-pixel text-xs text-amber-300 transition hover:bg-amber-900/40"
                        >
                            <Eye className="h-3 w-3" />
                            View Details
                        </Link>
                        {isActive && (
                            <button
                                onClick={() => disbandCaravan(caravan.id)}
                                disabled={
                                    isDisbanding === caravan.id || caravan.status === "traveling"
                                }
                                className="flex flex-1 items-center justify-center gap-1 rounded border border-red-600/50 bg-red-900/20 px-3 py-1.5 font-pixel text-xs text-red-300 transition hover:bg-red-900/40 disabled:cursor-not-allowed disabled:opacity-50"
                                title={
                                    caravan.status === "traveling"
                                        ? "Cannot disband while traveling"
                                        : "Disband caravan"
                                }
                            >
                                <XCircle className="h-3 w-3" />
                                {isDisbanding === caravan.id ? "Disbanding..." : "Disband"}
                            </button>
                        )}
                    </div>
                )}

                {/* Arrived info */}
                {caravan.arrived_at && (
                    <div className="mt-2 flex items-center gap-1 font-pixel text-[10px] text-stone-500">
                        <Clock className="h-3 w-3" />
                        <span>Arrived: {new Date(caravan.arrived_at).toLocaleDateString()}</span>
                    </div>
                )}
            </div>
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="My Caravans" />
            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="font-pixel text-2xl text-amber-400">My Caravans</h1>
                        <p className="font-pixel text-sm text-stone-400">
                            Manage your trade caravans and cargo
                        </p>
                    </div>
                    <button
                        onClick={() => setShowCreateForm(!showCreateForm)}
                        className="flex items-center gap-2 rounded border-2 border-amber-600/50 bg-amber-900/20 px-4 py-2 font-pixel text-xs text-amber-300 transition hover:bg-amber-900/40"
                    >
                        <Plus className="h-4 w-4" />
                        {showCreateForm ? "Cancel" : "New Caravan"}
                    </button>
                </div>

                {/* Messages */}
                {error && (
                    <div className="rounded-lg border border-red-500/50 bg-red-900/30 p-3 font-pixel text-sm text-red-300">
                        {error}
                    </div>
                )}
                {success && (
                    <div className="rounded-lg border border-green-500/50 bg-green-900/30 p-3 font-pixel text-sm text-green-300">
                        {success}
                    </div>
                )}

                {/* Create Form */}
                {showCreateForm && (
                    <div className="rounded-xl border-2 border-amber-500/30 bg-amber-900/20 p-4">
                        <h3 className="mb-4 font-pixel text-base text-amber-300">
                            Create New Caravan
                        </h3>

                        <div className="mb-3 rounded-lg bg-stone-800/50 p-2 font-pixel text-xs text-stone-400">
                            <MapPin className="mr-1 inline h-3 w-3" />
                            Creating at: <span className="text-white">{current_location.name}</span>
                        </div>

                        <div className="mb-4">
                            <label className="mb-1 block font-pixel text-xs text-stone-400">
                                Caravan Name
                            </label>
                            <input
                                type="text"
                                value={formData.name}
                                onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                                maxLength={100}
                                className="w-full rounded border border-stone-600 bg-stone-800 px-3 py-2 font-pixel text-sm text-white focus:border-amber-500 focus:outline-none"
                                placeholder="e.g., Northern Traders"
                            />
                        </div>

                        <div className="mb-4">
                            <label className="mb-1 block font-pixel text-xs text-stone-400">
                                Guards ({guard_cost}g each)
                            </label>
                            <input
                                type="number"
                                value={formData.guards}
                                onChange={(e) =>
                                    setFormData({
                                        ...formData,
                                        guards: Math.max(
                                            0,
                                            Math.min(20, parseInt(e.target.value) || 0),
                                        ),
                                    })
                                }
                                min={0}
                                max={20}
                                className="w-full rounded border border-stone-600 bg-stone-800 px-3 py-2 font-pixel text-sm text-white focus:border-amber-500 focus:outline-none"
                            />
                            <p className="mt-1 font-pixel text-[10px] text-stone-500">
                                Guards reduce bandit attack chance during travel
                            </p>
                        </div>

                        <div className="mb-4 rounded-lg bg-stone-800/50 p-3">
                            <div className="grid grid-cols-2 gap-2 font-pixel text-xs">
                                <div className="text-stone-400">Base Cost:</div>
                                <div className="text-right text-white">{caravan_cost}g</div>
                                <div className="text-stone-400">Guard Cost:</div>
                                <div className="text-right text-white">
                                    {formData.guards * guard_cost}g
                                </div>
                                <div className="text-stone-400">Capacity:</div>
                                <div className="text-right text-white">{base_capacity} units</div>
                                <div className="border-t border-stone-600 pt-1 text-amber-400">
                                    Total:
                                </div>
                                <div className="border-t border-stone-600 pt-1 text-right text-amber-300">
                                    {totalCost}g
                                </div>
                            </div>
                        </div>

                        <button
                            onClick={createCaravan}
                            disabled={!formData.name.trim() || isCreating}
                            className="w-full rounded bg-amber-600 py-2 font-pixel text-sm text-white transition hover:bg-amber-500 disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            {isCreating ? "Creating..." : `Create Caravan (${totalCost}g)`}
                        </button>
                    </div>
                )}

                {/* Active Caravans */}
                {active_caravans.length > 0 && (
                    <div>
                        <h2 className="mb-3 font-pixel text-lg text-amber-300">Active Caravans</h2>
                        <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                            {active_caravans.map((caravan) => renderCaravanCard(caravan))}
                        </div>
                    </div>
                )}

                {/* Arrived Caravans */}
                {arrived_caravans.length > 0 && (
                    <div>
                        <h2 className="mb-3 font-pixel text-lg text-green-300">
                            Arrived - Ready to Unload
                        </h2>
                        <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                            {arrived_caravans.map((caravan) => renderCaravanCard(caravan))}
                        </div>
                    </div>
                )}

                {/* Completed Caravans */}
                {completed_caravans.length > 0 && (
                    <div>
                        <h2 className="mb-3 font-pixel text-lg text-stone-400">Recent History</h2>
                        <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                            {completed_caravans.map((caravan) => renderCaravanCard(caravan, false))}
                        </div>
                    </div>
                )}

                {/* Empty State */}
                {active_caravans.length === 0 &&
                    arrived_caravans.length === 0 &&
                    completed_caravans.length === 0 && (
                        <div className="flex flex-1 items-center justify-center">
                            <div className="text-center">
                                <Truck className="mx-auto mb-3 h-16 w-16 text-stone-600" />
                                <p className="font-pixel text-base text-stone-500">
                                    No caravans yet
                                </p>
                                <p className="font-pixel text-xs text-stone-600">
                                    Create your first caravan to start trading!
                                </p>
                            </div>
                        </div>
                    )}

                {/* Available Routes Info */}
                {available_routes.length > 0 && (
                    <div className="mt-auto rounded-lg border border-stone-700 bg-stone-800/50 p-3">
                        <h4 className="mb-2 font-pixel text-xs text-stone-400">
                            Available Trade Routes ({available_routes.length})
                        </h4>
                        <div className="flex flex-wrap gap-2">
                            {available_routes.slice(0, 5).map((route) => (
                                <div
                                    key={route.id}
                                    className="rounded bg-stone-700/50 px-2 py-1 font-pixel text-[10px]"
                                >
                                    <span className="text-white">{route.name}</span>
                                    <span className="text-stone-400">
                                        {" "}
                                        ({route.base_travel_days} days)
                                    </span>
                                </div>
                            ))}
                            {available_routes.length > 5 && (
                                <span className="font-pixel text-[10px] text-stone-500">
                                    +{available_routes.length - 5} more
                                </span>
                            )}
                        </div>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
