import { Link } from "@inertiajs/react";
import { Coins, Swords } from "lucide-react";
import { show as showUser } from "@/actions/App/Http/Controllers/Admin/UserController";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";

interface PlayerItem {
    id: number;
    username: string;
    gold: number;
    combat_level: number;
}

interface Props {
    players: PlayerItem[];
}

export function TopByGold({ players }: Props) {
    return (
        <Card className="border-stone-800 bg-stone-900/50">
            <CardHeader>
                <CardTitle className="flex items-center gap-2 text-stone-100">
                    <Coins className="size-5 text-yellow-400" />
                    Top by Gold
                </CardTitle>
                <CardDescription className="text-stone-400">
                    Wealthiest players (excluding admins)
                </CardDescription>
            </CardHeader>
            <CardContent>
                {players.length === 0 ? (
                    <p className="py-8 text-center text-stone-500">No players found</p>
                ) : (
                    <div className="space-y-2">
                        {players.map((player, index) => (
                            <Link
                                key={player.id}
                                href={showUser.url(player.id)}
                                className="flex items-center justify-between rounded-lg border border-stone-800 bg-stone-900/30 p-3 transition-colors hover:border-stone-700 hover:bg-stone-800/50"
                            >
                                <div className="flex items-center gap-3">
                                    <span
                                        className={`flex size-6 items-center justify-center rounded-full text-xs font-bold ${
                                            index === 0
                                                ? "bg-yellow-500/20 text-yellow-400"
                                                : index === 1
                                                  ? "bg-stone-400/20 text-stone-300"
                                                  : index === 2
                                                    ? "bg-amber-600/20 text-amber-500"
                                                    : "bg-stone-800 text-stone-500"
                                        }`}
                                    >
                                        {index + 1}
                                    </span>
                                    <div className="min-w-0">
                                        <p className="font-medium text-stone-100">
                                            {player.username}
                                        </p>
                                        <div className="flex items-center gap-1 text-xs text-stone-500">
                                            <Swords className="size-3" />
                                            <span>Lvl {player.combat_level}</span>
                                        </div>
                                    </div>
                                </div>
                                <div className="flex items-center gap-1 rounded-md bg-yellow-900/30 px-2 py-1">
                                    <Coins className="size-3 text-yellow-400" />
                                    <span className="text-sm font-medium text-yellow-300">
                                        {player.gold.toLocaleString()}
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
