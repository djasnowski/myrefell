import { Head, router, usePage } from "@inertiajs/react";
import {
    AlertTriangle,
    ArrowLeft,
    Building2,
    Church,
    Clock,
    Coins,
    Crown,
    Flame,
    Hammer,
    Heart,
    MapPin,
    Plus,
    Sparkles,
    Star,
    TrendingUp,
    X,
    Zap,
} from "lucide-react";
import { useState, useEffect } from "react";
import AppLayout from "@/layouts/app-layout";
import { locationPath } from "@/lib/utils";
import type { BreadcrumbItem } from "@/types";

interface Religion {
    id: number;
    name: string;
    icon: string;
    color: string;
    type: "cult" | "religion";
}

interface Feature {
    id: number;
    type_id: number;
    name: string;
    slug: string;
    description: string;
    icon: string;
    category: string;
    level: number;
    max_level: number;
    effects: Record<string, number>;
    can_upgrade: boolean;
    upgrade_cost: { gold: number; devotion: number; items: Record<string, number> } | null;
    next_level_effects: Record<string, number> | null;
    prayer_energy_cost: number;
    prayer_devotion_cost: number;
    prayer_duration_minutes: number;
}

interface ActiveBuff {
    id: number;
    feature_id: number;
    feature_name: string;
    feature_icon: string;
    effects: Record<string, number>;
    expires_at: string;
    remaining_time: string;
    remaining_seconds: number;
}

interface AvailableFeature {
    id: number;
    slug: string;
    name: string;
    description: string;
    icon: string;
    category: string;
    min_hq_tier: number;
    max_level: number;
    effects_at_level_1: Record<string, number>;
    build_cost: { gold: number; devotion: number; items: Record<string, number> };
}

interface Project {
    id: number;
    project_type: string;
    project_type_display: string;
    description: string;
    feature_type: { id: number; name: string; icon: string } | null;
    target_level: number;
    status: string;
    progress: number;
    gold_required: number;
    gold_invested: number;
    devotion_required: number;
    devotion_invested: number;
    items_required: Record<string, number> | null;
    items_invested: Record<string, number> | null;
    started_at: string | null;
    construction_ends_at: string | null;
    remaining_time: string | null;
    remaining_seconds: number | null;
    total_construction_seconds: number | null;
    is_constructing: boolean;
    is_construction_complete: boolean;
}

interface Headquarters {
    exists: boolean;
    is_member: boolean;
    is_prophet: boolean;
    is_priest: boolean;
    player_devotion: number;
    id?: number;
    tier?: number;
    tier_name?: string;
    name?: string;
    is_built?: boolean;
    is_at_hq?: boolean;
    location_type?: string;
    location_id?: number;
    location_name?: string;
    total_devotion_invested?: number;
    total_gold_invested?: number;
    can_upgrade?: boolean;
    upgrade_cost?: { gold: number; devotion: number; items: Record<string, number> } | null;
    next_tier_name?: string | null;
    tier_bonuses?: { blessing_cost: number; blessing_duration: number; devotion_gain: number };
    active_buffs?: ActiveBuff[];
    features?: Feature[];
    available_features?: AvailableFeature[];
    active_projects?: Project[];
    prophet_prayer_level?: number;
    next_tier_prayer_requirement?: number | null;
    max_tier?: number;
}

interface Treasury {
    balance: number;
    total_collected: number;
    total_distributed: number;
    recent_transactions: {
        id: number;
        type: string;
        amount: number;
        balance_after: number;
        description: string;
        user: string | null;
        created_at: string;
        time_ago: string;
    }[];
}

interface PageProps {
    religion: Religion;
    headquarters: Headquarters;
    treasury: Treasury;
    gold: number;
    energy: number;
    current_location: { type: string | null; id: number | null; name: string | null };
    [key: string]: unknown;
}

const formatEffectKey = (key: string): string => {
    return key
        .split("_")
        .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
        .join(" ");
};

