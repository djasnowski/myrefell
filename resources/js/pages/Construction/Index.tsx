import { Head, router, usePage } from "@inertiajs/react";
import { Coins, Hammer, Loader2, Lock, Zap } from "lucide-react";
import { useEffect, useState } from "react";
import AppLayout from "@/layouts/app-layout";
import { gameToast } from "@/components/ui/game-toast";
import { locationPath } from "@/lib/utils";
import type { BreadcrumbItem } from "@/types";

interface Contract {
    id: string;
    name: string;
    level: number;
    is_unlocked: boolean;
    plank_type: string;
    planks_min: number;
    planks_max: number;
    xp_min: number;
    xp_max: number;
    gold_min: number;
    gold_max: number;
    energy_cost: number;
    player_planks: number;
    has_enough_planks: boolean;
    has_enough_energy: boolean;
}

interface Location {
    type: string;
    id: number;
    name: string;
}

interface PageProps {
    contracts: Contract[];
    constructionLevel: number;
    playerEnergy: number;
    maxEnergy: number;
    location?: Location;
    flash?: { success?: string; error?: string };
    [key: string]: unknown;
}

const tierColors: Record<string, { bg: string; border: string; text: string }> = {
    beginner: {
        bg: "from-stone-800/50 to-stone-900",
        border: "border-stone-600/50",
        text: "text-stone-300",
    },
    apprentice: {
        bg: "from-green-900/40 to-stone-900",
        border: "border-green-600/50",
        text: "text-green-400",
    },
    journeyman: {
        bg: "from-blue-900/40 to-stone-900",
        border: "border-blue-600/50",
        text: "text-blue-400",
    },
    expert: {
        bg: "from-purple-900/40 to-stone-900",
        border: "border-purple-600/50",
        text: "text-purple-400",
    },
    master: {
        bg: "from-amber-900/40 to-stone-900",
        border: "border-amber-500/50",
        text: "text-amber-400",
    },
};

export default function ConstructionIndex() {
    const { contracts, constructionLevel, playerEnergy, maxEnergy, location, flash } =
        usePage<PageProps>().props;
    const [loading, setLoading] = useState<string | null>(null);

    useEffect(() => {
        if (flash?.success) {
            gameToast.success(flash.success);
        }
        if (flash?.error) {
            gameToast.error(flash.error);
        }
    }, [flash]);

    const baseUrl = location ? locationPath(location.type, location.id) : "";

    const breadcrumbs: BreadcrumbItem[] = [
        { title: "Dashboard", href: "/dashboard" },
        ...(location
            ? [{ title: location.name, href: locationPath(location.type, location.id) }]
            : []),
        { title: "Construction", href: "#" },
    ];

    const handleContract = (tierId: string) => {
        setLoading(tierId);
        router.post(
            `${baseUrl}/construction/contract`,
            { tier: tierId },
            {
                preserveScroll: true,
                onSuccess: () => {
                    router.reload();
                },
                onFinish: () => setLoading(null),
            },
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Construction" />

            <div className="mx-auto max-w-4xl p-4">
                <div className="mb-6 flex items-center gap-3">
                    <div className="rounded-lg bg-orange-900/50 p-2">
                        <Hammer className="h-6 w-6 text-orange-400" />
                    </div>
                    <div>
                        <h1 className="font-pixel text-xl text-orange-100">
                            Construction Contracts
                        </h1>
                        <p className="font-pixel text-xs text-stone-400">
                            Take on building contracts to train Construction (Level{" "}
                            {constructionLevel})
                        </p>
                    </div>
                    <div className="ml-auto flex items-center gap-1.5 rounded-md bg-emerald-900/30 px-3 py-1.5">
                        <Zap className="h-4 w-4 text-emerald-400" />
                        <span className="font-pixel text-sm text-emerald-300">
                            {playerEnergy}/{maxEnergy}
                        </span>
                    </div>
                </div>

                <div className="grid gap-3">
                    {contracts.map((contract) => {
                        const colors = tierColors[contract.id] || tierColors.beginner;
                        const isLoading = loading === contract.id;
                        const canDo =
                            contract.is_unlocked &&
                            contract.has_enough_planks &&
                            contract.has_enough_energy;

                        return (
                            <div
                                key={contract.id}
                                className={`rounded-lg border bg-gradient-to-br p-4 ${colors.border} ${colors.bg} ${!contract.is_unlocked ? "opacity-60" : ""}`}
                            >
                                <div className="flex flex-wrap items-center gap-4">
                                    <div className="min-w-0 flex-1">
                                        <div className="flex items-center gap-2">
                                            <span className={`font-pixel text-sm ${colors.text}`}>
                                                {contract.name}
                                            </span>
                                            {!contract.is_unlocked && (
                                                <span className="flex items-center gap-1 font-pixel text-[10px] text-stone-500">
                                                    <Lock className="h-3 w-3" />
                                                    Level {contract.level}
                                                </span>
                                            )}
                                        </div>
                                        <div className="mt-1 flex flex-wrap gap-3 font-pixel text-[10px] text-stone-400">
                                            <span
                                                className={
                                                    contract.has_enough_planks
                                                        ? "text-stone-400"
                                                        : "text-red-400"
                                                }
                                            >
                                                {contract.planks_min}-{contract.planks_max}{" "}
                                                {contract.plank_type} (have {contract.player_planks}
                                                )
                                            </span>
                                            <span className="text-amber-400/70">
                                                +{contract.xp_min}-{contract.xp_max} XP
                                            </span>
                                            <span className="text-yellow-400/70">
                                                +{contract.gold_min}-{contract.gold_max} gold
                                            </span>
                                            <span
                                                className={
                                                    contract.has_enough_energy
                                                        ? "text-emerald-400/70"
                                                        : "text-red-400"
                                                }
                                            >
                                                <Zap className="mr-0.5 inline h-3 w-3" />
                                                {contract.energy_cost}
                                            </span>
                                        </div>
                                    </div>

                                    <button
                                        onClick={() => handleContract(contract.id)}
                                        disabled={!canDo || isLoading}
                                        className={`rounded-md border px-4 py-2 font-pixel text-xs transition-colors disabled:cursor-not-allowed disabled:opacity-40 ${colors.border} ${colors.text} hover:bg-white/5`}
                                    >
                                        {isLoading ? (
                                            <Loader2 className="h-4 w-4 animate-spin" />
                                        ) : (
                                            "Take Contract"
                                        )}
                                    </button>
                                </div>
                            </div>
                        );
                    })}
                </div>
            </div>
        </AppLayout>
    );
}
