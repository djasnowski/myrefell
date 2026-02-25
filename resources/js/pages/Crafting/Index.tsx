import { Head, router, usePage } from "@inertiajs/react";
import { ArrowRight, Backpack, Lock, Scissors, X, Zap } from "lucide-react";
import { useCallback, useEffect, useState } from "react";

const CRAFT_COOLDOWN_MS = 3000;
import AppLayout from "@/layouts/app-layout";
import { ActionQueueControls } from "@/components/action-queue-controls";
import { InventorySummary } from "@/components/inventory-summary";
import { gameToast } from "@/components/ui/game-toast";
import {
    useActionQueue,
    getActionVerb,
    type ActionResult,
    type QueueStats,
} from "@/hooks/use-action-queue";
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

interface CraftingInfo {
    can_craft: boolean;
    recipes: Record<string, Recipe[]>;
    all_recipes: Record<string, Recipe[]>;
    player_energy: number;
    max_energy: number;
    free_slots: number;
    crafting_level: number;
    crafting_xp: number;
    crafting_xp_progress: number;
    crafting_xp_to_next: number;
    inventory_summary: { name: string; quantity: number }[];
}

interface CraftResult {
    success: boolean;
    message: string;
    item?: { name: string; quantity: number };
    xp_awarded?: number;
    skill?: string;
    leveled_up?: boolean;
    new_level?: number;
    energy_remaining?: number;
}

interface Location {
    type: string;
    id: number;
    name: string;
}

interface PageProps {
    crafting_info: CraftingInfo;
    location?: Location;
    [key: string]: unknown;
}

const getBreadcrumbs = (location?: Location): BreadcrumbItem[] => {
    const baseUrl = location ? locationPath(location.type, location.id) : null;
    return [
        { title: "Dashboard", href: "/dashboard" },
        ...(location && baseUrl ? [{ title: location.name, href: baseUrl }] : []),
        {
            title: "Crafting",
            href: baseUrl ? `${baseUrl}/crafting` : "/crafting",
        },
    ];
};

function RecipeCard({
    recipe,
    isSelected,
    onSelect,
    stats,
}: {
    recipe: Recipe;
    isSelected: boolean;
    onSelect: (id: string) => void;
    stats?: { xp: number; items: number };
}) {
    return (
        <button
            onClick={() => !recipe.is_locked && onSelect(recipe.id)}
            className={`w-full rounded-lg border p-3 text-left transition ${
                recipe.is_locked
                    ? "cursor-not-allowed border-stone-700 bg-stone-800/30 opacity-60"
                    : isSelected
                      ? "border-amber-400 bg-amber-900/30 ring-1 ring-amber-400/50"
                      : recipe.can_make
                        ? "border-amber-600/50 bg-stone-800/50 hover:border-amber-500/70"
                        : "border-stone-700 bg-stone-800/50 hover:border-stone-600"
            }`}
        >
            <div className="mb-3 flex items-center justify-between">
                <div className="flex items-center gap-2">
                    <Scissors className="h-4 w-4 text-stone-400" />
                    <span className="font-pixel text-sm text-amber-300">{recipe.name}</span>
                </div>
                {recipe.is_locked && <Lock className="h-4 w-4 text-stone-500" />}
            </div>

            {/* Materials */}
            <div className="mb-3 space-y-1">
                {recipe.materials.map((material, idx) => (
                    <div key={idx} className="flex items-center justify-between text-stone-400">
                        <span className="font-pixel text-[10px]">{material.name}</span>
                        <span
                            className={`font-pixel text-[10px] ${material.has_enough ? "text-green-400" : "text-red-400"}`}
                        >
                            {material.have}/{material.required}
                        </span>
                    </div>
                ))}
            </div>

            {/* Output */}
            <div className="mb-3 flex items-center gap-2 rounded bg-stone-900/50 px-2 py-1">
                <ArrowRight className="h-3 w-3 text-amber-400" />
                <span className="font-pixel text-xs text-stone-300">
                    {recipe.output.quantity}x {recipe.output.name}
                </span>
            </div>

            {/* Stats Row */}
            <div className="flex items-center justify-between text-stone-500">
                <span className="flex items-center gap-1 font-pixel text-[10px]">
                    <Zap className="h-3 w-3 text-yellow-500" />
                    {recipe.energy_cost}
                </span>
                <span className="font-pixel text-[10px] text-amber-400">
                    +{recipe.xp_reward} XP
                </span>
            </div>

            {/* Locked message */}
            {recipe.is_locked && (
                <div className="mt-3 rounded-md bg-stone-900/50 px-3 py-2 text-center">
                    <span className="font-pixel text-[10px] text-stone-500">
                        Requires Level {recipe.required_level} {recipe.skill}
                    </span>
                </div>
            )}

            {/* Can't make message */}
            {!recipe.is_locked && !recipe.can_make && (
                <div className="mt-3 flex items-center justify-center gap-1 font-pixel text-[10px] text-stone-500">
                    <X className="h-3 w-3" />
                    Missing Materials
                </div>
            )}

            {/* Session stats */}
            {stats && (
                <div className="mt-3 border-t border-stone-700/50 pt-2 font-pixel text-[8px] text-green-400">
                    ×{stats.items} crafted · {stats.xp} XP
                </div>
            )}
        </button>
    );
}

