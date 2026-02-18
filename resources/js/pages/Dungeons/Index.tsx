import { Head, Link, router, usePage } from "@inertiajs/react";
import {
    Castle,
    Coins,
    Heart,
    Package,
    Shield,
    Skull,
    Sparkles,
    Sword,
    Trophy,
    X,
    Zap,
} from "lucide-react";
import { useState } from "react";
import AppLayout from "@/layouts/app-layout";
import EquipmentPanel from "@/components/equipment-panel";
import type { EquippedSlots } from "@/components/equipment-panel";
import type { BreadcrumbItem } from "@/types";

interface FloorMonsterSpawn {
    id: number;
    monster: {
        id: number;
        name: string;
        combat_level: number;
    };
}

interface DungeonFloor {
    id: number;
    floor_number: number;
    name: string | null;
    monster_count: number;
    is_boss_floor: boolean;
    monsters: FloorMonsterSpawn[];
}

interface Monster {
    id: number;
    name: string;
    combat_level: number;
    is_boss: boolean;
}

interface Kingdom {
    id: number;
    name: string;
}

interface Dungeon {
    id: number;
    name: string;
    description: string | null;
    theme: string;
    difficulty: "easy" | "normal" | "hard" | "nightmare";
    min_combat_level: number;
    recommended_level: number;
    floor_count: number;
    xp_reward_base: number;
    gold_reward_min: number;
    gold_reward_max: number;
    energy_cost: number;
    boss_monster: Monster | null;
    kingdom: Kingdom | null;
    floors?: DungeonFloor[];
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

interface EnergyInfo {
    current: number;
}

interface LootItem {
    name: string;
    quantity: number;
}

interface DungeonCompletion {
    dungeon_name: string;
    total_rewards: {
        xp: number;
        gold: number;
        skill: string;
    };
    loot_items: LootItem[];
}

interface AttackStyle {
    name: string;
    attack_type: "stab" | "slash" | "crush";
    weapon_style: "accurate" | "aggressive" | "controlled" | "defensive";
    xp_skills: string[];
}

interface PageProps {
    dungeons: Dungeon[];
    kingdom: Kingdom;
    player_stats: PlayerStats;
    equipment: Equipment;
    equipped_slots: EquippedSlots;
    energy: EnergyInfo;
    loot_count: number;
    dungeon_completion: DungeonCompletion | null;
    weapon_subtype: string;
    weapon_speed: number;
    available_attack_styles: AttackStyle[];
    [key: string]: unknown;
}

// Breadcrumbs are generated dynamically based on kingdom

const difficultyColors: Record<string, string> = {
    easy: "border-green-500/50 bg-green-900/20 text-green-400",
    normal: "border-yellow-500/50 bg-yellow-900/20 text-yellow-400",
    hard: "border-red-500/50 bg-red-900/20 text-red-400",
    nightmare: "border-purple-500/50 bg-purple-900/20 text-purple-400",
};

const themeIcons: Record<string, string> = {
    goblin_fortress: "Goblin",
    undead_crypt: "Undead",
    dragon_lair: "Dragon",
    bandit_hideout: "Bandit",
    ancient_ruins: "Ruins",
    elemental_cavern: "Elemental",
    demon_pit: "Demon",
};

export default function DungeonsIndex() {
    const {
        dungeons,
        kingdom,
        player_stats,
        equipment,
        equipped_slots,
        energy,
        loot_count,
        dungeon_completion,
        weapon_subtype,
        weapon_speed,
        available_attack_styles,
    } = usePage<PageProps>().props;
    const [selectedDungeon, setSelectedDungeon] = useState<Dungeon | null>(null);
    const [attackStyleIndex, setAttackStyleIndex] = useState(0);
    const [isEntering, setIsEntering] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const [showCompletionModal, setShowCompletionModal] = useState(!!dungeon_completion);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: "Dashboard", href: "/dashboard" },
        { title: kingdom.name, href: `/kingdoms/${kingdom.id}` },
        { title: "Dungeons", href: `/kingdoms/${kingdom.id}/dungeons` },
    ];

    const canEnter = (dungeon: Dungeon) =>
        energy.current >= dungeon.energy_cost &&
        player_stats.hp > 0 &&
        player_stats.combat_level >= dungeon.min_combat_level;

    const enterDungeon = async () => {
        if (!selectedDungeon || isEntering) return;

        setIsEntering(true);
        setError(null);

        try {
            const response = await fetch(`/kingdoms/${kingdom.id}/dungeons/enter`, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN":
                        document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')
                            ?.content || "",
                },
                body: JSON.stringify({
                    dungeon_id: selectedDungeon.id,
                    attack_style_index: attackStyleIndex,
                }),
            });

            const data = await response.json();

            if (data.success) {
                router.reload();
            } else {
                setError(data.message);
            }
        } catch {
            setError("Failed to enter dungeon");
        } finally {
            setIsEntering(false);
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dungeons" />
            <div className="flex h-full flex-1 flex-col p-4">
                {/* Header */}
                <div className="mb-6 flex items-start justify-between">
                    <div>
                        <h1 className="font-pixel text-2xl text-amber-400">Dungeons</h1>
                        <p className="font-pixel text-sm text-stone-400">
                            {kingdom.name} - Explore dangerous dungeons for great rewards
                        </p>
                    </div>
                    <Link
                        href={`/kingdoms/${kingdom.id}/dungeons/loot`}
                        className="flex items-center gap-2 rounded-lg border border-stone-700 bg-stone-800/50 px-4 py-2 font-pixel text-sm text-stone-300 transition hover:border-amber-500/50 hover:bg-stone-700/50"
                    >
                        <Package className="h-4 w-4" />
                        Loot Storage
                        {loot_count > 0 && (
                            <span className="rounded-full bg-amber-600 px-2 py-0.5 text-xs text-white">
                                {loot_count}
                            </span>
                        )}
                    </Link>
                </div>

                <div className="flex flex-1 gap-4">
                    {/* Equipment Sidebar - Left */}
                    <div className="hidden w-64 shrink-0 lg:block">
                        <div className="sticky top-4">
                            <EquipmentPanel
                                equippedSlots={equipped_slots}
                                combatStats={{
                                    attack: player_stats.attack,
                                    strength: player_stats.strength,
                                    defense: player_stats.defense,
                                    hp: player_stats.hp,
                                    max_hp: player_stats.max_hp,
                                    atk_bonus: equipment.atk_bonus,
                                    str_bonus: equipment.str_bonus,
                                    def_bonus: equipment.def_bonus,
                                    hp_bonus: equipment.hp_bonus,
                                }}
                            />
                        </div>
                    </div>

                    {/* Main Content */}
                    <div className="flex min-w-0 flex-1 flex-col">
                        {/* Player Status */}
                        <div className="mb-6 rounded-lg border border-stone-700 bg-stone-800/50 p-4">
                            <div className="mb-3 flex items-center justify-between">
                                <h3 className="font-pixel text-sm text-amber-300">Status</h3>
                                <span className="font-pixel text-sm text-amber-400">
                                    Combat Level {player_stats.combat_level}
                                </span>
                            </div>
                            <div className="grid gap-4 sm:grid-cols-2">
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
                                <div>
                                    <div className="mb-1 flex items-center justify-between font-pixel text-xs">
                                        <span className="flex items-center gap-1 text-yellow-400">
                                            <Zap className="h-3 w-3" /> Energy
                                        </span>
                                        <span className="text-stone-300">{energy.current}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {/* Attack Style Selection */}
                        <div className="mb-6 rounded-lg border border-stone-700 bg-stone-800/50 p-4">
                            <div className="mb-3 flex items-center justify-between">
                                <h3 className="font-pixel text-sm text-amber-300">Attack Style</h3>
                                <span className="font-pixel text-[10px] text-stone-500 capitalize">
                                    {weapon_subtype === "unarmed" ? "Unarmed" : weapon_subtype}
                                </span>
                            </div>
                            <div className="grid gap-2 sm:grid-cols-2 lg:grid-cols-4">
                                {available_attack_styles.map((style, index) => {
                                    const typeColors: Record<string, string> = {
                                        stab: "text-teal-400",
                                        slash: "text-red-400",
                                        crush: "text-amber-400",
                                    };
                                    const typeBg: Record<string, string> = {
                                        stab: "bg-teal-900/30 border-teal-500/50",
                                        slash: "bg-red-900/30 border-red-500/50",
                                        crush: "bg-amber-900/30 border-amber-500/50",
                                    };
                                    return (
                                        <button
                                            key={index}
                                            onClick={() => setAttackStyleIndex(index)}
                                            className={`rounded-lg border-2 px-3 py-2 text-left transition ${
                                                attackStyleIndex === index
                                                    ? `${typeBg[style.attack_type]} ring-1 ring-amber-500/50`
                                                    : "border-stone-600 bg-stone-800/50 hover:bg-stone-700/50"
                                            }`}
                                        >
                                            <div className="flex items-center justify-between">
                                                <span
                                                    className={`font-pixel text-sm ${
                                                        attackStyleIndex === index
                                                            ? typeColors[style.attack_type]
                                                            : "text-stone-300"
                                                    }`}
                                                >
                                                    {style.name}
                                                </span>
                                                <span
                                                    className={`rounded px-1 py-0.5 font-pixel text-[10px] capitalize ${
                                                        typeColors[style.attack_type]
                                                    }`}
                                                >
                                                    {style.attack_type}
                                                </span>
                                            </div>
                                            <div className="mt-1 flex items-center justify-between font-pixel text-[10px] text-stone-500">
                                                <span className="capitalize">
                                                    {style.weapon_style}
                                                </span>
                                                <span className="capitalize">
                                                    {style.xp_skills.length > 1
                                                        ? "Shared XP"
                                                        : `${style.xp_skills[0]} XP`}
                                                </span>
                                            </div>
                                        </button>
                                    );
                                })}
                            </div>
                        </div>

                        {/* Error Message */}
                        {error && (
                            <div className="mb-4 rounded-lg border border-red-500/50 bg-red-900/30 p-3 font-pixel text-sm text-red-300">
                                {error}
                            </div>
                        )}

                        {/* Dungeons Grid */}
                        <div className="mb-6">
                            <h3 className="mb-4 font-pixel text-lg text-amber-300">
                                Available Dungeons
                            </h3>
                            {dungeons.length === 0 ? (
                                <div className="flex flex-1 items-center justify-center py-12">
                                    <div className="text-center">
                                        <Castle className="mx-auto mb-3 h-16 w-16 text-stone-600" />
                                        <p className="font-pixel text-base text-stone-500">
                                            No dungeons available here
                                        </p>
                                        <p className="font-pixel text-xs text-stone-600">
                                            Try a different location or level up
                                        </p>
                                    </div>
                                </div>
                            ) : (
                                <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                                    {dungeons.map((dungeon) => {
                                        const isSelected = selectedDungeon?.id === dungeon.id;
                                        const colorClass =
                                            difficultyColors[dungeon.difficulty] ||
                                            "border-stone-600/50 bg-stone-800/50";
                                        const canEnterThis = canEnter(dungeon);

                                        return (
                                            <div
                                                key={dungeon.id}
                                                className={`rounded-xl border-2 p-4 transition ${colorClass} ${
                                                    isSelected ? "ring-2 ring-amber-500" : ""
                                                } ${!canEnterThis ? "opacity-50" : ""}`}
                                            >
                                                <button
                                                    onClick={() =>
                                                        setSelectedDungeon(
                                                            isSelected ? null : dungeon,
                                                        )
                                                    }
                                                    disabled={!canEnterThis}
                                                    className={`w-full text-left ${!canEnterThis ? "cursor-not-allowed" : ""}`}
                                                >
                                                    <div className="mb-3 flex items-center justify-between">
                                                        <div>
                                                            <h4 className="font-pixel text-base text-amber-300">
                                                                {dungeon.name}
                                                            </h4>
                                                            <p className="font-pixel text-[10px] text-stone-400">
                                                                {themeIcons[dungeon.theme] ||
                                                                    dungeon.theme}{" "}
                                                                - {dungeon.floor_count} Floors
                                                            </p>
                                                        </div>
                                                        <div className="rounded bg-stone-800/50 px-2 py-1">
                                                            <span className="font-pixel text-xs capitalize">
                                                                {dungeon.difficulty}
                                                            </span>
                                                        </div>
                                                    </div>

                                                    {dungeon.description && (
                                                        <p className="mb-3 font-pixel text-[10px] text-stone-400">
                                                            {dungeon.description}
                                                        </p>
                                                    )}

                                                    <div className="mb-2 grid grid-cols-2 gap-2 font-pixel text-xs text-stone-400">
                                                        <span>
                                                            Level: {dungeon.min_combat_level}+
                                                        </span>
                                                        <span>
                                                            Rec: {dungeon.recommended_level}
                                                        </span>
                                                        <span>Energy: {dungeon.energy_cost}</span>
                                                        <span>XP: {dungeon.xp_reward_base}+</span>
                                                    </div>

                                                    <div className="flex items-center justify-between font-pixel text-xs text-stone-400">
                                                        <span>
                                                            Gold: {dungeon.gold_reward_min}-
                                                            {dungeon.gold_reward_max}
                                                        </span>
                                                        {dungeon.boss_monster && (
                                                            <span className="text-red-400">
                                                                Boss: {dungeon.boss_monster.name}
                                                            </span>
                                                        )}
                                                    </div>

                                                    {/* Monster list from floor spawns */}
                                                    {dungeon.floors &&
                                                        dungeon.floors.length > 0 &&
                                                        (() => {
                                                            const uniqueMonsters = new Map<
                                                                number,
                                                                {
                                                                    name: string;
                                                                    combat_level: number;
                                                                }
                                                            >();
                                                            dungeon.floors.forEach((floor) => {
                                                                floor.monsters?.forEach((spawn) => {
                                                                    if (
                                                                        spawn.monster &&
                                                                        !uniqueMonsters.has(
                                                                            spawn.monster.id,
                                                                        )
                                                                    ) {
                                                                        uniqueMonsters.set(
                                                                            spawn.monster.id,
                                                                            spawn.monster,
                                                                        );
                                                                    }
                                                                });
                                                            });
                                                            const monsterList = Array.from(
                                                                uniqueMonsters.values(),
                                                            ).sort(
                                                                (a, b) =>
                                                                    a.combat_level - b.combat_level,
                                                            );
                                                            if (monsterList.length === 0)
                                                                return null;
                                                            return (
                                                                <div className="mt-2 border-t border-stone-700/50 pt-2">
                                                                    <span className="font-pixel text-[10px] text-stone-500">
                                                                        Monsters:{" "}
                                                                    </span>
                                                                    <span className="font-pixel text-[10px] text-stone-400">
                                                                        {monsterList
                                                                            .map(
                                                                                (m) =>
                                                                                    `${m.name} (Lv${m.combat_level})`,
                                                                            )
                                                                            .join(", ")}
                                                                    </span>
                                                                </div>
                                                            );
                                                        })()}
                                                </button>

                                                {/* Enter button appears when selected */}
                                                {isSelected && (
                                                    <button
                                                        onClick={enterDungeon}
                                                        disabled={!canEnterThis || isEntering}
                                                        className={`mt-3 w-full rounded-lg py-2 font-pixel text-sm transition ${
                                                            canEnterThis && !isEntering
                                                                ? "bg-amber-600 text-white hover:bg-amber-500"
                                                                : "cursor-not-allowed bg-stone-700 text-stone-500"
                                                        }`}
                                                    >
                                                        {isEntering
                                                            ? "Entering..."
                                                            : "Enter Dungeon"}
                                                    </button>
                                                )}
                                            </div>
                                        );
                                    })}
                                </div>
                            )}
                        </div>
                    </div>
                </div>

                {/* Dungeon Completion Modal */}
                {showCompletionModal && dungeon_completion && (
                    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/70 p-4">
                        <div className="w-full max-w-lg rounded-xl border-2 border-green-500/50 bg-gradient-to-b from-stone-800 to-stone-900 p-6 shadow-2xl">
                            {/* Header */}
                            <div className="mb-6 flex items-center justify-between">
                                <div className="flex items-center gap-3">
                                    <div className="flex h-14 w-14 items-center justify-center rounded-full bg-green-500/20 ring-2 ring-green-500/50">
                                        <Trophy className="h-7 w-7 text-green-400" />
                                    </div>
                                    <div>
                                        <h2 className="font-pixel text-2xl text-green-400">
                                            Dungeon Complete!
                                        </h2>
                                        <p className="font-pixel text-sm text-stone-300">
                                            You conquered {dungeon_completion.dungeon_name}
                                        </p>
                                    </div>
                                </div>
                                <button
                                    onClick={() => setShowCompletionModal(false)}
                                    className="rounded-lg p-2 text-stone-400 transition hover:bg-stone-700 hover:text-white"
                                >
                                    <X className="h-5 w-5" />
                                </button>
                            </div>

                            {/* Rewards */}
                            <div className="mb-6 space-y-3">
                                {/* XP Reward */}
                                <div
                                    className={`flex items-center gap-4 rounded-lg border-2 px-4 py-3 ${
                                        dungeon_completion.total_rewards.skill === "attack"
                                            ? "border-red-500/50 bg-red-900/30"
                                            : dungeon_completion.total_rewards.skill === "strength"
                                              ? "border-orange-500/50 bg-orange-900/30"
                                              : "border-blue-500/50 bg-blue-900/30"
                                    }`}
                                >
                                    {dungeon_completion.total_rewards.skill === "attack" ? (
                                        <Sword className="h-10 w-10 text-red-400" />
                                    ) : dungeon_completion.total_rewards.skill === "strength" ? (
                                        <Skull className="h-10 w-10 text-orange-400" />
                                    ) : (
                                        <Shield className="h-10 w-10 text-blue-400" />
                                    )}
                                    <div>
                                        <div
                                            className={`font-pixel text-3xl ${
                                                dungeon_completion.total_rewards.skill === "attack"
                                                    ? "text-red-400"
                                                    : dungeon_completion.total_rewards.skill ===
                                                        "strength"
                                                      ? "text-orange-400"
                                                      : "text-blue-400"
                                            }`}
                                        >
                                            +{dungeon_completion.total_rewards.xp}
                                        </div>
                                        <div className="font-pixel text-sm capitalize text-stone-400">
                                            {dungeon_completion.total_rewards.skill} XP
                                        </div>
                                    </div>
                                </div>

                                {/* Gold Reward */}
                                <div className="flex items-center gap-4 rounded-lg border-2 border-yellow-500/50 bg-yellow-900/30 px-4 py-3">
                                    <Coins className="h-10 w-10 text-yellow-400" />
                                    <div>
                                        <div className="font-pixel text-3xl text-yellow-400">
                                            +{dungeon_completion.total_rewards.gold}
                                        </div>
                                        <div className="font-pixel text-sm text-stone-400">
                                            Gold
                                        </div>
                                    </div>
                                </div>

                                {/* Loot Items */}
                                {dungeon_completion.loot_items.length > 0 && (
                                    <div className="rounded-lg border-2 border-purple-500/50 bg-purple-900/30 p-4">
                                        <div className="mb-2 flex items-center gap-2">
                                            <Package className="h-5 w-5 text-purple-400" />
                                            <span className="font-pixel text-sm text-purple-300">
                                                Loot (stored in Loot Storage)
                                            </span>
                                        </div>
                                        <div className="grid grid-cols-2 gap-2">
                                            {dungeon_completion.loot_items.map((item, i) => (
                                                <div
                                                    key={i}
                                                    className="flex items-center justify-between rounded bg-purple-950/50 px-3 py-2"
                                                >
                                                    <span className="font-pixel text-sm text-purple-200">
                                                        {item.name}
                                                    </span>
                                                    <span className="font-pixel text-sm text-purple-400">
                                                        x{item.quantity}
                                                    </span>
                                                </div>
                                            ))}
                                        </div>
                                    </div>
                                )}

                                {dungeon_completion.loot_items.length === 0 && (
                                    <div className="rounded-lg border-2 border-dashed border-stone-600/50 bg-stone-800/30 p-4 text-center">
                                        <Package className="mx-auto mb-2 h-8 w-8 text-stone-500" />
                                        <span className="font-pixel text-sm text-stone-500">
                                            No loot this run
                                        </span>
                                    </div>
                                )}
                            </div>

                            {/* Action Buttons */}
                            <div className="flex gap-3">
                                <button
                                    onClick={() => setShowCompletionModal(false)}
                                    className="flex-1 rounded-lg bg-green-600 px-6 py-3 font-pixel text-sm text-white shadow-lg shadow-green-900/50 transition hover:bg-green-500"
                                >
                                    <Sparkles className="mr-2 inline h-4 w-4" />
                                    Continue
                                </button>
                                {dungeon_completion.loot_items.length > 0 && (
                                    <Link
                                        href={`/kingdoms/${kingdom.id}/dungeons/loot`}
                                        className="flex items-center justify-center rounded-lg border border-purple-500/50 bg-purple-900/30 px-6 py-3 font-pixel text-sm text-purple-300 transition hover:bg-purple-900/50"
                                    >
                                        <Package className="mr-2 h-4 w-4" />
                                        View Loot
                                    </Link>
                                )}
                            </div>
                        </div>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
