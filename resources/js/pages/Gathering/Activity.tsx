import { Head, router, usePage } from "@inertiajs/react";
import {
    AlertTriangle,
    Axe,
    Backpack,
    Fish,
    Leaf,
    Loader2,
    Package,
    Pickaxe,
    Snowflake,
    Sparkles,
    Sun,
    TreeDeciduous,
    Zap,
} from "lucide-react";
import { useEffect, useRef, useState } from "react";
import AppLayout from "@/layouts/app-layout";
import { gameToast } from "@/components/ui/game-toast";
import { locationPath } from "@/lib/utils";
import type { BreadcrumbItem } from "@/types";

const GATHER_COOLDOWN_MS = 3000;

interface Resource {
    name: string;
    weight: number;
    min_level: number;
    xp_bonus: number;
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
    const [loading, setLoading] = useState(false);
    const [currentEnergy, setCurrentEnergy] = useState(player_energy);
    const [cooldown, setCooldown] = useState(0);
    const [selectedResource, setSelectedResource] = useState<string | null>(null);
    const cooldownInterval = useRef<NodeJS.Timeout | null>(null);

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

    const canGather =
        currentEnergy >= activity.energy_cost && !activity.inventory_full && cooldown <= 0;

    const startCooldown = () => {
        setCooldown(GATHER_COOLDOWN_MS);
        if (cooldownInterval.current) {
            clearInterval(cooldownInterval.current);
        }
        const startTime = Date.now();
        cooldownInterval.current = setInterval(() => {
            const elapsed = Date.now() - startTime;
            const remaining = Math.max(0, GATHER_COOLDOWN_MS - elapsed);
            setCooldown(remaining);
            if (remaining <= 0 && cooldownInterval.current) {
                clearInterval(cooldownInterval.current);
                cooldownInterval.current = null;
            }
        }, 50);
    };

    // Sync energy state when props change (from router.reload)
    useEffect(() => {
        setCurrentEnergy(player_energy);
    }, [player_energy]);

    useEffect(() => {
        return () => {
            if (cooldownInterval.current) {
                clearInterval(cooldownInterval.current);
            }
        };
    }, []);

    const handleGather = async () => {
        if (!canGather || loading || cooldown > 0) return;

        setLoading(true);

        try {
            const response = await fetch(`${baseLocationUrl}/gathering/gather`, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN":
                        document
                            .querySelector('meta[name="csrf-token"]')
                            ?.getAttribute("content") || "",
                },
                body: JSON.stringify({
                    activity: activity.id,
                    resource: selectedResource,
                }),
            });

            const data: GatherResult = await response.json();

            // Show toast notification
            if (data.success && data.resource) {
                const quantity = data.quantity && data.quantity > 1 ? `${data.quantity}x ` : "";
                const bonus = data.seasonal_bonus ? " (Seasonal Bonus!)" : "";
                gameToast.success(`${quantity}${data.resource.name}${bonus}`, {
                    xp: data.xp_awarded,
                    levelUp:
                        data.leveled_up && data.new_level
                            ? { skill: activity.skill, level: data.new_level }
                            : undefined,
                });

                // Start cooldown timer
                startCooldown();
            } else if (!data.success) {
                gameToast.error(data.message);
            }

            if (data.success && data.energy_remaining !== undefined) {
                setCurrentEnergy(data.energy_remaining);
            }

            // Reload sidebar and activity data
            router.reload({ only: ["sidebar", "activity"] });
        } catch {
            gameToast.error("An error occurred");
        } finally {
            setLoading(false);
        }
    };

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

                    {/* Gather Button */}
                    <div className="relative mb-6">
                        <button
                            onClick={handleGather}
                            disabled={!canGather || loading || cooldown > 0}
                            className={`relative flex w-full items-center justify-center gap-3 overflow-hidden rounded-xl border-2 px-6 py-4 font-pixel text-lg transition ${
                                canGather && !loading && cooldown <= 0
                                    ? "border-amber-600 bg-amber-900/30 text-amber-300 hover:bg-amber-800/50"
                                    : "cursor-not-allowed border-stone-700 bg-stone-800/50 text-stone-500"
                            }`}
                        >
                            {/* Cooldown progress bar */}
                            {cooldown > 0 && (
                                <div
                                    className="absolute inset-0 bg-amber-600/20 transition-all"
                                    style={{ width: `${(cooldown / GATHER_COOLDOWN_MS) * 100}%` }}
                                />
                            )}
                            <span className="relative z-10 flex items-center gap-3">
                                {loading ? (
                                    <>
                                        <Loader2 className="h-6 w-6 animate-spin" />
                                        Gathering...
                                    </>
                                ) : cooldown > 0 ? (
                                    <>
                                        <Icon className="h-6 w-6" />
                                        {(cooldown / 1000).toFixed(1)}s
                                    </>
                                ) : (
                                    <>
                                        <Icon className="h-6 w-6" />
                                        {selectedResource
                                            ? `Gather ${selectedResource}`
                                            : "Gather (Random)"}
                                    </>
                                )}
                            </span>
                        </button>
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
                            {activity.resources.map((resource) => (
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
                                    <span className="font-pixel text-[10px] text-amber-400">
                                        +{activity.base_xp + resource.xp_bonus} XP
                                    </span>
                                </button>
                            ))}
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
