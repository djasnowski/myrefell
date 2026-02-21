import { Head, Link } from "@inertiajs/react";
import { Crown, Newspaper, Shield } from "lucide-react";
import { Badge } from "@/components/ui/badge";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { RulerDisplay } from "@/components/ui/legitimacy-badge";
import AppLayout from "@/layouts/app-layout";
import type { BreadcrumbItem } from "@/types";

interface Barony {
    id: number;
    name: string;
    biome: string;
    village_count: number;
    town_count: number;
}

interface Role {
    id: number;
    name: string;
    slug: string;
    description: string;
    tier: number;
    salary: number;
    is_elected: boolean;
    holder: {
        id: number;
        username: string;
        legitimacy: number;
        appointed_at: string;
    } | null;
}

interface Ruler {
    id: number;
    username: string;
    primary_title?: string | null;
    legitimacy?: number;
}

interface Duchy {
    id: number;
    name: string;
    description: string;
    biome: string;
    tax_rate: number;
    coordinates: {
        x: number;
        y: number;
    };
    kingdom: {
        id: number;
        name: string;
        biome: string;
    } | null;
    baronies: Barony[];
    barony_count: number;
    duke?: Ruler | null;
}

interface Props {
    duchy: Duchy;
    roles: Role[];
    current_user_id: number;
    is_duke: boolean;
}

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