export default function CraftingIndex() {
    const { crafting_info, location } = usePage<PageProps>().props;
    const [currentEnergy, setCurrentEnergy] = useState(crafting_info.player_energy);
    const [selectedRecipe, setSelectedRecipe] = useState<string | null>(null);
    const [sessionStats, setSessionStats] = useState<Record<string, { xp: number; items: number }>>(
        {},
    );

    // Build the craft URL based on location
    const craftUrl = location
        ? `${locationPath(location.type, location.id)}/crafting/craft`
        : "/crafting/craft";

    const buildBody = useCallback(() => ({ recipe: selectedRecipe }), [selectedRecipe]);

    const onActionComplete = useCallback((data: ActionResult) => {
        if (data.success && data.energy_remaining !== undefined) {
            setCurrentEnergy(data.energy_remaining);
        }
        if (data.success && data.item) {
            const name = data.item.name;
            const xp = data.xp_awarded ?? 0;
            const qty = data.item.quantity ?? 1;
            setSessionStats((prev) => ({
                ...prev,
                [name]: {
                    xp: (prev[name]?.xp ?? 0) + xp,
                    items: (prev[name]?.items ?? 0) + qty,
                },
            }));
        }
    }, []);

    const onQueueComplete = useCallback((stats: QueueStats) => {
        if (stats.completed === 0) return;

        const verb = getActionVerb(stats.actionType);
        if (stats.completed === 1 && stats.itemName) {
            gameToast.success(`${verb} ${stats.totalQuantity}x ${stats.itemName}`, {
                xp: stats.totalXp,
                levelUp: stats.lastLevelUp,
            });
        } else if (stats.completed > 1) {
            const qty = stats.totalQuantity > 0 ? `${stats.totalQuantity}x ` : "";
            gameToast.success(
                `${verb} ${qty}${stats.itemName ?? "items"} (${stats.completed} actions)`,
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
        url: craftUrl,
        buildBody,
        cooldownMs: CRAFT_COOLDOWN_MS,
        onActionComplete,
        onQueueComplete,
        reloadProps: ["crafting_info", "sidebar"],
        actionType: "craft",
        buildActionParams,
    });

    useEffect(() => {
        // Reload fresh data on mount to avoid stale cache from Inertia navigation
        router.reload({ only: ["crafting_info"] });
    }, []);

    // Combine all recipes for display
    const allRecipes = Object.entries(crafting_info.all_recipes).flatMap(([, recipes]) => recipes);

    // Auto-select first craftable recipe if none selected
    const selected = allRecipes.find((r) => r.id === selectedRecipe);
    const effectiveSelected = selected && !selected.is_locked ? selected : null;

    return (
        <AppLayout breadcrumbs={getBreadcrumbs(location)}>
            <Head title="Crafting" />
            <div className="flex h-full flex-1 flex-col p-4">
                {/* Header */}
                <div className="mb-4 flex items-center gap-3">
                    <div className="rounded-lg bg-amber-900/30 p-3">
                        <Scissors className="h-8 w-8 text-amber-400" />
                    </div>
                    <div>
                        <h1 className="font-pixel text-2xl text-amber-400">Crafting</h1>
                        <p className="font-pixel text-xs text-stone-400">
                            Create items from raw materials
                        </p>
                    </div>
                </div>

                {/* Status Bar */}
                <div className="mb-4 grid grid-cols-3 gap-2 sm:gap-4">
                    <div className="rounded-lg border border-stone-700 bg-stone-800/50 p-2 sm:p-3">
                        <div className="mb-1 flex items-center gap-1 font-pixel text-[10px] text-yellow-400 sm:text-xs">
                            <Zap className="h-3 w-3 shrink-0" />
                            <span className="truncate">Energy</span>
                        </div>
                        <div className="h-2 w-full overflow-hidden rounded-full bg-stone-700">
                            <div
                                className="h-full bg-gradient-to-r from-yellow-600 to-yellow-400 transition-all"
                                style={{
                                    width: `${(currentEnergy / crafting_info.max_energy) * 100}%`,
                                }}
                            />
                        </div>
                        <div className="mt-1 font-pixel text-[9px] text-stone-400 sm:text-[10px]">
                            {currentEnergy} / {crafting_info.max_energy}
                        </div>
                    </div>
                    <div className="rounded-lg border border-stone-700 bg-stone-800/50 p-2 sm:p-3">
                        <div className="mb-1 flex items-center gap-1 font-pixel text-[10px] text-amber-300 sm:text-xs">
                            <Backpack className="h-3 w-3 shrink-0" />
                            <span className="truncate">Inventory</span>
                        </div>
                        <div className="font-pixel text-base text-stone-300 sm:text-lg">
                            {crafting_info.free_slots}{" "}
                            <span className="text-[10px] text-stone-500 sm:text-xs">slots</span>
                        </div>
                    </div>
                    <div className="rounded-lg border border-stone-700 bg-stone-800/50 p-2 sm:p-3">
                        <div className="mb-1 flex items-center justify-between gap-1">
                            <div className="flex min-w-0 items-center gap-1 font-pixel text-[10px] text-amber-400 sm:text-xs">
                                <Scissors className="h-3 w-3 shrink-0" />
                                <span className="truncate">Crafting</span>
                            </div>
                            <span className="shrink-0 font-pixel text-[10px] text-stone-300 sm:text-xs">
                                {crafting_info.crafting_level}
                            </span>
                        </div>
                        <div className="h-2 w-full overflow-hidden rounded-full bg-stone-700">
                            <div
                                className="h-full bg-gradient-to-r from-amber-600 to-amber-400 transition-all"
                                style={{
                                    width: `${crafting_info.crafting_xp_progress}%`,
                                }}
                            />
                        </div>
                        <div className="mt-1 font-pixel text-[9px] text-stone-400 sm:text-[10px]">
                            {crafting_info.crafting_xp_to_next.toLocaleString()} XP to next level
                        </div>
                    </div>
                </div>

                <InventorySummary items={crafting_info.inventory_summary} />

                {/* Queue Controls */}
                {effectiveSelected && (
                    <div className="mb-4 rounded-lg border border-amber-600/50 bg-stone-800/50 p-3">
                        <div className="mb-2 font-pixel text-xs text-amber-300">
                            {effectiveSelected.name}
                        </div>
                        <ActionQueueControls
                            isQueueActive={isQueueActive}
                            queueProgress={queueProgress}
                            isActionLoading={isActionLoading}
                            cooldown={cooldown}
                            cooldownMs={CRAFT_COOLDOWN_MS}
                            onStart={startQueue}
                            onCancel={cancelQueue}
                            onSingle={performSingleAction}
                            disabled={!effectiveSelected.can_make || isGloballyLocked}
                            actionLabel="Craft"
                            activeLabel="Crafting"
                            totalXp={totalXp}
                            startedAt={queueStartedAt}
                        />
                    </div>
                )}

                {!selectedRecipe && allRecipes.length > 0 && (
                    <div className="mb-4 rounded-lg border border-stone-600 bg-stone-800/30 p-3 text-center font-pixel text-xs text-stone-400">
                        Select a recipe below to craft
                    </div>
                )}

                {/* Recipe Grid */}
                <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                    {allRecipes.map((recipe) => (
                        <RecipeCard
                            key={recipe.id}
                            recipe={recipe}
                            isSelected={selectedRecipe === recipe.id}
                            onSelect={setSelectedRecipe}
                            stats={sessionStats[recipe.output.name]}
                        />
                    ))}
                </div>

                {allRecipes.length === 0 && (
                    <div className="flex flex-1 items-center justify-center">
                        <div className="text-center">
                            <Scissors className="mx-auto mb-3 h-12 w-12 text-stone-600" />
                            <p className="font-pixel text-sm text-stone-500">
                                No recipes available
                            </p>
                        </div>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
