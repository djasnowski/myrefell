import { Head, Link, usePage } from "@inertiajs/react";
import { Calendar, Gavel, Scale, User } from "lucide-react";
import { Button } from "@/components/ui/button";
import AppLayout from "@/layouts/app-layout";
import type { BreadcrumbItem } from "@/types";

interface Trial {
    id: number;
    defendant: string;
    crime: string;
    crime_description: string;
    court: string;
    status: string;
    scheduled_at: string | null;
    prosecution_argument: string | null;
    defense_argument: string | null;
}

interface PageProps {
    trials: Trial[];
    [key: string]: unknown;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: "Dashboard", href: "/dashboard" },
    { title: "Crime", href: "/crime" },
    { title: "Pending Trials", href: "#" },
];

const statusColors: Record<string, string> = {
    scheduled: "bg-blue-900/30 text-blue-400",
    in_progress: "bg-amber-900/30 text-amber-400",
    awaiting_verdict: "bg-purple-900/30 text-purple-400",
};

export default function PendingTrials() {
    const { trials } = usePage<PageProps>().props;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Pending Trials" />

            <div className="space-y-6 p-6">
                {/* Header */}
                <div className="flex items-center gap-3">
                    <div className="rounded-lg bg-purple-900/30 p-2">
                        <Scale className="size-6 text-purple-400" />
                    </div>
                    <div>
                        <h1 className="font-[Cinzel] text-2xl font-bold text-stone-100">
                            Pending Trials
                        </h1>
                        <p className="text-sm text-stone-400">Trials awaiting your verdict</p>
                    </div>
                </div>

                {trials.length === 0 ? (
                    <div className="rounded-xl border border-stone-800 bg-stone-900/50 p-8 text-center">
                        <Scale className="mx-auto size-12 text-stone-600" />
                        <p className="mt-4 text-stone-400">No pending trials</p>
                    </div>
                ) : (
                    <div className="space-y-4">
                        {trials.map((trial) => (
                            <div
                                key={trial.id}
                                className="rounded-xl border border-stone-800 bg-stone-900/50 p-4"
                            >
                                <div className="flex items-start justify-between">
                                    <div>
                                        <div className="flex items-center gap-2">
                                            <User className="size-4 text-stone-500" />
                                            <span className="font-semibold text-stone-100">
                                                {trial.defendant}
                                            </span>
                                            <span className="text-stone-600">charged with</span>
                                            <span className="rounded bg-red-900/30 px-2 py-0.5 text-xs text-red-400">
                                                {trial.crime}
                                            </span>
                                        </div>
                                        <div className="mt-1 flex items-center gap-3 text-xs">
                                            <span className="flex items-center gap-1 text-stone-500">
                                                <Gavel className="size-3" />
                                                {trial.court}
                                            </span>
                                            {trial.scheduled_at && (
                                                <span className="flex items-center gap-1 text-stone-500">
                                                    <Calendar className="size-3" />
                                                    {trial.scheduled_at}
                                                </span>
                                            )}
                                            <span
                                                className={`rounded px-2 py-0.5 ${statusColors[trial.status] || "bg-stone-800 text-stone-400"}`}
                                            >
                                                {trial.status.replace("_", " ")}
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                {trial.crime_description && (
                                    <p className="mt-3 text-sm text-stone-400">
                                        {trial.crime_description}
                                    </p>
                                )}

                                <div className="mt-4 grid gap-3 md:grid-cols-2">
                                    <div className="rounded bg-stone-900/50 p-3">
                                        <p className="text-xs font-medium text-stone-500">
                                            Prosecution Argument
                                        </p>
                                        <p className="mt-1 text-sm text-stone-300">
                                            {trial.prosecution_argument || "Not yet submitted"}
                                        </p>
                                    </div>
                                    <div className="rounded bg-stone-900/50 p-3">
                                        <p className="text-xs font-medium text-stone-500">
                                            Defense Argument
                                        </p>
                                        <p className="mt-1 text-sm text-stone-300">
                                            {trial.defense_argument || "Not yet submitted"}
                                        </p>
                                    </div>
                                </div>

                                <div className="mt-4 flex justify-end">
                                    <Link href={`/crime/trials/${trial.id}`}>
                                        <Button size="sm">
                                            <Gavel className="size-4" />
                                            Review Trial
                                        </Button>
                                    </Link>
                                </div>
                            </div>
                        ))}
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
