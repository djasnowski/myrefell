import { Head, Link, router, usePage } from "@inertiajs/react";
import {
    AlertTriangle,
    ArrowLeft,
    ArrowRight,
    MapPin,
    Plus,
    Route,
    Shield,
    Truck,
    X,
} from "lucide-react";
import { useState } from "react";
import AppLayout from "@/layouts/app-layout";
import type { BreadcrumbItem } from "@/types";

interface Location {
    id: number;
    name: string;
    type: string;
    barony_id?: number;
}

interface TradeRoute {
    id: number;
    name: string;
    origin: {
        type: string;
        id: number;
        name: string;
    };
    destination: {
        type: string;
        id: number;
        name: string;
    };
    distance: number;
    base_travel_days: number;
    danger_level: "safe" | "moderate" | "dangerous" | "perilous";
    bandit_chance: number;
    active_caravans_count: number;
    notes: string | null;
}

interface PageProps {
    barony: {
        id: number;
        name: string;
    };
    routes: TradeRoute[];
    barony_locations: Location[];
    all_locations: Location[];
    [key: string]: unknown;
}

const dangerColors: Record<string, { border: string; bg: string; text: string; label: string }> = {
    safe: {
        border: "border-green-500/50",
        bg: "bg-green-900/20",
        text: "text-green-400",
        label: "Safe",
    },
    moderate: {
        border: "border-yellow-500/50",
        bg: "bg-yellow-900/20",
        text: "text-yellow-400",
        label: "Moderate",
    },
    dangerous: {
        border: "border-orange-500/50",
        bg: "bg-orange-900/20",
        text: "text-orange-400",
        label: "Dangerous",
    },
    perilous: {
        border: "border-red-500/50",
        bg: "bg-red-900/20",
        text: "text-red-400",
        label: "Perilous",
    },
};

