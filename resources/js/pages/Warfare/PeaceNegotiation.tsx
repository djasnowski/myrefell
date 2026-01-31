import { Head, Link, router, usePage } from "@inertiajs/react";
import {
    AlertTriangle,
    ArrowLeft,
    Check,
    Clock,
    Coins,
    Flag,
    HandshakeIcon,
    Map,
    Scale,
    Shield,
    Sword,
    Swords,
    X,
} from "lucide-react";
import { useState } from "react";
import AppLayout from "@/layouts/app-layout";
import type { BreadcrumbItem } from "@/types";

interface WarSide {
    type: string;
    id: number;
    name: string;
    kingdom_id: number | null;
    kingdom_name: string | null;
}

interface WarGoal {
    id: number;
    goal_type: string;
    is_achieved: boolean;
    war_score_value: number;
}

interface War {
    id: number;
    name: string;
    casus_belli: string;
    status: string;
    attacker_war_score: number;
    defender_war_score: number;
    declared_at: string | null;
    ended_at: string | null;
    days_active: number;
    attacker: WarSide;
    defender: WarSide;
    goals?: WarGoal[];
}

interface Territory {
    id: number;
    type: string;
    name: string;
    can_demand: boolean;
    direction: "to_you" | "from_you";
}

interface TruceOption {
    value: number;
    label: string;
}

interface PageProps {
    war: War;
    can_negotiate: boolean;
    error?: string;
    war_score: {
        attacker: number;
        defender: number;
    };
    user_side: "attacker" | "defender" | null;
    territories: Territory[];
    player_gold: number;
    enemy_gold: number;
    truce_options: TruceOption[];
    [key: string]: unknown;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: "Dashboard", href: "/dashboard" },
    { title: "Warfare", href: "#" },
    { title: "Wars", href: "/warfare/wars" },
    { title: "Peace Negotiation", href: "#" },
];

