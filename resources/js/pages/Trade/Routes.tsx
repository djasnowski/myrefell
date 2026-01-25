import { Head, router, usePage } from '@inertiajs/react';
import { AlertTriangle, ArrowRight, MapPin, Plus, Route, Shield, Truck } from 'lucide-react';
import { useState } from 'react';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

interface Location {
    type: string;
    id: number;
    name: string;
}

interface TradeRoute {
    id: number;
    name: string;
    origin: Location;
    destination: Location;
    distance: number;
    base_travel_days: number;
    danger_level: 'safe' | 'moderate' | 'dangerous' | 'perilous';
    bandit_chance: number;
    active_caravans_count: number;
    notes: string | null;
}

interface PageProps {
    routes: TradeRoute[];
    can_create: boolean;
    [key: string]: unknown;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Trade Routes', href: '/trade/routes' },
];

const dangerColors: Record<string, { border: string; bg: string; text: string; label: string }> = {
    safe: {
        border: 'border-green-500/50',
        bg: 'bg-green-900/20',
        text: 'text-green-400',
        label: 'Safe',
    },
    moderate: {
        border: 'border-yellow-500/50',
        bg: 'bg-yellow-900/20',
        text: 'text-yellow-400',
        label: 'Moderate',
    },
    dangerous: {
        border: 'border-orange-500/50',
        bg: 'bg-orange-900/20',
        text: 'text-orange-400',
        label: 'Dangerous',
    },
    perilous: {
        border: 'border-red-500/50',
        bg: 'bg-red-900/20',
        text: 'text-red-400',
        label: 'Perilous',
    },
};

