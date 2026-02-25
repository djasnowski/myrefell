import { Head, usePage } from "@inertiajs/react";
import {
    AlertTriangle,
    Axe,
    Backpack,
    Fish,
    Flame,
    Info,
    Leaf,
    Mountain,
    Package,
    Pickaxe,
    Snowflake,
    Sparkles,
    Sun,
    TreeDeciduous,
    Waves,
    Wheat,
    Zap,
} from "lucide-react";
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from "@/components/ui/dialog";
import { useCallback, useEffect, useState } from "react";
import AppLayout from "@/layouts/app-layout";
import { ActionQueueControls } from "@/components/action-queue-controls";
import { InventorySummary } from "@/components/inventory-summary";
import { gameToast } from "@/components/ui/game-toast";
import {
    useActionQueue,
    getActionVerb,
    type ActionResult,
    type QueueStats,
} from "@/hooks/use-action-queue";
import { locationPath } from "@/lib/utils";
import type { BreadcrumbItem } from "@/types";

const GATHER_COOLDOWN_MS = 3000;

const formatTimeRemaining = (hours: number): string => {
    if (hours < 1) {
        const minutes = Math.ceil(hours * 60);
        return `${minutes}m`;
    }
    const wholeHours = Math.floor(hours);
    const minutes = Math.round((hours - wholeHours) * 60);
    if (minutes === 0) {
        return `${wholeHours}h`;
    }
    return `${wholeHours}h ${minutes}m`;
};

interface Resource {
    name: string;
    weight: number;
    min_level: number;
    xp_bonus: number;
}

interface BiomeAttunement {
    kingdom_id: number | null;
    kingdom_name: string | null;
    biome: string | null;
    biome_description: string | null;
    biome_skills: string[];
    attunement_level: number;
    attunement_bonus: number;
    hours_in_kingdom: number;
    arrived_at: string | null;
    next_level: {
        level: number;
        bonus: number;
        hours_remaining: number;
    } | null;
    max_level: number;
}

interface Activity {
    id: string;
    name: string;
    skill: string;
    skill_level: number;
    skill_xp: number;
    skill_xp_progress: number;
    skill_xp_to_next: number;
    energy_cost: number;
    base_xp: number;
    player_energy: number;
    can_gather: boolean;
    resources: Resource[];
    next_unlock: Resource | null;
    inventory_full: boolean;
    free_slots: number;
    seasonal_modifier: number;
    current_season: "spring" | "summer" | "autumn" | "winter";
    biome_attunement: BiomeAttunement | null;
    biome_bonus: number;
    gathering_xp_bonus: number;
    xp_penalty: number;
    inventory_summary: { name: string; quantity: number }[];
}

interface GatherResult {
    success: boolean;
    message: string;
    resource?: {
        name: string;
        description: string;
    };
    quantity?: number;
    xp_awarded?: number;
    skill?: string;
    leveled_up?: boolean;
    new_level?: number;
    energy_remaining?: number;
    seasonal_bonus?: boolean;
}

interface Location {
    type: string;
    id: number;
    name: string;
}

interface PageProps {
    activity: Activity;
    player_energy: number;
    max_energy: number;
    location: Location;
    [key: string]: unknown;
}

const activityIcons: Record<string, typeof Pickaxe> = {
    mining: Pickaxe,
    fishing: Fish,
    woodcutting: Axe,
    herblore: Leaf,
};

const activityBgColors: Record<string, string> = {
    mining: "from-stone-800 to-stone-900 border-stone-600",
    fishing: "from-blue-900/50 to-stone-900 border-blue-600/50",
    woodcutting: "from-green-900/50 to-stone-900 border-green-600/50",
    herblore: "from-emerald-900/50 to-stone-900 border-emerald-600/50",
};

const seasonIcons: Record<string, typeof Sun> = {
    spring: Leaf,
    summer: Sun,
    autumn: TreeDeciduous,
    winter: Snowflake,
};

