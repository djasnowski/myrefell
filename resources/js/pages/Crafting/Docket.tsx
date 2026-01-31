import { Head, router, usePage } from "@inertiajs/react";
import {
    ArrowRight,
    Beef,
    Check,
    ClipboardList,
    Clock,
    Coins,
    Hammer,
    Loader2,
    Package,
    Scissors,
    ShoppingCart,
    User,
    X,
    Zap,
} from "lucide-react";
import { useState } from "react";
import AppLayout from "@/layouts/app-layout";
import type { BreadcrumbItem } from "@/types";

interface Material {
    name: string;
    quantity: number;
}

interface NpcRecipe {
    id: string;
    name: string;
    category: string;
    output: { name: string; quantity: number };
    gold_cost: number;
    can_afford: boolean;
    materials: Material[];
}

interface OrderUser {
    id: number;
    username: string;
}

interface CraftingOrderDisplay {
    id: number;
    recipe_id: string;
    recipe_name: string;
    category: string;
    quantity: number;
    output: { name: string; quantity: number } | null;
    materials: Material[];
    gold_cost: number;
    crafter_payment: number;
    status: string;
    fulfillment_type: string;
    customer: OrderUser | null;
    crafter: OrderUser | null;
    is_tardy: boolean;
    minutes_until_due: number | null;
    accepted_at: string | null;
    due_at: string | null;
    created_at: string;
}

interface DocketInfo {
    can_access: boolean;
    location_type: string;
    location_id: number;
    pending_orders: CraftingOrderDisplay[];
    my_accepted_orders: CraftingOrderDisplay[];
    my_placed_orders: CraftingOrderDisplay[];
    npc_recipes: NpcRecipe[];
    player_gold: number;
}

interface ActionResult {
    success: boolean;
    message: string;
    item?: { name: string; quantity: number };
    gold_spent?: number;
    gold_earned?: number;
    gold_remaining?: number;
    xp_earned?: number;
    skill?: string;
    leveled_up?: boolean;
}

interface PageProps {
    docket_info: DocketInfo;
    [key: string]: unknown;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: "Dashboard", href: "/dashboard" },
    { title: "Crafting Docket", href: "/docket" },
];

const categoryIcons: Record<string, typeof Hammer> = {
    smithing: Hammer,
    cooking: Beef,
    crafting: Scissors,
};

function NpcRecipeCard({
    recipe,
    onBuy,
    loading,
    playerGold,
}: {
    recipe: NpcRecipe;
    onBuy: (id: string) => void;
    loading: string | null;
    playerGold: number;
}) {
    const isLoading = loading === recipe.id;
    const canAfford = playerGold >= recipe.gold_cost;
    const CategoryIcon = categoryIcons[recipe.category] || Hammer;

    return (
        <div
            className={`rounded-lg border p-3 transition ${
                canAfford
                    ? "border-amber-600/50 bg-stone-800/50"
                    : "border-stone-700 bg-stone-800/30 opacity-60"
            }`}
        >
            <div className="mb-2 flex items-center justify-between">
                <div className="flex items-center gap-2">
                    <CategoryIcon className="h-4 w-4 text-stone-400" />
                    <span className="font-pixel text-sm text-amber-300">{recipe.name}</span>
                </div>
            </div>

            <div className="mb-2 flex items-center gap-2 rounded bg-stone-900/50 px-2 py-1">
                <ArrowRight className="h-3 w-3 text-amber-400" />
                <span className="font-pixel text-xs text-stone-300">
                    {recipe.output.quantity}x {recipe.output.name}
                </span>
            </div>

            <div className="mb-3 flex items-center justify-between">
                <span className="flex items-center gap-1 font-pixel text-xs text-yellow-400">
                    <Coins className="h-3 w-3" />
                    {recipe.gold_cost} gold
                </span>
                <span className="font-pixel text-[10px] text-stone-500">Instant</span>
            </div>

            <button
                onClick={() => onBuy(recipe.id)}
                disabled={!canAfford || loading !== null}
                className={`flex w-full items-center justify-center gap-2 rounded-md px-3 py-2 font-pixel text-xs transition ${
                    canAfford && !loading
                        ? "bg-amber-600 text-stone-900 hover:bg-amber-500"
                        : "cursor-not-allowed bg-stone-700 text-stone-500"
                }`}
            >
                {isLoading ? (
                    <>
                        <Loader2 className="h-3 w-3 animate-spin" />
                        Buying...
                    </>
                ) : canAfford ? (
                    <>
                        <ShoppingCart className="h-3 w-3" />
                        Buy from NPC
                    </>
                ) : (
                    <>
                        <X className="h-3 w-3" />
                        Not Enough Gold
                    </>
                )}
            </button>
        </div>
    );
}

