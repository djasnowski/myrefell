import { Head, router, usePage } from "@inertiajs/react";
import {
    ArrowLeft,
    Clock,
    Coins,
    Crown,
    Eye,
    EyeOff,
    Flame,
    Hammer,
    Heart,
    Lock,
    MapPin,
    Skull,
    Sparkles,
    TrendingUp,
    Unlock,
    X,
    Zap,
} from "lucide-react";
import { useState, useEffect } from "react";
import AppLayout from "@/layouts/app-layout";
import { locationPath } from "@/lib/utils";
import type { BreadcrumbItem } from "@/types";

interface Cult {
    id: number;
    name: string;
    description: string;
    icon: string;
    color: string;
    member_count: number;
}

interface CultBelief {
    id: number;
    name: string;
    description: string;
    icon: string;
    type: string;
    effects: Record<string, number>;
    required_hideout_tier: number;
    hp_cost: number;
    energy_cost: number;
    is_unlocked?: boolean;
    tier_name?: string;
}

interface Project {
    id: number;
    project_type: string;
    project_type_display: string;
    description: string;
    target_tier: number;
    target_tier_name: string;
    status: string;
    progress: number;
    gold_required: number;
    gold_invested: number;
    devotion_required: number;
    devotion_invested: number;
    construction_ends_at: string | null;
    remaining_time: string | null;
    remaining_seconds: number | null;
    is_constructing: boolean;
    is_construction_complete: boolean;
}

interface TierInfo {
    tier: number;
    name: string;
    gold: number;
    devotion: number;
    is_current: boolean;
    is_unlocked: boolean;
    unlocked_beliefs: string[];
}

interface Hideout {
    exists: boolean;
    is_cult: boolean;
    is_member: boolean;
    is_prophet: boolean;
    is_priest: boolean;
    player_devotion: number;
    can_build?: boolean;
    tier?: number;
    tier_name?: string;
    location_type?: string;
    location_id?: number;
    location_name?: string;
    max_tier?: number;
    can_upgrade?: boolean;
    upgrade_cost?: { gold: number; devotion: number } | null;
    next_tier_name?: string | null;
    is_at_hideout?: boolean;
    available_beliefs?: CultBelief[];
    all_cult_beliefs?: CultBelief[];
    active_project?: Project | null;
    tier_progression?: TierInfo[];
}

interface Player {
    gold: number;
    energy: number;
    current_hp: number;
    max_hp: number;
}

interface Location {
    type: string | null;
    id: number | null;
    name: string | null;
}

interface PageProps {
    cult: Cult;
    hideout: Hideout;
    player: Player;
    location: Location;
    [key: string]: unknown;
}

const formatEffectKey = (key: string): string => {
    return key
        .split("_")
        .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
        .join(" ");
};

const formatCountdown = (seconds: number): string => {
    if (seconds <= 0) return "Complete!";

    const days = Math.floor(seconds / 86400);
    const hours = Math.floor((seconds % 86400) / 3600);
    const minutes = Math.floor((seconds % 3600) / 60);
    const secs = seconds % 60;

    if (days > 0) {
        return `${days}d ${hours}h ${minutes}m`;
    }
    if (hours > 0) {
        return `${hours}h ${minutes}m ${secs}s`;
    }
    if (minutes > 0) {
        return `${minutes}m ${secs}s`;
    }
    return `${secs}s`;
};

