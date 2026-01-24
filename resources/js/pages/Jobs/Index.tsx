import { Head, router, usePage } from '@inertiajs/react';
import {
    Axe,
    Briefcase,
    Clock,
    Hammer,
    HardHat,
    Loader2,
    Pickaxe,
    Shield,
    Sparkles,
    Store,
    Swords,
    Utensils,
    Wheat,
    Zap,
} from 'lucide-react';
import { useState } from 'react';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

interface Job {
    id: number;
    name: string;
    icon: string;
    description: string;
    category: string;
    category_display: string;
    location_type: string;
    energy_cost: number;
    base_wage: number;
    xp_reward: number;
    xp_skill: string | null;
    cooldown_minutes: number;
    required_skill: string | null;
    required_skill_level: number;
    required_level: number;
    current_workers: number;
    max_workers: number;
}

interface Employment {
    id: number;
    job_id: number;
    name: string;
    icon: string;
    description: string;
    category: string;
    location_type: string;
    location_id: number;
    location_name: string;
    energy_cost: number;
    base_wage: number;
    xp_reward: number;
    xp_skill: string | null;
    cooldown_minutes: number;
    status: string;
    hired_at: string;
    last_worked_at: string | null;
    times_worked: number;
    total_earnings: number;
    can_work: boolean;
    minutes_until_work: number;
}

interface PageProps {
    location_type: string;
    location_id: number;
    location_name: string;
    available_jobs: Job[];
    current_employment: Employment[];
    all_employment: Employment[];
    max_jobs: number;
    player: {
        energy: number;
        max_energy: number;
        gold: number;
    };
    [key: string]: unknown;
}

const iconMap: Record<string, typeof Briefcase> = {
    utensils: Utensils,
    sparkles: Sparkles,
    horse: HardHat, // Using HardHat as substitute
    wheat: Wheat,
    pickaxe: Pickaxe,
    axe: Axe,
    shield: Shield,
    swords: Swords,
    store: Store,
    hammer: Hammer,
    briefcase: Briefcase,
};

const categoryColors: Record<string, string> = {
    service: 'border-purple-500/50 bg-purple-900/20',
    labor: 'border-amber-500/50 bg-amber-900/20',
    skilled: 'border-blue-500/50 bg-blue-900/20',
};

function JobCard({
    job,
    onApply,
    loading,
    canApply,
}: {
    job: Job;
    onApply: () => void;
    loading: boolean;
    canApply: boolean;
}) {
    const Icon = iconMap[job.icon] || Briefcase;

    return (
        <div className={`rounded-xl border-2 ${categoryColors[job.category] || 'border-stone-600/50 bg-stone-800/50'} p-4`}>
            <div className="mb-3 flex items-start justify-between">
                <div className="flex items-center gap-2">
                    <div className="rounded-lg bg-stone-800/50 p-2">
                        <Icon className="h-5 w-5 text-stone-300" />
                    </div>
                    <div>
                        <h3 className="font-pixel text-sm text-amber-300">{job.name}</h3>
                        <span className="font-pixel text-[10px] text-stone-400">{job.category_display}</span>
                    </div>
                </div>
                <div className="text-right">
                    <span className="font-pixel text-xs text-stone-400">
                        {job.current_workers}/{job.max_workers} workers
                    </span>
                </div>
            </div>

            <p className="mb-3 text-sm text-stone-300">{job.description}</p>

            <div className="mb-3 grid grid-cols-2 gap-2 rounded-lg bg-stone-800/50 p-2">
                <div className="flex items-center gap-1">
                    <span className="font-pixel text-[10px] text-stone-400">Wage:</span>
                    <span className="font-pixel text-xs text-amber-300">{job.base_wage}g</span>
                </div>
                <div className="flex items-center gap-1">
                    <Zap className="h-3 w-3 text-yellow-400" />
                    <span className="font-pixel text-xs text-stone-300">{job.energy_cost}</span>
                </div>
                {job.xp_reward > 0 && job.xp_skill && (
                    <div className="col-span-2 flex items-center gap-1">
                        <span className="font-pixel text-[10px] text-stone-400">XP:</span>
                        <span className="font-pixel text-xs text-emerald-300">
                            +{job.xp_reward} {job.xp_skill}
                        </span>
                    </div>
                )}
                <div className="col-span-2 flex items-center gap-1">
                    <Clock className="h-3 w-3 text-stone-400" />
                    <span className="font-pixel text-[10px] text-stone-400">{job.cooldown_minutes}min cooldown</span>
                </div>
            </div>

            {(job.required_skill || job.required_level > 1) && (
                <div className="mb-3 rounded-lg bg-stone-900/50 p-2">
                    <span className="font-pixel text-[10px] text-stone-500">Requirements:</span>
                    <div className="mt-1 flex flex-wrap gap-2">
                        {job.required_level > 1 && (
                            <span className="font-pixel text-[10px] text-stone-400">Combat Lv.{job.required_level}</span>
                        )}
                        {job.required_skill && (
                            <span className="font-pixel text-[10px] text-stone-400">
                                {job.required_skill} Lv.{job.required_skill_level}
                            </span>
                        )}
                    </div>
                </div>
            )}

            <button
                onClick={onApply}
                disabled={loading || !canApply}
                className="flex w-full items-center justify-center gap-2 rounded-lg border-2 border-green-600 bg-green-900/30 px-4 py-2 font-pixel text-xs text-green-300 transition hover:bg-green-800/50 disabled:cursor-not-allowed disabled:opacity-50"
            >
                {loading ? <Loader2 className="h-4 w-4 animate-spin" /> : 'Apply for Job'}
            </button>
        </div>
    );
}

