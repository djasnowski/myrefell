import { Head, router, usePage } from "@inertiajs/react";
import {
    Bed,
    Coins,
    Gauge,
    Heart,
    Home,
    ShoppingCart,
    Sparkles,
    User,
    Utensils,
    Zap,
} from "lucide-react";
import { useState } from "react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import AppLayout from "@/layouts/app-layout";
import type { BreadcrumbItem } from "@/types";

interface HorseStock {
    id: number;
    name: string;
    description: string | null;
    breed: string;
    speed_multiplier: number;
    stamina: number;
    max_stamina: number;
    price: number;
    rarity: string;
}

interface UserHorse {
    id: number;
    custom_name: string | null;
    horse: {
        name: string;
        breed: string;
        speed_multiplier: number;
    };
    stamina: number;
    max_stamina: number;
    is_active?: boolean;
    is_stabled: boolean;
    stabled_location_type: string | null;
    stabled_location_id: number | null;
    sell_value: number;
}

interface PlayerHorseData {
    id: number;
    name: string;
    type: string;
    speed_multiplier: number;
    stamina: number;
    max_stamina: number;
    is_active: boolean;
    is_stabled: boolean;
    stabled_location_type: string | null;
    stabled_location_id: number | null;
    sell_price: number;
}

interface StabledHorse {
    id: number;
    name: string;
    type: string;
    speed_multiplier: number;
    stamina: number;
    max_stamina: number;
    owner_id: number;
    owner_name: string;
}

interface LocationContext {
    type: string;
    id: number;
    name: string;
}

interface PageProps {
    stock: HorseStock[];
    userHorse: UserHorse | null;
    userHorses: PlayerHorseData[];
    stabledHorses: StabledHorse[];
    isStablemaster: boolean;
    maxHorses: number;
    horseCount: number;
    locationType: string;
    userGold: number;
    location?: LocationContext;
    [key: string]: unknown;
}

const rarityConfig: Record<
    string,
    { border: string; bg: string; badge: string; glow: string; icon: string }
> = {
    common: {
        border: "border-stone-600/50",
        bg: "bg-gradient-to-br from-stone-900/80 to-stone-950/90",
        badge: "bg-stone-700 text-stone-300",
        glow: "",
        icon: "text-stone-500",
    },
    uncommon: {
        border: "border-green-600/50",
        bg: "bg-gradient-to-br from-green-950/40 to-stone-950/90",
        badge: "bg-green-900/80 text-green-300",
        glow: "shadow-green-900/20 shadow-lg",
        icon: "text-green-500",
    },
    rare: {
        border: "border-blue-500/50",
        bg: "bg-gradient-to-br from-blue-950/40 to-stone-950/90",
        badge: "bg-blue-900/80 text-blue-300",
        glow: "shadow-blue-900/30 shadow-lg",
        icon: "text-blue-400",
    },
    epic: {
        border: "border-purple-500/50",
        bg: "bg-gradient-to-br from-purple-950/40 to-stone-950/90",
        badge: "bg-purple-900/80 text-purple-300",
        glow: "shadow-purple-900/40 shadow-xl",
        icon: "text-purple-400",
    },
    legendary: {
        border: "border-amber-400/60",
        bg: "bg-gradient-to-br from-amber-950/50 to-stone-950/90",
        badge: "bg-amber-900/80 text-amber-300",
        glow: "shadow-amber-900/50 shadow-xl",
        icon: "text-amber-400",
    },
};

const locationTypeToPlural: Record<string, string> = {
    village: "villages",
    town: "towns",
    barony: "baronies",
    duchy: "duchies",
    kingdom: "kingdoms",
};

function getBreadcrumbs(location?: LocationContext): BreadcrumbItem[] {
    const crumbs: BreadcrumbItem[] = [{ title: "Dashboard", href: "/dashboard" }];

    if (location) {
        const plural = locationTypeToPlural[location.type] || `${location.type}s`;
        crumbs.push({
            title: location.name,
            href: `/${plural}/${location.id}`,
        });
        crumbs.push({
            title: "Stables",
            href: `/${plural}/${location.id}/stables`,
        });
    } else {
        crumbs.push({ title: "Stables", href: "/stable" });
    }

    return crumbs;
}

