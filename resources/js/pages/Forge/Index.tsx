import { Head, router, usePage } from "@inertiajs/react";
import {
    ArrowUp,
    Backpack,
    ChevronDown,
    ChevronRight,
    Flame,
    Loader2,
    Lock,
    Package,
    Zap,
} from "lucide-react";
import { useState } from "react";
import AppLayout from "@/layouts/app-layout";
import type { BreadcrumbItem } from "@/types";

interface Material {
    name: string;
    required: number;
    have: number;
    has_enough: boolean;
}

interface Recipe {
    id: string;
    name: string;
    category: string;
    skill: string;
    required_level: number;
    xp_reward: number;
    energy_cost: number;
    materials: Material[];
    output: { name: string; quantity: number };
    can_make: boolean;
    is_locked: boolean;
    current_level: number;
}

interface MetalTier {
    name: string;
    base_level: number;
    color: string;
    unlocked: boolean;
}

interface BarInventory {
    name: string;
    quantity: number;
    metal: string;
}

interface ForgeInfo {
    can_forge: boolean;
    metal_tiers: Record<string, MetalTier>;
    smelting_recipes: Recipe[];
    player_energy: number;
    max_energy: number;
    free_slots: number;
    bar_count: number;
    bars_in_inventory: BarInventory[];
    smithing_level: number;
    smithing_xp: number;
    smithing_xp_progress: number;
    smithing_xp_to_next: number;
}

interface Location {
    type: string;
    id: number;
    name: string;
}

interface PageProps {
    forge_info: ForgeInfo;
    location?: Location;
    [key: string]: unknown;
}

const getBreadcrumbs = (location?: Location): BreadcrumbItem[] => [
    { title: "Dashboard", href: "/dashboard" },
    ...(location ? [{ title: location.name, href: `/${location.type}s/${location.id}` }] : []),
    {
        title: "Forge",
        href: location ? `/${location.type}s/${location.id}/forge` : "/forge",
    },
];

const metalColors: Record<string, { bg: string; border: string; text: string; glow: string }> = {
    Bronze: {
        bg: "bg-orange-900/30",
        border: "border-orange-600/50",
        text: "text-orange-400",
        glow: "shadow-orange-500/20",
    },
    Iron: {
        bg: "bg-gray-800/30",
        border: "border-gray-500/50",
        text: "text-gray-400",
        glow: "shadow-gray-500/20",
    },
    Steel: {
        bg: "bg-slate-800/30",
        border: "border-slate-400/50",
        text: "text-slate-300",
        glow: "shadow-slate-400/20",
    },
    Mithril: {
        bg: "bg-blue-900/30",
        border: "border-blue-500/50",
        text: "text-blue-400",
        glow: "shadow-blue-500/20",
    },
    Celestial: {
        bg: "bg-purple-900/30",
        border: "border-purple-500/50",
        text: "text-purple-400",
        glow: "shadow-purple-500/20",
    },
    Oria: {
        bg: "bg-amber-900/30",
        border: "border-amber-500/50",
        text: "text-amber-400",
        glow: "shadow-amber-500/20",
    },
};

