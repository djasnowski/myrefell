import { Head, Link, usePage } from "@inertiajs/react";
import {
    AlertTriangle,
    Ban,
    Clock,
    Coins,
    FileText,
    Gavel,
    Lock,
    MapPin,
    Scale,
    Shield,
    Skull,
    Target,
    User,
} from "lucide-react";
import AppLayout from "@/layouts/app-layout";
import type { BreadcrumbItem } from "@/types";

interface JailInfo {
    location: string;
    remaining_days: number;
    release_at: string;
}

interface OutlawInfo {
    declared_by: string;
    reason: string;
}

interface Exile {
    id: number;
    location: string;
    location_type: string;
    reason: string;
    expires_at: string | null;
    is_permanent: boolean;
}

interface Bounty {
    id: number;
    reward: number;
    capture_type: string;
    reason: string;
    posted_by: string;
}

interface PendingTrial {
    id: number;
    crime: string;
    court: string;
    judge: string | null;
    scheduled_at: string | null;
    status: string;
}

interface PastPunishment {
    id: number;
    type: string;
    description: string;
    status: string;
    crime: string | null;
    created_at: string;
}

interface PageProps {
    player: {
        id: number;
        username: string;
    };
    status: {
        is_jailed: boolean;
        is_outlaw: boolean;
        jail_info: JailInfo | null;
        outlaw_info: OutlawInfo | null;
    };
    exiles: Exile[];
    bounties: Bounty[];
    punishments: PastPunishment[];
    pending_trials: PendingTrial[];
    [key: string]: unknown;
}

const statusColors = {
    pending: "text-yellow-300 bg-yellow-900/50",
    active: "text-red-300 bg-red-900/50",
    completed: "text-green-300 bg-green-900/50",
    pardoned: "text-blue-300 bg-blue-900/50",
};

