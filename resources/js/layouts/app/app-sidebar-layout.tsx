import { AppContent } from "@/components/app-content";
import { AppShell } from "@/components/app-shell";
import { AppSidebar } from "@/components/app-sidebar";
import { AppSidebarHeader } from "@/components/app-sidebar-header";
import { ImpersonationBanner } from "@/components/impersonation-banner";
import { NotificationTest } from "@/components/notification-test";
import { GameToaster } from "@/components/ui/game-toast";
import type { AppLayoutProps } from "@/types";

export default function AppSidebarLayout({ children, breadcrumbs = [] }: AppLayoutProps) {
    return (
        <AppShell variant="sidebar">
            <AppSidebar />
            <AppContent variant="sidebar" className="!min-h-svh">
                <ImpersonationBanner />
                <AppSidebarHeader breadcrumbs={breadcrumbs} />
                {children}
            </AppContent>
            <GameToaster />
            {import.meta.env.DEV && <NotificationTest />}
        </AppShell>
    );
}
