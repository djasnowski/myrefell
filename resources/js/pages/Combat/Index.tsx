import { Head, router, usePage } from "@inertiajs/react";
import { Skull, Sword, Shield, Zap, Heart } from "lucide-react";
import { useState } from "react";
import AppLayout from "@/layouts/app-layout";
import EquipmentPanel from "@/components/equipment-panel";
import type { EquippedSlots } from "@/components/equipment-panel";
import type { BreadcrumbItem } from "@/types";

interface Monster {
    id: number;
    name: string;
    type: string;
    combat_level: number;
    hp: number;
    max_hp: number;
    xp_reward: number;
    gold_drop_min: number;
    gold_drop_max: number;
    is_boss: boolean;
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
    cost: number;
}

interface AttackStyle {
    name: string;
    attack_type: "stab" | "slash" | "crush";
    weapon_style: "accurate" | "aggressive" | "controlled" | "defensive";
    xp_skills: string[];
}

interface PageProps {
    monsters: Monster[];
    player_stats: PlayerStats;
    equipment: Equipment;
    equipped_slots: EquippedSlots;
    energy: EnergyInfo;
    weapon_subtype: string;
    weapon_speed: number;
    available_attack_styles: AttackStyle[];
    [key: string]: unknown;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: "Dashboard", href: "/dashboard" },
    { title: "Combat", href: "/combat" },
];

const attackTypeColors: Record<string, string> = {
    stab: "text-teal-400",
    slash: "text-red-400",
    crush: "text-amber-400",
};

const attackTypeBg: Record<string, string> = {
    stab: "bg-teal-900/30 border-teal-500/50",
    slash: "bg-red-900/30 border-red-500/50",
    crush: "bg-amber-900/30 border-amber-500/50",
};

const speedLabels: Record<number, string> = {
    4: "Fast",
    5: "Normal",
    6: "Slow",
    7: "Very Slow",
};

const monsterTypeColors: Record<string, string> = {
    humanoid: "border-amber-500/50 bg-amber-900/20",
    beast: "border-green-500/50 bg-green-900/20",
    undead: "border-purple-500/50 bg-purple-900/20",
    dragon: "border-red-500/50 bg-red-900/20",
    demon: "border-rose-500/50 bg-rose-900/20",
    elemental: "border-cyan-500/50 bg-cyan-900/20",
    giant: "border-orange-500/50 bg-orange-900/20",
    goblinoid: "border-yellow-500/50 bg-yellow-900/20",
};

