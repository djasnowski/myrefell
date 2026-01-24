import { Head, router, usePage } from '@inertiajs/react';
import { ArrowUp, Axe, Backpack, Fish, Loader2, Package, Pickaxe, Sparkles, Zap } from 'lucide-react';
import { useState } from 'react';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

interface Resource {
    name: string;
    weight: number;
    min_level: number;
    xp_bonus: number;
}

interface Activity {
    id: string;
    name: string;
    skill: string;
    skill_level: number;
    energy_cost: number;
    base_xp: number;
    player_energy: number;
    can_gather: boolean;
    resources: Resource[];
    next_unlock: Resource | null;
    inventory_full: boolean;
    free_slots: number;
}

interface GatherResult {
    success: boolean;
    message: string;
    resource?: {
        name: string;
        description: string;
    };
    xp_awarded?: number;
    skill?: string;
    leveled_up?: boolean;
    energy_remaining?: number;
}

interface PageProps {
    activity: Activity;
    player_energy: number;
    max_energy: number;
    [key: string]: unknown;
}

const activityIcons: Record<string, typeof Pickaxe> = {
    mining: Pickaxe,
    fishing: Fish,
    woodcutting: Axe,
};

const activityBgColors: Record<string, string> = {
    mining: 'from-stone-800 to-stone-900 border-stone-600',
    fishing: 'from-blue-900/50 to-stone-900 border-blue-600/50',
    woodcutting: 'from-green-900/50 to-stone-900 border-green-600/50',
};

