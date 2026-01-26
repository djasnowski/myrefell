import { Head, Link, router, usePage } from '@inertiajs/react';
import {
    AlertTriangle,
    ArrowLeft,
    Castle,
    Check,
    Crown,
    Flag,
    Shield,
    Skull,
    Sword,
    Swords,
    Target,
    Users,
} from 'lucide-react';
import { useState } from 'react';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

interface Target {
    id: number;
    type: 'kingdom' | 'barony';
    name: string;
    ruler_name: string;
    kingdom_name?: string;
    kingdom_id?: number;
    estimated_troops: number;
    allies: string[];
}

interface CasusBelli {
    value: string;
    label: string;
    description: string;
    legitimacy_impact: number;
}

interface WarGoalType {
    value: string;
    label: string;
    description: string;
}

interface PlayerArmy {
    id: number;
    name: string;
    total_troops: number;
    total_attack: number;
    total_defense: number;
    status: string;
}

interface PotentialAlly {
    id: number;
    type: string;
    name: string;
    estimated_troops: number;
    likelihood: string;
}

interface PageProps {
    potential_targets: {
        kingdoms: Target[];
        baronies: Target[];
    };
    casus_belli_types: CasusBelli[];
    war_goal_types: WarGoalType[];
    player_armies: PlayerArmy[];
    player_strength: {
        total_troops: number;
        total_attack: number;
    };
    potential_allies: PotentialAlly[];
    user_kingdom: { id: number; name: string } | null;
    user_barony: { id: number; name: string; kingdom_id: number } | null;
    [key: string]: unknown;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Warfare', href: '#' },
    { title: 'Wars', href: '/warfare/wars' },
    { title: 'Declare War', href: '#' },
];

const casusBelliIcons: Record<string, typeof Sword> = {
    conquest: Sword,
    claim: Crown,
    holy_war: Target,
    raid: Skull,
    rebellion: Flag,
};

