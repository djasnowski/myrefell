import { Head, usePage } from "@inertiajs/react";
import { Footprints, Lock, Sparkles, Zap } from "lucide-react";
import { useCallback, useEffect, useState } from "react";
import AppLayout from "@/layouts/app-layout";
import { ActionQueueControls } from "@/components/action-queue-controls";
import { gameToast } from "@/components/ui/game-toast";
import { useActionQueue, type ActionResult, type QueueStats } from "@/hooks/use-action-queue";
import { locationPath } from "@/lib/utils";
import type { BreadcrumbItem } from "@/types";

const AGILITY_COOLDOWN_MS = 3000;

interface Obstacle {
    id: string;
    name: string;
    description: string;
    min_level: number;
    energy_cost: number;
    base_xp: number;
    success_rate: number;
    is_unlocked: boolean;
    is_legendary: boolean;
    can_attempt: boolean;
}

interface AgilityInfo {
    can_train: boolean;
    obstacles: Obstacle[];
    player_energy: number;
    max_energy: number;
    agility_level: number;
    agility_xp: number;
    agility_xp_progress: number;
    agility_xp_to_next: number;
}

interface Location {
    type: string;
    id: number;
    name: string;
}

interface TrainResult {
    success: boolean;
    failed?: boolean;
    message: string;
    xp_awarded?: number;
    new_level?: number;
    leveled_up?: boolean;
    energy_remaining?: number;
}

interface PageProps {
    agility_info: AgilityInfo;
    location?: Location;
    [key: string]: unknown;
}

// Gradient colors for obstacles based on level tier
const getObstacleColors = (minLevel: number, isLegendary: boolean) => {
    if (isLegendary) {
        return {
            bg: "from-purple-900/50 to-stone-900",
            border: "border-purple-500/50",
            text: "text-purple-400",
            button: "border-purple-600/50 text-purple-400",
        };
    }
    if (minLevel >= 70) {
        return {
            bg: "from-amber-900/50 to-stone-900",
            border: "border-amber-600/50",
            text: "text-amber-400",
            button: "border-amber-600/50 text-amber-400",
        };
    }
    if (minLevel >= 50) {
        return {
            bg: "from-cyan-900/50 to-stone-900",
            border: "border-cyan-600/50",
            text: "text-cyan-400",
            button: "border-cyan-600/50 text-cyan-400",
        };
    }
    if (minLevel >= 30) {
        return {
            bg: "from-blue-900/50 to-stone-900",
            border: "border-blue-600/50",
            text: "text-blue-400",
            button: "border-blue-600/50 text-blue-400",
        };
    }
    if (minLevel >= 15) {
        return {
            bg: "from-green-900/50 to-stone-900",
            border: "border-green-600/50",
            text: "text-green-400",
            button: "border-green-600/50 text-green-400",
        };
    }
    return {
        bg: "from-stone-800/50 to-stone-900",
        border: "border-stone-600/50",
        text: "text-stone-300",
        button: "border-stone-600/50 text-stone-300",
    };
};

