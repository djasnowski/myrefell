import { Head, router, usePage } from '@inertiajs/react';
import { ArrowLeft, Church, Coins, Crown, Heart, Shield, Star, Users, Zap } from 'lucide-react';
import { useState } from 'react';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

interface Belief {
    id: number;
    name: string;
    description: string;
    icon: string;
    type: 'virtue' | 'vice' | 'neutral';
    effects: Record<string, number> | null;
}

interface Religion {
    id: number;
    name: string;
    description: string | null;
    icon: string;
    color: string;
    type: 'cult' | 'religion';
    is_public: boolean;
    is_cult: boolean;
    is_religion: boolean;
    member_count: number;
    member_limit: number | null;
    belief_limit: number;
    founder: { id: number; username: string } | null;
    beliefs: Belief[];
    combined_effects: Record<string, number>;
}

interface Membership {
    id: number;
    religion_id: number;
    religion_name: string;
    rank: string;
    rank_display: string;
    devotion: number;
    is_prophet: boolean;
    is_priest: boolean;
    can_be_promoted: boolean;
}

interface Member {
    id: number;
    user_id: number;
    username: string;
    rank: string;
    rank_display: string;
    devotion: number;
    joined_at: string;
}

interface Structure {
    id: number;
    name: string;
    structure_type: string;
    type_display: string;
    location_type: string;
    location_id: number;
}

interface PageProps {
    religion: Religion;
    membership: Membership | null;
    is_member: boolean;
    can_join: boolean;
    kingdom_status: string | null;
    members: Member[];
    structures: Structure[];
    energy: { current: number };
    gold: number;
    [key: string]: unknown;
}

const beliefTypeColors: Record<string, string> = {
    virtue: 'text-green-400 bg-green-900/30 border-green-500/30',
    vice: 'text-red-400 bg-red-900/30 border-red-500/30',
    neutral: 'text-blue-400 bg-blue-900/30 border-blue-500/30',
};

const rankColors: Record<string, string> = {
    prophet: 'text-yellow-400 bg-yellow-900/30',
    priest: 'text-purple-400 bg-purple-900/30',
    follower: 'text-stone-400 bg-stone-700/30',
};

