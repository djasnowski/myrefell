import { Head, router, usePage } from '@inertiajs/react';
import { Check, Coins, Crown, FileText, Loader2, MapPin, X } from 'lucide-react';
import { useState } from 'react';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

interface EnnoblementRequest {
    id: number;
    request_type: string;
    gold_offered: number;
    reason: string | null;
    status: string;
    created_at: string;
    requester: {
        id: number;
        username: string;
    };
    kingdom: {
        id: number;
        name: string;
    };
    spouse: {
        id: number;
        username: string;
    } | null;
}

interface Pagination {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    data: EnnoblementRequest[];
}

interface PageProps {
    requests: Pagination;
    [key: string]: unknown;
}

const requestTypeDisplay: Record<string, string> = {
    royal_decree: 'Royal Decree',
    purchase: 'Purchase Title',
    military_service: 'Military Service',
    marriage: 'Marriage into Nobility',
};

const titleOptions = ['Knight', 'Sir', 'Dame', 'Lord', 'Lady', 'Baron', 'Baroness', 'Count', 'Countess'];

function RequestCard({
    request,
    onApprove,
    onDeny,
    loading,
}: {
    request: EnnoblementRequest;
    onApprove: (id: number, title: string, message: string) => void;
    onDeny: (id: number, message: string) => void;
    loading: number | null;
}) {
    const [responseMessage, setResponseMessage] = useState('');
    const [titleGranted, setTitleGranted] = useState('Knight');
    const [showResponse, setShowResponse] = useState(false);
    const isLoading = loading === request.id;

    return (
        <div className="rounded-xl border-2 border-purple-500/50 bg-purple-900/20 p-4">
            <div className="mb-3 flex items-start justify-between">
                <div className="flex items-center gap-3">
                    <div className="rounded-lg bg-purple-800/50 p-2">
                        <FileText className="h-5 w-5 text-purple-300" />
                    </div>
                    <div>
                        <h3 className="font-pixel text-sm text-purple-300">{request.requester.username}</h3>
                        <p className="font-pixel text-xs text-stone-400">{requestTypeDisplay[request.request_type]}</p>
                    </div>
                </div>
                {request.gold_offered > 0 && (
                    <div className="flex items-center gap-1 rounded-lg bg-amber-900/30 px-2 py-1">
                        <Coins className="h-4 w-4 text-amber-400" />
                        <span className="font-pixel text-xs text-amber-300">{request.gold_offered.toLocaleString()}g</span>
                    </div>
                )}
            </div>

            <div className="mb-3 flex items-center gap-2">
                <MapPin className="h-4 w-4 text-amber-400" />
                <span className="font-pixel text-xs text-amber-300">{request.kingdom.name}</span>
            </div>

            {request.spouse && (
                <div className="mb-3 flex items-center gap-2 rounded-lg bg-pink-900/30 px-2 py-1">
                    <span className="font-pixel text-[10px] text-pink-300">Spouse: {request.spouse.username}</span>
                </div>
            )}

            {request.reason && (
                <div className="mb-3 rounded-lg bg-stone-900/50 p-2">
                    <span className="font-pixel text-[10px] text-stone-500">Reason:</span>
                    <p className="text-xs text-stone-300">{request.reason}</p>
                </div>
            )}

            <p className="mb-3 font-pixel text-[10px] text-stone-500">Submitted {request.created_at}</p>

            {!showResponse ? (
                <div className="flex gap-2">
                    <button
                        onClick={() => setShowResponse(true)}
                        className="flex flex-1 items-center justify-center gap-2 rounded border-2 border-purple-600/50 bg-purple-900/20 px-3 py-2 font-pixel text-xs text-purple-300 transition hover:bg-purple-800/30"
                    >
                        <Check className="h-4 w-4" />
                        Grant Title
                    </button>
                    <button
                        onClick={() => setShowResponse(true)}
                        className="flex flex-1 items-center justify-center gap-2 rounded border-2 border-red-600/50 bg-red-900/20 px-3 py-2 font-pixel text-xs text-red-300 transition hover:bg-red-800/30"
                    >
                        <X className="h-4 w-4" />
                        Deny
                    </button>
                </div>
            ) : (
                <div className="space-y-2">
                    <div>
                        <label className="mb-1 block font-pixel text-[10px] text-stone-400">Title to Grant</label>
                        <select
                            value={titleGranted}
                            onChange={(e) => setTitleGranted(e.target.value)}
                            className="w-full rounded border border-stone-600 bg-stone-800 p-2 font-pixel text-xs text-stone-200"
                        >
                            {titleOptions.map((title) => (
                                <option key={title} value={title}>
                                    {title}
                                </option>
                            ))}
                        </select>
                    </div>
                    <textarea
                        value={responseMessage}
                        onChange={(e) => setResponseMessage(e.target.value)}
                        placeholder="Response message (optional)"
                        maxLength={500}
                        rows={2}
                        className="w-full rounded border border-stone-600 bg-stone-800 p-2 font-pixel text-xs text-stone-200"
                    />
                    <div className="flex gap-2">
                        <button
                            onClick={() => onApprove(request.id, titleGranted, responseMessage)}
                            disabled={isLoading}
                            className="flex flex-1 items-center justify-center gap-2 rounded border-2 border-purple-600/50 bg-purple-900/20 px-3 py-2 font-pixel text-xs text-purple-300 transition hover:bg-purple-800/30 disabled:opacity-50"
                        >
                            {isLoading ? <Loader2 className="h-4 w-4 animate-spin" /> : <Crown className="h-4 w-4" />}
                            Ennoble as {titleGranted}
                        </button>
                        <button
                            onClick={() => onDeny(request.id, responseMessage)}
                            disabled={isLoading}
                            className="flex flex-1 items-center justify-center gap-2 rounded border-2 border-red-600/50 bg-red-900/20 px-3 py-2 font-pixel text-xs text-red-300 transition hover:bg-red-800/30 disabled:opacity-50"
                        >
                            {isLoading ? <Loader2 className="h-4 w-4 animate-spin" /> : <X className="h-4 w-4" />}
                            Deny
                        </button>
                    </div>
                    <button
                        onClick={() => setShowResponse(false)}
                        className="w-full font-pixel text-[10px] text-stone-500 hover:text-stone-300"
                    >
                        Cancel
                    </button>
                </div>
            )}
        </div>
    );
}

