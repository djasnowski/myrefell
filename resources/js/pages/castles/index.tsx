import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';

interface Castle {
    id: number;
    name: string;
    description: string;
    biome: string;
    tax_rate: number;
    villages_count: number;
    kingdom: {
        id: number;
        name: string;
    } | null;
    is_capital: boolean;
    coordinates: {
        x: number;
        y: number;
    };
}

interface Props {
    castles: Castle[];
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Castles', href: '/castles' },
];

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

export default function CastlesIndex({ castles }: Props) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Castles" />
            <div className="flex flex-col gap-6 p-6">
                <div>
                    <h1 className="text-2xl font-bold">Castles of Myrefell</h1>
                    <p className="text-muted-foreground">Strongholds that protect the realm.</p>
                </div>

                <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                    {castles.map((castle) => (
                        <Link key={castle.id} href={`/castles/${castle.id}`}>
                            <Card className="transition-shadow hover:shadow-lg cursor-pointer h-full">
                                <CardHeader>
                                    <div className="flex items-center justify-between">
                                        <CardTitle>
                                            {castle.name}
                                            {castle.is_capital && (
                                                <span className="ml-2 text-xs text-amber-600 dark:text-amber-400">
                                                    (Capital)
                                                </span>
                                            )}
                                        </CardTitle>
                                        <Badge className={biomeColors[castle.biome] || ''}>
                                            {castle.biome}
                                        </Badge>
                                    </div>
                                    <CardDescription>{castle.description}</CardDescription>
                                </CardHeader>
                                <CardContent>
                                    <div className="grid grid-cols-2 gap-4 text-sm">
                                        <div>
                                            <span className="text-muted-foreground">Kingdom:</span>
                                            <p className="font-medium">{castle.kingdom?.name || 'None'}</p>
                                        </div>
                                        <div>
                                            <span className="text-muted-foreground">Villages:</span>
                                            <p className="font-medium">{castle.villages_count}</p>
                                        </div>
                                        <div>
                                            <span className="text-muted-foreground">Tax Rate:</span>
                                            <p className="font-medium">{castle.tax_rate}%</p>
                                        </div>
                                        <div>
                                            <span className="text-muted-foreground">Coordinates:</span>
                                            <p className="font-medium">({castle.coordinates.x}, {castle.coordinates.y})</p>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        </Link>
                    ))}
                </div>
            </div>
        </AppLayout>
    );
}
