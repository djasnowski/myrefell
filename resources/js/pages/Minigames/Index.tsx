import { Head, router, usePage } from "@inertiajs/react";
import { Clock, Flame, Gift, History, Loader2, Sparkles, Star, Trophy } from "lucide-react";
import { useEffect, useRef, useState } from "react";
import AppLayout from "@/layouts/app-layout";
import { gameToast } from "@/components/ui/game-toast";
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from "@/components/ui/dialog";
import { Button } from "@/components/ui/button";
import type { BreadcrumbItem } from "@/types";

interface SpinResult {
    success: boolean;
    reward_type: "gold" | "item";
    reward_amount?: number;
    reward_item?: {
        name: string;
        rarity: string;
    };
    rarity: "common" | "uncommon" | "rare" | "epic";
    message: string;
    new_streak: number;
    segment_index: number;
}

interface RecentPlay {
    id: number;
    reward_type: "gold" | "item";
    reward_amount: number | null;
    reward_rarity: string;
    item_name: string | null;
    played_at: string;
}

interface PageProps {
    can_play: boolean;
    streak: number;
    last_played: string | null;
    recent_plays: RecentPlay[];
    flash?: {
        success?: string;
        error?: string;
        result?: SpinResult;
    };
    [key: string]: unknown;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: "Dashboard", href: "/dashboard" },
    { title: "Minigames", href: "/minigames" },
];

// Wheel segment configuration - 12 segments
const WHEEL_SEGMENTS = [
    { label: "50 Gold", color: "#78350f", textColor: "#fbbf24" },
    { label: "Rare Item", color: "#1e3a8a", textColor: "#60a5fa" },
    { label: "100 Gold", color: "#422006", textColor: "#fcd34d" },
    { label: "150 Gold", color: "#78350f", textColor: "#fbbf24" },
    { label: "Epic Item", color: "#581c87", textColor: "#c084fc" },
    { label: "200 Gold", color: "#422006", textColor: "#fcd34d" },
    { label: "Mystery Box", color: "#064e3b", textColor: "#34d399" },
    { label: "300 Gold", color: "#78350f", textColor: "#fbbf24" },
    { label: "500 Gold", color: "#422006", textColor: "#fcd34d" },
    { label: "Jackpot!", color: "#7c2d12", textColor: "#fb923c" },
    { label: "750 Gold", color: "#78350f", textColor: "#fbbf24" },
    { label: "1000 Gold", color: "#422006", textColor: "#fcd34d" },
];

const SEGMENT_COUNT = WHEEL_SEGMENTS.length;
const SEGMENT_ANGLE = 360 / SEGMENT_COUNT;

interface WheelOfFortuneProps {
    disabled: boolean;
    onSpinStart: () => void;
    onSpinComplete: (segmentIndex: number) => void;
    targetSegment: number | null;
    spinning: boolean;
}

