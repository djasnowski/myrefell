import { Head, Link, router, usePage } from '@inertiajs/react';
import {
    ArrowLeft,
    Calendar,
    Coins,
    Flag,
    Heart,
    MapPin,
    Minus,
    Package,
    Plus,
    Shield,
    Sword,
    Swords,
    Target,
    Users,
    XCircle,
} from 'lucide-react';
import { useState } from 'react';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

interface Location {
    type: string;
    id: number;
    name: string;
}

interface Commander {
    id: number;
    name: string;
}

interface ArmyUnit {
    id: number;
    unit_type: string;
    count: number;
    max_count: number;
    attack: number;
    defense: number;
    status: string;
    total_attack: number;
    total_defense: number;
}

interface SupplyLine {
    id: number;
    source: Location;
    status: 'active' | 'disrupted' | 'severed';
    supply_rate: number;
    effective_rate: number;
    distance: number;
    safety: number;
}

interface BattleHistory {
    id: number;
    name: string;
    status: string;
    outcome: string | null;
    casualties: number;
    started_at: string | null;
    ended_at: string | null;
}

interface NearbySettlement {
    type: string;
    id: number;
    name: string;
    distance: number | null;
    travel_days: number;
}

interface UnitTypeInfo {
    name: string;
    description: string;
    stats: {
        attack: number;
        defense: number;
        upkeep: number;
        morale_bonus: number;
    };
}

interface Army {
    id: number;
    name: string;
    status: string;
    morale: number;
    supplies: number;
    supplies_days_remaining: number;
    daily_supply_cost: number;
    gold_upkeep: number;
    total_troops: number;
    total_attack: number;
    total_defense: number;
    commander: Commander | null;
    location: Location;
    units: ArmyUnit[];
    composition: Record<string, number>;
    mustered_at: string | null;
}

interface PageProps {
    army: Army;
    supply_line: SupplyLine | null;
    battle_history: BattleHistory[];
    nearby_settlements: NearbySettlement[];
    unit_types: Record<string, UnitTypeInfo>;
    recruitment_costs: Record<string, number>;
    can_recruit: boolean;
    [key: string]: unknown;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Warfare', href: '#' },
    { title: 'Armies', href: '/warfare/armies' },
    { title: 'Army Details', href: '#' },
];

const statusColors: Record<string, { bg: string; text: string; label: string }> = {
    mustering: { bg: 'bg-blue-900/30', text: 'text-blue-400', label: 'Mustering' },
    marching: { bg: 'bg-amber-900/30', text: 'text-amber-400', label: 'Marching' },
    encamped: { bg: 'bg-green-900/30', text: 'text-green-400', label: 'Encamped' },
    besieging: { bg: 'bg-purple-900/30', text: 'text-purple-400', label: 'Besieging' },
    in_battle: { bg: 'bg-red-900/30', text: 'text-red-400', label: 'In Battle' },
    disbanded: { bg: 'bg-stone-900/30', text: 'text-stone-400', label: 'Disbanded' },
};

const supplyStatusColors: Record<string, { bg: string; text: string; label: string }> = {
    active: { bg: 'bg-green-900/30', text: 'text-green-400', label: 'Active' },
    disrupted: { bg: 'bg-yellow-900/30', text: 'text-yellow-400', label: 'Disrupted' },
    severed: { bg: 'bg-red-900/30', text: 'text-red-400', label: 'Severed' },
};

const unitTypeLabels: Record<string, string> = {
    levy: 'Levy',
    militia: 'Militia',
    men_at_arms: 'Men-at-Arms',
    knights: 'Knights',
    archers: 'Archers',
    crossbowmen: 'Crossbowmen',
    cavalry: 'Cavalry',
    siege_engineers: 'Siege Engineers',
};

const outcomeColors: Record<string, string> = {
    victory: 'text-green-400',
    defeat: 'text-red-400',
    routed: 'text-red-500',
    withdrew: 'text-yellow-400',
};

