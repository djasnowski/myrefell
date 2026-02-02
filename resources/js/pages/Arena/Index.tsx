import { Head, usePage } from "@inertiajs/react";
import { Crosshair, Swords, Target, Trophy } from "lucide-react";
import { useState } from "react";
import AppLayout from "@/layouts/app-layout";
import { ArcheryGame } from "@/components/games/archery-game";
import type { BreadcrumbItem } from "@/types";

interface PageProps {
    location: {
        id: number;
        name: string;
        type: string;
    };
    player: {
        id: number;
        username: string;
        gold: number;
        energy: number;
        max_energy: number;
    };
    [key: string]: unknown;
}

export default function ArenaIndex() {
    const { location, player } = usePage<PageProps>().props;
    const [activeGame, setActiveGame] = useState<"archery" | null>("archery");
    const [totalScore, setTotalScore] = useState(0);
    const [shotHistory, setShotHistory] = useState<
        { type: "bullseye" | "hit" | "miss"; score: number }[]
    >([]);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: "Dashboard", href: "/dashboard" },
        { title: location.name, href: `/${location.type}s/${location.id}` },
        { title: "Arena", href: "#" },
    ];

    const handleScore = (score: number, type: "bullseye" | "hit" | "miss") => {
        setTotalScore((prev) => prev + score);
        setShotHistory((prev) => [...prev.slice(-9), { type, score }]);
    };

    const games = [
        {
            id: "archery" as const,
            name: "Archery",
            description: "Test your aim with bow and arrow",
            icon: Target,
            color: "amber",
            available: true,
        },
        {
            id: "combat" as const,
            name: "Combat Tournament",
            description: "Fight other players for glory",
            icon: Swords,
            color: "red",
            available: false,
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Arena - ${location.name}`} />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-y-auto p-4">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-3">
                        <div className="rounded-lg border border-amber-600/50 bg-amber-900/30 p-3">
                            <Target className="h-8 w-8 text-amber-400" />
                        </div>
                        <div>
                            <h1 className="font-pixel text-2xl text-amber-300">Arena</h1>
                            <p className="text-sm text-stone-400">
                                Compete in games of skill at {location.name}
                            </p>
                        </div>
                    </div>
                    <div className="flex items-center gap-4">
                        <div className="flex items-center gap-2 rounded-lg border border-stone-600/50 bg-stone-800/50 px-3 py-2">
                            <Trophy className="h-4 w-4 text-amber-400" />
                            <span className="font-pixel text-sm text-amber-300">
                                {totalScore} pts
                            </span>
                        </div>
                    </div>
                </div>

                {/* Game Selection Tabs */}
                <div className="flex gap-2">
                    {games.map((game) => {
                        const Icon = game.icon;
                        const isActive = activeGame === game.id;
                        return (
                            <button
                                key={game.id}
                                onClick={() => game.available && setActiveGame(game.id)}
                                disabled={!game.available}
                                className={`flex items-center gap-2 rounded-lg border-2 px-4 py-2 font-pixel text-sm transition ${
                                    isActive
                                        ? "border-amber-500 bg-amber-900/30 text-amber-300"
                                        : game.available
                                          ? "border-stone-600/50 bg-stone-800/50 text-stone-400 hover:border-stone-500 hover:text-stone-300"
                                          : "border-stone-700/30 bg-stone-900/30 text-stone-600 cursor-not-allowed"
                                }`}
                            >
                                <Icon className="h-4 w-4" />
                                {game.name}
                                {!game.available && (
                                    <span className="ml-1 rounded bg-stone-700 px-1.5 py-0.5 text-[10px] text-stone-400">
                                        Soon
                                    </span>
                                )}
                            </button>
                        );
                    })}
                </div>

                {/* Game Area */}
                {activeGame === "archery" && (
                    <div className="flex flex-col gap-4 lg:flex-row">
                        {/* Main game */}
                        <div className="flex-1">
                            <ArcheryGame onScore={handleScore} />
                        </div>

                        {/* Side panel */}
                        <div className="w-full lg:w-64 space-y-4">
                            {/* Shot History */}
                            <div className="rounded-xl border border-stone-600/50 bg-stone-800/30 p-4">
                                <h3 className="mb-3 font-pixel text-sm text-amber-300">
                                    Shot History
                                </h3>
                                <div className="space-y-2">
                                    {shotHistory.length === 0 ? (
                                        <p className="text-xs text-stone-500">No shots yet</p>
                                    ) : (
                                        shotHistory
                                            .slice()
                                            .reverse()
                                            .map((shot, i) => (
                                                <div
                                                    key={i}
                                                    className={`flex items-center justify-between rounded px-2 py-1 text-xs ${
                                                        shot.type === "bullseye"
                                                            ? "bg-red-900/30 text-red-400"
                                                            : shot.type === "hit"
                                                              ? "bg-amber-900/30 text-amber-400"
                                                              : "bg-stone-800/50 text-stone-500"
                                                    }`}
                                                >
                                                    <span className="font-pixel">
                                                        {shot.type === "bullseye"
                                                            ? "BULLSEYE"
                                                            : shot.type === "hit"
                                                              ? "HIT"
                                                              : "MISS"}
                                                    </span>
                                                    <span>+{shot.score}</span>
                                                </div>
                                            ))
                                    )}
                                </div>
                            </div>

                            {/* Instructions */}
                            <div className="rounded-xl border border-stone-600/50 bg-stone-800/30 p-4">
                                <h3 className="mb-3 font-pixel text-sm text-amber-300">
                                    How to Play
                                </h3>
                                <ul className="space-y-2 text-xs text-stone-400">
                                    <li className="flex items-start gap-2">
                                        <Crosshair className="mt-0.5 h-3 w-3 shrink-0 text-amber-500" />
                                        Click and drag down-left to draw the bow
                                    </li>
                                    <li className="flex items-start gap-2">
                                        <Target className="mt-0.5 h-3 w-3 shrink-0 text-amber-500" />
                                        Release to shoot at the target
                                    </li>
                                    <li className="flex items-start gap-2">
                                        <Trophy className="mt-0.5 h-3 w-3 shrink-0 text-amber-500" />
                                        Bullseye: 100 pts, Hit: 50 pts
                                    </li>
                                </ul>
                            </div>

                            {/* Scoring */}
                            <div className="rounded-xl border border-amber-600/30 bg-amber-900/20 p-4">
                                <h3 className="mb-2 font-pixel text-sm text-amber-300">Scoring</h3>
                                <div className="space-y-1 text-xs">
                                    <div className="flex justify-between">
                                        <span className="text-red-400">Bullseye</span>
                                        <span className="text-stone-300">100 pts</span>
                                    </div>
                                    <div className="flex justify-between">
                                        <span className="text-amber-400">Hit</span>
                                        <span className="text-stone-300">50 pts</span>
                                    </div>
                                    <div className="flex justify-between">
                                        <span className="text-stone-500">Miss</span>
                                        <span className="text-stone-300">0 pts</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                )}

                {/* Placeholder for other games */}
                {!activeGame && (
                    <div className="flex flex-1 flex-col items-center justify-center rounded-xl border-2 border-dashed border-stone-600/50 bg-stone-800/20 p-12 text-center">
                        <div className="rounded-full border-2 border-stone-600/50 bg-stone-800/50 p-6">
                            <Crosshair className="h-16 w-16 text-stone-500" />
                        </div>
                        <h2 className="mt-6 font-pixel text-xl text-stone-400">Select a Game</h2>
                        <p className="mt-2 max-w-md text-stone-500">
                            Choose a game from the tabs above to start playing!
                        </p>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
