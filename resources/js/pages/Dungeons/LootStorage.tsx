import { Head, Link, router, usePage } from "@inertiajs/react";
import { AlertTriangle, ArrowLeft, Backpack, Clock, Package } from "lucide-react";
import { useState } from "react";
import AppLayout from "@/layouts/app-layout";
import { gameToast } from "@/components/ui/game-toast";
import type { BreadcrumbItem } from "@/types";

interface Item {
    id: number;
    name: string;
    icon: string | null;
    type: string;
}

interface Kingdom {
    id: number;
    name: string;
}

interface LootEntry {
    id: number;
    item: Item;
    quantity: number;
    expires_at: string;
    days_until_expiry: number;
}

interface KingdomLoot {
    kingdom: Kingdom;
    items: LootEntry[];
}

interface PageProps {
    kingdom: Kingdom;
    loot_by_kingdom: KingdomLoot[];
    total_items: number;
    inventory_free_slots: number;
    [key: string]: unknown;
}

export default function LootStorage() {
    const { kingdom, loot_by_kingdom, total_items, inventory_free_slots } =
        usePage<PageProps>().props;
    const [claimingId, setClaimingId] = useState<number | null>(null);
    const [claimingKingdomId, setClaimingKingdomId] = useState<number | null>(null);
    const [error, setError] = useState<string | null>(null);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: "Dashboard", href: "/dashboard" },
        { title: kingdom.name, href: `/kingdoms/${kingdom.id}` },
        { title: "Dungeons", href: `/kingdoms/${kingdom.id}/dungeons` },
        { title: "Loot Storage", href: `/kingdoms/${kingdom.id}/dungeons/loot` },
    ];

    const claimItem = async (storageId: number) => {
        if (claimingId) return;
        setClaimingId(storageId);
        setError(null);

        try {
            const response = await fetch(`/kingdoms/${kingdom.id}/dungeons/loot/claim`, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN":
                        document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')
                            ?.content || "",
                },
                body: JSON.stringify({ storage_id: storageId }),
            });

            const data = await response.json();
            if (data.success) {
                gameToast.success(data.message || "Loot claimed!", { icon: Package });
                router.reload();
            } else {
                setError(data.message || "Failed to claim item");
            }
        } finally {
            setClaimingId(null);
        }
    };

    const claimAllFromKingdom = async (kingdomId: number) => {
        if (claimingKingdomId) return;
        setClaimingKingdomId(kingdomId);
        setError(null);

        try {
            const response = await fetch(`/kingdoms/${kingdom.id}/dungeons/loot/claim-all`, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN":
                        document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')
                            ?.content || "",
                },
                body: JSON.stringify({ kingdom_id: kingdomId }),
            });

            const data = await response.json();
            if (data.success) {
                gameToast.success(data.message || "All loot claimed!", { icon: Package });
                router.reload();
            } else {
                setError(data.message || "Failed to claim items");
            }
        } finally {
            setClaimingKingdomId(null);
        }
    };

    const getExpiryColor = (daysLeft: number) => {
        if (daysLeft <= 1) return "text-red-400";
        if (daysLeft <= 3) return "text-orange-400";
        return "text-stone-400";
    };

    const getExpiryBgColor = (daysLeft: number) => {
        if (daysLeft <= 1) return "bg-red-900/20 border-red-500/30";
        if (daysLeft <= 3) return "bg-orange-900/20 border-orange-500/30";
        return "";
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dungeon Loot Storage" />
            <div className="flex h-full flex-1 flex-col p-4">
                {/* Header */}
                <div className="mb-6 flex items-center justify-between">
                    <div>
                        <h1 className="font-pixel text-2xl text-amber-400">Loot Storage</h1>
                        <p className="font-pixel text-sm text-stone-400">
                            Claim your dungeon loot before it expires
                        </p>
                    </div>
                    <Link
                        href={`/kingdoms/${kingdom.id}/dungeons`}
                        className="flex items-center gap-2 rounded-lg border border-stone-700 bg-stone-800/50 px-4 py-2 font-pixel text-sm text-stone-300 transition hover:border-amber-500/50 hover:bg-stone-700/50"
                    >
                        <ArrowLeft className="h-4 w-4" />
                        Back to Dungeons
                    </Link>
                </div>

                {/* Inventory Full Warning */}
                {inventory_free_slots === 0 && (
                    <div className="mb-4 rounded-lg border border-red-500/50 bg-red-900/30 p-4">
                        <div className="flex items-start gap-3">
                            <Backpack className="mt-0.5 h-5 w-5 shrink-0 text-red-400" />
                            <div className="font-pixel text-sm text-red-200">
                                <p className="mb-1 font-bold">Your inventory is full!</p>
                                <p className="text-red-300/70">
                                    You need to free up inventory space before you can claim loot.
                                </p>
                            </div>
                        </div>
                    </div>
                )}

                {/* Low Inventory Warning */}
                {inventory_free_slots > 0 && inventory_free_slots <= 3 && (
                    <div className="mb-4 rounded-lg border border-orange-500/50 bg-orange-900/30 p-4">
                        <div className="flex items-start gap-3">
                            <Backpack className="mt-0.5 h-5 w-5 shrink-0 text-orange-400" />
                            <div className="font-pixel text-sm text-orange-200">
                                <p>
                                    Low inventory space:{" "}
                                    <span className="font-bold">{inventory_free_slots}</span> slot
                                    {inventory_free_slots !== 1 ? "s" : ""} remaining
                                </p>
                            </div>
                        </div>
                    </div>
                )}

                {/* Error Message (hide inventory full errors since we show the banner) */}
                {error && !error.includes("inventory is full") && (
                    <div className="mb-4 rounded-lg border border-red-500/50 bg-red-900/30 p-3 font-pixel text-sm text-red-300">
                        {error}
                    </div>
                )}

                {/* Info Banner */}
                <div className="mb-6 rounded-lg border border-amber-500/30 bg-amber-900/20 p-4">
                    <div className="flex items-start gap-3">
                        <AlertTriangle className="mt-0.5 h-5 w-5 shrink-0 text-amber-400" />
                        <div className="font-pixel text-sm text-amber-200">
                            <p className="mb-1">Dungeon loot is stored here until you claim it.</p>
                            <p className="text-amber-300/70">
                                Items expire 2 weeks after being earned. Claim them before they
                                disappear!
                            </p>
                        </div>
                    </div>
                </div>

                {/* Empty State */}
                {loot_by_kingdom.length === 0 ? (
                    <div className="flex flex-1 flex-col items-center justify-center py-12">
                        <Package className="mb-4 h-16 w-16 text-stone-600" />
                        <h2 className="mb-2 font-pixel text-lg text-stone-400">No Loot Stored</h2>
                        <p className="font-pixel text-sm text-stone-500">
                            Complete dungeons to earn loot!
                        </p>
                    </div>
                ) : (
                    <div className="space-y-6">
                        {/* Summary */}
                        <div className="rounded-lg border border-stone-700 bg-stone-800/50 p-4">
                            <div className="flex items-center justify-between">
                                <span className="font-pixel text-sm text-stone-400">
                                    Total Items
                                </span>
                                <span className="font-pixel text-lg text-amber-400">
                                    {total_items}
                                </span>
                            </div>
                        </div>

                        {/* Loot by Kingdom */}
                        {loot_by_kingdom.map(({ kingdom, items }) => (
                            <div
                                key={kingdom.id}
                                className="rounded-lg border border-stone-700 bg-stone-800/50"
                            >
                                {/* Kingdom Header */}
                                <div className="flex items-center justify-between border-b border-stone-700 p-4">
                                    <h2 className="font-pixel text-lg text-amber-300">
                                        {kingdom.name}
                                    </h2>
                                    <button
                                        onClick={() => claimAllFromKingdom(kingdom.id)}
                                        disabled={
                                            claimingKingdomId === kingdom.id ||
                                            inventory_free_slots === 0
                                        }
                                        className={`rounded-lg px-4 py-2 font-pixel text-sm transition disabled:cursor-not-allowed disabled:opacity-50 ${
                                            inventory_free_slots === 0
                                                ? "bg-stone-600 text-stone-400"
                                                : "bg-amber-600 text-white hover:bg-amber-500"
                                        }`}
                                    >
                                        {claimingKingdomId === kingdom.id
                                            ? "Claiming..."
                                            : "Claim All"}
                                    </button>
                                </div>

                                {/* Items Grid */}
                                <div className="grid gap-3 p-4 sm:grid-cols-2 lg:grid-cols-3">
                                    {items.map((entry) => (
                                        <div
                                            key={entry.id}
                                            className={`rounded-lg border border-stone-600 bg-stone-900/50 p-3 ${getExpiryBgColor(entry.days_until_expiry)}`}
                                        >
                                            <div className="mb-2 flex items-start justify-between">
                                                <div className="flex items-center gap-2">
                                                    {entry.item.icon ? (
                                                        <img
                                                            src={`/images/items/${entry.item.icon}`}
                                                            alt={entry.item.name}
                                                            className="h-8 w-8 object-contain"
                                                        />
                                                    ) : (
                                                        <div className="flex h-8 w-8 items-center justify-center rounded bg-stone-700">
                                                            <Package className="h-4 w-4 text-stone-400" />
                                                        </div>
                                                    )}
                                                    <div>
                                                        <span className="font-pixel text-sm text-stone-200">
                                                            {entry.item.name}
                                                        </span>
                                                        <span className="ml-2 font-pixel text-sm text-amber-400">
                                                            x{entry.quantity}
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>

                                            <div className="flex items-center justify-between">
                                                <div
                                                    className={`flex items-center gap-1 font-pixel text-xs ${getExpiryColor(entry.days_until_expiry)}`}
                                                >
                                                    <Clock className="h-3 w-3" />
                                                    {entry.days_until_expiry <= 0
                                                        ? "Expiring today!"
                                                        : entry.days_until_expiry === 1
                                                          ? "1 day left"
                                                          : `${entry.days_until_expiry} days left`}
                                                </div>

                                                <button
                                                    onClick={() => claimItem(entry.id)}
                                                    disabled={
                                                        claimingId === entry.id ||
                                                        inventory_free_slots === 0
                                                    }
                                                    className={`rounded px-3 py-1 font-pixel text-xs transition disabled:cursor-not-allowed disabled:opacity-50 ${
                                                        inventory_free_slots === 0
                                                            ? "bg-stone-800 text-stone-500"
                                                            : "bg-stone-700 text-stone-200 hover:bg-stone-600"
                                                    }`}
                                                >
                                                    {claimingId === entry.id ? "..." : "Claim"}
                                                </button>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        ))}
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
