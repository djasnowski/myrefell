import { Head, router, usePage } from '@inertiajs/react';
import { AlertTriangle, ArrowRight, Coins, Loader2, MapPin, Percent, Plus, Route, Settings, Truck } from 'lucide-react';
import { useState } from 'react';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

interface Territory {
    type: string;
    id: number;
    name: string;
}

interface RouteInfo {
    id: number;
    name: string;
    origin: {
        name: string;
        type: string;
    };
    destination: {
        name: string;
        type: string;
    };
    tariff_rate: number;
    caravans_this_week: number;
    revenue: number;
}

interface TariffInfo {
    id: number;
    item_id: number | null;
    item_name: string;
    tariff_rate: number;
    is_active: boolean;
    set_by: { id: number; username: string } | null;
    total_collected: number;
    created_at: string;
    updated_at: string;
}

interface Item {
    id: number;
    name: string;
    type: string;
    base_value: number;
}

interface Revenue {
    this_week: number;
    this_month: number;
    total: number;
}

interface PageProps {
    can_manage: boolean;
    territory: Territory | null;
    routes: RouteInfo[];
    tariffs: TariffInfo[];
    revenue: Revenue;
    min_rate: number;
    max_rate: number;
    items: Item[];
    [key: string]: unknown;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Trade Routes', href: '/trade/routes' },
    { title: 'Tariffs', href: '/trade/tariffs' },
];

function formatGold(amount: number): string {
    return amount.toLocaleString();
}

