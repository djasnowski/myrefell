import { Sparkles, Trophy } from "lucide-react";
import { useState } from "react";
import { Breadcrumbs } from "@/components/breadcrumbs";
import ChangelogModal from "@/components/changelog-modal";
import LeaderboardModal from "@/components/leaderboard-modal";
import { Button } from "@/components/ui/button";
import { SidebarTrigger } from "@/components/ui/sidebar";
import type { BreadcrumbItem as BreadcrumbItemType } from "@/types";

export function AppSidebarHeader({ breadcrumbs = [] }: { breadcrumbs?: BreadcrumbItemType[] }) {
    const [showChangelog, setShowChangelog] = useState(false);
    const [showLeaderboard, setShowLeaderboard] = useState(false);

    return (
        <>
            <header className="flex h-16 shrink-0 items-center gap-2 border-b border-sidebar-border/50 px-6 transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 md:px-4">
                <div className="flex items-center gap-2">
                    <SidebarTrigger className="-ml-1" />
                    <Breadcrumbs breadcrumbs={breadcrumbs} />
                </div>
                <div className="ml-auto flex items-center gap-2">
                    <Button
                        variant="ghost"
                        size="sm"
                        className="group gap-2 text-muted-foreground hover:text-foreground"
                        onClick={() => setShowLeaderboard(true)}
                    >
                        <Trophy className="size-4 text-amber-400" />
                        <span className="hidden sm:inline">Leaderboard</span>
                    </Button>
                    <Button
                        variant="ghost"
                        size="sm"
                        className="group gap-2 text-muted-foreground hover:text-foreground"
                        onClick={() => setShowChangelog(true)}
                    >
                        <Sparkles className="size-4 text-amber-500" />
                        <span className="hidden sm:inline">What's New</span>
                    </Button>
                </div>
            </header>

            {showChangelog && <ChangelogModal onClose={() => setShowChangelog(false)} />}
            {showLeaderboard && <LeaderboardModal onClose={() => setShowLeaderboard(false)} />}
        </>
    );
}