function SmeltingRecipeCard({
    recipe,
    onSmelt,
    loading,
    metalColor,
}: {
    recipe: Recipe;
    onSmelt: (id: string) => void;
    loading: string | null;
    metalColor: { bg: string; border: string; text: string; glow: string };
}) {
    const isLoading = loading === recipe.id;
    const colors = metalColor;
    const canSmelt = recipe.can_make && !recipe.is_locked && loading === null;

    // Check if all materials are available
    const hasAllMaterials = recipe.materials.every((m) => m.has_enough);

    return (
        <button
            onClick={() => canSmelt && onSmelt(recipe.id)}
            disabled={!canSmelt}
            className={`group relative w-full overflow-hidden rounded-xl border-2 p-4 text-left transition-all ${
                recipe.is_locked
                    ? "cursor-not-allowed border-stone-700/50 bg-stone-900/50 opacity-60"
                    : recipe.can_make
                      ? `${colors.border} bg-gradient-to-br from-stone-800/80 to-stone-900/80 hover:scale-[1.02] hover:shadow-xl ${colors.glow} cursor-pointer`
                      : "cursor-not-allowed border-stone-700 bg-stone-900/50"
            }`}
        >
            {/* Loading overlay */}
            {isLoading && (
                <div className="absolute inset-0 z-10 flex items-center justify-center bg-stone-900/80">
                    <Loader2 className="h-8 w-8 animate-spin text-amber-400" />
                </div>
            )}

            {/* Item name */}
            <div className="mb-3 flex items-center justify-between">
                <h4
                    className={`text-lg font-semibold ${recipe.is_locked ? "text-stone-500" : colors.text}`}
                >
                    {recipe.name}
                </h4>
                {recipe.is_locked ? (
                    <div className="flex items-center gap-1.5 rounded-lg bg-stone-800 px-2 py-1">
                        <Lock className="h-4 w-4 text-stone-500" />
                        <span className="text-sm text-stone-500">Lvl {recipe.required_level}</span>
                    </div>
                ) : canSmelt ? (
                    <div className="flex items-center gap-1.5 rounded-lg bg-orange-600/20 px-3 py-1 transition-colors group-hover:bg-orange-600/40">
                        <Flame className="h-4 w-4 text-orange-400" />
                        <span className="text-sm font-medium text-orange-400">Smelt</span>
                    </div>
                ) : null}
            </div>

            {/* Materials needed */}
            <div className="mb-3 space-y-1">
                {recipe.materials.map((material, idx) => (
                    <div
                        key={idx}
                        className={`flex items-center justify-between rounded px-2 py-1 text-sm ${
                            material.has_enough ? "bg-green-900/30" : "bg-red-900/30"
                        }`}
                    >
                        <span className={material.has_enough ? "text-green-300" : "text-red-300"}>
                            {material.name}
                        </span>
                        <span className={material.has_enough ? "text-green-400" : "text-red-400"}>
                            {material.have}/{material.required}
                        </span>
                    </div>
                ))}
            </div>

            {/* Stats */}
            <div className="grid grid-cols-2 gap-2">
                <div className="flex items-center gap-2 rounded-lg bg-yellow-900/30 p-2">
                    <Zap className="h-5 w-5 text-yellow-400" />
                    <div>
                        <div className="text-base font-bold text-yellow-300">
                            {recipe.energy_cost}
                        </div>
                        <div className="text-xs text-yellow-500/80">Energy</div>
                    </div>
                </div>
                <div className="flex items-center gap-2 rounded-lg bg-green-900/30 p-2">
                    <ArrowUp className="h-5 w-5 text-green-400" />
                    <div>
                        <div className="text-base font-bold text-green-300">
                            +{recipe.xp_reward}
                        </div>
                        <div className="text-xs text-green-500/80">XP</div>
                    </div>
                </div>
            </div>
        </button>
    );
}