export default function BaronyTradeRoutes() {
    const { barony, routes, barony_locations, all_locations } = usePage<PageProps>().props;

    const [showCreateForm, setShowCreateForm] = useState(false);
    const [formData, setFormData] = useState({
        name: "",
        origin_id: "",
        origin_type: "",
        destination_id: "",
        destination_type: "",
        danger_level: "moderate",
    });
    const [isCreating, setIsCreating] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const [success, setSuccess] = useState<string | null>(null);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: "Dashboard", href: "/dashboard" },
        { title: "Baronies", href: "/baronies" },
        { title: barony.name, href: `/baronies/${barony.id}` },
        { title: "Trade Routes", href: "#" },
    ];

    const handleOriginChange = (value: string) => {
        if (!value) {
            setFormData({ ...formData, origin_id: "", origin_type: "" });
            return;
        }
        const [type, id] = value.split(":");
        setFormData({ ...formData, origin_id: id, origin_type: type });
    };

    const handleDestinationChange = (value: string) => {
        if (!value) {
            setFormData({ ...formData, destination_id: "", destination_type: "" });
            return;
        }
        const [type, id] = value.split(":");
        setFormData({ ...formData, destination_id: id, destination_type: type });
    };

    const createRoute = async () => {
        if (!formData.name.trim() || !formData.origin_id || !formData.destination_id) {
            setError("Please fill in all required fields.");
            return;
        }

        setIsCreating(true);
        setError(null);

        try {
            const response = await fetch(`/baronies/${barony.id}/trade-routes`, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN":
                        document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')
                            ?.content || "",
                },
                body: JSON.stringify({
                    name: formData.name,
                    origin_type: formData.origin_type,
                    origin_id: parseInt(formData.origin_id, 10),
                    destination_type: formData.destination_type,
                    destination_id: parseInt(formData.destination_id, 10),
                    danger_level: formData.danger_level,
                }),
            });

            const data = await response.json();
            if (data.success) {
                setSuccess(data.message);
                setShowCreateForm(false);
                setFormData({
                    name: "",
                    origin_id: "",
                    origin_type: "",
                    destination_id: "",
                    destination_type: "",
                    danger_level: "moderate",
                });
                router.reload();
            } else {
                setError(data.message);
            }
        } catch {
            setError("Failed to create trade route");
        } finally {
            setIsCreating(false);
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Trade Routes - ${barony.name}`} />
            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                {/* Header */}
                <div className="flex items-start justify-between">
                    <div>
                        <div className="mb-2">
                            <Link
                                href={`/baronies/${barony.id}`}
                                className="flex items-center gap-1 font-pixel text-xs text-stone-400 transition hover:text-stone-300"
                            >
                                <ArrowLeft className="h-3 w-3" />
                                Back to {barony.name}
                            </Link>
                        </div>
                        <div className="flex items-center gap-3">
                            <Route className="h-8 w-8 text-emerald-400" />
                            <div>
                                <h1 className="font-pixel text-2xl text-emerald-400">
                                    Trade Routes
                                </h1>
                                <p className="font-pixel text-sm text-stone-400">
                                    Manage caravan routes for {barony.name}
                                </p>
                            </div>
                        </div>
                    </div>
                    <button
                        onClick={() => setShowCreateForm(!showCreateForm)}
                        className="flex items-center gap-2 rounded border-2 border-emerald-600/50 bg-emerald-900/20 px-4 py-2 font-pixel text-xs text-emerald-300 transition hover:bg-emerald-900/40"
                    >
                        {showCreateForm ? (
                            <>
                                <X className="h-4 w-4" />
                                Cancel
                            </>
                        ) : (
                            <>
                                <Plus className="h-4 w-4" />
                                Create Route
                            </>
                        )}
                    </button>
                </div>

                {/* Messages */}
                {error && (
                    <div className="rounded-lg border border-red-500/50 bg-red-900/30 p-3 font-pixel text-sm text-red-300">
                        {error}
                    </div>
                )}
                {success && (
                    <div className="rounded-lg border border-green-500/50 bg-green-900/30 p-3 font-pixel text-sm text-green-300">
                        {success}
                    </div>
                )}

                {/* Create Route Form */}
                {showCreateForm && (
                    <div className="rounded-xl border-2 border-emerald-500/30 bg-emerald-900/20 p-4">
                        <h3 className="mb-4 font-pixel text-base text-emerald-300">
                            Create New Trade Route
                        </h3>

                        <div className="mb-4">
                            <label className="mb-1 block font-pixel text-xs text-stone-400">
                                Route Name
                            </label>
                            <input
                                type="text"
                                value={formData.name}
                                onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                                maxLength={100}
                                className="w-full rounded border border-stone-600 bg-stone-800 px-3 py-2 font-pixel text-sm text-white focus:border-emerald-500 focus:outline-none"
                                placeholder="e.g., Northern Trade Road"
                            />
                        </div>

                        <div className="mb-4 grid gap-4 md:grid-cols-2">
                            <div>
                                <label className="mb-1 block font-pixel text-xs text-stone-400">
                                    Origin (within {barony.name})
                                </label>
                                <select
                                    value={
                                        formData.origin_type && formData.origin_id
                                            ? `${formData.origin_type}:${formData.origin_id}`
                                            : ""
                                    }
                                    onChange={(e) => handleOriginChange(e.target.value)}
                                    className="w-full rounded border border-stone-600 bg-stone-800 px-3 py-2 font-pixel text-sm text-white focus:border-emerald-500 focus:outline-none"
                                >
                                    <option value="">-- Select Origin --</option>
                                    {barony_locations.map((loc) => (
                                        <option
                                            key={`${loc.type}:${loc.id}`}
                                            value={`${loc.type}:${loc.id}`}
                                        >
                                            {loc.name} ({loc.type})
                                        </option>
                                    ))}
                                </select>
                                <p className="mt-1 font-pixel text-[10px] text-stone-500">
                                    Must be within your barony
                                </p>
                            </div>
                            <div>
                                <label className="mb-1 block font-pixel text-xs text-stone-400">
                                    Destination
                                </label>
                                <select
                                    value={
                                        formData.destination_type && formData.destination_id
                                            ? `${formData.destination_type}:${formData.destination_id}`
                                            : ""
                                    }
                                    onChange={(e) => handleDestinationChange(e.target.value)}
                                    className="w-full rounded border border-stone-600 bg-stone-800 px-3 py-2 font-pixel text-sm text-white focus:border-emerald-500 focus:outline-none"
                                >
                                    <option value="">-- Select Destination --</option>
                                    {all_locations.map((loc) => (
                                        <option
                                            key={`${loc.type}:${loc.id}`}
                                            value={`${loc.type}:${loc.id}`}
                                        >
                                            {loc.name} ({loc.type})
                                        </option>
                                    ))}
                                </select>
                                <p className="mt-1 font-pixel text-[10px] text-stone-500">
                                    Can be any settlement in the realm
                                </p>
                            </div>
                        </div>

                        <div className="mb-4">
                            <label className="mb-2 block font-pixel text-xs text-stone-400">
                                Danger Level
                            </label>
                            <div className="grid gap-2 md:grid-cols-4">
                                {Object.entries(dangerColors).map(([level, colors]) => (
                                    <button
                                        key={level}
                                        type="button"
                                        onClick={() =>
                                            setFormData({ ...formData, danger_level: level })
                                        }
                                        className={`rounded-lg border-2 p-3 text-center transition ${
                                            formData.danger_level === level
                                                ? `${colors.border} ${colors.bg}`
                                                : "border-stone-600 bg-stone-800 hover:border-stone-500"
                                        }`}
                                    >
                                        <span
                                            className={`font-pixel text-sm ${formData.danger_level === level ? colors.text : "text-white"}`}
                                        >
                                            {colors.label}
                                        </span>
                                    </button>
                                ))}
                            </div>
                        </div>

                        <button
                            onClick={createRoute}
                            disabled={
                                !formData.name.trim() ||
                                !formData.origin_id ||
                                !formData.destination_id ||
                                isCreating
                            }
                            className="w-full rounded bg-emerald-600 py-2 font-pixel text-sm text-white transition hover:bg-emerald-500 disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            {isCreating ? "Creating..." : "Create Trade Route"}
                        </button>
                    </div>
                )}

                {/* Routes List */}
                {routes.length > 0 ? (
                    <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                        {routes.map((route) => {
                            const dangerStyle =
                                dangerColors[route.danger_level] || dangerColors.moderate;
                            return (
                                <div
                                    key={route.id}
                                    className={`rounded-xl border-2 ${dangerStyle.border} ${dangerStyle.bg} p-4`}
                                >
                                    {/* Route Header */}
                                    <div className="mb-3 flex items-start justify-between">
                                        <div className="flex items-center gap-2">
                                            <Route className="h-5 w-5 text-emerald-400" />
                                            <h3 className="font-pixel text-base text-white">
                                                {route.name}
                                            </h3>
                                        </div>
                                        <span
                                            className={`rounded px-2 py-1 font-pixel text-[10px] ${dangerStyle.bg} ${dangerStyle.text}`}
                                        >
                                            {dangerStyle.label}
                                        </span>
                                    </div>

                                    {/* Route Path */}
                                    <div className="mb-4 flex items-center justify-between rounded-lg bg-stone-800/50 p-3">
                                        <div className="flex items-center gap-1">
                                            <MapPin className="h-4 w-4 text-green-400" />
                                            <div>
                                                <div className="font-pixel text-sm text-white">
                                                    {route.origin.name}
                                                </div>
                                                <div className="font-pixel text-[10px] capitalize text-stone-500">
                                                    {route.origin.type}
                                                </div>
                                            </div>
                                        </div>
                                        <ArrowRight className="h-5 w-5 text-stone-500" />
                                        <div className="flex items-center gap-1">
                                            <MapPin className="h-4 w-4 text-red-400" />
                                            <div className="text-right">
                                                <div className="font-pixel text-sm text-white">
                                                    {route.destination.name}
                                                </div>
                                                <div className="font-pixel text-[10px] capitalize text-stone-500">
                                                    {route.destination.type}
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    {/* Route Stats */}
                                    <div className="mb-3 grid grid-cols-2 gap-2 font-pixel text-xs">
                                        <div className="flex items-center gap-1 text-stone-400">
                                            <span className="text-stone-500">Distance:</span>
                                            <span className="text-white">
                                                {route.base_travel_days}{" "}
                                                {route.base_travel_days === 1 ? "day" : "days"}
                                            </span>
                                        </div>
                                        <div className="flex items-center gap-1 text-stone-400">
                                            <AlertTriangle className="h-3 w-3 text-orange-400" />
                                            <span className="text-stone-500">Danger:</span>
                                            <span className={dangerStyle.text}>
                                                {route.bandit_chance}%
                                            </span>
                                        </div>
                                    </div>

                                    {/* Active Caravans */}
                                    <div className="flex items-center justify-between rounded-lg bg-stone-800/30 p-2">
                                        <div className="flex items-center gap-2 font-pixel text-xs text-stone-400">
                                            <Truck className="h-4 w-4 text-amber-400" />
                                            <span>Active Caravans:</span>
                                        </div>
                                        <span className="font-pixel text-sm text-amber-300">
                                            {route.active_caravans_count}
                                        </span>
                                    </div>

                                    {/* Notes */}
                                    {route.notes && (
                                        <div className="mt-3 rounded-lg bg-stone-800/30 p-2">
                                            <p className="font-pixel text-[10px] text-stone-500">
                                                {route.notes}
                                            </p>
                                        </div>
                                    )}
                                </div>
                            );
                        })}
                    </div>
                ) : (
                    /* Empty State */
                    <div className="flex flex-1 items-center justify-center">
                        <div className="text-center">
                            <Route className="mx-auto mb-3 h-16 w-16 text-stone-600" />
                            <p className="font-pixel text-base text-stone-500">
                                No trade routes established
                            </p>
                            <p className="font-pixel text-xs text-stone-600">
                                Create your first trade route to enable commerce!
                            </p>
                        </div>
                    </div>
                )}

                {/* Legend */}
                <div className="mt-auto rounded-lg border border-stone-700 bg-stone-800/50 p-3">
                    <h4 className="mb-2 font-pixel text-xs text-stone-400">Danger Level Legend</h4>
                    <div className="flex flex-wrap gap-4 font-pixel text-xs">
                        {Object.entries(dangerColors).map(([level, colors]) => (
                            <div key={level} className="flex items-center gap-2">
                                <Shield className={`h-3 w-3 ${colors.text}`} />
                                <span className={colors.text}>{colors.label}</span>
                                <span className="text-stone-500">
                                    (
                                    {level === "safe"
                                        ? "5% bandit chance"
                                        : level === "moderate"
                                          ? "15% bandit chance"
                                          : level === "dangerous"
                                            ? "30% bandit chance"
                                            : "50% bandit chance"}
                                    )
                                </span>
                            </div>
                        ))}
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
