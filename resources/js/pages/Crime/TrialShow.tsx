import { Head, Link, router, usePage } from '@inertiajs/react';
import {
    AlertTriangle,
    ArrowLeft,
    Calendar,
    CheckCircle,
    FileText,
    Gavel,
    MapPin,
    Scale,
    ScrollText,
    Shield,
    Skull,
    User,
    Users,
    XCircle,
} from 'lucide-react';
import { useState } from 'react';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

interface Trial {
    id: number;
    status: string;
    status_display: string;
    court_level: string;
    court_display: string;
    location_name: string;
    scheduled_at: string | null;
    started_at: string | null;
    concluded_at: string | null;
    verdict: string | null;
    verdict_display: string;
    verdict_reasoning: string | null;
    prosecution_argument: string | null;
    defense_argument: string | null;
    can_appeal: boolean;
}

interface Crime {
    type: string;
    severity: string;
    severity_display: string;
    description: string | null;
    committed_at: string | null;
}

interface Accusation {
    text: string | null;
    evidence: string[];
}

interface Person {
    id: number;
    username: string;
}

interface Witness {
    id: number;
    name: string;
    testimony: string | null;
    has_testified: boolean;
}

interface Punishment {
    id: number;
    type: string;
    type_display: string;
    description: string;
    status: string;
    status_display: string;
}

interface UserRole {
    is_defendant: boolean;
    is_accuser: boolean;
    is_judge: boolean;
    can_submit_defense: boolean;
    can_render_verdict: boolean;
}

interface PageProps {
    trial: Trial;
    crime: Crime;
    accusation: Accusation;
    defendant: Person;
    accuser: Person | null;
    judge: Person | null;
    witnesses: Witness[];
    punishments: Punishment[];
    user_role: UserRole;
    [key: string]: unknown;
}

const severityColors: Record<string, { bg: string; text: string; border: string }> = {
    minor: { bg: 'bg-green-900/30', text: 'text-green-400', border: 'border-green-500/50' },
    moderate: { bg: 'bg-yellow-900/30', text: 'text-yellow-400', border: 'border-yellow-500/50' },
    major: { bg: 'bg-orange-900/30', text: 'text-orange-400', border: 'border-orange-500/50' },
    capital: { bg: 'bg-red-900/30', text: 'text-red-400', border: 'border-red-500/50' },
};

const statusColors: Record<string, { bg: string; text: string }> = {
    scheduled: { bg: 'bg-blue-900/50', text: 'text-blue-300' },
    in_progress: { bg: 'bg-yellow-900/50', text: 'text-yellow-300' },
    awaiting_verdict: { bg: 'bg-purple-900/50', text: 'text-purple-300' },
    concluded: { bg: 'bg-stone-700/50', text: 'text-stone-300' },
    appealed: { bg: 'bg-amber-900/50', text: 'text-amber-300' },
    dismissed: { bg: 'bg-stone-600/50', text: 'text-stone-400' },
};

const verdictColors: Record<string, { bg: string; text: string; icon: typeof CheckCircle }> = {
    guilty: { bg: 'bg-red-900/50', text: 'text-red-300', icon: XCircle },
    not_guilty: { bg: 'bg-green-900/50', text: 'text-green-300', icon: CheckCircle },
    dismissed: { bg: 'bg-stone-700/50', text: 'text-stone-300', icon: Shield },
};

const punishmentStatusColors: Record<string, string> = {
    pending: 'text-yellow-300 bg-yellow-900/50',
    active: 'text-red-300 bg-red-900/50',
    completed: 'text-green-300 bg-green-900/50',
    pardoned: 'text-blue-300 bg-blue-900/50',
};

