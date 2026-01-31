import { Head, Link, router } from "@inertiajs/react";
import {
    Building2,
    Calendar,
    Castle,
    ChevronLeft,
    ChevronRight,
    Clock,
    Crown,
    Gavel,
    MapPin,
    Scale,
    Search,
    Shield,
    User,
} from "lucide-react";
import { useState } from "react";
import AppLayout from "@/layouts/app-layout";
import type { BreadcrumbItem } from "@/types";

interface Trial {
    id: number;
    defendant: {
        id: number;
        username: string;
    };
    crime: {
        name: string;
        severity: string;
        description: string;
    };
    court_level: string;
    court_display: string;
    location: {
        id: number;
        name: string;
        type: string;
    };
    judge: {
        id: number;
        username: string;
    } | null;
    status: string;
    status_display: string;
    scheduled_at: string | null;
    started_at: string | null;
}

interface CourtStats {
    total_pending: number;
    village_trials: number;
    barony_trials: number;
    kingdom_trials: number;
    scheduled_today: number;
}

interface Props {
    trials: {
        data: Trial[];
        current_page: number;
        last_page: number;
        total: number;
    };
    stats: CourtStats;
    filter: string | null;
    current_location: {
        village: { id: number; name: string } | null;
        barony: { id: number; name: string } | null;
        kingdom: { id: number; name: string } | null;
    };
}

const COURT_LEVELS = [
    { value: "", label: "All Courts", icon: Scale },
    { value: "village", label: "Village Court", icon: Building2 },
    { value: "barony", label: "Baron's Court", icon: Castle },
    { value: "kingdom", label: "Royal Court", icon: Crown },
];

const SEVERITY_COLORS: Record<string, string> = {
    minor: "text-yellow-400 bg-yellow-900/30 border-yellow-600/50",
    moderate: "text-orange-400 bg-orange-900/30 border-orange-600/50",
    serious: "text-red-400 bg-red-900/30 border-red-600/50",
    capital: "text-red-300 bg-red-800/50 border-red-500/50",
};

const STATUS_COLORS: Record<string, string> = {
    scheduled: "text-blue-400 bg-blue-900/30",
    in_progress: "text-amber-400 bg-amber-900/30",
    awaiting_verdict: "text-purple-400 bg-purple-900/30",
};

