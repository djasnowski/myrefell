import { Head, Link, router, usePage } from "@inertiajs/react";
import { formatDistanceToNow } from "date-fns";
import {
    Activity,
    ArrowLeft,
    Backpack,
    Ban,
    Briefcase,
    Calendar,
    CheckCircle,
    Church,
    Clock,
    Coins,
    Crown,
    Edit,
    Globe,
    Heart,
    History,
    Mail,
    MailCheck,
    MapPin,
    Scroll,
    Shield,
    ShieldOff,
    Sparkles,
    Swords,
    User,
    UserX,
    Users,
    Zap,
} from "lucide-react";
import { useState } from "react";
import {
    edit as editUser,
    index as usersIndex,
} from "@/actions/App/Http/Controllers/Admin/UserController";
import { ActivityTable } from "@/components/admin/activity-table";
import { EquipmentSlots } from "@/components/admin/equipment-slots";
import { InventoryGrid } from "@/components/admin/inventory-grid";
import { SkillCard } from "@/components/admin/skill-card";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Textarea } from "@/components/ui/textarea";
import AdminLayout from "@/layouts/admin-layout";
import { cn } from "@/lib/utils";
import type { BreadcrumbItem, SharedData } from "@/types";

interface Ban {
    id: number;
    reason: string;
    banned_at: string;
    banned_by: { id: number; username: string } | null;
    unbanned_at: string | null;
    unbanned_by: { id: number; username: string } | null;
    unban_reason: string | null;
    is_active: boolean;
}

interface UserData {
    id: number;
    username: string;
    email: string;
    is_admin: boolean;
    is_banned: boolean;
    banned_at: string | null;
    created_at: string;
    email_verified_at: string | null;
    registration_ip: string | null;
    last_login_ip: string | null;
    last_login_at: string | null;
    gender: string | null;
    social_class: string | null;
    gold: number;
    hp: number;
    max_hp: number;
    energy: number;
    max_energy: number;
    primary_title: string | null;
    title_tier: number | null;
    combat_level: number;
    home_village: { id: number; name: string } | null;
    bans: Ban[];
}

interface Skill {
    skill_name: string;
    level: number;
    xp: number;
    progress: number;
    is_combat: boolean;
}

interface InventoryItem {
    id: number;
    slot_number: number;
    quantity: number;
    is_equipped: boolean;
    item: {
        id: number;
        name: string;
        type: string;
        rarity: string;
        equipment_slot: string | null;
        atk_bonus: number | null;
        str_bonus: number | null;
        def_bonus: number | null;
    } | null;
}

interface Title {
    id: number;
    title: string;
    tier: number;
    is_active: boolean;
    domain_type: string | null;
    legitimacy: number;
    granted_at: string | null;
    revoked_at: string | null;
}

interface Role {
    id: number;
    role_name: string | null;
    location_type: string;
    location_name: string;
    status: string;
    legitimacy: number;
    appointed_at: string | null;
}

interface Dynasty {
    id: number;
    dynasty_name: string | null;
    dynasty_id: number;
    first_name: string;
    generation: number;
    is_heir: boolean;
    is_legitimate: boolean;
    status: string;
}

interface Religion {
    id: number;
    religion_name: string | null;
    religion_id: number;
    rank: string;
    devotion: number;
    joined_at: string | null;
}

interface Employment {
    id: number;
    employer_type: string;
    status: string;
    hired_at: string | null;
    total_earnings: number;
}

interface PlayerHorse {
    id: number;
    name: string;
    horse_type: string | null;
    health: number;
    stamina: number;
    max_stamina: number;
    is_stabled: boolean;
}

interface Disease {
    id: number;
    disease_name: string | null;
    severity: string;
    infected_at: string | null;
    cured_at: string | null;
}

interface ActivityItem {
    id: number;
    activity_type: string;
    activity_subtype: string | null;
    description: string;
    location_type: string;
    created_at: string;
}

interface BankAccount {
    id: number;
    balance: number;
    account_type: string;
}

interface Props {
    user: UserData;
    skills: Skill[];
    inventory: InventoryItem[];
    titles: Title[];
    roles: Role[];
    dynasty: Dynasty | null;
    religion: Religion | null;
    employment: Employment[];
    horse: PlayerHorse | null;
    diseases: Disease[];
    activities: ActivityItem[];
    bankAccounts: BankAccount[];
}

type TabType =
    | "overview"
    | "skills"
    | "inventory"
    | "titles"
    | "dynasty"
    | "religion"
    | "employment"
    | "activity"
    | "admin";

const tabs: { id: TabType; label: string; icon: typeof User }[] = [
    { id: "overview", label: "Overview", icon: User },
    { id: "skills", label: "Skills", icon: Swords },
    { id: "inventory", label: "Inventory", icon: Backpack },
    { id: "titles", label: "Titles & Roles", icon: Crown },
    { id: "dynasty", label: "Dynasty", icon: Users },
    { id: "religion", label: "Religion", icon: Church },
    { id: "employment", label: "Employment", icon: Briefcase },
    { id: "activity", label: "Activity Log", icon: Activity },
    { id: "admin", label: "Admin Actions", icon: Shield },
];