export default function StableIndex() {
    const {
        stock,
        userHorse,
        userHorses,
        stabledHorses,
        isStablemaster,
        maxHorses,
        horseCount,
        userGold,
        location,
    } = usePage<PageProps>().props;
    const [buyingId, setBuyingId] = useState<number | null>(null);
    const [customName, setCustomName] = useState("");
    const [loading, setLoading] = useState(false);

    const breadcrumbs = getBreadcrumbs(location);
    const canBuyMore = horseCount < maxHorses;

    const handleBuy = (horse: HorseStock) => {
        setLoading(true);
        router.post(
            "/stable/buy",
            {
                horse_id: horse.id,
                price: horse.price,
                custom_name: customName || null,
            },
            {
                preserveScroll: true,
                onSuccess: () => {
                    router.reload();
                    setBuyingId(null);
                    setCustomName("");
                },
                onFinish: () => setLoading(false),
            },
        );
    };

    const handleSell = () => {
        if (!confirm("Are you sure you want to sell your horse?")) return;
        setLoading(true);
        router.post(
            "/stable/sell",
            {},
            {
                preserveScroll: true,
                onSuccess: () => {
                    router.reload();
                },
                onFinish: () => setLoading(false),
            },
        );
    };

    const handleStable = () => {
        setLoading(true);
        router.post(
            "/stable/stable",
            {},
            {
                preserveScroll: true,
                onSuccess: () => {
                    router.reload();
                },
                onFinish: () => setLoading(false),
            },
        );
    };

    const handleRetrieve = () => {
        setLoading(true);
        router.post(
            "/stable/retrieve",
            {},
            {
                preserveScroll: true,
                onSuccess: () => {
                    router.reload();
                },
                onFinish: () => setLoading(false),
            },
        );
    };

    const handleRest = (playerHorseId?: number) => {
        setLoading(true);
        router.post(
            "/stable/rest",
            { player_horse_id: playerHorseId },
            {
                preserveScroll: true,
                onSuccess: () => {
                    router.reload({
                        only: ["userHorse", "userHorses", "stabledHorses", "userGold"],
                    });
                },
                onFinish: () => setLoading(false),
            },
        );
    };

    const handleFeedAll = () => {
        setLoading(true);
        router.post(
            "/stable/feed",
            {},
            {
                preserveScroll: true,
                onSuccess: () => {
                    router.reload({ only: ["stabledHorses"] });
                },
                onFinish: () => setLoading(false),
            },
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={location ? `Stables - ${location.name}` : "Stables"} />

            <div className="space-y-6 p-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-3">
                        <div className="rounded-lg bg-amber-900/30 p-2">
                            <Gauge className="size-6 text-amber-400" />
                        </div>
                        <div>
                            <h1 className="font-[Cinzel] text-2xl font-bold text-stone-100">
                                {location ? `${location.name} Stables` : "Stables"}
                            </h1>
                            <p className="text-sm text-stone-400">Buy and manage your horse</p>
                        </div>
                    </div>
                    <div className="flex items-center gap-2 text-amber-400">
                        <Coins className="size-5" />
                        <span className="font-semibold">{userGold.toLocaleString()}</span>
                    </div>
                </div>

                {/* Your Horse */}
                {userHorse && (
                    <div className="rounded-xl border border-amber-900/50 bg-amber-900/20 p-4">
                        <h3 className="font-[Cinzel] font-semibold text-amber-400">Your Horse</h3>
                        <div className="mt-3 flex items-center justify-between">
                            <div>
                                <p className="text-xl font-semibold text-stone-100">
                                    {userHorse.custom_name || userHorse.horse.name}
                                </p>
                                <p className="text-sm text-stone-400">{userHorse.horse.breed}</p>

                                {/* Stats - Bigger and with color coding */}
                                <div className="mt-3 flex items-center gap-6">
                                    <div className="flex items-center gap-2">
                                        <Zap className="size-6 text-blue-400" />
                                        <div>
                                            <div className="text-xs text-stone-500">Speed</div>
                                            <div className="font-[Cinzel] text-2xl font-bold text-blue-400">
                                                {userHorse.horse.speed_multiplier}x
                                            </div>
                                        </div>
                                    </div>
                                    <div className="flex items-center gap-2">
                                        <Heart
                                            className={`size-6 ${
                                                userHorse.stamina / userHorse.max_stamina <= 0.1
                                                    ? "text-red-500 animate-pulse"
                                                    : userHorse.stamina / userHorse.max_stamina <=
                                                        0.25
                                                      ? "text-red-400"
                                                      : userHorse.stamina / userHorse.max_stamina <=
                                                          0.5
                                                        ? "text-yellow-400"
                                                        : "text-green-400"
                                            }`}
                                        />
                                        <div>
                                            <div className="text-xs text-stone-500">Stamina</div>
                                            <div
                                                className={`font-[Cinzel] text-2xl font-bold ${
                                                    userHorse.stamina / userHorse.max_stamina <= 0.1
                                                        ? "text-red-500"
                                                        : userHorse.stamina /
                                                                userHorse.max_stamina <=
                                                            0.25
                                                          ? "text-red-400"
                                                          : userHorse.stamina /
                                                                  userHorse.max_stamina <=
                                                              0.5
                                                            ? "text-yellow-400"
                                                            : "text-green-400"
                                                }`}
                                            >
                                                {userHorse.stamina}/{userHorse.max_stamina}
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {/* Low stamina warning */}
                                {userHorse.stamina / userHorse.max_stamina <= 0.1 && (
                                    <div className="mt-3 rounded-lg border border-red-600/50 bg-red-900/30 px-3 py-2 text-sm text-red-300">
                                        <strong>Warning:</strong> Your horse is exhausted and cannot
                                        travel! Rest at a stable to restore stamina.
                                    </div>
                                )}
                                {userHorse.stamina / userHorse.max_stamina > 0.1 &&
                                    userHorse.stamina / userHorse.max_stamina <= 0.25 && (
                                        <div className="mt-3 rounded-lg border border-yellow-600/50 bg-yellow-900/30 px-3 py-2 text-sm text-yellow-300">
                                            Your horse is tired. Consider resting soon.
                                        </div>
                                    )}

                                <p className="mt-2 text-xs text-stone-500">
                                    {userHorse.is_stabled ? "Currently stabled" : "With you"}
                                </p>
                            </div>
                            <div className="flex flex-col items-end gap-2">
                                {/* Stable/Retrieve buttons */}
                                {userHorse.is_stabled ? (
                                    <Button
                                        size="sm"
                                        onClick={handleRetrieve}
                                        disabled={loading}
                                        className="bg-blue-600 hover:bg-blue-500"
                                    >
                                        <Home className="size-4" />
                                        Retrieve Horse
                                    </Button>
                                ) : (
                                    <Button
                                        size="sm"
                                        onClick={handleStable}
                                        disabled={loading}
                                        className="bg-stone-600 hover:bg-stone-500"
                                    >
                                        <Home className="size-4" />
                                        Stable Horse
                                    </Button>
                                )}

                                {/* Rest button - available at any stable */}
                                {userHorse.stamina < userHorse.max_stamina && (
                                    <Button
                                        size="sm"
                                        onClick={() => handleRest(userHorse.id)}
                                        disabled={loading || userGold < 50}
                                        className="bg-green-600 hover:bg-green-500"
                                    >
                                        <Bed className="size-4" />
                                        Rest Horse (50g)
                                    </Button>
                                )}

                                <div className="text-right">
                                    <p className="text-xs text-stone-500">
                                        Sell value: {userHorse.sell_value.toLocaleString()}g
                                    </p>
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        onClick={handleSell}
                                        disabled={loading}
                                        className="mt-1 border-red-900 text-red-400 hover:bg-red-900/20"
                                    >
                                        Sell Horse
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </div>
                )}

                {/* Horses Stabled Here */}
                {stabledHorses.length > 0 && (
                    <div className="rounded-xl border border-stone-700/50 bg-stone-800/30 p-4">
                        <div className="mb-3 flex items-center justify-between">
                            <h3 className="font-[Cinzel] font-semibold text-stone-300">
                                Horses Stabled Here ({stabledHorses.length})
                            </h3>
                            {isStablemaster && (
                                <Button
                                    size="sm"
                                    onClick={handleFeedAll}
                                    disabled={loading}
                                    className="bg-orange-600 hover:bg-orange-500"
                                >
                                    <Utensils className="size-4" />
                                    Feed All Horses
                                </Button>
                            )}
                        </div>
                        <div className="space-y-2">
                            {stabledHorses.map((horse) => {
                                const staminaRatio = horse.stamina / horse.max_stamina;
                                const staminaColor =
                                    staminaRatio <= 0.1
                                        ? "text-red-500"
                                        : staminaRatio <= 0.25
                                          ? "text-red-400"
                                          : staminaRatio <= 0.5
                                            ? "text-yellow-400"
                                            : "text-green-400";

                                return (
                                    <div
                                        key={horse.id}
                                        className="flex items-center justify-between rounded-lg border border-stone-700/30 bg-stone-900/50 p-3"
                                    >
                                        <div className="flex items-center gap-3">
                                            <div className="rounded-lg bg-amber-900/30 p-2">
                                                <Gauge className="size-5 text-amber-400" />
                                            </div>
                                            <div>
                                                <p className="font-semibold text-stone-100">
                                                    {horse.name}
                                                </p>
                                                <div className="flex items-center gap-3 text-sm">
                                                    <span className="flex items-center gap-1 text-stone-400">
                                                        <User className="size-3" />
                                                        {horse.owner_name}
                                                    </span>
                                                    <span className="flex items-center gap-1">
                                                        <Zap className="size-3 text-blue-400" />
                                                        <span className="text-blue-400">
                                                            {horse.speed_multiplier}x
                                                        </span>
                                                    </span>
                                                    <span className="flex items-center gap-1">
                                                        <Heart
                                                            className={`size-3 ${staminaColor} ${staminaRatio <= 0.1 ? "animate-pulse" : ""}`}
                                                        />
                                                        <span className={staminaColor}>
                                                            {horse.stamina}/{horse.max_stamina}
                                                        </span>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                );
                            })}
                        </div>
                    </div>
                )}

                {/* Horses for Sale */}
                <div>
                    <h2 className="mb-4 font-[Cinzel] text-lg font-semibold text-stone-100">
                        Horses for Sale
                    </h2>

                    {stock.length === 0 ? (
                        <div className="rounded-xl border border-stone-800 bg-stone-900/50 p-8 text-center">
                            <Gauge className="mx-auto size-12 text-stone-600" />
                            <p className="mt-4 text-stone-400">
                                No horses available at this location
                            </p>
                        </div>
                    ) : (
                        <div className="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                            {stock.map((horse) => {
                                const rarity = rarityConfig[horse.rarity] || rarityConfig.common;
                                const canAfford = userGold >= horse.price;
                                const canBuy = canAfford && canBuyMore;

                                return (
                                    <div
                                        key={horse.id}
                                        className={`group relative overflow-hidden rounded-xl border-2 ${rarity.border} ${rarity.bg} ${rarity.glow} transition-all duration-300 hover:scale-[1.02]`}
                                    >
                                        {/* Rarity Badge */}
                                        <div className="absolute right-3 top-3">
                                            <span
                                                className={`flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-medium capitalize ${rarity.badge}`}
                                            >
                                                {horse.rarity === "legendary" && (
                                                    <Sparkles className="size-3" />
                                                )}
                                                {horse.rarity}
                                            </span>
                                        </div>

                                        {/* Horse Icon & Name */}
                                        <div className="p-5 pb-3">
                                            <div className="flex items-start gap-3">
                                                <div
                                                    className={`rounded-lg bg-stone-900/60 p-2.5 ${rarity.icon}`}
                                                >
                                                    <Gauge className="size-6" />
                                                </div>
                                                <div className="flex-1 pt-0.5">
                                                    <h3 className="font-[Cinzel] text-lg font-bold text-stone-100">
                                                        {horse.name}
                                                    </h3>
                                                    <p className="text-sm text-stone-500">
                                                        {horse.breed}
                                                    </p>
                                                </div>
                                            </div>

                                            {horse.description && (
                                                <p className="mt-3 text-sm leading-relaxed text-stone-400">
                                                    {horse.description}
                                                </p>
                                            )}
                                        </div>

                                        {/* Stats */}
                                        <div className="mx-5 grid grid-cols-2 gap-3">
                                            <div className="rounded-lg bg-stone-900/60 p-3">
                                                <div className="flex items-center gap-1.5 text-xs font-medium text-stone-500">
                                                    <Zap className="size-3.5 text-blue-400" />
                                                    Speed
                                                </div>
                                                <div className="mt-1 font-[Cinzel] text-xl font-bold text-stone-100">
                                                    {horse.speed_multiplier}x
                                                </div>
                                                <div className="text-xs text-stone-600">
                                                    travel speed
                                                </div>
                                            </div>
                                            <div className="rounded-lg bg-stone-900/60 p-3">
                                                <div className="flex items-center gap-1.5 text-xs font-medium text-stone-500">
                                                    <Heart className="size-3.5 text-red-400" />
                                                    Stamina
                                                </div>
                                                <div className="mt-1 font-[Cinzel] text-xl font-bold text-stone-100">
                                                    {horse.max_stamina}
                                                </div>
                                                <div className="text-xs text-stone-600">
                                                    max endurance
                                                </div>
                                            </div>
                                        </div>

                                        {/* Price & Buy */}
                                        <div className="mt-4 border-t border-stone-800/50 bg-stone-950/40 p-4">
                                            {buyingId === horse.id ? (
                                                <div className="space-y-3">
                                                    <Input
                                                        placeholder="Give your horse a name (optional)"
                                                        value={customName}
                                                        onChange={(e) =>
                                                            setCustomName(e.target.value)
                                                        }
                                                        className="border-stone-700 bg-stone-900/80 text-sm"
                                                    />
                                                    <div className="flex gap-2">
                                                        <Button
                                                            className="flex-1"
                                                            onClick={() => handleBuy(horse)}
                                                            disabled={loading || !canBuy}
                                                        >
                                                            <Coins className="size-4" />
                                                            Purchase for{" "}
                                                            {horse.price.toLocaleString()}g
                                                        </Button>
                                                        <Button
                                                            variant="ghost"
                                                            onClick={() => {
                                                                setBuyingId(null);
                                                                setCustomName("");
                                                            }}
                                                        >
                                                            Cancel
                                                        </Button>
                                                    </div>
                                                </div>
                                            ) : (
                                                <div className="flex items-center justify-between">
                                                    <div>
                                                        <div className="flex items-center gap-1.5 text-2xl font-bold text-amber-400">
                                                            <Coins className="size-5" />
                                                            {horse.price.toLocaleString()}
                                                        </div>
                                                        {!canAfford && (
                                                            <p className="mt-0.5 text-xs text-red-400">
                                                                Not enough gold
                                                            </p>
                                                        )}
                                                        {!canBuyMore && canAfford && (
                                                            <p className="mt-0.5 text-xs text-stone-500">
                                                                Stable full ({horseCount}/
                                                                {maxHorses})
                                                            </p>
                                                        )}
                                                    </div>
                                                    <Button
                                                        onClick={() => setBuyingId(horse.id)}
                                                        disabled={!canBuy}
                                                        className={
                                                            canBuy
                                                                ? "bg-amber-600 hover:bg-amber-500"
                                                                : ""
                                                        }
                                                    >
                                                        <ShoppingCart className="size-4" />
                                                        Buy
                                                    </Button>
                                                </div>
                                            )}
                                        </div>
                                    </div>
                                );
                            })}
                        </div>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