function WheelOfFortune({
    disabled,
    onSpinStart,
    onSpinComplete,
    targetSegment,
    spinning,
}: WheelOfFortuneProps) {
    const wheelRef = useRef<HTMLUListElement>(null);
    const animationRef = useRef<Animation | null>(null);
    const previousEndDegreeRef = useRef(0);

    useEffect(() => {
        if (spinning && targetSegment !== null && wheelRef.current) {
            // Cancel any existing animation
            if (animationRef.current) {
                animationRef.current.cancel();
            }

            // Calculate rotation to land on target segment
            // Pointer is at left (180°). Segment i is at (i * 30)°
            // Wheel has -15° base offset so pointer aligns with segment centers
            // To land segment i under pointer: rotate so segment start reaches 180°
            const segmentAngle = 360 / SEGMENT_COUNT;
            const segmentStart = targetSegment * segmentAngle;
            // Rotation needed = 180 - segmentStart (normalize to 0-360)
            const targetAngle = (((180 - segmentStart) % 360) + 360) % 360;

            // Add 5-7 full rotations for spin effect
            // Start from -15deg (base offset) and end at targetAngle - 15deg
            const extraSpins = 5 + Math.floor(Math.random() * 3);
            const newEndDegree = extraSpins * 360 + targetAngle - 15;

            // Use Web Animations API - animate from base offset
            animationRef.current = wheelRef.current.animate(
                [{ transform: `rotate(-15deg)` }, { transform: `rotate(${newEndDegree}deg)` }],
                {
                    duration: 4000,
                    direction: "normal",
                    easing: "cubic-bezier(0.440, -0.205, 0.000, 1.130)",
                    fill: "forwards",
                    iterations: 1,
                },
            );

            animationRef.current.onfinish = () => {
                onSpinComplete(targetSegment);
            };
        }
    }, [spinning, targetSegment, onSpinComplete]);

    return (
        <div className="relative flex flex-col items-center">
            {/* Wheel container - using CSS from original */}
            <fieldset
                className="relative aspect-square w-[320px]"
                style={{
                    containerType: "inline-size",
                }}
            >
                {/* Pointer on left side pointing right */}
                <div
                    className="absolute left-[-8px] top-1/2 z-10"
                    style={{
                        width: 0,
                        height: 0,
                        borderTop: "14px solid transparent",
                        borderBottom: "14px solid transparent",
                        borderLeft: "24px solid #dc2626",
                        transform: "translateY(-50%)",
                        filter: "drop-shadow(2px 0 3px rgba(0,0,0,0.6))",
                    }}
                />

                {/* Wheel */}
                <ul
                    ref={wheelRef}
                    className="absolute inset-0"
                    style={{
                        clipPath: "inset(0 0 0 0 round 50%)",
                        display: "grid",
                        placeContent: "center start",
                        transform: "rotate(-15deg)", // Offset so pointer aligns with segment centers
                    }}
                >
                    {WHEEL_SEGMENTS.map((segment, index) => (
                        <li
                            key={index}
                            className="grid content-center"
                            style={{
                                gridArea: "1 / -1",
                                aspectRatio: `1 / calc(2 * tan(180deg / ${SEGMENT_COUNT}))`,
                                backgroundColor: segment.color,
                                clipPath: "polygon(0% 0%, 100% 50%, 0% 100%)",
                                width: "50cqi",
                                paddingLeft: "1ch",
                                transformOrigin: "center right",
                                rotate: `${index * SEGMENT_ANGLE}deg`,
                                userSelect: "none",
                            }}
                        >
                            <span
                                className="font-pixel text-[3cqi] font-bold"
                                style={{ color: segment.textColor }}
                            >
                                {segment.label}
                            </span>
                        </li>
                    ))}
                </ul>

                {/* Center circle */}
                <div
                    className="pointer-events-none absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2"
                    style={{
                        width: "20cqi",
                        height: "20cqi",
                        borderRadius: "50%",
                        backgroundColor: "hsla(0, 0%, 100%, 0.1)",
                        border: "3px solid #c9a227",
                    }}
                />
            </fieldset>

            {/* Spin button */}
            <button
                onClick={onSpinStart}
                disabled={disabled || spinning}
                className={`mt-6 flex items-center gap-2 rounded-lg border-2 px-8 py-3 font-pixel text-lg transition-all ${
                    disabled || spinning
                        ? "cursor-not-allowed border-stone-600 bg-stone-800/50 text-stone-500"
                        : "border-amber-500 bg-gradient-to-br from-amber-800 to-amber-900 text-amber-200 hover:from-amber-700 hover:to-amber-800"
                }`}
            >
                {spinning ? (
                    <>
                        <Loader2 className="h-5 w-5 animate-spin" />
                        Spinning...
                    </>
                ) : (
                    <>
                        <Sparkles className="h-5 w-5" />
                        Spin the Wheel!
                    </>
                )}
            </button>
        </div>
    );
}

function StreakDisplay({ streak }: { streak: number }) {
    const maxStreak = 5;
    return (
        <div className="flex items-center gap-1">
            {Array.from({ length: maxStreak }).map((_, i) => (
                <Flame
                    key={i}
                    className={`h-5 w-5 transition-all ${
                        i < streak
                            ? "text-orange-400 drop-shadow-[0_0_4px_rgba(251,146,60,0.5)]"
                            : "text-stone-600"
                    }`}
                />
            ))}
        </div>
    );
}

function TimeUntilReset() {
    const [timeLeft, setTimeLeft] = useState("");

    useEffect(() => {
        const calculateTimeLeft = () => {
            const now = new Date();
            const tomorrow = new Date(now);
            tomorrow.setDate(tomorrow.getDate() + 1);
            tomorrow.setHours(0, 0, 0, 0);

            const diff = tomorrow.getTime() - now.getTime();
            const hours = Math.floor(diff / (1000 * 60 * 60));
            const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((diff % (1000 * 60)) / 1000);

            setTimeLeft(`${hours}h ${minutes}m ${seconds}s`);
        };

        calculateTimeLeft();
        const interval = setInterval(calculateTimeLeft, 1000);
        return () => clearInterval(interval);
    }, []);

    return (
        <div className="flex items-center gap-2 rounded-lg border border-stone-600 bg-stone-800/50 px-4 py-2">
            <Clock className="h-4 w-4 text-stone-400" />
            <span className="font-pixel text-xs text-stone-400">Resets in:</span>
            <span className="font-pixel text-sm text-amber-400">{timeLeft}</span>
        </div>
    );
}

