import { Head, router, usePage } from "@inertiajs/react";
import { Crosshair, Dumbbell, Heart, Loader2, Shield, Sword, Zap } from "lucide-react";
import { useEffect, useRef, useState } from "react";
import AppLayout from "@/layouts/app-layout";
import { gameToast } from "@/components/ui/game-toast";
import type { BreadcrumbItem } from "@/types";

const TRAIN_COOLDOWN_MS = 3000;

interface Exercise {
    id: string;
    name: string;
    description: string;
    skill: string;
    skill_level: number;
    skill_xp: number;
    skill_progress: number;
    xp_to_next_level: number;
    energy_cost: number;
    base_xp: number;
    can_train: boolean;
}

interface StatInfo {
    level: number;
    xp: number;
    progress: number;
    xp_to_next_level: number;
}

interface CombatStats {
    attack: StatInfo;
    strength: StatInfo;
    defense: StatInfo;
    hitpoints: StatInfo;
    range: StatInfo;
    combat_level: number;
    [key: string]: StatInfo | number;
}

interface TrainResult {
    success: boolean;
    message: string;
    exercise?: string;
    xp_awarded?: number;
    skill?: string;
    new_level?: number;
    leveled_up?: boolean;
    energy_remaining?: number;
    skill_progress?: number;
    xp_to_next_level?: number;
}

interface PageProps {
    exercises: Exercise[];
    combat_stats: CombatStats;
    player_energy: number;
    max_energy: number;
    player_hp: number;
    max_hp: number;
    [key: string]: unknown;
}

const exerciseIcons: Record<string, typeof Sword> = {
    attack: Sword,
    strength: Dumbbell,
    defense: Shield,
    hitpoints: Heart,
    range: Crosshair,
};

const exerciseColors: Record<string, { bg: string; border: string; text: string }> = {
    attack: {
        bg: "from-red-900/50 to-stone-900",
        border: "border-red-600/50",
        text: "text-red-400",
    },
    strength: {
        bg: "from-orange-900/50 to-stone-900",
        border: "border-orange-600/50",
        text: "text-orange-400",
    },
    defense: {
        bg: "from-blue-900/50 to-stone-900",
        border: "border-blue-600/50",
        text: "text-blue-400",
    },
    hitpoints: {
        bg: "from-pink-900/50 to-stone-900",
        border: "border-pink-600/50",
        text: "text-pink-400",
    },
    range: {
        bg: "from-green-900/50 to-stone-900",
        border: "border-green-600/50",
        text: "text-green-400",
    },
};

