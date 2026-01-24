import { Head, router, usePage } from '@inertiajs/react';
import {
    ArrowRight,
    Check,
    Clock,
    Crown,
    Home,
    Loader2,
    MapPin,
    Shield,
    X,
} from 'lucide-react';
import { useState } from 'react';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

interface Village {
    id: number;
    name: string;
    castle?: string;
    kingdom?: string;
}

interface MigrationRequestData {
    id: number;
    user: {
        id: number;
        username: string;
    };
    from_village: Village;
    to_village: Village;
    status: string;
    elder_approved: boolean | null;
    lord_approved: boolean | null;
    king_approved: boolean | null;
    needs_elder: boolean;
    needs_lord: boolean;
    needs_king: boolean;
    denial_reason: string | null;
    created_at: string;
    completed_at: string | null;
}

interface PageProps {
    current_village: Village | null;
    pending_request: MigrationRequestData | null;
    requests_to_approve: MigrationRequestData[];
    request_history: MigrationRequestData[];
    can_migrate: boolean;
    cooldown_ends: string | null;
    [key: string]: unknown;
}

function ApprovalBadge({ approved, needed }: { approved: boolean | null; needed: boolean }) {
    if (!needed) {
        return <span className="font-pixel text-[10px] text-stone-600">N/A</span>;
    }

    if (approved === null) {
        return (
            <span className="flex items-center gap-1 font-pixel text-[10px] text-amber-400">
                <Clock className="h-3 w-3" /> Pending
            </span>
        );
    }

    if (approved) {
        return (
            <span className="flex items-center gap-1 font-pixel text-[10px] text-green-400">
                <Check className="h-3 w-3" /> Approved
            </span>
        );
    }

    return (
        <span className="flex items-center gap-1 font-pixel text-[10px] text-red-400">
            <X className="h-3 w-3" /> Denied
        </span>
    );
}

function RequestCard({
    request,
    showApproveButtons,
    onApprove,
    onDeny,
    onCancel,
    loading,
    isOwn,
}: {
    request: MigrationRequestData;
    showApproveButtons?: 'elder' | 'lord' | 'king';
    onApprove?: (id: number, level: string) => void;
    onDeny?: (id: number, level: string) => void;
    onCancel?: (id: number) => void;
    loading: number | null;
    isOwn?: boolean;
}) {
    const statusColors: Record<string, string> = {
        pending: 'border-amber-500/50 bg-amber-900/20',
        approved: 'border-green-500/50 bg-green-900/20',
        denied: 'border-red-500/50 bg-red-900/20',
        completed: 'border-blue-500/50 bg-blue-900/20',
        cancelled: 'border-stone-500/50 bg-stone-800/50',
    };

    return (
        <div className={`rounded-xl border-2 ${statusColors[request.status] || statusColors.pending} p-4`}>
            <div className="mb-3 flex items-center justify-between">
                <div className="flex items-center gap-2">
                    <MapPin className="h-4 w-4 text-amber-300" />
                    <span className="font-pixel text-sm text-stone-200">{request.user.username}</span>
                </div>
                <span className="rounded bg-stone-800 px-2 py-1 font-pixel text-[10px] uppercase text-stone-400">
                    {request.status}
                </span>
            </div>

            <div className="mb-3 flex items-center gap-2 text-sm">
                <span className="text-stone-400">{request.from_village.name}</span>
                <ArrowRight className="h-4 w-4 text-stone-600" />
                <span className="text-amber-300">{request.to_village.name}</span>
            </div>

            {request.to_village.castle && (
                <p className="mb-2 font-pixel text-[10px] text-stone-500">
                    Castle: {request.to_village.castle}
                    {request.to_village.kingdom && ` | Kingdom: ${request.to_village.kingdom}`}
                </p>
            )}

            {/* Approval Status */}
            <div className="mb-3 grid grid-cols-3 gap-2 rounded-lg bg-stone-800/50 p-2">
                <div className="text-center">
                    <p className="font-pixel text-[10px] text-stone-500">Elder</p>
                    <ApprovalBadge approved={request.elder_approved} needed={request.needs_elder} />
                </div>
                <div className="text-center">
                    <p className="font-pixel text-[10px] text-stone-500">Lord</p>
                    <ApprovalBadge approved={request.lord_approved} needed={request.needs_lord} />
                </div>
                <div className="text-center">
                    <p className="font-pixel text-[10px] text-stone-500">King</p>
                    <ApprovalBadge approved={request.king_approved} needed={request.needs_king} />
                </div>
            </div>

            {request.denial_reason && (
                <p className="mb-3 rounded bg-red-900/30 p-2 text-xs text-red-300">
                    Reason: {request.denial_reason}
                </p>
            )}

            {/* Action Buttons */}
            {showApproveButtons && request.status === 'pending' && (
                <div className="flex gap-2">
                    <button
                        onClick={() => onApprove?.(request.id, showApproveButtons)}
                        disabled={loading === request.id}
                        className="flex flex-1 items-center justify-center gap-2 rounded-lg border-2 border-green-600/50 bg-green-900/20 px-3 py-2 font-pixel text-xs text-green-300 transition hover:bg-green-800/30 disabled:opacity-50"
                    >
                        {loading === request.id ? <Loader2 className="h-4 w-4 animate-spin" /> : <Check className="h-4 w-4" />}
                        Approve
                    </button>
                    <button
                        onClick={() => onDeny?.(request.id, showApproveButtons)}
                        disabled={loading === request.id}
                        className="flex flex-1 items-center justify-center gap-2 rounded-lg border-2 border-red-600/50 bg-red-900/20 px-3 py-2 font-pixel text-xs text-red-300 transition hover:bg-red-800/30 disabled:opacity-50"
                    >
                        <X className="h-4 w-4" />
                        Deny
                    </button>
                </div>
            )}

            {isOwn && request.status === 'pending' && (
                <button
                    onClick={() => onCancel?.(request.id)}
                    disabled={loading === request.id}
                    className="flex w-full items-center justify-center gap-2 rounded-lg border-2 border-stone-600/50 bg-stone-800/50 px-3 py-2 font-pixel text-xs text-stone-300 transition hover:bg-stone-700/50 disabled:opacity-50"
                >
                    {loading === request.id ? <Loader2 className="h-4 w-4 animate-spin" /> : <X className="h-4 w-4" />}
                    Cancel Request
                </button>
            )}

            <p className="mt-2 text-right font-pixel text-[10px] text-stone-600">
                {new Date(request.created_at).toLocaleDateString()}
            </p>
        </div>
    );
}

