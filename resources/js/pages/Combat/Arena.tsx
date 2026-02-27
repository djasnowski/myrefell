import { Head, router, usePage } from "@inertiajs/react";
import {
    Sword,
    Shield,
    Heart,
    Zap,
    Apple,
    DoorOpen,
    Skull,
    Coins,
    Package,
    Sparkles,
} from "lucide-react";
import { useState, useEffect } from "react";
import AppLayout from "@/layouts/app-layout";
import type { BreadcrumbItem } from "@/types";

interface Monster {
    id: number;
    name: string;
    type: string;
    combat_level: number;
    max_hp: number;
}

interface CombatLog {
    id: number;
    round: number;
    actor: "player" | "monster";
    action: "attack" | "eat" | "flee";
    hit: boolean;
    damage: number;
    player_hp_after: number;
    monster_hp_after: number;
    hp_restored: number;
    item?: { name: string };
}

interface AttackStyle {
    name: string;
    attack_type: "stab" | "slash" | "crush";
    weapon_style: "accurate" | "aggressive" | "controlled" | "defensive";
    xp_skills: string[];
}

interface CombatSession {
    id: number;
    player_hp: number;
    monster_hp: number;
    round: number;
    training_style: string;
    attack_style_index: number;
    status: "active" | "victory" | "defeat" | "fled";
    monster: Monster;
    logs: CombatLog[];
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

interface PageProps {
    session: CombatSession;
    player_stats: PlayerStats;
    equipment: Equipment;
    food: FoodItem[];
    weapon_subtype: string;
    available_attack_styles: AttackStyle[];
    [key: string]: unknown;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: "Dashboard", href: "/dashboard" },
    { title: "Combat", href: "/combat" },
    { title: "Battle", href: "/combat" },
];