export default function TrainingIndex() {
    const { exercises, combat_stats, player_energy, max_energy, player_hp, max_hp } =
        usePage<PageProps>().props;
    const [loading, setLoading] = useState<string | null>(null);
    const [currentEnergy, setCurrentEnergy] = useState(player_energy);
    const [currentStats, setCurrentStats] = useState(combat_stats);
    const [currentHp, setCurrentHp] = useState(player_hp);
    const [cooldown, setCooldown] = useState(0);
    const cooldownInterval = useRef<NodeJS.Timeout | null>(null);

    const startCooldown = () => {
        setCooldown(TRAIN_COOLDOWN_MS);
        if (cooldownInterval.current) clearInterval(cooldownInterval.current);
        const startTime = Date.now();
        cooldownInterval.current = setInterval(() => {
            const remaining = Math.max(0, TRAIN_COOLDOWN_MS - (Date.now() - startTime));
            setCooldown(remaining);
            if (remaining <= 0 && cooldownInterval.current) {
                clearInterval(cooldownInterval.current);
                cooldownInterval.current = null;
            }
        }, 50);
    };

    useEffect(() => {
        return () => {
            if (cooldownInterval.current) clearInterval(cooldownInterval.current);
        };
    }, []);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: "Dashboard", href: "/dashboard" },
        { title: "Training Grounds", href: "/training" },
    ];

    const handleTrain = async (exercise: string) => {
        if (loading || cooldown > 0) return;

        const exerciseData = exercises.find((e) => e.id === exercise);
        if (!exerciseData || currentEnergy < exerciseData.energy_cost) return;

        setLoading(exercise);

        try {
            const response = await fetch("/training/train", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN":
                        document
                            .querySelector('meta[name="csrf-token"]')
                            ?.getAttribute("content") || "",
                },
                body: JSON.stringify({ exercise }),
            });

            const data: TrainResult = await response.json();

            // Show toast notification
            const skillName = data.skill
                ? data.skill.charAt(0).toUpperCase() + data.skill.slice(1)
                : "Combat";
            gameToast.training({ ...data, skill: skillName });

            if (data.success) {
                startCooldown();
                if (data.energy_remaining !== undefined) {
                    setCurrentEnergy(data.energy_remaining);
                }

                // Update HP if training caused any HP change
                if (player_hp > 0) {
                    setCurrentHp(player_hp);
                }

                // Update the stat that was trained
                if (
                    data.skill &&
                    data.skill_progress !== undefined &&
                    data.xp_to_next_level !== undefined
                ) {
                    const skillKey = data.skill as "attack" | "strength" | "defense";
                    setCurrentStats((prev) => {
                        const updatedStat = {
                            ...prev[skillKey],
                            level: data.new_level || prev[skillKey].level,
                            progress: data.skill_progress!,
                            xp_to_next_level: data.xp_to_next_level!,
                        };

                        const newStats = {
                            ...prev,
                            [skillKey]: updatedStat,
                        };

                        newStats.combat_level = Math.floor(
                            ((skillKey === "attack" ? updatedStat.level : prev.attack.level) +
                                (skillKey === "strength"
                                    ? updatedStat.level
                                    : prev.strength.level) +
                                (skillKey === "defense" ? updatedStat.level : prev.defense.level)) /
                                3,
                        );

                        return newStats;
                    });
                }
            }

            // Reload sidebar data and sync HP
            router.reload({
                only: ["sidebar", "player_hp"],
                onSuccess: (page) => {
                    const props = page.props as PageProps;
                    if (props.player_hp !== undefined) {
                        setCurrentHp(props.player_hp);
                    }
                },
            });
        } catch {
            gameToast.error("An error occurred");
        } finally {
            setLoading(null);
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Training Grounds" />
            <div className="flex h-full flex-1 flex-col p-4">
                <div className="mx-auto w-full max-w-3xl">
                    {/* Header */}
                    <div className="mb-6 rounded-xl border-2 border-amber-600/50 bg-gradient-to-br from-amber-900/30 to-stone-900 p-6">
                        <div className="flex items-center gap-4">
                            <div className="rounded-lg bg-stone-800/50 p-4">
                                <Sword className="h-12 w-12 text-amber-400" />
                            </div>
                            <div>
                                <h1 className="font-pixel text-2xl text-amber-400">
                                    Training Grounds
                                </h1>
                                <p className="font-pixel text-xs text-stone-400">
                                    Train your combat skills to become stronger
                                </p>
                            </div>
                        </div>
                    </div>

                    {/* Combat Stats Overview */}
                    <div className="mb-6 grid grid-cols-4 gap-3">
                        <div className="rounded-lg border border-stone-600 bg-stone-800/50 p-3 text-center">
                            <div className="font-pixel text-xs text-stone-400">Combat Level</div>
                            <div className="font-pixel text-2xl text-amber-400">
                                {currentStats.combat_level}
                            </div>
                        </div>
                        <div className="rounded-lg border border-red-600/30 bg-stone-800/50 p-3 text-center">
                            <div className="font-pixel text-xs text-red-400">Attack</div>
                            <div className="font-pixel text-xl text-red-300">
                                {currentStats.attack.level}
                            </div>
                        </div>
                        <div className="rounded-lg border border-orange-600/30 bg-stone-800/50 p-3 text-center">
                            <div className="font-pixel text-xs text-orange-400">Strength</div>
                            <div className="font-pixel text-xl text-orange-300">
                                {currentStats.strength.level}
                            </div>
                        </div>
                        <div className="rounded-lg border border-blue-600/30 bg-stone-800/50 p-3 text-center">
                            <div className="font-pixel text-xs text-blue-400">Defense</div>
                            <div className="font-pixel text-xl text-blue-300">
                                {currentStats.defense.level}
                            </div>
                        </div>
                    </div>

                    {/* HP and Energy Bars */}
                    <div className="mb-6 grid grid-cols-2 gap-3">
                        <div className="rounded-lg border border-stone-700 bg-stone-800/50 p-3">
                            <div className="mb-1 flex items-center justify-between">
                                <div className="flex items-center gap-1 font-pixel text-xs text-red-400">
                                    <Heart className="h-3 w-3" />
                                    HP
                                </div>
                                <div className="font-pixel text-xs text-stone-400">
                                    {currentHp} / {max_hp}
                                </div>
                            </div>
                            <div className="h-2 w-full overflow-hidden rounded-full bg-stone-700">
                                <div
                                    className="h-full bg-gradient-to-r from-red-600 to-red-400 transition-all"
                                    style={{ width: `${(currentHp / max_hp) * 100}%` }}
                                />
                            </div>
                        </div>
                        <div className="rounded-lg border border-stone-700 bg-stone-800/50 p-3">
                            <div className="mb-1 flex items-center justify-between">
                                <div className="flex items-center gap-1 font-pixel text-xs text-yellow-400">
                                    <Zap className="h-3 w-3" />
                                    Energy
                                </div>
                                <div className="font-pixel text-xs text-stone-400">
                                    {currentEnergy} / {max_energy}
                                </div>
                            </div>
                            <div className="h-2 w-full overflow-hidden rounded-full bg-stone-700">
                                <div
                                    className="h-full bg-gradient-to-r from-yellow-600 to-yellow-400 transition-all"
                                    style={{ width: `${(currentEnergy / max_energy) * 100}%` }}
                                />
                            </div>
                        </div>
                    </div>

                    {/* Training Exercises */}
                    <div className="space-y-4">
                        {exercises.map((exercise) => {
                            const Icon = exerciseIcons[exercise.id];
                            const colors = exerciseColors[exercise.id];
                            const stat = currentStats[exercise.skill as keyof CombatStats] as {
                                level: number;
                                progress: number;
                                xp_to_next_level: number;
                            };
                            const canTrain = currentEnergy >= exercise.energy_cost;

                            return (
                                <div
                                    key={exercise.id}
                                    className={`rounded-xl border-2 bg-gradient-to-br ${colors.bg} ${colors.border} px-4 py-6`}
                                >
                                    <div className="flex items-start gap-4">
                                        <div className="rounded-lg bg-stone-800/50 p-3">
                                            <Icon className={`h-8 w-8 ${colors.text}`} />
                                        </div>
                                        <div className="flex-1">
                                            <div className="flex items-start justify-between gap-4">
                                                <div className="flex-1">
                                                    <h3
                                                        className={`font-pixel text-lg ${colors.text}`}
                                                    >
                                                        {exercise.name}
                                                    </h3>
                                                    <p className="max-w-md py-1 font-pixel text-[10px] text-stone-400">
                                                        {exercise.description}
                                                    </p>
                                                </div>
                                                <div className="whitespace-nowrap font-pixel text-lg text-stone-300">
                                                    Level {stat.level}
                                                </div>
                                            </div>

                                            {/* Progress bar with XP info */}
                                            <div className="relative mt-2 h-4 w-full overflow-hidden rounded-full bg-stone-700">
                                                <div
                                                    className={`h-full transition-all ${
                                                        exercise.id === "attack"
                                                            ? "bg-red-500"
                                                            : exercise.id === "strength"
                                                              ? "bg-orange-500"
                                                              : "bg-blue-500"
                                                    }`}
                                                    style={{ width: `${stat.progress}%` }}
                                                />
                                                <div className="absolute inset-0 flex items-center justify-center font-pixel text-[10px] text-white drop-shadow-[0_1px_1px_rgba(0,0,0,0.8)]">
                                                    {stat.xp_to_next_level.toLocaleString()} XP to
                                                    lvl {stat.level + 1}
                                                </div>
                                            </div>

                                            <div className="mt-3 flex items-center justify-between">
                                                <div className="flex items-center gap-3">
                                                    <span className="font-pixel text-[10px] text-stone-400">
                                                        <Zap className="mr-1 inline h-3 w-3 text-yellow-400" />
                                                        {exercise.energy_cost} energy
                                                    </span>
                                                    <span className="font-pixel text-[10px] text-amber-400">
                                                        +{exercise.base_xp} XP
                                                    </span>
                                                </div>
                                                <button
                                                    onClick={() => handleTrain(exercise.id)}
                                                    disabled={
                                                        !canTrain ||
                                                        loading !== null ||
                                                        cooldown > 0
                                                    }
                                                    className={`relative overflow-hidden rounded-lg px-4 py-2 font-pixel text-xs transition ${
                                                        canTrain &&
                                                        loading === null &&
                                                        cooldown <= 0
                                                            ? `${colors.border} bg-stone-800/80 hover:bg-stone-700/80 ${colors.text}`
                                                            : "cursor-not-allowed border-stone-700 bg-stone-800/50 text-stone-500"
                                                    } border`}
                                                >
                                                    {cooldown > 0 && (
                                                        <div
                                                            className="absolute inset-0 bg-stone-600/30"
                                                            style={{
                                                                width: `${(cooldown / TRAIN_COOLDOWN_MS) * 100}%`,
                                                            }}
                                                        />
                                                    )}
                                                    <span className="relative">
                                                        {loading === exercise.id ? (
                                                            <span className="flex items-center gap-1">
                                                                <Loader2 className="h-3 w-3 animate-spin" />
                                                                Training...
                                                            </span>
                                                        ) : cooldown > 0 ? (
                                                            `${(cooldown / 1000).toFixed(1)}s`
                                                        ) : (
                                                            "Train"
                                                        )}
                                                    </span>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            );
                        })}
                    </div>

                    {/* Info Box */}
                    <div className="mt-6 rounded-lg border border-stone-600 bg-stone-800/30 p-4">
                        <h3 className="mb-2 font-pixel text-sm text-stone-300">About Training</h3>
                        <ul className="space-y-1 font-pixel text-[10px] text-stone-400">
                            <li>- Each training session costs 10 energy</li>
                            <li>- Combat skills (Attack, Strength, Defense) start at level 5</li>
                            <li>- Higher levels provide diminishing XP returns</li>
                            <li>- Combat Level is the average of your three combat stats</li>
                        </ul>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
