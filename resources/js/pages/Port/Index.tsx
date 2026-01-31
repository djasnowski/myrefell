import { Head, router, usePage } from "@inertiajs/react";
import { Anchor, Coins, Crown, Loader2, Ship, Sparkles } from "lucide-react";
import { useState } from "react";
import AppLayout from "@/layouts/app-layout";
import type { BreadcrumbItem } from "@/types";

interface Destination {
    id: number;
    name: string;
    kingdom_id: number;
    kingdom_name: string;
    biome: string;
    cost: number;
    travel_time: number;
}

interface PortInfo {
    port_id: number;
    port_name: string;
    port_biome: string;
    kingdom_id: number;
    kingdom_name: string;
    harbormaster_name: string;
    harbormaster_title: string;
    gold: number;
    base_ship_cost: number;
    destinations: Destination[];
}

interface PageProps {
    port_info: PortInfo;
    flash?: {
        success?: string;
        error?: string;
    };
    [key: string]: unknown;
}

function formatNumber(n: number): string {
    return n.toLocaleString();
}

export default function PortIndex() {
    const { port_info, flash } = usePage<PageProps>().props;
    const [loading, setLoading] = useState<number | null>(null);
    const [error, setError] = useState<string | null>(flash?.error || null);
    const [success, setSuccess] = useState<string | null>(flash?.success || null);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: "Dashboard", href: "/dashboard" },
        { title: port_info.port_name, href: `/villages/${port_info.port_id}` },
        { title: "Harbor", href: "#" },
    ];

    const handleBookPassage = (destinationId: number) => {
        setLoading(destinationId);
        setError(null);
        setSuccess(null);

        router.post(
            "/port/book",
            { destination_id: destinationId },
            {
                preserveScroll: true,
                onSuccess: () => {
                    router.reload();
                },
                onError: (errors) => {
                    setError((Object.values(errors)[0] as string) || "An error occurred");
                    setLoading(null);
                },
                onFinish: () => {
                    // Only reset loading if we're still on the page (error case)
                    // On success we redirect to dashboard
                    setLoading(null);
                },
            },
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Harbor - ${port_info.port_name}`} />
            <div className="flex h-full flex-1 flex-col p-4">
                {/* Header */}
                <div className="mb-6 flex items-center gap-3">
                    <div className="rounded-lg bg-blue-900/30 p-3">
                        <Anchor className="h-8 w-8 text-blue-400" />
                    </div>
                    <div>
                        <h1 className="font-pixel text-2xl text-blue-400">Harbor</h1>
                        <div className="flex items-center gap-1 text-stone-400">
                            <Ship className="h-3 w-3" />
                            <span className="font-pixel text-xs">{port_info.port_name}</span>
                        </div>
                    </div>
                </div>

                <div className="w-full">
                    {/* Harbormaster Introduction */}
                    <div className="mb-6 rounded-xl border-2 border-stone-700 bg-stone-800/50 p-6">
                        <div className="mb-4 flex items-center gap-4">
                            <div className="flex h-16 w-16 items-center justify-center rounded-full bg-stone-700">
                                <Sparkles className="h-8 w-8 text-blue-400" />
                            </div>
                            <div>
                                <h2 className="font-pixel text-lg text-blue-300">
                                    {port_info.harbormaster_name}
                                </h2>
                                <p className="font-pixel text-xs text-stone-400">
                                    {port_info.harbormaster_title}
                                </p>
                            </div>
                        </div>
                        <p className="font-pixel text-xs leading-relaxed text-stone-300">
                            {port_info.destinations.length > 0
                                ? `"Welcome to ${port_info.port_name}, traveler! Looking to sail to distant lands? Passage starts at ${formatNumber(port_info.base_ship_cost)} gold, depending on distance."`
                                : '"Ahoy! Unfortunately, no ships are departing at this time. Check back later."'}
                        </p>
                    </div>

                    {/* Messages */}
                    {error && (
                        <div className="mb-4 rounded-lg border border-red-600/50 bg-red-900/20 px-4 py-2 font-pixel text-xs text-red-400">
                            {error}
                        </div>
                    )}
                    {success && (
                        <div className="mb-4 rounded-lg border border-green-600/50 bg-green-900/20 px-4 py-2 font-pixel text-xs text-green-400">
                            {success}
                        </div>
                    )}

                    {/* Gold Display */}
                    <div className="mb-6 rounded-xl border-2 border-stone-700 bg-stone-800/50 p-4">
                        <div className="flex items-center justify-between">
                            <div className="flex items-center gap-2">
                                <Coins className="h-5 w-5 text-yellow-400" />
                                <span className="font-pixel text-sm text-stone-300">Your Gold</span>
                            </div>
                            <span className="font-pixel text-lg text-yellow-400">
                                {formatNumber(port_info.gold)}
                            </span>
                        </div>
                        <div className="mt-2 text-right">
                            <span className="font-pixel text-[10px] text-stone-500">
                                Cost varies by distance (from{" "}
                                {formatNumber(port_info.base_ship_cost)} gold)
                            </span>
                        </div>
                    </div>

                    {/* Destinations */}
                    {port_info.destinations.length > 0 ? (
                        <div className="space-y-3">
                            <h3 className="font-pixel text-sm text-stone-300">
                                Available Destinations
                            </h3>
                            {port_info.destinations.map((dest) => {
                                const canAfford = port_info.gold >= dest.cost;
                                const isLoading = loading === dest.id;

                                return (
                                    <button
                                        key={dest.id}
                                        onClick={() => handleBookPassage(dest.id)}
                                        disabled={!canAfford || loading !== null}
                                        className={`w-full rounded-xl border-2 p-4 text-left transition ${
                                            canAfford
                                                ? "border-blue-600/50 bg-blue-900/20 hover:bg-blue-900/30"
                                                : "cursor-not-allowed border-stone-700 bg-stone-800/30 opacity-50"
                                        }`}
                                    >
                                        <div className="flex items-center justify-between">
                                            <div>
                                                <div className="flex items-center gap-2">
                                                    <Ship className="h-4 w-4 text-blue-400" />
                                                    <h4 className="font-pixel text-sm text-blue-300">
                                                        {dest.name}
                                                    </h4>
                                                </div>
                                                <div className="mt-1 flex items-center gap-1">
                                                    <Crown className="h-3 w-3 text-amber-400" />
                                                    <span className="font-pixel text-[10px] text-stone-400">
                                                        {dest.kingdom_name}
                                                    </span>
                                                    <span className="font-pixel text-[10px] capitalize text-stone-500">
                                                        ({dest.biome})
                                                    </span>
                                                </div>
                                            </div>
                                            <div className="text-right">
                                                <div className="flex items-center gap-1">
                                                    {isLoading ? (
                                                        <Loader2 className="h-4 w-4 animate-spin text-blue-400" />
                                                    ) : (
                                                        <Ship className="h-4 w-4 text-blue-400" />
                                                    )}
                                                    <span className="font-pixel text-sm text-blue-300">
                                                        {dest.travel_time} min
                                                    </span>
                                                </div>
                                                <div className="flex items-center justify-end gap-1">
                                                    <Coins className="h-3 w-3 text-yellow-400" />
                                                    <span
                                                        className={`font-pixel text-xs ${canAfford ? "text-yellow-400" : "text-red-400"}`}
                                                    >
                                                        {formatNumber(dest.cost)}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </button>
                                );
                            })}
                        </div>
                    ) : (
                        <div className="rounded-xl border-2 border-stone-700 bg-stone-800/30 p-8 text-center">
                            <Ship className="mx-auto mb-3 h-12 w-12 text-stone-500" />
                            <h3 className="mb-2 font-pixel text-lg text-stone-400">
                                No Ships Available
                            </h3>
                            <p className="font-pixel text-xs text-stone-500">
                                There are no ships departing at this time.
                            </p>
                        </div>
                    )}

                    {/* Info */}
                    <div className="mt-6 text-center">
                        <p className="font-pixel text-[10px] text-stone-500">
                            Travel time varies based on distance between ports
                        </p>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