export default function DeclareWar() {
    const {
        potential_targets,
        casus_belli_types,
        war_goal_types,
        player_armies,
        player_strength,
        potential_allies,
        user_kingdom,
        user_barony,
    } = usePage<PageProps>().props;

    const [selectedTargetType, setSelectedTargetType] = useState<'kingdom' | 'barony' | ''>('');
    const [selectedTargetId, setSelectedTargetId] = useState<number | null>(null);
    const [selectedCasusBelli, setSelectedCasusBelli] = useState<string>('');
    const [selectedWarGoals, setSelectedWarGoals] = useState<string[]>([]);
    const [warName, setWarName] = useState<string>('');
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [error, setError] = useState<string | null>(null);

    const allTargets = [
        ...potential_targets.kingdoms,
        ...potential_targets.baronies,
    ];

    const selectedTarget = allTargets.find(
        (t) => t.type === selectedTargetType && t.id === selectedTargetId
    );

    const selectedCasusBelliInfo = casus_belli_types.find((c) => c.value === selectedCasusBelli);

    const canDeclareWar = user_kingdom || user_barony;

    const toggleWarGoal = (goalValue: string) => {
        setSelectedWarGoals((prev) =>
            prev.includes(goalValue)
                ? prev.filter((g) => g !== goalValue)
                : [...prev, goalValue]
        );
    };

    const estimateWinChance = (): number => {
        if (!selectedTarget) return 0;
        const enemyStrength = selectedTarget.estimated_troops || 1;
        const playerStrengthValue = player_strength.total_troops || 1;
        const ratio = playerStrengthValue / enemyStrength;
        return Math.min(95, Math.max(5, Math.round(ratio * 50)));
    };

    const handleDeclareWar = async () => {
        if (!selectedTargetType || !selectedTargetId || !selectedCasusBelli || selectedWarGoals.length === 0) {
            setError('Please select a target, casus belli, and at least one war goal.');
            return;
        }

        setIsSubmitting(true);
        setError(null);

        try {
            const response = await fetch('/warfare/declare', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector<HTMLMetaElement>(
                        'meta[name="csrf-token"]'
                    )?.content || '',
                },
                body: JSON.stringify({
                    target_type: selectedTargetType,
                    target_id: selectedTargetId,
                    casus_belli: selectedCasusBelli,
                    war_goals: selectedWarGoals,
                    war_name: warName || null,
                }),
            });

            const data = await response.json();

            if (data.success) {
                router.visit(`/warfare/wars/${data.war_id}`);
            } else {
                setError(data.message || 'Failed to declare war.');
            }
        } catch {
            setError('An error occurred while declaring war.');
        } finally {
            setIsSubmitting(false);
        }
    };

    if (!canDeclareWar) {
        return (
            <AppLayout breadcrumbs={breadcrumbs}>
                <Head title="Declare War" />
                <div className="flex h-full flex-1 flex-col gap-6 p-4">
                    <Link
                        href="/warfare/wars"
                        className="flex items-center gap-2 font-pixel text-sm text-stone-400 transition hover:text-stone-300"
                    >
                        <ArrowLeft className="h-4 w-4" />
                        Back to Wars
                    </Link>

                    <div className="rounded-xl border-2 border-red-500/30 bg-red-900/20 p-8 text-center">
                        <AlertTriangle className="mx-auto mb-4 h-12 w-12 text-red-400" />
                        <h1 className="mb-2 font-pixel text-xl text-white">Cannot Declare War</h1>
                        <p className="font-pixel text-sm text-stone-400">
                            You must rule a kingdom or barony to declare war.
                        </p>
                    </div>
                </div>
            </AppLayout>
        );
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Declare War" />
            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                {/* Back Link */}
                <Link
                    href="/warfare/wars"
                    className="flex items-center gap-2 font-pixel text-sm text-stone-400 transition hover:text-stone-300"
                >
                    <ArrowLeft className="h-4 w-4" />
                    Back to Wars
                </Link>

                {/* Header */}
                <div className="flex items-center gap-3">
                    <Swords className="h-8 w-8 text-red-400" />
                    <div>
                        <h1 className="font-pixel text-2xl text-white">Declare War</h1>
                        <p className="font-pixel text-sm text-stone-400">
                            As ruler of {user_kingdom?.name || user_barony?.name}
                        </p>
                    </div>
                </div>

                {/* Error Display */}
                {error && (
                    <div className="rounded-xl border-2 border-red-500/30 bg-red-900/20 p-4">
                        <div className="flex items-center gap-2">
                            <AlertTriangle className="h-5 w-5 text-red-400" />
                            <span className="font-pixel text-sm text-red-300">{error}</span>
                        </div>
                    </div>
                )}

                <div className="grid gap-6 lg:grid-cols-2">
                    {/* Left Column: Target Selection */}
                    <div className="space-y-6">
                        {/* Target Selection */}
                        <div className="rounded-xl border-2 border-stone-600/50 bg-stone-800/30 p-4">
                            <h2 className="mb-4 flex items-center gap-2 font-pixel text-lg text-white">
                                <Target className="h-5 w-5 text-red-400" />
                                Select Target
                            </h2>

                            {/* Target Type Tabs */}
                            <div className="mb-4 flex gap-2">
                                <button
                                    onClick={() => {
                                        setSelectedTargetType('kingdom');
                                        setSelectedTargetId(null);
                                    }}
                                    className={`flex items-center gap-2 rounded border px-3 py-1.5 font-pixel text-xs transition ${
                                        selectedTargetType === 'kingdom'
                                            ? 'border-amber-500/50 bg-amber-900/30 text-amber-300'
                                            : 'border-stone-600/50 bg-stone-900/20 text-stone-400 hover:bg-stone-900/40'
                                    }`}
                                >
                                    <Crown className="h-3 w-3" />
                                    Kingdoms ({potential_targets.kingdoms.length})
                                </button>
                                <button
                                    onClick={() => {
                                        setSelectedTargetType('barony');
                                        setSelectedTargetId(null);
                                    }}
                                    className={`flex items-center gap-2 rounded border px-3 py-1.5 font-pixel text-xs transition ${
                                        selectedTargetType === 'barony'
                                            ? 'border-amber-500/50 bg-amber-900/30 text-amber-300'
                                            : 'border-stone-600/50 bg-stone-900/20 text-stone-400 hover:bg-stone-900/40'
                                    }`}
                                >
                                    <Castle className="h-3 w-3" />
                                    Baronies ({potential_targets.baronies.length})
                                </button>
                            </div>

                            {/* Target List */}
                            <div className="max-h-64 space-y-2 overflow-y-auto">
                                {selectedTargetType === 'kingdom' &&
                                    potential_targets.kingdoms.map((target) => (
                                        <button
                                            key={`kingdom-${target.id}`}
                                            onClick={() => setSelectedTargetId(target.id)}
                                            className={`w-full rounded-lg p-3 text-left transition ${
                                                selectedTargetId === target.id
                                                    ? 'border-2 border-red-500/50 bg-red-900/30'
                                                    : 'bg-stone-900/50 hover:bg-stone-900/70'
                                            }`}
                                        >
                                            <div className="flex items-center justify-between">
                                                <div className="flex items-center gap-2">
                                                    <Crown className="h-4 w-4 text-amber-400" />
                                                    <span className="font-pixel text-sm text-white">{target.name}</span>
                                                </div>
                                                {selectedTargetId === target.id && (
                                                    <Check className="h-4 w-4 text-green-400" />
                                                )}
                                            </div>
                                            <div className="mt-1 font-pixel text-[10px] text-stone-400">
                                                Ruler: {target.ruler_name} | Military: ~{target.estimated_troops} soldiers
                                            </div>
                                            {target.allies.length > 0 && (
                                                <div className="mt-1 font-pixel text-[10px] text-stone-500">
                                                    Allies: {target.allies.join(', ')}
                                                </div>
                                            )}
                                        </button>
                                    ))}

                                {selectedTargetType === 'barony' &&
                                    potential_targets.baronies.map((target) => (
                                        <button
                                            key={`barony-${target.id}`}
                                            onClick={() => setSelectedTargetId(target.id)}
                                            className={`w-full rounded-lg p-3 text-left transition ${
                                                selectedTargetId === target.id
                                                    ? 'border-2 border-red-500/50 bg-red-900/30'
                                                    : 'bg-stone-900/50 hover:bg-stone-900/70'
                                            }`}
                                        >
                                            <div className="flex items-center justify-between">
                                                <div className="flex items-center gap-2">
                                                    <Castle className="h-4 w-4 text-purple-400" />
                                                    <span className="font-pixel text-sm text-white">{target.name}</span>
                                                </div>
                                                {selectedTargetId === target.id && (
                                                    <Check className="h-4 w-4 text-green-400" />
                                                )}
                                            </div>
                                            <div className="mt-1 font-pixel text-[10px] text-stone-400">
                                                Baron: {target.ruler_name} | Kingdom: {target.kingdom_name}
                                            </div>
                                            <div className="mt-1 font-pixel text-[10px] text-stone-500">
                                                Military: ~{target.estimated_troops} soldiers
                                            </div>
                                        </button>
                                    ))}

                                {!selectedTargetType && (
                                    <p className="font-pixel text-sm text-stone-500">
                                        Select a target type above to see available targets.
                                    </p>
                                )}
                            </div>
                        </div>

                        {/* Target Info Card */}
                        {selectedTarget && (
                            <div className="rounded-xl border-2 border-red-500/30 bg-red-900/20 p-4">
                                <h3 className="mb-3 font-pixel text-sm text-red-400">Target Information</h3>
                                <div className="space-y-2 font-pixel text-xs">
                                    <div className="flex justify-between">
                                        <span className="text-stone-400">Name:</span>
                                        <span className="text-white">{selectedTarget.name}</span>
                                    </div>
                                    <div className="flex justify-between">
                                        <span className="text-stone-400">Ruler:</span>
                                        <span className="text-white">{selectedTarget.ruler_name}</span>
                                    </div>
                                    <div className="flex justify-between">
                                        <span className="text-stone-400">Military:</span>
                                        <span className="text-white">~{selectedTarget.estimated_troops} soldiers</span>
                                    </div>
                                    {selectedTarget.allies.length > 0 && (
                                        <div className="flex justify-between">
                                            <span className="text-stone-400">Allies:</span>
                                            <span className="text-white">{selectedTarget.allies.join(', ')}</span>
                                        </div>
                                    )}
                                </div>
                            </div>
                        )}
                    </div>

                    {/* Right Column: Casus Belli & Goals */}
                    <div className="space-y-6">
                        {/* Casus Belli Selection */}
                        <div className="rounded-xl border-2 border-stone-600/50 bg-stone-800/30 p-4">
                            <h2 className="mb-4 flex items-center gap-2 font-pixel text-lg text-white">
                                <Flag className="h-5 w-5 text-amber-400" />
                                Casus Belli (Justification)
                            </h2>

                            <div className="space-y-2">
                                {casus_belli_types.map((cb) => {
                                    const Icon = casusBelliIcons[cb.value] || Sword;
                                    return (
                                        <button
                                            key={cb.value}
                                            onClick={() => setSelectedCasusBelli(cb.value)}
                                            className={`w-full rounded-lg p-3 text-left transition ${
                                                selectedCasusBelli === cb.value
                                                    ? 'border-2 border-amber-500/50 bg-amber-900/30'
                                                    : 'bg-stone-900/50 hover:bg-stone-900/70'
                                            }`}
                                        >
                                            <div className="flex items-center justify-between">
                                                <div className="flex items-center gap-2">
                                                    <Icon className="h-4 w-4 text-amber-400" />
                                                    <span className="font-pixel text-sm text-white">{cb.label}</span>
                                                </div>
                                                <span
                                                    className={`font-pixel text-[10px] ${
                                                        cb.legitimacy_impact >= 0 ? 'text-green-400' : 'text-red-400'
                                                    }`}
                                                >
                                                    {cb.legitimacy_impact >= 0 ? '+' : ''}
                                                    {cb.legitimacy_impact} legitimacy
                                                </span>
                                            </div>
                                            <p className="mt-1 font-pixel text-[10px] text-stone-400">{cb.description}</p>
                                        </button>
                                    );
                                })}
                            </div>
                        </div>

                        {/* War Goals Selection */}
                        <div className="rounded-xl border-2 border-stone-600/50 bg-stone-800/30 p-4">
                            <h2 className="mb-4 flex items-center gap-2 font-pixel text-lg text-white">
                                <Target className="h-5 w-5 text-green-400" />
                                War Goals
                            </h2>

                            <div className="space-y-2">
                                {war_goal_types.map((goal) => (
                                    <button
                                        key={goal.value}
                                        onClick={() => toggleWarGoal(goal.value)}
                                        className={`w-full rounded-lg p-3 text-left transition ${
                                            selectedWarGoals.includes(goal.value)
                                                ? 'border-2 border-green-500/50 bg-green-900/30'
                                                : 'bg-stone-900/50 hover:bg-stone-900/70'
                                        }`}
                                    >
                                        <div className="flex items-center justify-between">
                                            <span className="font-pixel text-sm text-white">{goal.label}</span>
                                            {selectedWarGoals.includes(goal.value) && (
                                                <Check className="h-4 w-4 text-green-400" />
                                            )}
                                        </div>
                                        <p className="mt-1 font-pixel text-[10px] text-stone-400">{goal.description}</p>
                                    </button>
                                ))}
                            </div>
                        </div>
                    </div>
                </div>

                {/* Your Forces Section */}
                <div className="rounded-xl border-2 border-stone-600/50 bg-stone-800/30 p-4">
                    <h2 className="mb-4 flex items-center gap-2 font-pixel text-lg text-white">
                        <Shield className="h-5 w-5 text-blue-400" />
                        Your Forces
                    </h2>

                    <div className="grid gap-4 md:grid-cols-2">
                        {/* Army Summary */}
                        <div className="rounded-lg bg-stone-900/50 p-3">
                            <div className="mb-2 flex items-center gap-2">
                                <Users className="h-4 w-4 text-stone-400" />
                                <span className="font-pixel text-sm text-white">Total Military Strength</span>
                            </div>
                            <div className="grid grid-cols-2 gap-2 font-pixel text-xs">
                                <div>
                                    <span className="text-stone-400">Soldiers: </span>
                                    <span className="text-white">{player_strength.total_troops}</span>
                                </div>
                                <div>
                                    <span className="text-stone-400">Attack Power: </span>
                                    <span className="text-white">{player_strength.total_attack}</span>
                                </div>
                            </div>
                        </div>

                        {/* Win Chance Estimate */}
                        {selectedTarget && (
                            <div className="rounded-lg bg-stone-900/50 p-3">
                                <div className="mb-2 font-pixel text-sm text-white">Estimated Chance of Victory</div>
                                <div className="flex items-center gap-3">
                                    <div className="h-3 flex-1 overflow-hidden rounded-full bg-stone-700">
                                        <div
                                            className={`h-full transition-all duration-300 ${
                                                estimateWinChance() >= 60
                                                    ? 'bg-green-500'
                                                    : estimateWinChance() >= 40
                                                      ? 'bg-yellow-500'
                                                      : 'bg-red-500'
                                            }`}
                                            style={{ width: `${estimateWinChance()}%` }}
                                        />
                                    </div>
                                    <span
                                        className={`font-pixel text-sm ${
                                            estimateWinChance() >= 60
                                                ? 'text-green-400'
                                                : estimateWinChance() >= 40
                                                  ? 'text-yellow-400'
                                                  : 'text-red-400'
                                        }`}
                                    >
                                        {estimateWinChance()}%
                                    </span>
                                </div>
                            </div>
                        )}
                    </div>

                    {/* Armies List */}
                    {player_armies.length > 0 && (
                        <div className="mt-4">
                            <div className="mb-2 font-pixel text-xs text-stone-400">Your Armies:</div>
                            <div className="grid gap-2 md:grid-cols-2 lg:grid-cols-3">
                                {player_armies.map((army) => (
                                    <div key={army.id} className="rounded bg-stone-900/30 p-2">
                                        <div className="flex items-center justify-between">
                                            <span className="font-pixel text-xs text-white">{army.name}</span>
                                            <span className="font-pixel text-[10px] text-stone-500">{army.status}</span>
                                        </div>
                                        <div className="mt-1 font-pixel text-[10px] text-stone-400">
                                            {army.total_troops} troops | ATK: {army.total_attack} | DEF: {army.total_defense}
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </div>
                    )}

                    {player_armies.length === 0 && (
                        <div className="mt-4 rounded-lg border border-amber-500/30 bg-amber-900/20 p-3">
                            <div className="flex items-center gap-2">
                                <AlertTriangle className="h-4 w-4 text-amber-400" />
                                <span className="font-pixel text-xs text-amber-300">
                                    You have no armies! Raise an army before declaring war.
                                </span>
                            </div>
                        </div>
                    )}
                </div>

                {/* Potential Allies */}
                {potential_allies.length > 0 && (
                    <div className="rounded-xl border-2 border-stone-600/50 bg-stone-800/30 p-4">
                        <h2 className="mb-4 flex items-center gap-2 font-pixel text-lg text-white">
                            <Users className="h-5 w-5 text-purple-400" />
                            Potential Allies Who May Join
                        </h2>

                        <div className="grid gap-2 md:grid-cols-2 lg:grid-cols-3">
                            {potential_allies.map((ally) => (
                                <div key={`${ally.type}-${ally.id}`} className="rounded-lg bg-stone-900/50 p-3">
                                    <div className="flex items-center gap-2">
                                        <Crown className="h-4 w-4 text-amber-400" />
                                        <span className="font-pixel text-sm text-white">{ally.name}</span>
                                    </div>
                                    <div className="mt-1 font-pixel text-[10px] text-stone-400">
                                        +{ally.estimated_troops} soldiers
                                    </div>
                                    <div className="mt-1 font-pixel text-[10px] text-stone-500">{ally.likelihood}</div>
                                </div>
                            ))}
                        </div>
                    </div>
                )}

                {/* War Name (Optional) */}
                <div className="rounded-xl border-2 border-stone-600/50 bg-stone-800/30 p-4">
                    <h2 className="mb-4 flex items-center gap-2 font-pixel text-lg text-white">
                        <Swords className="h-5 w-5 text-stone-400" />
                        War Name (Optional)
                    </h2>
                    <input
                        type="text"
                        value={warName}
                        onChange={(e) => setWarName(e.target.value)}
                        placeholder="Leave blank to auto-generate"
                        className="w-full rounded border border-stone-600/50 bg-stone-900/50 px-3 py-2 font-pixel text-sm text-white placeholder-stone-500 focus:border-amber-500/50 focus:outline-none"
                        maxLength={255}
                    />
                </div>

                {/* Warning & Submit */}
                <div className="rounded-xl border-2 border-amber-500/30 bg-amber-900/20 p-4">
                    <div className="mb-4 flex items-start gap-3">
                        <AlertTriangle className="h-5 w-5 shrink-0 text-amber-400" />
                        <div>
                            <p className="font-pixel text-sm text-amber-300">
                                Warning: This will begin active warfare.
                            </p>
                            {selectedCasusBelliInfo && (
                                <p className="mt-1 font-pixel text-xs text-stone-400">
                                    Your legitimacy will change by{' '}
                                    <span
                                        className={
                                            selectedCasusBelliInfo.legitimacy_impact >= 0
                                                ? 'text-green-400'
                                                : 'text-red-400'
                                        }
                                    >
                                        {selectedCasusBelliInfo.legitimacy_impact >= 0 ? '+' : ''}
                                        {selectedCasusBelliInfo.legitimacy_impact}
                                    </span>
                                    .
                                </p>
                            )}
                            <p className="mt-1 font-pixel text-xs text-stone-400">
                                Any existing truces with the target will be broken.
                            </p>
                        </div>
                    </div>

                    <div className="flex justify-end gap-3">
                        <Link
                            href="/warfare/wars"
                            className="rounded border border-stone-600/50 bg-stone-900/20 px-4 py-2 font-pixel text-xs text-stone-300 transition hover:bg-stone-900/40"
                        >
                            Cancel
                        </Link>
                        <button
                            onClick={handleDeclareWar}
                            disabled={
                                isSubmitting ||
                                !selectedTargetType ||
                                !selectedTargetId ||
                                !selectedCasusBelli ||
                                selectedWarGoals.length === 0
                            }
                            className="flex items-center gap-2 rounded border border-red-600/50 bg-red-900/20 px-4 py-2 font-pixel text-xs text-red-300 transition hover:bg-red-900/40 disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            <Swords className="h-4 w-4" />
                            {isSubmitting ? 'Declaring War...' : 'Declare War'}
                        </button>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
