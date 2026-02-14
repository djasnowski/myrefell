import { Head, router } from "@inertiajs/react";
import {
    AlertTriangle,
    CheckCircle,
    Clock,
    Flag,
    Loader2,
    ScrollText,
    Shield,
    ThumbsDown,
    ThumbsUp,
    User,
    Users,
    XCircle,
} from "lucide-react";
import { useEffect, useState } from "react";
import AppLayout from "@/layouts/app-layout";
import type { BreadcrumbItem } from "@/types";

interface NoConfidenceVote {
    id: number;
    target_role: string;
    target_player: { id: number; username: string };
    domain_type: string;
    domain_id: number;
    domain_name: string;
    status: string;
    voting_starts_at: string | null;
    voting_ends_at: string | null;
    finalized_at: string | null;
    votes_for: number;
    votes_against: number;
    votes_cast: number;
    quorum_required: number;
    quorum_met: boolean;
    eligible_voters: number;
    is_open: boolean;
    has_ended: boolean;
    percentage_for: number;
    percentage_against: number;
    reason: string | null;
    notes: string | null;
    initiated_by: { id: number; username: string } | null;
}

interface UserState {
    has_voted: boolean;
    is_eligible: boolean;
    can_vote: boolean;
    is_target: boolean;
    is_initiator: boolean;
}

interface Props {
    vote: NoConfidenceVote;
    user_state: UserState;
}

function useCountdown(endTime: string | null): string {
    const [timeLeft, setTimeLeft] = useState("");

    useEffect(() => {
        if (!endTime) {
            setTimeLeft("");
            return;
        }

        const update = () => {
            const diff = new Date(endTime).getTime() - Date.now();
            if (diff <= 0) {
                setTimeLeft("Voting ended");
                return;
            }
            const days = Math.floor(diff / (1000 * 60 * 60 * 24));
            const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
            if (days > 0) {
                setTimeLeft(`${days}d ${hours}h ${minutes}m`);
            } else {
                setTimeLeft(`${hours}h ${minutes}m`);
            }
        };

        update();
        const interval = setInterval(update, 30000);
        return () => clearInterval(interval);
    }, [endTime]);

    return timeLeft;
}

function formatLocalTime(dateString: string): string {
    return new Date(dateString).toLocaleString(undefined, {
        month: "short",
        day: "numeric",
        hour: "numeric",
        minute: "2-digit",
    });
}

