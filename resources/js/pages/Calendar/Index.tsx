import { Head, usePage } from "@inertiajs/react";
import {
    Calendar,
    Cloud,
    CloudRain,
    Leaf,
    Snowflake,
    Sun,
    TrendingDown,
    TrendingUp,
} from "lucide-react";
import AppLayout from "@/layouts/app-layout";
import type { BreadcrumbItem } from "@/types";

interface CalendarData {
    year: number;
    season: "spring" | "summer" | "autumn" | "winter";
    week: number;
    week_of_year: number;
    day: number;
    day_of_year: number;
    formatted_date: string;
    season_description: string;
    travel_modifier: number;
    gathering_modifier: number;
    last_tick_at: string | null;
}

interface PageProps {
    calendar: CalendarData;
    [key: string]: unknown;
}

const seasonConfig = {
    spring: {
        icon: Cloud,
        gradient: "from-green-900/50 to-stone-900",
        border: "border-green-600/50",
        text: "text-green-400",
        accent: "bg-green-500",
    },
    summer: {
        icon: Sun,
        gradient: "from-yellow-900/50 to-stone-900",
        border: "border-yellow-600/50",
        text: "text-yellow-400",
        accent: "bg-yellow-500",
    },
    autumn: {
        icon: Leaf,
        gradient: "from-orange-900/50 to-stone-900",
        border: "border-orange-600/50",
        text: "text-orange-400",
        accent: "bg-orange-500",
    },
    winter: {
        icon: Snowflake,
        gradient: "from-blue-900/50 to-stone-900",
        border: "border-blue-600/50",
        text: "text-blue-400",
        accent: "bg-blue-500",
    },
};

