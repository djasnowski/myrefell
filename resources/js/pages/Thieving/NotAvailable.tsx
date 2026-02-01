import { Head, Link } from "@inertiajs/react";
import { Hand, MapPin } from "lucide-react";
import AppLayout from "@/layouts/app-layout";
import type { BreadcrumbItem } from "@/types";

interface Props {
    message: string;
}

export default function ThievingNotAvailable({ message }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: "Dashboard", href: "/dashboard" },
        { title: "Thieving", href: "#" },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Thieving Unavailable" />
            <div className="flex h-full flex-1 flex-col items-center justify-center p-8">
                <div className="rounded-xl border-2 border-stone-700 bg-stone-800/50 p-8 text-center">
                    <Hand className="mx-auto mb-4 h-16 w-16 text-stone-600" />
                    <h1 className="mb-2 font-pixel text-2xl text-stone-400">
                        No Targets Available
                    </h1>
                    <p className="mb-6 text-stone-500">{message}</p>
                    <Link
                        href="/dashboard"
                        className="inline-flex items-center gap-2 rounded-lg border-2 border-stone-600 bg-stone-700 px-6 py-3 font-pixel text-sm text-stone-300 transition hover:bg-stone-600"
                    >
                        <MapPin className="h-4 w-4" />
                        Return to Dashboard
                    </Link>
                </div>
            </div>
        </AppLayout>
    );
}
