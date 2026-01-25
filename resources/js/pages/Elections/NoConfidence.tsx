import { Head, Link } from '@inertiajs/react';
import { AlertTriangle, Clock, ThumbsDown, ThumbsUp } from 'lucide-react';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

interface NoConfidenceVote {
    id: number;
    target_role: string;
    target_player: { id: number; username: string };
    domain_type: string;
    domain_name: string;
    status: string;
    voting_starts_at: string | null;
    voting_ends_at: string | null;
    votes_for: number;
    votes_against: number;
    votes_cast: number;
    quorum_required: number;
    quorum_met: boolean;
    reason: string | null;
    initiated_by: { id: number; username: string } | null;
}

interface Props {
    votes: {
        data: NoConfidenceVote[];
        links: { url: string | null; label: string; active: boolean }[];
    };
}

export default function NoConfidenceIndex({ votes }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Elections', href: '/elections' },
        { title: 'No Confidence Votes', href: '/no-confidence' },
    ];

    const getStatusColor = (status: string) => {
        switch (status) {
            case 'open': return 'bg-yellow-900/50 text-yellow-400';
            case 'passed': return 'bg-red-900/50 text-red-400';
            case 'failed': return 'bg-green-900/50 text-green-400';
            case 'closed': return 'bg-stone-700 text-stone-400';
            default: return 'bg-stone-700 text-stone-400';
        }
    };

    const getStatusIcon = (status: string) => {
        switch (status) {
            case 'open': return <Clock className="h-4 w-4" />;
            case 'passed': return <ThumbsDown className="h-4 w-4" />;
            case 'failed': return <ThumbsUp className="h-4 w-4" />;
            default: return <AlertTriangle className="h-4 w-4" />;
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="No Confidence Votes" />
            <div className="flex h-full flex-1 flex-col overflow-auto p-4">
                {/* Header */}
                <div className="mb-6 flex items-center gap-3">
                    <div className="rounded-lg bg-red-900/30 p-3">
                        <AlertTriangle className="h-8 w-8 text-red-400" />
                    </div>
                    <div>
                        <h1 className="font-pixel text-2xl text-red-400">No Confidence Votes</h1>
                        <p className="font-pixel text-xs text-stone-500">Challenge role holders in your domain</p>
                    </div>
                </div>

                <div className="mx-auto w-full max-w-4xl">
                    {votes.data.length === 0 ? (
                        <div className="rounded-xl border-2 border-stone-700 bg-stone-800/50 p-8 text-center">
                            <AlertTriangle className="mx-auto mb-2 h-12 w-12 text-stone-600" />
                            <div className="font-pixel text-sm text-stone-400">No active votes</div>
                            <div className="font-pixel text-xs text-stone-600">All leaders maintain the trust of their people.</div>
                        </div>
                    ) : (
                        <div className="space-y-3">
                            {votes.data.map((vote) => (
                                <Link
                                    key={vote.id}
                                    href={`/no-confidence/${vote.id}`}
                                    className="block rounded-xl border-2 border-stone-700 bg-stone-800/50 p-4 transition hover:border-red-600/50 hover:bg-stone-800"
                                >
                                    <div className="flex items-center justify-between">
                                        <div className="flex items-center gap-3">
                                            <div className="rounded-lg bg-red-900/30 p-2">
                                                <AlertTriangle className="h-5 w-5 text-red-400" />
                                            </div>
                                            <div>
                                                <div className="flex items-center gap-2">
                                                    <span className="font-pixel text-sm text-stone-200">
                                                        Vote against{' '}
                                                        <span className="text-red-400">{vote.target_player.username}</span>
                                                    </span>
                                                    <span className="font-pixel text-xs text-stone-500 capitalize">
                                                        ({vote.target_role})
                                                    </span>
                                                </div>
                                                <div className="mt-1 flex items-center gap-3 font-pixel text-[10px] text-stone-500">
                                                    <span>{vote.domain_name}</span>
                                                    <span className="capitalize">{vote.domain_type}</span>
                                                    <span className={`flex items-center gap-1 rounded px-2 py-0.5 ${getStatusColor(vote.status)}`}>
                                                        {getStatusIcon(vote.status)}
                                                        {vote.status}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                        <div className="text-right">
                                            <div className="flex items-center gap-3">
                                                <div className="flex items-center gap-1">
                                                    <ThumbsUp className="h-3 w-3 text-red-400" />
                                                    <span className="font-pixel text-xs text-red-400">{vote.votes_for}</span>
                                                </div>
                                                <div className="flex items-center gap-1">
                                                    <ThumbsDown className="h-3 w-3 text-green-400" />
                                                    <span className="font-pixel text-xs text-green-400">{vote.votes_against}</span>
                                                </div>
                                            </div>
                                            <div className="mt-1 font-pixel text-[10px] text-stone-500">
                                                {vote.votes_cast}/{vote.quorum_required} votes
                                            </div>
                                        </div>
                                    </div>
                                    {vote.reason && (
                                        <div className="mt-2 rounded bg-stone-900/50 p-2">
                                            <span className="font-pixel text-[10px] text-stone-400">
                                                Reason: "{vote.reason}"
                                            </span>
                                        </div>
                                    )}
                                </Link>
                            ))}
                        </div>
                    )}

                    {/* Pagination */}
                    {votes.links.length > 3 && (
                        <div className="mt-4 flex justify-center gap-1">
                            {votes.links.map((link, index) => (
                                <Link
                                    key={index}
                                    href={link.url || '#'}
                                    className={`rounded px-3 py-1 font-pixel text-xs ${
                                        link.active
                                            ? 'bg-red-600 text-white'
                                            : link.url
                                            ? 'bg-stone-700 text-stone-300 hover:bg-stone-600'
                                            : 'bg-stone-800 text-stone-600 cursor-not-allowed'
                                    }`}
                                    dangerouslySetInnerHTML={{ __html: link.label }}
                                />
                            ))}
                        </div>
                    )}

                    {/* Info Box */}
                    <div className="mt-6 rounded-xl border-2 border-stone-700 bg-stone-800/50 p-4">
                        <h2 className="mb-3 font-pixel text-sm text-stone-300">How No Confidence Votes Work</h2>
                        <ul className="space-y-2 font-pixel text-[10px] text-stone-400">
                            <li className="flex items-start gap-2">
                                <span className="text-red-400">1.</span>
                                Any resident can initiate a vote against a role holder in their domain.
                            </li>
                            <li className="flex items-start gap-2">
                                <span className="text-red-400">2.</span>
                                Voting lasts 48 hours. A majority of voters must participate for the vote to count.
                            </li>
                            <li className="flex items-start gap-2">
                                <span className="text-red-400">3.</span>
                                If more people vote for removal than against, the role holder loses their position.
                            </li>
                            <li className="flex items-start gap-2">
                                <span className="text-red-400">4.</span>
                                The role becomes vacant - a new election or appointment can fill the position.
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
