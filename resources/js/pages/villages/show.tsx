import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';

interface Resident {
    id: number;
    username: string;
    combat_level: number;
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
    castle: {
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
}

interface Props {
    village: Village;
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

export default function VillageShow({ village }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Villages', href: '/villages' },
        { title: village.name, href: `/villages/${village.id}` },
    ];

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
                            <CardDescription>Castle</CardDescription>
                            <CardTitle className="text-lg">
                                {village.castle ? (
                                    <Link href={`/castles/${village.castle.id}`} className="hover:underline">
                                        {village.castle.name}
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
