import { Link } from "@inertiajs/react";
import { NavAdminControls } from "@/components/nav-admin-controls";
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
} from "@/components/ui/sidebar";
import { dashboard } from "@/routes";
import AppLogo from "./app-logo";

export function AppSidebar() {
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
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
