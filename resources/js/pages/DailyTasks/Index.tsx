import { Head, router, usePage } from '@inertiajs/react';
import { CheckCircle, Circle, Clock, Gift, Loader2, Sparkles, Swords, Trees, Utensils, Wrench } from 'lucide-react';
import { useState } from 'react';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

interface TaskRewards {
    gold: number;
    xp: number;
    xp_skill: string | null;
}

interface DailyTask {
    id: number;
    name: string;
    description: string;
    category: string;
    task_type: string;
    current_progress: number;
    target_amount: number;
    progress_percent: number;
    status: 'active' | 'completed' | 'claimed' | 'expired';
    rewards: TaskRewards;
    energy_cost: number;
}

interface TaskStats {
    completed_today: number;
    total_today: number;
    total_completed: number;
}

interface PageProps {
    tasks: DailyTask[];
    stats: TaskStats;
    [key: string]: unknown;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Daily Tasks', href: '/daily-tasks' },
];

const categoryIcons: Record<string, typeof Swords> = {
    combat: Swords,
    gathering: Trees,
    crafting: Wrench,
    service: Utensils,
};

const categoryColors: Record<string, string> = {
    combat: 'border-red-500/50 bg-red-900/20',
    gathering: 'border-green-500/50 bg-green-900/20',
    crafting: 'border-blue-500/50 bg-blue-900/20',
    service: 'border-purple-500/50 bg-purple-900/20',
};

const statusConfig = {
    active: { icon: Circle, color: 'text-stone-400', label: 'In Progress' },
    completed: { icon: Gift, color: 'text-amber-400', label: 'Ready to Claim' },
    claimed: { icon: CheckCircle, color: 'text-green-400', label: 'Claimed' },
    expired: { icon: Clock, color: 'text-stone-500', label: 'Expired' },
};

function TaskCard({ task, onClaim, onProgress }: { task: DailyTask; onClaim: () => void; onProgress: () => void }) {
    const [claiming, setClaiming] = useState(false);
    const [progressing, setProgressing] = useState(false);

    const CategoryIcon = categoryIcons[task.category] || Circle;
    const StatusIcon = statusConfig[task.status].icon;
    const statusColor = statusConfig[task.status].color;

    const handleClaim = async () => {
        setClaiming(true);
        onClaim();
    };

    const handleProgress = async () => {
        setProgressing(true);
        onProgress();
        setTimeout(() => setProgressing(false), 500);
    };

    const isClaimable = task.status === 'completed';
    const isActive = task.status === 'active';

    return (
        <div
            className={`rounded-xl border-2 ${categoryColors[task.category] || 'border-stone-600/50 bg-stone-800/50'} p-4 transition-all ${
                isClaimable ? 'ring-2 ring-amber-400/50' : ''
            }`}
        >
            {/* Header */}
            <div className="mb-3 flex items-start justify-between">
                <div className="flex items-center gap-2">
                    <div className="rounded-lg bg-stone-800/50 p-2">
                        <CategoryIcon className="h-5 w-5 text-stone-300" />
                    </div>
                    <div>
                        <h3 className="font-pixel text-sm text-amber-300">{task.name}</h3>
                        <span className="font-pixel text-[10px] capitalize text-stone-400">{task.category}</span>
                    </div>
                </div>
                <div className={`flex items-center gap-1 ${statusColor}`}>
                    <StatusIcon className="h-4 w-4" />
                    <span className="font-pixel text-[10px]">{statusConfig[task.status].label}</span>
                </div>
            </div>

            {/* Description */}
            <p className="mb-3 text-sm text-stone-300">{task.description}</p>

            {/* Progress Bar */}
            <div className="mb-3">
                <div className="mb-1 flex items-center justify-between">
                    <span className="font-pixel text-[10px] text-stone-400">Progress</span>
                    <span className="font-pixel text-xs text-stone-300">
                        {task.current_progress}/{task.target_amount}
                    </span>
                </div>
                <div className="h-3 w-full overflow-hidden rounded-full bg-stone-700">
                    <div
                        className={`h-full transition-all duration-500 ${
                            task.status === 'claimed'
                                ? 'bg-green-500'
                                : task.status === 'completed'
                                  ? 'bg-amber-500'
                                  : 'bg-gradient-to-r from-blue-600 to-blue-400'
                        }`}
                        style={{ width: `${task.progress_percent}%` }}
                    />
                </div>
            </div>

            {/* Rewards */}
            <div className="mb-3 flex items-center gap-3 rounded-lg bg-stone-800/50 px-3 py-2">
                <span className="font-pixel text-[10px] text-stone-400">Rewards:</span>
                {task.rewards.gold > 0 && (
                    <span className="flex items-center gap-1 font-pixel text-xs text-amber-300">
                        🪙 {task.rewards.gold}
                    </span>
                )}
                {task.rewards.xp > 0 && task.rewards.xp_skill && (
                    <span className="flex items-center gap-1 font-pixel text-xs text-emerald-300">
                        ✨ {task.rewards.xp} {task.rewards.xp_skill} XP
                    </span>
                )}
            </div>

            {/* Actions */}
            <div className="flex gap-2">
                {isClaimable && (
                    <button
                        onClick={handleClaim}
                        disabled={claiming}
                        className="flex flex-1 items-center justify-center gap-2 rounded-lg border-2 border-amber-500 bg-amber-900/30 px-4 py-2 font-pixel text-xs text-amber-300 transition hover:bg-amber-800/50 disabled:opacity-50"
                    >
                        {claiming ? (
                            <Loader2 className="h-4 w-4 animate-spin" />
                        ) : (
                            <>
                                <Gift className="h-4 w-4" />
                                Claim Reward
                            </>
                        )}
                    </button>
                )}
                {isActive && (
                    <button
                        onClick={handleProgress}
                        disabled={progressing}
                        className="flex flex-1 items-center justify-center gap-2 rounded-lg border-2 border-stone-600 bg-stone-800/50 px-4 py-2 font-pixel text-xs text-stone-300 transition hover:bg-stone-700/50 disabled:opacity-50"
                    >
                        {progressing ? (
                            <Loader2 className="h-4 w-4 animate-spin" />
                        ) : (
                            <>
                                <Sparkles className="h-4 w-4" />
                                Do Task
                            </>
                        )}
                    </button>
                )}
                {task.status === 'claimed' && (
                    <div className="flex flex-1 items-center justify-center gap-2 rounded-lg border-2 border-green-600/50 bg-green-900/20 px-4 py-2 font-pixel text-xs text-green-400">
                        <CheckCircle className="h-4 w-4" />
                        Completed!
                    </div>
                )}
            </div>
        </div>
    );
}

