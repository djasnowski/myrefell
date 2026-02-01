import { AppContent } from "@/components/app-content";
import { AppShell } from "@/components/app-shell";
import { AppSidebar } from "@/components/app-sidebar";
import { AppSidebarHeader } from "@/components/app-sidebar-header";
import { GameToaster } from "@/components/ui/game-toast";
import type { AppLayoutProps } from "@/types";

export default function AppSidebarLayout({ children, breadcrumbs = [] }: AppLayoutProps) {
    return (
        <AppShell variant="sidebar">
            <AppSidebar />
            <AppContent variant="sidebar" className="!min-h-svh">
                <AppSidebarHeader breadcrumbs={breadcrumbs} />
                {children}
            </AppContent>
            <GameToaster />
        </AppShell>
    );
}
