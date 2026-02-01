import { Head, router, usePage } from "@inertiajs/react";
import {
    ArrowRight,
    Backpack,
    Check,
    FlaskConical,
    Heart,
    Leaf,
    Loader2,
    Lock,
    Shield,
    Sparkles,
    Sword,
    X,
    Zap,
} from "lucide-react";
import { useState } from "react";
import AppLayout from "@/layouts/app-layout";
import { gameToast } from "@/components/ui/game-toast";
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

interface BrewResult {
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
    brewing_info: BrewingInfo;
    location?: Location;
    [key: string]: unknown;
}

const getBreadcrumbs = (location?: Location): BreadcrumbItem[] => [
    { title: "Dashboard", href: "/dashboard" },
    ...(location ? [{ title: location.name, href: `/${location.type}s/${location.id}` }] : []),
    {
        title: "Apothecary",
        href: location ? `/${location.type}s/${location.id}/apothecary` : "/apothecary",
    },
];

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
    onBrew,
    loading,
}: {
    recipe: Recipe;
    onBrew: (id: string) => void;
    loading: string | null;
}) {
    const isLoading = loading === recipe.id;
    const CategoryIcon = categoryIcons[recipe.category] || FlaskConical;
    const categoryColor = categoryColors[recipe.category] || "text-stone-400";

    return (
        <div
            className={`rounded-lg border p-3 transition ${
                recipe.is_locked
                    ? "border-stone-700 bg-stone-800/30 opacity-60"
                    : recipe.can_make
                      ? "border-emerald-600/50 bg-stone-800/50"
                      : "border-stone-700 bg-stone-800/50"
            }`}
        >
            <div className="mb-3 flex items-center justify-between">
                <div className="flex items-center gap-2">
                    <CategoryIcon className={`h-4 w-4 ${categoryColor}`} />
                    <span className="font-pixel text-sm text-emerald-300">{recipe.name}</span>
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
                <ArrowRight className="h-3 w-3 text-emerald-400" />
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
                <span className="font-pixel text-[10px] text-emerald-400">
                    +{recipe.xp_reward} XP
                </span>
            </div>

            {/* Brew Button */}
            {recipe.is_locked ? (
                <div className="rounded-md bg-stone-900/50 px-3 py-2 text-center">
                    <span className="font-pixel text-[10px] text-stone-500">
                        Requires Level {recipe.required_level} Herblore
                    </span>
                </div>
            ) : (
                <button
                    onClick={() => onBrew(recipe.id)}
                    disabled={!recipe.can_make || loading !== null}
                    className={`flex w-full items-center justify-center gap-2 rounded-md px-3 py-2 font-pixel text-xs transition ${
                        recipe.can_make && !loading
                            ? "bg-emerald-600 text-stone-900 hover:bg-emerald-500"
                            : "cursor-not-allowed bg-stone-700 text-stone-500"
                    }`}
                >
                    {isLoading ? (
                        <>
                            <Loader2 className="h-3 w-3 animate-spin" />
                            Brewing...
                        </>
                    ) : recipe.can_make ? (
                        <>
                            <Check className="h-3 w-3" />
                            Brew
                        </>
                    ) : (
                        <>
                            <X className="h-3 w-3" />
                            Missing Ingredients
                        </>
                    )}
                </button>
            )}
        </div>
    );
}

export default function ApothecaryIndex() {
    const { brewing_info, location } = usePage<PageProps>().props;
    const [loading, setLoading] = useState<string | null>(null);
    const [activeCategory, setActiveCategory] = useState<string>("all");
    const [currentEnergy, setCurrentEnergy] = useState(brewing_info.player_energy);

    const brewUrl = location
        ? `/${location.type}s/${location.id}/apothecary/brew`
        : "/apothecary/brew";

    const handleBrew = async (recipeId: string) => {
        setLoading(recipeId);

        try {
            const response = await fetch(brewUrl, {
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

            const data: BrewResult = await response.json();

            if (data.success && data.item) {
                gameToast.success(`Brewed ${data.item.quantity}x ${data.item.name}`, {
                    xp: data.xp_awarded,
                    levelUp: data.leveled_up
                        ? {
                              skill: data.skill || "Herblore",
                              level: (brewing_info.herblore_level || 0) + 1,
                          }
                        : undefined,
                });
            } else if (!data.success) {
                gameToast.error(data.message);
            }

            if (data.success && data.energy_remaining !== undefined) {
                setCurrentEnergy(data.energy_remaining);
            }

            router.reload({ only: ["brewing_info", "sidebar"] });
        } catch {
            gameToast.error("An error occurred");
        } finally {
            setLoading(null);
        }
    };

    const allRecipes = Object.entries(brewing_info.all_recipes).flatMap(([, recipes]) => recipes);
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
                                    width: `${(currentEnergy / brewing_info.max_energy) * 100}%`,
                                }}
                            />
                        </div>
                        <div className="mt-1 font-pixel text-[10px] text-stone-400">
                            {currentEnergy} / {brewing_info.max_energy}
                        </div>
                    </div>
                    <div className="rounded-lg border border-stone-700 bg-stone-800/50 p-3">
                        <div className="mb-1 flex items-center justify-between">
                            <div className="flex items-center gap-1 font-pixel text-xs text-amber-300">
                                <Backpack className="h-3 w-3" />
                                Inventory
                            </div>
                            <span className="font-pixel text-xs text-stone-400">
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
                                    const Icon = ingredient.type === "vial" ? FlaskConical : Leaf;
                                    return (
                                        <div
                                            key={ingredient.name}
                                            className={`flex items-center gap-1.5 rounded border ${style.border} ${style.bg} px-2 py-1`}
                                        >
                                            <Icon className={`h-3 w-3 ${style.icon}`} />
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
                    <div className="rounded-lg border border-stone-700 bg-stone-800/50 p-3">
                        <div className="mb-1 flex items-center justify-between">
                            <div className="flex items-center gap-1 font-pixel text-xs text-emerald-400">
                                <Leaf className="h-3 w-3" />
                                Herblore
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
                            onBrew={handleBrew}
                            loading={loading}
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
