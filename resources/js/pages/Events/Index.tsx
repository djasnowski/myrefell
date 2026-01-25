import { Head, router, usePage } from '@inertiajs/react';
import {
    Calendar,
    CalendarDays,
    Coins,
    Crown,
    MapPin,
    PartyPopper,
    Skull,
    Swords,
    Target,
    Trophy,
    Users,
} from 'lucide-react';
import { useState } from 'react';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

interface FestivalType {
    id: number;
    name: string;
    category: string;
    description: string;
    activities: string[];
    bonuses: Record<string, number>;
}

interface Festival {
    id: number;
    name: string;
    type: FestivalType;
    status: 'scheduled' | 'active' | 'completed' | 'cancelled';
    location_type: string;
    location_id: number;
    location_name: string;
    starts_at: string;
    ends_at: string;
    starts_at_formatted: string;
    ends_at_formatted: string;
    organizer_name: string | null;
    participant_count: number;
    is_participating: boolean;
    has_tournaments: boolean;
}

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
    registration_ends_at: string;
    registration_ends_formatted: string;
    starts_at: string;
    starts_at_formatted: string;
    prize_pool: number;
    competitor_count: number;
    current_round: number | null;
    total_rounds: number | null;
    is_registered: boolean;
    user_status: string | null;
    festival_id: number | null;
    festival_name: string | null;
    is_registration_open: boolean;
}

interface RoyalEvent {
    id: number;
    event_type: string;
    event_type_name: string;
    title: string;
    description: string | null;
    status: 'scheduled' | 'active' | 'completed' | 'cancelled';
    location_type: string;
    location_id: number;
    location_name: string;
    scheduled_at: string;
    scheduled_at_formatted: string;
    primary_participant_name: string | null;
    secondary_participant_name: string | null;
}

interface CalendarData {
    year: number;
    season: 'spring' | 'summer' | 'autumn' | 'winter';
    week: number;
    formatted_date: string;
}

interface PageProps {
    active_festivals: Festival[];
    upcoming_festivals: Festival[];
    registration_open_tournaments: Tournament[];
    in_progress_tournaments: Tournament[];
    upcoming_royal_events: RoyalEvent[];
    calendar: CalendarData;
    user_gold: number;
    user_combat_level: number;
    [key: string]: unknown;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Events', href: '/events' },
];

const festivalCategoryColors: Record<string, { bg: string; text: string; border: string }> = {
    seasonal: { bg: 'bg-green-900/30', text: 'text-green-400', border: 'border-green-600/50' },
    religious: { bg: 'bg-purple-900/30', text: 'text-purple-400', border: 'border-purple-600/50' },
    royal: { bg: 'bg-amber-900/30', text: 'text-amber-400', border: 'border-amber-600/50' },
    special: { bg: 'bg-blue-900/30', text: 'text-blue-400', border: 'border-blue-600/50' },
};

const combatTypeIcons: Record<string, typeof Swords> = {
    melee: Swords,
    joust: Target,
    archery: Target,
    wrestling: Users,
    mixed: Swords,
};

const statusColors: Record<string, { bg: string; text: string }> = {
    scheduled: { bg: 'bg-blue-900/30', text: 'text-blue-400' },
    active: { bg: 'bg-green-900/30', text: 'text-green-400' },
    registration: { bg: 'bg-amber-900/30', text: 'text-amber-400' },
    in_progress: { bg: 'bg-red-900/30', text: 'text-red-400' },
    completed: { bg: 'bg-stone-900/30', text: 'text-stone-400' },
    cancelled: { bg: 'bg-stone-900/30', text: 'text-stone-500' },
};

