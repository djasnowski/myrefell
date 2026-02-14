import { Head, router, usePage } from "@inertiajs/react";
import { Check, Clock, Crown, Loader2, Scroll, User, X, XCircle } from "lucide-react";
import { useState } from "react";
import AppLayout from "@/layouts/app-layout";
import { gameToast } from "@/components/ui/game-toast";
import type { BreadcrumbItem } from "@/types";

interface Petition {
    id: number;
    petitioner: { id: number; username: string };
    target_role: string;
    target_holder: { id: number; username: string };
    location_type: string;
    location_id: number;
    petition_reason: string;
    request_appointment: boolean;
    created_at: string;
    expires_at: string | null;
}

interface MyPetition {
    id: number;
    authority: { id: number; username: string };
    target_role: string;
    target_holder: { id: number; username: string };
    petition_reason: string;
    request_appointment: boolean;
    status: string;
    created_at: string;
    expires_at: string | null;
}

interface PageProps {
    pending_petitions: Petition[];
    my_petitions: MyPetition[];
    [key: string]: unknown;
}

function formatDate(dateString: string): string {
    return new Date(dateString).toLocaleDateString("en-US", {
        month: "short",
        day: "numeric",
    });
}

function timeUntil(dateString: string): string {
    const diff = new Date(dateString).getTime() - Date.now();
    if (diff <= 0) return "Expired";
    const days = Math.floor(diff / (1000 * 60 * 60 * 24));
    const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
    if (days > 0) return `${days}d ${hours}h`;
    return `${hours}h`;
}