export default function ArmyShow() {
    const {
        army,
        supply_line,
        battle_history,
        nearby_settlements,
        unit_types,
        recruitment_costs,
        can_recruit,
    } = usePage<PageProps>().props;

    const [selectedUnitType, setSelectedUnitType] = useState<string>('');
    const [recruitCount, setRecruitCount] = useState(10);
    const [selectedDestination, setSelectedDestination] = useState<string>('');
    const [isRecruiting, setIsRecruiting] = useState(false);
    const [isMoving, setIsMoving] = useState(false);
    const [isDisbanding, setIsDisbanding] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const [success, setSuccess] = useState<string | null>(null);

    const status = statusColors[army.status] || statusColors.encamped;
    const canMove = !['in_battle', 'marching', 'disbanded'].includes(army.status);
    const canDisband = !['in_battle', 'disbanded'].includes(army.status);

    const recruitmentCost = selectedUnitType
        ? (recruitment_costs[selectedUnitType] || 0) * recruitCount
        : 0;

    const recruitSoldiers = async () => {
        if (!selectedUnitType || recruitCount < 1) return;

        setIsRecruiting(true);
        setError(null);

        try {
            const response = await fetch(`/warfare/armies/${army.id}/recruit`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content || '',
                },
                body: JSON.stringify({
                    unit_type: selectedUnitType,
                    count: recruitCount,
                }),
            });

            const data = await response.json();
            if (data.success) {
                setSuccess(data.message);
                setSelectedUnitType('');
                setRecruitCount(10);
                router.reload();
            } else {
                setError(data.message);
            }
        } catch {
            setError('Failed to recruit soldiers');
        } finally {
            setIsRecruiting(false);
        }
    };

    const moveArmy = async () => {
        if (!selectedDestination) return;

        const [locationType, locationId] = selectedDestination.split(':');

        setIsMoving(true);
        setError(null);

        try {
            const response = await fetch(`/warfare/armies/${army.id}/move`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content || '',
                },
                body: JSON.stringify({
                    location_type: locationType,
                    location_id: parseInt(locationId),
                }),
            });

            const data = await response.json();
            if (data.success) {
                setSuccess(data.message);
                setSelectedDestination('');
                router.reload();
            } else {
                setError(data.message);
            }
        } catch {
            setError('Failed to move army');
        } finally {
            setIsMoving(false);
        }
    };

    const disbandArmy = async () => {
        if (!confirm('Are you sure you want to disband this army? This action cannot be undone.')) {
            return;
        }

        setIsDisbanding(true);
        setError(null);

        try {
            const response = await fetch(`/warfare/armies/${army.id}/disband`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content || '',
                },
            });

            const data = await response.json();
            if (data.success) {
                setSuccess(data.message);
                router.visit('/warfare/armies');
            } else {
                setError(data.message);
            }
        } catch {
            setError('Failed to disband army');
        } finally {
            setIsDisbanding(false);
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Army: ${army.name}`} />
            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                {/* Back Link */}
                <Link
                    href="/warfare/armies"
                    className="flex items-center gap-2 font-pixel text-sm text-stone-400 transition hover:text-stone-300"
                >
                    <ArrowLeft className="h-4 w-4" />
                    Back to Armies
                </Link>

                {/* Header */}
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <div className="flex items-center gap-3">
                        <Swords className="h-8 w-8 text-red-400" />
                        <div>
                            <h1 className="font-pixel text-2xl text-white">{army.name}</h1>
                            <div className="flex items-center gap-3 font-pixel text-sm text-stone-400">
                                <span className="flex items-center gap-1">
                                    <Flag className="h-3 w-3 text-amber-400" />
                                    {army.commander?.name ?? 'No Commander'}
                                </span>
                                <span className="flex items-center gap-1">
                                    <MapPin className="h-3 w-3 text-green-400" />
                                    {army.location.name}
                                </span>
                            </div>
                        </div>
                    </div>
                    <div className="flex items-center gap-3">
                        <span className={`rounded px-3 py-1.5 font-pixel text-xs ${status.bg} ${status.text}`}>
                            {status.label}
                        </span>
                        {canDisband && (
                            <button
                                onClick={disbandArmy}
                                disabled={isDisbanding}
                                className="flex items-center gap-1 rounded border border-red-600/50 bg-red-900/20 px-3 py-1.5 font-pixel text-xs text-red-300 transition hover:bg-red-900/40 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                <XCircle className="h-3 w-3" />
                                {isDisbanding ? 'Disbanding...' : 'Disband'}
                            </button>
                        )}
                    </div>
                </div>

                {/* Messages */}
                {error && (
                    <div className="rounded-lg border border-red-500/50 bg-red-900/30 p-3 font-pixel text-sm text-red-300">
                        {error}
                    </div>
                )}
                {success && (
                    <div className="rounded-lg border border-green-500/50 bg-green-900/30 p-3 font-pixel text-sm text-green-300">
                        {success}
                    </div>
                )}

                {/* Morale & Supplies */}
                <div className="grid gap-4 md:grid-cols-2">
                    {/* Morale */}
                    <div className="rounded-xl border-2 border-red-500/30 bg-red-900/20 p-4">
                        <div className="mb-2 flex items-center gap-2">
                            <Heart className="h-5 w-5 text-red-400" />
                            <span className="font-pixel text-sm text-white">Morale</span>
                            <span className={`ml-auto font-pixel text-lg ${
                                army.morale >= 70 ? 'text-green-400' : army.morale >= 40 ? 'text-yellow-400' : 'text-red-400'
                            }`}>
                                {army.morale}%
                            </span>
                        </div>
                        <div className="h-3 overflow-hidden rounded-full bg-stone-800">
                            <div
                                className={`h-full transition-all ${
                                    army.morale >= 70 ? 'bg-green-500' : army.morale >= 40 ? 'bg-yellow-500' : 'bg-red-500'
                                }`}
                                style={{ width: `${army.morale}%` }}
                            />
                        </div>
                    </div>

                    {/* Supplies */}
                    <div className="rounded-xl border-2 border-amber-500/30 bg-amber-900/20 p-4">
                        <div className="mb-2 flex items-center gap-2">
                            <Package className="h-5 w-5 text-amber-400" />
                            <span className="font-pixel text-sm text-white">Supplies</span>
                            <span className={`ml-auto font-pixel text-lg ${
                                army.supplies_days_remaining >= 20 ? 'text-green-400' : army.supplies_days_remaining >= 10 ? 'text-yellow-400' : 'text-red-400'
                            }`}>
                                {army.supplies_days_remaining} days
                            </span>
                        </div>
                        <div className="h-3 overflow-hidden rounded-full bg-stone-800">
                            <div
                                className={`h-full transition-all ${
                                    army.supplies_days_remaining >= 20 ? 'bg-green-500' : army.supplies_days_remaining >= 10 ? 'bg-yellow-500' : 'bg-red-500'
                                }`}
                                style={{ width: `${Math.min(100, (army.supplies_days_remaining / 30) * 100)}%` }}
                            />
                        </div>
                        <div className="mt-2 font-pixel text-[10px] text-stone-400">
                            Daily consumption: {army.daily_supply_cost} | Upkeep: {army.gold_upkeep}g/day
                        </div>
                    </div>
                </div>

                {/* Main Grid */}
                <div className="grid gap-6 lg:grid-cols-2">
                    {/* Left Column */}
                    <div className="flex flex-col gap-6">
                        {/* Unit Composition */}
                        <div className="rounded-xl border-2 border-stone-600/50 bg-stone-800/30 p-4">
                            <div className="mb-4 flex items-center justify-between">
                                <h2 className="flex items-center gap-2 font-pixel text-lg text-white">
                                    <Users className="h-5 w-5 text-stone-400" />
                                    Unit Composition
                                </h2>
                                <span className="font-pixel text-sm text-stone-400">
                                    Total: {army.total_troops}
                                </span>
                            </div>

                            {army.units.length > 0 ? (
                                <div className="overflow-x-auto">
                                    <table className="w-full font-pixel text-xs">
                                        <thead>
                                            <tr className="border-b border-stone-700 text-stone-400">
                                                <th className="pb-2 text-left">Unit Type</th>
                                                <th className="pb-2 text-right">Count</th>
                                                <th className="pb-2 text-right">
                                                    <span className="flex items-center justify-end gap-1">
                                                        <Sword className="h-3 w-3 text-red-400" />
                                                        Attack
                                                    </span>
                                                </th>
                                                <th className="pb-2 text-right">
                                                    <span className="flex items-center justify-end gap-1">
                                                        <Shield className="h-3 w-3 text-blue-400" />
                                                        Defense
                                                    </span>
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {army.units.map((unit) => (
                                                <tr key={unit.id} className="border-b border-stone-700/50">
                                                    <td className="py-2 text-white">
                                                        {unitTypeLabels[unit.unit_type] || unit.unit_type}
                                                    </td>
                                                    <td className="py-2 text-right text-white">{unit.count}</td>
                                                    <td className="py-2 text-right text-red-300">{unit.total_attack}</td>
                                                    <td className="py-2 text-right text-blue-300">{unit.total_defense}</td>
                                                </tr>
                                            ))}
                                        </tbody>
                                        <tfoot>
                                            <tr className="font-bold text-white">
                                                <td className="pt-2">Total Combat Power</td>
                                                <td className="pt-2 text-right">{army.total_troops}</td>
                                                <td className="pt-2 text-right text-red-400">{army.total_attack}</td>
                                                <td className="pt-2 text-right text-blue-400">{army.total_defense}</td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            ) : (
                                <div className="py-4 text-center font-pixel text-sm text-stone-500">
                                    No units recruited yet
                                </div>
                            )}

                            {/* Recruit Form */}
                            {can_recruit && (
                                <div className="mt-4 border-t border-stone-700 pt-4">
                                    <h3 className="mb-3 font-pixel text-sm text-stone-300">Recruit Soldiers</h3>
                                    <div className="flex flex-wrap gap-2">
                                        <select
                                            value={selectedUnitType}
                                            onChange={(e) => setSelectedUnitType(e.target.value)}
                                            className="flex-1 rounded border border-stone-600 bg-stone-800 px-3 py-2 font-pixel text-xs text-white focus:border-red-500 focus:outline-none"
                                        >
                                            <option value="">Select unit type...</option>
                                            {Object.entries(unit_types).map(([key, info]) => (
                                                <option key={key} value={key}>
                                                    {info.name} ({recruitment_costs[key]}g each)
                                                </option>
                                            ))}
                                        </select>
                                        <div className="flex items-center gap-1">
                                            <button
                                                onClick={() => setRecruitCount(Math.max(1, recruitCount - 10))}
                                                className="rounded border border-stone-600 bg-stone-800 p-2 text-stone-400 hover:bg-stone-700"
                                            >
                                                <Minus className="h-3 w-3" />
                                            </button>
                                            <input
                                                type="number"
                                                value={recruitCount}
                                                onChange={(e) => setRecruitCount(Math.max(1, Math.min(100, parseInt(e.target.value) || 1)))}
                                                className="w-16 rounded border border-stone-600 bg-stone-800 px-2 py-2 text-center font-pixel text-xs text-white focus:border-red-500 focus:outline-none"
                                                min="1"
                                                max="100"
                                            />
                                            <button
                                                onClick={() => setRecruitCount(Math.min(100, recruitCount + 10))}
                                                className="rounded border border-stone-600 bg-stone-800 p-2 text-stone-400 hover:bg-stone-700"
                                            >
                                                <Plus className="h-3 w-3" />
                                            </button>
                                        </div>
                                        <button
                                            onClick={recruitSoldiers}
                                            disabled={!selectedUnitType || isRecruiting}
                                            className="flex items-center gap-1 rounded border border-green-600/50 bg-green-900/20 px-4 py-2 font-pixel text-xs text-green-300 transition hover:bg-green-900/40 disabled:cursor-not-allowed disabled:opacity-50"
                                        >
                                            <Plus className="h-3 w-3" />
                                            {isRecruiting ? 'Recruiting...' : `Recruit (${recruitmentCost}g)`}
                                        </button>
                                    </div>
                                    {selectedUnitType && unit_types[selectedUnitType] && (
                                        <p className="mt-2 font-pixel text-[10px] text-stone-500">
                                            {unit_types[selectedUnitType].description}
                                        </p>
                                    )}
                                </div>
                            )}
                        </div>

                        {/* Movement Orders */}
                        <div className="rounded-xl border-2 border-stone-600/50 bg-stone-800/30 p-4">
                            <h2 className="mb-4 flex items-center gap-2 font-pixel text-lg text-white">
                                <Target className="h-5 w-5 text-amber-400" />
                                Movement Orders
                            </h2>

                            {canMove ? (
                                <div className="flex flex-wrap gap-2">
                                    <select
                                        value={selectedDestination}
                                        onChange={(e) => setSelectedDestination(e.target.value)}
                                        className="flex-1 rounded border border-stone-600 bg-stone-800 px-3 py-2 font-pixel text-xs text-white focus:border-amber-500 focus:outline-none"
                                    >
                                        <option value="">Select destination...</option>
                                        {nearby_settlements.map((settlement) => (
                                            <option key={`${settlement.type}:${settlement.id}`} value={`${settlement.type}:${settlement.id}`}>
                                                {settlement.name} ({settlement.travel_days} day{settlement.travel_days !== 1 ? 's' : ''})
                                            </option>
                                        ))}
                                    </select>
                                    <button
                                        onClick={moveArmy}
                                        disabled={!selectedDestination || isMoving}
                                        className="flex items-center gap-1 rounded border border-amber-600/50 bg-amber-900/20 px-4 py-2 font-pixel text-xs text-amber-300 transition hover:bg-amber-900/40 disabled:cursor-not-allowed disabled:opacity-50"
                                    >
                                        <MapPin className="h-3 w-3" />
                                        {isMoving ? 'Marching...' : 'Begin March'}
                                    </button>
                                </div>
                            ) : (
                                <p className="font-pixel text-sm text-stone-500">
                                    {army.status === 'marching' && 'Army is currently marching.'}
                                    {army.status === 'in_battle' && 'Cannot move while in battle.'}
                                    {army.status === 'disbanded' && 'Army has been disbanded.'}
                                </p>
                            )}

                            {nearby_settlements.length > 0 && canMove && (
                                <div className="mt-3 font-pixel text-[10px] text-stone-500">
                                    Nearby: {nearby_settlements.slice(0, 3).map(s => `${s.name} (${s.travel_days}d)`).join(', ')}
                                </div>
                            )}
                        </div>
                    </div>

                    {/* Right Column */}
                    <div className="flex flex-col gap-6">
                        {/* Supply Line */}
                        <div className="rounded-xl border-2 border-stone-600/50 bg-stone-800/30 p-4">
                            <h2 className="mb-4 flex items-center gap-2 font-pixel text-lg text-white">
                                <Package className="h-5 w-5 text-green-400" />
                                Supply Line
                            </h2>

                            {supply_line ? (
                                <div className="space-y-2">
                                    <div className="flex items-center justify-between font-pixel text-xs">
                                        <span className="text-stone-400">Source:</span>
                                        <span className="text-white">{supply_line.source.name}</span>
                                    </div>
                                    <div className="flex items-center justify-between font-pixel text-xs">
                                        <span className="text-stone-400">Status:</span>
                                        <span className={`rounded px-2 py-0.5 ${supplyStatusColors[supply_line.status]?.bg || ''} ${supplyStatusColors[supply_line.status]?.text || 'text-white'}`}>
                                            {supplyStatusColors[supply_line.status]?.label || supply_line.status}
                                        </span>
                                    </div>
                                    <div className="flex items-center justify-between font-pixel text-xs">
                                        <span className="text-stone-400">Supply Rate:</span>
                                        <span className="text-white">{supply_line.effective_rate}/day</span>
                                    </div>
                                    <div className="flex items-center justify-between font-pixel text-xs">
                                        <span className="text-stone-400">Route Safety:</span>
                                        <span className={supply_line.safety >= 70 ? 'text-green-400' : supply_line.safety >= 40 ? 'text-yellow-400' : 'text-red-400'}>
                                            {supply_line.safety}%
                                        </span>
                                    </div>
                                </div>
                            ) : (
                                <p className="font-pixel text-sm text-stone-500">
                                    No supply line established
                                </p>
                            )}
                        </div>

                        {/* Battle History */}
                        <div className="rounded-xl border-2 border-stone-600/50 bg-stone-800/30 p-4">
                            <h2 className="mb-4 flex items-center gap-2 font-pixel text-lg text-white">
                                <Swords className="h-5 w-5 text-red-400" />
                                Battle History
                            </h2>

                            {battle_history.length > 0 ? (
                                <div className="space-y-3">
                                    {battle_history.map((battle) => (
                                        <div
                                            key={battle.id}
                                            className="rounded-lg bg-stone-900/50 p-3"
                                        >
                                            <div className="flex items-start justify-between">
                                                <div>
                                                    <div className="font-pixel text-sm text-white">
                                                        {battle.name}
                                                    </div>
                                                    {battle.started_at && (
                                                        <div className="flex items-center gap-1 font-pixel text-[10px] text-stone-500">
                                                            <Calendar className="h-3 w-3" />
                                                            {new Date(battle.started_at).toLocaleDateString()}
                                                        </div>
                                                    )}
                                                </div>
                                                {battle.outcome && (
                                                    <span className={`font-pixel text-xs capitalize ${outcomeColors[battle.outcome] || 'text-stone-400'}`}>
                                                        {battle.outcome}
                                                    </span>
                                                )}
                                            </div>
                                            {battle.casualties > 0 && (
                                                <div className="mt-1 font-pixel text-[10px] text-red-400">
                                                    Casualties: {battle.casualties}
                                                </div>
                                            )}
                                        </div>
                                    ))}
                                </div>
                            ) : (
                                <p className="font-pixel text-sm text-stone-500">
                                    No battles fought yet
                                </p>
                            )}
                        </div>

                        {/* Army Info */}
                        <div className="rounded-xl border-2 border-stone-600/50 bg-stone-800/30 p-4">
                            <h2 className="mb-4 flex items-center gap-2 font-pixel text-lg text-white">
                                <Coins className="h-5 w-5 text-yellow-400" />
                                Army Info
                            </h2>

                            <div className="space-y-2">
                                <div className="flex items-center justify-between font-pixel text-xs">
                                    <span className="text-stone-400">Daily Upkeep:</span>
                                    <span className="text-yellow-300">{army.gold_upkeep}g/day</span>
                                </div>
                                <div className="flex items-center justify-between font-pixel text-xs">
                                    <span className="text-stone-400">Daily Supply Cost:</span>
                                    <span className="text-amber-300">{army.daily_supply_cost}/day</span>
                                </div>
                                {army.mustered_at && (
                                    <div className="flex items-center justify-between font-pixel text-xs">
                                        <span className="text-stone-400">Mustered:</span>
                                        <span className="text-white">
                                            {new Date(army.mustered_at).toLocaleDateString()}
                                        </span>
                                    </div>
                                )}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
