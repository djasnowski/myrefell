import { Head, Link } from "@inertiajs/react";
import { ArrowLeft, Construction, Landmark } from "lucide-react";
import AppLayout from "@/layouts/app-layout";
import type { BreadcrumbItem } from "@/types";

interface Props {
    town: {
        id: number;
        name: string;
    };
}

export default function Treasury({ town }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: "Dashboard", href: "/dashboard" },
        { title: town.name, href: `/towns/${town.id}` },
        { title: "Treasury", href: "#" },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`${town.name} Treasury`} />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-y-auto p-4">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="font-pixel text-2xl text-amber-400">{town.name} Treasury</h1>
                        <p className="font-pixel text-sm text-stone-400">
                            Manage the town's finances
                        </p>
                    </div>
                    <Link
                        href={`/towns/${town.id}`}
                        className="flex items-center gap-2 rounded-lg border border-stone-600/50 bg-stone-800/50 px-3 py-2 font-pixel text-xs text-stone-300 transition hover:bg-stone-700/50"
                    >
                        <ArrowLeft className="h-4 w-4" />
                        Back to Town
                    </Link>
                </div>

                {/* Coming Soon Pane */}
                <div className="flex flex-1 items-center justify-center">
                    <div className="max-w-md rounded-xl border-2 border-stone-600/50 bg-stone-800/30 p-8 text-center">
                        <div className="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full border-2 border-amber-500/30 bg-amber-900/30">
                            <Construction className="h-8 w-8 text-amber-400" />
                        </div>
                        <h2 className="mb-2 font-pixel text-xl text-amber-300">Coming Soon</h2>
                        <p className="mb-4 font-pixel text-sm text-stone-400">
                            The town treasury system is still under construction. Soon mayors will
                            be able to manage town finances, set budgets, and fund public works.
                        </p>
                        <div className="flex items-center justify-center gap-2 text-stone-500">
                            <Landmark className="h-4 w-4" />
                            <span className="font-pixel text-xs">
                                The coffers are being prepared...
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
