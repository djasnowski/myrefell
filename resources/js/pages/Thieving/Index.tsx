import { Head, router, usePage } from "@inertiajs/react";
import {
    AlertTriangle,
    ArrowUp,
    Coins,
    Crown,
    Gem,
    Hand,
    Loader2,
    Lock,
    Package,
    Percent,
    Skull,
    User,
    Zap,
} from "lucide-react";
import { useState } from "react";
import AppLayout from "@/layouts/app-layout";
import type { BreadcrumbItem } from "@/types";

interface Target {
    id: string;
    name: string;
    description: string;
    min_level: number;
    energy_cost: number;
    base_xp: number;
    gold_range: [number, number];
    success_rate: number;
    catch_gold_loss: number;
    catch_energy_loss: number;
    is_unlocked: boolean;
    is_legendary: boolean;
    can_attempt: boolean;
}

interface ThievingInfo {
    can_thieve: boolean;
    targets: Target[];
    player_energy: number;
    max_energy: number;
    player_gold: number;
    thieving_level: number;
    thieving_xp: number;
    thieving_xp_progress: number;
    thieving_xp_to_next: number;
    free_slots: number;
}

interface ThieveResult {
    success: boolean;
    caught: boolean;
    message: string;
    gold_stolen?: number;
    gold_lost?: number;
    xp_awarded?: number;
    leveled_up?: boolean;
    loot?: { name: string; quantity: number };
    energy_remaining?: number;
    gold_remaining?: number;
}

interface PageProps {
    location?: {
        type: string;
        id: number;
        name: string;
    };
    thieving_info: ThievingInfo;
    [key: string]: unknown;
}

function TargetCard({
    target,
    onThieve,
    loading,
}: {
    target: Target;
    onThieve: (id: string) => void;
    loading: string | null;
}) {
    const isLoading = loading === target.id;

    // Target-specific styling
    const getTargetStyle = () => {
        if (target.is_legendary) {
            return {
                border: "border-purple-500/50",
                bg: "bg-purple-900/20",
                iconBg: "bg-purple-900/50",
                iconColor: "text-purple-400",
                buttonBg: "bg-purple-600 hover:bg-purple-500",
            };
        }
        if (target.min_level >= 60) {
            return {
                border: "border-amber-500/50",
                bg: "bg-amber-900/20",
                iconBg: "bg-amber-900/50",
                iconColor: "text-amber-400",
                buttonBg: "bg-amber-600 hover:bg-amber-500",
            };
        }
        if (target.min_level >= 40) {
            return {
                border: "border-blue-500/50",
                bg: "bg-blue-900/20",
                iconBg: "bg-blue-900/50",
                iconColor: "text-blue-400",
                buttonBg: "bg-blue-600 hover:bg-blue-500",
            };
        }
        return {
            border: "border-stone-600/50",
            bg: "bg-stone-800/30",
            iconBg: "bg-stone-800/50",
            iconColor: "text-stone-400",
            buttonBg: "bg-stone-600 hover:bg-stone-500",
        };
    };

    const style = getTargetStyle();

    const getTargetIcon = () => {
        if (target.is_legendary) return <Skull className={`h-8 w-8 ${style.iconColor}`} />;
        if (target.id === "noble" || target.id === "royal_vault")
            return <Crown className={`h-8 w-8 ${style.iconColor}`} />;
        if (target.id === "castle_treasury")
            return <Gem className={`h-8 w-8 ${style.iconColor}`} />;
        return <User className={`h-8 w-8 ${style.iconColor}`} />;
    };

    return (
        <div
            className={`rounded-xl border-2 p-4 transition ${style.border} ${style.bg} ${
                !target.is_unlocked ? "opacity-50" : ""
            }`}
        >
            {/* Header */}
            <div className="mb-3 flex items-start gap-3">
                <div className={`rounded-lg p-2 ${style.iconBg}`}>{getTargetIcon()}</div>
                <div className="flex-1">
                    <div className="flex items-center gap-2">
                        <h3 className="font-pixel text-base text-stone-200">{target.name}</h3>
                        {target.is_legendary && (
                            <span className="rounded bg-purple-900/50 px-1.5 py-0.5 font-pixel text-[10px] text-purple-300">
                                LEGENDARY
                            </span>
                        )}
                        {!target.is_unlocked && <Lock className="h-4 w-4 text-stone-500" />}
                    </div>
                    <p className="mt-1 text-xs text-stone-400">{target.description}</p>
                </div>
            </div>

            {/* Stats Grid */}
            <div className="mb-3 grid grid-cols-2 gap-2">
                <div className="rounded-lg bg-stone-900/50 p-2">
                    <div className="flex items-center gap-2">
                        <Coins className="h-4 w-4 text-amber-400" />
                        <span className="font-pixel text-sm text-amber-300">
                            {target.gold_range[0]}-{target.gold_range[1]}g
                        </span>
                    </div>
                    <div className="font-pixel text-[10px] text-stone-500">Gold Range</div>
                </div>
                <div className="rounded-lg bg-stone-900/50 p-2">
                    <div className="flex items-center gap-2">
                        <Percent className="h-4 w-4 text-green-400" />
                        <span
                            className={`font-pixel text-sm ${
                                target.success_rate >= 80
                                    ? "text-green-300"
                                    : target.success_rate >= 60
                                      ? "text-yellow-300"
                                      : target.success_rate >= 30
                                        ? "text-orange-300"
                                        : "text-red-300"
                            }`}
                        >
                            {target.success_rate}%
                        </span>
                    </div>
                    <div className="font-pixel text-[10px] text-stone-500">Success Rate</div>
                </div>
                <div className="rounded-lg bg-stone-900/50 p-2">
                    <div className="flex items-center gap-2">
                        <Zap className="h-4 w-4 text-yellow-400" />
                        <span className="font-pixel text-sm text-yellow-300">
                            {target.energy_cost}
                        </span>
                    </div>
                    <div className="font-pixel text-[10px] text-stone-500">Energy</div>
                </div>
                <div className="rounded-lg bg-stone-900/50 p-2">
                    <div className="flex items-center gap-2">
                        <ArrowUp className="h-4 w-4 text-cyan-400" />
                        <span className="font-pixel text-sm text-cyan-300">+{target.base_xp}</span>
                    </div>
                    <div className="font-pixel text-[10px] text-stone-500">XP</div>
                </div>
            </div>

            {/* Penalty Warning */}
            <div className="mb-3 rounded-lg border border-red-900/50 bg-red-900/20 p-2">
                <div className="flex items-center gap-2 text-red-400">
                    <AlertTriangle className="h-4 w-4" />
                    <span className="font-pixel text-[10px]">
                        If caught: -{target.catch_gold_loss}g, -{target.catch_energy_loss} energy
                    </span>
                </div>
            </div>

            {/* Action Button */}
            {!target.is_unlocked ? (
                <div className="rounded-lg bg-stone-900/50 py-2 text-center">
                    <span className="font-pixel text-xs text-stone-500">
                        Requires Level {target.min_level}
                    </span>
                </div>
            ) : (
                <button
                    onClick={() => onThieve(target.id)}
                    disabled={!target.can_attempt || loading !== null}
                    className={`flex w-full items-center justify-center gap-2 rounded-lg py-2.5 font-pixel text-sm text-stone-900 transition disabled:cursor-not-allowed disabled:opacity-50 ${style.buttonBg}`}
                >
                    {isLoading ? (
                        <>
                            <Loader2 className="h-4 w-4 animate-spin" />
                            Attempting...
                        </>
                    ) : (
                        <>
                            <Hand className="h-4 w-4" />
                            Pickpocket
                        </>
                    )}
                </button>
            )}
        </div>
    );
}

