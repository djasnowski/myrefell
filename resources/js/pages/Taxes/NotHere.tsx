import { Head, Link } from "@inertiajs/react";
import { Landmark, MapPin } from "lucide-react";
import AppLayout from "@/layouts/app-layout";
import type { BreadcrumbItem } from "@/types";

interface PageProps {
    location: string;
    [key: string]: unknown;
}

export default function NotHere({ location }: PageProps) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: "Dashboard", href: "/dashboard" },
        { title: "Taxes", href: "#" },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Taxes - Not Here" />
            <div className="flex h-full flex-1 flex-col items-center justify-center p-4">
                <div className="text-center">
                    <div className="mb-4 inline-flex rounded-full bg-stone-800 p-4">
                        <Landmark className="h-12 w-12 text-stone-500" />
                    </div>
                    <h1 className="mb-2 font-pixel text-xl text-stone-300">Not at Location</h1>
                    <p className="mb-6 font-pixel text-sm text-stone-500">
                        You need to be at <span className="text-amber-400">{location}</span> to view
                        its treasury.
                    </p>
                    <Link
                        href="/travel"
                        className="inline-flex items-center gap-2 rounded-lg border-2 border-amber-600 bg-amber-900/30 px-6 py-3 font-pixel text-sm text-amber-300 transition hover:bg-amber-800/50"
                    >
                        <MapPin className="h-4 w-4" />
                        Travel
                    </Link>
                </div>
            </div>
        </AppLayout>
    );
}
