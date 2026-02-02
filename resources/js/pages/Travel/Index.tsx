import { Head, router, usePage } from "@inertiajs/react";
import { Castle, Church, Clock, Home, Loader2, MapPin, Trees, X, Zap } from "lucide-react";
import { useEffect, useRef, useState } from "react";
import AppLayout from "@/layouts/app-layout";
import type { BreadcrumbItem } from "@/types";

interface Destination {
    type: string;
    id: number;
    name: string;
    travel_time: number;
}

interface TravelStatus {
    is_traveling: boolean;
    destination: {
        type: string;
        id: number;
        name: string;
    };
    started_at: string;
    arrives_at: string;
    total_seconds: number;
    elapsed_seconds: number;
    remaining_seconds: number;
    progress_percent: number;
    has_arrived: boolean;
}

interface ArrivalInfo {
    arrived: boolean;
    location: {
        type: string;
        id: number;
        name: string;
    };
}

interface PageProps {
    travel_status: TravelStatus | null;
    destinations: Destination[];
    energy_cost: number;
    just_arrived: ArrivalInfo | null;
    is_dev: boolean;
    [key: string]: unknown;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: "Dashboard", href: "/dashboard" },
    { title: "Travel", href: "/travel" },
];

const locationIcons: Record<string, typeof Home> = {
    village: Home,
    barony: Castle,
    town: Church,
    wilderness: Trees,
};

function formatTime(seconds: number): string {
    const mins = Math.floor(seconds / 60);
    const secs = seconds % 60;
    if (mins > 0) {
        return `${mins}m ${secs}s`;
    }
    return `${secs}s`;
}

function TravelProgress({
    status,
    onCancel,
    onArrive,
    onSkip,
    isDev,
}: {
    status: TravelStatus;
    onCancel: () => void;
    onArrive: () => void;
    onSkip?: () => void;
    isDev?: boolean;
}) {
    const [remaining, setRemaining] = useState(status.remaining_seconds);
    const [progress, setProgress] = useState(status.progress_percent);
    const arrivedRef = useRef(false);

    useEffect(() => {
        if (status.has_arrived && !arrivedRef.current) {
            arrivedRef.current = true;
            onArrive();
            return;
        }

        const interval = setInterval(() => {
            setRemaining((prev) => {
                const newVal = Math.max(0, prev - 1);
                if (newVal <= 0 && !arrivedRef.current) {
                    arrivedRef.current = true;
                    clearInterval(interval);
                    onArrive();
                }
                return newVal;
            });
            setProgress((prev) => Math.min(100, prev + 100 / status.total_seconds));
        }, 1000);

        return () => clearInterval(interval);
    }, [status.has_arrived, status.total_seconds, onArrive]);

    const Icon = locationIcons[status.destination.type] || MapPin;

    return (
        <div className="mx-auto max-w-md">
            <div className="rounded-xl border-2 border-amber-600/50 bg-gradient-to-b from-stone-800 to-stone-900 p-6">
                <div className="mb-4 text-center">
                    <div className="mb-2 inline-flex h-16 w-16 items-center justify-center rounded-full bg-amber-900/30">
                        <Loader2 className="h-8 w-8 animate-spin text-amber-400" />
                    </div>
                    <h2 className="font-pixel text-lg text-amber-400">Traveling...</h2>
                </div>

                <div className="mb-4 flex items-center justify-center gap-2">
                    <Icon className="h-5 w-5 text-stone-400" />
                    <span className="font-pixel text-sm text-stone-200">
                        {status.destination.name}
                    </span>
                </div>

                {/* Progress Bar */}
                <div className="mb-2">
                    <div className="h-4 w-full overflow-hidden rounded-full bg-stone-700">
                        <div
                            className="h-full bg-gradient-to-r from-amber-600 to-amber-400 transition-all duration-1000"
                            style={{ width: `${progress}%` }}
                        />
                    </div>
                </div>

                <div className="mb-6 flex items-center justify-center gap-2">
                    <Clock className="h-4 w-4 text-stone-400" />
                    <span className="font-pixel text-sm text-stone-300">
                        {formatTime(remaining)} remaining
                    </span>
                </div>

                <div className="flex gap-2">
                    <button
                        onClick={onCancel}
                        className="flex flex-1 items-center justify-center gap-2 rounded-lg border-2 border-red-600/50 bg-red-900/20 px-4 py-2 font-pixel text-sm text-red-400 transition hover:bg-red-900/40"
                    >
                        <X className="h-4 w-4" />
                        Cancel
                    </button>
                    {isDev && onSkip && (
                        <button
                            onClick={onSkip}
                            className="flex items-center justify-center gap-2 rounded-lg border-2 border-blue-600/50 bg-blue-900/20 px-4 py-2 font-pixel text-sm text-blue-400 transition hover:bg-blue-900/40"
                        >
                            <Zap className="h-4 w-4" />
                            Skip
                        </button>
                    )}
                </div>
            </div>
        </div>
    );
}