function PendingOrderCard({
    order,
    onAccept,
    loading,
}: {
    order: CraftingOrderDisplay;
    onAccept: (id: number) => void;
    loading: number | null;
}) {
    const isLoading = loading === order.id;
    const CategoryIcon = categoryIcons[order.category] || Hammer;

    return (
        <div className="rounded-lg border border-stone-700 bg-stone-800/50 p-3">
            <div className="mb-2 flex items-center justify-between">
                <div className="flex items-center gap-2">
                    <CategoryIcon className="h-4 w-4 text-stone-400" />
                    <span className="font-pixel text-sm text-amber-300">{order.recipe_name}</span>
                    {order.quantity > 1 && (
                        <span className="font-pixel text-xs text-stone-500">x{order.quantity}</span>
                    )}
                </div>
            </div>

            {order.output && (
                <div className="mb-2 flex items-center gap-2 rounded bg-stone-900/50 px-2 py-1">
                    <ArrowRight className="h-3 w-3 text-amber-400" />
                    <span className="font-pixel text-xs text-stone-300">
                        {order.output.quantity}x {order.output.name}
                    </span>
                </div>
            )}

            <div className="mb-2 space-y-1">
                <div className="font-pixel text-[10px] text-stone-500">Materials needed:</div>
                {order.materials.map((material, idx) => (
                    <div key={idx} className="flex items-center justify-between text-stone-400">
                        <span className="font-pixel text-[10px]">{material.name}</span>
                        <span className="font-pixel text-[10px]">{material.quantity}</span>
                    </div>
                ))}
            </div>

            <div className="mb-3 flex items-center justify-between">
                <span className="flex items-center gap-1 font-pixel text-xs text-green-400">
                    <Coins className="h-3 w-3" />+{order.crafter_payment} gold
                </span>
                <span className="flex items-center gap-1 font-pixel text-[10px] text-stone-500">
                    <User className="h-3 w-3" />
                    {order.customer?.username || "Unknown"}
                </span>
            </div>

            <button
                onClick={() => onAccept(order.id)}
                disabled={loading !== null}
                className={`flex w-full items-center justify-center gap-2 rounded-md px-3 py-2 font-pixel text-xs transition ${
                    !loading
                        ? "bg-green-600 text-stone-900 hover:bg-green-500"
                        : "cursor-not-allowed bg-stone-700 text-stone-500"
                }`}
            >
                {isLoading ? (
                    <>
                        <Loader2 className="h-3 w-3 animate-spin" />
                        Accepting...
                    </>
                ) : (
                    <>
                        <Check className="h-3 w-3" />
                        Accept Order
                    </>
                )}
            </button>
        </div>
    );
}

