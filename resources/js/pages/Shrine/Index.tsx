import { Head, router, usePage } from "@inertiajs/react";
import {
    Bell,
    BookOpen,
    Check,
    Church,
    Clock,
    Coins,
    Fish,
    Heart,
    HeartPulse,
    Loader2,
    Lock,
    Pickaxe,
    Shield,
    Sparkles,
    Sword,
    Swords,
    TreeDeciduous,
    User,
    Users,
    Wheat,
    Wind,
    Wrench,
    X,
    Zap,
} from "lucide-react";
import { useState } from "react";
import AppLayout from "@/layouts/app-layout";
import type { BreadcrumbItem } from "@/types";

interface BlessingEffect {
    [key: string]: number;
}

interface BlessingType {
    id: number;
    name: string;
    slug: string;
    icon: string;
    description: string;
    category: string;
    effects: BlessingEffect;
    duration: string;
    duration_minutes: number;
    gold_cost: number;
    energy_cost: number;
    prayer_level_required: number;
}

interface ActiveBlessing {
    id: number;
    name: string;
    slug: string;
    icon: string;
    description: string;
    category: string;
    effects: BlessingEffect;
    expires_at: string;
    time_remaining: string;
    minutes_remaining: number;
    granted_by: string;
    is_active: boolean;
}

interface NearbyPlayer {
    id: number;
    name: string;
}

interface PendingRequest {
    id: number;
    user_id: number;
    username: string;
    blessing_type_id: number;
    blessing_name: string;
    blessing_icon: string;
    message: string | null;
    created_at: string;
}

interface PriestData {
    prayer_level: number;
    prayer_xp: number;
    prayer_xp_to_next: number;
    available_blessings: BlessingType[];
    nearby_players: NearbyPlayer[];
    blessings_given_today: number;
    pending_requests: PendingRequest[];
}

interface RecentBlessing {
    id: number;
    recipient: string;
    granted_by: string;
    blessing_name: string;
    blessing_icon: string;
    time_ago: string;
}

interface PageProps {
    active_blessings: ActiveBlessing[];
    is_priest: boolean;
    priest_data: PriestData | null;
    shrine_blessings: BlessingType[];
    prayer_level: number;
    recent_blessings: RecentBlessing[];
    energy: number;
    gold: number;
    current_user_id: number;
    location: {
        type: string;
        id: number;
        name?: string;
    };
    [key: string]: unknown;
}

const iconMap: Record<string, typeof Sparkles> = {
    sparkles: Sparkles,
    swords: Swords,
    sword: Sword,
    shield: Shield,
    heart: Heart,
    "heart-pulse": HeartPulse,
    wheat: Wheat,
    fish: Fish,
    "tree-deciduous": TreeDeciduous,
    pickaxe: Pickaxe,
    wrench: Wrench,
    coins: Coins,
    zap: Zap,
    wind: Wind,
    "book-open": BookOpen,
    church: Church,
};

const categoryColors: Record<string, string> = {
    combat: "border-red-600/50 bg-red-900/20",
    skill: "border-blue-600/50 bg-blue-900/20",
    general: "border-purple-600/50 bg-purple-900/20",
};

const categoryLabels: Record<string, string> = {
    combat: "Combat",
    skill: "Skill",
    general: "General",
};

function formatEffect(key: string, value: number): string {
    const labels: Record<string, string> = {
        attack_bonus: "Attack",
        defense_bonus: "Defense",
        strength_bonus: "Strength",
        max_hp_bonus: "Max HP",
        hp_regen_bonus: "HP Regen",
        energy_regen_bonus: "Energy Regen",
        gold_find_bonus: "Gold Find",
        rare_drop_bonus: "Rare Drops",
        travel_speed_bonus: "Travel Speed",
        all_xp_bonus: "All XP",
        farming_bonus: "Farming",
        farming_xp_bonus: "Farming XP",
        fishing_xp_bonus: "Fishing XP",
        fishing_yield_bonus: "Fishing Yield",
        woodcutting_xp_bonus: "Woodcutting XP",
        woodcutting_yield_bonus: "Wood Yield",
        mining_xp_bonus: "Mining XP",
        mining_yield_bonus: "Mining Yield",
        smithing_xp_bonus: "Smithing XP",
        crafting_xp_bonus: "Crafting XP",
        action_cooldown_seconds: "Cooldown",
    };

    // Special formatting for cooldown (show as seconds, not percentage)
    if (key === "action_cooldown_seconds") {
        return `${value}s Cooldown`;
    }

    // If no label found, convert snake_case to Title Case
    const label =
        labels[key] ||
        key
            .split("_")
            .map((w) => w.charAt(0).toUpperCase() + w.slice(1))
            .join(" ");
    return `+${value}% ${label}`;
}