const categoryIcons: Record<string, typeof Flame> = {
    altar: Flame,
    library: Star,
    vault: Coins,
    garden: Heart,
    sanctum: Crown,
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

export default function HeadquartersIndex() {
    const { religion, headquarters, treasury, gold, energy, current_location } =
        usePage<PageProps>().props;

    // Build location-scoped base URL for HQ routes
    // Use HQ location if built, otherwise use player's current location
    const hqBaseUrl =
        headquarters.location_type && headquarters.location_id
            ? `${locationPath(headquarters.location_type, headquarters.location_id)}/religions/${religion.id}/headquarters`
            : current_location.type && current_location.id
              ? `${locationPath(current_location.type, current_location.id)}/religions/${religion.id}/headquarters`
              : `/religions/${religion.id}/headquarters`;

    // Build location-scoped URL for religion show page
    const religionUrl =
        headquarters.location_type && headquarters.location_id
            ? `${locationPath(headquarters.location_type, headquarters.location_id)}/religions/${religion.id}`
            : `/religions/${religion.id}`;

    const [isLoading, setIsLoading] = useState(false);
    const [donationAmount, setDonationAmount] = useState(100);
    const [contributeGold, setContributeGold] = useState(0);
    const [contributeDevotion, setContributeDevotion] = useState(0);
    const [showBuildModal, setShowBuildModal] = useState(false);
    const [showUpgradeFeatureModal, setShowUpgradeFeatureModal] = useState(false);
    const [featureToUpgrade, setFeatureToUpgrade] = useState<Feature | null>(null);
    const [countdowns, setCountdowns] = useState<Record<number, number>>({});
    const [buffCountdowns, setBuffCountdowns] = useState<Record<number, number>>({});

    // Initialize countdowns from projects
    useEffect(() => {
        const constructingProjects = headquarters.active_projects?.filter(
            (p) => p.is_constructing && p.remaining_seconds !== null,
        );

        if (constructingProjects && constructingProjects.length > 0) {
            const initial: Record<number, number> = {};
            constructingProjects.forEach((p) => {
                if (p.remaining_seconds !== null) {
                    initial[p.id] = p.remaining_seconds;
                }
            });
            setCountdowns(initial);
        }
    }, [headquarters.active_projects]);

    // Initialize buff countdowns
    useEffect(() => {
        const buffs = headquarters.active_buffs || [];
        if (buffs.length > 0) {
            const initial: Record<number, number> = {};
            buffs.forEach((b) => {
                initial[b.feature_id] = b.remaining_seconds;
            });
            setBuffCountdowns(initial);
        }
    }, [headquarters.active_buffs]);

    // Countdown timer effect for projects
    useEffect(() => {
        const hasActiveCountdowns = Object.values(countdowns).some((s) => s > 0);
        if (!hasActiveCountdowns) return;

        const interval = setInterval(() => {
            setCountdowns((prev) => {
                const updated: Record<number, number> = {};
                let anyExpired = false;

                for (const [id, seconds] of Object.entries(prev)) {
                    const newSeconds = Math.max(0, seconds - 1);
                    updated[Number(id)] = newSeconds;
                    if (newSeconds === 0 && seconds > 0) {
                        anyExpired = true;
                    }
                }

                // Reload page when a timer expires to get updated project status
                if (anyExpired) {
                    setTimeout(() => router.reload(), 500);
                }

                return updated;
            });
        }, 1000);

        return () => clearInterval(interval);
    }, [countdowns]);

    // Countdown timer effect for buffs
    useEffect(() => {
        const hasActiveBuffs = Object.values(buffCountdowns).some((s) => s > 0);
        if (!hasActiveBuffs) return;

        const interval = setInterval(() => {
            setBuffCountdowns((prev) => {
                const updated: Record<number, number> = {};
                for (const [id, seconds] of Object.entries(prev)) {
                    updated[Number(id)] = Math.max(0, seconds - 1);
                }
                return updated;
            });
        }, 1000);

        return () => clearInterval(interval);
    }, [buffCountdowns]);

    // Check if a feature has an active buff
    const getBuffForFeature = (featureId: number): ActiveBuff | undefined => {
        return headquarters.active_buffs?.find((b) => b.feature_id === featureId);
    };

    const handlePray = (featureId: number) => {
        setIsLoading(true);
        router.post(
            `${hqBaseUrl}/features/${featureId}/pray`,
            {},
            {
                preserveScroll: true,
                onSuccess: () => router.reload(),
                onFinish: () => setIsLoading(false),
            },
        );
    };

    const canBuildHere = current_location.type && current_location.id && current_location.name;

    const breadcrumbs: BreadcrumbItem[] = [
        { title: "Dashboard", href: "/dashboard" },
        { title: religion.name, href: religionUrl },
        { title: "Headquarters", href: hqBaseUrl },
    ];

    const handleDonate = () => {
        setIsLoading(true);
        router.post(
            `${hqBaseUrl}/donate`,
            { amount: donationAmount },
            {
                preserveScroll: true,
                onSuccess: () => router.reload(),
                onFinish: () => setIsLoading(false),
            },
        );
    };

    const handleBuildHq = () => {
        if (!canBuildHere) return;
        setIsLoading(true);
        router.post(
            `${hqBaseUrl}/build`,
            {
                location_type: current_location.type,
                location_id: current_location.id,
            },
            {
                preserveScroll: true,
                onSuccess: () => {
                    setShowBuildModal(false);
                    router.reload();
                },
                onFinish: () => setIsLoading(false),
            },
        );
    };

    const handleStartUpgrade = () => {
        setIsLoading(true);
        router.post(
            `${hqBaseUrl}/upgrade`,
            {},
            {
                preserveScroll: true,
                onSuccess: () => router.reload(),
                onFinish: () => setIsLoading(false),
            },
        );
    };

    const handleBuildFeature = (featureTypeId: number) => {
        setIsLoading(true);
        router.post(
            `${hqBaseUrl}/features`,
            { feature_type_id: featureTypeId },
            {
                preserveScroll: true,
                onSuccess: () => router.reload(),
                onFinish: () => setIsLoading(false),
            },
        );
    };

    const handleUpgradeFeature = (featureId: number) => {
        setIsLoading(true);
        router.post(
            `${hqBaseUrl}/features/${featureId}/upgrade`,
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
            `${hqBaseUrl}/projects/${projectId}/contribute`,
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
            `${hqBaseUrl}/projects/${projectId}/complete`,
            {},
            {
                preserveScroll: true,
                onSuccess: () => router.reload(),
                onFinish: () => setIsLoading(false),
            },
        );
    };

    // Helper to check if a feature has an active project
    const getActiveProjectForFeature = (featureTypeId: number): Project | undefined => {
        return headquarters.active_projects?.find((p) => p.feature_type?.id === featureTypeId);
    };

    // Check if HQ upgrade is in progress
    const hqUpgradeProject = headquarters.active_projects?.find(
        (p) => p.project_type === "hq_upgrade",
    );

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`${religion.name} Headquarters`} />
            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                {/* Back Button */}
                <a
                    href={`/religions/${religion.id}`}
                    className="flex items-center gap-2 font-pixel text-sm text-stone-400 hover:text-white"
                >
                    <ArrowLeft className="h-4 w-4" />
                    Back to {religion.name}
                </a>

                {/* Header */}
                <div className="flex items-start justify-between">
                    <div className="flex items-center gap-3">
                        <Church className="h-8 w-8" style={{ color: religion.color }} />
                        <div>
                            <h1 className="font-pixel text-2xl text-amber-400">
                                {headquarters.name || `${religion.name} Headquarters`}
                            </h1>
                            {headquarters.is_built && (
                                <div className="flex items-center gap-2">
                                    <span className="rounded bg-purple-900/50 px-2 py-0.5 font-pixel text-xs text-purple-300">
                                        {headquarters.tier_name}
                                    </span>
                                    <span className="flex items-center gap-1 font-pixel text-xs text-stone-400">
                                        <MapPin className="h-3 w-3" />
                                        {headquarters.location_name}
                                    </span>
                                </div>
                            )}
                        </div>
                    </div>
                    <div className="flex items-center gap-4">
                        <div className="flex items-center gap-2 font-pixel text-sm">
                            <Coins className="h-4 w-4 text-amber-400" />
                            <span className="text-stone-300">{gold.toLocaleString()}</span>
                        </div>
                        <div className="flex items-center gap-2 font-pixel text-sm">
                            <Zap className="h-4 w-4 text-green-400" />
                            <span className="text-stone-300">{energy}</span>
                        </div>
                        {headquarters.is_member && (
                            <div className="flex items-center gap-2 font-pixel text-sm">
                                <Heart className="h-4 w-4 text-pink-400" />
                                <span className="text-stone-300">
                                    {headquarters.player_devotion.toLocaleString()} devotion
                                </span>
                            </div>
                        )}
                    </div>
                </div>

                {/* Not Built Yet */}
                {!headquarters.is_built && headquarters.is_prophet && (
                    <div className="rounded-lg border border-yellow-500/30 bg-yellow-900/20 p-6 text-center">
                        <Building2 className="mx-auto h-12 w-12 text-yellow-400 mb-4" />
                        <h2 className="font-pixel text-lg text-yellow-300 mb-2">
                            Establish Your Headquarters
                        </h2>
                        <p className="font-pixel text-sm text-stone-400 mb-4">
                            Choose a location to build your Chapel. This will become the permanent
                            home of your religion where members must travel to contribute.
                        </p>

                        {canBuildHere ? (
                            <>
                                <div className="mb-4 rounded border border-stone-600 bg-stone-800 p-3 inline-block">
                                    <div className="flex items-center gap-2 font-pixel text-sm">
                                        <MapPin className="h-4 w-4 text-amber-400" />
                                        <span className="text-stone-400">Your Location:</span>
                                        <span className="text-white">{current_location.name}</span>
                                        <span className="text-stone-500 capitalize">
                                            ({current_location.type})
                                        </span>
                                    </div>
                                </div>
                                <div>
                                    <button
                                        onClick={() => setShowBuildModal(true)}
                                        disabled={isLoading}
                                        className="rounded bg-yellow-600 px-6 py-2 font-pixel text-sm text-white transition hover:bg-yellow-500 disabled:opacity-50"
                                    >
                                        Build Chapel Here
                                    </button>
                                </div>
                            </>
                        ) : (
                            <div className="rounded border border-red-500/30 bg-red-900/20 p-4">
                                <p className="font-pixel text-sm text-red-300">
                                    You must be at a valid location (village, barony, town, or
                                    kingdom) to build your headquarters.
                                </p>
                            </div>
                        )}
                    </div>
                )}

                {!headquarters.is_built && !headquarters.is_prophet && (
                    <div className="rounded-lg border border-stone-700 bg-stone-800/50 p-6 text-center">
                        <Building2 className="mx-auto h-12 w-12 text-stone-500 mb-4" />
                        <p className="font-pixel text-sm text-stone-400">
                            The headquarters has not been built yet. The Prophet must establish it
                            first.
                        </p>
                    </div>
                )}

                {headquarters.is_built && (
                    <div className="grid gap-6 lg:grid-cols-3">
                        {/* Main Content */}
                        <div className="lg:col-span-2 space-y-6">
                            {/* Active Projects */}
                            {headquarters.active_projects &&
                                headquarters.active_projects.length > 0 && (
                                    <div className="rounded-lg border border-blue-500/30 bg-blue-900/20 p-4">
                                        <h2 className="mb-3 flex items-center gap-2 font-pixel text-sm text-blue-300">
                                            <Hammer className="h-4 w-4" />
                                            Active Construction (
                                            {headquarters.active_projects.length})
                                        </h2>
                                        <div className="space-y-4">
                                            {headquarters.active_projects.map((project) => (
                                                <div
                                                    key={project.id}
                                                    className={`rounded-lg border p-3 ${
                                                        project.is_constructing
                                                            ? "border-green-500/30 bg-green-900/20"
                                                            : "border-stone-600 bg-stone-800/50"
                                                    }`}
                                                >
                                                    <div className="mb-3">
                                                        <div className="font-pixel text-sm text-white mb-1">
                                                            {project.description}
                                                        </div>

                                                        {/* Construction Timer */}
                                                        {project.is_constructing && (
                                                            <div className="mb-2 rounded bg-green-900/50 p-2 text-center relative overflow-hidden">
                                                                {/* Animated background timer bar - shrinks from right to left based on total construction time */}
                                                                {!project.is_construction_complete &&
                                                                    countdowns[project.id] !==
                                                                        undefined &&
                                                                    project.total_construction_seconds && (
                                                                        <div
                                                                            className="absolute inset-y-0 left-0 bg-green-600/30 transition-all duration-1000 ease-linear"
                                                                            style={{
                                                                                width: `${Math.max(0, Math.min(100, (countdowns[project.id] / project.total_construction_seconds) * 100))}%`,
                                                                            }}
                                                                        />
                                                                    )}
                                                                <div className="relative z-10">
                                                                    <div className="flex items-center justify-center gap-2 mb-1">
                                                                        <Clock className="h-4 w-4 text-green-400 animate-pulse" />
                                                                        <span className="font-pixel text-sm text-green-300">
                                                                            Under Construction
                                                                        </span>
                                                                    </div>
                                                                    <div className="font-pixel text-lg text-green-400">
                                                                        {countdowns[project.id] !==
                                                                        undefined
                                                                            ? formatCountdown(
                                                                                  countdowns[
                                                                                      project.id
                                                                                  ],
                                                                              )
                                                                            : project.remaining_time ||
                                                                              "Calculating..."}
                                                                    </div>
                                                                    <div className="font-pixel text-xs text-stone-400 mt-1">
                                                                        {project.is_construction_complete
                                                                            ? "Ready for completion!"
                                                                            : "Time remaining"}
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        )}

                                                        {/* Progress bar - only show when not constructing */}
                                                        {!project.is_constructing && (
                                                            <>
                                                                <div className="mb-1 h-3 rounded-full bg-stone-700">
                                                                    <div
                                                                        className="h-full rounded-full bg-blue-500 transition-all"
                                                                        style={{
                                                                            width: `${project.progress}%`,
                                                                        }}
                                                                    />
                                                                </div>
                                                                <div className="font-pixel text-xs text-stone-400">
                                                                    {project.progress}% funded
                                                                </div>
                                                            </>
                                                        )}
                                                    </div>

                                                    {/* Requirements */}
                                                    <div className="grid grid-cols-2 gap-3 mb-3">
                                                        <div className="rounded bg-stone-900/50 p-2">
                                                            <div className="font-pixel text-xs text-stone-400">
                                                                Gold
                                                            </div>
                                                            <div
                                                                className={`font-pixel text-xs ${
                                                                    project.gold_invested >=
                                                                    project.gold_required
                                                                        ? "text-green-400"
                                                                        : "text-amber-400"
                                                                }`}
                                                            >
                                                                {project.gold_invested.toLocaleString()}{" "}
                                                                /{" "}
                                                                {project.gold_required.toLocaleString()}
                                                                {project.gold_invested >=
                                                                    project.gold_required && " ✓"}
                                                            </div>
                                                        </div>
                                                        <div className="rounded bg-stone-900/50 p-2">
                                                            <div className="font-pixel text-xs text-stone-400">
                                                                Devotion
                                                            </div>
                                                            <div
                                                                className={`font-pixel text-xs ${
                                                                    project.devotion_invested >=
                                                                    project.devotion_required
                                                                        ? "text-green-400"
                                                                        : "text-pink-400"
                                                                }`}
                                                            >
                                                                {project.devotion_invested.toLocaleString()}{" "}
                                                                /{" "}
                                                                {project.devotion_required.toLocaleString()}
                                                                {project.devotion_invested >=
                                                                    project.devotion_required &&
                                                                    " ✓"}
                                                            </div>
                                                        </div>
                                                    </div>

                                                    {/* Contribute Form - only show when not constructing */}
                                                    {headquarters.is_member &&
                                                        !project.is_constructing && (
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
                                                                                            e.target
                                                                                                .value,
                                                                                        ) || 0,
                                                                                    ),
                                                                                )
                                                                            }
                                                                            min={0}
                                                                            max={gold}
                                                                            className="w-full rounded border border-stone-600 bg-stone-800 px-3 py-2 font-pixel text-xs text-white"
                                                                        />
                                                                    </div>
                                                                    <div className="flex-1 min-w-[100px]">
                                                                        <label className="block font-pixel text-xs text-pink-400 mb-1">
                                                                            Devotion
                                                                        </label>
                                                                        <input
                                                                            type="number"
                                                                            placeholder="0"
                                                                            value={
                                                                                contributeDevotion
                                                                            }
                                                                            onChange={(e) =>
                                                                                setContributeDevotion(
                                                                                    Math.max(
                                                                                        0,
                                                                                        parseInt(
                                                                                            e.target
                                                                                                .value,
                                                                                        ) || 0,
                                                                                    ),
                                                                                )
                                                                            }
                                                                            min={0}
                                                                            max={
                                                                                headquarters.player_devotion
                                                                            }
                                                                            className="w-full rounded border border-stone-600 bg-stone-800 px-3 py-2 font-pixel text-xs text-white"
                                                                        />
                                                                    </div>
                                                                    <button
                                                                        onClick={() =>
                                                                            handleContribute(
                                                                                project.id,
                                                                            )
                                                                        }
                                                                        disabled={
                                                                            isLoading ||
                                                                            (contributeGold === 0 &&
                                                                                contributeDevotion ===
                                                                                    0)
                                                                        }
                                                                        className="rounded bg-blue-600 px-4 py-2 font-pixel text-xs text-white transition hover:bg-blue-500 disabled:opacity-50"
                                                                    >
                                                                        Contribute
                                                                    </button>
                                                                </div>
                                                            </div>
                                                        )}

                                                    {/* Under construction message or Complete button */}
                                                    {project.is_constructing && (
                                                        <div className="border-t border-green-500/30 pt-3 text-center">
                                                            {project.is_construction_complete ? (
                                                                headquarters.is_prophet ? (
                                                                    <button
                                                                        onClick={() =>
                                                                            handleCompleteProject(
                                                                                project.id,
                                                                            )
                                                                        }
                                                                        disabled={isLoading}
                                                                        className="rounded bg-green-600 px-4 py-2 font-pixel text-sm text-white transition hover:bg-green-500 disabled:opacity-50"
                                                                    >
                                                                        Complete Construction
                                                                    </button>
                                                                ) : (
                                                                    <span className="font-pixel text-xs text-green-300">
                                                                        Construction complete!
                                                                        Awaiting Prophet to
                                                                        finalize.
                                                                    </span>
                                                                )
                                                            ) : (
                                                                <span className="font-pixel text-xs text-green-300">
                                                                    Fully funded! Construction in
                                                                    progress...
                                                                </span>
                                                            )}
                                                        </div>
                                                    )}
                                                </div>
                                            ))}
                                        </div>
                                    </div>
                                )}

                            {/* Prayer Stations (Built Features) */}
                            {headquarters.features && headquarters.features.length > 0 && (
                                <div className="rounded-lg border border-stone-700 bg-stone-800/50 p-4">
                                    <h2 className="mb-3 font-pixel text-sm text-amber-300">
                                        Prayer Stations
                                    </h2>
                                    <div className="grid gap-3">
                                        {headquarters.features.map((feature) => {
                                            const IconComponent =
                                                categoryIcons[feature.category] || Star;
                                            const activeBuff = getBuffForFeature(feature.id);
                                            const canPray =
                                                headquarters.is_at_hq && headquarters.is_member;
                                            const hasEnoughEnergy =
                                                energy >= feature.prayer_energy_cost;
                                            const hasEnoughDevotion =
                                                headquarters.player_devotion >=
                                                feature.prayer_devotion_cost;

                                            return (
                                                <div
                                                    key={feature.id}
                                                    className={`rounded-lg border p-3 ${
                                                        activeBuff
                                                            ? "border-green-500/30 bg-green-900/20"
                                                            : "border-stone-600 bg-stone-800"
                                                    }`}
                                                >
                                                    <div className="flex items-start justify-between">
                                                        <div className="flex items-center gap-2">
                                                            <IconComponent className="h-5 w-5 text-purple-400" />
                                                            <div>
                                                                <div className="font-pixel text-sm text-white">
                                                                    {feature.name}
                                                                </div>
                                                                <div className="font-pixel text-xs text-stone-400">
                                                                    Level {feature.level}/
                                                                    {feature.max_level}
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div className="flex items-center gap-2">
                                                            {/* Prophet can upgrade */}
                                                            {feature.can_upgrade &&
                                                                headquarters.is_prophet &&
                                                                !getActiveProjectForFeature(
                                                                    feature.type_id,
                                                                ) && (
                                                                    <button
                                                                        onClick={() => {
                                                                            setFeatureToUpgrade(
                                                                                feature,
                                                                            );
                                                                            setShowUpgradeFeatureModal(
                                                                                true,
                                                                            );
                                                                        }}
                                                                        disabled={isLoading}
                                                                        className="rounded bg-purple-600/50 px-2 py-1 font-pixel text-xs text-purple-200 transition hover:bg-purple-600 disabled:opacity-50"
                                                                    >
                                                                        <TrendingUp className="inline h-3 w-3 mr-1" />
                                                                        Upgrade
                                                                    </button>
                                                                )}
                                                            {getActiveProjectForFeature(
                                                                feature.type_id,
                                                            ) && (
                                                                <span className="font-pixel text-xs text-blue-400">
                                                                    <Hammer className="inline h-3 w-3 mr-1" />
                                                                    Upgrading...
                                                                </span>
                                                            )}
                                                        </div>
                                                    </div>

                                                    {/* Effects */}
                                                    <div className="mt-2 font-pixel text-xs text-purple-300">
                                                        {Object.entries(feature.effects).map(
                                                            ([key, value]) => (
                                                                <span key={key} className="mr-3">
                                                                    {formatEffectKey(key)}: +{value}
                                                                    %
                                                                </span>
                                                            ),
                                                        )}
                                                    </div>

                                                    {/* Prayer Info & Button */}
                                                    <div className="mt-3 border-t border-stone-700 pt-3">
                                                        {activeBuff ? (
                                                            <div className="flex items-center justify-between">
                                                                <span className="font-pixel text-xs text-green-400">
                                                                    Buff Active
                                                                </span>
                                                                <span className="font-pixel text-xs text-stone-400 flex items-center gap-1">
                                                                    <Clock className="h-3 w-3" />
                                                                    {buffCountdowns[feature.id] !==
                                                                    undefined
                                                                        ? formatCountdown(
                                                                              buffCountdowns[
                                                                                  feature.id
                                                                              ],
                                                                          )
                                                                        : activeBuff.remaining_time}
                                                                </span>
                                                            </div>
                                                        ) : (
                                                            <div className="flex items-center justify-between">
                                                                <div className="font-pixel text-xs text-stone-400">
                                                                    <span className="mr-2">
                                                                        <Zap className="inline h-3 w-3 text-green-400" />{" "}
                                                                        {feature.prayer_energy_cost}
                                                                    </span>
                                                                    <span className="mr-2">
                                                                        <Heart className="inline h-3 w-3 text-pink-400" />{" "}
                                                                        {
                                                                            feature.prayer_devotion_cost
                                                                        }
                                                                    </span>
                                                                    <span>
                                                                        <Clock className="inline h-3 w-3" />{" "}
                                                                        {
                                                                            feature.prayer_duration_minutes
                                                                        }
                                                                        m
                                                                    </span>
                                                                </div>
                                                                {canPray ? (
                                                                    <button
                                                                        onClick={() =>
                                                                            handlePray(feature.id)
                                                                        }
                                                                        disabled={
                                                                            isLoading ||
                                                                            !hasEnoughEnergy ||
                                                                            !hasEnoughDevotion
                                                                        }
                                                                        className="rounded bg-amber-600 px-3 py-1 font-pixel text-xs text-white transition hover:bg-amber-500 disabled:opacity-50"
                                                                    >
                                                                        Pray
                                                                    </button>
                                                                ) : (
                                                                    <span className="font-pixel text-xs text-stone-500">
                                                                        Travel to HQ to pray
                                                                    </span>
                                                                )}
                                                            </div>
                                                        )}
                                                    </div>
                                                </div>
                                            );
                                        })}
                                    </div>
                                </div>
                            )}

                            {/* Empty State for Members */}
                            {!headquarters.is_prophet &&
                                (!headquarters.features || headquarters.features.length === 0) &&
                                (!headquarters.active_projects ||
                                    headquarters.active_projects.length === 0) && (
                                    <div className="rounded-lg border border-stone-700 bg-stone-800/50 p-6 text-center">
                                        <Church className="mx-auto h-12 w-12 text-stone-500 mb-4" />
                                        <h2 className="font-pixel text-lg text-stone-300 mb-2">
                                            Headquarters Under Development
                                        </h2>
                                        <p className="font-pixel text-sm text-stone-400">
                                            The Prophet has not yet built any prayer stations. Check
                                            back later or donate to the treasury to help fund
                                            construction projects.
                                        </p>
                                    </div>
                                )}

                            {/* Available Features */}
                            {headquarters.is_prophet &&
                                headquarters.available_features &&
                                headquarters.available_features.length > 0 && (
                                    <div className="rounded-lg border border-stone-700 bg-stone-800/50 p-4">
                                        <h2 className="mb-3 font-pixel text-sm text-amber-300">
                                            Available to Build
                                        </h2>
                                        <div className="grid gap-3">
                                            {headquarters.available_features.map((feature) => {
                                                const IconComponent =
                                                    categoryIcons[feature.category] || Star;
                                                const isBeingBuilt = getActiveProjectForFeature(
                                                    feature.id,
                                                );
                                                return (
                                                    <div
                                                        key={feature.id}
                                                        className="rounded-lg border border-stone-600 bg-stone-800 p-3"
                                                    >
                                                        <div className="flex items-start justify-between">
                                                            <div className="flex items-center gap-2">
                                                                <IconComponent className="h-5 w-5 text-stone-400" />
                                                                <div>
                                                                    <div className="font-pixel text-sm text-white">
                                                                        {feature.name}
                                                                    </div>
                                                                    <div className="font-pixel text-xs text-stone-500">
                                                                        {feature.description}
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            {isBeingBuilt ? (
                                                                <span className="font-pixel text-xs text-blue-400">
                                                                    <Hammer className="inline h-3 w-3 mr-1" />
                                                                    Building...
                                                                </span>
                                                            ) : (
                                                                <button
                                                                    onClick={() =>
                                                                        handleBuildFeature(
                                                                            feature.id,
                                                                        )
                                                                    }
                                                                    disabled={isLoading}
                                                                    className="rounded bg-amber-600/50 px-3 py-1 font-pixel text-xs text-amber-200 transition hover:bg-amber-600 disabled:opacity-50"
                                                                >
                                                                    <Plus className="inline h-3 w-3 mr-1" />
                                                                    Build
                                                                </button>
                                                            )}
                                                        </div>
                                                        <div className="mt-2 flex flex-wrap gap-2">
                                                            <span className="rounded bg-stone-700 px-2 py-0.5 font-pixel text-xs text-amber-400">
                                                                {feature.build_cost.gold.toLocaleString()}
                                                                g
                                                            </span>
                                                            <span className="rounded bg-stone-700 px-2 py-0.5 font-pixel text-xs text-pink-400">
                                                                {feature.build_cost.devotion.toLocaleString()}{" "}
                                                                devotion
                                                            </span>
                                                        </div>
                                                    </div>
                                                );
                                            })}
                                        </div>
                                    </div>
                                )}
                        </div>

                        {/* Sidebar */}
                        <div className="space-y-6">
                            {/* Treasury */}
                            <div className="rounded-lg border border-stone-700 bg-stone-800/50 p-4">
                                <h2 className="mb-3 flex items-center gap-2 font-pixel text-sm text-amber-300">
                                    <Coins className="h-4 w-4" />
                                    Treasury
                                </h2>
                                <div className="mb-4 text-center">
                                    <div className="font-pixel text-3xl text-amber-400">
                                        {treasury.balance.toLocaleString()}
                                    </div>
                                    <div className="font-pixel text-xs text-stone-400">gold</div>
                                </div>

                                {/* Donate */}
                                {headquarters.is_member && (
                                    <div className="border-t border-stone-700 pt-4">
                                        <div className="flex items-center gap-2">
                                            <input
                                                type="number"
                                                value={donationAmount}
                                                onChange={(e) =>
                                                    setDonationAmount(
                                                        Math.max(1, parseInt(e.target.value) || 1),
                                                    )
                                                }
                                                min={1}
                                                className="w-24 rounded border border-stone-600 bg-stone-800 px-2 py-2 font-pixel text-xs text-white"
                                            />
                                            <button
                                                onClick={handleDonate}
                                                disabled={isLoading || gold < donationAmount}
                                                className="flex flex-1 items-center justify-center gap-2 rounded bg-amber-600/50 py-2 font-pixel text-xs text-amber-200 transition hover:bg-amber-600 disabled:opacity-50"
                                            >
                                                <Coins className="h-4 w-4" />
                                                Donate
                                            </button>
                                        </div>
                                    </div>
                                )}

                                {/* Recent Transactions */}
                                {treasury.recent_transactions.length > 0 && (
                                    <div className="mt-4 border-t border-stone-700 pt-4">
                                        <div className="font-pixel text-xs text-stone-400 mb-2">
                                            Recent Activity
                                        </div>
                                        <div className="space-y-2 max-h-48 overflow-y-auto">
                                            {treasury.recent_transactions.slice(0, 5).map((tx) => (
                                                <div
                                                    key={tx.id}
                                                    className="flex justify-between text-xs"
                                                >
                                                    <span className="font-pixel text-stone-400 truncate max-w-[120px]">
                                                        {tx.description}
                                                    </span>
                                                    <span
                                                        className={`font-pixel ${
                                                            tx.amount > 0
                                                                ? "text-green-400"
                                                                : "text-red-400"
                                                        }`}
                                                    >
                                                        {tx.amount > 0 ? "+" : ""}
                                                        {tx.amount.toLocaleString()}
                                                    </span>
                                                </div>
                                            ))}
                                        </div>
                                    </div>
                                )}
                            </div>

                            {/* Prophet Controls */}
                            {headquarters.is_prophet && (
                                <div className="rounded-lg border border-yellow-500/30 bg-yellow-900/20 p-4">
                                    <h2 className="mb-3 flex items-center gap-2 font-pixel text-sm text-yellow-300">
                                        <Crown className="h-4 w-4" />
                                        Prophet Controls
                                    </h2>

                                    {hqUpgradeProject ? (
                                        <p className="font-pixel text-xs text-blue-400 text-center">
                                            <Hammer className="inline h-3 w-3 mr-1" />
                                            HQ Upgrade in progress...
                                        </p>
                                    ) : headquarters.can_upgrade && headquarters.upgrade_cost ? (
                                        <div className="mb-3">
                                            <button
                                                onClick={handleStartUpgrade}
                                                disabled={
                                                    isLoading ||
                                                    (headquarters.next_tier_prayer_requirement &&
                                                        headquarters.prophet_prayer_level &&
                                                        headquarters.prophet_prayer_level <
                                                            headquarters.next_tier_prayer_requirement)
                                                }
                                                className="w-full rounded bg-yellow-600 py-2 font-pixel text-xs text-white transition hover:bg-yellow-500 disabled:opacity-50"
                                            >
                                                Upgrade to {headquarters.next_tier_name}
                                            </button>
                                            <div className="mt-2 flex flex-wrap gap-2 justify-center">
                                                <span className="font-pixel text-xs text-amber-400">
                                                    {headquarters.upgrade_cost.gold.toLocaleString()}
                                                    g
                                                </span>
                                                <span className="font-pixel text-xs text-pink-400">
                                                    {headquarters.upgrade_cost.devotion.toLocaleString()}{" "}
                                                    devotion
                                                </span>
                                            </div>
                                            {headquarters.next_tier_prayer_requirement && (
                                                <div className="mt-2 text-center">
                                                    <span
                                                        className={`font-pixel text-xs ${
                                                            headquarters.prophet_prayer_level &&
                                                            headquarters.prophet_prayer_level >=
                                                                headquarters.next_tier_prayer_requirement
                                                                ? "text-green-400"
                                                                : "text-red-400"
                                                        }`}
                                                    >
                                                        Requires Prayer Level{" "}
                                                        {headquarters.next_tier_prayer_requirement}
                                                        {headquarters.prophet_prayer_level && (
                                                            <span className="text-stone-400">
                                                                {" "}
                                                                (You have{" "}
                                                                {headquarters.prophet_prayer_level})
                                                            </span>
                                                        )}
                                                    </span>
                                                </div>
                                            )}
                                        </div>
                                    ) : (
                                        <p className="font-pixel text-xs text-stone-400 text-center">
                                            {headquarters.tier === headquarters.max_tier
                                                ? "Maximum tier reached!"
                                                : "Cannot upgrade at this time."}
                                        </p>
                                    )}
                                </div>
                            )}

                            {/* Stats */}
                            <div className="rounded-lg border border-stone-700 bg-stone-800/50 p-4">
                                <h2 className="mb-3 font-pixel text-sm text-amber-300">
                                    Statistics
                                </h2>
                                <div className="space-y-2">
                                    <div className="flex justify-between font-pixel text-xs">
                                        <span className="text-stone-400">Total Gold Invested</span>
                                        <span className="text-amber-400">
                                            {(
                                                headquarters.total_gold_invested || 0
                                            ).toLocaleString()}
                                        </span>
                                    </div>
                                    <div className="flex justify-between font-pixel text-xs">
                                        <span className="text-stone-400">
                                            Total Devotion Invested
                                        </span>
                                        <span className="text-pink-400">
                                            {(
                                                headquarters.total_devotion_invested || 0
                                            ).toLocaleString()}
                                        </span>
                                    </div>
                                    <div className="flex justify-between font-pixel text-xs">
                                        <span className="text-stone-400">Treasury Collected</span>
                                        <span className="text-green-400">
                                            {treasury.total_collected.toLocaleString()}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            {/* Active Buffs */}
                            {headquarters.active_buffs && headquarters.active_buffs.length > 0 && (
                                <div className="rounded-lg border border-green-500/30 bg-green-900/20 p-4">
                                    <h2 className="mb-3 flex items-center gap-2 font-pixel text-sm text-green-300">
                                        <Sparkles className="h-4 w-4" />
                                        Active Prayer Buffs
                                    </h2>
                                    <div className="space-y-2">
                                        {headquarters.active_buffs.map((buff) => (
                                            <div
                                                key={buff.id}
                                                className="rounded bg-stone-800/50 p-2 flex items-center justify-between"
                                            >
                                                <div>
                                                    <div className="font-pixel text-xs text-white">
                                                        {buff.feature_name}
                                                    </div>
                                                    <div className="font-pixel text-xs text-green-400">
                                                        {Object.entries(buff.effects).map(
                                                            ([key, value]) => (
                                                                <span key={key} className="mr-2">
                                                                    {formatEffectKey(key)}: +{value}
                                                                    %
                                                                </span>
                                                            ),
                                                        )}
                                                    </div>
                                                </div>
                                                <div className="font-pixel text-xs text-stone-400 flex items-center gap-1">
                                                    <Clock className="h-3 w-3" />
                                                    {buffCountdowns[buff.feature_id] !== undefined
                                                        ? formatCountdown(
                                                              buffCountdowns[buff.feature_id],
                                                          )
                                                        : buff.remaining_time}
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                </div>
                            )}
                        </div>
                    </div>
                )}
            </div>

            {/* Build Headquarters Confirmation Modal */}
            {showBuildModal && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-4">
                    <div className="relative w-full max-w-md rounded-lg border border-yellow-500/50 bg-stone-900 p-6">
                        {/* Close Button */}
                        <button
                            onClick={() => setShowBuildModal(false)}
                            className="absolute right-4 top-4 text-stone-400 hover:text-white"
                        >
                            <X className="h-5 w-5" />
                        </button>

                        <div className="mb-4 flex items-center justify-center gap-2">
                            <Building2 className="h-6 w-6 text-yellow-400" />
                            <h2 className="font-pixel text-lg text-yellow-300">Confirm Location</h2>
                        </div>

                        <div className="mb-4 rounded border border-amber-500/30 bg-amber-900/20 p-4">
                            <div className="flex items-center gap-2 justify-center mb-2">
                                <MapPin className="h-5 w-5 text-amber-400" />
                                <span className="font-pixel text-lg text-white">
                                    {current_location.name}
                                </span>
                            </div>
                            <div className="font-pixel text-xs text-stone-400 text-center capitalize">
                                {current_location.type}
                            </div>
                        </div>

                        <div className="mb-6 rounded border border-stone-600 bg-stone-800 p-4">
                            <div className="flex items-start gap-2 mb-3">
                                <AlertTriangle className="h-5 w-5 text-yellow-400 shrink-0 mt-0.5" />
                                <div className="font-pixel text-sm text-yellow-300">
                                    Important Information
                                </div>
                            </div>
                            <ul className="space-y-2 font-pixel text-xs text-stone-300">
                                <li className="flex items-start gap-2">
                                    <span className="text-amber-400">•</span>
                                    <span>
                                        This location will become your religion's{" "}
                                        <strong className="text-white">
                                            permanent headquarters
                                        </strong>
                                        .
                                    </span>
                                </li>
                                <li className="flex items-start gap-2">
                                    <span className="text-amber-400">•</span>
                                    <span>
                                        Members must{" "}
                                        <strong className="text-white">travel here</strong> to
                                        contribute gold, devotion, and items to construction
                                        projects.
                                    </span>
                                </li>
                                <li className="flex items-start gap-2">
                                    <span className="text-amber-400">•</span>
                                    <span>
                                        Choose a central, accessible location for your followers.
                                    </span>
                                </li>
                                <li className="flex items-start gap-2">
                                    <span className="text-amber-400">•</span>
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
                                onClick={handleBuildHq}
                                disabled={isLoading}
                                className="flex-1 rounded bg-yellow-600 py-2 font-pixel text-sm text-white transition hover:bg-yellow-500 disabled:opacity-50"
                            >
                                {isLoading ? "Building..." : "Build Chapel Here"}
                            </button>
                        </div>
                    </div>
                </div>
            )}

            {/* Upgrade Feature Confirmation Modal */}
            {showUpgradeFeatureModal && featureToUpgrade && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-4">
                    <div className="relative w-full max-w-md rounded-lg border border-purple-500/50 bg-stone-900 p-6">
                        {/* Close Button */}
                        <button
                            onClick={() => {
                                setShowUpgradeFeatureModal(false);
                                setFeatureToUpgrade(null);
                            }}
                            className="absolute right-4 top-4 text-stone-400 hover:text-white"
                        >
                            <X className="h-5 w-5" />
                        </button>

                        <div className="mb-4 flex items-center justify-center gap-2">
                            <TrendingUp className="h-6 w-6 text-purple-400" />
                            <h2 className="font-pixel text-lg text-purple-300">
                                Upgrade {featureToUpgrade.name}
                            </h2>
                        </div>

                        <div className="mb-4 text-center font-pixel text-sm text-stone-300">
                            Level {featureToUpgrade.level} → Level {featureToUpgrade.level + 1}
                        </div>

                        {/* Current vs Next Level Effects */}
                        <div className="mb-4 space-y-3">
                            <div className="rounded border border-stone-600 bg-stone-800 p-3">
                                <div className="font-pixel text-xs text-stone-400 mb-2">
                                    Current Effects
                                </div>
                                <div className="font-pixel text-xs text-purple-300">
                                    {Object.entries(featureToUpgrade.effects).map(
                                        ([key, value]) => (
                                            <span key={key} className="mr-3">
                                                {formatEffectKey(key)}: +{value}%
                                            </span>
                                        ),
                                    )}
                                </div>
                            </div>
                            {featureToUpgrade.next_level_effects && (
                                <div className="rounded border border-green-500/30 bg-green-900/20 p-3">
                                    <div className="font-pixel text-xs text-green-400 mb-2">
                                        After Upgrade
                                    </div>
                                    <div className="font-pixel text-xs text-green-300">
                                        {Object.entries(featureToUpgrade.next_level_effects).map(
                                            ([key, value]) => (
                                                <span key={key} className="mr-3">
                                                    {formatEffectKey(key)}: +{value}%
                                                </span>
                                            ),
                                        )}
                                    </div>
                                </div>
                            )}
                        </div>

                        {/* Upgrade Cost */}
                        {featureToUpgrade.upgrade_cost && (
                            <div className="mb-4 rounded border border-amber-500/30 bg-amber-900/20 p-3">
                                <div className="font-pixel text-xs text-amber-400 mb-2">
                                    Upgrade Cost
                                </div>
                                <div className="flex items-center gap-4 font-pixel text-sm">
                                    <span className="flex items-center gap-1 text-yellow-400">
                                        <Coins className="h-4 w-4" />
                                        {featureToUpgrade.upgrade_cost.gold.toLocaleString()}
                                    </span>
                                    <span className="flex items-center gap-1 text-pink-400">
                                        <Heart className="h-4 w-4" />
                                        {featureToUpgrade.upgrade_cost.devotion.toLocaleString()}
                                    </span>
                                </div>
                            </div>
                        )}

                        <div className="mb-4 rounded border border-stone-600 bg-stone-800 p-3">
                            <div className="font-pixel text-xs text-stone-400">
                                This will start a construction project. Members can contribute gold
                                and devotion to complete it.
                            </div>
                        </div>

                        <div className="flex gap-3">
                            <button
                                onClick={() => {
                                    setShowUpgradeFeatureModal(false);
                                    setFeatureToUpgrade(null);
                                }}
                                className="flex-1 rounded border border-stone-600 bg-stone-800 py-2 font-pixel text-sm text-stone-300 transition hover:bg-stone-700"
                            >
                                Cancel
                            </button>
                            <button
                                onClick={() => {
                                    handleUpgradeFeature(featureToUpgrade.id);
                                    setShowUpgradeFeatureModal(false);
                                    setFeatureToUpgrade(null);
                                }}
                                disabled={isLoading}
                                className="flex-1 rounded bg-purple-600 py-2 font-pixel text-sm text-white transition hover:bg-purple-500 disabled:opacity-50"
                            >
                                {isLoading ? "Starting..." : "Start Upgrade"}
                            </button>
                        </div>
                    </div>
                </div>
            )}
        </AppLayout>
    );
}
