import { Head, Link, router, usePage } from "@inertiajs/react";
import {
    AlertTriangle,
    ArrowLeft,
    ArrowRight,
    Box,
    Calendar,
    Check,
    Clock,
    Coins,
    Info,
    MapPin,
    Minus,
    Package,
    Plus,
    Route,
    Shield,
    Truck,
    X,
    XCircle,
} from "lucide-react";
import { useState } from "react";
import AppLayout from "@/layouts/app-layout";
import { Tooltip, TooltipContent, TooltipTrigger } from "@/components/ui/tooltip";
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

interface CaravanEvent {
    id: number;
    event_type: string;
    event_type_name: string;
    description: string;
    gold_lost: number;
    gold_gained: number;
    goods_lost: number;
    guards_lost: number;
    days_delayed: number;
    is_negative: boolean;
    is_positive: boolean;
    created_at: string;
}

interface TradeRoute {
    id: number;
    name: string;
    danger_level: string;
    base_travel_days: number;
    origin_name: string;
    destination_name: string;
}

interface AvailableRoute {
    id: number;
    name: string;
    origin: Location;
    destination: Location;
    base_travel_days: number;
    danger_level: string;
}

interface InventoryItem {
    id: number;
    name: string;
    quantity: number;
    base_price: number;
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
    goods: CaravanGoods[];
    total_goods: number;
    remaining_capacity: number;
    goods_value: number;
    events: CaravanEvent[];
    can_depart: boolean;
    is_traveling: boolean;
    has_arrived: boolean;
}

interface PageProps {
    caravan: Caravan;
    available_routes: AvailableRoute[];
    inventory: InventoryItem[];
    [key: string]: unknown;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: "Dashboard", href: "/dashboard" },
    { title: "Trade Routes", href: "/trade/routes" },
    { title: "My Caravans", href: "/trade/caravans" },
    { title: "Caravan Details", href: "#" },
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