export default function ThievingIndex() {
    const { location, thieving_info } = usePage<PageProps>().props;
    const [loading, setLoading] = useState<string | null>(null);
    const [result, setResult] = useState<ThieveResult | null>(null);
    const [currentEnergy, setCurrentEnergy] = useState(thieving_info.player_energy);
    const [currentGold, setCurrentGold] = useState(thieving_info.player_gold);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: "Dashboard", href: "/dashboard" },
        ...(location
            ? [
                  { title: location.name, href: `/${location.type}s/${location.id}` },
                  { title: "Thieving", href: "#" },
              ]
            : [{ title: "Thieving", href: "#" }]),
    ];

    const baseUrl = location
        ? `/${location.type}s/${location.id}/thieving`
        : "/villages/1/thieving";

    const handleThieve = async (targetId: string) => {
        setLoading(targetId);
        setResult(null);

        try {
            const response = await fetch(`${baseUrl}/attempt`, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN":
                        document
                            .querySelector('meta[name="csrf-token"]')
                            ?.getAttribute("content") || "",
                },
                body: JSON.stringify({ target: targetId }),
            });

            const data: ThieveResult = await response.json();
            setResult(data);

            if (data.energy_remaining !== undefined) {
                setCurrentEnergy(data.energy_remaining);
            }
            if (data.gold_remaining !== undefined) {
                setCurrentGold(data.gold_remaining);
            }

            // Reload to update sidebar and info
            router.reload({ only: ["thieving_info", "sidebar"] });
        } catch {
            setResult({ success: false, caught: false, message: "An error occurred" });
        } finally {
            setLoading(null);
        }
    };

    // Separate targets by unlock status
    const unlockedTargets = thieving_info.targets.filter((t) => t.is_unlocked);
    const lockedTargets = thieving_info.targets.filter((t) => !t.is_unlocked);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Thieving - ${location?.name || "Unknown"}`} />
            <div className="flex h-full flex-1 flex-col overflow-hidden p-4">
                {/* Header */}
                <div className="mb-6 flex items-center justify-between">
                    <div className="flex items-center gap-3">
                        <div className="rounded-lg bg-stone-800/50 p-3">
                            <Hand className="h-8 w-8 text-stone-400" />
                        </div>
                        <div>
                            <h1 className="font-pixel text-2xl text-stone-300">Thieving</h1>
                            <p className="font-pixel text-sm text-stone-500">
                                Pickpocket targets for gold and items
                            </p>
                        </div>
                    </div>
                    <div className="flex items-center gap-4">
                        {/* Skill Progress */}
                        <div className="rounded-lg border-2 border-stone-600/50 bg-stone-800/50 px-4 py-2">
                            <div className="flex items-center gap-2">
                                <Hand className="h-4 w-4 text-stone-400" />
                                <span className="font-pixel text-sm text-stone-300">
                                    Lv. {thieving_info.thieving_level}
                                </span>
                            </div>
                            <div className="mt-1 h-1 w-24 overflow-hidden rounded-full bg-stone-700">
                                <div
                                    className="h-full bg-stone-400 transition-all"
                                    style={{ width: `${thieving_info.thieving_xp_progress}%` }}
                                />
                            </div>
                        </div>
                        <div className="flex items-center gap-2 rounded-lg border-2 border-stone-600/50 bg-stone-800/50 px-4 py-2">
                            <Zap className="h-4 w-4 text-yellow-400" />
                            <span className="font-pixel text-sm text-stone-300">
                                {currentEnergy}/{thieving_info.max_energy}
                            </span>
                        </div>
                        <div className="flex items-center gap-2 rounded-lg border-2 border-stone-600/50 bg-stone-800/50 px-4 py-2">
                            <Coins className="h-4 w-4 text-amber-400" />
                            <span className="font-pixel text-sm text-amber-300">
                                {currentGold}g
                            </span>
                        </div>
                    </div>
                </div>

                {/* Result Message */}
                {result && (
                    <div
                        className={`mb-6 rounded-xl border-2 p-4 ${
                            result.success
                                ? "border-green-500/50 bg-green-900/20"
                                : result.caught
                                  ? "border-red-500/50 bg-red-900/20"
                                  : "border-stone-600/50 bg-stone-800/30"
                        }`}
                    >
                        <div className="flex items-center gap-3">
                            {result.success ? (
                                <Coins className="h-6 w-6 text-green-400" />
                            ) : result.caught ? (
                                <AlertTriangle className="h-6 w-6 text-red-400" />
                            ) : (
                                <Hand className="h-6 w-6 text-stone-400" />
                            )}
                            <div>
                                <p
                                    className={`font-pixel text-sm ${
                                        result.success
                                            ? "text-green-300"
                                            : result.caught
                                              ? "text-red-300"
                                              : "text-stone-300"
                                    }`}
                                >
                                    {result.message}
                                </p>
                                <div className="mt-1 flex items-center gap-3">
                                    {result.xp_awarded && (
                                        <span className="font-pixel text-xs text-cyan-400">
                                            +{result.xp_awarded} XP
                                        </span>
                                    )}
                                    {result.leveled_up && (
                                        <span className="flex items-center gap-1 font-pixel text-xs text-yellow-300">
                                            <ArrowUp className="h-3 w-3" />
                                            Level Up!
                                        </span>
                                    )}
                                    {result.loot && (
                                        <span className="flex items-center gap-1 font-pixel text-xs text-purple-400">
                                            <Package className="h-3 w-3" />+{result.loot.quantity}x{" "}
                                            {result.loot.name}
                                        </span>
                                    )}
                                </div>
                            </div>
                        </div>
                    </div>
                )}

                {/* Targets Grid */}
                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                    {unlockedTargets.map((target) => (
                        <TargetCard
                            key={target.id}
                            target={target}
                            onThieve={handleThieve}
                            loading={loading}
                        />
                    ))}
                </div>

                {/* Locked Targets */}
                {lockedTargets.length > 0 && (
                    <>
                        <h2 className="mb-4 mt-8 flex items-center gap-2 font-pixel text-lg text-stone-500">
                            <Lock className="h-5 w-5" />
                            Locked Targets
                        </h2>
                        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                            {lockedTargets.map((target) => (
                                <TargetCard
                                    key={target.id}
                                    target={target}
                                    onThieve={handleThieve}
                                    loading={loading}
                                />
                            ))}
                        </div>
                    </>
                )}
            </div>
        </AppLayout>
    );
}