function BlessingCard({
    blessing,
    onSelect,
    selected,
    disabled,
}: {
    blessing: BlessingType;
    onSelect: () => void;
    selected: boolean;
    disabled: boolean;
}) {
    const Icon = iconMap[blessing.icon] || Sparkles;

    return (
        <button
            onClick={onSelect}
            disabled={disabled}
            className={`flex items-center gap-2 rounded-lg border p-2 text-left transition sm:gap-3 sm:p-3 ${categoryColors[blessing.category]} ${
                selected ? "ring-2 ring-amber-400" : ""
            } ${disabled ? "cursor-not-allowed opacity-50" : "hover:brightness-110"}`}
        >
            {/* Large Icon - hidden on mobile */}
            <div className="hidden h-12 w-12 flex-shrink-0 items-center justify-center rounded-lg bg-stone-800/50 sm:flex">
                <Icon className="h-7 w-7 text-amber-300" />
            </div>

            {/* Content */}
            <div className="min-w-0 flex-1">
                <div className="flex items-center gap-1.5 font-pixel text-xs text-amber-300 sm:text-sm">
                    <Icon className="h-4 w-4 sm:hidden" />
                    {blessing.name}
                </div>
                <div className="mt-1 flex flex-wrap gap-1">
                    {Object.entries(blessing.effects).map(([key, value]) => (
                        <span
                            key={key}
                            className="rounded bg-stone-800 px-1 py-0.5 font-pixel text-[9px] text-green-400 sm:px-1.5 sm:text-[10px]"
                        >
                            {formatEffect(key, value)}
                        </span>
                    ))}
                </div>
                <div className="mt-1.5 flex items-center gap-2 font-pixel text-[9px] text-stone-400 sm:gap-3 sm:text-[10px]">
                    <span className="flex items-center gap-1">
                        <Clock className="h-2.5 w-2.5 sm:h-3 sm:w-3" />
                        {blessing.duration}
                    </span>
                    <span className="flex items-center gap-1">
                        <Coins className="h-2.5 w-2.5 text-amber-400 sm:h-3 sm:w-3" />
                        {blessing.gold_cost}g
                    </span>
                    <span className="flex items-center gap-1">
                        <Zap className="h-2.5 w-2.5 text-blue-400 sm:h-3 sm:w-3" />
                        {blessing.energy_cost}
                    </span>
                </div>
            </div>
        </button>
    );
}

function ActiveBlessingCard({ blessing }: { blessing: ActiveBlessing }) {
    const Icon = iconMap[blessing.icon] || Sparkles;
    const progress = Math.max(0, Math.min(100, (blessing.minutes_remaining / 60) * 100));
    // Remove "from now" from time remaining
    const timeDisplay = blessing.time_remaining.replace(/ from now$/, "");

    return (
        <div className={`rounded-lg border p-3 sm:p-4 ${categoryColors[blessing.category]}`}>
            <div className="mb-2 flex items-center justify-between gap-2">
                <div className="flex items-center gap-2">
                    <Icon className="h-5 w-5 text-amber-300 sm:h-6 sm:w-6" />
                    <span className="font-pixel text-xs text-amber-300 sm:text-sm">
                        {blessing.name}
                    </span>
                </div>
                <div className="flex shrink-0 items-center gap-1 font-pixel text-[10px] text-stone-400 sm:text-xs">
                    <Clock className="h-3 w-3" />
                    {timeDisplay}
                </div>
            </div>
            <div className="mb-3 flex flex-wrap gap-1">
                {Object.entries(blessing.effects).map(([key, value]) => (
                    <span
                        key={key}
                        className="rounded bg-stone-800 px-1.5 py-0.5 font-pixel text-[10px] text-green-400 sm:px-2 sm:py-1 sm:text-xs"
                    >
                        {formatEffect(key, value)}
                    </span>
                ))}
            </div>
            <div className="h-1.5 overflow-hidden rounded-full bg-stone-700">
                <div
                    className="h-full bg-amber-500 transition-all"
                    style={{ width: `${progress}%` }}
                />
            </div>
            <div className="mt-2 text-[10px] text-stone-500 sm:text-xs">
                From: {blessing.granted_by}
            </div>
        </div>
    );
}

