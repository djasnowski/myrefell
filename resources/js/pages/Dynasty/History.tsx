import { Head, Link, router } from '@inertiajs/react';
import {
    Calendar,
    ChevronLeft,
    ChevronRight,
    Crown,
    Filter,
    History as HistoryIcon,
    Shield,
    TrendingDown,
    TrendingUp,
} from 'lucide-react';
import { useState } from 'react';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

interface DynastyEvent {
    id: number;
    type: string;
    title: string;
    description: string;
    prestige_change: number;
    occurred_at: string;
    year: number;
    member: { id: number; name: string } | null;
}

interface Dynasty {
    id: number;
    name: string;
    prestige: number;
    founded_at: string | null;
}

interface Props {
    dynasty: Dynasty;
    events: {
        data: DynastyEvent[];
        current_page: number;
        last_page: number;
        total: number;
    };
    stats: {
        total_events: number;
        births: number;
        deaths: number;
        marriages: number;
        total_prestige_gained: number;
        total_prestige_lost: number;
    };
    filter: string | null;
}

const EVENT_TYPES = [
    { value: '', label: 'All Events', icon: '📜' },
    { value: 'birth', label: 'Births', icon: '🎂' },
    { value: 'death', label: 'Deaths', icon: '💀' },
    { value: 'marriage', label: 'Marriages', icon: '💍' },
    { value: 'divorce', label: 'Divorces', icon: '💔' },
    { value: 'succession', label: 'Successions', icon: '👑' },
    { value: 'achievement', label: 'Achievements', icon: '🏆' },
    { value: 'scandal', label: 'Scandals', icon: '😱' },
    { value: 'alliance', label: 'Alliances', icon: '🤝' },
    { value: 'inheritance', label: 'Inheritances', icon: '📜' },
];

