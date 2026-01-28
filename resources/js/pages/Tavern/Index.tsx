import { Head, router, usePage } from '@inertiajs/react';
import { Beer, Coins, Loader2, MessageCircle, Zap } from 'lucide-react';
import { useState } from 'react';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

interface Activity {
    id: number;
    username: string;
    description: string;
    type: string;
    subtype: string | null;
    time_ago: string;
}

interface PageProps {
    location: {
        type: string;
        id: number;
        name: string;
    } | null;
    player: {
        energy: number;
        max_energy: number;
        gold: number;
    };
    rest: {
        cost: number;
        energy_restored: number;
        can_rest: boolean;
    };
    recent_activity: Activity[];
    [key: string]: unknown;
}

export default function TavernIndex() {
    const { location, player, rest, recent_activity } = usePage<PageProps>().props;
    const [loading, setLoading] = useState(false);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        ...(location
            ? [
                  { title: location.name, href: `/${location.type}s/${location.id}` },
                  { title: 'Tavern', href: '#' },
              ]
            : [{ title: 'Tavern', href: '#' }]),
    ];

    const handleRest = () => {
        setLoading(true);
        router.post(
            `/villages/${location?.id}/tavern/rest`,
            {},
            {
                preserveScroll: true,
                onSuccess: () => {
                    router.reload();
                },
                onFinish: () => setLoading(false),
            }
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Tavern - ${location?.name || 'Unknown'}`} />
            <div className="flex h-full flex-1 flex-col overflow-hidden p-4">
                {/* Header */}
                <div className="mb-6 flex items-center justify-between">
                    <div className="flex items-center gap-3">
                        <div className="rounded-lg bg-amber-900/30 p-3">
                            <Beer className="h-8 w-8 text-amber-400" />
                        </div>
                        <div>
                            <h1 className="font-pixel text-2xl text-amber-400">Tavern</h1>
                            <p className="font-pixel text-sm text-stone-400">
                                Rest your weary bones at {location?.name}
                            </p>
                        </div>
                    </div>
                    <div className="flex items-center gap-4">
                        <div className="flex items-center gap-2 rounded-lg border-2 border-stone-600/50 bg-stone-800/50 px-4 py-2">
                            <Zap className="h-4 w-4 text-yellow-400" />
                            <span className="font-pixel text-sm text-stone-300">
                                {player.energy}/{player.max_energy}
                            </span>
                        </div>
                        <div className="flex items-center gap-2 rounded-lg border-2 border-stone-600/50 bg-stone-800/50 px-4 py-2">
                            <Coins className="h-4 w-4 text-amber-400" />
                            <span className="font-pixel text-sm text-amber-300">{player.gold}g</span>
                        </div>
                    </div>
                </div>

                <div className="grid gap-6 lg:grid-cols-2">
                    {/* Rest Section */}
                    <div className="rounded-xl border-2 border-amber-600/50 bg-amber-900/20 p-6">
                        <h2 className="mb-4 font-pixel text-lg text-amber-300">Rest & Recuperate</h2>
                        <p className="mb-4 text-sm text-stone-300">
                            Sit by the fire, enjoy a warm meal, and recover your energy. The innkeeper will
                            ensure you're well taken care of.
                        </p>

                        <div className="mb-4 grid grid-cols-2 gap-4">
                            <div className="rounded-lg bg-stone-800/50 p-4 text-center">
                                <div className="flex items-center justify-center gap-1">
                                    <Coins className="h-5 w-5 text-amber-400" />
                                    <span className="font-pixel text-xl text-amber-300">{rest.cost}g</span>
                                </div>
                                <div className="font-pixel text-xs text-stone-500">Cost</div>
                            </div>
                            <div className="rounded-lg bg-stone-800/50 p-4 text-center">
                                <div className="flex items-center justify-center gap-1">
                                    <Zap className="h-5 w-5 text-yellow-400" />
                                    <span className="font-pixel text-xl text-yellow-300">+{rest.energy_restored}</span>
                                </div>
                                <div className="font-pixel text-xs text-stone-500">Energy</div>
                            </div>
                        </div>

                        {player.energy >= player.max_energy ? (
                            <div className="rounded-lg bg-green-900/30 p-3 text-center">
                                <span className="font-pixel text-sm text-green-300">You are fully rested!</span>
                            </div>
                        ) : player.gold < rest.cost ? (
                            <div className="rounded-lg bg-red-900/30 p-3 text-center">
                                <span className="font-pixel text-sm text-red-300">
                                    You need {rest.cost}g to rest here
                                </span>
                            </div>
                        ) : (
                            <button
                                onClick={handleRest}
                                disabled={loading || !rest.can_rest}
                                className="flex w-full items-center justify-center gap-2 rounded-lg border-2 border-amber-500 bg-amber-900/30 px-4 py-3 font-pixel text-sm text-amber-300 transition hover:bg-amber-800/50 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                {loading ? (
                                    <Loader2 className="h-5 w-5 animate-spin" />
                                ) : (
                                    <>
                                        <Beer className="h-5 w-5" />
                                        Rest at the Tavern
                                    </>
                                )}
                            </button>
                        )}
                    </div>

                    {/* Rumors Section */}
                    <div className="rounded-xl border-2 border-stone-600/50 bg-stone-800/30 p-6">
                        <div className="mb-4 flex items-center gap-2">
                            <MessageCircle className="h-5 w-5 text-stone-400" />
                            <h2 className="font-pixel text-lg text-stone-300">Local Rumors</h2>
                        </div>
                        <p className="mb-4 text-sm text-stone-400">
                            Overhear what the locals have been up to...
                        </p>

                        {recent_activity.length > 0 ? (
                            <div className="max-h-80 space-y-2 overflow-y-auto">
                                {recent_activity.map((activity) => (
                                    <div
                                        key={activity.id}
                                        className="rounded-lg bg-stone-900/50 p-3"
                                    >
                                        <p className="text-sm text-stone-300">{activity.description}</p>
                                        <p className="mt-1 font-pixel text-[10px] text-stone-500">
                                            {activity.time_ago}
                                        </p>
                                    </div>
                                ))}
                            </div>
                        ) : (
                            <div className="py-8 text-center">
                                <MessageCircle className="mx-auto mb-2 h-8 w-8 text-stone-600" />
                                <p className="font-pixel text-sm text-stone-500">
                                    The tavern is quiet today...
                                </p>
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
