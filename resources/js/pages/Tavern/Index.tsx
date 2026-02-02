import { Head, router, usePage } from "@inertiajs/react";
import {
    ArrowRight,
    Beer,
    Check,
    ChefHat,
    Coins,
    Dices,
    Loader2,
    Lock,
    MessageCircle,
    X,
    Zap,
} from "lucide-react";
import { useState } from "react";
import AppLayout from "@/layouts/app-layout";
import { gameToast } from "@/components/ui/game-toast";
import { DiceGame } from "@/components/games/dice-game";
import type { BreadcrumbItem } from "@/types";

interface Activity {
    id: number;
    username: string;
    description: string;
    type: string;
    subtype: string | null;
    time_ago: string;
}

interface Material {
    name: string;
    required: number;
    have: number;
    has_enough: boolean;
}

interface Recipe {
    id: string;
    name: string;
    required_level: number;
    xp_reward: number;
    energy_cost: number;
    materials: Material[];
    output: { name: string; quantity: number };
    can_make: boolean;
    is_locked: boolean;
    current_level: number;
}

interface CookingInfo {
    recipes: Recipe[];
    cooking_level: number;
}

interface CookResult {
    success: boolean;
    message: string;
    item?: { name: string; quantity: number };
    xp_awarded?: number;
    leveled_up?: boolean;
    energy_remaining?: number;
}

interface TavernStats {
    wins: number;
    losses: number;
    total_profit: number;
}

interface RecentGame {
    id: number;
    game_type: string;
    wager: number;
    won: boolean;
    payout: number;
    energy_awarded: number;
    played_at: string;
}

interface DiceInfo {
    can_play: boolean;
    cooldown_ends: string | null;
    reason: string | null;
    min_wager: number;
    max_wager: number;
    games: string[];
    recent_games: RecentGame[];
    tavern_stats: TavernStats;
}

interface PageProps {
    location: {
        type: string;
        id: number;
        name: string;
    } | null;
    player: {
        energy: number;
        max_energy: number;
        gold: number;
    };
    rest: {
        cost: number;
        energy_restored: number;
        can_rest: boolean;
    };
    recent_activity: Activity[];
    cooking: CookingInfo;
    dice: DiceInfo;
    [key: string]: unknown;
}

function RecipeCard({
    recipe,
    onCook,
    loading,
}: {
    recipe: Recipe;
    onCook: (id: string) => void;
    loading: string | null;
}) {
    const isLoading = loading === recipe.id;

    return (
        <div
            className={`rounded-lg border p-3 transition ${
                recipe.is_locked
                    ? "border-stone-700 bg-stone-800/30 opacity-60"
                    : recipe.can_make
                      ? "border-orange-600/50 bg-orange-900/20"
                      : "border-stone-700 bg-stone-800/50"
            }`}
        >
            <div className="mb-2 flex items-center justify-between">
                <span className="font-pixel text-sm text-amber-300">{recipe.name}</span>
                {recipe.is_locked && <Lock className="h-4 w-4 text-stone-500" />}
            </div>

            {/* Materials */}
            <div className="mb-2 space-y-1">
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
            <div className="mb-2 flex items-center gap-2 rounded bg-stone-900/50 px-2 py-1">
                <ArrowRight className="h-3 w-3 text-orange-400" />
                <span className="font-pixel text-xs text-stone-300">
                    {recipe.output.quantity}x {recipe.output.name}
                </span>
            </div>

            {/* Stats Row */}
            <div className="mb-2 flex items-center justify-between text-stone-500">
                <span className="flex items-center gap-1 font-pixel text-[10px]">
                    <Zap className="h-3 w-3 text-yellow-500" />
                    {recipe.energy_cost}
                </span>
                <span className="font-pixel text-[10px] text-amber-400">
                    +{recipe.xp_reward} XP
                </span>
            </div>

            {/* Cook Button */}
            {recipe.is_locked ? (
                <div className="rounded-md bg-stone-900/50 px-2 py-1.5 text-center">
                    <span className="font-pixel text-[10px] text-stone-500">
                        Requires Level {recipe.required_level}
                    </span>
                </div>
            ) : (
                <button
                    onClick={() => onCook(recipe.id)}
                    disabled={!recipe.can_make || loading !== null}
                    className={`flex w-full items-center justify-center gap-2 rounded-md px-2 py-1.5 font-pixel text-xs transition ${
                        recipe.can_make && !loading
                            ? "bg-orange-600 text-stone-900 hover:bg-orange-500"
                            : "cursor-not-allowed bg-stone-700 text-stone-500"
                    }`}
                >
                    {isLoading ? (
                        <>
                            <Loader2 className="h-3 w-3 animate-spin" />
                            Cooking...
                        </>
                    ) : recipe.can_make ? (
                        <>
                            <Check className="h-3 w-3" />
                            Cook
                        </>
                    ) : (
                        <>
                            <X className="h-3 w-3" />
                            Missing
                        </>
                    )}
                </button>
            )}
        </div>
    );
}

