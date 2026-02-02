import { router } from "@inertiajs/react";
import {
    Coins,
    Dice1,
    Dice2,
    Dice3,
    Dice4,
    Dice5,
    Dice6,
    Dices,
    Loader2,
    Minus,
    Plus,
    Timer,
    Trophy,
    TrendingDown,
    Zap,
} from "lucide-react";
import { useState, useEffect, useCallback } from "react";
import { gameToast } from "@/components/ui/game-toast";

interface TavernStats {
    wins: number;
    losses: number;
    total_profit: number;
}

interface RecentGame {
    id: number;
    game_type: string;
    wager: number;
    won: boolean;
    payout: number;
    energy_awarded: number;
    played_at: string;
}

interface DiceGameProps {
    locationUrl: string;
    canPlay: boolean;
    cooldownEnds: string | null;
    reason?: string | null;
    minWager: number;
    maxWager: number;
    playerGold: number;
    tavernStats: TavernStats;
    recentGames?: RecentGame[];
    initialGame?: GameType;
    onGameComplete?: () => void;
    onClose?: () => void;
}

type GameType = "high_roll" | "hazard" | "doubles";

interface DiceResult {
    success: boolean;
    message: string;
    won?: boolean;
    rolls?:
        | {
              player?: number[];
              house?: number[];
          }
        | Array<{ dice: number[]; total: number; type: string }>;
    payout?: number;
    energy?: number;
    new_gold?: number;
    new_energy?: number;
}

const GAME_INFO: Record<
    GameType,
    { name: string; description: string; odds: string; payout: string }
> = {
    high_roll: {
        name: "High Roll",
        description: "Both roll 2d6. Highest total wins. Ties go to the house.",
        odds: "~47%",
        payout: "1.35x",
    },
    hazard: {
        name: "Hazard",
        description:
            "7 or 11 wins, 2/3/12 loses. Others establish a point - hit it before rolling 7.",
        odds: "~49%",
        payout: "1.6x",
    },
    doubles: {
        name: "Doubles",
        description: "Roll doubles to win! Low odds but decent payout.",
        odds: "17%",
        payout: "1.8x",
    },
};

function DiceFace({
    value,
    size = "md",
    rolling = false,
}: {
    value: number;
    size?: "sm" | "md" | "lg";
    rolling?: boolean;
}) {
    const sizeClasses = {
        sm: "h-8 w-8",
        md: "h-12 w-12",
        lg: "h-16 w-16",
    };

    const DiceIcon = [Dice1, Dice2, Dice3, Dice4, Dice5, Dice6][value - 1] || Dice1;

    return (
        <DiceIcon
            className={`${sizeClasses[size]} text-amber-100 ${rolling ? "animate-spin" : ""}`}
        />
    );
}

function CooldownTimer({ cooldownEnds }: { cooldownEnds: string }) {
    const [timeLeft, setTimeLeft] = useState<number>(0);

    useEffect(() => {
        const calculateTimeLeft = () => {
            const end = new Date(cooldownEnds).getTime();
            const now = Date.now();
            return Math.max(0, Math.floor((end - now) / 1000));
        };

        setTimeLeft(calculateTimeLeft());

        const interval = setInterval(() => {
            const remaining = calculateTimeLeft();
            setTimeLeft(remaining);
            if (remaining <= 0) {
                clearInterval(interval);
                router.reload({ only: ["dice"] });
            }
        }, 1000);

        return () => clearInterval(interval);
    }, [cooldownEnds]);

    const minutes = Math.floor(timeLeft / 60);
    const seconds = timeLeft % 60;

    return (
        <div className="flex items-center gap-2 text-purple-400">
            <Timer className="h-4 w-4" />
            <span className="font-pixel text-sm">
                {minutes}:{seconds.toString().padStart(2, "0")}
            </span>
        </div>
    );
}

interface GameCardProps {
    gameType: GameType;
    isSelected: boolean;
    onSelect: () => void;
    onPlay: () => void;
    wager: number;
    canPlay: boolean;
    canAfford: boolean;
    loading: boolean;
    cooldownEnds: string | null;
}

