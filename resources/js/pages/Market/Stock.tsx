import { Head, router, usePage } from "@inertiajs/react";
import { ArrowRight, Minus, Package, Plus, Store, Warehouse } from "lucide-react";
import { useState } from "react";
import AppLayout from "@/layouts/app-layout";
import type { BreadcrumbItem } from "@/types";

interface StockableItem {
    inventory_id: number;
    item_id: number;
    item_name: string;
    item_type: string;
    quantity: number;
    base_value: number;
    slot_number: number;
}

interface StockpileItem {
    item_id: number;
    item_name: string;
    item_type: string;
    quantity: number;
    base_value: number;
}

interface PageProps {
    stockable_items: StockableItem[];
    managed_stockpile: StockpileItem[];
    location_name: string;
    flash?: { success?: string; error?: string };
    errors?: { error?: string };
    [key: string]: unknown;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: "Dashboard", href: "/dashboard" },
    { title: "Market", href: "#" },
    { title: "Stock Market", href: "/market/stock" },
];

const typeColors: Record<string, string> = {
    resource: "text-blue-400",
    consumable: "text-green-400",
    tool: "text-orange-400",
    misc: "text-stone-400",
};

export default function MarketStock() {
    const { stockable_items, managed_stockpile, location_name, flash, errors } =
        usePage<PageProps>().props;
    const [quantities, setQuantities] = useState<Record<number, number>>({});
    const [loading, setLoading] = useState<number | null>(null);

    const getQuantity = (itemId: number, max: number) => {
        return Math.min(quantities[itemId] || 1, max);
    };

    const setQuantity = (itemId: number, value: number, max: number) => {
        setQuantities((prev) => ({
            ...prev,
            [itemId]: Math.max(1, Math.min(value, max)),
        }));
    };

    const handleStock = (item: StockableItem) => {
        const qty = getQuantity(item.item_id, item.quantity);
        setLoading(item.item_id);

        router.post(
            "/market/stock",
            {
                item_id: item.item_id,
                quantity: qty,
            },
            {
                preserveScroll: true,
                onSuccess: () => {
                    router.reload();
                },
                onFinish: () => setLoading(null),
            },
        );
    };

    const hasNoRole = stockable_items.length === 0 && managed_stockpile.length === 0;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Stock Market" />
            <div className="flex h-full flex-1 flex-col overflow-hidden p-4">
                {/* Header */}
                <div className="mb-6">
                    <div className="flex items-center gap-3">
                        <Store className="h-8 w-8 text-amber-400" />
                        <div>
                            <h1 className="font-pixel text-2xl text-amber-400">Stock Market</h1>
                            <p className="font-pixel text-sm text-stone-400">
                                Supply goods to {location_name}'s market
                            </p>
                        </div>
                    </div>
                </div>

                {/* Flash Messages */}
                {flash?.success && (
                    <div className="mb-4 rounded-lg border border-green-600/50 bg-green-900/20 p-3">
                        <p className="font-pixel text-sm text-green-400">{flash.success}</p>
                    </div>
                )}
                {(flash?.error || errors?.error) && (
                    <div className="mb-4 rounded-lg border border-red-600/50 bg-red-900/20 p-3">
                        <p className="font-pixel text-sm text-red-400">
                            {flash?.error || errors?.error}
                        </p>
                    </div>
                )}

                {hasNoRole ? (
                    <div className="flex flex-1 items-center justify-center">
                        <div className="text-center">
                            <Warehouse className="mx-auto mb-4 h-16 w-16 text-stone-600" />
                            <p className="font-pixel text-lg text-stone-400">No Role Here</p>
                            <p className="font-pixel text-sm text-stone-500">
                                You need to hold a role at this location to stock the market.
                            </p>
                        </div>
                    </div>
                ) : (
                    <div className="grid flex-1 gap-6 overflow-hidden lg:grid-cols-2">
                        {/* Your Inventory - Stockable Items */}
                        <div className="flex flex-col overflow-hidden rounded-xl border-2 border-stone-600 bg-stone-800/50">
                            <div className="border-b border-stone-600 bg-stone-800 px-4 py-3">
                                <div className="flex items-center gap-2">
                                    <Package className="h-5 w-5 text-amber-400" />
                                    <h2 className="font-pixel text-sm text-amber-400">
                                        Your Inventory
                                    </h2>
                                </div>
                                <p className="font-pixel text-xs text-stone-500">
                                    Items you can stock based on your role
                                </p>
                            </div>

                            <div className="flex-1 overflow-y-auto p-4">
                                {stockable_items.length === 0 ? (
                                    <p className="font-pixel text-sm text-stone-500">
                                        No stockable items in your inventory.
                                    </p>
                                ) : (
                                    <div className="space-y-2">
                                        {stockable_items.map((item) => (
                                            <div
                                                key={item.inventory_id}
                                                className="rounded-lg border border-stone-600 bg-stone-900/50 p-3"
                                            >
                                                <div className="flex items-center justify-between">
                                                    <div>
                                                        <div className="font-pixel text-sm text-stone-200">
                                                            {item.item_name}
                                                        </div>
                                                        <div className="flex items-center gap-2">
                                                            <span
                                                                className={`font-pixel text-xs capitalize ${typeColors[item.item_type]}`}
                                                            >
                                                                {item.item_type}
                                                            </span>
                                                            <span className="font-pixel text-xs text-stone-500">
                                                                x{item.quantity}
                                                            </span>
                                                        </div>
                                                    </div>

                                                    <div className="flex items-center gap-2">
                                                        {/* Quantity Selector */}
                                                        <div className="flex items-center gap-1">
                                                            <button
                                                                onClick={() =>
                                                                    setQuantity(
                                                                        item.item_id,
                                                                        getQuantity(
                                                                            item.item_id,
                                                                            item.quantity,
                                                                        ) - 1,
                                                                        item.quantity,
                                                                    )
                                                                }
                                                                className="rounded bg-stone-700 p-1 hover:bg-stone-600"
                                                            >
                                                                <Minus className="h-3 w-3 text-stone-400" />
                                                            </button>
                                                            <input
                                                                type="number"
                                                                value={getQuantity(
                                                                    item.item_id,
                                                                    item.quantity,
                                                                )}
                                                                onChange={(e) =>
                                                                    setQuantity(
                                                                        item.item_id,
                                                                        parseInt(e.target.value) ||
                                                                            1,
                                                                        item.quantity,
                                                                    )
                                                                }
                                                                className="w-12 rounded border border-stone-600 bg-stone-800 px-1 py-0.5 text-center font-pixel text-xs text-stone-200"
                                                                min="1"
                                                                max={item.quantity}
                                                            />
                                                            <button
                                                                onClick={() =>
                                                                    setQuantity(
                                                                        item.item_id,
                                                                        getQuantity(
                                                                            item.item_id,
                                                                            item.quantity,
                                                                        ) + 1,
                                                                        item.quantity,
                                                                    )
                                                                }
                                                                className="rounded bg-stone-700 p-1 hover:bg-stone-600"
                                                            >
                                                                <Plus className="h-3 w-3 text-stone-400" />
                                                            </button>
                                                        </div>

                                                        {/* Stock Button */}
                                                        <button
                                                            onClick={() => handleStock(item)}
                                                            disabled={loading === item.item_id}
                                                            className="flex items-center gap-1 rounded-lg border border-green-600 bg-green-900/30 px-3 py-1.5 font-pixel text-xs text-green-400 transition hover:bg-green-800/50 disabled:opacity-50"
                                                        >
                                                            <ArrowRight className="h-3 w-3" />
                                                            Stock
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                )}
                            </div>
                        </div>

                        {/* Current Market Stockpile */}
                        <div className="flex flex-col overflow-hidden rounded-xl border-2 border-stone-600 bg-stone-800/50">
                            <div className="border-b border-stone-600 bg-stone-800 px-4 py-3">
                                <div className="flex items-center gap-2">
                                    <Warehouse className="h-5 w-5 text-amber-400" />
                                    <h2 className="font-pixel text-sm text-amber-400">
                                        Market Stock
                                    </h2>
                                </div>
                                <p className="font-pixel text-xs text-stone-500">
                                    Current items available in the market
                                </p>
                            </div>

                            <div className="flex-1 overflow-y-auto p-4">
                                {managed_stockpile.length === 0 ? (
                                    <p className="font-pixel text-sm text-stone-500">
                                        No items in the market stockpile.
                                    </p>
                                ) : (
                                    <div className="space-y-2">
                                        {managed_stockpile.map((item) => (
                                            <div
                                                key={item.item_id}
                                                className="flex items-center justify-between rounded-lg border border-stone-600 bg-stone-900/50 p-3"
                                            >
                                                <div>
                                                    <div className="font-pixel text-sm text-stone-200">
                                                        {item.item_name}
                                                    </div>
                                                    <span
                                                        className={`font-pixel text-xs capitalize ${typeColors[item.item_type]}`}
                                                    >
                                                        {item.item_type}
                                                    </span>
                                                </div>
                                                <div className="text-right">
                                                    <div className="font-pixel text-lg text-amber-400">
                                                        {item.quantity}
                                                    </div>
                                                    <div className="font-pixel text-xs text-stone-500">
                                                        in stock
                                                    </div>
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                )}
                            </div>
                        </div>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