function DestinationCard({
    destination,
    energyCost,
    onTravel,
    traveling,
}: {
    destination: Destination;
    energyCost: number;
    onTravel: () => void;
    traveling: boolean;
}) {
    const Icon = locationIcons[destination.type] || MapPin;

    const bgColors: Record<string, string> = {
        village: "border-green-600/50 bg-green-900/20 hover:bg-green-900/30",
        barony: "border-stone-500/50 bg-stone-800/50 hover:bg-stone-700/50",
        town: "border-blue-600/50 bg-blue-900/20 hover:bg-blue-900/30",
        wilderness: "border-amber-600/50 bg-amber-900/20 hover:bg-amber-900/30",
    };

    return (
        <button
            onClick={onTravel}
            disabled={traveling}
            className={`w-full rounded-xl border-2 p-4 text-left transition ${bgColors[destination.type] || "border-stone-600/50 bg-stone-800/50"} disabled:cursor-not-allowed disabled:opacity-50`}
        >
            <div className="mb-2 flex items-center gap-3">
                <div className="rounded-lg bg-stone-800/50 p-2">
                    <Icon className="h-6 w-6 text-stone-300" />
                </div>
                <div>
                    <h3 className="font-pixel text-sm text-amber-300">{destination.name}</h3>
                    <span className="font-pixel text-[10px] capitalize text-stone-400">
                        {destination.type}
                    </span>
                </div>
            </div>

            <div className="flex items-center justify-between">
                <div className="flex items-center gap-1 text-stone-400">
                    <Clock className="h-3 w-3" />
                    <span className="font-pixel text-[10px]">{destination.travel_time} min</span>
                </div>
                <div className="flex items-center gap-1 text-yellow-400">
                    <Zap className="h-3 w-3" />
                    <span className="font-pixel text-[10px]">{energyCost}</span>
                </div>
            </div>
        </button>
    );
}

