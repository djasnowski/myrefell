import { Link, usePage } from "@inertiajs/react";
import { useEffect, useState } from "react";
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
        color: "text-green-400",
        bg: "bg-green-900/30",
        border: "border-green-600/30",
    },
    summer: {
        color: "text-yellow-400",
        bg: "bg-yellow-900/30",
        border: "border-yellow-600/30",
    },
    autumn: {
        color: "text-orange-400",
        bg: "bg-orange-900/30",
        border: "border-orange-600/30",
    },
    winter: {
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

    // Format countdown
    const countdownStr = `${countdown.hours.toString().padStart(2, "0")}:${countdown.minutes.toString().padStart(2, "0")}:${countdown.seconds.toString().padStart(2, "0")}`;

    // When collapsed, hide entirely
    if (state === "collapsed") {
        return null;
    }

    return (
        <Link
            href="/calendar"
            className={`block rounded-lg border ${config.border} ${config.bg} px-2 py-1.5 transition hover:opacity-80`}
        >
            <div className={`font-pixel text-[10px] ${config.color}`}>
                {calendar.formatted_date}
            </div>
            <div className="font-pixel text-[9px] text-stone-500">Next day: {countdownStr}</div>
        </Link>
    );
}
