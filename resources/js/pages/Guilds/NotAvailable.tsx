import { Head, usePage } from "@inertiajs/react";
import { AlertCircle, ArrowLeft } from "lucide-react";
import AppLayout from "@/layouts/app-layout";
import type { BreadcrumbItem } from "@/types";

interface PageProps {
    message: string;
    [key: string]: unknown;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: "Dashboard", href: "/dashboard" },
    { title: "Guilds", href: "/guilds" },
];

export default function NotAvailable() {
    const { message } = usePage<PageProps>().props;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Guilds Unavailable" />
            <div className="flex h-full flex-1 flex-col items-center justify-center gap-6 p-4">
                <div className="text-center">
                    <AlertCircle className="mx-auto mb-4 h-16 w-16 text-amber-500" />
                    <h1 className="font-pixel text-2xl text-amber-400">Guilds Unavailable</h1>
                    <p className="mt-2 font-pixel text-sm text-stone-400">{message}</p>
                </div>

                <a
                    href="/dashboard"
                    className="flex items-center gap-2 rounded bg-stone-700 px-4 py-2 font-pixel text-sm text-stone-300 transition hover:bg-stone-600"
                >
                    <ArrowLeft className="h-4 w-4" />
                    Return to Dashboard
                </a>
            </div>
        </AppLayout>
    );
}
