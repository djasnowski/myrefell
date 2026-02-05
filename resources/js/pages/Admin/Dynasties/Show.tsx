import { Head, Link } from "@inertiajs/react";
import { formatDistanceToNow } from "date-fns";
import {
    ArrowLeft,
    Calendar,
    Crown,
    Edit,
    History,
    Scroll,
    Skull,
    Star,
    TrendingDown,
    TrendingUp,
    User,
    Users,
} from "lucide-react";
import {
    edit as editDynasty,
    index as dynastiesIndex,
} from "@/actions/App/Http/Controllers/Admin/DynastyController";
import { show as showUser } from "@/actions/App/Http/Controllers/Admin/UserController";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import AdminLayout from "@/layouts/admin-layout";
import { cn } from "@/lib/utils";
import type { BreadcrumbItem } from "@/types";

interface DynastyData {
    id: number;
    name: string;
    motto: string | null;
    coat_of_arms: string | null;
    prestige: number;
    wealth_score: number;
    generations: number;
    history: string[] | null;
    founded_at: string | null;
    created_at: string;
    founder: { id: number; username: string } | null;
    current_head: { id: number; username: string } | null;
}

interface DynastyMember {
    id: number;
    first_name: string;
    full_name: string;
    gender: string;
    generation: number;
    birth_order: number;
    status: string;
    is_heir: boolean;
    is_legitimate: boolean;
    is_disinherited: boolean;
    birth_date: string | null;
    death_date: string | null;
    death_cause: string | null;
    user: { id: number; username: string } | null;
}

interface DynastyEvent {
    id: number;
    event_type: string;
    title: string;
    description: string | null;
    prestige_change: number;
    occurred_at: string | null;
}

interface SuccessionRules {
    id: number;
    succession_type: string;
    gender_preference: string;
    legitimacy_required: boolean;
}

interface Props {
    dynasty: DynastyData;
    members: DynastyMember[];
    events: DynastyEvent[];
    successionRules: SuccessionRules | null;
}

