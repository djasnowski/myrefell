import { Head, usePage } from "@inertiajs/react";
import { Church } from "lucide-react";
import AppLayout from "@/layouts/app-layout";
import type { BreadcrumbItem } from "@/types";

interface PageProps {
    message: string;
    [key: string]: unknown;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: "Dashboard", href: "/dashboard" },
    { title: "Religions", href: "/religions" },
];

export default function ReligionsNotAvailable() {
    const { message } = usePage<PageProps>().props;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Religions Unavailable" />
            <div className="flex h-full flex-1 items-center justify-center p-4">
                <div className="text-center">
                    <Church className="mx-auto mb-4 h-16 w-16 text-stone-600" />
                    <h1 className="mb-2 font-pixel text-xl text-amber-400">
                        Religions Unavailable
                    </h1>
                    <p className="font-pixel text-sm text-stone-400">{message}</p>
                    <a
                        href="/dashboard"
                        className="mt-4 inline-block rounded bg-amber-600 px-4 py-2 font-pixel text-sm text-white transition hover:bg-amber-500"
                    >
                        Return to Dashboard
                    </a>
                </div>
            </div>
        </AppLayout>
    );
}