function AcceptedOrderCard({
    order,
    onComplete,
    onAbandon,
    loading,
}: {
    order: CraftingOrderDisplay;
    onComplete: (id: number) => void;
    onAbandon: (id: number) => void;
    loading: number | null;
}) {
    const isLoading = loading === order.id;
    const CategoryIcon = categoryIcons[order.category] || Hammer;

    return (
        <div
            className={`rounded-lg border p-3 ${
                order.is_tardy
                    ? "border-red-600/50 bg-red-900/20"
                    : "border-amber-600/50 bg-stone-800/50"
            }`}
        >
            <div className="mb-2 flex items-center justify-between">
                <div className="flex items-center gap-2">
                    <CategoryIcon className="h-4 w-4 text-stone-400" />
                    <span className="font-pixel text-sm text-amber-300">{order.recipe_name}</span>
                </div>
                <div
                    className={`flex items-center gap-1 font-pixel text-xs ${
                        order.is_tardy ? "text-red-400" : "text-yellow-400"
                    }`}
                >
                    <Clock className="h-3 w-3" />
                    {order.is_tardy ? "LATE!" : `${order.minutes_until_due}m left`}
                </div>
            </div>

            <div className="mb-2 space-y-1">
                <div className="font-pixel text-[10px] text-stone-500">You need:</div>
                {order.materials.map((material, idx) => (
                    <div key={idx} className="flex items-center justify-between text-stone-400">
                        <span className="font-pixel text-[10px]">{material.name}</span>
                        <span className="font-pixel text-[10px]">{material.quantity}</span>
                    </div>
                ))}
            </div>

            <div className="mb-3 flex items-center justify-between">
                <span className="flex items-center gap-1 font-pixel text-xs text-green-400">
                    <Coins className="h-3 w-3" />+{order.crafter_payment} gold
                </span>
                <span className="flex items-center gap-1 font-pixel text-[10px] text-stone-500">
                    <User className="h-3 w-3" />
                    For: {order.customer?.username || "Unknown"}
                </span>
            </div>

            <div className="flex gap-2">
                <button
                    onClick={() => onComplete(order.id)}
                    disabled={loading !== null}
                    className={`flex flex-1 items-center justify-center gap-2 rounded-md px-3 py-2 font-pixel text-xs transition ${
                        !loading
                            ? "bg-green-600 text-stone-900 hover:bg-green-500"
                            : "cursor-not-allowed bg-stone-700 text-stone-500"
                    }`}
                >
                    {isLoading ? (
                        <Loader2 className="h-3 w-3 animate-spin" />
                    ) : (
                        <>
                            <Check className="h-3 w-3" />
                            Complete
                        </>
                    )}
                </button>
                <button
                    onClick={() => onAbandon(order.id)}
                    disabled={loading !== null}
                    className="flex items-center justify-center gap-1 rounded-md bg-stone-700 px-3 py-2 font-pixel text-xs text-stone-400 transition hover:bg-stone-600"
                >
                    <X className="h-3 w-3" />
                </button>
            </div>
        </div>
    );
}

function MyOrderCard({
    order,
    onCancel,
    loading,
}: {
    order: CraftingOrderDisplay;
    onCancel: (id: number) => void;
    loading: number | null;
}) {
    const isLoading = loading === order.id;
    const CategoryIcon = categoryIcons[order.category] || Hammer;
    const isPending = order.status === "pending";

    return (
        <div className="rounded-lg border border-stone-700 bg-stone-800/50 p-3">
            <div className="mb-2 flex items-center justify-between">
                <div className="flex items-center gap-2">
                    <CategoryIcon className="h-4 w-4 text-stone-400" />
                    <span className="font-pixel text-sm text-amber-300">{order.recipe_name}</span>
                </div>
                <span
                    className={`rounded px-2 py-0.5 font-pixel text-[10px] ${
                        isPending
                            ? "bg-yellow-900/50 text-yellow-400"
                            : "bg-blue-900/50 text-blue-400"
                    }`}
                >
                    {isPending ? "Waiting" : "In Progress"}
                </span>
            </div>

            {order.output && (
                <div className="mb-2 flex items-center gap-2 rounded bg-stone-900/50 px-2 py-1">
                    <ArrowRight className="h-3 w-3 text-amber-400" />
                    <span className="font-pixel text-xs text-stone-300">
                        {order.output.quantity}x {order.output.name}
                    </span>
                </div>
            )}

            <div className="mb-3 flex items-center justify-between text-stone-500">
                <span className="font-pixel text-[10px]">Paid: {order.gold_cost} gold</span>
                {order.crafter && (
                    <span className="flex items-center gap-1 font-pixel text-[10px]">
                        <User className="h-3 w-3" />
                        {order.crafter.username}
                    </span>
                )}
            </div>

            {isPending && (
                <button
                    onClick={() => onCancel(order.id)}
                    disabled={loading !== null}
                    className="flex w-full items-center justify-center gap-2 rounded-md bg-stone-700 px-3 py-2 font-pixel text-xs text-stone-400 transition hover:bg-stone-600"
                >
                    {isLoading ? (
                        <Loader2 className="h-3 w-3 animate-spin" />
                    ) : (
                        <>
                            <X className="h-3 w-3" />
                            Cancel Order
                        </>
                    )}
                </button>
            )}
        </div>
    );
}

