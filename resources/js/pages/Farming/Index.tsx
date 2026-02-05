import { Head, router, usePage } from "@inertiajs/react";
import {
    AlertTriangle,
    Check,
    Clock,
    Coins,
    Droplets,
    Hand,
    HelpCircle,
    Leaf,
    Plus,
    Scissors,
    Search,
    Sparkles,
    Sprout,
    Users,
    Warehouse,
    Wheat,
    X,
    Zap,
} from "lucide-react";
import { useState, useEffect, useRef } from "react";
import AppLayout from "@/layouts/app-layout";
import { useNotifications } from "@/hooks/use-notifications";
import type { BreadcrumbItem } from "@/types";

interface CropType {
    id: number;
    name: string;
    slug: string;
    icon: string;
    description: string;
    grow_time_minutes: number;
    farming_level_required: number;
    farming_xp: number;
    yield_min: number;
    yield_max: number;
    seed_item_id: number | null;
    seed_name: string | null;
    seeds_owned: number;
    seasons: string[] | null;
    is_in_season: boolean;
    can_plant: boolean;
}

interface Plot {
    id: number;
    status: "empty" | "planted" | "growing" | "ready" | "withered";
    crop: { id: number; name: string; icon: string } | null;
    quality: number;
    times_tended: number;
    is_watered: boolean;
    growth_progress: number;
    time_remaining: string | null;
    ready_at: string | null;
    is_ready: boolean;
    has_withered: boolean;
    withers_at: string | null;
    planted_at: string | null;
}

interface FarmingSkill {
    level: number;
    xp: number;
    xp_to_next: number;
    xp_progress: number;
}

interface MasterFarmerBonuses {
    yield_bonus: number;
    xp_bonus: number;
}

interface ActiveBlessing {
    name: string;
    xp_bonus: number;
    expires_at: string;
}

interface FoodBreakdownItem {
    name: string;
    quantity: number;
    food_value: number;
    total_points: number;
}

interface VillageFoodStats {
    food_available: number;
    food_points: number;
    food_needed_per_week: number;
    weeks_of_food: number;
    granary_capacity: number;
    population: number;
    npc_count: number;
    player_count: number;
    starving_npcs: number;
    starving_players: number;
    food_breakdown: FoodBreakdownItem[];
}

interface PageProps {
    plots: Plot[];
    crop_types: CropType[];
    current_season: string;
    farming_skill: FarmingSkill;
    location: { type: string; id: number };
    max_plots: number;
    gold: number;
    master_farmer_bonuses: MasterFarmerBonuses | null;
    active_blessings: ActiveBlessing[] | null;
    village_food: VillageFoodStats | null;
    location_name: string | null;
    error?: string;
    [key: string]: unknown;
}

const locationPaths: Record<string, string> = {
    village: "villages",
    town: "towns",
    barony: "baronies",
    duchy: "duchies",
    kingdom: "kingdoms",
};

const statusColors: Record<string, string> = {
    empty: "border-stone-600/50 bg-stone-800/30",
    planted: "border-amber-600/50 bg-amber-900/20",
    growing: "border-green-600/50 bg-green-900/20",
    ready: "border-yellow-500/50 bg-yellow-900/30 animate-pulse",
    withered: "border-red-600/50 bg-red-900/20",
};

const statusIcons: Record<string, typeof Wheat> = {
    empty: Sprout,
    planted: Leaf,
    growing: Sprout,
    ready: Wheat,
    withered: AlertTriangle,
};

function formatTime(minutes: number): string {
    if (minutes < 60) return `${minutes}m`;
    const hours = Math.floor(minutes / 60);
    const mins = minutes % 60;
    return mins > 0 ? `${hours}h ${mins}m` : `${hours}h`;
}