export default function EventsIndex() {
    const {
        active_festivals,
        upcoming_festivals,
        registration_open_tournaments,
        in_progress_tournaments,
        upcoming_royal_events,
        calendar,
        user_gold,
        user_combat_level,
    } = usePage<PageProps>().props;

    const [joiningFestival, setJoiningFestival] = useState<number | null>(null);
    const [registeringTournament, setRegisteringTournament] = useState<number | null>(null);
    const [error, setError] = useState<string | null>(null);
    const [success, setSuccess] = useState<string | null>(null);

    const joinFestival = async (festivalId: number) => {
        setJoiningFestival(festivalId);
        setError(null);

        try {
            const response = await fetch(`/events/festivals/${festivalId}/join`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content || '',
                },
                body: JSON.stringify({ role: 'attendee' }),
            });

            const data = await response.json();
            if (data.success) {
                setSuccess('You have joined the festival!');
                router.reload();
            } else {
                setError(data.message);
            }
        } catch {
            setError('Failed to join festival');
        } finally {
            setJoiningFestival(null);
        }
    };

    const registerForTournament = async (tournamentId: number) => {
        setRegisteringTournament(tournamentId);
        setError(null);

        try {
            const response = await fetch(`/events/tournaments/${tournamentId}/register`, {
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
            setRegisteringTournament(null);
        }
    };

    const renderFestivalCard = (festival: Festival) => {
        const categoryStyle = festivalCategoryColors[festival.type.category] || festivalCategoryColors.special;
        const statusStyle = statusColors[festival.status] || statusColors.scheduled;

        return (
            <div
                key={festival.id}
                className={`rounded-xl border-2 ${categoryStyle.border} ${categoryStyle.bg} p-4`}
            >
                {/* Header */}
                <div className="mb-3 flex items-start justify-between">
                    <div className="flex items-center gap-2">
                        <PartyPopper className={`h-5 w-5 ${categoryStyle.text}`} />
                        <h3 className="font-pixel text-base text-white">{festival.name}</h3>
                    </div>
                    <span className={`rounded px-2 py-1 font-pixel text-[10px] capitalize ${statusStyle.bg} ${statusStyle.text}`}>
                        {festival.status}
                    </span>
                </div>

                {/* Location & Dates */}
                <div className="mb-3 rounded-lg bg-stone-900/50 p-2">
                    <div className="mb-1 flex items-center gap-2 font-pixel text-xs">
                        <MapPin className="h-3 w-3 text-amber-400" />
                        <span className="text-stone-400">Location:</span>
                        <span className="capitalize text-white">{festival.location_name}</span>
                    </div>
                    <div className="flex items-center gap-2 font-pixel text-xs">
                        <CalendarDays className="h-3 w-3 text-blue-400" />
                        <span className="text-stone-400">
                            {festival.status === 'active' ? 'Until:' : 'Starts:'}
                        </span>
                        <span className="text-white">
                            {festival.status === 'active' ? festival.ends_at_formatted : festival.starts_at_formatted}
                        </span>
                    </div>
                </div>

                {/* Description */}
                {festival.type.description && (
                    <p className="mb-3 font-pixel text-[10px] text-stone-400">
                        {festival.type.description}
                    </p>
                )}

                {/* Activities */}
                {festival.type.activities && festival.type.activities.length > 0 && (
                    <div className="mb-3">
                        <div className="mb-1 font-pixel text-[10px] text-stone-500">Activities:</div>
                        <div className="flex flex-wrap gap-1">
                            {festival.type.activities.map((activity, i) => (
                                <span
                                    key={i}
                                    className="rounded bg-stone-700/50 px-2 py-0.5 font-pixel text-[10px] capitalize text-stone-300"
                                >
                                    {activity}
                                </span>
                            ))}
                        </div>
                    </div>
                )}

                {/* Stats */}
                <div className="mb-3 flex items-center gap-4 font-pixel text-xs">
                    <div className="flex items-center gap-1">
                        <Users className="h-3 w-3 text-blue-400" />
                        <span className="text-stone-400">Participants:</span>
                        <span className="text-white">{festival.participant_count}</span>
                    </div>
                    {festival.has_tournaments && (
                        <div className="flex items-center gap-1">
                            <Trophy className="h-3 w-3 text-amber-400" />
                            <span className="text-amber-300">Has Tournaments</span>
                        </div>
                    )}
                </div>

                {/* Action */}
                {festival.status === 'active' && !festival.is_participating && (
                    <button
                        onClick={() => joinFestival(festival.id)}
                        disabled={joiningFestival === festival.id}
                        className={`w-full rounded border ${categoryStyle.border} ${categoryStyle.bg} px-3 py-1.5 font-pixel text-xs ${categoryStyle.text} transition hover:brightness-125 disabled:cursor-not-allowed disabled:opacity-50`}
                    >
                        {joiningFestival === festival.id ? 'Joining...' : 'Participate'}
                    </button>
                )}
                {festival.is_participating && (
                    <div className="rounded bg-green-900/30 px-3 py-1.5 text-center font-pixel text-xs text-green-400">
                        You are participating
                    </div>
                )}
            </div>
        );
    };

    const renderTournamentCard = (tournament: Tournament) => {
        const CombatIcon = combatTypeIcons[tournament.type.combat_type] || Swords;
        const statusStyle = statusColors[tournament.status] || statusColors.registration;
        const canRegister = tournament.is_registration_open && !tournament.is_registered;
        const meetsLevel = user_combat_level >= tournament.type.min_level;
        const hasGold = user_gold >= tournament.type.entry_fee;

        return (
            <div
                key={tournament.id}
                className="rounded-xl border-2 border-red-600/50 bg-red-900/20 p-4"
            >
                {/* Header */}
                <div className="mb-3 flex items-start justify-between">
                    <div className="flex items-center gap-2">
                        <CombatIcon className="h-5 w-5 text-red-400" />
                        <h3 className="font-pixel text-base text-white">{tournament.name}</h3>
                    </div>
                    <div className="flex items-center gap-2">
                        {tournament.type.is_lethal && (
                            <span className="flex items-center gap-1 rounded bg-red-900/50 px-1.5 py-0.5 font-pixel text-[10px] text-red-300">
                                <Skull className="h-3 w-3" />
                                Lethal
                            </span>
                        )}
                        <span className={`rounded px-2 py-1 font-pixel text-[10px] capitalize ${statusStyle.bg} ${statusStyle.text}`}>
                            {tournament.status.replace('_', ' ')}
                        </span>
                    </div>
                </div>

                {/* Location & Dates */}
                <div className="mb-3 rounded-lg bg-stone-900/50 p-2">
                    <div className="mb-1 flex items-center gap-2 font-pixel text-xs">
                        <MapPin className="h-3 w-3 text-amber-400" />
                        <span className="text-stone-400">Location:</span>
                        <span className="capitalize text-white">{tournament.location_name}</span>
                    </div>
                    {tournament.status === 'registration' && (
                        <div className="flex items-center gap-2 font-pixel text-xs">
                            <CalendarDays className="h-3 w-3 text-blue-400" />
                            <span className="text-stone-400">Registration ends:</span>
                            <span className="text-white">{tournament.registration_ends_formatted}</span>
                        </div>
                    )}
                    {tournament.status === 'in_progress' && tournament.current_round && (
                        <div className="flex items-center gap-2 font-pixel text-xs">
                            <Trophy className="h-3 w-3 text-amber-400" />
                            <span className="text-stone-400">Round:</span>
                            <span className="text-white">{tournament.current_round} / {tournament.total_rounds}</span>
                        </div>
                    )}
                </div>

                {/* Tournament Type Info */}
                <div className="mb-3 rounded-lg bg-stone-800/50 p-2">
                    <div className="mb-1 font-pixel text-xs capitalize text-red-300">
                        {tournament.type.name} - {tournament.type.combat_type}
                    </div>
                    {tournament.type.description && (
                        <p className="font-pixel text-[10px] text-stone-400">
                            {tournament.type.description}
                        </p>
                    )}
                </div>

                {/* Stats Grid */}
                <div className="mb-3 grid grid-cols-3 gap-2">
                    <div className="rounded-lg bg-stone-900/30 p-2 text-center">
                        <div className="flex items-center justify-center gap-1">
                            <Coins className="h-3 w-3 text-yellow-400" />
                            <span className="font-pixel text-[10px] text-stone-400">Prize</span>
                        </div>
                        <div className="font-pixel text-sm text-yellow-300">{tournament.prize_pool}g</div>
                    </div>
                    <div className="rounded-lg bg-stone-900/30 p-2 text-center">
                        <div className="flex items-center justify-center gap-1">
                            <Users className="h-3 w-3 text-blue-400" />
                            <span className="font-pixel text-[10px] text-stone-400">Competitors</span>
                        </div>
                        <div className="font-pixel text-sm text-white">
                            {tournament.competitor_count}/{tournament.type.max_participants}
                        </div>
                    </div>
                    <div className="rounded-lg bg-stone-900/30 p-2 text-center">
                        <div className="flex items-center justify-center gap-1">
                            <Swords className="h-3 w-3 text-red-400" />
                            <span className="font-pixel text-[10px] text-stone-400">Entry</span>
                        </div>
                        <div className="font-pixel text-sm text-white">{tournament.type.entry_fee}g</div>
                    </div>
                </div>

                {/* Requirements */}
                {canRegister && (
                    <div className="mb-3 font-pixel text-[10px]">
                        <span className="text-stone-500">Requirements: </span>
                        <span className={meetsLevel ? 'text-green-400' : 'text-red-400'}>
                            Lvl {tournament.type.min_level}+
                        </span>
                        {tournament.type.entry_fee > 0 && (
                            <>
                                <span className="text-stone-500"> | </span>
                                <span className={hasGold ? 'text-green-400' : 'text-red-400'}>
                                    {tournament.type.entry_fee}g entry
                                </span>
                            </>
                        )}
                    </div>
                )}

                {/* Festival Link */}
                {tournament.festival_name && (
                    <div className="mb-3 flex items-center gap-1 font-pixel text-[10px] text-stone-400">
                        <PartyPopper className="h-3 w-3" />
                        Part of: <span className="text-amber-300">{tournament.festival_name}</span>
                    </div>
                )}

                {/* Action */}
                {canRegister && (
                    <button
                        onClick={() => registerForTournament(tournament.id)}
                        disabled={registeringTournament === tournament.id || !meetsLevel || !hasGold}
                        className="w-full rounded border border-red-600/50 bg-red-900/20 px-3 py-1.5 font-pixel text-xs text-red-300 transition hover:bg-red-900/40 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        {registeringTournament === tournament.id
                            ? 'Registering...'
                            : `Register (${tournament.type.entry_fee}g)`}
                    </button>
                )}
                {tournament.is_registered && (
                    <div className="rounded bg-green-900/30 px-3 py-1.5 text-center font-pixel text-xs text-green-400">
                        Registered {tournament.user_status && tournament.user_status !== 'registered' && (
                            <span className="capitalize">- {tournament.user_status}</span>
                        )}
                    </div>
                )}
            </div>
        );
    };

    const renderRoyalEventCard = (event: RoyalEvent) => {
        const statusStyle = statusColors[event.status] || statusColors.scheduled;

        return (
            <div
                key={event.id}
                className="rounded-xl border-2 border-amber-600/50 bg-amber-900/20 p-4"
            >
                {/* Header */}
                <div className="mb-3 flex items-start justify-between">
                    <div className="flex items-center gap-2">
                        <Crown className="h-5 w-5 text-amber-400" />
                        <h3 className="font-pixel text-base text-white">{event.title}</h3>
                    </div>
                    <span className={`rounded px-2 py-1 font-pixel text-[10px] capitalize ${statusStyle.bg} ${statusStyle.text}`}>
                        {event.status}
                    </span>
                </div>

                {/* Event Type */}
                <div className="mb-3">
                    <span className="rounded bg-amber-900/50 px-2 py-1 font-pixel text-[10px] text-amber-300">
                        {event.event_type_name}
                    </span>
                </div>

                {/* Location & Date */}
                <div className="mb-3 rounded-lg bg-stone-900/50 p-2">
                    <div className="mb-1 flex items-center gap-2 font-pixel text-xs">
                        <MapPin className="h-3 w-3 text-amber-400" />
                        <span className="text-stone-400">Location:</span>
                        <span className="capitalize text-white">{event.location_name}</span>
                    </div>
                    <div className="flex items-center gap-2 font-pixel text-xs">
                        <CalendarDays className="h-3 w-3 text-blue-400" />
                        <span className="text-stone-400">Date:</span>
                        <span className="text-white">{event.scheduled_at_formatted}</span>
                    </div>
                </div>

                {/* Participants */}
                {event.primary_participant_name && (
                    <div className="rounded-lg bg-stone-800/50 p-2 font-pixel text-xs">
                        <div className="flex items-center gap-2">
                            <span className="text-stone-400">Participants:</span>
                            <span className="text-white">{event.primary_participant_name}</span>
                            {event.secondary_participant_name && (
                                <>
                                    <span className="text-stone-500">&</span>
                                    <span className="text-white">{event.secondary_participant_name}</span>
                                </>
                            )}
                        </div>
                    </div>
                )}

                {/* Description */}
                {event.description && (
                    <p className="mt-3 font-pixel text-[10px] text-stone-400">
                        {event.description}
                    </p>
                )}
            </div>
        );
    };

    const hasNoEvents =
        active_festivals.length === 0 &&
        upcoming_festivals.length === 0 &&
        registration_open_tournaments.length === 0 &&
        in_progress_tournaments.length === 0 &&
        upcoming_royal_events.length === 0;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Events Calendar" />
            <div className="flex h-full flex-1 flex-col gap-6 overflow-y-auto p-4">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="font-pixel text-2xl text-amber-400">Events Calendar</h1>
                        <p className="font-pixel text-sm text-stone-400">
                            {calendar.formatted_date} - {calendar.season.charAt(0).toUpperCase() + calendar.season.slice(1)}, Year {calendar.year}
                        </p>
                    </div>
                    <div className="flex items-center gap-2 rounded-lg bg-stone-800/50 px-3 py-2">
                        <Calendar className="h-4 w-4 text-amber-400" />
                        <span className="font-pixel text-xs text-stone-300">
                            Week {calendar.week}
                        </span>
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

                {/* Active Festivals */}
                {active_festivals.length > 0 && (
                    <div>
                        <h2 className="mb-3 flex items-center gap-2 font-pixel text-lg text-green-400">
                            <PartyPopper className="h-5 w-5" />
                            Active Festivals
                        </h2>
                        <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                            {active_festivals.map(renderFestivalCard)}
                        </div>
                    </div>
                )}

                {/* Upcoming Festivals */}
                {upcoming_festivals.length > 0 && (
                    <div>
                        <h2 className="mb-3 flex items-center gap-2 font-pixel text-lg text-blue-400">
                            <CalendarDays className="h-5 w-5" />
                            Upcoming Festivals
                        </h2>
                        <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                            {upcoming_festivals.map(renderFestivalCard)}
                        </div>
                    </div>
                )}

                {/* Tournaments - Registration Open */}
                {registration_open_tournaments.length > 0 && (
                    <div>
                        <h2 className="mb-3 flex items-center gap-2 font-pixel text-lg text-amber-400">
                            <Trophy className="h-5 w-5" />
                            Tournaments - Registration Open
                        </h2>
                        <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                            {registration_open_tournaments.map(renderTournamentCard)}
                        </div>
                    </div>
                )}

                {/* Tournaments - In Progress */}
                {in_progress_tournaments.length > 0 && (
                    <div>
                        <h2 className="mb-3 flex items-center gap-2 font-pixel text-lg text-red-400">
                            <Swords className="h-5 w-5" />
                            Tournaments - In Progress
                        </h2>
                        <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                            {in_progress_tournaments.map(renderTournamentCard)}
                        </div>
                    </div>
                )}

                {/* Royal Events */}
                {upcoming_royal_events.length > 0 && (
                    <div>
                        <h2 className="mb-3 flex items-center gap-2 font-pixel text-lg text-amber-300">
                            <Crown className="h-5 w-5" />
                            Royal Events
                        </h2>
                        <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                            {upcoming_royal_events.map(renderRoyalEventCard)}
                        </div>
                    </div>
                )}

                {/* Empty State */}
                {hasNoEvents && (
                    <div className="flex flex-1 items-center justify-center">
                        <div className="text-center">
                            <Calendar className="mx-auto mb-3 h-16 w-16 text-stone-600" />
                            <p className="font-pixel text-base text-stone-500">No events scheduled</p>
                            <p className="font-pixel text-xs text-stone-600">
                                Check back later for festivals, tournaments, and royal events!
                            </p>
                        </div>
                    </div>
                )}

                {/* Legend */}
                <div className="rounded-lg border border-stone-700 bg-stone-800/30 p-4">
                    <h3 className="mb-3 font-pixel text-sm text-stone-300">Event Types</h3>
                    <div className="grid grid-cols-2 gap-4 md:grid-cols-4">
                        <div className="flex items-center gap-2">
                            <PartyPopper className="h-4 w-4 text-green-400" />
                            <span className="font-pixel text-[10px] text-stone-400">Festivals</span>
                        </div>
                        <div className="flex items-center gap-2">
                            <Trophy className="h-4 w-4 text-amber-400" />
                            <span className="font-pixel text-[10px] text-stone-400">Tournaments</span>
                        </div>
                        <div className="flex items-center gap-2">
                            <Crown className="h-4 w-4 text-amber-300" />
                            <span className="font-pixel text-[10px] text-stone-400">Royal Events</span>
                        </div>
                        <div className="flex items-center gap-2">
                            <Skull className="h-4 w-4 text-red-400" />
                            <span className="font-pixel text-[10px] text-stone-400">Lethal Combat</span>
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
