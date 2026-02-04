import { Head, Link, router, usePage } from "@inertiajs/react";
import {
    Bell,
    BookOpen,
    Check,
    ChevronDown,
    Church,
    Clock,
    Coins,
    Crown,
    Fish,
    Heart,
    HeartPulse,
    Loader2,
    Lock,
    Mail,
    Pickaxe,
    Plus,
    Shield,
    Skull,
    Sparkles,
    Star,
    Sword,
    Swords,
    TreeDeciduous,
    Unlock,
    User,
    UserPlus,
    Users,
    Wheat,
    Wind,
    Wrench,
    X,
    Zap,
} from "lucide-react";
import { useState } from "react";
import AppLayout from "@/layouts/app-layout";
import { locationPath } from "@/lib/utils";
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

interface LocalReligion {
    id: number;
    name: string;
    description: string;
    icon: string | null;
    color: string | null;
    type: string;
    is_cult: boolean;
    is_public: boolean;
    member_count: number;
    founder: { id: number; username: string } | null;
    beliefs: { id: number; name: string; description: string; icon: string }[];
    hq_tier: number;
    kingdom_status: string | null;
    is_banned: boolean;
    is_member: boolean;
    can_join: boolean;
}

interface ReligionInvite {
    id: number;
    religion: {
        id: number;
        name: string;
        icon: string | null;
        color: string | null;
        type: string;
        is_cult: boolean;
    } | null;
    inviter: { id: number; username: string } | null;
    status: string;
    status_display: string;
    message: string | null;
    expires_at: string;
    expires_in: string;
    can_respond: boolean;
    created_at: string;
}

interface ReligionMembership {
    id: number;
    religion_id: number;
    religion_name: string;
    religion_icon: string | null;
    religion_color: string | null;
    religion_type: string;
    rank: string;
    rank_display: string;
    devotion: number;
    joined_at: string;
    is_prophet: boolean;
    is_priest: boolean;
}

interface Belief {
    id: number;
    name: string;
    description: string;
    icon: string;
    type: string;
    effects: Record<string, number>;
}

interface ActiveBelief {
    id: number;
    belief_id: number;
    belief_name: string;
    belief_icon: string;
    belief_effects: Record<string, number>;
    religion_id: number;
    religion_name: string;
    devotion_spent: number;
    expires_at: string;
    remaining_seconds: number;
}

interface BeliefActivation {
    min_devotion: number;
    max_devotion: number;
    min_duration_minutes: number;
    max_duration_minutes: number;
}

interface CultBelief {
    id: number;
    name: string;
    description: string;
    icon: string;
    type: string;
    effects: Record<string, number>;
    required_hideout_tier: number;
    tier_name: string;
    hp_cost: number;
    energy_cost: number;
    is_unlocked: boolean;
}