export default function CrimeIndex() {
    const { player, status, exiles, bounties, punishments, pending_trials } =
        usePage<PageProps>().props;

    const breadcrumbs: BreadcrumbItem[] = [
        { title: "Dashboard", href: "/dashboard" },
        { title: "Criminal Record", href: "#" },
    ];

    const hasIssues =
        status.is_jailed || status.is_outlaw || exiles.length > 0 || bounties.length > 0;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Criminal Record" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-y-auto p-4">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="font-pixel text-2xl text-amber-400">Criminal Record</h1>
                        <p className="flex items-center gap-1 font-pixel text-sm text-stone-400">
                            <User className="h-3 w-3" />
                            {player.username}'s standing with the law
                        </p>
                    </div>
                    <div className="flex flex-wrap gap-2">
                        <Link
                            href="/crime/court"
                            className="flex items-center gap-2 rounded-lg border border-purple-600/50 bg-purple-900/20 px-3 py-2 font-pixel text-xs text-purple-300 transition hover:bg-purple-800/30"
                        >
                            <Scale className="h-4 w-4" />
                            Court Docket
                        </Link>
                        <Link
                            href="/crime/accuse"
                            className="flex items-center gap-2 rounded-lg border border-red-600/50 bg-red-900/20 px-3 py-2 font-pixel text-xs text-red-300 transition hover:bg-red-800/30"
                        >
                            <Gavel className="h-4 w-4" />
                            File Accusation
                        </Link>
                        <Link
                            href="/crime/bounties"
                            className="flex items-center gap-2 rounded-lg border border-amber-600/50 bg-amber-900/20 px-3 py-2 font-pixel text-xs text-amber-300 transition hover:bg-amber-800/30"
                        >
                            <Target className="h-4 w-4" />
                            Bounty Board
                        </Link>
                        <Link
                            href="/crime/types"
                            className="flex items-center gap-2 rounded-lg border border-stone-600/50 bg-stone-800/50 px-3 py-2 font-pixel text-xs text-stone-300 transition hover:bg-stone-700/50"
                        >
                            <FileText className="h-4 w-4" />
                            Crime Types
                        </Link>
                    </div>
                </div>

                {/* Current Status */}
                <div
                    className={`rounded-xl border-2 p-4 ${
                        hasIssues
                            ? "border-red-500/50 bg-red-900/20"
                            : "border-green-500/50 bg-green-900/20"
                    }`}
                >
                    <div className="flex items-center gap-3">
                        <div
                            className={`rounded-lg p-3 ${hasIssues ? "bg-red-800/50" : "bg-green-800/50"}`}
                        >
                            {hasIssues ? (
                                <AlertTriangle className="h-6 w-6 text-red-300" />
                            ) : (
                                <Shield className="h-6 w-6 text-green-300" />
                            )}
                        </div>
                        <div>
                            <h2
                                className={`font-pixel text-lg ${hasIssues ? "text-red-300" : "text-green-300"}`}
                            >
                                {hasIssues ? "Criminal Status" : "Clean Record"}
                            </h2>
                            <p className="font-pixel text-xs text-stone-400">
                                {hasIssues
                                    ? "You have outstanding legal issues"
                                    : "You are in good standing with the law"}
                            </p>
                        </div>
                    </div>
                </div>

                {/* Jailed Status */}
                {status.is_jailed && status.jail_info && (
                    <div className="rounded-xl border-2 border-red-500/50 bg-red-900/20 p-4">
                        <div className="flex items-center gap-3">
                            <div className="rounded-lg bg-red-800/50 p-3">
                                <Lock className="h-6 w-6 text-red-300" />
                            </div>
                            <div className="flex-1">
                                <h3 className="font-pixel text-lg text-red-300">Imprisoned</h3>
                                <div className="flex items-center gap-4 text-sm">
                                    <div className="flex items-center gap-1">
                                        <MapPin className="h-4 w-4 text-stone-400" />
                                        <span className="text-stone-300">
                                            {status.jail_info.location}
                                        </span>
                                    </div>
                                    <div className="flex items-center gap-1">
                                        <Clock className="h-4 w-4 text-stone-400" />
                                        <span className="text-stone-300">
                                            {status.jail_info.remaining_days} days remaining
                                        </span>
                                    </div>
                                </div>
                                <p className="mt-1 font-pixel text-[10px] text-stone-500">
                                    Release: {status.jail_info.release_at}
                                </p>
                            </div>
                        </div>
                    </div>
                )}

                {/* Outlaw Status */}
                {status.is_outlaw && status.outlaw_info && (
                    <div className="rounded-xl border-2 border-red-500/50 bg-red-900/20 p-4">
                        <div className="flex items-center gap-3">
                            <div className="rounded-lg bg-red-800/50 p-3">
                                <Skull className="h-6 w-6 text-red-300" />
                            </div>
                            <div className="flex-1">
                                <h3 className="font-pixel text-lg text-red-300">Outlaw</h3>
                                <p className="text-sm text-stone-300">
                                    Declared by: {status.outlaw_info.declared_by}
                                </p>
                                <p className="font-pixel text-xs text-stone-400">
                                    {status.outlaw_info.reason}
                                </p>
                                <p className="mt-1 font-pixel text-[10px] text-red-400">
                                    Anyone may kill you without legal consequence
                                </p>
                            </div>
                        </div>
                    </div>
                )}

                {/* Exiles */}
                {exiles.length > 0 && (
                    <div className="rounded-xl border-2 border-stone-600/50 bg-stone-800/30 p-4">
                        <h3 className="mb-3 flex items-center gap-2 font-pixel text-lg text-amber-300">
                            <Ban className="h-5 w-5" />
                            Exile Orders
                        </h3>
                        <div className="space-y-2">
                            {exiles.map((exile) => (
                                <div
                                    key={exile.id}
                                    className="flex items-center justify-between rounded-lg border border-stone-600/50 bg-stone-800/50 p-3"
                                >
                                    <div>
                                        <div className="flex items-center gap-2">
                                            <MapPin className="h-4 w-4 text-red-400" />
                                            <span className="font-pixel text-sm text-stone-200">
                                                {exile.location} ({exile.location_type})
                                            </span>
                                        </div>
                                        <p className="font-pixel text-[10px] text-stone-400">
                                            {exile.reason}
                                        </p>
                                    </div>
                                    <span
                                        className={`font-pixel text-xs ${exile.is_permanent ? "text-red-300" : "text-yellow-300"}`}
                                    >
                                        {exile.is_permanent
                                            ? "Permanent"
                                            : `Expires ${exile.expires_at}`}
                                    </span>
                                </div>
                            ))}
                        </div>
                    </div>
                )}

                {/* Bounties on Player */}
                {bounties.length > 0 && (
                    <div className="rounded-xl border-2 border-amber-500/50 bg-amber-900/20 p-4">
                        <h3 className="mb-3 flex items-center gap-2 font-pixel text-lg text-amber-300">
                            <Target className="h-5 w-5" />
                            Bounties on Your Head
                        </h3>
                        <div className="space-y-2">
                            {bounties.map((bounty) => (
                                <div
                                    key={bounty.id}
                                    className="flex items-center justify-between rounded-lg border border-amber-600/50 bg-amber-900/30 p-3"
                                >
                                    <div>
                                        <div className="flex items-center gap-2">
                                            <Coins className="h-4 w-4 text-amber-400" />
                                            <span className="font-pixel text-sm text-amber-300">
                                                {bounty.reward.toLocaleString()} gold
                                            </span>
                                            <span className="rounded bg-stone-800/50 px-1.5 py-0.5 font-pixel text-[10px] text-stone-300">
                                                {bounty.capture_type}
                                            </span>
                                        </div>
                                        <p className="font-pixel text-[10px] text-stone-400">
                                            {bounty.reason}
                                        </p>
                                    </div>
                                    <span className="font-pixel text-xs text-stone-400">
                                        by {bounty.posted_by}
                                    </span>
                                </div>
                            ))}
                        </div>
                    </div>
                )}

                {/* Pending Trials */}
                {pending_trials.length > 0 && (
                    <div className="rounded-xl border-2 border-purple-500/50 bg-purple-900/20 p-4">
                        <h3 className="mb-3 flex items-center gap-2 font-pixel text-lg text-purple-300">
                            <Gavel className="h-5 w-5" />
                            Pending Trials
                        </h3>
                        <div className="space-y-2">
                            {pending_trials.map((trial) => (
                                <Link
                                    key={trial.id}
                                    href={`/crime/trials/${trial.id}`}
                                    className="block rounded-lg border border-purple-600/50 bg-purple-900/30 p-3 transition hover:border-purple-500 hover:bg-purple-900/40"
                                >
                                    <div className="flex items-center justify-between">
                                        <span className="font-pixel text-sm text-purple-300">
                                            {trial.crime}
                                        </span>
                                        <span className="rounded bg-stone-800/50 px-1.5 py-0.5 font-pixel text-[10px] text-stone-300">
                                            {trial.status}
                                        </span>
                                    </div>
                                    <div className="mt-1 flex items-center gap-4 text-[10px] text-stone-400">
                                        <span>Court: {trial.court}</span>
                                        {trial.judge && <span>Judge: {trial.judge}</span>}
                                        {trial.scheduled_at && (
                                            <span>Scheduled: {trial.scheduled_at}</span>
                                        )}
                                    </div>
                                </Link>
                            ))}
                        </div>
                    </div>
                )}

                {/* Past Punishments */}
                {punishments.length > 0 && (
                    <div className="rounded-xl border-2 border-stone-600/50 bg-stone-800/30 p-4">
                        <h3 className="mb-3 flex items-center gap-2 font-pixel text-lg text-stone-300">
                            <Scale className="h-5 w-5" />
                            Punishment History
                        </h3>
                        <div className="space-y-2">
                            {punishments.map((punishment) => (
                                <div
                                    key={punishment.id}
                                    className="flex items-center justify-between rounded-lg border border-stone-600/50 bg-stone-800/50 p-3"
                                >
                                    <div>
                                        <div className="flex items-center gap-2">
                                            <span className="font-pixel text-sm text-stone-200">
                                                {punishment.type}
                                            </span>
                                            {punishment.crime && (
                                                <span className="font-pixel text-[10px] text-stone-500">
                                                    for {punishment.crime}
                                                </span>
                                            )}
                                        </div>
                                        <p className="font-pixel text-[10px] text-stone-400">
                                            {punishment.description}
                                        </p>
                                    </div>
                                    <div className="text-right">
                                        <span
                                            className={`rounded px-1.5 py-0.5 font-pixel text-[10px] ${
                                                statusColors[
                                                    punishment.status as keyof typeof statusColors
                                                ] || "text-stone-300 bg-stone-800/50"
                                            }`}
                                        >
                                            {punishment.status}
                                        </span>
                                        <p className="mt-1 font-pixel text-[10px] text-stone-500">
                                            {punishment.created_at}
                                        </p>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>
                )}

                {/* No Record */}
                {!hasIssues && punishments.length === 0 && pending_trials.length === 0 && (
                    <div className="flex flex-1 items-center justify-center py-12">
                        <div className="text-center">
                            <Shield className="mx-auto mb-3 h-16 w-16 text-green-600" />
                            <p className="font-pixel text-base text-green-400">Spotless Record</p>
                            <p className="font-pixel text-xs text-stone-500">
                                You have never been convicted of any crime.
                            </p>
                        </div>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
