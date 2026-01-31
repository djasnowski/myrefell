import { Head, Link, router } from "@inertiajs/react";
import {
    Crown,
    ScrollText,
    Check,
    X,
    Coins,
    ChevronLeft,
    User,
    Clock,
    MessageSquare,
    Sparkles,
} from "lucide-react";
import { useState } from "react";
import AppLayout from "@/layouts/app-layout";
import type { BreadcrumbItem } from "@/types";

interface TitleType {
    id: number;
    name: string;
    description: string | null;
    style_of_address: string | null;
    requires_ceremony: boolean;
}

interface Petitioner {
    id: number;
    username: string;
    current_title: string | null;
    gold: number;
}

interface Petition {
    id: number;
    petitioner: Petitioner;
    title_type: TitleType;
    status: string;
    petition_message: string | null;
    is_purchase: boolean;
    gold_offered: number;
    domain_type: string | null;
    domain_name: string | null;
    created_at: string;
    expires_at: string | null;
}

interface Props {
    petition: Petition;
}

export default function Review({ petition }: Props) {
    const [processing, setProcessing] = useState(false);
    const [responseMessage, setResponseMessage] = useState("");
    const [showDenyForm, setShowDenyForm] = useState(false);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: "Dashboard", href: "/dashboard" },
        { title: "Titles", href: "/titles" },
        { title: "Review Petition", href: "#" },
    ];

    const handleApprove = () => {
        if (processing) return;
        setProcessing(true);
        router.post(
            `/titles/review/${petition.id}/approve`,
            {
                response_message: responseMessage || null,
            },
            {
                onSuccess: () => {
                    router.reload();
                },
                onFinish: () => setProcessing(false),
            },
        );
    };

    const handleDeny = () => {
        if (processing) return;
        setProcessing(true);
        router.post(
            `/titles/review/${petition.id}/deny`,
            {
                response_message: responseMessage || null,
            },
            {
                onSuccess: () => {
                    router.reload();
                },
                onFinish: () => setProcessing(false),
            },
        );
    };

    const isPending = petition.status === "pending";

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Review Petition" />
            <div className="flex h-full flex-1 flex-col overflow-auto p-4">
                {/* Back Link */}
                <Link
                    href="/titles"
                    className="mb-4 inline-flex items-center gap-1 font-pixel text-xs text-stone-400 hover:text-stone-300"
                >
                    <ChevronLeft className="h-4 w-4" />
                    Back to Titles
                </Link>

                <div className="mx-auto w-full max-w-2xl space-y-6">
                    {/* Header */}
                    <div className="flex items-center gap-4">
                        <div className="flex h-14 w-14 items-center justify-center rounded-lg bg-amber-900/30">
                            <ScrollText className="h-8 w-8 text-amber-400" />
                        </div>
                        <div>
                            <h1 className="font-pixel text-2xl text-amber-400">Review Petition</h1>
                            <p className="font-pixel text-xs text-stone-500">
                                Submitted {petition.created_at}
                            </p>
                        </div>
                    </div>

                    {/* Petitioner Info */}
                    <div className="rounded-xl border-2 border-stone-700 bg-stone-800/50 p-4">
                        <h2 className="mb-4 flex items-center gap-2 font-pixel text-sm text-stone-300">
                            <User className="h-4 w-4 text-blue-400" />
                            Petitioner
                        </h2>
                        <div className="flex items-center gap-4">
                            <div className="flex h-12 w-12 items-center justify-center rounded-full bg-blue-900/30">
                                <User className="h-6 w-6 text-blue-400" />
                            </div>
                            <div>
                                <div className="font-pixel text-lg text-stone-200">
                                    {petition.petitioner.username}
                                </div>
                                {petition.petitioner.current_title && (
                                    <div className="font-pixel text-xs text-stone-500">
                                        {petition.petitioner.current_title}
                                    </div>
                                )}
                                <div className="flex items-center gap-1 font-pixel text-xs text-amber-400">
                                    <Coins className="h-3 w-3" />
                                    {petition.petitioner.gold.toLocaleString()}g
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Title Requested */}
                    <div className="rounded-xl border-2 border-amber-600/50 bg-amber-900/20 p-4">
                        <h2 className="mb-4 flex items-center gap-2 font-pixel text-sm text-stone-300">
                            <Crown className="h-4 w-4 text-amber-400" />
                            Title Requested
                        </h2>
                        <div className="flex items-center gap-3">
                            <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-amber-900/50">
                                <Crown className="h-5 w-5 text-amber-400" />
                            </div>
                            <div>
                                <div className="font-pixel text-lg text-amber-400">
                                    {petition.title_type.name}
                                </div>
                                {petition.domain_name && (
                                    <div className="font-pixel text-xs text-stone-500">
                                        of {petition.domain_name}
                                    </div>
                                )}
                            </div>
                        </div>
                        {petition.title_type.description && (
                            <p className="mt-3 font-pixel text-xs text-stone-400">
                                {petition.title_type.description}
                            </p>
                        )}
                        {petition.title_type.style_of_address && (
                            <div className="mt-3 rounded-lg border border-stone-700 bg-stone-900/50 p-2">
                                <div className="font-pixel text-[10px] text-stone-500">
                                    Style of Address
                                </div>
                                <div className="font-pixel text-xs text-stone-300">
                                    {petition.title_type.style_of_address}
                                </div>
                            </div>
                        )}
                        {petition.title_type.requires_ceremony && (
                            <div className="mt-3 flex items-center gap-2 font-pixel text-xs text-purple-400">
                                <Sparkles className="h-4 w-4" />
                                This title requires a ceremony after approval
                            </div>
                        )}
                    </div>

                    {/* Purchase Offer */}
                    {petition.is_purchase && (
                        <div className="rounded-xl border-2 border-amber-600/50 bg-amber-900/20 p-4">
                            <div className="flex items-center gap-2">
                                <Coins className="h-5 w-5 text-amber-400" />
                                <span className="font-pixel text-sm text-amber-400">
                                    Purchase Offer
                                </span>
                            </div>
                            <div className="mt-2 font-pixel text-2xl text-amber-300">
                                {petition.gold_offered.toLocaleString()}g
                            </div>
                            <p className="mt-1 font-pixel text-[10px] text-stone-500">
                                Gold will be transferred to you upon approval
                            </p>
                        </div>
                    )}

                    {/* Petition Message */}
                    {petition.petition_message && (
                        <div className="rounded-xl border-2 border-stone-700 bg-stone-800/50 p-4">
                            <h2 className="mb-3 flex items-center gap-2 font-pixel text-sm text-stone-300">
                                <MessageSquare className="h-4 w-4 text-green-400" />
                                Petitioner's Message
                            </h2>
                            <div className="rounded-lg border border-stone-700 bg-stone-900/50 p-3">
                                <p className="font-pixel text-sm text-stone-300 whitespace-pre-wrap">
                                    {petition.petition_message}
                                </p>
                            </div>
                        </div>
                    )}

                    {/* Expiration */}
                    {petition.expires_at && (
                        <div className="flex items-center gap-2 rounded-lg border border-stone-700 bg-stone-800/50 p-3">
                            <Clock className="h-4 w-4 text-stone-500" />
                            <span className="font-pixel text-xs text-stone-500">
                                Expires: {petition.expires_at}
                            </span>
                        </div>
                    )}

                    {/* Response Form */}
                    {isPending && (
                        <div className="rounded-xl border-2 border-stone-700 bg-stone-800/50 p-4">
                            <h2 className="mb-4 flex items-center gap-2 font-pixel text-sm text-stone-300">
                                <ScrollText className="h-4 w-4 text-amber-400" />
                                Your Response
                            </h2>

                            <div className="mb-4">
                                <label className="mb-2 block font-pixel text-xs text-stone-400">
                                    Response Message (optional)
                                </label>
                                <textarea
                                    value={responseMessage}
                                    onChange={(e) => setResponseMessage(e.target.value)}
                                    maxLength={500}
                                    rows={3}
                                    placeholder="Add a message to accompany your decision..."
                                    className="w-full rounded-lg border border-stone-600 bg-stone-900 px-3 py-2 font-pixel text-sm text-stone-200 placeholder:text-stone-600"
                                />
                                <div className="mt-1 text-right font-pixel text-[10px] text-stone-600">
                                    {responseMessage.length}/500
                                </div>
                            </div>

                            {!showDenyForm ? (
                                <div className="flex gap-3">
                                    <button
                                        onClick={handleApprove}
                                        disabled={processing}
                                        className="flex flex-1 items-center justify-center gap-2 rounded-lg bg-green-600 py-3 font-pixel text-sm text-white transition hover:bg-green-500 disabled:opacity-50"
                                    >
                                        <Check className="h-4 w-4" />
                                        {processing ? "Processing..." : "Approve Petition"}
                                    </button>
                                    <button
                                        onClick={() => setShowDenyForm(true)}
                                        disabled={processing}
                                        className="flex flex-1 items-center justify-center gap-2 rounded-lg bg-red-600 py-3 font-pixel text-sm text-white transition hover:bg-red-500 disabled:opacity-50"
                                    >
                                        <X className="h-4 w-4" />
                                        Deny Petition
                                    </button>
                                </div>
                            ) : (
                                <div className="space-y-3">
                                    <div className="rounded-lg border border-red-600/50 bg-red-900/20 p-3">
                                        <p className="font-pixel text-xs text-red-400">
                                            Are you sure you want to deny this petition?
                                        </p>
                                    </div>
                                    <div className="flex gap-3">
                                        <button
                                            onClick={handleDeny}
                                            disabled={processing}
                                            className="flex flex-1 items-center justify-center gap-2 rounded-lg bg-red-600 py-3 font-pixel text-sm text-white transition hover:bg-red-500 disabled:opacity-50"
                                        >
                                            <X className="h-4 w-4" />
                                            {processing ? "Processing..." : "Confirm Denial"}
                                        </button>
                                        <button
                                            onClick={() => setShowDenyForm(false)}
                                            disabled={processing}
                                            className="flex-1 rounded-lg border border-stone-600 py-3 font-pixel text-sm text-stone-400 transition hover:bg-stone-700"
                                        >
                                            Cancel
                                        </button>
                                    </div>
                                </div>
                            )}

                            {petition.is_purchase && (
                                <p className="mt-3 font-pixel text-[10px] text-amber-400 text-center">
                                    If approved, {petition.gold_offered.toLocaleString()}g will be
                                    transferred to you.
                                </p>
                            )}
                        </div>
                    )}

                    {/* Already Processed */}
                    {!isPending && (
                        <div className="rounded-xl border-2 border-stone-700 bg-stone-800/50 p-4 text-center">
                            <p className="font-pixel text-xs text-stone-400">
                                This petition has already been processed.
                            </p>
                            <div className="mt-2">
                                <span
                                    className={`inline-block rounded px-3 py-1 font-pixel text-xs ${
                                        petition.status === "approved" ||
                                        petition.status === "ceremony_pending"
                                            ? "bg-green-900/30 text-green-400"
                                            : petition.status === "denied"
                                              ? "bg-red-900/30 text-red-400"
                                              : "bg-stone-800/50 text-stone-400"
                                    }`}
                                >
                                    {petition.status === "ceremony_pending"
                                        ? "Approved (Awaiting Ceremony)"
                                        : petition.status.charAt(0).toUpperCase() +
                                          petition.status.slice(1)}
                                </span>
                            </div>
                            <Link
                                href="/titles"
                                className="mt-4 inline-block rounded-lg bg-stone-700 px-4 py-2 font-pixel text-xs text-stone-300 transition hover:bg-stone-600"
                            >
                                Back to Titles
                            </Link>
                        </div>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
