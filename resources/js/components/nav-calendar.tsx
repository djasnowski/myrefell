import { Link, usePage } from "@inertiajs/react";
import { Cloud, Leaf, Snowflake, Sun } from "lucide-react";
import { useEffect, useState } from "react";
import { Tooltip, TooltipContent, TooltipTrigger } from "@/components/ui/tooltip";
import { useSidebar } from "@/components/ui/sidebar";

interface CalendarData {
    year: number;
    season: "spring" | "summer" | "autumn" | "winter";
    week: number;
    week_of_year: number;
    formatted_date: string;
}

interface PageProps {
    calendar: CalendarData;
    [key: string]: unknown;
}

const seasonConfig = {
    spring: {
        icon: Cloud,
        color: "text-green-400",
        bg: "bg-green-900/30",
        border: "border-green-600/30",
    },
    summer: {
        icon: Sun,
        color: "text-yellow-400",
        bg: "bg-yellow-900/30",
        border: "border-yellow-600/30",
    },
    autumn: {
        icon: Leaf,
        color: "text-orange-400",
        bg: "bg-orange-900/30",
        border: "border-orange-600/30",
    },
    winter: {
        icon: Snowflake,
        color: "text-blue-400",
        bg: "bg-blue-900/30",
        border: "border-blue-600/30",
    },
};

function getTimeUntilMidnightUTC(): { hours: number; minutes: number; seconds: number } {
    const now = new Date();
    const midnightUTC = new Date(
        Date.UTC(now.getUTCFullYear(), now.getUTCMonth(), now.getUTCDate() + 1, 0, 0, 0, 0),
    );
    const diff = midnightUTC.getTime() - now.getTime();

    const hours = Math.floor(diff / (1000 * 60 * 60));
    const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
    const seconds = Math.floor((diff % (1000 * 60)) / 1000);

    return { hours, minutes, seconds };
}

export function NavCalendar() {
    const { calendar } = usePage<PageProps>().props;
    const { state } = useSidebar();
    const [countdown, setCountdown] = useState(getTimeUntilMidnightUTC());

    useEffect(() => {
        const timer = setInterval(() => {
            setCountdown(getTimeUntilMidnightUTC());
        }, 1000);

        return () => clearInterval(timer);
    }, []);

    if (!calendar) return null;

    const config = seasonConfig[calendar.season];
    const SeasonIcon = config.icon;

    // Format countdown
    const countdownStr = `${countdown.hours.toString().padStart(2, "0")}:${countdown.minutes.toString().padStart(2, "0")}:${countdown.seconds.toString().padStart(2, "0")}`;

    // When collapsed, show just the season icon
    if (state === "collapsed") {
        return (
            <Tooltip>
                <TooltipTrigger asChild>
                    <Link
                        href="/calendar"
                        className={`flex items-center justify-center rounded-lg border ${config.border} ${config.bg} p-2`}
                    >
                        <SeasonIcon className={`h-5 w-5 ${config.color}`} />
                    </Link>
                </TooltipTrigger>
                <TooltipContent side="right" className="bg-stone-900 border-stone-700">
                    <div className="font-pixel text-xs">
                        <div className={config.color}>{calendar.formatted_date}</div>
                        <div className="text-stone-400 mt-1">Next day in: {countdownStr}</div>
                    </div>
                </TooltipContent>
            </Tooltip>
        );
    }

    return (
        <Link
            href="/calendar"
            className={`flex items-center gap-3 rounded-lg border ${config.border} ${config.bg} px-3 py-2 transition hover:opacity-80`}
        >
            <SeasonIcon className={`h-5 w-5 ${config.color}`} />
            <div className="flex-1 min-w-0">
                <div className={`font-pixel text-xs ${config.color} truncate`}>
                    {calendar.formatted_date}
                </div>
                <div className="font-pixel text-[10px] text-stone-500">
                    Next day: {countdownStr}
                </div>
            </div>
        </Link>
    );
}
