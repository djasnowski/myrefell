import { Head, Link, usePage } from '@inertiajs/react';
import {
    ArrowLeft,
    Calendar,
    Castle,
    Flag,
    Heart,
    MapPin,
    Shield,
    Skull,
    Sword,
    Swords,
    Target,
    Trophy,
    Users,
} from 'lucide-react';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

interface Location {
    id?: number;
    name: string;
    type: string;
}

interface WarInfo {
    id: number;
    name: string;
    status: string;
}

interface Participant {
    id: number;
    army_id: number;
    army_name: string;
    commander_name: string;
    side: string;
    is_commander: boolean;
    troops_committed: number;
    casualties: number;
    casualty_rate: number;
    morale_at_start: number;
    morale_at_end: number | null;
    morale_loss: number;
    outcome: string | null;
}

interface BattleLogEntry {
    day: number;
    description: string;
    attacker_casualties?: number;
    defender_casualties?: number;
}

interface Battle {
    id: number;
    name: string;
    battle_type: string;
    status: string;
    phase: string;
    day: number;
    location: Location;
    attacker_troops_start: number;
    defender_troops_start: number;
    attacker_casualties: number;
    defender_casualties: number;
    attacker_remaining: number;
    defender_remaining: number;
    terrain_modifiers: Record<string, number>;
    weather_modifiers: Record<string, number>;
    battle_log: BattleLogEntry[];
    started_at: string | null;
    ended_at: string | null;
    is_ongoing: boolean;
    is_ended: boolean;
}

interface PageProps {
    battle: Battle;
    attackers: Participant[];
    defenders: Participant[];
    attacker_commander: string | null;
    defender_commander: string | null;
    war: WarInfo | null;
    [key: string]: unknown;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Warfare', href: '#' },
    { title: 'Wars', href: '/warfare/wars' },
    { title: 'Battle', href: '#' },
];

const statusColors: Record<string, { bg: string; text: string; label: string }> = {
    ongoing: { bg: 'bg-amber-900/30', text: 'text-amber-400', label: 'Ongoing' },
    attacker_victory: { bg: 'bg-red-900/30', text: 'text-red-400', label: 'Attacker Victory' },
    defender_victory: { bg: 'bg-blue-900/30', text: 'text-blue-400', label: 'Defender Victory' },
    draw: { bg: 'bg-stone-900/30', text: 'text-stone-400', label: 'Draw' },
    inconclusive: { bg: 'bg-stone-900/30', text: 'text-stone-500', label: 'Inconclusive' },
};

const battleTypeLabels: Record<string, string> = {
    field: 'Field Battle',
    siege_assault: 'Siege Assault',
    naval: 'Naval Battle',
    skirmish: 'Skirmish',
};

const phaseLabels: Record<string, string> = {
    engagement: 'Engagement',
    melee: 'Melee',
    pursuit: 'Pursuit',
    aftermath: 'Aftermath',
};

const outcomeLabels: Record<string, { text: string; color: string }> = {
    victory: { text: 'Victory', color: 'text-green-400' },
    defeat: { text: 'Defeat', color: 'text-red-400' },
    routed: { text: 'Routed', color: 'text-red-500' },
    withdrew: { text: 'Withdrew', color: 'text-yellow-400' },
};

const locationTypeIcons: Record<string, typeof Castle> = {
    castle: Castle,
    town: Flag,
    village: Users,
};