export default function TrialShow() {
    const { trial, crime, accusation, defendant, accuser, judge, witnesses, punishments, user_role } =
        usePage<PageProps>().props;

    const [defenseText, setDefenseText] = useState('');
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [error, setError] = useState<string | null>(null);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Criminal Record', href: '/crime' },
        { title: `Trial #${trial.id}`, href: '#' },
    ];

    const isConcluded = trial.status === 'concluded' || trial.status === 'dismissed';
    const severity = severityColors[crime.severity] || severityColors.moderate;
    const status = statusColors[trial.status] || statusColors.scheduled;

    const handleSubmitDefense = async (e: React.FormEvent) => {
        e.preventDefault();

        if (!defenseText.trim()) {
            setError('Please enter your defense statement.');
            return;
        }

        setIsSubmitting(true);
        setError(null);

        router.post(
            `/crime/trials/${trial.id}/defense`,
            { defense_argument: defenseText },
            {
                onError: (errors) => {
                    setError(Object.values(errors).flat().join(', ') || 'Failed to submit defense.');
                    setIsSubmitting(false);
                },
                onFinish: () => {
                    setIsSubmitting(false);
                },
            }
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Trial #${trial.id}`} />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-y-auto p-4">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <div className="flex items-center gap-3">
                            <h1 className="font-pixel text-2xl text-amber-400">Trial #{trial.id}</h1>
                            <span
                                className={`rounded px-2 py-0.5 font-pixel text-xs ${status.bg} ${status.text}`}
                            >
                                {trial.status_display}
                            </span>
                        </div>
                        <p className="font-pixel text-sm text-stone-400">{crime.type}</p>
                    </div>
                    <Link
                        href="/crime"
                        className="flex items-center gap-2 rounded-lg border border-stone-600/50 bg-stone-800/50 px-3 py-2 font-pixel text-xs text-stone-300 transition hover:bg-stone-700/50"
                    >
                        <ArrowLeft className="h-4 w-4" />
                        Back to Record
                    </Link>
                </div>

                {/* Court Info */}
                <div className="rounded-xl border-2 border-purple-500/50 bg-purple-900/20 p-4">
                    <div className="flex items-center gap-3">
                        <div className="rounded-lg bg-purple-800/50 p-3">
                            <Scale className="h-6 w-6 text-purple-300" />
                        </div>
                        <div className="flex-1">
                            <h2 className="font-pixel text-lg text-purple-300">{trial.court_display}</h2>
                            <div className="flex flex-wrap items-center gap-4 text-sm text-stone-400">
                                <div className="flex items-center gap-1">
                                    <MapPin className="h-4 w-4" />
                                    <span>{trial.location_name}</span>
                                </div>
                                {judge && (
                                    <div className="flex items-center gap-1">
                                        <Gavel className="h-4 w-4" />
                                        <span>Judge: {judge.username}</span>
                                    </div>
                                )}
                                {trial.scheduled_at && (
                                    <div className="flex items-center gap-1">
                                        <Calendar className="h-4 w-4" />
                                        <span>Scheduled: {trial.scheduled_at}</span>
                                    </div>
                                )}
                            </div>
                        </div>
                    </div>
                </div>

                {/* Verdict (if concluded) */}
                {isConcluded && trial.verdict && (() => {
                    const vc = verdictColors[trial.verdict] || verdictColors.dismissed;
                    const VerdictIcon = vc.icon;
                    return (
                    <div
                        className={`rounded-xl border-2 p-4 ${
                            trial.verdict === 'guilty'
                                ? 'border-red-500/50 bg-red-900/20'
                                : trial.verdict === 'not_guilty'
                                  ? 'border-green-500/50 bg-green-900/20'
                                  : 'border-stone-500/50 bg-stone-800/30'
                        }`}
                    >
                        <div className="flex items-center gap-3">
                            <div
                                className={`rounded-lg p-3 ${vc.bg}`}
                            >
                                <VerdictIcon className={`h-6 w-6 ${vc.text}`} />
                            </div>
                            <div className="flex-1">
                                <h2
                                    className={`font-pixel text-lg ${vc.text}`}
                                >
                                    Verdict: {trial.verdict_display}
                                </h2>
                                {trial.concluded_at && (
                                    <p className="font-pixel text-xs text-stone-500">
                                        Concluded on {trial.concluded_at}
                                    </p>
                                )}
                            </div>
                        </div>
                        {trial.verdict_reasoning && (
                            <div className="mt-3 rounded-lg bg-stone-900/50 p-3">
                                <p className="font-pixel text-xs text-stone-400">Judge's Reasoning:</p>
                                <p className="mt-1 text-sm text-stone-300">{trial.verdict_reasoning}</p>
                            </div>
                        )}
                        {trial.can_appeal && (
                            <div className="mt-3 flex items-center gap-2 rounded-lg bg-amber-900/30 p-2 font-pixel text-xs text-amber-300">
                                <AlertTriangle className="h-4 w-4" />
                                You may appeal this verdict to a higher court.
                            </div>
                        )}
                    </div>
                    );
                })()}

                {/* Parties */}
                <div className="grid gap-4 md:grid-cols-2">
                    {/* Accused */}
                    <div className="rounded-xl border-2 border-red-500/50 bg-red-900/20 p-4">
                        <h3 className="mb-3 flex items-center gap-2 font-pixel text-sm text-red-300">
                            <Skull className="h-4 w-4" />
                            ACCUSED
                        </h3>
                        <div className="flex items-center gap-3">
                            <div className="rounded-full bg-red-800/50 p-2">
                                <User className="h-5 w-5 text-red-300" />
                            </div>
                            <div>
                                <p className="font-pixel text-base text-white">{defendant.username}</p>
                                {user_role.is_defendant && (
                                    <span className="font-pixel text-[10px] text-amber-300">(You)</span>
                                )}
                            </div>
                        </div>
                    </div>

                    {/* Accuser */}
                    <div className="rounded-xl border-2 border-blue-500/50 bg-blue-900/20 p-4">
                        <h3 className="mb-3 flex items-center gap-2 font-pixel text-sm text-blue-300">
                            <FileText className="h-4 w-4" />
                            ACCUSER
                        </h3>
                        <div className="flex items-center gap-3">
                            <div className="rounded-full bg-blue-800/50 p-2">
                                <User className="h-5 w-5 text-blue-300" />
                            </div>
                            <div>
                                <p className="font-pixel text-base text-white">
                                    {accuser?.username || 'Unknown'}
                                </p>
                                {user_role.is_accuser && (
                                    <span className="font-pixel text-[10px] text-amber-300">(You)</span>
                                )}
                            </div>
                        </div>
                    </div>
                </div>

                {/* Crime Details */}
                <div
                    className={`rounded-xl border-2 p-4 ${severity.border} ${severity.bg}`}
                >
                    <h3 className={`mb-3 flex items-center gap-2 font-pixel text-sm ${severity.text}`}>
                        <Gavel className="h-4 w-4" />
                        Crime Details
                    </h3>
                    <div className="flex items-center justify-between">
                        <span className="font-pixel text-base text-white">{crime.type}</span>
                        <span
                            className={`rounded px-2 py-0.5 font-pixel text-[10px] ${severity.bg} ${severity.text}`}
                        >
                            {crime.severity_display}
                        </span>
                    </div>
                    {crime.description && (
                        <p className="mt-2 text-sm text-stone-300">{crime.description}</p>
                    )}
                    {crime.committed_at && (
                        <p className="mt-2 font-pixel text-[10px] text-stone-500">
                            Allegedly committed: {crime.committed_at}
                        </p>
                    )}
                </div>

                {/* Accusation Text */}
                {accusation.text && (
                    <div className="rounded-xl border-2 border-stone-600/50 bg-stone-800/30 p-4">
                        <h3 className="mb-3 flex items-center gap-2 font-pixel text-sm text-stone-300">
                            <ScrollText className="h-4 w-4" />
                            Accusation Statement
                        </h3>
                        <p className="text-sm text-stone-300">{accusation.text}</p>
                    </div>
                )}

                {/* Evidence */}
                {accusation.evidence.length > 0 && (
                    <div className="rounded-xl border-2 border-stone-600/50 bg-stone-800/30 p-4">
                        <h3 className="mb-3 flex items-center gap-2 font-pixel text-sm text-stone-300">
                            <FileText className="h-4 w-4" />
                            Evidence Presented
                        </h3>
                        <ul className="space-y-2">
                            {accusation.evidence.map((item, index) => (
                                <li
                                    key={index}
                                    className="flex items-start gap-2 rounded-lg bg-stone-900/50 p-2"
                                >
                                    <span className="font-pixel text-xs text-amber-400">{index + 1}.</span>
                                    <span className="text-sm text-stone-300">{item}</span>
                                </li>
                            ))}
                        </ul>
                    </div>
                )}

                {/* Witnesses */}
                {witnesses.length > 0 && (
                    <div className="rounded-xl border-2 border-stone-600/50 bg-stone-800/30 p-4">
                        <h3 className="mb-3 flex items-center gap-2 font-pixel text-sm text-stone-300">
                            <Users className="h-4 w-4" />
                            Witnesses
                        </h3>
                        <div className="space-y-2">
                            {witnesses.map((witness) => (
                                <div
                                    key={witness.id}
                                    className="rounded-lg border border-stone-700 bg-stone-900/50 p-3"
                                >
                                    <div className="flex items-center justify-between">
                                        <span className="font-pixel text-sm text-white">{witness.name}</span>
                                        <span
                                            className={`rounded px-1.5 py-0.5 font-pixel text-[10px] ${
                                                witness.has_testified
                                                    ? 'bg-green-900/50 text-green-300'
                                                    : 'bg-stone-700/50 text-stone-400'
                                            }`}
                                        >
                                            {witness.has_testified ? 'Testified' : 'Pending'}
                                        </span>
                                    </div>
                                    {witness.testimony && (
                                        <p className="mt-2 text-sm italic text-stone-400">
                                            "{witness.testimony}"
                                        </p>
                                    )}
                                </div>
                            ))}
                        </div>
                    </div>
                )}

                {/* Prosecution Argument */}
                {trial.prosecution_argument && (
                    <div className="rounded-xl border-2 border-blue-500/50 bg-blue-900/20 p-4">
                        <h3 className="mb-3 flex items-center gap-2 font-pixel text-sm text-blue-300">
                            <FileText className="h-4 w-4" />
                            Prosecution Argument
                        </h3>
                        <p className="text-sm text-stone-300">{trial.prosecution_argument}</p>
                    </div>
                )}

                {/* Defense Argument */}
                {trial.defense_argument && (
                    <div className="rounded-xl border-2 border-green-500/50 bg-green-900/20 p-4">
                        <h3 className="mb-3 flex items-center gap-2 font-pixel text-sm text-green-300">
                            <Shield className="h-4 w-4" />
                            Defense Statement
                        </h3>
                        <p className="text-sm text-stone-300">{trial.defense_argument}</p>
                    </div>
                )}

                {/* Defense Form (for defendant) */}
                {user_role.can_submit_defense && (
                    <div className="rounded-xl border-2 border-amber-500/50 bg-amber-900/20 p-4">
                        <h3 className="mb-3 flex items-center gap-2 font-pixel text-sm text-amber-300">
                            <Shield className="h-4 w-4" />
                            Submit Your Defense
                        </h3>
                        <p className="mb-3 font-pixel text-xs text-stone-400">
                            This is your opportunity to present your side of the story to the court.
                        </p>

                        {error && (
                            <div className="mb-3 rounded-lg border border-red-500/50 bg-red-900/30 p-3 font-pixel text-sm text-red-300">
                                {error}
                            </div>
                        )}

                        <form onSubmit={handleSubmitDefense}>
                            <textarea
                                value={defenseText}
                                onChange={(e) => setDefenseText(e.target.value)}
                                maxLength={2000}
                                rows={5}
                                placeholder="Enter your defense statement..."
                                className="w-full resize-none rounded border border-stone-600 bg-stone-800 px-3 py-2 font-pixel text-sm text-white placeholder-stone-500 focus:border-amber-500 focus:outline-none"
                            />
                            <div className="mt-2 flex items-center justify-between">
                                <span className="font-pixel text-[10px] text-stone-500">
                                    {defenseText.length}/2000 characters
                                </span>
                                <button
                                    type="submit"
                                    disabled={isSubmitting || !defenseText.trim()}
                                    className="rounded border-2 border-amber-600/50 bg-amber-900/30 px-4 py-2 font-pixel text-sm text-amber-300 transition hover:bg-amber-900/50 disabled:cursor-not-allowed disabled:opacity-50"
                                >
                                    {isSubmitting ? 'Submitting...' : 'Submit Defense'}
                                </button>
                            </div>
                        </form>
                    </div>
                )}

                {/* Punishments (if any) */}
                {punishments.length > 0 && (
                    <div className="rounded-xl border-2 border-red-500/50 bg-red-900/20 p-4">
                        <h3 className="mb-3 flex items-center gap-2 font-pixel text-sm text-red-300">
                            <Skull className="h-4 w-4" />
                            Sentence
                        </h3>
                        <div className="space-y-2">
                            {punishments.map((punishment) => (
                                <div
                                    key={punishment.id}
                                    className="flex items-center justify-between rounded-lg border border-red-600/50 bg-red-900/30 p-3"
                                >
                                    <div>
                                        <span className="font-pixel text-sm text-white">
                                            {punishment.type_display}
                                        </span>
                                        <p className="font-pixel text-[10px] text-stone-400">
                                            {punishment.description}
                                        </p>
                                    </div>
                                    <span
                                        className={`rounded px-1.5 py-0.5 font-pixel text-[10px] ${
                                            punishmentStatusColors[punishment.status] ||
                                            'bg-stone-700/50 text-stone-300'
                                        }`}
                                    >
                                        {punishment.status_display}
                                    </span>
                                </div>
                            ))}
                        </div>
                    </div>
                )}

                {/* Judge Notice */}
                {user_role.is_judge && (
                    <div className="rounded-xl border-2 border-purple-500/50 bg-purple-900/20 p-4">
                        <div className="flex items-center gap-3">
                            <div className="rounded-lg bg-purple-800/50 p-2">
                                <Gavel className="h-5 w-5 text-purple-300" />
                            </div>
                            <div>
                                <p className="font-pixel text-sm text-purple-300">
                                    You are the presiding judge
                                </p>
                                {user_role.can_render_verdict && (
                                    <p className="font-pixel text-xs text-stone-400">
                                        Go to{' '}
                                        <Link
                                            href="/crime/trials"
                                            className="text-amber-300 underline hover:text-amber-200"
                                        >
                                            Pending Trials
                                        </Link>{' '}
                                        to render your verdict.
                                    </p>
                                )}
                            </div>
                        </div>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