export default function Tariffs() {
    const { can_manage, territory, routes, tariffs, revenue, min_rate, max_rate, items } = usePage<PageProps>().props;

    const [showAddForm, setShowAddForm] = useState(false);
    const [editingTariff, setEditingTariff] = useState<TariffInfo | null>(null);
    const [formData, setFormData] = useState({
        item_id: '',
        tariff_rate: '10',
    });
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const [success, setSuccess] = useState<string | null>(null);

    const handleCreateTariff = async () => {
        if (!territory) return;

        const rate = parseInt(formData.tariff_rate, 10);
        if (isNaN(rate) || rate < min_rate || rate > max_rate) {
            setError(`Tariff rate must be between ${min_rate}% and ${max_rate}%`);
            return;
        }

        setLoading(true);
        setError(null);

        try {
            const response = await fetch('/trade/tariffs', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content || '',
                },
                body: JSON.stringify({
                    location_type: territory.type,
                    location_id: territory.id,
                    item_id: formData.item_id ? parseInt(formData.item_id, 10) : null,
                    tariff_rate: rate,
                }),
            });

            const data = await response.json();
            if (data.success) {
                setSuccess(data.message);
                setShowAddForm(false);
                setFormData({ item_id: '', tariff_rate: '10' });
                router.reload();
            } else {
                setError(data.message);
            }
        } catch {
            setError('Failed to create tariff');
        } finally {
            setLoading(false);
        }
    };

    const handleUpdateTariff = async (tariff: TariffInfo, newRate: number, isActive?: boolean) => {
        if (!territory) return;

        if (newRate < min_rate || newRate > max_rate) {
            setError(`Tariff rate must be between ${min_rate}% and ${max_rate}%`);
            return;
        }

        setLoading(true);
        setError(null);

        try {
            const response = await fetch(`/trade/tariffs/${tariff.id}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content || '',
                },
                body: JSON.stringify({
                    tariff_rate: newRate,
                    is_active: isActive ?? tariff.is_active,
                }),
            });

            const data = await response.json();
            if (data.success) {
                setSuccess(data.message);
                setEditingTariff(null);
                router.reload();
            } else {
                setError(data.message);
            }
        } catch {
            setError('Failed to update tariff');
        } finally {
            setLoading(false);
        }
    };

    // No territory - show empty state
    if (!can_manage || !territory) {
        return (
            <AppLayout breadcrumbs={breadcrumbs}>
                <Head title="Tariff Management" />
                <div className="flex h-full flex-1 flex-col items-center justify-center p-4">
                    <div className="text-center">
                        <Percent className="mx-auto mb-4 h-16 w-16 text-stone-600" />
                        <h1 className="mb-2 font-pixel text-xl text-stone-400">No Territory to Manage</h1>
                        <p className="font-pixel text-sm text-stone-500">
                            You must be a Baron or King to manage trade tariffs.
                        </p>
                    </div>
                </div>
            </AppLayout>
        );
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Tariff Management" />
            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="font-pixel text-2xl text-amber-400">Tariff Management</h1>
                        <p className="font-pixel text-sm text-stone-400">
                            Your Territory: <span className="text-amber-300">{territory.name}</span>
                        </p>
                    </div>
                    <button
                        onClick={() => setShowAddForm(!showAddForm)}
                        className="flex items-center gap-2 rounded border-2 border-amber-600/50 bg-amber-900/20 px-4 py-2 font-pixel text-xs text-amber-300 transition hover:bg-amber-900/40"
                    >
                        <Plus className="h-4 w-4" />
                        {showAddForm ? 'Cancel' : 'Add Tariff'}
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

                {/* Add Tariff Form */}
                {showAddForm && (
                    <div className="rounded-xl border-2 border-amber-500/30 bg-amber-900/20 p-4">
                        <h3 className="mb-4 font-pixel text-base text-amber-300">Create New Tariff</h3>

                        <div className="mb-4 grid gap-4 md:grid-cols-2">
                            <div>
                                <label className="mb-1 block font-pixel text-xs text-stone-400">Item (optional)</label>
                                <select
                                    value={formData.item_id}
                                    onChange={(e) => setFormData({ ...formData, item_id: e.target.value })}
                                    className="w-full rounded border border-stone-600 bg-stone-800 px-3 py-2 font-pixel text-sm text-white focus:border-amber-500 focus:outline-none"
                                >
                                    <option value="">All Goods (General Tariff)</option>
                                    {items.map((item) => (
                                        <option key={item.id} value={item.id}>
                                            {item.name} ({item.type})
                                        </option>
                                    ))}
                                </select>
                                <p className="mt-1 font-pixel text-[10px] text-stone-500">
                                    Leave empty to apply to all goods
                                </p>
                            </div>
                            <div>
                                <label className="mb-1 block font-pixel text-xs text-stone-400">Tariff Rate (%)</label>
                                <input
                                    type="number"
                                    value={formData.tariff_rate}
                                    onChange={(e) => setFormData({ ...formData, tariff_rate: e.target.value })}
                                    min={min_rate}
                                    max={max_rate}
                                    className="w-full rounded border border-stone-600 bg-stone-800 px-3 py-2 font-pixel text-sm text-white focus:border-amber-500 focus:outline-none"
                                />
                                <p className="mt-1 font-pixel text-[10px] text-stone-500">
                                    Must be between {min_rate}% and {max_rate}%
                                </p>
                            </div>
                        </div>

                        <button
                            onClick={handleCreateTariff}
                            disabled={loading}
                            className="w-full rounded bg-amber-600 py-2 font-pixel text-sm text-white transition hover:bg-amber-500 disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            {loading ? <Loader2 className="mx-auto h-4 w-4 animate-spin" /> : 'Create Tariff'}
                        </button>
                    </div>
                )}

                <div className="grid gap-6 lg:grid-cols-2">
                    {/* Left Column - Routes Through Territory */}
                    <div className="space-y-4">
                        <div className="rounded-xl border-2 border-stone-700 bg-stone-800/50 p-4">
                            <h2 className="mb-4 font-pixel text-sm text-stone-300">Trade Routes Through Your Territory</h2>

                            {routes.length > 0 ? (
                                <div className="space-y-3">
                                    {routes.map((route) => (
                                        <div
                                            key={route.id}
                                            className="rounded-lg border border-stone-600 bg-stone-900/50 p-3"
                                        >
                                            {/* Route Path */}
                                            <div className="mb-3 flex items-center gap-2">
                                                <Route className="h-4 w-4 text-amber-400" />
                                                <div className="flex flex-1 items-center gap-1 font-pixel text-xs">
                                                    <span className="text-white">{route.origin.name}</span>
                                                    <ArrowRight className="h-3 w-3 text-stone-500" />
                                                    <span className="text-white">{route.destination.name}</span>
                                                </div>
                                            </div>

                                            {/* Route Stats */}
                                            <div className="grid grid-cols-3 gap-2 text-center">
                                                <div className="rounded bg-stone-800 px-2 py-1">
                                                    <div className="font-pixel text-[10px] text-stone-500">Tariff</div>
                                                    <div className="font-pixel text-sm text-yellow-400">{route.tariff_rate}%</div>
                                                </div>
                                                <div className="rounded bg-stone-800 px-2 py-1">
                                                    <div className="font-pixel text-[10px] text-stone-500">This Week</div>
                                                    <div className="flex items-center justify-center gap-1 font-pixel text-sm text-stone-300">
                                                        <Truck className="h-3 w-3" />
                                                        {route.caravans_this_week}
                                                    </div>
                                                </div>
                                                <div className="rounded bg-stone-800 px-2 py-1">
                                                    <div className="font-pixel text-[10px] text-stone-500">Revenue</div>
                                                    <div className="font-pixel text-sm text-green-400">{formatGold(route.revenue)}g</div>
                                                </div>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            ) : (
                                <div className="py-8 text-center">
                                    <MapPin className="mx-auto h-8 w-8 text-stone-600" />
                                    <p className="mt-2 font-pixel text-xs text-stone-500">No trade routes pass through your territory</p>
                                </div>
                            )}
                        </div>

                        {/* Revenue Summary */}
                        <div className="rounded-xl border-2 border-green-600/50 bg-green-900/20 p-4">
                            <div className="mb-3 flex items-center gap-2">
                                <Coins className="h-5 w-5 text-green-400" />
                                <h2 className="font-pixel text-sm text-green-300">Revenue Summary</h2>
                            </div>
                            <div className="grid grid-cols-3 gap-3">
                                <div className="text-center">
                                    <div className="font-pixel text-[10px] text-stone-400">This Week</div>
                                    <div className="font-pixel text-lg text-green-400">{formatGold(revenue.this_week)}g</div>
                                </div>
                                <div className="text-center">
                                    <div className="font-pixel text-[10px] text-stone-400">This Month</div>
                                    <div className="font-pixel text-lg text-green-400">{formatGold(revenue.this_month)}g</div>
                                </div>
                                <div className="text-center">
                                    <div className="font-pixel text-[10px] text-stone-400">Total</div>
                                    <div className="font-pixel text-lg text-green-400">{formatGold(revenue.total)}g</div>
                                </div>
                            </div>
                        </div>

                        {/* Warning */}
                        <div className="rounded-lg border border-orange-500/30 bg-orange-900/10 p-3">
                            <div className="flex gap-2">
                                <AlertTriangle className="h-4 w-4 shrink-0 text-orange-400" />
                                <p className="font-pixel text-[10px] text-orange-300">
                                    High tariffs (&gt;25%) may cause merchants to avoid your territory or use alternate routes.
                                </p>
                            </div>
                        </div>
                    </div>

                    {/* Right Column - Active Tariffs */}
                    <div className="space-y-4">
                        <div className="rounded-xl border-2 border-stone-700 bg-stone-800/50 p-4">
                            <div className="mb-4 flex items-center justify-between">
                                <h2 className="font-pixel text-sm text-stone-300">Active Tariffs</h2>
                                <span className="rounded bg-stone-700 px-2 py-0.5 font-pixel text-[10px] text-stone-400">
                                    {tariffs.filter((t) => t.is_active).length} active
                                </span>
                            </div>

                            {tariffs.length > 0 ? (
                                <div className="space-y-3">
                                    {tariffs.map((tariff) => (
                                        <div
                                            key={tariff.id}
                                            className={`rounded-lg border p-3 ${
                                                tariff.is_active
                                                    ? 'border-amber-600/50 bg-amber-900/20'
                                                    : 'border-stone-600 bg-stone-900/50 opacity-60'
                                            }`}
                                        >
                                            <div className="mb-2 flex items-center justify-between">
                                                <div className="flex items-center gap-2">
                                                    <Percent className="h-4 w-4 text-amber-400" />
                                                    <span className="font-pixel text-sm text-white">{tariff.item_name}</span>
                                                </div>
                                                <span
                                                    className={`rounded px-2 py-0.5 font-pixel text-[10px] ${
                                                        tariff.is_active
                                                            ? 'bg-green-900/50 text-green-400'
                                                            : 'bg-stone-700 text-stone-400'
                                                    }`}
                                                >
                                                    {tariff.is_active ? 'Active' : 'Inactive'}
                                                </span>
                                            </div>

                                            {editingTariff?.id === tariff.id ? (
                                                <div className="flex items-center gap-2">
                                                    <input
                                                        type="number"
                                                        defaultValue={tariff.tariff_rate}
                                                        min={min_rate}
                                                        max={max_rate}
                                                        id={`rate-${tariff.id}`}
                                                        className="w-20 rounded border border-stone-600 bg-stone-800 px-2 py-1 font-pixel text-sm text-white focus:border-amber-500 focus:outline-none"
                                                    />
                                                    <span className="font-pixel text-xs text-stone-400">%</span>
                                                    <button
                                                        onClick={() => {
                                                            const input = document.getElementById(`rate-${tariff.id}`) as HTMLInputElement;
                                                            const newRate = parseInt(input.value, 10);
                                                            handleUpdateTariff(tariff, newRate);
                                                        }}
                                                        disabled={loading}
                                                        className="rounded bg-green-600 px-2 py-1 font-pixel text-[10px] text-white hover:bg-green-500 disabled:opacity-50"
                                                    >
                                                        Save
                                                    </button>
                                                    <button
                                                        onClick={() => setEditingTariff(null)}
                                                        className="rounded bg-stone-600 px-2 py-1 font-pixel text-[10px] text-white hover:bg-stone-500"
                                                    >
                                                        Cancel
                                                    </button>
                                                </div>
                                            ) : (
                                                <div className="flex items-center justify-between">
                                                    <div className="grid grid-cols-2 gap-4">
                                                        <div>
                                                            <div className="font-pixel text-[10px] text-stone-500">Rate</div>
                                                            <div className="font-pixel text-lg text-yellow-400">{tariff.tariff_rate}%</div>
                                                        </div>
                                                        <div>
                                                            <div className="font-pixel text-[10px] text-stone-500">Collected</div>
                                                            <div className="font-pixel text-sm text-green-400">
                                                                {formatGold(tariff.total_collected)}g
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div className="flex gap-1">
                                                        <button
                                                            onClick={() => setEditingTariff(tariff)}
                                                            className="rounded bg-stone-700 px-2 py-1 font-pixel text-[10px] text-stone-300 hover:bg-stone-600"
                                                        >
                                                            <Settings className="h-3 w-3" />
                                                        </button>
                                                        <button
                                                            onClick={() => handleUpdateTariff(tariff, tariff.tariff_rate, !tariff.is_active)}
                                                            disabled={loading}
                                                            className={`rounded px-2 py-1 font-pixel text-[10px] ${
                                                                tariff.is_active
                                                                    ? 'bg-red-900/50 text-red-400 hover:bg-red-800/50'
                                                                    : 'bg-green-900/50 text-green-400 hover:bg-green-800/50'
                                                            }`}
                                                        >
                                                            {tariff.is_active ? 'Disable' : 'Enable'}
                                                        </button>
                                                    </div>
                                                </div>
                                            )}

                                            {tariff.set_by && (
                                                <div className="mt-2 font-pixel text-[10px] text-stone-500">
                                                    Set by {tariff.set_by.username}
                                                </div>
                                            )}
                                        </div>
                                    ))}
                                </div>
                            ) : (
                                <div className="py-8 text-center">
                                    <Percent className="mx-auto h-8 w-8 text-stone-600" />
                                    <p className="mt-2 font-pixel text-xs text-stone-500">No tariffs configured</p>
                                    <p className="font-pixel text-[10px] text-stone-600">
                                        Create a tariff to collect fees from passing caravans
                                    </p>
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