export default function DocketPage() {
    const { docket_info } = usePage<PageProps>().props;
    const [loading, setLoading] = useState<string | number | null>(null);
    const [result, setResult] = useState<ActionResult | null>(null);
    const [activeTab, setActiveTab] = useState<"npc" | "orders" | "my-work" | "my-orders">("npc");
    const [playerGold, setPlayerGold] = useState(docket_info.player_gold);

    const handleNpcBuy = async (recipeId: string) => {
        setLoading(recipeId);
        setResult(null);

        try {
            const response = await fetch("/docket/npc-order", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN":
                        document
                            .querySelector('meta[name="csrf-token"]')
                            ?.getAttribute("content") || "",
                },
                body: JSON.stringify({ recipe: recipeId, quantity: 1 }),
            });

            const data: ActionResult = await response.json();
            setResult(data);

            if (data.success && data.gold_remaining !== undefined) {
                setPlayerGold(data.gold_remaining);
            }

            router.reload({ only: ["docket_info", "sidebar"] });
        } catch {
            setResult({ success: false, message: "An error occurred" });
        } finally {
            setLoading(null);
        }
    };

    const handleAcceptOrder = async (orderId: number) => {
        setLoading(orderId);
        setResult(null);

        try {
            const response = await fetch(`/docket/${orderId}/accept`, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN":
                        document
                            .querySelector('meta[name="csrf-token"]')
                            ?.getAttribute("content") || "",
                },
            });

            const data: ActionResult = await response.json();
            setResult(data);
            router.reload({ only: ["docket_info", "sidebar"] });
        } catch {
            setResult({ success: false, message: "An error occurred" });
        } finally {
            setLoading(null);
        }
    };

    const handleCompleteOrder = async (orderId: number) => {
        setLoading(orderId);
        setResult(null);

        try {
            const response = await fetch(`/docket/${orderId}/complete`, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN":
                        document
                            .querySelector('meta[name="csrf-token"]')
                            ?.getAttribute("content") || "",
                },
            });

            const data: ActionResult = await response.json();
            setResult(data);

            if (data.success && data.gold_remaining !== undefined) {
                setPlayerGold(data.gold_remaining);
            }

            router.reload({ only: ["docket_info", "sidebar"] });
        } catch {
            setResult({ success: false, message: "An error occurred" });
        } finally {
            setLoading(null);
        }
    };

    const handleAbandonOrder = async (orderId: number) => {
        setLoading(orderId);
        setResult(null);

        try {
            const response = await fetch(`/docket/${orderId}/abandon`, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN":
                        document
                            .querySelector('meta[name="csrf-token"]')
                            ?.getAttribute("content") || "",
                },
            });

            const data: ActionResult = await response.json();
            setResult(data);
            router.reload({ only: ["docket_info", "sidebar"] });
        } catch {
            setResult({ success: false, message: "An error occurred" });
        } finally {
            setLoading(null);
        }
    };

    const handleCancelOrder = async (orderId: number) => {
        setLoading(orderId);
        setResult(null);

        try {
            const response = await fetch(`/docket/${orderId}/cancel`, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN":
                        document
                            .querySelector('meta[name="csrf-token"]')
                            ?.getAttribute("content") || "",
                },
            });

            const data: ActionResult = await response.json();
            setResult(data);

            if (data.success && data.gold_remaining !== undefined) {
                setPlayerGold(data.gold_remaining);
            }

            router.reload({ only: ["docket_info", "sidebar"] });
        } catch {
            setResult({ success: false, message: "An error occurred" });
        } finally {
            setLoading(null);
        }
    };

    const tabs = [
        { id: "npc" as const, label: "NPC Shop", count: docket_info.npc_recipes.length },
        { id: "orders" as const, label: "Open Orders", count: docket_info.pending_orders.length },
        { id: "my-work" as const, label: "My Work", count: docket_info.my_accepted_orders.length },
        {
            id: "my-orders" as const,
            label: "My Orders",
            count: docket_info.my_placed_orders.length,
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Crafting Docket" />
            <div className="flex h-full flex-1 flex-col p-4">
                {/* Header */}
                <div className="mb-4 flex items-center gap-3">
                    <div className="rounded-lg bg-amber-900/30 p-3">
                        <ClipboardList className="h-8 w-8 text-amber-400" />
                    </div>
                    <div>
                        <h1 className="font-pixel text-2xl text-amber-400">Crafting Docket</h1>
                        <p className="font-pixel text-xs text-stone-400">
                            Buy crafted items or fulfill orders for gold
                        </p>
                    </div>
                </div>

                {/* Gold Display */}
                <div className="mb-4 rounded-lg border border-stone-700 bg-stone-800/50 p-3">
                    <div className="flex items-center gap-2">
                        <Coins className="h-5 w-5 text-yellow-400" />
                        <span className="font-pixel text-lg text-yellow-400">{playerGold}</span>
                        <span className="font-pixel text-xs text-stone-500">gold</span>
                    </div>
                </div>

                {/* Result Message */}
                {result && (
                    <div
                        className={`mb-4 rounded-lg border p-3 ${
                            result.success
                                ? "border-green-600/50 bg-green-900/20"
                                : "border-red-600/50 bg-red-900/20"
                        }`}
                    >
                        <div className="flex items-center gap-3">
                            {result.success && result.item ? (
                                <>
                                    <Package className="h-6 w-6 text-green-400" />
                                    <div>
                                        <div className="font-pixel text-sm text-green-300">
                                            {result.message}
                                        </div>
                                        {result.xp_earned && (
                                            <div className="flex items-center gap-2">
                                                <span className="font-pixel text-[10px] text-amber-400">
                                                    +{result.xp_earned} XP
                                                </span>
                                                {result.leveled_up && (
                                                    <span className="font-pixel text-[10px] text-yellow-300">
                                                        Level Up!
                                                    </span>
                                                )}
                                            </div>
                                        )}
                                    </div>
                                </>
                            ) : result.success ? (
                                <>
                                    <Check className="h-6 w-6 text-green-400" />
                                    <div className="font-pixel text-sm text-green-300">
                                        {result.message}
                                    </div>
                                </>
                            ) : (
                                <span className="font-pixel text-sm text-red-400">
                                    {result.message}
                                </span>
                            )}
                        </div>
                    </div>
                )}

                {/* Tabs */}
                <div className="mb-4 flex gap-2 overflow-x-auto">
                    {tabs.map((tab) => (
                        <button
                            key={tab.id}
                            onClick={() => setActiveTab(tab.id)}
                            className={`flex items-center gap-2 whitespace-nowrap rounded-lg px-3 py-1.5 font-pixel text-xs transition ${
                                activeTab === tab.id
                                    ? "bg-amber-600 text-stone-900"
                                    : "bg-stone-800 text-stone-400 hover:bg-stone-700"
                            }`}
                        >
                            {tab.label}
                            {tab.count > 0 && (
                                <span
                                    className={`rounded px-1.5 py-0.5 text-[10px] ${
                                        activeTab === tab.id ? "bg-stone-900/30" : "bg-stone-700"
                                    }`}
                                >
                                    {tab.count}
                                </span>
                            )}
                        </button>
                    ))}
                </div>

                {/* Tab Content */}
                <div className="flex-1 overflow-y-auto">
                    {activeTab === "npc" && (
                        <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                            {docket_info.npc_recipes.map((recipe) => (
                                <NpcRecipeCard
                                    key={recipe.id}
                                    recipe={recipe}
                                    onBuy={handleNpcBuy}
                                    loading={typeof loading === "string" ? loading : null}
                                    playerGold={playerGold}
                                />
                            ))}
                        </div>
                    )}

                    {activeTab === "orders" && (
                        <>
                            {docket_info.pending_orders.length === 0 ? (
                                <div className="flex flex-1 items-center justify-center py-12">
                                    <div className="text-center">
                                        <ClipboardList className="mx-auto mb-3 h-12 w-12 text-stone-600" />
                                        <p className="font-pixel text-sm text-stone-500">
                                            No open orders available
                                        </p>
                                        <p className="font-pixel text-xs text-stone-600">
                                            Check back later or try another location
                                        </p>
                                    </div>
                                </div>
                            ) : (
                                <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                                    {docket_info.pending_orders.map((order) => (
                                        <PendingOrderCard
                                            key={order.id}
                                            order={order}
                                            onAccept={handleAcceptOrder}
                                            loading={typeof loading === "number" ? loading : null}
                                        />
                                    ))}
                                </div>
                            )}
                        </>
                    )}

                    {activeTab === "my-work" && (
                        <>
                            {docket_info.my_accepted_orders.length === 0 ? (
                                <div className="flex flex-1 items-center justify-center py-12">
                                    <div className="text-center">
                                        <Zap className="mx-auto mb-3 h-12 w-12 text-stone-600" />
                                        <p className="font-pixel text-sm text-stone-500">
                                            No active work orders
                                        </p>
                                        <p className="font-pixel text-xs text-stone-600">
                                            Accept orders from the Open Orders tab
                                        </p>
                                    </div>
                                </div>
                            ) : (
                                <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                                    {docket_info.my_accepted_orders.map((order) => (
                                        <AcceptedOrderCard
                                            key={order.id}
                                            order={order}
                                            onComplete={handleCompleteOrder}
                                            onAbandon={handleAbandonOrder}
                                            loading={typeof loading === "number" ? loading : null}
                                        />
                                    ))}
                                </div>
                            )}
                        </>
                    )}

                    {activeTab === "my-orders" && (
                        <>
                            {docket_info.my_placed_orders.length === 0 ? (
                                <div className="flex flex-1 items-center justify-center py-12">
                                    <div className="text-center">
                                        <ShoppingCart className="mx-auto mb-3 h-12 w-12 text-stone-600" />
                                        <p className="font-pixel text-sm text-stone-500">
                                            No placed orders
                                        </p>
                                        <p className="font-pixel text-xs text-stone-600">
                                            Place orders from the crafting page for player crafters
                                        </p>
                                    </div>
                                </div>
                            ) : (
                                <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                                    {docket_info.my_placed_orders.map((order) => (
                                        <MyOrderCard
                                            key={order.id}
                                            order={order}
                                            onCancel={handleCancelOrder}
                                            loading={typeof loading === "number" ? loading : null}
                                        />
                                    ))}
                                </div>
                            )}
                        </>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
