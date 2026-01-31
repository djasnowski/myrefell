import { Head, usePage } from "@inertiajs/react";
import { MapPin } from "lucide-react";
import AppLayout from "@/layouts/app-layout";
import type { BreadcrumbItem } from "@/types";

interface PageProps {
    location: string;
    [key: string]: unknown;
}

export default function NotHere() {
    const { location } = usePage<PageProps>().props;

    const breadcrumbs: BreadcrumbItem[] = [
        { title: "Dashboard", href: "/dashboard" },
        { title: "Roles", href: "#" },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Not Here" />
            <div className="flex h-full flex-1 flex-col items-center justify-center p-4">
                <div className="text-center">
                    <MapPin className="mx-auto mb-4 h-16 w-16 text-stone-600" />
                    <h1 className="font-pixel text-2xl text-amber-400">Not at this Location</h1>
                    <p className="mt-2 font-pixel text-sm text-stone-400">
                        You must travel to {location} to view or interact with roles here.
                    </p>
                    <a
                        href="/travel"
                        className="mt-6 inline-block rounded-lg border-2 border-amber-500 bg-amber-900/30 px-6 py-2 font-pixel text-sm text-amber-300 transition hover:bg-amber-800/50"
                    >
                        Go to Travel
                    </a>
                </div>
            </div>
        </AppLayout>
    );
}
