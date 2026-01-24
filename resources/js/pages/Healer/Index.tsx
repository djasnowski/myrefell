import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';
import { Head, router, usePage } from '@inertiajs/react';
import { Castle, Church, Coins, Heart, Home, Loader2, Sparkles } from 'lucide-react';
import { useState } from 'react';

interface HealingOption {
    id: string;
    label: string;
    description: string;
    hp_restored: number;
    cost: number;
}

interface HealerInfo {
    location_type: string;
    location_id: number;
    location_name: string;
    healer_name: string;
    healer_title: string;
    hp: number;
    max_hp: number;
    missing_hp: number;
    gold: number;
    cost_per_hp: number;
    options: HealingOption[];
}

interface PageProps {
    healer_info: HealerInfo;
    [key: string]: unknown;
}

const locationIcons: Record<string, typeof Home> = {
    village: Home,
    castle: Castle,
    town: Church,
};

function formatNumber(n: number): string {
    return n.toLocaleString();
}

function HealthBar({ current, max }: { current: number; max: number }) {
    const percent = Math.round((current / max) * 100);
    const barColor = percent > 60 ? 'bg-green-500' : percent > 30 ? 'bg-yellow-500' : 'bg-red-500';

    return (
        <div className="w-full">
            <div className="mb-1 flex justify-between font-pixel text-xs">
                <span className="text-stone-400">Health</span>
                <span className={percent > 60 ? 'text-green-400' : percent > 30 ? 'text-yellow-400' : 'text-red-400'}>
                    {current} / {max}
                </span>
            </div>
            <div className="h-4 w-full overflow-hidden rounded-full bg-stone-700">
                <div className={`h-full transition-all duration-500 ${barColor}`} style={{ width: `${percent}%` }} />
            </div>
        </div>
    );
}

