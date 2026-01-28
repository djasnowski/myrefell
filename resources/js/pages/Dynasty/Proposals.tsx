import { Head, Link, router } from '@inertiajs/react';
import {
    ArrowLeft,
    Calendar,
    Check,
    Clock,
    Coins,
    Heart,
    HeartOff,
    History,
    Mail,
    MailOpen,
    Send,
    User,
    X,
} from 'lucide-react';
import { useState } from 'react';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

interface DynastyRef {
    id: number;
    name: string;
}

interface MemberInfo {
    id: number;
    name: string;
    age: number | null;
    gender: string;
    dynasty_name: string | null;
}

interface Proposal {
    id: number;
    status: string;
    direction: 'incoming' | 'outgoing';
    proposer: MemberInfo;
    proposed: MemberInfo;
    proposer_dynasty: DynastyRef | null;
    proposed_dynasty: DynastyRef | null;
    offered_dowry: number;
    message: string | null;
    response_message: string | null;
    created_at: string;
    responded_at: string | null;
    expires_at: string | null;
    can_respond: boolean;
}

interface Marriage {
    id: number;
    status: string;
    marriage_type: string;
    spouse1: MemberInfo;
    spouse2: MemberInfo;
    wedding_date: string | null;
    wedding_year: string | null;
    end_date: string | null;
    end_reason: string | null;
    duration: number | null;
}

interface Props {
    has_dynasty: boolean;
    dynasty_name?: string;
    incoming: Proposal[];
    outgoing: Proposal[];
    marriages: Marriage[];
    can_propose: boolean;
    is_head?: boolean;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Dynasty', href: '/dynasty' },
    { title: 'Marriage Proposals', href: '/dynasty/proposals' },
];

const statusColors: Record<string, { bg: string; text: string; label: string }> = {
    pending: { bg: 'bg-blue-900/30', text: 'text-blue-400', label: 'Pending' },
    accepted: { bg: 'bg-green-900/30', text: 'text-green-400', label: 'Accepted' },
    rejected: { bg: 'bg-red-900/30', text: 'text-red-400', label: 'Rejected' },
    withdrawn: { bg: 'bg-stone-900/30', text: 'text-stone-500', label: 'Withdrawn' },
    expired: { bg: 'bg-stone-900/30', text: 'text-stone-500', label: 'Expired' },
};

const marriageStatusColors: Record<string, { bg: string; text: string; label: string }> = {
    active: { bg: 'bg-green-900/30', text: 'text-green-400', label: 'Active' },
    divorced: { bg: 'bg-red-900/30', text: 'text-red-400', label: 'Divorced' },
    annulled: { bg: 'bg-stone-900/30', text: 'text-stone-500', label: 'Annulled' },
    widowed: { bg: 'bg-purple-900/30', text: 'text-purple-400', label: 'Widowed' },
};

