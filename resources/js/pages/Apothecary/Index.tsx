import { Head, router, usePage } from "@inertiajs/react";
import {
    ArrowRight,
    Backpack,
    FlaskConical,
    Heart,
    Leaf,
    Lock,
    Shield,
    Sparkles,
    Sword,
    X,
    Zap,
} from "lucide-react";
import { useCallback, useEffect, useState } from "react";
import { ActionQueueControls } from "@/components/action-queue-controls";
import {
    type ActionResult,
    type QueueStats,
    getActionVerb,
    useActionQueue,
} from "@/hooks/use-action-queue";
import { gameToast } from "@/components/ui/game-toast";

const BREW_COOLDOWN_MS = 3000;
import AppLayout from "@/layouts/app-layout";
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

interface IngredientInventory {
    name: string;
    quantity: number;
    type: "herb" | "vial" | "monster" | "potion";
}

interface BrewingInfo {
    can_brew: boolean;
    recipes: Record<string, Recipe[]>;
    all_recipes: Record<string, Recipe[]>;
    player_energy: number;
    max_energy: number;
    free_slots: number;
    herblore_level: number;
    herblore_xp: number;
    herblore_xp_progress: number;
    herblore_xp_to_next: number;
    herbs_in_inventory: IngredientInventory[];
}

interface Location {
    type: string;
    id: number;
    name: string;
}

interface PageProps {
    brewing_info: BrewingInfo;
    location?: Location;
    [key: string]: unknown;
}

const getBreadcrumbs = (location?: Location): BreadcrumbItem[] => {
    const baseUrl = location ? locationPath(location.type, location.id) : null;
    return [
        { title: "Dashboard", href: "/dashboard" },
        ...(location && baseUrl ? [{ title: location.name, href: baseUrl }] : []),
        {
            title: "Apothecary",
            href: baseUrl ? `${baseUrl}/apothecary` : "/apothecary",
        },
    ];
};

const categoryIcons: Record<string, typeof FlaskConical> = {
    restoration: Heart,
    combat: Sword,
    spiritual: Sparkles,
};

const categoryLabels: Record<string, string> = {
    restoration: "Restoration",
    combat: "Combat",
    spiritual: "Spiritual",
};

const categoryColors: Record<string, string> = {
    restoration: "text-green-400",
    combat: "text-red-400",
    spiritual: "text-purple-400",
};

function RecipeCard({
    recipe,
    isSelected,
    onSelect,
}: {
    recipe: Recipe;
    isSelected: boolean;
    onSelect: (id: string) => void;
}) {
    const CategoryIcon = categoryIcons[recipe.category] || FlaskConical;
    const categoryColor = categoryColors[recipe.category] || "text-stone-400";
    const canSelect = !recipe.is_locked;

    return (
        <button
            onClick={() => canSelect && onSelect(recipe.id)}
            disabled={!canSelect}
            className={`w-full rounded-lg border p-3 text-left transition ${
                recipe.is_locked
                    ? "cursor-not-allowed border-stone-700 bg-stone-800/30 opacity-60"
                    : isSelected
                      ? "border-emerald-400 bg-stone-800/50 ring-2 ring-emerald-400/30 shadow-lg shadow-emerald-500/10"
                      : recipe.can_make
                        ? "cursor-pointer border-emerald-600/50 bg-stone-800/50 hover:border-emerald-500/70"
                        : "cursor-pointer border-stone-700 bg-stone-800/50 hover:border-stone-600"
            }`}
        >
            <div className="mb-3 flex items-center justify-between">
                <div className="flex items-center gap-2">
                    <CategoryIcon className={`h-4 w-4 ${categoryColor}`} />
                    <span
                        className={`font-pixel text-sm ${isSelected ? "text-emerald-200" : "text-emerald-300"}`}
                    >
                        {recipe.name}
                    </span>
                </div>
                {recipe.is_locked ? (
                    <Lock className="h-4 w-4 text-stone-500" />
                ) : isSelected ? (
                    <span className="font-pixel text-[10px] text-emerald-300">Selected</span>
                ) : null}
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
                <ArrowRight className="h-3 w-3 text-emerald-400" />
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
                <span className="font-pixel text-[10px] text-emerald-400">
                    +{recipe.xp_reward} XP
                </span>
            </div>

            {/* Locked message */}
            {recipe.is_locked && (
                <div className="mt-3 rounded-md bg-stone-900/50 px-3 py-2 text-center">
                    <span className="font-pixel text-[10px] text-stone-500">
                        Requires Level {recipe.required_level} Herblore
                    </span>
                </div>
            )}

            {/* Can't make indicator */}
            {!recipe.is_locked && !recipe.can_make && !isSelected && (
                <div className="mt-3 flex items-center justify-center gap-1 font-pixel text-[10px] text-stone-500">
                    <X className="h-3 w-3" />
                    Missing Ingredients
                </div>
            )}
        </button>
    );
}

