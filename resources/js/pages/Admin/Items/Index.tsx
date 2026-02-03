import { Head, router } from "@inertiajs/react";
import { ChevronLeft, ChevronRight, Package, Search } from "lucide-react";
import { useState, useEffect } from "react";
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

interface Item {
    id: number;
    name: string;
    description: string | null;
    type: string;
    subtype: string | null;
    rarity: string | null;
    stackable: boolean;
    max_stack: number | null;
    atk_bonus: number | null;
    str_bonus: number | null;
    def_bonus: number | null;
    hp_bonus: number | null;
    energy_bonus: number | null;
    equipment_slot: string | null;
    required_level: number | null;
    required_skill: string | null;
    required_skill_level: number | null;
    base_value: number | null;
    is_perishable: boolean;
    food_value: number | null;
    is_tradeable: boolean;
}

interface PaginatedItems {
    data: Item[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number;
    to: number;
}

interface Filters {
    search: string;
    type: string;
    subtype: string;
    rarity: string;
}

interface Props {
    items: PaginatedItems;
    types: string[];
    subtypes: string[];
    rarities: string[];
    filters: Filters;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: "Admin", href: "/admin" },
    { title: "Items", href: "/admin/items" },
];

const rarityColors: Record<string, string> = {
    common: "bg-stone-500",
    uncommon: "bg-green-500",
    rare: "bg-blue-500",
    epic: "bg-purple-500",
    legendary: "bg-orange-500",
};