export default function CalendarIndex() {
    const { calendar } = usePage<PageProps>().props;
    const config = seasonConfig[calendar.season];
    const SeasonIcon = config.icon;

    const breadcrumbs: BreadcrumbItem[] = [
        { title: "Dashboard", href: "/dashboard" },
        { title: "Calendar", href: "/calendar" },
    ];

    const formatModifier = (modifier: number) => {
        const percentage = Math.round((modifier - 1) * 100);
        if (percentage === 0) return "Normal";
        return percentage > 0 ? `+${percentage}%` : `${percentage}%`;
    };

    const getModifierColor = (modifier: number, isInverse: boolean = false) => {
        if (modifier === 1) return "text-stone-400";
        const isBetter = isInverse ? modifier > 1 : modifier < 1;
        return isBetter ? "text-green-400" : "text-red-400";
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Calendar" />
            <div className="flex h-full flex-1 flex-col p-4">
                <div className="w-full">
                    {/* Header */}
                    <div
                        className={`mb-6 rounded-xl border-2 ${config.border} bg-gradient-to-br ${config.gradient} p-6`}
                    >
                        <div className="flex items-center gap-4">
                            <div className="rounded-lg bg-stone-800/50 p-4">
                                <SeasonIcon className={`h-12 w-12 ${config.text}`} />
                            </div>
                            <div>
                                <h1 className={`font-pixel text-2xl ${config.text}`}>
                                    World Calendar
                                </h1>
                                <p className="font-pixel text-xs text-stone-400">
                                    Track the passage of time in Myrefell
                                </p>
                            </div>
                        </div>
                    </div>

                    {/* Current Date Display */}
                    <div className="mb-6 rounded-xl border-2 border-stone-600 bg-stone-800/50 p-6 text-center">
                        <div className="flex items-center justify-center gap-2 mb-2">
                            <Calendar className="h-5 w-5 text-amber-400" />
                            <span className="font-pixel text-sm text-stone-400">Current Date</span>
                        </div>
                        <div className={`font-pixel text-3xl ${config.text}`}>
                            {calendar.formatted_date}
                        </div>
                        <div className="mt-2 font-pixel text-xs text-stone-500">
                            Week {calendar.week_of_year} of 52
                        </div>
                    </div>

                    {/* Season Info */}
                    <div
                        className={`mb-6 rounded-xl border-2 ${config.border} bg-gradient-to-br ${config.gradient} p-4`}
                    >
                        <div className="flex items-start gap-3">
                            <SeasonIcon className={`h-6 w-6 ${config.text} mt-0.5`} />
                            <div>
                                <h3 className={`font-pixel text-lg ${config.text} capitalize`}>
                                    {calendar.season}
                                </h3>
                                <p className="font-pixel text-xs text-stone-400 mt-1">
                                    {calendar.season_description}
                                </p>
                            </div>
                        </div>
                    </div>

                    {/* Week Progress */}
                    <div className="mb-6 rounded-lg border border-stone-700 bg-stone-800/50 p-4">
                        <div className="flex items-center justify-between mb-2">
                            <span className="font-pixel text-xs text-stone-400">Week Progress</span>
                            <span className="font-pixel text-xs text-stone-500">
                                Day {calendar.day} of 7
                            </span>
                        </div>
                        <div className="h-3 w-full overflow-hidden rounded-full bg-stone-700">
                            <div
                                className={`h-full ${config.accent} transition-all`}
                                style={{ width: `${(calendar.day / 7) * 100}%` }}
                            />
                        </div>
                    </div>

                    {/* Season Progress */}
                    <div className="mb-6 rounded-lg border border-stone-700 bg-stone-800/50 p-4">
                        <div className="flex items-center justify-between mb-2">
                            <span className="font-pixel text-xs text-stone-400">
                                Season Progress
                            </span>
                            <span className="font-pixel text-xs text-stone-500">
                                Week {calendar.week} of 13
                            </span>
                        </div>
                        <div className="h-3 w-full overflow-hidden rounded-full bg-stone-700">
                            <div
                                className={`h-full ${config.accent} transition-all`}
                                style={{ width: `${(calendar.week / 13) * 100}%` }}
                            />
                        </div>
                        <div className="flex justify-between mt-2">
                            {["Early", "Mid", "Late"].map((phase, i) => (
                                <span
                                    key={phase}
                                    className={`font-pixel text-[10px] ${
                                        Math.ceil(calendar.week / 4) === i + 1
                                            ? config.text
                                            : "text-stone-600"
                                    }`}
                                >
                                    {phase} {calendar.season}
                                </span>
                            ))}
                        </div>
                    </div>

                    {/* Seasonal Effects */}
                    <div className="grid grid-cols-2 gap-4 mb-6">
                        <div className="rounded-lg border border-stone-700 bg-stone-800/50 p-4">
                            <div className="flex items-center gap-2 mb-2">
                                {calendar.travel_modifier > 1 ? (
                                    <TrendingDown className="h-4 w-4 text-red-400" />
                                ) : calendar.travel_modifier < 1 ? (
                                    <TrendingUp className="h-4 w-4 text-green-400" />
                                ) : (
                                    <CloudRain className="h-4 w-4 text-stone-400" />
                                )}
                                <span className="font-pixel text-xs text-stone-400">
                                    Travel Speed
                                </span>
                            </div>
                            <div
                                className={`font-pixel text-xl ${getModifierColor(calendar.travel_modifier)}`}
                            >
                                {formatModifier(calendar.travel_modifier)}
                            </div>
                            <p className="font-pixel text-[10px] text-stone-500 mt-1">
                                {calendar.travel_modifier > 1
                                    ? "Roads are difficult"
                                    : calendar.travel_modifier < 1
                                      ? "Clear skies ahead"
                                      : "Normal conditions"}
                            </p>
                        </div>

                        <div className="rounded-lg border border-stone-700 bg-stone-800/50 p-4">
                            <div className="flex items-center gap-2 mb-2">
                                {calendar.gathering_modifier > 1 ? (
                                    <TrendingUp className="h-4 w-4 text-green-400" />
                                ) : calendar.gathering_modifier < 1 ? (
                                    <TrendingDown className="h-4 w-4 text-red-400" />
                                ) : (
                                    <Leaf className="h-4 w-4 text-stone-400" />
                                )}
                                <span className="font-pixel text-xs text-stone-400">
                                    Gathering Yield
                                </span>
                            </div>
                            <div
                                className={`font-pixel text-xl ${getModifierColor(calendar.gathering_modifier, true)}`}
                            >
                                {formatModifier(calendar.gathering_modifier)}
                            </div>
                            <p className="font-pixel text-[10px] text-stone-500 mt-1">
                                {calendar.gathering_modifier > 1
                                    ? "Abundant resources"
                                    : calendar.gathering_modifier < 1
                                      ? "Scarce resources"
                                      : "Normal yields"}
                            </p>
                        </div>
                    </div>

                    {/* Year Progress */}
                    <div className="rounded-lg border border-stone-700 bg-stone-800/50 p-4">
                        <div className="flex items-center justify-between mb-3">
                            <span className="font-pixel text-xs text-stone-400">
                                Year {calendar.year} Progress
                            </span>
                            <span className="font-pixel text-xs text-stone-500">
                                {calendar.week_of_year} / 52 weeks
                            </span>
                        </div>
                        <div className="grid grid-cols-4 gap-1 mb-2">
                            {(["spring", "summer", "autumn", "winter"] as const).map((season) => {
                                const sConfig = seasonConfig[season];
                                const isCurrentSeason = calendar.season === season;
                                const isPastSeason =
                                    ["spring", "summer", "autumn", "winter"].indexOf(season) <
                                    ["spring", "summer", "autumn", "winter"].indexOf(
                                        calendar.season,
                                    );

                                return (
                                    <div
                                        key={season}
                                        className={`h-2 rounded-full overflow-hidden ${
                                            isPastSeason ? "bg-stone-500" : "bg-stone-700"
                                        }`}
                                    >
                                        {isCurrentSeason && (
                                            <div
                                                className={`h-full ${sConfig.accent}`}
                                                style={{ width: `${(calendar.week / 13) * 100}%` }}
                                            />
                                        )}
                                        {isPastSeason && (
                                            <div className="h-full w-full bg-stone-500" />
                                        )}
                                    </div>
                                );
                            })}
                        </div>
                        <div className="flex justify-between">
                            {(["spring", "summer", "autumn", "winter"] as const).map((season) => {
                                const sConfig = seasonConfig[season];
                                const isCurrentSeason = calendar.season === season;
                                return (
                                    <span
                                        key={season}
                                        className={`font-pixel text-[10px] capitalize ${
                                            isCurrentSeason ? sConfig.text : "text-stone-600"
                                        }`}
                                    >
                                        {season}
                                    </span>
                                );
                            })}
                        </div>
                    </div>

                    {/* Info Box */}
                    <div className="mt-6 rounded-lg border border-stone-600 bg-stone-800/30 p-4">
                        <h3 className="mb-2 font-pixel text-sm text-stone-300">
                            About the Calendar
                        </h3>
                        <ul className="space-y-1 font-pixel text-[10px] text-stone-400">
                            <li>- Each week has 7 days</li>
                            <li>- Each season has 13 weeks</li>
                            <li>- Each year has 4 seasons (52 weeks total)</li>
                            <li>- 1 real day = 1 game day</li>
                            <li>- Seasons affect travel speed, gathering yields, and more</li>
                        </ul>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
