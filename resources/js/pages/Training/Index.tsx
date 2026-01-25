import { Head, router, usePage } from '@inertiajs/react';
import { ArrowUp, Dumbbell, Loader2, Shield, Sword, Zap } from 'lucide-react';
import { useState } from 'react';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

interface Exercise {
    id: string;
    name: string;
    description: string;
    skill: string;
    skill_level: number;
    skill_xp: number;
    skill_progress: number;
    xp_to_next_level: number;
    energy_cost: number;
    base_xp: number;
    can_train: boolean;
}

interface CombatStats {
    attack: {
        level: number;
        xp: number;
        progress: number;
        xp_to_next_level: number;
    };
    strength: {
        level: number;
        xp: number;
        progress: number;
        xp_to_next_level: number;
    };
    defense: {
        level: number;
        xp: number;
        progress: number;
        xp_to_next_level: number;
    };
    combat_level: number;
}

interface TrainResult {
    success: boolean;
    message: string;
    exercise?: string;
    xp_awarded?: number;
    skill?: string;
    new_level?: number;
    leveled_up?: boolean;
    energy_remaining?: number;
    skill_progress?: number;
    xp_to_next_level?: number;
}

interface PageProps {
    exercises: Exercise[];
    combat_stats: CombatStats;
    player_energy: number;
    max_energy: number;
    [key: string]: unknown;
}

const exerciseIcons: Record<string, typeof Sword> = {
    attack: Sword,
    strength: Dumbbell,
    defense: Shield,
};

const exerciseColors: Record<string, { bg: string; border: string; text: string }> = {
    attack: {
        bg: 'from-red-900/50 to-stone-900',
        border: 'border-red-600/50',
        text: 'text-red-400',
    },
    strength: {
        bg: 'from-orange-900/50 to-stone-900',
        border: 'border-orange-600/50',
        text: 'text-orange-400',
    },
    defense: {
        bg: 'from-blue-900/50 to-stone-900',
        border: 'border-blue-600/50',
        text: 'text-blue-400',
    },
};

