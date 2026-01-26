import { Head, Link, router } from '@inertiajs/react';
import {
    AlertTriangle,
    ArrowLeft,
    Check,
    Scale,
    Shield,
    Star,
    User,
    UserMinus,
    Users,
} from 'lucide-react';
import { useState } from 'react';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

interface SuccessionMember {
    id: number;
    name: string;
    first_name: string;
    generation: number;
    gender: string;
    age: number | null;
    is_legitimate: boolean;
    is_disinherited: boolean;
    is_heir: boolean;
    status: string;
}

interface SuccessionType {
    value: string;
    label: string;
    description: string;
}

interface GenderLaw {
    value: string;
    label: string;
    description: string;
}

interface SuccessionRules {
    succession_type: string;
    gender_law: string;
    allows_bastards: boolean;
    allows_adoption: boolean;
    minimum_age: number;
}

interface Dynasty {
    id: number;
    name: string;
    prestige: number;
}

interface Props {
    dynasty: Dynasty;
    rules: SuccessionRules | null;
    succession_line: SuccessionMember[];
    is_head: boolean;
    succession_types: SuccessionType[];
    gender_laws: GenderLaw[];
    change_cost: number;
    disinherit_cost: number;
}

export default function Succession({
    dynasty,
    rules,
    succession_line,
    is_head,
    succession_types,
    gender_laws,
    change_cost,
    disinherit_cost,
}: Props) {
    const [showChangeModal, setShowChangeModal] = useState(false);
    const [showDisinheritModal, setShowDisinheritModal] = useState<SuccessionMember | null>(null);
    const [saving, setSaving] = useState(false);

    // Form state
    const [formData, setFormData] = useState({
        succession_type: rules?.succession_type || 'primogeniture',
        gender_law: rules?.gender_law || 'agnatic-cognatic',
        allows_bastards: rules?.allows_bastards || false,
        allows_adoption: rules?.allows_adoption || false,
        minimum_age: rules?.minimum_age || 16,
    });
    const [disinheritReason, setDisinheritReason] = useState('');

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Dynasty', href: '/dynasty' },
        { title: 'Succession', href: '/dynasty/succession' },
    ];

    const handleSaveRules = () => {
        if (saving) return;
        setSaving(true);
        router.put('/dynasty/succession', formData, {
            preserveScroll: true,
            onFinish: () => {
                setSaving(false);
                setShowChangeModal(false);
            },
        });
    };

    const handleDisinherit = () => {
        if (!showDisinheritModal || saving) return;
        setSaving(true);
        router.post(`/dynasty/succession/disinherit/${showDisinheritModal.id}`, {
            reason: disinheritReason || null,
        }, {
            preserveScroll: true,
            onFinish: () => {
                setSaving(false);
                setShowDisinheritModal(null);
                setDisinheritReason('');
            },
        });
    };

    const getSuccessionTypeLabel = (value: string) => {
        return succession_types.find(t => t.value === value)?.label || value;
    };

    const getSuccessionTypeDesc = (value: string) => {
        return succession_types.find(t => t.value === value)?.description || '';
    };

    const getGenderLawLabel = (value: string) => {
        return gender_laws.find(l => l.value === value)?.label || value;
    };

    const getGenderLawDesc = (value: string) => {
        return gender_laws.find(l => l.value === value)?.description || '';
    };

    if (!rules) {
        return (
            <AppLayout breadcrumbs={breadcrumbs}>
                <Head title="Succession Rules" />
                <div className="flex h-full flex-1 flex-col items-center justify-center p-4">
                    <Scale className="mb-4 h-12 w-12 text-stone-600" />
                    <h1 className="font-pixel text-xl text-stone-400">No Succession Rules</h1>
                    <p className="mt-2 font-pixel text-xs text-stone-500">
                        Your dynasty has no succession rules configured.
                    </p>
                    <Link
                        href="/dynasty"
                        className="mt-4 flex items-center gap-2 rounded-lg border border-stone-600 px-4 py-2 font-pixel text-xs text-stone-400 transition hover:bg-stone-700"
                    >
                        <ArrowLeft className="h-4 w-4" />
                        Back to Dynasty
                    </Link>
                </div>
            </AppLayout>
        );
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Succession - House ${dynasty.name}`} />
            <div className="flex h-full flex-1 flex-col overflow-auto p-4">
                {/* Header */}
                <div className="mb-6 flex items-center justify-between">
                    <div className="flex items-center gap-4">
                        <Link
                            href="/dynasty"
                            className="rounded-lg border border-stone-700 p-2 transition hover:bg-stone-700"
                        >
                            <ArrowLeft className="h-5 w-5 text-stone-400" />
                        </Link>
                        <div>
                            <h1 className="font-pixel text-xl text-amber-400">Succession Rules</h1>
                            <p className="font-pixel text-xs text-stone-500">House {dynasty.name}</p>
                        </div>
                    </div>
                    {is_head && (
                        <button
                            onClick={() => setShowChangeModal(true)}
                            className="flex items-center gap-2 rounded-lg border-2 border-purple-600/50 bg-purple-900/20 px-4 py-2 font-pixel text-xs text-purple-400 transition hover:bg-purple-900/30"
                        >
                            <Scale className="h-4 w-4" />
                            Change Rules (-{change_cost} prestige)
                        </button>
                    )}
                </div>

                <div className="mx-auto w-full max-w-4xl">
                    <div className="grid gap-4 lg:grid-cols-2">
                        {/* Current Rules */}
                        <div className="rounded-xl border-2 border-stone-700 bg-stone-800/50 p-4">
                            <h2 className="mb-4 flex items-center gap-2 font-pixel text-sm text-stone-300">
                                <Shield className="h-4 w-4 text-purple-400" />
                                Current Rules
                            </h2>

                            <div className="space-y-4">
                                <div className="rounded-lg border border-stone-700 bg-stone-800/30 p-3">
                                    <div className="font-pixel text-[10px] text-stone-500">Succession Type</div>
                                    <div className="font-pixel text-sm text-purple-400">
                                        {getSuccessionTypeLabel(rules.succession_type)}
                                    </div>
                                    <div className="mt-1 font-pixel text-[10px] text-stone-500">
                                        ({getSuccessionTypeDesc(rules.succession_type)})
                                    </div>
                                </div>

                                <div className="rounded-lg border border-stone-700 bg-stone-800/30 p-3">
                                    <div className="font-pixel text-[10px] text-stone-500">Gender Law</div>
                                    <div className="font-pixel text-sm text-blue-400">
                                        {getGenderLawLabel(rules.gender_law)}
                                    </div>
                                    <div className="mt-1 font-pixel text-[10px] text-stone-500">
                                        ({getGenderLawDesc(rules.gender_law)})
                                    </div>
                                </div>

                                <div className="grid grid-cols-2 gap-2">
                                    <div className="rounded-lg border border-stone-700 bg-stone-800/30 p-3">
                                        <div className="font-pixel text-[10px] text-stone-500">Bastards</div>
                                        <div className={`font-pixel text-xs ${rules.allows_bastards ? 'text-green-400' : 'text-red-400'}`}>
                                            {rules.allows_bastards ? 'Eligible' : 'Not Eligible'}
                                        </div>
                                    </div>
                                    <div className="rounded-lg border border-stone-700 bg-stone-800/30 p-3">
                                        <div className="font-pixel text-[10px] text-stone-500">Adoption</div>
                                        <div className={`font-pixel text-xs ${rules.allows_adoption ? 'text-green-400' : 'text-red-400'}`}>
                                            {rules.allows_adoption ? 'Allowed' : 'Not Allowed'}
                                        </div>
                                    </div>
                                </div>

                                <div className="rounded-lg border border-stone-700 bg-stone-800/30 p-3">
                                    <div className="font-pixel text-[10px] text-stone-500">Minimum Age</div>
                                    <div className="font-pixel text-sm text-stone-300">
                                        {rules.minimum_age} years
                                    </div>
                                </div>
                            </div>
                        </div>

                        {/* Prestige Info */}
                        <div className="rounded-xl border-2 border-stone-700 bg-stone-800/50 p-4">
                            <h2 className="mb-4 flex items-center gap-2 font-pixel text-sm text-stone-300">
                                <Star className="h-4 w-4 text-amber-400" />
                                Dynasty Prestige
                            </h2>

                            <div className="mb-4 text-center">
                                <div className="font-pixel text-3xl text-amber-400">
                                    {dynasty.prestige.toLocaleString()}
                                </div>
                                <div className="font-pixel text-xs text-stone-500">prestige points</div>
                            </div>

                            <div className="space-y-2 rounded-lg border border-amber-600/30 bg-amber-900/10 p-3">
                                <div className="flex items-center justify-between font-pixel text-xs">
                                    <span className="text-stone-400">Change Rules Cost</span>
                                    <span className="text-amber-400">-{change_cost}</span>
                                </div>
                                <div className="flex items-center justify-between font-pixel text-xs">
                                    <span className="text-stone-400">Disinherit Cost</span>
                                    <span className="text-amber-400">-{disinherit_cost}</span>
                                </div>
                            </div>

                            {is_head && dynasty.prestige < change_cost && (
                                <div className="mt-4 rounded-lg border border-red-600/30 bg-red-900/10 p-3">
                                    <div className="flex items-center gap-2 font-pixel text-xs text-red-400">
                                        <AlertTriangle className="h-4 w-4" />
                                        Insufficient prestige to change rules
                                    </div>
                                </div>
                            )}
                        </div>
                    </div>

                    {/* Succession Line */}
                    <div className="mt-4 rounded-xl border-2 border-stone-700 bg-stone-800/50 p-4">
                        <h2 className="mb-4 flex items-center gap-2 font-pixel text-sm text-stone-300">
                            <Users className="h-4 w-4 text-green-400" />
                            Line of Succession
                        </h2>

                        {succession_line.length === 0 ? (
                            <div className="py-8 text-center">
                                <Users className="mx-auto mb-2 h-8 w-8 text-stone-600" />
                                <div className="font-pixel text-xs text-stone-500">No eligible heirs</div>
                                <div className="mt-1 font-pixel text-[10px] text-stone-600">
                                    The dynasty has no members who qualify under current succession rules.
                                </div>
                            </div>
                        ) : (
                            <div className="space-y-2">
                                {succession_line.map((member, index) => (
                                    <div
                                        key={member.id}
                                        className={`flex items-center justify-between rounded-lg border p-3 ${
                                            index === 0
                                                ? 'border-purple-600/50 bg-purple-900/20'
                                                : 'border-stone-700 bg-stone-800/30'
                                        }`}
                                    >
                                        <div className="flex items-center gap-3">
                                            <div className={`flex h-8 w-8 items-center justify-center rounded-full font-pixel text-sm ${
                                                index === 0
                                                    ? 'bg-purple-900/50 text-purple-400'
                                                    : 'bg-stone-700/50 text-stone-400'
                                            }`}>
                                                {index + 1}
                                            </div>
                                            <div className={`flex h-8 w-8 items-center justify-center rounded-full ${
                                                member.gender === 'male' ? 'bg-blue-900/50' : 'bg-pink-900/50'
                                            }`}>
                                                <User className={`h-4 w-4 ${
                                                    member.gender === 'male' ? 'text-blue-400' : 'text-pink-400'
                                                }`} />
                                            </div>
                                            <div>
                                                <div className="flex items-center gap-2">
                                                    <span className="font-pixel text-sm text-stone-200">
                                                        {member.name}
                                                    </span>
                                                    {index === 0 && (
                                                        <Star className="h-3 w-3 text-purple-400" />
                                                    )}
                                                </div>
                                                <div className="flex items-center gap-2 font-pixel text-[10px] text-stone-500">
                                                    <span>Gen {member.generation}</span>
                                                    {member.age !== null && <span>Age {member.age}</span>}
                                                    {!member.is_legitimate && (
                                                        <span className="text-orange-400">Illegitimate</span>
                                                    )}
                                                </div>
                                            </div>
                                        </div>
                                        <div className="flex items-center gap-2">
                                            {index === 0 && (
                                                <span className="rounded bg-purple-900/50 px-2 py-0.5 font-pixel text-[10px] text-purple-400">
                                                    Heir
                                                </span>
                                            )}
                                            {is_head && !member.is_disinherited && (
                                                <button
                                                    onClick={() => setShowDisinheritModal(member)}
                                                    className="rounded border border-red-600/50 px-2 py-1 font-pixel text-[10px] text-red-400 transition hover:bg-red-900/20"
                                                    title="Disinherit"
                                                >
                                                    <UserMinus className="h-3 w-3" />
                                                </button>
                                            )}
                                        </div>
                                    </div>
                                ))}
                            </div>
                        )}

                        <div className="mt-4 rounded-lg border border-stone-700 bg-stone-800/30 p-3">
                            <div className="flex items-start gap-2 font-pixel text-[10px] text-stone-500">
                                <Star className="mt-0.5 h-3 w-3 text-purple-400" />
                                <span>The heir will inherit leadership of the dynasty upon the death of the current head.</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {/* Change Rules Modal */}
            {showChangeModal && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
                    <div className="w-full max-w-lg rounded-xl border-2 border-stone-700 bg-stone-800 p-6">
                        <h3 className="mb-4 font-pixel text-lg text-amber-400">Change Succession Rules</h3>

                        <div className="space-y-4">
                            {/* Succession Type */}
                            <div>
                                <label className="mb-1 block font-pixel text-xs text-stone-400">
                                    Succession Type
                                </label>
                                <div className="space-y-2">
                                    {succession_types.map((type) => (
                                        <label
                                            key={type.value}
                                            className={`flex cursor-pointer items-start gap-3 rounded-lg border p-3 transition ${
                                                formData.succession_type === type.value
                                                    ? 'border-purple-600/50 bg-purple-900/20'
                                                    : 'border-stone-700 hover:bg-stone-700/50'
                                            }`}
                                        >
                                            <input
                                                type="radio"
                                                name="succession_type"
                                                value={type.value}
                                                checked={formData.succession_type === type.value}
                                                onChange={(e) => setFormData({ ...formData, succession_type: e.target.value })}
                                                className="mt-1"
                                            />
                                            <div>
                                                <div className="font-pixel text-sm text-stone-200">{type.label}</div>
                                                <div className="font-pixel text-[10px] text-stone-500">{type.description}</div>
                                            </div>
                                        </label>
                                    ))}
                                </div>
                            </div>

                            {/* Gender Law */}
                            <div>
                                <label className="mb-1 block font-pixel text-xs text-stone-400">
                                    Gender Law
                                </label>
                                <div className="space-y-2">
                                    {gender_laws.map((law) => (
                                        <label
                                            key={law.value}
                                            className={`flex cursor-pointer items-start gap-3 rounded-lg border p-3 transition ${
                                                formData.gender_law === law.value
                                                    ? 'border-blue-600/50 bg-blue-900/20'
                                                    : 'border-stone-700 hover:bg-stone-700/50'
                                            }`}
                                        >
                                            <input
                                                type="radio"
                                                name="gender_law"
                                                value={law.value}
                                                checked={formData.gender_law === law.value}
                                                onChange={(e) => setFormData({ ...formData, gender_law: e.target.value })}
                                                className="mt-1"
                                            />
                                            <div>
                                                <div className="font-pixel text-sm text-stone-200">{law.label}</div>
                                                <div className="font-pixel text-[10px] text-stone-500">{law.description}</div>
                                            </div>
                                        </label>
                                    ))}
                                </div>
                            </div>

                            {/* Toggles */}
                            <div className="grid grid-cols-2 gap-4">
                                <label className="flex cursor-pointer items-center gap-2 rounded-lg border border-stone-700 p-3 hover:bg-stone-700/50">
                                    <input
                                        type="checkbox"
                                        checked={formData.allows_bastards}
                                        onChange={(e) => setFormData({ ...formData, allows_bastards: e.target.checked })}
                                    />
                                    <div>
                                        <div className="font-pixel text-xs text-stone-200">Allow Bastards</div>
                                        <div className="font-pixel text-[10px] text-stone-500">Can inherit</div>
                                    </div>
                                </label>
                                <label className="flex cursor-pointer items-center gap-2 rounded-lg border border-stone-700 p-3 hover:bg-stone-700/50">
                                    <input
                                        type="checkbox"
                                        checked={formData.allows_adoption}
                                        onChange={(e) => setFormData({ ...formData, allows_adoption: e.target.checked })}
                                    />
                                    <div>
                                        <div className="font-pixel text-xs text-stone-200">Allow Adoption</div>
                                        <div className="font-pixel text-[10px] text-stone-500">Can inherit</div>
                                    </div>
                                </label>
                            </div>

                            {/* Minimum Age */}
                            <div>
                                <label className="mb-1 block font-pixel text-xs text-stone-400">
                                    Minimum Age to Inherit
                                </label>
                                <input
                                    type="number"
                                    min="0"
                                    max="100"
                                    value={formData.minimum_age}
                                    onChange={(e) => setFormData({ ...formData, minimum_age: parseInt(e.target.value) || 0 })}
                                    className="w-full rounded-lg border border-stone-600 bg-stone-900 px-3 py-2 font-pixel text-sm text-stone-200"
                                />
                            </div>
                        </div>

                        <div className="mt-6 rounded-lg border border-amber-600/30 bg-amber-900/10 p-3">
                            <div className="flex items-center gap-2 font-pixel text-xs text-amber-400">
                                <AlertTriangle className="h-4 w-4" />
                                <span>Cost: {change_cost} prestige (You have: {dynasty.prestige})</span>
                            </div>
                        </div>

                        <div className="mt-4 flex gap-2">
                            <button
                                onClick={() => setShowChangeModal(false)}
                                className="flex-1 rounded-lg border border-stone-600 py-2 font-pixel text-xs text-stone-400 transition hover:bg-stone-700"
                            >
                                Cancel
                            </button>
                            <button
                                onClick={handleSaveRules}
                                disabled={saving || dynasty.prestige < change_cost}
                                className="flex flex-1 items-center justify-center gap-2 rounded-lg bg-purple-600 py-2 font-pixel text-xs text-white transition hover:bg-purple-500 disabled:opacity-50"
                            >
                                {saving ? 'Saving...' : (
                                    <>
                                        <Check className="h-4 w-4" />
                                        Confirm Changes
                                    </>
                                )}
                            </button>
                        </div>
                    </div>
                </div>
            )}

            {/* Disinherit Modal */}
            {showDisinheritModal && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
                    <div className="w-full max-w-md rounded-xl border-2 border-red-700/50 bg-stone-800 p-6">
                        <h3 className="mb-4 font-pixel text-lg text-red-400">Disinherit {showDisinheritModal.name}</h3>

                        <div className="mb-4 rounded-lg border border-red-600/30 bg-red-900/10 p-3">
                            <div className="flex items-start gap-2 font-pixel text-xs text-red-400">
                                <AlertTriangle className="mt-0.5 h-4 w-4" />
                                <div>
                                    <p>This action will permanently remove {showDisinheritModal.first_name} from the line of succession.</p>
                                    <p className="mt-1 text-stone-500">Cost: {disinherit_cost} prestige</p>
                                </div>
                            </div>
                        </div>

                        <div className="mb-4">
                            <label className="mb-1 block font-pixel text-xs text-stone-400">
                                Reason (optional)
                            </label>
                            <input
                                type="text"
                                value={disinheritReason}
                                onChange={(e) => setDisinheritReason(e.target.value)}
                                placeholder="State a reason..."
                                maxLength={255}
                                className="w-full rounded-lg border border-stone-600 bg-stone-900 px-3 py-2 font-pixel text-sm text-stone-200 placeholder:text-stone-600"
                            />
                        </div>

                        <div className="flex gap-2">
                            <button
                                onClick={() => {
                                    setShowDisinheritModal(null);
                                    setDisinheritReason('');
                                }}
                                className="flex-1 rounded-lg border border-stone-600 py-2 font-pixel text-xs text-stone-400 transition hover:bg-stone-700"
                            >
                                Cancel
                            </button>
                            <button
                                onClick={handleDisinherit}
                                disabled={saving || dynasty.prestige < disinherit_cost}
                                className="flex flex-1 items-center justify-center gap-2 rounded-lg bg-red-600 py-2 font-pixel text-xs text-white transition hover:bg-red-500 disabled:opacity-50"
                            >
                                {saving ? 'Processing...' : (
                                    <>
                                        <UserMinus className="h-4 w-4" />
                                        Disinherit
                                    </>
                                )}
                            </button>
                        </div>
                    </div>
                </div>
            )}
        </AppLayout>
    );
}