export default function MigrationIndex() {
    const { current_village, pending_request, requests_to_approve, request_history, can_migrate, cooldown_ends } =
        usePage<PageProps>().props;

    const [loading, setLoading] = useState<number | null>(null);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Migration', href: '/migration' },
    ];

    const handleApprove = (id: number, level: string) => {
        setLoading(id);
        router.post(`/migration/${id}/approve`, { level }, {
            preserveScroll: true,
            onFinish: () => setLoading(null),
        });
    };

    const handleDeny = (id: number, level: string) => {
        const reason = prompt('Reason for denial (optional):');
        setLoading(id);
        router.post(`/migration/${id}/deny`, { level, reason }, {
            preserveScroll: true,
            onFinish: () => setLoading(null),
        });
    };

    const handleCancel = (id: number) => {
        setLoading(id);
        router.post(`/migration/${id}/cancel`, {}, {
            preserveScroll: true,
            onFinish: () => setLoading(null),
        });
    };

    // Determine which level the current user can approve
    const getApprovalLevel = (request: MigrationRequestData): 'elder' | 'lord' | 'king' | undefined => {
        if (request.needs_elder && request.elder_approved === null) return 'elder';
        if (request.needs_lord && request.lord_approved === null) return 'lord';
        if (request.needs_king && request.king_approved === null) return 'king';
        return undefined;
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Migration" />
            <div className="flex h-full flex-1 flex-col gap-6 overflow-y-auto p-4">
                {/* Header */}
                <div>
                    <h1 className="font-pixel text-2xl text-amber-400">Migration</h1>
                    <p className="font-pixel text-sm text-stone-400">Request to move to a new village</p>
                </div>

                {/* Current Village */}
                <div className="rounded-xl border-2 border-stone-600/50 bg-stone-800/50 p-4">
                    <div className="flex items-center gap-2">
                        <Home className="h-5 w-5 text-green-400" />
                        <span className="font-pixel text-sm text-stone-400">Current Home:</span>
                        <span className="font-pixel text-lg text-green-300">{current_village?.name || 'None'}</span>
                    </div>
                    {current_village?.castle && (
                        <p className="mt-1 font-pixel text-xs text-stone-500">
                            <Shield className="mr-1 inline h-3 w-3" />
                            {current_village.castle}
                            {current_village.kingdom && (
                                <>
                                    <Crown className="mx-1 inline h-3 w-3" />
                                    {current_village.kingdom}
                                </>
                            )}
                        </p>
                    )}
                    {!can_migrate && cooldown_ends && (
                        <p className="mt-2 font-pixel text-xs text-amber-400">
                            <Clock className="mr-1 inline h-3 w-3" />
                            Cooldown until {new Date(cooldown_ends).toLocaleDateString()}
                        </p>
                    )}
                </div>

                {/* Pending Request */}
                {pending_request && (
                    <div>
                        <h2 className="mb-3 font-pixel text-lg text-stone-300">Your Pending Request</h2>
                        <RequestCard
                            request={pending_request}
                            onCancel={handleCancel}
                            loading={loading}
                            isOwn
                        />
                    </div>
                )}

                {/* Requests to Approve */}
                {requests_to_approve.length > 0 && (
                    <div>
                        <h2 className="mb-3 font-pixel text-lg text-amber-300">
                            Requests Awaiting Your Approval ({requests_to_approve.length})
                        </h2>
                        <div className="grid gap-4 md:grid-cols-2">
                            {requests_to_approve.map((request) => (
                                <RequestCard
                                    key={request.id}
                                    request={request}
                                    showApproveButtons={getApprovalLevel(request)}
                                    onApprove={handleApprove}
                                    onDeny={handleDeny}
                                    loading={loading}
                                />
                            ))}
                        </div>
                    </div>
                )}

                {/* How to Request Migration */}
                {!pending_request && can_migrate && (
                    <div className="rounded-xl border-2 border-blue-600/30 bg-blue-900/10 p-4">
                        <h3 className="font-pixel text-sm text-blue-300">How to Request Migration</h3>
                        <p className="mt-2 text-xs text-stone-400">
                            Visit any village and click "Request to Move Here" to start a migration request.
                            You'll need approval from the local Elder, Lord, and King (if they exist).
                        </p>
                    </div>
                )}

                {/* Request History */}
                {request_history.length > 0 && (
                    <div>
                        <h2 className="mb-3 font-pixel text-lg text-stone-300">Recent History</h2>
                        <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                            {request_history.filter(r => r.status !== 'pending').map((request) => (
                                <RequestCard
                                    key={request.id}
                                    request={request}
                                    loading={loading}
                                />
                            ))}
                        </div>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