export default function CombatArena() {
    const {
        session: initialSession,
        player_stats,
        food: initialFood,
        weapon_subtype,
        available_attack_styles,
    } = usePage<PageProps>().props;
    const activeStyle = available_attack_styles?.[initialSession.attack_style_index] ?? null;

    const attackTypeColors: Record<string, string> = {
        stab: "text-teal-400",
        slash: "text-red-400",
        crush: "text-amber-400",
    };
    const [session, setSession] = useState(initialSession);
    const [food, setFood] = useState(initialFood);
    const [isActing, setIsActing] = useState(false);
    const [combatLogs, setCombatLogs] = useState<CombatLog[]>(initialSession.logs || []);
    const [rewards, setRewards] = useState<{
        xp: number;
        skill: string;
        xp_skills?: string[];
        levels_gained: number;
        gold: number;
        items: { name: string; quantity: number }[];
        attack_style?: string;
    } | null>(null);
    const [showFood, setShowFood] = useState(false);

    const isActive = session.status === "active";
    const isVictory = session.status === "victory";
    const isDefeat = session.status === "defeat";
    const hasFled = session.status === "fled";

    useEffect(() => {
        const logContainer = document.getElementById("combat-logs");
        if (logContainer) {
            logContainer.scrollTop = logContainer.scrollHeight;
        }
    }, [combatLogs]);

    const performAction = async (
        action: "attack" | "flee",
        extraData?: Record<string, unknown>,
    ) => {
        if (isActing || !isActive) return;

        setIsActing(true);

        try {
            const response = await fetch(`/combat/${action}`, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN":
                        document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')
                            ?.content || "",
                },
                body: JSON.stringify(extraData || {}),
            });

            const data = await response.json();

            if (data.data?.session) {
                setSession(data.data.session);
            }
            if (data.data?.logs) {
                setCombatLogs((prev) => [...prev, ...data.data.logs]);
            }
            if (data.data?.rewards) {
                setRewards(data.data.rewards);
            }
        } catch (error) {
            console.error("Combat action failed:", error);
        } finally {
            setIsActing(false);
        }
    };

    const eatFood = async (inventorySlotId: number) => {
        if (isActing || !isActive) return;

        setIsActing(true);
        setShowFood(false);

        try {
            const response = await fetch("/combat/eat", {
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

            if (data.data?.session) {
                setSession(data.data.session);
            }
            if (data.data?.logs) {
                setCombatLogs((prev) => [...prev, ...data.data.logs]);
            }

            // Update food list
            setFood((prev) =>
                prev
                    .map((f) => (f.id === inventorySlotId ? { ...f, quantity: f.quantity - 1 } : f))
                    .filter((f) => f.quantity > 0),
            );
        } catch (error) {
            console.error("Eat action failed:", error);
        } finally {
            setIsActing(false);
        }
    };

    const sidebar = usePage().props.sidebar as {
        location?: { type: string; id: number };
    } | null;

    const returnToCombat = () => {
        if (isDefeat) {
            const loc = sidebar?.location;
            if (loc?.type && loc?.id) {
                const pathMap: Record<string, string> = {
                    village: `/villages/${loc.id}/healer`,
                    town: `/towns/${loc.id}/infirmary`,
                    barony: `/baronies/${loc.id}/infirmary`,
                    kingdom: `/kingdoms/${loc.id}/infirmary`,
                };
                const path = pathMap[loc.type] || "/dashboard";
                router.visit(path);
                return;
            }
        }
        router.visit("/combat");
    };

    const getLogMessage = (log: CombatLog): string => {
        const actorName = log.actor === "player" ? "You" : session.monster.name;

        switch (log.action) {
            case "attack":
                return log.hit
                    ? `${actorName} hit for ${log.damage} damage!`
                    : `${actorName} missed!`;
            case "eat":
                return `You ate ${log.item?.name || "food"} and restored ${log.hp_restored} HP.`;
            case "flee":
                return log.hit ? "You successfully fled from combat!" : "You failed to flee!";
            default:
                return "Unknown action";
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Fighting ${session.monster.name}`} />
            <div className="flex h-full flex-1 flex-col p-4">
                {/* Combat Header */}
                <div className="mb-4 shrink-0 text-center">
                    <h1 className="font-pixel text-xl text-amber-400">Round {session.round}</h1>
                    <div className="flex items-center justify-center gap-2">
                        {activeStyle && (
                            <>
                                <span
                                    className={`font-pixel text-xs ${attackTypeColors[activeStyle.attack_type] ?? "text-stone-400"}`}
                                >
                                    {activeStyle.name}
                                </span>
                                <span className="font-pixel text-[10px] text-stone-600">|</span>
                                <span className="font-pixel text-[10px] capitalize text-stone-500">
                                    {activeStyle.attack_type}
                                </span>
                                <span className="font-pixel text-[10px] text-stone-600">|</span>
                            </>
                        )}
                        <span className="font-pixel text-[10px] capitalize text-stone-500">
                            {activeStyle
                                ? activeStyle.xp_skills.join("+") + " XP"
                                : session.training_style + " XP"}
                        </span>
                    </div>
                </div>

                {/* Health Bars */}
                <div className="mb-6 shrink-0 grid gap-4 md:grid-cols-2">
                    {/* Player HP */}
                    <div className="rounded-lg border border-stone-700 bg-stone-800/50 p-4">
                        <div className="mb-2 flex items-center justify-between">
                            <span className="font-pixel text-sm text-amber-300">You</span>
                            <span className="font-pixel text-xs text-stone-400">
                                Level {player_stats.combat_level}
                            </span>
                        </div>
                        <div className="mb-1 flex items-center justify-between font-pixel text-xs">
                            <span className="flex items-center gap-1 text-red-400">
                                <Heart className="h-3 w-3" /> HP
                            </span>
                            <span className="text-stone-300">
                                {session.player_hp} / {player_stats.max_hp}
                            </span>
                        </div>
                        <div className="h-4 w-full overflow-hidden rounded-full bg-stone-700">
                            <div
                                className="h-full bg-gradient-to-r from-green-600 to-green-400 transition-all duration-300"
                                style={{
                                    width: `${(session.player_hp / player_stats.max_hp) * 100}%`,
                                }}
                            />
                        </div>
                    </div>

                    {/* Monster HP */}
                    <div className="rounded-lg border border-stone-700 bg-stone-800/50 p-4">
                        <div className="mb-2 flex items-center justify-between">
                            <span className="font-pixel text-sm text-red-400">
                                {session.monster.name}
                            </span>
                            <span className="font-pixel text-xs text-stone-400">
                                Level {session.monster.combat_level}
                            </span>
                        </div>
                        <div className="mb-1 flex items-center justify-between font-pixel text-xs">
                            <span className="flex items-center gap-1 text-red-400">
                                <Heart className="h-3 w-3" /> HP
                            </span>
                            <span className="text-stone-300">
                                {session.monster_hp} / {session.monster.max_hp}
                            </span>
                        </div>
                        <div className="h-4 w-full overflow-hidden rounded-full bg-stone-700">
                            <div
                                className="h-full bg-gradient-to-r from-red-600 to-red-400 transition-all duration-300"
                                style={{
                                    width: `${(session.monster_hp / session.monster.max_hp) * 100}%`,
                                }}
                            />
                        </div>
                    </div>
                </div>

                {/* Combat Log - Flexible Height */}
                <div className="mb-4 flex min-h-0 flex-1 flex-col overflow-hidden rounded-lg border border-stone-700 bg-stone-900/50 p-4">
                    <h3 className="mb-3 shrink-0 font-pixel text-sm text-amber-300">Combat Log</h3>
                    <div id="combat-logs" className="h-full space-y-1 overflow-y-auto">
                        {combatLogs.map((log) => (
                            <div
                                key={log.id}
                                className={`rounded px-2 py-1 font-pixel text-xs ${
                                    log.actor === "player"
                                        ? "bg-stone-800/50 text-green-400"
                                        : "bg-stone-800/50 text-red-400"
                                }`}
                            >
                                <span className="text-stone-500">R{log.round}:</span>{" "}
                                {getLogMessage(log)}
                            </div>
                        ))}
                        {combatLogs.length === 0 && (
                            <p className="text-center font-pixel text-xs text-stone-500">
                                Combat begins...
                            </p>
                        )}
                    </div>
                </div>

                {/* Victory Screen */}
                {isVictory && rewards && (
                    <div className="shrink-0 rounded-xl border-2 border-green-500/50 bg-gradient-to-b from-green-900/40 to-green-950/60 p-4">
                        {/* Header - Compact */}
                        <div className="mb-4 flex items-center justify-center gap-3">
                            <div className="flex h-12 w-12 items-center justify-center rounded-full bg-green-500/20 ring-2 ring-green-500/50">
                                <Sword className="h-6 w-6 text-green-400" />
                            </div>
                            <div>
                                <h2 className="font-pixel text-xl text-green-400">Victory!</h2>
                                <p className="font-pixel text-xs text-stone-300">
                                    Defeated {session.monster.name}
                                </p>
                            </div>
                        </div>

                        {/* Rewards Row - Horizontal flex */}
                        <div className="mb-4 flex flex-wrap items-stretch justify-center gap-3">
                            {/* XP Reward */}
                            <div
                                className={`flex min-w-[120px] flex-1 items-center gap-3 rounded-lg border-2 px-4 py-3 ${
                                    rewards.skill === "attack"
                                        ? "border-red-500/50 bg-red-900/30"
                                        : rewards.skill === "strength"
                                          ? "border-orange-500/50 bg-orange-900/30"
                                          : "border-blue-500/50 bg-blue-900/30"
                                }`}
                            >
                                {rewards.skill === "attack" ? (
                                    <Sword className="h-8 w-8 text-red-400" />
                                ) : rewards.skill === "strength" ? (
                                    <Zap className="h-8 w-8 text-orange-400" />
                                ) : (
                                    <Shield className="h-8 w-8 text-blue-400" />
                                )}
                                <div>
                                    <div
                                        className={`font-pixel text-2xl ${
                                            rewards.skill === "attack"
                                                ? "text-red-400"
                                                : rewards.skill === "strength"
                                                  ? "text-orange-400"
                                                  : "text-blue-400"
                                        }`}
                                    >
                                        +{rewards.xp}
                                    </div>
                                    <div className="font-pixel text-xs capitalize text-stone-400">
                                        {rewards.xp_skills && rewards.xp_skills.length > 1
                                            ? rewards.xp_skills.join("+") + " XP"
                                            : rewards.skill + " XP"}
                                        {rewards.levels_gained > 0 && (
                                            <span className="ml-1 text-yellow-400">
                                                <Sparkles className="inline h-3 w-3" /> Level Up!
                                            </span>
                                        )}
                                    </div>
                                </div>
                            </div>

                            {/* Gold Reward */}
                            <div className="flex min-w-[120px] flex-1 items-center gap-3 rounded-lg border-2 border-yellow-500/50 bg-yellow-900/30 px-4 py-3">
                                <Coins className="h-8 w-8 text-yellow-400" />
                                <div>
                                    <div className="font-pixel text-2xl text-yellow-400">
                                        +{rewards.gold}
                                    </div>
                                    <div className="font-pixel text-xs text-stone-400">Gold</div>
                                </div>
                            </div>

                            {/* Loot Items */}
                            {rewards.items.map((item, i) => (
                                <div
                                    key={i}
                                    className="flex min-w-[120px] flex-1 items-center gap-3 rounded-lg border-2 border-purple-500/50 bg-purple-900/30 px-4 py-3"
                                >
                                    <Package className="h-8 w-8 text-purple-400" />
                                    <div>
                                        <div className="font-pixel text-lg text-purple-300">
                                            {item.name}
                                        </div>
                                        <div className="font-pixel text-xs text-stone-400">
                                            x{item.quantity}
                                        </div>
                                    </div>
                                </div>
                            ))}

                            {/* Empty slot if no loot */}
                            {rewards.items.length === 0 && (
                                <div className="flex min-w-[120px] flex-1 items-center gap-3 rounded-lg border-2 border-dashed border-stone-600/50 bg-stone-800/30 px-4 py-3">
                                    <Package className="h-8 w-8 text-stone-500" />
                                    <div className="font-pixel text-sm text-stone-500">No loot</div>
                                </div>
                            )}
                        </div>

                        {/* Action Buttons */}
                        <div className="flex items-center justify-center gap-3">
                            <button
                                onClick={returnToCombat}
                                className="rounded-lg bg-green-600 px-6 py-2 font-pixel text-sm text-white shadow-lg shadow-green-900/50 transition hover:bg-green-500 hover:shadow-green-800/50"
                            >
                                Continue Fighting
                            </button>
                            <button
                                onClick={() => router.visit("/dashboard")}
                                className="rounded-lg border border-stone-600 bg-stone-700 px-6 py-2 font-pixel text-sm text-stone-300 transition hover:bg-stone-600"
                            >
                                Retreat
                            </button>
                        </div>
                    </div>
                )}

                {/* Defeat Screen */}
                {isDefeat && (
                    <div className="shrink-0 rounded-xl border-2 border-red-500/50 bg-gradient-to-b from-red-900/40 to-red-950/60 p-4">
                        {/* Header - Compact */}
                        <div className="mb-4 flex items-center justify-center gap-3">
                            <div className="flex h-12 w-12 items-center justify-center rounded-full bg-red-500/20 ring-2 ring-red-500/50">
                                <Skull className="h-6 w-6 text-red-400" />
                            </div>
                            <div>
                                <h2 className="font-pixel text-xl text-red-400">Defeat!</h2>
                                <p className="font-pixel text-xs text-stone-300">
                                    Killed by {session.monster.name}
                                </p>
                            </div>
                        </div>

                        {/* Info Box */}
                        <div className="mb-4 rounded-lg border border-red-500/30 bg-red-950/50 p-3 text-center">
                            <p className="font-pixel text-xs text-stone-400">
                                You've been taken to the infirmary. Your wounds will heal
                                automatically.
                            </p>
                        </div>

                        {/* Return Button */}
                        <div className="text-center">
                            <button
                                onClick={returnToCombat}
                                className="rounded-lg bg-stone-700 px-8 py-2 font-pixel text-sm text-white shadow-lg transition hover:bg-stone-600"
                            >
                                Return
                            </button>
                        </div>
                    </div>
                )}

                {/* Fled Screen */}
                {hasFled && (
                    <div className="shrink-0 rounded-xl border-2 border-yellow-500/50 bg-gradient-to-b from-yellow-900/40 to-yellow-950/60 p-4">
                        {/* Header - Compact */}
                        <div className="mb-4 flex items-center justify-center gap-3">
                            <div className="flex h-12 w-12 items-center justify-center rounded-full bg-yellow-500/20 ring-2 ring-yellow-500/50">
                                <DoorOpen className="h-6 w-6 text-yellow-400" />
                            </div>
                            <div>
                                <h2 className="font-pixel text-xl text-yellow-400">Escaped!</h2>
                                <p className="font-pixel text-xs text-stone-300">
                                    Successfully fled from combat
                                </p>
                            </div>
                        </div>

                        {/* Continue Button */}
                        <div className="text-center">
                            <button
                                onClick={returnToCombat}
                                className="rounded-lg bg-yellow-600 px-8 py-2 font-pixel text-sm text-white shadow-lg shadow-yellow-900/50 transition hover:bg-yellow-500"
                            >
                                Continue
                            </button>
                        </div>
                    </div>
                )}

                {/* Action Buttons */}
                {isActive && (
                    <div className="shrink-0 space-y-3">
                        {/* Food Panel */}
                        {showFood && (
                            <div className="rounded-lg border border-stone-700 bg-stone-800/90 p-4">
                                <div className="mb-3 flex items-center justify-between">
                                    <h3 className="font-pixel text-sm text-amber-300">
                                        Select Food
                                    </h3>
                                    <button
                                        onClick={() => setShowFood(false)}
                                        className="font-pixel text-xs text-stone-400 hover:text-white"
                                    >
                                        Cancel
                                    </button>
                                </div>
                                {food.length === 0 ? (
                                    <p className="font-pixel text-xs text-stone-400">
                                        No food available
                                    </p>
                                ) : (
                                    <div className="grid gap-2">
                                        {food.map((item) => (
                                            <button
                                                key={item.id}
                                                onClick={() => eatFood(item.id)}
                                                disabled={isActing}
                                                className="flex items-center justify-between rounded border border-stone-600 bg-stone-700/50 px-3 py-2 text-left transition hover:bg-stone-700"
                                            >
                                                <span className="font-pixel text-xs text-stone-300">
                                                    {item.name}
                                                </span>
                                                <span className="font-pixel text-xs">
                                                    <span className="text-green-400">
                                                        +{item.hp_bonus} HP
                                                    </span>
                                                    <span className="ml-2 text-stone-500">
                                                        x{item.quantity}
                                                    </span>
                                                </span>
                                            </button>
                                        ))}
                                    </div>
                                )}
                            </div>
                        )}

                        {/* Main Actions */}
                        <div className="grid grid-cols-3 gap-3">
                            <button
                                onClick={() => performAction("attack")}
                                disabled={isActing}
                                className={`flex flex-col items-center gap-1 rounded-lg border border-red-500/50 bg-red-900/30 px-4 py-3 transition ${
                                    isActing ? "opacity-50" : "hover:bg-red-900/50"
                                }`}
                            >
                                <Sword className="h-6 w-6 text-red-400" />
                                <span className="font-pixel text-xs text-red-300">Attack</span>
                            </button>

                            <button
                                onClick={() => setShowFood(!showFood)}
                                disabled={isActing || food.length === 0}
                                className={`flex flex-col items-center gap-1 rounded-lg border border-green-500/50 bg-green-900/30 px-4 py-3 transition ${
                                    isActing || food.length === 0
                                        ? "opacity-50"
                                        : "hover:bg-green-900/50"
                                }`}
                            >
                                <Apple className="h-6 w-6 text-green-400" />
                                <span className="font-pixel text-xs text-green-300">
                                    Eat ({food.length})
                                </span>
                            </button>

                            <button
                                onClick={() => performAction("flee")}
                                disabled={isActing}
                                className={`flex flex-col items-center gap-1 rounded-lg border border-yellow-500/50 bg-yellow-900/30 px-4 py-3 transition ${
                                    isActing ? "opacity-50" : "hover:bg-yellow-900/50"
                                }`}
                            >
                                <DoorOpen className="h-6 w-6 text-yellow-400" />
                                <span className="font-pixel text-xs text-yellow-300">Flee</span>
                            </button>
                        </div>

                        {isActing && (
                            <p className="text-center font-pixel text-xs text-stone-400">
                                Processing...
                            </p>
                        )}
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
