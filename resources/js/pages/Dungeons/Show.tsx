import { Head, router, usePage } from '@inertiajs/react';
import {
    Castle,
    Heart,
    Shield,
    Skull,
    Swords,
    Zap,
} from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

interface Monster {
    id: number;
    name: string;
    combat_level: number;
    is_boss: boolean;
}

interface DungeonFloor {
    id: number;
    floor_number: number;
    name: string | null;
    monster_count: number;
    is_boss_floor: boolean;
}

interface Dungeon {
    id: number;
    name: string;
    description: string | null;
    theme: string;
    difficulty: 'easy' | 'normal' | 'hard' | 'nightmare';
    min_combat_level: number;
    recommended_level: number;
    floor_count: number;
    xp_reward_base: number;
    gold_reward_min: number;
    gold_reward_max: number;
    energy_cost: number;
    boss_monster: Monster | null;
    floors: DungeonFloor[];
}

interface PlayerStats {
    combat_level: number;
    hp: number;
    max_hp: number;
}

interface EnergyInfo {
    current: number;
    cost: number;
}

interface PageProps {
    dungeon: Dungeon;
    can_enter: boolean;
    player_stats: PlayerStats;
    energy: EnergyInfo;
    [key: string]: unknown;
}

const difficultyColors: Record<string, string> = {
    easy: 'border-green-500/50 bg-green-900/20 text-green-400',
    normal: 'border-yellow-500/50 bg-yellow-900/20 text-yellow-400',
    hard: 'border-red-500/50 bg-red-900/20 text-red-400',
    nightmare: 'border-purple-500/50 bg-purple-900/20 text-purple-400',
};

