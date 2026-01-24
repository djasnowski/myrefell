import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';
import { Head, router, usePage } from '@inertiajs/react';
import {
    Axe,
    Bug,
    Check,
    ChevronRight,
    Coins,
    Fish,
    Gem,
    Hammer,
    Loader2,
    PawPrint,
    ScrollText,
    Swords,
    Utensils,
    X,
} from 'lucide-react';
import { useState } from 'react';

interface Quest {
    id: number;
    name: string;
    icon: string;
    description: string;
    objective: string;
    category: string;
    category_display: string;
    target_amount: number;
    gold_reward: number;
    xp_reward: number;
    xp_skill: string | null;
    repeatable: boolean;
}

interface PlayerQuest {
    id: number;
    quest_id: number;
    name: string;
    icon: string;
    description: string;
    objective: string;
    category: string;
    target_amount: number;
    current_progress: number;
    progress_percent: number;
    status: string;
    can_claim: boolean;
    gold_reward: number;
    xp_reward: number;
    xp_skill: string | null;
}

interface PageProps {
    available_quests: Quest[];
    active_quests: PlayerQuest[];
    completed_quests: PlayerQuest[];
    max_active_quests: number;
    village_id: number;
    [key: string]: unknown;
}

const iconMap: Record<string, typeof ScrollText> = {
    bug: Bug,
    'paw-print': PawPrint,
    gem: Gem,
    fish: Fish,
    axe: Axe,
    hammer: Hammer,
    utensils: Utensils,
    scroll: ScrollText,
    swords: Swords,
};

const categoryColors: Record<string, string> = {
    combat: 'border-red-600/50 bg-red-900/20',
    gathering: 'border-green-600/50 bg-green-900/20',
    delivery: 'border-blue-600/50 bg-blue-900/20',
    exploration: 'border-purple-600/50 bg-purple-900/20',
};

function QuestIcon({ icon, className }: { icon: string; className?: string }) {
    const IconComponent = iconMap[icon] || ScrollText;
    return <IconComponent className={className} />;
}

