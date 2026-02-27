import { Head, Link, usePage } from "@inertiajs/react";
import { ShoppingBag, Store, User } from "lucide-react";
import AppLayout from "@/layouts/app-layout";
import type { BreadcrumbItem } from "@/types";
import { locationPath } from "@/lib/utils";

interface ShopData {
    id: number;
    name: string;
    slug: string;
    npc_name: string;
    description: string | null;
    icon: string | null;
    item_count: number;
}

interface LocationData {
    type: string;
    id: number;
    name: string;
}

interface PageProps {
    shops: ShopData[];
    location?: LocationData;
    [key: string]: unknown;
}

export default function ShopsIndex() {
    const { shops, location } = usePage<PageProps>().props;

    const breadcrumbs: BreadcrumbItem[] = [{ title: "Dashboard", href: "/dashboard" }];

    if (location) {
        breadcrumbs.push({
            title: location.name,
            href: locationPath(location.type, location.id),
        });
    }

    breadcrumbs.push({ title: "Shops", href: "#" });

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Shops${location ? ` - ${location.name}` : ""}`} />
            <div className="flex h-full flex-1 flex-col p-4">
                {/* Header */}
                <div className="mb-6 flex items-center gap-3">
                    <div className="rounded-lg bg-amber-900/30 p-3">
                        <ShoppingBag className="h-8 w-8 text-amber-400" />
                    </div>
                    <div>
                        <h1 className="font-pixel text-2xl text-amber-400">Shops</h1>
                        {location && (
                            <p className="font-pixel text-xs text-stone-400">{location.name}</p>
                        )}
                    </div>
                </div>

                {shops.length === 0 ? (
                    <div className="rounded-xl border-2 border-stone-700 bg-stone-800/50 p-8 text-center">
                        <Store className="mx-auto mb-3 h-12 w-12 text-stone-500" />
                        <h3 className="mb-2 font-pixel text-lg text-stone-400">No Shops Here</h3>
                        <p className="font-pixel text-xs text-stone-500">
                            There are no specialty shops at this location.
                        </p>
                    </div>
                ) : (
                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        {shops.map((shop) => (
                            <Link
                                key={shop.id}
                                href={
                                    location
                                        ? `${locationPath(location.type, location.id)}/shops/${shop.slug}`
                                        : "#"
                                }
                                className="group rounded-xl border-2 border-stone-700 bg-stone-800/50 p-5 transition-all hover:border-amber-600/50 hover:bg-stone-800/80"
                            >
                                <div className="mb-3 flex items-center gap-3">
                                    <div className="flex h-12 w-12 items-center justify-center rounded-full bg-amber-900/30 transition-colors group-hover:bg-amber-900/50">
                                        <Store className="h-6 w-6 text-amber-400" />
                                    </div>
                                    <div className="min-w-0 flex-1">
                                        <h3 className="truncate font-pixel text-sm text-amber-300">
                                            {shop.name}
                                        </h3>
                                        <div className="flex items-center gap-1 text-stone-400">
                                            <User className="h-3 w-3" />
                                            <span className="font-pixel text-[10px]">
                                                {shop.npc_name}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                {shop.description && (
                                    <p className="mb-3 line-clamp-2 font-pixel text-[10px] leading-relaxed text-stone-400">
                                        {shop.description}
                                    </p>
                                )}
                                <div className="flex items-center justify-between">
                                    <span className="font-pixel text-[10px] text-stone-500">
                                        {shop.item_count} {shop.item_count === 1 ? "item" : "items"}{" "}
                                        for sale
                                    </span>
                                    <span className="font-pixel text-[10px] text-amber-400 opacity-0 transition-opacity group-hover:opacity-100">
                                        Browse →
                                    </span>
                                </div>
                            </Link>
                        ))}
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
