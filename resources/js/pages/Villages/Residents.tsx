import { Head, Link, usePage } from "@inertiajs/react";
import { Crown, Shield, Sword, User, Users } from "lucide-react";
import AppLayout from "@/layouts/app-layout";
import type { BreadcrumbItem } from "@/types";

interface Resident {
    id: number;
    username: string;
    combat_level: number;
    gender: string;
    primary_title: string | null;
    title_tier: number | null;
}

interface Village {
    id: number;
    name: string;
}

interface PageProps {
    village: Village;
    residents: Resident[];
    count: number;
    [key: string]: unknown;
}

const titleColors: Record<number, string> = {
    1: "text-stone-400", // Peasant
    2: "text-blue-400", // Knight
    3: "text-purple-400", // Lord
    4: "text-yellow-400", // King
};

function getTitleIcon(tier: number | null) {
    switch (tier) {
        case 4:
            return <Crown className="h-4 w-4 text-yellow-400" />;
        case 3:
        case 2:
            return <Shield className="h-4 w-4 text-blue-400" />;
        default:
            return <User className="h-4 w-4 text-stone-400" />;
    }
}

export default function Residents() {
    const { village, residents, count } = usePage<PageProps>().props;

    const breadcrumbs: BreadcrumbItem[] = [
        { title: "Dashboard", href: "/dashboard" },
        { title: village.name, href: `/villages/${village.id}` },
        { title: "Residents", href: "#" },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Residents - ${village.name}`} />
            <div className="flex h-full flex-1 flex-col p-4">
                {/* Header */}
                <div className="mb-6 flex items-center gap-3">
                    <div className="rounded-lg bg-stone-700/50 p-3">
                        <Users className="h-8 w-8 text-stone-300" />
                    </div>
                    <div>
                        <h1 className="font-pixel text-2xl text-amber-400">Residents</h1>
                        <p className="font-pixel text-xs text-stone-400">
                            {count} {count === 1 ? "person" : "people"} living in {village.name}
                        </p>
                    </div>
                </div>

                {/* Residents List */}
                <div className="w-full">
                    {residents.length > 0 ? (
                        <div className="rounded-xl border-2 border-stone-700 bg-stone-800/50 p-4">
                            <div className="space-y-2">
                                {residents.map((resident) => {
                                    const titleColor =
                                        titleColors[resident.title_tier || 1] || "text-stone-400";
                                    const titleIcon = getTitleIcon(resident.title_tier);

                                    return (
                                        <div
                                            key={resident.id}
                                            className="flex items-center justify-between rounded-lg bg-stone-900/50 px-4 py-3 transition hover:bg-stone-900/70"
                                        >
                                            <div className="flex items-center gap-3">
                                                {titleIcon}
                                                <div>
                                                    <div className="font-pixel text-sm text-stone-200">
                                                        {resident.username}
                                                    </div>
                                                    {resident.primary_title && (
                                                        <div
                                                            className={`font-pixel text-[10px] capitalize ${titleColor}`}
                                                        >
                                                            {resident.primary_title}
                                                        </div>
                                                    )}
                                                </div>
                                            </div>
                                            <div className="flex items-center gap-2">
                                                <Sword className="h-3 w-3 text-stone-500" />
                                                <span className="font-pixel text-xs text-stone-400">
                                                    Lvl {resident.combat_level}
                                                </span>
                                            </div>
                                        </div>
                                    );
                                })}
                            </div>
                        </div>
                    ) : (
                        <div className="rounded-xl border-2 border-stone-700 bg-stone-800/50 p-8 text-center">
                            <Users className="mx-auto mb-3 h-12 w-12 text-stone-600" />
                            <p className="font-pixel text-sm text-stone-500">No residents yet</p>
                            <p className="font-pixel text-xs text-stone-600">
                                This village is empty
                            </p>
                        </div>
                    )}

                    {/* Back Link */}
                    <div className="mt-6 text-center">
                        <Link
                            href={`/villages/${village.id}`}
                            className="font-pixel text-xs text-stone-500 transition hover:text-amber-400"
                        >
                            Back to {village.name}
                        </Link>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