function EmploymentCard({
    employment,
    onWork,
    onQuit,
    workLoading,
    quitLoading,
    playerEnergy,
}: {
    employment: Employment;
    onWork: () => void;
    onQuit: () => void;
    workLoading: boolean;
    quitLoading: boolean;
    playerEnergy: number;
}) {
    const Icon = iconMap[employment.icon] || Briefcase;
    const hasEnergy = playerEnergy >= employment.energy_cost;

    return (
        <div className="rounded-xl border-2 border-green-600/50 bg-green-900/20 p-4">
            <div className="mb-3 flex items-start justify-between">
                <div className="flex items-center gap-2">
                    <div className="rounded-lg bg-stone-800/50 p-2">
                        <Icon className="h-5 w-5 text-green-300" />
                    </div>
                    <div>
                        <h3 className="font-pixel text-sm text-green-300">{employment.name}</h3>
                        <span className="font-pixel text-[10px] text-stone-400">{employment.location_name}</span>
                    </div>
                </div>
                <div className="rounded-lg bg-green-800/50 px-2 py-1">
                    <span className="font-pixel text-[10px] text-green-300">Employed</span>
                </div>
            </div>

            <div className="mb-3 grid grid-cols-2 gap-2 rounded-lg bg-stone-800/50 p-2">
                <div>
                    <span className="font-pixel text-[10px] text-stone-400">Times Worked</span>
                    <p className="font-pixel text-sm text-stone-200">{employment.times_worked}</p>
                </div>
                <div>
                    <span className="font-pixel text-[10px] text-stone-400">Total Earned</span>
                    <p className="font-pixel text-sm text-amber-300">{employment.total_earnings}g</p>
                </div>
            </div>

            <div className="mb-3 flex items-center justify-between rounded-lg bg-stone-800/50 p-2">
                <div className="flex items-center gap-1">
                    <span className="font-pixel text-[10px] text-stone-400">Wage:</span>
                    <span className="font-pixel text-xs text-amber-300">{employment.base_wage}g</span>
                </div>
                <div className="flex items-center gap-1">
                    <Zap className="h-3 w-3 text-yellow-400" />
                    <span className="font-pixel text-xs text-stone-300">{employment.energy_cost}</span>
                </div>
            </div>

            {!employment.can_work && employment.minutes_until_work > 0 && (
                <div className="mb-3 flex items-center gap-2 rounded-lg bg-stone-900/50 p-2">
                    <Clock className="h-4 w-4 text-stone-400" />
                    <span className="font-pixel text-xs text-stone-400">
                        Can work again in {employment.minutes_until_work} min
                    </span>
                </div>
            )}

            <div className="flex gap-2">
                <button
                    onClick={onWork}
                    disabled={workLoading || !employment.can_work || !hasEnergy}
                    className="flex flex-1 items-center justify-center gap-2 rounded-lg border-2 border-amber-500 bg-amber-900/30 px-4 py-2 font-pixel text-xs text-amber-300 transition hover:bg-amber-800/50 disabled:cursor-not-allowed disabled:opacity-50"
                    title={!hasEnergy ? `Need ${employment.energy_cost} energy` : ''}
                >
                    {workLoading ? (
                        <Loader2 className="h-4 w-4 animate-spin" />
                    ) : (
                        <>
                            <Briefcase className="h-4 w-4" />
                            Work
                        </>
                    )}
                </button>
                <button
                    onClick={onQuit}
                    disabled={quitLoading}
                    className="flex items-center justify-center gap-2 rounded-lg border-2 border-red-600/50 bg-red-900/20 px-4 py-2 font-pixel text-xs text-red-300 transition hover:bg-red-800/30 disabled:cursor-not-allowed disabled:opacity-50"
                >
                    {quitLoading ? <Loader2 className="h-4 w-4 animate-spin" /> : 'Quit'}
                </button>
            </div>
        </div>
    );
}

