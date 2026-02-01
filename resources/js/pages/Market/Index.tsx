import { Head, router, usePage } from "@inertiajs/react";
import {
    ArrowDown,
    ArrowUp,
    Beer,
    Beef,
    Castle,
    Church,
    Coins,
    Fish,
    Home,
    Loader2,
    Package,
    Pickaxe,
    Search,
    ShoppingCart,
    Store,
    TreeDeciduous,
    TrendingDown,
    TrendingUp,
    Wheat,
    Wrench,
    type LucideIcon,
} from "lucide-react";
import { useState } from "react";
import AppLayout from "@/layouts/app-layout";
import type { BreadcrumbItem } from "@/types";

interface MarketInfo {
    location_type: string;
    location_id: number;
    location_name: string;
    gold_on_hand: number;
}

interface MarketItem {
    item_id: number;
    item_name: string;
    item_type: string;
    item_description: string;
    base_price: number;
    buy_price: number;
    sell_price: number;
    current_price: number;
    supply_quantity: number;
    demand_level: number;
    seasonal_modifier: number;
    supply_modifier: number;
}

interface SellableItem {
    inventory_ids: number[];
    item_id: number;
    item_name: string;
    item_type: string;
    quantity: number;
    sell_price: number;
}

interface Transaction {
    id: number;
    type: "buy" | "sell";
    item_name: string;
    quantity: number;
    price_per_unit: number;
    total_gold: number;
    created_at: string;
    formatted_date: string;
}

interface PageProps {
    market_info: MarketInfo;
    market_prices: MarketItem[];
    sellable_items: SellableItem[];
    recent_transactions: Transaction[];
    [key: string]: unknown;
}

const locationPaths: Record<string, string> = {
    village: "villages",
    barony: "baronies",
    town: "towns",
    duchy: "duchies",
    kingdom: "kingdoms",
};

const locationIcons: Record<string, typeof Home> = {
    village: Home,
    barony: Castle,
    town: Church,
};

function formatGold(amount: number): string {
    return amount.toLocaleString();
}

function getPriceIndicator(modifier: number) {
    if (modifier > 1.1) {
        return {
            icon: TrendingUp,
            color: "text-red-400",
            label: "High",
            tooltip: "Price is high due to low supply or season",
        };
    } else if (modifier < 0.9) {
        return {
            icon: TrendingDown,
            color: "text-green-400",
            label: "Low",
            tooltip: "Price is low due to high supply or season",
        };
    }
    return null;
}

function getItemTypeColor(type: string): string {
    switch (type) {
        case "resource":
            return "text-blue-400";
        case "consumable":
            return "text-green-400";
        case "tool":
            return "text-orange-400";
        default:
            return "text-stone-400";
    }
}

// Role suggestions for when market is empty
const roleStockSuggestions: { role: string; icon: LucideIcon; items: string; color: string }[] = [
    {
        role: "Miner",
        icon: Pickaxe,
        items: "Ores (Copper, Iron, Coal, Gold)",
        color: "text-slate-400",
    },
    { role: "Blacksmith", icon: Wrench, items: "Bars, Tools, Weapons", color: "text-orange-400" },
    { role: "Fisherman", icon: Fish, items: "Raw Fish, Fishing Gear", color: "text-blue-400" },
    { role: "Baker", icon: Wheat, items: "Flour, Bread, Meat Pies", color: "text-amber-400" },
    { role: "Forester", icon: TreeDeciduous, items: "Wood, Oak, Willow", color: "text-green-400" },
    { role: "Innkeeper", icon: Beer, items: "Cooked Food", color: "text-yellow-400" },
    { role: "Hunter/Butcher", icon: Beef, items: "Raw Meat, Leather", color: "text-red-400" },
];