type GameType = "high_roll" | "hazard" | "doubles";

const GAME_INFO: Record<
    GameType,
    { name: string; description: string; odds: string; payout: string }
> = {
    high_roll: {
        name: "High Roll",
        description: "Both roll 2d6. Highest wins.",
        odds: "~47%",
        payout: "1.35x",
    },
    hazard: {
        name: "Hazard",
        description: "7/11 wins, set a point, avoid 7!",
        odds: "~49%",
        payout: "1.6x",
    },
    doubles: {
        name: "Doubles",
        description: "Roll doubles to win!",
        odds: "17%",
        payout: "1.8x",
    },
};

export default function TavernIndex() {
    const { location, player, rest, recent_activity, cooking, dice } = usePage<PageProps>().props;
    const [loading, setLoading] = useState(false);
    const [cookingLoading, setCookingLoading] = useState<string | null>(null);
    const [currentEnergy, setCurrentEnergy] = useState(player.energy);
    const [selectedDiceGame, setSelectedDiceGame] = useState<GameType | null>(null);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: "Dashboard", href: "/dashboard" },
        ...(location
            ? [
                  { title: location.name, href: `/${location.type}s/${location.id}` },
                  { title: "Tavern", href: "#" },
              ]
            : [{ title: "Tavern", href: "#" }]),
    ];

    // Build the correct URL based on location type
    const baseUrl = location ? `/${location.type}s/${location.id}/tavern` : "/villages/1/tavern";

    const handleRest = () => {
        setLoading(true);
        router.post(
            `${baseUrl}/rest`,
            {},
            {
                preserveScroll: true,
                onSuccess: () => {
                    router.reload();
                },
                onFinish: () => setLoading(false),
            },
        );
    };

    const handleCook = async (recipeId: string) => {
        setCookingLoading(recipeId);

        try {
            const response = await fetch(`${baseUrl}/cook`, {
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

            const data: CookResult = await response.json();

            if (data.success && data.item) {
                gameToast.success(`Cooked ${data.item.quantity}x ${data.item.name}`, {
                    xp: data.xp_awarded,
                    levelUp: data.leveled_up
                        ? { skill: "Cooking", level: (cooking.cooking_level || 0) + 1 }
                        : undefined,
                });
            } else if (!data.success) {
                gameToast.error(data.message);
            }

            if (data.success && data.energy_remaining !== undefined) {
                setCurrentEnergy(data.energy_remaining);
            }

            // Reload to update materials
            router.reload({ only: ["cooking", "player", "sidebar"] });
        } catch {
            gameToast.error("An error occurred");
        } finally {
            setCookingLoading(null);
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Tavern - ${location?.name || "Unknown"}`} />
            <div className="flex h-full flex-1 flex-col overflow-hidden p-4">
                {/* Header */}
                <div className="mb-6 flex items-center justify-between">
                    <div className="flex items-center gap-3">
                        <div className="rounded-lg bg-amber-900/30 p-3">
                            <Beer className="h-8 w-8 text-amber-400" />
                        </div>
                        <div>
                            <h1 className="font-pixel text-2xl text-amber-400">Tavern</h1>
                            <p className="font-pixel text-sm text-stone-400">
                                Rest, cook, and hear rumors at {location?.name}
                            </p>
                        </div>
                    </div>
                    <div className="flex items-center gap-4">
                        <div className="flex items-center gap-2 rounded-lg border-2 border-stone-600/50 bg-stone-800/50 px-4 py-2">
                            <Zap className="h-4 w-4 text-yellow-400" />
                            <span className="font-pixel text-sm text-stone-300">
                                {currentEnergy}/{player.max_energy}
                            </span>
                        </div>
                        <div className="flex items-center gap-2 rounded-lg border-2 border-stone-600/50 bg-stone-800/50 px-4 py-2">
                            <Coins className="h-4 w-4 text-amber-400" />
                            <span className="font-pixel text-sm text-amber-300">
                                {player.gold}g
                            </span>
                        </div>
                    </div>
                </div>

                {/* Main Grid: Left column (Rest + Dice) | Right 3 columns (Kitchen) */}
                <div className="mb-6 grid gap-6 lg:grid-cols-4">
                    {/* Left Column: Rest & Dice Games stacked */}
                    <div className="flex flex-col gap-6">
                        {/* Rest Section */}
                        <div className="rounded-xl border-2 border-amber-600/50 bg-amber-900/20 p-6">
                            <h2 className="mb-4 font-pixel text-lg text-amber-300">
                                Rest & Recuperate
                            </h2>
                            <p className="mb-4 text-sm text-stone-300">
                                Sit by the fire and recover your energy.
                            </p>

                            <div className="mb-4 grid grid-cols-2 gap-3">
                                <div className="rounded-lg bg-stone-800/50 p-3 text-center">
                                    <div className="flex items-center justify-center gap-1">
                                        <Coins className="h-4 w-4 text-amber-400" />
                                        <span className="font-pixel text-lg text-amber-300">
                                            {rest.cost}g
                                        </span>
                                    </div>
                                    <div className="font-pixel text-[10px] text-stone-500">
                                        Cost
                                    </div>
                                </div>
                                <div className="rounded-lg bg-stone-800/50 p-3 text-center">
                                    <div className="flex items-center justify-center gap-1">
                                        <Zap className="h-4 w-4 text-yellow-400" />
                                        <span className="font-pixel text-lg text-yellow-300">
                                            +{rest.energy_restored}
                                        </span>
                                    </div>
                                    <div className="font-pixel text-[10px] text-stone-500">
                                        Energy
                                    </div>
                                </div>
                            </div>

                            {currentEnergy >= player.max_energy ? (
                                <div className="rounded-lg bg-green-900/30 p-3 text-center">
                                    <span className="font-pixel text-sm text-green-300">
                                        Fully rested!
                                    </span>
                                </div>
                            ) : player.gold < rest.cost ? (
                                <div className="rounded-lg bg-red-900/30 p-3 text-center">
                                    <span className="font-pixel text-sm text-red-300">
                                        Need {rest.cost}g
                                    </span>
                                </div>
                            ) : (
                                <button
                                    onClick={handleRest}
                                    disabled={loading || !rest.can_rest}
                                    className="flex w-full items-center justify-center gap-2 rounded-lg border-2 border-amber-500 bg-amber-900/30 px-4 py-3 font-pixel text-sm text-amber-300 transition hover:bg-amber-800/50 disabled:cursor-not-allowed disabled:opacity-50"
                                >
                                    {loading ? (
                                        <Loader2 className="h-5 w-5 animate-spin" />
                                    ) : (
                                        <>
                                            <Beer className="h-5 w-5" />
                                            Rest
                                        </>
                                    )}
                                </button>
                            )}
                        </div>

                        {/* Dice Games Section */}
                        <div className="flex-1 rounded-xl border-2 border-purple-600/50 bg-purple-900/20 p-6">
                            <div className="mb-4 flex items-center gap-2">
                                <Dices className="h-5 w-5 text-purple-400" />
                                <h2 className="font-pixel text-lg text-purple-300">Dice Games</h2>
                            </div>

                            {/* Game Selection */}
                            <div className="space-y-2">
                                {(Object.keys(GAME_INFO) as GameType[]).map((gameType) => {
                                    const info = GAME_INFO[gameType];
                                    return (
                                        <button
                                            key={gameType}
                                            onClick={() => setSelectedDiceGame(gameType)}
                                            className="flex w-full items-center justify-between rounded-lg border border-purple-600/30 bg-purple-900/30 p-3 text-left transition hover:border-purple-500/50 hover:bg-purple-800/40"
                                        >
                                            <div>
                                                <p className="font-pixel text-sm text-purple-300">
                                                    {info.name}
                                                </p>
                                                <p className="font-pixel text-[10px] text-stone-400">
                                                    {info.description}
                                                </p>
                                            </div>
                                            <div className="text-right">
                                                <p className="font-pixel text-[10px] text-green-400">
                                                    {info.odds}
                                                </p>
                                                <p className="font-pixel text-[10px] text-amber-400">
                                                    {info.payout}
                                                </p>
                                            </div>
                                        </button>
                                    );
                                })}
                            </div>

                            {/* Stats */}
                            <div className="mt-4 rounded-lg border border-stone-600/30 bg-stone-800/30 p-3">
                                <p className="mb-2 font-pixel text-[10px] text-stone-500">
                                    Your Stats
                                </p>
                                <div className="grid grid-cols-3 gap-2">
                                    <div className="text-center">
                                        <p className="font-pixel text-sm text-green-400">
                                            {dice.tavern_stats.wins}
                                        </p>
                                        <p className="font-pixel text-[10px] text-stone-500">
                                            Wins
                                        </p>
                                    </div>
                                    <div className="text-center">
                                        <p className="font-pixel text-sm text-red-400">
                                            {dice.tavern_stats.losses}
                                        </p>
                                        <p className="font-pixel text-[10px] text-stone-500">
                                            Losses
                                        </p>
                                    </div>
                                    <div className="text-center">
                                        <p
                                            className={`font-pixel text-sm ${
                                                dice.tavern_stats.total_profit >= 0
                                                    ? "text-green-400"
                                                    : "text-red-400"
                                            }`}
                                        >
                                            {dice.tavern_stats.total_profit >= 0 ? "+" : ""}
                                            {dice.tavern_stats.total_profit}g
                                        </p>
                                        <p className="font-pixel text-[10px] text-stone-500">
                                            Profit
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Kitchen Section - Takes 3 columns, full height */}
                    <div className="rounded-xl border-2 border-orange-600/50 bg-orange-900/20 p-6 lg:col-span-3">
                        <div className="mb-4 flex items-center justify-between">
                            <div className="flex items-center gap-2">
                                <ChefHat className="h-5 w-5 text-orange-400" />
                                <h2 className="font-pixel text-lg text-orange-300">Kitchen</h2>
                            </div>
                            <span className="font-pixel text-xs text-stone-400">
                                Cooking Lv. {cooking.cooking_level}
                            </span>
                        </div>

                        {/* Recipe Grid - 4 columns */}
                        <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                            {cooking.recipes.map((recipe) => (
                                <RecipeCard
                                    key={recipe.id}
                                    recipe={recipe}
                                    onCook={handleCook}
                                    loading={cookingLoading}
                                />
                            ))}
                        </div>
                    </div>
                </div>

                {/* Dice Game Modal */}
                {selectedDiceGame && (
                    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/70 p-4">
                        <div className="w-full max-w-2xl rounded-xl border-2 border-purple-600/50 bg-stone-900 p-6">
                            <div className="mb-4 flex items-center justify-between">
                                <div className="flex items-center gap-2">
                                    <Dices className="h-5 w-5 text-purple-400" />
                                    <h2 className="font-pixel text-lg text-purple-300">
                                        {GAME_INFO[selectedDiceGame].name}
                                    </h2>
                                </div>
                                <button
                                    onClick={() => setSelectedDiceGame(null)}
                                    className="rounded-lg p-2 text-stone-400 transition hover:bg-stone-800 hover:text-stone-200"
                                >
                                    <X className="h-5 w-5" />
                                </button>
                            </div>
                            <DiceGame
                                locationUrl={baseUrl}
                                canPlay={dice.can_play}
                                cooldownEnds={dice.cooldown_ends}
                                reason={dice.reason}
                                minWager={dice.min_wager}
                                maxWager={dice.max_wager}
                                playerGold={player.gold}
                                tavernStats={dice.tavern_stats}
                                recentGames={dice.recent_games}
                                initialGame={selectedDiceGame}
                                onGameComplete={() => {
                                    router.reload();
                                }}
                                onClose={() => setSelectedDiceGame(null)}
                            />
                        </div>
                    </div>
                )}

                {/* Row 3: Local Rumors - Full Width */}
                <div className="rounded-xl border-2 border-stone-600/50 bg-stone-800/30 p-6">
                    <div className="mb-4 flex items-center gap-2">
                        <MessageCircle className="h-5 w-5 text-stone-400" />
                        <h2 className="font-pixel text-lg text-stone-300">Local Rumors</h2>
                    </div>

                    {recent_activity.length > 0 ? (
                        <div className="grid gap-2 sm:grid-cols-2 lg:grid-cols-4">
                            {recent_activity.slice(0, 8).map((activity) => (
                                <div key={activity.id} className="rounded-lg bg-stone-900/50 p-3">
                                    <p className="text-sm text-stone-300">{activity.description}</p>
                                    <p className="mt-1 font-pixel text-[10px] text-stone-500">
                                        {activity.time_ago}
                                    </p>
                                </div>
                            ))}
                        </div>
                    ) : (
                        <div className="py-4 text-center">
                            <MessageCircle className="mx-auto mb-2 h-8 w-8 text-stone-600" />
                            <p className="font-pixel text-sm text-stone-500">
                                The tavern is quiet today...
                            </p>
                        </div>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
