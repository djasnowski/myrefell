import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';
import { Head, router, usePage } from '@inertiajs/react';
import {
    Axe,
    Bug,
    Check,
    Coins,
    Fish,
    Gem,
    Hammer,
    Loader2,
    PawPrint,
    ScrollText,
    Swords,
    Trash2,
    Utensils,
} from 'lucide-react';
import { useState } from 'react';

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
    active_quests: PlayerQuest[];
    completed_quests: PlayerQuest[];
    max_active_quests: number;
    [key: string]: unknown;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Quest Log', href: '/quests' },
];

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

function QuestIcon({ icon, className }: { icon: string; className?: string }) {
    const IconComponent = iconMap[icon] || ScrollText;
    return <IconComponent className={className} />;
}

export default function QuestLog() {
    const { active_quests, completed_quests, max_active_quests } = usePage<PageProps>().props;
    const [abandonLoading, setAbandonLoading] = useState<number | null>(null);
    const [claimLoading, setClaimLoading] = useState<number | null>(null);

    const handleAbandon = async (playerQuestId: number) => {
        setAbandonLoading(playerQuestId);
        try {
            await fetch(`/quests/${playerQuestId}/abandon`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
            });
            router.reload({ only: ['active_quests', 'completed_quests', 'sidebar'] });
        } finally {
            setAbandonLoading(null);
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
            router.reload({ only: ['active_quests', 'completed_quests', 'sidebar'] });
        } finally {
            setClaimLoading(null);
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Quest Log" />
            <div className="flex h-full flex-1 flex-col p-4">
                {/* Header */}
                <div className="mb-6 flex items-center gap-3">
                    <div className="rounded-lg bg-amber-900/30 p-3">
                        <ScrollText className="h-8 w-8 text-amber-400" />
                    </div>
                    <div>
                        <h1 className="font-pixel text-2xl text-amber-400">Quest Log</h1>
                        <p className="font-pixel text-xs text-stone-400">
                            {active_quests.length}/{max_active_quests} active quests
                        </p>
                    </div>
                </div>

                <div className="mx-auto w-full max-w-2xl space-y-4">
                    {/* Completed Quests Ready to Claim */}
                    {completed_quests.length > 0 && (
                        <div className="rounded-xl border-2 border-green-600/50 bg-green-900/20 p-4">
                            <h2 className="mb-4 font-pixel text-sm text-green-300">Ready to Claim</h2>
                            <div className="space-y-3">
                                {completed_quests.map((pq) => (
                                    <div key={pq.id} className="flex items-center justify-between rounded-lg bg-stone-900/50 p-3">
                                        <div className="flex items-center gap-3">
                                            <QuestIcon icon={pq.icon} className="h-5 w-5 text-green-400" />
                                            <div>
                                                <div className="font-pixel text-sm text-stone-300">{pq.name}</div>
                                                <div className="flex items-center gap-2">
                                                    <span className="flex items-center gap-1 font-pixel text-[10px] text-yellow-400">
                                                        <Coins className="h-3 w-3" />
                                                        {pq.gold_reward}
                                                    </span>
                                                    {pq.xp_reward > 0 && (
                                                        <span className="font-pixel text-[10px] text-amber-400">
                                                            +{pq.xp_reward} XP
                                                        </span>
                                                    )}
                                                </div>
                                            </div>
                                        </div>
                                        <button
                                            onClick={() => handleClaim(pq.id)}
                                            disabled={claimLoading !== null}
                                            className="flex items-center gap-1 rounded-lg bg-green-600 px-3 py-2 font-pixel text-xs text-stone-900 transition hover:bg-green-500"
                                        >
                                            {claimLoading === pq.id ? (
                                                <Loader2 className="h-4 w-4 animate-spin" />
                                            ) : (
                                                <Check className="h-4 w-4" />
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
                        <h2 className="mb-4 font-pixel text-sm text-stone-300">Active Quests</h2>
                        {active_quests.length > 0 ? (
                            <div className="space-y-4">
                                {active_quests.map((pq) => (
                                    <div key={pq.id} className="rounded-lg bg-stone-900/50 p-4">
                                        <div className="mb-2 flex items-center justify-between">
                                            <div className="flex items-center gap-2">
                                                <QuestIcon icon={pq.icon} className="h-5 w-5 text-amber-400" />
                                                <span className="font-pixel text-sm text-amber-300">{pq.name}</span>
                                            </div>
                                            <button
                                                onClick={() => handleAbandon(pq.id)}
                                                disabled={abandonLoading !== null}
                                                className="rounded p-1 text-stone-500 transition hover:bg-stone-700 hover:text-red-400"
                                                title="Abandon quest"
                                            >
                                                {abandonLoading === pq.id ? (
                                                    <Loader2 className="h-4 w-4 animate-spin" />
                                                ) : (
                                                    <Trash2 className="h-4 w-4" />
                                                )}
                                            </button>
                                        </div>
                                        <p className="mb-3 font-pixel text-xs text-stone-400">{pq.objective}</p>

                                        {/* Progress */}
                                        <div className="mb-1 flex justify-between font-pixel text-[10px] text-stone-500">
                                            <span>Progress</span>
                                            <span>
                                                {pq.current_progress}/{pq.target_amount}
                                            </span>
                                        </div>
                                        <div className="mb-3 h-2 w-full overflow-hidden rounded-full bg-stone-700">
                                            <div
                                                className="h-full bg-gradient-to-r from-amber-600 to-amber-400 transition-all"
                                                style={{ width: `${pq.progress_percent}%` }}
                                            />
                                        </div>

                                        {/* Rewards */}
                                        <div className="flex items-center gap-3">
                                            <span className="flex items-center gap-1 font-pixel text-[10px] text-yellow-400">
                                                <Coins className="h-3 w-3" />
                                                {pq.gold_reward}
                                            </span>
                                            {pq.xp_reward > 0 && (
                                                <span className="font-pixel text-[10px] text-amber-400">
                                                    +{pq.xp_reward} XP
                                                </span>
                                            )}
                                        </div>
                                    </div>
                                ))}
                            </div>
                        ) : (
                            <div className="py-8 text-center">
                                <ScrollText className="mx-auto mb-3 h-12 w-12 text-stone-600" />
                                <p className="font-pixel text-sm text-stone-500">No active quests</p>
                                <p className="font-pixel text-xs text-stone-600">Visit a village notice board to find work</p>
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
