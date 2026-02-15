import { Link, usePage } from "@inertiajs/react";
import {
    Church,
    Crown,
    LayoutDashboard,
    Mail,
    Package,
    Scroll,
    Shield,
    ShieldAlert,
    Users,
} from "lucide-react";
import { index as adminAppeals } from "@/actions/App/Http/Controllers/Admin/AppealController";
import { index as adminDashboard } from "@/actions/App/Http/Controllers/Admin/DashboardController";
import { index as adminDynasties } from "@/actions/App/Http/Controllers/Admin/DynastyController";
import { index as adminItems } from "@/actions/App/Http/Controllers/Admin/ItemController";
import { index as adminMail } from "@/actions/App/Http/Controllers/Admin/MailController";
import { index as adminReligions } from "@/actions/App/Http/Controllers/Admin/ReligionController";
import { index as adminSuspiciousActivity } from "@/actions/App/Http/Controllers/Admin/SuspiciousActivityController";
import { index as adminUsers } from "@/actions/App/Http/Controllers/Admin/UserController";
import AppLogo from "@/components/app-logo";
import { NavUser } from "@/components/nav-user";
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarGroup,
    SidebarGroupContent,
    SidebarGroupLabel,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from "@/components/ui/sidebar";
import { dashboard } from "@/routes";

const adminNavItems = [
    {
        title: "Dashboard",
        url: adminDashboard.url(),
        icon: LayoutDashboard,
    },
    {
        title: "Users",
        url: adminUsers.url(),
        icon: Users,
    },
    {
        title: "Dynasties",
        url: adminDynasties.url(),
        icon: Crown,
    },
    {
        title: "Religions",
        url: adminReligions.url(),
        icon: Church,
    },
    {
        title: "Items",
        url: adminItems.url(),
        icon: Package,
    },
    {
        title: "Cheating",
        url: adminSuspiciousActivity.url(),
        icon: ShieldAlert,
    },
    {
        title: "Mail",
        url: adminMail.url(),
        icon: Mail,
    },
    {
        title: "Appeals",
        url: adminAppeals.url(),
        icon: Scroll,
    },
];

export function AdminSidebar() {
    const { url } = usePage();

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
                <SidebarGroup>
                    <SidebarGroupLabel className="flex items-center gap-2">
                        <Shield className="size-4" />
                        Admin Panel
                    </SidebarGroupLabel>
                    <SidebarGroupContent>
                        <SidebarMenu>
                            {adminNavItems.map((item) => (
                                <SidebarMenuItem key={item.title}>
                                    <SidebarMenuButton asChild isActive={url.startsWith(item.url)}>
                                        <Link href={item.url}>
                                            <item.icon className="size-4" />
                                            <span>{item.title}</span>
                                        </Link>
                                    </SidebarMenuButton>
                                </SidebarMenuItem>
                            ))}
                        </SidebarMenu>
                    </SidebarGroupContent>
                </SidebarGroup>
            </SidebarContent>

            <SidebarFooter>
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