export default function ForgeIndex() {
    const { forge_info, location } = usePage<PageProps>().props;
    const [loading, setLoading] = useState<string | null>(null);
    const [result, setResult] = useState<{
        success: boolean;
        message: string;
        item?: { name: string; quantity: number };
        xp_awarded?: number;
        leveled_up?: boolean;
    } | null>(null);
    const [currentEnergy, setCurrentEnergy] = useState(forge_info.player_energy);

    const smeltUrl = location ? `/${location.type}s/${location.id}/forge/smelt` : "/forge/smelt";

    const handleSmelt = (recipeId: string) => {
        setLoading(recipeId);
        setResult(null);

        router.post(
            smeltUrl,
            { recipe: recipeId },
            {
                preserveScroll: true,
                onSuccess: () => {
                    router.reload({ only: ["forge_info", "sidebar"] });
                },
                onFinish: () => {
                    setLoading(null);
                },
            },
        );
    };

    // Group recipes by metal tier
    const recipesByMetal: Record<string, Recipe[]> = {};
    for (const recipe of forge_info.smelting_recipes || []) {
        const metal = recipe.name.replace(" Bar", "");
        if (!recipesByMetal[metal]) {
            recipesByMetal[metal] = [];
        }
        recipesByMetal[metal].push(recipe);
    }

    const metalOrder = ["Bronze", "Iron", "Steel", "Mithril", "Celestial", "Oria"];

    return (
        <AppLayout breadcrumbs={getBreadcrumbs(location)}>
            <Head title="Forge" />
            <div className="flex h-full flex-1 flex-col p-4">
                {/* Header */}
                <div className="mb-4 flex items-center gap-3">
                    <div className="rounded-lg bg-orange-900/30 p-3">
                        <Flame className="h-8 w-8 text-orange-400" />
                    </div>
                    <div>
                        <h1 className="font-pixel text-2xl text-orange-400">The Forge</h1>
                        <p className="font-pixel text-xs text-stone-400">
                            Smelt ores into metal bars
                        </p>
                    </div>
                </div>

                {/* Status Bar */}
                <div className="mb-4 grid grid-cols-3 gap-4">
                    <div className="rounded-lg border border-stone-700 bg-stone-800/50 p-3">
                        <div className="mb-1 flex items-center gap-1 font-pixel text-xs text-yellow-400">
                            <Zap className="h-3 w-3" />
                            Energy
                        </div>
                        <div className="h-2 w-full overflow-hidden rounded-full bg-stone-700">
                            <div
                                className="h-full bg-gradient-to-r from-yellow-600 to-yellow-400 transition-all"
                                style={{
                                    width: `${(currentEnergy / forge_info.max_energy) * 100}%`,
                                }}
                            />
                        </div>
                        <div className="mt-1 font-pixel text-[10px] text-stone-400">
                            {currentEnergy} / {forge_info.max_energy}
                        </div>
                    </div>
                    <div className="rounded-lg border border-stone-700 bg-stone-800/50 p-3">
                        <div className="mb-1 flex items-center justify-between">
                            <div className="flex items-center gap-1 font-pixel text-xs text-amber-300">
                                <Backpack className="h-3 w-3" />
                                Bars in Inventory
                            </div>
                            <span className="font-pixel text-xs text-stone-400">
                                {forge_info.bar_count} total
                            </span>
                        </div>
                        {forge_info.bars_in_inventory.length > 0 ? (
                            <div className="mt-1 flex flex-wrap gap-1.5">
                                {forge_info.bars_in_inventory.map((bar) => {
                                    const colors = metalColors[bar.metal] || metalColors.Bronze;
                                    return (
                                        <div
                                            key={bar.name}
                                            className={`flex items-center gap-1.5 rounded border px-2 py-1 ${colors.border} ${colors.bg}`}
                                        >
                                            <Package className={`h-3 w-3 ${colors.text}`} />
                                            <span
                                                className={`font-pixel text-[10px] ${colors.text}`}
                                            >
                                                {bar.metal}
                                            </span>
                                            <span className="font-pixel text-[10px] text-stone-400">
                                                ×{bar.quantity}
                                            </span>
                                        </div>
                                    );
                                })}
                            </div>
                        ) : (
                            <div className="mt-1 font-pixel text-[10px] text-stone-500">
                                No bars yet
                            </div>
                        )}
                    </div>
                    <div className="rounded-lg border border-stone-700 bg-stone-800/50 p-3">
                        <div className="mb-1 flex items-center justify-between">
                            <div className="flex items-center gap-1 font-pixel text-xs text-orange-400">
                                <Flame className="h-3 w-3" />
                                Smithing
                            </div>
                            <span className="font-pixel text-xs text-stone-300">
                                {forge_info.smithing_level}/99
                            </span>
                        </div>
                        <div className="h-2 w-full overflow-hidden rounded-full bg-stone-700">
                            <div
                                className="h-full bg-gradient-to-r from-orange-600 to-orange-400 transition-all"
                                style={{
                                    width: `${forge_info.smithing_xp_progress}%`,
                                }}
                            />
                        </div>
                        <div className="mt-1 font-pixel text-[10px] text-stone-400">
                            {forge_info.smithing_xp_to_next.toLocaleString()} XP to next level
                        </div>
                    </div>
                </div>

                {/* Smelting Recipes Grid */}
                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    {metalOrder.map((metal) => {
                        const recipes = recipesByMetal[metal];
                        if (!recipes || recipes.length === 0) return null;

                        const colors = metalColors[metal] || metalColors.Bronze;
                        const tier = forge_info.metal_tiers[metal];

                        return recipes.map((recipe) => (
                            <SmeltingRecipeCard
                                key={recipe.id}
                                recipe={recipe}
                                onSmelt={handleSmelt}
                                loading={loading}
                                metalColor={colors}
                            />
                        ));
                    })}
                </div>

                {/* Empty state */}
                {(!forge_info.smelting_recipes || forge_info.smelting_recipes.length === 0) && (
                    <div className="flex flex-col items-center justify-center py-12 text-center">
                        <Flame className="h-16 w-16 text-stone-600" />
                        <h3 className="mt-4 font-pixel text-lg text-stone-400">
                            No Recipes Available
                        </h3>
                        <p className="mt-2 font-pixel text-sm text-stone-500">
                            Level up your smithing skill to unlock smelting recipes
                        </p>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