export default function CombatIndex() {
    const {
        monsters,
        player_stats,
        equipment,
        equipped_slots,
        energy,
        weapon_subtype,
        weapon_speed,
        available_attack_styles,
    } = usePage<PageProps>().props;
    const [selectedMonster, setSelectedMonster] = useState<Monster | null>(null);
    const [attackStyleIndex, setAttackStyleIndex] = useState(0);
    const [isStarting, setIsStarting] = useState(false);
    const [error, setError] = useState<string | null>(null);

    const canFight = energy.current >= energy.cost && player_stats.hp > 0;

    const startCombat = async () => {
        if (!selectedMonster || isStarting) return;

        setIsStarting(true);
        setError(null);

        try {
            const response = await fetch("/combat/start", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN":
                        document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')
                            ?.content || "",
                },
                body: JSON.stringify({
                    monster_id: selectedMonster.id,
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
            setError("Failed to start combat");
        } finally {
            setIsStarting(false);
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Combat" />
            <div className="flex h-full flex-1 gap-4 p-4">
                {/* Main Content */}
                <div className="flex min-w-0 flex-1 flex-col">
                    {/* Header */}
                    <div className="mb-6">
                        <h1 className="font-pixel text-2xl text-amber-400">Combat</h1>
                        <p className="font-pixel text-sm text-stone-400">
                            Battle monsters to gain experience and loot
                        </p>
                    </div>

                    {/* Player Stats - Full Width */}
                    <div className="mb-6 rounded-lg border border-stone-700 bg-stone-800/50 p-6">
                        <div className="grid grid-cols-2 gap-6 lg:grid-cols-5">
                            {/* HP */}
                            <div className="col-span-2 lg:col-span-1">
                                <div className="mb-2 flex items-center gap-2">
                                    <Heart className="h-5 w-5 text-red-400" />
                                    <span className="font-pixel text-sm text-stone-400">
                                        Health
                                    </span>
                                </div>
                                <div className="mb-1 h-3 w-full overflow-hidden rounded-full bg-stone-700">
                                    <div
                                        className="h-full bg-gradient-to-r from-red-600 to-red-400"
                                        style={{
                                            width: `${(player_stats.hp / player_stats.max_hp) * 100}%`,
                                        }}
                                    />
                                </div>
                                <div className="font-pixel text-lg text-white">
                                    {player_stats.hp} <span className="text-stone-500">/</span>{" "}
                                    <span className="text-stone-400">{player_stats.max_hp}</span>
                                </div>
                            </div>

                            {/* Energy */}
                            <div>
                                <div className="mb-2 flex items-center gap-2">
                                    <Zap className="h-5 w-5 text-yellow-400" />
                                    <span className="font-pixel text-sm text-stone-400">
                                        Energy
                                    </span>
                                </div>
                                <div className="font-pixel text-2xl text-yellow-400">
                                    {energy.current}
                                </div>
                                <div className="font-pixel text-xs text-stone-500">
                                    costs {energy.cost} per fight
                                </div>
                            </div>

                            {/* Attack */}
                            <div>
                                <div className="mb-2 flex items-center gap-2">
                                    <Sword className="h-5 w-5 text-red-400" />
                                    <span className="font-pixel text-sm text-stone-400">
                                        Attack
                                    </span>
                                </div>
                                <div className="font-pixel text-2xl text-white">
                                    {player_stats.attack}
                                    {equipment.atk_bonus > 0 && (
                                        <span className="ml-1 text-lg text-green-400">
                                            +{equipment.atk_bonus}
                                        </span>
                                    )}
                                </div>
                            </div>

                            {/* Strength */}
                            <div>
                                <div className="mb-2 flex items-center gap-2">
                                    <Skull className="h-5 w-5 text-orange-400" />
                                    <span className="font-pixel text-sm text-stone-400">
                                        Strength
                                    </span>
                                </div>
                                <div className="font-pixel text-2xl text-white">
                                    {player_stats.strength}
                                    {equipment.str_bonus > 0 && (
                                        <span className="ml-1 text-lg text-green-400">
                                            +{equipment.str_bonus}
                                        </span>
                                    )}
                                </div>
                            </div>

                            {/* Defense */}
                            <div>
                                <div className="mb-2 flex items-center gap-2">
                                    <Shield className="h-5 w-5 text-blue-400" />
                                    <span className="font-pixel text-sm text-stone-400">
                                        Defense
                                    </span>
                                </div>
                                <div className="font-pixel text-2xl text-white">
                                    {player_stats.defense}
                                    {equipment.def_bonus > 0 && (
                                        <span className="ml-1 text-lg text-green-400">
                                            +{equipment.def_bonus}
                                        </span>
                                    )}
                                </div>
                            </div>
                        </div>

                        {/* Combat Level Badge */}
                        <div className="mt-4 flex items-center justify-between border-t border-stone-700 pt-4">
                            <div className="font-pixel text-lg text-amber-400">
                                Combat Level {player_stats.combat_level}
                            </div>
                        </div>
                    </div>

                    {/* Attack Style Selection */}
                    <div className="mb-6 rounded-lg border border-stone-700 bg-stone-800/50 p-4">
                        <div className="mb-3 flex items-center justify-between">
                            <h3 className="font-pixel text-sm text-amber-300">Attack Style</h3>
                            <div className="flex items-center gap-2">
                                <span className="font-pixel text-[10px] text-stone-500 capitalize">
                                    {weapon_subtype === "unarmed" ? "Unarmed" : weapon_subtype}
                                </span>
                                <span
                                    className={`rounded px-1.5 py-0.5 font-pixel text-[10px] ${
                                        weapon_speed <= 4
                                            ? "bg-green-900/50 text-green-400"
                                            : weapon_speed <= 5
                                              ? "bg-stone-700 text-stone-400"
                                              : "bg-red-900/50 text-red-400"
                                    }`}
                                >
                                    {speedLabels[weapon_speed] || "Normal"}
                                </span>
                            </div>
                        </div>
                        <div className="grid gap-2 sm:grid-cols-2 lg:grid-cols-4">
                            {available_attack_styles.map((style, index) => (
                                <button
                                    key={index}
                                    onClick={() => setAttackStyleIndex(index)}
                                    className={`rounded-lg border-2 px-3 py-2 text-left transition ${
                                        attackStyleIndex === index
                                            ? `${attackTypeBg[style.attack_type]} ring-1 ring-amber-500/50`
                                            : "border-stone-600 bg-stone-800/50 hover:bg-stone-700/50"
                                    }`}
                                >
                                    <div className="flex items-center justify-between">
                                        <span
                                            className={`font-pixel text-sm ${
                                                attackStyleIndex === index
                                                    ? attackTypeColors[style.attack_type]
                                                    : "text-stone-300"
                                            }`}
                                        >
                                            {style.name}
                                        </span>
                                        <span
                                            className={`rounded px-1 py-0.5 font-pixel text-[10px] capitalize ${
                                                attackTypeColors[style.attack_type]
                                            }`}
                                        >
                                            {style.attack_type}
                                        </span>
                                    </div>
                                    <div className="mt-1 flex items-center justify-between font-pixel text-[10px] text-stone-500">
                                        <span className="capitalize">{style.weapon_style}</span>
                                        <span className="capitalize">
                                            {style.xp_skills.length > 1
                                                ? "Shared XP"
                                                : `${style.xp_skills[0]} XP`}
                                        </span>
                                    </div>
                                </button>
                            ))}
                        </div>
                    </div>

                    {/* Error Message */}
                    {error && (
                        <div className="mb-4 rounded-lg border border-red-500/50 bg-red-900/30 p-3 font-pixel text-sm text-red-300">
                            {error}
                        </div>
                    )}

                    {/* Monsters Grid */}
                    <div className="mb-6">
                        <h3 className="mb-4 font-pixel text-lg text-amber-300">
                            Available Monsters
                        </h3>
                        {monsters.length === 0 ? (
                            <div className="flex flex-1 items-center justify-center py-12">
                                <div className="text-center">
                                    <Skull className="mx-auto mb-3 h-16 w-16 text-stone-600" />
                                    <p className="font-pixel text-base text-stone-500">
                                        No monsters available here
                                    </p>
                                    <p className="font-pixel text-xs text-stone-600">
                                        Try a different location
                                    </p>
                                </div>
                            </div>
                        ) : (
                            <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                                {monsters.map((monster) => {
                                    const isSelected = selectedMonster?.id === monster.id;
                                    const colorClass =
                                        monsterTypeColors[monster.type] ||
                                        "border-stone-600/50 bg-stone-800/50";

                                    return (
                                        <div
                                            key={monster.id}
                                            className={`rounded-xl border-2 p-4 transition ${colorClass} ${
                                                isSelected ? "ring-2 ring-amber-500" : ""
                                            }`}
                                        >
                                            <button
                                                onClick={() =>
                                                    setSelectedMonster(isSelected ? null : monster)
                                                }
                                                className="w-full text-left"
                                            >
                                                <div className="mb-3 flex items-center justify-between">
                                                    <div>
                                                        <h4 className="font-pixel text-base text-amber-300">
                                                            {monster.is_boss && (
                                                                <span className="text-red-400">
                                                                    [BOSS]{" "}
                                                                </span>
                                                            )}
                                                            {monster.name}
                                                        </h4>
                                                        <p className="font-pixel text-[10px] capitalize text-stone-400">
                                                            {monster.type} - Level{" "}
                                                            {monster.combat_level}
                                                        </p>
                                                    </div>
                                                    <div className="rounded bg-stone-800/50 px-2 py-1">
                                                        <Heart className="inline h-3 w-3 text-red-400" />
                                                        <span className="ml-1 font-pixel text-xs text-stone-300">
                                                            {monster.max_hp}
                                                        </span>
                                                    </div>
                                                </div>

                                                <div className="flex items-center justify-between font-pixel text-xs text-stone-400">
                                                    <span>XP: {monster.xp_reward}</span>
                                                    <span>
                                                        Gold: {monster.gold_drop_min}-
                                                        {monster.gold_drop_max}
                                                    </span>
                                                </div>
                                            </button>

                                            {/* Fight button appears when selected */}
                                            {isSelected && (
                                                <button
                                                    onClick={startCombat}
                                                    disabled={!canFight || isStarting}
                                                    className={`mt-3 w-full rounded-lg py-2 font-pixel text-sm transition ${
                                                        canFight && !isStarting
                                                            ? "bg-red-600 text-white hover:bg-red-500"
                                                            : "cursor-not-allowed bg-stone-700 text-stone-500"
                                                    }`}
                                                >
                                                    {isStarting ? "Starting..." : "Fight!"}
                                                </button>
                                            )}
                                        </div>
                                    );
                                })}
                            </div>
                        )}
                    </div>
                </div>

                {/* Equipment Sidebar */}
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
            </div>
        </AppLayout>
    );
}