export default function Proposals({
    has_dynasty,
    dynasty_name,
    incoming,
    outgoing,
    marriages,
    can_propose,
    is_head,
}: Props) {
    const [processing, setProcessing] = useState<number | null>(null);
    const [error, setError] = useState<string | null>(null);
    const [success, setSuccess] = useState<string | null>(null);

    const handleAccept = async (proposalId: number) => {
        setProcessing(proposalId);
        setError(null);

        router.post(`/dynasty/proposals/${proposalId}/accept`, {}, {
            preserveScroll: true,
            onSuccess: () => {
                setSuccess('Proposal accepted! The wedding has taken place.');
                router.reload();
            },
            onError: (errors) => {
                setError(Object.values(errors).flat().join(', ') || 'Failed to accept proposal');
            },
            onFinish: () => setProcessing(null),
        });
    };

    const handleReject = async (proposalId: number) => {
        setProcessing(proposalId);
        setError(null);

        router.post(`/dynasty/proposals/${proposalId}/reject`, {}, {
            preserveScroll: true,
            onSuccess: () => {
                setSuccess('Proposal rejected.');
                router.reload();
            },
            onError: (errors) => {
                setError(Object.values(errors).flat().join(', ') || 'Failed to reject proposal');
            },
            onFinish: () => setProcessing(null),
        });
    };

    const handleWithdraw = async (proposalId: number) => {
        setProcessing(proposalId);
        setError(null);

        router.post(`/dynasty/proposals/${proposalId}/withdraw`, {}, {
            preserveScroll: true,
            onSuccess: () => {
                setSuccess('Proposal withdrawn.');
                router.reload();
            },
            onError: (errors) => {
                setError(Object.values(errors).flat().join(', ') || 'Failed to withdraw proposal');
            },
            onFinish: () => setProcessing(null),
        });
    };

    // No dynasty state
    if (!has_dynasty) {
        return (
            <AppLayout breadcrumbs={breadcrumbs}>
                <Head title="Marriage Proposals" />
                <div className="flex h-full flex-1 flex-col overflow-auto p-4">
                    <div className="w-full">
                        <div className="rounded-xl border-2 border-stone-700 bg-stone-800/50 p-6 text-center">
                            <Heart className="mx-auto mb-4 h-12 w-12 text-stone-600" />
                            <h2 className="mb-2 font-pixel text-lg text-stone-300">No Dynasty</h2>
                            <p className="mb-4 font-pixel text-xs text-stone-500">
                                You must found a dynasty before you can manage marriage proposals.
                            </p>
                            <Link
                                href="/dynasty"
                                className="inline-block rounded border-2 border-amber-600/50 bg-amber-900/20 px-4 py-2 font-pixel text-sm text-amber-400 transition hover:bg-amber-900/40"
                            >
                                Go to Dynasty
                            </Link>
                        </div>
                    </div>
                </div>
            </AppLayout>
        );
    }

    const pendingIncoming = incoming.filter(p => p.status === 'pending');
    const pendingOutgoing = outgoing.filter(p => p.status === 'pending');

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Marriage Proposals" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-y-auto p-4">
                {/* Back Link */}
                <Link
                    href="/dynasty"
                    className="flex items-center gap-2 font-pixel text-sm text-stone-400 transition hover:text-stone-300"
                >
                    <ArrowLeft className="h-4 w-4" />
                    Back to Dynasty
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
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-3">
                        <div className="rounded-lg bg-pink-900/30 p-3">
                            <Heart className="h-6 w-6 text-pink-400" />
                        </div>
                        <div>
                            <h1 className="font-pixel text-xl text-pink-400">Marriage Proposals</h1>
                            <p className="font-pixel text-xs text-stone-500">
                            House {dynasty_name} {is_head && '— Head of House'}
                        </p>
                        </div>
                    </div>
                    {can_propose && (
                        <Link
                            href="/dynasty/proposals/create"
                            className="flex items-center gap-2 rounded border-2 border-pink-600/50 bg-pink-900/20 px-4 py-2 font-pixel text-sm text-pink-400 transition hover:bg-pink-900/40"
                        >
                            <Send className="h-4 w-4" />
                            Make New Proposal
                        </Link>
                    )}
                </div>

                <div className="mx-auto w-full max-w-4xl space-y-6">
                    {/* Incoming Proposals */}
                    <div className="rounded-xl border-2 border-blue-600/50 bg-blue-900/20 p-4">
                        <h2 className="mb-4 flex items-center gap-2 font-pixel text-sm text-blue-400">
                            <Mail className="h-4 w-4" />
                            Incoming Proposals ({pendingIncoming.length})
                        </h2>

                        {pendingIncoming.length === 0 ? (
                            <div className="py-6 text-center">
                                <MailOpen className="mx-auto mb-2 h-8 w-8 text-stone-600" />
                                <p className="font-pixel text-xs text-stone-500">No pending incoming proposals</p>
                            </div>
                        ) : (
                            <div className="space-y-3">
                                {pendingIncoming.map((proposal) => (
                                    <div
                                        key={proposal.id}
                                        className="rounded-lg border border-stone-700 bg-stone-800/50 p-4"
                                    >
                                        <div className="flex flex-wrap items-start justify-between gap-4">
                                            <div className="flex-1">
                                                <div className="mb-2 flex items-center gap-2">
                                                    <span className="font-pixel text-xs text-stone-400">From:</span>
                                                    <span className="font-pixel text-sm text-amber-400">
                                                        House {proposal.proposer_dynasty?.name || 'Unknown'}
                                                    </span>
                                                    <span className="font-pixel text-[10px] text-stone-500">
                                                        {proposal.created_at}
                                                    </span>
                                                </div>

                                                <div className="mb-3 flex items-center gap-2 font-pixel text-xs">
                                                    <span className="text-stone-400">Proposed Match:</span>
                                                    <span className="text-pink-400">{proposal.proposer.name}</span>
                                                    <Heart className="h-3 w-3 text-pink-400" />
                                                    <span className="text-pink-400">{proposal.proposed.name}</span>
                                                </div>

                                                {proposal.offered_dowry > 0 && (
                                                    <div className="mb-2 flex items-center gap-2 font-pixel text-xs">
                                                        <Coins className="h-3 w-3 text-yellow-400" />
                                                        <span className="text-stone-400">Dowry Offered:</span>
                                                        <span className="text-yellow-400">{proposal.offered_dowry}g</span>
                                                    </div>
                                                )}

                                                {proposal.message && (
                                                    <div className="mt-2 rounded bg-stone-900/50 p-2">
                                                        <p className="font-pixel text-[10px] italic text-stone-400">
                                                            "{proposal.message}"
                                                        </p>
                                                    </div>
                                                )}

                                                {proposal.expires_at && (
                                                    <div className="mt-2 flex items-center gap-1 font-pixel text-[10px] text-stone-500">
                                                        <Clock className="h-3 w-3" />
                                                        Expires {proposal.expires_at}
                                                    </div>
                                                )}
                                            </div>

                                            <div className="flex items-center gap-2">
                                                {proposal.can_respond && (
                                                    <>
                                                        <button
                                                            onClick={() => handleAccept(proposal.id)}
                                                            disabled={!is_head || processing === proposal.id}
                                                            className="flex items-center gap-1 rounded border border-green-600/50 bg-green-900/20 px-3 py-1.5 font-pixel text-xs text-green-400 transition hover:bg-green-900/40 disabled:opacity-50"
                                                        >
                                                            <Check className="h-3 w-3" />
                                                            Accept
                                                        </button>
                                                        <button
                                                            onClick={() => handleReject(proposal.id)}
                                                            disabled={!is_head || processing === proposal.id}
                                                            className="flex items-center gap-1 rounded border border-red-600/50 bg-red-900/20 px-3 py-1.5 font-pixel text-xs text-red-400 transition hover:bg-red-900/40 disabled:opacity-50"
                                                        >
                                                            <X className="h-3 w-3" />
                                                            Reject
                                                        </button>
                                                        {!is_head && (
                                                            <p className="font-pixel text-[10px] text-stone-500">
                                                                Only the dynasty head can respond
                                                            </p>
                                                        )}
                                                    </>
                                                )}
                                            </div>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        )}
                    </div>

                    {/* Outgoing Proposals */}
                    <div className="rounded-xl border-2 border-purple-600/50 bg-purple-900/20 p-4">
                        <h2 className="mb-4 flex items-center gap-2 font-pixel text-sm text-purple-400">
                            <Send className="h-4 w-4" />
                            Outgoing Proposals ({pendingOutgoing.length} pending)
                        </h2>

                        {outgoing.length === 0 ? (
                            <div className="py-6 text-center">
                                <Send className="mx-auto mb-2 h-8 w-8 text-stone-600" />
                                <p className="font-pixel text-xs text-stone-500">No outgoing proposals</p>
                            </div>
                        ) : (
                            <div className="space-y-3">
                                {outgoing.map((proposal) => {
                                    const statusStyle = statusColors[proposal.status] || statusColors.pending;
                                    return (
                                        <div
                                            key={proposal.id}
                                            className="rounded-lg border border-stone-700 bg-stone-800/50 p-4"
                                        >
                                            <div className="flex flex-wrap items-start justify-between gap-4">
                                                <div className="flex-1">
                                                    <div className="mb-2 flex items-center gap-2">
                                                        <span className="font-pixel text-xs text-stone-400">To:</span>
                                                        <span className="font-pixel text-sm text-amber-400">
                                                            House {proposal.proposed_dynasty?.name || 'Unknown'}
                                                        </span>
                                                        <span className={`rounded px-2 py-0.5 font-pixel text-[10px] ${statusStyle.bg} ${statusStyle.text}`}>
                                                            {statusStyle.label}
                                                        </span>
                                                    </div>

                                                    <div className="mb-3 flex items-center gap-2 font-pixel text-xs">
                                                        <span className="text-stone-400">Proposed Match:</span>
                                                        <span className="text-pink-400">{proposal.proposer.name}</span>
                                                        <Heart className="h-3 w-3 text-pink-400" />
                                                        <span className="text-pink-400">{proposal.proposed.name}</span>
                                                    </div>

                                                    {proposal.offered_dowry > 0 && (
                                                        <div className="mb-2 flex items-center gap-2 font-pixel text-xs">
                                                            <Coins className="h-3 w-3 text-yellow-400" />
                                                            <span className="text-stone-400">Dowry Offered:</span>
                                                            <span className="text-yellow-400">{proposal.offered_dowry}g</span>
                                                        </div>
                                                    )}

                                                    <div className="flex items-center gap-2 font-pixel text-[10px] text-stone-500">
                                                        <Calendar className="h-3 w-3" />
                                                        Sent {proposal.created_at}
                                                    </div>

                                                    {proposal.responded_at && (
                                                        <div className="mt-1 font-pixel text-[10px] text-stone-500">
                                                            Responded {proposal.responded_at}
                                                        </div>
                                                    )}
                                                </div>

                                                <div className="flex items-center gap-2">
                                                    {proposal.status === 'pending' && (
                                                        <button
                                                            onClick={() => handleWithdraw(proposal.id)}
                                                            disabled={processing === proposal.id}
                                                            className="flex items-center gap-1 rounded border border-stone-600/50 bg-stone-900/20 px-3 py-1.5 font-pixel text-xs text-stone-400 transition hover:bg-stone-900/40 disabled:opacity-50"
                                                        >
                                                            <X className="h-3 w-3" />
                                                            Withdraw
                                                        </button>
                                                    )}
                                                </div>
                                            </div>
                                        </div>
                                    );
                                })}
                            </div>
                        )}
                    </div>

                    {/* Recent Marriages */}
                    <div className="rounded-xl border-2 border-stone-600/50 bg-stone-800/30 p-4">
                        <h2 className="mb-4 flex items-center gap-2 font-pixel text-sm text-stone-300">
                            <History className="h-4 w-4 text-pink-400" />
                            Recent Marriages
                        </h2>

                        {marriages.length === 0 ? (
                            <div className="py-6 text-center">
                                <HeartOff className="mx-auto mb-2 h-8 w-8 text-stone-600" />
                                <p className="font-pixel text-xs text-stone-500">No marriages recorded</p>
                            </div>
                        ) : (
                            <div className="space-y-2">
                                {marriages.map((marriage) => {
                                    const statusStyle = marriageStatusColors[marriage.status] || marriageStatusColors.active;
                                    return (
                                        <div
                                            key={marriage.id}
                                            className="flex items-center justify-between rounded-lg border border-stone-700 bg-stone-900/50 p-3"
                                        >
                                            <div className="flex items-center gap-3">
                                                <div className="flex items-center gap-2 font-pixel text-xs">
                                                    <User className="h-3 w-3 text-blue-400" />
                                                    <span className="text-stone-300">{marriage.spouse1.name}</span>
                                                    <span className="text-stone-600">&</span>
                                                    <User className="h-3 w-3 text-pink-400" />
                                                    <span className="text-stone-300">{marriage.spouse2.name}</span>
                                                </div>
                                            </div>
                                            <div className="flex items-center gap-3">
                                                <span className="font-pixel text-[10px] text-stone-500">
                                                    {marriage.wedding_year ? `Year ${marriage.wedding_year}` : 'Unknown date'}
                                                </span>
                                                <span className={`rounded px-2 py-0.5 font-pixel text-[10px] ${statusStyle.bg} ${statusStyle.text}`}>
                                                    {statusStyle.label}
                                                </span>
                                            </div>
                                        </div>
                                    );
                                })}
                            </div>
                        )}
                    </div>

                    {/* Info Notice */}
                    <div className="rounded-lg border border-amber-600/30 bg-amber-900/10 p-3">
                        <p className="font-pixel text-[10px] text-amber-400/80">
                            Marriage alliances can strengthen ties between dynasties. Breaking alliances may result
                            in prestige loss and damaged relationships. Marriage alliances require divorce or
                            annulment to break (additional prestige cost).
                        </p>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
