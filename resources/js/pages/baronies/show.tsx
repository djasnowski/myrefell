import { Head, Link } from '@inertiajs/react';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { RulerDisplay } from '@/components/ui/legitimacy-badge';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

interface Village {
    id: number;
    name: string;
    biome: string;
    is_town: boolean;
    population: number;
}

interface Ruler {
    id: number;
    username: string;
    primary_title?: string | null;
    legitimacy?: number;
}

interface Barony {
    id: number;
    name: string;
    description: string;
    biome: string;
    tax_rate: number;
    is_capital: boolean;
    coordinates: {
        x: number;
        y: number;
    };
    kingdom: {
        id: number;
        name: string;
        biome: string;
    } | null;
    villages: Village[];
    village_count: number;
    baron?: Ruler | null;
}

interface Props {
    barony: Barony;
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

export default function BaronyShow({ barony, current_user_id }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Baronies', href: '/baronies' },
        { title: barony.name, href: `/baronies/${barony.id}` },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={barony.name} />
            <div className="flex flex-col gap-6 p-6">
                <div className="flex items-start justify-between">
                    <div>
                        <div className="flex items-center gap-3">
                            <h1 className="text-2xl font-bold">
                                {barony.name}
                                {barony.is_capital && (
                                    <span className="ml-2 text-sm text-amber-600 dark:text-amber-400">
                                        (Capital)
                                    </span>
                                )}
                            </h1>
                            <Badge className={biomeColors[barony.biome] || ''}>
                                {barony.biome}
                            </Badge>
                        </div>
                        <p className="text-muted-foreground mt-1">{barony.description}</p>
                    </div>
                </div>

                <div className="grid gap-4 md:grid-cols-4">
                    <Card>
                        <CardHeader className="pb-2">
                            <CardDescription>Kingdom</CardDescription>
                            <CardTitle className="text-lg">
                                {barony.kingdom ? (
                                    <Link href={`/kingdoms/${barony.kingdom.id}`} className="hover:underline">
                                        {barony.kingdom.name}
                                    </Link>
                                ) : (
                                    'Independent'
                                )}
                            </CardTitle>
                        </CardHeader>
                    </Card>
                    <Card>
                        <CardHeader className="pb-2">
                            <CardDescription>Villages</CardDescription>
                            <CardTitle className="text-lg">{barony.village_count}</CardTitle>
                        </CardHeader>
                    </Card>
                    <Card>
                        <CardHeader className="pb-2">
                            <CardDescription>Tax Rate</CardDescription>
                            <CardTitle className="text-lg">{barony.tax_rate}%</CardTitle>
                        </CardHeader>
                    </Card>
                    <Card>
                        <CardHeader className="pb-2">
                            <CardDescription>Coordinates</CardDescription>
                            <CardTitle className="text-lg">({barony.coordinates.x}, {barony.coordinates.y})</CardTitle>
                        </CardHeader>
                    </Card>
                </div>

                {/* Baron / Ruler */}
                <RulerDisplay
                    ruler={barony.baron}
                    title="Baron"
                    isCurrentUser={barony.baron?.id === current_user_id}
                />

                <div className="flex gap-4">
                    <Link
                        href={`/baronies/${barony.id}/roles`}
                        className="flex-1 rounded-lg border-2 border-amber-600/50 bg-amber-900/20 p-4 text-center transition hover:bg-amber-800/30"
                    >
                        <span className="font-pixel text-lg text-amber-300">View Roles</span>
                        <p className="text-sm text-stone-400">See barony officials and positions</p>
                    </Link>
                </div>

                <div>
                    <h2 className="text-xl font-semibold mb-4">Villages</h2>
                    <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                        {barony.villages.map((village) => (
                            <Link key={village.id} href={`/villages/${village.id}`}>
                                <Card className="transition-shadow hover:shadow-lg cursor-pointer h-full">
                                    <CardHeader>
                                        <div className="flex items-center justify-between">
                                            <CardTitle className="text-base">
                                                {village.name}
                                                {village.is_town && (
                                                    <span className="ml-2 text-xs text-purple-600 dark:text-purple-400">
                                                        (Town)
                                                    </span>
                                                )}
                                            </CardTitle>
                                            <Badge className={biomeColors[village.biome] || ''} variant="secondary">
                                                {village.biome}
                                            </Badge>
                                        </div>
                                    </CardHeader>
                                    <CardContent>
                                        <p className="text-sm text-muted-foreground">
                                            Population: {village.population.toLocaleString()}
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