export default function JobsIndex() {
    const { location_type, location_id, location_name, available_jobs, current_employment, all_employment, max_jobs, player } =
        usePage<PageProps>().props;

    const [applyLoading, setApplyLoading] = useState<number | null>(null);
    const [workLoading, setWorkLoading] = useState<number | null>(null);
    const [quitLoading, setQuitLoading] = useState<number | null>(null);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: location_name, href: `/${location_type}s/${location_id}` },
        { title: 'Jobs', href: '#' },
    ];

    const canApplyForMore = all_employment.length < max_jobs;

    const handleApply = (jobId: number) => {
        setApplyLoading(jobId);
        router.post(
            '/jobs/apply',
            {
                job_id: jobId,
                location_type: location_type,
                location_id: location_id,
            },
            {
                preserveScroll: true,
                onFinish: () => setApplyLoading(null),
            }
        );
    };

    const handleWork = (employmentId: number) => {
        setWorkLoading(employmentId);
        router.post(
            `/jobs/${employmentId}/work`,
            {},
            {
                preserveScroll: true,
                onFinish: () => setWorkLoading(null),
            }
        );
    };

    const handleQuit = (employmentId: number) => {
        setQuitLoading(employmentId);
        router.post(
            `/jobs/${employmentId}/quit`,
            {},
            {
                preserveScroll: true,
                onFinish: () => setQuitLoading(null),
            }
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Jobs - ${location_name}`} />
            <div className="flex h-full flex-1 flex-col overflow-hidden p-4">
                {/* Header */}
                <div className="mb-6 flex items-center justify-between">
                    <div>
                        <h1 className="font-pixel text-2xl text-amber-400">Jobs</h1>
                        <p className="font-pixel text-sm text-stone-400">Find work at {location_name}</p>
                    </div>
                    <div className="flex items-center gap-4">
                        <div className="flex items-center gap-2 rounded-lg border-2 border-stone-600/50 bg-stone-800/50 px-4 py-2">
                            <Zap className="h-4 w-4 text-yellow-400" />
                            <span className="font-pixel text-sm text-stone-300">
                                {player.energy}/{player.max_energy}
                            </span>
                        </div>
                        <div className="rounded-lg border-2 border-stone-600/50 bg-stone-800/50 px-4 py-2">
                            <span className="font-pixel text-xs text-stone-400">Jobs:</span>
                            <span className="ml-2 font-pixel text-sm text-amber-300">
                                {all_employment.length}/{max_jobs}
                            </span>
                        </div>
                    </div>
                </div>

                {/* Current Employment Section */}
                {current_employment.length > 0 && (
                    <div className="mb-6">
                        <h2 className="mb-3 font-pixel text-lg text-green-400">Your Jobs Here</h2>
                        <div className="grid gap-4 md:grid-cols-2">
                            {current_employment.map((employment) => (
                                <EmploymentCard
                                    key={employment.id}
                                    employment={employment}
                                    onWork={() => handleWork(employment.id)}
                                    onQuit={() => handleQuit(employment.id)}
                                    workLoading={workLoading === employment.id}
                                    quitLoading={quitLoading === employment.id}
                                    playerEnergy={player.energy}
                                />
                            ))}
                        </div>
                    </div>
                )}

                {/* Available Jobs Section */}
                <div>
                    <h2 className="mb-3 font-pixel text-lg text-amber-300">Available Positions</h2>
                    {!canApplyForMore && (
                        <div className="mb-4 rounded-lg border-2 border-amber-600/50 bg-amber-900/20 p-3">
                            <p className="font-pixel text-xs text-amber-300">
                                You have the maximum number of jobs ({max_jobs}). Quit one to apply for another.
                            </p>
                        </div>
                    )}
                    {available_jobs.length > 0 ? (
                        <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                            {available_jobs.map((job) => (
                                <JobCard
                                    key={job.id}
                                    job={job}
                                    onApply={() => handleApply(job.id)}
                                    loading={applyLoading === job.id}
                                    canApply={canApplyForMore}
                                />
                            ))}
                        </div>
                    ) : (
                        <div className="flex flex-1 items-center justify-center py-12">
                            <div className="text-center">
                                <div className="mb-3 text-6xl">
                                    <Briefcase className="mx-auto h-16 w-16 text-stone-600" />
                                </div>
                                <p className="font-pixel text-base text-stone-500">No positions available</p>
                                <p className="font-pixel text-xs text-stone-600">
                                    {current_employment.length > 0
                                        ? 'You already have all available jobs here.'
                                        : 'Check back later or try another location.'}
                                </p>
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
