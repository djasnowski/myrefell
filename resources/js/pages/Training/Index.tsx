import { Head, usePage } from "@inertiajs/react";
import { Crosshair, Dumbbell, Heart, Shield, Sword, Zap } from "lucide-react";
import { useCallback, useEffect, useState } from "react";
import AppLayout from "@/layouts/app-layout";
import { ActionQueueControls } from "@/components/action-queue-controls";
import { gameToast } from "@/components/ui/game-toast";
import { useActionQueue, type ActionResult, type QueueStats } from "@/hooks/use-action-queue";
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
    const [currentEnergy, setCurrentEnergy] = useState(player_energy);
    const [currentStats, setCurrentStats] = useState(combat_stats);
    const [currentHp, setCurrentHp] = useState(player_hp);
    const [selectedExercise, setSelectedExercise] = useState<string | null>(null);
    const [queueXp, setQueueXp] = useState(0);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: "Dashboard", href: "/dashboard" },
        { title: "Training Grounds", href: "/training" },
    ];

    const buildBody = useCallback(() => ({ exercise: selectedExercise }), [selectedExercise]);

    const onActionComplete = useCallback((data: ActionResult) => {
        if (data.success && data.energy_remaining !== undefined) {
            setCurrentEnergy(data.energy_remaining);
        }
    }, []);

    const onQueueComplete = useCallback(
        (stats: QueueStats) => {
            setQueueXp(0);
            if (stats.completed === 0) return;

            const skillName = stats.lastLevelUp?.skill ?? selectedExercise ?? "Combat";
            const displaySkill = skillName.charAt(0).toUpperCase() + skillName.slice(1);

            if (stats.completed === 1) {
                gameToast.training({
                    success: true,
                    message: `Trained ${displaySkill}!`,
                    xp_awarded: stats.totalXp,
                    leveled_up: !!stats.lastLevelUp,
                    new_level: stats.lastLevelUp?.level,
                    skill: displaySkill,
                });
            } else {
                gameToast.success(`Trained ${displaySkill} ${stats.completed} times`, {
                    xp: stats.totalXp,
                    levelUp: stats.lastLevelUp,
                });
            }
        },
        [selectedExercise],
    );

    const {
        startQueue,
        cancelQueue,
        isQueueActive,
        queueProgress,
        isActionLoading,
        cooldown,
        performSingleAction,
    } = useActionQueue({
        url: "/training/train",
        buildBody,
        cooldownMs: TRAIN_COOLDOWN_MS,
        onActionComplete: useCallback(
            (data: ActionResult) => {
                onActionComplete(data);
                if (data.success) {
                    setQueueXp((prev) => prev + (data.xp_awarded ?? 0));
                }
            },
            [onActionComplete],
        ),
        onQueueComplete,
        reloadProps: ["sidebar", "player_hp", "exercises", "combat_stats"],
    });

    // Sync state when props change
    useEffect(() => {
        setCurrentStats(combat_stats);
    }, [combat_stats]);

    useEffect(() => {
        setCurrentHp(player_hp);
    }, [player_hp]);

    useEffect(() => {
        setCurrentEnergy(player_energy);
    }, [player_energy]);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Training Grounds" />
            <div className="flex h-full flex-1 flex-col p-4">
                <div className="mx-auto w-full max-w-3xl">
                    {/* Header */}
                    <div className="mb-6 rounded-xl border-2 border-amber-600/50 bg-gradient-to-br from-amber-900/30 to-stone-900 p-4 sm:p-6">
                        <div className="flex items-center gap-3 sm:gap-4">
                            <div className="rounded-lg bg-stone-800/50 p-3 sm:p-4">
                                <Sword className="h-8 w-8 text-amber-400 sm:h-12 sm:w-12" />
                            </div>
                            <div>
                                <h1 className="font-pixel text-xl text-amber-400 sm:text-2xl">
                                    Training Grounds
                                </h1>
                                <p className="font-pixel text-[10px] text-stone-400 sm:text-xs">
                                    Train your combat skills to become stronger
                                </p>
                            </div>
                        </div>
                    </div>

                    {/* Combat Stats Overview */}
                    <div className="mb-6 grid grid-cols-2 gap-2 sm:grid-cols-4 sm:gap-3">
                        <div className="rounded-lg border border-stone-600 bg-stone-800/50 p-2 text-center sm:p-3">
                            <div className="font-pixel text-[10px] text-stone-400 sm:text-xs">
                                Combat Level
                            </div>
                            <div className="font-pixel text-lg text-amber-400 sm:text-2xl">
                                {currentStats.combat_level}
                            </div>
                        </div>
                        <div className="rounded-lg border border-red-600/30 bg-stone-800/50 p-2 text-center sm:p-3">
                            <div className="font-pixel text-[10px] text-red-400 sm:text-xs">
                                Attack
                            </div>
                            <div className="font-pixel text-lg text-red-300 sm:text-xl">
                                {currentStats.attack.level}
                            </div>
                        </div>
                        <div className="rounded-lg border border-orange-600/30 bg-stone-800/50 p-2 text-center sm:p-3">
                            <div className="font-pixel text-[10px] text-orange-400 sm:text-xs">
                                Strength
                            </div>
                            <div className="font-pixel text-lg text-orange-300 sm:text-xl">
                                {currentStats.strength.level}
                            </div>
                        </div>
                        <div className="rounded-lg border border-blue-600/30 bg-stone-800/50 p-2 text-center sm:p-3">
                            <div className="font-pixel text-[10px] text-blue-400 sm:text-xs">
                                Defense
                            </div>
                            <div className="font-pixel text-lg text-blue-300 sm:text-xl">
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
                            const isSelected = selectedExercise === exercise.id;

                            return (
                                <div
                                    key={exercise.id}
                                    onClick={() =>
                                        !isQueueActive && setSelectedExercise(exercise.id)
                                    }
                                    className={`cursor-pointer rounded-xl border-2 bg-gradient-to-br ${colors.bg} ${
                                        isSelected
                                            ? "border-amber-400 ring-1 ring-amber-400/50"
                                            : colors.border
                                    } px-3 py-4 sm:px-4 sm:py-6`}
                                >
                                    <div className="flex items-start gap-3 sm:gap-4">
                                        <div className="hidden rounded-lg bg-stone-800/50 p-3 sm:block">
                                            <Icon className={`h-8 w-8 ${colors.text}`} />
                                        </div>
                                        <div className="min-w-0 flex-1">
                                            <div className="flex items-start justify-between gap-2 sm:gap-4">
                                                <div className="min-w-0 flex-1">
                                                    <div className="flex items-center gap-2">
                                                        <Icon
                                                            className={`h-5 w-5 shrink-0 sm:hidden ${colors.text}`}
                                                        />
                                                        <h3
                                                            className={`font-pixel text-sm sm:text-lg ${colors.text}`}
                                                        >
                                                            {exercise.name}
                                                        </h3>
                                                        <span className="whitespace-nowrap font-pixel text-xs text-stone-400 sm:hidden">
                                                            Lv.{stat.level}
                                                        </span>
                                                    </div>
                                                    <p className="hidden py-1 font-pixel text-[10px] text-stone-400 sm:block sm:max-w-md">
                                                        {exercise.description}
                                                    </p>
                                                </div>
                                                <div className="hidden whitespace-nowrap font-pixel text-lg text-stone-300 sm:block">
                                                    Level {stat.level}
                                                </div>
                                            </div>

                                            {/* Progress bar with XP info */}
                                            <div className="relative mt-2 h-3 w-full overflow-hidden rounded-full bg-stone-700 sm:h-4">
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

                                            <div className="mt-2 sm:mt-3">
                                                <div className="mb-2 flex items-center gap-2 sm:gap-3">
                                                    <span className="font-pixel text-[10px] text-stone-400">
                                                        <Zap className="mr-0.5 inline h-3 w-3 text-yellow-400 sm:mr-1" />
                                                        {exercise.energy_cost}
                                                    </span>
                                                    <span className="font-pixel text-[10px] text-amber-400">
                                                        +{exercise.base_xp} XP
                                                    </span>
                                                </div>
                                                {isSelected && (
                                                    <ActionQueueControls
                                                        isQueueActive={isQueueActive}
                                                        queueProgress={queueProgress}
                                                        isActionLoading={isActionLoading}
                                                        cooldown={cooldown}
                                                        cooldownMs={TRAIN_COOLDOWN_MS}
                                                        onStart={startQueue}
                                                        onCancel={cancelQueue}
                                                        onSingle={performSingleAction}
                                                        disabled={!canTrain}
                                                        actionLabel="Train"
                                                        activeLabel="Training"
                                                        totalXp={queueXp}
                                                        buttonClassName={`${colors.border} bg-stone-800/80 hover:bg-stone-700/80 ${colors.text}`}
                                                    />
                                                )}
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