export default function TravelIndex() {
    const { travel_status, destinations, energy_cost, just_arrived, is_dev } =
        usePage<PageProps>().props;
    const [traveling, setTraveling] = useState(false);
    const [showArrival, setShowArrival] = useState(!!just_arrived);
    const [travelError, setTravelError] = useState<string | null>(null);

    const handleTravel = (destination: Destination) => {
        setTraveling(true);
        setTravelError(null);
        router.post(
            "/travel/start",
            {
                destination_type: destination.type,
                destination_id: destination.id,
            },
            {
                preserveScroll: true,
                onSuccess: () => {
                    router.reload();
                },
                onFinish: () => setTraveling(false),
                onError: (errors) => {
                    const errorMessage =
                        errors.travel || Object.values(errors)[0] || "Failed to start travel.";
                    setTravelError(errorMessage as string);
                },
            },
        );
    };

    const handleCancel = () => {
        router.post(
            "/travel/cancel",
            {},
            {
                preserveScroll: true,
                onSuccess: () => {
                    router.reload();
                },
            },
        );
    };

    const handleArrive = () => {
        router.post(
            "/travel/arrive",
            {},
            {
                preserveScroll: true,
                onSuccess: () => {
                    router.reload();
                },
            },
        );
    };

    const handleSkip = () => {
        router.post(
            "/travel/skip",
            {},
            {
                preserveScroll: true,
                onSuccess: () => {
                    router.reload();
                },
            },
        );
    };

    const dismissArrival = () => {
        setShowArrival(false);
    };

    // Show arrival notification
    if (showArrival && just_arrived) {
        const Icon = locationIcons[just_arrived.location.type] || MapPin;
        return (
            <AppLayout breadcrumbs={breadcrumbs}>
                <Head title="Arrived!" />
                <div className="flex h-full flex-1 items-center justify-center p-4">
                    <div className="mx-auto max-w-md text-center">
                        <div className="rounded-xl border-2 border-green-600/50 bg-gradient-to-b from-stone-800 to-stone-900 p-8">
                            <div className="mb-4 inline-flex h-20 w-20 items-center justify-center rounded-full bg-green-900/30">
                                <Icon className="h-10 w-10 text-green-400" />
                            </div>
                            <h2 className="mb-2 font-pixel text-xl text-green-400">
                                You have arrived!
                            </h2>
                            <p className="mb-6 font-pixel text-sm text-stone-300">
                                {just_arrived.location.name}
                            </p>
                            <button
                                onClick={dismissArrival}
                                className="rounded-lg border-2 border-green-600 bg-green-900/30 px-6 py-2 font-pixel text-sm text-green-300 transition hover:bg-green-800/50"
                            >
                                Continue
                            </button>
                        </div>
                    </div>
                </div>
            </AppLayout>
        );
    }

    // Show destinations (with travel progress if traveling)
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={travel_status?.is_traveling ? "Traveling..." : "Travel"} />
            <div className="flex h-full flex-1 flex-col p-4">
                {/* Travel Progress - shown at top when traveling */}
                {travel_status?.is_traveling && (
                    <div className="mb-6">
                        <TravelProgress
                            status={travel_status}
                            onCancel={handleCancel}
                            onArrive={handleArrive}
                            onSkip={handleSkip}
                            isDev={is_dev}
                        />
                    </div>
                )}

                {/* Header - only show when not traveling */}
                {!travel_status?.is_traveling && (
                    <div className="mb-6">
                        <h1 className="font-pixel text-2xl text-amber-400">Travel</h1>
                        <p className="font-pixel text-sm text-stone-400">Choose your destination</p>
                    </div>
                )}

                {travelError && (
                    <div className="mb-4 flex items-center gap-2 rounded-lg border border-red-600/50 bg-red-900/30 px-4 py-3">
                        <Zap className="h-5 w-5 text-red-400" />
                        <span className="font-pixel text-sm text-red-300">{travelError}</span>
                        <button
                            onClick={() => setTravelError(null)}
                            className="ml-auto text-red-400 hover:text-red-300"
                        >
                            <X className="h-4 w-4" />
                        </button>
                    </div>
                )}

                {/* Destinations - blurred when traveling */}
                <div
                    className={
                        travel_status?.is_traveling ? "pointer-events-none opacity-30 blur-sm" : ""
                    }
                >
                    {destinations.length > 0 ? (
                        <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                            {destinations.map((dest) => (
                                <DestinationCard
                                    key={`${dest.type}-${dest.id}`}
                                    destination={dest}
                                    energyCost={energy_cost}
                                    onTravel={() => handleTravel(dest)}
                                    traveling={traveling}
                                />
                            ))}
                        </div>
                    ) : (
                        <div className="flex flex-1 items-center justify-center py-12">
                            <div className="text-center">
                                <div className="mb-3 text-6xl">🗺️</div>
                                <p className="font-pixel text-base text-stone-500">Nowhere to go</p>
                                <p className="font-pixel text-xs text-stone-600">
                                    You seem to be stuck...
                                </p>
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