export default function BattleShow() {
    const { battle, attackers, defenders, attacker_commander, defender_commander, war } = usePage<PageProps>().props;

    const status = statusColors[battle.status] || statusColors.ongoing;
    const LocationIcon = locationTypeIcons[battle.location.type] || MapPin;

    const attackerCasualtyRate = battle.attacker_troops_start > 0
        ? Math.round((battle.attacker_casualties / battle.attacker_troops_start) * 100)
        : 0;

    const defenderCasualtyRate = battle.defender_troops_start > 0
        ? Math.round((battle.defender_casualties / battle.defender_troops_start) * 100)
        : 0;

    const renderProgressBar = (value: number, maxValue: number, colorClass: string) => {
        const percentage = Math.min(100, Math.max(0, (value / maxValue) * 100));
        return (
            <div className="h-3 w-full overflow-hidden rounded bg-stone-800">
                <div
                    className={`h-full transition-all duration-300 ${colorClass}`}
                    style={{ width: `${percentage}%` }}
                />
            </div>
        );
    };

    const formatModifiers = (modifiers: Record<string, number>) => {
        return Object.entries(modifiers)
            .filter(([_, value]) => value !== 0)
            .map(([key, value]) => ({
                name: key.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase()),
                value: value > 0 ? `+${value}%` : `${value}%`,
                isPositive: value > 0,
            }));
    };

    const terrainMods = formatModifiers(battle.terrain_modifiers);
    const weatherMods = formatModifiers(battle.weather_modifiers);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={battle.name || `Battle at ${battle.location.name}`} />
            <div className="flex h-full flex-1 flex-col gap-6 overflow-y-auto p-4">
                {/* Header */}
                <div className="flex items-start justify-between">
                    <div>
                        <div className="mb-2 flex items-center gap-2">
                            <Link
                                href={war ? `/warfare/wars/${war.id}` : '/warfare/wars'}
                                className="flex items-center gap-1 font-pixel text-xs text-stone-400 transition hover:text-stone-300"
                            >
                                <ArrowLeft className="h-3 w-3" />
                                {war ? `Back to ${war.name}` : 'Back to Wars'}
                            </Link>
                        </div>
                        <div className="flex items-center gap-3">
                            <Swords className="h-8 w-8 text-red-400" />
                            <div>
                                <h1 className="font-pixel text-2xl text-amber-400">
                                    {battle.name || `Battle at ${battle.location.name}`}
                                </h1>
                                <div className="flex items-center gap-3 font-pixel text-sm text-stone-400">
                                    <span className="flex items-center gap-1">
                                        <LocationIcon className="h-3 w-3" />
                                        {battle.location.name}
                                    </span>
                                    <span>|</span>
                                    <span>{battleTypeLabels[battle.battle_type] || battle.battle_type}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div className="flex flex-col items-end gap-2">
                        <span className={`rounded px-3 py-1.5 font-pixel text-xs ${status.bg} ${status.text}`}>
                            {status.label}
                        </span>
                        {battle.is_ongoing && (
                            <span className="font-pixel text-xs text-stone-400">
                                Day {battle.day} | Phase: {phaseLabels[battle.phase] || battle.phase}
                            </span>
                        )}
                    </div>
                </div>

                {/* War Link */}
                {war && (
                    <div className="rounded-lg border border-stone-600/50 bg-stone-800/30 p-3">
                        <div className="flex items-center gap-2 font-pixel text-xs">
                            <Swords className="h-4 w-4 text-red-400" />
                            <span className="text-stone-400">Part of:</span>
                            <Link
                                href={`/warfare/wars/${war.id}`}
                                className="text-amber-300 transition hover:text-amber-200"
                            >
                                {war.name}
                            </Link>
                            <span className="rounded bg-stone-700/50 px-2 py-0.5 text-[10px] capitalize text-stone-400">
                                {war.status.replace('_', ' ')}
                            </span>
                        </div>
                    </div>
                )}

                {/* Terrain and Weather Modifiers */}
                {(terrainMods.length > 0 || weatherMods.length > 0) && (
                    <div className="rounded-lg border border-stone-600/50 bg-stone-800/30 p-3">
                        <div className="flex flex-wrap gap-4 font-pixel text-xs">
                            {terrainMods.length > 0 && (
                                <div className="flex items-center gap-2">
                                    <MapPin className="h-4 w-4 text-amber-400" />
                                    <span className="text-stone-400">Terrain:</span>
                                    {terrainMods.map((mod, idx) => (
                                        <span
                                            key={idx}
                                            className={`rounded px-2 py-0.5 ${mod.isPositive ? 'bg-green-900/30 text-green-400' : 'bg-red-900/30 text-red-400'}`}
                                        >
                                            {mod.name} {mod.value}
                                        </span>
                                    ))}
                                </div>
                            )}
                            {weatherMods.length > 0 && (
                                <div className="flex items-center gap-2">
                                    <span className="text-stone-400">Weather:</span>
                                    {weatherMods.map((mod, idx) => (
                                        <span
                                            key={idx}
                                            className={`rounded px-2 py-0.5 ${mod.isPositive ? 'bg-green-900/30 text-green-400' : 'bg-red-900/30 text-red-400'}`}
                                        >
                                            {mod.name} {mod.value}
                                        </span>
                                    ))}
                                </div>
                            )}
                        </div>
                    </div>
                )}

                {/* Force Comparison */}
                <div className="grid gap-6 lg:grid-cols-2">
                    {/* Attackers */}
                    <div className="rounded-xl border-2 border-red-600/50 bg-red-900/20 p-4">
                        <div className="mb-4 flex items-center justify-between">
                            <div className="flex items-center gap-2">
                                <Sword className="h-5 w-5 text-red-400" />
                                <h2 className="font-pixel text-lg text-red-300">Attackers</h2>
                            </div>
                            {battle.status === 'attacker_victory' && (
                                <Trophy className="h-5 w-5 text-amber-400" />
                            )}
                        </div>

                        {/* Commander */}
                        <div className="mb-3 rounded-lg bg-stone-900/50 p-3">
                            <div className="font-pixel text-xs text-stone-400">Commander</div>
                            <div className="font-pixel text-sm text-white">{attacker_commander || 'Unknown'}</div>
                        </div>

                        {/* Force Stats */}
                        <div className="mb-3 grid grid-cols-2 gap-3">
                            <div className="rounded-lg bg-stone-900/30 p-2 text-center">
                                <div className="font-pixel text-[10px] text-stone-400">Initial</div>
                                <div className="font-pixel text-lg text-white">{battle.attacker_troops_start}</div>
                            </div>
                            <div className="rounded-lg bg-stone-900/30 p-2 text-center">
                                <div className="font-pixel text-[10px] text-stone-400">Remaining</div>
                                <div className="font-pixel text-lg text-green-400">{battle.attacker_remaining}</div>
                            </div>
                        </div>

                        {/* Casualties */}
                        <div className="mb-4">
                            <div className="mb-1 flex items-center justify-between font-pixel text-xs">
                                <span className="flex items-center gap-1 text-stone-400">
                                    <Skull className="h-3 w-3" />
                                    Casualties
                                </span>
                                <span className="text-red-400">
                                    {battle.attacker_casualties} ({attackerCasualtyRate}%)
                                </span>
                            </div>
                            {renderProgressBar(battle.attacker_casualties, battle.attacker_troops_start, 'bg-red-500')}
                        </div>

                        {/* Participating Armies */}
                        {attackers.length > 0 && (
                            <div>
                                <div className="mb-2 font-pixel text-xs text-stone-400">Participating Armies</div>
                                <div className="space-y-2">
                                    {attackers.map((p) => (
                                        <div
                                            key={p.id}
                                            className="rounded-lg bg-stone-900/50 p-2"
                                        >
                                            <div className="flex items-center justify-between">
                                                <div>
                                                    <div className="flex items-center gap-2">
                                                        <span className="font-pixel text-sm text-white">
                                                            {p.army_name}
                                                        </span>
                                                        {p.is_commander && (
                                                            <span className="rounded bg-amber-900/50 px-1.5 py-0.5 font-pixel text-[10px] text-amber-300">
                                                                CMD
                                                            </span>
                                                        )}
                                                    </div>
                                                    <div className="font-pixel text-[10px] text-stone-500">
                                                        Led by {p.commander_name}
                                                    </div>
                                                </div>
                                                {p.outcome && (
                                                    <span className={`font-pixel text-xs ${outcomeLabels[p.outcome]?.color || 'text-stone-400'}`}>
                                                        {outcomeLabels[p.outcome]?.text || p.outcome}
                                                    </span>
                                                )}
                                            </div>
                                            <div className="mt-2 grid grid-cols-3 gap-2 font-pixel text-[10px]">
                                                <div>
                                                    <span className="text-stone-500">Troops:</span>{' '}
                                                    <span className="text-white">{p.troops_committed}</span>
                                                </div>
                                                <div>
                                                    <span className="text-stone-500">Lost:</span>{' '}
                                                    <span className="text-red-400">{p.casualties}</span>
                                                </div>
                                                <div>
                                                    <span className="text-stone-500">Rate:</span>{' '}
                                                    <span className="text-amber-400">{Math.round(p.casualty_rate)}%</span>
                                                </div>
                                            </div>
                                            {p.morale_at_end !== null && (
                                                <div className="mt-1 flex items-center gap-1 font-pixel text-[10px]">
                                                    <Heart className="h-3 w-3 text-red-400" />
                                                    <span className="text-stone-500">Morale:</span>
                                                    <span className="text-white">{p.morale_at_start}%</span>
                                                    <span className="text-stone-500">→</span>
                                                    <span className={p.morale_at_end >= 50 ? 'text-green-400' : 'text-red-400'}>
                                                        {p.morale_at_end}%
                                                    </span>
                                                    <span className="text-stone-500">(-{p.morale_loss})</span>
                                                </div>
                                            )}
                                        </div>
                                    ))}
                                </div>
                            </div>
                        )}
                    </div>

                    {/* Defenders */}
                    <div className="rounded-xl border-2 border-blue-600/50 bg-blue-900/20 p-4">
                        <div className="mb-4 flex items-center justify-between">
                            <div className="flex items-center gap-2">
                                <Shield className="h-5 w-5 text-blue-400" />
                                <h2 className="font-pixel text-lg text-blue-300">Defenders</h2>
                            </div>
                            {battle.status === 'defender_victory' && (
                                <Trophy className="h-5 w-5 text-amber-400" />
                            )}
                        </div>

                        {/* Commander */}
                        <div className="mb-3 rounded-lg bg-stone-900/50 p-3">
                            <div className="font-pixel text-xs text-stone-400">Commander</div>
                            <div className="font-pixel text-sm text-white">{defender_commander || 'Unknown'}</div>
                        </div>

                        {/* Force Stats */}
                        <div className="mb-3 grid grid-cols-2 gap-3">
                            <div className="rounded-lg bg-stone-900/30 p-2 text-center">
                                <div className="font-pixel text-[10px] text-stone-400">Initial</div>
                                <div className="font-pixel text-lg text-white">{battle.defender_troops_start}</div>
                            </div>
                            <div className="rounded-lg bg-stone-900/30 p-2 text-center">
                                <div className="font-pixel text-[10px] text-stone-400">Remaining</div>
                                <div className="font-pixel text-lg text-green-400">{battle.defender_remaining}</div>
                            </div>
                        </div>

                        {/* Casualties */}
                        <div className="mb-4">
                            <div className="mb-1 flex items-center justify-between font-pixel text-xs">
                                <span className="flex items-center gap-1 text-stone-400">
                                    <Skull className="h-3 w-3" />
                                    Casualties
                                </span>
                                <span className="text-red-400">
                                    {battle.defender_casualties} ({defenderCasualtyRate}%)
                                </span>
                            </div>
                            {renderProgressBar(battle.defender_casualties, battle.defender_troops_start, 'bg-red-500')}
                        </div>

                        {/* Participating Armies */}
                        {defenders.length > 0 && (
                            <div>
                                <div className="mb-2 font-pixel text-xs text-stone-400">Participating Armies</div>
                                <div className="space-y-2">
                                    {defenders.map((p) => (
                                        <div
                                            key={p.id}
                                            className="rounded-lg bg-stone-900/50 p-2"
                                        >
                                            <div className="flex items-center justify-between">
                                                <div>
                                                    <div className="flex items-center gap-2">
                                                        <span className="font-pixel text-sm text-white">
                                                            {p.army_name}
                                                        </span>
                                                        {p.is_commander && (
                                                            <span className="rounded bg-amber-900/50 px-1.5 py-0.5 font-pixel text-[10px] text-amber-300">
                                                                CMD
                                                            </span>
                                                        )}
                                                    </div>
                                                    <div className="font-pixel text-[10px] text-stone-500">
                                                        Led by {p.commander_name}
                                                    </div>
                                                </div>
                                                {p.outcome && (
                                                    <span className={`font-pixel text-xs ${outcomeLabels[p.outcome]?.color || 'text-stone-400'}`}>
                                                        {outcomeLabels[p.outcome]?.text || p.outcome}
                                                    </span>
                                                )}
                                            </div>
                                            <div className="mt-2 grid grid-cols-3 gap-2 font-pixel text-[10px]">
                                                <div>
                                                    <span className="text-stone-500">Troops:</span>{' '}
                                                    <span className="text-white">{p.troops_committed}</span>
                                                </div>
                                                <div>
                                                    <span className="text-stone-500">Lost:</span>{' '}
                                                    <span className="text-red-400">{p.casualties}</span>
                                                </div>
                                                <div>
                                                    <span className="text-stone-500">Rate:</span>{' '}
                                                    <span className="text-amber-400">{Math.round(p.casualty_rate)}%</span>
                                                </div>
                                            </div>
                                            {p.morale_at_end !== null && (
                                                <div className="mt-1 flex items-center gap-1 font-pixel text-[10px]">
                                                    <Heart className="h-3 w-3 text-red-400" />
                                                    <span className="text-stone-500">Morale:</span>
                                                    <span className="text-white">{p.morale_at_start}%</span>
                                                    <span className="text-stone-500">→</span>
                                                    <span className={p.morale_at_end >= 50 ? 'text-green-400' : 'text-red-400'}>
                                                        {p.morale_at_end}%
                                                    </span>
                                                    <span className="text-stone-500">(-{p.morale_loss})</span>
                                                </div>
                                            )}
                                        </div>
                                    ))}
                                </div>
                            </div>
                        )}
                    </div>
                </div>

                {/* Battle Log */}
                <div className="rounded-xl border-2 border-stone-600/50 bg-stone-800/30 p-4">
                    <div className="mb-4 flex items-center gap-2">
                        <Calendar className="h-5 w-5 text-amber-400" />
                        <h2 className="font-pixel text-lg text-amber-300">Battle Log</h2>
                    </div>

                    {battle.battle_log.length > 0 ? (
                        <div className="max-h-80 space-y-2 overflow-y-auto">
                            {[...battle.battle_log].reverse().map((entry, index) => (
                                <div
                                    key={index}
                                    className="rounded-lg bg-stone-900/50 p-3"
                                >
                                    <div className="mb-1 font-pixel text-xs text-amber-400">
                                        Day {entry.day}
                                    </div>
                                    <div className="font-pixel text-sm text-stone-300">
                                        {entry.description}
                                    </div>
                                    {(entry.attacker_casualties || entry.defender_casualties) && (
                                        <div className="mt-2 flex gap-4 font-pixel text-[10px]">
                                            {entry.attacker_casualties !== undefined && entry.attacker_casualties > 0 && (
                                                <span className="text-red-400">
                                                    Attackers: -{entry.attacker_casualties}
                                                </span>
                                            )}
                                            {entry.defender_casualties !== undefined && entry.defender_casualties > 0 && (
                                                <span className="text-blue-400">
                                                    Defenders: -{entry.defender_casualties}
                                                </span>
                                            )}
                                        </div>
                                    )}
                                </div>
                            ))}
                        </div>
                    ) : (
                        <div className="rounded-lg bg-stone-900/50 p-4 text-center font-pixel text-xs text-stone-500">
                            {battle.is_ongoing
                                ? 'Battle is just beginning. No events recorded yet.'
                                : 'No battle log available.'}
                        </div>
                    )}
                </div>

                {/* Battle Summary (for ended battles) */}
                {battle.is_ended && (
                    <div className={`rounded-xl border-2 p-4 ${
                        battle.status === 'attacker_victory'
                            ? 'border-red-600/50 bg-red-900/20'
                            : battle.status === 'defender_victory'
                                ? 'border-blue-600/50 bg-blue-900/20'
                                : 'border-stone-600/50 bg-stone-800/30'
                    }`}>
                        <div className="text-center">
                            <Trophy className={`mx-auto mb-2 h-12 w-12 ${
                                battle.status === 'attacker_victory'
                                    ? 'text-red-400'
                                    : battle.status === 'defender_victory'
                                        ? 'text-blue-400'
                                        : 'text-stone-400'
                            }`} />
                            <h3 className="font-pixel text-lg text-white">
                                {battle.status === 'attacker_victory'
                                    ? 'Attacker Victory!'
                                    : battle.status === 'defender_victory'
                                        ? 'Defender Victory!'
                                        : battle.status === 'draw'
                                            ? 'Battle Ended in Draw'
                                            : 'Battle Inconclusive'}
                            </h3>
                            <div className="mt-2 font-pixel text-sm text-stone-400">
                                Total Casualties: {battle.attacker_casualties + battle.defender_casualties}
                            </div>
                            {battle.ended_at && (
                                <div className="mt-1 font-pixel text-xs text-stone-500">
                                    Ended: {new Date(battle.ended_at).toLocaleDateString()}
                                </div>
                            )}
                        </div>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