interface PageProps {
    active_blessings: ActiveBlessing[];
    active_beliefs: ActiveBelief[];
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
    local_religions: LocalReligion[];
    pending_invites: ReligionInvite[];
    current_membership: ReligionMembership | null;
    beliefs: Belief[];
    belief_activation: BeliefActivation;
    cult_beliefs: CultBelief[];
    player_hp: number;
    player_max_hp: number;
    nearby_players_for_invite: { id: number; username: string; combat_level: number }[];
    pending_outgoing_invites: ReligionInvite[];
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
        active_beliefs,
        is_priest,
        priest_data,
        shrine_blessings,
        prayer_level,
        recent_blessings,
        energy,
        gold,
        current_user_id,
        location,
        local_religions,
        pending_invites,
        current_membership,
        beliefs,
        belief_activation,
        cult_beliefs,
        player_hp,
        player_max_hp,
        nearby_players_for_invite,
        pending_outgoing_invites,
    } = usePage<PageProps>().props;

    const [selectedBlessing, setSelectedBlessing] = useState<BlessingType | null>(null);
    const [selectedPlayer, setSelectedPlayer] = useState<number | null>(null);
    const [loading, setLoading] = useState(false);
    const [prayLoading, setPrayLoading] = useState(false);
    const [activeCategory, setActiveCategory] = useState<string>("all");
    const [activeTab, setActiveTab] = useState<"blessings" | "religions">("blessings");
    const [joinLoading, setJoinLoading] = useState<number | null>(null);
    const [inviteLoading, setInviteLoading] = useState<number | null>(null);
    const [showCreateCult, setShowCreateCult] = useState(false);
    const [cultName, setCultName] = useState("");
    const [cultDescription, setCultDescription] = useState("");
    const [selectedBeliefs, setSelectedBeliefs] = useState<number[]>([]);
    const [createLoading, setCreateLoading] = useState(false);
    const [beliefDevotionAmount, setBeliefDevotionAmount] = useState(
        belief_activation?.min_devotion || 50,
    );
    const [activateBeliefLoading, setActivateBeliefLoading] = useState(false);
    const [selectedCultBelief, setSelectedCultBelief] = useState<number | null>(null);
    const [cultBeliefDevotionAmount, setCultBeliefDevotionAmount] = useState(
        belief_activation?.min_devotion || 50,
    );
    const [activateCultBeliefLoading, setActivateCultBeliefLoading] = useState(false);
    const [showAcceptModal, setShowAcceptModal] = useState<ReligionInvite | null>(null);
    const [showDeclineModal, setShowDeclineModal] = useState<ReligionInvite | null>(null);
    const [selectedInvitePlayer, setSelectedInvitePlayer] = useState<number | null>(null);
    const [inviteMessage, setInviteMessage] = useState("");
    const [sendInviteLoading, setSendInviteLoading] = useState(false);
    const [inviteDropdownOpen, setInviteDropdownOpen] = useState(false);
    const [cancelInviteLoading, setCancelInviteLoading] = useState<number | null>(null);

    const baseLocationUrl = locationPath(location.type, location.id);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: "Dashboard", href: "/dashboard" },
        ...(location.name ? [{ title: location.name, href: baseLocationUrl }] : []),
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

    const handleJoinReligion = (religionId: number) => {
        setJoinLoading(religionId);
        router.post(
            "/religions/join",
            { religion_id: religionId },
            {
                preserveScroll: true,
                onSuccess: () => {
                    router.reload();
                },
                onFinish: () => setJoinLoading(null),
            },
        );
    };

    const handleAcceptInvite = (invite: ReligionInvite) => {
        setShowAcceptModal(invite);
    };

    const handleDeclineInvite = (invite: ReligionInvite) => {
        setShowDeclineModal(invite);
    };

    const confirmAcceptInvite = () => {
        if (!showAcceptModal) return;
        setInviteLoading(showAcceptModal.id);
        router.post(
            "/religions/invite/accept",
            { invite_id: showAcceptModal.id },
            {
                preserveScroll: true,
                onSuccess: () => {
                    router.reload();
                    setShowAcceptModal(null);
                },
                onFinish: () => setInviteLoading(null),
            },
        );
    };

    const confirmDeclineInvite = () => {
        if (!showDeclineModal) return;
        setInviteLoading(showDeclineModal.id);
        router.post(
            "/religions/invite/decline",
            { invite_id: showDeclineModal.id },
            {
                preserveScroll: true,
                onSuccess: () => {
                    router.reload();
                    setShowDeclineModal(null);
                },
                onFinish: () => setInviteLoading(null),
            },
        );
    };

    const handleCreateCult = () => {
        if (!cultName || selectedBeliefs.length === 0) return;
        setCreateLoading(true);
        router.post(
            "/religions/create-cult",
            {
                name: cultName,
                description: cultDescription,
                belief_ids: selectedBeliefs,
            },
            {
                preserveScroll: true,
                onSuccess: () => {
                    router.reload();
                    setShowCreateCult(false);
                    setCultName("");
                    setCultDescription("");
                    setSelectedBeliefs([]);
                },
                onFinish: () => setCreateLoading(false),
            },
        );
    };

    const toggleBelief = (beliefId: number) => {
        if (selectedBeliefs.includes(beliefId)) {
            setSelectedBeliefs(selectedBeliefs.filter((id) => id !== beliefId));
        } else if (selectedBeliefs.length < 2) {
            setSelectedBeliefs([...selectedBeliefs, beliefId]);
        }
    };

    const handleActivateBeliefs = () => {
        if (!current_membership) return;
        setActivateBeliefLoading(true);
        router.post(
            `${baseLocationUrl}/shrine/activate-beliefs`,
            {
                religion_id: current_membership.religion_id,
                devotion: beliefDevotionAmount,
            },
            {
                preserveScroll: true,
                onSuccess: () => {
                    router.reload();
                },
                onFinish: () => setActivateBeliefLoading(false),
            },
        );
    };

    const calculateBeliefDuration = (devotion: number): number => {
        if (!belief_activation) return 5;
        const duration = belief_activation.min_duration_minutes + Math.floor(devotion / 10);
        return Math.min(duration, belief_activation.max_duration_minutes);
    };

    const handleActivateCultBelief = () => {
        if (!current_membership || !selectedCultBelief) return;
        setActivateCultBeliefLoading(true);
        router.post(
            `${baseLocationUrl}/shrine/activate-cult-beliefs`,
            {
                religion_id: current_membership.religion_id,
                belief_id: selectedCultBelief,
                devotion: cultBeliefDevotionAmount,
            },
            {
                preserveScroll: true,
                onSuccess: () => {
                    setSelectedCultBelief(null);
                    router.reload();
                },
                onFinish: () => setActivateCultBeliefLoading(false),
            },
        );
    };

    // Check if a cult belief is already active
    const isCultBeliefActive = (beliefId: number): boolean => {
        return active_beliefs?.some((ab) => ab.belief_id === beliefId) ?? false;
    };

    const handleSendInvite = () => {
        if (!selectedInvitePlayer || !current_membership) return;
        setSendInviteLoading(true);
        router.post(
            "/religions/invite",
            {
                religion_id: current_membership.religion_id,
                user_id: selectedInvitePlayer,
                message: inviteMessage || null,
            },
            {
                preserveScroll: true,
                onSuccess: () => {
                    router.reload();
                    setSelectedInvitePlayer(null);
                    setInviteMessage("");
                },
                onFinish: () => setSendInviteLoading(false),
            },
        );
    };

    const handleCancelInvite = (inviteId: number) => {
        setCancelInviteLoading(inviteId);
        router.post(
            "/religions/invite/cancel",
            { invite_id: inviteId },
            {
                preserveScroll: true,
                onSuccess: () => router.reload(),
                onFinish: () => setCancelInviteLoading(null),
            },
        );
    };

    const canInvite =
        current_membership && (current_membership.is_prophet || current_membership.is_priest);

    // Check if any of the current religion's beliefs are already active
    const hasActiveReligionBeliefs = current_membership
        ? active_beliefs?.some((ab) => ab.religion_id === current_membership.religion_id)
        : false;

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
                                {activeTab === "blessings"
                                    ? is_priest
                                        ? "Bestow blessings upon the faithful"
                                        : "Pray for divine favor"
                                    : "Discover and join religions"}
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

                {/* Tab Navigation */}
                <div className="flex gap-2">
                    <button
                        onClick={() => setActiveTab("blessings")}
                        className={`flex items-center gap-1.5 rounded-lg px-3 py-1.5 font-pixel text-xs transition sm:px-4 sm:py-2 sm:text-sm ${
                            activeTab === "blessings"
                                ? "bg-amber-600 text-white"
                                : "bg-stone-800 text-stone-400 hover:bg-stone-700"
                        }`}
                    >
                        <Sparkles className="h-3.5 w-3.5 sm:h-4 sm:w-4" />
                        Blessings
                    </button>
                    <button
                        onClick={() => setActiveTab("religions")}
                        className={`flex items-center gap-1.5 rounded-lg px-3 py-1.5 font-pixel text-xs transition sm:px-4 sm:py-2 sm:text-sm ${
                            activeTab === "religions"
                                ? "bg-purple-600 text-white"
                                : "bg-stone-800 text-stone-400 hover:bg-stone-700"
                        }`}
                    >
                        <Crown className="h-3.5 w-3.5 sm:h-4 sm:w-4" />
                        Religions
                        {pending_invites.length > 0 && (
                            <span className="ml-1 rounded-full bg-red-500 px-1.5 py-0.5 text-[10px] text-white">
                                {pending_invites.length}
                            </span>
                        )}
                    </button>
                </div>

                {activeTab === "blessings" ? (
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
                                            const Icon =
                                                iconMap[blessing.blessing_icon] || Sparkles;
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
                                            <div className="rounded-lg border border-amber-600/50 bg-amber-900/20 p-3 sm:p-4">
                                                <div className="mb-2 flex items-center gap-2 sm:mb-3">
                                                    <Bell className="h-4 w-4 text-amber-400 sm:h-5 sm:w-5" />
                                                    <span className="font-pixel text-xs text-amber-300 sm:text-sm">
                                                        Requests (
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
                                                                className="flex flex-col gap-2 rounded border border-stone-700 bg-stone-800/50 p-2 sm:flex-row sm:items-center sm:justify-between sm:p-3"
                                                            >
                                                                <div className="flex items-center gap-2 sm:gap-3">
                                                                    <Icon className="h-4 w-4 shrink-0 text-amber-300 sm:h-5 sm:w-5" />
                                                                    <div className="min-w-0 flex-1">
                                                                        <div className="truncate font-pixel text-[10px] text-stone-200 sm:text-xs">
                                                                            <span className="text-amber-300">
                                                                                {req.username}
                                                                            </span>{" "}
                                                                            <span className="hidden sm:inline">
                                                                                requests{" "}
                                                                            </span>
                                                                            <span className="text-amber-300">
                                                                                {req.blessing_name}
                                                                            </span>
                                                                        </div>
                                                                        {req.message && (
                                                                            <div className="truncate font-pixel text-[9px] italic text-stone-400 sm:text-[10px]">
                                                                                "{req.message}"
                                                                            </div>
                                                                        )}
                                                                    </div>
                                                                </div>
                                                                <div className="flex shrink-0 gap-2">
                                                                    <button
                                                                        onClick={() => {
                                                                            router.post(
                                                                                `${baseLocationUrl}/shrine/request/${req.id}/approve`,
                                                                                {},
                                                                                {
                                                                                    preserveScroll: true,
                                                                                },
                                                                            );
                                                                        }}
                                                                        className="flex flex-1 items-center justify-center gap-1 rounded bg-green-600 px-2 py-1 font-pixel text-[10px] text-white hover:bg-green-500 sm:flex-initial"
                                                                    >
                                                                        <Check className="h-3 w-3" />
                                                                        <span className="hidden sm:inline">
                                                                            Approve
                                                                        </span>
                                                                    </button>
                                                                    <button
                                                                        onClick={() => {
                                                                            router.post(
                                                                                `${baseLocationUrl}/shrine/request/${req.id}/deny`,
                                                                                {},
                                                                                {
                                                                                    preserveScroll: true,
                                                                                },
                                                                            );
                                                                        }}
                                                                        className="flex flex-1 items-center justify-center gap-1 rounded bg-red-600 px-2 py-1 font-pixel text-[10px] text-white hover:bg-red-500 sm:flex-initial"
                                                                    >
                                                                        <X className="h-3 w-3" />
                                                                        <span className="hidden sm:inline">
                                                                            Deny
                                                                        </span>
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
                                                                    selectedBlessing?.id ===
                                                                    blessing.id
                                                                }
                                                                onSelect={() =>
                                                                    setSelectedBlessing(blessing)
                                                                }
                                                                disabled={
                                                                    energy < blessing.energy_cost
                                                                }
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
                                                    .filter(
                                                        (player) => player.id !== current_user_id,
                                                    )
                                                    .map((player) => (
                                                        <button
                                                            key={player.id}
                                                            onClick={() =>
                                                                setSelectedPlayer(player.id)
                                                            }
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
                                            Without a Priest, you may pray directly. Costs 50% more
                                            gold and lasts 25% shorter.
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
                                                            Req. Prayer{" "}
                                                            {blessing.prayer_level_required}
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
                                                                gold < blessing.gold_cost ||
                                                                prayLoading
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
                ) : (
                    /* Religions Tab */
                    <div className="flex-1 overflow-y-auto">
                        <div className="space-y-4">
                            {/* Current Membership */}
                            {current_membership && (
                                <div className="rounded-lg border border-purple-600/50 bg-purple-900/20 p-4">
                                    <div className="flex items-center justify-between">
                                        <div className="flex items-center gap-3">
                                            <div className="flex h-12 w-12 items-center justify-center rounded-lg bg-purple-800/50">
                                                <Crown className="h-6 w-6 text-purple-300" />
                                            </div>
                                            <div>
                                                <h3 className="font-pixel text-sm text-purple-300">
                                                    Your{" "}
                                                    {current_membership.religion_type === "cult"
                                                        ? "Cult"
                                                        : "Religion"}
                                                </h3>
                                                <Link
                                                    href={`${baseLocationUrl}/religions/${current_membership.religion_id}`}
                                                    className="font-pixel text-lg text-amber-400 hover:underline"
                                                >
                                                    {current_membership.religion_name}
                                                </Link>
                                            </div>
                                        </div>
                                        <div className="text-right">
                                            <div className="font-pixel text-xs text-stone-400">
                                                Rank
                                            </div>
                                            <div className="font-pixel text-sm text-amber-300">
                                                {current_membership.rank_display}
                                            </div>
                                        </div>
                                    </div>
                                    <div className="mt-3 flex items-center gap-4">
                                        <div className="flex items-center gap-1 font-pixel text-xs text-stone-400">
                                            <Star className="h-4 w-4 text-amber-400" />
                                            Devotion: {current_membership.devotion.toLocaleString()}
                                        </div>
                                        <Link
                                            href={`${baseLocationUrl}/religions/${current_membership.religion_id}`}
                                            className="ml-auto font-pixel text-xs text-purple-400 hover:underline"
                                        >
                                            View Details
                                        </Link>
                                    </div>
                                </div>
                            )}

                            {/* Invite Players - for Prophet/Priest */}
                            {canInvite && (
                                <div className="rounded-lg border border-amber-600/50 bg-amber-900/20 p-4">
                                    <div className="mb-3 flex items-center justify-between">
                                        <h3 className="flex items-center gap-2 font-pixel text-sm text-amber-300">
                                            <UserPlus className="h-5 w-5" />
                                            Extend Invitation
                                        </h3>
                                        <div className="group relative">
                                            <span className="cursor-help font-pixel text-xs text-stone-500">
                                                ?
                                            </span>
                                            <div className="absolute right-0 top-6 z-10 hidden w-48 rounded border border-stone-600 bg-stone-800 p-2 text-xs text-stone-300 shadow-lg group-hover:block">
                                                Those who wander may seek purpose. A gentle
                                                invitation can guide lost souls toward
                                                enlightenment.
                                            </div>
                                        </div>
                                    </div>
                                    {nearby_players_for_invite &&
                                    nearby_players_for_invite.length > 0 ? (
                                        <div className="space-y-3">
                                            <div className="relative">
                                                <label className="block font-pixel text-xs text-stone-400 mb-1">
                                                    Wandering Souls
                                                </label>
                                                <button
                                                    type="button"
                                                    onClick={() =>
                                                        setInviteDropdownOpen(!inviteDropdownOpen)
                                                    }
                                                    className="flex w-full items-center justify-between rounded border border-stone-600 bg-stone-800 px-3 py-2 font-pixel text-xs text-white hover:border-amber-600/50"
                                                >
                                                    <span
                                                        className={
                                                            selectedInvitePlayer
                                                                ? "text-white"
                                                                : "text-stone-500"
                                                        }
                                                    >
                                                        {selectedInvitePlayer
                                                            ? (() => {
                                                                  const player =
                                                                      nearby_players_for_invite.find(
                                                                          (p) =>
                                                                              p.id ===
                                                                              selectedInvitePlayer,
                                                                      );
                                                                  return player ? (
                                                                      <span className="flex items-center gap-2">
                                                                          {player.username}
                                                                          <span className="text-amber-400">
                                                                              Lv{" "}
                                                                              {player.combat_level}
                                                                          </span>
                                                                      </span>
                                                                  ) : (
                                                                      "Select a player..."
                                                                  );
                                                              })()
                                                            : "Select a player..."}
                                                    </span>
                                                    <ChevronDown
                                                        className={`h-4 w-4 text-stone-400 transition-transform ${inviteDropdownOpen ? "rotate-180" : ""}`}
                                                    />
                                                </button>
                                                {inviteDropdownOpen && (
                                                    <>
                                                        <div
                                                            className="fixed inset-0 z-10"
                                                            onClick={() =>
                                                                setInviteDropdownOpen(false)
                                                            }
                                                        />
                                                        <div className="absolute left-0 right-0 top-full z-20 mt-1 max-h-48 overflow-y-auto rounded border border-stone-600 bg-stone-900 shadow-lg">
                                                            {nearby_players_for_invite.map(
                                                                (player) => (
                                                                    <button
                                                                        key={player.id}
                                                                        type="button"
                                                                        onClick={() => {
                                                                            setSelectedInvitePlayer(
                                                                                player.id,
                                                                            );
                                                                            setInviteDropdownOpen(
                                                                                false,
                                                                            );
                                                                        }}
                                                                        className={`flex w-full items-center justify-between px-3 py-2 text-left font-pixel text-xs transition hover:bg-stone-800 ${
                                                                            selectedInvitePlayer ===
                                                                            player.id
                                                                                ? "bg-amber-900/30 text-amber-200"
                                                                                : "text-stone-200"
                                                                        }`}
                                                                    >
                                                                        <span>
                                                                            {player.username}
                                                                        </span>
                                                                        <span className="text-amber-400">
                                                                            Lv {player.combat_level}
                                                                        </span>
                                                                    </button>
                                                                ),
                                                            )}
                                                        </div>
                                                    </>
                                                )}
                                            </div>
                                            <div>
                                                <label className="block font-pixel text-xs text-stone-400 mb-1">
                                                    Message (optional)
                                                </label>
                                                <input
                                                    type="text"
                                                    value={inviteMessage}
                                                    onChange={(e) =>
                                                        setInviteMessage(e.target.value)
                                                    }
                                                    placeholder="A word of welcome..."
                                                    maxLength={200}
                                                    className="w-full rounded border border-stone-600 bg-stone-800 px-3 py-2 font-pixel text-xs text-white placeholder:text-stone-500"
                                                />
                                            </div>
                                            <button
                                                onClick={handleSendInvite}
                                                disabled={
                                                    !selectedInvitePlayer || sendInviteLoading
                                                }
                                                className="w-full rounded bg-amber-600 px-4 py-2 font-pixel text-xs text-white hover:bg-amber-500 disabled:opacity-50"
                                            >
                                                {sendInviteLoading ? (
                                                    <Loader2 className="mx-auto h-4 w-4 animate-spin" />
                                                ) : (
                                                    "Send Invitation"
                                                )}
                                            </button>
                                        </div>
                                    ) : (
                                        <div className="font-pixel text-xs text-stone-400 text-center">
                                            No wandering souls nearby to invite.
                                        </div>
                                    )}

                                    {/* Pending Outgoing Invites */}
                                    {pending_outgoing_invites &&
                                        pending_outgoing_invites.length > 0 && (
                                            <div className="mt-4 border-t border-stone-700 pt-4">
                                                <h4 className="mb-2 flex items-center gap-2 font-pixel text-xs text-stone-400">
                                                    <Mail className="h-4 w-4" />
                                                    Pending Invites (
                                                    {pending_outgoing_invites.length})
                                                </h4>
                                                <div className="space-y-2">
                                                    {pending_outgoing_invites.map((invite) => (
                                                        <div
                                                            key={invite.id}
                                                            className="flex items-center justify-between rounded border border-stone-700 bg-stone-800/50 px-3 py-2"
                                                        >
                                                            <div className="flex items-center gap-2">
                                                                <User className="h-3 w-3 text-stone-500" />
                                                                <span className="font-pixel text-xs text-stone-200">
                                                                    {invite.invitee?.username}
                                                                </span>
                                                                <span className="font-pixel text-[10px] text-stone-500">
                                                                    {invite.expires_in}
                                                                </span>
                                                            </div>
                                                            <button
                                                                onClick={() =>
                                                                    handleCancelInvite(invite.id)
                                                                }
                                                                disabled={
                                                                    cancelInviteLoading ===
                                                                    invite.id
                                                                }
                                                                className="rounded bg-red-900/50 px-2 py-1 font-pixel text-[10px] text-red-300 hover:bg-red-900 disabled:opacity-50"
                                                            >
                                                                {cancelInviteLoading ===
                                                                invite.id ? (
                                                                    <Loader2 className="h-3 w-3 animate-spin" />
                                                                ) : (
                                                                    "Cancel"
                                                                )}
                                                            </button>
                                                        </div>
                                                    ))}
                                                </div>
                                            </div>
                                        )}
                                </div>
                            )}

                            {/* Active Beliefs */}
                            {active_beliefs && active_beliefs.length > 0 && (
                                <div className="rounded-lg border border-green-600/50 bg-green-900/20 p-4">
                                    <h3 className="mb-3 flex items-center gap-2 font-pixel text-sm text-green-300">
                                        <Sparkles className="h-5 w-5" />
                                        Active Beliefs
                                    </h3>
                                    <div className="space-y-2">
                                        {active_beliefs.map((ab) => (
                                            <div
                                                key={ab.id}
                                                className="rounded border border-stone-700 bg-stone-800/50 p-3"
                                            >
                                                <div className="flex items-center justify-between">
                                                    <div className="flex items-center gap-2">
                                                        <BookOpen className="h-4 w-4 text-green-400" />
                                                        <span className="font-pixel text-xs text-white">
                                                            {ab.belief_name}
                                                        </span>
                                                    </div>
                                                    <div className="flex items-center gap-1 font-pixel text-xs text-stone-400">
                                                        <Clock className="h-3 w-3" />
                                                        {Math.ceil(ab.remaining_seconds / 60)}m
                                                    </div>
                                                </div>
                                                <div className="mt-1 font-pixel text-[10px] text-green-400">
                                                    {Object.entries(ab.belief_effects || {}).map(
                                                        ([key, val]) => (
                                                            <span key={key} className="mr-2">
                                                                {formatEffect(key, val as number)}
                                                            </span>
                                                        ),
                                                    )}
                                                </div>
                                                <div className="mt-1 font-pixel text-[10px] text-stone-500">
                                                    From {ab.religion_name}
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                </div>
                            )}

                            {/* Belief Activation */}
                            {current_membership && belief_activation && (
                                <div className="rounded-lg border border-blue-600/50 bg-blue-900/20 p-4">
                                    <h3 className="mb-3 flex items-center gap-2 font-pixel text-sm text-blue-300">
                                        <BookOpen className="h-5 w-5" />
                                        Activate Beliefs
                                    </h3>
                                    {hasActiveReligionBeliefs ? (
                                        <div className="font-pixel text-xs text-stone-400 text-center">
                                            Your religion's beliefs are already active.
                                        </div>
                                    ) : current_membership.devotion <
                                      belief_activation.min_devotion ? (
                                        <div className="font-pixel text-xs text-stone-400 text-center">
                                            You need at least {belief_activation.min_devotion}{" "}
                                            devotion to activate beliefs.
                                        </div>
                                    ) : (
                                        <div className="space-y-3">
                                            <div className="font-pixel text-xs text-stone-300">
                                                Spend devotion to temporarily activate your
                                                religion's beliefs.
                                            </div>
                                            <div>
                                                <label className="block font-pixel text-xs text-stone-400 mb-1">
                                                    Devotion to spend (
                                                    {belief_activation.min_devotion} -{" "}
                                                    {Math.min(
                                                        belief_activation.max_devotion,
                                                        current_membership.devotion,
                                                    )}
                                                    )
                                                </label>
                                                <input
                                                    type="range"
                                                    min={belief_activation.min_devotion}
                                                    max={Math.min(
                                                        belief_activation.max_devotion,
                                                        current_membership.devotion,
                                                    )}
                                                    value={beliefDevotionAmount}
                                                    onChange={(e) =>
                                                        setBeliefDevotionAmount(
                                                            Number(e.target.value),
                                                        )
                                                    }
                                                    className="w-full"
                                                />
                                                <div className="flex justify-between font-pixel text-xs text-stone-400 mt-1">
                                                    <span>{beliefDevotionAmount} devotion</span>
                                                    <span>
                                                        {calculateBeliefDuration(
                                                            beliefDevotionAmount,
                                                        )}{" "}
                                                        minutes
                                                    </span>
                                                </div>
                                            </div>
                                            <button
                                                onClick={handleActivateBeliefs}
                                                disabled={activateBeliefLoading}
                                                className="w-full rounded bg-blue-600 px-4 py-2 font-pixel text-xs text-white hover:bg-blue-500 disabled:opacity-50"
                                            >
                                                {activateBeliefLoading ? (
                                                    <Loader2 className="mx-auto h-4 w-4 animate-spin" />
                                                ) : (
                                                    `Activate for ${calculateBeliefDuration(beliefDevotionAmount)} minutes`
                                                )}
                                            </button>
                                        </div>
                                    )}
                                </div>
                            )}

                            {/* Forbidden Arts - Cult Beliefs */}
                            {current_membership?.is_cult &&
                                cult_beliefs &&
                                cult_beliefs.length > 0 && (
                                    <div className="rounded-lg border border-red-800/50 bg-gradient-to-b from-red-950/30 to-stone-900/80 p-4">
                                        <h3 className="mb-3 flex items-center gap-2 font-pixel text-sm text-red-300">
                                            <Skull className="h-5 w-5" />
                                            Forbidden Arts
                                        </h3>
                                        <p className="mb-3 font-pixel text-xs text-stone-400">
                                            Dark powers that require a blood sacrifice to activate.
                                        </p>
                                        <div className="space-y-2 mb-4 max-h-60 overflow-y-auto">
                                            {cult_beliefs.map((belief) => {
                                                const isActive = isCultBeliefActive(belief.id);
                                                const isSelected = selectedCultBelief === belief.id;
                                                const canAffordHp = player_hp >= belief.hp_cost + 1;
                                                const canAffordEnergy =
                                                    energy >= belief.energy_cost;
                                                const canAffordDevotion =
                                                    current_membership &&
                                                    current_membership.devotion >=
                                                        belief_activation?.min_devotion;
                                                const canActivate =
                                                    belief.is_unlocked &&
                                                    !isActive &&
                                                    canAffordHp &&
                                                    canAffordEnergy &&
                                                    canAffordDevotion;

                                                return (
                                                    <div
                                                        key={belief.id}
                                                        onClick={() =>
                                                            canActivate &&
                                                            setSelectedCultBelief(
                                                                isSelected ? null : belief.id,
                                                            )
                                                        }
                                                        className={`rounded border p-2 transition-all ${
                                                            isActive
                                                                ? "border-green-600/50 bg-green-900/30 cursor-default"
                                                                : !belief.is_unlocked
                                                                  ? "border-stone-700/50 bg-stone-900/30 opacity-50 cursor-not-allowed"
                                                                  : !canActivate
                                                                    ? "border-stone-600/50 bg-stone-800/30 opacity-60 cursor-not-allowed"
                                                                    : isSelected
                                                                      ? "border-red-500 bg-red-900/40 cursor-pointer"
                                                                      : "border-red-800/50 bg-red-950/30 cursor-pointer hover:border-red-600/50"
                                                        }`}
                                                    >
                                                        <div className="flex items-start justify-between">
                                                            <div className="flex items-center gap-2">
                                                                {belief.is_unlocked ? (
                                                                    isActive ? (
                                                                        <Sparkles className="h-4 w-4 text-green-400" />
                                                                    ) : (
                                                                        <Unlock className="h-4 w-4 text-red-400" />
                                                                    )
                                                                ) : (
                                                                    <Lock className="h-4 w-4 text-stone-500" />
                                                                )}
                                                                <span
                                                                    className={`font-pixel text-xs ${
                                                                        isActive
                                                                            ? "text-green-300"
                                                                            : belief.is_unlocked
                                                                              ? "text-white"
                                                                              : "text-stone-500"
                                                                    }`}
                                                                >
                                                                    {belief.name}
                                                                </span>
                                                            </div>
                                                            {!belief.is_unlocked && (
                                                                <span className="font-pixel text-[10px] text-stone-500">
                                                                    Req: {belief.tier_name}
                                                                </span>
                                                            )}
                                                            {isActive && (
                                                                <span className="font-pixel text-[10px] text-green-400">
                                                                    Active
                                                                </span>
                                                            )}
                                                        </div>
                                                        {belief.is_unlocked && (
                                                            <>
                                                                <div className="mt-1 font-pixel text-[10px] text-stone-400">
                                                                    {belief.description}
                                                                </div>
                                                                <div className="mt-1 font-pixel text-[10px] text-red-300/80">
                                                                    {Object.entries(
                                                                        belief.effects,
                                                                    ).map(([key, val]) => (
                                                                        <span
                                                                            key={key}
                                                                            className="mr-2"
                                                                        >
                                                                            {key
                                                                                .split("_")
                                                                                .map(
                                                                                    (w) =>
                                                                                        w
                                                                                            .charAt(
                                                                                                0,
                                                                                            )
                                                                                            .toUpperCase() +
                                                                                        w.slice(1),
                                                                                )
                                                                                .join(" ")}
                                                                            :{" "}
                                                                            {(val as number) > 0
                                                                                ? "+"
                                                                                : ""}
                                                                            {val as number}%
                                                                        </span>
                                                                    ))}
                                                                </div>
                                                            </>
                                                        )}
                                                        {belief.is_unlocked && !isActive && (
                                                            <div className="mt-1 flex items-center gap-2 font-pixel text-[10px] text-stone-500">
                                                                <span
                                                                    className={`flex items-center gap-0.5 ${!canAffordHp ? "text-red-500" : ""}`}
                                                                >
                                                                    <Heart className="h-3 w-3" />{" "}
                                                                    {belief.hp_cost}
                                                                </span>
                                                                <span
                                                                    className={`flex items-center gap-0.5 ${!canAffordEnergy ? "text-red-500" : ""}`}
                                                                >
                                                                    <Zap className="h-3 w-3" />{" "}
                                                                    {belief.energy_cost}
                                                                </span>
                                                                <span>+ Devotion</span>
                                                            </div>
                                                        )}
                                                    </div>
                                                );
                                            })}
                                        </div>

                                        {/* Activation controls when a belief is selected */}
                                        {selectedCultBelief &&
                                            belief_activation &&
                                            current_membership && (
                                                <div className="border-t border-red-800/50 pt-3 space-y-3">
                                                    <div>
                                                        <label className="block font-pixel text-xs text-stone-400 mb-1">
                                                            Devotion (
                                                            {belief_activation.min_devotion} -{" "}
                                                            {Math.min(
                                                                belief_activation.max_devotion,
                                                                current_membership.devotion,
                                                            )}
                                                            )
                                                        </label>
                                                        <input
                                                            type="range"
                                                            min={belief_activation.min_devotion}
                                                            max={Math.min(
                                                                belief_activation.max_devotion,
                                                                current_membership.devotion,
                                                            )}
                                                            value={cultBeliefDevotionAmount}
                                                            onChange={(e) =>
                                                                setCultBeliefDevotionAmount(
                                                                    Number(e.target.value),
                                                                )
                                                            }
                                                            className="w-full accent-red-500"
                                                        />
                                                        <div className="flex justify-between font-pixel text-xs text-stone-400 mt-1">
                                                            <span>
                                                                {cultBeliefDevotionAmount} devotion
                                                            </span>
                                                            <span>
                                                                {calculateBeliefDuration(
                                                                    cultBeliefDevotionAmount,
                                                                )}{" "}
                                                                minutes
                                                            </span>
                                                        </div>
                                                    </div>
                                                    <div className="flex items-center justify-between text-xs font-pixel">
                                                        <div className="flex items-center gap-3 text-stone-400">
                                                            <span className="flex items-center gap-1 text-red-400">
                                                                <Heart className="h-3 w-3" />-
                                                                {cult_beliefs.find(
                                                                    (b) =>
                                                                        b.id === selectedCultBelief,
                                                                )?.hp_cost || 0}{" "}
                                                                HP
                                                            </span>
                                                            <span className="flex items-center gap-1 text-green-400">
                                                                <Zap className="h-3 w-3" />-
                                                                {cult_beliefs.find(
                                                                    (b) =>
                                                                        b.id === selectedCultBelief,
                                                                )?.energy_cost || 0}
                                                            </span>
                                                        </div>
                                                        <span className="text-stone-500">
                                                            HP: {player_hp}/{player_max_hp}
                                                        </span>
                                                    </div>
                                                    <button
                                                        onClick={handleActivateCultBelief}
                                                        disabled={activateCultBeliefLoading}
                                                        className="w-full rounded bg-red-700 border border-red-600 px-4 py-2 font-pixel text-xs text-white hover:bg-red-600 disabled:opacity-50"
                                                    >
                                                        {activateCultBeliefLoading ? (
                                                            <Loader2 className="mx-auto h-4 w-4 animate-spin" />
                                                        ) : (
                                                            "Perform Blood Sacrifice"
                                                        )}
                                                    </button>
                                                </div>
                                            )}
                                    </div>
                                )}

                            {/* Pending Invites */}
                            {pending_invites.length > 0 && (
                                <div className="rounded-lg border border-amber-600/50 bg-amber-900/20 p-4">
                                    <h3 className="mb-3 flex items-center gap-2 font-pixel text-sm text-amber-300">
                                        <Mail className="h-5 w-5" />
                                        Pending Invites ({pending_invites.length})
                                    </h3>
                                    <div className="space-y-2">
                                        {pending_invites.map((invite) => (
                                            <div
                                                key={invite.id}
                                                className="flex flex-col gap-2 rounded border border-stone-700 bg-stone-800/50 p-3 sm:flex-row sm:items-center sm:justify-between"
                                            >
                                                <div>
                                                    <div className="font-pixel text-xs text-stone-200">
                                                        <span className="text-amber-300">
                                                            {invite.inviter?.username}
                                                        </span>{" "}
                                                        invited you to join{" "}
                                                        <span className="text-purple-300">
                                                            {invite.religion?.name}
                                                        </span>
                                                    </div>
                                                    {invite.message && (
                                                        <div className="mt-1 font-pixel text-[10px] italic text-stone-400">
                                                            "{invite.message}"
                                                        </div>
                                                    )}
                                                    <div className="mt-1 font-pixel text-[10px] text-stone-500">
                                                        Expires {invite.expires_in}
                                                    </div>
                                                </div>
                                                <div className="flex gap-2">
                                                    <button
                                                        onClick={() => handleAcceptInvite(invite)}
                                                        disabled={inviteLoading === invite.id}
                                                        className="flex flex-1 items-center justify-center gap-1 rounded bg-green-600 px-3 py-1.5 font-pixel text-xs text-white hover:bg-green-500 disabled:opacity-50 sm:flex-initial"
                                                    >
                                                        {inviteLoading === invite.id ? (
                                                            <Loader2 className="h-3 w-3 animate-spin" />
                                                        ) : (
                                                            <>
                                                                <Check className="h-3 w-3" />
                                                                Accept
                                                            </>
                                                        )}
                                                    </button>
                                                    <button
                                                        onClick={() => handleDeclineInvite(invite)}
                                                        disabled={inviteLoading === invite.id}
                                                        className="flex flex-1 items-center justify-center gap-1 rounded bg-red-600 px-3 py-1.5 font-pixel text-xs text-white hover:bg-red-500 disabled:opacity-50 sm:flex-initial"
                                                    >
                                                        <X className="h-3 w-3" />
                                                        Decline
                                                    </button>
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                </div>
                            )}

                            {/* Local Religions */}
                            {local_religions.length > 0 && (
                                <div>
                                    <h3 className="mb-3 flex items-center gap-2 font-pixel text-sm text-amber-300">
                                        <Church className="h-5 w-5" />
                                        Religions at This Location
                                    </h3>
                                    <div className="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                                        {local_religions.map((religion) => (
                                            <div
                                                key={religion.id}
                                                className={`rounded-lg border p-4 ${
                                                    religion.is_member
                                                        ? "border-purple-600/50 bg-purple-900/20"
                                                        : religion.is_banned
                                                          ? "border-red-600/50 bg-red-900/20"
                                                          : "border-stone-700 bg-stone-900/50"
                                                }`}
                                            >
                                                <div className="mb-2 flex items-center justify-between">
                                                    <div className="flex items-center gap-2">
                                                        <Crown
                                                            className={`h-5 w-5 ${religion.is_member ? "text-purple-400" : "text-amber-400"}`}
                                                        />
                                                        <span className="font-pixel text-sm text-amber-300">
                                                            {religion.name}
                                                        </span>
                                                    </div>
                                                    {religion.is_cult && (
                                                        <span className="rounded bg-stone-700 px-1.5 py-0.5 font-pixel text-[10px] text-stone-400">
                                                            Cult
                                                        </span>
                                                    )}
                                                </div>

                                                {religion.description && (
                                                    <p className="mb-2 font-pixel text-[10px] text-stone-400 line-clamp-2">
                                                        {religion.description}
                                                    </p>
                                                )}

                                                <div className="mb-2 flex flex-wrap gap-1">
                                                    {religion.beliefs.slice(0, 3).map((belief) => (
                                                        <span
                                                            key={belief.id}
                                                            className="rounded bg-stone-800 px-1.5 py-0.5 font-pixel text-[10px] text-purple-300"
                                                            title={belief.description}
                                                        >
                                                            {belief.name}
                                                        </span>
                                                    ))}
                                                </div>

                                                <div className="mb-3 flex items-center gap-3 font-pixel text-[10px] text-stone-500">
                                                    <span className="flex items-center gap-1">
                                                        <Users className="h-3 w-3" />
                                                        {religion.member_count} members
                                                    </span>
                                                    <span className="flex items-center gap-1">
                                                        <Star className="h-3 w-3" />
                                                        Tier {religion.hq_tier}
                                                    </span>
                                                </div>

                                                {religion.is_member ? (
                                                    <Link
                                                        href={`${baseLocationUrl}/religions/${religion.id}`}
                                                        className="flex w-full items-center justify-center gap-2 rounded bg-purple-600 px-3 py-1.5 font-pixel text-xs text-white hover:bg-purple-500"
                                                    >
                                                        View Details
                                                    </Link>
                                                ) : religion.is_banned ? (
                                                    <div className="flex w-full items-center justify-center gap-2 rounded bg-red-900/50 px-3 py-1.5 font-pixel text-xs text-red-300">
                                                        <X className="h-3 w-3" />
                                                        Banned in Kingdom
                                                    </div>
                                                ) : current_membership ? (
                                                    <div className="flex w-full items-center justify-center gap-2 rounded bg-stone-700 px-3 py-1.5 font-pixel text-xs text-stone-400">
                                                        Already in a religion
                                                    </div>
                                                ) : religion.can_join ? (
                                                    <button
                                                        onClick={() =>
                                                            handleJoinReligion(religion.id)
                                                        }
                                                        disabled={joinLoading === religion.id}
                                                        className="flex w-full items-center justify-center gap-2 rounded bg-amber-600 px-3 py-1.5 font-pixel text-xs text-white hover:bg-amber-500 disabled:opacity-50"
                                                    >
                                                        {joinLoading === religion.id ? (
                                                            <Loader2 className="h-3 w-3 animate-spin" />
                                                        ) : (
                                                            <>
                                                                <UserPlus className="h-3 w-3" />
                                                                Join
                                                            </>
                                                        )}
                                                    </button>
                                                ) : (
                                                    <div className="flex w-full items-center justify-center gap-2 rounded bg-stone-700 px-3 py-1.5 font-pixel text-xs text-stone-400">
                                                        <Lock className="h-3 w-3" />
                                                        {religion.is_public
                                                            ? "Cannot Join"
                                                            : "Private"}
                                                    </div>
                                                )}
                                            </div>
                                        ))}
                                    </div>
                                </div>
                            )}

                            {/* No local religions and no membership */}
                            {local_religions.length === 0 && !current_membership && (
                                <div className="rounded-lg border border-dashed border-stone-600 p-6 text-center">
                                    <Church className="mx-auto mb-3 h-10 w-10 text-stone-600" />
                                    <p className="font-pixel text-sm text-stone-400">
                                        No religions have built their headquarters here.
                                    </p>
                                    <p className="mt-2 font-pixel text-xs text-stone-500">
                                        Travel to other locations or start your own cult!
                                    </p>
                                </div>
                            )}

                            {/* Create Cult Button */}
                            {!current_membership && (
                                <div className="mt-4">
                                    {!showCreateCult ? (
                                        <button
                                            onClick={() => setShowCreateCult(true)}
                                            className="flex w-full items-center justify-center gap-2 rounded-lg border border-dashed border-purple-600/50 bg-purple-900/10 px-4 py-3 font-pixel text-sm text-purple-300 transition hover:bg-purple-900/20"
                                        >
                                            <Plus className="h-5 w-5" />
                                            Found a Cult
                                        </button>
                                    ) : (
                                        <div className="rounded-lg border border-purple-600/50 bg-purple-900/20 p-4">
                                            <h3 className="mb-3 font-pixel text-sm text-purple-300">
                                                Found a New Cult
                                            </h3>
                                            <div className="space-y-3">
                                                <div>
                                                    <label className="mb-1 block font-pixel text-xs text-stone-400">
                                                        Cult Name
                                                    </label>
                                                    <input
                                                        type="text"
                                                        value={cultName}
                                                        onChange={(e) =>
                                                            setCultName(e.target.value)
                                                        }
                                                        className="w-full rounded border border-stone-600 bg-stone-800 px-3 py-2 font-pixel text-sm text-stone-200 focus:border-purple-500 focus:outline-none"
                                                        placeholder="Enter cult name..."
                                                        maxLength={50}
                                                    />
                                                </div>
                                                <div>
                                                    <label className="mb-1 block font-pixel text-xs text-stone-400">
                                                        Description (optional)
                                                    </label>
                                                    <textarea
                                                        value={cultDescription}
                                                        onChange={(e) =>
                                                            setCultDescription(e.target.value)
                                                        }
                                                        className="w-full rounded border border-stone-600 bg-stone-800 px-3 py-2 font-pixel text-sm text-stone-200 focus:border-purple-500 focus:outline-none"
                                                        placeholder="Describe your cult..."
                                                        rows={2}
                                                        maxLength={500}
                                                    />
                                                </div>
                                                <div>
                                                    <label className="mb-2 block font-pixel text-xs text-stone-400">
                                                        Select Beliefs (1-2)
                                                    </label>
                                                    <div className="grid max-h-48 grid-cols-2 gap-2 overflow-y-auto sm:grid-cols-3">
                                                        {beliefs.map((belief) => (
                                                            <button
                                                                key={belief.id}
                                                                onClick={() =>
                                                                    toggleBelief(belief.id)
                                                                }
                                                                className={`rounded border p-2 text-left transition ${
                                                                    selectedBeliefs.includes(
                                                                        belief.id,
                                                                    )
                                                                        ? "border-purple-500 bg-purple-900/30"
                                                                        : "border-stone-700 bg-stone-800/50 hover:bg-stone-800"
                                                                }`}
                                                            >
                                                                <div className="font-pixel text-[10px] text-amber-300">
                                                                    {belief.name}
                                                                </div>
                                                                <div className="mt-1 font-pixel text-[9px] text-stone-400 line-clamp-2">
                                                                    {belief.description}
                                                                </div>
                                                            </button>
                                                        ))}
                                                    </div>
                                                </div>
                                                <div className="flex gap-2">
                                                    <button
                                                        onClick={handleCreateCult}
                                                        disabled={
                                                            createLoading ||
                                                            !cultName ||
                                                            selectedBeliefs.length === 0
                                                        }
                                                        className="flex flex-1 items-center justify-center gap-2 rounded bg-purple-600 px-4 py-2 font-pixel text-sm text-white hover:bg-purple-500 disabled:opacity-50"
                                                    >
                                                        {createLoading ? (
                                                            <Loader2 className="h-4 w-4 animate-spin" />
                                                        ) : (
                                                            <>
                                                                <Plus className="h-4 w-4" />
                                                                Create Cult
                                                            </>
                                                        )}
                                                    </button>
                                                    <button
                                                        onClick={() => {
                                                            setShowCreateCult(false);
                                                            setCultName("");
                                                            setCultDescription("");
                                                            setSelectedBeliefs([]);
                                                        }}
                                                        className="rounded bg-stone-700 px-4 py-2 font-pixel text-sm text-stone-300 hover:bg-stone-600"
                                                    >
                                                        Cancel
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    )}
                                </div>
                            )}
                        </div>
                    </div>
                )}
            </div>

            {/* Accept Invite Modal */}
            {showAcceptModal && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-4">
                    <div className="relative w-full max-w-md rounded-lg border border-green-500/50 bg-stone-900 p-6">
                        <button
                            onClick={() => setShowAcceptModal(null)}
                            className="absolute right-4 top-4 text-stone-400 hover:text-white"
                        >
                            <X className="h-5 w-5" />
                        </button>

                        <div className="mb-4 flex items-center justify-center gap-2">
                            <Check className="h-6 w-6 text-green-400" />
                            <h2 className="font-pixel text-lg text-green-300">Accept Invitation</h2>
                        </div>

                        <div className="mb-4 text-center">
                            <p className="font-pixel text-sm text-stone-300">
                                Join{" "}
                                <span className="text-purple-300">
                                    {showAcceptModal.religion?.name}
                                </span>
                                ?
                            </p>
                            {showAcceptModal.inviter && (
                                <p className="font-pixel text-xs text-stone-400 mt-1">
                                    Invited by {showAcceptModal.inviter.username}
                                </p>
                            )}
                        </div>

                        {current_membership && (
                            <div className="mb-4 rounded border border-amber-500/50 bg-amber-900/20 p-3">
                                <div className="flex items-start gap-2">
                                    <Crown className="h-4 w-4 text-amber-400 shrink-0 mt-0.5" />
                                    <div className="font-pixel text-xs text-amber-300">
                                        <p className="mb-1">
                                            You are currently{" "}
                                            {current_membership.is_prophet
                                                ? "the Prophet"
                                                : current_membership.is_priest
                                                  ? "a Priest"
                                                  : "a member"}{" "}
                                            of{" "}
                                            <span className="text-purple-300">
                                                {current_membership.religion_name}
                                            </span>
                                            .
                                        </p>
                                        {current_membership.is_prophet ? (
                                            <p className="text-amber-200">
                                                Accepting will <strong>abdicate</strong> your
                                                position as Prophet. This action will be logged.
                                            </p>
                                        ) : (
                                            <p className="text-stone-400">
                                                Accepting will leave your current religion.
                                            </p>
                                        )}
                                    </div>
                                </div>
                            </div>
                        )}

                        <div className="flex gap-3">
                            <button
                                onClick={() => setShowAcceptModal(null)}
                                className="flex-1 rounded border border-stone-600 bg-stone-800 px-4 py-2 font-pixel text-xs text-stone-300 hover:bg-stone-700"
                            >
                                Cancel
                            </button>
                            <button
                                onClick={confirmAcceptInvite}
                                disabled={inviteLoading === showAcceptModal.id}
                                className="flex-1 rounded bg-green-600 px-4 py-2 font-pixel text-xs text-white hover:bg-green-500 disabled:opacity-50"
                            >
                                {inviteLoading === showAcceptModal.id ? (
                                    <Loader2 className="mx-auto h-4 w-4 animate-spin" />
                                ) : (
                                    "Accept & Join"
                                )}
                            </button>
                        </div>
                    </div>
                </div>
            )}

            {/* Decline Invite Modal */}
            {showDeclineModal && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-4">
                    <div className="relative w-full max-w-md rounded-lg border border-red-500/50 bg-stone-900 p-6">
                        <button
                            onClick={() => setShowDeclineModal(null)}
                            className="absolute right-4 top-4 text-stone-400 hover:text-white"
                        >
                            <X className="h-5 w-5" />
                        </button>

                        <div className="mb-4 flex items-center justify-center gap-2">
                            <X className="h-6 w-6 text-red-400" />
                            <h2 className="font-pixel text-lg text-red-300">Decline Invitation</h2>
                        </div>

                        <div className="mb-4 text-center">
                            <p className="font-pixel text-sm text-stone-300">
                                Decline invitation from{" "}
                                <span className="text-purple-300">
                                    {showDeclineModal.religion?.name}
                                </span>
                                ?
                            </p>
                            {showDeclineModal.inviter && (
                                <p className="font-pixel text-xs text-stone-400 mt-1">
                                    Invited by {showDeclineModal.inviter.username}
                                </p>
                            )}
                        </div>

                        <div className="flex gap-3">
                            <button
                                onClick={() => setShowDeclineModal(null)}
                                className="flex-1 rounded border border-stone-600 bg-stone-800 px-4 py-2 font-pixel text-xs text-stone-300 hover:bg-stone-700"
                            >
                                Cancel
                            </button>
                            <button
                                onClick={confirmDeclineInvite}
                                disabled={inviteLoading === showDeclineModal.id}
                                className="flex-1 rounded bg-red-600 px-4 py-2 font-pixel text-xs text-white hover:bg-red-500 disabled:opacity-50"
                            >
                                {inviteLoading === showDeclineModal.id ? (
                                    <Loader2 className="mx-auto h-4 w-4 animate-spin" />
                                ) : (
                                    "Decline"
                                )}
                            </button>
                        </div>
                    </div>
                </div>
            )}
        </AppLayout>
    );
}