export default function HealerIndex() {
    const { healer_info } = usePage<PageProps>().props;
    const [loading, setLoading] = useState<string | null>(null);
    const [error, setError] = useState<string | null>(null);
    const [success, setSuccess] = useState<string | null>(null);

    const LocationIcon = locationIcons[healer_info.location_type] || Home;
    const isFullHealth = healer_info.missing_hp <= 0;

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: healer_info.location_name, href: `/${healer_info.location_type}s/${healer_info.location_id}` },
        { title: healer_info.location_type === 'village' ? 'Healer' : 'Infirmary', href: '#' },
    ];

    const handleHeal = async (optionId: string) => {
        setLoading(optionId);
        setError(null);
        setSuccess(null);

        try {
            const response = await fetch('/healer/heal', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
                body: JSON.stringify({ option: optionId }),
            });

            const data = await response.json();

            if (data.success) {
                setSuccess(data.message);
                router.reload({ only: ['healer_info', 'sidebar'] });
            } else {
                setError(data.message);
            }
        } catch {
            setError('An error occurred');
        } finally {
            setLoading(null);
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`${healer_info.location_type === 'village' ? 'Healer' : 'Infirmary'} - ${healer_info.location_name}`} />
            <div className="flex h-full flex-1 flex-col p-4">
                {/* Header */}
                <div className="mb-6 flex items-center gap-3">
                    <div className="rounded-lg bg-red-900/30 p-3">
                        <Heart className="h-8 w-8 text-red-400" />
                    </div>
                    <div>
                        <h1 className="font-pixel text-2xl text-red-400">
                            {healer_info.location_type === 'village' ? 'Healer' : 'Infirmary'}
                        </h1>
                        <div className="flex items-center gap-1 text-stone-400">
                            <LocationIcon className="h-3 w-3" />
                            <span className="font-pixel text-xs">{healer_info.location_name}</span>
                        </div>
                    </div>
                </div>

                <div className="mx-auto w-full max-w-2xl">
                    {/* Healer Introduction */}
                    <div className="mb-6 rounded-xl border-2 border-stone-700 bg-stone-800/50 p-6">
                        <div className="mb-4 flex items-center gap-4">
                            <div className="flex h-16 w-16 items-center justify-center rounded-full bg-stone-700">
                                <Sparkles className="h-8 w-8 text-amber-400" />
                            </div>
                            <div>
                                <h2 className="font-pixel text-lg text-amber-300">{healer_info.healer_name}</h2>
                                <p className="font-pixel text-xs text-stone-400">{healer_info.healer_title}</p>
                            </div>
                        </div>
                        <p className="font-pixel text-xs leading-relaxed text-stone-300">
                            {isFullHealth
                                ? '"You look well, traveler. No need for my services today. Come back if you get into trouble."'
                                : '"Ah, I see you\'ve been in a scuffle. Let me tend to your wounds. My services are not free, but fair."'}
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

                    {/* Health Status */}
                    <div className="mb-6 rounded-xl border-2 border-stone-700 bg-stone-800/50 p-4">
                        <HealthBar current={healer_info.hp} max={healer_info.max_hp} />
                        <div className="mt-3 flex items-center justify-between">
                            <div className="flex items-center gap-1 text-stone-400">
                                <Heart className="h-3 w-3 text-red-400" />
                                <span className="font-pixel text-[10px]">
                                    Missing: {formatNumber(healer_info.missing_hp)} HP
                                </span>
                            </div>
                            <div className="flex items-center gap-1 text-stone-400">
                                <Coins className="h-3 w-3 text-yellow-400" />
                                <span className="font-pixel text-[10px]">Your Gold: {formatNumber(healer_info.gold)}</span>
                            </div>
                        </div>
                    </div>

                    {/* Healing Options */}
                    {isFullHealth ? (
                        <div className="rounded-xl border-2 border-green-600/50 bg-green-900/20 p-8 text-center">
                            <Heart className="mx-auto mb-3 h-12 w-12 text-green-400" />
                            <h3 className="mb-2 font-pixel text-lg text-green-400">Full Health</h3>
                            <p className="font-pixel text-xs text-stone-400">You are in perfect condition.</p>
                        </div>
                    ) : (
                        <div className="space-y-3">
                            <h3 className="font-pixel text-sm text-stone-300">Healing Services</h3>
                            {healer_info.options.map((option) => {
                                const canAfford = healer_info.gold >= option.cost;
                                const isLoading = loading === option.id;

                                return (
                                    <button
                                        key={option.id}
                                        onClick={() => handleHeal(option.id)}
                                        disabled={!canAfford || loading !== null}
                                        className={`w-full rounded-xl border-2 p-4 text-left transition ${
                                            canAfford
                                                ? 'border-red-600/50 bg-red-900/20 hover:bg-red-900/30'
                                                : 'cursor-not-allowed border-stone-700 bg-stone-800/30 opacity-50'
                                        }`}
                                    >
                                        <div className="flex items-center justify-between">
                                            <div>
                                                <h4 className="font-pixel text-sm text-red-300">{option.label}</h4>
                                                <p className="font-pixel text-[10px] text-stone-400">{option.description}</p>
                                            </div>
                                            <div className="text-right">
                                                <div className="flex items-center gap-1">
                                                    {isLoading ? (
                                                        <Loader2 className="h-4 w-4 animate-spin text-red-400" />
                                                    ) : (
                                                        <Heart className="h-4 w-4 text-red-400" />
                                                    )}
                                                    <span className="font-pixel text-sm text-red-300">
                                                        +{formatNumber(option.hp_restored)}
                                                    </span>
                                                </div>
                                                <div className="flex items-center justify-end gap-1">
                                                    <Coins className="h-3 w-3 text-yellow-400" />
                                                    <span
                                                        className={`font-pixel text-xs ${canAfford ? 'text-yellow-400' : 'text-red-400'}`}
                                                    >
                                                        {formatNumber(option.cost)}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </button>
                                );
                            })}
                        </div>
                    )}

                    {/* Cost Info */}
                    <div className="mt-6 text-center">
                        <p className="font-pixel text-[10px] text-stone-500">
                            Healing costs {healer_info.cost_per_hp} gold per HP restored
                        </p>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