export default function FarmingIndex() {
    const {
        plots,
        crop_types,
        current_season,
        farming_skill,
        location,
        max_plots,
        gold,
        master_farmer_bonuses,
        active_blessings,
        village_food,
        location_name,
        error,
    } = usePage<PageProps>().props;

    // Dynamic breadcrumbs based on location
    const breadcrumbs: BreadcrumbItem[] = [
        { title: "Dashboard", href: "/dashboard" },
        ...(location_name
            ? [
                  {
                      title: location_name,
                      href: `/${locationPaths[location.type] || location.type + "s"}/${location.id}`,
                  },
              ]
            : []),
        { title: "Farming", href: "#" },
    ];
    const [selectedPlot, setSelectedPlot] = useState<Plot | null>(null);
    const [loading, setLoading] = useState<string | null>(null);
    const [message, setMessage] = useState<string | null>(null);
    const [showCropModal, setShowCropModal] = useState<number | null>(null); // plot id
    const [cropSearch, setCropSearch] = useState("");
    const [now, setNow] = useState(Date.now());
    const [showFoodHelp, setShowFoodHelp] = useState(false);
    const [showFoodBreakdown, setShowFoodBreakdown] = useState(false);
    const [cropTab, setCropTab] = useState<"available" | "out_of_season">("available");
    const { sendNotification } = useNotifications();
    const notifiedPlotsRef = useRef<Set<number>>(new Set());

    // Live countdown timer - tick every second and check for ready crops
    useEffect(() => {
        const interval = setInterval(() => {
            const currentTime = Date.now();
            setNow(currentTime);

            // Check for newly ready crops
            plots.forEach((plot) => {
                if (plot.ready_at && !plot.is_ready && !notifiedPlotsRef.current.has(plot.id)) {
                    const readyTime = new Date(plot.ready_at).getTime();
                    if (currentTime >= readyTime) {
                        notifiedPlotsRef.current.add(plot.id);
                        sendNotification("Crop Ready!", {
                            body: `Your ${plot.crop?.name || "crop"} is ready to harvest`,
                            tag: `crop-ready-${plot.id}`,
                        });
                    }
                }
            });
        }, 1000);
        return () => clearInterval(interval);
    }, [plots, sendNotification]);

    // Calculate live countdown from ready_at timestamp
    const getCountdown = (readyAt: string | null): string | null => {
        if (!readyAt) return null;
        const readyTime = new Date(readyAt).getTime();
        const diff = readyTime - now;
        if (diff <= 0) return null;

        const seconds = Math.floor(diff / 1000);
        const minutes = Math.floor(seconds / 60);
        const hours = Math.floor(minutes / 60);

        if (hours > 0) {
            return `${hours}h ${minutes % 60}m ${seconds % 60}s`;
        } else if (minutes > 0) {
            return `${minutes}m ${seconds % 60}s`;
        } else {
            return `${seconds}s`;
        }
    };

    // Calculate countdown until crop withers
    const getWitherCountdown = (withersAt: string | null): string | null => {
        if (!withersAt) return null;
        const witherTime = new Date(withersAt).getTime();
        const diff = witherTime - now;
        if (diff <= 0) return null;

        const seconds = Math.floor(diff / 1000);
        const minutes = Math.floor(seconds / 60);
        const hours = Math.floor(minutes / 60);

        if (hours > 0) {
            return `${hours}h ${minutes % 60}m`;
        } else if (minutes > 0) {
            return `${minutes}m ${seconds % 60}s`;
        } else {
            return `${seconds}s`;
        }
    };

    // Calculate live growth progress
    const getLiveProgress = (plot: Plot): number => {
        if (!plot.ready_at || !plot.planted_at) return plot.growth_progress;
        if (plot.is_ready) return 100;

        const readyTime = new Date(plot.ready_at).getTime();
        const diff = readyTime - now;
        if (diff <= 0) return 100;

        // We don't have planted_at as timestamp, so use the server progress as base
        return plot.growth_progress;
    };

    const currentPlotCount = plots.length;
    const nextPlotCost = (currentPlotCount + 1) * 100;
    const canBuyPlot = currentPlotCount < max_plots && gold >= nextPlotCost;

    const showMessage = (msg: string) => {
        setMessage(msg);
        setTimeout(() => setMessage(null), 3000);
    };

    const handleAction = (action: string, plotId?: number, data?: Record<string, unknown>) => {
        setLoading(action + (plotId || ""));
        const url = plotId ? `/farming/${plotId}/${action}` : `/farming/${action}`;

        router.post(url, data || {}, {
            preserveScroll: true,
            onSuccess: (page) => {
                const flash = page.props.flash as { success?: string } | undefined;
                if (flash?.success) showMessage(flash.success);
                router.reload({ only: ["plots", "gold", "farming_skill", "village_food"] });
            },
            onError: (errors) => {
                const msg = (Object.values(errors)[0] as string) || "Action failed";
                showMessage(msg);
            },
            onFinish: () => setLoading(null),
        });
    };

    const handleHarvest = (plotId: number, donateToGranary: boolean) => {
        handleAction("harvest", plotId, { donate_to_granary: donateToGranary });
    };

    const canDonateToGranary = location.type === "village" || location.type === "town";

    if (error) {
        return (
            <AppLayout breadcrumbs={breadcrumbs}>
                <Head title="Farming" />
                <div className="flex h-full flex-1 items-center justify-center p-4">
                    <div className="text-center">
                        <Wheat className="mx-auto mb-4 h-16 w-16 text-stone-600" />
                        <p className="font-pixel text-lg text-stone-400">{error}</p>
                    </div>
                </div>
            </AppLayout>
        );
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Farming" />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                {/* Header */}
                <div className="flex items-start justify-between">
                    <div>
                        <h1 className="font-pixel text-2xl text-amber-400">Farming</h1>
                        <p className="font-pixel text-sm text-stone-400">
                            Grow crops and harvest your yield
                        </p>
                    </div>
                    <div className="text-right">
                        <div className="flex items-center gap-1 font-pixel text-sm text-yellow-400">
                            <Coins className="h-4 w-4" />
                            {gold.toLocaleString()} gold
                        </div>
                    </div>
                </div>

                {/* Message Toast */}
                {message && (
                    <div className="fixed top-4 right-4 z-50 rounded-lg border border-amber-500/50 bg-stone-900 px-4 py-2 font-pixel text-sm text-amber-300 shadow-lg">
                        {message}
                    </div>
                )}

                {/* Master Farmer Bonus Banner */}
                {master_farmer_bonuses && (
                    <div className="rounded-lg border border-amber-500/50 bg-amber-900/20 p-3">
                        <div className="flex items-center gap-2">
                            <Wheat className="h-4 w-4 text-amber-400" />
                            <span className="font-pixel text-xs text-amber-300">
                                Master Farmer Bonus Active!
                            </span>
                        </div>
                        <div className="mt-1 flex gap-4 font-pixel text-[10px] text-amber-400/80">
                            <span>+{master_farmer_bonuses.yield_bonus}% crop yield</span>
                            <span>+{master_farmer_bonuses.xp_bonus}% farming XP</span>
                        </div>
                    </div>
                )}

                {/* Active Blessings */}
                {active_blessings && active_blessings.length > 0 && (
                    <div className="rounded-lg border border-purple-500/50 bg-purple-900/20 p-3">
                        <div className="flex items-center gap-2">
                            <Sparkles className="h-4 w-4 text-purple-400" />
                            <span className="font-pixel text-xs text-purple-300">
                                Blessing Active!
                            </span>
                        </div>
                        <div className="mt-1 flex flex-wrap gap-4 font-pixel text-[10px] text-purple-400/80">
                            {active_blessings.map((blessing, idx) => (
                                <span key={idx}>
                                    {blessing.name}: +{blessing.xp_bonus}% farming XP
                                </span>
                            ))}
                        </div>
                    </div>
                )}

                {/* Skill Progress */}
                <div className="rounded-lg border border-green-700/50 bg-green-900/20 p-3">
                    <div className="mb-1 flex items-center justify-between font-pixel text-xs">
                        <span className="flex items-center gap-1 text-green-400">
                            <Wheat className="h-3 w-3" />
                            Farming Level {farming_skill.level}
                        </span>
                        <span className="text-stone-300">
                            {farming_skill.xp.toLocaleString()} /{" "}
                            {farming_skill.xp_to_next.toLocaleString()} XP
                        </span>
                    </div>
                    <div className="h-2 w-full overflow-hidden rounded-full bg-stone-700">
                        <div
                            className="h-full bg-gradient-to-r from-green-600 to-green-400 transition-all"
                            style={{ width: `${farming_skill.xp_progress}%` }}
                        />
                    </div>
                </div>

                {/* Village Granary Status */}
                {village_food && (
                    <div
                        onClick={() => setShowFoodBreakdown(true)}
                        className={`cursor-pointer rounded-lg border p-3 transition hover:brightness-110 ${
                            village_food.weeks_of_food < 4
                                ? "border-red-600/50 bg-red-900/20"
                                : village_food.weeks_of_food < 8
                                  ? "border-yellow-600/50 bg-yellow-900/20"
                                  : "border-stone-600/50 bg-stone-800/30"
                        }`}
                    >
                        <div className="mb-2 flex items-center justify-between">
                            <div className="flex items-center gap-2">
                                <Warehouse className="h-4 w-4 text-amber-400" />
                                <span className="font-pixel text-xs text-amber-300">
                                    {location_name} Granary
                                </span>
                                <span className="font-pixel text-[10px] text-stone-500">
                                    (click for details)
                                </span>
                            </div>
                            <button
                                onClick={(e) => {
                                    e.stopPropagation();
                                    setShowFoodHelp(true);
                                }}
                                className="flex items-center gap-1 rounded px-2 py-1 font-pixel text-[10px] text-stone-400 hover:bg-stone-700 hover:text-stone-200"
                            >
                                <HelpCircle className="h-3 w-3" />
                                How Food Works
                            </button>
                        </div>
                        <div className="grid grid-cols-2 gap-3 font-pixel text-[10px] sm:grid-cols-5">
                            <div>
                                <span className="text-stone-500">Food Items</span>
                                <div className="text-stone-200">
                                    {village_food.food_available.toLocaleString()}
                                </div>
                            </div>
                            <div>
                                <span className="text-stone-500">Food Points</span>
                                <div className="text-amber-400">
                                    {village_food.food_points.toLocaleString()}
                                </div>
                            </div>
                            <div>
                                <span className="text-stone-500">Weekly Need</span>
                                <div className="text-stone-200">
                                    {village_food.food_needed_per_week.toLocaleString()}
                                </div>
                            </div>
                            <div>
                                <span className="text-stone-500">Weeks Left</span>
                                <div
                                    className={
                                        village_food.weeks_of_food < 4
                                            ? "text-red-400"
                                            : village_food.weeks_of_food < 8
                                              ? "text-yellow-400"
                                              : "text-green-400"
                                    }
                                >
                                    {village_food.weeks_of_food} weeks
                                </div>
                            </div>
                            <div>
                                <span className="text-stone-500">Population</span>
                                <div className="flex items-center gap-1 text-stone-200">
                                    <Users className="h-2 w-2" />
                                    {village_food.population}
                                </div>
                            </div>
                        </div>
                        {(village_food.starving_npcs > 0 || village_food.starving_players > 0) && (
                            <div className="mt-2 flex items-center gap-1 font-pixel text-[10px] text-red-400">
                                <AlertTriangle className="h-3 w-3" />
                                {village_food.starving_npcs + village_food.starving_players} people
                                are starving!
                            </div>
                        )}
                    </div>
                )}

                {/* Plots Grid */}
                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                    {plots.map((plot) => {
                        const StatusIcon = statusIcons[plot.status] || Sprout;
                        const isSelected = selectedPlot?.id === plot.id;

                        return (
                            <div
                                key={plot.id}
                                onClick={() => setSelectedPlot(isSelected ? null : plot)}
                                className={`cursor-pointer rounded-xl border-2 p-4 transition ${statusColors[plot.status]} ${isSelected ? "ring-2 ring-amber-400" : ""}`}
                            >
                                <div className="mb-3 flex items-center justify-between">
                                    <div className="flex items-center gap-2">
                                        <StatusIcon
                                            className={`h-5 w-5 ${plot.has_withered ? "text-red-400" : plot.is_ready ? "text-yellow-400" : "text-green-400"}`}
                                        />
                                        <span className="font-pixel text-sm capitalize text-stone-300">
                                            {plot.crop?.name || "Empty Plot"}
                                        </span>
                                    </div>
                                    {plot.is_watered && (
                                        <span title="Watered">
                                            <Droplets className="h-4 w-4 text-blue-400" />
                                        </span>
                                    )}
                                </div>

                                {plot.status !== "empty" && (
                                    <>
                                        {/* Growth Progress */}
                                        <div className="mb-2">
                                            <div className="mb-1 flex justify-between font-pixel text-[10px] text-stone-400">
                                                <span>Growth</span>
                                                <span>{plot.growth_progress}%</span>
                                            </div>
                                            <div className="h-1.5 w-full overflow-hidden rounded-full bg-stone-700">
                                                <div
                                                    className={`h-full transition-all ${plot.has_withered ? "bg-red-500" : plot.is_ready ? "bg-yellow-400" : "bg-green-500"}`}
                                                    style={{ width: `${plot.growth_progress}%` }}
                                                />
                                            </div>
                                        </div>

                                        {/* Quality */}
                                        <div className="mb-2 flex items-center gap-2 font-pixel text-[10px]">
                                            <Sparkles className="h-3 w-3 text-purple-400" />
                                            <span className="text-stone-400">
                                                Quality: {plot.quality}%
                                            </span>
                                            <span className="text-stone-500">
                                                ({plot.times_tended}x tended)
                                            </span>
                                        </div>

                                        {/* Time Remaining - Live Countdown */}
                                        {plot.ready_at && !plot.is_ready && (
                                            <div className="flex items-center gap-1 font-pixel text-[10px] text-stone-400">
                                                <Clock className="h-3 w-3" />
                                                {getCountdown(plot.ready_at) || "Ready!"}
                                            </div>
                                        )}

                                        {/* Wither Countdown - shows when crop is ready */}
                                        {plot.is_ready && !plot.has_withered && plot.withers_at && (
                                            <div className="flex items-center gap-1 font-pixel text-[10px] text-red-400">
                                                <AlertTriangle className="h-3 w-3" />
                                                Withers in{" "}
                                                {getWitherCountdown(plot.withers_at) || "soon"}
                                            </div>
                                        )}
                                    </>
                                )}

                                {/* Actions */}
                                <div className="mt-3 flex flex-wrap gap-2 border-t border-stone-700 pt-3">
                                    {plot.status === "empty" && (
                                        <button
                                            onClick={(e) => {
                                                e.stopPropagation();
                                                setShowCropModal(plot.id);
                                                setCropSearch("");
                                            }}
                                            disabled={loading !== null}
                                            className="flex w-full items-center justify-center gap-2 rounded bg-amber-600/50 px-3 py-2 font-pixel text-xs text-amber-200 hover:bg-amber-600"
                                        >
                                            <Sprout className="h-4 w-4" />
                                            Choose Crop to Plant
                                        </button>
                                    )}

                                    <div className="flex min-h-[28px] flex-wrap gap-1">
                                        {["planted", "growing"].includes(plot.status) &&
                                            !plot.is_watered && (
                                                <button
                                                    onClick={(e) => {
                                                        e.stopPropagation();
                                                        handleAction("water", plot.id);
                                                    }}
                                                    disabled={loading !== null}
                                                    className="flex items-center gap-1 rounded bg-blue-600/50 px-2 py-1 font-pixel text-[10px] text-blue-200 hover:bg-blue-600"
                                                >
                                                    <Droplets className="h-3 w-3" />
                                                    Water
                                                </button>
                                            )}

                                        {["planted", "growing"].includes(plot.status) && (
                                            <button
                                                onClick={(e) => {
                                                    e.stopPropagation();
                                                    handleAction("tend", plot.id);
                                                }}
                                                disabled={loading !== null}
                                                className="flex items-center gap-1 rounded bg-green-600/50 px-2 py-1 font-pixel text-[10px] text-green-200 hover:bg-green-600"
                                            >
                                                <Hand className="h-3 w-3" />
                                                Tend (5 <Zap className="inline h-2 w-2" />)
                                            </button>
                                        )}

                                        {plot.is_ready && !plot.has_withered && (
                                            <>
                                                <button
                                                    onClick={(e) => {
                                                        e.stopPropagation();
                                                        handleHarvest(plot.id, false);
                                                    }}
                                                    disabled={loading !== null}
                                                    className="flex items-center gap-1 rounded bg-yellow-600/50 px-2 py-1 font-pixel text-[10px] text-yellow-200 hover:bg-yellow-600"
                                                >
                                                    <Check className="h-3 w-3" />
                                                    Harvest
                                                </button>
                                                {canDonateToGranary && (
                                                    <button
                                                        onClick={(e) => {
                                                            e.stopPropagation();
                                                            handleHarvest(plot.id, true);
                                                        }}
                                                        disabled={loading !== null}
                                                        className="flex items-center gap-1 rounded bg-amber-600/50 px-2 py-1 font-pixel text-[10px] text-amber-200 hover:bg-amber-600"
                                                        title="Donate harvest to the local granary"
                                                    >
                                                        <Warehouse className="h-3 w-3" />
                                                        Donate
                                                    </button>
                                                )}
                                            </>
                                        )}
                                    </div>

                                    {(plot.has_withered || plot.status !== "empty") && (
                                        <button
                                            onClick={(e) => {
                                                e.stopPropagation();
                                                handleAction("clear", plot.id);
                                            }}
                                            disabled={loading !== null}
                                            className="flex items-center gap-1 rounded bg-red-600/50 px-2 py-1 font-pixel text-[10px] text-red-200 hover:bg-red-600"
                                        >
                                            <Scissors className="h-3 w-3" />
                                            Clear
                                        </button>
                                    )}
                                </div>
                            </div>
                        );
                    })}

                    {/* Buy New Plot */}
                    {currentPlotCount < max_plots && (
                        <button
                            onClick={() => handleAction("buy-plot")}
                            disabled={!canBuyPlot || loading !== null}
                            className={`flex flex-col items-center justify-center rounded-xl border-2 border-dashed p-4 transition ${canBuyPlot ? "border-amber-600/50 bg-amber-900/10 hover:bg-amber-900/30" : "cursor-not-allowed border-stone-700 bg-stone-800/20 opacity-50"}`}
                        >
                            <Plus className="mb-2 h-8 w-8 text-amber-400" />
                            <span className="font-pixel text-sm text-amber-300">Buy Plot</span>
                            <span className="font-pixel text-[10px] text-stone-400">
                                {nextPlotCost} gold
                            </span>
                        </button>
                    )}
                </div>

                {/* No Plots Message */}
                {plots.length === 0 && (
                    <div className="flex flex-1 items-center justify-center">
                        <div className="text-center">
                            <Wheat className="mx-auto mb-4 h-16 w-16 text-stone-600" />
                            <p className="font-pixel text-lg text-stone-400">No farm plots yet</p>
                            <p className="mb-4 font-pixel text-sm text-stone-500">
                                Purchase your first plot to start farming
                            </p>
                            <button
                                onClick={() => handleAction("buy-plot")}
                                disabled={gold < 100 || loading !== null}
                                className="rounded-lg bg-amber-600 px-4 py-2 font-pixel text-sm text-white hover:bg-amber-500 disabled:opacity-50"
                            >
                                Buy First Plot (100 gold)
                            </button>
                        </div>
                    </div>
                )}

                {/* Crop Types Reference */}
                {crop_types.length > 0 && (
                    <div className="mt-4 rounded-lg border border-stone-700 bg-stone-800/30 p-4">
                        {/* Current Season Badge */}
                        <div className="mb-3 flex items-center justify-between">
                            <div className="flex gap-2">
                                <button
                                    onClick={() => setCropTab("available")}
                                    className={`font-pixel text-sm px-3 py-1 rounded ${
                                        cropTab === "available"
                                            ? "bg-amber-600 text-white"
                                            : "bg-stone-700 text-stone-400 hover:bg-stone-600"
                                    }`}
                                >
                                    Available Crops
                                </button>
                                <button
                                    onClick={() => setCropTab("out_of_season")}
                                    className={`font-pixel text-sm px-3 py-1 rounded ${
                                        cropTab === "out_of_season"
                                            ? "bg-amber-600 text-white"
                                            : "bg-stone-700 text-stone-400 hover:bg-stone-600"
                                    }`}
                                >
                                    Out of Season
                                    {crop_types.filter((c) => !c.is_in_season).length > 0 && (
                                        <span className="ml-1 text-stone-500">
                                            ({crop_types.filter((c) => !c.is_in_season).length})
                                        </span>
                                    )}
                                </button>
                            </div>
                            <div className="flex items-center gap-1.5 rounded bg-stone-700/50 px-2 py-1">
                                <Leaf className="h-3 w-3 text-green-400" />
                                <span className="font-pixel text-xs text-stone-300 capitalize">
                                    {current_season}
                                </span>
                            </div>
                        </div>

                        {/* Crop Grid */}
                        <div className="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                            {crop_types
                                .filter((crop) =>
                                    cropTab === "available"
                                        ? crop.is_in_season
                                        : !crop.is_in_season,
                                )
                                .map((crop) => (
                                    <div
                                        key={crop.id}
                                        className={`rounded-lg border border-stone-700 bg-stone-800/50 p-2 ${!crop.can_plant ? "opacity-50" : ""}`}
                                    >
                                        <div className="flex items-center justify-between">
                                            <span className="font-pixel text-xs text-stone-200">
                                                {crop.name}
                                            </span>
                                            <span className="font-pixel text-[10px] text-green-400">
                                                Lvl {crop.farming_level_required}
                                            </span>
                                        </div>
                                        <div className="mt-1 flex items-center justify-between">
                                            <div className="flex flex-wrap items-center gap-2 font-pixel text-[10px] text-stone-400">
                                                <span>
                                                    <Clock className="inline h-2 w-2" />{" "}
                                                    {formatTime(crop.grow_time_minutes)}
                                                </span>
                                                <span
                                                    className={
                                                        crop.seeds_owned > 0
                                                            ? "text-green-400"
                                                            : "text-red-400"
                                                    }
                                                >
                                                    <Sprout className="inline h-2 w-2" />{" "}
                                                    {crop.seeds_owned} seeds
                                                </span>
                                                <span>
                                                    <Wheat className="inline h-2 w-2" />{" "}
                                                    {crop.yield_min}-{crop.yield_max}
                                                </span>
                                            </div>
                                            <div className="flex gap-1 font-pixel text-[10px]">
                                                {(
                                                    crop.seasons || [
                                                        "spring",
                                                        "summer",
                                                        "autumn",
                                                        "winter",
                                                    ]
                                                ).map((season) => {
                                                    const isCurrent = season === current_season;
                                                    return (
                                                        <span
                                                            key={season}
                                                            className={`px-1 rounded capitalize ${
                                                                isCurrent
                                                                    ? "bg-green-600 text-white"
                                                                    : "bg-stone-600 text-stone-300"
                                                            }`}
                                                        >
                                                            {season}
                                                        </span>
                                                    );
                                                })}
                                            </div>
                                        </div>
                                    </div>
                                ))}
                        </div>

                        {/* Empty state for out of season tab */}
                        {cropTab === "out_of_season" &&
                            crop_types.filter((c) => !c.is_in_season).length === 0 && (
                                <p className="font-pixel text-xs text-stone-500 text-center py-4">
                                    All crops you can grow are in season!
                                </p>
                            )}
                    </div>
                )}
            </div>

            {/* Crop Selection Modal */}
            {showCropModal !== null && (
                <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
                    <div
                        className="absolute inset-0 bg-black/70 backdrop-blur-sm"
                        onClick={() => setShowCropModal(null)}
                    />
                    <div className="relative max-h-[85vh] w-full max-w-3xl overflow-hidden rounded-xl border border-amber-600/50 bg-stone-900 shadow-2xl">
                        {/* Header */}
                        <div className="flex items-center justify-between border-b border-stone-700 px-4 py-3">
                            <div className="flex items-center gap-2">
                                <Sprout className="h-5 w-5 text-green-400" />
                                <h2 className="font-pixel text-lg text-amber-300">Choose a Crop</h2>
                            </div>
                            <button
                                onClick={() => setShowCropModal(null)}
                                className="rounded p-1 hover:bg-stone-700"
                            >
                                <X className="h-5 w-5 text-stone-400" />
                            </button>
                        </div>

                        {/* Search */}
                        <div className="border-b border-stone-700 px-4 py-3">
                            <div className="relative">
                                <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-stone-500" />
                                <input
                                    type="text"
                                    placeholder="Search crops..."
                                    value={cropSearch}
                                    onChange={(e) => setCropSearch(e.target.value)}
                                    className="w-full rounded-lg border border-stone-600 bg-stone-800 py-2 pl-10 pr-4 font-pixel text-sm text-stone-200 placeholder-stone-500 focus:border-amber-500 focus:outline-none"
                                    autoFocus
                                />
                            </div>
                            <div className="mt-2 flex items-center gap-2 font-pixel text-xs text-stone-500">
                                <Sprout className="h-3 w-3 text-green-400" />
                                <span>Select a crop you have seeds for</span>
                            </div>
                        </div>

                        {/* Crop Grid */}
                        <div className="max-h-[55vh] overflow-y-auto p-4">
                            <div className="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                                {crop_types
                                    .filter((c) => c.can_plant)
                                    .filter(
                                        (c) =>
                                            !cropSearch ||
                                            c.name.toLowerCase().includes(cropSearch.toLowerCase()),
                                    )
                                    .sort(
                                        (a, b) =>
                                            a.farming_level_required - b.farming_level_required,
                                    )
                                    .map((crop) => {
                                        const hasSeeds = crop.seeds_owned > 0;
                                        return (
                                            <button
                                                key={crop.id}
                                                onClick={() => {
                                                    if (hasSeeds) {
                                                        handleAction("plant", showCropModal, {
                                                            crop_type_id: crop.id,
                                                        });
                                                        setShowCropModal(null);
                                                    }
                                                }}
                                                disabled={!hasSeeds || loading !== null}
                                                className={`flex flex-col rounded-lg border p-3 text-left transition ${
                                                    hasSeeds
                                                        ? "border-stone-600 bg-stone-800 hover:border-amber-500 hover:bg-stone-700"
                                                        : "cursor-not-allowed border-stone-700 bg-stone-800/50 opacity-50"
                                                }`}
                                            >
                                                <div className="mb-1 flex items-center justify-between">
                                                    <span className="font-pixel text-sm text-amber-300">
                                                        {crop.name}
                                                    </span>
                                                    <span className="font-pixel text-[10px] text-stone-500">
                                                        Lv.{crop.farming_level_required}
                                                    </span>
                                                </div>
                                                <p className="mb-2 font-pixel text-[10px] text-stone-500 line-clamp-2">
                                                    {crop.description}
                                                </p>
                                                <div className="mt-auto flex flex-wrap gap-2 font-pixel text-[10px]">
                                                    <span
                                                        className={`flex items-center gap-1 ${hasSeeds ? "text-green-400" : "text-red-400"}`}
                                                    >
                                                        <Sprout className="h-3 w-3" />
                                                        {crop.seeds_owned} seeds
                                                    </span>
                                                    <span className="flex items-center gap-1 text-blue-400">
                                                        <Clock className="h-3 w-3" />
                                                        {formatTime(crop.grow_time_minutes)}
                                                    </span>
                                                    <span className="flex items-center gap-1 text-amber-400">
                                                        <Wheat className="h-3 w-3" />
                                                        {crop.yield_min}-{crop.yield_max}
                                                    </span>
                                                    <span className="flex items-center gap-1 text-purple-400">
                                                        <Sparkles className="h-3 w-3" />
                                                        {crop.farming_xp} XP
                                                    </span>
                                                </div>
                                            </button>
                                        );
                                    })}
                            </div>
                            {crop_types
                                .filter((c) => c.can_plant)
                                .filter(
                                    (c) =>
                                        !cropSearch ||
                                        c.name.toLowerCase().includes(cropSearch.toLowerCase()),
                                ).length === 0 && (
                                <div className="py-8 text-center">
                                    <Wheat className="mx-auto mb-2 h-8 w-8 text-stone-600" />
                                    <p className="font-pixel text-sm text-stone-500">
                                        No crops match your search
                                    </p>
                                </div>
                            )}
                        </div>

                        {/* Footer */}
                        <div className="border-t border-stone-700 px-4 py-3">
                            <p className="font-pixel text-[10px] text-stone-500">
                                Tip: Higher level crops take longer to grow but yield more XP and
                                produce.
                            </p>
                        </div>
                    </div>
                </div>
            )}

            {/* Food System Help Modal */}
            {showFoodHelp && (
                <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
                    <div
                        className="absolute inset-0 bg-black/70 backdrop-blur-sm"
                        onClick={() => setShowFoodHelp(false)}
                    />
                    <div className="relative max-h-[85vh] w-full max-w-2xl overflow-y-auto rounded-xl border border-amber-600/50 bg-stone-900 shadow-2xl">
                        {/* Header */}
                        <div className="flex items-center justify-between border-b border-stone-700 px-4 py-3">
                            <div className="flex items-center gap-2">
                                <Warehouse className="h-5 w-5 text-amber-400" />
                                <h2 className="font-pixel text-lg text-amber-300">
                                    How Food Works
                                </h2>
                            </div>
                            <button
                                onClick={() => setShowFoodHelp(false)}
                                className="rounded p-1 hover:bg-stone-700"
                            >
                                <X className="h-5 w-5 text-stone-400" />
                            </button>
                        </div>

                        {/* Content */}
                        <div className="space-y-4 p-4">
                            <div>
                                <h3 className="mb-2 font-pixel text-sm text-amber-300">
                                    Feeding the Population
                                </h3>
                                <p className="font-pixel text-xs text-stone-400">
                                    Every week, the town consumes food from the granary to feed its
                                    population. If there's not enough food, people will starve!
                                </p>
                            </div>

                            <div>
                                <h3 className="mb-2 font-pixel text-sm text-amber-300">
                                    Food Values
                                </h3>
                                <p className="mb-2 font-pixel text-xs text-stone-400">
                                    Different foods have different nutritional values. Better food
                                    feeds more people per unit:
                                </p>
                                <div className="space-y-1 rounded-lg border border-stone-700 bg-stone-800/50 p-3">
                                    <div className="flex items-center justify-between font-pixel text-xs">
                                        <span className="text-stone-300">Raw Crops</span>
                                        <span className="text-stone-500">
                                            Wheat, Potatoes, Corn...
                                        </span>
                                        <span className="text-amber-400">Feeds 2</span>
                                    </div>
                                    <div className="flex items-center justify-between font-pixel text-xs">
                                        <span className="text-stone-300">Processed</span>
                                        <span className="text-stone-500">
                                            Grain, Bread, Cheese...
                                        </span>
                                        <span className="text-amber-400">Feeds 4</span>
                                    </div>
                                    <div className="flex items-center justify-between font-pixel text-xs">
                                        <span className="text-stone-300">Cooked</span>
                                        <span className="text-stone-500">
                                            Meat Pie, Roasted Boar...
                                        </span>
                                        <span className="text-amber-400">Feeds 6</span>
                                    </div>
                                    <div className="flex items-center justify-between font-pixel text-xs">
                                        <span className="text-stone-300">Premium</span>
                                        <span className="text-stone-500">
                                            Dragon Steak, Ambrosia...
                                        </span>
                                        <span className="text-amber-400">Feeds 8</span>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <h3 className="mb-2 font-pixel text-sm text-amber-300">
                                    How to Donate Food
                                </h3>
                                <ul className="list-inside list-disc space-y-1 font-pixel text-xs text-stone-400">
                                    <li>
                                        <strong className="text-stone-300">From Farming:</strong>{" "}
                                        Click "Donate" when harvesting to send crops directly to the
                                        granary
                                    </li>
                                    <li>
                                        <strong className="text-stone-300">From Inventory:</strong>{" "}
                                        Go to your inventory and donate any food items you have
                                    </li>
                                </ul>
                            </div>

                            <div>
                                <h3 className="mb-2 font-pixel text-sm text-amber-300">
                                    Consumption Priority
                                </h3>
                                <p className="font-pixel text-xs text-stone-400">
                                    The town will consume cheaper food first (raw crops before
                                    premium food) to preserve valuable food for emergencies.
                                </p>
                            </div>

                            <div className="rounded-lg border border-amber-600/30 bg-amber-900/20 p-3">
                                <p className="font-pixel text-xs text-amber-300">
                                    Tip: Donating food helps the community thrive! A well-fed
                                    population means more workers, traders, and opportunities.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            )}

            {/* Food Breakdown Modal */}
            {showFoodBreakdown && village_food && (
                <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
                    <div
                        className="absolute inset-0 bg-black/70 backdrop-blur-sm"
                        onClick={() => setShowFoodBreakdown(false)}
                    />
                    <div className="relative max-h-[85vh] w-full max-w-2xl overflow-y-auto rounded-xl border border-amber-600/50 bg-stone-900 shadow-2xl">
                        {/* Header */}
                        <div className="flex items-center justify-between border-b border-stone-700 px-4 py-3">
                            <div className="flex items-center gap-2">
                                <Warehouse className="h-5 w-5 text-amber-400" />
                                <h2 className="font-pixel text-lg text-amber-300">
                                    {location_name} Granary Contents
                                </h2>
                            </div>
                            <button
                                onClick={() => setShowFoodBreakdown(false)}
                                className="rounded p-1 hover:bg-stone-700"
                            >
                                <X className="h-5 w-5 text-stone-400" />
                            </button>
                        </div>

                        {/* Summary */}
                        <div className="border-b border-stone-700 px-4 py-3">
                            <div className="grid grid-cols-2 gap-4 font-pixel text-xs sm:grid-cols-4">
                                <div>
                                    <span className="text-stone-500">Total Items</span>
                                    <div className="text-lg text-stone-200">
                                        {village_food.food_available.toLocaleString()}
                                    </div>
                                </div>
                                <div>
                                    <span className="text-stone-500">Total Food Points</span>
                                    <div className="text-lg text-amber-400">
                                        {village_food.food_points.toLocaleString()}
                                    </div>
                                </div>
                                <div>
                                    <span className="text-stone-500">Population</span>
                                    <div className="text-lg text-stone-200">
                                        {village_food.population}
                                    </div>
                                </div>
                                <div>
                                    <span className="text-stone-500">Weeks of Food</span>
                                    <div
                                        className={`text-lg ${
                                            village_food.weeks_of_food < 4
                                                ? "text-red-400"
                                                : village_food.weeks_of_food < 8
                                                  ? "text-yellow-400"
                                                  : "text-green-400"
                                        }`}
                                    >
                                        {village_food.weeks_of_food}
                                    </div>
                                </div>
                            </div>
                        </div>

                        {/* Food Items Table */}
                        <div className="p-4">
                            <h3 className="mb-3 font-pixel text-sm text-amber-300">
                                Food Breakdown
                            </h3>
                            {village_food.food_breakdown.length > 0 ? (
                                <div className="overflow-hidden rounded-lg border border-stone-700">
                                    <table className="w-full">
                                        <thead className="bg-stone-800">
                                            <tr className="font-pixel text-[10px] text-stone-400">
                                                <th className="px-3 py-2 text-left">Item</th>
                                                <th className="px-3 py-2 text-right">Quantity</th>
                                                <th className="px-3 py-2 text-right">
                                                    Feeds Per Item
                                                </th>
                                                <th className="px-3 py-2 text-right">
                                                    Total Points
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y divide-stone-700">
                                            {village_food.food_breakdown.map((item, index) => (
                                                <tr
                                                    key={index}
                                                    className="font-pixel text-xs hover:bg-stone-800/50"
                                                >
                                                    <td className="px-3 py-2 text-stone-200">
                                                        {item.name}
                                                    </td>
                                                    <td className="px-3 py-2 text-right text-stone-300">
                                                        {item.quantity.toLocaleString()}
                                                    </td>
                                                    <td className="px-3 py-2 text-right text-stone-400">
                                                        {item.food_value}
                                                    </td>
                                                    <td className="px-3 py-2 text-right text-amber-400">
                                                        {item.total_points.toLocaleString()}
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                        <tfoot className="border-t border-stone-600 bg-stone-800">
                                            <tr className="font-pixel text-xs font-bold">
                                                <td className="px-3 py-2 text-stone-200">Total</td>
                                                <td className="px-3 py-2 text-right text-stone-200">
                                                    {village_food.food_available.toLocaleString()}
                                                </td>
                                                <td className="px-3 py-2 text-right text-stone-400">
                                                    —
                                                </td>
                                                <td className="px-3 py-2 text-right text-amber-400">
                                                    {village_food.food_points.toLocaleString()}
                                                </td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            ) : (
                                <div className="rounded-lg border border-stone-700 bg-stone-800/50 py-8 text-center">
                                    <Warehouse className="mx-auto mb-2 h-8 w-8 text-stone-600" />
                                    <p className="font-pixel text-sm text-stone-500">
                                        The granary is empty
                                    </p>
                                    <p className="font-pixel text-xs text-stone-600">
                                        Donate food from your inventory or farming harvests
                                    </p>
                                </div>
                            )}

                            <div className="mt-4 rounded-lg border border-stone-700 bg-stone-800/50 p-3">
                                <p className="font-pixel text-[10px] text-stone-400">
                                    <span className="text-amber-400">Food Points</span> = Quantity ×
                                    Feeds Per Item. Each week, the village consumes food points
                                    equal to its population. Higher quality food feeds more people
                                    per item.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            )}
        </AppLayout>
    );
}