function RecentPlaysHistory({ plays }: { plays: RecentPlay[] }) {
    const rarityColors: Record<string, string> = {
        common: "text-stone-400 border-stone-500/50 bg-stone-800/30",
        uncommon: "text-green-400 border-green-500/50 bg-green-900/20",
        rare: "text-blue-400 border-blue-500/50 bg-blue-900/20",
        epic: "text-purple-400 border-purple-500/50 bg-purple-900/20",
    };

    if (plays.length === 0) {
        return (
            <div className="py-6 text-center">
                <History className="mx-auto mb-2 h-8 w-8 text-stone-600" />
                <p className="font-pixel text-xs text-stone-500">No spins yet</p>
            </div>
        );
    }

    return (
        <div className="space-y-2">
            {plays.map((play) => (
                <div
                    key={play.id}
                    className={`flex items-center justify-between rounded-lg border px-3 py-2 ${
                        rarityColors[play.reward_rarity] || rarityColors.common
                    }`}
                >
                    <div className="flex items-center gap-2">
                        <Gift className="h-4 w-4" />
                        <span className="font-pixel text-xs">
                            {play.reward_type === "gold"
                                ? `${play.reward_amount} Gold`
                                : play.item_name || "Item"}
                        </span>
                    </div>
                    <span className="font-pixel text-[10px] capitalize text-stone-500">
                        {play.reward_rarity}
                    </span>
                </div>
            ))}
        </div>
    );
}