export default function HideoutIndex() {
    const { cult, hideout, player, location } = usePage<PageProps>().props;

    // Build location-scoped base URL for hideout routes
    const hideoutBaseUrl =
        hideout.location_type && hideout.location_id
            ? `${locationPath(hideout.location_type, hideout.location_id)}/cults/${cult.id}/hideout`
            : location.type && location.id
              ? `${locationPath(location.type, location.id)}/cults/${cult.id}/hideout`
              : `/cults/${cult.id}/hideout`;

    // Build location-scoped URL for cult show page
    const cultUrl =
        hideout.location_type && hideout.location_id
            ? `${locationPath(hideout.location_type, hideout.location_id)}/religions/${cult.id}`
            : location.type && location.id
              ? `${locationPath(location.type, location.id)}/religions/${cult.id}`
              : `/religions/${cult.id}`;

    const [isLoading, setIsLoading] = useState(false);
    const [contributeGold, setContributeGold] = useState(0);
    const [contributeDevotion, setContributeDevotion] = useState(0);
    const [showBuildModal, setShowBuildModal] = useState(false);
    const [countdown, setCountdown] = useState<number | null>(null);

    // Initialize countdown from project
    useEffect(() => {
        if (hideout.active_project?.is_constructing && hideout.active_project.remaining_seconds) {
            setCountdown(hideout.active_project.remaining_seconds);
        }
    }, [hideout.active_project]);

    // Countdown timer effect
    useEffect(() => {
        if (countdown === null || countdown <= 0) return;

        const interval = setInterval(() => {
            setCountdown((prev) => {
                if (prev === null || prev <= 0) return 0;
                const newValue = prev - 1;
                if (newValue === 0) {
                    setTimeout(() => router.reload(), 500);
                }
                return newValue;
            });
        }, 1000);

        return () => clearInterval(interval);
    }, [countdown]);

    const canBuildHere = location.type && location.id && location.name;

    const breadcrumbs: BreadcrumbItem[] = [
        { title: "Dashboard", href: "/dashboard" },
        { title: cult.name, href: cultUrl },
        { title: "Hideout", href: hideoutBaseUrl },
    ];

    const handleBuildHideout = () => {
        if (!canBuildHere) return;
        setIsLoading(true);
        router.post(
            `${hideoutBaseUrl}/build`,
            {},
            {
                preserveScroll: true,
                onSuccess: () => {
                    setShowBuildModal(false);
                    router.reload();
                },
                onFinish: () => {
                    setIsLoading(false);
                },
            },
        );
    };

    const handleStartUpgrade = () => {
        setIsLoading(true);
        router.post(
            `${hideoutBaseUrl}/upgrade`,
            {},
            {
                preserveScroll: true,
                onSuccess: () => router.reload(),
                onFinish: () => setIsLoading(false),
            },
        );
    };

    const handleContribute = (projectId: number) => {
        setIsLoading(true);
        router.post(
            `${hideoutBaseUrl}/projects/${projectId}/contribute`,
            {
                gold: contributeGold,
                devotion: contributeDevotion,
            },
            {
                preserveScroll: true,
                onSuccess: () => {
                    setContributeGold(0);
                    setContributeDevotion(0);
                    router.reload();
                },
                onFinish: () => setIsLoading(false),
            },
        );
    };

    const handleCompleteProject = (projectId: number) => {
        setIsLoading(true);
        router.post(
            `${hideoutBaseUrl}/projects/${projectId}/complete`,
            {},
            {
                preserveScroll: true,
                onSuccess: () => router.reload(),
                onFinish: () => setIsLoading(false),
            },
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`${cult.name} Hideout`} />
            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                {/* Back Button */}
                <a
                    href={cultUrl}
                    className="flex items-center gap-2 font-pixel text-sm text-stone-400 hover:text-red-400 transition"
                >
                    <ArrowLeft className="h-4 w-4" />
                    Back to {cult.name}
                </a>

                {/* Header - Dark theme */}
                <div className="flex items-start justify-between">
                    <div className="flex items-center gap-3">
                        <div className="rounded-lg bg-red-900/30 p-2 border border-red-800/50">
                            <Skull className="h-8 w-8 text-red-500" />
                        </div>
                        <div>
                            <h1 className="font-pixel text-2xl text-red-400">
                                {hideout.tier_name || "Cult Hideout"}
                            </h1>
                            {hideout.exists && (
                                <div className="flex items-center gap-2">
                                    <span className="rounded bg-red-900/50 px-2 py-0.5 font-pixel text-xs text-red-300 border border-red-800/50">
                                        Tier {hideout.tier}
                                    </span>
                                    <span className="flex items-center gap-1 font-pixel text-xs text-stone-400">
                                        <MapPin className="h-3 w-3" />
                                        {hideout.location_name}
                                    </span>
                                </div>
                            )}
                        </div>
                    </div>
                    <div className="flex items-center gap-4">
                        <div className="flex items-center gap-2 font-pixel text-sm">
                            <Coins className="h-4 w-4 text-amber-400" />
                            <span className="text-stone-300">{player.gold.toLocaleString()}</span>
                        </div>
                        <div className="flex items-center gap-2 font-pixel text-sm">
                            <Zap className="h-4 w-4 text-green-400" />
                            <span className="text-stone-300">{player.energy}</span>
                        </div>
                        <div className="flex items-center gap-2 font-pixel text-sm">
                            <Heart className="h-4 w-4 text-red-500" />
                            <span className="text-stone-300">
                                {player.current_hp}/{player.max_hp}
                            </span>
                        </div>
                        {hideout.is_member && (
                            <div className="flex items-center gap-2 font-pixel text-sm">
                                <Flame className="h-4 w-4 text-orange-400" />
                                <span className="text-stone-300">
                                    {hideout.player_devotion.toLocaleString()} devotion
                                </span>
                            </div>
                        )}
                    </div>
                </div>

                {/* Not Built Yet */}
                {!hideout.exists && hideout.is_prophet && (
                    <div className="rounded-lg border border-red-800/50 bg-gradient-to-b from-red-950/40 to-stone-900/80 p-6 text-center">
                        <EyeOff className="mx-auto h-12 w-12 text-red-500 mb-4" />
                        <h2 className="font-pixel text-lg text-red-300 mb-2">
                            Establish Your Hideout
                        </h2>
                        <p className="font-pixel text-sm text-stone-400 mb-4">
                            Choose a location to establish your cult's hidden sanctuary. This secret
                            location will serve as the base for your forbidden operations.
                        </p>

                        {canBuildHere ? (
                            <>
                                <div className="mb-4 rounded border border-red-800/30 bg-stone-900/50 p-3 inline-block">
                                    <div className="flex items-center gap-2 font-pixel text-sm">
                                        <MapPin className="h-4 w-4 text-red-400" />
                                        <span className="text-stone-400">Your Location:</span>
                                        <span className="text-white">{location.name}</span>
                                        <span className="text-stone-500 capitalize">
                                            ({location.type})
                                        </span>
                                    </div>
                                </div>
                                <div>
                                    <button
                                        onClick={() => setShowBuildModal(true)}
                                        disabled={isLoading}
                                        className="rounded bg-red-700 px-6 py-2 font-pixel text-sm text-white transition hover:bg-red-600 disabled:opacity-50 border border-red-600"
                                    >
                                        Establish Hidden Cellar
                                    </button>
                                </div>
                            </>
                        ) : (
                            <div className="rounded border border-red-800/30 bg-red-950/30 p-4">
                                <p className="font-pixel text-sm text-red-300">
                                    You must be at a valid location (village, barony, town, or
                                    kingdom) to establish your hideout.
                                </p>
                            </div>
                        )}
                    </div>
                )}

                {!hideout.exists && !hideout.is_prophet && (
                    <div className="rounded-lg border border-stone-700 bg-stone-800/50 p-6 text-center">
                        <EyeOff className="mx-auto h-12 w-12 text-stone-500 mb-4" />
                        <p className="font-pixel text-sm text-stone-400">
                            The hideout has not been established yet. The Prophet must set it up
                            first.
                        </p>
                    </div>
                )}

                {hideout.exists && (
                    <div className="grid gap-6 lg:grid-cols-3">
                        {/* Main Content */}
                        <div className="lg:col-span-2 space-y-6">
                            {/* Active Project */}
                            {hideout.active_project && (
                                <div className="rounded-lg border border-red-800/50 bg-gradient-to-b from-red-950/30 to-stone-900/80 p-4">
                                    <h2 className="mb-3 flex items-center gap-2 font-pixel text-sm text-red-300">
                                        <Hammer className="h-4 w-4" />
                                        Construction in Progress
                                    </h2>
                                    <div
                                        className={`rounded-lg border p-3 ${
                                            hideout.active_project.is_constructing
                                                ? "border-orange-500/30 bg-orange-900/20"
                                                : "border-stone-600 bg-stone-800/50"
                                        }`}
                                    >
                                        <div className="mb-3">
                                            <div className="font-pixel text-sm text-white mb-1">
                                                Upgrading to{" "}
                                                {hideout.active_project.target_tier_name}
                                            </div>

                                            {/* Construction Timer */}
                                            {hideout.active_project.is_constructing && (
                                                <div className="mb-2 rounded bg-orange-900/50 p-2 text-center relative overflow-hidden border border-orange-800/30">
                                                    <div className="relative z-10">
                                                        <div className="flex items-center justify-center gap-2 mb-1">
                                                            <Clock className="h-4 w-4 text-orange-400 animate-pulse" />
                                                            <span className="font-pixel text-sm text-orange-300">
                                                                Dark Rituals in Progress
                                                            </span>
                                                        </div>
                                                        <div className="font-pixel text-lg text-orange-400">
                                                            {countdown !== null
                                                                ? formatCountdown(countdown)
                                                                : hideout.active_project
                                                                      .remaining_time ||
                                                                  "Calculating..."}
                                                        </div>
                                                        <div className="font-pixel text-xs text-stone-400 mt-1">
                                                            {hideout.active_project
                                                                .is_construction_complete
                                                                ? "Ready for completion!"
                                                                : "Time remaining"}
                                                        </div>
                                                    </div>
                                                </div>
                                            )}

                                            {/* Progress bar - only show when not constructing */}
                                            {!hideout.active_project.is_constructing && (
                                                <>
                                                    <div className="mb-1 h-3 rounded-full bg-stone-700">
                                                        <div
                                                            className="h-full rounded-full bg-red-600 transition-all"
                                                            style={{
                                                                width: `${hideout.active_project.progress}%`,
                                                            }}
                                                        />
                                                    </div>
                                                    <div className="font-pixel text-xs text-stone-400">
                                                        {hideout.active_project.progress}% funded
                                                    </div>
                                                </>
                                            )}
                                        </div>

                                        {/* Requirements */}
                                        <div className="grid grid-cols-2 gap-3 mb-3">
                                            <div className="rounded bg-stone-900/50 p-2 border border-stone-700/50">
                                                <div className="font-pixel text-xs text-stone-400">
                                                    Gold Required
                                                </div>
                                                <div
                                                    className={`font-pixel text-xs ${
                                                        hideout.active_project.gold_invested >=
                                                        hideout.active_project.gold_required
                                                            ? "text-green-400"
                                                            : "text-amber-400"
                                                    }`}
                                                >
                                                    {hideout.active_project.gold_invested.toLocaleString()}{" "}
                                                    /{" "}
                                                    {hideout.active_project.gold_required.toLocaleString()}
                                                    {hideout.active_project.gold_invested >=
                                                        hideout.active_project.gold_required &&
                                                        " ✓"}
                                                </div>
                                            </div>
                                            <div className="rounded bg-stone-900/50 p-2 border border-stone-700/50">
                                                <div className="font-pixel text-xs text-stone-400">
                                                    Devotion Required
                                                </div>
                                                <div
                                                    className={`font-pixel text-xs ${
                                                        hideout.active_project.devotion_invested >=
                                                        hideout.active_project.devotion_required
                                                            ? "text-green-400"
                                                            : "text-orange-400"
                                                    }`}
                                                >
                                                    {hideout.active_project.devotion_invested.toLocaleString()}{" "}
                                                    /{" "}
                                                    {hideout.active_project.devotion_required.toLocaleString()}
                                                    {hideout.active_project.devotion_invested >=
                                                        hideout.active_project.devotion_required &&
                                                        " ✓"}
                                                </div>
                                            </div>
                                        </div>

                                        {/* Contribute Form - only show when not constructing */}
                                        {hideout.is_member &&
                                            !hideout.active_project.is_constructing && (
                                                <div className="border-t border-stone-700 pt-3">
                                                    <div className="flex flex-wrap gap-3 items-end">
                                                        <div className="flex-1 min-w-[100px]">
                                                            <label className="block font-pixel text-xs text-amber-400 mb-1">
                                                                Gold
                                                            </label>
                                                            <input
                                                                type="number"
                                                                placeholder="0"
                                                                value={contributeGold}
                                                                onChange={(e) =>
                                                                    setContributeGold(
                                                                        Math.max(
                                                                            0,
                                                                            parseInt(
                                                                                e.target.value,
                                                                            ) || 0,
                                                                        ),
                                                                    )
                                                                }
                                                                min={0}
                                                                max={player.gold}
                                                                className="w-full rounded border border-stone-600 bg-stone-800 px-3 py-2 font-pixel text-xs text-white"
                                                            />
                                                        </div>
                                                        <div className="flex-1 min-w-[100px]">
                                                            <label className="block font-pixel text-xs text-orange-400 mb-1">
                                                                Devotion
                                                            </label>
                                                            <input
                                                                type="number"
                                                                placeholder="0"
                                                                value={contributeDevotion}
                                                                onChange={(e) =>
                                                                    setContributeDevotion(
                                                                        Math.max(
                                                                            0,
                                                                            parseInt(
                                                                                e.target.value,
                                                                            ) || 0,
                                                                        ),
                                                                    )
                                                                }
                                                                min={0}
                                                                max={hideout.player_devotion}
                                                                className="w-full rounded border border-stone-600 bg-stone-800 px-3 py-2 font-pixel text-xs text-white"
                                                            />
                                                        </div>
                                                        <button
                                                            onClick={() =>
                                                                handleContribute(
                                                                    hideout.active_project!.id,
                                                                )
                                                            }
                                                            disabled={
                                                                isLoading ||
                                                                (contributeGold === 0 &&
                                                                    contributeDevotion === 0)
                                                            }
                                                            className="rounded bg-red-700 px-4 py-2 font-pixel text-xs text-white transition hover:bg-red-600 disabled:opacity-50 border border-red-600"
                                                        >
                                                            Contribute
                                                        </button>
                                                    </div>
                                                </div>
                                            )}

                                        {/* Under construction message or Complete button */}
                                        {hideout.active_project.is_constructing && (
                                            <div className="border-t border-orange-500/30 pt-3 text-center">
                                                {hideout.active_project.is_construction_complete ? (
                                                    hideout.is_prophet ? (
                                                        <button
                                                            onClick={() =>
                                                                handleCompleteProject(
                                                                    hideout.active_project!.id,
                                                                )
                                                            }
                                                            disabled={isLoading}
                                                            className="rounded bg-orange-600 px-4 py-2 font-pixel text-sm text-white transition hover:bg-orange-500 disabled:opacity-50"
                                                        >
                                                            Complete Construction
                                                        </button>
                                                    ) : (
                                                        <span className="font-pixel text-xs text-orange-300">
                                                            Construction complete! Awaiting Prophet
                                                            to finalize.
                                                        </span>
                                                    )
                                                ) : (
                                                    <span className="font-pixel text-xs text-orange-300">
                                                        Fully funded! Dark rituals in progress...
                                                    </span>
                                                )}
                                            </div>
                                        )}
                                    </div>
                                </div>
                            )}

                            {/* Forbidden Arts - Cult Beliefs */}
                            <div className="rounded-lg border border-red-800/50 bg-gradient-to-b from-red-950/30 to-stone-900/80 p-4">
                                <h2 className="mb-3 flex items-center gap-2 font-pixel text-sm text-red-300">
                                    <Skull className="h-4 w-4" />
                                    Forbidden Arts
                                </h2>
                                <p className="mb-4 font-pixel text-xs text-stone-400">
                                    Dark powers unlocked through your hideout. Activate these at the
                                    Shrine using devotion, energy, and a blood sacrifice.
                                </p>
                                <div className="grid gap-3">
                                    {hideout.all_cult_beliefs?.map((belief) => (
                                        <div
                                            key={belief.id}
                                            className={`rounded-lg border p-3 ${
                                                belief.is_unlocked
                                                    ? "border-red-700/50 bg-red-950/30"
                                                    : "border-stone-700/50 bg-stone-900/30 opacity-60"
                                            }`}
                                        >
                                            <div className="flex items-start justify-between">
                                                <div className="flex items-center gap-2">
                                                    {belief.is_unlocked ? (
                                                        <Unlock className="h-5 w-5 text-red-400" />
                                                    ) : (
                                                        <Lock className="h-5 w-5 text-stone-500" />
                                                    )}
                                                    <div>
                                                        <div
                                                            className={`font-pixel text-sm ${
                                                                belief.is_unlocked
                                                                    ? "text-white"
                                                                    : "text-stone-400"
                                                            }`}
                                                        >
                                                            {belief.name}
                                                        </div>
                                                        <div className="font-pixel text-xs text-stone-500">
                                                            {belief.description}
                                                        </div>
                                                    </div>
                                                </div>
                                                <div className="text-right">
                                                    {belief.is_unlocked ? (
                                                        <span className="rounded bg-red-900/50 px-2 py-0.5 font-pixel text-xs text-red-300 border border-red-800/30">
                                                            Unlocked
                                                        </span>
                                                    ) : (
                                                        <span className="rounded bg-stone-800 px-2 py-0.5 font-pixel text-xs text-stone-400 border border-stone-700">
                                                            Requires {belief.tier_name}
                                                        </span>
                                                    )}
                                                </div>
                                            </div>

                                            {/* Effects */}
                                            <div className="mt-2 font-pixel text-xs text-red-300/80">
                                                {Object.entries(belief.effects).map(
                                                    ([key, value]) => (
                                                        <span key={key} className="mr-3">
                                                            {formatEffectKey(key)}:{" "}
                                                            {value > 0 ? "+" : ""}
                                                            {value}%
                                                        </span>
                                                    ),
                                                )}
                                            </div>

                                            {/* Activation Cost */}
                                            {belief.is_unlocked && (
                                                <div className="mt-2 flex items-center gap-3 font-pixel text-xs text-stone-400">
                                                    <span className="flex items-center gap-1">
                                                        <Zap className="h-3 w-3 text-green-400" />
                                                        {belief.energy_cost}
                                                    </span>
                                                    <span className="flex items-center gap-1">
                                                        <Heart className="h-3 w-3 text-red-500" />
                                                        {belief.hp_cost} HP
                                                    </span>
                                                    <span className="text-stone-500">
                                                        + Devotion
                                                    </span>
                                                </div>
                                            )}
                                        </div>
                                    ))}
                                </div>
                            </div>
                        </div>

                        {/* Sidebar */}
                        <div className="space-y-6">
                            {/* Prophet Controls */}
                            {hideout.is_prophet && (
                                <div className="rounded-lg border border-red-800/50 bg-gradient-to-b from-red-950/30 to-stone-900/80 p-4">
                                    <h2 className="mb-3 flex items-center gap-2 font-pixel text-sm text-red-300">
                                        <Crown className="h-4 w-4" />
                                        Prophet Controls
                                    </h2>

                                    {hideout.active_project ? (
                                        <p className="font-pixel text-xs text-orange-400 text-center">
                                            <Hammer className="inline h-3 w-3 mr-1" />
                                            Upgrade in progress...
                                        </p>
                                    ) : hideout.can_upgrade && hideout.upgrade_cost ? (
                                        <div className="mb-3">
                                            <button
                                                onClick={handleStartUpgrade}
                                                disabled={isLoading}
                                                className="w-full rounded bg-red-700 py-2 font-pixel text-xs text-white transition hover:bg-red-600 disabled:opacity-50 border border-red-600"
                                            >
                                                <TrendingUp className="inline h-3 w-3 mr-1" />
                                                Upgrade to {hideout.next_tier_name}
                                            </button>
                                            <div className="mt-2 flex flex-wrap gap-2 justify-center">
                                                <span className="font-pixel text-xs text-amber-400">
                                                    {hideout.upgrade_cost.gold.toLocaleString()}g
                                                </span>
                                                <span className="font-pixel text-xs text-orange-400">
                                                    {hideout.upgrade_cost.devotion.toLocaleString()}{" "}
                                                    devotion
                                                </span>
                                            </div>
                                        </div>
                                    ) : (
                                        <p className="font-pixel text-xs text-stone-400 text-center">
                                            {hideout.tier === hideout.max_tier
                                                ? "Maximum tier reached! Your Dark Citadel stands supreme."
                                                : "Cannot upgrade at this time."}
                                        </p>
                                    )}
                                </div>
                            )}

                            {/* Tier Progression */}
                            <div className="rounded-lg border border-red-800/50 bg-gradient-to-b from-red-950/30 to-stone-900/80 p-4">
                                <h2 className="mb-3 flex items-center gap-2 font-pixel text-sm text-red-300">
                                    <TrendingUp className="h-4 w-4" />
                                    Hideout Progression
                                </h2>
                                <div className="space-y-2">
                                    {hideout.tier_progression?.map((tier) => (
                                        <div
                                            key={tier.tier}
                                            className={`rounded p-2 border ${
                                                tier.is_current
                                                    ? "border-red-600/50 bg-red-900/30"
                                                    : tier.is_unlocked
                                                      ? "border-stone-600/50 bg-stone-800/30"
                                                      : "border-stone-700/30 bg-stone-900/20 opacity-50"
                                            }`}
                                        >
                                            <div className="flex items-center justify-between mb-1">
                                                <span
                                                    className={`font-pixel text-xs ${
                                                        tier.is_current
                                                            ? "text-red-300"
                                                            : tier.is_unlocked
                                                              ? "text-stone-300"
                                                              : "text-stone-500"
                                                    }`}
                                                >
                                                    {tier.name}
                                                </span>
                                                {tier.is_current && (
                                                    <span className="rounded bg-red-700/50 px-1.5 py-0.5 font-pixel text-xs text-red-200">
                                                        Current
                                                    </span>
                                                )}
                                                {tier.is_unlocked && !tier.is_current && (
                                                    <Eye className="h-3 w-3 text-green-400" />
                                                )}
                                            </div>
                                            {tier.unlocked_beliefs.length > 0 && (
                                                <div className="font-pixel text-xs text-stone-500">
                                                    Unlocks: {tier.unlocked_beliefs.join(", ")}
                                                </div>
                                            )}
                                        </div>
                                    ))}
                                </div>
                            </div>

                            {/* Location Status */}
                            <div className="rounded-lg border border-stone-700 bg-stone-800/50 p-4">
                                <h2 className="mb-3 font-pixel text-sm text-stone-300">
                                    Hideout Location
                                </h2>
                                <div className="flex items-center gap-2 mb-2">
                                    <MapPin className="h-4 w-4 text-red-400" />
                                    <span className="font-pixel text-sm text-white">
                                        {hideout.location_name}
                                    </span>
                                </div>
                                <div
                                    className={`rounded p-2 text-center ${
                                        hideout.is_at_hideout
                                            ? "bg-green-900/30 border border-green-700/30"
                                            : "bg-stone-900/30 border border-stone-700/30"
                                    }`}
                                >
                                    <span
                                        className={`font-pixel text-xs ${
                                            hideout.is_at_hideout
                                                ? "text-green-400"
                                                : "text-stone-400"
                                        }`}
                                    >
                                        {hideout.is_at_hideout
                                            ? "You are at the hideout"
                                            : "Travel here to contribute"}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                )}
            </div>

            {/* Build Hideout Confirmation Modal */}
            {showBuildModal && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-4">
                    <div className="relative w-full max-w-md rounded-lg border border-red-800/50 bg-stone-900 p-6">
                        {/* Close Button */}
                        <button
                            onClick={() => setShowBuildModal(false)}
                            className="absolute right-4 top-4 text-stone-400 hover:text-white"
                        >
                            <X className="h-5 w-5" />
                        </button>

                        <div className="mb-4 flex items-center justify-center gap-2">
                            <EyeOff className="h-6 w-6 text-red-500" />
                            <h2 className="font-pixel text-lg text-red-300">
                                Establish Hidden Cellar
                            </h2>
                        </div>

                        <div className="mb-4 rounded border border-red-800/30 bg-red-950/20 p-4">
                            <div className="flex items-center gap-2 justify-center mb-2">
                                <MapPin className="h-5 w-5 text-red-400" />
                                <span className="font-pixel text-lg text-white">
                                    {location.name}
                                </span>
                            </div>
                            <div className="font-pixel text-xs text-stone-400 text-center capitalize">
                                {location.type}
                            </div>
                        </div>

                        <div className="mb-6 rounded border border-stone-600 bg-stone-800 p-4">
                            <div className="flex items-start gap-2 mb-3">
                                <Skull className="h-5 w-5 text-red-500 shrink-0 mt-0.5" />
                                <div className="font-pixel text-sm text-red-300">
                                    Important Information
                                </div>
                            </div>
                            <ul className="space-y-2 font-pixel text-xs text-stone-300">
                                <li className="flex items-start gap-2">
                                    <span className="text-red-400">•</span>
                                    <span>
                                        This will become your cult's{" "}
                                        <strong className="text-white">
                                            permanent hidden sanctuary
                                        </strong>
                                        .
                                    </span>
                                </li>
                                <li className="flex items-start gap-2">
                                    <span className="text-red-400">•</span>
                                    <span>
                                        Members must{" "}
                                        <strong className="text-white">travel here</strong> to
                                        contribute to upgrades.
                                    </span>
                                </li>
                                <li className="flex items-start gap-2">
                                    <span className="text-red-400">•</span>
                                    <span>
                                        Upgrading unlocks{" "}
                                        <strong className="text-red-300">Forbidden Arts</strong> -
                                        dark powers for your followers.
                                    </span>
                                </li>
                                <li className="flex items-start gap-2">
                                    <span className="text-red-400">•</span>
                                    <span>
                                        <strong className="text-red-400">
                                            This cannot be changed later!
                                        </strong>
                                    </span>
                                </li>
                            </ul>
                        </div>

                        <div className="flex gap-3">
                            <button
                                onClick={() => setShowBuildModal(false)}
                                className="flex-1 rounded border border-stone-600 bg-stone-800 py-2 font-pixel text-sm text-stone-300 transition hover:bg-stone-700"
                            >
                                Cancel
                            </button>
                            <button
                                onClick={handleBuildHideout}
                                disabled={isLoading}
                                className="flex-1 rounded bg-red-700 py-2 font-pixel text-sm text-white transition hover:bg-red-600 disabled:opacity-50 border border-red-600"
                            >
                                {isLoading ? "Establishing..." : "Establish Hideout"}
                            </button>
                        </div>
                    </div>
                </div>
            )}
        </AppLayout>
    );
}
