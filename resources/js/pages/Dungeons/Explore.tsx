import { Head, router, usePage } from "@inertiajs/react";
import { ArrowDown, Cookie, Heart, Shield, Skull, Sword, Trophy, X, Zap } from "lucide-react";
import { useState } from "react";
import AppLayout from "@/layouts/app-layout";
import type { BreadcrumbItem } from "@/types";

interface DungeonFloor {
    id: number;
    floor_number: number;
    name: string | null;
    display_name: string;
    monster_count: number;
    is_boss_floor: boolean;
}

interface Dungeon {
    id: number;
    name: string;
    description: string | null;
    theme: string;
    difficulty: string;
    floor_count: number;
    floors: DungeonFloor[];
}

interface DungeonSession {
    id: number;
    current_floor: number;
    monsters_defeated: number;
    total_monsters_on_floor: number;
    status: string;
    xp_accumulated: number;
    gold_accumulated: number;
    loot_accumulated: Record<number, number> | null;
    training_style: string;
    progress_percentage: number;
    dungeon: Dungeon;
}

interface PlayerStats {
    hp: number;
    max_hp: number;
    combat_level: number;
    attack: number;
    strength: number;
    defense: number;
}

interface Equipment {
    atk_bonus: number;
    str_bonus: number;
    def_bonus: number;
    hp_bonus: number;
}

interface FoodItem {
    id: number;
    name: string;
    hp_bonus: number;
    quantity: number;
}

interface Kingdom {
    id: number;
    name: string;
}

interface PageProps {
    kingdom: Kingdom;
    session: DungeonSession;
    player_stats: PlayerStats;
    equipment: Equipment;
    food: FoodItem[];
    [key: string]: unknown;
}

