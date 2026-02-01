import { Head, usePage } from "@inertiajs/react";
import { FlaskConical } from "lucide-react";
import AppLayout from "@/layouts/app-layout";
import type { BreadcrumbItem } from "@/types";

interface PageProps {
    message: string;
    [key: string]: unknown;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: "Dashboard", href: "/dashboard" },
    { title: "Apothecary", href: "#" },
];

export default function ApothecaryNotAvailable() {
    const { message } = usePage<PageProps>().props;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Apothecary Not Available" />
            <div className="flex h-full flex-1 items-center justify-center p-4">
                <div className="text-center">
                    <div className="mb-4 flex justify-center">
                        <div className="rounded-full bg-stone-800 p-6">
                            <FlaskConical className="h-16 w-16 text-stone-600" />
                        </div>
                    </div>
                    <h1 className="mb-2 font-pixel text-xl text-stone-400">
                        Apothecary Unavailable
                    </h1>
                    <p className="font-pixel text-sm text-stone-500">{message}</p>
                </div>
            </div>
        </AppLayout>
    );
}