export default function DailyTasksIndex() {
    const { tasks, stats } = usePage<PageProps>().props;

    const handleClaim = (taskId: number) => {
        router.post(`/daily-tasks/${taskId}/claim`, {}, {
            preserveScroll: true,
            onSuccess: () => {
                router.reload();
            },
        });
    };

    const handleProgress = (taskId: number) => {
        router.post(`/daily-tasks/${taskId}/progress`, { amount: 1 }, {
            preserveScroll: true,
            onSuccess: () => {
                router.reload();
            },
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Daily Tasks" />
            <div className="flex h-full flex-1 flex-col overflow-hidden p-4">
                {/* Header */}
                <div className="mb-6 flex items-center justify-between">
                    <div>
                        <h1 className="font-pixel text-2xl text-amber-400">Daily Tasks</h1>
                        <p className="font-pixel text-sm text-stone-400">Complete tasks to earn gold and XP</p>
                    </div>
                    <div className="flex items-center gap-4">
                        <div className="rounded-lg border-2 border-stone-600/50 bg-stone-800/50 px-4 py-2">
                            <span className="font-pixel text-xs text-stone-400">Today:</span>
                            <span className="ml-2 font-pixel text-sm text-amber-300">
                                {stats.completed_today}/{stats.total_today}
                            </span>
                        </div>
                        <div className="rounded-lg border-2 border-stone-600/50 bg-stone-800/50 px-4 py-2">
                            <span className="font-pixel text-xs text-stone-400">Total:</span>
                            <span className="ml-2 font-pixel text-sm text-emerald-300">{stats.total_completed}</span>
                        </div>
                    </div>
                </div>

                {/* Tasks Grid */}
                {tasks.length > 0 ? (
                    <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                        {tasks.map((task) => (
                            <TaskCard
                                key={task.id}
                                task={task}
                                onClaim={() => handleClaim(task.id)}
                                onProgress={() => handleProgress(task.id)}
                            />
                        ))}
                    </div>
                ) : (
                    <div className="flex flex-1 items-center justify-center">
                        <div className="text-center">
                            <div className="mb-3 text-6xl">📋</div>
                            <p className="font-pixel text-base text-stone-500">No tasks available</p>
                            <p className="font-pixel text-xs text-stone-600">Check back tomorrow!</p>
                        </div>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
