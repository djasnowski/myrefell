import { Head, router, usePage } from '@inertiajs/react';
import { Sword, Heart, Zap, Apple, DoorOpen, Skull } from 'lucide-react';
import { useState, useEffect } from 'react';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

interface Monster {
    id: number;
    name: string;
    type: string;
    combat_level: number;
    max_hp: number;
}

interface CombatLog {
    id: number;
    round: number;
    actor: 'player' | 'monster';
    action: 'attack' | 'eat' | 'flee';
    hit: boolean;
    damage: number;
    player_hp_after: number;
    monster_hp_after: number;
    hp_restored: number;
    item?: { name: string };
}

interface CombatSession {
    id: number;
    player_hp: number;
    monster_hp: number;
    round: number;
    training_style: string;
    status: 'active' | 'victory' | 'defeat' | 'fled';
    monster: Monster;
    logs: CombatLog[];
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

interface FoodItem {
    id: number;
    name: string;
    hp_bonus: number;
    quantity: number;
}

interface PageProps {
    session: CombatSession;
    player_stats: PlayerStats;
    equipment: Equipment;
    food: FoodItem[];
    [key: string]: unknown;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Combat', href: '/combat' },
    { title: 'Battle', href: '/combat' },
];

export default function CombatArena() {
    const { session: initialSession, player_stats, food: initialFood } = usePage<PageProps>().props;
    const [session, setSession] = useState(initialSession);
    const [food, setFood] = useState(initialFood);
    const [isActing, setIsActing] = useState(false);
    const [combatLogs, setCombatLogs] = useState<CombatLog[]>(initialSession.logs || []);
    const [rewards, setRewards] = useState<{
        xp: number;
        skill: string;
        levels_gained: number;
        gold: number;
        items: { name: string; quantity: number }[];
    } | null>(null);
    const [showFood, setShowFood] = useState(false);

    const isActive = session.status === 'active';
    const isVictory = session.status === 'victory';
    const isDefeat = session.status === 'defeat';
    const hasFled = session.status === 'fled';

    useEffect(() => {
        const logContainer = document.getElementById('combat-logs');
        if (logContainer) {
            logContainer.scrollTop = logContainer.scrollHeight;
        }
    }, [combatLogs]);

    const performAction = async (action: 'attack' | 'flee', extraData?: Record<string, unknown>) => {
        if (isActing || !isActive) return;

        setIsActing(true);

        try {
            const response = await fetch(`/combat/${action}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content || '',
                },
                body: JSON.stringify(extraData || {}),
            });

            const data = await response.json();

            if (data.data?.session) {
                setSession(data.data.session);
            }
            if (data.data?.logs) {
                setCombatLogs((prev) => [...prev, ...data.data.logs]);
            }
            if (data.data?.rewards) {
                setRewards(data.data.rewards);
            }
        } catch (error) {
            console.error('Combat action failed:', error);
        } finally {
            setIsActing(false);
        }
    };

    const eatFood = async (inventorySlotId: number) => {
        if (isActing || !isActive) return;

        setIsActing(true);
        setShowFood(false);

        try {
            const response = await fetch('/combat/eat', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content || '',
                },
                body: JSON.stringify({ inventory_slot_id: inventorySlotId }),
            });

            const data = await response.json();

            if (data.data?.session) {
                setSession(data.data.session);
            }
            if (data.data?.logs) {
                setCombatLogs((prev) => [...prev, ...data.data.logs]);
            }

            // Update food list
            setFood((prev) =>
                prev
                    .map((f) => (f.id === inventorySlotId ? { ...f, quantity: f.quantity - 1 } : f))
                    .filter((f) => f.quantity > 0)
            );
        } catch (error) {
            console.error('Eat action failed:', error);
        } finally {
            setIsActing(false);
        }
    };

    const returnToCombat = () => {
        router.visit('/combat');
    };

    const getLogMessage = (log: CombatLog): string => {
        const actorName = log.actor === 'player' ? 'You' : session.monster.name;

        switch (log.action) {
            case 'attack':
                return log.hit
                    ? `${actorName} hit for ${log.damage} damage!`
                    : `${actorName} missed!`;
            case 'eat':
                return `You ate ${log.item?.name || 'food'} and restored ${log.hp_restored} HP.`;
            case 'flee':
                return log.hit ? 'You successfully fled from combat!' : 'You failed to flee!';
            default:
                return 'Unknown action';
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Fighting ${session.monster.name}`} />
            <div className="flex h-full flex-1 flex-col p-4">
                {/* Combat Header */}
                <div className="mb-4 text-center">
                    <h1 className="font-pixel text-xl text-amber-400">Round {session.round}</h1>
                    <p className="font-pixel text-xs text-stone-400">Training: {session.training_style}</p>
                </div>

                {/* Health Bars */}
                <div className="mb-6 grid gap-4 md:grid-cols-2">
                    {/* Player HP */}
                    <div className="rounded-lg border border-stone-700 bg-stone-800/50 p-4">
                        <div className="mb-2 flex items-center justify-between">
                            <span className="font-pixel text-sm text-amber-300">You</span>
                            <span className="font-pixel text-xs text-stone-400">Level {player_stats.combat_level}</span>
                        </div>
                        <div className="mb-1 flex items-center justify-between font-pixel text-xs">
                            <span className="flex items-center gap-1 text-red-400">
                                <Heart className="h-3 w-3" /> HP
                            </span>
                            <span className="text-stone-300">
                                {session.player_hp} / {player_stats.max_hp}
                            </span>
                        </div>
                        <div className="h-4 w-full overflow-hidden rounded-full bg-stone-700">
                            <div
                                className="h-full bg-gradient-to-r from-green-600 to-green-400 transition-all duration-300"
                                style={{ width: `${(session.player_hp / player_stats.max_hp) * 100}%` }}
                            />
                        </div>
                    </div>

                    {/* Monster HP */}
                    <div className="rounded-lg border border-stone-700 bg-stone-800/50 p-4">
                        <div className="mb-2 flex items-center justify-between">
                            <span className="font-pixel text-sm text-red-400">{session.monster.name}</span>
                            <span className="font-pixel text-xs text-stone-400">Level {session.monster.combat_level}</span>
                        </div>
                        <div className="mb-1 flex items-center justify-between font-pixel text-xs">
                            <span className="flex items-center gap-1 text-red-400">
                                <Heart className="h-3 w-3" /> HP
                            </span>
                            <span className="text-stone-300">
                                {session.monster_hp} / {session.monster.max_hp}
                            </span>
                        </div>
                        <div className="h-4 w-full overflow-hidden rounded-full bg-stone-700">
                            <div
                                className="h-full bg-gradient-to-r from-red-600 to-red-400 transition-all duration-300"
                                style={{ width: `${(session.monster_hp / session.monster.max_hp) * 100}%` }}
                            />
                        </div>
                    </div>
                </div>

                {/* Combat Log */}
                <div className="mb-6 flex-1 rounded-lg border border-stone-700 bg-stone-900/50 p-4">
                    <h3 className="mb-3 font-pixel text-sm text-amber-300">Combat Log</h3>
                    <div
                        id="combat-logs"
                        className="h-48 space-y-2 overflow-y-auto scrollbar-thin scrollbar-track-stone-800 scrollbar-thumb-stone-600"
                    >
                        {combatLogs.map((log) => (
                            <div
                                key={log.id}
                                className={`rounded px-2 py-1 font-pixel text-xs ${
                                    log.actor === 'player'
                                        ? 'bg-stone-800/50 text-green-400'
                                        : 'bg-stone-800/50 text-red-400'
                                }`}
                            >
                                <span className="text-stone-500">R{log.round}:</span> {getLogMessage(log)}
                            </div>
                        ))}
                        {combatLogs.length === 0 && (
                            <p className="text-center font-pixel text-xs text-stone-500">Combat begins...</p>
                        )}
                    </div>
                </div>

                {/* Victory Screen */}
                {isVictory && rewards && (
                    <div className="mb-6 rounded-lg border border-green-500/50 bg-green-900/30 p-6 text-center">
                        <Sword className="mx-auto mb-3 h-12 w-12 text-green-400" />
                        <h2 className="mb-2 font-pixel text-xl text-green-400">Victory!</h2>
                        <p className="mb-4 font-pixel text-sm text-stone-300">You defeated {session.monster.name}!</p>

                        <div className="grid gap-2 text-left">
                            <div className="font-pixel text-xs text-stone-400">
                                <Zap className="mr-1 inline h-3 w-3 text-amber-400" />
                                {rewards.xp} {rewards.skill} XP
                                {rewards.levels_gained > 0 && (
                                    <span className="ml-2 text-green-400">+{rewards.levels_gained} level(s)!</span>
                                )}
                            </div>
                            {rewards.gold > 0 && (
                                <div className="font-pixel text-xs text-stone-400">
                                    <span className="mr-1 text-yellow-400">Gold:</span> {rewards.gold}
                                </div>
                            )}
                            {rewards.items.map((item, i) => (
                                <div key={i} className="font-pixel text-xs text-stone-400">
                                    <span className="mr-1 text-blue-400">Loot:</span> {item.name} x{item.quantity}
                                </div>
                            ))}
                        </div>

                        <button
                            onClick={returnToCombat}
                            className="mt-4 rounded-lg bg-green-600 px-6 py-2 font-pixel text-sm text-white hover:bg-green-500"
                        >
                            Continue
                        </button>
                    </div>
                )}

                {/* Defeat Screen */}
                {isDefeat && (
                    <div className="mb-6 rounded-lg border border-red-500/50 bg-red-900/30 p-6 text-center">
                        <Skull className="mx-auto mb-3 h-12 w-12 text-red-400" />
                        <h2 className="mb-2 font-pixel text-xl text-red-400">Defeat!</h2>
                        <p className="mb-4 font-pixel text-sm text-stone-300">You were killed by {session.monster.name}.</p>
                        <p className="mb-4 font-pixel text-xs text-stone-400">Your energy has been reduced.</p>

                        <button
                            onClick={returnToCombat}
                            className="rounded-lg bg-stone-700 px-6 py-2 font-pixel text-sm text-white hover:bg-stone-600"
                        >
                            Return
                        </button>
                    </div>
                )}

                {/* Fled Screen */}
                {hasFled && (
                    <div className="mb-6 rounded-lg border border-yellow-500/50 bg-yellow-900/30 p-6 text-center">
                        <DoorOpen className="mx-auto mb-3 h-12 w-12 text-yellow-400" />
                        <h2 className="mb-2 font-pixel text-xl text-yellow-400">Escaped!</h2>
                        <p className="mb-4 font-pixel text-sm text-stone-300">You successfully fled from combat.</p>

                        <button
                            onClick={returnToCombat}
                            className="rounded-lg bg-yellow-600 px-6 py-2 font-pixel text-sm text-white hover:bg-yellow-500"
                        >
                            Continue
                        </button>
                    </div>
                )}

                {/* Action Buttons */}
                {isActive && (
                    <div className="space-y-3">
                        {/* Food Panel */}
                        {showFood && (
                            <div className="rounded-lg border border-stone-700 bg-stone-800/90 p-4">
                                <div className="mb-3 flex items-center justify-between">
                                    <h3 className="font-pixel text-sm text-amber-300">Select Food</h3>
                                    <button
                                        onClick={() => setShowFood(false)}
                                        className="font-pixel text-xs text-stone-400 hover:text-white"
                                    >
                                        Cancel
                                    </button>
                                </div>
                                {food.length === 0 ? (
                                    <p className="font-pixel text-xs text-stone-400">No food available</p>
                                ) : (
                                    <div className="grid gap-2">
                                        {food.map((item) => (
                                            <button
                                                key={item.id}
                                                onClick={() => eatFood(item.id)}
                                                disabled={isActing}
                                                className="flex items-center justify-between rounded border border-stone-600 bg-stone-700/50 px-3 py-2 text-left transition hover:bg-stone-700"
                                            >
                                                <span className="font-pixel text-xs text-stone-300">{item.name}</span>
                                                <span className="font-pixel text-xs">
                                                    <span className="text-green-400">+{item.hp_bonus} HP</span>
                                                    <span className="ml-2 text-stone-500">x{item.quantity}</span>
                                                </span>
                                            </button>
                                        ))}
                                    </div>
                                )}
                            </div>
                        )}

                        {/* Main Actions */}
                        <div className="grid grid-cols-3 gap-3">
                            <button
                                onClick={() => performAction('attack')}
                                disabled={isActing}
                                className={`flex flex-col items-center gap-1 rounded-lg border border-red-500/50 bg-red-900/30 px-4 py-3 transition ${
                                    isActing ? 'opacity-50' : 'hover:bg-red-900/50'
                                }`}
                            >
                                <Sword className="h-6 w-6 text-red-400" />
                                <span className="font-pixel text-xs text-red-300">Attack</span>
                            </button>

                            <button
                                onClick={() => setShowFood(!showFood)}
                                disabled={isActing || food.length === 0}
                                className={`flex flex-col items-center gap-1 rounded-lg border border-green-500/50 bg-green-900/30 px-4 py-3 transition ${
                                    isActing || food.length === 0 ? 'opacity-50' : 'hover:bg-green-900/50'
                                }`}
                            >
                                <Apple className="h-6 w-6 text-green-400" />
                                <span className="font-pixel text-xs text-green-300">Eat ({food.length})</span>
                            </button>

                            <button
                                onClick={() => performAction('flee')}
                                disabled={isActing}
                                className={`flex flex-col items-center gap-1 rounded-lg border border-yellow-500/50 bg-yellow-900/30 px-4 py-3 transition ${
                                    isActing ? 'opacity-50' : 'hover:bg-yellow-900/50'
                                }`}
                            >
                                <DoorOpen className="h-6 w-6 text-yellow-400" />
                                <span className="font-pixel text-xs text-yellow-300">Flee</span>
                            </button>
                        </div>

                        {isActing && (
                            <p className="text-center font-pixel text-xs text-stone-400">Processing...</p>
                        )}
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