function GameCard({
    gameType,
    isSelected,
    onSelect,
    onPlay,
    wager,
    canPlay,
    canAfford,
    loading,
    cooldownEnds,
}: GameCardProps) {
    const info = GAME_INFO[gameType];
    const isLoading = loading && isSelected;
    const canPlayThis = canPlay && canAfford && !loading;

    return (
        <div
            className={`rounded-lg border p-3 transition ${
                isSelected
                    ? "border-purple-600/50 bg-purple-900/20"
                    : "border-stone-700 bg-stone-800/50 hover:border-purple-600/30"
            }`}
        >
            <div className="mb-2 flex items-center justify-between">
                <span className="font-pixel text-sm text-purple-300">{info.name}</span>
                <Dices className="h-4 w-4 text-purple-400" />
            </div>

            {/* Odds and Payout */}
            <div className="mb-2 space-y-1">
                <div className="flex items-center justify-between text-stone-400">
                    <span className="font-pixel text-[10px]">Win Chance</span>
                    <span className="font-pixel text-[10px] text-green-400">{info.odds}</span>
                </div>
                <div className="flex items-center justify-between text-stone-400">
                    <span className="font-pixel text-[10px]">Payout</span>
                    <span className="font-pixel text-[10px] text-amber-400">{info.payout}</span>
                </div>
            </div>

            {/* Description */}
            <div className="mb-2 rounded bg-stone-900/50 px-2 py-1">
                <p className="font-pixel text-[10px] text-stone-400">{info.description}</p>
            </div>

            {/* Stats Row */}
            <div className="mb-2 flex items-center justify-between text-stone-500">
                <span className="flex items-center gap-1 font-pixel text-[10px]">
                    <Zap className="h-3 w-3 text-yellow-500" />
                    +3-10
                </span>
                <span className="font-pixel text-[10px] text-amber-400">{wager}g bet</span>
            </div>

            {/* Play Button */}
            {isSelected ? (
                <button
                    onClick={onPlay}
                    disabled={!canPlayThis}
                    className={`flex w-full items-center justify-center gap-2 rounded-md px-2 py-1.5 font-pixel text-xs transition ${
                        canPlayThis
                            ? "bg-purple-600 text-white hover:bg-purple-500"
                            : "cursor-not-allowed bg-stone-700 text-stone-500"
                    }`}
                >
                    {isLoading ? (
                        <>
                            <Loader2 className="h-3 w-3 animate-spin" />
                            Rolling...
                        </>
                    ) : !canPlay && cooldownEnds ? (
                        <CooldownTimer cooldownEnds={cooldownEnds} />
                    ) : !canAfford ? (
                        "Need more gold"
                    ) : (
                        <>
                            <Dices className="h-3 w-3" />
                            Roll
                        </>
                    )}
                </button>
            ) : (
                <button
                    onClick={onSelect}
                    disabled={loading}
                    className="flex w-full items-center justify-center gap-2 rounded-md bg-stone-700/50 px-2 py-1.5 font-pixel text-xs text-stone-400 transition hover:bg-stone-600/50 disabled:opacity-50"
                >
                    Select
                </button>
            )}
        </div>
    );
}

