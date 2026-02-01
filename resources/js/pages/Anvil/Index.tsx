import { Head, router, usePage } from "@inertiajs/react";
import {
    Anvil,
    ArrowUp,
    Backpack,
    ChevronDown,
    ChevronRight,
    Loader2,
    Lock,
    Package,
    Shield,
    Swords,
    Target,
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

interface RecipesByCategory {
    weapons: Recipe[];
    armor: Recipe[];
    ammunition: Recipe[];
}

interface BarInventory {
    name: string;
    quantity: number;
    metal: string;
}

interface AnvilInfo {
    can_smith: boolean;
    metal_tiers: Record<string, MetalTier>;
    recipes_by_tier: Record<string, RecipesByCategory>;
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
    anvil_info: AnvilInfo;
    location?: Location;
    [key: string]: unknown;
}

const getBreadcrumbs = (location?: Location): BreadcrumbItem[] => [
    { title: "Dashboard", href: "/dashboard" },
    ...(location ? [{ title: location.name, href: `/${location.type}s/${location.id}` }] : []),
    {
        title: "Anvil",
        href: location ? `/${location.type}s/${location.id}/anvil` : "/anvil",
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

function RecipeCard({
    recipe,
    onSmith,
    loading,
    compact = false,
    metalColor,
}: {
    recipe: Recipe;
    onSmith: (id: string) => void;
    loading: string | null;
    compact?: boolean;
    metalColor?: { bg: string; border: string; text: string; glow: string };
}) {
    const isLoading = loading === recipe.id;
    const itemName = recipe.name.split(" ").slice(1).join(" "); // Remove metal prefix
    const hasEnoughBars = (recipe.materials[0]?.have ?? 0) >= (recipe.materials[0]?.required ?? 1);
    const colors = metalColor || metalColors.Bronze;
    const canSmith = recipe.can_make && !recipe.is_locked && loading === null;

    return (
        <button
            onClick={() => canSmith && onSmith(recipe.id)}
            disabled={!canSmith}
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
            <div className="mb-4 flex items-center justify-between">
                <h4
                    className={`text-lg font-semibold ${recipe.is_locked ? "text-stone-500" : colors.text}`}
                >
                    {compact ? itemName : recipe.name}
                </h4>
                {recipe.is_locked ? (
                    <div className="flex items-center gap-1.5 rounded-lg bg-stone-800 px-2 py-1">
                        <Lock className="h-4 w-4 text-stone-500" />
                        <span className="text-sm text-stone-500">Lvl {recipe.required_level}</span>
                    </div>
                ) : canSmith ? (
                    <div className="flex items-center gap-1.5 rounded-lg bg-amber-600/20 px-3 py-1 transition-colors group-hover:bg-amber-600/40">
                        <Anvil className="h-4 w-4 text-amber-400" />
                        <span className="text-sm font-medium text-amber-400">Smith</span>
                    </div>
                ) : null}
            </div>

            {/* Stats grid */}
            <div className="grid grid-cols-3 gap-2">
                <div className="flex items-center gap-3 rounded-lg bg-yellow-900/30 p-3">
                    <Zap className="h-6 w-6 text-yellow-400" />
                    <div>
                        <div className="text-lg font-bold text-yellow-300">
                            {recipe.energy_cost}
                        </div>
                        <div className="text-xs text-yellow-500/80">Energy</div>
                    </div>
                </div>
                <div className="flex items-center gap-3 rounded-lg bg-green-900/30 p-3">
                    <ArrowUp className="h-6 w-6 text-green-400" />
                    <div>
                        <div className="text-lg font-bold text-green-300">+{recipe.xp_reward}</div>
                        <div className="text-xs text-green-500/80">XP</div>
                    </div>
                </div>
                <div
                    className={`flex items-center gap-3 rounded-lg p-3 ${hasEnoughBars ? "bg-blue-900/30" : "bg-red-900/30"}`}
                >
                    <Package
                        className={`h-6 w-6 ${hasEnoughBars ? "text-blue-400" : "text-red-400"}`}
                    />
                    <div>
                        <div
                            className={`text-lg font-bold ${hasEnoughBars ? "text-blue-300" : "text-red-300"}`}
                        >
                            {recipe.materials[0]?.have ?? 0}/{recipe.materials[0]?.required ?? 0}
                        </div>
                        <div
                            className={`text-xs ${hasEnoughBars ? "text-blue-500/80" : "text-red-500/80"}`}
                        >
                            Bars
                        </div>
                    </div>
                </div>
            </div>
        </button>
    );
}

function MetalTierSection({
    metal,
    tier,
    recipes,
    onSmith,
    loading,
    defaultExpanded = false,
}: {
    metal: string;
    tier: MetalTier;
    recipes: RecipesByCategory;
    onSmith: (id: string) => void;
    loading: string | null;
    defaultExpanded?: boolean;
}) {
    const [expanded, setExpanded] = useState(defaultExpanded);
    const colors = metalColors[metal] || metalColors.Bronze;

    const totalRecipes = recipes.weapons.length + recipes.armor.length + recipes.ammunition.length;
    const availableRecipes = [...recipes.weapons, ...recipes.armor, ...recipes.ammunition].filter(
        (r) => r.can_make,
    ).length;

    return (
        <div
            className={`rounded-lg border ${colors.border} ${colors.bg} overflow-hidden transition-shadow ${tier.unlocked ? `hover:shadow-lg ${colors.glow}` : "opacity-60"}`}
        >
            {/* Header */}
            <button
                onClick={() => tier.unlocked && setExpanded(!expanded)}
                disabled={!tier.unlocked}
                className={`flex w-full items-center justify-between px-4 py-3 ${tier.unlocked ? "cursor-pointer" : "cursor-not-allowed"}`}
            >
                <div className="flex items-center gap-3">
                    <div className={`rounded-lg ${colors.bg} p-2`}>
                        <Anvil className={`h-5 w-5 ${colors.text}`} />
                    </div>
                    <div className="text-left">
                        <h3 className={`font-pixel text-sm ${colors.text}`}>{metal}</h3>
                        <p className="font-pixel text-[10px] text-stone-500">
                            Level {tier.base_level}+ Required
                        </p>
                    </div>
                </div>

                <div className="flex items-center gap-3">
                    {tier.unlocked ? (
                        <>
                            <div className="text-right">
                                <span className="font-pixel text-xs text-stone-400">
                                    {availableRecipes}/{totalRecipes}
                                </span>
                                <p className="font-pixel text-[10px] text-stone-500">available</p>
                            </div>
                            {expanded ? (
                                <ChevronDown className={`h-4 w-4 ${colors.text}`} />
                            ) : (
                                <ChevronRight className={`h-4 w-4 ${colors.text}`} />
                            )}
                        </>
                    ) : (
                        <Lock className="h-4 w-4 text-stone-500" />
                    )}
                </div>
            </button>

            {/* Content */}
            {expanded && tier.unlocked && (
                <div className="border-t border-stone-700/50 px-4 py-4">
                    {/* Weapons */}
                    {recipes.weapons.length > 0 && (
                        <div className="mb-6">
                            <div className="mb-3 flex items-center gap-2">
                                <Swords className="h-4 w-4 text-red-400" />
                                <span className="text-sm font-medium text-stone-300">Weapons</span>
                                <span className="text-xs text-stone-500">
                                    ({recipes.weapons.length})
                                </span>
                            </div>
                            <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                                {recipes.weapons.map((recipe) => (
                                    <RecipeCard
                                        key={recipe.id}
                                        recipe={recipe}
                                        onSmith={onSmith}
                                        loading={loading}
                                        compact
                                        metalColor={colors}
                                    />
                                ))}
                            </div>
                        </div>
                    )}

                    {/* Armor */}
                    {recipes.armor.length > 0 && (
                        <div className="mb-6">
                            <div className="mb-3 flex items-center gap-2">
                                <Shield className="h-4 w-4 text-blue-400" />
                                <span className="text-sm font-medium text-stone-300">Armor</span>
                                <span className="text-xs text-stone-500">
                                    ({recipes.armor.length})
                                </span>
                            </div>
                            <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                                {recipes.armor.map((recipe) => (
                                    <RecipeCard
                                        key={recipe.id}
                                        recipe={recipe}
                                        onSmith={onSmith}
                                        loading={loading}
                                        compact
                                        metalColor={colors}
                                    />
                                ))}
                            </div>
                        </div>
                    )}

                    {/* Ammunition */}
                    {recipes.ammunition.length > 0 && (
                        <div>
                            <div className="mb-3 flex items-center gap-2">
                                <Target className="h-4 w-4 text-green-400" />
                                <span className="text-sm font-medium text-stone-300">
                                    Ammunition
                                </span>
                                <span className="text-xs text-stone-500">
                                    ({recipes.ammunition.length})
                                </span>
                            </div>
                            <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                                {recipes.ammunition.map((recipe) => (
                                    <RecipeCard
                                        key={recipe.id}
                                        recipe={recipe}
                                        onSmith={onSmith}
                                        loading={loading}
                                        compact
                                        metalColor={colors}
                                    />
                                ))}
                            </div>
                        </div>
                    )}
                </div>
            )}
        </div>
    );
}

export default function AnvilIndex() {
    const { anvil_info, location } = usePage<PageProps>().props;
    const [loading, setLoading] = useState<string | null>(null);
    const [currentEnergy, setCurrentEnergy] = useState(anvil_info.player_energy);

    const smithUrl = location ? `/${location.type}s/${location.id}/anvil/smith` : "/anvil/smith";

    const handleSmith = (recipeId: string) => {
        setLoading(recipeId);

        router.post(
            smithUrl,
            { recipe: recipeId },
            {
                preserveScroll: true,
                onSuccess: () => {
                    router.reload({ only: ["anvil_info", "sidebar"] });
                },
                onFinish: () => {
                    setLoading(null);
                },
            },
        );
    };

    const metalOrder = ["Bronze", "Iron", "Steel", "Mithril", "Celestial", "Oria"];

    return (
        <AppLayout breadcrumbs={getBreadcrumbs(location)}>
            <Head title="Anvil" />
            <div className="flex h-full flex-1 flex-col p-4">
                {/* Header */}
                <div className="mb-4 flex items-center gap-3">
                    <div className="rounded-lg bg-amber-900/30 p-3">
                        <Anvil className="h-8 w-8 text-amber-400" />
                    </div>
                    <div>
                        <h1 className="font-pixel text-2xl text-amber-400">The Anvil</h1>
                        <p className="font-pixel text-xs text-stone-400">
                            Smith weapons and armor from metal bars
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
                                    width: `${(currentEnergy / anvil_info.max_energy) * 100}%`,
                                }}
                            />
                        </div>
                        <div className="mt-1 font-pixel text-[10px] text-stone-400">
                            {currentEnergy} / {anvil_info.max_energy}
                        </div>
                    </div>
                    <div className="rounded-lg border border-stone-700 bg-stone-800/50 p-3">
                        <div className="mb-1 flex items-center justify-between">
                            <div className="flex items-center gap-1 font-pixel text-xs text-amber-300">
                                <Backpack className="h-3 w-3" />
                                Bars Available
                            </div>
                            <span className="font-pixel text-xs text-stone-400">
                                {anvil_info.free_slots} slots
                            </span>
                        </div>
                        {anvil_info.bars_in_inventory.length > 0 ? (
                            <div className="mt-1 flex flex-wrap gap-1.5">
                                {anvil_info.bars_in_inventory.map((bar) => {
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
                                No bars - smelt some at the Forge first
                            </div>
                        )}
                    </div>
                    <div className="rounded-lg border border-stone-700 bg-stone-800/50 p-3">
                        <div className="mb-1 flex items-center justify-between">
                            <div className="flex items-center gap-1 font-pixel text-xs text-amber-400">
                                <Anvil className="h-3 w-3" />
                                Smithing
                            </div>
                            <span className="font-pixel text-xs text-stone-300">
                                {anvil_info.smithing_level}/99
                            </span>
                        </div>
                        <div className="h-2 w-full overflow-hidden rounded-full bg-stone-700">
                            <div
                                className="h-full bg-gradient-to-r from-amber-600 to-amber-400 transition-all"
                                style={{
                                    width: `${anvil_info.smithing_xp_progress}%`,
                                }}
                            />
                        </div>
                        <div className="mt-1 font-pixel text-[10px] text-stone-400">
                            {anvil_info.smithing_xp_to_next.toLocaleString()} XP to next level
                        </div>
                    </div>
                </div>

                {/* Metal Tiers */}
                <div className="space-y-3">
                    {metalOrder.map((metal, index) => {
                        const tier = anvil_info.metal_tiers[metal];
                        const recipes = anvil_info.recipes_by_tier[metal];

                        if (!tier || !recipes) return null;

                        return (
                            <MetalTierSection
                                key={metal}
                                metal={metal}
                                tier={tier}
                                recipes={recipes}
                                onSmith={handleSmith}
                                loading={loading}
                                defaultExpanded={index === 0 && tier.unlocked}
                            />
                        );
                    })}
                </div>
            </div>
        </AppLayout>
    );
}
