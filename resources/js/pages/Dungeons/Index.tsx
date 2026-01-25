import { Head, router, usePage } from '@inertiajs/react';
import { Castle, Heart, Shield, Skull, Sword, Zap } from 'lucide-react';
import { useState } from 'react';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

interface DungeonFloor {
    id: number;
    floor_number: number;
    name: string | null;
    monster_count: number;
    is_boss_floor: boolean;
}

interface Monster {
    id: number;
    name: string;
    combat_level: number;
    is_boss: boolean;
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
    floors?: DungeonFloor[];
}

interface PlayerStats {
    hp: number;
    max_hp: number;
    combat_level: number;
    attack: number;
    strength: number;
    defense: number;
}

interface Equipment {
    atk_bonus: number;
    str_bonus: number;
    def_bonus: number;
    hp_bonus: number;
}

interface EnergyInfo {
    current: number;
}

interface PageProps {
    dungeons: Dungeon[];
    player_stats: PlayerStats;
    equipment: Equipment;
    energy: EnergyInfo;
    [key: string]: unknown;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Dungeons', href: '/dungeons' },
];

const difficultyColors: Record<string, string> = {
    easy: 'border-green-500/50 bg-green-900/20 text-green-400',
    normal: 'border-yellow-500/50 bg-yellow-900/20 text-yellow-400',
    hard: 'border-red-500/50 bg-red-900/20 text-red-400',
    nightmare: 'border-purple-500/50 bg-purple-900/20 text-purple-400',
};

const themeIcons: Record<string, string> = {
    goblin_fortress: 'Goblin',
    undead_crypt: 'Undead',
    dragon_lair: 'Dragon',
    bandit_hideout: 'Bandit',
    ancient_ruins: 'Ruins',
    elemental_cavern: 'Elemental',
    demon_pit: 'Demon',
};