export default function MarketIndex() {
    const { market_info, market_prices, sellable_items, recent_transactions } =
        usePage<PageProps>().props;
    const [activeTab, setActiveTab] = useState<"buy" | "sell">("buy");
    const [selectedItem, setSelectedItem] = useState<MarketItem | SellableItem | null>(null);
    const [quantity, setQuantity] = useState("1");
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const [success, setSuccess] = useState<string | null>(null);
    const [filterType, setFilterType] = useState<string>("all");
    const [searchQuery, setSearchQuery] = useState("");

    const LocationIcon = locationIcons[market_info.location_type] || Home;

    const breadcrumbs: BreadcrumbItem[] = [
        { title: "Dashboard", href: "/dashboard" },
        {
            title: market_info.location_name,
            href: `/${locationPaths[market_info.location_type] || market_info.location_type + "s"}/${market_info.location_id}`,
        },
        { title: "Market", href: "#" },
    ];

    const filteredBuyItems = market_prices.filter((item) => {
        const matchesType = filterType === "all" || item.item_type === filterType;
        const matchesSearch =
            !searchQuery || item.item_name.toLowerCase().includes(searchQuery.toLowerCase());
        return matchesType && matchesSearch;
    });

    const filteredSellItems = sellable_items.filter((item) => {
        return !searchQuery || item.item_name.toLowerCase().includes(searchQuery.toLowerCase());
    });

    const handleBuy = async () => {
        if (!selectedItem || !("buy_price" in selectedItem)) return;

        const qty = parseInt(quantity, 10);
        if (isNaN(qty) || qty <= 0) {
            setError("Enter a valid quantity");
            return;
        }

        setLoading(true);
        setError(null);
        setSuccess(null);

        try {
            const response = await fetch("/market/buy", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN":
                        document
                            .querySelector('meta[name="csrf-token"]')
                            ?.getAttribute("content") || "",
                },
                body: JSON.stringify({
                    item_id: selectedItem.item_id,
                    quantity: qty,
                }),
            });

            const data = await response.json();

            if (data.success) {
                setSuccess(data.message);
                setQuantity("1");
                setSelectedItem(null);
                router.reload({
                    only: [
                        "market_info",
                        "market_prices",
                        "sellable_items",
                        "recent_transactions",
                        "sidebar",
                    ],
                });
            } else {
                setError(data.message);
            }
        } catch {
            setError("An error occurred");
        } finally {
            setLoading(false);
        }
    };

    const handleSell = async () => {
        if (!selectedItem || !("inventory_ids" in selectedItem)) return;

        const qty = parseInt(quantity, 10);
        if (isNaN(qty) || qty <= 0) {
            setError("Enter a valid quantity");
            return;
        }

        if (qty > selectedItem.quantity) {
            setError(`You only have ${selectedItem.quantity} of this item`);
            return;
        }

        setLoading(true);
        setError(null);
        setSuccess(null);

        try {
            const response = await fetch("/market/sell", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN":
                        document
                            .querySelector('meta[name="csrf-token"]')
                            ?.getAttribute("content") || "",
                },
                body: JSON.stringify({
                    item_id: selectedItem.item_id,
                    quantity: qty,
                }),
            });

            const data = await response.json();

            if (data.success) {
                setSuccess(data.message);
                setQuantity("1");
                setSelectedItem(null);
                router.reload({
                    only: [
                        "market_info",
                        "market_prices",
                        "sellable_items",
                        "recent_transactions",
                        "sidebar",
                    ],
                });
            } else {
                setError(data.message);
            }
        } catch {
            setError("An error occurred");
        } finally {
            setLoading(false);
        }
    };

    const calculateTotal = () => {
        const qty = parseInt(quantity, 10) || 0;
        if (!selectedItem) return 0;

        if (activeTab === "buy" && "buy_price" in selectedItem) {
            return selectedItem.buy_price * qty;
        } else if (activeTab === "sell" && "sell_price" in selectedItem) {
            return selectedItem.sell_price * qty;
        }
        return 0;
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Market - ${market_info.location_name}`} />
            <div className="flex h-full flex-1 flex-col p-4">
                {/* Header */}
                <div className="mb-6 flex items-center justify-between">
                    <div className="flex items-center gap-3">
                        <div className="rounded-lg bg-emerald-900/30 p-3">
                            <Store className="h-8 w-8 text-emerald-400" />
                        </div>
                        <div>
                            <h1 className="font-pixel text-2xl text-emerald-400">Market</h1>
                            <div className="flex items-center gap-1 text-stone-400">
                                <LocationIcon className="h-3 w-3" />
                                <span className="font-pixel text-xs">
                                    {market_info.location_name}
                                </span>
                            </div>
                        </div>
                    </div>
                    <div className="flex items-center gap-2 rounded-lg border border-stone-600 bg-stone-800 px-4 py-2">
                        <Coins className="h-4 w-4 text-yellow-400" />
                        <span className="font-pixel text-sm text-yellow-400">
                            {formatGold(market_info.gold_on_hand)}
                        </span>
                    </div>
                </div>

                <div className="grid gap-6 lg:grid-cols-3">
                    {/* Left Column - Items List */}
                    <div className="lg:col-span-2 space-y-4">
                        {/* Tabs */}
                        <div className="flex gap-2">
                            <button
                                onClick={() => {
                                    setActiveTab("buy");
                                    setSelectedItem(null);
                                    setQuantity("1");
                                }}
                                className={`flex items-center gap-2 rounded-lg border-2 px-4 py-2 font-pixel text-sm transition ${
                                    activeTab === "buy"
                                        ? "border-emerald-600 bg-emerald-900/30 text-emerald-300"
                                        : "border-stone-600 bg-stone-800 text-stone-400 hover:bg-stone-700"
                                }`}
                            >
                                <ShoppingCart className="h-4 w-4" />
                                Buy
                            </button>
                            <button
                                onClick={() => {
                                    setActiveTab("sell");
                                    setSelectedItem(null);
                                    setQuantity("1");
                                }}
                                className={`flex items-center gap-2 rounded-lg border-2 px-4 py-2 font-pixel text-sm transition ${
                                    activeTab === "sell"
                                        ? "border-amber-600 bg-amber-900/30 text-amber-300"
                                        : "border-stone-600 bg-stone-800 text-stone-400 hover:bg-stone-700"
                                }`}
                            >
                                <Package className="h-4 w-4" />
                                Sell
                            </button>
                        </div>

                        {/* Search */}
                        <div className="relative">
                            <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-stone-500" />
                            <input
                                type="text"
                                placeholder="Search items..."
                                value={searchQuery}
                                onChange={(e) => setSearchQuery(e.target.value)}
                                className="w-full rounded-lg border-2 border-stone-600 bg-stone-900 py-2 pl-10 pr-4 font-pixel text-sm text-stone-200 placeholder-stone-500 focus:border-emerald-600 focus:outline-none"
                            />
                        </div>

                        {/* Filter (Buy tab only) */}
                        {activeTab === "buy" && (
                            <div className="flex gap-2 flex-wrap">
                                {["all", "resource", "consumable", "tool", "misc"].map((type) => (
                                    <button
                                        key={type}
                                        onClick={() => setFilterType(type)}
                                        className={`rounded-lg border px-3 py-1 font-pixel text-xs transition ${
                                            filterType === type
                                                ? "border-emerald-600 bg-emerald-900/30 text-emerald-300"
                                                : "border-stone-600 bg-stone-800 text-stone-400 hover:bg-stone-700"
                                        }`}
                                    >
                                        {type === "all"
                                            ? "All"
                                            : type.charAt(0).toUpperCase() + type.slice(1)}
                                    </button>
                                ))}
                            </div>
                        )}

                        {/* Items List */}
                        <div className="rounded-xl border-2 border-stone-700 bg-stone-800/50">
                            {activeTab === "buy" ? (
                                filteredBuyItems.length > 0 ? (
                                    <div className="divide-y divide-stone-700">
                                        {filteredBuyItems.map((item) => {
                                            const priceIndicator = getPriceIndicator(
                                                item.seasonal_modifier * item.supply_modifier,
                                            );
                                            const isSelected =
                                                selectedItem &&
                                                "buy_price" in selectedItem &&
                                                selectedItem.item_id === item.item_id;

                                            return (
                                                <button
                                                    key={item.item_id}
                                                    onClick={() => {
                                                        setSelectedItem(item);
                                                        setQuantity("1");
                                                        setError(null);
                                                        setSuccess(null);
                                                    }}
                                                    className={`flex w-full items-center gap-3 p-3 text-left transition ${
                                                        isSelected
                                                            ? "bg-emerald-900/20"
                                                            : "hover:bg-stone-700/50"
                                                    }`}
                                                >
                                                    <span className="font-pixel text-xs text-stone-500 w-10">
                                                        x{item.supply_quantity}
                                                    </span>
                                                    <div className="flex-1">
                                                        <div className="flex items-center gap-2">
                                                            <span className="font-pixel text-sm text-stone-200">
                                                                {item.item_name}
                                                            </span>
                                                            <span
                                                                className={`font-pixel text-[10px] ${getItemTypeColor(item.item_type)}`}
                                                            >
                                                                {item.item_type}
                                                            </span>
                                                            {priceIndicator && (
                                                                <span
                                                                    title={priceIndicator.tooltip}
                                                                    className="cursor-help"
                                                                >
                                                                    <priceIndicator.icon
                                                                        className={`h-3 w-3 ${priceIndicator.color}`}
                                                                    />
                                                                </span>
                                                            )}
                                                        </div>
                                                    </div>
                                                    <div className="font-pixel text-sm text-yellow-400">
                                                        {formatGold(item.buy_price)}g
                                                    </div>
                                                </button>
                                            );
                                        })}
                                    </div>
                                ) : (
                                    <div className="p-4">
                                        <div className="mb-4 text-center">
                                            <Store className="mx-auto mb-2 h-8 w-8 text-stone-600" />
                                            <p className="font-pixel text-sm text-stone-400">
                                                No items available
                                            </p>
                                            <p className="font-pixel text-xs text-stone-500">
                                                The market needs role holders to stock goods
                                            </p>
                                        </div>
                                        <div className="space-y-2">
                                            <p className="font-pixel text-xs text-stone-500 mb-2">
                                                Roles that can stock:
                                            </p>
                                            {roleStockSuggestions.map((suggestion) => (
                                                <div
                                                    key={suggestion.role}
                                                    className="flex items-center gap-3 rounded-lg bg-stone-900/50 px-3 py-2"
                                                >
                                                    <suggestion.icon
                                                        className={`h-4 w-4 ${suggestion.color}`}
                                                    />
                                                    <div className="flex-1">
                                                        <span
                                                            className={`font-pixel text-xs ${suggestion.color}`}
                                                        >
                                                            {suggestion.role}
                                                        </span>
                                                        <span className="font-pixel text-xs text-stone-500 ml-2">
                                                            - {suggestion.items}
                                                        </span>
                                                    </div>
                                                </div>
                                            ))}
                                        </div>
                                    </div>
                                )
                            ) : filteredSellItems.length > 0 ? (
                                <div className="divide-y divide-stone-700">
                                    {filteredSellItems.map((item) => {
                                        const isSelected =
                                            selectedItem &&
                                            "inventory_ids" in selectedItem &&
                                            selectedItem.item_id === item.item_id;

                                        return (
                                            <button
                                                key={item.item_id}
                                                onClick={() => {
                                                    setSelectedItem(item);
                                                    setQuantity("1");
                                                    setError(null);
                                                    setSuccess(null);
                                                }}
                                                className={`flex w-full items-center gap-3 p-3 text-left transition ${
                                                    isSelected
                                                        ? "bg-amber-900/20"
                                                        : "hover:bg-stone-700/50"
                                                }`}
                                            >
                                                <span className="font-pixel text-xs text-stone-500 w-10">
                                                    x{item.quantity}
                                                </span>
                                                <div className="flex-1">
                                                    <div className="flex items-center gap-2">
                                                        <span className="font-pixel text-sm text-stone-200">
                                                            {item.item_name}
                                                        </span>
                                                        <span
                                                            className={`font-pixel text-[10px] ${getItemTypeColor(item.item_type)}`}
                                                        >
                                                            {item.item_type}
                                                        </span>
                                                    </div>
                                                </div>
                                                <div className="font-pixel text-sm text-yellow-400">
                                                    {formatGold(item.sell_price)}g
                                                </div>
                                            </button>
                                        );
                                    })}
                                </div>
                            ) : (
                                <div className="py-12 text-center">
                                    <Package className="mx-auto mb-2 h-8 w-8 text-stone-600" />
                                    <p className="font-pixel text-xs text-stone-500">
                                        No items to sell
                                    </p>
                                </div>
                            )}
                        </div>
                    </div>

                    {/* Right Column - Transaction Panel & History */}
                    <div className="space-y-4">
                        {/* Transaction Panel */}
                        <div className="rounded-xl border-2 border-stone-700 bg-stone-800/50 p-4">
                            <h2 className="mb-4 font-pixel text-sm text-stone-300">
                                {activeTab === "buy" ? "Purchase" : "Sell"}
                            </h2>

                            {/* Messages */}
                            {error && (
                                <div className="mb-4 rounded-lg border border-red-600/50 bg-red-900/20 px-4 py-2 font-pixel text-xs text-red-400">
                                    {error}
                                </div>
                            )}
                            {success && (
                                <div className="mb-4 rounded-lg border border-green-600/50 bg-green-900/20 px-4 py-2 font-pixel text-xs text-green-400">
                                    {success}
                                </div>
                            )}

                            {selectedItem ? (
                                <>
                                    {/* Selected Item Info */}
                                    <div className="mb-4 rounded-lg bg-stone-900/50 p-3">
                                        <div className="font-pixel text-sm text-stone-200">
                                            {selectedItem.item_name}
                                        </div>
                                        <div className="mt-1 font-pixel text-[10px] text-stone-500">
                                            {"buy_price" in selectedItem
                                                ? `Buy: ${formatGold(selectedItem.buy_price)}g each`
                                                : `Sell: ${formatGold(selectedItem.sell_price)}g each`}
                                        </div>
                                        {"quantity" in selectedItem && (
                                            <div className="mt-1 font-pixel text-[10px] text-stone-500">
                                                You have: {selectedItem.quantity}
                                            </div>
                                        )}
                                    </div>

                                    {/* Quantity Input */}
                                    <div className="mb-4">
                                        <label className="mb-2 block font-pixel text-xs text-stone-400">
                                            Quantity
                                        </label>
                                        <input
                                            type="number"
                                            value={quantity}
                                            onChange={(e) => setQuantity(e.target.value)}
                                            className="w-full rounded-lg border-2 border-stone-600 bg-stone-900 px-4 py-2 font-pixel text-lg text-amber-300 placeholder-stone-600 focus:border-amber-500 focus:outline-none"
                                            min="1"
                                            max={
                                                "quantity" in selectedItem
                                                    ? selectedItem.quantity
                                                    : undefined
                                            }
                                        />
                                    </div>

                                    {/* Quick Quantity Buttons */}
                                    {"quantity" in selectedItem && (
                                        <div className="mb-4 flex gap-2">
                                            {[1, 5, 10].map((amt) => (
                                                <button
                                                    key={amt}
                                                    onClick={() =>
                                                        setQuantity(
                                                            Math.min(
                                                                amt,
                                                                selectedItem.quantity,
                                                            ).toString(),
                                                        )
                                                    }
                                                    className="flex-1 rounded-lg border border-stone-600 bg-stone-700 px-2 py-1 font-pixel text-[10px] text-stone-300 transition hover:bg-stone-600"
                                                >
                                                    {amt}
                                                </button>
                                            ))}
                                            <button
                                                onClick={() =>
                                                    setQuantity(selectedItem.quantity.toString())
                                                }
                                                className="flex-1 rounded-lg border border-stone-600 bg-stone-700 px-2 py-1 font-pixel text-[10px] text-stone-300 transition hover:bg-stone-600"
                                            >
                                                All
                                            </button>
                                        </div>
                                    )}

                                    {/* Total */}
                                    <div className="mb-4 flex items-center justify-between rounded-lg bg-stone-900/50 p-3">
                                        <span className="font-pixel text-xs text-stone-400">
                                            Total
                                        </span>
                                        <span className="font-pixel text-lg text-yellow-400">
                                            {formatGold(calculateTotal())}g
                                        </span>
                                    </div>

                                    {/* Action Button */}
                                    <button
                                        onClick={activeTab === "buy" ? handleBuy : handleSell}
                                        disabled={loading}
                                        className={`flex w-full items-center justify-center gap-2 rounded-lg border-2 px-4 py-3 font-pixel text-sm transition disabled:opacity-50 ${
                                            activeTab === "buy"
                                                ? "border-emerald-600 bg-emerald-900/30 text-emerald-300 hover:bg-emerald-800/50"
                                                : "border-amber-600 bg-amber-900/30 text-amber-300 hover:bg-amber-800/50"
                                        }`}
                                    >
                                        {loading ? (
                                            <Loader2 className="h-4 w-4 animate-spin" />
                                        ) : activeTab === "buy" ? (
                                            <ArrowDown className="h-4 w-4" />
                                        ) : (
                                            <ArrowUp className="h-4 w-4" />
                                        )}
                                        {activeTab === "buy" ? "Buy" : "Sell"}
                                    </button>
                                </>
                            ) : (
                                <div className="py-8 text-center">
                                    <p className="font-pixel text-xs text-stone-500">
                                        Select an item to{" "}
                                        {activeTab === "buy" ? "purchase" : "sell"}
                                    </p>
                                </div>
                            )}
                        </div>

                        {/* Recent Transactions */}
                        <div className="rounded-xl border-2 border-stone-700 bg-stone-800/50 p-4">
                            <h2 className="mb-4 font-pixel text-sm text-stone-300">
                                Recent Trades
                            </h2>
                            {recent_transactions.length > 0 ? (
                                <div className="space-y-2">
                                    {recent_transactions.map((tx) => (
                                        <div
                                            key={tx.id}
                                            className="flex items-center justify-between rounded-lg bg-stone-900/50 px-3 py-2"
                                        >
                                            <div className="flex items-center gap-2">
                                                {tx.type === "buy" ? (
                                                    <ArrowDown className="h-3 w-3 text-emerald-400" />
                                                ) : (
                                                    <ArrowUp className="h-3 w-3 text-amber-400" />
                                                )}
                                                <div>
                                                    <div className="font-pixel text-xs text-stone-300">
                                                        {tx.quantity}x {tx.item_name}
                                                    </div>
                                                    <div className="font-pixel text-[10px] text-stone-500">
                                                        {tx.formatted_date}
                                                    </div>
                                                </div>
                                            </div>
                                            <div
                                                className={`font-pixel text-sm ${tx.type === "buy" ? "text-red-400" : "text-green-400"}`}
                                            >
                                                {tx.type === "buy" ? "-" : "+"}
                                                {formatGold(tx.total_gold)}
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            ) : (
                                <div className="py-8 text-center">
                                    <Store className="mx-auto mb-2 h-8 w-8 text-stone-600" />
                                    <p className="font-pixel text-xs text-stone-500">
                                        No trades yet
                                    </p>
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
