import { Head, router, usePage } from '@inertiajs/react';
import { Coins, Loader2, MapPin, Skull, Target, User } from 'lucide-react';
import { useState } from 'react';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

interface Bounty {
    id: number;
    reward_amount: number;
    capture_type: string;
    reason: string;
    status: string;
    expires_at: string | null;
    target: {
        id: number;
        username: string;
    };
    posted_by: {
        id: number;
        username: string;
    } | null;
    crime: {
        crime_type: {
            name: string;
        };
    } | null;
}

interface Pagination {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    data: Bounty[];
}

interface PageProps {
    bounties: Pagination;
    [key: string]: unknown;
}

const captureTypeColors: Record<string, string> = {
    alive: 'text-green-300 bg-green-900/50',
    dead_or_alive: 'text-amber-300 bg-amber-900/50',
    dead: 'text-red-300 bg-red-900/50',
};

const captureTypeDisplay: Record<string, string> = {
    alive: 'Wanted Alive',
    dead_or_alive: 'Dead or Alive',
    dead: 'Wanted Dead',
};

export default function BountyBoard() {
    const { bounties } = usePage<PageProps>().props;
    const [claimingId, setClaimingId] = useState<number | null>(null);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Criminal Record', href: '/crime' },
        { title: 'Bounty Board', href: '#' },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Bounty Board" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-y-auto p-4">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="font-pixel text-2xl text-amber-400">Bounty Board</h1>
                        <p className="font-pixel text-sm text-stone-400">Hunt outlaws for gold</p>
                    </div>
                    <div className="rounded-lg border-2 border-stone-600/50 bg-stone-800/50 px-4 py-2">
                        <span className="font-pixel text-xs text-stone-400">Active Bounties:</span>
                        <span className="ml-2 font-pixel text-sm text-amber-300">{bounties.total}</span>
                    </div>
                </div>

                {/* Bounties Grid */}
                {bounties.data.length > 0 ? (
                    <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                        {bounties.data.map((bounty) => (
                            <div
                                key={bounty.id}
                                className="rounded-xl border-2 border-amber-500/50 bg-amber-900/20 p-4"
                            >
                                {/* Target */}
                                <div className="mb-3 flex items-center justify-between">
                                    <div className="flex items-center gap-2">
                                        <div className="rounded-lg bg-stone-800/50 p-2">
                                            <User className="h-5 w-5 text-red-400" />
                                        </div>
                                        <div>
                                            <h3 className="font-pixel text-sm text-red-300">
                                                {bounty.target.username}
                                            </h3>
                                            <span
                                                className={`rounded px-1.5 py-0.5 font-pixel text-[10px] ${captureTypeColors[bounty.capture_type]}`}
                                            >
                                                {captureTypeDisplay[bounty.capture_type]}
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                {/* Reward */}
                                <div className="mb-3 flex items-center justify-center gap-2 rounded-lg border border-amber-600/50 bg-amber-900/30 py-2">
                                    <Coins className="h-5 w-5 text-amber-400" />
                                    <span className="font-pixel text-lg text-amber-300">
                                        {bounty.reward_amount.toLocaleString()} gold
                                    </span>
                                </div>

                                {/* Crime */}
                                {bounty.crime && (
                                    <div className="mb-2 flex items-center gap-2">
                                        <Skull className="h-4 w-4 text-stone-400" />
                                        <span className="font-pixel text-xs text-stone-300">
                                            {bounty.crime.crime_type.name}
                                        </span>
                                    </div>
                                )}

                                {/* Reason */}
                                <p className="mb-3 text-xs text-stone-400">{bounty.reason}</p>

                                {/* Location */}
                                <div className="mb-2 flex items-center gap-1 text-[10px] text-stone-500">
                                    <MapPin className="h-3 w-3" />
                                    Last seen in the area
                                </div>

                                {/* Posted By */}
                                <div className="mb-3 flex items-center justify-between text-[10px] text-stone-500">
                                    <span>
                                        Posted by:{' '}
                                        {bounty.posted_by ? bounty.posted_by.username : 'Authority'}
                                    </span>
                                    {bounty.expires_at && <span>Expires: {bounty.expires_at}</span>}
                                </div>

                                {/* Claim Button */}
                                <button
                                    onClick={() => {
                                        setClaimingId(bounty.id);
                                        router.post(`/crime/bounties/${bounty.id}/claim`, {}, {
                                            preserveScroll: true,
                                            onSuccess: () => router.reload(),
                                            onFinish: () => setClaimingId(null),
                                        });
                                    }}
                                    disabled={claimingId === bounty.id}
                                    className="flex w-full items-center justify-center gap-2 rounded-lg border border-amber-600/50 bg-amber-900/30 px-3 py-2 font-pixel text-xs text-amber-300 transition hover:bg-amber-800/50 disabled:opacity-50"
                                >
                                    {claimingId === bounty.id ? (
                                        <>
                                            <Loader2 className="h-4 w-4 animate-spin" />
                                            Claiming...
                                        </>
                                    ) : (
                                        <>
                                            <Target className="h-4 w-4" />
                                            Accept Bounty
                                        </>
                                    )}
                                </button>
                            </div>
                        ))}
                    </div>
                ) : (
                    <div className="flex flex-1 items-center justify-center py-12">
                        <div className="text-center">
                            <Target className="mx-auto mb-3 h-16 w-16 text-stone-600" />
                            <p className="font-pixel text-base text-stone-500">No Active Bounties</p>
                            <p className="font-pixel text-xs text-stone-600">
                                The realm is at peace... for now.
                            </p>
                        </div>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