export default function EnnoblementRequests() {
    const { requests } = usePage<PageProps>().props;
    const [loading, setLoading] = useState<number | null>(null);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Social Class', href: '/social-class' },
        { title: 'Ennoblement Requests', href: '#' },
    ];

    const handleApprove = (id: number, title: string, message: string) => {
        setLoading(id);
        router.post(
            `/social-class/ennoblement/${id}/approve`,
            {
                title_granted: title,
                response_message: message || undefined,
            },
            {
                preserveScroll: true,
                onFinish: () => setLoading(null),
            }
        );
    };

    const handleDeny = (id: number, message: string) => {
        setLoading(id);
        router.post(
            `/social-class/ennoblement/${id}/deny`,
            { response_message: message || undefined },
            {
                preserveScroll: true,
                onFinish: () => setLoading(null),
            }
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Ennoblement Requests" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-y-auto p-4">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="font-pixel text-2xl text-purple-400">Ennoblement Requests</h1>
                        <p className="font-pixel text-sm text-stone-400">Commoners petitioning for nobility</p>
                    </div>
                    <div className="rounded-lg border-2 border-purple-600/50 bg-purple-800/50 px-4 py-2">
                        <span className="font-pixel text-xs text-stone-400">Pending:</span>
                        <span className="ml-2 font-pixel text-sm text-purple-300">{requests.total}</span>
                    </div>
                </div>

                {requests.data.length > 0 ? (
                    <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                        {requests.data.map((request) => (
                            <RequestCard
                                key={request.id}
                                request={request}
                                onApprove={handleApprove}
                                onDeny={handleDeny}
                                loading={loading}
                            />
                        ))}
                    </div>
                ) : (
                    <div className="flex flex-1 items-center justify-center py-12">
                        <div className="text-center">
                            <Crown className="mx-auto mb-3 h-16 w-16 text-stone-600" />
                            <p className="font-pixel text-base text-stone-500">No pending requests</p>
                            <p className="font-pixel text-xs text-stone-600">All ennoblement requests have been processed.</p>
                        </div>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
