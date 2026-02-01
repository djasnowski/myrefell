import { Head, router, usePage } from "@inertiajs/react";
import {
    ArrowRight,
    ArrowUp,
    Backpack,
    Beef,
    Check,
    Loader2,
    Lock,
    Package,
    Scissors,
    X,
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

interface CraftingInfo {
    can_craft: boolean;
    recipes: Record<string, Recipe[]>;
    all_recipes: Record<string, Recipe[]>;
    player_energy: number;
    max_energy: number;
    free_slots: number;
}

interface CraftResult {
    success: boolean;
    message: string;
    item?: { name: string; quantity: number };
    xp_awarded?: number;
    skill?: string;
    leveled_up?: boolean;
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

const getBreadcrumbs = (location?: Location): BreadcrumbItem[] => [
    { title: "Dashboard", href: "/dashboard" },
    ...(location ? [{ title: location.name, href: `/${location.type}s/${location.id}` }] : []),
    {
        title: "Crafting",
        href: location ? `/${location.type}s/${location.id}/crafting` : "/crafting",
    },
];

const categoryIcons: Record<string, typeof Hammer> = {
    cooking: Beef,
    crafting: Scissors,
};

const categoryLabels: Record<string, string> = {
    cooking: "Cooking",
    crafting: "Crafting",
};

function RecipeCard({
    recipe,
    onCraft,
    loading,
}: {
    recipe: Recipe;
    onCraft: (id: string) => void;
    loading: string | null;
}) {
    const isLoading = loading === recipe.id;
    const CategoryIcon = categoryIcons[recipe.category] || Hammer;

    return (
        <div
            className={`rounded-lg border p-3 transition ${
                recipe.is_locked
                    ? "border-stone-700 bg-stone-800/30 opacity-60"
                    : recipe.can_make
                      ? "border-amber-600/50 bg-stone-800/50"
                      : "border-stone-700 bg-stone-800/50"
            }`}
        >
            <div className="mb-3 flex items-center justify-between">
                <div className="flex items-center gap-2">
                    <CategoryIcon className="h-4 w-4 text-stone-400" />
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
            <div className="mb-3 flex items-center justify-between text-stone-500">
                <span className="flex items-center gap-1 font-pixel text-[10px]">
                    <Zap className="h-3 w-3 text-yellow-500" />
                    {recipe.energy_cost}
                </span>
                <span className="font-pixel text-[10px] text-amber-400">
                    +{recipe.xp_reward} XP
                </span>
            </div>

            {/* Craft Button */}
            {recipe.is_locked ? (
                <div className="rounded-md bg-stone-900/50 px-3 py-2 text-center">
                    <span className="font-pixel text-[10px] text-stone-500">
                        Requires Level {recipe.required_level} {recipe.skill}
                    </span>
                </div>
            ) : (
                <button
                    onClick={() => onCraft(recipe.id)}
                    disabled={!recipe.can_make || loading !== null}
                    className={`flex w-full items-center justify-center gap-2 rounded-md px-3 py-2 font-pixel text-xs transition ${
                        recipe.can_make && !loading
                            ? "bg-amber-600 text-stone-900 hover:bg-amber-500"
                            : "cursor-not-allowed bg-stone-700 text-stone-500"
                    }`}
                >
                    {isLoading ? (
                        <>
                            <Loader2 className="h-3 w-3 animate-spin" />
                            Crafting...
                        </>
                    ) : recipe.can_make ? (
                        <>
                            <Check className="h-3 w-3" />
                            Craft
                        </>
                    ) : (
                        <>
                            <X className="h-3 w-3" />
                            Missing Materials
                        </>
                    )}
                </button>
            )}
        </div>
    );
}

export default function CraftingIndex() {
    const { crafting_info, location } = usePage<PageProps>().props;
    const [loading, setLoading] = useState<string | null>(null);
    const [result, setResult] = useState<CraftResult | null>(null);
    const [activeCategory, setActiveCategory] = useState<string>("all");
    const [currentEnergy, setCurrentEnergy] = useState(crafting_info.player_energy);

    // Build the craft URL based on location
    const craftUrl = location
        ? `/${location.type}s/${location.id}/crafting/craft`
        : "/crafting/craft";

    const handleCraft = async (recipeId: string) => {
        setLoading(recipeId);
        setResult(null);

        try {
            const response = await fetch(craftUrl, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN":
                        document
                            .querySelector('meta[name="csrf-token"]')
                            ?.getAttribute("content") || "",
                },
                body: JSON.stringify({ recipe: recipeId }),
            });

            const data: CraftResult = await response.json();
            setResult(data);

            if (data.success && data.energy_remaining !== undefined) {
                setCurrentEnergy(data.energy_remaining);
            }

            // Reload to update materials
            router.reload({ only: ["crafting_info", "sidebar"] });
        } catch {
            setResult({ success: false, message: "An error occurred" });
        } finally {
            setLoading(null);
        }
    };

    // Combine all recipes for display
    const allRecipes = Object.entries(crafting_info.all_recipes).flatMap(([, recipes]) => recipes);
    const displayRecipes =
        activeCategory === "all" ? allRecipes : crafting_info.all_recipes[activeCategory] || [];

    const categories = ["all", ...Object.keys(crafting_info.all_recipes)];

    return (
        <AppLayout breadcrumbs={getBreadcrumbs(location)}>
            <Head title="Crafting" />
            <div className="flex h-full flex-1 flex-col p-4">
                {/* Header */}
                <div className="mb-4 flex items-center gap-3">
                    <div className="rounded-lg bg-amber-900/30 p-3">
                        <Hammer className="h-8 w-8 text-amber-400" />
                    </div>
                    <div>
                        <h1 className="font-pixel text-2xl text-amber-400">Crafting</h1>
                        <p className="font-pixel text-xs text-stone-400">
                            Create items from raw materials
                        </p>
                    </div>
                </div>

                {/* Status Bar */}
                <div className="mb-4 grid grid-cols-2 gap-4">
                    <div className="rounded-lg border border-stone-700 bg-stone-800/50 p-3">
                        <div className="mb-1 flex items-center gap-1 font-pixel text-xs text-yellow-400">
                            <Zap className="h-3 w-3" />
                            Energy
                        </div>
                        <div className="h-2 w-full overflow-hidden rounded-full bg-stone-700">
                            <div
                                className="h-full bg-gradient-to-r from-yellow-600 to-yellow-400 transition-all"
                                style={{
                                    width: `${(currentEnergy / crafting_info.max_energy) * 100}%`,
                                }}
                            />
                        </div>
                        <div className="mt-1 font-pixel text-[10px] text-stone-400">
                            {currentEnergy} / {crafting_info.max_energy}
                        </div>
                    </div>
                    <div className="rounded-lg border border-stone-700 bg-stone-800/50 p-3">
                        <div className="mb-1 flex items-center gap-1 font-pixel text-xs text-amber-300">
                            <Backpack className="h-3 w-3" />
                            Inventory
                        </div>
                        <div className="font-pixel text-lg text-stone-300">
                            {crafting_info.free_slots} slots
                        </div>
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
                            {result.success && result.item && (
                                <>
                                    <Package className="h-6 w-6 text-green-400" />
                                    <div>
                                        <div className="font-pixel text-sm text-green-300">
                                            Crafted {result.item.quantity}x {result.item.name}
                                        </div>
                                        <div className="flex items-center gap-2">
                                            <span className="font-pixel text-[10px] text-amber-400">
                                                +{result.xp_awarded} XP
                                            </span>
                                            {result.leveled_up && (
                                                <span className="flex items-center gap-1 font-pixel text-[10px] text-yellow-300">
                                                    <ArrowUp className="h-3 w-3" />
                                                    Level Up!
                                                </span>
                                            )}
                                        </div>
                                    </div>
                                </>
                            )}
                            {!result.success && (
                                <span className="font-pixel text-sm text-red-400">
                                    {result.message}
                                </span>
                            )}
                        </div>
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
                                        ? "bg-amber-600 text-stone-900"
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
                            onCraft={handleCraft}
                            loading={loading}
                        />
                    ))}
                </div>

                {displayRecipes.length === 0 && (
                    <div className="flex flex-1 items-center justify-center">
                        <div className="text-center">
                            <Hammer className="mx-auto mb-3 h-12 w-12 text-stone-600" />
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