export default function Show({
    user,
    skills,
    inventory,
    titles,
    roles,
    dynasty,
    religion,
    employment,
    horse,
    diseases,
    activities,
    bankAccounts,
}: Props) {
    const { auth } = usePage<SharedData>().props;
    const [activeTab, setActiveTab] = useState<TabType>("overview");
    const [banReason, setBanReason] = useState("");
    const [unbanReason, setUnbanReason] = useState("");
    const [showBanForm, setShowBanForm] = useState(false);
    const [showUnbanForm, setShowUnbanForm] = useState(false);
    const [loading, setLoading] = useState(false);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: "Admin", href: "/admin" },
        { title: "Users", href: "/admin/users" },
        { title: user.username, href: `/admin/users/${user.id}` },
    ];

    const formatDateTime = (dateStr: string) => {
        return new Date(dateStr).toLocaleString("en-US", {
            year: "numeric",
            month: "short",
            day: "numeric",
            hour: "2-digit",
            minute: "2-digit",
        });
    };

    const canBan = !user.is_admin && !user.is_banned && auth.user.id !== user.id;
    const canUnban = user.is_banned;

    const handleBan = () => {
        if (!banReason.trim()) return;
        setLoading(true);
        router.post(
            `/admin/users/${user.id}/ban`,
            { reason: banReason },
            {
                preserveScroll: true,
                onSuccess: () => {
                    router.reload();
                    setBanReason("");
                    setShowBanForm(false);
                },
                onFinish: () => setLoading(false),
            },
        );
    };

    const handleUnban = () => {
        setLoading(true);
        router.post(
            `/admin/users/${user.id}/unban`,
            { reason: unbanReason },
            {
                preserveScroll: true,
                onSuccess: () => {
                    router.reload();
                    setUnbanReason("");
                    setShowUnbanForm(false);
                },
                onFinish: () => setLoading(false),
            },
        );
    };

    const combatSkills = skills.filter((s) => s.is_combat);
    const gatheringSkills = skills.filter(
        (s) =>
            !s.is_combat && ["mining", "woodcutting", "fishing", "farming"].includes(s.skill_name),
    );
    const craftingSkills = skills.filter(
        (s) => !s.is_combat && ["cooking", "smithing", "crafting"].includes(s.skill_name),
    );

    const activeTitles = titles.filter((t) => t.is_active);
    const activeRoles = roles.filter((r) => r.status === "active");

    return (
        <AdminLayout breadcrumbs={breadcrumbs}>
            <Head title={`User: ${user.username}`} />

            <div className="space-y-6 p-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-4">
                        <Link href={usersIndex.url()}>
                            <Button variant="ghost" size="sm">
                                <ArrowLeft className="size-4" />
                                Back
                            </Button>
                        </Link>
                        <div className="flex items-center gap-3">
                            <div className="rounded-lg bg-blue-900/30 p-2">
                                <User className="size-6 text-blue-400" />
                            </div>
                            <div>
                                <div className="flex items-center gap-2">
                                    <h1 className="font-[Cinzel] text-2xl font-bold text-stone-100">
                                        {user.username}
                                    </h1>
                                    {user.is_admin && (
                                        <Badge
                                            variant="secondary"
                                            className="bg-purple-900/30 text-purple-400"
                                        >
                                            <Shield className="size-3" />
                                            Admin
                                        </Badge>
                                    )}
                                    {user.is_banned && (
                                        <Badge
                                            variant="destructive"
                                            className="bg-red-900/30 text-red-400"
                                        >
                                            <UserX className="size-3" />
                                            Banned
                                        </Badge>
                                    )}
                                </div>
                                <p className="text-sm text-stone-400">{user.email}</p>
                            </div>
                        </div>
                    </div>
                    <div className="flex items-center gap-3">
                        <div className="flex items-center gap-2 rounded-md bg-stone-800 px-3 py-1">
                            <Swords className="size-4 text-amber-400" />
                            <span className="text-sm font-medium text-stone-300">
                                Combat Lv. {user.combat_level}
                            </span>
                        </div>
                        <Link href={editUser.url(user.id)}>
                            <Button variant="outline" className="border-stone-700">
                                <Edit className="size-4" />
                                Edit
                            </Button>
                        </Link>
                    </div>
                </div>

                {/* Tabs */}
                <div className="flex gap-1 overflow-x-auto rounded-lg border border-stone-800 bg-stone-900/50 p-1">
                    {tabs.map((tab) => {
                        const Icon = tab.icon;
                        return (
                            <button
                                key={tab.id}
                                onClick={() => setActiveTab(tab.id)}
                                className={cn(
                                    "flex items-center gap-2 rounded-md px-4 py-2 text-sm font-medium transition-colors",
                                    activeTab === tab.id
                                        ? "bg-stone-800 text-stone-100"
                                        : "text-stone-400 hover:bg-stone-800/50 hover:text-stone-300",
                                )}
                            >
                                <Icon className="size-4" />
                                {tab.label}
                            </button>
                        );
                    })}
                </div>

                {/* Tab Content */}
                <div className="min-h-[400px]">
                    {/* Overview Tab */}
                    {activeTab === "overview" && (
                        <div className="grid gap-6 lg:grid-cols-3">
                            <div className="space-y-6 lg:col-span-2">
                                {/* Account Info */}
                                <Card className="border-stone-800 bg-stone-900/50">
                                    <CardHeader>
                                        <CardTitle className="text-stone-100">
                                            Account Information
                                        </CardTitle>
                                    </CardHeader>
                                    <CardContent>
                                        <dl className="grid gap-4 sm:grid-cols-2">
                                            <div>
                                                <dt className="flex items-center gap-2 text-sm text-stone-400">
                                                    <Mail className="size-4" />
                                                    Email
                                                </dt>
                                                <dd className="mt-1 text-stone-100">
                                                    {user.email}
                                                </dd>
                                            </div>
                                            <div>
                                                <dt className="flex items-center gap-2 text-sm text-stone-400">
                                                    <MailCheck className="size-4" />
                                                    Email Verified
                                                </dt>
                                                <dd className="mt-1 text-stone-100">
                                                    {user.email_verified_at
                                                        ? formatDateTime(user.email_verified_at)
                                                        : "Not verified"}
                                                </dd>
                                            </div>
                                            <div>
                                                <dt className="flex items-center gap-2 text-sm text-stone-400">
                                                    <Calendar className="size-4" />
                                                    Joined
                                                </dt>
                                                <dd className="mt-1 text-stone-100">
                                                    {formatDateTime(user.created_at)}
                                                </dd>
                                            </div>
                                            <div>
                                                <dt className="flex items-center gap-2 text-sm text-stone-400">
                                                    <Crown className="size-4" />
                                                    Title
                                                </dt>
                                                <dd className="mt-1 capitalize text-stone-100">
                                                    {user.primary_title || "Peasant"}
                                                </dd>
                                            </div>
                                            <div>
                                                <dt className="flex items-center gap-2 text-sm text-stone-400">
                                                    <Globe className="size-4" />
                                                    Registration IP
                                                </dt>
                                                <dd className="mt-1 font-mono text-sm text-stone-100">
                                                    {user.registration_ip || "Not recorded"}
                                                </dd>
                                            </div>
                                            <div>
                                                <dt className="flex items-center gap-2 text-sm text-stone-400">
                                                    <Globe className="size-4" />
                                                    Last Login IP
                                                </dt>
                                                <dd className="mt-1 font-mono text-sm text-stone-100">
                                                    {user.last_login_ip || "Never logged in"}
                                                </dd>
                                            </div>
                                            <div className="sm:col-span-2">
                                                <dt className="flex items-center gap-2 text-sm text-stone-400">
                                                    <Clock className="size-4" />
                                                    Last Login
                                                </dt>
                                                <dd className="mt-1 text-stone-100">
                                                    {user.last_login_at
                                                        ? formatDateTime(user.last_login_at)
                                                        : "Never logged in"}
                                                </dd>
                                            </div>
                                        </dl>
                                    </CardContent>
                                </Card>

                                {/* Game Stats */}
                                <Card className="border-stone-800 bg-stone-900/50">
                                    <CardHeader>
                                        <CardTitle className="text-stone-100">Game Stats</CardTitle>
                                    </CardHeader>
                                    <CardContent>
                                        <dl className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                                            <div className="rounded-lg border border-stone-800 bg-stone-900/30 p-3">
                                                <dt className="flex items-center gap-2 text-sm text-stone-400">
                                                    <Heart className="size-4 text-red-400" />
                                                    Health
                                                </dt>
                                                <dd className="mt-1 text-lg font-semibold text-stone-100">
                                                    {user.hp}/{user.max_hp}
                                                </dd>
                                            </div>
                                            <div className="rounded-lg border border-stone-800 bg-stone-900/30 p-3">
                                                <dt className="flex items-center gap-2 text-sm text-stone-400">
                                                    <Zap className="size-4 text-blue-400" />
                                                    Energy
                                                </dt>
                                                <dd className="mt-1 text-lg font-semibold text-stone-100">
                                                    {user.energy}/{user.max_energy}
                                                </dd>
                                            </div>
                                            <div className="rounded-lg border border-stone-800 bg-stone-900/30 p-3">
                                                <dt className="flex items-center gap-2 text-sm text-stone-400">
                                                    <Coins className="size-4 text-amber-400" />
                                                    Gold
                                                </dt>
                                                <dd className="mt-1 text-lg font-semibold text-stone-100">
                                                    {user.gold.toLocaleString()}
                                                </dd>
                                            </div>
                                            <div className="rounded-lg border border-stone-800 bg-stone-900/30 p-3">
                                                <dt className="flex items-center gap-2 text-sm text-stone-400">
                                                    <User className="size-4 text-green-400" />
                                                    Class
                                                </dt>
                                                <dd className="mt-1 text-lg font-semibold capitalize text-stone-100">
                                                    {user.social_class || "Serf"}
                                                </dd>
                                            </div>
                                        </dl>
                                    </CardContent>
                                </Card>

                                {/* Ban History */}
                                <Card className="border-stone-800 bg-stone-900/50">
                                    <CardHeader>
                                        <CardTitle className="flex items-center gap-2 text-stone-100">
                                            <History className="size-5" />
                                            Ban History
                                        </CardTitle>
                                        <CardDescription className="text-stone-400">
                                            {user.bans.length} total{" "}
                                            {user.bans.length === 1 ? "ban" : "bans"}
                                        </CardDescription>
                                    </CardHeader>
                                    <CardContent>
                                        {user.bans.length === 0 ? (
                                            <p className="text-center text-stone-500">
                                                No ban history
                                            </p>
                                        ) : (
                                            <div className="space-y-4">
                                                {user.bans.slice(0, 3).map((ban) => (
                                                    <div
                                                        key={ban.id}
                                                        className={cn(
                                                            "rounded-lg border p-4",
                                                            ban.is_active
                                                                ? "border-red-900/50 bg-red-900/10"
                                                                : "border-stone-800 bg-stone-900/30",
                                                        )}
                                                    >
                                                        <div className="flex items-start justify-between">
                                                            <div>
                                                                <div className="flex items-center gap-2">
                                                                    {ban.is_active ? (
                                                                        <Badge
                                                                            variant="destructive"
                                                                            className="bg-red-900/30 text-red-400"
                                                                        >
                                                                            Active
                                                                        </Badge>
                                                                    ) : (
                                                                        <Badge
                                                                            variant="secondary"
                                                                            className="bg-stone-800 text-stone-400"
                                                                        >
                                                                            Lifted
                                                                        </Badge>
                                                                    )}
                                                                    <span className="text-sm text-stone-400">
                                                                        {formatDateTime(
                                                                            ban.banned_at,
                                                                        )}
                                                                    </span>
                                                                </div>
                                                                <p className="mt-2 text-stone-100">
                                                                    {ban.reason}
                                                                </p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                ))}
                                            </div>
                                        )}
                                    </CardContent>
                                </Card>
                            </div>

                            {/* Quick Info Sidebar */}
                            <div className="space-y-6">
                                <Card className="border-stone-800 bg-stone-900/50">
                                    <CardHeader>
                                        <CardTitle className="text-stone-100">Quick Info</CardTitle>
                                    </CardHeader>
                                    <CardContent>
                                        <dl className="space-y-3">
                                            <div className="flex justify-between">
                                                <dt className="text-stone-400">Gender</dt>
                                                <dd className="capitalize text-stone-100">
                                                    {user.gender || "Unknown"}
                                                </dd>
                                            </div>
                                            <div className="flex justify-between">
                                                <dt className="text-stone-400">Home Village</dt>
                                                <dd className="text-stone-100">
                                                    {user.home_village?.name || "None"}
                                                </dd>
                                            </div>
                                            <div className="flex justify-between">
                                                <dt className="text-stone-400">Total Bans</dt>
                                                <dd className="text-stone-100">
                                                    {user.bans.length}
                                                </dd>
                                            </div>
                                            <div className="flex justify-between">
                                                <dt className="text-stone-400">Active Titles</dt>
                                                <dd className="text-stone-100">
                                                    {activeTitles.length}
                                                </dd>
                                            </div>
                                            <div className="flex justify-between">
                                                <dt className="text-stone-400">Active Roles</dt>
                                                <dd className="text-stone-100">
                                                    {activeRoles.length}
                                                </dd>
                                            </div>
                                        </dl>
                                    </CardContent>
                                </Card>

                                {horse && (
                                    <Card className="border-stone-800 bg-stone-900/50">
                                        <CardHeader>
                                            <CardTitle className="flex items-center gap-2 text-stone-100">
                                                <Swords className="size-5 text-amber-400" />
                                                Horse
                                            </CardTitle>
                                        </CardHeader>
                                        <CardContent>
                                            <dl className="space-y-2">
                                                <div className="flex justify-between">
                                                    <dt className="text-stone-400">Name</dt>
                                                    <dd className="text-stone-100">{horse.name}</dd>
                                                </div>
                                                <div className="flex justify-between">
                                                    <dt className="text-stone-400">Type</dt>
                                                    <dd className="text-stone-100">
                                                        {horse.horse_type || "Unknown"}
                                                    </dd>
                                                </div>
                                                <div className="flex justify-between">
                                                    <dt className="text-stone-400">Health</dt>
                                                    <dd className="text-stone-100">
                                                        {horse.health}%
                                                    </dd>
                                                </div>
                                                <div className="flex justify-between">
                                                    <dt className="text-stone-400">Stamina</dt>
                                                    <dd className="text-stone-100">
                                                        {horse.stamina}/{horse.max_stamina}
                                                    </dd>
                                                </div>
                                                <div className="flex justify-between">
                                                    <dt className="text-stone-400">Status</dt>
                                                    <dd className="text-stone-100">
                                                        {horse.is_stabled
                                                            ? "Stabled"
                                                            : "With Player"}
                                                    </dd>
                                                </div>
                                            </dl>
                                        </CardContent>
                                    </Card>
                                )}

                                {diseases.length > 0 && (
                                    <Card className="border-red-900/50 bg-red-900/10">
                                        <CardHeader>
                                            <CardTitle className="flex items-center gap-2 text-red-400">
                                                <Sparkles className="size-5" />
                                                Active Diseases
                                            </CardTitle>
                                        </CardHeader>
                                        <CardContent>
                                            <div className="space-y-2">
                                                {diseases
                                                    .filter((d) => !d.cured_at)
                                                    .map((disease) => (
                                                        <div
                                                            key={disease.id}
                                                            className="flex items-center justify-between"
                                                        >
                                                            <span className="text-stone-100">
                                                                {disease.disease_name}
                                                            </span>
                                                            <Badge
                                                                variant="destructive"
                                                                className="bg-red-900/30"
                                                            >
                                                                {disease.severity}
                                                            </Badge>
                                                        </div>
                                                    ))}
                                            </div>
                                        </CardContent>
                                    </Card>
                                )}
                            </div>
                        </div>
                    )}

                    {/* Skills Tab */}
                    {activeTab === "skills" && (
                        <div className="space-y-6">
                            <Card className="border-stone-800 bg-stone-900/50">
                                <CardHeader>
                                    <CardTitle className="flex items-center gap-2 text-stone-100">
                                        <Swords className="size-5 text-red-400" />
                                        Combat Skills
                                    </CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5">
                                        {combatSkills.map((skill) => (
                                            <SkillCard
                                                key={skill.skill_name}
                                                name={skill.skill_name}
                                                level={skill.level}
                                                xp={skill.xp}
                                                progress={skill.progress}
                                                isCombat={skill.is_combat}
                                            />
                                        ))}
                                    </div>
                                </CardContent>
                            </Card>

                            <Card className="border-stone-800 bg-stone-900/50">
                                <CardHeader>
                                    <CardTitle className="flex items-center gap-2 text-stone-100">
                                        <MapPin className="size-5 text-green-400" />
                                        Gathering Skills
                                    </CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                                        {gatheringSkills.map((skill) => (
                                            <SkillCard
                                                key={skill.skill_name}
                                                name={skill.skill_name}
                                                level={skill.level}
                                                xp={skill.xp}
                                                progress={skill.progress}
                                                isCombat={skill.is_combat}
                                            />
                                        ))}
                                    </div>
                                </CardContent>
                            </Card>

                            <Card className="border-stone-800 bg-stone-900/50">
                                <CardHeader>
                                    <CardTitle className="flex items-center gap-2 text-stone-100">
                                        <Scroll className="size-5 text-orange-400" />
                                        Crafting Skills
                                    </CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                                        {craftingSkills.map((skill) => (
                                            <SkillCard
                                                key={skill.skill_name}
                                                name={skill.skill_name}
                                                level={skill.level}
                                                xp={skill.xp}
                                                progress={skill.progress}
                                                isCombat={skill.is_combat}
                                            />
                                        ))}
                                    </div>
                                </CardContent>
                            </Card>
                        </div>
                    )}

                    {/* Inventory Tab */}
                    {activeTab === "inventory" && (
                        <div className="grid gap-6 lg:grid-cols-3">
                            <div className="lg:col-span-2">
                                <Card className="border-stone-800 bg-stone-900/50">
                                    <CardHeader>
                                        <CardTitle className="flex items-center gap-2 text-stone-100">
                                            <Backpack className="size-5 text-amber-400" />
                                            Inventory
                                        </CardTitle>
                                        <CardDescription className="text-stone-400">
                                            {inventory.filter((i) => i.item).length} / 28 slots used
                                        </CardDescription>
                                    </CardHeader>
                                    <CardContent>
                                        <InventoryGrid inventory={inventory} />
                                    </CardContent>
                                </Card>
                            </div>
                            <div>
                                <Card className="border-stone-800 bg-stone-900/50">
                                    <CardHeader>
                                        <CardTitle className="text-stone-100">Equipment</CardTitle>
                                    </CardHeader>
                                    <CardContent>
                                        <EquipmentSlots inventory={inventory} />
                                    </CardContent>
                                </Card>
                            </div>
                        </div>
                    )}

                    {/* Titles & Roles Tab */}
                    {activeTab === "titles" && (
                        <div className="grid gap-6 lg:grid-cols-2">
                            <Card className="border-stone-800 bg-stone-900/50">
                                <CardHeader>
                                    <CardTitle className="flex items-center gap-2 text-stone-100">
                                        <Crown className="size-5 text-amber-400" />
                                        Titles
                                    </CardTitle>
                                    <CardDescription className="text-stone-400">
                                        {activeTitles.length} active,{" "}
                                        {titles.length - activeTitles.length} revoked
                                    </CardDescription>
                                </CardHeader>
                                <CardContent>
                                    {titles.length === 0 ? (
                                        <p className="py-4 text-center text-stone-500">No titles</p>
                                    ) : (
                                        <div className="space-y-3">
                                            {titles.map((title) => (
                                                <div
                                                    key={title.id}
                                                    className={cn(
                                                        "rounded-lg border p-3",
                                                        title.is_active
                                                            ? "border-amber-900/50 bg-amber-900/10"
                                                            : "border-stone-800 bg-stone-900/30",
                                                    )}
                                                >
                                                    <div className="flex items-center justify-between">
                                                        <span className="font-medium capitalize text-stone-100">
                                                            {title.title}
                                                        </span>
                                                        <Badge
                                                            variant={
                                                                title.is_active
                                                                    ? "default"
                                                                    : "secondary"
                                                            }
                                                            className={
                                                                title.is_active
                                                                    ? "bg-amber-900/30 text-amber-400"
                                                                    : "bg-stone-800 text-stone-500"
                                                            }
                                                        >
                                                            Tier {title.tier}
                                                        </Badge>
                                                    </div>
                                                    <div className="mt-2 flex items-center gap-4 text-xs text-stone-500">
                                                        {title.domain_type && (
                                                            <span className="capitalize">
                                                                {title.domain_type}
                                                            </span>
                                                        )}
                                                        <span>Legitimacy: {title.legitimacy}</span>
                                                        {title.granted_at && (
                                                            <span>
                                                                {formatDistanceToNow(
                                                                    new Date(title.granted_at),
                                                                    { addSuffix: true },
                                                                )}
                                                            </span>
                                                        )}
                                                    </div>
                                                </div>
                                            ))}
                                        </div>
                                    )}
                                </CardContent>
                            </Card>

                            <Card className="border-stone-800 bg-stone-900/50">
                                <CardHeader>
                                    <CardTitle className="flex items-center gap-2 text-stone-100">
                                        <Briefcase className="size-5 text-cyan-400" />
                                        Roles
                                    </CardTitle>
                                    <CardDescription className="text-stone-400">
                                        {activeRoles.length} active roles
                                    </CardDescription>
                                </CardHeader>
                                <CardContent>
                                    {roles.length === 0 ? (
                                        <p className="py-4 text-center text-stone-500">No roles</p>
                                    ) : (
                                        <div className="space-y-3">
                                            {roles.map((role) => (
                                                <div
                                                    key={role.id}
                                                    className={cn(
                                                        "rounded-lg border p-3",
                                                        role.status === "active"
                                                            ? "border-cyan-900/50 bg-cyan-900/10"
                                                            : "border-stone-800 bg-stone-900/30",
                                                    )}
                                                >
                                                    <div className="flex items-center justify-between">
                                                        <span className="font-medium text-stone-100">
                                                            {role.role_name || "Unknown Role"}
                                                        </span>
                                                        <Badge
                                                            variant={
                                                                role.status === "active"
                                                                    ? "default"
                                                                    : "secondary"
                                                            }
                                                            className={cn(
                                                                role.status === "active"
                                                                    ? "bg-green-900/30 text-green-400"
                                                                    : "bg-stone-800 text-stone-500",
                                                                "capitalize",
                                                            )}
                                                        >
                                                            {role.status}
                                                        </Badge>
                                                    </div>
                                                    <div className="mt-2 text-xs text-stone-500">
                                                        <span className="capitalize">
                                                            {role.location_type}
                                                        </span>
                                                        : {role.location_name}
                                                        <span className="ml-4">
                                                            Legitimacy: {role.legitimacy}
                                                        </span>
                                                    </div>
                                                </div>
                                            ))}
                                        </div>
                                    )}
                                </CardContent>
                            </Card>
                        </div>
                    )}

                    {/* Dynasty Tab */}
                    {activeTab === "dynasty" && (
                        <Card className="border-stone-800 bg-stone-900/50">
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2 text-stone-100">
                                    <Users className="size-5 text-purple-400" />
                                    Dynasty
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                {dynasty ? (
                                    <dl className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                                        <div className="rounded-lg border border-stone-800 bg-stone-900/30 p-3">
                                            <dt className="text-sm text-stone-400">Dynasty Name</dt>
                                            <dd className="mt-1 text-lg font-semibold text-stone-100">
                                                {dynasty.dynasty_name || "Unknown"}
                                            </dd>
                                        </div>
                                        <div className="rounded-lg border border-stone-800 bg-stone-900/30 p-3">
                                            <dt className="text-sm text-stone-400">First Name</dt>
                                            <dd className="mt-1 text-lg font-semibold text-stone-100">
                                                {dynasty.first_name}
                                            </dd>
                                        </div>
                                        <div className="rounded-lg border border-stone-800 bg-stone-900/30 p-3">
                                            <dt className="text-sm text-stone-400">Generation</dt>
                                            <dd className="mt-1 text-lg font-semibold text-stone-100">
                                                {dynasty.generation}
                                            </dd>
                                        </div>
                                        <div className="rounded-lg border border-stone-800 bg-stone-900/30 p-3">
                                            <dt className="text-sm text-stone-400">Status</dt>
                                            <dd className="mt-1 text-lg font-semibold capitalize text-stone-100">
                                                {dynasty.status}
                                            </dd>
                                        </div>
                                        <div className="rounded-lg border border-stone-800 bg-stone-900/30 p-3">
                                            <dt className="text-sm text-stone-400">Is Heir</dt>
                                            <dd className="mt-1 text-lg font-semibold text-stone-100">
                                                {dynasty.is_heir ? (
                                                    <Badge className="bg-amber-900/30 text-amber-400">
                                                        Yes
                                                    </Badge>
                                                ) : (
                                                    "No"
                                                )}
                                            </dd>
                                        </div>
                                        <div className="rounded-lg border border-stone-800 bg-stone-900/30 p-3">
                                            <dt className="text-sm text-stone-400">Legitimate</dt>
                                            <dd className="mt-1 text-lg font-semibold text-stone-100">
                                                {dynasty.is_legitimate ? "Yes" : "No"}
                                            </dd>
                                        </div>
                                    </dl>
                                ) : (
                                    <p className="py-8 text-center text-stone-500">
                                        Not a member of any dynasty
                                    </p>
                                )}
                            </CardContent>
                        </Card>
                    )}

                    {/* Religion Tab */}
                    {activeTab === "religion" && (
                        <Card className="border-stone-800 bg-stone-900/50">
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2 text-stone-100">
                                    <Church className="size-5 text-purple-400" />
                                    Religion
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                {religion ? (
                                    <dl className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                                        <div className="rounded-lg border border-stone-800 bg-stone-900/30 p-3">
                                            <dt className="text-sm text-stone-400">Religion</dt>
                                            <dd className="mt-1 text-lg font-semibold text-stone-100">
                                                {religion.religion_name || "Unknown"}
                                            </dd>
                                        </div>
                                        <div className="rounded-lg border border-stone-800 bg-stone-900/30 p-3">
                                            <dt className="text-sm text-stone-400">Rank</dt>
                                            <dd className="mt-1 text-lg font-semibold capitalize text-stone-100">
                                                {religion.rank}
                                            </dd>
                                        </div>
                                        <div className="rounded-lg border border-stone-800 bg-stone-900/30 p-3">
                                            <dt className="text-sm text-stone-400">Devotion</dt>
                                            <dd className="mt-1 text-lg font-semibold text-stone-100">
                                                {religion.devotion.toLocaleString()}
                                            </dd>
                                        </div>
                                        <div className="rounded-lg border border-stone-800 bg-stone-900/30 p-3">
                                            <dt className="text-sm text-stone-400">Joined</dt>
                                            <dd className="mt-1 text-lg font-semibold text-stone-100">
                                                {religion.joined_at
                                                    ? formatDistanceToNow(
                                                          new Date(religion.joined_at),
                                                          { addSuffix: true },
                                                      )
                                                    : "Unknown"}
                                            </dd>
                                        </div>
                                    </dl>
                                ) : (
                                    <p className="py-8 text-center text-stone-500">
                                        Not a member of any religion
                                    </p>
                                )}
                            </CardContent>
                        </Card>
                    )}

                    {/* Employment Tab */}
                    {activeTab === "employment" && (
                        <div className="grid gap-6 lg:grid-cols-2">
                            <Card className="border-stone-800 bg-stone-900/50">
                                <CardHeader>
                                    <CardTitle className="flex items-center gap-2 text-stone-100">
                                        <Briefcase className="size-5 text-cyan-400" />
                                        Employment History
                                    </CardTitle>
                                </CardHeader>
                                <CardContent>
                                    {employment.length === 0 ? (
                                        <p className="py-8 text-center text-stone-500">
                                            No employment history
                                        </p>
                                    ) : (
                                        <div className="space-y-3">
                                            {employment.map((emp) => (
                                                <div
                                                    key={emp.id}
                                                    className={cn(
                                                        "rounded-lg border p-3",
                                                        emp.status === "employed"
                                                            ? "border-green-900/50 bg-green-900/10"
                                                            : "border-stone-800 bg-stone-900/30",
                                                    )}
                                                >
                                                    <div className="flex items-center justify-between">
                                                        <span className="font-medium capitalize text-stone-100">
                                                            {emp.employer_type}
                                                        </span>
                                                        <Badge
                                                            variant={
                                                                emp.status === "employed"
                                                                    ? "default"
                                                                    : "secondary"
                                                            }
                                                            className={cn(
                                                                emp.status === "employed"
                                                                    ? "bg-green-900/30 text-green-400"
                                                                    : "bg-stone-800 text-stone-500",
                                                                "capitalize",
                                                            )}
                                                        >
                                                            {emp.status}
                                                        </Badge>
                                                    </div>
                                                    <div className="mt-2 flex items-center gap-4 text-xs text-stone-500">
                                                        <span>
                                                            Total Earnings:{" "}
                                                            {emp.total_earnings.toLocaleString()}{" "}
                                                            gold
                                                        </span>
                                                        {emp.hired_at && (
                                                            <span>
                                                                Hired{" "}
                                                                {formatDistanceToNow(
                                                                    new Date(emp.hired_at),
                                                                    { addSuffix: true },
                                                                )}
                                                            </span>
                                                        )}
                                                    </div>
                                                </div>
                                            ))}
                                        </div>
                                    )}
                                </CardContent>
                            </Card>

                            <Card className="border-stone-800 bg-stone-900/50">
                                <CardHeader>
                                    <CardTitle className="flex items-center gap-2 text-stone-100">
                                        <Coins className="size-5 text-amber-400" />
                                        Bank Accounts
                                    </CardTitle>
                                </CardHeader>
                                <CardContent>
                                    {bankAccounts.length === 0 ? (
                                        <p className="py-8 text-center text-stone-500">
                                            No bank accounts
                                        </p>
                                    ) : (
                                        <div className="space-y-3">
                                            {bankAccounts.map((acc) => (
                                                <div
                                                    key={acc.id}
                                                    className="flex items-center justify-between rounded-lg border border-stone-800 bg-stone-900/30 p-3"
                                                >
                                                    <span className="font-medium capitalize text-stone-100">
                                                        {acc.account_type || "Savings"}
                                                    </span>
                                                    <span className="text-lg font-semibold text-amber-400">
                                                        {acc.balance.toLocaleString()} gold
                                                    </span>
                                                </div>
                                            ))}
                                        </div>
                                    )}
                                </CardContent>
                            </Card>
                        </div>
                    )}

                    {/* Activity Tab */}
                    {activeTab === "activity" && (
                        <Card className="border-stone-800 bg-stone-900/50">
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2 text-stone-100">
                                    <Activity className="size-5 text-blue-400" />
                                    Activity Log
                                </CardTitle>
                                <CardDescription className="text-stone-400">
                                    Recent activities (last 50)
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <ActivityTable activities={activities} />
                            </CardContent>
                        </Card>
                    )}

                    {/* Admin Actions Tab */}
                    {activeTab === "admin" && (
                        <div className="grid gap-6 lg:grid-cols-2">
                            <Card className="border-stone-800 bg-stone-900/50">
                                <CardHeader>
                                    <CardTitle className="text-stone-100">
                                        Ban / Unban User
                                    </CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-4">
                                    {canBan && !showBanForm && (
                                        <Button
                                            variant="destructive"
                                            className="w-full"
                                            onClick={() => setShowBanForm(true)}
                                        >
                                            <Ban className="size-4" />
                                            Ban User
                                        </Button>
                                    )}

                                    {showBanForm && (
                                        <div className="space-y-3 rounded-lg border border-red-900/50 bg-red-900/10 p-4">
                                            <h4 className="font-medium text-red-400">
                                                Ban {user.username}
                                            </h4>
                                            <Textarea
                                                placeholder="Reason for ban (required)..."
                                                value={banReason}
                                                onChange={(e) => setBanReason(e.target.value)}
                                                className="border-stone-700 bg-stone-900/50"
                                                rows={3}
                                            />
                                            <div className="flex gap-2">
                                                <Button
                                                    variant="destructive"
                                                    size="sm"
                                                    onClick={handleBan}
                                                    disabled={loading || !banReason.trim()}
                                                >
                                                    {loading ? "Banning..." : "Confirm Ban"}
                                                </Button>
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    onClick={() => {
                                                        setShowBanForm(false);
                                                        setBanReason("");
                                                    }}
                                                    className="border-stone-700"
                                                >
                                                    Cancel
                                                </Button>
                                            </div>
                                        </div>
                                    )}

                                    {canUnban && !showUnbanForm && (
                                        <Button
                                            variant="outline"
                                            className="w-full border-green-900 text-green-400 hover:bg-green-900/20"
                                            onClick={() => setShowUnbanForm(true)}
                                        >
                                            <ShieldOff className="size-4" />
                                            Unban User
                                        </Button>
                                    )}

                                    {showUnbanForm && (
                                        <div className="space-y-3 rounded-lg border border-green-900/50 bg-green-900/10 p-4">
                                            <h4 className="font-medium text-green-400">
                                                Unban {user.username}
                                            </h4>
                                            <Textarea
                                                placeholder="Reason for unban (optional)..."
                                                value={unbanReason}
                                                onChange={(e) => setUnbanReason(e.target.value)}
                                                className="border-stone-700 bg-stone-900/50"
                                                rows={3}
                                            />
                                            <div className="flex gap-2">
                                                <Button
                                                    variant="default"
                                                    size="sm"
                                                    onClick={handleUnban}
                                                    disabled={loading}
                                                    className="bg-green-600 hover:bg-green-700"
                                                >
                                                    {loading ? "Unbanning..." : "Confirm Unban"}
                                                </Button>
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    onClick={() => {
                                                        setShowUnbanForm(false);
                                                        setUnbanReason("");
                                                    }}
                                                    className="border-stone-700"
                                                >
                                                    Cancel
                                                </Button>
                                            </div>
                                        </div>
                                    )}

                                    {user.is_admin && user.id !== auth.user.id && (
                                        <p className="text-center text-sm text-stone-500">
                                            Cannot ban other administrators
                                        </p>
                                    )}

                                    {user.id === auth.user.id && (
                                        <p className="text-center text-sm text-stone-500">
                                            Cannot ban yourself
                                        </p>
                                    )}
                                </CardContent>
                            </Card>

                            <Card className="border-stone-800 bg-stone-900/50">
                                <CardHeader>
                                    <CardTitle className="flex items-center gap-2 text-stone-100">
                                        <History className="size-5" />
                                        Full Ban History
                                    </CardTitle>
                                    <CardDescription className="text-stone-400">
                                        {user.bans.length} total{" "}
                                        {user.bans.length === 1 ? "ban" : "bans"}
                                    </CardDescription>
                                </CardHeader>
                                <CardContent>
                                    {user.bans.length === 0 ? (
                                        <p className="py-4 text-center text-stone-500">
                                            No ban history
                                        </p>
                                    ) : (
                                        <div className="space-y-4">
                                            {user.bans.map((ban) => (
                                                <div
                                                    key={ban.id}
                                                    className={cn(
                                                        "rounded-lg border p-4",
                                                        ban.is_active
                                                            ? "border-red-900/50 bg-red-900/10"
                                                            : "border-stone-800 bg-stone-900/30",
                                                    )}
                                                >
                                                    <div className="flex items-start justify-between">
                                                        <div>
                                                            <div className="flex items-center gap-2">
                                                                {ban.is_active ? (
                                                                    <Badge
                                                                        variant="destructive"
                                                                        className="bg-red-900/30 text-red-400"
                                                                    >
                                                                        Active
                                                                    </Badge>
                                                                ) : (
                                                                    <Badge
                                                                        variant="secondary"
                                                                        className="bg-stone-800 text-stone-400"
                                                                    >
                                                                        Lifted
                                                                    </Badge>
                                                                )}
                                                                <span className="text-sm text-stone-400">
                                                                    {formatDateTime(ban.banned_at)}
                                                                </span>
                                                            </div>
                                                            <p className="mt-2 text-stone-100">
                                                                {ban.reason}
                                                            </p>
                                                            <p className="mt-1 text-xs text-stone-500">
                                                                Banned by:{" "}
                                                                {ban.banned_by?.username ||
                                                                    "Unknown"}
                                                            </p>
                                                        </div>
                                                    </div>
                                                    {ban.unbanned_at && (
                                                        <div className="mt-3 border-t border-stone-800 pt-3">
                                                            <p className="text-sm text-green-400">
                                                                <CheckCircle className="mr-1 inline size-4" />
                                                                Unbanned{" "}
                                                                {formatDateTime(ban.unbanned_at)} by{" "}
                                                                {ban.unbanned_by?.username ||
                                                                    "Unknown"}
                                                            </p>
                                                            {ban.unban_reason && (
                                                                <p className="mt-1 text-sm text-stone-400">
                                                                    Reason: {ban.unban_reason}
                                                                </p>
                                                            )}
                                                        </div>
                                                    )}
                                                </div>
                                            ))}
                                        </div>
                                    )}
                                </CardContent>
                            </Card>
                        </div>
                    )}
                </div>
            </div>
        </AdminLayout>
    );
}