export default function TrainingIndex() {
    const { exercises, combat_stats, player_energy, max_energy } = usePage<PageProps>().props;
    const [loading, setLoading] = useState<string | null>(null);
    const [result, setResult] = useState<TrainResult | null>(null);
    const [currentEnergy, setCurrentEnergy] = useState(player_energy);
    const [currentStats, setCurrentStats] = useState(combat_stats);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Training Grounds', href: '/training' },
    ];

    const handleTrain = async (exercise: string) => {
        if (loading) return;

        const exerciseData = exercises.find((e) => e.id === exercise);
        if (!exerciseData || currentEnergy < exerciseData.energy_cost) return;

        setLoading(exercise);
        setResult(null);

        try {
            const response = await fetch('/training/train', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
                body: JSON.stringify({ exercise }),
            });

            const data: TrainResult = await response.json();
            setResult(data);

            if (data.success) {
                if (data.energy_remaining !== undefined) {
                    setCurrentEnergy(data.energy_remaining);
                }

                // Update the stat that was trained
                if (data.skill && data.skill_progress !== undefined && data.xp_to_next_level !== undefined) {
                    const skillKey = data.skill as 'attack' | 'strength' | 'defense';
                    setCurrentStats((prev) => {
                        const updatedStat = {
                            ...prev[skillKey],
                            level: data.new_level || prev[skillKey].level,
                            progress: data.skill_progress!,
                            xp_to_next_level: data.xp_to_next_level!,
                        };

                        const newStats = {
                            ...prev,
                            [skillKey]: updatedStat,
                        };

                        newStats.combat_level = Math.floor(
                            ((skillKey === 'attack' ? updatedStat.level : prev.attack.level) +
                                (skillKey === 'strength' ? updatedStat.level : prev.strength.level) +
                                (skillKey === 'defense' ? updatedStat.level : prev.defense.level)) /
                                3
                        );

                        return newStats;
                    });
                }
            }

            // Reload sidebar data
            router.reload({ only: ['sidebar'] });
        } catch {
            setResult({ success: false, message: 'An error occurred' });
        } finally {
            setLoading(null);
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Training Grounds" />
            <div className="flex h-full flex-1 flex-col p-4">
                <div className="mx-auto w-full max-w-3xl">
                    {/* Header */}
                    <div className="mb-6 rounded-xl border-2 border-amber-600/50 bg-gradient-to-br from-amber-900/30 to-stone-900 p-6">
                        <div className="flex items-center gap-4">
                            <div className="rounded-lg bg-stone-800/50 p-4">
                                <Sword className="h-12 w-12 text-amber-400" />
                            </div>
                            <div>
                                <h1 className="font-pixel text-2xl text-amber-400">Training Grounds</h1>
                                <p className="font-pixel text-xs text-stone-400">
                                    Train your combat skills to become stronger
                                </p>
                            </div>
                        </div>
                    </div>

                    {/* Combat Stats Overview */}
                    <div className="mb-6 grid grid-cols-4 gap-3">
                        <div className="rounded-lg border border-stone-600 bg-stone-800/50 p-3 text-center">
                            <div className="font-pixel text-xs text-stone-400">Combat Level</div>
                            <div className="font-pixel text-2xl text-amber-400">{currentStats.combat_level}</div>
                        </div>
                        <div className="rounded-lg border border-red-600/30 bg-stone-800/50 p-3 text-center">
                            <div className="font-pixel text-xs text-red-400">Attack</div>
                            <div className="font-pixel text-xl text-red-300">{currentStats.attack.level}</div>
                        </div>
                        <div className="rounded-lg border border-orange-600/30 bg-stone-800/50 p-3 text-center">
                            <div className="font-pixel text-xs text-orange-400">Strength</div>
                            <div className="font-pixel text-xl text-orange-300">{currentStats.strength.level}</div>
                        </div>
                        <div className="rounded-lg border border-blue-600/30 bg-stone-800/50 p-3 text-center">
                            <div className="font-pixel text-xs text-blue-400">Defense</div>
                            <div className="font-pixel text-xl text-blue-300">{currentStats.defense.level}</div>
                        </div>
                    </div>

                    {/* Energy Bar */}
                    <div className="mb-6 rounded-lg border border-stone-700 bg-stone-800/50 p-3">
                        <div className="mb-1 flex items-center justify-between">
                            <div className="flex items-center gap-1 font-pixel text-xs text-yellow-400">
                                <Zap className="h-3 w-3" />
                                Energy
                            </div>
                            <div className="font-pixel text-xs text-stone-400">
                                {currentEnergy} / {max_energy}
                            </div>
                        </div>
                        <div className="h-2 w-full overflow-hidden rounded-full bg-stone-700">
                            <div
                                className="h-full bg-gradient-to-r from-yellow-600 to-yellow-400 transition-all"
                                style={{ width: `${(currentEnergy / max_energy) * 100}%` }}
                            />
                        </div>
                    </div>

                    {/* Result Display */}
                    {result && (
                        <div
                            className={`mb-6 rounded-lg border p-4 ${
                                result.success ? 'border-green-600/50 bg-green-900/20' : 'border-red-600/50 bg-red-900/20'
                            }`}
                        >
                            <div className="flex items-center gap-3">
                                {result.success ? (
                                    <>
                                        <div
                                            className={`rounded-lg p-2 ${
                                                exerciseColors[result.exercise || 'attack'].bg
                                            }`}
                                        >
                                            {(() => {
                                                const Icon = exerciseIcons[result.exercise || 'attack'];
                                                return (
                                                    <Icon
                                                        className={`h-6 w-6 ${
                                                            exerciseColors[result.exercise || 'attack'].text
                                                        }`}
                                                    />
                                                );
                                            })()}
                                        </div>
                                        <div>
                                            <div className="font-pixel text-sm text-green-300">{result.message}</div>
                                            <div className="flex items-center gap-2">
                                                <span className="font-pixel text-[10px] text-amber-400">
                                                    +{result.xp_awarded} XP
                                                </span>
                                                {result.leveled_up && (
                                                    <span className="flex items-center gap-1 font-pixel text-[10px] text-yellow-300">
                                                        <ArrowUp className="h-3 w-3" />
                                                        Level {result.new_level}!
                                                    </span>
                                                )}
                                            </div>
                                        </div>
                                    </>
                                ) : (
                                    <div className="font-pixel text-sm text-red-400">{result.message}</div>
                                )}
                            </div>
                        </div>
                    )}

                    {/* Training Exercises */}
                    <div className="space-y-4">
                        {exercises.map((exercise) => {
                            const Icon = exerciseIcons[exercise.id];
                            const colors = exerciseColors[exercise.id];
                            const stat = currentStats[exercise.skill as keyof CombatStats] as {
                                level: number;
                                progress: number;
                                xp_to_next_level: number;
                            };
                            const canTrain = currentEnergy >= exercise.energy_cost;

                            return (
                                <div
                                    key={exercise.id}
                                    className={`rounded-xl border-2 bg-gradient-to-br ${colors.bg} ${colors.border} p-4`}
                                >
                                    <div className="flex items-start gap-4">
                                        <div className="rounded-lg bg-stone-800/50 p-3">
                                            <Icon className={`h-8 w-8 ${colors.text}`} />
                                        </div>
                                        <div className="flex-1">
                                            <div className="flex items-center justify-between">
                                                <div>
                                                    <h3 className={`font-pixel text-lg ${colors.text}`}>
                                                        {exercise.name}
                                                    </h3>
                                                    <p className="font-pixel text-[10px] text-stone-400">
                                                        {exercise.description}
                                                    </p>
                                                </div>
                                                <div className="text-right">
                                                    <div className="font-pixel text-sm text-stone-300">
                                                        Level {stat.level}
                                                    </div>
                                                    <div className="font-pixel text-[10px] text-stone-500">
                                                        {stat.xp_to_next_level.toLocaleString()} XP to next
                                                    </div>
                                                </div>
                                            </div>

                                            {/* Progress bar */}
                                            <div className="mt-2 h-1.5 w-full overflow-hidden rounded-full bg-stone-700">
                                                <div
                                                    className={`h-full transition-all ${
                                                        exercise.id === 'attack'
                                                            ? 'bg-red-500'
                                                            : exercise.id === 'strength'
                                                              ? 'bg-orange-500'
                                                              : 'bg-blue-500'
                                                    }`}
                                                    style={{ width: `${stat.progress}%` }}
                                                />
                                            </div>

                                            <div className="mt-3 flex items-center justify-between">
                                                <div className="flex items-center gap-3">
                                                    <span className="font-pixel text-[10px] text-stone-400">
                                                        <Zap className="mr-1 inline h-3 w-3 text-yellow-400" />
                                                        {exercise.energy_cost} energy
                                                    </span>
                                                    <span className="font-pixel text-[10px] text-amber-400">
                                                        +{exercise.base_xp} XP
                                                    </span>
                                                </div>
                                                <button
                                                    onClick={() => handleTrain(exercise.id)}
                                                    disabled={!canTrain || loading !== null}
                                                    className={`rounded-lg px-4 py-2 font-pixel text-xs transition ${
                                                        canTrain && loading === null
                                                            ? `${colors.border} bg-stone-800/80 hover:bg-stone-700/80 ${colors.text}`
                                                            : 'cursor-not-allowed border-stone-700 bg-stone-800/50 text-stone-500'
                                                    } border`}
                                                >
                                                    {loading === exercise.id ? (
                                                        <span className="flex items-center gap-1">
                                                            <Loader2 className="h-3 w-3 animate-spin" />
                                                            Training...
                                                        </span>
                                                    ) : (
                                                        'Train'
                                                    )}
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            );
                        })}
                    </div>

                    {/* Info Box */}
                    <div className="mt-6 rounded-lg border border-stone-600 bg-stone-800/30 p-4">
                        <h3 className="mb-2 font-pixel text-sm text-stone-300">About Training</h3>
                        <ul className="space-y-1 font-pixel text-[10px] text-stone-400">
                            <li>- Each training session costs 10 energy</li>
                            <li>- Combat skills (Attack, Strength, Defense) start at level 5</li>
                            <li>- Higher levels provide diminishing XP returns</li>
                            <li>- Combat Level is the average of your three combat stats</li>
                        </ul>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