export function DiceGame({
    locationUrl,
    canPlay,
    cooldownEnds,
    reason,
    minWager,
    maxWager,
    playerGold,
    tavernStats,
    recentGames = [],
    initialGame,
    onGameComplete,
    onClose,
}: DiceGameProps) {
    const [selectedGame, setSelectedGame] = useState<GameType>(initialGame || "high_roll");
    const [wager, setWager] = useState<number>(Math.min(50, Math.max(minWager, playerGold)));
    const [loading, setLoading] = useState(false);
    const [rolling, setRolling] = useState(false);
    const [lastResult, setLastResult] = useState<DiceResult | null>(null);
    const [displayDice, setDisplayDice] = useState<{ player: number[]; house?: number[] } | null>(
        null,
    );

    // If initialGame is provided, we're in modal mode - simplified layout
    const isModalMode = !!initialGame;

    useEffect(() => {
        setWager(Math.min(wager, Math.max(minWager, playerGold)));
    }, [playerGold, minWager]);

    const handleWagerChange = useCallback(
        (delta: number) => {
            setWager((prev) =>
                Math.min(maxWager, Math.max(minWager, Math.min(prev + delta, playerGold))),
            );
        },
        [minWager, maxWager, playerGold],
    );

    const handleQuickWager = useCallback(
        (amount: number) => {
            if (amount === -1) {
                setWager(Math.min(maxWager, playerGold));
            } else {
                setWager(Math.min(amount, Math.min(maxWager, playerGold)));
            }
        },
        [maxWager, playerGold],
    );

    const handlePlay = async () => {
        if (!canPlay || loading || playerGold < wager) return;

        setLoading(true);
        setRolling(true);
        setLastResult(null);
        setDisplayDice(null);

        const rollInterval = setInterval(() => {
            setDisplayDice({
                player: [Math.ceil(Math.random() * 6), Math.ceil(Math.random() * 6)],
                house:
                    selectedGame === "high_roll"
                        ? [Math.ceil(Math.random() * 6), Math.ceil(Math.random() * 6)]
                        : undefined,
            });
        }, 100);

        await new Promise((resolve) => setTimeout(resolve, 1500));

        router.post(
            `${locationUrl}/dice`,
            {
                game_type: selectedGame,
                wager: wager,
            },
            {
                preserveScroll: true,
                onSuccess: (page) => {
                    clearInterval(rollInterval);
                    setRolling(false);

                    console.log("Flash data:", page.props.flash);
                    const result = (page.props.flash as { dice_result?: DiceResult })?.dice_result;
                    console.log("Dice result:", result);
                    if (result) {
                        setLastResult(result);

                        if (result.rolls) {
                            if (Array.isArray(result.rolls)) {
                                const lastRoll = result.rolls[result.rolls.length - 1];
                                setDisplayDice({ player: lastRoll.dice });
                            } else {
                                setDisplayDice({
                                    player: result.rolls.player || [1, 1],
                                    house: result.rolls.house,
                                });
                            }
                        }

                        if (result.won) {
                            gameToast.success(`${result.message}`, {
                                gold: result.payout,
                                energy: result.energy,
                            });
                        } else {
                            gameToast.error(result.message);
                        }
                    }

                    // Delay reload so user can see the result
                    setTimeout(() => {
                        router.reload();
                        onGameComplete?.();
                    }, 3000);
                },
                onError: () => {
                    clearInterval(rollInterval);
                    setRolling(false);
                    gameToast.error("Something went wrong!");
                },
                onFinish: () => {
                    setLoading(false);
                },
            },
        );
    };

    const effectiveMaxWager = Math.min(maxWager, playerGold);
    const canAffordWager = playerGold >= wager;
    const canPlayNow = canPlay && canAffordWager && !loading;
    const gameInfo = GAME_INFO[selectedGame];

    // Modal mode: simplified layout for playing a specific game
    if (isModalMode) {
        return (
            <div className="space-y-4">
                {/* Game Description */}
                <div className="rounded-lg bg-stone-800/50 p-3">
                    <p className="text-sm text-stone-300">{gameInfo.description}</p>
                    <div className="mt-2 flex gap-4">
                        <span className="font-pixel text-xs text-green-400">
                            Win: {gameInfo.odds}
                        </span>
                        <span className="font-pixel text-xs text-amber-400">
                            Payout: {gameInfo.payout}
                        </span>
                    </div>
                </div>

                {/* Dice Display */}
                {(rolling || displayDice) && (
                    <div className="grid h-[140px] place-content-center rounded-lg bg-stone-800/50 px-6">
                        <div className="flex items-center justify-center gap-6">
                            <div className="text-center">
                                <p className="mb-2 font-pixel text-xs text-stone-400">You</p>
                                <div className="flex gap-2">
                                    {(displayDice?.player || [1, 1]).map((val, idx) => (
                                        <DiceFace
                                            key={idx}
                                            value={val}
                                            size="lg"
                                            rolling={rolling}
                                        />
                                    ))}
                                </div>
                            </div>

                            {selectedGame === "high_roll" && displayDice?.house && (
                                <>
                                    <div className="font-pixel text-xl text-stone-500">vs</div>
                                    <div className="text-center">
                                        <p className="mb-2 font-pixel text-xs text-stone-400">
                                            House
                                        </p>
                                        <div className="flex gap-2">
                                            {displayDice.house.map((val, idx) => (
                                                <DiceFace
                                                    key={idx}
                                                    value={val}
                                                    size="lg"
                                                    rolling={rolling}
                                                />
                                            ))}
                                        </div>
                                    </div>
                                </>
                            )}

                            {lastResult && !rolling && (
                                <div className="ml-4 text-center">
                                    <div className="flex items-center gap-2">
                                        {lastResult.won ? (
                                            <Trophy className="h-6 w-6 text-green-400" />
                                        ) : (
                                            <TrendingDown className="h-6 w-6 text-red-400" />
                                        )}
                                        <span
                                            className={`font-pixel text-xl ${
                                                lastResult.won ? "text-green-300" : "text-red-300"
                                            }`}
                                        >
                                            {lastResult.won ? "Win!" : "Lose"}
                                        </span>
                                    </div>
                                    <p className="mt-1 max-w-[200px] text-xs text-stone-400">
                                        {lastResult.message}
                                    </p>
                                    <div className="mt-2 flex items-center justify-center gap-4 text-sm">
                                        <span
                                            className={
                                                lastResult.won ? "text-green-400" : "text-red-400"
                                            }
                                        >
                                            {lastResult.won ? "+" : ""}
                                            {lastResult.payout}g
                                        </span>
                                        <span className="text-yellow-400">
                                            +{lastResult.energy} energy
                                        </span>
                                    </div>
                                </div>
                            )}
                        </div>
                    </div>
                )}

                {/* Wager Controls */}
                <div className="rounded-lg border border-stone-600/30 bg-stone-800/30 p-4">
                    <div className="mb-3 flex items-center justify-between">
                        <span className="font-pixel text-sm text-stone-400">Wager</span>
                        <div className="flex items-center gap-1">
                            <Coins className="h-5 w-5 text-amber-400" />
                            <span className="font-pixel text-xl text-amber-300">{wager}g</span>
                        </div>
                    </div>

                    <div className="flex items-center gap-2">
                        <button
                            onClick={() => handleWagerChange(-10)}
                            disabled={loading || wager <= minWager}
                            className="rounded-lg border border-stone-600 bg-stone-700/50 p-2 transition hover:bg-stone-600/50 disabled:opacity-50"
                        >
                            <Minus className="h-4 w-4 text-stone-300" />
                        </button>

                        <input
                            type="range"
                            min={minWager}
                            max={effectiveMaxWager}
                            step={10}
                            value={wager}
                            onChange={(e) => setWager(Number(e.target.value))}
                            disabled={loading}
                            className="flex-1 accent-purple-500"
                        />

                        <button
                            onClick={() => handleWagerChange(10)}
                            disabled={loading || wager >= effectiveMaxWager}
                            className="rounded-lg border border-stone-600 bg-stone-700/50 p-2 transition hover:bg-stone-600/50 disabled:opacity-50"
                        >
                            <Plus className="h-4 w-4 text-stone-300" />
                        </button>
                    </div>

                    <div className="mt-3 grid grid-cols-4 gap-2">
                        {[10, 50, 100, 250, 500, 1000, 2500].map((amount) => (
                            <button
                                key={amount}
                                onClick={() => handleQuickWager(amount)}
                                disabled={loading || playerGold < amount}
                                className="rounded-md border border-stone-600/50 bg-stone-700/30 px-2 py-1.5 font-pixel text-xs text-stone-400 transition hover:bg-stone-600/50 disabled:opacity-50"
                            >
                                {amount}g
                            </button>
                        ))}
                        <button
                            onClick={() => handleQuickWager(-1)}
                            disabled={loading || playerGold < minWager}
                            className="rounded-md border border-amber-600/50 bg-amber-900/30 px-2 py-1.5 font-pixel text-xs text-amber-400 transition hover:bg-amber-800/50 disabled:opacity-50"
                        >
                            Max
                        </button>
                    </div>
                </div>

                {/* Play Button */}
                {!canPlay && cooldownEnds ? (
                    <div className="flex items-center justify-center gap-2 rounded-lg bg-stone-800/50 p-4">
                        <CooldownTimer cooldownEnds={cooldownEnds} />
                    </div>
                ) : (
                    <button
                        onClick={handlePlay}
                        disabled={!canPlayNow}
                        className={`flex w-full items-center justify-center gap-2 rounded-lg px-4 py-3 font-pixel text-lg transition ${
                            canPlayNow
                                ? "bg-purple-600 text-white hover:bg-purple-500"
                                : "cursor-not-allowed bg-stone-700 text-stone-500"
                        }`}
                    >
                        {loading ? (
                            <>
                                <Loader2 className="h-5 w-5 animate-spin" />
                                Rolling...
                            </>
                        ) : !canAffordWager ? (
                            "Not enough gold"
                        ) : (
                            <>
                                <Dices className="h-5 w-5" />
                                Roll the Dice
                            </>
                        )}
                    </button>
                )}

                {/* Reason why can't play */}
                {!canPlay && reason && !cooldownEnds && (
                    <p className="text-center font-pixel text-sm text-red-400">{reason}</p>
                )}
            </div>
        );
    }

    // Full layout mode (not currently used but kept for flexibility)
    return (
        <div className="grid gap-6 lg:grid-cols-4">
            {/* Left Column: Wager & Stats */}
            <div className="space-y-4">
                {/* Wager Section */}
                <div>
                    <p className="mb-2 text-sm text-stone-300">Place your wager and pick a game.</p>

                    <div className="mb-3 flex items-center justify-between">
                        <span className="font-pixel text-sm text-stone-400">Wager</span>
                        <div className="flex items-center gap-1">
                            <Coins className="h-4 w-4 text-amber-400" />
                            <span className="font-pixel text-lg text-amber-300">{wager}g</span>
                        </div>
                    </div>

                    {/* Wager Controls */}
                    <div className="flex items-center gap-2">
                        <button
                            onClick={() => handleWagerChange(-10)}
                            disabled={loading || wager <= minWager}
                            className="rounded-lg border border-stone-600 bg-stone-700/50 p-2 transition hover:bg-stone-600/50 disabled:opacity-50"
                        >
                            <Minus className="h-4 w-4 text-stone-300" />
                        </button>

                        <input
                            type="range"
                            min={minWager}
                            max={effectiveMaxWager}
                            step={10}
                            value={wager}
                            onChange={(e) => setWager(Number(e.target.value))}
                            disabled={loading}
                            className="flex-1 accent-purple-500"
                        />

                        <button
                            onClick={() => handleWagerChange(10)}
                            disabled={loading || wager >= effectiveMaxWager}
                            className="rounded-lg border border-stone-600 bg-stone-700/50 p-2 transition hover:bg-stone-600/50 disabled:opacity-50"
                        >
                            <Plus className="h-4 w-4 text-stone-300" />
                        </button>
                    </div>

                    {/* Quick Wager Buttons */}
                    <div className="mt-3 grid grid-cols-3 gap-2">
                        {[10, 50, 100, 250, 500, 1000].map((amount) => (
                            <button
                                key={amount}
                                onClick={() => handleQuickWager(amount)}
                                disabled={loading || playerGold < amount}
                                className="rounded-md border border-stone-600/50 bg-stone-700/30 px-2 py-1 font-pixel text-[10px] text-stone-400 transition hover:bg-stone-600/50 disabled:opacity-50"
                            >
                                {amount}g
                            </button>
                        ))}
                        <button
                            onClick={() => handleQuickWager(2500)}
                            disabled={loading || playerGold < 2500}
                            className="rounded-md border border-stone-600/50 bg-stone-700/30 px-2 py-1 font-pixel text-[10px] text-stone-400 transition hover:bg-stone-600/50 disabled:opacity-50"
                        >
                            2500g
                        </button>
                        <button
                            onClick={() => handleQuickWager(-1)}
                            disabled={loading || playerGold < minWager}
                            className="col-span-2 rounded-md border border-amber-600/50 bg-amber-900/30 px-2 py-1 font-pixel text-[10px] text-amber-400 transition hover:bg-amber-800/50 disabled:opacity-50"
                        >
                            Max
                        </button>
                    </div>
                </div>

                {/* Stats */}
                <div className="rounded-lg border border-stone-600/30 bg-stone-800/30 p-3">
                    <p className="mb-2 font-pixel text-xs text-stone-500">Your Stats Here</p>
                    <div className="grid grid-cols-3 gap-2">
                        <div className="text-center">
                            <p className="font-pixel text-sm text-green-400">{tavernStats.wins}</p>
                            <p className="font-pixel text-[10px] text-stone-500">Wins</p>
                        </div>
                        <div className="text-center">
                            <p className="font-pixel text-sm text-red-400">{tavernStats.losses}</p>
                            <p className="font-pixel text-[10px] text-stone-500">Losses</p>
                        </div>
                        <div className="text-center">
                            <p
                                className={`font-pixel text-sm ${
                                    tavernStats.total_profit >= 0
                                        ? "text-green-400"
                                        : "text-red-400"
                                }`}
                            >
                                {tavernStats.total_profit >= 0 ? "+" : ""}
                                {tavernStats.total_profit}g
                            </p>
                            <p className="font-pixel text-[10px] text-stone-500">Profit</p>
                        </div>
                    </div>
                </div>

                {/* Reason why can't play */}
                {!canPlay && reason && !cooldownEnds && (
                    <p className="font-pixel text-xs text-red-400">{reason}</p>
                )}

                {/* Recent Games */}
                {recentGames.length > 0 && (
                    <div className="rounded-lg border border-stone-600/30 bg-stone-800/20 p-3">
                        <p className="mb-2 font-pixel text-xs text-stone-500">Recent Games</p>
                        <div className="space-y-1">
                            {recentGames.slice(0, 3).map((game) => (
                                <div
                                    key={game.id}
                                    className="flex items-center justify-between text-[10px]"
                                >
                                    <span className="text-stone-400">
                                        {GAME_INFO[game.game_type as GameType]?.name ||
                                            game.game_type}
                                    </span>
                                    <span className={game.won ? "text-green-400" : "text-red-400"}>
                                        {game.won ? "+" : ""}
                                        {game.payout}g
                                    </span>
                                </div>
                            ))}
                        </div>
                    </div>
                )}
            </div>

            {/* Right Column: Game Cards */}
            <div className="lg:col-span-3">
                {/* Dice Display (shows when rolling or has result) */}
                {(rolling || displayDice) && (
                    <div className="mb-4 flex items-center justify-center gap-6 rounded-lg bg-stone-800/50 p-4">
                        <div className="text-center">
                            <p className="mb-2 font-pixel text-xs text-stone-400">You</p>
                            <div className="flex gap-2">
                                {(displayDice?.player || [1, 1]).map((val, idx) => (
                                    <DiceFace key={idx} value={val} size="lg" rolling={rolling} />
                                ))}
                            </div>
                        </div>

                        {selectedGame === "high_roll" && displayDice?.house && (
                            <>
                                <div className="font-pixel text-xl text-stone-500">vs</div>
                                <div className="text-center">
                                    <p className="mb-2 font-pixel text-xs text-stone-400">House</p>
                                    <div className="flex gap-2">
                                        {displayDice.house.map((val, idx) => (
                                            <DiceFace
                                                key={idx}
                                                value={val}
                                                size="lg"
                                                rolling={rolling}
                                            />
                                        ))}
                                    </div>
                                </div>
                            </>
                        )}

                        {/* Result inline */}
                        {lastResult && !rolling && (
                            <div className="ml-4 text-center">
                                <div className="flex items-center gap-2">
                                    {lastResult.won ? (
                                        <Trophy className="h-5 w-5 text-green-400" />
                                    ) : (
                                        <TrendingDown className="h-5 w-5 text-red-400" />
                                    )}
                                    <span
                                        className={`font-pixel text-lg ${
                                            lastResult.won ? "text-green-300" : "text-red-300"
                                        }`}
                                    >
                                        {lastResult.won ? "Win!" : "Lose"}
                                    </span>
                                </div>
                                <div className="mt-1 flex items-center justify-center gap-3 text-sm">
                                    <span
                                        className={
                                            lastResult.won ? "text-green-400" : "text-red-400"
                                        }
                                    >
                                        {lastResult.won ? "+" : ""}
                                        {lastResult.payout}g
                                    </span>
                                    <span className="text-yellow-400">
                                        +{lastResult.energy} energy
                                    </span>
                                </div>
                            </div>
                        )}
                    </div>
                )}

                {/* Game Type Cards Grid */}
                <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    {(Object.keys(GAME_INFO) as GameType[]).map((gameType) => (
                        <GameCard
                            key={gameType}
                            gameType={gameType}
                            isSelected={selectedGame === gameType}
                            onSelect={() => setSelectedGame(gameType)}
                            onPlay={handlePlay}
                            wager={wager}
                            canPlay={canPlay}
                            canAfford={canAffordWager}
                            loading={loading}
                            cooldownEnds={cooldownEnds}
                        />
                    ))}
                </div>
            </div>
        </div>
    );
}
