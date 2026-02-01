import { Link, router, usePage } from "@inertiajs/react";
import { Sparkles, Trophy, Users } from "lucide-react";
import { useEffect, useState } from "react";
import { Breadcrumbs } from "@/components/breadcrumbs";
import ChangelogModal from "@/components/changelog-modal";
import { Button } from "@/components/ui/button";
import { SidebarTrigger } from "@/components/ui/sidebar";
import type { BreadcrumbItem as BreadcrumbItemType, SharedData } from "@/types";

export function AppSidebarHeader({ breadcrumbs = [] }: { breadcrumbs?: BreadcrumbItemType[] }) {
    const [showChangelog, setShowChangelog] = useState(false);
    const { changelog, online_count } = usePage<SharedData>().props;
    const hasUnread = changelog?.has_unread ?? false;

    // Poll for online count every 5 seconds
    useEffect(() => {
        const interval = setInterval(() => {
            router.reload({ only: ["online_count"] });
        }, 5000);

        return () => clearInterval(interval);
    }, []);

    return (
        <>
            <header className="flex h-16 shrink-0 items-center gap-2 border-b border-sidebar-border/50 px-6 transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 md:px-4">
                <div className="flex items-center gap-2">
                    <SidebarTrigger className="-ml-1" />
                    <Breadcrumbs breadcrumbs={breadcrumbs} />
                </div>
                <div className="ml-auto flex items-center gap-2">
                    {online_count !== undefined && online_count > 0 && (
                        <div className="flex items-center gap-1.5 rounded-md bg-green-900/30 px-2 py-1 text-xs">
                            <span className="relative flex h-2 w-2">
                                <span className="absolute inline-flex h-full w-full animate-ping rounded-full bg-green-400 opacity-75"></span>
                                <span className="relative inline-flex h-2 w-2 rounded-full bg-green-500"></span>
                            </span>
                            <Users className="size-3 text-green-400" />
                            <span className="font-pixel text-green-300">{online_count}</span>
                        </div>
                    )}
                    <Button
                        variant="ghost"
                        size="sm"
                        className="group gap-2 text-muted-foreground hover:text-foreground"
                        asChild
                    >
                        <Link href="/leaderboard">
                            <Trophy className="size-4 text-amber-400" />
                            <span className="hidden sm:inline">Leaderboard</span>
                        </Link>
                    </Button>
                    <Button
                        variant="ghost"
                        size="sm"
                        className="group gap-2 text-muted-foreground hover:text-foreground relative"
                        onClick={() => setShowChangelog(true)}
                    >
                        <span className="relative">
                            <Sparkles className="size-4 text-amber-500" />
                            {hasUnread && (
                                <span className="absolute -top-1 -right-1 flex h-2 w-2">
                                    <span className="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                                    <span className="relative inline-flex rounded-full h-2 w-2 bg-red-500"></span>
                                </span>
                            )}
                        </span>
                        <span className="hidden sm:inline">What's New {hasUnread && "(New!)"}</span>
                    </Button>
                </div>
            </header>

            {showChangelog && (
                <ChangelogModal onClose={() => setShowChangelog(false)} hasUnread={hasUnread} />
            )}
        </>
    );
}