export default function DynastyHistory({
    dynasty,
    events,
    stats,
    filter,
}: Props) {
    const [selectedFilter, setSelectedFilter] = useState(filter || '');

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Dynasty', href: '/dynasty' },
        { title: 'History', href: '/dynasty/history' },
    ];

    const getEventIcon = (type: string): React.ReactNode => {
        if (type === 'succession') {
            return <Crown className="h-5 w-5 text-amber-400" />;
        }
        if (type === 'alliance') {
            return <Shield className="h-5 w-5 text-blue-400" />;
        }
        const found = EVENT_TYPES.find(t => t.value === type);
        return found?.icon || '📌';
    };

    const handleFilterChange = (value: string) => {
        setSelectedFilter(value);
        router.get('/dynasty/history', value ? { filter: value } : {}, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const goToPage = (page: number) => {
        router.get('/dynasty/history', {
            page,
            ...(selectedFilter ? { filter: selectedFilter } : {}),
        }, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    // Group events by year
    const eventsByYear = events.data.reduce((acc, event) => {
        const year = event.year;
        if (!acc[year]) {
            acc[year] = [];
        }
        acc[year].push(event);
        return acc;
    }, {} as Record<number, DynastyEvent[]>);

    const years = Object.keys(eventsByYear).map(Number).sort((a, b) => b - a);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`House ${dynasty.name} - History`} />
            <div className="flex h-full flex-1 flex-col overflow-auto p-4">
                {/* Header */}
                <div className="mb-6 flex items-center gap-4">
                    <Link
                        href="/dynasty"
                        className="rounded-lg border border-stone-700 p-2 transition hover:bg-stone-800"
                    >
                        <ChevronLeft className="h-5 w-5 text-stone-400" />
                    </Link>
                    <div className="flex h-12 w-12 items-center justify-center rounded-lg bg-amber-900/30">
                        <HistoryIcon className="h-7 w-7 text-amber-400" />
                    </div>
                    <div>
                        <h1 className="font-pixel text-xl text-amber-400">Dynasty History</h1>
                        <p className="font-pixel text-xs text-stone-500">
                            Chronicle of House {dynasty.name}
                        </p>
                    </div>
                </div>

                <div className="mx-auto w-full max-w-4xl">
                    {/* Stats Overview */}
                    <div className="mb-4 grid grid-cols-2 gap-3 md:grid-cols-4">
                        <div className="rounded-lg border border-stone-700 bg-stone-800/50 p-3 text-center">
                            <div className="font-pixel text-lg text-stone-300">{stats.total_events}</div>
                            <div className="font-pixel text-[10px] text-stone-500">Total Events</div>
                        </div>
                        <div className="rounded-lg border border-blue-600/50 bg-blue-900/20 p-3 text-center">
                            <div className="font-pixel text-lg text-blue-400">{stats.births}</div>
                            <div className="font-pixel text-[10px] text-stone-500">Births</div>
                        </div>
                        <div className="rounded-lg border border-pink-600/50 bg-pink-900/20 p-3 text-center">
                            <div className="font-pixel text-lg text-pink-400">{stats.marriages}</div>
                            <div className="font-pixel text-[10px] text-stone-500">Marriages</div>
                        </div>
                        <div className="rounded-lg border border-stone-600 bg-stone-800/50 p-3 text-center">
                            <div className="flex items-center justify-center gap-2">
                                {stats.total_prestige_gained - stats.total_prestige_lost >= 0 ? (
                                    <TrendingUp className="h-4 w-4 text-green-400" />
                                ) : (
                                    <TrendingDown className="h-4 w-4 text-red-400" />
                                )}
                                <span className={`font-pixel text-lg ${
                                    stats.total_prestige_gained - stats.total_prestige_lost >= 0
                                        ? 'text-green-400'
                                        : 'text-red-400'
                                }`}>
                                    {stats.total_prestige_gained - stats.total_prestige_lost >= 0 ? '+' : ''}
                                    {stats.total_prestige_gained - stats.total_prestige_lost}
                                </span>
                            </div>
                            <div className="font-pixel text-[10px] text-stone-500">Net Prestige</div>
                        </div>
                    </div>

                    {/* Filter */}
                    <div className="mb-4 rounded-xl border-2 border-stone-700 bg-stone-800/50 p-4">
                        <div className="flex items-center gap-2 mb-3">
                            <Filter className="h-4 w-4 text-stone-400" />
                            <span className="font-pixel text-xs text-stone-400">Filter by Event Type</span>
                        </div>
                        <div className="flex flex-wrap gap-2">
                            {EVENT_TYPES.map((type) => (
                                <button
                                    key={type.value}
                                    onClick={() => handleFilterChange(type.value)}
                                    className={`flex items-center gap-1.5 rounded-lg border px-3 py-1.5 font-pixel text-xs transition ${
                                        selectedFilter === type.value
                                            ? 'border-amber-600 bg-amber-900/30 text-amber-400'
                                            : 'border-stone-700 bg-stone-800/30 text-stone-400 hover:bg-stone-700'
                                    }`}
                                >
                                    <span>{type.icon}</span>
                                    {type.label}
                                </button>
                            ))}
                        </div>
                    </div>

                    {/* Events Timeline */}
                    <div className="rounded-xl border-2 border-stone-700 bg-stone-800/50 p-4">
                        <h2 className="mb-4 flex items-center gap-2 font-pixel text-sm text-stone-300">
                            <Calendar className="h-4 w-4 text-amber-400" />
                            Chronicle ({events.total} events)
                        </h2>

                        {events.data.length === 0 ? (
                            <div className="py-12 text-center">
                                <HistoryIcon className="mx-auto mb-3 h-12 w-12 text-stone-600" />
                                <div className="font-pixel text-sm text-stone-500">No events recorded</div>
                                <p className="mt-1 font-pixel text-xs text-stone-600">
                                    {selectedFilter
                                        ? 'Try a different filter'
                                        : 'Your dynasty history will appear here'}
                                </p>
                            </div>
                        ) : (
                            <div className="space-y-6">
                                {years.map((year) => (
                                    <div key={year}>
                                        {/* Year Header */}
                                        <div className="mb-3 flex items-center gap-3">
                                            <div className="rounded-lg bg-amber-900/30 px-3 py-1">
                                                <span className="font-pixel text-sm text-amber-400">Year {year}</span>
                                            </div>
                                            <div className="flex-1 h-px bg-stone-700" />
                                        </div>

                                        {/* Events for Year */}
                                        <div className="space-y-2 pl-4 border-l-2 border-stone-700">
                                            {eventsByYear[year].map((event) => (
                                                <div
                                                    key={event.id}
                                                    className={`relative flex items-start gap-3 rounded-lg border p-3 ${
                                                        event.prestige_change > 0
                                                            ? 'border-green-600/30 bg-green-900/10'
                                                            : event.prestige_change < 0
                                                                ? 'border-red-600/30 bg-red-900/10'
                                                                : 'border-stone-700 bg-stone-800/30'
                                                    }`}
                                                >
                                                    {/* Timeline dot */}
                                                    <div className="absolute -left-[1.4rem] top-4 h-2 w-2 rounded-full bg-stone-600" />

                                                    <div className="text-xl">{getEventIcon(event.type)}</div>
                                                    <div className="flex-1">
                                                        <div className="flex items-start justify-between gap-2">
                                                            <div>
                                                                <span className="font-pixel text-sm text-stone-200">
                                                                    {event.title}
                                                                </span>
                                                                {event.member && (
                                                                    <span className="ml-2 font-pixel text-xs text-stone-500">
                                                                        ({event.member.name})
                                                                    </span>
                                                                )}
                                                            </div>
                                                            <span className="whitespace-nowrap font-pixel text-[10px] text-stone-500">
                                                                {event.occurred_at}
                                                            </span>
                                                        </div>
                                                        {event.description && (
                                                            <p className="mt-1 font-pixel text-xs text-stone-400">
                                                                {event.description}
                                                            </p>
                                                        )}
                                                        {event.prestige_change !== 0 && (
                                                            <div className={`mt-2 inline-flex items-center gap-1 rounded px-2 py-0.5 font-pixel text-[10px] ${
                                                                event.prestige_change > 0
                                                                    ? 'bg-green-900/30 text-green-400'
                                                                    : 'bg-red-900/30 text-red-400'
                                                            }`}>
                                                                {event.prestige_change > 0 ? (
                                                                    <TrendingUp className="h-3 w-3" />
                                                                ) : (
                                                                    <TrendingDown className="h-3 w-3" />
                                                                )}
                                                                {event.prestige_change > 0 ? '+' : ''}{event.prestige_change} prestige
                                                            </div>
                                                        )}
                                                    </div>
                                                </div>
                                            ))}
                                        </div>
                                    </div>
                                ))}
                            </div>
                        )}

                        {/* Pagination */}
                        {events.last_page > 1 && (
                            <div className="mt-6 flex items-center justify-center gap-2">
                                <button
                                    onClick={() => goToPage(events.current_page - 1)}
                                    disabled={events.current_page === 1}
                                    className="rounded-lg border border-stone-700 p-2 transition hover:bg-stone-700 disabled:opacity-50"
                                >
                                    <ChevronLeft className="h-4 w-4 text-stone-400" />
                                </button>
                                <span className="font-pixel text-xs text-stone-400">
                                    Page {events.current_page} of {events.last_page}
                                </span>
                                <button
                                    onClick={() => goToPage(events.current_page + 1)}
                                    disabled={events.current_page === events.last_page}
                                    className="rounded-lg border border-stone-700 p-2 transition hover:bg-stone-700 disabled:opacity-50"
                                >
                                    <ChevronRight className="h-4 w-4 text-stone-400" />
                                </button>
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
