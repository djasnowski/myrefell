import { Head, router, usePage } from "@inertiajs/react";
import { Check, Coins, FileText, Loader2, MapPin, User, X } from "lucide-react";
import { useState } from "react";
import AppLayout from "@/layouts/app-layout";
import type { BreadcrumbItem } from "@/types";

interface ManumissionRequest {
    id: number;
    request_type: string;
    gold_offered: number;
    reason: string | null;
    status: string;
    created_at: string;
    serf: {
        id: number;
        username: string;
    };
    barony: {
        id: number;
        name: string;
    };
}

interface Pagination {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    data: ManumissionRequest[];
}

interface PageProps {
    requests: Pagination;
    [key: string]: unknown;
}

const requestTypeDisplay: Record<string, string> = {
    decree: "Baron's Decree",
    purchase: "Purchase Freedom",
    military_service: "Military Service",
    exceptional_service: "Exceptional Service",
};

function RequestCard({
    request,
    onApprove,
    onDeny,
    loading,
}: {
    request: ManumissionRequest;
    onApprove: (id: number, message: string) => void;
    onDeny: (id: number, message: string) => void;
    loading: number | null;
}) {
    const [responseMessage, setResponseMessage] = useState("");
    const [showResponse, setShowResponse] = useState(false);
    const isLoading = loading === request.id;

    return (
        <div className="rounded-xl border-2 border-stone-600/50 bg-stone-800/30 p-4">
            <div className="mb-3 flex items-start justify-between">
                <div className="flex items-center gap-3">
                    <div className="rounded-lg bg-stone-700/50 p-2">
                        <User className="h-5 w-5 text-stone-300" />
                    </div>
                    <div>
                        <h3 className="font-pixel text-sm text-amber-300">
                            {request.serf.username}
                        </h3>
                        <p className="font-pixel text-xs text-stone-400">
                            {requestTypeDisplay[request.request_type]}
                        </p>
                    </div>
                </div>
                {request.gold_offered > 0 && (
                    <div className="flex items-center gap-1 rounded-lg bg-amber-900/30 px-2 py-1">
                        <Coins className="h-4 w-4 text-amber-400" />
                        <span className="font-pixel text-xs text-amber-300">
                            {request.gold_offered.toLocaleString()}g
                        </span>
                    </div>
                )}
            </div>

            <div className="mb-3 flex items-center gap-2">
                <MapPin className="h-4 w-4 text-purple-400" />
                <span className="font-pixel text-xs text-purple-300">{request.barony.name}</span>
            </div>

            {request.reason && (
                <div className="mb-3 rounded-lg bg-stone-900/50 p-2">
                    <span className="font-pixel text-[10px] text-stone-500">Reason:</span>
                    <p className="text-xs text-stone-300">{request.reason}</p>
                </div>
            )}

            <p className="mb-3 font-pixel text-[10px] text-stone-500">
                Submitted {request.created_at}
            </p>

            {!showResponse ? (
                <div className="flex gap-2">
                    <button
                        onClick={() => setShowResponse(true)}
                        className="flex flex-1 items-center justify-center gap-2 rounded border-2 border-green-600/50 bg-green-900/20 px-3 py-2 font-pixel text-xs text-green-300 transition hover:bg-green-800/30"
                    >
                        <Check className="h-4 w-4" />
                        Approve
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
                            onClick={() => onApprove(request.id, responseMessage)}
                            disabled={isLoading}
                            className="flex flex-1 items-center justify-center gap-2 rounded border-2 border-green-600/50 bg-green-900/20 px-3 py-2 font-pixel text-xs text-green-300 transition hover:bg-green-800/30 disabled:opacity-50"
                        >
                            {isLoading ? (
                                <Loader2 className="h-4 w-4 animate-spin" />
                            ) : (
                                <Check className="h-4 w-4" />
                            )}
                            Grant Freedom
                        </button>
                        <button
                            onClick={() => onDeny(request.id, responseMessage)}
                            disabled={isLoading}
                            className="flex flex-1 items-center justify-center gap-2 rounded border-2 border-red-600/50 bg-red-900/20 px-3 py-2 font-pixel text-xs text-red-300 transition hover:bg-red-800/30 disabled:opacity-50"
                        >
                            {isLoading ? (
                                <Loader2 className="h-4 w-4 animate-spin" />
                            ) : (
                                <X className="h-4 w-4" />
                            )}
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

export default function ManumissionRequests() {
    const { requests } = usePage<PageProps>().props;
    const [loading, setLoading] = useState<number | null>(null);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: "Dashboard", href: "/dashboard" },
        { title: "Social Class", href: "/social-class" },
        { title: "Manumission Requests", href: "#" },
    ];

    const handleApprove = (id: number, message: string) => {
        setLoading(id);
        router.post(
            `/social-class/manumission/${id}/approve`,
            { response_message: message || undefined },
            {
                preserveScroll: true,
                onFinish: () => setLoading(null),
            },
        );
    };

    const handleDeny = (id: number, message: string) => {
        setLoading(id);
        router.post(
            `/social-class/manumission/${id}/deny`,
            { response_message: message || undefined },
            {
                preserveScroll: true,
                onFinish: () => setLoading(null),
            },
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Manumission Requests" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-y-auto p-4">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="font-pixel text-2xl text-amber-400">Manumission Requests</h1>
                        <p className="font-pixel text-sm text-stone-400">
                            Serfs petitioning for freedom
                        </p>
                    </div>
                    <div className="rounded-lg border-2 border-stone-600/50 bg-stone-800/50 px-4 py-2">
                        <span className="font-pixel text-xs text-stone-400">Pending:</span>
                        <span className="ml-2 font-pixel text-sm text-amber-300">
                            {requests.total}
                        </span>
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
                            <FileText className="mx-auto mb-3 h-16 w-16 text-stone-600" />
                            <p className="font-pixel text-base text-stone-500">
                                No pending requests
                            </p>
                            <p className="font-pixel text-xs text-stone-600">
                                All manumission requests have been processed.
                            </p>
                        </div>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
