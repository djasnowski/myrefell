import { Head, router, usePage } from '@inertiajs/react';
import { Skull, Sword, Shield, Zap, Heart } from 'lucide-react';
import { useState } from 'react';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

interface Monster {
    id: number;
    name: string;
    type: string;
    combat_level: number;
    hp: number;
    max_hp: number;
    xp_reward: number;
    gold_drop_min: number;
    gold_drop_max: number;
    is_boss: boolean;
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
    cost: number;
}

interface PageProps {
    monsters: Monster[];
    player_stats: PlayerStats;
    equipment: Equipment;
    energy: EnergyInfo;
    [key: string]: unknown;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Combat', href: '/combat' },
];

const monsterTypeColors: Record<string, string> = {
    humanoid: 'border-amber-500/50 bg-amber-900/20',
    beast: 'border-green-500/50 bg-green-900/20',
    undead: 'border-purple-500/50 bg-purple-900/20',
    dragon: 'border-red-500/50 bg-red-900/20',
    demon: 'border-rose-500/50 bg-rose-900/20',
    elemental: 'border-cyan-500/50 bg-cyan-900/20',
    giant: 'border-orange-500/50 bg-orange-900/20',
    goblinoid: 'border-yellow-500/50 bg-yellow-900/20',
};

export default function CombatIndex() {
    const { monsters, player_stats, equipment, energy } = usePage<PageProps>().props;
    const [selectedMonster, setSelectedMonster] = useState<Monster | null>(null);
    const [trainingStyle, setTrainingStyle] = useState<'attack' | 'strength' | 'defense'>('attack');
    const [isStarting, setIsStarting] = useState(false);
    const [error, setError] = useState<string | null>(null);

    const canFight = energy.current >= energy.cost && player_stats.hp > 0;

    const startCombat = async () => {
        if (!selectedMonster || isStarting) return;

        setIsStarting(true);
        setError(null);

        try {
            const response = await fetch('/combat/start', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content || '',
                },
                body: JSON.stringify({
                    monster_id: selectedMonster.id,
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
            setError('Failed to start combat');
        } finally {
            setIsStarting(false);
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Combat" />
            <div className="flex h-full flex-1 flex-col p-4">
                {/* Header */}
                <div className="mb-6">
                    <h1 className="font-pixel text-2xl text-amber-400">Combat</h1>
                    <p className="font-pixel text-sm text-stone-400">Battle monsters to gain experience and loot</p>
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
                                    <span className="text-stone-300">
                                        {energy.current} (costs {energy.cost})
                                    </span>
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
                                    {equipment.atk_bonus > 0 && (
                                        <span className="text-green-400"> +{equipment.atk_bonus}</span>
                                    )}
                                </div>
                            </div>
                            <div className="text-center">
                                <Skull className="mx-auto h-5 w-5 text-orange-400" />
                                <div className="font-pixel text-xs text-stone-400">Strength</div>
                                <div className="font-pixel text-sm text-white">
                                    {player_stats.strength}
                                    {equipment.str_bonus > 0 && (
                                        <span className="text-green-400"> +{equipment.str_bonus}</span>
                                    )}
                                </div>
                            </div>
                            <div className="text-center">
                                <Shield className="mx-auto h-5 w-5 text-blue-400" />
                                <div className="font-pixel text-xs text-stone-400">Defense</div>
                                <div className="font-pixel text-sm text-white">
                                    {player_stats.defense}
                                    {equipment.def_bonus > 0 && (
                                        <span className="text-green-400"> +{equipment.def_bonus}</span>
                                    )}
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

                {/* Monsters Grid */}
                <div className="mb-6">
                    <h3 className="mb-4 font-pixel text-lg text-amber-300">Available Monsters</h3>
                    {monsters.length === 0 ? (
                        <div className="flex flex-1 items-center justify-center py-12">
                            <div className="text-center">
                                <Skull className="mx-auto mb-3 h-16 w-16 text-stone-600" />
                                <p className="font-pixel text-base text-stone-500">No monsters available here</p>
                                <p className="font-pixel text-xs text-stone-600">Try a different location</p>
                            </div>
                        </div>
                    ) : (
                        <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                            {monsters.map((monster) => {
                                const isSelected = selectedMonster?.id === monster.id;
                                const colorClass = monsterTypeColors[monster.type] || 'border-stone-600/50 bg-stone-800/50';

                                return (
                                    <button
                                        key={monster.id}
                                        onClick={() => setSelectedMonster(monster)}
                                        className={`rounded-xl border-2 p-4 text-left transition ${colorClass} ${
                                            isSelected ? 'ring-2 ring-amber-500' : ''
                                        }`}
                                    >
                                        <div className="mb-3 flex items-center justify-between">
                                            <div>
                                                <h4 className="font-pixel text-base text-amber-300">
                                                    {monster.is_boss && <span className="text-red-400">[BOSS] </span>}
                                                    {monster.name}
                                                </h4>
                                                <p className="font-pixel text-[10px] capitalize text-stone-400">
                                                    {monster.type} - Level {monster.combat_level}
                                                </p>
                                            </div>
                                            <div className="rounded bg-stone-800/50 px-2 py-1">
                                                <Heart className="inline h-3 w-3 text-red-400" />
                                                <span className="ml-1 font-pixel text-xs text-stone-300">{monster.max_hp}</span>
                                            </div>
                                        </div>

                                        <div className="flex items-center justify-between font-pixel text-xs text-stone-400">
                                            <span>XP: {monster.xp_reward}</span>
                                            <span>
                                                Gold: {monster.gold_drop_min}-{monster.gold_drop_max}
                                            </span>
                                        </div>
                                    </button>
                                );
                            })}
                        </div>
                    )}
                </div>

                {/* Fight Button */}
                {selectedMonster && (
                    <div className="fixed bottom-20 left-0 right-0 border-t border-stone-700 bg-stone-900/95 p-4">
                        <div className="mx-auto flex max-w-4xl items-center justify-between">
                            <div>
                                <p className="font-pixel text-sm text-amber-300">Selected: {selectedMonster.name}</p>
                                <p className="font-pixel text-xs text-stone-400">
                                    Level {selectedMonster.combat_level} | Training: {trainingStyle}
                                </p>
                            </div>
                            <button
                                onClick={startCombat}
                                disabled={!canFight || isStarting}
                                className={`rounded-lg px-6 py-3 font-pixel text-sm transition ${
                                    canFight && !isStarting
                                        ? 'bg-red-600 text-white hover:bg-red-500'
                                        : 'cursor-not-allowed bg-stone-700 text-stone-500'
                                }`}
                            >
                                {isStarting ? 'Starting...' : 'Fight!'}
                            </button>
                        </div>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
