import { Head, Link } from "@inertiajs/react";
import { Badge } from "@/components/ui/badge";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import AppLayout from "@/layouts/app-layout";
import type { BreadcrumbItem } from "@/types";

interface Village {
    id: number;
    name: string;
    description: string;
    biome: string;
    is_town: boolean;
    population: number;
    barony: {
        id: number;
        name: string;
    } | null;
    kingdom: {
        id: number;
        name: string;
    } | null;
    coordinates: {
        x: number;
        y: number;
    };
}

interface Props {
    villages: Village[];
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: "Dashboard", href: "/dashboard" },
    { title: "Villages", href: "/villages" },
];

const biomeColors: Record<string, string> = {
    plains: "bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200",
    forest: "bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-200",
    tundra: "bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200",
    coastal: "bg-cyan-100 text-cyan-800 dark:bg-cyan-900 dark:text-cyan-200",
    desert: "bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200",
    volcano: "bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200",
    mountains: "bg-slate-100 text-slate-800 dark:bg-slate-900 dark:text-slate-200",
    swamps: "bg-lime-100 text-lime-800 dark:bg-lime-900 dark:text-lime-200",
};

export default function VillagesIndex({ villages }: Props) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Villages" />
            <div className="flex flex-col gap-6 p-6">
                <div>
                    <h1 className="text-2xl font-bold">Villages of Myrefell</h1>
                    <p className="text-muted-foreground">
                        Where the people of the realm make their homes.
                    </p>
                </div>

                <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                    {villages.map((village) => (
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
                                        <Badge className={biomeColors[village.biome] || ""}>
                                            {village.biome}
                                        </Badge>
                                    </div>
                                    <CardDescription className="line-clamp-2">
                                        {village.description}
                                    </CardDescription>
                                </CardHeader>
                                <CardContent>
                                    <div className="grid grid-cols-2 gap-2 text-sm">
                                        <div>
                                            <span className="text-muted-foreground">
                                                Population:
                                            </span>
                                            <p className="font-medium">
                                                {village.population.toLocaleString()}
                                            </p>
                                        </div>
                                        <div>
                                            <span className="text-muted-foreground">Barony:</span>
                                            <p className="font-medium truncate">
                                                {village.barony?.name || "None"}
                                            </p>
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
