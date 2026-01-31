import { Link } from '@inertiajs/react';
import { Swords, UserPlus } from 'lucide-react';
import { formatDistanceToNow } from 'date-fns';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { show as showUser } from '@/actions/App/Http/Controllers/Admin/UserController';

interface PlayerItem {
    id: number;
    username: string;
    created_at: string;
    current_location_type: string | null;
    combat_level: number;
}

interface Props {
    players: PlayerItem[];
}

export function LatestPlayers({ players }: Props) {
    return (
        <Card className="border-stone-800 bg-stone-900/50">
            <CardHeader>
                <CardTitle className="flex items-center gap-2 text-stone-100">
                    <UserPlus className="size-5 text-green-400" />
                    Latest Players
                </CardTitle>
                <CardDescription className="text-stone-400">
                    Most recently registered accounts
                </CardDescription>
            </CardHeader>
            <CardContent>
                {players.length === 0 ? (
                    <p className="py-8 text-center text-stone-500">
                        No players registered yet
                    </p>
                ) : (
                    <div className="space-y-2">
                        {players.map((player) => (
                            <Link
                                key={player.id}
                                href={showUser.url(player.id)}
                                className="flex items-center justify-between rounded-lg border border-stone-800 bg-stone-900/30 p-3 transition-colors hover:border-stone-700 hover:bg-stone-800/50"
                            >
                                <div className="min-w-0">
                                    <p className="font-medium text-stone-100">
                                        {player.username}
                                    </p>
                                    <p className="text-xs text-stone-500">
                                        {formatDistanceToNow(
                                            new Date(player.created_at),
                                            { addSuffix: true }
                                        )}
                                    </p>
                                </div>
                                <div className="flex items-center gap-1 rounded-md bg-stone-800 px-2 py-1">
                                    <Swords className="size-3 text-amber-400" />
                                    <span className="text-sm font-medium text-stone-300">
                                        {player.combat_level}
                                    </span>
                                </div>
                            </Link>
                        ))}
                    </div>
                )}
            </CardContent>
        </Card>
    );
}
