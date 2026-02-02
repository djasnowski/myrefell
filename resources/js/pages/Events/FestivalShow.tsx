import { Head, Link, router, usePage } from "@inertiajs/react";
import {
    ArrowLeft,
    Calendar,
    CalendarDays,
    Clock,
    Coins,
    Dice5,
    Gift,
    MapPin,
    Music,
    PartyPopper,
    Skull,
    Star,
    Swords,
    Target,
    Trophy,
    Utensils,
    Users,
} from "lucide-react";
import { useState } from "react";
import AppLayout from "@/layouts/app-layout";
import { formatBonusLabel } from "@/lib/utils";
import type { BreadcrumbItem } from "@/types";

interface FestivalType {
    id: number;
    name: string;
    category: string;
    description: string;
    activities: string[];
    bonuses: Record<string, number>;
    season: string | null;
}

interface Festival {
    id: number;
    name: string;
    type: FestivalType;
    status: "scheduled" | "active" | "completed" | "cancelled";
    location_type: string;
    location_id: number;
    location_name: string;
    starts_at: string | null;
    ends_at: string | null;
    starts_at_formatted: string | null;
    ends_at_formatted: string | null;
    organizer_id: number | null;
    organizer_name: string | null;
    budget: number;
    attendance_count: number;
    results: Record<string, unknown>;
    duration_days: number;
    current_day: number | null;
}

interface Participant {
    id: number;
    user_id: number;
    username: string;
    role: string;
    gold_spent: number;
    gold_earned: number;
    activities_completed: string[];
    joined_at: string | null;
}

interface TournamentType {
    id: number;
    name: string;
    combat_type: string;
    description: string;
    entry_fee: number;
    min_level: number;
    max_participants: number;
    is_lethal: boolean;
}

interface Tournament {
    id: number;
    name: string;
    type: TournamentType;
    status: "registration" | "in_progress" | "completed" | "cancelled";
    location_type: string;
    location_id: number;
    location_name: string;
    registration_ends_at: string | null;
    registration_ends_formatted: string | null;
    starts_at: string | null;
    starts_at_formatted: string | null;
    prize_pool: number;
    competitor_count: number;
    current_round: number | null;
    total_rounds: number | null;
    is_registered: boolean;
    user_status: string | null;
    is_registration_open: boolean;
}

interface UserParticipation {
    role: string;
    gold_spent: number;
    gold_earned: number;
    activities_completed: string[];
}

interface PageProps {
    festival: Festival;
    participants: Participant[];
    tournaments: Tournament[];
    is_participating: boolean;
    user_participation: UserParticipation | null;
    user_gold: number;
    user_combat_level: number;
    [key: string]: unknown;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: "Dashboard", href: "/dashboard" },
    { title: "Events", href: "/events" },
    { title: "Festival Details", href: "#" },
];

const festivalCategoryColors: Record<string, { bg: string; text: string; border: string }> = {
    seasonal: { bg: "bg-green-900/30", text: "text-green-400", border: "border-green-600/50" },
    religious: { bg: "bg-purple-900/30", text: "text-purple-400", border: "border-purple-600/50" },
    royal: { bg: "bg-amber-900/30", text: "text-amber-400", border: "border-amber-600/50" },
    special: { bg: "bg-blue-900/30", text: "text-blue-400", border: "border-blue-600/50" },
};

const statusColors: Record<string, { bg: string; text: string; label: string }> = {
    scheduled: { bg: "bg-blue-900/30", text: "text-blue-400", label: "Scheduled" },
    active: { bg: "bg-green-900/30", text: "text-green-400", label: "Active" },
    completed: { bg: "bg-stone-900/30", text: "text-stone-400", label: "Completed" },
    cancelled: { bg: "bg-stone-900/30", text: "text-stone-500", label: "Cancelled" },
};

