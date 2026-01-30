import { Head, router, usePage } from '@inertiajs/react';
import { Coins, Gauge, Heart, ShoppingCart, Sparkles } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

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
    is_stabled: boolean;
    sell_value: number;
}

interface PageProps {
    stock: HorseStock[];
    userHorse: UserHorse | null;
    locationType: string;
    userGold: number;
    [key: string]: unknown;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Stable', href: '/stable' },
];

const rarityColors: Record<string, string> = {
    common: 'border-stone-500/50 bg-stone-900/20 text-stone-400',
    uncommon: 'border-green-500/50 bg-green-900/20 text-green-400',
    rare: 'border-blue-500/50 bg-blue-900/20 text-blue-400',
    epic: 'border-purple-500/50 bg-purple-900/20 text-purple-400',
    legendary: 'border-amber-500/50 bg-amber-900/20 text-amber-400',
};

export default function StableIndex() {
    const { stock, userHorse, locationType, userGold } = usePage<PageProps>().props;
    const [buyingId, setBuyingId] = useState<number | null>(null);
    const [customName, setCustomName] = useState('');
    const [loading, setLoading] = useState(false);

    const handleBuy = (horse: HorseStock) => {
        setLoading(true);
        router.post(
            '/stable/buy',
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
                    setCustomName('');
                },
                onFinish: () => setLoading(false),
            }
        );
    };

    const handleSell = () => {
        if (!confirm('Are you sure you want to sell your horse?')) return;
        setLoading(true);
        router.post(
            '/stable/sell',
            {},
            {
                preserveScroll: true,
                onSuccess: () => {
                    router.reload();
                },
                onFinish: () => setLoading(false),
            }
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Stable" />

            <div className="space-y-6 p-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-3">
                        <div className="rounded-lg bg-amber-900/30 p-2">
                            <Gauge className="size-6 text-amber-400" />
                        </div>
                        <div>
                            <h1 className="font-[Cinzel] text-2xl font-bold text-stone-100">
                                Stable
                            </h1>
                            <p className="text-sm text-stone-400">
                                Buy and manage your horse
                            </p>
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
                        <h3 className="font-[Cinzel] font-semibold text-amber-400">
                            Your Horse
                        </h3>
                        <div className="mt-3 flex items-center justify-between">
                            <div>
                                <p className="text-lg font-semibold text-stone-100">
                                    {userHorse.custom_name || userHorse.horse.name}
                                </p>
                                <p className="text-sm text-stone-400">
                                    {userHorse.horse.breed}
                                </p>
                                <div className="mt-2 flex items-center gap-4 text-sm">
                                    <span className="flex items-center gap-1 text-stone-400">
                                        <Gauge className="size-4 text-blue-400" />
                                        Speed: {userHorse.horse.speed_multiplier}x
                                    </span>
                                    <span className="flex items-center gap-1 text-stone-400">
                                        <Heart className="size-4 text-red-400" />
                                        Stamina: {userHorse.stamina}/{userHorse.max_stamina}
                                    </span>
                                </div>
                                <p className="mt-1 text-xs text-stone-500">
                                    {userHorse.is_stabled ? 'Currently stabled' : 'With you'}
                                </p>
                            </div>
                            <div className="text-right">
                                <p className="text-xs text-stone-500">Sell value</p>
                                <p className="text-amber-400">
                                    {userHorse.sell_value.toLocaleString()} gold
                                </p>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    onClick={handleSell}
                                    disabled={loading}
                                    className="mt-2 border-red-900 text-red-400 hover:bg-red-900/20"
                                >
                                    Sell Horse
                                </Button>
                            </div>
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
                        <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                            {stock.map((horse) => (
                                <div
                                    key={horse.id}
                                    className={`rounded-xl border p-4 ${rarityColors[horse.rarity] || rarityColors.common}`}
                                >
                                    <div className="flex items-start justify-between">
                                        <div>
                                            <h3 className="font-[Cinzel] font-semibold text-stone-100">
                                                {horse.name}
                                            </h3>
                                            <p className="text-sm text-stone-400">{horse.breed}</p>
                                        </div>
                                        <span className="rounded px-2 py-0.5 text-xs capitalize">
                                            {horse.rarity}
                                        </span>
                                    </div>

                                    {horse.description && (
                                        <p className="mt-2 text-sm text-stone-400">
                                            {horse.description}
                                        </p>
                                    )}

                                    <div className="mt-3 grid grid-cols-2 gap-2 text-sm">
                                        <div className="rounded bg-stone-900/50 p-2">
                                            <span className="flex items-center gap-1 text-stone-500">
                                                <Gauge className="size-3" />
                                                Speed
                                            </span>
                                            <span className="text-stone-100">
                                                {horse.speed_multiplier}x
                                            </span>
                                        </div>
                                        <div className="rounded bg-stone-900/50 p-2">
                                            <span className="flex items-center gap-1 text-stone-500">
                                                <Heart className="size-3" />
                                                Stamina
                                            </span>
                                            <span className="text-stone-100">
                                                {horse.max_stamina}
                                            </span>
                                        </div>
                                    </div>

                                    <div className="mt-3 flex items-center justify-between border-t border-stone-800 pt-3">
                                        <span className="flex items-center gap-1 text-lg font-semibold text-amber-400">
                                            <Coins className="size-4" />
                                            {horse.price.toLocaleString()}
                                        </span>

                                        {buyingId === horse.id ? (
                                            <div className="flex items-center gap-2">
                                                <Input
                                                    placeholder="Name (optional)"
                                                    value={customName}
                                                    onChange={(e) => setCustomName(e.target.value)}
                                                    className="h-8 w-32 border-stone-700 bg-stone-900/50 text-sm"
                                                />
                                                <Button
                                                    size="sm"
                                                    onClick={() => handleBuy(horse)}
                                                    disabled={loading || userGold < horse.price || !!userHorse}
                                                >
                                                    Confirm
                                                </Button>
                                                <Button
                                                    size="sm"
                                                    variant="ghost"
                                                    onClick={() => {
                                                        setBuyingId(null);
                                                        setCustomName('');
                                                    }}
                                                >
                                                    Cancel
                                                </Button>
                                            </div>
                                        ) : (
                                            <Button
                                                size="sm"
                                                onClick={() => setBuyingId(horse.id)}
                                                disabled={userGold < horse.price || !!userHorse}
                                            >
                                                <ShoppingCart className="size-4" />
                                                Buy
                                            </Button>
                                        )}
                                    </div>

                                    {userHorse && (
                                        <p className="mt-2 text-xs text-stone-500">
                                            Sell your current horse first
                                        </p>
                                    )}
                                    {userGold < horse.price && !userHorse && (
                                        <p className="mt-2 text-xs text-red-400">
                                            Not enough gold
                                        </p>
                                    )}
                                </div>
                            ))}
                        </div>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
