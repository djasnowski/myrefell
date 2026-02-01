import { Head, Link } from "@inertiajs/react";
import { Footprints } from "lucide-react";
import AppLayout from "@/layouts/app-layout";
import type { BreadcrumbItem } from "@/types";

interface PageProps {
    message: string;
}

export default function AgilityNotAvailable({ message }: PageProps) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: "Dashboard", href: "/dashboard" },
        { title: "Agility Course", href: "#" },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Agility Course" />
            <div className="flex h-full flex-1 flex-col items-center justify-center p-4">
                <div className="max-w-md text-center">
                    <div className="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-stone-800">
                        <Footprints className="h-8 w-8 text-stone-500" />
                    </div>
                    <h1 className="mb-2 font-pixel text-xl text-stone-300">
                        Agility Course Not Available
                    </h1>
                    <p className="mb-6 font-pixel text-xs text-stone-500">{message}</p>
                    <Link
                        href="/dashboard"
                        className="inline-block rounded-lg border border-stone-600 bg-stone-800 px-4 py-2 font-pixel text-xs text-stone-300 transition hover:bg-stone-700"
                    >
                        Return to Dashboard
                    </Link>
                </div>
            </div>
        </AppLayout>
    );
}
