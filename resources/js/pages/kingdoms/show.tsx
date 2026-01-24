import { Head, Link } from '@inertiajs/react';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

interface Castle {
    id: number;
    name: string;
    biome: string;
    is_capital: boolean;
    village_count: number;
}

interface Kingdom {
    id: number;
    name: string;
    description: string;
    biome: string;
    tax_rate: number;
    coordinates: {
        x: number;
        y: number;
    };
    capital: {
        id: number;
        name: string;
        biome: string;
    } | null;
    castles: Castle[];
    castle_count: number;
    total_villages: number;
}

interface Props {
    kingdom: Kingdom;
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

export default function KingdomShow({ kingdom }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Kingdoms', href: '/kingdoms' },
        { title: kingdom.name, href: `/kingdoms/${kingdom.id}` },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={kingdom.name} />
            <div className="flex flex-col gap-6 p-6">
                <div className="flex items-start justify-between">
                    <div>
                        <div className="flex items-center gap-3">
                            <h1 className="text-2xl font-bold">{kingdom.name}</h1>
                            <Badge className={biomeColors[kingdom.biome] || ''}>
                                {kingdom.biome}
                            </Badge>
                        </div>
                        <p className="text-muted-foreground mt-1">{kingdom.description}</p>
                    </div>
                </div>

                <div className="grid gap-4 md:grid-cols-4">
                    <Card>
                        <CardHeader className="pb-2">
                            <CardDescription>Capital</CardDescription>
                            <CardTitle className="text-lg">
                                {kingdom.capital ? (
                                    <Link href={`/castles/${kingdom.capital.id}`} className="hover:underline">
                                        {kingdom.capital.name}
                                    </Link>
                                ) : (
                                    'None'
                                )}
                            </CardTitle>
                        </CardHeader>
                    </Card>
                    <Card>
                        <CardHeader className="pb-2">
                            <CardDescription>Castles</CardDescription>
                            <CardTitle className="text-lg">{kingdom.castle_count}</CardTitle>
                        </CardHeader>
                    </Card>
                    <Card>
                        <CardHeader className="pb-2">
                            <CardDescription>Total Villages</CardDescription>
                            <CardTitle className="text-lg">{kingdom.total_villages}</CardTitle>
                        </CardHeader>
                    </Card>
                    <Card>
                        <CardHeader className="pb-2">
                            <CardDescription>Tax Rate</CardDescription>
                            <CardTitle className="text-lg">{kingdom.tax_rate}%</CardTitle>
                        </CardHeader>
                    </Card>
                </div>

                <div className="flex gap-4">
                    <Link
                        href={`/kingdoms/${kingdom.id}/roles`}
                        className="flex-1 rounded-lg border-2 border-amber-600/50 bg-amber-900/20 p-4 text-center transition hover:bg-amber-800/30"
                    >
                        <span className="font-pixel text-lg text-amber-300">View Roles</span>
                        <p className="text-sm text-stone-400">See kingdom officials and positions</p>
                    </Link>
                </div>

                <div>
                    <h2 className="text-xl font-semibold mb-4">Castles</h2>
                    <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                        {kingdom.castles.map((castle) => (
                            <Link key={castle.id} href={`/castles/${castle.id}`}>
                                <Card className="transition-shadow hover:shadow-lg cursor-pointer h-full">
                                    <CardHeader>
                                        <div className="flex items-center justify-between">
                                            <CardTitle className="text-base">
                                                {castle.name}
                                                {castle.is_capital && (
                                                    <span className="ml-2 text-xs text-amber-600 dark:text-amber-400">
                                                        (Capital)
                                                    </span>
                                                )}
                                            </CardTitle>
                                            <Badge className={biomeColors[castle.biome] || ''} variant="secondary">
                                                {castle.biome}
                                            </Badge>
                                        </div>
                                    </CardHeader>
                                    <CardContent>
                                        <p className="text-sm text-muted-foreground">
                                            {castle.village_count} {castle.village_count === 1 ? 'village' : 'villages'}
                                        </p>
                                    </CardContent>
                                </Card>
                            </Link>
                        ))}
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
