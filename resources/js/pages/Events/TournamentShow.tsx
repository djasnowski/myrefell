import { Head, Link, router, usePage } from '@inertiajs/react';
import {
    ArrowLeft,
    Calendar,
    Clock,
    Coins,
    Crown,
    MapPin,
    Skull,
    Star,
    Swords,
    Target,
    Trophy,
    Users,
    XCircle,
} from 'lucide-react';
import { useState } from 'react';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

interface TournamentType {
    id: number;
    name: string;
    combat_type: string;
    description: string;
    entry_fee: number;
    min_level: number;
    max_participants: number;
    is_lethal: boolean;
}

interface Tournament {
    id: number;
    name: string;
    type: TournamentType;
    status: 'registration' | 'in_progress' | 'completed' | 'cancelled';
    location_type: string;
    location_id: number;
    location_name: string;
    registration_ends_at: string | null;
    registration_ends_formatted: string | null;
    starts_at: string | null;
    starts_at_formatted: string | null;
    completed_at: string | null;
    completed_at_formatted: string | null;
    prize_pool: number;
    current_round: number | null;
    total_rounds: number | null;
    sponsor_name: string | null;
    sponsor_contribution: number;
    festival_id: number | null;
    festival_name: string | null;
    is_registration_open: boolean;
}

interface Competitor {
    id: number;
    user_id: number;
    username: string;
    seed: number | null;
    status: 'registered' | 'active' | 'eliminated' | 'winner' | 'withdrew';
    wins: number;
    losses: number;
    final_placement: number | null;
    prize_won: number;
    fame_earned: number;
}

interface MatchCompetitor {
    id: number;
    username: string;
    seed: number | null;
}

interface Match {
    id: number;
    round_number: number;
    match_number: number;
    competitor1: MatchCompetitor | null;
    competitor2: MatchCompetitor | null;
    winner_id: number | null;
    winner_username: string | null;
    status: 'pending' | 'in_progress' | 'completed' | 'cancelled';
    competitor1_score: number;
    competitor2_score: number;
    is_bye: boolean;
    combat_log: string[];
}

interface UserCompetitor {
    id: number;
    seed: number | null;
    status: string;
    wins: number;
    losses: number;
    prize_won: number;
}

interface UserNextMatch {
    id: number;
    round_number: number;
    opponent_username: string;
    status: string;
}

interface Winner {
    id: number;
    username: string;
    prize_won: number;
}

interface PageProps {
    tournament: Tournament;
    competitors: Competitor[];
    matches_by_round: Record<string, Match[]>;
    is_registered: boolean;
    user_competitor: UserCompetitor | null;
    user_next_match: UserNextMatch | null;
    winner: Winner | null;
    user_gold: number;
    user_combat_level: number;
    [key: string]: unknown;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Events', href: '/events' },
    { title: 'Tournament Details', href: '#' },
];

const statusColors: Record<string, { bg: string; text: string; label: string }> = {
    registration: { bg: 'bg-blue-900/30', text: 'text-blue-400', label: 'Registration Open' },
    in_progress: { bg: 'bg-amber-900/30', text: 'text-amber-400', label: 'In Progress' },
    completed: { bg: 'bg-green-900/30', text: 'text-green-400', label: 'Completed' },
    cancelled: { bg: 'bg-stone-900/30', text: 'text-stone-500', label: 'Cancelled' },
};

const competitorStatusColors: Record<string, { bg: string; text: string }> = {
    registered: { bg: 'bg-blue-900/30', text: 'text-blue-400' },
    active: { bg: 'bg-green-900/30', text: 'text-green-400' },
    eliminated: { bg: 'bg-red-900/30', text: 'text-red-400' },
    winner: { bg: 'bg-amber-900/30', text: 'text-amber-400' },
    withdrew: { bg: 'bg-stone-900/30', text: 'text-stone-500' },
};

