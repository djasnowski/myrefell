import { Head, router } from "@inertiajs/react";
import { ChevronLeft, ChevronRight, Crown, Search } from "lucide-react";
import { useState, useEffect } from "react";
import { show as showDynasty } from "@/actions/App/Http/Controllers/Admin/DynastyController";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import AdminLayout from "@/layouts/admin-layout";
import type { BreadcrumbItem } from "@/types";

interface Dynasty {
    id: number;
    name: string;
    motto: string | null;
    founder: { id: number; username: string } | null;
    current_head: { id: number; username: string } | null;
    members_count: number;
    living_members_count: number;
    prestige: number;
    wealth_score: number;
    generations: number;
    founded_at: string | null;
    created_at: string;
}

interface PaginatedDynasties {
    data: Dynasty[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number;
    to: number;
    links: Array<{
        url: string | null;
        label: string;
        active: boolean;
    }>;
}

interface Filters {
    search: string;
    min_prestige: string;
}

interface Props {
    dynasties: PaginatedDynasties;
    filters: Filters;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: "Admin", href: "/admin" },
    { title: "Dynasties", href: "/admin/dynasties" },
];

export default function Index({ dynasties, filters }: Props) {
    const [search, setSearch] = useState(filters.search);
    const [minPrestige, setMinPrestige] = useState(filters.min_prestige);

    useEffect(() => {
        const timeout = setTimeout(() => {
            if (search !== filters.search) {
                router.get(
                    "/admin/dynasties",
                    { ...filters, search },
                    { preserveState: true, preserveScroll: true },
                );
            }
        }, 300);

        return () => clearTimeout(timeout);
    }, [search]);

    const handleFilterChange = (key: string, value: string) => {
        router.get("/admin/dynasties", { ...filters, [key]: value }, { preserveState: true });
    };

    const formatDate = (dateStr: string | null) => {
        if (!dateStr) return "—";
        return new Date(dateStr).toLocaleDateString("en-US", {
            year: "numeric",
            month: "short",
            day: "numeric",
        });
    };

    return (
        <AdminLayout breadcrumbs={breadcrumbs}>
            <Head title="Manage Dynasties" />

            <div className="space-y-6 p-6">
                {/* Header */}
                <div className="flex items-center gap-3">
                    <div className="rounded-lg bg-amber-900/30 p-2">
                        <Crown className="size-6 text-amber-400" />
                    </div>
                    <div>
                        <h1 className="font-[Cinzel] text-2xl font-bold text-stone-100">
                            Manage Dynasties
                        </h1>
                        <p className="text-sm text-stone-400">
                            {dynasties.total.toLocaleString()} total dynasties
                        </p>
                    </div>
                </div>

                {/* Filters */}
                <Card className="border-stone-800 bg-stone-900/50">
                    <CardContent className="pt-6">
                        <div className="flex flex-wrap items-center gap-4">
                            {/* Search */}
                            <div className="flex-1">
                                <div className="relative">
                                    <Search className="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-stone-500" />
                                    <Input
                                        type="text"
                                        placeholder="Search by name..."
                                        value={search}
                                        onChange={(e) => setSearch(e.target.value)}
                                        className="pl-10 border-stone-700 bg-stone-900/50"
                                    />
                                </div>
                            </div>

                            {/* Min Prestige */}
                            <div className="w-[180px]">
                                <Input
                                    type="number"
                                    placeholder="Min prestige"
                                    value={minPrestige}
                                    onChange={(e) => {
                                        setMinPrestige(e.target.value);
                                        handleFilterChange("min_prestige", e.target.value);
                                    }}
                                    className="border-stone-700 bg-stone-900/50"
                                />
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* Dynasties Table */}
                <Card className="border-stone-800 bg-stone-900/50">
                    <CardHeader>
                        <CardTitle className="text-stone-100">Dynasties</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="overflow-x-auto">
                            <table className="w-full">
                                <thead>
                                    <tr className="border-b border-stone-800">
                                        <th className="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-stone-400">
                                            ID
                                        </th>
                                        <th className="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-stone-400">
                                            Name
                                        </th>
                                        <th className="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-stone-400">
                                            Founder
                                        </th>
                                        <th className="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-stone-400">
                                            Current Head
                                        </th>
                                        <th className="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-stone-400">
                                            Members
                                        </th>
                                        <th className="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-stone-400">
                                            Prestige
                                        </th>
                                        <th className="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-stone-400">
                                            Founded
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-stone-800">
                                    {dynasties.data.map((dynasty) => (
                                        <tr
                                            key={dynasty.id}
                                            className="hover:bg-stone-800/50 transition cursor-pointer"
                                            onClick={() =>
                                                router.visit(showDynasty.url(dynasty.id))
                                            }
                                        >
                                            <td className="px-4 py-3 text-stone-500">
                                                {dynasty.id}
                                            </td>
                                            <td className="px-4 py-3">
                                                <div>
                                                    <span className="font-medium text-stone-100">
                                                        {dynasty.name}
                                                    </span>
                                                    {dynasty.motto && (
                                                        <p className="text-xs text-stone-500 italic mt-0.5">
                                                            "{dynasty.motto}"
                                                        </p>
                                                    )}
                                                </div>
                                            </td>
                                            <td className="px-4 py-3 text-stone-400">
                                                {dynasty.founder?.username ?? "—"}
                                            </td>
                                            <td className="px-4 py-3 text-stone-400">
                                                {dynasty.current_head?.username ?? "—"}
                                            </td>
                                            <td className="px-4 py-3">
                                                <div className="flex items-center gap-2">
                                                    <span className="text-stone-100">
                                                        {dynasty.living_members_count}
                                                    </span>
                                                    <span className="text-stone-500 text-xs">
                                                        / {dynasty.members_count} total
                                                    </span>
                                                    {dynasty.generations > 1 && (
                                                        <Badge
                                                            variant="secondary"
                                                            className="bg-purple-900/30 text-purple-400 text-xs"
                                                        >
                                                            Gen {dynasty.generations}
                                                        </Badge>
                                                    )}
                                                </div>
                                            </td>
                                            <td className="px-4 py-3">
                                                <span className="text-amber-400 font-medium">
                                                    {dynasty.prestige.toLocaleString()}
                                                </span>
                                            </td>
                                            <td className="px-4 py-3 text-stone-400">
                                                {formatDate(dynasty.founded_at)}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>

                        {/* Pagination */}
                        {dynasties.last_page > 1 && (
                            <div className="mt-6 flex items-center justify-between border-t border-stone-800 pt-6">
                                <p className="text-sm text-stone-400">
                                    Showing {dynasties.from} to {dynasties.to} of {dynasties.total}{" "}
                                    dynasties
                                </p>
                                <div className="flex gap-2">
                                    {dynasties.links.map((link, index) => {
                                        if (link.label.includes("Previous")) {
                                            return (
                                                <Button
                                                    key={index}
                                                    variant="outline"
                                                    size="sm"
                                                    disabled={!link.url}
                                                    onClick={() => link.url && router.get(link.url)}
                                                    className="border-stone-700"
                                                >
                                                    <ChevronLeft className="size-4" />
                                                </Button>
                                            );
                                        }
                                        if (link.label.includes("Next")) {
                                            return (
                                                <Button
                                                    key={index}
                                                    variant="outline"
                                                    size="sm"
                                                    disabled={!link.url}
                                                    onClick={() => link.url && router.get(link.url)}
                                                    className="border-stone-700"
                                                >
                                                    <ChevronRight className="size-4" />
                                                </Button>
                                            );
                                        }
                                        return (
                                            <Button
                                                key={index}
                                                variant={link.active ? "default" : "outline"}
                                                size="sm"
                                                onClick={() => link.url && router.get(link.url)}
                                                className={link.active ? "" : "border-stone-700"}
                                            >
                                                {link.label}
                                            </Button>
                                        );
                                    })}
                                </div>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AdminLayout>
    );
}