export default function AgilityIndex() {
    const { agility_info, location } = usePage<PageProps>().props;
    const [currentEnergy, setCurrentEnergy] = useState(agility_info.player_energy);
    const [currentLevel, setCurrentLevel] = useState(agility_info.agility_level);
    const [currentXpProgress, setCurrentXpProgress] = useState(agility_info.agility_xp_progress);
    const [currentXpToNext, setCurrentXpToNext] = useState(agility_info.agility_xp_to_next);
    const [selectedObstacle, setSelectedObstacle] = useState<string | null>(null);
    const [queueXp, setQueueXp] = useState(0);

    const baseLocationUrl = location ? locationPath(location.type, location.id) : null;
    const trainUrl = baseLocationUrl ? `${baseLocationUrl}/agility/train` : "/agility/train";

    const breadcrumbs: BreadcrumbItem[] = [
        { title: "Dashboard", href: "/dashboard" },
        ...(location && baseLocationUrl ? [{ title: location.name, href: baseLocationUrl }] : []),
        { title: "Agility Course", href: "#" },
    ];

    const buildBody = useCallback(() => ({ obstacle: selectedObstacle }), [selectedObstacle]);

    const onActionComplete = useCallback((data: ActionResult) => {
        if (data.energy_remaining !== undefined) {
            setCurrentEnergy(data.energy_remaining);
        }
        if (data.leveled_up && data.new_level) {
            setCurrentLevel(data.new_level);
        }
    }, []);

    const onQueueComplete = useCallback((stats: QueueStats) => {
        setQueueXp(0);
        if (stats.completed === 0) return;

        if (stats.completed === 1) {
            gameToast.training({
                success: true,
                message: `Trained Agility!`,
                xp_awarded: stats.totalXp,
                leveled_up: !!stats.lastLevelUp,
                new_level: stats.lastLevelUp?.level,
                skill: "Agility",
            });
        } else {
            gameToast.success(`Completed ${stats.completed} agility attempts`, {
                xp: stats.totalXp,
                levelUp: stats.lastLevelUp,
            });
        }
    }, []);

    // Agility: partial fail (failed=true but still got XP) should continue queue
    const shouldContinue = useCallback((data: ActionResult) => {
        return data.success || data.failed === true;
    }, []);

    const {
        startQueue,
        cancelQueue,
        isQueueActive,
        queueProgress,
        isActionLoading,
        cooldown,
        performSingleAction,
    } = useActionQueue({
        url: trainUrl,
        buildBody,
        cooldownMs: AGILITY_COOLDOWN_MS,
        onActionComplete: useCallback(
            (data: ActionResult) => {
                onActionComplete(data);
                setQueueXp((prev) => prev + (data.xp_awarded ?? 0));
            },
            [onActionComplete],
        ),
        onQueueComplete,
        shouldContinue,
        reloadProps: ["sidebar", "agility_info"],
    });

    // Sync state from props
    useEffect(() => {
        setCurrentEnergy(agility_info.player_energy);
        setCurrentLevel(agility_info.agility_level);
        setCurrentXpProgress(agility_info.agility_xp_progress);
        setCurrentXpToNext(agility_info.agility_xp_to_next);
    }, [agility_info]);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Agility Course" />
            <div className="flex h-full flex-1 flex-col p-4">
                <div className="mx-auto w-full max-w-3xl">
                    {/* Header */}
                    <div className="mb-6 rounded-xl border-2 border-emerald-600/50 bg-gradient-to-br from-emerald-900/30 to-stone-900 p-4 sm:p-6">
                        <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:gap-4">
                            <div className="flex items-center gap-3 sm:gap-4">
                                <div className="rounded-lg bg-stone-800/50 p-3 sm:p-4">
                                    <Footprints className="h-8 w-8 text-emerald-400 sm:h-12 sm:w-12" />
                                </div>
                                <div className="flex-1">
                                    <h1 className="font-pixel text-xl text-emerald-400 sm:text-2xl">
                                        Agility Course
                                    </h1>
                                    <p className="font-pixel text-[10px] text-stone-400 sm:text-xs">
                                        Navigate obstacles to train your agility
                                    </p>
                                </div>
                            </div>
                            <div className="flex items-center justify-between rounded-lg border border-stone-700 bg-stone-800/30 px-3 py-2 sm:flex-col sm:border-0 sm:bg-transparent sm:px-0 sm:py-0 sm:text-right">
                                <div className="font-pixel text-sm text-emerald-400 sm:text-lg">
                                    Level {currentLevel}
                                </div>
                                <div className="font-pixel text-[10px] text-stone-400">
                                    {currentXpToNext.toLocaleString()} XP to next
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* XP Progress Bar - hidden on mobile since header shows level/XP */}
                    <div className="mb-6 hidden rounded-lg border border-stone-700 bg-stone-800/50 p-3 sm:block">
                        <div className="mb-1 flex items-center justify-between">
                            <div className="flex items-center gap-1 font-pixel text-xs text-emerald-400">
                                <Footprints className="h-3 w-3" />
                                Agility Level {currentLevel}
                            </div>
                            <div className="font-pixel text-xs text-stone-400">
                                {Math.round(currentXpProgress)}%
                            </div>
                        </div>
                        <div className="h-3 w-full overflow-hidden rounded-full bg-stone-700">
                            <div
                                className="h-full bg-gradient-to-r from-emerald-600 to-emerald-400 transition-all"
                                style={{ width: `${currentXpProgress}%` }}
                            />
                        </div>
                    </div>

                    {/* Energy Bar */}
                    <div className="mb-6 rounded-lg border border-stone-700 bg-stone-800/50 p-3">
                        <div className="mb-1 flex items-center justify-between">
                            <div className="flex items-center gap-1 font-pixel text-xs text-yellow-400">
                                <Zap className="h-3 w-3" />
                                Energy
                            </div>
                            <div className="font-pixel text-xs text-stone-400">
                                {currentEnergy} / {agility_info.max_energy}
                            </div>
                        </div>
                        <div className="h-2 w-full overflow-hidden rounded-full bg-stone-700">
                            <div
                                className="h-full bg-gradient-to-r from-yellow-600 to-yellow-400 transition-all"
                                style={{
                                    width: `${(currentEnergy / agility_info.max_energy) * 100}%`,
                                }}
                            />
                        </div>
                    </div>

                    {/* Obstacles */}
                    <div className="space-y-4">
                        {agility_info.obstacles.map((obstacle) => {
                            const colors = getObstacleColors(
                                obstacle.min_level,
                                obstacle.is_legendary,
                            );
                            const canTrain =
                                obstacle.is_unlocked && currentEnergy >= obstacle.energy_cost;
                            const isSelected = selectedObstacle === obstacle.id;

                            return (
                                <div
                                    key={obstacle.id}
                                    onClick={() =>
                                        obstacle.is_unlocked &&
                                        !isQueueActive &&
                                        setSelectedObstacle(obstacle.id)
                                    }
                                    className={`rounded-xl border-2 bg-gradient-to-br ${colors.bg} ${
                                        isSelected
                                            ? "border-amber-400 ring-1 ring-amber-400/50"
                                            : colors.border
                                    } px-4 py-4 ${
                                        !obstacle.is_unlocked ? "opacity-60" : "cursor-pointer"
                                    }`}
                                >
                                    <div className="flex items-start gap-3 sm:gap-4">
                                        <div className="hidden rounded-lg bg-stone-800/50 p-3 sm:block">
                                            {obstacle.is_legendary ? (
                                                <Sparkles className={`h-8 w-8 ${colors.text}`} />
                                            ) : obstacle.is_unlocked ? (
                                                <Footprints className={`h-8 w-8 ${colors.text}`} />
                                            ) : (
                                                <Lock className="h-8 w-8 text-stone-500" />
                                            )}
                                        </div>
                                        <div className="min-w-0 flex-1">
                                            <div className="flex items-start justify-between gap-4">
                                                <div className="flex-1">
                                                    <div className="flex items-center gap-2">
                                                        <h3
                                                            className={`font-pixel text-lg ${colors.text}`}
                                                        >
                                                            {obstacle.name}
                                                        </h3>
                                                        {obstacle.is_legendary && (
                                                            <span className="rounded bg-purple-600/30 px-1.5 py-0.5 font-pixel text-[8px] text-purple-300">
                                                                LEGENDARY
                                                            </span>
                                                        )}
                                                    </div>
                                                    <p className="max-w-md py-1 font-pixel text-[10px] text-stone-400">
                                                        {obstacle.description}
                                                    </p>
                                                </div>
                                                <div className="whitespace-nowrap font-pixel text-sm text-stone-400">
                                                    Lvl {obstacle.min_level}
                                                </div>
                                            </div>

                                            <div className="mt-3">
                                                <div className="mb-2 flex flex-wrap items-center gap-2 sm:gap-3">
                                                    <span className="font-pixel text-[10px] text-stone-400">
                                                        <Zap className="mr-1 inline h-3 w-3 text-yellow-400" />
                                                        {obstacle.energy_cost}
                                                    </span>
                                                    <span className="font-pixel text-[10px] text-amber-400">
                                                        +{obstacle.base_xp} XP
                                                    </span>
                                                    {obstacle.is_unlocked && (
                                                        <span className="font-pixel text-[10px] text-stone-500">
                                                            {obstacle.success_rate}%
                                                        </span>
                                                    )}
                                                </div>
                                                {obstacle.is_unlocked && isSelected ? (
                                                    <ActionQueueControls
                                                        isQueueActive={isQueueActive}
                                                        queueProgress={queueProgress}
                                                        isActionLoading={isActionLoading}
                                                        cooldown={cooldown}
                                                        cooldownMs={AGILITY_COOLDOWN_MS}
                                                        onStart={startQueue}
                                                        onCancel={cancelQueue}
                                                        onSingle={performSingleAction}
                                                        disabled={!canTrain}
                                                        actionLabel="Attempt"
                                                        activeLabel="Attempting"
                                                        totalXp={queueXp}
                                                        buttonClassName={`${colors.button} bg-stone-800/80 hover:bg-stone-700/80`}
                                                    />
                                                ) : !obstacle.is_unlocked ? (
                                                    <span className="font-pixel text-[10px] text-stone-500">
                                                        Requires Level {obstacle.min_level}
                                                    </span>
                                                ) : null}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            );
                        })}
                    </div>

                    {/* Info Box */}
                    <div className="mt-6 rounded-lg border border-stone-600 bg-stone-800/30 p-4">
                        <h3 className="mb-2 font-pixel text-sm text-stone-300">About Agility</h3>
                        <ul className="space-y-1 font-pixel text-[10px] text-stone-400">
                            <li>- Each obstacle has a chance of success based on your level</li>
                            <li>- Successful attempts award full XP, failures award 25% XP</li>
                            <li>- Higher level obstacles provide more XP but are harder</li>
                            <li>
                                - Some advanced obstacles are only available at higher-tier
                                locations
                            </li>
                            <li>- Legendary obstacles can only be completed at Duchies</li>
                        </ul>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
