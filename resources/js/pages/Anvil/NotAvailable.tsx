import { Head, usePage } from "@inertiajs/react";
import { Anvil } from "lucide-react";
import AppLayout from "@/layouts/app-layout";

interface PageProps {
    message: string;
    [key: string]: unknown;
}

export default function AnvilNotAvailable() {
    const { message } = usePage<PageProps>().props;

    return (
        <AppLayout
            breadcrumbs={[
                { title: "Dashboard", href: "/dashboard" },
                { title: "Anvil", href: "/anvil" },
            ]}
        >
            <Head title="Anvil Not Available" />
            <div className="flex h-full flex-1 flex-col items-center justify-center p-4">
                <div className="rounded-lg bg-stone-800/50 p-8 text-center">
                    <Anvil className="mx-auto h-16 w-16 text-stone-600" />
                    <h1 className="mt-4 font-pixel text-xl text-stone-400">Anvil Not Available</h1>
                    <p className="mt-2 font-pixel text-sm text-stone-500">{message}</p>
                </div>
            </div>
        </AppLayout>
    );
}