export default function MinigamesIndex() {
    const { can_play, streak, recent_plays, flash } = usePage<PageProps>().props;

    const [spinning, setSpinning] = useState(false);
    const [targetSegment, setTargetSegment] = useState<number | null>(null);
    const [showResultModal, setShowResultModal] = useState(false);
    const [spinResult, setSpinResult] = useState<SpinResult | null>(null);
    const [canPlayState, setCanPlayState] = useState(can_play);
    const [pendingResult, setPendingResult] = useState<SpinResult | null>(null);

    // Only show error flashes, not results (results shown after animation)
    useEffect(() => {
        if (flash?.error) {
            gameToast.error(flash.error);
        }
    }, [flash]);

    const handleSpinStart = () => {
        if (!canPlayState || spinning) return;

        setSpinning(true);

        router.post(
            "/minigames/spin",
            {},
            {
                preserveScroll: true,
                onSuccess: (page) => {
                    const result = (page.props as PageProps).flash?.result;
                    if (result) {
                        // Store result but don't show yet - wait for animation
                        setPendingResult(result);
                        setTargetSegment(result.segment_index);
                    }
                },
                onError: () => {
                    setSpinning(false);
                    gameToast.error("Failed to spin the wheel");
                },
                // Don't reload here - we'll reload after modal is closed
            },
        );
    };

    const handleSpinComplete = () => {
        setSpinning(false);
        setCanPlayState(false);

        // Wait 3 seconds so user can see where it landed, then show modal
        if (pendingResult) {
            setTimeout(() => {
                setSpinResult(pendingResult);
                setShowResultModal(true);
                setPendingResult(null);
            }, 3000);
        }
    };

    const closeResultModal = () => {
        setShowResultModal(false);
        setSpinResult(null);
        setTargetSegment(null);
        // Reload to refresh CSRF and data
        router.reload();
    };

    const getRarityStyle = (rarity: string) => {
        switch (rarity) {
            case "epic":
                return "text-purple-400 bg-purple-900/30 border-purple-500";
            case "rare":
                return "text-blue-400 bg-blue-900/30 border-blue-500";
            case "uncommon":
                return "text-green-400 bg-green-900/30 border-green-500";
            default:
                return "text-stone-300 bg-stone-800/30 border-stone-500";
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Daily Wheel" />
            <div className="flex h-full flex-1 flex-col overflow-auto p-4">
                <div className="mx-auto w-full max-w-4xl">
                    {/* Header */}
                    <div className="mb-6 rounded-xl border-2 border-amber-600/50 bg-gradient-to-br from-amber-900/30 to-stone-900 p-6">
                        <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                            <div className="flex items-center gap-4">
                                <div className="rounded-lg bg-stone-800/50 p-4">
                                    <Trophy className="h-12 w-12 text-amber-400" />
                                </div>
                                <div>
                                    <h1 className="font-pixel text-2xl text-amber-400">
                                        Daily Wheel of Fortune
                                    </h1>
                                    <p className="font-pixel text-xs text-stone-400">
                                        Spin once per day for amazing rewards!
                                    </p>
                                </div>
                            </div>
                            <div className="flex flex-col items-end gap-2">
                                <div className="flex items-center gap-2">
                                    <span className="font-pixel text-xs text-stone-400">
                                        Daily Streak:
                                    </span>
                                    <StreakDisplay streak={streak} />
                                </div>
                                <span className="font-pixel text-[10px] text-stone-500">
                                    Higher streak = better rewards!
                                </span>
                            </div>
                        </div>
                    </div>

                    {/* Main Content Grid */}
                    <div className="grid gap-6 lg:grid-cols-3">
                        {/* Wheel Section */}
                        <div className="lg:col-span-2">
                            <div className="rounded-xl border-2 border-stone-600/50 bg-stone-800/30 p-6">
                                {!canPlayState && !spinning && (
                                    <div className="mb-4 flex flex-col items-center gap-3 rounded-lg border border-amber-600/50 bg-amber-900/20 p-4">
                                        <Star className="h-8 w-8 text-amber-400" />
                                        <p className="font-pixel text-sm text-amber-300">
                                            Come back tomorrow for another spin!
                                        </p>
                                        <TimeUntilReset />
                                    </div>
                                )}
                                <WheelOfFortune
                                    disabled={!canPlayState}
                                    onSpinStart={handleSpinStart}
                                    onSpinComplete={handleSpinComplete}
                                    targetSegment={targetSegment}
                                    spinning={spinning}
                                />
                            </div>
                        </div>

                        {/* Side Panel */}
                        <div className="space-y-6">
                            {/* Recent Rewards */}
                            <div className="rounded-xl border-2 border-stone-600/50 bg-stone-800/30 p-4">
                                <div className="mb-3 flex items-center gap-2">
                                    <History className="h-4 w-4 text-amber-400" />
                                    <h3 className="font-pixel text-sm text-amber-300">
                                        Recent Rewards
                                    </h3>
                                </div>
                                <RecentPlaysHistory plays={recent_plays} />
                            </div>
                        </div>
                    </div>

                    {/* Info Box */}
                    <div className="mt-6 rounded-lg border border-stone-600 bg-stone-800/30 p-4">
                        <h3 className="mb-2 font-pixel text-sm text-stone-300">How It Works</h3>
                        <ul className="space-y-1 font-pixel text-[10px] text-stone-400">
                            <li>• Spin the wheel once per day for free</li>
                            <li>• Build your streak by spinning on consecutive days</li>
                            <li>• Higher streaks increase your chances of rare rewards</li>
                            <li>• Rewards include gold, rare items, and mystery boxes</li>
                            <li>• The wheel resets at midnight server time</li>
                        </ul>
                    </div>
                </div>
            </div>

            {/* Result Modal */}
            <Dialog open={showResultModal} onOpenChange={setShowResultModal}>
                <DialogContent className="border-2 border-amber-600/50 bg-gradient-to-br from-stone-900 to-stone-950 sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle className="flex items-center gap-2 font-pixel text-xl text-amber-400">
                            <Gift className="h-6 w-6" />
                            Congratulations!
                        </DialogTitle>
                        <DialogDescription className="font-pixel text-sm text-stone-400">
                            You won a reward from the Wheel of Fortune!
                        </DialogDescription>
                    </DialogHeader>

                    {spinResult && (
                        <div className="flex flex-col items-center py-6">
                            <div
                                className={`mb-4 rounded-lg border-2 px-6 py-4 ${getRarityStyle(spinResult.rarity)}`}
                            >
                                <p className="font-pixel text-lg">
                                    {spinResult.reward_type === "gold"
                                        ? `${spinResult.reward_amount} Gold`
                                        : spinResult.reward_item?.name || "Mystery Item"}
                                </p>
                                <p className="mt-1 text-center font-pixel text-xs capitalize">
                                    {spinResult.rarity} Reward
                                </p>
                            </div>

                            <div className="flex items-center gap-2">
                                <span className="font-pixel text-xs text-stone-400">
                                    New Streak:
                                </span>
                                <StreakDisplay streak={spinResult.new_streak} />
                            </div>
                        </div>
                    )}

                    <DialogFooter>
                        <Button
                            onClick={closeResultModal}
                            className="w-full border-2 border-amber-500 bg-amber-900/50 font-pixel text-amber-200 hover:bg-amber-800/50"
                        >
                            Huzzah!
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
