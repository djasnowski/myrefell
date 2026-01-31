import { Head, Link } from "@inertiajs/react";
import { Badge } from "@/components/ui/badge";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import AppLayout from "@/layouts/app-layout";
import type { BreadcrumbItem } from "@/types";

interface Duchy {
    id: number;
    name: string;
    description: string;
    biome: string;
    tax_rate: number;
    baronies_count: number;
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
    duchies: Duchy[];
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: "Dashboard", href: "/dashboard" },
    { title: "Duchies", href: "/duchies" },
];

const biomeColors: Record<string, string> = {
    plains: "bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200",
    forest: "bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-200",
    tundra: "bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200",
    coastal: "bg-cyan-100 text-cyan-800 dark:bg-cyan-900 dark:text-cyan-200",
    desert: "bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200",
    volcanic: "bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200",
    mountains: "bg-slate-100 text-slate-800 dark:bg-slate-900 dark:text-slate-200",
    swamps: "bg-lime-100 text-lime-800 dark:bg-lime-900 dark:text-lime-200",
};

export default function DuchiesIndex({ duchies }: Props) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Duchies" />
            <div className="flex flex-col gap-6 p-6">
                <div>
                    <h1 className="text-2xl font-bold">Duchies of Myrefell</h1>
                    <p className="text-muted-foreground">
                        Great realms ruled by powerful Dukes, bridging the gap between Kingdoms and
                        Baronies.
                    </p>
                </div>

                <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                    {duchies.map((duchy) => (
                        <Link key={duchy.id} href={`/duchies/${duchy.id}`}>
                            <Card className="transition-shadow hover:shadow-lg cursor-pointer h-full">
                                <CardHeader>
                                    <div className="flex items-center justify-between">
                                        <CardTitle>{duchy.name}</CardTitle>
                                        <Badge className={biomeColors[duchy.biome] || ""}>
                                            {duchy.biome}
                                        </Badge>
                                    </div>
                                    <CardDescription>{duchy.description}</CardDescription>
                                </CardHeader>
                                <CardContent>
                                    <div className="grid grid-cols-2 gap-4 text-sm">
                                        <div>
                                            <span className="text-muted-foreground">Kingdom:</span>
                                            <p className="font-medium">
                                                {duchy.kingdom?.name || "Independent"}
                                            </p>
                                        </div>
                                        <div>
                                            <span className="text-muted-foreground">Baronies:</span>
                                            <p className="font-medium">{duchy.baronies_count}</p>
                                        </div>
                                        <div>
                                            <span className="text-muted-foreground">Tax Rate:</span>
                                            <p className="font-medium">{duchy.tax_rate}%</p>
                                        </div>
                                        <div>
                                            <span className="text-muted-foreground">
                                                Coordinates:
                                            </span>
                                            <p className="font-medium">
                                                {duchy.coordinates.x && duchy.coordinates.y
                                                    ? `(${duchy.coordinates.x}, ${duchy.coordinates.y})`
                                                    : "Unknown"}
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
