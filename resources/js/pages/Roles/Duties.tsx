import { Head, Link, usePage } from "@inertiajs/react";
import {
    Crown,
    ExternalLink,
    Gavel,
    HandHelping,
    Lock,
    MapPin,
    Scale,
    Scroll,
    ScrollText,
    ShieldAlert,
    type LucideIcon,
} from "lucide-react";
import AppLayout from "@/layouts/app-layout";
import type { BreadcrumbItem } from "@/types";

interface Category {
    key: string;
    label: string;
    description: string;
    count: number;
    href: string;
    icon: string;
}

interface PageProps {
    role: {
        name: string;
        slug: string;
        location_name: string;
    } | null;
    categories: Category[];
    [key: string]: unknown;
}

const iconMap: Record<string, LucideIcon> = {
    "map-pin": MapPin,
    gavel: Gavel,
    scroll: Scroll,
    "scroll-text": ScrollText,
    crown: Crown,
    "hand-helping": HandHelping,
    "shield-alert": ShieldAlert,
    scale: Scale,
    lock: Lock,
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: "Dashboard", href: "/dashboard" },
    { title: "Roles", href: "/roles" },
    { title: "Duties", href: "#" },
];

export default function Duties() {
    const { role, categories } = usePage<PageProps>().props;

    if (!role) {
        return (
            <AppLayout breadcrumbs={breadcrumbs}>
                <Head title="Role Duties" />
                <div className="flex h-full flex-1 items-center justify-center p-4">
                    <div className="text-center">
                        <Crown className="mx-auto mb-2 h-12 w-12 text-stone-600" />
                        <p className="font-pixel text-sm text-stone-500">
                            You do not hold any active role
                        </p>
                    </div>
                </div>
            </AppLayout>
        );
    }

    const totalPending = categories.reduce((sum, c) => sum + c.count, 0);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Role Duties" />
            <div className="flex h-full flex-1 flex-col overflow-hidden p-4">
                {/* Header */}
                <div className="mb-4">
                    <h1 className="font-pixel text-xl text-amber-400">Role Duties</h1>
                    <p className="font-pixel text-xs text-stone-400">
                        {role.name} of {role.location_name}
                        {totalPending > 0 && (
                            <span className="ml-2 rounded bg-red-900/50 px-1.5 py-0.5 font-pixel text-[10px] text-red-300">
                                {totalPending} pending
                            </span>
                        )}
                    </p>
                </div>

                <div className="-mx-1 flex-1 space-y-3 overflow-y-auto px-1">
                    {categories.map((category) => {
                        const Icon = iconMap[category.icon] || Scroll;
                        const hasItems = category.count > 0;

                        return (
                            <Link
                                key={category.key}
                                href={category.href}
                                className={`flex items-center gap-3 rounded-lg border p-3 transition ${
                                    hasItems
                                        ? "border-amber-600/30 bg-stone-800/60 hover:bg-stone-800/80"
                                        : "border-stone-700/30 bg-stone-800/20 opacity-60 hover:opacity-80"
                                }`}
                            >
                                <div
                                    className={`flex h-10 w-10 shrink-0 items-center justify-center rounded-lg ${
                                        hasItems ? "bg-amber-900/30" : "bg-stone-800/40"
                                    }`}
                                >
                                    <Icon
                                        className={`h-5 w-5 ${
                                            hasItems ? "text-amber-400" : "text-stone-500"
                                        }`}
                                    />
                                </div>

                                <div className="min-w-0 flex-1">
                                    <div className="flex items-center gap-2">
                                        <span
                                            className={`font-pixel text-sm ${
                                                hasItems ? "text-stone-200" : "text-stone-400"
                                            }`}
                                        >
                                            {category.label}
                                        </span>
                                        {hasItems && (
                                            <span className="rounded bg-red-900/50 px-1.5 py-0.5 font-pixel text-[10px] text-red-300">
                                                {category.count}
                                            </span>
                                        )}
                                    </div>
                                    <p className="font-pixel text-[10px] text-stone-500">
                                        {category.description}
                                    </p>
                                </div>

                                <ExternalLink
                                    className={`h-4 w-4 shrink-0 ${
                                        hasItems ? "text-stone-400" : "text-stone-600"
                                    }`}
                                />
                            </Link>
                        );
                    })}

                    {categories.length === 0 && (
                        <div className="flex flex-1 items-center justify-center py-16">
                            <div className="text-center">
                                <Crown className="mx-auto mb-2 h-12 w-12 text-stone-600" />
                                <p className="font-pixel text-sm text-stone-500">
                                    No duties for your role
                                </p>
                                <p className="font-pixel text-xs text-stone-600">
                                    Your role does not have any actionable categories
                                </p>
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