export default function CaravanShow() {
    const { caravan, available_routes, inventory } = usePage<PageProps>().props;

    const [selectedItem, setSelectedItem] = useState<number | "">("");
    const [loadQuantity, setLoadQuantity] = useState(1);
    const [selectedRoute, setSelectedRoute] = useState<number | "">("");
    const [isLoading, setIsLoading] = useState(false);
    const [isDispatching, setIsDispatching] = useState(false);
    const [isDisbanding, setIsDisbanding] = useState(false);
    const [isRemoving, setIsRemoving] = useState<number | null>(null);
    const [isUnloading, setIsUnloading] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const [success, setSuccess] = useState<string | null>(null);
    const [showTradeRouteInfo, setShowTradeRouteInfo] = useState(false);

    const status = statusColors[caravan.status] || statusColors.preparing;
    const selectedInventoryItem = inventory.find((item) => item.id === selectedItem);
    const maxLoadQuantity = selectedInventoryItem
        ? Math.min(selectedInventoryItem.quantity, caravan.remaining_capacity)
        : 0;

    const loadGoods = async () => {
        if (!selectedItem || loadQuantity < 1) return;

        setIsLoading(true);
        setError(null);

        try {
            const response = await fetch(`/trade/caravans/${caravan.id}/load`, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN":
                        document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')
                            ?.content || "",
                },
                body: JSON.stringify({
                    item_id: selectedItem,
                    quantity: loadQuantity,
                }),
            });

            const data = await response.json();
            if (data.success) {
                setSuccess(data.message);
                setSelectedItem("");
                setLoadQuantity(1);
                router.reload();
            } else {
                setError(data.message);
            }
        } catch {
            setError("Failed to load goods");
        } finally {
            setIsLoading(false);
        }
    };

    const removeGoods = async (itemId: number, quantity: number) => {
        setIsRemoving(itemId);
        setError(null);

        try {
            const response = await fetch(`/trade/caravans/${caravan.id}/remove-goods`, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN":
                        document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')
                            ?.content || "",
                },
                body: JSON.stringify({
                    item_id: itemId,
                    quantity: quantity,
                }),
            });

            const data = await response.json();
            if (data.success) {
                setSuccess(data.message);
                router.reload();
            } else {
                setError(data.message);
            }
        } catch {
            setError("Failed to remove goods");
        } finally {
            setIsRemoving(null);
        }
    };

    const dispatchCaravan = async () => {
        if (!selectedRoute) {
            setError("Please select a destination route.");
            return;
        }

        setIsDispatching(true);
        setError(null);

        try {
            const response = await fetch(`/trade/caravans/${caravan.id}/dispatch`, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN":
                        document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')
                            ?.content || "",
                },
                body: JSON.stringify({
                    route_id: selectedRoute,
                }),
            });

            const data = await response.json();
            if (data.success) {
                setSuccess(data.message);
                router.reload();
            } else {
                setError(data.message);
            }
        } catch {
            setError("Failed to dispatch caravan");
        } finally {
            setIsDispatching(false);
        }
    };

    const disbandCaravan = async () => {
        if (
            !confirm(
                "Are you sure you want to disband this caravan? Goods and gold will be returned to you.",
            )
        ) {
            return;
        }

        setIsDisbanding(true);
        setError(null);

        try {
            const response = await fetch(`/trade/caravans/${caravan.id}/disband`, {
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
                router.visit("/trade/caravans");
            } else {
                setError(data.message);
            }
        } catch {
            setError("Failed to disband caravan");
        } finally {
            setIsDisbanding(false);
        }
    };

    const unloadGoods = async (itemId: number, quantity: number, salePrice: number) => {
        setIsUnloading(true);
        setError(null);

        try {
            const response = await fetch(`/trade/caravans/${caravan.id}/unload`, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN":
                        document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')
                            ?.content || "",
                },
                body: JSON.stringify({
                    item_id: itemId,
                    quantity: quantity,
                    sale_price: salePrice,
                }),
            });

            const data = await response.json();
            if (data.success) {
                setSuccess(data.message);
                router.reload();
            } else {
                setError(data.message);
            }
        } catch {
            setError("Failed to unload goods");
        } finally {
            setIsUnloading(false);
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Caravan - ${caravan.name}`} />
            <div className="flex h-full flex-1 flex-col gap-6 overflow-y-auto p-4">
                {/* Header */}
                <div className="flex items-start justify-between">
                    <div>
                        <div className="mb-2 flex items-center gap-2">
                            <Link
                                href="/trade/caravans"
                                className="flex items-center gap-1 font-pixel text-xs text-stone-400 transition hover:text-stone-300"
                            >
                                <ArrowLeft className="h-3 w-3" />
                                Back to Caravans
                            </Link>
                        </div>
                        <div className="flex items-center gap-3">
                            <Truck className="h-8 w-8 text-amber-400" />
                            <div>
                                <h1 className="font-pixel text-2xl text-amber-400">
                                    {caravan.name}
                                </h1>
                                <p className="font-pixel text-sm text-stone-400">
                                    {caravan.route
                                        ? `${caravan.route.origin_name} → ${caravan.route.destination_name}`
                                        : `At ${caravan.current_location.name}`}
                                </p>
                            </div>
                        </div>
                    </div>
                    <span
                        className={`rounded px-3 py-1.5 font-pixel text-xs ${status.bg} ${status.text}`}
                    >
                        {status.label}
                    </span>
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

                {/* Travel Progress (if in transit) */}
                {caravan.is_traveling && (
                    <div className="rounded-xl border-2 border-amber-500/50 bg-amber-900/20 p-4">
                        <div className="mb-3 flex items-center justify-between">
                            <h2 className="flex items-center gap-2 font-pixel text-lg text-amber-300">
                                <Route className="h-5 w-5" />
                                Travel Progress
                            </h2>
                            <span className="font-pixel text-sm text-stone-400">
                                Day {caravan.travel_progress} of {caravan.travel_total}
                            </span>
                        </div>

                        <div className="mb-3 flex items-center justify-between rounded-lg bg-stone-900/50 p-3">
                            <div className="flex items-center gap-2">
                                <MapPin className="h-4 w-4 text-green-400" />
                                <span className="font-pixel text-sm text-white">
                                    {caravan.current_location.name}
                                </span>
                            </div>
                            <ArrowRight className="h-5 w-5 text-stone-500" />
                            <div className="flex items-center gap-2">
                                <MapPin className="h-4 w-4 text-red-400" />
                                <span className="font-pixel text-sm text-white">
                                    {caravan.destination.name}
                                </span>
                            </div>
                        </div>

                        <div className="mb-2 flex justify-between font-pixel text-xs text-stone-400">
                            <span>Progress</span>
                            <span>{caravan.travel_progress_percent}%</span>
                        </div>
                        <div className="h-4 w-full overflow-hidden rounded-full bg-stone-700">
                            <div
                                className="h-full bg-amber-500 transition-all duration-300"
                                style={{ width: `${caravan.travel_progress_percent}%` }}
                            />
                        </div>
                    </div>
                )}

                {/* Arrived Notice */}
                {caravan.has_arrived && (
                    <div className="rounded-xl border-2 border-green-500/50 bg-green-900/20 p-4 text-center">
                        <Check className="mx-auto mb-2 h-12 w-12 text-green-400" />
                        <h3 className="font-pixel text-lg text-green-300">
                            Arrived at {caravan.destination.name}!
                        </h3>
                        <p className="font-pixel text-sm text-stone-400">
                            Unload your goods to complete the trade.
                        </p>
                    </div>
                )}

                <div className="grid gap-6 lg:grid-cols-2">
                    {/* Left Column - Goods & Loading */}
                    <div className="space-y-4">
                        {/* Goods Loaded */}
                        <div className="rounded-xl border-2 border-stone-600/50 bg-stone-800/30 p-4">
                            <div className="mb-4 flex items-center justify-between">
                                <h2 className="flex items-center gap-2 font-pixel text-lg text-amber-300">
                                    <Package className="h-5 w-5" />
                                    Goods Loaded
                                </h2>
                                <span className="flex items-center gap-1.5 font-pixel text-sm text-amber-400">
                                    Purchase Value: {caravan.goods_value}g
                                    <Tooltip>
                                        <TooltipTrigger asChild>
                                            <Info className="h-3.5 w-3.5 cursor-help text-stone-500" />
                                        </TooltipTrigger>
                                        <TooltipContent>
                                            <p className="max-w-52 text-xs">
                                                The total cost paid to load these goods. Sell at a
                                                higher price at your destination to earn a profit.
                                            </p>
                                        </TooltipContent>
                                    </Tooltip>
                                </span>
                            </div>

                            {caravan.goods.length > 0 ? (
                                <div className="space-y-2">
                                    {caravan.goods.map((goods) => (
                                        <div
                                            key={goods.id}
                                            className="flex items-center justify-between rounded-lg bg-stone-900/50 p-3"
                                        >
                                            <div>
                                                <div className="font-pixel text-sm text-white">
                                                    {goods.item_name}
                                                </div>
                                                <div className="font-pixel text-xs text-stone-400">
                                                    x{goods.quantity} @ {goods.purchase_price}g each
                                                </div>
                                            </div>
                                            <div className="flex items-center gap-3">
                                                <span className="font-pixel text-sm text-amber-300">
                                                    {goods.total_value}g
                                                </span>
                                                {caravan.status === "preparing" && (
                                                    <button
                                                        onClick={() =>
                                                            removeGoods(
                                                                goods.item_id,
                                                                goods.quantity,
                                                            )
                                                        }
                                                        disabled={isRemoving === goods.item_id}
                                                        className="rounded border border-red-600/50 bg-red-900/20 px-2 py-1 font-pixel text-[10px] text-red-300 transition hover:bg-red-900/40 disabled:opacity-50"
                                                    >
                                                        {isRemoving === goods.item_id
                                                            ? "..."
                                                            : "Remove"}
                                                    </button>
                                                )}
                                                {caravan.has_arrived && (
                                                    <button
                                                        onClick={() =>
                                                            unloadGoods(
                                                                goods.item_id,
                                                                goods.quantity,
                                                                goods.purchase_price,
                                                            )
                                                        }
                                                        disabled={isUnloading}
                                                        className="rounded border border-green-600/50 bg-green-900/20 px-2 py-1 font-pixel text-[10px] text-green-300 transition hover:bg-green-900/40 disabled:opacity-50"
                                                    >
                                                        {isUnloading ? "..." : "Unload"}
                                                    </button>
                                                )}
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            ) : (
                                <div className="rounded-lg bg-stone-900/50 p-4 text-center font-pixel text-sm text-stone-500">
                                    No goods loaded
                                </div>
                            )}

                            {/* Capacity Bar */}
                            <div className="mt-4">
                                <div className="mb-1 flex justify-between font-pixel text-xs text-stone-400">
                                    <span>Capacity</span>
                                    <span>
                                        {caravan.total_goods} / {caravan.capacity}
                                    </span>
                                </div>
                                <div className="h-3 w-full overflow-hidden rounded-full bg-stone-700">
                                    <div
                                        className="h-full bg-purple-500 transition-all"
                                        style={{
                                            width: `${(caravan.total_goods / caravan.capacity) * 100}%`,
                                        }}
                                    />
                                </div>
                            </div>
                        </div>

                        {/* Load More Goods (if preparing) */}
                        {caravan.status === "preparing" &&
                            inventory.length > 0 &&
                            caravan.remaining_capacity > 0 && (
                                <div className="rounded-xl border-2 border-blue-500/30 bg-blue-900/20 p-4">
                                    <h3 className="mb-4 flex items-center gap-2 font-pixel text-base text-blue-300">
                                        <Plus className="h-4 w-4" />
                                        Load More Goods
                                    </h3>

                                    <div className="mb-3">
                                        <label className="mb-1 block font-pixel text-xs text-stone-400">
                                            Select Item
                                        </label>
                                        <select
                                            value={selectedItem}
                                            onChange={(e) => {
                                                setSelectedItem(
                                                    e.target.value ? parseInt(e.target.value) : "",
                                                );
                                                setLoadQuantity(1);
                                            }}
                                            className="w-full rounded border border-stone-600 bg-stone-800 px-3 py-2 font-pixel text-sm text-white focus:border-blue-500 focus:outline-none"
                                        >
                                            <option value="">-- Select Item --</option>
                                            {inventory.map((item) => (
                                                <option key={item.id} value={item.id}>
                                                    {item.name} (x{item.quantity} available)
                                                </option>
                                            ))}
                                        </select>
                                    </div>

                                    {selectedItem && (
                                        <>
                                            <div className="mb-3">
                                                <label className="mb-1 block font-pixel text-xs text-stone-400">
                                                    Quantity (max: {maxLoadQuantity})
                                                </label>
                                                <div className="flex items-center gap-2">
                                                    <button
                                                        onClick={() =>
                                                            setLoadQuantity(
                                                                Math.max(1, loadQuantity - 1),
                                                            )
                                                        }
                                                        className="rounded border border-stone-600 bg-stone-700 p-2 text-white transition hover:bg-stone-600"
                                                    >
                                                        <Minus className="h-4 w-4" />
                                                    </button>
                                                    <input
                                                        type="number"
                                                        value={loadQuantity}
                                                        onChange={(e) =>
                                                            setLoadQuantity(
                                                                Math.max(
                                                                    1,
                                                                    Math.min(
                                                                        maxLoadQuantity,
                                                                        parseInt(e.target.value) ||
                                                                            1,
                                                                    ),
                                                                ),
                                                            )
                                                        }
                                                        min={1}
                                                        max={maxLoadQuantity}
                                                        className="w-20 rounded border border-stone-600 bg-stone-800 px-3 py-2 text-center font-pixel text-sm text-white focus:border-blue-500 focus:outline-none"
                                                    />
                                                    <button
                                                        onClick={() =>
                                                            setLoadQuantity(
                                                                Math.min(
                                                                    maxLoadQuantity,
                                                                    loadQuantity + 1,
                                                                ),
                                                            )
                                                        }
                                                        className="rounded border border-stone-600 bg-stone-700 p-2 text-white transition hover:bg-stone-600"
                                                    >
                                                        <Plus className="h-4 w-4" />
                                                    </button>
                                                    <button
                                                        onClick={() =>
                                                            setLoadQuantity(maxLoadQuantity)
                                                        }
                                                        className="ml-2 rounded border border-stone-600 bg-stone-700 px-2 py-2 font-pixel text-xs text-stone-300 transition hover:bg-stone-600"
                                                    >
                                                        Max
                                                    </button>
                                                </div>
                                            </div>

                                            <button
                                                onClick={loadGoods}
                                                disabled={isLoading || loadQuantity < 1}
                                                className="w-full rounded bg-blue-600 py-2 font-pixel text-sm text-white transition hover:bg-blue-500 disabled:cursor-not-allowed disabled:opacity-50"
                                            >
                                                {isLoading
                                                    ? "Loading..."
                                                    : `Add ${loadQuantity} to Caravan`}
                                            </button>
                                        </>
                                    )}
                                </div>
                            )}

                        {/* No inventory message */}
                        {caravan.status === "preparing" && inventory.length === 0 && (
                            <div className="rounded-xl border-2 border-stone-600/30 bg-stone-800/20 p-4 text-center">
                                <Box className="mx-auto mb-2 h-8 w-8 text-stone-500" />
                                <p className="font-pixel text-sm text-stone-500">
                                    No tradeable items in inventory
                                </p>
                            </div>
                        )}
                    </div>

                    {/* Right Column - Stats, Actions, Events */}
                    <div className="space-y-4">
                        {/* Caravan Stats */}
                        <div className="rounded-xl border-2 border-stone-600/50 bg-stone-800/30 p-4">
                            <h2 className="mb-4 font-pixel text-lg text-stone-300">
                                Caravan Stats
                            </h2>
                            <div className="grid grid-cols-2 gap-3">
                                <div className="rounded-lg bg-stone-900/50 p-3">
                                    <div className="flex items-center gap-2">
                                        <Shield className="h-4 w-4 text-blue-400" />
                                        <span className="font-pixel text-xs text-stone-400">
                                            Guards
                                        </span>
                                    </div>
                                    <div className="mt-1 font-pixel text-xl text-white">
                                        {caravan.guards}
                                    </div>
                                </div>
                                <div className="rounded-lg bg-stone-900/50 p-3">
                                    <div className="flex items-center gap-2">
                                        <Coins className="h-4 w-4 text-yellow-400" />
                                        <span className="font-pixel text-xs text-stone-400">
                                            Gold Carried
                                        </span>
                                    </div>
                                    <div className="mt-1 font-pixel text-xl text-yellow-300">
                                        {caravan.gold_carried}
                                    </div>
                                </div>
                                <div className="rounded-lg bg-stone-900/50 p-3">
                                    <div className="flex items-center gap-2">
                                        <Box className="h-4 w-4 text-purple-400" />
                                        <span className="font-pixel text-xs text-stone-400">
                                            Capacity
                                        </span>
                                    </div>
                                    <div className="mt-1 font-pixel text-xl text-white">
                                        {caravan.total_goods}/{caravan.capacity}
                                    </div>
                                </div>
                                <div className="rounded-lg bg-stone-900/50 p-3">
                                    <div className="flex items-center gap-2">
                                        <MapPin className="h-4 w-4 text-green-400" />
                                        <span className="font-pixel text-xs text-stone-400">
                                            Location
                                        </span>
                                    </div>
                                    <div className="mt-1 truncate font-pixel text-sm text-white">
                                        {caravan.current_location.name}
                                    </div>
                                </div>
                            </div>

                            {caravan.route && (
                                <div className="mt-4 rounded-lg bg-stone-900/30 p-3">
                                    <div className="flex items-center gap-2 font-pixel text-xs text-stone-400">
                                        <Route className="h-4 w-4" />
                                        <span>Route: {caravan.route.name}</span>
                                        <span
                                            className={`capitalize ${dangerColors[caravan.route.danger_level] || "text-stone-400"}`}
                                        >
                                            ({caravan.route.danger_level})
                                        </span>
                                    </div>
                                </div>
                            )}
                        </div>

                        {/* Dispatch Caravan (if preparing and has goods) */}
                        {caravan.status === "preparing" && (
                            <div className="rounded-xl border-2 border-stone-600/50 bg-stone-800/30 p-4">
                                <h2 className="mb-4 font-pixel text-lg text-stone-300">
                                    Dispatch Caravan
                                </h2>

                                {available_routes.length > 0 ? (
                                    <>
                                        <div className="mb-4">
                                            <label className="mb-1 block font-pixel text-xs text-stone-400">
                                                Select Destination Route
                                            </label>
                                            <select
                                                value={selectedRoute}
                                                onChange={(e) =>
                                                    setSelectedRoute(
                                                        e.target.value
                                                            ? parseInt(e.target.value)
                                                            : "",
                                                    )
                                                }
                                                className="w-full rounded border border-stone-600 bg-stone-800 px-3 py-2 font-pixel text-sm text-white focus:border-amber-500 focus:outline-none"
                                            >
                                                <option value="">-- Select Route --</option>
                                                {available_routes.map((route) => (
                                                    <option key={route.id} value={route.id}>
                                                        {route.destination.name} (
                                                        {route.base_travel_days} days,{" "}
                                                        {route.danger_level})
                                                    </option>
                                                ))}
                                            </select>
                                        </div>

                                        {selectedRoute && (
                                            <div className="mb-4 rounded-lg bg-stone-900/50 p-3">
                                                {(() => {
                                                    const route = available_routes.find(
                                                        (r) => r.id === selectedRoute,
                                                    );
                                                    if (!route) return null;
                                                    return (
                                                        <div className="space-y-1 font-pixel text-xs">
                                                            <div className="flex justify-between">
                                                                <span className="text-stone-400">
                                                                    Destination:
                                                                </span>
                                                                <span className="text-white">
                                                                    {route.destination.name}
                                                                </span>
                                                            </div>
                                                            <div className="flex justify-between">
                                                                <span className="text-stone-400">
                                                                    Travel Time:
                                                                </span>
                                                                <span className="text-white">
                                                                    {route.base_travel_days} days
                                                                </span>
                                                            </div>
                                                            <div className="flex justify-between">
                                                                <span className="text-stone-400">
                                                                    Danger:
                                                                </span>
                                                                <span
                                                                    className={`capitalize ${dangerColors[route.danger_level] || "text-stone-400"}`}
                                                                >
                                                                    {route.danger_level}
                                                                </span>
                                                            </div>
                                                        </div>
                                                    );
                                                })()}
                                            </div>
                                        )}

                                        <button
                                            onClick={dispatchCaravan}
                                            disabled={
                                                !caravan.can_depart ||
                                                !selectedRoute ||
                                                isDispatching
                                            }
                                            className="flex w-full items-center justify-center gap-2 rounded bg-amber-600 py-3 font-pixel text-sm text-white transition hover:bg-amber-500 disabled:cursor-not-allowed disabled:opacity-50"
                                        >
                                            <ArrowRight className="h-5 w-5" />
                                            {isDispatching ? "Dispatching..." : "Dispatch Caravan"}
                                        </button>

                                        {!caravan.can_depart && caravan.total_goods === 0 && (
                                            <p className="mt-2 font-pixel text-[10px] text-stone-500">
                                                <AlertTriangle className="mr-1 inline h-3 w-3" />
                                                Load goods before dispatching.
                                            </p>
                                        )}
                                    </>
                                ) : (
                                    <div className="rounded-lg bg-stone-900/50 p-4 text-center">
                                        <Route className="mx-auto mb-2 h-8 w-8 text-stone-500" />
                                        <p className="font-pixel text-sm text-stone-500">
                                            No trade routes available from this location.
                                        </p>
                                        <button
                                            onClick={() => setShowTradeRouteInfo(true)}
                                            className="mt-2 inline-flex items-center gap-1 font-pixel text-xs text-amber-400 transition hover:text-amber-300"
                                        >
                                            <Info className="h-3 w-3" />
                                            How are trade routes created?
                                        </button>
                                    </div>
                                )}
                            </div>
                        )}

                        {/* Cancel/Disband Button */}
                        {(caravan.status === "preparing" || caravan.status === "arrived") && (
                            <button
                                onClick={disbandCaravan}
                                disabled={isDisbanding}
                                className="flex w-full items-center justify-center gap-2 rounded border-2 border-red-600/50 bg-red-900/20 px-4 py-2 font-pixel text-sm text-red-300 transition hover:bg-red-900/40 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                <XCircle className="h-4 w-4" />
                                {isDisbanding ? "Disbanding..." : "Disband Caravan"}
                            </button>
                        )}

                        {/* Cannot disband while traveling */}
                        {caravan.is_traveling && (
                            <div className="rounded-lg border border-stone-600/50 bg-stone-800/30 p-3 text-center font-pixel text-xs text-stone-500">
                                <AlertTriangle className="mr-1 inline h-3 w-3" />
                                Cannot disband while traveling. Wait for arrival.
                            </div>
                        )}

                        {/* Event Log */}
                        <div className="rounded-xl border-2 border-stone-600/50 bg-stone-800/30 p-4">
                            <h2 className="mb-4 flex items-center gap-2 font-pixel text-lg text-stone-300">
                                <Calendar className="h-5 w-5" />
                                Event Log
                            </h2>

                            {caravan.events.length > 0 ? (
                                <div className="max-h-64 space-y-2 overflow-y-auto">
                                    {caravan.events.map((event) => (
                                        <div
                                            key={event.id}
                                            className={`rounded-lg p-3 ${
                                                event.is_negative
                                                    ? "bg-red-900/20"
                                                    : event.is_positive
                                                      ? "bg-green-900/20"
                                                      : "bg-stone-900/50"
                                            }`}
                                        >
                                            <div className="mb-1 flex items-center justify-between">
                                                <span
                                                    className={`font-pixel text-xs ${
                                                        event.is_negative
                                                            ? "text-red-400"
                                                            : event.is_positive
                                                              ? "text-green-400"
                                                              : "text-stone-400"
                                                    }`}
                                                >
                                                    {event.event_type_name}
                                                </span>
                                                <span className="flex items-center gap-1 font-pixel text-[10px] text-stone-500">
                                                    <Clock className="h-3 w-3" />
                                                    {new Date(
                                                        event.created_at,
                                                    ).toLocaleDateString()}
                                                </span>
                                            </div>
                                            <p className="font-pixel text-xs text-stone-300">
                                                {event.description}
                                            </p>
                                            {(event.gold_lost > 0 ||
                                                event.goods_lost > 0 ||
                                                event.guards_lost > 0 ||
                                                event.days_delayed > 0) && (
                                                <div className="mt-1 flex flex-wrap gap-2 font-pixel text-[10px] text-stone-500">
                                                    {event.gold_lost > 0 && (
                                                        <span className="text-red-400">
                                                            -{event.gold_lost}g
                                                        </span>
                                                    )}
                                                    {event.gold_gained > 0 && (
                                                        <span className="text-green-400">
                                                            +{event.gold_gained}g
                                                        </span>
                                                    )}
                                                    {event.goods_lost > 0 && (
                                                        <span className="text-orange-400">
                                                            -{event.goods_lost} goods
                                                        </span>
                                                    )}
                                                    {event.guards_lost > 0 && (
                                                        <span className="text-blue-400">
                                                            -{event.guards_lost} guards
                                                        </span>
                                                    )}
                                                    {event.days_delayed > 0 && (
                                                        <span className="text-purple-400">
                                                            +{event.days_delayed} days delayed
                                                        </span>
                                                    )}
                                                </div>
                                            )}
                                        </div>
                                    ))}
                                </div>
                            ) : (
                                <div className="rounded-lg bg-stone-900/50 p-4 text-center font-pixel text-sm text-stone-500">
                                    No events recorded yet.
                                </div>
                            )}
                        </div>
                    </div>
                </div>

                {/* Trade Route Info Modal */}
                {showTradeRouteInfo && (
                    <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
                        <div
                            className="absolute inset-0 bg-black/70 backdrop-blur-sm"
                            onClick={() => setShowTradeRouteInfo(false)}
                        />
                        <div className="relative w-full max-w-lg rounded-xl border-2 border-amber-500/50 bg-stone-900 p-6 shadow-xl">
                            <button
                                onClick={() => setShowTradeRouteInfo(false)}
                                className="absolute right-3 top-3 rounded p-1 text-stone-400 transition hover:bg-stone-800 hover:text-stone-200"
                            >
                                <X className="h-5 w-5" />
                            </button>

                            <div className="mb-4 flex items-center gap-3">
                                <div className="rounded-lg bg-amber-900/30 p-2">
                                    <Route className="h-6 w-6 text-amber-400" />
                                </div>
                                <h3 className="font-pixel text-lg text-amber-300">
                                    Creating Trade Routes
                                </h3>
                            </div>

                            <div className="space-y-4 font-pixel text-sm text-stone-300">
                                <p>
                                    Trade routes connect settlements and allow caravans to travel
                                    safely between them.
                                </p>

                                <div className="rounded-lg bg-stone-800/50 p-3">
                                    <h4 className="mb-2 text-amber-400">
                                        Who can create trade routes?
                                    </h4>
                                    <p className="text-stone-400">
                                        Only <span className="text-amber-300">Baronies</span> and{" "}
                                        <span className="text-amber-300">Kingdoms</span> have the
                                        authority to establish official trade routes between
                                        settlements within their territory.
                                    </p>
                                </div>

                                <div className="rounded-lg bg-stone-800/50 p-3">
                                    <h4 className="mb-2 text-amber-400">
                                        How to request a trade route
                                    </h4>
                                    <ul className="list-inside list-disc space-y-1 text-stone-400">
                                        <li>Petition your local Baron or King</li>
                                        <li>Routes require settlements at both ends</li>
                                        <li>Longer routes are more dangerous</li>
                                    </ul>
                                </div>

                                <p className="text-xs text-stone-500">
                                    Once a trade route is established from this location, it will
                                    appear in the dispatch menu above.
                                </p>
                            </div>

                            <button
                                onClick={() => setShowTradeRouteInfo(false)}
                                className="mt-4 w-full rounded bg-amber-600 py-2 font-pixel text-sm text-white transition hover:bg-amber-500"
                            >
                                Got it
                            </button>
                        </div>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