const seasonColors: Record<string, string> = {
    spring: "text-green-400 border-green-600/50 bg-green-900/20",
    summer: "text-yellow-400 border-yellow-600/50 bg-yellow-900/20",
    autumn: "text-orange-400 border-orange-600/50 bg-orange-900/20",
    winter: "text-blue-400 border-blue-600/50 bg-blue-900/20",
};

export default function GatheringActivity() {
    const { activity, player_energy, max_energy, location } = usePage<PageProps>().props;
    const [currentEnergy, setCurrentEnergy] = useState(player_energy);
    const [selectedResource, setSelectedResource] = useState<string | null>(null);
    const [sessionStats, setSessionStats] = useState<Record<string, { xp: number; items: number }>>(
        {},
    );

    const Icon = activityIcons[activity.id] || Pickaxe;
    const bgColor = activityBgColors[activity.id] || "from-stone-800 to-stone-900 border-stone-600";

    const SeasonIcon = seasonIcons[activity.current_season] || Sun;
    const seasonColor = seasonColors[activity.current_season] || "text-stone-400";
    const modifierPercent = Math.round((activity.seasonal_modifier - 1) * 100);
    const isBonus = modifierPercent > 0;
    const isPenalty = modifierPercent < 0;

    const baseLocationUrl = locationPath(location.type, location.id);
    const breadcrumbs: BreadcrumbItem[] = [
        { title: "Dashboard", href: "/dashboard" },
        { title: location.name, href: baseLocationUrl },
        { title: "Gathering", href: `${baseLocationUrl}/gathering` },
        {
            title: activity.name,
            href: `${baseLocationUrl}/gathering/${activity.id}`,
        },
    ];

    const gatherUrl = `${baseLocationUrl}/gathering/gather`;

    const buildBody = useCallback(
        () => ({
            activity: activity.id,
            resource: selectedResource,
        }),
        [activity.id, selectedResource],
    );

    const onActionComplete = useCallback((data: ActionResult) => {
        if (data.success && data.energy_remaining !== undefined) {
            setCurrentEnergy(data.energy_remaining);
        }
        if (data.success && data.resource) {
            const name = data.resource.name;
            const xp = data.xp_awarded ?? 0;
            const qty = data.quantity ?? 1;
            setSessionStats((prev) => ({
                ...prev,
                [name]: {
                    xp: (prev[name]?.xp ?? 0) + xp,
                    items: (prev[name]?.items ?? 0) + qty,
                },
            }));
        }
    }, []);

    const onQueueComplete = useCallback((stats: QueueStats) => {
        if (stats.completed === 0) return;

        const verb = getActionVerb(stats.actionType);
        if (stats.completed === 1 && stats.itemName) {
            const qty = stats.totalQuantity > 1 ? `${stats.totalQuantity}x ` : "";
            gameToast.success(`${qty}${stats.itemName}`, {
                xp: stats.totalXp,
                levelUp: stats.lastLevelUp,
            });
        } else if (stats.completed > 1) {
            const qty = stats.totalQuantity > 0 ? `${stats.totalQuantity}x ` : "";
            gameToast.success(
                `${verb} ${qty}${stats.itemName ?? "resources"} (${stats.completed} actions)`,
                {
                    xp: stats.totalXp,
                    levelUp: stats.lastLevelUp,
                },
            );
        }
    }, []);

    const buildActionParams = useCallback(
        () => ({
            activity: activity.id,
            resource: selectedResource,
            location_type: location.type,
            location_id: location.id,
        }),
        [activity.id, selectedResource, location],
    );

    const {
        startQueue,
        cancelQueue,
        isQueueActive,
        queueProgress,
        isActionLoading,
        cooldown,
        performSingleAction,
        isGloballyLocked,
        totalXp,
        queueStartedAt,
    } = useActionQueue({
        url: gatherUrl,
        buildBody,
        cooldownMs: GATHER_COOLDOWN_MS,
        onActionComplete,
        onQueueComplete,
        reloadProps: ["sidebar", "activity"],
        actionType: "gather",
        buildActionParams,
    });

    // Sync energy state when props change (from router.reload)
    useEffect(() => {
        setCurrentEnergy(player_energy);
    }, [player_energy]);

    const canGather = currentEnergy >= activity.energy_cost && !activity.inventory_full;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={activity.name} />
            <div className="flex h-full flex-1 flex-col p-4">
                <div className="w-full">
                    {/* Header Card */}
                    <div className={`mb-6 rounded-xl border-2 bg-gradient-to-br p-6 ${bgColor}`}>
                        <div className="flex items-center gap-4">
                            <div className="rounded-lg bg-stone-800/50 p-4">
                                <Icon className="h-12 w-12 text-amber-400" />
                            </div>
                            <div>
                                <h1 className="font-pixel text-2xl text-amber-400">
                                    {activity.name}
                                </h1>
                                <p className="font-pixel text-xs capitalize text-stone-400">
                                    {activity.skill} Level {activity.skill_level}
                                </p>
                            </div>
                        </div>
                    </div>

                    {/* Seasonal Modifier Banner */}
                    <div className={`mb-4 rounded-lg border p-3 ${seasonColor}`}>
                        <div className="flex items-center justify-between">
                            <div className="flex items-center gap-2">
                                <SeasonIcon className="h-4 w-4" />
                                <span className="font-pixel text-xs capitalize">
                                    {activity.current_season}
                                </span>
                            </div>
                            <div className="font-pixel text-xs">
                                {isBonus && (
                                    <span className="text-green-400">
                                        +{modifierPercent}% bonus yield chance
                                    </span>
                                )}
                                {isPenalty && (
                                    <span className="text-red-400">
                                        {modifierPercent}% reduced yields
                                    </span>
                                )}
                                {!isBonus && !isPenalty && (
                                    <span className="text-stone-400">Normal yields</span>
                                )}
                            </div>
                        </div>
                    </div>

                    {/* Biome Attunement Banner */}
                    {activity.biome_attunement && activity.biome_bonus > 0 && (
                        <div className="mb-4 rounded-lg border border-purple-600/50 bg-purple-900/20 p-3">
                            <div className="flex items-center justify-between">
                                <div className="flex items-center gap-2">
                                    <div className="rounded-md bg-purple-800/50 p-1.5">
                                        {activity.biome_attunement.biome === "plains" && (
                                            <Wheat className="h-4 w-4 text-green-400" />
                                        )}
                                        {activity.biome_attunement.biome === "tundra" && (
                                            <Mountain className="h-4 w-4 text-blue-400" />
                                        )}
                                        {activity.biome_attunement.biome === "coastal" && (
                                            <Waves className="h-4 w-4 text-cyan-400" />
                                        )}
                                        {activity.biome_attunement.biome === "volcano" && (
                                            <Flame className="h-4 w-4 text-orange-400" />
                                        )}
                                    </div>
                                    <div>
                                        <div className="flex items-center gap-1">
                                            <span className="font-pixel text-xs capitalize text-purple-300">
                                                {activity.biome_attunement.biome} Attunement
                                            </span>
                                            <Dialog>
                                                <DialogTrigger asChild>
                                                    <button className="text-purple-400 hover:text-purple-300">
                                                        <Info className="h-3 w-3" />
                                                    </button>
                                                </DialogTrigger>
                                                <DialogContent className="border-purple-600/50 bg-stone-900">
                                                    <DialogHeader>
                                                        <DialogTitle className="font-pixel capitalize text-purple-300">
                                                            {activity.biome_attunement.biome}{" "}
                                                            Attunement
                                                        </DialogTitle>
                                                        <DialogDescription className="text-stone-400">
                                                            {
                                                                activity.biome_attunement
                                                                    .biome_description
                                                            }
                                                        </DialogDescription>
                                                    </DialogHeader>
                                                    <div className="space-y-3 text-sm text-stone-300">
                                                        <p>
                                                            The longer you stay in a kingdom, the
                                                            more attuned you become to its land.
                                                            This grants bonus XP for certain skills.
                                                        </p>
                                                        <div className="rounded-lg bg-stone-800/50 p-3">
                                                            <div className="font-pixel text-xs text-purple-400 mb-2">
                                                                Attunement Levels
                                                            </div>
                                                            <div className="space-y-1 text-xs">
                                                                <div className="flex justify-between">
                                                                    <span>
                                                                        Level 1 (30 minutes)
                                                                    </span>
                                                                    <span className="text-purple-300">
                                                                        +10% XP
                                                                    </span>
                                                                </div>
                                                                <div className="flex justify-between">
                                                                    <span>Level 2 (2 hours)</span>
                                                                    <span className="text-purple-300">
                                                                        +20% XP
                                                                    </span>
                                                                </div>
                                                                <div className="flex justify-between">
                                                                    <span>Level 3 (4 hours)</span>
                                                                    <span className="text-purple-300">
                                                                        +30% XP
                                                                    </span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <p className="text-xs text-stone-500">
                                                            Attunement resets when you travel to a
                                                            different kingdom.
                                                        </p>
                                                    </div>
                                                </DialogContent>
                                            </Dialog>
                                        </div>
                                        <div className="flex items-center gap-1">
                                            {[1, 2, 3].map((lvl) => (
                                                <div
                                                    key={lvl}
                                                    className={`h-1.5 w-4 rounded-full ${
                                                        lvl <=
                                                        activity.biome_attunement!.attunement_level
                                                            ? "bg-purple-400"
                                                            : "bg-stone-700"
                                                    }`}
                                                />
                                            ))}
                                            <span className="ml-1 font-pixel text-[10px] text-stone-400">
                                                Lvl {activity.biome_attunement.attunement_level}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div className="text-right">
                                    <div className="font-pixel text-sm text-purple-300">
                                        +{activity.biome_bonus}% XP
                                    </div>
                                    {activity.biome_attunement.next_level && (
                                        <div className="font-pixel text-[10px] text-stone-500">
                                            +{activity.biome_attunement.next_level.bonus}% in{" "}
                                            {formatTimeRemaining(
                                                activity.biome_attunement.next_level
                                                    .hours_remaining,
                                            )}
                                        </div>
                                    )}
                                </div>
                            </div>
                        </div>
                    )}

                    {/* Inventory Full Warning */}
                    {activity.inventory_full && (
                        <div className="mb-4 rounded-lg border-2 border-red-600 bg-red-900/30 p-4">
                            <div className="flex items-center gap-3">
                                <AlertTriangle className="h-6 w-6 text-red-400" />
                                <div>
                                    <p className="font-pixel text-sm text-red-300">
                                        Inventory Full
                                    </p>
                                    <p className="font-pixel text-xs text-red-400/80">
                                        You need to sell or drop items before you can gather more
                                        resources.
                                    </p>
                                </div>
                            </div>
                        </div>
                    )}

                    {/* Energy, Skill, and Inventory Status */}
                    <div className="mb-6 grid grid-cols-3 gap-2 sm:gap-4">
                        <div className="rounded-lg border border-stone-700 bg-stone-800/50 p-2 sm:p-3">
                            <div className="mb-1 flex items-center gap-1 font-pixel text-[10px] text-yellow-400 sm:text-xs">
                                <Zap className="h-3 w-3 shrink-0" />
                                <span className="truncate">Energy</span>
                            </div>
                            <div className="h-2 w-full overflow-hidden rounded-full bg-stone-700">
                                <div
                                    className="h-full bg-gradient-to-r from-yellow-600 to-yellow-400 transition-all"
                                    style={{ width: `${(currentEnergy / max_energy) * 100}%` }}
                                />
                            </div>
                            <div className="mt-1 font-pixel text-[9px] text-stone-400 sm:text-[10px]">
                                <span className="sm:hidden">
                                    {currentEnergy} / {max_energy} ({activity.energy_cost} per
                                    action)
                                </span>
                                <span className="hidden sm:inline">
                                    {currentEnergy} / {max_energy} ({activity.energy_cost} per
                                    action)
                                </span>
                            </div>
                        </div>
                        <div className="rounded-lg border border-stone-700 bg-stone-800/50 p-2 sm:p-3">
                            <div className="mb-1 flex items-center justify-between gap-1">
                                <div className="flex min-w-0 items-center gap-1 font-pixel text-[10px] capitalize text-amber-400 sm:text-xs">
                                    <Icon className="h-3 w-3 shrink-0" />
                                    <span className="truncate">{activity.skill}</span>
                                </div>
                                <span className="shrink-0 font-pixel text-[10px] text-stone-300 sm:text-xs">
                                    {activity.skill_level}
                                </span>
                            </div>
                            <div className="h-2 w-full overflow-hidden rounded-full bg-stone-700">
                                <div
                                    className="h-full bg-gradient-to-r from-amber-600 to-amber-400 transition-all"
                                    style={{ width: `${activity.skill_xp_progress}%` }}
                                />
                            </div>
                            <div className="mt-1 font-pixel text-[9px] text-stone-400 sm:text-[10px]">
                                <span className="sm:hidden">
                                    {activity.skill_xp_to_next.toLocaleString()} XP to next level
                                </span>
                                <span className="hidden sm:inline">
                                    {activity.skill_xp_to_next.toLocaleString()} XP to next level
                                </span>
                            </div>
                        </div>
                        <div className="rounded-lg border border-stone-700 bg-stone-800/50 p-2 sm:p-3">
                            <div className="mb-1 flex items-center gap-1 font-pixel text-[10px] text-amber-300 sm:text-xs">
                                <Backpack className="h-3 w-3 shrink-0" />
                                <span className="truncate">Inventory</span>
                            </div>
                            <div className="font-pixel text-base text-stone-300 sm:text-lg">
                                {activity.free_slots}{" "}
                                <span className="text-[10px] text-stone-500 sm:text-xs">slots</span>
                            </div>
                            <div className="font-pixel text-[9px] text-stone-500 sm:text-[10px]">
                                {activity.inventory_full ? "Full!" : "Space available"}
                            </div>
                        </div>
                    </div>

                    <InventorySummary items={activity.inventory_summary} />

                    {/* Gather Controls */}
                    <div className="mb-6 rounded-xl border-2 border-amber-600/50 bg-stone-800/50 p-4">
                        <ActionQueueControls
                            isQueueActive={isQueueActive}
                            queueProgress={queueProgress}
                            isActionLoading={isActionLoading}
                            cooldown={cooldown}
                            cooldownMs={GATHER_COOLDOWN_MS}
                            onStart={startQueue}
                            onCancel={cancelQueue}
                            onSingle={performSingleAction}
                            disabled={!canGather || isGloballyLocked}
                            actionLabel={selectedResource ? `Gather ${selectedResource}` : "Gather"}
                            activeLabel="Gathering"
                            totalXp={totalXp}
                            startedAt={queueStartedAt}
                            buttonClassName="bg-amber-600 text-stone-900 hover:bg-amber-500"
                        />
                    </div>

                    {/* Available Resources */}
                    <div className="rounded-xl border-2 border-stone-700 bg-stone-800/50 p-4">
                        <div className="mb-4 flex items-center justify-between">
                            <h2 className="font-pixel text-sm text-stone-300">Select Resource</h2>
                            {selectedResource && (
                                <button
                                    onClick={() => setSelectedResource(null)}
                                    className="font-pixel text-[10px] text-stone-500 hover:text-stone-300"
                                >
                                    Clear (Random)
                                </button>
                            )}
                        </div>
                        <div className="grid gap-2">
                            {activity.resources.map((resource) => {
                                // Calculate XP with all bonuses included (biome, belief, penalty)
                                let xp = activity.base_xp + resource.xp_bonus;
                                // Apply belief gathering XP bonus
                                if (activity.gathering_xp_bonus !== 0) {
                                    xp = Math.ceil(xp * (1 + activity.gathering_xp_bonus / 100));
                                }
                                // Apply XP penalty (negative value)
                                if (activity.xp_penalty !== 0) {
                                    xp = Math.ceil(xp * (1 + activity.xp_penalty / 100));
                                }
                                // Apply biome bonus
                                if (activity.biome_bonus > 0) {
                                    xp = Math.ceil(xp * (1 + activity.biome_bonus / 100));
                                }
                                const totalXp = xp;

                                const baseXp = activity.base_xp + resource.xp_bonus;
                                const hasBonuses =
                                    activity.gathering_xp_bonus !== 0 ||
                                    activity.xp_penalty !== 0 ||
                                    activity.biome_bonus > 0;

                                return (
                                    <button
                                        key={resource.name}
                                        onClick={() => setSelectedResource(resource.name)}
                                        className={`flex items-center justify-between rounded-lg px-3 py-2 transition ${
                                            selectedResource === resource.name
                                                ? "border-2 border-amber-500 bg-amber-900/30"
                                                : "border border-transparent bg-stone-900/50 hover:bg-stone-800/50"
                                        }`}
                                    >
                                        <div className="flex items-center gap-2">
                                            <Package
                                                className={`h-4 w-4 ${selectedResource === resource.name ? "text-amber-400" : "text-stone-400"}`}
                                            />
                                            <span
                                                className={`font-pixel text-xs ${selectedResource === resource.name ? "text-amber-300" : "text-stone-300"}`}
                                            >
                                                {resource.name}
                                            </span>
                                        </div>
                                        <div className="text-right">
                                            <span className="font-pixel text-[10px] text-amber-400">
                                                +{totalXp} XP
                                            </span>
                                            {hasBonuses && (
                                                <div className="font-pixel text-[8px] text-stone-500">
                                                    {baseXp} base
                                                    {activity.gathering_xp_bonus > 0 && (
                                                        <span className="text-green-500">
                                                            {" "}
                                                            +{activity.gathering_xp_bonus}%
                                                        </span>
                                                    )}
                                                    {activity.xp_penalty !== 0 && (
                                                        <span className="text-red-500">
                                                            {" "}
                                                            {activity.xp_penalty}%
                                                        </span>
                                                    )}
                                                    {activity.biome_bonus > 0 && (
                                                        <span className="text-blue-400">
                                                            {" "}
                                                            +{activity.biome_bonus}% biome
                                                        </span>
                                                    )}
                                                </div>
                                            )}
                                            {sessionStats[resource.name] && (
                                                <div className="font-pixel text-[8px] text-green-400">
                                                    ×{sessionStats[resource.name].items} ·{" "}
                                                    {sessionStats[resource.name].xp} XP
                                                </div>
                                            )}
                                        </div>
                                    </button>
                                );
                            })}
                        </div>

                        {/* Next Unlock */}
                        {activity.next_unlock && (
                            <div className="mt-4 rounded-lg border border-stone-600 bg-stone-900/30 p-3">
                                <div className="flex items-center gap-2">
                                    <Sparkles className="h-4 w-4 text-purple-400" />
                                    <span className="font-pixel text-xs text-purple-300">
                                        Next unlock
                                    </span>
                                </div>
                                <div className="mt-1 font-pixel text-sm text-stone-300">
                                    {activity.next_unlock.name}
                                </div>
                                <div className="font-pixel text-[10px] text-stone-500">
                                    Requires Level {activity.next_unlock.min_level}
                                </div>
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
