import { Link } from "@inertiajs/react";
import { AlertTriangle, Clock, Flag, Users } from "lucide-react";
import { useEffect, useState } from "react";

interface NoConfidenceVote {
    id: number;
    target_role: string;
    target_player: { id: number; username: string };
    status: string;
    voting_ends_at: string | null;
    votes_for: number;
    votes_against: number;
    quorum_required: number;
}

function useCountdown(endTime: string | null): string {
    const [timeLeft, setTimeLeft] = useState("");

    useEffect(() => {
        if (!endTime) {
            setTimeLeft("");
            return;
        }

        const update = () => {
            const diff = new Date(endTime).getTime() - Date.now();
            if (diff <= 0) {
                setTimeLeft("Voting ended");
                return;
            }
            const hours = Math.floor(diff / (1000 * 60 * 60));
            const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
            setTimeLeft(`${hours}h ${minutes}m remaining`);
        };

        update();
        const interval = setInterval(update, 60000);
        return () => clearInterval(interval);
    }, [endTime]);

    return timeLeft;
}

export default function NoConfidenceBanner({ vote }: { vote: NoConfidenceVote }) {
    const timeLeft = useCountdown(vote.voting_ends_at);
    const roleName = vote.target_role.replace(/_/g, " ").replace(/\b\w/g, (c) => c.toUpperCase());

    return (
        <Link
            href={`/no-confidence/${vote.id}`}
            className="group block rounded-lg border-2 border-red-700/50 bg-gradient-to-r from-red-950/60 via-red-900/30 to-red-950/60 p-4 transition hover:border-red-600/60 hover:from-red-950/80"
        >
            <div className="flex items-center gap-3">
                <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border border-red-700/50 bg-red-900/40">
                    <Flag className="h-5 w-5 text-red-400" />
                </div>
                <div className="flex-1">
                    <div className="flex items-center gap-2">
                        <AlertTriangle className="h-4 w-4 text-red-400" />
                        <span className="font-pixel text-sm text-red-300">
                            No-Confidence Vote in Progress
                        </span>
                    </div>
                    <p className="mt-0.5 font-pixel text-xs text-stone-400">
                        A vote to remove{" "}
                        <span className="text-amber-300">{vote.target_player.username}</span> as{" "}
                        <span className="text-stone-300">{roleName}</span> is underway
                    </p>
                </div>
                <div className="hidden shrink-0 items-center gap-4 sm:flex">
                    <div className="text-center">
                        <div className="flex items-center gap-1">
                            <Users className="h-3 w-3 text-stone-500" />
                            <span className="font-pixel text-xs text-stone-400">
                                {vote.votes_for + vote.votes_against} / {vote.quorum_required}
                            </span>
                        </div>
                        <span className="font-pixel text-[10px] text-stone-600">votes cast</span>
                    </div>
                    {timeLeft && (
                        <div className="text-center">
                            <div className="flex items-center gap-1">
                                <Clock className="h-3 w-3 text-stone-500" />
                                <span className="font-pixel text-xs text-stone-400">
                                    {timeLeft}
                                </span>
                            </div>
                            <span className="font-pixel text-[10px] text-stone-600">to vote</span>
                        </div>
                    )}
                </div>
            </div>
        </Link>
    );
}