export default function ShrineIndex() {
    const {
        active_blessings,
        is_priest,
        priest_data,
        shrine_blessings,
        prayer_level,
        recent_blessings,
        energy,
        gold,
        current_user_id,
        location,
    } = usePage<PageProps>().props;

    const [selectedBlessing, setSelectedBlessing] = useState<BlessingType | null>(null);
    const [selectedPlayer, setSelectedPlayer] = useState<number | null>(null);
    const [loading, setLoading] = useState(false);
    const [prayLoading, setPrayLoading] = useState(false);
    const [activeCategory, setActiveCategory] = useState<string>("all");

    const breadcrumbs: BreadcrumbItem[] = [
        { title: "Dashboard", href: "/dashboard" },
        ...(location.name
            ? [{ title: location.name, href: `/${location.type}s/${location.id}` }]
            : []),
        { title: "Shrine", href: "#" },
    ];

    const handleBless = () => {
        if (!selectedBlessing || !selectedPlayer) return;

        // If "Yourself" was selected (-1), use the current user's ID
        const targetId = selectedPlayer === -1 ? current_user_id : selectedPlayer;

        setLoading(true);
        router.post(
            "/shrine/bless",
            {
                blessing_type_id: selectedBlessing.id,
                target_user_id: targetId,
            },
            {
                preserveScroll: true,
                onSuccess: () => {
                    router.reload();
                },
                onFinish: () => {
                    setLoading(false);
                    setSelectedBlessing(null);
                    setSelectedPlayer(null);
                },
            },
        );
    };

    const handlePray = (blessingType: BlessingType) => {
        setPrayLoading(true);
        router.post(
            "/shrine/pray",
            { blessing_type_id: blessingType.id },
            {
                preserveScroll: true,
                onSuccess: () => {
                    router.reload();
                },
                onFinish: () => setPrayLoading(false),
            },
        );
    };

    const filteredBlessings =
        priest_data?.available_blessings.filter(
            (b) => activeCategory === "all" || b.category === activeCategory,
        ) || [];

    const groupedBlessings = filteredBlessings.reduce(
        (acc, blessing) => {
            if (!acc[blessing.category]) acc[blessing.category] = [];
            acc[blessing.category].push(blessing);
            return acc;
        },
        {} as Record<string, BlessingType[]>,
    );

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Shrine" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-hidden p-3 sm:p-4">
                {/* Header */}
                <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <div className="flex items-center gap-2 sm:gap-3">
                        <Church className="hidden h-8 w-8 text-amber-400 sm:block" />
                        <div>
                            <h1 className="font-pixel text-base text-amber-400 sm:text-xl">
                                {location.name ? `${location.name} Shrine` : "Shrine"}
                            </h1>
                            <p className="font-pixel text-[10px] text-stone-400 sm:text-xs">
                                {is_priest
                                    ? "Bestow blessings upon the faithful"
                                    : "Pray for divine favor"}
                            </p>
                        </div>
                    </div>
                    <div className="flex items-center gap-3">
                        <div className="flex items-center gap-1 font-pixel text-xs text-amber-400 sm:text-sm">
                            <Coins className="h-3.5 w-3.5 sm:h-4 sm:w-4" />
                            {gold.toLocaleString()}
                        </div>
                        <div className="flex items-center gap-1 font-pixel text-xs text-blue-400 sm:text-sm">
                            <Zap className="h-3.5 w-3.5 sm:h-4 sm:w-4" />
                            {energy}
                        </div>
                    </div>
                </div>

                <div className="flex flex-1 flex-col gap-4 overflow-hidden sm:flex-row">
                    {/* Left: Active Blessings */}
                    <div className="shrink-0 overflow-y-auto rounded-lg border border-stone-700 bg-stone-900/50 p-3 sm:w-96 sm:p-4">
                        <h2 className="mb-3 flex items-center gap-2 font-pixel text-xs text-amber-300 sm:text-sm">
                            <Sparkles className="h-4 w-4" />
                            Active Blessings
                        </h2>

                        {active_blessings.length === 0 ? (
                            <div className="rounded-lg border border-dashed border-stone-700 p-3 text-center sm:p-4">
                                <Sparkles className="mx-auto mb-2 h-6 w-6 text-stone-600 sm:h-8 sm:w-8" />
                                <p className="font-pixel text-[10px] text-stone-500 sm:text-xs">
                                    No active blessings
                                </p>
                                <p className="hidden font-pixel text-[10px] text-stone-600 sm:block">
                                    {is_priest
                                        ? "Bless yourself or others"
                                        : "Pray at the shrine for blessings"}
                                </p>
                            </div>
                        ) : (
                            <div className="space-y-2">
                                {active_blessings.map((blessing) => (
                                    <ActiveBlessingCard key={blessing.id} blessing={blessing} />
                                ))}
                            </div>
                        )}

                        {/* Recent Blessings at this Shrine - hidden on mobile */}
                        {recent_blessings && recent_blessings.length > 0 && (
                            <div className="mt-4 hidden sm:block">
                                <h2 className="mb-3 flex items-center gap-2 font-pixel text-sm text-amber-300">
                                    <Church className="h-4 w-4" />
                                    Recent Blessings
                                </h2>
                                <div className="space-y-1.5">
                                    {recent_blessings.map((blessing) => {
                                        const Icon = iconMap[blessing.blessing_icon] || Sparkles;
                                        return (
                                            <div
                                                key={blessing.id}
                                                className="flex items-center gap-2 rounded border border-stone-700/50 bg-stone-800/30 px-2 py-1.5"
                                            >
                                                <Icon className="h-4 w-4 flex-shrink-0 text-amber-400/70" />
                                                <div className="min-w-0 flex-1">
                                                    <div className="truncate font-pixel text-[10px] text-stone-300">
                                                        <span className="text-amber-300">
                                                            {blessing.granted_by}
                                                        </span>
                                                        {" blessed "}
                                                        <span className="text-green-400">
                                                            {blessing.recipient}
                                                        </span>
                                                    </div>
                                                    <div className="font-pixel text-[9px] text-stone-500">
                                                        {blessing.blessing_name} ·{" "}
                                                        {blessing.time_ago}
                                                    </div>
                                                </div>
                                            </div>
                                        );
                                    })}
                                </div>
                            </div>
                        )}
                    </div>

                    {/* Right: Priest Panel or Self-Prayer */}
                    <div className="-mx-1 flex-1 overflow-y-auto px-1">
                        {is_priest && priest_data ? (
                            // Priest view
                            <div className="space-y-3 sm:space-y-4">
                                {/* Priest Stats */}
                                <div className="rounded-lg border border-purple-600/50 bg-purple-900/20 p-3 sm:p-4">
                                    <div className="flex items-center justify-between">
                                        <div className="flex items-center gap-2">
                                            <Church className="h-4 w-4 text-purple-400 sm:h-5 sm:w-5" />
                                            <span className="font-pixel text-xs text-purple-300 sm:text-sm">
                                                Priest Powers
                                            </span>
                                        </div>
                                        <span className="font-pixel text-[10px] text-stone-400 sm:text-xs">
                                            Today: {priest_data.blessings_given_today}
                                        </span>
                                    </div>
                                    <div className="mt-2 flex items-center gap-4 text-[10px] text-stone-400 sm:text-xs">
                                        <span>
                                            XP: {priest_data.prayer_xp} /{" "}
                                            {priest_data.prayer_xp_to_next}
                                        </span>
                                    </div>
                                </div>

                                {/* Pending Blessing Requests */}
                                {priest_data.pending_requests &&
                                    priest_data.pending_requests.length > 0 && (
                                        <div className="rounded-lg border border-amber-600/50 bg-amber-900/20 p-4">
                                            <div className="mb-3 flex items-center gap-2">
                                                <Bell className="h-5 w-5 text-amber-400" />
                                                <span className="font-pixel text-sm text-amber-300">
                                                    Blessing Requests (
                                                    {priest_data.pending_requests.length})
                                                </span>
                                            </div>
                                            <div className="space-y-2">
                                                {priest_data.pending_requests.map((req) => {
                                                    const Icon =
                                                        iconMap[req.blessing_icon] || Sparkles;
                                                    return (
                                                        <div
                                                            key={req.id}
                                                            className="flex items-center justify-between rounded border border-stone-700 bg-stone-800/50 p-3"
                                                        >
                                                            <div className="flex items-center gap-3">
                                                                <Icon className="h-5 w-5 text-amber-300" />
                                                                <div>
                                                                    <div className="font-pixel text-xs text-stone-200">
                                                                        <span className="text-amber-300">
                                                                            {req.username}
                                                                        </span>{" "}
                                                                        requests{" "}
                                                                        <span className="text-amber-300">
                                                                            {req.blessing_name}
                                                                        </span>
                                                                    </div>
                                                                    {req.message && (
                                                                        <div className="font-pixel text-[10px] text-stone-400 italic">
                                                                            "{req.message}"
                                                                        </div>
                                                                    )}
                                                                    <div className="font-pixel text-[9px] text-stone-500">
                                                                        {req.created_at}
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div className="flex gap-2">
                                                                <button
                                                                    onClick={() => {
                                                                        router.post(
                                                                            `/${location.type}s/${location.id}/shrine/request/${req.id}/approve`,
                                                                            {},
                                                                            {
                                                                                preserveScroll: true,
                                                                            },
                                                                        );
                                                                    }}
                                                                    className="flex items-center gap-1 rounded bg-green-600 px-2 py-1 font-pixel text-[10px] text-white hover:bg-green-500"
                                                                >
                                                                    <Check className="h-3 w-3" />
                                                                    Approve
                                                                </button>
                                                                <button
                                                                    onClick={() => {
                                                                        router.post(
                                                                            `/${location.type}s/${location.id}/shrine/request/${req.id}/deny`,
                                                                            {},
                                                                            {
                                                                                preserveScroll: true,
                                                                            },
                                                                        );
                                                                    }}
                                                                    className="flex items-center gap-1 rounded bg-red-600 px-2 py-1 font-pixel text-[10px] text-white hover:bg-red-500"
                                                                >
                                                                    <X className="h-3 w-3" />
                                                                    Deny
                                                                </button>
                                                            </div>
                                                        </div>
                                                    );
                                                })}
                                            </div>
                                        </div>
                                    )}

                                {/* Category Filter */}
                                <div className="flex flex-wrap gap-1.5 sm:gap-2">
                                    {["all", "combat", "skill", "general"].map((cat) => (
                                        <button
                                            key={cat}
                                            onClick={() => setActiveCategory(cat)}
                                            className={`rounded px-2 py-1 font-pixel text-[10px] transition sm:px-3 sm:text-xs ${
                                                activeCategory === cat
                                                    ? "bg-amber-600 text-white"
                                                    : "bg-stone-800 text-stone-400 hover:bg-stone-700"
                                            }`}
                                        >
                                            {cat === "all" ? "All" : categoryLabels[cat]}
                                        </button>
                                    ))}
                                </div>

                                {/* Blessings Grid */}
                                <div className="space-y-3 sm:space-y-4">
                                    {Object.entries(groupedBlessings).map(
                                        ([category, blessings]) => (
                                            <div key={category}>
                                                <h3 className="mb-2 font-pixel text-[10px] text-stone-400 sm:text-xs">
                                                    {categoryLabels[category]} Blessings
                                                </h3>
                                                <div className="grid grid-cols-1 gap-2 sm:grid-cols-2 lg:grid-cols-3">
                                                    {blessings.map((blessing) => (
                                                        <BlessingCard
                                                            key={blessing.id}
                                                            blessing={blessing}
                                                            selected={
                                                                selectedBlessing?.id === blessing.id
                                                            }
                                                            onSelect={() =>
                                                                setSelectedBlessing(blessing)
                                                            }
                                                            disabled={energy < blessing.energy_cost}
                                                        />
                                                    ))}
                                                </div>
                                            </div>
                                        ),
                                    )}
                                </div>

                                {/* Target Selection */}
                                {selectedBlessing && (
                                    <div className="rounded-lg border border-amber-600/50 bg-amber-900/20 p-3 sm:p-4">
                                        <h3 className="mb-2 font-pixel text-xs text-amber-300 sm:text-sm">
                                            Bestow {selectedBlessing.name} upon:
                                        </h3>
                                        <div className="mb-3 flex flex-wrap gap-1.5 sm:gap-2">
                                            <button
                                                onClick={() => setSelectedPlayer(-1)}
                                                className={`flex items-center gap-1 rounded px-2 py-1 font-pixel text-[10px] transition sm:px-3 sm:py-1.5 sm:text-xs ${
                                                    selectedPlayer === -1
                                                        ? "bg-amber-600 text-white"
                                                        : "bg-stone-800 text-stone-300 hover:bg-stone-700"
                                                }`}
                                            >
                                                <User className="h-3 w-3" />
                                                Yourself
                                            </button>
                                            {priest_data.nearby_players
                                                .filter((player) => player.id !== current_user_id)
                                                .map((player) => (
                                                    <button
                                                        key={player.id}
                                                        onClick={() => setSelectedPlayer(player.id)}
                                                        className={`flex items-center gap-1 rounded px-2 py-1 font-pixel text-[10px] transition sm:px-3 sm:py-1.5 sm:text-xs ${
                                                            selectedPlayer === player.id
                                                                ? "bg-amber-600 text-white"
                                                                : "bg-stone-800 text-stone-300 hover:bg-stone-700"
                                                        }`}
                                                    >
                                                        <Users className="h-3 w-3" />
                                                        {player.name}
                                                    </button>
                                                ))}
                                        </div>
                                        <button
                                            onClick={handleBless}
                                            disabled={!selectedPlayer || loading}
                                            className="flex w-full items-center justify-center gap-2 rounded-lg bg-amber-600 px-3 py-1.5 font-pixel text-xs text-white transition hover:bg-amber-500 disabled:cursor-not-allowed disabled:opacity-50 sm:px-4 sm:py-2 sm:text-sm"
                                        >
                                            {loading ? (
                                                <Loader2 className="h-4 w-4 animate-spin" />
                                            ) : (
                                                <>
                                                    <Sparkles className="h-4 w-4" />
                                                    Bestow Blessing
                                                </>
                                            )}
                                        </button>
                                    </div>
                                )}
                            </div>
                        ) : (
                            // Non-priest view - self prayer
                            <div className="space-y-4">
                                <div className="rounded-lg border border-stone-700 bg-stone-900/50 p-3 sm:p-4">
                                    <h2 className="mb-1 font-pixel text-xs text-amber-300 sm:mb-2 sm:text-sm">
                                        Prayer at the Shrine
                                    </h2>
                                    <p className="font-pixel text-[10px] text-stone-400 sm:text-xs">
                                        Without a Priest, you may pray directly. Costs 50% more gold
                                        and lasts 25% shorter.
                                    </p>
                                </div>

                                <div className="grid grid-cols-1 gap-2 sm:grid-cols-2 sm:gap-3 lg:grid-cols-3">
                                    {shrine_blessings.map((blessing) => {
                                        const isLocked =
                                            prayer_level < blessing.prayer_level_required;
                                        const isActive = active_blessings.some(
                                            (ab) => ab.slug === blessing.slug,
                                        );
                                        const Icon = iconMap[blessing.icon] || Sparkles;
                                        return (
                                            <div
                                                key={blessing.slug}
                                                className={`relative rounded-lg border p-3 sm:p-4 ${isLocked ? "border-stone-700 bg-stone-900/50 opacity-75" : isActive ? "border-green-600/50 bg-green-900/20" : categoryColors[blessing.category]}`}
                                            >
                                                {isLocked && (
                                                    <div className="absolute right-2 top-2">
                                                        <Lock className="h-4 w-4 text-stone-500" />
                                                    </div>
                                                )}
                                                {isActive && (
                                                    <div className="absolute right-2 top-2">
                                                        <Check className="h-4 w-4 text-green-400" />
                                                    </div>
                                                )}
                                                <div className="mb-2 flex items-center gap-2">
                                                    <Icon
                                                        className={`h-5 w-5 sm:h-6 sm:w-6 ${isLocked ? "text-stone-500" : isActive ? "text-green-400" : "text-amber-300"}`}
                                                    />
                                                    <span
                                                        className={`font-pixel text-xs sm:text-sm ${isLocked ? "text-stone-500" : isActive ? "text-green-400" : "text-amber-300"}`}
                                                    >
                                                        {blessing.name}
                                                    </span>
                                                </div>
                                                <p className="mb-2 hidden font-pixel text-xs text-stone-400 sm:block">
                                                    {blessing.description}
                                                </p>
                                                <div className="mb-2 flex flex-wrap gap-1 sm:mb-3 sm:gap-1.5">
                                                    {Object.entries(blessing.effects).map(
                                                        ([key, value]) => (
                                                            <span
                                                                key={key}
                                                                className={`rounded bg-stone-800 px-1.5 py-0.5 font-pixel text-[10px] sm:px-2 sm:py-1 sm:text-xs ${isLocked ? "text-stone-500" : "text-green-400"}`}
                                                            >
                                                                {formatEffect(key, value)}
                                                            </span>
                                                        ),
                                                    )}
                                                </div>
                                                <div className="mb-2 flex items-center justify-between font-pixel text-[10px] text-stone-500 sm:mb-3 sm:text-xs">
                                                    <span className="flex items-center gap-1">
                                                        <Clock className="h-3 w-3 sm:h-4 sm:w-4" />
                                                        {blessing.duration}
                                                    </span>
                                                    <span className="flex items-center gap-1">
                                                        <Coins
                                                            className={`h-3 w-3 sm:h-4 sm:w-4 ${isLocked ? "text-stone-500" : "text-amber-400"}`}
                                                        />
                                                        {blessing.gold_cost}g
                                                    </span>
                                                </div>
                                                {isLocked ? (
                                                    <div className="flex w-full items-center justify-center gap-2 rounded bg-stone-700 px-2 py-1.5 font-pixel text-[10px] text-stone-400 sm:px-3 sm:py-2 sm:text-xs">
                                                        <Lock className="h-3 w-3" />
                                                        Req. Prayer {blessing.prayer_level_required}
                                                    </div>
                                                ) : isActive ? (
                                                    <div className="flex w-full items-center justify-center gap-2 rounded bg-green-700 px-2 py-1.5 font-pixel text-xs text-green-200 sm:px-3 sm:py-2 sm:text-sm">
                                                        <Check className="h-4 w-4" />
                                                        Active
                                                    </div>
                                                ) : (
                                                    <button
                                                        onClick={() => handlePray(blessing)}
                                                        disabled={
                                                            gold < blessing.gold_cost || prayLoading
                                                        }
                                                        className="flex w-full items-center justify-center gap-2 rounded bg-amber-600 px-2 py-1.5 font-pixel text-xs text-white hover:bg-amber-500 disabled:cursor-not-allowed disabled:opacity-50 sm:px-3 sm:py-2 sm:text-sm"
                                                    >
                                                        {prayLoading ? (
                                                            <Loader2 className="h-4 w-4 animate-spin" />
                                                        ) : (
                                                            "Pray"
                                                        )}
                                                    </button>
                                                )}
                                            </div>
                                        );
                                    })}
                                </div>

                                <div className="rounded-lg border border-dashed border-stone-600 p-3 text-center sm:p-4">
                                    <Church className="mx-auto mb-2 h-6 w-6 text-stone-600 sm:h-8 sm:w-8" />
                                    <p className="font-pixel text-[10px] text-stone-500 sm:text-xs">
                                        A Priest can bless you at full duration and normal cost.
                                    </p>
                                    <p className="mt-1 font-pixel text-[10px] text-stone-600 sm:text-xs">
                                        Self-prayer costs 50% more and lasts 25% shorter.
                                    </p>
                                </div>
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
