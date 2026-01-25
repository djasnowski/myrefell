import { Head, Link, router } from '@inertiajs/react';
import { Home, Loader2, MapPin } from 'lucide-react';
import { useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { RulerDisplay } from '@/components/ui/legitimacy-badge';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

interface Resident {
    id: number;
    username: string;
    combat_level: number;
}

interface Ruler {
    id: number;
    username: string;
    primary_title?: string | null;
    legitimacy?: number;
}

interface Village {
    id: number;
    name: string;
    description: string;
    biome: string;
    is_town: boolean;
    population: number;
    wealth: number;
    coordinates: {
        x: number;
        y: number;
    };
    barony: {
        id: number;
        name: string;
        biome: string;
    } | null;
    kingdom: {
        id: number;
        name: string;
    } | null;
    residents: Resident[];
    resident_count: number;
    elder?: Ruler | null;
}

interface Props {
    village: Village;
    is_resident: boolean;
    can_migrate: boolean;
    has_pending_request: boolean;
    current_user_id: number;
}

const biomeColors: Record<string, string> = {
    plains: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
    forest: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-200',
    tundra: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
    coastal: 'bg-cyan-100 text-cyan-800 dark:bg-cyan-900 dark:text-cyan-200',
    desert: 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200',
    volcano: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
    mountains: 'bg-slate-100 text-slate-800 dark:bg-slate-900 dark:text-slate-200',
    swamps: 'bg-lime-100 text-lime-800 dark:bg-lime-900 dark:text-lime-200',
};

export default function VillageShow({ village, is_resident, can_migrate, has_pending_request, current_user_id }: Props) {
    const [loading, setLoading] = useState(false);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Villages', href: '/villages' },
        { title: village.name, href: `/villages/${village.id}` },
    ];

    const handleRequestMigration = () => {
        setLoading(true);
        router.post(`/migration/request/${village.id}`, {}, {
            onFinish: () => setLoading(false),
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={village.name} />
            <div className="flex flex-col gap-6 p-6">
                <div className="flex items-start justify-between">
                    <div>
                        <div className="flex items-center gap-3">
                            <h1 className="text-2xl font-bold">
                                {village.name}
                                {village.is_town && (
                                    <span className="ml-2 text-sm text-purple-600 dark:text-purple-400">
                                        (Town)
                                    </span>
                                )}
                            </h1>
                            <Badge className={biomeColors[village.biome] || ''}>
                                {village.biome}
                            </Badge>
                        </div>
                        <p className="text-muted-foreground mt-1">{village.description}</p>
                    </div>
                </div>

                <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-5">
                    <Card>
                        <CardHeader className="pb-2">
                            <CardDescription>Kingdom</CardDescription>
                            <CardTitle className="text-lg">
                                {village.kingdom ? (
                                    <Link href={`/kingdoms/${village.kingdom.id}`} className="hover:underline">
                                        {village.kingdom.name}
                                    </Link>
                                ) : (
                                    'None'
                                )}
                            </CardTitle>
                        </CardHeader>
                    </Card>
                    <Card>
                        <CardHeader className="pb-2">
                            <CardDescription>Barony</CardDescription>
                            <CardTitle className="text-lg">
                                {village.barony ? (
                                    <Link href={`/baronies/${village.barony.id}`} className="hover:underline">
                                        {village.barony.name}
                                    </Link>
                                ) : (
                                    'None'
                                )}
                            </CardTitle>
                        </CardHeader>
                    </Card>
                    <Card>
                        <CardHeader className="pb-2">
                            <CardDescription>Population</CardDescription>
                            <CardTitle className="text-lg">{village.population.toLocaleString()}</CardTitle>
                        </CardHeader>
                    </Card>
                    <Card>
                        <CardHeader className="pb-2">
                            <CardDescription>Wealth</CardDescription>
                            <CardTitle className="text-lg">{village.wealth.toLocaleString()} gold</CardTitle>
                        </CardHeader>
                    </Card>
                    <Card>
                        <CardHeader className="pb-2">
                            <CardDescription>Coordinates</CardDescription>
                            <CardTitle className="text-lg">({village.coordinates.x}, {village.coordinates.y})</CardTitle>
                        </CardHeader>
                    </Card>
                </div>

                {/* Village Elder / Ruler */}
                <RulerDisplay
                    ruler={village.elder}
                    title="Village Elder"
                    isCurrentUser={village.elder?.id === current_user_id}
                />

                <div className="flex gap-4">
                    <Link
                        href={`/villages/${village.id}/roles`}
                        className="flex-1 rounded-lg border-2 border-amber-600/50 bg-amber-900/20 p-4 text-center transition hover:bg-amber-800/30"
                    >
                        <span className="font-pixel text-lg text-amber-300">View Roles</span>
                        <p className="text-sm text-stone-400">See village officials and positions</p>
                    </Link>
                </div>

                {/* Migration / Settlement */}
                {is_resident ? (
                    <div className="flex items-center gap-2 rounded-lg border-2 border-green-600/50 bg-green-900/20 p-4">
                        <Home className="h-5 w-5 text-green-400" />
                        <span className="font-pixel text-green-300">This is your home village</span>
                    </div>
                ) : has_pending_request ? (
                    <div className="rounded-lg border-2 border-amber-600/50 bg-amber-900/20 p-4">
                        <p className="font-pixel text-amber-300">You have a pending migration request</p>
                        <Link href="/migration" className="text-sm text-stone-400 hover:underline">
                            View your request
                        </Link>
                    </div>
                ) : can_migrate ? (
                    <button
                        onClick={handleRequestMigration}
                        disabled={loading}
                        className="flex w-full items-center justify-center gap-2 rounded-lg border-2 border-blue-600/50 bg-blue-900/20 p-4 transition hover:bg-blue-800/30 disabled:opacity-50"
                    >
                        {loading ? (
                            <Loader2 className="h-5 w-5 animate-spin text-blue-300" />
                        ) : (
                            <MapPin className="h-5 w-5 text-blue-300" />
                        )}
                        <span className="font-pixel text-lg text-blue-300">Request to Settle Here</span>
                    </button>
                ) : (
                    <div className="rounded-lg border-2 border-stone-600/50 bg-stone-800/50 p-4">
                        <p className="font-pixel text-stone-400">Migration on cooldown</p>
                        <p className="text-sm text-stone-500">You must wait before you can move again</p>
                    </div>
                )}

                <div>
                    <h2 className="text-xl font-semibold mb-4">
                        Residents ({village.resident_count})
                    </h2>
                    {village.residents.length > 0 ? (
                        <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                            {village.residents.map((resident) => (
                                <Card key={resident.id}>
                                    <CardHeader>
                                        <CardTitle className="text-base">{resident.username}</CardTitle>
                                        <CardDescription>
                                            Combat Level: {resident.combat_level}
                                        </CardDescription>
                                    </CardHeader>
                                </Card>
                            ))}
                        </div>
                    ) : (
                        <Card>
                            <CardContent className="py-8 text-center text-muted-foreground">
                                No adventurers have settled in this village yet.
                            </CardContent>
                        </Card>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
