import { Head, router, usePage } from "@inertiajs/react";
import {
    AlertTriangle,
    ArrowLeft,
    Coins,
    Loader2,
    Minus,
    Package,
    Plus,
    ShoppingBag,
    Store,
    User,
} from "lucide-react";
import { useState } from "react";
import AppLayout from "@/layouts/app-layout";
import type { BreadcrumbItem } from "@/types";
import { locationPath } from "@/lib/utils";

interface ShopData {
    id: number;
    name: string;
    slug: string;
    npc_name: string;
    npc_description: string | null;
    description: string | null;
    icon: string | null;
}

interface ShopItemData {
    id: number;
    item_name: string;
    item_description: string | null;
    item_type: string;
    item_rarity: string;
    item_stackable: boolean;
    item_max_stack: number | null;
    price: number;
    in_stock: boolean;
    stock_quantity: number | null;
    max_stock: number | null;
}

interface LocationData {
    type: string;
    id: number;
    name: string;
}

interface PageProps {
    shop: ShopData;
    items: ShopItemData[];
    player_gold: number;
    inventory_free_slots: number;
    inventory_max_slots: number;
    location?: LocationData;
    [key: string]: unknown;
}

const rarityColors: Record<string, string> = {
    common: "text-stone-300",
    uncommon: "text-green-400",
    rare: "text-blue-400",
    epic: "text-purple-400",
    legendary: "text-amber-400",
};

const rarityBorderColors: Record<string, string> = {
    common: "border-stone-600",
    uncommon: "border-green-600/50",
    rare: "border-blue-600/50",
    epic: "border-purple-600/50",
    legendary: "border-amber-600/50",
};

function formatNumber(n: number): string {
    return n.toLocaleString();
}

