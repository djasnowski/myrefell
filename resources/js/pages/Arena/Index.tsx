import { Head, usePage } from "@inertiajs/react";
import { Construction, Crosshair, Swords, Target, Trophy } from "lucide-react";
import AppLayout from "@/layouts/app-layout";
import type { BreadcrumbItem } from "@/types";

interface PageProps {
    location: {
        id: number;
        name: string;
        type: string;
    };
    player: {
        id: number;
        username: string;
        gold: number;
        energy: number;
        max_energy: number;
    };
    [key: string]: unknown;
}

export default function ArenaIndex() {
    const { location, player } = usePage<PageProps>().props;

    const breadcrumbs: BreadcrumbItem[] = [
        { title: "Dashboard", href: "/dashboard" },
        { title: location.name, href: `/${location.type}s/${location.id}` },
        { title: "Arena", href: "#" },
    ];

    const games = [
        {
            id: "archery",
            name: "Archery",
            description: "Test your aim with bow and arrow",
            icon: Target,
            color: "amber",
            available: false,
            comingSoon: true,
        },
        {
            id: "combat",
            name: "Combat Tournament",
            description: "Fight other players for glory",
            icon: Swords,
            color: "red",
            available: false,
            comingSoon: true,
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Arena - ${location.name}`} />
            <div className="flex h-full flex-1 flex-col gap-6 overflow-y-auto p-4">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-3">
                        <div className="rounded-lg border border-amber-600/50 bg-amber-900/30 p-3">
                            <Target className="h-8 w-8 text-amber-400" />
                        </div>
                        <div>
                            <h1 className="font-pixel text-2xl text-amber-300">Arena</h1>
                            <p className="text-sm text-stone-400">
                                Compete in games of skill at {location.name}
                            </p>
                        </div>
                    </div>
                    <div className="flex items-center gap-4">
                        <div className="flex items-center gap-2 rounded-lg border border-stone-600/50 bg-stone-800/50 px-3 py-2">
                            <Trophy className="h-4 w-4 text-amber-400" />
                            <span className="font-pixel text-sm text-amber-300">0 Wins</span>
                        </div>
                    </div>
                </div>

                {/* Games Grid */}
                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    {games.map((game) => {
                        const Icon = game.icon;
                        return (
                            <div
                                key={game.id}
                                className={`relative rounded-xl border-2 p-6 transition ${
                                    game.available
                                        ? `border-${game.color}-600/50 bg-${game.color}-900/20 hover:bg-${game.color}-900/30 cursor-pointer`
                                        : "border-stone-600/30 bg-stone-800/30 opacity-60"
                                }`}
                            >
                                {game.comingSoon && (
                                    <div className="absolute -top-2 -right-2 flex items-center gap-1 rounded-full border border-amber-500/50 bg-amber-900/80 px-2 py-0.5">
                                        <Construction className="h-3 w-3 text-amber-400" />
                                        <span className="font-pixel text-[10px] text-amber-300">
                                            Coming Soon
                                        </span>
                                    </div>
                                )}
                                <div className="flex items-start gap-4">
                                    <div
                                        className={`rounded-lg border p-3 ${
                                            game.available
                                                ? `border-${game.color}-600/50 bg-${game.color}-900/30`
                                                : "border-stone-600/50 bg-stone-800/50"
                                        }`}
                                    >
                                        <Icon
                                            className={`h-8 w-8 ${
                                                game.available
                                                    ? `text-${game.color}-400`
                                                    : "text-stone-500"
                                            }`}
                                        />
                                    </div>
                                    <div className="flex-1">
                                        <h3
                                            className={`font-pixel text-lg ${
                                                game.available
                                                    ? `text-${game.color}-300`
                                                    : "text-stone-400"
                                            }`}
                                        >
                                            {game.name}
                                        </h3>
                                        <p className="mt-1 text-sm text-stone-500">
                                            {game.description}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        );
                    })}
                </div>

                {/* Placeholder Content */}
                <div className="flex flex-1 flex-col items-center justify-center rounded-xl border-2 border-dashed border-stone-600/50 bg-stone-800/20 p-12 text-center">
                    <div className="rounded-full border-2 border-stone-600/50 bg-stone-800/50 p-6">
                        <Crosshair className="h-16 w-16 text-stone-500" />
                    </div>
                    <h2 className="mt-6 font-pixel text-xl text-stone-400">Arena Coming Soon</h2>
                    <p className="mt-2 max-w-md text-stone-500">
                        The arena is being constructed. Soon you'll be able to test your skills in
                        archery competitions and combat tournaments!
                    </p>
                </div>
            </div>
        </AppLayout>
    );
}
