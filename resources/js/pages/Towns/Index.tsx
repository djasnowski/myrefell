import { Head } from "@inertiajs/react";
import { Castle } from "lucide-react";
import { Card, CardContent } from "@/components/ui/card";
import AppLayout from "@/layouts/app-layout";
import type { BreadcrumbItem } from "@/types";

const breadcrumbs: BreadcrumbItem[] = [
    { title: "Dashboard", href: "/dashboard" },
    { title: "Towns", href: "/towns" },
];

export default function TownsIndex() {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Towns" />
            <div className="flex flex-col gap-6 p-6">
                <div className="flex min-h-[60vh] items-center justify-center">
                    <Card className="max-w-2xl border-amber-900/30 bg-amber-950/10">
                        <CardContent className="p-8 text-center">
                            <Castle className="mx-auto mb-6 h-16 w-16 text-amber-600" />
                            <h1 className="mb-6 font-serif text-2xl font-bold italic text-amber-200">
                                A Traveler's Lament
                            </h1>
                            <blockquote className="space-y-4 font-serif text-lg leading-relaxed text-muted-foreground italic">
                                <p>
                                    So many towns to explore,
                                    <br />
                                    yet so little time remains...
                                </p>
                                <p>
                                    'Tis a cruel medieval world,
                                    <br />
                                    where pestilence and war
                                    <br />
                                    claim the weary traveler
                                    <br />
                                    before their journey's end.
                                </p>
                            </blockquote>
                            <p className="mt-8 text-sm text-muted-foreground">
                                Visit towns through baronies or the world map.
                            </p>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}
