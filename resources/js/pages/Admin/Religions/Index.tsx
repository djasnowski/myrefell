import { Head, router } from "@inertiajs/react";
import { ChevronLeft, ChevronRight, Church, Search, Skull } from "lucide-react";
import { useState, useEffect } from "react";
import { show as showReligion } from "@/actions/App/Http/Controllers/Admin/ReligionController";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
import AdminLayout from "@/layouts/admin-layout";
import type { BreadcrumbItem } from "@/types";

interface Religion {
    id: number;
    name: string;
    type: "cult" | "religion";
    icon: string | null;
    color: string | null;
    is_public: boolean;
    is_active: boolean;
    members_count: number;
    member_limit: number | null;
    hideout_tier: number | null;
    treasury_balance: number;
    founder: { id: number; username: string } | null;
    created_at: string;
}

interface PaginatedReligions {
    data: Religion[];
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
    type: string;
    active: string;
}

interface Props {
    religions: PaginatedReligions;
    filters: Filters;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: "Admin", href: "/admin" },
    { title: "Religions", href: "/admin/religions" },
];

export default function Index({ religions, filters }: Props) {
    const [search, setSearch] = useState(filters.search);

    useEffect(() => {
        const timeout = setTimeout(() => {
            if (search !== filters.search) {
                router.get(
                    "/admin/religions",
                    { ...filters, search },
                    { preserveState: true, preserveScroll: true },
                );
            }
        }, 300);

        return () => clearTimeout(timeout);
    }, [search]);

    const handleFilterChange = (key: string, value: string) => {
        router.get("/admin/religions", { ...filters, [key]: value }, { preserveState: true });
    };

    const formatDate = (dateStr: string) => {
        return new Date(dateStr).toLocaleDateString("en-US", {
            year: "numeric",
            month: "short",
            day: "numeric",
        });
    };

    return (
        <AdminLayout breadcrumbs={breadcrumbs}>
            <Head title="Manage Religions" />

            <div className="space-y-6 p-6">
                {/* Header */}
                <div className="flex items-center gap-3">
                    <div className="rounded-lg bg-purple-900/30 p-2">
                        <Church className="size-6 text-purple-400" />
                    </div>
                    <div>
                        <h1 className="font-[Cinzel] text-2xl font-bold text-stone-100">
                            Manage Religions
                        </h1>
                        <p className="text-sm text-stone-400">
                            {religions.total.toLocaleString()} total religions & cults
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

                            {/* Type Filter */}
                            <Select
                                value={filters.type || "all"}
                                onValueChange={(value) =>
                                    handleFilterChange("type", value === "all" ? "" : value)
                                }
                            >
                                <SelectTrigger className="w-[150px] border-stone-700 bg-stone-900/50">
                                    <SelectValue placeholder="Type" />
                                </SelectTrigger>
                                <SelectContent className="border-stone-700 bg-stone-900">
                                    <SelectItem value="all">All types</SelectItem>
                                    <SelectItem value="religion">Religions</SelectItem>
                                    <SelectItem value="cult">Cults</SelectItem>
                                </SelectContent>
                            </Select>

                            {/* Active Filter */}
                            <Select
                                value={filters.active || "all"}
                                onValueChange={(value) =>
                                    handleFilterChange("active", value === "all" ? "" : value)
                                }
                            >
                                <SelectTrigger className="w-[150px] border-stone-700 bg-stone-900/50">
                                    <SelectValue placeholder="Status" />
                                </SelectTrigger>
                                <SelectContent className="border-stone-700 bg-stone-900">
                                    <SelectItem value="all">All statuses</SelectItem>
                                    <SelectItem value="true">Active only</SelectItem>
                                    <SelectItem value="false">Inactive only</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </CardContent>
                </Card>

                {/* Religions Table */}
                <Card className="border-stone-800 bg-stone-900/50">
                    <CardHeader>
                        <CardTitle className="text-stone-100">Religions & Cults</CardTitle>
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
                                            Type
                                        </th>
                                        <th className="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-stone-400">
                                            Founder
                                        </th>
                                        <th className="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-stone-400">
                                            Members
                                        </th>
                                        <th className="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-stone-400">
                                            Treasury
                                        </th>
                                        <th className="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-stone-400">
                                            Status
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-stone-800">
                                    {religions.data.map((religion) => (
                                        <tr
                                            key={religion.id}
                                            className="hover:bg-stone-800/50 transition cursor-pointer"
                                            onClick={() =>
                                                router.visit(showReligion.url(religion.id))
                                            }
                                        >
                                            <td className="px-4 py-3 text-stone-500">
                                                {religion.id}
                                            </td>
                                            <td className="px-4 py-3">
                                                <div className="flex items-center gap-2">
                                                    {religion.type === "cult" ? (
                                                        <Skull className="size-4 text-red-400" />
                                                    ) : (
                                                        <Church className="size-4 text-purple-400" />
                                                    )}
                                                    <span className="font-medium text-stone-100">
                                                        {religion.name}
                                                    </span>
                                                    {!religion.is_public && (
                                                        <Badge
                                                            variant="secondary"
                                                            className="bg-stone-700 text-stone-400 text-xs"
                                                        >
                                                            Private
                                                        </Badge>
                                                    )}
                                                </div>
                                            </td>
                                            <td className="px-4 py-3">
                                                <Badge
                                                    variant="secondary"
                                                    className={
                                                        religion.type === "cult"
                                                            ? "bg-red-900/30 text-red-400"
                                                            : "bg-purple-900/30 text-purple-400"
                                                    }
                                                >
                                                    {religion.type === "cult" ? "Cult" : "Religion"}
                                                </Badge>
                                                {religion.type === "cult" &&
                                                    religion.hideout_tier !== null &&
                                                    religion.hideout_tier > 0 && (
                                                        <Badge
                                                            variant="secondary"
                                                            className="ml-1 bg-stone-700 text-stone-300 text-xs"
                                                        >
                                                            T{religion.hideout_tier}
                                                        </Badge>
                                                    )}
                                            </td>
                                            <td className="px-4 py-3 text-stone-400">
                                                {religion.founder?.username ?? "—"}
                                            </td>
                                            <td className="px-4 py-3 text-stone-100">
                                                {religion.members_count}
                                            </td>
                                            <td className="px-4 py-3 text-amber-400">
                                                {religion.treasury_balance.toLocaleString()}g
                                            </td>
                                            <td className="px-4 py-3">
                                                <Badge
                                                    variant="secondary"
                                                    className={
                                                        religion.is_active
                                                            ? "bg-green-900/30 text-green-400"
                                                            : "bg-stone-700 text-stone-400"
                                                    }
                                                >
                                                    {religion.is_active ? "Active" : "Inactive"}
                                                </Badge>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>

                        {/* Pagination */}
                        {religions.last_page > 1 && (
                            <div className="mt-6 flex items-center justify-between border-t border-stone-800 pt-6">
                                <p className="text-sm text-stone-400">
                                    Showing {religions.from} to {religions.to} of {religions.total}{" "}
                                    religions
                                </p>
                                <div className="flex gap-2">
                                    {religions.links.map((link, index) => {
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