export default function ReligionShow() {
    const { religion, membership, is_member, can_join, kingdom_status, members, structures, energy, gold } =
        usePage<PageProps>().props;

    const [isLoading, setIsLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const [success, setSuccess] = useState<string | null>(null);
    const [donationAmount, setDonationAmount] = useState(100);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Religions', href: '/religions' },
        { title: religion.name, href: `/religions/${religion.id}` },
    ];

    const performAction = async (actionType: string, additionalData: Record<string, unknown> = {}) => {
        setIsLoading(true);
        setError(null);

        try {
            const response = await fetch('/religions/action', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content || '',
                },
                body: JSON.stringify({
                    religion_id: religion.id,
                    action_type: actionType,
                    ...additionalData,
                }),
            });

            const data = await response.json();
            if (data.success) {
                setSuccess(data.message);
                router.reload();
            } else {
                setError(data.message);
            }
        } catch {
            setError('Failed to perform action');
        } finally {
            setIsLoading(false);
        }
    };

    const joinReligion = async () => {
        setIsLoading(true);
        setError(null);

        try {
            const response = await fetch('/religions/join', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content || '',
                },
                body: JSON.stringify({ religion_id: religion.id }),
            });

            const data = await response.json();
            if (data.success) {
                setSuccess(data.message);
                router.reload();
            } else {
                setError(data.message);
            }
        } catch {
            setError('Failed to join religion');
        } finally {
            setIsLoading(false);
        }
    };

    const leaveReligion = async () => {
        if (!confirm('Are you sure you want to leave this religion?')) return;

        setIsLoading(true);
        setError(null);

        try {
            const response = await fetch('/religions/leave', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content || '',
                },
                body: JSON.stringify({ religion_id: religion.id }),
            });

            const data = await response.json();
            if (data.success) {
                setSuccess(data.message);
                router.visit('/religions');
            } else {
                setError(data.message);
            }
        } catch {
            setError('Failed to leave religion');
        } finally {
            setIsLoading(false);
        }
    };

    const promoteMember = async (memberId: number) => {
        setIsLoading(true);
        setError(null);

        try {
            const response = await fetch('/religions/promote', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content || '',
                },
                body: JSON.stringify({ member_id: memberId }),
            });

            const data = await response.json();
            if (data.success) {
                setSuccess(data.message);
                router.reload();
            } else {
                setError(data.message);
            }
        } catch {
            setError('Failed to promote member');
        } finally {
            setIsLoading(false);
        }
    };

    const demoteMember = async (memberId: number) => {
        setIsLoading(true);
        setError(null);

        try {
            const response = await fetch('/religions/demote', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content || '',
                },
                body: JSON.stringify({ member_id: memberId }),
            });

            const data = await response.json();
            if (data.success) {
                setSuccess(data.message);
                router.reload();
            } else {
                setError(data.message);
            }
        } catch {
            setError('Failed to demote member');
        } finally {
            setIsLoading(false);
        }
    };

    const makePublic = async () => {
        setIsLoading(true);
        setError(null);

        try {
            const response = await fetch('/religions/make-public', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content || '',
                },
                body: JSON.stringify({ religion_id: religion.id }),
            });

            const data = await response.json();
            if (data.success) {
                setSuccess(data.message);
                router.reload();
            } else {
                setError(data.message);
            }
        } catch {
            setError('Failed to make public');
        } finally {
            setIsLoading(false);
        }
    };

    const convertToReligion = async () => {
        if (!confirm('Convert this cult to a full religion? This costs 100,000 gold and requires 15+ members.')) return;

        setIsLoading(true);
        setError(null);

        try {
            const response = await fetch('/religions/convert', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content || '',
                },
                body: JSON.stringify({ religion_id: religion.id }),
            });

            const data = await response.json();
            if (data.success) {
                setSuccess(data.message);
                router.reload();
            } else {
                setError(data.message);
            }
        } catch {
            setError('Failed to convert to religion');
        } finally {
            setIsLoading(false);
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={religion.name} />
            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                {/* Back Button */}
                <a
                    href="/religions"
                    className="flex items-center gap-2 font-pixel text-sm text-stone-400 hover:text-white"
                >
                    <ArrowLeft className="h-4 w-4" />
                    Back to Religions
                </a>

                {/* Header */}
                <div className="flex items-start justify-between">
                    <div className="flex items-center gap-3">
                        <Church className="h-8 w-8" style={{ color: religion.color }} />
                        <div>
                            <h1 className="font-pixel text-2xl text-amber-400">{religion.name}</h1>
                            <div className="flex items-center gap-2">
                                <span className={`rounded px-2 py-0.5 font-pixel text-xs capitalize ${
                                    religion.is_cult ? 'bg-stone-700 text-stone-300' : 'bg-amber-900/50 text-amber-300'
                                }`}>
                                    {religion.type}
                                </span>
                                {kingdom_status && (
                                    <span className={`rounded px-2 py-0.5 font-pixel text-xs ${
                                        kingdom_status === 'state' ? 'bg-green-900/50 text-green-300' :
                                        kingdom_status === 'banned' ? 'bg-red-900/50 text-red-300' :
                                        'bg-stone-700 text-stone-300'
                                    }`}>
                                        {kingdom_status === 'state' ? 'State Religion' : kingdom_status}
                                    </span>
                                )}
                            </div>
                        </div>
                    </div>
                    <div className="flex items-center gap-4">
                        <div className="flex items-center gap-2 font-pixel text-sm">
                            <Zap className="h-4 w-4 text-yellow-400" />
                            <span className="text-stone-300">{energy.current}</span>
                        </div>
                        <div className="flex items-center gap-2 font-pixel text-sm">
                            <Coins className="h-4 w-4 text-amber-400" />
                            <span className="text-stone-300">{gold.toLocaleString()}</span>
                        </div>
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

                <div className="grid gap-6 lg:grid-cols-3">
                    {/* Main Info */}
                    <div className="lg:col-span-2 space-y-6">
                        {/* Description */}
                        {religion.description && (
                            <div className="rounded-lg border border-stone-700 bg-stone-800/50 p-4">
                                <h2 className="mb-2 font-pixel text-sm text-amber-300">About</h2>
                                <p className="font-pixel text-sm text-stone-300">{religion.description}</p>
                            </div>
                        )}

                        {/* Beliefs */}
                        <div className="rounded-lg border border-stone-700 bg-stone-800/50 p-4">
                            <h2 className="mb-3 font-pixel text-sm text-amber-300">Beliefs ({religion.beliefs.length}/{religion.belief_limit})</h2>
                            <div className="space-y-2">
                                {religion.beliefs.map((belief) => (
                                    <div
                                        key={belief.id}
                                        className={`rounded-lg border p-3 ${beliefTypeColors[belief.type]}`}
                                    >
                                        <div className="font-pixel text-sm">{belief.name}</div>
                                        <div className="font-pixel text-xs opacity-80">{belief.description}</div>
                                        {belief.effects && (
                                            <div className="mt-1 font-pixel text-xs text-amber-400">
                                                {Object.entries(belief.effects).map(([k, v]) => (
                                                    <span key={k} className="mr-2">
                                                        {k}: {v > 0 ? '+' : ''}{v}
                                                    </span>
                                                ))}
                                            </div>
                                        )}
                                    </div>
                                ))}
                            </div>
                            {Object.keys(religion.combined_effects).length > 0 && (
                                <div className="mt-3 border-t border-stone-700 pt-3">
                                    <div className="font-pixel text-xs text-stone-400">Combined Effects:</div>
                                    <div className="font-pixel text-sm text-amber-400">
                                        {Object.entries(religion.combined_effects).map(([k, v]) => (
                                            <span key={k} className="mr-3">
                                                {k}: {v > 0 ? '+' : ''}{v}
                                            </span>
                                        ))}
                                    </div>
                                </div>
                            )}
                        </div>

                        {/* Member Actions (if member) */}
                        {is_member && membership && (
                            <div className="rounded-lg border border-purple-500/30 bg-purple-900/20 p-4">
                                <h2 className="mb-3 font-pixel text-sm text-purple-300">Religious Actions</h2>
                                <div className="grid gap-3 md:grid-cols-2">
                                    <button
                                        onClick={() => performAction('prayer')}
                                        disabled={isLoading || energy.current < 5}
                                        className="flex items-center justify-center gap-2 rounded bg-purple-600/50 py-2 font-pixel text-xs text-purple-200 transition hover:bg-purple-600 disabled:cursor-not-allowed disabled:opacity-50"
                                    >
                                        <Heart className="h-4 w-4" />
                                        Pray (5 Energy)
                                    </button>
                                    <button
                                        onClick={() => performAction('ritual')}
                                        disabled={isLoading || energy.current < 15}
                                        className="flex items-center justify-center gap-2 rounded bg-indigo-600/50 py-2 font-pixel text-xs text-indigo-200 transition hover:bg-indigo-600 disabled:cursor-not-allowed disabled:opacity-50"
                                    >
                                        <Star className="h-4 w-4" />
                                        Ritual (15 Energy)
                                    </button>
                                    <button
                                        onClick={() => performAction('sacrifice')}
                                        disabled={isLoading || energy.current < 20}
                                        className="flex items-center justify-center gap-2 rounded bg-red-600/50 py-2 font-pixel text-xs text-red-200 transition hover:bg-red-600 disabled:cursor-not-allowed disabled:opacity-50"
                                    >
                                        <Shield className="h-4 w-4" />
                                        Sacrifice (20 Energy)
                                    </button>
                                    <div className="flex items-center gap-2">
                                        <input
                                            type="number"
                                            value={donationAmount}
                                            onChange={(e) => setDonationAmount(Math.max(10, parseInt(e.target.value) || 10))}
                                            min={10}
                                            className="w-24 rounded border border-stone-600 bg-stone-800 px-2 py-2 font-pixel text-xs text-white"
                                        />
                                        <button
                                            onClick={() => performAction('donation', { donation_amount: donationAmount })}
                                            disabled={isLoading || gold < donationAmount}
                                            className="flex flex-1 items-center justify-center gap-2 rounded bg-amber-600/50 py-2 font-pixel text-xs text-amber-200 transition hover:bg-amber-600 disabled:cursor-not-allowed disabled:opacity-50"
                                        >
                                            <Coins className="h-4 w-4" />
                                            Donate
                                        </button>
                                    </div>
                                </div>

                                {/* Leave Button */}
                                {!membership.is_prophet && (
                                    <button
                                        onClick={leaveReligion}
                                        disabled={isLoading}
                                        className="mt-4 w-full rounded border border-red-500/50 bg-transparent py-2 font-pixel text-xs text-red-400 transition hover:bg-red-900/30 disabled:cursor-not-allowed disabled:opacity-50"
                                    >
                                        Leave Religion
                                    </button>
                                )}
                            </div>
                        )}

                        {/* Prophet Controls */}
                        {membership?.is_prophet && (
                            <div className="rounded-lg border border-yellow-500/30 bg-yellow-900/20 p-4">
                                <h2 className="mb-3 font-pixel text-sm text-yellow-300">Prophet Controls</h2>
                                <div className="flex flex-wrap gap-2">
                                    {!religion.is_public && (
                                        <button
                                            onClick={makePublic}
                                            disabled={isLoading}
                                            className="rounded bg-green-600/50 px-4 py-2 font-pixel text-xs text-green-200 transition hover:bg-green-600 disabled:cursor-not-allowed disabled:opacity-50"
                                        >
                                            Make Public
                                        </button>
                                    )}
                                    {religion.is_cult && religion.member_count >= 15 && (
                                        <button
                                            onClick={convertToReligion}
                                            disabled={isLoading || gold < 100000}
                                            className="rounded bg-amber-600/50 px-4 py-2 font-pixel text-xs text-amber-200 transition hover:bg-amber-600 disabled:cursor-not-allowed disabled:opacity-50"
                                        >
                                            Convert to Religion (100K Gold)
                                        </button>
                                    )}
                                </div>
                            </div>
                        )}

                        {/* Join Button */}
                        {!is_member && can_join && (
                            <button
                                onClick={joinReligion}
                                disabled={isLoading}
                                className="w-full rounded bg-purple-600 py-3 font-pixel text-sm text-white transition hover:bg-purple-500 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                {isLoading ? 'Joining...' : 'Join This Religion'}
                            </button>
                        )}
                    </div>

                    {/* Sidebar */}
                    <div className="space-y-6">
                        {/* Membership Status */}
                        {membership && (
                            <div className="rounded-lg border border-stone-700 bg-stone-800/50 p-4">
                                <h2 className="mb-3 font-pixel text-sm text-amber-300">Your Status</h2>
                                <div className="space-y-2 font-pixel text-xs">
                                    <div className="flex justify-between">
                                        <span className="text-stone-400">Rank:</span>
                                        <span className={`rounded px-2 py-0.5 ${rankColors[membership.rank]}`}>
                                            {membership.rank_display}
                                        </span>
                                    </div>
                                    <div className="flex justify-between">
                                        <span className="text-stone-400">Devotion:</span>
                                        <span className="text-pink-400">{membership.devotion}</span>
                                    </div>
                                </div>
                            </div>
                        )}

                        {/* Members */}
                        <div className="rounded-lg border border-stone-700 bg-stone-800/50 p-4">
                            <h2 className="mb-3 flex items-center gap-2 font-pixel text-sm text-amber-300">
                                <Users className="h-4 w-4" />
                                Members ({religion.member_count}
                                {religion.member_limit && `/${religion.member_limit}`})
                            </h2>
                            <div className="max-h-64 space-y-2 overflow-y-auto">
                                {members.map((member) => (
                                    <div
                                        key={member.id}
                                        className="flex items-center justify-between rounded bg-stone-900/50 p-2"
                                    >
                                        <div>
                                            <div className="flex items-center gap-2">
                                                {member.rank === 'prophet' && <Crown className="h-3 w-3 text-yellow-400" />}
                                                <span className="font-pixel text-xs text-white">{member.username}</span>
                                            </div>
                                            <span className={`font-pixel text-[10px] ${
                                                member.rank === 'prophet' ? 'text-yellow-400' :
                                                member.rank === 'priest' ? 'text-purple-400' :
                                                'text-stone-500'
                                            }`}>
                                                {member.rank_display} - {member.devotion} devotion
                                            </span>
                                        </div>
                                        {membership?.is_prophet && member.rank !== 'prophet' && (
                                            <div className="flex gap-1">
                                                {member.rank === 'follower' && member.devotion >= 1000 && (
                                                    <button
                                                        onClick={() => promoteMember(member.id)}
                                                        disabled={isLoading}
                                                        className="rounded bg-green-600/50 px-2 py-1 font-pixel text-[10px] text-green-200 hover:bg-green-600"
                                                    >
                                                        Promote
                                                    </button>
                                                )}
                                                {member.rank === 'priest' && (
                                                    <button
                                                        onClick={() => demoteMember(member.id)}
                                                        disabled={isLoading}
                                                        className="rounded bg-red-600/50 px-2 py-1 font-pixel text-[10px] text-red-200 hover:bg-red-600"
                                                    >
                                                        Demote
                                                    </button>
                                                )}
                                            </div>
                                        )}
                                    </div>
                                ))}
                            </div>
                        </div>

                        {/* Structures */}
                        {structures.length > 0 && (
                            <div className="rounded-lg border border-stone-700 bg-stone-800/50 p-4">
                                <h2 className="mb-3 font-pixel text-sm text-amber-300">Structures</h2>
                                <div className="space-y-2">
                                    {structures.map((structure) => (
                                        <div key={structure.id} className="rounded bg-stone-900/50 p-2">
                                            <div className="font-pixel text-xs text-white">{structure.name}</div>
                                            <div className="font-pixel text-[10px] text-stone-400">
                                                {structure.type_display} at {structure.location_type} #{structure.location_id}
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        )}

                        {/* Founder */}
                        {religion.founder && (
                            <div className="rounded-lg border border-stone-700 bg-stone-800/50 p-4">
                                <h2 className="mb-2 font-pixel text-sm text-amber-300">Founder</h2>
                                <div className="flex items-center gap-2">
                                    <Crown className="h-4 w-4 text-yellow-400" />
                                    <span className="font-pixel text-sm text-white">{religion.founder.username}</span>
                                </div>
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