export default function Show({ dynasty, members, events, successionRules }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: "Admin", href: "/admin" },
        { title: "Dynasties", href: "/admin/dynasties" },
        { title: dynasty.name, href: `/admin/dynasties/${dynasty.id}` },
    ];

    const formatDate = (dateStr: string | null) => {
        if (!dateStr) return "—";
        return new Date(dateStr).toLocaleDateString("en-US", {
            year: "numeric",
            month: "short",
            day: "numeric",
        });
    };

    const getStatusColor = (status: string) => {
        switch (status) {
            case "alive":
                return "bg-green-900/30 text-green-400";
            case "dead":
                return "bg-stone-800 text-stone-400";
            case "missing":
                return "bg-yellow-900/30 text-yellow-400";
            case "exiled":
                return "bg-red-900/30 text-red-400";
            default:
                return "bg-stone-800 text-stone-400";
        }
    };

    const getEventTypeColor = (type: string) => {
        switch (type) {
            case "birth":
                return "bg-green-900/30 text-green-400";
            case "death":
                return "bg-stone-700 text-stone-300";
            case "marriage":
                return "bg-pink-900/30 text-pink-400";
            case "succession":
                return "bg-amber-900/30 text-amber-400";
            case "achievement":
                return "bg-blue-900/30 text-blue-400";
            case "scandal":
                return "bg-red-900/30 text-red-400";
            default:
                return "bg-stone-800 text-stone-400";
        }
    };

    const livingMembers = members.filter((m) => m.status === "alive");
    const deadMembers = members.filter((m) => m.status === "dead");

    return (
        <AdminLayout breadcrumbs={breadcrumbs}>
            <Head title={`Dynasty: ${dynasty.name}`} />

            <div className="space-y-6 p-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-4">
                        <Link href={dynastiesIndex.url()}>
                            <Button variant="ghost" size="sm">
                                <ArrowLeft className="size-4" />
                                Back
                            </Button>
                        </Link>
                        <div className="flex items-center gap-3">
                            <div className="rounded-lg bg-amber-900/30 p-2">
                                <Crown className="size-6 text-amber-400" />
                            </div>
                            <div>
                                <h1 className="font-[Cinzel] text-2xl font-bold text-stone-100">
                                    {dynasty.name}
                                </h1>
                                {dynasty.motto && (
                                    <p className="text-sm text-stone-400 italic">
                                        "{dynasty.motto}"
                                    </p>
                                )}
                            </div>
                        </div>
                    </div>
                    <Link href={editDynasty.url(dynasty.id)}>
                        <Button variant="outline" className="border-stone-700">
                            <Edit className="size-4" />
                            Edit Dynasty
                        </Button>
                    </Link>
                </div>

                {/* Overview Cards */}
                <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <Card className="border-stone-800 bg-stone-900/50">
                        <CardContent className="pt-6">
                            <div className="flex items-center gap-3">
                                <Star className="size-5 text-amber-400" />
                                <div>
                                    <p className="text-sm text-stone-400">Prestige</p>
                                    <p className="text-2xl font-bold text-amber-400">
                                        {dynasty.prestige.toLocaleString()}
                                    </p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                    <Card className="border-stone-800 bg-stone-900/50">
                        <CardContent className="pt-6">
                            <div className="flex items-center gap-3">
                                <Users className="size-5 text-blue-400" />
                                <div>
                                    <p className="text-sm text-stone-400">Living Members</p>
                                    <p className="text-2xl font-bold text-stone-100">
                                        {livingMembers.length}
                                    </p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                    <Card className="border-stone-800 bg-stone-900/50">
                        <CardContent className="pt-6">
                            <div className="flex items-center gap-3">
                                <Scroll className="size-5 text-purple-400" />
                                <div>
                                    <p className="text-sm text-stone-400">Generations</p>
                                    <p className="text-2xl font-bold text-stone-100">
                                        {dynasty.generations}
                                    </p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                    <Card className="border-stone-800 bg-stone-900/50">
                        <CardContent className="pt-6">
                            <div className="flex items-center gap-3">
                                <Calendar className="size-5 text-green-400" />
                                <div>
                                    <p className="text-sm text-stone-400">Founded</p>
                                    <p className="text-lg font-medium text-stone-100">
                                        {formatDate(dynasty.founded_at)}
                                    </p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* Main Content Grid */}
                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    {/* Details & Leadership */}
                    <div className="space-y-6">
                        {/* Dynasty Details */}
                        <Card className="border-stone-800 bg-stone-900/50">
                            <CardHeader>
                                <CardTitle className="text-stone-100">Details</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="grid grid-cols-2 gap-4">
                                    <div>
                                        <p className="text-xs text-stone-500 uppercase">Founder</p>
                                        {dynasty.founder ? (
                                            <Link
                                                href={showUser.url(dynasty.founder.id)}
                                                className="text-blue-400 hover:underline"
                                            >
                                                {dynasty.founder.username}
                                            </Link>
                                        ) : (
                                            <p className="text-stone-400">—</p>
                                        )}
                                    </div>
                                    <div>
                                        <p className="text-xs text-stone-500 uppercase">
                                            Current Head
                                        </p>
                                        {dynasty.current_head ? (
                                            <Link
                                                href={showUser.url(dynasty.current_head.id)}
                                                className="text-blue-400 hover:underline"
                                            >
                                                {dynasty.current_head.username}
                                            </Link>
                                        ) : (
                                            <p className="text-stone-400">—</p>
                                        )}
                                    </div>
                                    <div>
                                        <p className="text-xs text-stone-500 uppercase">
                                            Wealth Score
                                        </p>
                                        <p className="text-stone-100">
                                            {dynasty.wealth_score.toLocaleString()}
                                        </p>
                                    </div>
                                    <div>
                                        <p className="text-xs text-stone-500 uppercase">
                                            Total Members
                                        </p>
                                        <p className="text-stone-100">{members.length}</p>
                                    </div>
                                </div>
                                {dynasty.coat_of_arms && (
                                    <div>
                                        <p className="text-xs text-stone-500 uppercase mb-2">
                                            Coat of Arms
                                        </p>
                                        <p className="text-stone-300 text-sm">
                                            {dynasty.coat_of_arms}
                                        </p>
                                    </div>
                                )}
                            </CardContent>
                        </Card>

                        {/* Succession Rules */}
                        {successionRules && (
                            <Card className="border-stone-800 bg-stone-900/50">
                                <CardHeader>
                                    <CardTitle className="text-stone-100">
                                        Succession Rules
                                    </CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-3">
                                    <div>
                                        <p className="text-xs text-stone-500 uppercase">Type</p>
                                        <p className="text-stone-100 capitalize">
                                            {successionRules.succession_type}
                                        </p>
                                    </div>
                                    <div>
                                        <p className="text-xs text-stone-500 uppercase">
                                            Gender Preference
                                        </p>
                                        <p className="text-stone-100 capitalize">
                                            {successionRules.gender_preference}
                                        </p>
                                    </div>
                                    <div>
                                        <p className="text-xs text-stone-500 uppercase">
                                            Legitimacy Required
                                        </p>
                                        <Badge
                                            variant="secondary"
                                            className={
                                                successionRules.legitimacy_required
                                                    ? "bg-green-900/30 text-green-400"
                                                    : "bg-stone-800 text-stone-400"
                                            }
                                        >
                                            {successionRules.legitimacy_required ? "Yes" : "No"}
                                        </Badge>
                                    </div>
                                </CardContent>
                            </Card>
                        )}
                    </div>

                    {/* Members */}
                    <Card className="border-stone-800 bg-stone-900/50 lg:col-span-2">
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-stone-100">
                                <Users className="size-5" />
                                Members ({members.length})
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="overflow-x-auto">
                                <table className="w-full">
                                    <thead>
                                        <tr className="border-b border-stone-800">
                                            <th className="px-3 py-2 text-left text-xs font-medium uppercase tracking-wider text-stone-400">
                                                Name
                                            </th>
                                            <th className="px-3 py-2 text-left text-xs font-medium uppercase tracking-wider text-stone-400">
                                                Player
                                            </th>
                                            <th className="px-3 py-2 text-left text-xs font-medium uppercase tracking-wider text-stone-400">
                                                Gen
                                            </th>
                                            <th className="px-3 py-2 text-left text-xs font-medium uppercase tracking-wider text-stone-400">
                                                Status
                                            </th>
                                            <th className="px-3 py-2 text-left text-xs font-medium uppercase tracking-wider text-stone-400">
                                                Flags
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-stone-800">
                                        {members.map((member) => (
                                            <tr
                                                key={member.id}
                                                className={cn(
                                                    "hover:bg-stone-800/50 transition",
                                                    member.status === "dead" && "opacity-60",
                                                )}
                                            >
                                                <td className="px-3 py-2">
                                                    <div className="flex items-center gap-2">
                                                        {member.status === "dead" ? (
                                                            <Skull className="size-4 text-stone-500" />
                                                        ) : (
                                                            <User className="size-4 text-stone-500" />
                                                        )}
                                                        <span className="font-medium text-stone-100">
                                                            {member.full_name}
                                                        </span>
                                                    </div>
                                                </td>
                                                <td className="px-3 py-2 text-stone-400">
                                                    {member.user ? (
                                                        <Link
                                                            href={showUser.url(member.user.id)}
                                                            className="text-blue-400 hover:underline"
                                                        >
                                                            {member.user.username}
                                                        </Link>
                                                    ) : (
                                                        <span className="text-stone-500">NPC</span>
                                                    )}
                                                </td>
                                                <td className="px-3 py-2 text-stone-400">
                                                    {member.generation}
                                                </td>
                                                <td className="px-3 py-2">
                                                    <Badge
                                                        variant="secondary"
                                                        className={getStatusColor(member.status)}
                                                    >
                                                        {member.status}
                                                    </Badge>
                                                </td>
                                                <td className="px-3 py-2">
                                                    <div className="flex items-center gap-1">
                                                        {member.is_heir && (
                                                            <Badge className="bg-amber-900/30 text-amber-400 text-xs">
                                                                Heir
                                                            </Badge>
                                                        )}
                                                        {!member.is_legitimate && (
                                                            <Badge className="bg-red-900/30 text-red-400 text-xs">
                                                                Bastard
                                                            </Badge>
                                                        )}
                                                        {member.is_disinherited && (
                                                            <Badge className="bg-stone-700 text-stone-400 text-xs">
                                                                Disinherited
                                                            </Badge>
                                                        )}
                                                    </div>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* Events Timeline */}
                <Card className="border-stone-800 bg-stone-900/50">
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2 text-stone-100">
                            <History className="size-5" />
                            Event History
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        {events.length === 0 ? (
                            <p className="text-stone-500 text-center py-8">No events recorded</p>
                        ) : (
                            <div className="space-y-4">
                                {events.map((event) => (
                                    <div
                                        key={event.id}
                                        className="flex items-start gap-4 p-3 rounded-lg bg-stone-800/30"
                                    >
                                        <Badge
                                            variant="secondary"
                                            className={getEventTypeColor(event.event_type)}
                                        >
                                            {event.event_type}
                                        </Badge>
                                        <div className="flex-1">
                                            <p className="font-medium text-stone-100">
                                                {event.title}
                                            </p>
                                            {event.description && (
                                                <p className="text-sm text-stone-400 mt-1">
                                                    {event.description}
                                                </p>
                                            )}
                                        </div>
                                        <div className="text-right">
                                            {event.prestige_change !== 0 && (
                                                <div
                                                    className={cn(
                                                        "flex items-center gap-1 text-sm font-medium",
                                                        event.prestige_change > 0
                                                            ? "text-green-400"
                                                            : "text-red-400",
                                                    )}
                                                >
                                                    {event.prestige_change > 0 ? (
                                                        <TrendingUp className="size-4" />
                                                    ) : (
                                                        <TrendingDown className="size-4" />
                                                    )}
                                                    {event.prestige_change > 0 ? "+" : ""}
                                                    {event.prestige_change}
                                                </div>
                                            )}
                                            {event.occurred_at && (
                                                <p className="text-xs text-stone-500 mt-1">
                                                    {formatDistanceToNow(
                                                        new Date(event.occurred_at),
                                                        {
                                                            addSuffix: true,
                                                        },
                                                    )}
                                                </p>
                                            )}
                                        </div>
                                    </div>
                                ))}
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AdminLayout>
    );
}
