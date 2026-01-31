import { Head, Link, usePage } from "@inertiajs/react";
import { Gauge, MapPin } from "lucide-react";
import AppLayout from "@/layouts/app-layout";
import type { BreadcrumbItem } from "@/types";

interface PageProps {
    message: string;
    [key: string]: unknown;
}

export default function StableNotAvailable() {
    const { message } = usePage<PageProps>().props;

    const breadcrumbs: BreadcrumbItem[] = [
        { title: "Dashboard", href: "/dashboard" },
        { title: "Stables", href: "/stable" },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Stables Unavailable" />
            <div className="flex h-full flex-1 items-center justify-center p-4">
                <div className="max-w-md text-center">
                    <div className="mx-auto mb-6 flex h-20 w-20 items-center justify-center rounded-full bg-stone-800/50">
                        <Gauge className="h-10 w-10 text-stone-500" />
                    </div>
                    <h1 className="mb-2 font-[Cinzel] text-xl text-stone-300">No Stables Here</h1>
                    <p className="mb-6 text-sm text-stone-500">{message}</p>
                    <Link
                        href="/travel"
                        className="inline-flex items-center gap-2 rounded-lg border border-amber-600 bg-amber-900/30 px-4 py-2 text-sm text-amber-300 transition hover:bg-amber-800/50"
                    >
                        <MapPin className="h-4 w-4" />
                        Travel to Find Stables
                    </Link>
                </div>
            </div>
        </AppLayout>
    );
}