export default function PetitionsIndex() {
    const { pending_petitions, my_petitions } = usePage<PageProps>().props;

    const [respondingTo, setRespondingTo] = useState<number | null>(null);
    const [responseMessage, setResponseMessage] = useState("");
    const [loading, setLoading] = useState<number | null>(null);
    const [withdrawing, setWithdrawing] = useState<number | null>(null);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: "Dashboard", href: "/dashboard" },
        { title: "Petitions", href: "#" },
    ];

    const handleApprove = (petitionId: number) => {
        setLoading(petitionId);
        router.post(
            `/roles/petitions/${petitionId}/approve`,
            { response_message: responseMessage || null },
            {
                preserveScroll: true,
                onSuccess: () => {
                    setRespondingTo(null);
                    setResponseMessage("");
                    router.reload();
                },
                onFinish: () => {
                    setLoading(null);
                },
            },
        );
    };

    const handleDeny = (petitionId: number) => {
        setLoading(petitionId);
        router.post(
            `/roles/petitions/${petitionId}/deny`,
            { response_message: responseMessage || null },
            {
                preserveScroll: true,
                onSuccess: () => {
                    setRespondingTo(null);
                    setResponseMessage("");
                    router.reload();
                },
                onFinish: () => {
                    setLoading(null);
                },
            },
        );
    };

    const handleWithdraw = (petitionId: number) => {
        setWithdrawing(petitionId);
        router.post(
            `/roles/petitions/${petitionId}/withdraw`,
            {},
            {
                preserveScroll: true,
                onSuccess: () => {
                    router.reload();
                },
                onFinish: () => {
                    setWithdrawing(null);
                },
            },
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Petitions" />
            <div className="flex h-full flex-1 flex-col overflow-hidden p-4">
                {/* Header */}
                <div className="mb-4">
                    <h1 className="font-pixel text-xl text-amber-400">Role Petitions</h1>
                    <p className="font-pixel text-xs text-stone-400">
                        Review petitions from citizens or manage your own
                    </p>
                </div>

                <div className="-mx-1 space-y-6 overflow-y-auto px-1">
                    {/* Pending Petitions (Authority View) */}
                    {pending_petitions.length > 0 && (
                        <div>
                            <h2 className="mb-3 flex items-center gap-2 font-pixel text-sm text-amber-300">
                                <Scroll className="h-4 w-4" />
                                Petitions Requiring Your Review
                                <span className="rounded bg-red-900/50 px-1.5 py-0.5 font-pixel text-[10px] text-red-300">
                                    {pending_petitions.length}
                                </span>
                            </h2>
                            <div className="space-y-3">
                                {pending_petitions.map((petition) => (
                                    <div
                                        key={petition.id}
                                        className="rounded-lg border border-amber-600/30 bg-stone-800/60 p-4"
                                    >
                                        <div className="mb-2 flex items-start justify-between">
                                            <div className="flex items-center gap-2">
                                                <User className="h-4 w-4 text-stone-400" />
                                                <span className="font-pixel text-sm text-stone-200">
                                                    {petition.petitioner.username}
                                                </span>
                                                <span className="font-pixel text-[10px] text-stone-500">
                                                    challenges
                                                </span>
                                                <span className="font-pixel text-sm text-amber-300">
                                                    {petition.target_holder.username}
                                                </span>
                                                <span className="font-pixel text-[10px] text-stone-500">
                                                    as {petition.target_role}
                                                </span>
                                            </div>
                                            <div className="flex items-center gap-1 text-stone-500">
                                                <Clock className="h-3 w-3" />
                                                <span className="font-pixel text-[10px]">
                                                    {petition.expires_at
                                                        ? timeUntil(petition.expires_at)
                                                        : "No expiry"}
                                                </span>
                                            </div>
                                        </div>

                                        <div className="mb-3 rounded bg-stone-900/50 p-2">
                                            <p className="font-pixel text-xs text-stone-300">
                                                "{petition.petition_reason}"
                                            </p>
                                        </div>

                                        {petition.request_appointment && (
                                            <div className="mb-3 flex items-center gap-1">
                                                <Crown className="h-3 w-3 text-amber-400" />
                                                <span className="font-pixel text-[10px] text-amber-400">
                                                    Petitioner requests appointment to the role
                                                </span>
                                            </div>
                                        )}

                                        {respondingTo === petition.id ? (
                                            <div className="space-y-2">
                                                <textarea
                                                    value={responseMessage}
                                                    onChange={(e) =>
                                                        setResponseMessage(e.target.value)
                                                    }
                                                    maxLength={500}
                                                    rows={2}
                                                    className="w-full rounded-lg border border-stone-600 bg-stone-900/80 px-3 py-2 font-pixel text-xs text-stone-200 placeholder-stone-500 focus:border-amber-500 focus:outline-none"
                                                    placeholder="Response message (optional)..."
                                                />
                                                <div className="flex gap-2">
                                                    <button
                                                        onClick={() => {
                                                            setRespondingTo(null);
                                                            setResponseMessage("");
                                                        }}
                                                        className="rounded-lg border border-stone-600 bg-stone-700/50 px-3 py-1.5 font-pixel text-[10px] text-stone-300 hover:bg-stone-700"
                                                    >
                                                        Cancel
                                                    </button>
                                                    <button
                                                        onClick={() => handleApprove(petition.id)}
                                                        disabled={loading === petition.id}
                                                        className="flex items-center gap-1 rounded-lg border border-green-600/50 bg-green-900/30 px-3 py-1.5 font-pixel text-[10px] text-green-300 hover:bg-green-800/40 disabled:opacity-50"
                                                    >
                                                        {loading === petition.id ? (
                                                            <Loader2 className="h-3 w-3 animate-spin" />
                                                        ) : (
                                                            <Check className="h-3 w-3" />
                                                        )}
                                                        Approve — Remove Holder
                                                    </button>
                                                    <button
                                                        onClick={() => handleDeny(petition.id)}
                                                        disabled={loading === petition.id}
                                                        className="flex items-center gap-1 rounded-lg border border-red-600/50 bg-red-900/30 px-3 py-1.5 font-pixel text-[10px] text-red-300 hover:bg-red-800/40 disabled:opacity-50"
                                                    >
                                                        {loading === petition.id ? (
                                                            <Loader2 className="h-3 w-3 animate-spin" />
                                                        ) : (
                                                            <X className="h-3 w-3" />
                                                        )}
                                                        Deny
                                                    </button>
                                                </div>
                                            </div>
                                        ) : (
                                            <div className="flex gap-2">
                                                <button
                                                    onClick={() => setRespondingTo(petition.id)}
                                                    className="flex items-center gap-1 rounded-lg border border-amber-600/50 bg-amber-900/30 px-3 py-1.5 font-pixel text-[10px] text-amber-300 hover:bg-amber-800/40"
                                                >
                                                    <Scroll className="h-3 w-3" />
                                                    Review
                                                </button>
                                            </div>
                                        )}
                                    </div>
                                ))}
                            </div>
                        </div>
                    )}

                    {/* My Petitions */}
                    {my_petitions.length > 0 && (
                        <div>
                            <h2 className="mb-3 flex items-center gap-2 font-pixel text-sm text-stone-300">
                                <User className="h-4 w-4" />
                                Your Filed Petitions
                            </h2>
                            <div className="space-y-3">
                                {my_petitions.map((petition) => (
                                    <div
                                        key={petition.id}
                                        className="rounded-lg border border-stone-600/30 bg-stone-800/40 p-4"
                                    >
                                        <div className="mb-2 flex items-start justify-between">
                                            <div className="flex items-center gap-2">
                                                <span className="font-pixel text-xs text-stone-400">
                                                    Challenging
                                                </span>
                                                <span className="font-pixel text-sm text-amber-300">
                                                    {petition.target_holder.username}
                                                </span>
                                                <span className="font-pixel text-[10px] text-stone-500">
                                                    as {petition.target_role}
                                                </span>
                                            </div>
                                            <div className="flex items-center gap-2">
                                                <span className="rounded bg-amber-900/50 px-1.5 py-0.5 font-pixel text-[10px] text-amber-300">
                                                    Pending
                                                </span>
                                                {petition.expires_at && (
                                                    <span className="font-pixel text-[10px] text-stone-500">
                                                        {timeUntil(petition.expires_at)}
                                                    </span>
                                                )}
                                            </div>
                                        </div>

                                        <div className="mb-2 rounded bg-stone-900/50 p-2">
                                            <p className="font-pixel text-xs text-stone-400">
                                                "{petition.petition_reason}"
                                            </p>
                                        </div>

                                        <div className="flex items-center justify-between">
                                            <span className="font-pixel text-[10px] text-stone-500">
                                                Reviewing: {petition.authority.username}
                                            </span>
                                            <button
                                                onClick={() => handleWithdraw(petition.id)}
                                                disabled={withdrawing === petition.id}
                                                className="flex items-center gap-1 rounded-lg border border-red-600/50 bg-red-900/30 px-3 py-1.5 font-pixel text-[10px] text-red-300 hover:bg-red-800/40 disabled:opacity-50"
                                            >
                                                {withdrawing === petition.id ? (
                                                    <Loader2 className="h-3 w-3 animate-spin" />
                                                ) : (
                                                    <XCircle className="h-3 w-3" />
                                                )}
                                                Withdraw
                                            </button>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </div>
                    )}

                    {/* Empty State */}
                    {pending_petitions.length === 0 && my_petitions.length === 0 && (
                        <div className="flex flex-1 items-center justify-center py-16">
                            <div className="text-center">
                                <Scroll className="mx-auto mb-2 h-12 w-12 text-stone-600" />
                                <p className="font-pixel text-sm text-stone-500">
                                    No petitions to review
                                </p>
                                <p className="font-pixel text-xs text-stone-600">
                                    Petitions will appear here when citizens challenge role holders
                                </p>
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
