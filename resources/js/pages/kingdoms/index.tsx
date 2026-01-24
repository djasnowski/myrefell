import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';

interface Kingdom {
    id: number;
    name: string;
    description: string;
    biome: string;
    tax_rate: number;
    castles_count: number;
    capital: {
        id: number;
        name: string;
    } | null;
    coordinates: {
        x: number;
        y: number;
    };
}

interface Props {
    kingdoms: Kingdom[];
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Kingdoms', href: '/kingdoms' },
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

export default function KingdomsIndex({ kingdoms }: Props) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Kingdoms" />
            <div className="flex flex-col gap-6 p-6">
                <div>
                    <h1 className="text-2xl font-bold">Kingdoms of Myrefell</h1>
                    <p className="text-muted-foreground">Explore the great kingdoms that rule these lands.</p>
                </div>

                <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                    {kingdoms.map((kingdom) => (
                        <Link key={kingdom.id} href={`/kingdoms/${kingdom.id}`}>
                            <Card className="transition-shadow hover:shadow-lg cursor-pointer h-full">
                                <CardHeader>
                                    <div className="flex items-center justify-between">
                                        <CardTitle>{kingdom.name}</CardTitle>
                                        <Badge className={biomeColors[kingdom.biome] || ''}>
                                            {kingdom.biome}
                                        </Badge>
                                    </div>
                                    <CardDescription>{kingdom.description}</CardDescription>
                                </CardHeader>
                                <CardContent>
                                    <div className="grid grid-cols-2 gap-4 text-sm">
                                        <div>
                                            <span className="text-muted-foreground">Capital:</span>
                                            <p className="font-medium">{kingdom.capital?.name || 'None'}</p>
                                        </div>
                                        <div>
                                            <span className="text-muted-foreground">Castles:</span>
                                            <p className="font-medium">{kingdom.castles_count}</p>
                                        </div>
                                        <div>
                                            <span className="text-muted-foreground">Tax Rate:</span>
                                            <p className="font-medium">{kingdom.tax_rate}%</p>
                                        </div>
                                        <div>
                                            <span className="text-muted-foreground">Coordinates:</span>
                                            <p className="font-medium">({kingdom.coordinates.x}, {kingdom.coordinates.y})</p>
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