export default function DungeonExplore() {
    const { kingdom, session, player_stats, equipment, food } = usePage<PageProps>().props;
    const [isFighting, setIsFighting] = useState(false);
    const [isAdvancing, setIsAdvancing] = useState(false);
    const [isEating, setIsEating] = useState(false);
    const [isAbandoning, setIsAbandoning] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const [lastResult, setLastResult] = useState<{
        message: string;
        rewards?: { xp: number; gold: number; items: { name: string; quantity: number }[] };
        floor_cleared?: boolean;
    } | null>(null);
    const [showFoodMenu, setShowFoodMenu] = useState(false);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: "Dashboard", href: "/dashboard" },
        { title: kingdom.name, href: `/kingdoms/${kingdom.id}` },
        { title: "Dungeons", href: `/kingdoms/${kingdom.id}/dungeons` },
        { title: "Explore", href: "#" },
    ];

    const dungeon = session.dungeon;
    const currentFloor = dungeon.floors.find((f) => f.floor_number === session.current_floor);
    const isFloorCleared = session.monsters_defeated >= session.total_monsters_on_floor;
    const isOnFinalFloor = session.current_floor >= dungeon.floor_count;

    const fightMonster = async () => {
        if (isFighting) return;

        setIsFighting(true);
        setError(null);
        setLastResult(null);

        try {
            const response = await fetch(`/kingdoms/${kingdom.id}/dungeons/fight`, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN":
                        document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')
                            ?.content || "",
                },
            });

            const data = await response.json();

            if (data.success) {
                setLastResult({
                    message: data.message,
                    rewards: data.data?.rewards,
                    floor_cleared: data.data?.floor_cleared,
                });

                if (data.data?.status === "completed" || data.data?.status === "failed") {
                    router.reload({ preserveScroll: true });
                } else {
                    router.reload({ only: ["session", "player_stats"], preserveScroll: true });
                }
            } else {
                if (data.data?.status === "failed") {
                    router.reload();
                } else {
                    setError(data.message);
                }
            }
        } catch {
            setError("Failed to fight monster");
        } finally {
            setIsFighting(false);
        }
    };

    const nextFloor = async () => {
        if (isAdvancing) return;

        setIsAdvancing(true);
        setError(null);
        setLastResult(null);

        try {
            const response = await fetch(`/kingdoms/${kingdom.id}/dungeons/next-floor`, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN":
                        document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')
                            ?.content || "",
                },
            });

            const data = await response.json();

            if (data.success) {
                setLastResult({ message: data.message });
                router.reload({ preserveScroll: true });
            } else {
                setError(data.message);
            }
        } catch {
            setError("Failed to advance");
        } finally {
            setIsAdvancing(false);
        }
    };

    const eatFood = async (inventorySlotId: number) => {
        if (isEating) return;

        setIsEating(true);
        setError(null);
        setShowFoodMenu(false);

        try {
            const response = await fetch(`/kingdoms/${kingdom.id}/dungeons/eat`, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN":
                        document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')
                            ?.content || "",
                },
                body: JSON.stringify({ inventory_slot_id: inventorySlotId }),
            });

            const data = await response.json();

            if (data.success) {
                setLastResult({ message: data.message });
                router.reload({ only: ["player_stats", "food"], preserveScroll: true });
            } else {
                setError(data.message);
            }
        } catch {
            setError("Failed to eat food");
        } finally {
            setIsEating(false);
        }
    };

    const abandonDungeon = async () => {
        if (isAbandoning) return;
        if (
            !confirm(
                "Are you sure you want to abandon this dungeon? You will lose all accumulated rewards.",
            )
        )
            return;

        setIsAbandoning(true);
        setError(null);

        try {
            const response = await fetch(`/kingdoms/${kingdom.id}/dungeons/abandon`, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN":
                        document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')
                            ?.content || "",
                },
            });

            await response.json();
            router.reload({ preserveScroll: true });
        } catch {
            setError("Failed to abandon dungeon");
        } finally {
            setIsAbandoning(false);
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`${dungeon.name} - Floor ${session.current_floor}`} />
            <div className="flex h-full flex-1 flex-col p-4">
                {/* Header */}
                <div className="mb-4 flex items-center justify-between">
                    <div>
                        <h1 className="font-pixel text-2xl text-amber-400">{dungeon.name}</h1>
                        <p className="font-pixel text-sm text-stone-400">
                            Floor {session.current_floor} of {dungeon.floor_count} -{" "}
                            {currentFloor?.display_name || `Floor ${session.current_floor}`}
                        </p>
                    </div>
                    <button
                        onClick={abandonDungeon}
                        disabled={isAbandoning}
                        className="rounded-lg border border-red-500/50 bg-red-900/20 px-3 py-1 font-pixel text-xs text-red-400 transition hover:bg-red-900/40"
                    >
                        <X className="inline h-3 w-3" /> Abandon
                    </button>
                </div>

                {/* Progress Bar */}
                <div className="mb-6">
                    <div className="mb-1 flex items-center justify-between font-pixel text-xs text-stone-400">
                        <span>Dungeon Progress</span>
                        <span>{Math.round(session.progress_percentage)}%</span>
                    </div>
                    <div className="h-3 w-full overflow-hidden rounded-full bg-stone-700">
                        <div
                            className="h-full bg-gradient-to-r from-amber-600 to-amber-400 transition-all"
                            style={{ width: `${session.progress_percentage}%` }}
                        />
                    </div>
                </div>

                {/* Main Content Grid */}
                <div className="mb-6 grid gap-4 md:grid-cols-2">
                    {/* Player Status */}
                    <div className="rounded-lg border border-stone-700 bg-stone-800/50 p-4">
                        <h3 className="mb-3 font-pixel text-sm text-amber-300">Your Status</h3>
                        <div className="space-y-3">
                            <div>
                                <div className="mb-1 flex items-center justify-between font-pixel text-xs">
                                    <span className="flex items-center gap-1 text-red-400">
                                        <Heart className="h-3 w-3" /> HP
                                    </span>
                                    <span className="text-stone-300">
                                        {player_stats.hp} / {player_stats.max_hp}
                                    </span>
                                </div>
                                <div className="h-2 w-full overflow-hidden rounded-full bg-stone-700">
                                    <div
                                        className="h-full bg-gradient-to-r from-red-600 to-red-400"
                                        style={{
                                            width: `${(player_stats.hp / player_stats.max_hp) * 100}%`,
                                        }}
                                    />
                                </div>
                            </div>
                            <div className="grid grid-cols-3 gap-2 text-center">
                                <div>
                                    <Sword className="mx-auto h-4 w-4 text-red-400" />
                                    <span className="font-pixel text-xs text-stone-300">
                                        {player_stats.attack}
                                        {equipment.atk_bonus > 0 && (
                                            <span className="text-green-400">
                                                +{equipment.atk_bonus}
                                            </span>
                                        )}
                                    </span>
                                </div>
                                <div>
                                    <Skull className="mx-auto h-4 w-4 text-orange-400" />
                                    <span className="font-pixel text-xs text-stone-300">
                                        {player_stats.strength}
                                        {equipment.str_bonus > 0 && (
                                            <span className="text-green-400">
                                                +{equipment.str_bonus}
                                            </span>
                                        )}
                                    </span>
                                </div>
                                <div>
                                    <Shield className="mx-auto h-4 w-4 text-blue-400" />
                                    <span className="font-pixel text-xs text-stone-300">
                                        {player_stats.defense}
                                        {equipment.def_bonus > 0 && (
                                            <span className="text-green-400">
                                                +{equipment.def_bonus}
                                            </span>
                                        )}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Floor Status */}
                    <div className="rounded-lg border border-stone-700 bg-stone-800/50 p-4">
                        <h3 className="mb-3 font-pixel text-sm text-amber-300">Floor Progress</h3>
                        <div className="space-y-3">
                            <div className="flex items-center justify-between">
                                <span className="font-pixel text-xs text-stone-400">
                                    Monsters Defeated
                                </span>
                                <span className="font-pixel text-sm text-white">
                                    {session.monsters_defeated} / {session.total_monsters_on_floor}
                                </span>
                            </div>
                            <div className="h-2 w-full overflow-hidden rounded-full bg-stone-700">
                                <div
                                    className="h-full bg-gradient-to-r from-purple-600 to-purple-400"
                                    style={{
                                        width: `${(session.monsters_defeated / session.total_monsters_on_floor) * 100}%`,
                                    }}
                                />
                            </div>
                            {currentFloor?.is_boss_floor && (
                                <div className="rounded bg-red-900/30 p-2 text-center">
                                    <span className="font-pixel text-xs text-red-400">
                                        Boss Floor
                                    </span>
                                </div>
                            )}
                        </div>
                    </div>
                </div>

                {/* Accumulated Rewards */}
                <div className="mb-6 rounded-lg border border-stone-700 bg-stone-800/50 p-4">
                    <h3 className="mb-3 font-pixel text-sm text-amber-300">Accumulated Rewards</h3>
                    <div className="flex gap-6">
                        <div className="flex items-center gap-2">
                            <Zap className="h-4 w-4 text-blue-400" />
                            <span className="font-pixel text-sm text-stone-300">
                                {session.xp_accumulated} XP ({session.training_style})
                            </span>
                        </div>
                        <div className="flex items-center gap-2">
                            <Trophy className="h-4 w-4 text-yellow-400" />
                            <span className="font-pixel text-sm text-stone-300">
                                {session.gold_accumulated} Gold
                            </span>
                        </div>
                    </div>
                </div>

                {/* Error Message */}
                {error && (
                    <div className="mb-4 rounded-lg border border-red-500/50 bg-red-900/30 p-3 font-pixel text-sm text-red-300">
                        {error}
                    </div>
                )}

                {/* Last Result */}
                {lastResult && (
                    <div className="mb-4 rounded-lg border border-green-500/50 bg-green-900/30 p-3">
                        <p className="font-pixel text-sm text-green-300">{lastResult.message}</p>
                        {lastResult.rewards && (
                            <div className="mt-2 font-pixel text-xs text-stone-400">
                                +{lastResult.rewards.xp} XP, +{lastResult.rewards.gold} Gold
                                {lastResult.rewards.items.length > 0 && (
                                    <span>
                                        , Loot:{" "}
                                        {lastResult.rewards.items
                                            .map((i) => `${i.name} x${i.quantity}`)
                                            .join(", ")}
                                    </span>
                                )}
                            </div>
                        )}
                    </div>
                )}

                {/* Actions */}
                <div className="mt-auto space-y-3">
                    {/* Food Menu */}
                    {showFoodMenu && (
                        <div className="rounded-lg border border-stone-700 bg-stone-800 p-4">
                            <h4 className="mb-3 font-pixel text-sm text-amber-300">Select Food</h4>
                            {food.length === 0 ? (
                                <p className="font-pixel text-xs text-stone-500">
                                    No food in inventory
                                </p>
                            ) : (
                                <div className="grid gap-2">
                                    {food.map((item) => (
                                        <button
                                            key={item.id}
                                            onClick={() => eatFood(item.id)}
                                            disabled={isEating}
                                            className="flex items-center justify-between rounded-lg border border-stone-600 bg-stone-700/50 px-3 py-2 transition hover:bg-stone-600/50"
                                        >
                                            <span className="font-pixel text-xs text-stone-300">
                                                {item.name} x{item.quantity}
                                            </span>
                                            <span className="font-pixel text-xs text-green-400">
                                                +{item.hp_bonus} HP
                                            </span>
                                        </button>
                                    ))}
                                </div>
                            )}
                            <button
                                onClick={() => setShowFoodMenu(false)}
                                className="mt-3 w-full rounded-lg border border-stone-600 px-3 py-2 font-pixel text-xs text-stone-400 transition hover:bg-stone-700"
                            >
                                Cancel
                            </button>
                        </div>
                    )}

                    {/* Action Buttons */}
                    <div className="flex gap-3">
                        {/* Eat Food Button */}
                        <button
                            onClick={() => setShowFoodMenu(!showFoodMenu)}
                            disabled={isEating || food.length === 0}
                            className={`flex-1 rounded-lg border px-4 py-3 font-pixel text-sm transition ${
                                food.length > 0
                                    ? "border-green-500/50 bg-green-900/30 text-green-400 hover:bg-green-900/50"
                                    : "cursor-not-allowed border-stone-600 bg-stone-800 text-stone-500"
                            }`}
                        >
                            <Cookie className="mr-2 inline h-4 w-4" />
                            Eat Food
                        </button>

                        {/* Main Action Button */}
                        {isFloorCleared ? (
                            isOnFinalFloor ? (
                                <div className="flex-1 rounded-lg border border-amber-500 bg-amber-900/30 px-4 py-3 text-center font-pixel text-sm text-amber-300">
                                    <Trophy className="mr-2 inline h-4 w-4" />
                                    Dungeon Complete!
                                </div>
                            ) : (
                                <button
                                    onClick={nextFloor}
                                    disabled={isAdvancing}
                                    className="flex-1 rounded-lg bg-amber-600 px-4 py-3 font-pixel text-sm text-white transition hover:bg-amber-500"
                                >
                                    <ArrowDown className="mr-2 inline h-4 w-4" />
                                    {isAdvancing ? "Descending..." : "Next Floor"}
                                </button>
                            )
                        ) : (
                            <button
                                onClick={fightMonster}
                                disabled={isFighting || player_stats.hp <= 0}
                                className={`flex-1 rounded-lg px-4 py-3 font-pixel text-sm transition ${
                                    player_stats.hp > 0 && !isFighting
                                        ? "bg-red-600 text-white hover:bg-red-500"
                                        : "cursor-not-allowed bg-stone-700 text-stone-500"
                                }`}
                            >
                                <Sword className="mr-2 inline h-4 w-4" />
                                {isFighting ? "Fighting..." : "Fight Monster"}
                            </button>
                        )}
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