export default function NoConfidenceShow({ vote, user_state }: Props) {
    const [voting, setVoting] = useState(false);
    const countdown = useCountdown(vote.is_open ? vote.voting_ends_at : null);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: "Dashboard", href: "/dashboard" },
        { title: "No Confidence Vote", href: "#" },
    ];

    const handleVote = (voteForRemoval: boolean) => {
        if (!user_state.can_vote || voting) return;
        setVoting(true);
        router.post(
            `/no-confidence/${vote.id}/vote`,
            { vote_for_removal: voteForRemoval },
            {
                preserveScroll: true,
                onSuccess: () => {
                    router.reload();
                },
                onFinish: () => setVoting(false),
            },
        );
    };

    const roleName = vote.target_role.replace(/_/g, " ").replace(/\b\w/g, (c) => c.toUpperCase());

    const statusConfig: Record<
        string,
        { label: string; color: string; border: string; bg: string }
    > = {
        open: {
            label: "Voting Open",
            color: "text-yellow-400",
            border: "border-yellow-600/50",
            bg: "bg-yellow-900/20",
        },
        pending: {
            label: "Pending",
            color: "text-yellow-400",
            border: "border-yellow-600/50",
            bg: "bg-yellow-900/20",
        },
        passed: {
            label: "Vote Passed — Removed from Office",
            color: "text-red-400",
            border: "border-red-600/50",
            bg: "bg-red-900/20",
        },
        failed: {
            label: "Vote Failed — Retained Position",
            color: "text-green-400",
            border: "border-green-600/50",
            bg: "bg-green-900/20",
        },
        closed: {
            label: "Voting Closed",
            color: "text-stone-400",
            border: "border-stone-600/50",
            bg: "bg-stone-800/30",
        },
    };

    const status = statusConfig[vote.status] || statusConfig.closed;
    const quorumProgress = Math.min(100, (vote.votes_cast / vote.quorum_required) * 100);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`No Confidence Vote — ${vote.target_player.username}`} />
            <div className="flex h-full flex-1 flex-col overflow-auto p-4">
                {/* Header */}
                <div className="mb-5 flex items-center gap-3">
                    <div className="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl border-2 border-red-700/50 bg-red-900/30">
                        <Flag className="h-6 w-6 text-red-400" />
                    </div>
                    <div className="flex-1">
                        <h1 className="font-pixel text-xl text-red-400">No Confidence Vote</h1>
                        <p className="font-pixel text-xs text-stone-400">
                            Remove{" "}
                            <span className="text-amber-300">{vote.target_player.username}</span> as{" "}
                            <span className="text-stone-200">{roleName}</span> of{" "}
                            <span className="text-stone-200">{vote.domain_name}</span>
                        </p>
                    </div>
                    <div className={`rounded-lg border ${status.border} ${status.bg} px-3 py-1.5`}>
                        <span className={`font-pixel text-xs ${status.color}`}>{status.label}</span>
                    </div>
                </div>

                <div className="mx-auto flex w-full max-w-4xl flex-col gap-4">
                    {/* Voting Progress — Full Width Row */}
                    <div className="rounded-xl border-2 border-stone-700 bg-stone-800/50 p-5">
                        <div className="mb-4 flex items-center justify-between">
                            <h2 className="flex items-center gap-2 font-pixel text-sm text-stone-200">
                                <Users className="h-4 w-4 text-blue-400" />
                                Voting Progress
                            </h2>
                            {vote.is_open && countdown && (
                                <div className="group relative flex items-center gap-1.5 rounded-lg border border-yellow-600/40 bg-yellow-900/20 px-3 py-1.5">
                                    <Clock className="h-3.5 w-3.5 text-yellow-400" />
                                    <span className="font-pixel text-xs text-yellow-300">
                                        Ends in {countdown}
                                    </span>
                                    {vote.voting_ends_at && (
                                        <span className="pointer-events-none absolute -bottom-8 left-1/2 z-10 -translate-x-1/2 whitespace-nowrap rounded bg-stone-900 px-2 py-1 font-pixel text-[10px] text-stone-300 opacity-0 shadow-lg transition group-hover:opacity-100">
                                            {formatLocalTime(vote.voting_ends_at)}
                                        </span>
                                    )}
                                </div>
                            )}
                            {vote.has_ended && vote.finalized_at && (
                                <span className="font-pixel text-[10px] text-stone-500">
                                    Ended {formatLocalTime(vote.finalized_at)}
                                </span>
                            )}
                        </div>

                        {/* Vote Bar */}
                        <div className="mb-4">
                            <div className="mb-2 flex justify-between font-pixel text-xs">
                                <span className="flex items-center gap-1.5 text-red-400">
                                    <ThumbsUp className="h-3.5 w-3.5" />
                                    For Removal
                                    <span className="rounded bg-red-900/50 px-1.5 py-0.5 text-[10px]">
                                        {vote.votes_for}
                                    </span>
                                </span>
                                <span className="flex items-center gap-1.5 text-green-400">
                                    <span className="rounded bg-green-900/50 px-1.5 py-0.5 text-[10px]">
                                        {vote.votes_against}
                                    </span>
                                    Against
                                    <ThumbsDown className="h-3.5 w-3.5" />
                                </span>
                            </div>
                            <div className="flex h-5 overflow-hidden rounded-full bg-stone-700">
                                {vote.votes_cast > 0 ? (
                                    <>
                                        <div
                                            className="bg-red-500 transition-all duration-500"
                                            style={{ width: `${vote.percentage_for}%` }}
                                        />
                                        <div
                                            className="bg-green-500 transition-all duration-500"
                                            style={{ width: `${vote.percentage_against}%` }}
                                        />
                                    </>
                                ) : (
                                    <div className="flex w-full items-center justify-center">
                                        <span className="font-pixel text-[10px] text-stone-500">
                                            No votes cast yet
                                        </span>
                                    </div>
                                )}
                            </div>
                            {vote.votes_cast > 0 && (
                                <div className="mt-1 flex justify-between font-pixel text-[10px] text-stone-500">
                                    <span>{vote.percentage_for}%</span>
                                    <span>{vote.percentage_against}%</span>
                                </div>
                            )}
                        </div>

                        {/* Quorum + Eligible in a row */}
                        <div className="grid grid-cols-2 gap-3">
                            <div className="flex items-center justify-between rounded-lg border border-stone-700 bg-stone-900/50 p-3">
                                <div>
                                    <div className="font-pixel text-[10px] text-stone-500">
                                        Quorum
                                    </div>
                                    <div className="font-pixel text-xs text-stone-300">
                                        {vote.votes_cast} / {vote.quorum_required} votes
                                    </div>
                                </div>
                                {vote.quorum_met ? (
                                    <CheckCircle className="h-5 w-5 text-green-400" />
                                ) : (
                                    <span className="font-pixel text-[10px] text-amber-400">
                                        {vote.quorum_required - vote.votes_cast} more needed
                                    </span>
                                )}
                            </div>
                            <div className="flex items-center justify-between rounded-lg border border-stone-700 bg-stone-900/50 p-3">
                                <div>
                                    <div className="font-pixel text-[10px] text-stone-500">
                                        Eligible Voters
                                    </div>
                                    <div className="font-pixel text-xs text-stone-300">
                                        {vote.eligible_voters} citizens
                                    </div>
                                </div>
                                <div className="h-6 w-6 rounded-full border border-stone-600 bg-stone-800">
                                    <div
                                        className="h-full rounded-full bg-blue-500/50"
                                        style={{
                                            clipPath: `inset(${100 - quorumProgress}% 0 0 0)`,
                                        }}
                                    />
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Bottom Row: Vote Details + Your Status */}
                    <div className="grid gap-4 md:grid-cols-2">
                        {/* Vote Details */}
                        <div className="rounded-xl border-2 border-stone-700 bg-stone-800/50 p-5">
                            <h2 className="mb-4 flex items-center gap-2 font-pixel text-sm text-stone-200">
                                <ScrollText className="h-4 w-4 text-red-400" />
                                Vote Details
                            </h2>
                            <div className="space-y-3">
                                <div className="flex items-center justify-between">
                                    <span className="font-pixel text-xs text-stone-500">
                                        Target
                                    </span>
                                    <span className="font-pixel text-sm text-red-400">
                                        {vote.target_player.username}
                                    </span>
                                </div>
                                <div className="h-px bg-stone-700/50" />
                                <div className="flex items-center justify-between">
                                    <span className="font-pixel text-xs text-stone-500">Role</span>
                                    <span className="font-pixel text-sm text-stone-200 capitalize">
                                        {roleName}
                                    </span>
                                </div>
                                <div className="h-px bg-stone-700/50" />
                                <div className="flex items-center justify-between">
                                    <span className="font-pixel text-xs text-stone-500">
                                        Location
                                    </span>
                                    <span className="font-pixel text-sm text-stone-200">
                                        {vote.domain_name}
                                    </span>
                                </div>
                                <div className="h-px bg-stone-700/50" />
                                <div className="flex items-center justify-between">
                                    <span className="font-pixel text-xs text-stone-500">
                                        Initiated By
                                    </span>
                                    <span className="font-pixel text-sm text-stone-300">
                                        {vote.initiated_by?.username ?? "Unknown"}
                                    </span>
                                </div>
                                {vote.reason && (
                                    <>
                                        <div className="h-px bg-stone-700/50" />
                                        <div>
                                            <span className="font-pixel text-xs text-stone-500">
                                                Reason
                                            </span>
                                            <p className="mt-1.5 rounded-lg bg-stone-900/50 p-3 font-pixel text-xs text-stone-400 italic">
                                                "{vote.reason}"
                                            </p>
                                        </div>
                                    </>
                                )}
                            </div>
                        </div>

                        {/* Your Status & Voting */}
                        <div className="rounded-xl border-2 border-stone-700 bg-stone-800/50 p-5">
                            <h2 className="mb-4 flex items-center gap-2 font-pixel text-sm text-stone-200">
                                <User className="h-4 w-4 text-amber-400" />
                                Your Status
                            </h2>
                            <div className="space-y-3">
                                <div className="flex items-center justify-between rounded-lg border border-stone-700 bg-stone-900/50 p-3">
                                    <span className="font-pixel text-xs text-stone-500">
                                        Eligible to Vote
                                    </span>
                                    {user_state.is_eligible ? (
                                        <span className="flex items-center gap-1 font-pixel text-xs text-green-400">
                                            <CheckCircle className="h-3.5 w-3.5" /> Yes
                                        </span>
                                    ) : (
                                        <span className="flex items-center gap-1 font-pixel text-xs text-red-400">
                                            <XCircle className="h-3.5 w-3.5" /> No
                                        </span>
                                    )}
                                </div>
                                <div className="flex items-center justify-between rounded-lg border border-stone-700 bg-stone-900/50 p-3">
                                    <span className="font-pixel text-xs text-stone-500">Voted</span>
                                    {user_state.has_voted ? (
                                        <span className="flex items-center gap-1 font-pixel text-xs text-green-400">
                                            <CheckCircle className="h-3.5 w-3.5" /> Yes
                                        </span>
                                    ) : (
                                        <span className="font-pixel text-xs text-stone-400">
                                            Not yet
                                        </span>
                                    )}
                                </div>

                                {user_state.is_target && (
                                    <div className="rounded-lg border border-red-600/50 bg-red-900/20 p-3 text-center">
                                        <Shield className="mx-auto mb-1 h-5 w-5 text-red-400" />
                                        <span className="font-pixel text-xs text-red-300">
                                            You are the target of this vote
                                        </span>
                                    </div>
                                )}

                                {user_state.is_initiator && (
                                    <div className="rounded-lg border border-amber-600/50 bg-amber-900/20 p-3 text-center">
                                        <Flag className="mx-auto mb-1 h-5 w-5 text-amber-400" />
                                        <span className="font-pixel text-xs text-amber-300">
                                            You initiated this vote
                                        </span>
                                    </div>
                                )}

                                {/* Voting Buttons */}
                                {user_state.can_vote && (
                                    <div className="pt-2">
                                        <div className="mb-3 text-center font-pixel text-[10px] text-stone-500">
                                            Cast your vote
                                        </div>
                                        <div className="grid grid-cols-2 gap-3">
                                            <button
                                                onClick={() => handleVote(true)}
                                                disabled={voting}
                                                className="flex items-center justify-center gap-2 rounded-lg border-2 border-red-600/50 bg-red-900/30 py-3 font-pixel text-xs text-red-300 transition hover:border-red-500 hover:bg-red-900/50 disabled:opacity-50"
                                            >
                                                {voting ? (
                                                    <Loader2 className="h-4 w-4 animate-spin" />
                                                ) : (
                                                    <ThumbsUp className="h-4 w-4" />
                                                )}
                                                Remove
                                            </button>
                                            <button
                                                onClick={() => handleVote(false)}
                                                disabled={voting}
                                                className="flex items-center justify-center gap-2 rounded-lg border-2 border-green-600/50 bg-green-900/30 py-3 font-pixel text-xs text-green-300 transition hover:border-green-500 hover:bg-green-900/50 disabled:opacity-50"
                                            >
                                                {voting ? (
                                                    <Loader2 className="h-4 w-4 animate-spin" />
                                                ) : (
                                                    <ThumbsDown className="h-4 w-4" />
                                                )}
                                                Keep
                                            </button>
                                        </div>
                                    </div>
                                )}

                                {user_state.has_voted && !user_state.can_vote && (
                                    <div className="rounded-lg border border-stone-600 bg-stone-800/50 p-3 text-center">
                                        <CheckCircle className="mx-auto mb-1 h-5 w-5 text-stone-500" />
                                        <span className="font-pixel text-xs text-stone-400">
                                            Your vote has been recorded
                                        </span>
                                    </div>
                                )}
                            </div>
                        </div>
                    </div>

                    {/* Outcome Notes */}
                    {vote.notes && (
                        <div
                            className={`rounded-xl border-2 p-5 ${
                                vote.status === "passed"
                                    ? "border-red-600/50 bg-red-900/20"
                                    : vote.status === "failed"
                                      ? "border-green-600/50 bg-green-900/20"
                                      : "border-stone-700 bg-stone-800/50"
                            }`}
                        >
                            <h2
                                className={`mb-2 font-pixel text-sm ${
                                    vote.status === "passed"
                                        ? "text-red-400"
                                        : vote.status === "failed"
                                          ? "text-green-400"
                                          : "text-stone-300"
                                }`}
                            >
                                Outcome
                            </h2>
                            <p className="font-pixel text-xs text-stone-400">{vote.notes}</p>
                        </div>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