export default function DungeonShow() {
    const { dungeon, can_enter, player_stats, energy } = usePage<PageProps>().props;
    const [trainingStyle, setTrainingStyle] = useState<'attack' | 'strength' | 'defense'>('attack');
    const [isEntering, setIsEntering] = useState(false);
    const [error, setError] = useState<string | null>(null);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Dungeons', href: '/dungeons' },
        { title: dungeon.name, href: '#' },
    ];

    const handleEnter = () => {
        setIsEntering(true);
        setError(null);
        router.post(
            '/dungeons/enter',
            {
                dungeon_id: dungeon.id,
                training_style: trainingStyle,
            },
            {
                onSuccess: () => {
                    router.visit('/dungeons/explore');
                },
                onError: (errors) => {
                    setError(errors.error || 'Failed to enter dungeon');
                    setIsEntering(false);
                },
                onFinish: () => setIsEntering(false),
            }
        );
    };

    const meetsLevel = player_stats.combat_level >= dungeon.min_combat_level;
    const hasEnergy = energy.current >= energy.cost;
    const hasHealth = player_stats.hp > 0;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={dungeon.name} />

            <div className="space-y-6 p-6">
                {/* Header */}
                <div className="flex items-start justify-between">
                    <div className="flex items-center gap-3">
                        <div className="rounded-lg bg-purple-900/30 p-2">
                            <Castle className="size-6 text-purple-400" />
                        </div>
                        <div>
                            <h1 className="font-[Cinzel] text-2xl font-bold text-stone-100">
                                {dungeon.name}
                            </h1>
                            <div className="mt-1 flex items-center gap-2">
                                <span
                                    className={`rounded border px-2 py-0.5 text-xs capitalize ${difficultyColors[dungeon.difficulty]}`}
                                >
                                    {dungeon.difficulty}
                                </span>
                                <span className="text-sm text-stone-400">
                                    {dungeon.floor_count} floors
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                {dungeon.description && (
                    <p className="text-stone-400">{dungeon.description}</p>
                )}

                <div className="grid gap-6 lg:grid-cols-3">
                    {/* Dungeon Info */}
                    <div className="space-y-4 lg:col-span-2">
                        {/* Requirements */}
                        <div className="rounded-xl border border-stone-800 bg-stone-900/50 p-4">
                            <h3 className="font-[Cinzel] font-semibold text-stone-100">
                                Requirements
                            </h3>
                            <div className="mt-3 grid grid-cols-2 gap-3">
                                <div
                                    className={`rounded p-3 ${meetsLevel ? 'bg-green-900/20' : 'bg-red-900/20'}`}
                                >
                                    <p className="text-xs text-stone-500">
                                        Minimum Combat Level
                                    </p>
                                    <p
                                        className={`text-lg font-semibold ${meetsLevel ? 'text-green-400' : 'text-red-400'}`}
                                    >
                                        {dungeon.min_combat_level}
                                    </p>
                                    <p className="text-xs text-stone-500">
                                        Your level: {player_stats.combat_level}
                                    </p>
                                </div>
                                <div
                                    className={`rounded p-3 ${hasEnergy ? 'bg-green-900/20' : 'bg-red-900/20'}`}
                                >
                                    <p className="text-xs text-stone-500">Energy Cost</p>
                                    <p
                                        className={`text-lg font-semibold ${hasEnergy ? 'text-green-400' : 'text-red-400'}`}
                                    >
                                        {energy.cost}
                                    </p>
                                    <p className="text-xs text-stone-500">
                                        Your energy: {energy.current}
                                    </p>
                                </div>
                            </div>
                        </div>

                        {/* Rewards */}
                        <div className="rounded-xl border border-stone-800 bg-stone-900/50 p-4">
                            <h3 className="font-[Cinzel] font-semibold text-stone-100">
                                Rewards
                            </h3>
                            <div className="mt-3 grid grid-cols-2 gap-3">
                                <div className="rounded bg-stone-900/50 p-3">
                                    <p className="text-xs text-stone-500">Base XP</p>
                                    <p className="text-lg font-semibold text-blue-400">
                                        {dungeon.xp_reward_base}
                                    </p>
                                </div>
                                <div className="rounded bg-stone-900/50 p-3">
                                    <p className="text-xs text-stone-500">Gold</p>
                                    <p className="text-lg font-semibold text-amber-400">
                                        {dungeon.gold_reward_min} - {dungeon.gold_reward_max}
                                    </p>
                                </div>
                            </div>
                        </div>

                        {/* Floors */}
                        {dungeon.floors && dungeon.floors.length > 0 && (
                            <div className="rounded-xl border border-stone-800 bg-stone-900/50 p-4">
                                <h3 className="font-[Cinzel] font-semibold text-stone-100">
                                    Floors
                                </h3>
                                <div className="mt-3 space-y-2">
                                    {dungeon.floors.map((floor) => (
                                        <div
                                            key={floor.id}
                                            className={`flex items-center justify-between rounded p-2 ${
                                                floor.is_boss_floor
                                                    ? 'bg-red-900/20'
                                                    : 'bg-stone-900/50'
                                            }`}
                                        >
                                            <span className="text-stone-300">
                                                Floor {floor.floor_number}
                                                {floor.name && `: ${floor.name}`}
                                            </span>
                                            <div className="flex items-center gap-2">
                                                <span className="text-xs text-stone-500">
                                                    {floor.monster_count} monsters
                                                </span>
                                                {floor.is_boss_floor && (
                                                    <Skull className="size-4 text-red-400" />
                                                )}
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        )}
                    </div>

                    {/* Enter Panel */}
                    <div className="space-y-4">
                        {/* Player Stats */}
                        <div className="rounded-xl border border-stone-800 bg-stone-900/50 p-4">
                            <h3 className="font-[Cinzel] font-semibold text-stone-100">
                                Your Stats
                            </h3>
                            <div className="mt-3 space-y-2">
                                <div className="flex items-center justify-between">
                                    <span className="flex items-center gap-2 text-stone-400">
                                        <Heart className="size-4 text-red-400" />
                                        HP
                                    </span>
                                    <span className="text-stone-100">
                                        {player_stats.hp}/{player_stats.max_hp}
                                    </span>
                                </div>
                                <div className="flex items-center justify-between">
                                    <span className="flex items-center gap-2 text-stone-400">
                                        <Zap className="size-4 text-blue-400" />
                                        Energy
                                    </span>
                                    <span className="text-stone-100">{energy.current}</span>
                                </div>
                                <div className="flex items-center justify-between">
                                    <span className="flex items-center gap-2 text-stone-400">
                                        <Shield className="size-4 text-purple-400" />
                                        Combat Level
                                    </span>
                                    <span className="text-stone-100">
                                        {player_stats.combat_level}
                                    </span>
                                </div>
                            </div>
                        </div>

                        {/* Training Style */}
                        <div className="rounded-xl border border-stone-800 bg-stone-900/50 p-4">
                            <h3 className="font-[Cinzel] font-semibold text-stone-100">
                                Training Style
                            </h3>
                            <p className="mt-1 text-xs text-stone-500">
                                Choose which skill gains XP
                            </p>
                            <div className="mt-3 grid grid-cols-3 gap-2">
                                {(['attack', 'strength', 'defense'] as const).map((style) => (
                                    <button
                                        key={style}
                                        onClick={() => setTrainingStyle(style)}
                                        className={`rounded p-2 text-center text-sm capitalize transition ${
                                            trainingStyle === style
                                                ? 'bg-amber-600 text-white'
                                                : 'bg-stone-800 text-stone-400 hover:bg-stone-700'
                                        }`}
                                    >
                                        {style}
                                    </button>
                                ))}
                            </div>
                        </div>

                        {/* Enter Button */}
                        <div className="rounded-xl border border-stone-800 bg-stone-900/50 p-4">
                            {error && (
                                <p className="mb-3 text-sm text-red-400">{error}</p>
                            )}
                            <Button
                                onClick={handleEnter}
                                disabled={!can_enter || isEntering}
                                className="w-full"
                                size="lg"
                            >
                                <Swords className="size-5" />
                                {isEntering ? 'Entering...' : 'Enter Dungeon'}
                            </Button>
                            {!can_enter && (
                                <p className="mt-2 text-center text-xs text-stone-500">
                                    {!meetsLevel && 'Combat level too low. '}
                                    {!hasEnergy && 'Not enough energy. '}
                                    {!hasHealth && 'HP too low. '}
                                </p>
                            )}
                        </div>

                        {/* Boss Info */}
                        {dungeon.boss_monster && (
                            <div className="rounded-xl border border-red-900/50 bg-red-900/20 p-4">
                                <h3 className="flex items-center gap-2 font-[Cinzel] font-semibold text-red-400">
                                    <Skull className="size-5" />
                                    Boss
                                </h3>
                                <p className="mt-2 text-stone-100">
                                    {dungeon.boss_monster.name}
                                </p>
                                <p className="text-sm text-stone-400">
                                    Level {dungeon.boss_monster.combat_level}
                                </p>
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