export default function PeaceNegotiation() {
    const {
        war,
        can_negotiate,
        error: initialError,
        war_score,
        user_side,
        territories,
        player_gold,
        enemy_gold,
        truce_options,
    } = usePage<PageProps>().props;

    const [treatyType, setTreatyType] = useState<"white_peace" | "surrender" | "negotiated">(
        "white_peace",
    );
    const [selectedTerritories, setSelectedTerritories] = useState<number[]>([]);
    const [goldPayment, setGoldPayment] = useState<number>(0);
    const [paymentDirection, setPaymentDirection] = useState<"to_you" | "from_you">("to_you");
    const [truceDays, setTruceDays] = useState<number>(365);
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [error, setError] = useState<string | null>(initialError || null);

    const isWinning =
        user_side === "attacker"
            ? war_score.attacker > war_score.defender
            : war_score.defender > war_score.attacker;

    const userScore = user_side === "attacker" ? war_score.attacker : war_score.defender;
    const enemyScore = user_side === "attacker" ? war_score.defender : war_score.attacker;

    const maxGoldDemand = Math.min(
        paymentDirection === "to_you" ? enemy_gold : player_gold,
        Math.max(0, userScore - enemyScore) * 50 + 1000,
    );

    const toggleTerritory = (territoryId: number) => {
        setSelectedTerritories((prev) =>
            prev.includes(territoryId)
                ? prev.filter((id) => id !== territoryId)
                : [...prev, territoryId],
        );
    };

    const calculateAcceptanceLikelihood = (): {
        percentage: number;
        label: string;
        factors: string[];
    } => {
        const factors: string[] = [];
        let baseChance = 50;

        // War score factor
        const scoreDiff = userScore - enemyScore;
        if (scoreDiff > 50) {
            baseChance += 30;
            factors.push(`War score strongly favors you (+30%)`);
        } else if (scoreDiff > 20) {
            baseChance += 15;
            factors.push(`War score favors you (+15%)`);
        } else if (scoreDiff < -50) {
            baseChance -= 30;
            factors.push(`War score strongly favors enemy (-30%)`);
        } else if (scoreDiff < -20) {
            baseChance -= 15;
            factors.push(`War score favors enemy (-15%)`);
        }

        // Treaty type factor
        if (treatyType === "white_peace") {
            baseChance += 20;
            factors.push(`White peace is more acceptable (+20%)`);
        } else if (treatyType === "surrender") {
            baseChance += 40;
            factors.push(`Surrender is always accepted (+40%)`);
        }

        // Territory demands factor
        if (selectedTerritories.length > 0) {
            const demandPenalty = selectedTerritories.length * 10;
            baseChance -= demandPenalty;
            factors.push(`Territory demands (-${demandPenalty}%)`);
        }

        // Gold payment factor
        if (goldPayment > 0) {
            if (paymentDirection === "to_you") {
                const goldPenalty = Math.min(20, Math.floor(goldPayment / 500) * 5);
                baseChance -= goldPenalty;
                if (goldPenalty > 0) factors.push(`Gold demands (-${goldPenalty}%)`);
            } else {
                const goldBonus = Math.min(15, Math.floor(goldPayment / 500) * 5);
                baseChance += goldBonus;
                if (goldBonus > 0) factors.push(`Offering gold (+${goldBonus}%)`);
            }
        }

        // Clamp to reasonable range
        const percentage = Math.min(95, Math.max(5, baseChance));

        let label = "Unlikely";
        if (percentage >= 80) label = "Very Likely";
        else if (percentage >= 65) label = "Likely";
        else if (percentage >= 45) label = "Uncertain";
        else if (percentage >= 25) label = "Unlikely";
        else label = "Very Unlikely";

        return { percentage, label, factors };
    };

    const likelihood = calculateAcceptanceLikelihood();

    const determineWinnerSide = (): "attacker" | "defender" | null => {
        if (treatyType === "white_peace") return null;
        if (treatyType === "surrender") {
            return user_side === "attacker" ? "defender" : "attacker";
        }
        // For negotiated peace, winner is determined by score or manual selection
        if (userScore > enemyScore) return user_side;
        if (enemyScore > userScore) return user_side === "attacker" ? "defender" : "attacker";
        return null;
    };

    const handleSubmit = async () => {
        setIsSubmitting(true);
        setError(null);

        try {
            const territoryChanges = selectedTerritories.map((id) => {
                const territory = territories.find((t) => t.id === id);
                return {
                    territory_id: id,
                    territory_type: territory?.type || "barony",
                    direction: territory?.direction || "to_you",
                };
            });

            const response = await fetch(`/warfare/wars/${war.id}/peace`, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN":
                        document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')
                            ?.content || "",
                },
                body: JSON.stringify({
                    treaty_type: treatyType,
                    winner_side: determineWinnerSide(),
                    territory_changes: territoryChanges,
                    gold_payment: paymentDirection === "to_you" ? goldPayment : -goldPayment,
                    truce_days: truceDays,
                }),
            });

            const data = await response.json();

            if (data.success) {
                router.visit(`/warfare/wars/${war.id}`);
            } else {
                setError(data.message || "Failed to offer peace.");
            }
        } catch {
            setError("An error occurred while offering peace.");
        } finally {
            setIsSubmitting(false);
        }
    };

    const renderWarScoreBar = () => {
        const attackerWidth = (war_score.attacker / 200) * 100;
        const defenderWidth = (war_score.defender / 200) * 100;

        return (
            <div className="flex h-8 w-full overflow-hidden rounded-lg bg-stone-800">
                <div
                    className="flex items-center justify-end bg-gradient-to-r from-red-700 to-red-500 pr-2 transition-all duration-300"
                    style={{ width: `${attackerWidth}%` }}
                >
                    {war_score.attacker > 10 && (
                        <span className="font-pixel text-xs text-white">{war_score.attacker}</span>
                    )}
                </div>
                <div className="w-1 bg-stone-600" />
                <div
                    className="flex items-center justify-start bg-gradient-to-r from-blue-500 to-blue-700 pl-2 transition-all duration-300"
                    style={{ width: `${defenderWidth}%` }}
                >
                    {war_score.defender > 10 && (
                        <span className="font-pixel text-xs text-white">{war_score.defender}</span>
                    )}
                </div>
            </div>
        );
    };

    // Cannot negotiate state
    if (!can_negotiate) {
        return (
            <AppLayout breadcrumbs={breadcrumbs}>
                <Head title="Peace Negotiation" />
                <div className="flex h-full flex-1 flex-col gap-6 p-4">
                    <Link
                        href={`/warfare/wars/${war.id}`}
                        className="flex items-center gap-2 font-pixel text-sm text-stone-400 transition hover:text-stone-300"
                    >
                        <ArrowLeft className="h-4 w-4" />
                        Back to War
                    </Link>

                    <div className="rounded-xl border-2 border-red-500/30 bg-red-900/20 p-8 text-center">
                        <AlertTriangle className="mx-auto mb-4 h-12 w-12 text-red-400" />
                        <h1 className="mb-2 font-pixel text-xl text-white">
                            Cannot Negotiate Peace
                        </h1>
                        <p className="font-pixel text-sm text-stone-400">
                            {initialError ||
                                "You do not have permission to negotiate peace in this war."}
                        </p>
                    </div>
                </div>
            </AppLayout>
        );
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Peace Negotiation" />
            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                {/* Back Link */}
                <Link
                    href={`/warfare/wars/${war.id}`}
                    className="flex items-center gap-2 font-pixel text-sm text-stone-400 transition hover:text-stone-300"
                >
                    <ArrowLeft className="h-4 w-4" />
                    Back to War
                </Link>

                {/* Header */}
                <div className="flex items-center gap-3">
                    <HandshakeIcon className="h-8 w-8 text-green-400" />
                    <div>
                        <h1 className="font-pixel text-2xl text-white">Peace Negotiation</h1>
                        <p className="font-pixel text-sm text-stone-400">{war.name}</p>
                    </div>
                </div>

                {/* Error Display */}
                {error && (
                    <div className="rounded-xl border-2 border-red-500/30 bg-red-900/20 p-4">
                        <div className="flex items-center gap-2">
                            <AlertTriangle className="h-5 w-5 text-red-400" />
                            <span className="font-pixel text-sm text-red-300">{error}</span>
                        </div>
                    </div>
                )}

                {/* Current War Score */}
                <div className="rounded-xl border-2 border-stone-600/50 bg-stone-800/30 p-4">
                    <h2 className="mb-4 flex items-center gap-2 font-pixel text-lg text-white">
                        <Scale className="h-5 w-5 text-amber-400" />
                        Current War Score
                    </h2>

                    <div className="mb-2 flex justify-between font-pixel text-xs">
                        <div className="flex items-center gap-2">
                            <Sword className="h-4 w-4 text-red-400" />
                            <span className="text-red-300">{war.attacker.name}</span>
                            {user_side === "attacker" && (
                                <span className="rounded bg-green-900/50 px-1.5 py-0.5 text-[10px] text-green-400">
                                    You
                                </span>
                            )}
                        </div>
                        <div className="flex items-center gap-2">
                            {user_side === "defender" && (
                                <span className="rounded bg-green-900/50 px-1.5 py-0.5 text-[10px] text-green-400">
                                    You
                                </span>
                            )}
                            <span className="text-blue-300">{war.defender.name}</span>
                            <Shield className="h-4 w-4 text-blue-400" />
                        </div>
                    </div>

                    {renderWarScoreBar()}

                    <div className="mt-3 text-center font-pixel text-sm">
                        {isWinning ? (
                            <span className="text-green-400">You are winning this war</span>
                        ) : userScore === enemyScore ? (
                            <span className="text-amber-400">The war is evenly matched</span>
                        ) : (
                            <span className="text-red-400">You are losing this war</span>
                        )}
                    </div>
                </div>

                <div className="grid gap-6 lg:grid-cols-2">
                    {/* Left Column: Treaty Options */}
                    <div className="space-y-6">
                        {/* Treaty Type Selection */}
                        <div className="rounded-xl border-2 border-stone-600/50 bg-stone-800/30 p-4">
                            <h2 className="mb-4 flex items-center gap-2 font-pixel text-lg text-white">
                                <Flag className="h-5 w-5 text-purple-400" />
                                Treaty Type
                            </h2>

                            <div className="space-y-2">
                                <button
                                    onClick={() => {
                                        setTreatyType("white_peace");
                                        setSelectedTerritories([]);
                                        setGoldPayment(0);
                                    }}
                                    className={`w-full rounded-lg p-3 text-left transition ${
                                        treatyType === "white_peace"
                                            ? "border-2 border-stone-400/50 bg-stone-700/30"
                                            : "bg-stone-900/50 hover:bg-stone-900/70"
                                    }`}
                                >
                                    <div className="flex items-center justify-between">
                                        <span className="font-pixel text-sm text-white">
                                            White Peace
                                        </span>
                                        {treatyType === "white_peace" && (
                                            <Check className="h-4 w-4 text-green-400" />
                                        )}
                                    </div>
                                    <p className="mt-1 font-pixel text-[10px] text-stone-400">
                                        End the war with no victor. No territory changes or
                                        payments.
                                    </p>
                                </button>

                                <button
                                    onClick={() => setTreatyType("negotiated")}
                                    className={`w-full rounded-lg p-3 text-left transition ${
                                        treatyType === "negotiated"
                                            ? "border-2 border-amber-500/50 bg-amber-900/30"
                                            : "bg-stone-900/50 hover:bg-stone-900/70"
                                    }`}
                                >
                                    <div className="flex items-center justify-between">
                                        <span className="font-pixel text-sm text-white">
                                            Negotiated Peace
                                        </span>
                                        {treatyType === "negotiated" && (
                                            <Check className="h-4 w-4 text-green-400" />
                                        )}
                                    </div>
                                    <p className="mt-1 font-pixel text-[10px] text-stone-400">
                                        Negotiate specific terms including territory and gold.
                                    </p>
                                </button>

                                <button
                                    onClick={() => setTreatyType("surrender")}
                                    className={`w-full rounded-lg p-3 text-left transition ${
                                        treatyType === "surrender"
                                            ? "border-2 border-red-500/50 bg-red-900/30"
                                            : "bg-stone-900/50 hover:bg-stone-900/70"
                                    }`}
                                >
                                    <div className="flex items-center justify-between">
                                        <span className="font-pixel text-sm text-white">
                                            Surrender
                                        </span>
                                        {treatyType === "surrender" && (
                                            <Check className="h-4 w-4 text-green-400" />
                                        )}
                                    </div>
                                    <p className="mt-1 font-pixel text-[10px] text-red-400">
                                        Admit defeat. The enemy gains full victory.
                                    </p>
                                </button>
                            </div>
                        </div>

                        {/* Territory Changes */}
                        {treatyType === "negotiated" && territories.length > 0 && (
                            <div className="rounded-xl border-2 border-stone-600/50 bg-stone-800/30 p-4">
                                <h2 className="mb-4 flex items-center gap-2 font-pixel text-lg text-white">
                                    <Map className="h-5 w-5 text-green-400" />
                                    Territory Changes
                                </h2>

                                <div className="mb-3 font-pixel text-xs text-stone-400">
                                    Select territories to be transferred as part of the peace.
                                </div>

                                <div className="max-h-48 space-y-2 overflow-y-auto">
                                    {territories.filter(
                                        (t) => t.direction === "to_you" && t.can_demand,
                                    ).length > 0 && (
                                        <div className="mb-2">
                                            <div className="mb-1 font-pixel text-[10px] text-green-400">
                                                Enemy Cedes to You:
                                            </div>
                                            {territories
                                                .filter(
                                                    (t) => t.direction === "to_you" && t.can_demand,
                                                )
                                                .map((territory) => (
                                                    <button
                                                        key={territory.id}
                                                        onClick={() =>
                                                            toggleTerritory(territory.id)
                                                        }
                                                        className={`w-full rounded p-2 text-left transition ${
                                                            selectedTerritories.includes(
                                                                territory.id,
                                                            )
                                                                ? "border border-green-500/50 bg-green-900/30"
                                                                : "bg-stone-900/50 hover:bg-stone-900/70"
                                                        }`}
                                                    >
                                                        <div className="flex items-center justify-between">
                                                            <span className="font-pixel text-xs text-white">
                                                                {territory.name}
                                                            </span>
                                                            {selectedTerritories.includes(
                                                                territory.id,
                                                            ) ? (
                                                                <Check className="h-3 w-3 text-green-400" />
                                                            ) : (
                                                                <X className="h-3 w-3 text-stone-500" />
                                                            )}
                                                        </div>
                                                    </button>
                                                ))}
                                        </div>
                                    )}

                                    {territories.filter(
                                        (t) => t.direction === "from_you" && t.can_demand,
                                    ).length > 0 && (
                                        <div>
                                            <div className="mb-1 font-pixel text-[10px] text-red-400">
                                                You Cede to Enemy:
                                            </div>
                                            {territories
                                                .filter(
                                                    (t) =>
                                                        t.direction === "from_you" && t.can_demand,
                                                )
                                                .map((territory) => (
                                                    <button
                                                        key={territory.id}
                                                        onClick={() =>
                                                            toggleTerritory(territory.id)
                                                        }
                                                        className={`w-full rounded p-2 text-left transition ${
                                                            selectedTerritories.includes(
                                                                territory.id,
                                                            )
                                                                ? "border border-red-500/50 bg-red-900/30"
                                                                : "bg-stone-900/50 hover:bg-stone-900/70"
                                                        }`}
                                                    >
                                                        <div className="flex items-center justify-between">
                                                            <span className="font-pixel text-xs text-white">
                                                                {territory.name}
                                                            </span>
                                                            {selectedTerritories.includes(
                                                                territory.id,
                                                            ) ? (
                                                                <Check className="h-3 w-3 text-red-400" />
                                                            ) : (
                                                                <X className="h-3 w-3 text-stone-500" />
                                                            )}
                                                        </div>
                                                    </button>
                                                ))}
                                        </div>
                                    )}
                                </div>

                                {territories.every((t) => !t.can_demand) && (
                                    <p className="font-pixel text-xs text-stone-500">
                                        No territories available for transfer based on current war
                                        score.
                                    </p>
                                )}
                            </div>
                        )}
                    </div>

                    {/* Right Column: Payment & Truce */}
                    <div className="space-y-6">
                        {/* Gold Payment */}
                        {treatyType === "negotiated" && (
                            <div className="rounded-xl border-2 border-stone-600/50 bg-stone-800/30 p-4">
                                <h2 className="mb-4 flex items-center gap-2 font-pixel text-lg text-white">
                                    <Coins className="h-5 w-5 text-amber-400" />
                                    Gold Payment
                                </h2>

                                <div className="mb-4 flex gap-2">
                                    <button
                                        onClick={() => {
                                            setPaymentDirection("to_you");
                                            setGoldPayment(0);
                                        }}
                                        className={`flex-1 rounded border px-3 py-1.5 font-pixel text-xs transition ${
                                            paymentDirection === "to_you"
                                                ? "border-green-500/50 bg-green-900/30 text-green-300"
                                                : "border-stone-600/50 bg-stone-900/20 text-stone-400 hover:bg-stone-900/40"
                                        }`}
                                    >
                                        They Pay You
                                    </button>
                                    <button
                                        onClick={() => {
                                            setPaymentDirection("from_you");
                                            setGoldPayment(0);
                                        }}
                                        className={`flex-1 rounded border px-3 py-1.5 font-pixel text-xs transition ${
                                            paymentDirection === "from_you"
                                                ? "border-red-500/50 bg-red-900/30 text-red-300"
                                                : "border-stone-600/50 bg-stone-900/20 text-stone-400 hover:bg-stone-900/40"
                                        }`}
                                    >
                                        You Pay Them
                                    </button>
                                </div>

                                <div className="space-y-3">
                                    <div className="flex items-center gap-3">
                                        <input
                                            type="range"
                                            min="0"
                                            max={maxGoldDemand}
                                            step="50"
                                            value={goldPayment}
                                            onChange={(e) =>
                                                setGoldPayment(parseInt(e.target.value))
                                            }
                                            className="h-2 flex-1 cursor-pointer appearance-none rounded-lg bg-stone-700"
                                        />
                                    </div>
                                    <div className="flex items-center gap-2">
                                        <input
                                            type="number"
                                            min="0"
                                            max={maxGoldDemand}
                                            value={goldPayment}
                                            onChange={(e) =>
                                                setGoldPayment(
                                                    Math.min(
                                                        maxGoldDemand,
                                                        Math.max(0, parseInt(e.target.value) || 0),
                                                    ),
                                                )
                                            }
                                            className="w-24 rounded border border-stone-600/50 bg-stone-900/50 px-3 py-2 font-pixel text-sm text-white focus:border-amber-500/50 focus:outline-none"
                                        />
                                        <span className="font-pixel text-sm text-amber-400">
                                            gold
                                        </span>
                                    </div>
                                    <div className="font-pixel text-[10px] text-stone-500">
                                        {paymentDirection === "to_you"
                                            ? `Enemy treasury: ~${enemy_gold}g`
                                            : `Your gold: ${player_gold}g`}
                                    </div>
                                </div>
                            </div>
                        )}

                        {/* Truce Duration */}
                        <div className="rounded-xl border-2 border-stone-600/50 bg-stone-800/30 p-4">
                            <h2 className="mb-4 flex items-center gap-2 font-pixel text-lg text-white">
                                <Clock className="h-5 w-5 text-blue-400" />
                                Truce Duration
                            </h2>

                            <div className="grid grid-cols-2 gap-2 sm:grid-cols-3">
                                {truce_options.map((option) => (
                                    <button
                                        key={option.value}
                                        onClick={() => setTruceDays(option.value)}
                                        className={`rounded border px-3 py-2 font-pixel text-xs transition ${
                                            truceDays === option.value
                                                ? "border-blue-500/50 bg-blue-900/30 text-blue-300"
                                                : "border-stone-600/50 bg-stone-900/20 text-stone-400 hover:bg-stone-900/40"
                                        }`}
                                    >
                                        {option.label}
                                    </button>
                                ))}
                            </div>

                            <p className="mt-3 font-pixel text-[10px] text-stone-500">
                                Neither side can declare war on the other during the truce period.
                            </p>
                        </div>

                        {/* Acceptance Likelihood */}
                        <div className="rounded-xl border-2 border-stone-600/50 bg-stone-800/30 p-4">
                            <h2 className="mb-4 flex items-center gap-2 font-pixel text-lg text-white">
                                <Swords className="h-5 w-5 text-stone-400" />
                                Acceptance Likelihood
                            </h2>

                            <div className="mb-3 flex items-center gap-3">
                                <div className="h-4 flex-1 overflow-hidden rounded-full bg-stone-700">
                                    <div
                                        className={`h-full transition-all duration-300 ${
                                            likelihood.percentage >= 65
                                                ? "bg-green-500"
                                                : likelihood.percentage >= 40
                                                  ? "bg-yellow-500"
                                                  : "bg-red-500"
                                        }`}
                                        style={{ width: `${likelihood.percentage}%` }}
                                    />
                                </div>
                                <span
                                    className={`font-pixel text-sm ${
                                        likelihood.percentage >= 65
                                            ? "text-green-400"
                                            : likelihood.percentage >= 40
                                              ? "text-yellow-400"
                                              : "text-red-400"
                                    }`}
                                >
                                    {likelihood.percentage}% ({likelihood.label})
                                </span>
                            </div>

                            <div className="space-y-1">
                                {likelihood.factors.map((factor, index) => (
                                    <div
                                        key={index}
                                        className="font-pixel text-[10px] text-stone-400"
                                    >
                                        {factor}
                                    </div>
                                ))}
                            </div>
                        </div>
                    </div>
                </div>

                {/* Warning & Submit */}
                <div className="rounded-xl border-2 border-amber-500/30 bg-amber-900/20 p-4">
                    <div className="mb-4 flex items-start gap-3">
                        <AlertTriangle className="h-5 w-5 shrink-0 text-amber-400" />
                        <div>
                            <p className="font-pixel text-sm text-amber-300">
                                {treatyType === "surrender"
                                    ? "Warning: You are offering to surrender. This will end the war with you as the loser."
                                    : treatyType === "white_peace"
                                      ? "A white peace will end the war with no victor and no territory changes."
                                      : "This will immediately end the war and apply all negotiated terms."}
                            </p>
                            <p className="mt-1 font-pixel text-xs text-stone-400">
                                A truce of{" "}
                                {truce_options.find((o) => o.value === truceDays)?.label ||
                                    `${truceDays} days`}{" "}
                                will prevent hostilities between both parties.
                            </p>
                        </div>
                    </div>

                    <div className="flex justify-end gap-3">
                        <Link
                            href={`/warfare/wars/${war.id}`}
                            className="rounded border border-stone-600/50 bg-stone-900/20 px-4 py-2 font-pixel text-xs text-stone-300 transition hover:bg-stone-900/40"
                        >
                            Cancel
                        </Link>
                        <button
                            onClick={handleSubmit}
                            disabled={isSubmitting}
                            className={`flex items-center gap-2 rounded border px-4 py-2 font-pixel text-xs transition disabled:cursor-not-allowed disabled:opacity-50 ${
                                treatyType === "surrender"
                                    ? "border-red-600/50 bg-red-900/20 text-red-300 hover:bg-red-900/40"
                                    : "border-green-600/50 bg-green-900/20 text-green-300 hover:bg-green-900/40"
                            }`}
                        >
                            <HandshakeIcon className="h-4 w-4" />
                            {isSubmitting
                                ? "Sending..."
                                : treatyType === "surrender"
                                  ? "Surrender"
                                  : "Send Peace Offer"}
                        </button>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
