import { Head, router, usePage } from "@inertiajs/react";
import { Crosshair, Gift, Medal, Swords, Target, Trophy } from "lucide-react";
import { useState } from "react";
import AppLayout from "@/layouts/app-layout";
import { ArcheryGame } from "@/components/games/archery-game";
import { locationPath } from "@/lib/utils";
import type { BreadcrumbItem } from "@/types";

interface LeaderboardEntry {
    rank: number;
    user_id: number;
    username: string;
    score: number;
}

interface PendingReward {
    id: number;
    minigame: string;
    reward_type: string;
    rank: number;
    gold_amount: number;
    item: {
        id: number;
        name: string;
        rarity: string;
    } | null;
    period_start: string;
    period_end: string;
}

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
    has_played_today: boolean;
    played_at_different_location: boolean;
    played_at_location: string | null;
    leaderboards: {
        daily: LeaderboardEntry[];
        weekly: LeaderboardEntry[];
        monthly: LeaderboardEntry[];
    };
    user_ranks: {
        daily: number | null;
        weekly: number | null;
        monthly: number | null;
    };
    pending_rewards: PendingReward[];
    [key: string]: unknown;
}

export default function ArenaIndex() {
    const {
        location,
        player,
        has_played_today,
        played_at_different_location,
        played_at_location,
        leaderboards,
        user_ranks,
        pending_rewards,
    } = usePage<PageProps>().props;
    const [activeGame, setActiveGame] = useState<"archery" | null>("archery");
    const [totalScore, setTotalScore] = useState(0);
    const [shotHistory, setShotHistory] = useState<
        { type: "bullseye" | "hit" | "miss"; score: number }[]
    >([]);
    const [leaderboardTab, setLeaderboardTab] = useState<"daily" | "weekly" | "monthly">("daily");
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [gameSubmitted, setGameSubmitted] = useState(false);
    const [isCollecting, setIsCollecting] = useState(false);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: "Dashboard", href: "/dashboard" },
        { title: location.name, href: locationPath(location.type, location.id) },
        { title: "Arena", href: "#" },
    ];

    const handleScore = (score: number, type: "bullseye" | "hit" | "miss") => {
        setTotalScore((prev) => prev + score);
        setShotHistory((prev) => [...prev.slice(-9), { type, score }]);
    };

    const handleGameEnd = (finalScore: number) => {
        if (isSubmitting || gameSubmitted) return;
        setIsSubmitting(true);

        router.post(
            "/minigames/submit-score",
            {
                minigame: "archery",
                score: finalScore,
                location_type: location.type,
                location_id: location.id,
            },
            {
                preserveScroll: true,
                onSuccess: () => {
                    setGameSubmitted(true);
                    router.reload();
                },
                onFinish: () => {
                    setIsSubmitting(false);
                },
            },
        );
    };

    const handleCollectRewards = () => {
        if (isCollecting) return;
        setIsCollecting(true);

        router.post(
            "/minigames/collect-rewards",
            {
                location_type: location.type,
                location_id: location.id,
            },
            {
                preserveScroll: true,
                onSuccess: () => {
                    router.reload();
                },
                onFinish: () => {
                    setIsCollecting(false);
                },
            },
        );
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

    const currentLeaderboard = leaderboards[leaderboardTab];
    const currentUserRank = user_ranks[leaderboardTab];

    const getRankBadge = (rank: number) => {
        if (rank === 1) return "text-amber-400";
        if (rank === 2) return "text-stone-300";
        if (rank === 3) return "text-amber-700";
        return "text-stone-500";
    };

    const getRewardTypeLabel = (type: string) => {
        switch (type) {
            case "daily":
                return "Daily";
            case "weekly":
                return "Weekly";
            case "monthly":
                return "Monthly";
            default:
                return type;
        }
    };

    const getRarityColor = (rarity: string) => {
        switch (rarity) {
            case "legendary":
                return "text-orange-400";
            case "epic":
                return "text-purple-400";
            case "rare":
                return "text-blue-400";
            default:
                return "text-stone-400";
        }
    };

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

                {/* Already Played at Different Location Banner */}
                {played_at_different_location && played_at_location && (
                    <div className="rounded-xl border border-stone-600/50 bg-stone-800/50 p-4">
                        <div className="flex items-center gap-3">
                            <Target className="h-5 w-5 text-stone-400" />
                            <div>
                                <p className="text-sm text-stone-300">
                                    You already played archery today at{" "}
                                    <span className="font-semibold text-amber-300">
                                        {played_at_location}
                                    </span>
                                </p>
                                <p className="text-xs text-stone-500">
                                    Come back tomorrow for another round!
                                </p>
                            </div>
                        </div>
                    </div>
                )}

                {/* Pending Rewards Banner */}
                {pending_rewards.length > 0 && (
                    <div className="rounded-xl border-2 border-amber-500/50 bg-amber-900/20 p-4">
                        <div className="flex items-center justify-between">
                            <div className="flex items-center gap-3">
                                <Gift className="h-6 w-6 text-amber-400" />
                                <div>
                                    <h3 className="font-pixel text-sm text-amber-300">
                                        Uncollected Rewards!
                                    </h3>
                                    <p className="text-xs text-stone-400">
                                        You have {pending_rewards.length} reward
                                        {pending_rewards.length !== 1 ? "s" : ""} waiting at this
                                        location
                                    </p>
                                </div>
                            </div>
                            <button
                                onClick={handleCollectRewards}
                                disabled={isCollecting}
                                className="rounded-lg border border-amber-500 bg-amber-600 px-4 py-2 font-pixel text-sm text-white transition hover:bg-amber-500 disabled:opacity-50"
                            >
                                {isCollecting ? "Collecting..." : "Collect All"}
                            </button>
                        </div>
                        <div className="mt-3 space-y-2">
                            {pending_rewards.map((reward) => (
                                <div
                                    key={reward.id}
                                    className="flex items-center justify-between rounded bg-stone-800/50 px-3 py-2 text-xs"
                                >
                                    <div className="flex items-center gap-2">
                                        <Medal className={getRankBadge(reward.rank)} />
                                        <span className="text-stone-300">
                                            {getRewardTypeLabel(reward.reward_type)} #{reward.rank}
                                        </span>
                                    </div>
                                    <div className="flex items-center gap-3">
                                        {reward.item && (
                                            <span className={getRarityColor(reward.item.rarity)}>
                                                {reward.item.name}
                                            </span>
                                        )}
                                        <span className="text-amber-400">
                                            +{reward.gold_amount} gold
                                        </span>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>
                )}

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
                            <ArcheryGame
                                onScore={handleScore}
                                onGameEnd={handleGameEnd}
                                disabled={has_played_today}
                                maxArrows={25}
                            />
                        </div>

                        {/* Side panel */}
                        <div className="w-full lg:w-72 space-y-4">
                            {/* Leaderboard */}
                            <div className="rounded-xl border border-stone-600/50 bg-stone-800/30 p-4">
                                <h3 className="mb-3 font-pixel text-sm text-amber-300">
                                    Leaderboard
                                </h3>
                                {/* Leaderboard Tabs */}
                                <div className="mb-3 flex gap-1">
                                    {(["daily", "weekly", "monthly"] as const).map((tab) => (
                                        <button
                                            key={tab}
                                            onClick={() => setLeaderboardTab(tab)}
                                            className={`flex-1 rounded px-2 py-1 font-pixel text-[10px] transition ${
                                                leaderboardTab === tab
                                                    ? "bg-amber-600 text-white"
                                                    : "bg-stone-700/50 text-stone-400 hover:bg-stone-700"
                                            }`}
                                        >
                                            {tab.charAt(0).toUpperCase() + tab.slice(1)}
                                        </button>
                                    ))}
                                </div>
                                {/* Leaderboard Entries */}
                                <div className="space-y-1">
                                    {currentLeaderboard.length === 0 ? (
                                        <p className="text-xs text-stone-500">No scores yet</p>
                                    ) : (
                                        currentLeaderboard.slice(0, 10).map((entry) => (
                                            <div
                                                key={entry.user_id}
                                                className={`flex items-center justify-between rounded px-2 py-1 text-xs ${
                                                    entry.user_id === player.id
                                                        ? "bg-amber-900/30"
                                                        : "bg-stone-800/30"
                                                }`}
                                            >
                                                <div className="flex items-center gap-2">
                                                    <span
                                                        className={`font-pixel ${getRankBadge(entry.rank)}`}
                                                    >
                                                        #{entry.rank}
                                                    </span>
                                                    <span
                                                        className={
                                                            entry.user_id === player.id
                                                                ? "text-amber-300"
                                                                : "text-stone-300"
                                                        }
                                                    >
                                                        {entry.username}
                                                    </span>
                                                </div>
                                                <span className="text-stone-400">
                                                    {entry.score}
                                                </span>
                                            </div>
                                        ))
                                    )}
                                </div>
                                {/* User's rank if not in top 10 */}
                                {currentUserRank && currentUserRank > 10 && (
                                    <div className="mt-2 border-t border-stone-700 pt-2">
                                        <div className="flex items-center justify-between rounded bg-amber-900/30 px-2 py-1 text-xs">
                                            <div className="flex items-center gap-2">
                                                <span className="font-pixel text-stone-400">
                                                    #{currentUserRank}
                                                </span>
                                                <span className="text-amber-300">You</span>
                                            </div>
                                        </div>
                                    </div>
                                )}
                            </div>

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
                                        Click the bow string and drag to aim
                                    </li>
                                    <li className="flex items-start gap-2">
                                        <Target className="mt-0.5 h-3 w-3 shrink-0 text-amber-500" />
                                        Release to shoot (10 arrows per day)
                                    </li>
                                    <li className="flex items-start gap-2">
                                        <Trophy className="mt-0.5 h-3 w-3 shrink-0 text-amber-500" />
                                        Top 3 win items, top 10 win gold!
                                    </li>
                                </ul>
                            </div>

                            {/* Scoring */}
                            <div className="rounded-xl border border-amber-600/30 bg-amber-900/20 p-4">
                                <h3 className="mb-2 font-pixel text-sm text-amber-300">Scoring</h3>
                                <div className="space-y-1 text-xs">
                                    <div className="flex justify-between">
                                        <span className="text-red-400">Bullseye</span>
                                        <span className="text-stone-300">~100 pts</span>
                                    </div>
                                    <div className="flex justify-between">
                                        <span className="text-amber-400">Inner Ring</span>
                                        <span className="text-stone-300">~80 pts</span>
                                    </div>
                                    <div className="flex justify-between">
                                        <span className="text-amber-400/70">Outer Ring</span>
                                        <span className="text-stone-300">~40 pts</span>
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