export default function TradeRoutes() {
    const { routes, can_create } = usePage<PageProps>().props;

    const [showCreateForm, setShowCreateForm] = useState(false);
    const [formData, setFormData] = useState({
        name: '',
        origin_type: 'village',
        origin_id: '',
        destination_type: 'village',
        destination_id: '',
        danger_level: 'moderate',
    });
    const [isCreating, setIsCreating] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const [success, setSuccess] = useState<string | null>(null);

    const createRoute = async () => {
        if (!formData.name.trim() || !formData.origin_id || !formData.destination_id) {
            setError('Please fill in all required fields.');
            return;
        }

        setIsCreating(true);
        setError(null);

        try {
            const response = await fetch('/trade/routes', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content || '',
                },
                body: JSON.stringify({
                    ...formData,
                    origin_id: parseInt(formData.origin_id, 10),
                    destination_id: parseInt(formData.destination_id, 10),
                }),
            });

            const data = await response.json();
            if (data.success) {
                setSuccess(data.message);
                setShowCreateForm(false);
                setFormData({
                    name: '',
                    origin_type: 'village',
                    origin_id: '',
                    destination_type: 'village',
                    destination_id: '',
                    danger_level: 'moderate',
                });
                router.reload();
            } else {
                setError(data.message);
            }
        } catch {
            setError('Failed to create trade route');
        } finally {
            setIsCreating(false);
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Trade Routes" />
            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="font-pixel text-2xl text-amber-400">Trade Routes</h1>
                        <p className="font-pixel text-sm text-stone-400">
                            Established trade routes between settlements
                        </p>
                    </div>
                    {can_create && (
                        <button
                            onClick={() => setShowCreateForm(!showCreateForm)}
                            className="flex items-center gap-2 rounded border-2 border-amber-600/50 bg-amber-900/20 px-4 py-2 font-pixel text-xs text-amber-300 transition hover:bg-amber-900/40"
                        >
                            <Plus className="h-4 w-4" />
                            {showCreateForm ? 'Cancel' : 'Create Route'}
                        </button>
                    )}
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
                {showCreateForm && can_create && (
                    <div className="rounded-xl border-2 border-amber-500/30 bg-amber-900/20 p-4">
                        <h3 className="mb-4 font-pixel text-base text-amber-300">Create New Trade Route</h3>

                        <div className="mb-4">
                            <label className="mb-1 block font-pixel text-xs text-stone-400">Route Name</label>
                            <input
                                type="text"
                                value={formData.name}
                                onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                                maxLength={100}
                                className="w-full rounded border border-stone-600 bg-stone-800 px-3 py-2 font-pixel text-sm text-white focus:border-amber-500 focus:outline-none"
                                placeholder="e.g., Northern Trade Road"
                            />
                        </div>

                        <div className="mb-4 grid gap-4 md:grid-cols-2">
                            <div>
                                <label className="mb-1 block font-pixel text-xs text-stone-400">Origin Type</label>
                                <select
                                    value={formData.origin_type}
                                    onChange={(e) => setFormData({ ...formData, origin_type: e.target.value })}
                                    className="w-full rounded border border-stone-600 bg-stone-800 px-3 py-2 font-pixel text-sm text-white focus:border-amber-500 focus:outline-none"
                                >
                                    <option value="village">Village</option>
                                    <option value="town">Town</option>
                                </select>
                            </div>
                            <div>
                                <label className="mb-1 block font-pixel text-xs text-stone-400">Origin ID</label>
                                <input
                                    type="number"
                                    value={formData.origin_id}
                                    onChange={(e) => setFormData({ ...formData, origin_id: e.target.value })}
                                    className="w-full rounded border border-stone-600 bg-stone-800 px-3 py-2 font-pixel text-sm text-white focus:border-amber-500 focus:outline-none"
                                    placeholder="Location ID"
                                />
                            </div>
                        </div>

                        <div className="mb-4 grid gap-4 md:grid-cols-2">
                            <div>
                                <label className="mb-1 block font-pixel text-xs text-stone-400">Destination Type</label>
                                <select
                                    value={formData.destination_type}
                                    onChange={(e) => setFormData({ ...formData, destination_type: e.target.value })}
                                    className="w-full rounded border border-stone-600 bg-stone-800 px-3 py-2 font-pixel text-sm text-white focus:border-amber-500 focus:outline-none"
                                >
                                    <option value="village">Village</option>
                                    <option value="town">Town</option>
                                </select>
                            </div>
                            <div>
                                <label className="mb-1 block font-pixel text-xs text-stone-400">Destination ID</label>
                                <input
                                    type="number"
                                    value={formData.destination_id}
                                    onChange={(e) => setFormData({ ...formData, destination_id: e.target.value })}
                                    className="w-full rounded border border-stone-600 bg-stone-800 px-3 py-2 font-pixel text-sm text-white focus:border-amber-500 focus:outline-none"
                                    placeholder="Location ID"
                                />
                            </div>
                        </div>

                        <div className="mb-4">
                            <label className="mb-2 block font-pixel text-xs text-stone-400">Danger Level</label>
                            <div className="grid gap-2 md:grid-cols-4">
                                {Object.entries(dangerColors).map(([level, colors]) => (
                                    <button
                                        key={level}
                                        type="button"
                                        onClick={() => setFormData({ ...formData, danger_level: level })}
                                        className={`rounded-lg border-2 p-3 text-center transition ${
                                            formData.danger_level === level
                                                ? `${colors.border} ${colors.bg}`
                                                : 'border-stone-600 bg-stone-800 hover:border-stone-500'
                                        }`}
                                    >
                                        <span className={`font-pixel text-sm ${formData.danger_level === level ? colors.text : 'text-white'}`}>
                                            {colors.label}
                                        </span>
                                    </button>
                                ))}
                            </div>
                        </div>

                        <button
                            onClick={createRoute}
                            disabled={!formData.name.trim() || !formData.origin_id || !formData.destination_id || isCreating}
                            className="w-full rounded bg-amber-600 py-2 font-pixel text-sm text-white transition hover:bg-amber-500 disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            {isCreating ? 'Creating...' : 'Create Trade Route'}
                        </button>
                    </div>
                )}

                {/* Routes List */}
                {routes.length > 0 ? (
                    <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                        {routes.map((route) => {
                            const dangerStyle = dangerColors[route.danger_level] || dangerColors.moderate;
                            return (
                                <div
                                    key={route.id}
                                    className={`rounded-xl border-2 ${dangerStyle.border} ${dangerStyle.bg} p-4`}
                                >
                                    {/* Route Header */}
                                    <div className="mb-3 flex items-start justify-between">
                                        <div className="flex items-center gap-2">
                                            <Route className="h-5 w-5 text-amber-400" />
                                            <h3 className="font-pixel text-base text-white">{route.name}</h3>
                                        </div>
                                        <span className={`rounded px-2 py-1 font-pixel text-[10px] ${dangerStyle.bg} ${dangerStyle.text}`}>
                                            {dangerStyle.label}
                                        </span>
                                    </div>

                                    {/* Route Path */}
                                    <div className="mb-4 flex items-center justify-between rounded-lg bg-stone-800/50 p-3">
                                        <div className="flex items-center gap-1">
                                            <MapPin className="h-4 w-4 text-green-400" />
                                            <div>
                                                <div className="font-pixel text-sm text-white">{route.origin.name}</div>
                                                <div className="font-pixel text-[10px] capitalize text-stone-500">
                                                    {route.origin.type}
                                                </div>
                                            </div>
                                        </div>
                                        <ArrowRight className="h-5 w-5 text-stone-500" />
                                        <div className="flex items-center gap-1">
                                            <MapPin className="h-4 w-4 text-red-400" />
                                            <div className="text-right">
                                                <div className="font-pixel text-sm text-white">{route.destination.name}</div>
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
                                            <span className="text-white">{route.base_travel_days} {route.base_travel_days === 1 ? 'day' : 'days'}</span>
                                        </div>
                                        <div className="flex items-center gap-1 text-stone-400">
                                            <AlertTriangle className="h-3 w-3 text-orange-400" />
                                            <span className="text-stone-500">Danger:</span>
                                            <span className={dangerStyle.text}>{route.bandit_chance}%</span>
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
                                            <p className="font-pixel text-[10px] text-stone-500">{route.notes}</p>
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
                            <p className="font-pixel text-base text-stone-500">No trade routes established</p>
                            <p className="font-pixel text-xs text-stone-600">
                                {can_create
                                    ? 'Create the first trade route to enable commerce!'
                                    : 'Only rulers can establish new trade routes.'}
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
                                    ({level === 'safe' ? 'Low' : level === 'moderate' ? 'Medium' : level === 'dangerous' ? 'High' : 'Very High'} risk)
                                </span>
                            </div>
                        ))}
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