export default function DungeonsIndex() {
    const { dungeons, player_stats, equipment, energy } = usePage<PageProps>().props;
    const [selectedDungeon, setSelectedDungeon] = useState<Dungeon | null>(null);
    const [trainingStyle, setTrainingStyle] = useState<'attack' | 'strength' | 'defense'>('attack');
    const [isEntering, setIsEntering] = useState(false);
    const [error, setError] = useState<string | null>(null);

    const canEnter = (dungeon: Dungeon) =>
        energy.current >= dungeon.energy_cost &&
        player_stats.hp > 0 &&
        player_stats.combat_level >= dungeon.min_combat_level;

    const enterDungeon = async () => {
        if (!selectedDungeon || isEntering) return;

        setIsEntering(true);
        setError(null);

        try {
            const response = await fetch('/dungeons/enter', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content || '',
                },
                body: JSON.stringify({
                    dungeon_id: selectedDungeon.id,
                    training_style: trainingStyle,
                }),
            });

            const data = await response.json();

            if (data.success) {
                router.reload();
            } else {
                setError(data.message);
            }
        } catch {
            setError('Failed to enter dungeon');
        } finally {
            setIsEntering(false);
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dungeons" />
            <div className="flex h-full flex-1 flex-col p-4">
                {/* Header */}
                <div className="mb-6">
                    <h1 className="font-pixel text-2xl text-amber-400">Dungeons</h1>
                    <p className="font-pixel text-sm text-stone-400">Explore dangerous dungeons for great rewards</p>
                </div>

                {/* Player Stats */}
                <div className="mb-6 grid gap-4 md:grid-cols-2">
                    {/* Health & Energy */}
                    <div className="rounded-lg border border-stone-700 bg-stone-800/50 p-4">
                        <h3 className="mb-3 font-pixel text-sm text-amber-300">Status</h3>
                        <div className="space-y-3">
                            <div>
                                <div className="mb-1 flex items-center justify-between font-pixel text-xs">
                                    <span className="flex items-center gap-1 text-red-400">
                                        <Heart className="h-3 w-3" /> HP
                                    </span>
                                    <span className="text-stone-300">
                                        {player_stats.hp} / {player_stats.max_hp}
                                    </span>
                                </div>
                                <div className="h-2 w-full overflow-hidden rounded-full bg-stone-700">
                                    <div
                                        className="h-full bg-gradient-to-r from-red-600 to-red-400"
                                        style={{ width: `${(player_stats.hp / player_stats.max_hp) * 100}%` }}
                                    />
                                </div>
                            </div>
                            <div>
                                <div className="mb-1 flex items-center justify-between font-pixel text-xs">
                                    <span className="flex items-center gap-1 text-yellow-400">
                                        <Zap className="h-3 w-3" /> Energy
                                    </span>
                                    <span className="text-stone-300">{energy.current}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Combat Stats */}
                    <div className="rounded-lg border border-stone-700 bg-stone-800/50 p-4">
                        <h3 className="mb-3 font-pixel text-sm text-amber-300">Combat Level {player_stats.combat_level}</h3>
                        <div className="grid grid-cols-3 gap-3">
                            <div className="text-center">
                                <Sword className="mx-auto h-5 w-5 text-red-400" />
                                <div className="font-pixel text-xs text-stone-400">Attack</div>
                                <div className="font-pixel text-sm text-white">
                                    {player_stats.attack}
                                    {equipment.atk_bonus > 0 && <span className="text-green-400"> +{equipment.atk_bonus}</span>}
                                </div>
                            </div>
                            <div className="text-center">
                                <Skull className="mx-auto h-5 w-5 text-orange-400" />
                                <div className="font-pixel text-xs text-stone-400">Strength</div>
                                <div className="font-pixel text-sm text-white">
                                    {player_stats.strength}
                                    {equipment.str_bonus > 0 && <span className="text-green-400"> +{equipment.str_bonus}</span>}
                                </div>
                            </div>
                            <div className="text-center">
                                <Shield className="mx-auto h-5 w-5 text-blue-400" />
                                <div className="font-pixel text-xs text-stone-400">Defense</div>
                                <div className="font-pixel text-sm text-white">
                                    {player_stats.defense}
                                    {equipment.def_bonus > 0 && <span className="text-green-400"> +{equipment.def_bonus}</span>}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Training Style Selection */}
                <div className="mb-6 rounded-lg border border-stone-700 bg-stone-800/50 p-4">
                    <h3 className="mb-3 font-pixel text-sm text-amber-300">Training Style (XP Focus)</h3>
                    <div className="flex gap-2">
                        {(['attack', 'strength', 'defense'] as const).map((style) => (
                            <button
                                key={style}
                                onClick={() => setTrainingStyle(style)}
                                className={`flex-1 rounded-lg border px-4 py-2 font-pixel text-xs capitalize transition ${
                                    trainingStyle === style
                                        ? 'border-amber-500 bg-amber-900/50 text-amber-300'
                                        : 'border-stone-600 bg-stone-800/50 text-stone-400 hover:bg-stone-700/50'
                                }`}
                            >
                                {style}
                            </button>
                        ))}
                    </div>
                </div>

                {/* Error Message */}
                {error && (
                    <div className="mb-4 rounded-lg border border-red-500/50 bg-red-900/30 p-3 font-pixel text-sm text-red-300">
                        {error}
                    </div>
                )}

                {/* Dungeons Grid */}
                <div className="mb-6">
                    <h3 className="mb-4 font-pixel text-lg text-amber-300">Available Dungeons</h3>
                    {dungeons.length === 0 ? (
                        <div className="flex flex-1 items-center justify-center py-12">
                            <div className="text-center">
                                <Castle className="mx-auto mb-3 h-16 w-16 text-stone-600" />
                                <p className="font-pixel text-base text-stone-500">No dungeons available here</p>
                                <p className="font-pixel text-xs text-stone-600">Try a different location or level up</p>
                            </div>
                        </div>
                    ) : (
                        <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                            {dungeons.map((dungeon) => {
                                const isSelected = selectedDungeon?.id === dungeon.id;
                                const colorClass = difficultyColors[dungeon.difficulty] || 'border-stone-600/50 bg-stone-800/50';
                                const canEnterThis = canEnter(dungeon);

                                return (
                                    <button
                                        key={dungeon.id}
                                        onClick={() => setSelectedDungeon(dungeon)}
                                        disabled={!canEnterThis}
                                        className={`rounded-xl border-2 p-4 text-left transition ${colorClass} ${
                                            isSelected ? 'ring-2 ring-amber-500' : ''
                                        } ${!canEnterThis ? 'cursor-not-allowed opacity-50' : ''}`}
                                    >
                                        <div className="mb-3 flex items-center justify-between">
                                            <div>
                                                <h4 className="font-pixel text-base text-amber-300">{dungeon.name}</h4>
                                                <p className="font-pixel text-[10px] text-stone-400">
                                                    {themeIcons[dungeon.theme] || dungeon.theme} - {dungeon.floor_count} Floors
                                                </p>
                                            </div>
                                            <div className="rounded bg-stone-800/50 px-2 py-1">
                                                <span className="font-pixel text-xs capitalize">{dungeon.difficulty}</span>
                                            </div>
                                        </div>

                                        {dungeon.description && (
                                            <p className="mb-3 font-pixel text-[10px] text-stone-400">{dungeon.description}</p>
                                        )}

                                        <div className="mb-2 grid grid-cols-2 gap-2 font-pixel text-xs text-stone-400">
                                            <span>Level: {dungeon.min_combat_level}+</span>
                                            <span>Rec: {dungeon.recommended_level}</span>
                                            <span>Energy: {dungeon.energy_cost}</span>
                                            <span>XP: {dungeon.xp_reward_base}+</span>
                                        </div>

                                        <div className="flex items-center justify-between font-pixel text-xs text-stone-400">
                                            <span>
                                                Gold: {dungeon.gold_reward_min}-{dungeon.gold_reward_max}
                                            </span>
                                            {dungeon.boss_monster && (
                                                <span className="text-red-400">Boss: {dungeon.boss_monster.name}</span>
                                            )}
                                        </div>
                                    </button>
                                );
                            })}
                        </div>
                    )}
                </div>

                {/* Enter Button */}
                {selectedDungeon && (
                    <div className="fixed bottom-20 left-0 right-0 border-t border-stone-700 bg-stone-900/95 p-4">
                        <div className="mx-auto flex max-w-4xl items-center justify-between">
                            <div>
                                <p className="font-pixel text-sm text-amber-300">Selected: {selectedDungeon.name}</p>
                                <p className="font-pixel text-xs text-stone-400">
                                    {selectedDungeon.floor_count} floors | Training: {trainingStyle} | Energy:{' '}
                                    {selectedDungeon.energy_cost}
                                </p>
                            </div>
                            <button
                                onClick={enterDungeon}
                                disabled={!canEnter(selectedDungeon) || isEntering}
                                className={`rounded-lg px-6 py-3 font-pixel text-sm transition ${
                                    canEnter(selectedDungeon) && !isEntering
                                        ? 'bg-amber-600 text-white hover:bg-amber-500'
                                        : 'cursor-not-allowed bg-stone-700 text-stone-500'
                                }`}
                            >
                                {isEntering ? 'Entering...' : 'Enter Dungeon'}
                            </button>
                        </div>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