export default function ShopShow() {
    const { shop, items, player_gold, inventory_free_slots, inventory_max_slots, location } =
        usePage<PageProps>().props;
    const inventoryFull = inventory_free_slots <= 0;
    const [buyingItem, setBuyingItem] = useState<number | null>(null);
    const [quantities, setQuantities] = useState<Record<number, number>>({});
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const [success, setSuccess] = useState<string | null>(null);

    const breadcrumbs: BreadcrumbItem[] = [{ title: "Dashboard", href: "/dashboard" }];

    if (location) {
        breadcrumbs.push({
            title: location.name,
            href: locationPath(location.type, location.id),
        });
        breadcrumbs.push({
            title: "Shops",
            href: `${locationPath(location.type, location.id)}/shops`,
        });
    }

    breadcrumbs.push({ title: shop.name, href: "#" });

    const getQuantity = (itemId: number) => quantities[itemId] || 1;

    const slotsNeeded = (item: ShopItemData, qty: number) => {
        if (item.item_stackable && item.item_max_stack) {
            return Math.ceil(qty / item.item_max_stack);
        }
        return qty;
    };

    const setQuantity = (itemId: number, qty: number) => {
        setQuantities((prev) => ({ ...prev, [itemId]: Math.max(1, qty) }));
    };

    const handleBuy = async (shopItem: ShopItemData) => {
        const quantity = getQuantity(shopItem.id);
        setLoading(true);
        setError(null);
        setSuccess(null);

        try {
            const shopUrl = location
                ? `${locationPath(location.type, location.id)}/shops/${shop.slug}/buy`
                : "#";

            const response = await fetch(shopUrl, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN":
                        document
                            .querySelector('meta[name="csrf-token"]')
                            ?.getAttribute("content") || "",
                },
                body: JSON.stringify({
                    shop_item_id: shopItem.id,
                    quantity,
                }),
            });

            const data = await response.json();

            if (data.success) {
                setSuccess(data.message);
                setBuyingItem(null);
                setQuantities((prev) => ({ ...prev, [shopItem.id]: 1 }));
                router.reload({
                    only: ["items", "player_gold", "inventory_free_slots", "sidebar"],
                });
            } else {
                setError(data.message);
            }
        } catch {
            setError("An error occurred while purchasing.");
        } finally {
            setLoading(false);
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`${shop.name}${location ? ` - ${location.name}` : ""}`} />
            <div className="flex h-full flex-1 flex-col p-4">
                {/* Back link */}
                {location && (
                    <button
                        onClick={() =>
                            router.visit(`${locationPath(location.type, location.id)}/shops`)
                        }
                        className="mb-4 flex items-center gap-1 font-pixel text-xs text-stone-400 transition-colors hover:text-amber-400"
                    >
                        <ArrowLeft className="h-3 w-3" />
                        Back to Shops
                    </button>
                )}

                {/* NPC Header */}
                <div className="mb-6 rounded-xl border-2 border-stone-700 bg-stone-800/50 p-6">
                    <div className="mb-4 flex items-center gap-4">
                        <div className="flex h-16 w-16 items-center justify-center rounded-full bg-amber-900/30">
                            <User className="h-8 w-8 text-amber-400" />
                        </div>
                        <div>
                            <h2 className="font-pixel text-lg text-amber-300">{shop.npc_name}</h2>
                            <div className="flex items-center gap-1 text-stone-400">
                                <Store className="h-3 w-3" />
                                <span className="font-pixel text-xs">{shop.name}</span>
                            </div>
                        </div>
                    </div>
                    {shop.npc_description && (
                        <p className="font-pixel text-xs leading-relaxed italic text-stone-300">
                            "{shop.npc_description}"
                        </p>
                    )}
                </div>

                {/* Gold bar */}
                <div className="mb-4 flex items-center justify-between rounded-lg border border-stone-700 bg-stone-800/50 px-4 py-2">
                    <div className="flex items-center gap-2">
                        <Coins className="h-4 w-4 text-yellow-400" />
                        <span className="font-pixel text-sm text-yellow-400">
                            {formatNumber(player_gold)} gold
                        </span>
                    </div>
                    <div className="flex items-center gap-1">
                        <Package className="h-3 w-3 text-stone-400" />
                        <span className="font-pixel text-[10px] text-stone-400">
                            {inventory_free_slots} / {inventory_max_slots} slots free
                        </span>
                    </div>
                </div>

                {/* Inventory full warning */}
                {inventoryFull && (
                    <div className="mb-4 flex items-center gap-2 rounded-lg border border-amber-600/50 bg-amber-900/20 px-4 py-2">
                        <AlertTriangle className="h-4 w-4 shrink-0 text-amber-400" />
                        <span className="font-pixel text-xs text-amber-400">
                            Your inventory is full. Free up space before purchasing.
                        </span>
                    </div>
                )}

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

                {/* Items list */}
                {items.length === 0 ? (
                    <div className="rounded-xl border-2 border-stone-700 bg-stone-800/50 p-8 text-center">
                        <ShoppingBag className="mx-auto mb-3 h-12 w-12 text-stone-500" />
                        <h3 className="mb-2 font-pixel text-lg text-stone-400">Nothing for Sale</h3>
                        <p className="font-pixel text-xs text-stone-500">
                            This shop has no items available right now.
                        </p>
                    </div>
                ) : (
                    <div className="space-y-3">
                        {items.map((item) => {
                            const qty = getQuantity(item.id);
                            const canAfford = player_gold >= item.price * qty;
                            const isBuying = buyingItem === item.id;
                            const needed = slotsNeeded(item, qty);
                            const hasSpace = needed <= inventory_free_slots;

                            return (
                                <div
                                    key={item.id}
                                    className={`rounded-xl border-2 p-4 transition-all ${rarityBorderColors[item.item_rarity] || "border-stone-700"} bg-stone-800/50`}
                                >
                                    {/* Item info row */}
                                    <div className="flex items-center justify-between gap-4">
                                        <div className="min-w-0 flex-1">
                                            <h4
                                                className={`font-pixel text-sm ${rarityColors[item.item_rarity] || "text-stone-300"}`}
                                            >
                                                {item.item_name}
                                            </h4>
                                            {item.item_description && (
                                                <p className="mt-0.5 font-pixel text-[10px] text-stone-400">
                                                    {item.item_description}
                                                </p>
                                            )}
                                            <div className="mt-1 flex items-center gap-3">
                                                <span className="font-pixel text-[10px] capitalize text-stone-500">
                                                    {item.item_type}
                                                </span>
                                                {item.stock_quantity !== null && (
                                                    <span
                                                        className={`font-pixel text-[10px] ${item.in_stock ? "text-stone-400" : "text-red-400"}`}
                                                    >
                                                        Stock: {item.stock_quantity}
                                                        {item.max_stock
                                                            ? ` / ${item.max_stock}`
                                                            : ""}
                                                    </span>
                                                )}
                                            </div>
                                        </div>

                                        <div className="flex shrink-0 items-center gap-3">
                                            <div className="flex items-center gap-1">
                                                <Coins className="h-3 w-3 text-yellow-400" />
                                                <span className="font-pixel text-sm text-yellow-400">
                                                    {formatNumber(item.price)}
                                                </span>
                                            </div>

                                            {!isBuying ? (
                                                <button
                                                    onClick={() => setBuyingItem(item.id)}
                                                    disabled={
                                                        !item.in_stock || inventoryFull || loading
                                                    }
                                                    className={`rounded-lg px-4 py-2 font-pixel text-xs transition-all ${
                                                        item.in_stock && !inventoryFull
                                                            ? "bg-amber-600 text-white hover:bg-amber-500"
                                                            : "cursor-not-allowed bg-stone-700 text-stone-500"
                                                    }`}
                                                >
                                                    {!item.in_stock
                                                        ? "Sold Out"
                                                        : inventoryFull
                                                          ? "Inv Full"
                                                          : "Buy"}
                                                </button>
                                            ) : (
                                                <button
                                                    onClick={() => {
                                                        setBuyingItem(null);
                                                        setQuantities((prev) => ({
                                                            ...prev,
                                                            [item.id]: 1,
                                                        }));
                                                    }}
                                                    disabled={loading}
                                                    className="rounded-lg px-2 py-1.5 font-pixel text-xs text-stone-400 transition-colors hover:text-white"
                                                >
                                                    ✕
                                                </button>
                                            )}
                                        </div>
                                    </div>

                                    {/* Purchase controls — separate row below */}
                                    {isBuying && (
                                        <div className="mt-3 flex items-center justify-between gap-4 rounded-lg border border-stone-700 bg-stone-900/50 px-4 py-3">
                                            {/* Quantity controls */}
                                            <div className="flex items-center gap-3">
                                                <span className="font-pixel text-[10px] text-stone-400">
                                                    Qty
                                                </span>
                                                <div className="flex items-center rounded-lg border border-stone-600 bg-stone-700/50">
                                                    <button
                                                        onClick={() =>
                                                            setQuantity(item.id, qty - 1)
                                                        }
                                                        disabled={loading}
                                                        className="px-2 py-1.5 text-stone-400 transition-colors hover:text-white"
                                                    >
                                                        <Minus className="h-3 w-3" />
                                                    </button>
                                                    <input
                                                        type="number"
                                                        min={1}
                                                        value={qty}
                                                        onChange={(e) => {
                                                            const val = parseInt(
                                                                e.target.value,
                                                                10,
                                                            );
                                                            if (!isNaN(val)) {
                                                                setQuantity(item.id, val);
                                                            }
                                                        }}
                                                        disabled={loading}
                                                        className="w-12 bg-transparent text-center font-pixel text-sm text-white outline-none [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none"
                                                    />
                                                    <button
                                                        onClick={() =>
                                                            setQuantity(item.id, qty + 1)
                                                        }
                                                        disabled={loading}
                                                        className="px-2 py-1.5 text-stone-400 transition-colors hover:text-white"
                                                    >
                                                        <Plus className="h-3 w-3" />
                                                    </button>
                                                </div>
                                            </div>

                                            {/* Total cost */}
                                            <div className="flex flex-col items-center">
                                                <span className="font-pixel text-[10px] text-stone-500">
                                                    Total
                                                </span>
                                                <span
                                                    className={`font-pixel text-sm ${canAfford ? "text-yellow-400" : "text-red-400"}`}
                                                >
                                                    {formatNumber(item.price * qty)}g
                                                </span>
                                            </div>

                                            {/* Slots info */}
                                            <div className="flex flex-col items-center">
                                                <span className="font-pixel text-[10px] text-stone-500">
                                                    Slots needed
                                                </span>
                                                <span
                                                    className={`font-pixel text-sm ${hasSpace ? "text-stone-300" : "text-red-400"}`}
                                                >
                                                    {needed} of {inventory_free_slots} free
                                                </span>
                                            </div>

                                            {/* Confirm */}
                                            <button
                                                onClick={() => handleBuy(item)}
                                                disabled={!canAfford || !hasSpace || loading}
                                                className={`rounded-lg px-5 py-2 font-pixel text-xs transition-all ${
                                                    canAfford && hasSpace && !loading
                                                        ? "bg-green-600 text-white hover:bg-green-500"
                                                        : "cursor-not-allowed bg-stone-700 text-stone-500"
                                                }`}
                                            >
                                                {loading ? (
                                                    <Loader2 className="h-3 w-3 animate-spin" />
                                                ) : !canAfford ? (
                                                    "Not enough gold"
                                                ) : !hasSpace ? (
                                                    "Not enough space"
                                                ) : (
                                                    "Confirm Purchase"
                                                )}
                                            </button>
                                        </div>
                                    )}
                                </div>
                            );
                        })}
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
