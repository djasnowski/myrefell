import { Head, router, usePage } from "@inertiajs/react";
import { Axe, Coins, Loader2, TreePine } from "lucide-react";
import { useEffect, useState } from "react";
import AppLayout from "@/layouts/app-layout";
import { gameToast } from "@/components/ui/game-toast";
import { locationPath } from "@/lib/utils";
import type { BreadcrumbItem } from "@/types";

interface Recipe {
    plank_name: string;
    log_name: string;
    fee: number;
    player_logs: number;
    plank_value: number;
}

interface Location {
    type: string;
    id: number;
    name: string;
}

interface PageProps {
    recipes: Recipe[];
    playerGold: number;
    location?: Location;
    flash?: { success?: string; error?: string };
    [key: string]: unknown;
}

export default function SawmillIndex() {
    const { recipes, playerGold, location, flash } = usePage<PageProps>().props;
    const [quantities, setQuantities] = useState<Record<string, number>>({});
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
        { title: "Sawmill", href: "#" },
    ];

    const handleConvert = (plankName: string) => {
        const qty = quantities[plankName] || 1;
        setLoading(plankName);

        router.post(
            `${baseUrl}/sawmill/convert`,
            { plank_name: plankName, quantity: qty },
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
            <Head title="Sawmill" />

            <div className="mx-auto max-w-4xl p-4">
                <div className="mb-6 flex items-center gap-3">
                    <div className="rounded-lg bg-amber-900/50 p-2">
                        <Axe className="h-6 w-6 text-amber-400" />
                    </div>
                    <div>
                        <h1 className="font-pixel text-xl text-amber-100">Sawmill</h1>
                        <p className="font-pixel text-xs text-stone-400">
                            Convert logs into planks for a fee
                        </p>
                    </div>
                    <div className="ml-auto flex items-center gap-1.5 rounded-md bg-yellow-900/30 px-3 py-1.5">
                        <Coins className="h-4 w-4 text-yellow-400" />
                        <span className="font-pixel text-sm text-yellow-300">
                            {playerGold.toLocaleString()}
                        </span>
                    </div>
                </div>

                <div className="grid gap-3">
                    {recipes.map((recipe) => {
                        const qty = quantities[recipe.plank_name] || 1;
                        const totalFee = recipe.fee * qty;
                        const canAfford = playerGold >= totalFee;
                        const hasLogs = recipe.player_logs >= qty;
                        const isLoading = loading === recipe.plank_name;

                        return (
                            <div
                                key={recipe.plank_name}
                                className="rounded-lg border border-stone-700/50 bg-gradient-to-br from-stone-800/50 to-stone-900 p-4"
                            >
                                <div className="flex flex-wrap items-center gap-4">
                                    <div className="flex items-center gap-3">
                                        <TreePine className="h-5 w-5 text-green-400" />
                                        <div>
                                            <div className="font-pixel text-sm text-stone-200">
                                                {recipe.log_name}
                                            </div>
                                            <div className="font-pixel text-[10px] text-stone-500">
                                                You have: {recipe.player_logs}
                                            </div>
                                        </div>
                                    </div>

                                    <div className="font-pixel text-xs text-stone-500">&rarr;</div>

                                    <div>
                                        <div className="font-pixel text-sm text-amber-300">
                                            {recipe.plank_name}
                                        </div>
                                        <div className="font-pixel text-[10px] text-stone-500">
                                            Value: {recipe.plank_value}g each
                                        </div>
                                    </div>

                                    <div className="ml-auto flex items-center gap-3">
                                        <div className="flex items-center gap-1.5">
                                            <Coins className="h-3.5 w-3.5 text-yellow-400" />
                                            <span className="font-pixel text-xs text-yellow-300">
                                                {recipe.fee}g/ea
                                            </span>
                                        </div>

                                        <input
                                            type="number"
                                            min={1}
                                            max={recipe.player_logs}
                                            value={qty}
                                            onChange={(e) =>
                                                setQuantities((prev) => ({
                                                    ...prev,
                                                    [recipe.plank_name]: Math.max(
                                                        1,
                                                        parseInt(e.target.value) || 1,
                                                    ),
                                                }))
                                            }
                                            className="w-16 rounded border border-stone-600 bg-stone-800 px-2 py-1 font-pixel text-xs text-stone-200 focus:border-amber-500 focus:outline-none"
                                        />

                                        <button
                                            onClick={() => handleConvert(recipe.plank_name)}
                                            disabled={
                                                isLoading ||
                                                !canAfford ||
                                                !hasLogs ||
                                                recipe.player_logs === 0
                                            }
                                            className="rounded-md border border-amber-600/50 bg-amber-900/50 px-3 py-1.5 font-pixel text-xs text-amber-300 transition-colors hover:bg-amber-800/50 disabled:cursor-not-allowed disabled:opacity-40"
                                        >
                                            {isLoading ? (
                                                <Loader2 className="h-3.5 w-3.5 animate-spin" />
                                            ) : (
                                                `Convert (${totalFee.toLocaleString()}g)`
                                            )}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        );
                    })}
                </div>
            </div>
        </AppLayout>
    );
}
