import { Head, router, usePage } from "@inertiajs/react";
import {
    ArrowUp,
    Backpack,
    Flame,
    Info,
    Lock,
    Mountain,
    Package,
    Waves,
    Wheat,
    Zap,
} from "lucide-react";
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from "@/components/ui/dialog";
import { useCallback, useEffect, useState } from "react";

const SMELT_COOLDOWN_MS = 3000;

const formatTimeRemaining = (hours: number): string => {
    if (hours < 1) {
        const minutes = Math.ceil(hours * 60);
        return `${minutes}m`;
    }
    const wholeHours = Math.floor(hours);
    const minutes = Math.round((hours - wholeHours) * 60);
    if (minutes === 0) {
        return `${wholeHours}h`;
    }
    return `${wholeHours}h ${minutes}m`;
};

import AppLayout from "@/layouts/app-layout";
import { ActionQueueControls } from "@/components/action-queue-controls";
import { gameToast } from "@/components/ui/game-toast";
import { useActionQueue, type ActionResult, type QueueStats } from "@/hooks/use-action-queue";
import { locationPath } from "@/lib/utils";
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

interface BiomeAttunement {
    kingdom_id: number | null;
    kingdom_name: string | null;
    biome: string | null;
    biome_description: string | null;
    biome_skills: string[];
    attunement_level: number;
    attunement_bonus: number;
    hours_in_kingdom: number;
    arrived_at: string | null;
    next_level: {
        level: number;
        bonus: number;
        hours_remaining: number;
    } | null;
    max_level: number;
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
    biome_attunement: BiomeAttunement | null;
    biome_bonus: number;
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

const getBreadcrumbs = (location?: Location): BreadcrumbItem[] => {
    const baseUrl = location ? locationPath(location.type, location.id) : null;
    return [
        { title: "Dashboard", href: "/dashboard" },
        ...(location && baseUrl ? [{ title: location.name, href: baseUrl }] : []),
        {
            title: "Forge",
            href: baseUrl ? `${baseUrl}/forge` : "/forge",
        },
    ];
};

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
    Silver: {
        bg: "bg-zinc-800/30",
        border: "border-zinc-400/50",
        text: "text-zinc-300",
        glow: "shadow-zinc-400/20",
    },
    Steel: {
        bg: "bg-slate-800/30",
        border: "border-slate-400/50",
        text: "text-slate-300",
        glow: "shadow-slate-400/20",
    },
    Gold: {
        bg: "bg-yellow-900/30",
        border: "border-yellow-500/50",
        text: "text-yellow-400",
        glow: "shadow-yellow-500/20",
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
    isSelected,
    onSelect,
    metalColor,
}: {
    recipe: Recipe;
    isSelected: boolean;
    onSelect: (id: string) => void;
    metalColor: { bg: string; border: string; text: string; glow: string };
}) {
    const colors = metalColor;

    return (
        <button
            onClick={() => !recipe.is_locked && onSelect(recipe.id)}
            className={`group relative w-full overflow-hidden rounded-xl border-2 p-4 text-left transition-all ${
                recipe.is_locked
                    ? "cursor-not-allowed border-stone-700/50 bg-stone-900/50 opacity-60"
                    : isSelected
                      ? `border-amber-400 bg-gradient-to-br from-stone-800/80 to-stone-900/80 ring-1 ring-amber-400/50 ${colors.glow}`
                      : recipe.can_make
                        ? `${colors.border} bg-gradient-to-br from-stone-800/80 to-stone-900/80 hover:scale-[1.02] hover:shadow-xl ${colors.glow} cursor-pointer`
                        : "cursor-not-allowed border-stone-700 bg-stone-900/50"
            }`}
        >
            {/* Item name */}
            <div className="mb-3 flex items-center justify-between">
                <h4
                    className={`text-lg font-semibold ${recipe.is_locked ? "text-stone-500" : colors.text}`}
                >
                    {recipe.name}
                </h4>
                {recipe.is_locked && (
                    <div className="flex items-center gap-1.5 rounded-lg bg-stone-800 px-2 py-1">
                        <Lock className="h-4 w-4 text-stone-500" />
                        <span className="text-sm text-stone-500">Lvl {recipe.required_level}</span>
                    </div>
                )}
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
    const [currentEnergy, setCurrentEnergy] = useState(forge_info.player_energy);
    const [selectedRecipe, setSelectedRecipe] = useState<string | null>(null);

    const smeltUrl = location
        ? `${locationPath(location.type, location.id)}/forge/smelt`
        : "/forge/smelt";

    const buildBody = useCallback(() => ({ recipe: selectedRecipe }), [selectedRecipe]);

    const onActionComplete = useCallback((data: ActionResult) => {
        if (data.success && data.energy_remaining !== undefined) {
            setCurrentEnergy(data.energy_remaining);
        }
    }, []);

    const onQueueComplete = useCallback((stats: QueueStats) => {
        if (stats.completed === 0) return;

        if (stats.completed === 1 && stats.itemName) {
            gameToast.success(`Smelted ${stats.totalQuantity}x ${stats.itemName}`, {
                xp: stats.totalXp,
                levelUp: stats.lastLevelUp,
            });
        } else if (stats.completed > 1) {
            const qty = stats.totalQuantity > 0 ? `${stats.totalQuantity}x ` : "";
            gameToast.success(
                `Smelted ${qty}${stats.itemName ?? "bars"} (${stats.completed} actions)`,
                {
                    xp: stats.totalXp,
                    levelUp: stats.lastLevelUp,
                },
            );
        }
    }, []);

    const buildActionParams = useCallback(
        () => ({
            recipe: selectedRecipe,
            location_type: location?.type,
            location_id: location?.id,
        }),
        [selectedRecipe, location],
    );

    const {
        startQueue,
        cancelQueue,
        isQueueActive,
        queueProgress,
        isActionLoading,
        cooldown,
        performSingleAction,
        isGloballyLocked,
        totalXp,
        queueStartedAt,
    } = useActionQueue({
        url: smeltUrl,
        buildBody,
        cooldownMs: SMELT_COOLDOWN_MS,
        onActionComplete,
        onQueueComplete,
        reloadProps: ["forge_info", "sidebar"],
        actionType: "smelt",
        buildActionParams,
    });

    useEffect(() => {
        // Reload fresh data on mount to avoid stale cache from Inertia navigation
        router.reload({ only: ["forge_info"] });
    }, []);

    // Find the selected recipe object
    const allRecipes = forge_info.smelting_recipes || [];
    const selected = allRecipes.find((r) => r.id === selectedRecipe);
    const effectiveSelected = selected && !selected.is_locked ? selected : null;

    // Group recipes by metal tier
    const recipesByMetal: Record<string, Recipe[]> = {};
    for (const recipe of forge_info.smelting_recipes || []) {
        const metal = recipe.name.replace(" Bar", "");
        if (!recipesByMetal[metal]) {
            recipesByMetal[metal] = [];
        }
        recipesByMetal[metal].push(recipe);
    }

    const metalOrder = [
        "Bronze",
        "Iron",
        "Silver",
        "Steel",
        "Gold",
        "Mithril",
        "Celestial",
        "Oria",
    ];

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
                        <h1 className="font-pixel text-xl text-orange-400 sm:text-2xl">
                            The Forge
                        </h1>
                        <p className="font-pixel text-[10px] text-stone-400 sm:text-xs">
                            Smelt ores into metal bars
                        </p>
                    </div>
                </div>

                {/* Status Bar */}
                <div className="mb-4 space-y-2 sm:space-y-0 sm:grid sm:grid-cols-3 sm:gap-4">
                    {/* Energy and Smithing - 2 columns on mobile, part of 3-col on desktop */}
                    <div className="grid grid-cols-2 gap-2 sm:contents">
                        <div className="rounded-lg border border-stone-700 bg-stone-800/50 p-2 sm:p-3">
                            <div className="mb-1 flex items-center gap-1 font-pixel text-[10px] text-yellow-400 sm:text-xs">
                                <Zap className="h-3 w-3 shrink-0" />
                                <span>Energy</span>
                            </div>
                            <div className="h-2 w-full overflow-hidden rounded-full bg-stone-700">
                                <div
                                    className="h-full bg-gradient-to-r from-yellow-600 to-yellow-400 transition-all"
                                    style={{
                                        width: `${(currentEnergy / forge_info.max_energy) * 100}%`,
                                    }}
                                />
                            </div>
                            <div className="mt-1 font-pixel text-[9px] text-stone-400 sm:text-[10px]">
                                {currentEnergy} / {forge_info.max_energy}
                            </div>
                        </div>
                        <div className="rounded-lg border border-stone-700 bg-stone-800/50 p-2 sm:hidden">
                            <div className="mb-1 flex items-center justify-between gap-1">
                                <div className="flex min-w-0 items-center gap-1 font-pixel text-[10px] text-orange-400">
                                    <Flame className="h-3 w-3 shrink-0" />
                                    <span>Smithing</span>
                                </div>
                                <span className="shrink-0 font-pixel text-[10px] text-stone-300">
                                    {forge_info.smithing_level}
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
                            <div className="mt-1 font-pixel text-[9px] text-stone-400">
                                {forge_info.smithing_xp_to_next.toLocaleString()} XP to next
                            </div>
                        </div>
                    </div>
                    {/* Bars - full width on mobile, middle column on desktop */}
                    <div
                        className={`rounded-lg border p-2 sm:p-3 ${forge_info.free_slots <= 0 ? "border-red-600/50 bg-red-900/20" : "border-stone-700 bg-stone-800/50"}`}
                    >
                        <div className="mb-1 flex items-center justify-between">
                            <div className="flex items-center gap-1 font-pixel text-[10px] text-amber-300 sm:text-xs">
                                <Backpack className="h-3 w-3 shrink-0" />
                                <span>Bars in Inventory</span>
                            </div>
                            <span
                                className={`font-pixel text-[10px] sm:text-xs ${forge_info.free_slots <= 0 ? "text-red-400" : "text-stone-400"}`}
                            >
                                {forge_info.free_slots <= 0
                                    ? "Inventory Full!"
                                    : `${forge_info.free_slots} slots free`}
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
                    {/* Smithing - only on desktop (mobile version above) */}
                    <div className="hidden rounded-lg border border-stone-700 bg-stone-800/50 p-3 sm:block">
                        <div className="mb-1 flex items-center justify-between">
                            <div className="flex items-center gap-1 font-pixel text-xs text-orange-400">
                                <Flame className="h-3 w-3 shrink-0" />
                                <span>Smithing</span>
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

                {/* Biome Attunement Banner */}
                {forge_info.biome_attunement && forge_info.biome_bonus > 0 && (
                    <div className="mb-4 rounded-lg border border-purple-600/50 bg-purple-900/20 p-3">
                        <div className="flex items-center justify-between">
                            <div className="flex items-center gap-2">
                                <div className="rounded-md bg-purple-800/50 p-1.5">
                                    {forge_info.biome_attunement.biome === "plains" && (
                                        <Wheat className="h-4 w-4 text-green-400" />
                                    )}
                                    {forge_info.biome_attunement.biome === "tundra" && (
                                        <Mountain className="h-4 w-4 text-blue-400" />
                                    )}
                                    {forge_info.biome_attunement.biome === "coastal" && (
                                        <Waves className="h-4 w-4 text-cyan-400" />
                                    )}
                                    {forge_info.biome_attunement.biome === "volcano" && (
                                        <Flame className="h-4 w-4 text-orange-400" />
                                    )}
                                </div>
                                <div>
                                    <div className="flex items-center gap-1">
                                        <span className="font-pixel text-xs capitalize text-purple-300">
                                            {forge_info.biome_attunement.biome} Attunement
                                        </span>
                                        <Dialog>
                                            <DialogTrigger asChild>
                                                <button className="text-purple-400 hover:text-purple-300">
                                                    <Info className="h-3 w-3" />
                                                </button>
                                            </DialogTrigger>
                                            <DialogContent className="border-purple-600/50 bg-stone-900">
                                                <DialogHeader>
                                                    <DialogTitle className="font-pixel capitalize text-purple-300">
                                                        {forge_info.biome_attunement.biome}{" "}
                                                        Attunement
                                                    </DialogTitle>
                                                    <DialogDescription className="text-stone-400">
                                                        {
                                                            forge_info.biome_attunement
                                                                .biome_description
                                                        }
                                                    </DialogDescription>
                                                </DialogHeader>
                                                <div className="space-y-3 text-sm text-stone-300">
                                                    <p>
                                                        The longer you stay in a kingdom, the more
                                                        attuned you become to its land. This grants
                                                        bonus XP for certain skills.
                                                    </p>
                                                    <div className="rounded-lg bg-stone-800/50 p-3">
                                                        <div className="font-pixel text-xs text-purple-400 mb-2">
                                                            Attunement Levels
                                                        </div>
                                                        <div className="space-y-1 text-xs">
                                                            <div className="flex justify-between">
                                                                <span>Level 1 (30 minutes)</span>
                                                                <span className="text-purple-300">
                                                                    +10% XP
                                                                </span>
                                                            </div>
                                                            <div className="flex justify-between">
                                                                <span>Level 2 (2 hours)</span>
                                                                <span className="text-purple-300">
                                                                    +20% XP
                                                                </span>
                                                            </div>
                                                            <div className="flex justify-between">
                                                                <span>Level 3 (4 hours)</span>
                                                                <span className="text-purple-300">
                                                                    +30% XP
                                                                </span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <p className="text-xs text-stone-500">
                                                        Attunement resets when you travel to a
                                                        different kingdom.
                                                    </p>
                                                </div>
                                            </DialogContent>
                                        </Dialog>
                                    </div>
                                    <div className="flex items-center gap-1">
                                        {[1, 2, 3].map((lvl) => (
                                            <div
                                                key={lvl}
                                                className={`h-1.5 w-4 rounded-full ${
                                                    lvl <=
                                                    forge_info.biome_attunement!.attunement_level
                                                        ? "bg-purple-400"
                                                        : "bg-stone-700"
                                                }`}
                                            />
                                        ))}
                                        <span className="ml-1 font-pixel text-[10px] text-stone-400">
                                            Lvl {forge_info.biome_attunement.attunement_level}
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div className="text-right">
                                <div className="font-pixel text-sm text-purple-300">
                                    +{forge_info.biome_bonus}% XP
                                </div>
                                {forge_info.biome_attunement.next_level && (
                                    <div className="font-pixel text-[10px] text-stone-500">
                                        +{forge_info.biome_attunement.next_level.bonus}% in{" "}
                                        {formatTimeRemaining(
                                            forge_info.biome_attunement.next_level.hours_remaining,
                                        )}
                                    </div>
                                )}
                            </div>
                        </div>
                    </div>
                )}

                {/* Inventory Full Warning */}
                {forge_info.free_slots <= 0 && (
                    <div className="mb-4 rounded-lg border border-red-600/50 bg-red-900/30 p-3 flex items-center gap-3">
                        <Backpack className="h-5 w-5 text-red-400 shrink-0" />
                        <div>
                            <div className="font-pixel text-sm text-red-300">Inventory Full</div>
                            <div className="font-pixel text-xs text-red-400/80">
                                You need at least 1 free inventory slot to smelt bars. Sell or drop
                                some items first.
                            </div>
                        </div>
                    </div>
                )}

                {/* Queue Controls */}
                {effectiveSelected && (
                    <div className="mb-4 rounded-lg border border-orange-600/50 bg-stone-800/50 p-3">
                        <div className="mb-2 font-pixel text-xs text-orange-300">
                            {effectiveSelected.name}
                        </div>
                        <ActionQueueControls
                            isQueueActive={isQueueActive}
                            queueProgress={queueProgress}
                            isActionLoading={isActionLoading}
                            cooldown={cooldown}
                            cooldownMs={SMELT_COOLDOWN_MS}
                            onStart={startQueue}
                            onCancel={cancelQueue}
                            onSingle={performSingleAction}
                            disabled={!effectiveSelected.can_make || isGloballyLocked}
                            actionLabel="Smelt"
                            activeLabel="Smelting"
                            totalXp={totalXp}
                            startedAt={queueStartedAt}
                            buttonClassName="bg-orange-600 text-stone-900 hover:bg-orange-500"
                        />
                    </div>
                )}

                {!selectedRecipe && allRecipes.length > 0 && (
                    <div className="mb-4 rounded-lg border border-stone-600 bg-stone-800/30 p-3 text-center font-pixel text-xs text-stone-400">
                        Select a recipe below to smelt
                    </div>
                )}

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
                                isSelected={selectedRecipe === recipe.id}
                                onSelect={setSelectedRecipe}
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