export default function GatheringActivity() {
    const { activity, player_energy, max_energy } = usePage<PageProps>().props;
    const [loading, setLoading] = useState(false);
    const [result, setResult] = useState<GatherResult | null>(null);
    const [currentEnergy, setCurrentEnergy] = useState(player_energy);

    const Icon = activityIcons[activity.id] || Pickaxe;
    const bgColor = activityBgColors[activity.id] || 'from-stone-800 to-stone-900 border-stone-600';

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Gathering', href: '/gathering' },
        { title: activity.name, href: `/gathering/${activity.id}` },
    ];

    const canGather = currentEnergy >= activity.energy_cost && !activity.inventory_full;

    const handleGather = async () => {
        if (!canGather || loading) return;

        setLoading(true);
        setResult(null);

        try {
            const response = await fetch('/gathering/gather', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
                body: JSON.stringify({ activity: activity.id }),
            });

            const data: GatherResult = await response.json();
            setResult(data);

            if (data.success && data.energy_remaining !== undefined) {
                setCurrentEnergy(data.energy_remaining);
            }

            // Reload sidebar data
            router.reload({ only: ['sidebar'] });
        } catch {
            setResult({ success: false, message: 'An error occurred' });
        } finally {
            setLoading(false);
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={activity.name} />
            <div className="flex h-full flex-1 flex-col p-4">
                <div className="mx-auto w-full max-w-2xl">
                    {/* Header Card */}
                    <div className={`mb-6 rounded-xl border-2 bg-gradient-to-br p-6 ${bgColor}`}>
                        <div className="flex items-center gap-4">
                            <div className="rounded-lg bg-stone-800/50 p-4">
                                <Icon className="h-12 w-12 text-amber-400" />
                            </div>
                            <div>
                                <h1 className="font-pixel text-2xl text-amber-400">{activity.name}</h1>
                                <p className="font-pixel text-xs capitalize text-stone-400">
                                    {activity.skill} Level {activity.skill_level}
                                </p>
                            </div>
                        </div>
                    </div>

                    {/* Energy and Inventory Status */}
                    <div className="mb-6 grid grid-cols-2 gap-4">
                        <div className="rounded-lg border border-stone-700 bg-stone-800/50 p-3">
                            <div className="mb-1 flex items-center gap-1 font-pixel text-xs text-yellow-400">
                                <Zap className="h-3 w-3" />
                                Energy
                            </div>
                            <div className="h-2 w-full overflow-hidden rounded-full bg-stone-700">
                                <div
                                    className="h-full bg-gradient-to-r from-yellow-600 to-yellow-400 transition-all"
                                    style={{ width: `${(currentEnergy / max_energy) * 100}%` }}
                                />
                            </div>
                            <div className="mt-1 font-pixel text-[10px] text-stone-400">
                                {currentEnergy} / {max_energy} ({activity.energy_cost} per action)
                            </div>
                        </div>
                        <div className="rounded-lg border border-stone-700 bg-stone-800/50 p-3">
                            <div className="mb-1 flex items-center gap-1 font-pixel text-xs text-amber-300">
                                <Backpack className="h-3 w-3" />
                                Inventory
                            </div>
                            <div className="font-pixel text-lg text-stone-300">{activity.free_slots} slots</div>
                            <div className="font-pixel text-[10px] text-stone-500">
                                {activity.inventory_full ? 'Inventory is full!' : 'Space available'}
                            </div>
                        </div>
                    </div>

                    {/* Result Display */}
                    {result && (
                        <div
                            className={`mb-6 rounded-lg border p-4 ${
                                result.success
                                    ? 'border-green-600/50 bg-green-900/20'
                                    : 'border-red-600/50 bg-red-900/20'
                            }`}
                        >
                            <div className="flex items-center gap-3">
                                {result.success && result.resource && (
                                    <>
                                        <Package className="h-8 w-8 text-green-400" />
                                        <div>
                                            <div className="font-pixel text-sm text-green-300">
                                                {result.resource.name}
                                            </div>
                                            <div className="flex items-center gap-2">
                                                <span className="font-pixel text-[10px] text-amber-400">
                                                    +{result.xp_awarded} XP
                                                </span>
                                                {result.leveled_up && (
                                                    <span className="flex items-center gap-1 font-pixel text-[10px] text-yellow-300">
                                                        <ArrowUp className="h-3 w-3" />
                                                        Level Up!
                                                    </span>
                                                )}
                                            </div>
                                        </div>
                                    </>
                                )}
                                {!result.success && (
                                    <div className="font-pixel text-sm text-red-400">{result.message}</div>
                                )}
                            </div>
                        </div>
                    )}

                    {/* Gather Button */}
                    <button
                        onClick={handleGather}
                        disabled={!canGather || loading}
                        className={`mb-6 flex w-full items-center justify-center gap-3 rounded-xl border-2 px-6 py-4 font-pixel text-lg transition ${
                            canGather && !loading
                                ? 'border-amber-600 bg-amber-900/30 text-amber-300 hover:bg-amber-800/50'
                                : 'cursor-not-allowed border-stone-700 bg-stone-800/50 text-stone-500'
                        }`}
                    >
                        {loading ? (
                            <>
                                <Loader2 className="h-6 w-6 animate-spin" />
                                Gathering...
                            </>
                        ) : (
                            <>
                                <Icon className="h-6 w-6" />
                                Gather
                            </>
                        )}
                    </button>

                    {/* Available Resources */}
                    <div className="rounded-xl border-2 border-stone-700 bg-stone-800/50 p-4">
                        <h2 className="mb-4 font-pixel text-sm text-stone-300">Available Resources</h2>
                        <div className="grid gap-2">
                            {activity.resources.map((resource) => (
                                <div
                                    key={resource.name}
                                    className="flex items-center justify-between rounded-lg bg-stone-900/50 px-3 py-2"
                                >
                                    <div className="flex items-center gap-2">
                                        <Package className="h-4 w-4 text-stone-400" />
                                        <span className="font-pixel text-xs text-stone-300">{resource.name}</span>
                                    </div>
                                    <span className="font-pixel text-[10px] text-amber-400">
                                        +{activity.base_xp + resource.xp_bonus} XP
                                    </span>
                                </div>
                            ))}
                        </div>

                        {/* Next Unlock */}
                        {activity.next_unlock && (
                            <div className="mt-4 rounded-lg border border-stone-600 bg-stone-900/30 p-3">
                                <div className="flex items-center gap-2">
                                    <Sparkles className="h-4 w-4 text-purple-400" />
                                    <span className="font-pixel text-xs text-purple-300">Next unlock</span>
                                </div>
                                <div className="mt-1 font-pixel text-sm text-stone-300">
                                    {activity.next_unlock.name}
                                </div>
                                <div className="font-pixel text-[10px] text-stone-500">
                                    Requires Level {activity.next_unlock.min_level}
                                </div>
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
