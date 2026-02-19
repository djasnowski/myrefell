import { Link } from "@inertiajs/react";
import { Swords, Users } from "lucide-react";

interface Player {
    id: number;
    username: string;
    combat_level: number;
}

interface PlayerListProps {
    title: string;
    players: Player[];
    totalCount: number;
    currentUserId: number;
    youLabelClass?: string;
    viewAllHref?: string;
}

export function PlayerList({
    title,
    players,
    totalCount,
    currentUserId,
    youLabelClass = "text-blue-400",
    viewAllHref,
}: PlayerListProps) {
    if (totalCount === 0) {
        return null;
    }

    return (
        <div>
            <div className="mb-3 flex items-center justify-between">
                <h2 className="font-pixel text-sm text-stone-400">
                    {title} ({totalCount})
                </h2>
                {viewAllHref && (
                    <Link href={viewAllHref} className="text-xs text-amber-400 hover:underline">
                        View All
                    </Link>
                )}
            </div>
            <div className="grid gap-2 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4">
                {players.map((player) => (
                    <div
                        key={player.id}
                        className="flex items-center gap-3 rounded-lg border border-stone-700 bg-stone-800/30 p-3"
                    >
                        <div className="flex h-10 w-10 items-center justify-center rounded-full bg-stone-700">
                            <Users className="h-5 w-5 text-stone-400" />
                        </div>
                        <div>
                            <div className="font-pixel text-sm text-stone-200">
                                {player.username}
                                {player.id === currentUserId && (
                                    <span className={`ml-1 text-xs ${youLabelClass}`}>(You)</span>
                                )}
                            </div>
                            <div className="flex items-center gap-1 text-xs text-stone-500">
                                <Swords className="h-3 w-3" />
                                Combat Lv. {player.combat_level}
                            </div>
                        </div>
                    </div>
                ))}
            </div>
            {totalCount > players.length && (
                <p className="mt-2 text-center text-xs text-stone-500">
                    +{totalCount - players.length} more
                </p>
            )}
        </div>
    );
}
