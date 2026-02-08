import { Link, usePage } from "@inertiajs/react";
import { Calendar, Sparkles } from "lucide-react";
import { NavAdminControls } from "@/components/nav-admin-controls";
import { NavCalendar } from "@/components/nav-calendar";
import { NavLocation } from "@/components/nav-location";
import { NavPlayerInfo } from "@/components/nav-player-info";
import { NavSkills } from "@/components/nav-skills";
import { NavUser } from "@/components/nav-user";
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarSeparator,
    useSidebar,
} from "@/components/ui/sidebar";
import { useChangelog } from "@/contexts/changelog-context";
import { dashboard } from "@/routes";
import AppLogo from "./app-logo";

export function AppSidebar() {
    const { hasUnread, openChangelog } = useChangelog();
    const { setOpenMobile } = useSidebar();

    const handleOpenChangelog = () => {
        setOpenMobile(false); // Close sidebar on mobile
        openChangelog();
    };

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboard()} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                {/* Player Info Card */}
                <div className="px-2 py-2">
                    <NavPlayerInfo />
                </div>

                <SidebarSeparator />

                {/* Calendar / World Time */}
                <div className="px-2 py-1">
                    <NavCalendar />
                </div>

                <SidebarSeparator />

                {/* Location-aware Navigation */}
                <NavLocation />
            </SidebarContent>

            <SidebarFooter>
                {/* Admin controls (dan only) */}
                <div className="px-2">
                    <NavAdminControls />
                </div>

                {/* Skills at bottom */}
                <div className="px-2 pb-2">
                    <NavSkills />
                </div>

                {/* Changelog - mobile only */}
                <div className="px-2 pb-2 sm:hidden">
                    <button
                        onClick={handleOpenChangelog}
                        className="flex w-full items-center gap-2 rounded-lg border border-amber-600/30 bg-amber-900/20 px-3 py-2 text-left transition hover:bg-amber-900/30"
                    >
                        <span className="relative">
                            <Sparkles className="h-4 w-4 text-amber-500" />
                            {hasUnread && (
                                <span className="absolute -top-1 -right-1 flex h-2 w-2">
                                    <span className="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                                    <span className="relative inline-flex rounded-full h-2 w-2 bg-red-500"></span>
                                </span>
                            )}
                        </span>
                        <span className="font-pixel text-xs text-amber-300">
                            Changelog {hasUnread && "(New!)"}
                        </span>
                    </button>
                </div>

                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