export default function Index({ items, types, subtypes, rarities, filters }: Props) {
    const [search, setSearch] = useState(filters.search);

    useEffect(() => {
        const timeout = setTimeout(() => {
            if (search !== filters.search) {
                router.get(
                    "/admin/items",
                    { ...filters, search },
                    { preserveState: true, preserveScroll: true },
                );
            }
        }, 300);

        return () => clearTimeout(timeout);
    }, [search]);

    const handleFilterChange = (key: string, value: string) => {
        router.get(
            "/admin/items",
            { ...filters, [key]: value === "all" ? "" : value },
            { preserveState: true },
        );
    };

    const handlePageChange = (page: number) => {
        router.get(
            "/admin/items",
            { ...filters, page },
            { preserveState: true, preserveScroll: true },
        );
    };

    return (
        <AdminLayout breadcrumbs={breadcrumbs}>
            <Head title="Items - Admin" />

            <div className="space-y-6 p-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold">Items</h1>
                        <p className="text-muted-foreground">{items.total} items in database</p>
                    </div>
                </div>

                {/* Filters */}
                <Card>
                    <CardContent className="pt-6">
                        <div className="flex flex-wrap gap-4">
                            <div className="relative flex-1 min-w-[200px]">
                                <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                                <Input
                                    placeholder="Search items..."
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                    className="pl-10"
                                />
                            </div>
                            <Select
                                value={filters.type || "all"}
                                onValueChange={(value) => handleFilterChange("type", value)}
                            >
                                <SelectTrigger className="w-[150px]">
                                    <SelectValue placeholder="Type" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All Types</SelectItem>
                                    {types.map((type) => (
                                        <SelectItem key={type} value={type}>
                                            {type}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <Select
                                value={filters.subtype || "all"}
                                onValueChange={(value) => handleFilterChange("subtype", value)}
                            >
                                <SelectTrigger className="w-[150px]">
                                    <SelectValue placeholder="Subtype" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All Subtypes</SelectItem>
                                    {subtypes.map((subtype) => (
                                        <SelectItem key={subtype} value={subtype}>
                                            {subtype}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <Select
                                value={filters.rarity || "all"}
                                onValueChange={(value) => handleFilterChange("rarity", value)}
                            >
                                <SelectTrigger className="w-[150px]">
                                    <SelectValue placeholder="Rarity" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All Rarities</SelectItem>
                                    {rarities.map((rarity) => (
                                        <SelectItem key={rarity} value={rarity}>
                                            {rarity}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>
                    </CardContent>
                </Card>

                {/* Items Table */}
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <Package className="h-5 w-5" />
                            Items ({items.from}-{items.to} of {items.total})
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b text-left">
                                        <th className="pb-3 pr-4 font-medium">ID</th>
                                        <th className="pb-3 pr-4 font-medium">Name</th>
                                        <th className="pb-3 pr-4 font-medium">Type</th>
                                        <th className="pb-3 pr-4 font-medium">Subtype</th>
                                        <th className="pb-3 pr-4 font-medium">Rarity</th>
                                        <th className="pb-3 pr-4 font-medium">Value</th>
                                        <th className="pb-3 pr-4 font-medium">Bonuses</th>
                                        <th className="pb-3 pr-4 font-medium">Slot</th>
                                        <th className="pb-3 pr-4 font-medium">Requirements</th>
                                        <th className="pb-3 pr-4 font-medium">Food</th>
                                        <th className="pb-3 font-medium">Flags</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {items.data.map((item) => (
                                        <tr
                                            key={item.id}
                                            className="border-b border-border/50 hover:bg-muted/50"
                                        >
                                            <td className="py-3 pr-4 text-muted-foreground">
                                                {item.id}
                                            </td>
                                            <td className="py-3 pr-4 font-medium">{item.name}</td>
                                            <td className="py-3 pr-4">
                                                <Badge variant="outline">{item.type}</Badge>
                                            </td>
                                            <td className="py-3 pr-4 text-muted-foreground">
                                                {item.subtype || "-"}
                                            </td>
                                            <td className="py-3 pr-4">
                                                {item.rarity && (
                                                    <Badge
                                                        className={`${rarityColors[item.rarity] || "bg-stone-500"} text-white`}
                                                    >
                                                        {item.rarity}
                                                    </Badge>
                                                )}
                                            </td>
                                            <td className="py-3 pr-4 text-muted-foreground">
                                                {item.base_value?.toLocaleString() || "-"}
                                            </td>
                                            <td className="py-3 pr-4">
                                                <div className="flex flex-wrap gap-1 text-xs">
                                                    {item.atk_bonus ? (
                                                        <span className="text-red-400">
                                                            ATK+{item.atk_bonus}
                                                        </span>
                                                    ) : null}
                                                    {item.str_bonus ? (
                                                        <span className="text-orange-400">
                                                            STR+{item.str_bonus}
                                                        </span>
                                                    ) : null}
                                                    {item.def_bonus ? (
                                                        <span className="text-blue-400">
                                                            DEF+{item.def_bonus}
                                                        </span>
                                                    ) : null}
                                                    {item.hp_bonus ? (
                                                        <span className="text-pink-400">
                                                            HP+{item.hp_bonus}
                                                        </span>
                                                    ) : null}
                                                    {item.energy_bonus ? (
                                                        <span className="text-yellow-400">
                                                            EN+{item.energy_bonus}
                                                        </span>
                                                    ) : null}
                                                    {!item.atk_bonus &&
                                                        !item.str_bonus &&
                                                        !item.def_bonus &&
                                                        !item.hp_bonus &&
                                                        !item.energy_bonus && (
                                                            <span className="text-muted-foreground">
                                                                -
                                                            </span>
                                                        )}
                                                </div>
                                            </td>
                                            <td className="py-3 pr-4 text-muted-foreground">
                                                {item.equipment_slot || "-"}
                                            </td>
                                            <td className="py-3 pr-4">
                                                <div className="flex flex-col gap-0.5 text-xs text-muted-foreground">
                                                    {item.required_level && (
                                                        <span>Lv.{item.required_level}</span>
                                                    )}
                                                    {item.required_skill && (
                                                        <span>
                                                            {item.required_skill}{" "}
                                                            {item.required_skill_level}
                                                        </span>
                                                    )}
                                                    {!item.required_level &&
                                                        !item.required_skill && <span>-</span>}
                                                </div>
                                            </td>
                                            <td className="py-3 pr-4 text-muted-foreground">
                                                {item.food_value || "-"}
                                            </td>
                                            <td className="py-3">
                                                <div className="flex flex-wrap gap-1">
                                                    {item.stackable && (
                                                        <Badge
                                                            variant="outline"
                                                            className="text-xs"
                                                        >
                                                            Stack
                                                            {item.max_stack
                                                                ? `(${item.max_stack})`
                                                                : ""}
                                                        </Badge>
                                                    )}
                                                    {item.is_perishable && (
                                                        <Badge
                                                            variant="outline"
                                                            className="text-xs text-amber-400 border-amber-400"
                                                        >
                                                            Perish
                                                        </Badge>
                                                    )}
                                                    {item.is_tradeable === false && (
                                                        <Badge
                                                            variant="outline"
                                                            className="text-xs text-red-400 border-red-400"
                                                        >
                                                            NoTrade
                                                        </Badge>
                                                    )}
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>

                        {/* Pagination */}
                        {items.last_page > 1 && (
                            <div className="mt-6 flex items-center justify-center gap-2 border-t pt-4">
                                <Button
                                    variant="outline"
                                    size="sm"
                                    onClick={() => handlePageChange(items.current_page - 1)}
                                    disabled={items.current_page === 1}
                                >
                                    <ChevronLeft className="h-4 w-4" />
                                    Prev
                                </Button>
                                <span className="px-4 text-sm text-muted-foreground">
                                    Page {items.current_page} of {items.last_page}
                                </span>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    onClick={() => handlePageChange(items.current_page + 1)}
                                    disabled={items.current_page === items.last_page}
                                >
                                    Next
                                    <ChevronRight className="h-4 w-4" />
                                </Button>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AdminLayout>
    );
}
