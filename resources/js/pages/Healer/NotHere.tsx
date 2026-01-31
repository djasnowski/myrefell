import { Head, Link, usePage } from "@inertiajs/react";
import { Heart, MapPin } from "lucide-react";
import AppLayout from "@/layouts/app-layout";
import type { BreadcrumbItem } from "@/types";

interface PageProps {
    message: string;
    [key: string]: unknown;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: "Dashboard", href: "/dashboard" },
    { title: "Healer", href: "#" },
];

export default function NotHere() {
    const { message } = usePage<PageProps>().props;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Healer - Not Here" />
            <div className="flex h-full flex-1 items-center justify-center p-4">
                <div className="mx-auto max-w-md text-center">
                    <div className="rounded-xl border-2 border-stone-700 bg-stone-800/50 p-8">
                        <div className="mb-4 inline-flex h-16 w-16 items-center justify-center rounded-full bg-stone-700/50">
                            <Heart className="h-8 w-8 text-stone-500" />
                        </div>
                        <h2 className="mb-2 font-pixel text-lg text-stone-300">
                            Cannot Access Healer
                        </h2>
                        <p className="mb-6 font-pixel text-xs text-stone-500">{message}</p>
                        <Link
                            href="/travel"
                            className="inline-flex items-center gap-2 rounded-lg border-2 border-amber-600 bg-amber-900/30 px-6 py-2 font-pixel text-sm text-amber-300 transition hover:bg-amber-800/50"
                        >
                            <MapPin className="h-4 w-4" />
                            Travel
                        </Link>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
