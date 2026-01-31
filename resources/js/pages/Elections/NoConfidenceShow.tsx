import { Head, router } from "@inertiajs/react";
import {
    AlertTriangle,
    CheckCircle,
    Clock,
    ScrollText,
    ThumbsDown,
    ThumbsUp,
    Users,
    XCircle,
} from "lucide-react";
import { useState } from "react";
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

export default function NoConfidenceShow({ vote, user_state }: Props) {
    const [voting, setVoting] = useState(false);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: "Dashboard", href: "/dashboard" },
        { title: "Elections", href: "/elections" },
        { title: "No Confidence Votes", href: "/no-confidence" },
        { title: `Vote #${vote.id}`, href: `/no-confidence/${vote.id}` },
    ];

    const handleVote = (voteForRemoval: boolean) => {
        if (!user_state.can_vote || voting) return;
        setVoting(true);
        router.post(
            `/no-confidence/${vote.id}/vote`,
            { vote_for_removal: voteForRemoval },
            {
                preserveScroll: true,
                onFinish: () => setVoting(false),
            },
        );
    };

    const getStatusColor = (status: string) => {
        switch (status) {
            case "open":
                return "bg-yellow-900/50 text-yellow-400";
            case "passed":
                return "bg-red-900/50 text-red-400";
            case "failed":
                return "bg-green-900/50 text-green-400";
            case "closed":
                return "bg-stone-700 text-stone-400";
            default:
                return "bg-stone-700 text-stone-400";
        }
    };

    const getStatusText = (status: string) => {
        switch (status) {
            case "passed":
                return "Removed from Office";
            case "failed":
                return "Retained Position";
            default:
                return status;
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`No Confidence Vote - ${vote.target_player.username}`} />
            <div className="flex h-full flex-1 flex-col overflow-auto p-4">
                {/* Header */}
                <div className="mb-6 flex items-center gap-3">
                    <div className="rounded-lg bg-red-900/30 p-3">
                        <AlertTriangle className="h-8 w-8 text-red-400" />
                    </div>
                    <div className="flex-1">
                        <h1 className="font-pixel text-2xl text-red-400">No Confidence Vote</h1>
                        <div className="flex items-center gap-2 text-stone-400">
                            <span className="font-pixel text-xs">
                                Against{" "}
                                <span className="text-red-300">{vote.target_player.username}</span>{" "}
                                (<span className="capitalize">{vote.target_role}</span>)
                            </span>
                            <span
                                className={`rounded px-2 py-0.5 font-pixel text-[10px] capitalize ${getStatusColor(vote.status)}`}
                            >
                                {getStatusText(vote.status)}
                            </span>
                        </div>
                    </div>
                </div>

                <div className="mx-auto w-full max-w-4xl">
                    <div className="grid gap-4 lg:grid-cols-3">
                        {/* Vote Info */}
                        <div className="rounded-xl border-2 border-stone-700 bg-stone-800/50 p-4">
                            <h2 className="mb-4 flex items-center gap-2 font-pixel text-sm text-stone-300">
                                <ScrollText className="h-4 w-4 text-red-400" />
                                Vote Details
                            </h2>
                            <div className="space-y-3">
                                <div className="flex justify-between">
                                    <span className="font-pixel text-[10px] text-stone-500">
                                        Target
                                    </span>
                                    <span className="font-pixel text-xs text-red-400">
                                        {vote.target_player.username}
                                    </span>
                                </div>
                                <div className="flex justify-between">
                                    <span className="font-pixel text-[10px] text-stone-500">
                                        Role
                                    </span>
                                    <span className="font-pixel text-xs text-stone-300 capitalize">
                                        {vote.target_role}
                                    </span>
                                </div>
                                <div className="flex justify-between">
                                    <span className="font-pixel text-[10px] text-stone-500">
                                        Location
                                    </span>
                                    <span className="font-pixel text-xs text-stone-300">
                                        {vote.domain_name}
                                    </span>
                                </div>
                                <div className="flex justify-between">
                                    <span className="font-pixel text-[10px] text-stone-500">
                                        Domain
                                    </span>
                                    <span className="font-pixel text-xs text-stone-300 capitalize">
                                        {vote.domain_type}
                                    </span>
                                </div>
                                {vote.voting_ends_at && (
                                    <div className="flex justify-between">
                                        <span className="font-pixel text-[10px] text-stone-500">
                                            Voting Ends
                                        </span>
                                        <span className="font-pixel text-xs text-stone-300">
                                            {new Date(vote.voting_ends_at).toLocaleString()}
                                        </span>
                                    </div>
                                )}
                                {vote.initiated_by && (
                                    <div className="flex justify-between">
                                        <span className="font-pixel text-[10px] text-stone-500">
                                            Initiated By
                                        </span>
                                        <span className="font-pixel text-xs text-stone-300">
                                            {vote.initiated_by.username}
                                        </span>
                                    </div>
                                )}
                            </div>
                        </div>

                        {/* Voting Progress */}
                        <div className="rounded-xl border-2 border-stone-700 bg-stone-800/50 p-4">
                            <h2 className="mb-4 flex items-center gap-2 font-pixel text-sm text-stone-300">
                                <Users className="h-4 w-4 text-blue-400" />
                                Voting Progress
                            </h2>
                            <div className="space-y-4">
                                {/* Vote Bar */}
                                <div>
                                    <div className="mb-2 flex justify-between font-pixel text-[10px]">
                                        <span className="flex items-center gap-1 text-red-400">
                                            <ThumbsUp className="h-3 w-3" /> For Removal (
                                            {vote.votes_for})
                                        </span>
                                        <span className="flex items-center gap-1 text-green-400">
                                            Against ({vote.votes_against}){" "}
                                            <ThumbsDown className="h-3 w-3" />
                                        </span>
                                    </div>
                                    <div className="flex h-4 overflow-hidden rounded-full bg-stone-700">
                                        {vote.votes_cast > 0 && (
                                            <>
                                                <div
                                                    className="bg-red-500 transition-all"
                                                    style={{ width: `${vote.percentage_for}%` }}
                                                />
                                                <div
                                                    className="bg-green-500 transition-all"
                                                    style={{ width: `${vote.percentage_against}%` }}
                                                />
                                            </>
                                        )}
                                    </div>
                                    <div className="mt-1 flex justify-between font-pixel text-[10px] text-stone-500">
                                        <span>{vote.percentage_for}%</span>
                                        <span>{vote.percentage_against}%</span>
                                    </div>
                                </div>

                                {/* Quorum */}
                                <div className="flex items-center justify-between rounded-lg border border-stone-700 bg-stone-800/30 p-2">
                                    <span className="font-pixel text-[10px] text-stone-500">
                                        Quorum
                                    </span>
                                    {vote.quorum_met ? (
                                        <span className="flex items-center gap-1 font-pixel text-xs text-green-400">
                                            <CheckCircle className="h-3 w-3" /> Met (
                                            {vote.votes_cast}/{vote.quorum_required})
                                        </span>
                                    ) : (
                                        <span className="flex items-center gap-1 font-pixel text-xs text-stone-400">
                                            <XCircle className="h-3 w-3" />{" "}
                                            {vote.quorum_required - vote.votes_cast} more needed
                                        </span>
                                    )}
                                </div>

                                <div className="flex items-center justify-between rounded-lg border border-stone-700 bg-stone-800/30 p-2">
                                    <span className="font-pixel text-[10px] text-stone-500">
                                        Eligible Voters
                                    </span>
                                    <span className="font-pixel text-xs text-stone-300">
                                        {vote.eligible_voters}
                                    </span>
                                </div>

                                {vote.is_open && (
                                    <div className="flex items-center gap-2 rounded-lg border border-yellow-600/50 bg-yellow-900/20 p-2">
                                        <Clock className="h-4 w-4 text-yellow-400" />
                                        <span className="font-pixel text-xs text-yellow-400">
                                            Voting Open
                                        </span>
                                    </div>
                                )}
                            </div>
                        </div>

                        {/* Your Status & Voting */}
                        <div className="rounded-xl border-2 border-stone-700 bg-stone-800/50 p-4">
                            <h2 className="mb-4 flex items-center gap-2 font-pixel text-sm text-stone-300">
                                <AlertTriangle className="h-4 w-4 text-yellow-400" />
                                Your Status
                            </h2>
                            <div className="space-y-3">
                                <div className="flex items-center justify-between rounded-lg border border-stone-700 bg-stone-800/30 p-2">
                                    <span className="font-pixel text-[10px] text-stone-500">
                                        Eligible
                                    </span>
                                    {user_state.is_eligible ? (
                                        <span className="font-pixel text-xs text-green-400">
                                            Yes
                                        </span>
                                    ) : (
                                        <span className="font-pixel text-xs text-red-400">No</span>
                                    )}
                                </div>
                                <div className="flex items-center justify-between rounded-lg border border-stone-700 bg-stone-800/30 p-2">
                                    <span className="font-pixel text-[10px] text-stone-500">
                                        Voted
                                    </span>
                                    {user_state.has_voted ? (
                                        <span className="font-pixel text-xs text-green-400">
                                            Yes
                                        </span>
                                    ) : (
                                        <span className="font-pixel text-xs text-stone-400">
                                            Not yet
                                        </span>
                                    )}
                                </div>

                                {user_state.is_target && (
                                    <div className="rounded-lg border border-red-600/50 bg-red-900/20 p-2 text-center">
                                        <span className="font-pixel text-xs text-red-400">
                                            You are the target of this vote
                                        </span>
                                    </div>
                                )}

                                {user_state.is_initiator && (
                                    <div className="rounded-lg border border-yellow-600/50 bg-yellow-900/20 p-2 text-center">
                                        <span className="font-pixel text-xs text-yellow-400">
                                            You initiated this vote
                                        </span>
                                    </div>
                                )}

                                {/* Voting Buttons */}
                                {user_state.can_vote && (
                                    <div className="space-y-2 pt-3">
                                        <div className="font-pixel text-[10px] text-stone-400 text-center mb-2">
                                            Cast your vote:
                                        </div>
                                        <button
                                            onClick={() => handleVote(true)}
                                            disabled={voting}
                                            className="w-full flex items-center justify-center gap-2 rounded-lg bg-red-600 py-3 font-pixel text-xs text-white transition hover:bg-red-500 disabled:opacity-50"
                                        >
                                            <ThumbsUp className="h-4 w-4" />
                                            {voting ? "Voting..." : "Vote for Removal"}
                                        </button>
                                        <button
                                            onClick={() => handleVote(false)}
                                            disabled={voting}
                                            className="w-full flex items-center justify-center gap-2 rounded-lg bg-green-600 py-3 font-pixel text-xs text-white transition hover:bg-green-500 disabled:opacity-50"
                                        >
                                            <ThumbsDown className="h-4 w-4" />
                                            {voting ? "Voting..." : "Vote to Keep"}
                                        </button>
                                    </div>
                                )}

                                {user_state.has_voted && (
                                    <div className="rounded-lg border border-stone-600 bg-stone-800/50 p-2 text-center">
                                        <span className="font-pixel text-xs text-stone-400">
                                            You have already voted
                                        </span>
                                    </div>
                                )}
                            </div>
                        </div>
                    </div>

                    {/* Reason */}
                    {vote.reason && (
                        <div className="mt-4 rounded-xl border-2 border-stone-700 bg-stone-800/50 p-4">
                            <h2 className="mb-2 font-pixel text-sm text-stone-300">
                                Reason for Vote
                            </h2>
                            <p className="font-pixel text-xs text-stone-400">"{vote.reason}"</p>
                        </div>
                    )}

                    {/* Outcome Notes */}
                    {vote.notes && (
                        <div
                            className={`mt-4 rounded-xl border-2 p-4 ${
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