const combatTypeIcons: Record<string, typeof Swords> = {
    melee: Swords,
    joust: Target,
    archery: Target,
    wrestling: Users,
    mixed: Swords,
};

export default function TournamentShow() {
    const {
        tournament,
        competitors,
        matches_by_round,
        is_registered,
        user_competitor,
        user_next_match,
        winner,
        user_gold,
        user_combat_level,
    } = usePage<PageProps>().props;

    const [registering, setRegistering] = useState(false);
    const [withdrawing, setWithdrawing] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const [success, setSuccess] = useState<string | null>(null);

    const statusStyle = statusColors[tournament.status] || statusColors.registration;
    const CombatIcon = combatTypeIcons[tournament.type.combat_type] || Swords;

    const canRegister = tournament.is_registration_open && !is_registered;
    const meetsLevel = user_combat_level >= tournament.type.min_level;
    const hasGold = user_gold >= tournament.type.entry_fee;
    const canWithdraw = is_registered && tournament.status === 'registration';

    const registerForTournament = async () => {
        setRegistering(true);
        setError(null);

        try {
            const response = await fetch(`/events/tournaments/${tournament.id}/register`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content || '',
                },
            });

            const data = await response.json();
            if (data.success) {
                setSuccess('You have registered for the tournament!');
                router.reload();
            } else {
                setError(data.message);
            }
        } catch {
            setError('Failed to register for tournament');
        } finally {
            setRegistering(false);
        }
    };

    const withdrawFromTournament = async () => {
        setWithdrawing(true);
        setError(null);

        try {
            const response = await fetch(`/events/tournaments/${tournament.id}/withdraw`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content || '',
                },
            });

            const data = await response.json();
            if (data.success) {
                setSuccess('You have withdrawn from the tournament.');
                router.reload();
            } else {
                setError(data.message);
            }
        } catch {
            setError('Failed to withdraw from tournament');
        } finally {
            setWithdrawing(false);
        }
    };

    // Get round names
    const getRoundName = (roundNumber: number, totalRounds: number): string => {
        if (roundNumber === totalRounds) return 'Finals';
        if (roundNumber === totalRounds - 1) return 'Semi-Finals';
        if (roundNumber === totalRounds - 2) return 'Quarter-Finals';
        return `Round ${roundNumber}`;
    };

    // Calculate progress for in-progress tournaments
    const progressPercent = tournament.current_round && tournament.total_rounds
        ? Math.round((tournament.current_round / tournament.total_rounds) * 100)
        : 0;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Tournament: ${tournament.name}`} />
            <div className="flex h-full flex-1 flex-col gap-6 overflow-y-auto p-4">
                {/* Back Link */}
                <Link
                    href="/events"
                    className="flex items-center gap-2 font-pixel text-sm text-stone-400 transition hover:text-stone-300"
                >
                    <ArrowLeft className="h-4 w-4" />
                    Back to Events
                </Link>

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

                {/* Header */}
                <div className="rounded-xl border-2 border-red-600/50 bg-red-900/20 p-4">
                    <div className="flex flex-wrap items-start justify-between gap-4">
                        <div className="flex items-center gap-3">
                            <CombatIcon className="h-8 w-8 text-red-400" />
                            <div>
                                <h1 className="font-pixel text-2xl text-white">{tournament.name}</h1>
                                <div className="flex flex-wrap items-center gap-3 font-pixel text-sm text-stone-400">
                                    <span className="rounded bg-red-900/30 px-2 py-0.5 text-[10px] capitalize text-red-300">
                                        {tournament.type.name}
                                    </span>
                                    <span className="flex items-center gap-1">
                                        <MapPin className="h-3 w-3 text-amber-400" />
                                        {tournament.location_name}
                                    </span>
                                    {tournament.type.is_lethal && (
                                        <span className="flex items-center gap-1 rounded bg-red-900/50 px-1.5 py-0.5 text-[10px] text-red-300">
                                            <Skull className="h-3 w-3" />
                                            Lethal
                                        </span>
                                    )}
                                </div>
                            </div>
                        </div>
                        <div className="flex items-center gap-3">
                            <span className={`rounded px-3 py-1.5 font-pixel text-xs ${statusStyle.bg} ${statusStyle.text}`}>
                                {statusStyle.label}
                            </span>
                        </div>
                    </div>

                    {/* Tournament Type Description */}
                    {tournament.type.description && (
                        <p className="mt-3 font-pixel text-xs text-stone-300 italic">
                            "{tournament.type.description}"
                        </p>
                    )}

                    {/* Festival Link */}
                    {tournament.festival_id && tournament.festival_name && (
                        <div className="mt-3">
                            <Link
                                href={`/events/festivals/${tournament.festival_id}`}
                                className="font-pixel text-xs text-amber-400 hover:text-amber-300"
                            >
                                Part of: {tournament.festival_name}
                            </Link>
                        </div>
                    )}
                </div>

                {/* Winner Banner (if completed) */}
                {tournament.status === 'completed' && winner && (
                    <div className="rounded-xl border-2 border-amber-500/50 bg-gradient-to-r from-amber-900/30 to-yellow-900/30 p-4">
                        <div className="flex items-center justify-center gap-4">
                            <Crown className="h-8 w-8 text-amber-400" />
                            <div className="text-center">
                                <div className="font-pixel text-xs text-amber-300">Tournament Champion</div>
                                <div className="font-pixel text-2xl text-white">{winner.username}</div>
                                {winner.prize_won > 0 && (
                                    <div className="font-pixel text-sm text-yellow-400">
                                        Won {winner.prize_won}g
                                    </div>
                                )}
                            </div>
                            <Crown className="h-8 w-8 text-amber-400" />
                        </div>
                    </div>
                )}

                {/* User Registration Status */}
                {is_registered && user_competitor && (
                    <div className="rounded-xl border-2 border-green-500/30 bg-green-900/20 p-4">
                        <div className="flex flex-wrap items-center justify-between gap-4">
                            <div className="flex items-center gap-3">
                                <Star className="h-5 w-5 text-green-400" />
                                <div>
                                    <span className="font-pixel text-sm text-white">
                                        You are competing!
                                    </span>
                                    <span className={`ml-2 rounded px-2 py-0.5 font-pixel text-[10px] capitalize ${competitorStatusColors[user_competitor.status]?.bg || 'bg-stone-700'} ${competitorStatusColors[user_competitor.status]?.text || 'text-stone-300'}`}>
                                        {user_competitor.status}
                                    </span>
                                </div>
                            </div>
                            <div className="flex items-center gap-4 font-pixel text-xs">
                                {user_competitor.seed && (
                                    <div>
                                        <span className="text-stone-400">Seed: </span>
                                        <span className="text-white">#{user_competitor.seed}</span>
                                    </div>
                                )}
                                <div>
                                    <span className="text-stone-400">Record: </span>
                                    <span className="text-green-400">{user_competitor.wins}W</span>
                                    <span className="text-stone-500"> - </span>
                                    <span className="text-red-400">{user_competitor.losses}L</span>
                                </div>
                                {user_competitor.prize_won > 0 && (
                                    <div>
                                        <span className="text-stone-400">Prize: </span>
                                        <span className="text-yellow-400">{user_competitor.prize_won}g</span>
                                    </div>
                                )}
                                {canWithdraw && (
                                    <button
                                        onClick={withdrawFromTournament}
                                        disabled={withdrawing}
                                        className="rounded border border-red-600/50 bg-red-900/20 px-3 py-1 font-pixel text-xs text-red-300 transition hover:bg-red-900/40 disabled:cursor-not-allowed disabled:opacity-50"
                                    >
                                        {withdrawing ? 'Withdrawing...' : 'Withdraw'}
                                    </button>
                                )}
                            </div>
                        </div>
                        {/* Next Match Info */}
                        {user_next_match && (
                            <div className="mt-3 rounded-lg bg-stone-900/50 p-3">
                                <div className="flex items-center gap-2 font-pixel text-xs">
                                    <Swords className="h-4 w-4 text-amber-400" />
                                    <span className="text-stone-300">Next Match:</span>
                                    <span className="text-white">
                                        {getRoundName(user_next_match.round_number, tournament.total_rounds || 1)} vs {user_next_match.opponent_username}
                                    </span>
                                </div>
                            </div>
                        )}
                    </div>
                )}

                {/* Progress Bar for In-Progress Tournaments */}
                {tournament.status === 'in_progress' && tournament.current_round && tournament.total_rounds && (
                    <div className="rounded-xl border-2 border-stone-600/50 bg-stone-800/30 p-4">
                        <div className="mb-2 flex items-center justify-between font-pixel text-xs">
                            <span className="flex items-center gap-2 text-stone-300">
                                <Trophy className="h-3 w-3 text-amber-400" />
                                Tournament Progress
                            </span>
                            <span className="text-stone-400">
                                {getRoundName(tournament.current_round, tournament.total_rounds)}
                            </span>
                        </div>
                        <div className="h-4 w-full overflow-hidden rounded-full bg-stone-700">
                            <div
                                className="h-full bg-gradient-to-r from-red-600 to-amber-500 transition-all duration-300"
                                style={{ width: `${progressPercent}%` }}
                            />
                        </div>
                        <div className="mt-1 text-right font-pixel text-[10px] text-stone-400">
                            Round {tournament.current_round} of {tournament.total_rounds}
                        </div>
                    </div>
                )}

                {/* Info Grid */}
                <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                    {/* Prize Pool */}
                    <div className="rounded-lg bg-stone-800/50 p-3 text-center">
                        <div className="flex items-center justify-center gap-1">
                            <Coins className="h-4 w-4 text-yellow-400" />
                            <span className="font-pixel text-[10px] text-stone-400">Prize Pool</span>
                        </div>
                        <div className="font-pixel text-lg text-yellow-300">{tournament.prize_pool}g</div>
                    </div>

                    {/* Competitors */}
                    <div className="rounded-lg bg-stone-800/50 p-3 text-center">
                        <div className="flex items-center justify-center gap-1">
                            <Users className="h-4 w-4 text-green-400" />
                            <span className="font-pixel text-[10px] text-stone-400">Competitors</span>
                        </div>
                        <div className="font-pixel text-lg text-white">
                            {competitors.filter(c => c.status !== 'withdrew').length}
                            <span className="text-stone-500">/{tournament.type.max_participants}</span>
                        </div>
                    </div>

                    {/* Entry Fee */}
                    <div className="rounded-lg bg-stone-800/50 p-3 text-center">
                        <div className="flex items-center justify-center gap-1">
                            <Coins className="h-4 w-4 text-amber-400" />
                            <span className="font-pixel text-[10px] text-stone-400">Entry Fee</span>
                        </div>
                        <div className="font-pixel text-lg text-white">{tournament.type.entry_fee}g</div>
                    </div>

                    {/* Min Level */}
                    <div className="rounded-lg bg-stone-800/50 p-3 text-center">
                        <div className="flex items-center justify-center gap-1">
                            <Star className="h-4 w-4 text-purple-400" />
                            <span className="font-pixel text-[10px] text-stone-400">Min Level</span>
                        </div>
                        <div className="font-pixel text-lg text-white">{tournament.type.min_level}</div>
                    </div>
                </div>

                {/* Registration Info */}
                {tournament.status === 'registration' && (
                    <div className="grid gap-4 md:grid-cols-2">
                        <div className="rounded-lg bg-stone-800/50 p-3">
                            <div className="flex items-center gap-2 font-pixel text-xs text-stone-400">
                                <Calendar className="h-3 w-3" />
                                Registration closes: {tournament.registration_ends_formatted}
                            </div>
                        </div>
                        {tournament.starts_at_formatted && (
                            <div className="rounded-lg bg-stone-800/50 p-3">
                                <div className="flex items-center gap-2 font-pixel text-xs text-stone-400">
                                    <Clock className="h-3 w-3" />
                                    Tournament starts: {tournament.starts_at_formatted}
                                </div>
                            </div>
                        )}
                    </div>
                )}

                {/* Sponsor Info */}
                {tournament.sponsor_name && (
                    <div className="rounded-lg bg-stone-800/50 p-3">
                        <div className="flex items-center justify-between font-pixel text-xs">
                            <span className="flex items-center gap-2 text-stone-400">
                                <Crown className="h-3 w-3 text-amber-400" />
                                Sponsored by: <span className="text-white">{tournament.sponsor_name}</span>
                            </span>
                            {tournament.sponsor_contribution > 0 && (
                                <span className="text-yellow-400">+{tournament.sponsor_contribution}g to prize pool</span>
                            )}
                        </div>
                    </div>
                )}

                {/* Tournament Bracket */}
                {Object.keys(matches_by_round).length > 0 && (
                    <div className="rounded-xl border-2 border-stone-600/50 bg-stone-800/30 p-4">
                        <h2 className="mb-4 flex items-center gap-2 font-pixel text-lg text-white">
                            <Trophy className="h-5 w-5 text-amber-400" />
                            Tournament Bracket
                        </h2>
                        <div className="overflow-x-auto">
                            <div className="flex gap-6 pb-4" style={{ minWidth: 'max-content' }}>
                                {Object.entries(matches_by_round)
                                    .sort(([a], [b]) => Number(a) - Number(b))
                                    .map(([roundNum, matches]) => (
                                        <div key={roundNum} className="flex flex-col gap-4">
                                            <div className="font-pixel text-sm text-amber-400 text-center">
                                                {getRoundName(Number(roundNum), tournament.total_rounds || 1)}
                                            </div>
                                            <div className="flex flex-col gap-4">
                                                {matches.map((match) => (
                                                    <div
                                                        key={match.id}
                                                        className={`w-56 rounded-lg border ${
                                                            match.status === 'completed'
                                                                ? 'border-stone-600/50 bg-stone-900/50'
                                                                : match.status === 'in_progress'
                                                                ? 'border-amber-500/50 bg-amber-900/20'
                                                                : 'border-stone-700/50 bg-stone-900/30'
                                                        }`}
                                                    >
                                                        {/* Match Header */}
                                                        <div className="border-b border-stone-700/50 px-3 py-1.5 text-center">
                                                            <span className="font-pixel text-[10px] text-stone-500">
                                                                Match {match.match_number}
                                                            </span>
                                                        </div>
                                                        {/* Competitor 1 */}
                                                        <div
                                                            className={`flex items-center justify-between px-3 py-2 ${
                                                                match.winner_id === match.competitor1?.id
                                                                    ? 'bg-green-900/30'
                                                                    : ''
                                                            }`}
                                                        >
                                                            <div className="flex items-center gap-2">
                                                                {match.competitor1?.seed && (
                                                                    <span className="font-pixel text-[10px] text-stone-500">
                                                                        #{match.competitor1.seed}
                                                                    </span>
                                                                )}
                                                                <span className={`font-pixel text-xs ${
                                                                    match.winner_id === match.competitor1?.id
                                                                        ? 'text-green-400'
                                                                        : match.competitor1
                                                                        ? 'text-white'
                                                                        : 'text-stone-600'
                                                                }`}>
                                                                    {match.competitor1?.username || 'TBD'}
                                                                </span>
                                                                {match.winner_id === match.competitor1?.id && (
                                                                    <Trophy className="h-3 w-3 text-amber-400" />
                                                                )}
                                                            </div>
                                                            {match.status === 'completed' && (
                                                                <span className="font-pixel text-xs text-stone-400">
                                                                    {match.competitor1_score}
                                                                </span>
                                                            )}
                                                        </div>
                                                        {/* VS Divider */}
                                                        <div className="border-y border-stone-700/30 bg-stone-800/50 px-3 py-1 text-center">
                                                            <span className="font-pixel text-[10px] text-stone-500">vs</span>
                                                        </div>
                                                        {/* Competitor 2 */}
                                                        <div
                                                            className={`flex items-center justify-between px-3 py-2 ${
                                                                match.winner_id === match.competitor2?.id
                                                                    ? 'bg-green-900/30'
                                                                    : ''
                                                            }`}
                                                        >
                                                            <div className="flex items-center gap-2">
                                                                {match.competitor2?.seed && (
                                                                    <span className="font-pixel text-[10px] text-stone-500">
                                                                        #{match.competitor2.seed}
                                                                    </span>
                                                                )}
                                                                <span className={`font-pixel text-xs ${
                                                                    match.winner_id === match.competitor2?.id
                                                                        ? 'text-green-400'
                                                                        : match.is_bye
                                                                        ? 'text-stone-600 italic'
                                                                        : match.competitor2
                                                                        ? 'text-white'
                                                                        : 'text-stone-600'
                                                                }`}>
                                                                    {match.is_bye ? 'BYE' : (match.competitor2?.username || 'TBD')}
                                                                </span>
                                                                {match.winner_id === match.competitor2?.id && (
                                                                    <Trophy className="h-3 w-3 text-amber-400" />
                                                                )}
                                                            </div>
                                                            {match.status === 'completed' && !match.is_bye && (
                                                                <span className="font-pixel text-xs text-stone-400">
                                                                    {match.competitor2_score}
                                                                </span>
                                                            )}
                                                        </div>
                                                    </div>
                                                ))}
                                            </div>
                                        </div>
                                    ))}
                            </div>
                        </div>
                    </div>
                )}

                {/* Competitors List */}
                <div className="rounded-xl border-2 border-stone-600/50 bg-stone-800/30 p-4">
                    <h2 className="mb-4 flex items-center gap-2 font-pixel text-lg text-white">
                        <Users className="h-5 w-5 text-blue-400" />
                        Competitors
                        <span className="font-pixel text-xs text-stone-400">
                            ({competitors.filter(c => c.status !== 'withdrew').length})
                        </span>
                    </h2>

                    {competitors.length > 0 ? (
                        <div className="overflow-x-auto">
                            <table className="w-full">
                                <thead>
                                    <tr className="border-b border-stone-700/50">
                                        <th className="px-3 py-2 text-left font-pixel text-[10px] text-stone-400">Seed</th>
                                        <th className="px-3 py-2 text-left font-pixel text-[10px] text-stone-400">Name</th>
                                        <th className="px-3 py-2 text-center font-pixel text-[10px] text-stone-400">Status</th>
                                        <th className="px-3 py-2 text-center font-pixel text-[10px] text-stone-400">Record</th>
                                        {tournament.status === 'completed' && (
                                            <th className="px-3 py-2 text-center font-pixel text-[10px] text-stone-400">Place</th>
                                        )}
                                    </tr>
                                </thead>
                                <tbody>
                                    {competitors
                                        .filter(c => c.status !== 'withdrew')
                                        .map((competitor) => {
                                            const statusStyle = competitorStatusColors[competitor.status] || competitorStatusColors.registered;
                                            return (
                                                <tr key={competitor.id} className="border-b border-stone-800/50">
                                                    <td className="px-3 py-2 font-pixel text-xs text-stone-500">
                                                        {competitor.seed ? `#${competitor.seed}` : '-'}
                                                    </td>
                                                    <td className="px-3 py-2">
                                                        <div className="flex items-center gap-2">
                                                            <span className="font-pixel text-xs text-white">
                                                                {competitor.username}
                                                            </span>
                                                            {competitor.status === 'winner' && (
                                                                <Crown className="h-3 w-3 text-amber-400" />
                                                            )}
                                                        </div>
                                                    </td>
                                                    <td className="px-3 py-2 text-center">
                                                        <span className={`rounded px-2 py-0.5 font-pixel text-[10px] capitalize ${statusStyle.bg} ${statusStyle.text}`}>
                                                            {competitor.status}
                                                        </span>
                                                    </td>
                                                    <td className="px-3 py-2 text-center font-pixel text-xs">
                                                        <span className="text-green-400">{competitor.wins}W</span>
                                                        <span className="text-stone-500"> - </span>
                                                        <span className="text-red-400">{competitor.losses}L</span>
                                                    </td>
                                                    {tournament.status === 'completed' && (
                                                        <td className="px-3 py-2 text-center font-pixel text-xs text-stone-400">
                                                            {competitor.final_placement ? `#${competitor.final_placement}` : '-'}
                                                        </td>
                                                    )}
                                                </tr>
                                            );
                                        })}
                                </tbody>
                            </table>
                        </div>
                    ) : (
                        <p className="font-pixel text-sm text-stone-500">
                            No competitors registered yet. Be the first to enter!
                        </p>
                    )}
                </div>

                {/* Register Action */}
                {canRegister && (
                    <div className="rounded-xl border-2 border-red-600/50 bg-red-900/20 p-4">
                        <h2 className="mb-4 flex items-center gap-2 font-pixel text-lg text-white">
                            <Swords className="h-5 w-5 text-red-400" />
                            Enter the Tournament
                        </h2>
                        <div className="mb-4 font-pixel text-xs text-stone-300">
                            <p>Test your combat prowess against other warriors!</p>
                            <ul className="mt-2 space-y-1 text-stone-400">
                                <li className="flex items-center gap-2">
                                    <span className={meetsLevel ? 'text-green-400' : 'text-red-400'}>
                                        {meetsLevel ? '✓' : '✗'}
                                    </span>
                                    Combat Level {tournament.type.min_level}+ required (You: {user_combat_level})
                                </li>
                                <li className="flex items-center gap-2">
                                    <span className={hasGold ? 'text-green-400' : 'text-red-400'}>
                                        {hasGold ? '✓' : '✗'}
                                    </span>
                                    Entry fee: {tournament.type.entry_fee}g (You have: {user_gold}g)
                                </li>
                            </ul>
                        </div>
                        <button
                            onClick={registerForTournament}
                            disabled={registering || !meetsLevel || !hasGold}
                            className="w-full rounded border-2 border-red-600/50 bg-red-900/30 px-4 py-2 font-pixel text-sm text-red-300 transition hover:bg-red-900/50 disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            {registering ? 'Registering...' : `Register (${tournament.type.entry_fee}g)`}
                        </button>
                    </div>
                )}

                {/* Cancelled Tournament Info */}
                {tournament.status === 'cancelled' && (
                    <div className="rounded-xl border-2 border-stone-600/50 bg-stone-800/30 p-4 text-center">
                        <XCircle className="mx-auto mb-2 h-8 w-8 text-stone-500" />
                        <p className="font-pixel text-sm text-stone-400">
                            This tournament has been cancelled
                        </p>
                    </div>
                )}

                {/* Completed Tournament Info */}
                {tournament.status === 'completed' && tournament.completed_at_formatted && (
                    <div className="rounded-xl border-2 border-stone-600/50 bg-stone-800/30 p-4 text-center">
                        <Trophy className="mx-auto mb-2 h-8 w-8 text-amber-400" />
                        <p className="font-pixel text-sm text-stone-300">
                            Tournament completed on {tournament.completed_at_formatted}
                        </p>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
