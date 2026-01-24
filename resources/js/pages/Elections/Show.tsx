import { Head, router } from '@inertiajs/react';
import { CheckCircle, Clock, Crown, ScrollText, Users, Vote, XCircle } from 'lucide-react';
import { useState } from 'react';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

interface Candidate {
    id: number;
    user_id: number;
    username: string;
    platform: string | null;
    vote_count: number;
    is_active: boolean;
    declared_at: string;
}

interface Election {
    id: number;
    election_type: string;
    role: string | null;
    domain_type: string;
    domain_id: number;
    domain_name: string;
    status: string;
    voting_starts_at: string | null;
    voting_ends_at: string | null;
    finalized_at: string | null;
    votes_cast: number;
    quorum_required: number;
    quorum_met: boolean;
    eligible_voters: number;
    is_open: boolean;
    has_ended: boolean;
    winner: { id: number; username: string } | null;
    is_self_appointment: boolean;
    notes: string | null;
    initiated_by: { id: number; username: string } | null;
}

interface UserState {
    has_voted: boolean;
    is_eligible: boolean;
    can_vote: boolean;
    is_candidate: boolean;
}

interface Props {
    election: Election;
    candidates: Candidate[];
    user_state: UserState;
}

export default function ElectionShow({ election, candidates, user_state }: Props) {
    const [voting, setVoting] = useState(false);
    const [declaring, setDeclaring] = useState(false);
    const [platform, setPlatform] = useState('');
    const [showDeclareForm, setShowDeclareForm] = useState(false);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Elections', href: '/elections' },
        { title: `${election.election_type} Election`, href: `/elections/${election.id}` },
    ];

    const handleVote = (candidateId: number) => {
        if (!user_state.can_vote || voting) return;
        setVoting(true);
        router.post(`/elections/${election.id}/vote`, { candidate_id: candidateId }, {
            preserveScroll: true,
            onFinish: () => setVoting(false),
        });
    };

    const handleDeclareCandidacy = () => {
        setDeclaring(true);
        router.post(`/elections/${election.id}/candidacy`, { platform }, {
            preserveScroll: true,
            onFinish: () => {
                setDeclaring(false);
                setShowDeclareForm(false);
                setPlatform('');
            },
        });
    };

    const handleWithdraw = () => {
        router.delete(`/elections/${election.id}/candidacy`, {
            preserveScroll: true,
        });
    };

    const getStatusColor = (status: string) => {
        switch (status) {
            case 'open': return 'bg-green-900/50 text-green-400';
            case 'completed': return 'bg-blue-900/50 text-blue-400';
            case 'failed': return 'bg-red-900/50 text-red-400';
            case 'closed': return 'bg-yellow-900/50 text-yellow-400';
            default: return 'bg-stone-700 text-stone-400';
        }
    };

    const getElectionTitle = () => {
        if (election.election_type === 'mayor') return 'Mayoral Election';
        if (election.election_type === 'king') return 'Royal Election';
        if (election.election_type === 'village_role') return `${election.role} Election`;
        return 'Election';
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`${getElectionTitle()} - ${election.domain_name}`} />
            <div className="flex h-full flex-1 flex-col overflow-auto p-4">
                {/* Header */}
                <div className="mb-6 flex items-center gap-3">
                    <div className="rounded-lg bg-purple-900/30 p-3">
                        <Vote className="h-8 w-8 text-purple-400" />
                    </div>
                    <div className="flex-1">
                        <h1 className="font-pixel text-2xl text-purple-400">{getElectionTitle()}</h1>
                        <div className="flex items-center gap-2 text-stone-400">
                            <span className="font-pixel text-xs">{election.domain_name}</span>
                            <span className={`rounded px-2 py-0.5 font-pixel text-[10px] ${getStatusColor(election.status)}`}>
                                {election.status}
                            </span>
                        </div>
                    </div>
                    {election.winner && (
                        <div className="flex items-center gap-2 rounded-lg border border-amber-600/50 bg-amber-900/20 px-3 py-2">
                            <Crown className="h-5 w-5 text-amber-400" />
                            <div>
                                <div className="font-pixel text-[10px] text-stone-500">Winner</div>
                                <div className="font-pixel text-sm text-amber-400">{election.winner.username}</div>
                            </div>
                        </div>
                    )}
                </div>

                <div className="mx-auto w-full max-w-4xl">
                    <div className="grid gap-4 lg:grid-cols-3">
                        {/* Election Info */}
                        <div className="rounded-xl border-2 border-stone-700 bg-stone-800/50 p-4">
                            <h2 className="mb-4 flex items-center gap-2 font-pixel text-sm text-stone-300">
                                <ScrollText className="h-4 w-4 text-purple-400" />
                                Election Details
                            </h2>
                            <div className="space-y-3">
                                <div className="flex justify-between">
                                    <span className="font-pixel text-[10px] text-stone-500">Type</span>
                                    <span className="font-pixel text-xs text-stone-300 capitalize">{election.election_type}</span>
                                </div>
                                {election.role && (
                                    <div className="flex justify-between">
                                        <span className="font-pixel text-[10px] text-stone-500">Position</span>
                                        <span className="font-pixel text-xs text-stone-300 capitalize">{election.role}</span>
                                    </div>
                                )}
                                <div className="flex justify-between">
                                    <span className="font-pixel text-[10px] text-stone-500">Domain</span>
                                    <span className="font-pixel text-xs text-stone-300 capitalize">{election.domain_type}</span>
                                </div>
                                {election.voting_ends_at && (
                                    <div className="flex justify-between">
                                        <span className="font-pixel text-[10px] text-stone-500">Voting Ends</span>
                                        <span className="font-pixel text-xs text-stone-300">
                                            {new Date(election.voting_ends_at).toLocaleString()}
                                        </span>
                                    </div>
                                )}
                                {election.initiated_by && (
                                    <div className="flex justify-between">
                                        <span className="font-pixel text-[10px] text-stone-500">Started By</span>
                                        <span className="font-pixel text-xs text-stone-300">{election.initiated_by.username}</span>
                                    </div>
                                )}
                                {election.is_self_appointment && (
                                    <div className="rounded bg-amber-900/30 px-2 py-1 text-center font-pixel text-[10px] text-amber-400">
                                        Self-Appointment (Small Village)
                                    </div>
                                )}
                            </div>
                        </div>

                        {/* Voting Stats */}
                        <div className="rounded-xl border-2 border-stone-700 bg-stone-800/50 p-4">
                            <h2 className="mb-4 flex items-center gap-2 font-pixel text-sm text-stone-300">
                                <Users className="h-4 w-4 text-blue-400" />
                                Voting Progress
                            </h2>
                            <div className="space-y-4">
                                <div>
                                    <div className="mb-1 flex justify-between font-pixel text-[10px]">
                                        <span className="text-stone-500">Votes Cast</span>
                                        <span className="text-stone-300">{election.votes_cast} / {election.quorum_required}</span>
                                    </div>
                                    <div className="h-2 overflow-hidden rounded-full bg-stone-700">
                                        <div
                                            className={`h-full transition-all ${election.quorum_met ? 'bg-green-500' : 'bg-purple-500'}`}
                                            style={{ width: `${Math.min(100, (election.votes_cast / election.quorum_required) * 100)}%` }}
                                        />
                                    </div>
                                </div>
                                <div className="flex items-center justify-between rounded-lg border border-stone-700 bg-stone-800/30 p-2">
                                    <span className="font-pixel text-[10px] text-stone-500">Quorum</span>
                                    {election.quorum_met ? (
                                        <span className="flex items-center gap-1 font-pixel text-xs text-green-400">
                                            <CheckCircle className="h-3 w-3" /> Met
                                        </span>
                                    ) : (
                                        <span className="flex items-center gap-1 font-pixel text-xs text-stone-400">
                                            <XCircle className="h-3 w-3" /> {election.quorum_required - election.votes_cast} more needed
                                        </span>
                                    )}
                                </div>
                                <div className="flex items-center justify-between rounded-lg border border-stone-700 bg-stone-800/30 p-2">
                                    <span className="font-pixel text-[10px] text-stone-500">Eligible Voters</span>
                                    <span className="font-pixel text-xs text-stone-300">{election.eligible_voters}</span>
                                </div>
                                {election.is_open && (
                                    <div className="flex items-center gap-2 rounded-lg border border-green-600/50 bg-green-900/20 p-2">
                                        <Clock className="h-4 w-4 text-green-400" />
                                        <span className="font-pixel text-xs text-green-400">Voting Open</span>
                                    </div>
                                )}
                            </div>
                        </div>

                        {/* Your Status */}
                        <div className="rounded-xl border-2 border-stone-700 bg-stone-800/50 p-4">
                            <h2 className="mb-4 flex items-center gap-2 font-pixel text-sm text-stone-300">
                                <Vote className="h-4 w-4 text-green-400" />
                                Your Status
                            </h2>
                            <div className="space-y-3">
                                <div className="flex items-center justify-between rounded-lg border border-stone-700 bg-stone-800/30 p-2">
                                    <span className="font-pixel text-[10px] text-stone-500">Eligible</span>
                                    {user_state.is_eligible ? (
                                        <span className="font-pixel text-xs text-green-400">Yes</span>
                                    ) : (
                                        <span className="font-pixel text-xs text-red-400">No</span>
                                    )}
                                </div>
                                <div className="flex items-center justify-between rounded-lg border border-stone-700 bg-stone-800/30 p-2">
                                    <span className="font-pixel text-[10px] text-stone-500">Voted</span>
                                    {user_state.has_voted ? (
                                        <span className="font-pixel text-xs text-green-400">Yes</span>
                                    ) : (
                                        <span className="font-pixel text-xs text-stone-400">Not yet</span>
                                    )}
                                </div>
                                <div className="flex items-center justify-between rounded-lg border border-stone-700 bg-stone-800/30 p-2">
                                    <span className="font-pixel text-[10px] text-stone-500">Candidate</span>
                                    {user_state.is_candidate ? (
                                        <span className="font-pixel text-xs text-purple-400">Running</span>
                                    ) : (
                                        <span className="font-pixel text-xs text-stone-400">No</span>
                                    )}
                                </div>

                                {election.is_open && user_state.is_eligible && !user_state.is_candidate && (
                                    <div className="pt-2">
                                        {showDeclareForm ? (
                                            <div className="space-y-2">
                                                <textarea
                                                    value={platform}
                                                    onChange={(e) => setPlatform(e.target.value)}
                                                    placeholder="Your campaign platform (optional)"
                                                    className="w-full rounded-lg border border-stone-600 bg-stone-900 p-2 font-pixel text-xs text-stone-200 placeholder:text-stone-600"
                                                    rows={3}
                                                />
                                                <div className="flex gap-2">
                                                    <button
                                                        onClick={handleDeclareCandidacy}
                                                        disabled={declaring}
                                                        className="flex-1 rounded-lg bg-purple-600 py-2 font-pixel text-xs text-white transition hover:bg-purple-500 disabled:opacity-50"
                                                    >
                                                        {declaring ? 'Declaring...' : 'Confirm'}
                                                    </button>
                                                    <button
                                                        onClick={() => setShowDeclareForm(false)}
                                                        className="rounded-lg border border-stone-600 px-3 py-2 font-pixel text-xs text-stone-400 transition hover:bg-stone-700"
                                                    >
                                                        Cancel
                                                    </button>
                                                </div>
                                            </div>
                                        ) : (
                                            <button
                                                onClick={() => setShowDeclareForm(true)}
                                                className="w-full rounded-lg border-2 border-purple-600/50 bg-purple-900/20 py-2 font-pixel text-xs text-purple-400 transition hover:bg-purple-900/40"
                                            >
                                                Declare Candidacy
                                            </button>
                                        )}
                                    </div>
                                )}

                                {election.is_open && user_state.is_candidate && (
                                    <button
                                        onClick={handleWithdraw}
                                        className="w-full rounded-lg border border-red-600/50 bg-red-900/20 py-2 font-pixel text-xs text-red-400 transition hover:bg-red-900/40"
                                    >
                                        Withdraw Candidacy
                                    </button>
                                )}
                            </div>
                        </div>
                    </div>

                    {/* Candidates */}
                    <div className="mt-4 rounded-xl border-2 border-stone-700 bg-stone-800/50 p-4">
                        <h2 className="mb-4 flex items-center gap-2 font-pixel text-sm text-stone-300">
                            <Crown className="h-4 w-4 text-amber-400" />
                            Candidates ({candidates.filter(c => c.is_active).length})
                        </h2>

                        {candidates.filter(c => c.is_active).length === 0 ? (
                            <div className="py-8 text-center">
                                <Users className="mx-auto mb-2 h-8 w-8 text-stone-600" />
                                <div className="font-pixel text-xs text-stone-500">No candidates yet</div>
                                <div className="font-pixel text-[10px] text-stone-600">Be the first to declare!</div>
                            </div>
                        ) : (
                            <div className="space-y-3">
                                {candidates
                                    .filter(c => c.is_active)
                                    .sort((a, b) => b.vote_count - a.vote_count)
                                    .map((candidate, index) => (
                                        <div
                                            key={candidate.id}
                                            className={`rounded-lg border p-3 ${
                                                election.winner?.id === candidate.user_id
                                                    ? 'border-amber-600/50 bg-amber-900/20'
                                                    : 'border-stone-700 bg-stone-800/30'
                                            }`}
                                        >
                                            <div className="flex items-center justify-between">
                                                <div className="flex items-center gap-3">
                                                    <div className={`flex h-8 w-8 items-center justify-center rounded-full font-pixel text-sm ${
                                                        index === 0 ? 'bg-amber-900/50 text-amber-400' :
                                                        index === 1 ? 'bg-stone-600 text-stone-300' :
                                                        index === 2 ? 'bg-amber-800/50 text-amber-600' :
                                                        'bg-stone-700 text-stone-400'
                                                    }`}>
                                                        {index + 1}
                                                    </div>
                                                    <div>
                                                        <div className="flex items-center gap-2">
                                                            <span className="font-pixel text-sm text-stone-200">{candidate.username}</span>
                                                            {election.winner?.id === candidate.user_id && (
                                                                <Crown className="h-4 w-4 text-amber-400" />
                                                            )}
                                                        </div>
                                                        {candidate.platform && (
                                                            <div className="mt-1 font-pixel text-[10px] text-stone-500">
                                                                "{candidate.platform}"
                                                            </div>
                                                        )}
                                                    </div>
                                                </div>
                                                <div className="flex items-center gap-3">
                                                    <div className="text-right">
                                                        <div className="font-pixel text-lg text-purple-400">{candidate.vote_count}</div>
                                                        <div className="font-pixel text-[10px] text-stone-500">votes</div>
                                                    </div>
                                                    {user_state.can_vote && (
                                                        <button
                                                            onClick={() => handleVote(candidate.id)}
                                                            disabled={voting}
                                                            className="rounded-lg bg-green-600 px-4 py-2 font-pixel text-xs text-white transition hover:bg-green-500 disabled:opacity-50"
                                                        >
                                                            {voting ? '...' : 'Vote'}
                                                        </button>
                                                    )}
                                                </div>
                                            </div>
                                        </div>
                                    ))}
                            </div>
                        )}
                    </div>

                    {/* Notes */}
                    {election.notes && (
                        <div className="mt-4 rounded-xl border-2 border-stone-700 bg-stone-800/50 p-4">
                            <h2 className="mb-2 font-pixel text-sm text-stone-300">Notes</h2>
                            <p className="font-pixel text-xs text-stone-400">{election.notes}</p>
                        </div>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