export default function DuchyShow({ duchy, roles, current_user_id, is_duke }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: "Dashboard", href: "/dashboard" },
        { title: "Duchies", href: "/duchies" },
        { title: duchy.name, href: `/duchies/${duchy.id}` },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`${duchy.name} - Duchy`} />
            <div className="flex flex-col gap-6 p-6">
                <div className="flex items-start justify-between">
                    <div>
                        <div className="flex items-center gap-3">
                            <h1 className="text-2xl font-bold">Duchy of {duchy.name}</h1>
                            <Badge className={biomeColors[duchy.biome] || ""}>{duchy.biome}</Badge>
                        </div>
                        <p className="text-muted-foreground mt-1">{duchy.description}</p>
                    </div>
                </div>

                <div className="grid gap-4 md:grid-cols-4">
                    <Card>
                        <CardHeader className="pb-2">
                            <CardDescription>Kingdom</CardDescription>
                            <CardTitle className="text-lg">
                                {duchy.kingdom ? (
                                    <Link
                                        href={`/kingdoms/${duchy.kingdom.id}`}
                                        className="hover:underline"
                                    >
                                        {duchy.kingdom.name}
                                    </Link>
                                ) : (
                                    "Independent"
                                )}
                            </CardTitle>
                        </CardHeader>
                    </Card>
                    <Card>
                        <CardHeader className="pb-2">
                            <CardDescription>Baronies</CardDescription>
                            <CardTitle className="text-lg">{duchy.barony_count}</CardTitle>
                        </CardHeader>
                    </Card>
                    <Card>
                        <CardHeader className="pb-2">
                            <CardDescription>Tax Rate</CardDescription>
                            <CardTitle className="text-lg">{duchy.tax_rate}%</CardTitle>
                        </CardHeader>
                    </Card>
                    <Card>
                        <CardHeader className="pb-2">
                            <CardDescription>Coordinates</CardDescription>
                            <CardTitle className="text-lg">
                                ({duchy.coordinates.x}, {duchy.coordinates.y})
                            </CardTitle>
                        </CardHeader>
                    </Card>
                </div>

                {/* Duke Authority Banner */}
                {is_duke && (
                    <div className="flex items-center gap-3 rounded-lg border border-amber-600/30 bg-amber-900/10 px-4 py-3">
                        <Crown className="h-5 w-5 text-amber-400" />
                        <div className="flex-1">
                            <div className="font-pixel text-sm text-amber-300">
                                You are the Duke
                            </div>
                            <div className="text-xs text-stone-400">
                                Manage duchy affairs, oversee baronies, appoint court positions
                            </div>
                        </div>
                        <div className="flex gap-2">
                            <Link
                                href={`/duchies/${duchy.id}/roles`}
                                className="flex items-center gap-1 rounded border border-stone-600 bg-stone-800 px-3 py-1.5 font-pixel text-xs text-stone-300 transition hover:bg-stone-700"
                            >
                                <Shield className="h-3 w-3" />
                                Roles
                            </Link>
                        </div>
                    </div>
                )}

                {/* Duke / Ruler */}
                <RulerDisplay
                    ruler={duchy.duke}
                    title="Duke"
                    isCurrentUser={duchy.duke?.id === current_user_id}
                />

                {/* Duchy Services */}
                <div>
                    <h2 className="mb-4 font-pixel text-lg text-stone-300">Duchy Services</h2>
                    <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                        <Link
                            href={`/duchies/${duchy.id}/notice-board`}
                            className="flex items-center gap-3 rounded-lg border-2 border-amber-600/50 bg-amber-900/20 p-4 transition hover:bg-amber-800/30"
                        >
                            <Newspaper className="h-8 w-8 text-amber-400" />
                            <div>
                                <span className="font-pixel text-sm text-amber-300">
                                    Notice Board
                                </span>
                                <p className="text-xs text-stone-500">
                                    Read and publish broadsheets
                                </p>
                            </div>
                        </Link>
                        <Link
                            href={`/duchies/${duchy.id}/roles`}
                            className="flex items-center gap-3 rounded-lg border-2 border-amber-600/50 bg-amber-900/20 p-4 transition hover:bg-amber-800/30"
                        >
                            <Shield className="h-8 w-8 text-amber-400" />
                            <div>
                                <span className="font-pixel text-sm text-amber-300">Roles</span>
                                <p className="text-xs text-stone-500">Officials & positions</p>
                            </div>
                        </Link>
                    </div>
                </div>

                {/* Duchy Roles */}
                <div>
                    <h2 className="text-xl font-semibold mb-4">Court Positions</h2>
                    <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                        {roles.map((role) => (
                            <Card
                                key={role.id}
                                className={
                                    role.holder ? "border-amber-500/30" : "border-stone-700/50"
                                }
                            >
                                <CardHeader className="pb-2">
                                    <div className="flex items-center justify-between">
                                        <CardTitle className="text-sm font-medium">
                                            {role.name}
                                        </CardTitle>
                                        <Badge variant="outline" className="text-xs">
                                            Tier {role.tier}
                                        </Badge>
                                    </div>
                                    <CardDescription className="text-xs">
                                        {role.salary}g/day
                                    </CardDescription>
                                </CardHeader>
                                <CardContent>
                                    {role.holder ? (
                                        <div className="text-sm">
                                            <Link
                                                href={`/players/${role.holder.id}`}
                                                className="text-amber-400 hover:underline"
                                            >
                                                {role.holder.username}
                                            </Link>
                                            <p className="text-xs text-stone-500 mt-1">
                                                Since {role.holder.appointed_at}
                                            </p>
                                        </div>
                                    ) : (
                                        <p className="text-sm text-stone-500 italic">Vacant</p>
                                    )}
                                </CardContent>
                            </Card>
                        ))}
                    </div>
                </div>

                {/* Baronies */}
                <div>
                    <h2 className="text-xl font-semibold mb-4">Baronies</h2>
                    <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                        {duchy.baronies.map((barony) => (
                            <Link key={barony.id} href={`/baronies/${barony.id}`}>
                                <Card className="transition-shadow hover:shadow-lg cursor-pointer h-full">
                                    <CardHeader>
                                        <div className="flex items-center justify-between">
                                            <CardTitle className="text-base">
                                                {barony.name}
                                            </CardTitle>
                                            <Badge
                                                className={biomeColors[barony.biome] || ""}
                                                variant="secondary"
                                            >
                                                {barony.biome}
                                            </Badge>
                                        </div>
                                    </CardHeader>
                                    <CardContent>
                                        <div className="flex gap-4 text-sm text-muted-foreground">
                                            <span>{barony.village_count} villages</span>
                                            <span>{barony.town_count} towns</span>
                                        </div>
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
