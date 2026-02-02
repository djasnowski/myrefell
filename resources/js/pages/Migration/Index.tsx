import { Head, router, usePage } from "@inertiajs/react";
import {
    ArrowRight,
    Building2,
    Castle,
    Check,
    Clock,
    Crown,
    HelpCircle,
    Home,
    Loader2,
    MapPin,
    Shield,
    TreePine,
    X,
} from "lucide-react";
import { useState } from "react";
import AppLayout from "@/layouts/app-layout";
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from "@/components/ui/dialog";
import type { BreadcrumbItem } from "@/types";

interface Location {
    type: string;
    id: number;
    name: string;
    barony?: string;
    kingdom?: string;
}

interface MigrationRequestData {
    id: number;
    user: {
        id: number;
        username: string;
    };
    from_location: Location;
    to_location: Location;
    status: string;
    elder_approved: boolean | null;
    mayor_approved: boolean | null;
    baron_approved: boolean | null;
    king_approved: boolean | null;
    needs_elder: boolean;
    needs_mayor: boolean;
    needs_baron: boolean;
    needs_king: boolean;
    denial_reason: string | null;
    created_at: string;
    completed_at: string | null;
}

interface PageProps {
    current_home: {
        type: string;
        id: number;
        name: string;
        barony?: string;
        kingdom?: string;
    } | null;
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
    showApproveButtons?: "elder" | "mayor" | "baron" | "king";
    onApprove?: (id: number, level: string) => void;
    onDeny?: (id: number, level: string) => void;
    onCancel?: (id: number) => void;
    loading: number | null;
    isOwn?: boolean;
}) {
    const statusColors: Record<string, string> = {
        pending: "border-amber-500/50 bg-amber-900/20",
        approved: "border-green-500/50 bg-green-900/20",
        denied: "border-red-500/50 bg-red-900/20",
        completed: "border-blue-500/50 bg-blue-900/20",
        cancelled: "border-stone-500/50 bg-stone-800/50",
    };

    return (
        <div
            className={`rounded-xl border-2 ${statusColors[request.status] || statusColors.pending} p-4`}
        >
            <div className="mb-3 flex items-center justify-between">
                <div className="flex items-center gap-2">
                    <MapPin className="h-4 w-4 text-amber-300" />
                    <span className="font-pixel text-sm text-stone-200">
                        {request.user.username}
                    </span>
                </div>
                <span className="rounded bg-stone-800 px-2 py-1 font-pixel text-[10px] uppercase text-stone-400">
                    {request.status}
                </span>
            </div>

            <div className="mb-3 flex items-center gap-2 text-sm">
                <span className="text-stone-400">{request.from_location.name}</span>
                <ArrowRight className="h-4 w-4 text-stone-600" />
                <span className="text-amber-300">{request.to_location.name}</span>
            </div>

            {request.to_location.barony && (
                <p className="mb-2 font-pixel text-[10px] text-stone-500">
                    Barony: {request.to_location.barony}
                    {request.to_location.kingdom && ` | Kingdom: ${request.to_location.kingdom}`}
                </p>
            )}

            {/* Approval Status */}
            <div className="mb-3 grid grid-cols-4 gap-2 rounded-lg bg-stone-800/50 p-2">
                <div className="text-center">
                    <p className="font-pixel text-[10px] text-stone-500">Elder</p>
                    <ApprovalBadge approved={request.elder_approved} needed={request.needs_elder} />
                </div>
                <div className="text-center">
                    <p className="font-pixel text-[10px] text-stone-500">Mayor</p>
                    <ApprovalBadge approved={request.mayor_approved} needed={request.needs_mayor} />
                </div>
                <div className="text-center">
                    <p className="font-pixel text-[10px] text-stone-500">Baron</p>
                    <ApprovalBadge approved={request.baron_approved} needed={request.needs_baron} />
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
            {showApproveButtons && request.status === "pending" && (
                <div className="flex gap-2">
                    <button
                        onClick={() => onApprove?.(request.id, showApproveButtons)}
                        disabled={loading === request.id}
                        className="flex flex-1 items-center justify-center gap-2 rounded-lg border-2 border-green-600/50 bg-green-900/20 px-3 py-2 font-pixel text-xs text-green-300 transition hover:bg-green-800/30 disabled:opacity-50"
                    >
                        {loading === request.id ? (
                            <Loader2 className="h-4 w-4 animate-spin" />
                        ) : (
                            <Check className="h-4 w-4" />
                        )}
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

            {isOwn && request.status === "pending" && (
                <button
                    onClick={() => onCancel?.(request.id)}
                    disabled={loading === request.id}
                    className="flex w-full items-center justify-center gap-2 rounded-lg border-2 border-stone-600/50 bg-stone-800/50 px-3 py-2 font-pixel text-xs text-stone-300 transition hover:bg-stone-700/50 disabled:opacity-50"
                >
                    {loading === request.id ? (
                        <Loader2 className="h-4 w-4 animate-spin" />
                    ) : (
                        <X className="h-4 w-4" />
                    )}
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
    const {
        current_home,
        pending_request,
        requests_to_approve,
        request_history,
        can_migrate,
        cooldown_ends,
    } = usePage<PageProps>().props;

    const getLocationIcon = (type: string) => {
        switch (type) {
            case "village":
                return <TreePine className="h-5 w-5 text-green-400" />;
            case "town":
                return <Building2 className="h-5 w-5 text-blue-400" />;
            case "barony":
                return <Shield className="h-5 w-5 text-purple-400" />;
            case "kingdom":
                return <Castle className="h-5 w-5 text-amber-400" />;
            default:
                return <Home className="h-5 w-5 text-green-400" />;
        }
    };

    const [loading, setLoading] = useState<number | null>(null);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: "Dashboard", href: "/dashboard" },
        { title: "Migration", href: "/migration" },
    ];

    const handleApprove = (id: number, level: string) => {
        setLoading(id);
        router.post(
            `/migration/${id}/approve`,
            { level },
            {
                preserveScroll: true,
                onFinish: () => setLoading(null),
            },
        );
    };

    const handleDeny = (id: number, level: string) => {
        const reason = prompt("Reason for denial (optional):");
        setLoading(id);
        router.post(
            `/migration/${id}/deny`,
            { level, reason },
            {
                preserveScroll: true,
                onFinish: () => setLoading(null),
            },
        );
    };

    const handleCancel = (id: number) => {
        setLoading(id);
        router.post(
            `/migration/${id}/cancel`,
            {},
            {
                preserveScroll: true,
                onFinish: () => setLoading(null),
            },
        );
    };

    // Determine which level the current user can approve
    const getApprovalLevel = (
        request: MigrationRequestData,
    ): "elder" | "mayor" | "baron" | "king" | undefined => {
        if (request.needs_elder && request.elder_approved === null) return "elder";
        if (request.needs_mayor && request.mayor_approved === null) return "mayor";
        if (request.needs_baron && request.baron_approved === null) return "baron";
        if (request.needs_king && request.king_approved === null) return "king";
        return undefined;
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Migration" />
            <div className="flex h-full flex-1 flex-col gap-6 overflow-y-auto p-4">
                {/* Header */}
                <div className="flex items-start justify-between">
                    <div>
                        <h1 className="font-pixel text-2xl text-amber-400">Migration</h1>
                        <p className="font-pixel text-sm text-stone-400">
                            Request to move to a new home
                        </p>
                    </div>
                    <Dialog>
                        <DialogTrigger asChild>
                            <button className="flex items-center gap-1.5 rounded-lg border border-stone-600/50 bg-stone-800/50 px-3 py-2 font-pixel text-xs text-stone-300 transition hover:bg-stone-700/50">
                                <HelpCircle className="h-4 w-4" />
                                How It Works
                            </button>
                        </DialogTrigger>
                        <DialogContent className="border-stone-600 bg-stone-900 text-stone-200">
                            <DialogHeader>
                                <DialogTitle className="font-pixel text-lg text-amber-400">
                                    Migration Approval System
                                </DialogTitle>
                                <DialogDescription className="text-stone-400">
                                    Who needs to approve your move depends on where you're going.
                                </DialogDescription>
                            </DialogHeader>
                            <div className="space-y-4">
                                {/* Village */}
                                <div className="rounded-lg border border-green-600/30 bg-green-900/10 p-3">
                                    <div className="mb-2 flex items-center gap-2">
                                        <TreePine className="h-4 w-4 text-green-400" />
                                        <span className="font-pixel text-sm text-green-300">
                                            Village
                                        </span>
                                    </div>
                                    <div className="flex items-center gap-2 text-xs text-stone-400">
                                        <span className="rounded bg-stone-800 px-2 py-1">
                                            Elder
                                        </span>
                                        <ArrowRight className="h-3 w-3" />
                                        <span className="rounded bg-stone-800 px-2 py-1">
                                            Baron
                                        </span>
                                        <ArrowRight className="h-3 w-3" />
                                        <span className="rounded bg-stone-800 px-2 py-1">King</span>
                                    </div>
                                </div>

                                {/* Town */}
                                <div className="rounded-lg border border-blue-600/30 bg-blue-900/10 p-3">
                                    <div className="mb-2 flex items-center gap-2">
                                        <Building2 className="h-4 w-4 text-blue-400" />
                                        <span className="font-pixel text-sm text-blue-300">
                                            Town
                                        </span>
                                    </div>
                                    <div className="flex items-center gap-2 text-xs text-stone-400">
                                        <span className="rounded bg-stone-800 px-2 py-1">
                                            Mayor
                                        </span>
                                        <span className="text-stone-500">(only)</span>
                                    </div>
                                </div>

                                {/* Barony */}
                                <div className="rounded-lg border border-purple-600/30 bg-purple-900/10 p-3">
                                    <div className="mb-2 flex items-center gap-2">
                                        <Shield className="h-4 w-4 text-purple-400" />
                                        <span className="font-pixel text-sm text-purple-300">
                                            Barony
                                        </span>
                                    </div>
                                    <div className="flex items-center gap-2 text-xs text-stone-400">
                                        <span className="rounded bg-stone-800 px-2 py-1">
                                            Baron
                                        </span>
                                        <ArrowRight className="h-3 w-3" />
                                        <span className="rounded bg-stone-800 px-2 py-1">King</span>
                                    </div>
                                </div>

                                {/* Kingdom */}
                                <div className="rounded-lg border border-amber-600/30 bg-amber-900/10 p-3">
                                    <div className="mb-2 flex items-center gap-2">
                                        <Castle className="h-4 w-4 text-amber-400" />
                                        <span className="font-pixel text-sm text-amber-300">
                                            Kingdom
                                        </span>
                                    </div>
                                    <div className="flex items-center gap-2 text-xs text-stone-400">
                                        <span className="rounded bg-stone-800 px-2 py-1">King</span>
                                        <span className="text-stone-500">(only)</span>
                                    </div>
                                </div>

                                {/* Auto-approve note */}
                                <div className="rounded-lg border border-stone-600/30 bg-stone-800/30 p-3">
                                    <p className="text-xs text-stone-400">
                                        <Check className="mr-1 inline h-3 w-3 text-green-400" />
                                        <strong className="text-stone-300">
                                            Auto-approved:
                                        </strong>{" "}
                                        If a position is vacant (no Elder, Mayor, Baron, or King),
                                        that approval step is automatically granted.
                                    </p>
                                </div>
                            </div>
                        </DialogContent>
                    </Dialog>
                </div>

                {/* Current Home */}
                <div className="rounded-xl border-2 border-stone-600/50 bg-stone-800/50 p-4">
                    <div className="flex items-center gap-2">
                        {current_home ? (
                            getLocationIcon(current_home.type)
                        ) : (
                            <Home className="h-5 w-5 text-stone-400" />
                        )}
                        <span className="font-pixel text-sm text-stone-400">Current Home:</span>
                        <span className="font-pixel text-lg text-green-300">
                            {current_home?.name || "None (Unsettled)"}
                        </span>
                        {current_home && (
                            <span className="rounded bg-stone-700 px-2 py-0.5 font-pixel text-[10px] uppercase text-stone-400">
                                {current_home.type}
                            </span>
                        )}
                    </div>
                    {current_home?.barony && (
                        <p className="mt-1 font-pixel text-xs text-stone-500">
                            <Shield className="mr-1 inline h-3 w-3" />
                            {current_home.barony}
                            {current_home.kingdom && (
                                <>
                                    <Crown className="mx-1 inline h-3 w-3" />
                                    {current_home.kingdom}
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
                        <h2 className="mb-3 font-pixel text-lg text-stone-300">
                            Your Pending Request
                        </h2>
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
                        <h3 className="font-pixel text-sm text-blue-300">
                            How to Request Migration
                        </h3>
                        <p className="mt-2 text-xs text-stone-400">
                            Visit any location and click "Request to Move Here" to start a migration
                            request. Click "How It Works" above to see who needs to approve your
                            request based on your destination.
                        </p>
                    </div>
                )}

                {/* Request History */}
                {request_history.length > 0 && (
                    <div>
                        <h2 className="mb-3 font-pixel text-lg text-stone-300">Recent History</h2>
                        <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                            {request_history
                                .filter((r) => r.status !== "pending")
                                .map((request) => (
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