export default function Court({ trials, stats, filter, current_location }: Props) {
    const [selectedFilter, setSelectedFilter] = useState(filter || "");

    const breadcrumbs: BreadcrumbItem[] = [
        { title: "Dashboard", href: "/dashboard" },
        { title: "Criminal Record", href: "/crime" },
        { title: "Court Docket", href: "/crime/court" },
    ];

    const handleFilterChange = (value: string) => {
        setSelectedFilter(value);
        router.get("/crime/court", value ? { court: value } : {}, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const goToPage = (page: number) => {
        router.get(
            "/crime/court",
            {
                page,
                ...(selectedFilter ? { court: selectedFilter } : {}),
            },
            {
                preserveState: true,
                preserveScroll: true,
            },
        );
    };

    const getCourtIcon = (level: string) => {
        switch (level) {
            case "village":
                return Building2;
            case "barony":
                return Castle;
            case "kingdom":
                return Crown;
            default:
                return Scale;
        }
    };

    const getCourtColor = (level: string) => {
        switch (level) {
            case "village":
                return "border-green-600/50 bg-green-900/20";
            case "barony":
                return "border-blue-600/50 bg-blue-900/20";
            case "kingdom":
                return "border-purple-600/50 bg-purple-900/20";
            default:
                return "border-stone-600/50 bg-stone-800/30";
        }
    };

    const getCourtTextColor = (level: string) => {
        switch (level) {
            case "village":
                return "text-green-400";
            case "barony":
                return "text-blue-400";
            case "kingdom":
                return "text-purple-400";
            default:
                return "text-stone-400";
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Court Docket" />
            <div className="flex h-full flex-1 flex-col overflow-auto p-4">
                {/* Header */}
                <div className="mb-6 flex items-center justify-between">
                    <div className="flex items-center gap-4">
                        <Link
                            href="/crime"
                            className="rounded-lg border border-stone-700 p-2 transition hover:bg-stone-800"
                        >
                            <ChevronLeft className="h-5 w-5 text-stone-400" />
                        </Link>
                        <div className="flex h-12 w-12 items-center justify-center rounded-lg bg-purple-900/30">
                            <Gavel className="h-7 w-7 text-purple-400" />
                        </div>
                        <div>
                            <h1 className="font-pixel text-xl text-amber-400">Court Docket</h1>
                            <p className="font-pixel text-xs text-stone-500">
                                Pending trials in your jurisdiction
                            </p>
                        </div>
                    </div>
                </div>

                <div className="mx-auto w-full max-w-4xl">
                    {/* Current Jurisdiction */}
                    <div className="mb-4 rounded-lg border border-stone-700 bg-stone-800/30 p-3">
                        <div className="flex items-center gap-2 font-pixel text-xs text-stone-400">
                            <MapPin className="h-4 w-4" />
                            <span>Your jurisdiction:</span>
                            {current_location.village && (
                                <span className="rounded bg-green-900/30 px-2 py-0.5 text-green-400">
                                    {current_location.village.name}
                                </span>
                            )}
                            {current_location.barony && (
                                <span className="rounded bg-blue-900/30 px-2 py-0.5 text-blue-400">
                                    {current_location.barony.name}
                                </span>
                            )}
                            {current_location.kingdom && (
                                <span className="rounded bg-purple-900/30 px-2 py-0.5 text-purple-400">
                                    {current_location.kingdom.name}
                                </span>
                            )}
                        </div>
                    </div>

                    {/* Stats */}
                    <div className="mb-4 grid grid-cols-2 gap-3 md:grid-cols-5">
                        <div className="rounded-lg border border-stone-700 bg-stone-800/50 p-3 text-center">
                            <div className="font-pixel text-lg text-stone-300">
                                {stats.total_pending}
                            </div>
                            <div className="font-pixel text-[10px] text-stone-500">
                                Total Pending
                            </div>
                        </div>
                        <div className="rounded-lg border border-green-600/50 bg-green-900/20 p-3 text-center">
                            <div className="font-pixel text-lg text-green-400">
                                {stats.village_trials}
                            </div>
                            <div className="font-pixel text-[10px] text-stone-500">
                                Village Court
                            </div>
                        </div>
                        <div className="rounded-lg border border-blue-600/50 bg-blue-900/20 p-3 text-center">
                            <div className="font-pixel text-lg text-blue-400">
                                {stats.barony_trials}
                            </div>
                            <div className="font-pixel text-[10px] text-stone-500">
                                Baron's Court
                            </div>
                        </div>
                        <div className="rounded-lg border border-purple-600/50 bg-purple-900/20 p-3 text-center">
                            <div className="font-pixel text-lg text-purple-400">
                                {stats.kingdom_trials}
                            </div>
                            <div className="font-pixel text-[10px] text-stone-500">Royal Court</div>
                        </div>
                        <div className="rounded-lg border border-amber-600/50 bg-amber-900/20 p-3 text-center">
                            <Clock className="mx-auto mb-1 h-4 w-4 text-amber-400" />
                            <div className="font-pixel text-lg text-amber-400">
                                {stats.scheduled_today}
                            </div>
                            <div className="font-pixel text-[10px] text-stone-500">Today</div>
                        </div>
                    </div>

                    {/* Filter */}
                    <div className="mb-4 rounded-xl border-2 border-stone-700 bg-stone-800/50 p-4">
                        <div className="flex items-center gap-2 mb-3">
                            <Search className="h-4 w-4 text-stone-400" />
                            <span className="font-pixel text-xs text-stone-400">
                                Filter by Court
                            </span>
                        </div>
                        <div className="flex flex-wrap gap-2">
                            {COURT_LEVELS.map((court) => {
                                const Icon = court.icon;
                                return (
                                    <button
                                        key={court.value}
                                        onClick={() => handleFilterChange(court.value)}
                                        className={`flex items-center gap-1.5 rounded-lg border px-3 py-1.5 font-pixel text-xs transition ${
                                            selectedFilter === court.value
                                                ? "border-amber-600 bg-amber-900/30 text-amber-400"
                                                : "border-stone-700 bg-stone-800/30 text-stone-400 hover:bg-stone-700"
                                        }`}
                                    >
                                        <Icon className="h-3.5 w-3.5" />
                                        {court.label}
                                    </button>
                                );
                            })}
                        </div>
                    </div>

                    {/* Trials List */}
                    <div className="rounded-xl border-2 border-stone-700 bg-stone-800/50 p-4">
                        <h2 className="mb-4 flex items-center gap-2 font-pixel text-sm text-stone-300">
                            <Scale className="h-4 w-4 text-amber-400" />
                            Pending Trials ({trials.total})
                        </h2>

                        {trials.data.length === 0 ? (
                            <div className="py-12 text-center">
                                <Shield className="mx-auto mb-3 h-12 w-12 text-stone-600" />
                                <div className="font-pixel text-sm text-stone-500">
                                    No pending trials
                                </div>
                                <p className="mt-1 font-pixel text-xs text-stone-600">
                                    {selectedFilter
                                        ? "Try a different court filter"
                                        : "The courts are quiet in your jurisdiction"}
                                </p>
                            </div>
                        ) : (
                            <div className="space-y-3">
                                {trials.data.map((trial) => {
                                    const CourtIcon = getCourtIcon(trial.court_level);
                                    return (
                                        <Link
                                            key={trial.id}
                                            href={`/crime/trials/${trial.id}`}
                                            className={`block rounded-lg border p-4 transition hover:border-amber-600/50 ${getCourtColor(trial.court_level)}`}
                                        >
                                            <div className="flex items-start justify-between gap-4">
                                                <div className="flex items-start gap-3">
                                                    <div
                                                        className={`rounded-lg p-2 ${getCourtColor(trial.court_level)}`}
                                                    >
                                                        <CourtIcon
                                                            className={`h-5 w-5 ${getCourtTextColor(trial.court_level)}`}
                                                        />
                                                    </div>
                                                    <div>
                                                        <div className="flex items-center gap-2">
                                                            <span className="font-pixel text-sm text-stone-200">
                                                                {trial.crime.name}
                                                            </span>
                                                            <span
                                                                className={`rounded border px-1.5 py-0.5 font-pixel text-[10px] ${
                                                                    SEVERITY_COLORS[
                                                                        trial.crime.severity
                                                                    ] ||
                                                                    "text-stone-400 bg-stone-800/50 border-stone-600"
                                                                }`}
                                                            >
                                                                {trial.crime.severity}
                                                            </span>
                                                        </div>
                                                        <div className="mt-1 flex items-center gap-3 font-pixel text-[10px] text-stone-500">
                                                            <span className="flex items-center gap-1">
                                                                <User className="h-3 w-3" />
                                                                Defendant:{" "}
                                                                {trial.defendant.username}
                                                            </span>
                                                            <span
                                                                className={getCourtTextColor(
                                                                    trial.court_level,
                                                                )}
                                                            >
                                                                {trial.court_display}
                                                            </span>
                                                        </div>
                                                        <div className="mt-1 flex items-center gap-3 font-pixel text-[10px] text-stone-500">
                                                            <span className="flex items-center gap-1">
                                                                <MapPin className="h-3 w-3" />
                                                                {trial.location.name}
                                                            </span>
                                                            {trial.judge && (
                                                                <span className="flex items-center gap-1">
                                                                    <Gavel className="h-3 w-3" />
                                                                    Judge: {trial.judge.username}
                                                                </span>
                                                            )}
                                                        </div>
                                                    </div>
                                                </div>
                                                <div className="text-right">
                                                    <span
                                                        className={`rounded px-2 py-0.5 font-pixel text-[10px] ${
                                                            STATUS_COLORS[trial.status] ||
                                                            "text-stone-400 bg-stone-800/50"
                                                        }`}
                                                    >
                                                        {trial.status_display}
                                                    </span>
                                                    {trial.scheduled_at && (
                                                        <div className="mt-2 flex items-center gap-1 font-pixel text-[10px] text-stone-500">
                                                            <Calendar className="h-3 w-3" />
                                                            {trial.scheduled_at}
                                                        </div>
                                                    )}
                                                </div>
                                            </div>
                                        </Link>
                                    );
                                })}
                            </div>
                        )}

                        {/* Pagination */}
                        {trials.last_page > 1 && (
                            <div className="mt-6 flex items-center justify-center gap-2">
                                <button
                                    onClick={() => goToPage(trials.current_page - 1)}
                                    disabled={trials.current_page === 1}
                                    className="rounded-lg border border-stone-700 p-2 transition hover:bg-stone-700 disabled:opacity-50"
                                >
                                    <ChevronLeft className="h-4 w-4 text-stone-400" />
                                </button>
                                <span className="font-pixel text-xs text-stone-400">
                                    Page {trials.current_page} of {trials.last_page}
                                </span>
                                <button
                                    onClick={() => goToPage(trials.current_page + 1)}
                                    disabled={trials.current_page === trials.last_page}
                                    className="rounded-lg border border-stone-700 p-2 transition hover:bg-stone-700 disabled:opacity-50"
                                >
                                    <ChevronRight className="h-4 w-4 text-stone-400" />
                                </button>
                            </div>
                        )}
                    </div>

                    {/* Court Info */}
                    <div className="mt-4 rounded-xl border border-stone-700 bg-stone-800/30 p-4">
                        <h3 className="mb-3 font-pixel text-xs text-stone-400">Court Hierarchy</h3>
                        <div className="grid gap-3 md:grid-cols-3">
                            <div className="rounded-lg border border-green-600/30 bg-green-900/10 p-3">
                                <div className="flex items-center gap-2 mb-1">
                                    <Building2 className="h-4 w-4 text-green-400" />
                                    <span className="font-pixel text-xs text-green-400">
                                        Village Court
                                    </span>
                                </div>
                                <p className="font-pixel text-[10px] text-stone-500">
                                    Minor offenses. Presided by village elder or appointed judge.
                                </p>
                            </div>
                            <div className="rounded-lg border border-blue-600/30 bg-blue-900/10 p-3">
                                <div className="flex items-center gap-2 mb-1">
                                    <Castle className="h-4 w-4 text-blue-400" />
                                    <span className="font-pixel text-xs text-blue-400">
                                        Baron's Court
                                    </span>
                                </div>
                                <p className="font-pixel text-[10px] text-stone-500">
                                    Moderate offenses and appeals. Presided by the Baron or
                                    delegate.
                                </p>
                            </div>
                            <div className="rounded-lg border border-purple-600/30 bg-purple-900/10 p-3">
                                <div className="flex items-center gap-2 mb-1">
                                    <Crown className="h-4 w-4 text-purple-400" />
                                    <span className="font-pixel text-xs text-purple-400">
                                        Royal Court
                                    </span>
                                </div>
                                <p className="font-pixel text-[10px] text-stone-500">
                                    Serious crimes and final appeals. Presided by the King or
                                    Chancellor.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