export default function NoticeBoard() {
    const { available_quests, active_quests, completed_quests, max_active_quests, village_id } =
        usePage<PageProps>().props;
    const [loading, setLoading] = useState<number | null>(null);
    const [claimLoading, setClaimLoading] = useState<number | null>(null);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Village', href: `/villages/${village_id}` },
        { title: 'Notice Board', href: '#' },
    ];

    const canAcceptMore = active_quests.length < max_active_quests;

    const handleAccept = async (questId: number) => {
        setLoading(questId);
        try {
            await fetch('/quests/accept', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
                body: JSON.stringify({ quest_id: questId }),
            });
            router.reload({ only: ['available_quests', 'active_quests', 'completed_quests', 'sidebar'] });
        } finally {
            setLoading(null);
        }
    };

    const handleClaim = async (playerQuestId: number) => {
        setClaimLoading(playerQuestId);
        try {
            await fetch(`/quests/${playerQuestId}/claim`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
            });
            router.reload({ only: ['available_quests', 'active_quests', 'completed_quests', 'sidebar'] });
        } finally {
            setClaimLoading(null);
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Notice Board" />
            <div className="flex h-full flex-1 flex-col p-4">
                {/* Header */}
                <div className="mb-6 flex items-center gap-3">
                    <div className="rounded-lg bg-amber-900/30 p-3">
                        <ScrollText className="h-8 w-8 text-amber-400" />
                    </div>
                    <div>
                        <h1 className="font-pixel text-2xl text-amber-400">Notice Board</h1>
                        <p className="font-pixel text-xs text-stone-400">
                            Active: {active_quests.length}/{max_active_quests}
                        </p>
                    </div>
                </div>

                <div className="grid gap-6 lg:grid-cols-2">
                    {/* Left Column - Active & Completed Quests */}
                    <div className="space-y-4">
                        {/* Completed Quests */}
                        {completed_quests.length > 0 && (
                            <div className="rounded-xl border-2 border-green-600/50 bg-green-900/20 p-4">
                                <h2 className="mb-3 font-pixel text-sm text-green-300">Ready to Claim</h2>
                                <div className="space-y-2">
                                    {completed_quests.map((pq) => (
                                        <div
                                            key={pq.id}
                                            className="flex items-center justify-between rounded-lg bg-stone-900/50 px-3 py-2"
                                        >
                                            <div className="flex items-center gap-2">
                                                <QuestIcon icon={pq.icon} className="h-4 w-4 text-green-400" />
                                                <span className="font-pixel text-xs text-stone-300">{pq.name}</span>
                                            </div>
                                            <button
                                                onClick={() => handleClaim(pq.id)}
                                                disabled={claimLoading !== null}
                                                className="flex items-center gap-1 rounded bg-green-600 px-2 py-1 font-pixel text-[10px] text-stone-900 transition hover:bg-green-500"
                                            >
                                                {claimLoading === pq.id ? (
                                                    <Loader2 className="h-3 w-3 animate-spin" />
                                                ) : (
                                                    <Check className="h-3 w-3" />
                                                )}
                                                Claim
                                            </button>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        )}

                        {/* Active Quests */}
                        <div className="rounded-xl border-2 border-stone-700 bg-stone-800/50 p-4">
                            <h2 className="mb-3 font-pixel text-sm text-stone-300">Active Quests</h2>
                            {active_quests.length > 0 ? (
                                <div className="space-y-3">
                                    {active_quests.map((pq) => (
                                        <div key={pq.id} className="rounded-lg bg-stone-900/50 p-3">
                                            <div className="mb-2 flex items-center gap-2">
                                                <QuestIcon icon={pq.icon} className="h-4 w-4 text-amber-400" />
                                                <span className="font-pixel text-xs text-amber-300">{pq.name}</span>
                                            </div>
                                            <p className="mb-2 font-pixel text-[10px] text-stone-400">{pq.objective}</p>
                                            <div className="mb-1 flex justify-between font-pixel text-[10px] text-stone-500">
                                                <span>Progress</span>
                                                <span>
                                                    {pq.current_progress}/{pq.target_amount}
                                                </span>
                                            </div>
                                            <div className="h-2 w-full overflow-hidden rounded-full bg-stone-700">
                                                <div
                                                    className="h-full bg-gradient-to-r from-amber-600 to-amber-400 transition-all"
                                                    style={{ width: `${pq.progress_percent}%` }}
                                                />
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            ) : (
                                <div className="py-6 text-center">
                                    <ScrollText className="mx-auto mb-2 h-8 w-8 text-stone-600" />
                                    <p className="font-pixel text-xs text-stone-500">No active quests</p>
                                </div>
                            )}
                        </div>
                    </div>

                    {/* Right Column - Available Quests */}
                    <div className="rounded-xl border-2 border-stone-700 bg-stone-800/50 p-4">
                        <h2 className="mb-3 font-pixel text-sm text-stone-300">Available Quests</h2>
                        {available_quests.length > 0 ? (
                            <div className="space-y-3">
                                {available_quests.map((quest) => {
                                    const colorClass = categoryColors[quest.category] || 'border-stone-600/50 bg-stone-800/50';
                                    const isLoading = loading === quest.id;

                                    return (
                                        <div key={quest.id} className={`rounded-lg border p-3 ${colorClass}`}>
                                            <div className="mb-2 flex items-center justify-between">
                                                <div className="flex items-center gap-2">
                                                    <QuestIcon icon={quest.icon} className="h-4 w-4 text-amber-400" />
                                                    <span className="font-pixel text-xs text-amber-300">{quest.name}</span>
                                                </div>
                                                <span className="font-pixel text-[10px] capitalize text-stone-500">
                                                    {quest.category_display}
                                                </span>
                                            </div>
                                            <p className="mb-2 font-pixel text-[10px] text-stone-400">{quest.description}</p>
                                            <div className="mb-3 flex items-center gap-1 text-stone-500">
                                                <ChevronRight className="h-3 w-3" />
                                                <span className="font-pixel text-[10px]">{quest.objective}</span>
                                            </div>
                                            <div className="flex items-center justify-between">
                                                <div className="flex items-center gap-3">
                                                    <span className="flex items-center gap-1 font-pixel text-[10px] text-yellow-400">
                                                        <Coins className="h-3 w-3" />
                                                        {quest.gold_reward}
                                                    </span>
                                                    {quest.xp_reward > 0 && (
                                                        <span className="font-pixel text-[10px] text-amber-400">
                                                            +{quest.xp_reward} XP
                                                        </span>
                                                    )}
                                                </div>
                                                <button
                                                    onClick={() => handleAccept(quest.id)}
                                                    disabled={!canAcceptMore || loading !== null}
                                                    className={`flex items-center gap-1 rounded px-2 py-1 font-pixel text-[10px] transition ${
                                                        canAcceptMore && !loading
                                                            ? 'bg-amber-600 text-stone-900 hover:bg-amber-500'
                                                            : 'cursor-not-allowed bg-stone-700 text-stone-500'
                                                    }`}
                                                >
                                                    {isLoading ? (
                                                        <Loader2 className="h-3 w-3 animate-spin" />
                                                    ) : (
                                                        <Check className="h-3 w-3" />
                                                    )}
                                                    Accept
                                                </button>
                                            </div>
                                        </div>
                                    );
                                })}
                            </div>
                        ) : (
                            <div className="py-8 text-center">
                                <ScrollText className="mx-auto mb-2 h-10 w-10 text-stone-600" />
                                <p className="font-pixel text-xs text-stone-500">No quests available</p>
                                <p className="font-pixel text-[10px] text-stone-600">Check back later</p>
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