const roleColors: Record<string, { bg: string; text: string }> = {
    attendee: { bg: "bg-blue-900/30", text: "text-blue-400" },
    performer: { bg: "bg-purple-900/30", text: "text-purple-400" },
    vendor: { bg: "bg-amber-900/30", text: "text-amber-400" },
    organizer: { bg: "bg-green-900/30", text: "text-green-400" },
    competitor: { bg: "bg-red-900/30", text: "text-red-400" },
};

const activityIcons: Record<string, typeof PartyPopper> = {
    dance: Music,
    feast: Utensils,
    games: Dice5,
    tournament: Swords,
    contest: Trophy,
    performance: Music,
    gift_exchange: Gift,
    prayer: Star,
};

const combatTypeIcons: Record<string, typeof Swords> = {
    melee: Swords,
    joust: Target,
    archery: Target,
    wrestling: Users,
    mixed: Swords,
};

export default function FestivalShow() {
    const {
        festival,
        participants,
        tournaments,
        is_participating,
        user_participation,
        user_gold,
        user_combat_level,
    } = usePage<PageProps>().props;

    const [joining, setJoining] = useState(false);
    const [leaving, setLeaving] = useState(false);
    const [registeringTournament, setRegisteringTournament] = useState<number | null>(null);
    const [error, setError] = useState<string | null>(null);
    const [success, setSuccess] = useState<string | null>(null);

    const categoryStyle =
        festivalCategoryColors[festival.type.category] || festivalCategoryColors.special;
    const statusStyle = statusColors[festival.status] || statusColors.scheduled;

    const joinFestival = async (role: string = "attendee") => {
        setJoining(true);
        setError(null);

        try {
            const response = await fetch(`/events/festivals/${festival.id}/join`, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN":
                        document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')
                            ?.content || "",
                },
                body: JSON.stringify({ role }),
            });

            const data = await response.json();
            if (data.success) {
                setSuccess("You have joined the festival!");
                router.reload();
            } else {
                setError(data.message);
            }
        } catch {
            setError("Failed to join festival");
        } finally {
            setJoining(false);
        }
    };

    const leaveFestival = async () => {
        setLeaving(true);
        setError(null);

        try {
            const response = await fetch(`/events/festivals/${festival.id}/leave`, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN":
                        document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')
                            ?.content || "",
                },
            });

            const data = await response.json();
            if (data.success) {
                setSuccess("You have left the festival.");
                router.reload();
            } else {
                setError(data.message);
            }
        } catch {
            setError("Failed to leave festival");
        } finally {
            setLeaving(false);
        }
    };

    const registerForTournament = async (tournamentId: number) => {
        setRegisteringTournament(tournamentId);
        setError(null);

        try {
            const response = await fetch(`/events/tournaments/${tournamentId}/register`, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN":
                        document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')
                            ?.content || "",
                },
            });

            const data = await response.json();
            if (data.success) {
                setSuccess("You have registered for the tournament!");
                router.reload();
            } else {
                setError(data.message);
            }
        } catch {
            setError("Failed to register for tournament");
        } finally {
            setRegisteringTournament(null);
        }
    };

    // Calculate progress percentage for active festivals
    const progressPercent =
        festival.current_day && festival.duration_days
            ? Math.min(100, Math.round((festival.current_day / festival.duration_days) * 100))
            : 0;

    // Count participants by role
    const participantsByRole = participants.reduce(
        (acc, p) => {
            acc[p.role] = (acc[p.role] || 0) + 1;
            return acc;
        },
        {} as Record<string, number>,
    );

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Festival: ${festival.name}`} />
            <div className="flex h-full flex-1 flex-col gap-6 overflow-y-auto p-4">
                {/* Back Link */}
                <Link
                    href="/events"
                    className="flex items-center gap-2 font-pixel text-sm text-stone-400 transition hover:text-stone-300"
                >
                    <ArrowLeft className="h-4 w-4" />
                    Back to Events
                </Link>

                {/* Messages */}
                {error && (
                    <div className="rounded-lg border border-red-500/50 bg-red-900/30 p-3 font-pixel text-sm text-red-300">
                        {error}
                    </div>
                )}
                {success && (
                    <div className="rounded-lg border border-green-500/50 bg-green-900/30 p-3 font-pixel text-sm text-green-300">
                        {success}
                    </div>
                )}

                {/* Header */}
                <div
                    className={`rounded-xl border-2 ${categoryStyle.border} ${categoryStyle.bg} p-4`}
                >
                    <div className="flex flex-wrap items-start justify-between gap-4">
                        <div className="flex items-center gap-3">
                            <PartyPopper className={`h-8 w-8 ${categoryStyle.text}`} />
                            <div>
                                <h1 className="font-pixel text-2xl text-white">{festival.name}</h1>
                                <div className="flex flex-wrap items-center gap-3 font-pixel text-sm text-stone-400">
                                    <span
                                        className={`rounded px-2 py-0.5 text-[10px] capitalize ${categoryStyle.bg} ${categoryStyle.text}`}
                                    >
                                        {festival.type.category}
                                    </span>
                                    <span className="flex items-center gap-1">
                                        <MapPin className="h-3 w-3 text-amber-400" />
                                        {festival.location_name}
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div className="flex items-center gap-3">
                            <span
                                className={`rounded px-3 py-1.5 font-pixel text-xs ${statusStyle.bg} ${statusStyle.text}`}
                            >
                                {statusStyle.label}
                            </span>
                        </div>
                    </div>

                    {/* Festival Type Description */}
                    {festival.type.description && (
                        <p className="mt-3 font-pixel text-xs text-stone-300 italic">
                            "{festival.type.description}"
                        </p>
                    )}
                </div>

                {/* User Participation Banner */}
                {is_participating && user_participation && (
                    <div className="rounded-xl border-2 border-green-500/30 bg-green-900/20 p-4">
                        <div className="flex flex-wrap items-center justify-between gap-4">
                            <div className="flex items-center gap-3">
                                <Star className="h-5 w-5 text-green-400" />
                                <div>
                                    <span className="font-pixel text-sm text-white">
                                        You are participating!
                                    </span>
                                    <span
                                        className={`ml-2 rounded px-2 py-0.5 font-pixel text-[10px] capitalize ${roleColors[user_participation.role]?.bg || "bg-stone-700"} ${roleColors[user_participation.role]?.text || "text-stone-300"}`}
                                    >
                                        {user_participation.role}
                                    </span>
                                </div>
                            </div>
                            <div className="flex items-center gap-4 font-pixel text-xs">
                                <div>
                                    <span className="text-stone-400">Spent: </span>
                                    <span className="text-yellow-400">
                                        {user_participation.gold_spent}g
                                    </span>
                                </div>
                                <div>
                                    <span className="text-stone-400">Earned: </span>
                                    <span className="text-green-400">
                                        {user_participation.gold_earned}g
                                    </span>
                                </div>
                                {festival.status !== "completed" && (
                                    <button
                                        onClick={leaveFestival}
                                        disabled={leaving}
                                        className="rounded border border-red-600/50 bg-red-900/20 px-3 py-1 font-pixel text-xs text-red-300 transition hover:bg-red-900/40 disabled:cursor-not-allowed disabled:opacity-50"
                                    >
                                        {leaving ? "Leaving..." : "Leave Festival"}
                                    </button>
                                )}
                            </div>
                        </div>
                    </div>
                )}

                {/* Progress Bar for Active Festivals */}
                {festival.status === "active" && festival.current_day && (
                    <div className="rounded-xl border-2 border-stone-600/50 bg-stone-800/30 p-4">
                        <div className="mb-2 flex items-center justify-between font-pixel text-xs">
                            <span className="flex items-center gap-2 text-stone-300">
                                <Clock className="h-3 w-3 text-amber-400" />
                                Festival Progress
                            </span>
                            <span className="text-stone-400">
                                Day {festival.current_day} of {festival.duration_days}
                            </span>
                        </div>
                        <div className="h-4 w-full overflow-hidden rounded-full bg-stone-700">
                            <div
                                className="h-full bg-gradient-to-r from-green-600 to-green-400 transition-all duration-300"
                                style={{ width: `${progressPercent}%` }}
                            />
                        </div>
                        <div className="mt-1 text-right font-pixel text-[10px] text-stone-400">
                            {progressPercent}% complete
                        </div>
                    </div>
                )}

                {/* Info Grid */}
                <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                    {/* Duration */}
                    <div className="rounded-lg bg-stone-800/50 p-3 text-center">
                        <div className="flex items-center justify-center gap-1">
                            <CalendarDays className="h-4 w-4 text-blue-400" />
                            <span className="font-pixel text-[10px] text-stone-400">Duration</span>
                        </div>
                        <div className="font-pixel text-lg text-white">
                            {festival.duration_days} days
                        </div>
                        {festival.starts_at_formatted && (
                            <div className="font-pixel text-[10px] text-stone-500">
                                {festival.starts_at_formatted} - {festival.ends_at_formatted}
                            </div>
                        )}
                    </div>

                    {/* Participants */}
                    <div className="rounded-lg bg-stone-800/50 p-3 text-center">
                        <div className="flex items-center justify-center gap-1">
                            <Users className="h-4 w-4 text-green-400" />
                            <span className="font-pixel text-[10px] text-stone-400">
                                Participants
                            </span>
                        </div>
                        <div className="font-pixel text-lg text-white">
                            {festival.attendance_count}
                        </div>
                    </div>

                    {/* Budget */}
                    {festival.budget > 0 && (
                        <div className="rounded-lg bg-stone-800/50 p-3 text-center">
                            <div className="flex items-center justify-center gap-1">
                                <Coins className="h-4 w-4 text-yellow-400" />
                                <span className="font-pixel text-[10px] text-stone-400">
                                    Budget
                                </span>
                            </div>
                            <div className="font-pixel text-lg text-yellow-300">
                                {festival.budget}g
                            </div>
                        </div>
                    )}

                    {/* Organizer */}
                    {festival.organizer_name && (
                        <div className="rounded-lg bg-stone-800/50 p-3 text-center">
                            <div className="flex items-center justify-center gap-1">
                                <Star className="h-4 w-4 text-amber-400" />
                                <span className="font-pixel text-[10px] text-stone-400">
                                    Organizer
                                </span>
                            </div>
                            <div className="font-pixel text-sm text-white">
                                {festival.organizer_name}
                            </div>
                        </div>
                    )}
                </div>

                {/* Activities */}
                {festival.type.activities && festival.type.activities.length > 0 && (
                    <div className="rounded-xl border-2 border-stone-600/50 bg-stone-800/30 p-4">
                        <h2 className="mb-4 flex items-center gap-2 font-pixel text-lg text-white">
                            <PartyPopper className="h-5 w-5 text-amber-400" />
                            Activities
                        </h2>
                        <div className="grid gap-3 md:grid-cols-2 lg:grid-cols-3">
                            {festival.type.activities.map((activity, i) => {
                                const ActivityIcon =
                                    activityIcons[activity.toLowerCase()] || PartyPopper;
                                const isCompleted =
                                    user_participation?.activities_completed?.includes(activity);
                                return (
                                    <div
                                        key={i}
                                        className={`flex items-center gap-3 rounded-lg p-3 ${
                                            isCompleted
                                                ? "border border-green-500/30 bg-green-900/20"
                                                : "bg-stone-900/50"
                                        }`}
                                    >
                                        <ActivityIcon
                                            className={`h-5 w-5 ${isCompleted ? "text-green-400" : categoryStyle.text}`}
                                        />
                                        <div className="flex-1">
                                            <span className="font-pixel text-sm capitalize text-white">
                                                {activity.replace("_", " ")}
                                            </span>
                                            {isCompleted && (
                                                <span className="ml-2 font-pixel text-[10px] text-green-400">
                                                    Completed
                                                </span>
                                            )}
                                        </div>
                                    </div>
                                );
                            })}
                        </div>
                    </div>
                )}

                {/* Bonuses */}
                {festival.type.bonuses && Object.keys(festival.type.bonuses).length > 0 && (
                    <div className="rounded-xl border-2 border-stone-600/50 bg-stone-800/30 p-4">
                        <h2 className="mb-4 flex items-center gap-2 font-pixel text-lg text-white">
                            <Gift className="h-5 w-5 text-purple-400" />
                            Festival Bonuses
                        </h2>
                        <div className="grid gap-2 md:grid-cols-2 lg:grid-cols-3">
                            {Object.entries(festival.type.bonuses).map(([key, value]) => (
                                <div
                                    key={key}
                                    className="flex items-center justify-between rounded-lg bg-stone-900/50 p-2"
                                >
                                    <span className="font-pixel text-xs text-stone-300">
                                        {formatBonusLabel(key)}
                                    </span>
                                    <span
                                        className={`font-pixel text-xs ${Number(value) >= 0 ? "text-green-400" : "text-red-400"}`}
                                    >
                                        {Number(value) >= 0 ? "+" : ""}
                                        {value}%
                                    </span>
                                </div>
                            ))}
                        </div>
                    </div>
                )}

                {/* Tournaments */}
                {tournaments.length > 0 && (
                    <div className="rounded-xl border-2 border-red-600/50 bg-red-900/20 p-4">
                        <h2 className="mb-4 flex items-center gap-2 font-pixel text-lg text-red-400">
                            <Trophy className="h-5 w-5" />
                            Tournaments
                        </h2>
                        <div className="grid gap-4 md:grid-cols-2">
                            {tournaments.map((tournament) => {
                                const CombatIcon =
                                    combatTypeIcons[tournament.type.combat_type] || Swords;
                                const tournamentStatusStyle =
                                    statusColors[tournament.status] || statusColors.scheduled;
                                const canRegister =
                                    tournament.is_registration_open && !tournament.is_registered;
                                const meetsLevel = user_combat_level >= tournament.type.min_level;
                                const hasGold = user_gold >= tournament.type.entry_fee;

                                return (
                                    <div
                                        key={tournament.id}
                                        className="rounded-lg bg-stone-900/50 p-4"
                                    >
                                        {/* Tournament Header */}
                                        <div className="mb-3 flex items-start justify-between">
                                            <div className="flex items-center gap-2">
                                                <CombatIcon className="h-5 w-5 text-red-400" />
                                                <span className="font-pixel text-sm text-white">
                                                    {tournament.name}
                                                </span>
                                            </div>
                                            <div className="flex items-center gap-2">
                                                {tournament.type.is_lethal && (
                                                    <span className="flex items-center gap-1 rounded bg-red-900/50 px-1.5 py-0.5 font-pixel text-[10px] text-red-300">
                                                        <Skull className="h-3 w-3" />
                                                        Lethal
                                                    </span>
                                                )}
                                                <span
                                                    className={`rounded px-2 py-0.5 font-pixel text-[10px] capitalize ${tournamentStatusStyle.bg} ${tournamentStatusStyle.text}`}
                                                >
                                                    {tournament.status.replace("_", " ")}
                                                </span>
                                            </div>
                                        </div>

                                        {/* Tournament Info */}
                                        <div className="mb-3 font-pixel text-xs capitalize text-red-300">
                                            {tournament.type.name} - {tournament.type.combat_type}
                                        </div>

                                        {/* Stats */}
                                        <div className="mb-3 grid grid-cols-3 gap-2">
                                            <div className="rounded bg-stone-800/50 p-2 text-center">
                                                <div className="font-pixel text-[10px] text-stone-400">
                                                    Prize
                                                </div>
                                                <div className="font-pixel text-sm text-yellow-300">
                                                    {tournament.prize_pool}g
                                                </div>
                                            </div>
                                            <div className="rounded bg-stone-800/50 p-2 text-center">
                                                <div className="font-pixel text-[10px] text-stone-400">
                                                    Competitors
                                                </div>
                                                <div className="font-pixel text-sm text-white">
                                                    {tournament.competitor_count}/
                                                    {tournament.type.max_participants}
                                                </div>
                                            </div>
                                            <div className="rounded bg-stone-800/50 p-2 text-center">
                                                <div className="font-pixel text-[10px] text-stone-400">
                                                    Entry
                                                </div>
                                                <div className="font-pixel text-sm text-white">
                                                    {tournament.type.entry_fee}g
                                                </div>
                                            </div>
                                        </div>

                                        {/* Registration info */}
                                        {tournament.status === "registration" &&
                                            tournament.registration_ends_formatted && (
                                                <div className="mb-3 flex items-center gap-2 font-pixel text-[10px] text-stone-400">
                                                    <Calendar className="h-3 w-3" />
                                                    Registration ends:{" "}
                                                    {tournament.registration_ends_formatted}
                                                </div>
                                            )}

                                        {/* Progress for in-progress */}
                                        {tournament.status === "in_progress" &&
                                            tournament.current_round && (
                                                <div className="mb-3 flex items-center gap-2 font-pixel text-[10px] text-amber-300">
                                                    <Trophy className="h-3 w-3" />
                                                    Round {tournament.current_round} /{" "}
                                                    {tournament.total_rounds}
                                                </div>
                                            )}

                                        {/* Action */}
                                        {canRegister && (
                                            <button
                                                onClick={() => registerForTournament(tournament.id)}
                                                disabled={
                                                    registeringTournament === tournament.id ||
                                                    !meetsLevel ||
                                                    !hasGold
                                                }
                                                className="w-full rounded border border-red-600/50 bg-red-900/30 px-3 py-1.5 font-pixel text-xs text-red-300 transition hover:bg-red-900/50 disabled:cursor-not-allowed disabled:opacity-50"
                                            >
                                                {registeringTournament === tournament.id
                                                    ? "Registering..."
                                                    : `Register (${tournament.type.entry_fee}g)`}
                                            </button>
                                        )}
                                        {tournament.is_registered && (
                                            <div className="rounded bg-green-900/30 px-3 py-1.5 text-center font-pixel text-xs text-green-400">
                                                Registered{" "}
                                                {tournament.user_status &&
                                                    tournament.user_status !== "registered" && (
                                                        <span className="capitalize">
                                                            - {tournament.user_status}
                                                        </span>
                                                    )}
                                            </div>
                                        )}
                                    </div>
                                );
                            })}
                        </div>
                    </div>
                )}

                {/* Participants List */}
                <div className="rounded-xl border-2 border-stone-600/50 bg-stone-800/30 p-4">
                    <h2 className="mb-4 flex items-center gap-2 font-pixel text-lg text-white">
                        <Users className="h-5 w-5 text-blue-400" />
                        Participants
                        <span className="font-pixel text-xs text-stone-400">
                            ({participants.length})
                        </span>
                    </h2>

                    {/* Role summary */}
                    <div className="mb-4 flex flex-wrap gap-2">
                        {Object.entries(participantsByRole).map(([role, count]) => {
                            const roleStyle = roleColors[role] || roleColors.attendee;
                            return (
                                <span
                                    key={role}
                                    className={`rounded px-2 py-1 font-pixel text-[10px] capitalize ${roleStyle.bg} ${roleStyle.text}`}
                                >
                                    {role}: {count}
                                </span>
                            );
                        })}
                    </div>

                    {participants.length > 0 ? (
                        <div className="max-h-64 overflow-y-auto">
                            <div className="grid gap-2 md:grid-cols-2 lg:grid-cols-3">
                                {participants.slice(0, 50).map((participant) => {
                                    const roleStyle =
                                        roleColors[participant.role] || roleColors.attendee;
                                    return (
                                        <div
                                            key={participant.id}
                                            className="flex items-center justify-between rounded-lg bg-stone-900/50 p-2"
                                        >
                                            <div className="flex items-center gap-2">
                                                <Users className="h-3 w-3 text-stone-400" />
                                                <span className="font-pixel text-xs text-white">
                                                    {participant.username}
                                                </span>
                                            </div>
                                            <span
                                                className={`rounded px-1.5 py-0.5 font-pixel text-[10px] capitalize ${roleStyle.bg} ${roleStyle.text}`}
                                            >
                                                {participant.role}
                                            </span>
                                        </div>
                                    );
                                })}
                            </div>
                            {participants.length > 50 && (
                                <div className="mt-3 text-center font-pixel text-xs text-stone-500">
                                    + {participants.length - 50} more participants
                                </div>
                            )}
                        </div>
                    ) : (
                        <p className="font-pixel text-sm text-stone-500">
                            No participants yet. Be the first to join!
                        </p>
                    )}
                </div>

                {/* Join Festival Action */}
                {!is_participating && festival.status === "active" && (
                    <div
                        className={`rounded-xl border-2 ${categoryStyle.border} ${categoryStyle.bg} p-4`}
                    >
                        <h2 className="mb-4 flex items-center gap-2 font-pixel text-lg text-white">
                            <PartyPopper className="h-5 w-5" />
                            Join the Festival
                        </h2>
                        <p className="mb-4 font-pixel text-xs text-stone-300">
                            Choose how you want to participate in this festival:
                        </p>
                        <div className="flex flex-wrap gap-3">
                            <button
                                onClick={() => joinFestival("attendee")}
                                disabled={joining}
                                className={`flex items-center gap-2 rounded border ${categoryStyle.border} ${categoryStyle.bg} px-4 py-2 font-pixel text-xs ${categoryStyle.text} transition hover:brightness-125 disabled:cursor-not-allowed disabled:opacity-50`}
                            >
                                <Users className="h-4 w-4" />
                                {joining ? "Joining..." : "Join as Attendee"}
                            </button>
                            <button
                                onClick={() => joinFestival("performer")}
                                disabled={joining}
                                className="flex items-center gap-2 rounded border border-purple-600/50 bg-purple-900/20 px-4 py-2 font-pixel text-xs text-purple-300 transition hover:brightness-125 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                <Music className="h-4 w-4" />
                                {joining ? "Joining..." : "Join as Performer"}
                            </button>
                            <button
                                onClick={() => joinFestival("vendor")}
                                disabled={joining}
                                className="flex items-center gap-2 rounded border border-amber-600/50 bg-amber-900/20 px-4 py-2 font-pixel text-xs text-amber-300 transition hover:brightness-125 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                <Coins className="h-4 w-4" />
                                {joining ? "Joining..." : "Join as Vendor"}
                            </button>
                        </div>
                    </div>
                )}

                {/* Scheduled Festival Info */}
                {festival.status === "scheduled" && (
                    <div className="rounded-xl border-2 border-blue-600/50 bg-blue-900/20 p-4 text-center">
                        <Calendar className="mx-auto mb-2 h-8 w-8 text-blue-400" />
                        <p className="font-pixel text-sm text-blue-300">
                            This festival is scheduled for {festival.starts_at_formatted}
                        </p>
                        <p className="font-pixel text-xs text-stone-400">
                            Check back when the festival begins to participate!
                        </p>
                    </div>
                )}

                {/* Completed Festival Info */}
                {festival.status === "completed" && (
                    <div className="rounded-xl border-2 border-stone-600/50 bg-stone-800/30 p-4 text-center">
                        <Trophy className="mx-auto mb-2 h-8 w-8 text-amber-400" />
                        <p className="font-pixel text-sm text-stone-300">This festival has ended</p>
                        <p className="font-pixel text-xs text-stone-500">
                            {festival.ends_at_formatted}
                        </p>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