export default function ApothecaryIndex() {
    const { brewing_info, location } = usePage<PageProps>().props;
    const [activeCategory, setActiveCategory] = useState<string>("all");
    const [currentEnergy, setCurrentEnergy] = useState(brewing_info.player_energy);
    const [selectedRecipe, setSelectedRecipe] = useState<string | null>(null);

    const brewUrl = location
        ? `${locationPath(location.type, location.id)}/apothecary/brew`
        : "/apothecary/brew";

    const buildBody = useCallback(() => ({ recipe: selectedRecipe }), [selectedRecipe]);

    const onActionComplete = useCallback((data: ActionResult) => {
        if (data.success && data.energy_remaining !== undefined) {
            setCurrentEnergy(data.energy_remaining);
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
                `${verb} ${qty}${stats.itemName ?? "potions"} (${stats.completed} actions)`,
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
        url: brewUrl,
        buildBody,
        cooldownMs: BREW_COOLDOWN_MS,
        onActionComplete,
        onQueueComplete,
        reloadProps: ["brewing_info", "sidebar"],
        actionType: "brew",
        buildActionParams,
    });

    useEffect(() => {
        // Reload fresh data on mount to avoid stale cache from Inertia navigation
        router.reload({ only: ["brewing_info"] });
    }, []);

    // Find the selected recipe object
    const allRecipes = Object.entries(brewing_info.all_recipes).flatMap(([, recipes]) => recipes);
    const selected = allRecipes.find((r) => r.id === selectedRecipe);
    const effectiveSelected = selected && !selected.is_locked ? selected : null;

    const displayRecipes =
        activeCategory === "all" ? allRecipes : brewing_info.all_recipes[activeCategory] || [];

    const categories = ["all", ...Object.keys(brewing_info.all_recipes)];

    return (
        <AppLayout breadcrumbs={getBreadcrumbs(location)}>
            <Head title="Apothecary" />
            <div className="flex h-full flex-1 flex-col p-4">
                {/* Header */}
                <div className="mb-4 flex items-center gap-3">
                    <div className="rounded-lg bg-emerald-900/30 p-3">
                        <FlaskConical className="h-8 w-8 text-emerald-400" />
                    </div>
                    <div>
                        <h1 className="font-pixel text-2xl text-emerald-400">Apothecary</h1>
                        <p className="font-pixel text-xs text-stone-400">
                            Brew potions and remedies from herbs
                        </p>
                    </div>
                </div>

                {/* Status Bar */}
                <div className="mb-4 space-y-2 sm:space-y-0 sm:grid sm:grid-cols-3 sm:gap-4">
                    {/* Energy and Herblore - 2 columns on mobile, part of 3-col on desktop */}
                    <div className="grid grid-cols-2 gap-2 sm:contents">
                        {/* Energy */}
                        <div className="rounded-lg border border-stone-700 bg-stone-800/50 p-2 sm:p-3">
                            <div className="mb-1 flex items-center gap-1 font-pixel text-[10px] text-yellow-400 sm:text-xs">
                                <Zap className="h-3 w-3 shrink-0" />
                                <span>Energy</span>
                            </div>
                            <div className="h-2 w-full overflow-hidden rounded-full bg-stone-700">
                                <div
                                    className="h-full bg-gradient-to-r from-yellow-600 to-yellow-400 transition-all"
                                    style={{
                                        width: `${(currentEnergy / brewing_info.max_energy) * 100}%`,
                                    }}
                                />
                            </div>
                            <div className="mt-1 font-pixel text-[9px] text-stone-400 sm:text-[10px]">
                                {currentEnergy} / {brewing_info.max_energy}
                            </div>
                        </div>

                        {/* Herblore Skill - mobile version */}
                        <div className="rounded-lg border border-stone-700 bg-stone-800/50 p-2 sm:hidden">
                            <div className="mb-1 flex items-center justify-between gap-1">
                                <div className="flex min-w-0 items-center gap-1 font-pixel text-[10px] text-emerald-400">
                                    <Leaf className="h-3 w-3 shrink-0" />
                                    <span>Herblore</span>
                                </div>
                                <span className="shrink-0 font-pixel text-[10px] text-stone-300">
                                    {brewing_info.herblore_level}
                                </span>
                            </div>
                            <div className="h-2 w-full overflow-hidden rounded-full bg-stone-700">
                                <div
                                    className="h-full bg-gradient-to-r from-emerald-600 to-emerald-400 transition-all"
                                    style={{
                                        width: `${brewing_info.herblore_xp_progress}%`,
                                    }}
                                />
                            </div>
                            <div className="mt-1 font-pixel text-[9px] text-stone-400">
                                {brewing_info.herblore_xp_to_next.toLocaleString()} XP to next
                            </div>
                        </div>
                    </div>

                    {/* Inventory - full width on mobile, middle column on desktop */}
                    <div className="rounded-lg border border-stone-700 bg-stone-800/50 p-2 sm:p-3">
                        <div className="mb-1 flex items-center justify-between">
                            <div className="flex items-center gap-1 font-pixel text-[10px] text-amber-300 sm:text-xs">
                                <Backpack className="h-3 w-3 shrink-0" />
                                <span>Ingredients</span>
                            </div>
                            <span className="font-pixel text-[10px] text-stone-400 sm:text-xs">
                                {brewing_info.free_slots} slots
                            </span>
                        </div>
                        {brewing_info.herbs_in_inventory.length > 0 ? (
                            <div className="mt-1 flex flex-wrap gap-1.5">
                                {brewing_info.herbs_in_inventory.slice(0, 6).map((ingredient) => {
                                    const styles = {
                                        herb: {
                                            border: "border-emerald-600/50",
                                            bg: "bg-emerald-900/30",
                                            text: "text-emerald-300",
                                            icon: "text-emerald-400",
                                        },
                                        vial: {
                                            border: "border-sky-600/50",
                                            bg: "bg-sky-900/30",
                                            text: "text-sky-300",
                                            icon: "text-sky-400",
                                        },
                                        monster: {
                                            border: "border-purple-600/50",
                                            bg: "bg-purple-900/30",
                                            text: "text-purple-300",
                                            icon: "text-purple-400",
                                        },
                                        potion: {
                                            border: "border-red-600/50",
                                            bg: "bg-red-900/30",
                                            text: "text-red-300",
                                            icon: "text-red-400",
                                        },
                                    };
                                    const style = styles[ingredient.type] || styles.herb;
                                    const IngredientIcon =
                                        ingredient.type === "vial" ? FlaskConical : Leaf;
                                    return (
                                        <div
                                            key={ingredient.name}
                                            className={`flex items-center gap-1.5 rounded border ${style.border} ${style.bg} px-2 py-1`}
                                        >
                                            <IngredientIcon className={`h-3 w-3 ${style.icon}`} />
                                            <span
                                                className={`font-pixel text-[10px] ${style.text}`}
                                            >
                                                {ingredient.name.length > 12
                                                    ? ingredient.name.substring(0, 10) + "..."
                                                    : ingredient.name}
                                            </span>
                                            <span className="font-pixel text-[10px] text-stone-400">
                                                ×{ingredient.quantity}
                                            </span>
                                        </div>
                                    );
                                })}
                                {brewing_info.herbs_in_inventory.length > 6 && (
                                    <div className="flex items-center rounded border border-stone-600/50 bg-stone-800/50 px-2 py-1">
                                        <span className="font-pixel text-[10px] text-stone-400">
                                            +{brewing_info.herbs_in_inventory.length - 6} more
                                        </span>
                                    </div>
                                )}
                            </div>
                        ) : (
                            <div className="mt-1 font-pixel text-[10px] text-stone-500">
                                No ingredients
                            </div>
                        )}
                    </div>

                    {/* Herblore Skill - desktop version */}
                    <div className="hidden rounded-lg border border-stone-700 bg-stone-800/50 p-3 sm:block">
                        <div className="mb-1 flex items-center justify-between">
                            <div className="flex items-center gap-1 font-pixel text-xs text-emerald-400">
                                <Leaf className="h-3 w-3 shrink-0" />
                                <span>Herblore</span>
                            </div>
                            <span className="font-pixel text-xs text-stone-300">
                                {brewing_info.herblore_level}/99
                            </span>
                        </div>
                        <div className="h-2 w-full overflow-hidden rounded-full bg-stone-700">
                            <div
                                className="h-full bg-gradient-to-r from-emerald-600 to-emerald-400 transition-all"
                                style={{
                                    width: `${brewing_info.herblore_xp_progress}%`,
                                }}
                            />
                        </div>
                        <div className="mt-1 font-pixel text-[10px] text-stone-400">
                            {brewing_info.herblore_xp_to_next.toLocaleString()} XP to next level
                        </div>
                    </div>
                </div>

                {/* Queue Controls */}
                {effectiveSelected && (
                    <div className="mb-4 rounded-lg border border-emerald-600/50 bg-stone-800/50 p-3">
                        <div className="mb-2 font-pixel text-xs text-emerald-300">
                            {effectiveSelected.name}
                        </div>
                        <ActionQueueControls
                            isQueueActive={isQueueActive}
                            queueProgress={queueProgress}
                            isActionLoading={isActionLoading}
                            cooldown={cooldown}
                            cooldownMs={BREW_COOLDOWN_MS}
                            onStart={startQueue}
                            onCancel={cancelQueue}
                            onSingle={performSingleAction}
                            disabled={!effectiveSelected.can_make || isGloballyLocked}
                            actionLabel="Brew"
                            activeLabel="Brewing"
                            totalXp={totalXp}
                            startedAt={queueStartedAt}
                            buttonClassName="bg-emerald-600 text-stone-900 hover:bg-emerald-500"
                        />
                    </div>
                )}

                {!selectedRecipe && (
                    <div className="mb-4 rounded-lg border border-stone-600 bg-stone-800/30 p-3 text-center font-pixel text-xs text-stone-400">
                        Select a recipe below to brew
                    </div>
                )}

                {/* Category Tabs */}
                <div className="mb-4 flex gap-2 overflow-x-auto">
                    {categories.map((cat) => {
                        const Icon = categoryIcons[cat];
                        const label = cat === "all" ? "All" : categoryLabels[cat] || cat;

                        return (
                            <button
                                key={cat}
                                onClick={() => setActiveCategory(cat)}
                                className={`flex items-center gap-1 whitespace-nowrap rounded-lg px-3 py-1.5 font-pixel text-xs transition ${
                                    activeCategory === cat
                                        ? "bg-emerald-600 text-stone-900"
                                        : "bg-stone-800 text-stone-400 hover:bg-stone-700"
                                }`}
                            >
                                {Icon && <Icon className="h-3 w-3" />}
                                {label}
                            </button>
                        );
                    })}
                </div>

                {/* Recipe Grid */}
                <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                    {displayRecipes.map((recipe) => (
                        <RecipeCard
                            key={recipe.id}
                            recipe={recipe}
                            isSelected={selectedRecipe === recipe.id}
                            onSelect={setSelectedRecipe}
                        />
                    ))}
                </div>

                {displayRecipes.length === 0 && (
                    <div className="flex flex-1 items-center justify-center">
                        <div className="text-center">
                            <FlaskConical className="mx-auto mb-3 h-12 w-12 text-stone-600" />
                            <p className="font-pixel text-sm text-stone-500">
                                No recipes available
                            </p>
                            <p className="font-pixel text-xs text-stone-600">
                                Increase your Herblore level to unlock more
                            </p>
                        </div>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
