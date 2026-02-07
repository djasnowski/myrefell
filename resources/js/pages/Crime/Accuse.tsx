import { Head, Link } from "@inertiajs/react";
import { ArrowLeft, Construction, Gavel } from "lucide-react";
import AppLayout from "@/layouts/app-layout";
import type { BreadcrumbItem } from "@/types";

const breadcrumbs: BreadcrumbItem[] = [
    { title: "Dashboard", href: "/dashboard" },
    { title: "Criminal Record", href: "/crime" },
    { title: "File Accusation", href: "#" },
];

export default function Accuse() {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="File an Accusation" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-y-auto p-4">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="font-pixel text-2xl text-amber-400">File an Accusation</h1>
                        <p className="font-pixel text-sm text-stone-400">
                            Report a crime to the local authorities
                        </p>
                    </div>
                    <Link
                        href="/crime"
                        className="flex items-center gap-2 rounded-lg border border-stone-600/50 bg-stone-800/50 px-3 py-2 font-pixel text-xs text-stone-300 transition hover:bg-stone-700/50"
                    >
                        <ArrowLeft className="h-4 w-4" />
                        Back to Record
                    </Link>
                </div>

                {/* Coming Soon Pane */}
                <div className="flex flex-1 items-center justify-center">
                    <div className="rounded-xl border-2 border-stone-600/50 bg-stone-800/30 p-8 text-center max-w-md">
                        <div className="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-amber-900/30 border-2 border-amber-500/30">
                            <Construction className="h-8 w-8 text-amber-400" />
                        </div>
                        <h2 className="font-pixel text-xl text-amber-300 mb-2">Coming Soon</h2>
                        <p className="font-pixel text-sm text-stone-400 mb-4">
                            The criminal justice system is still under construction. Soon you'll be
                            able to file accusations against other players for their crimes.
                        </p>
                        <div className="flex items-center justify-center gap-2 text-stone-500">
                            <Gavel className="h-4 w-4" />
                            <span className="font-pixel text-xs">
                                Courts will open their doors soon...
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
