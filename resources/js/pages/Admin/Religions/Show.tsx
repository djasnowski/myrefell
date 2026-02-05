import { Head, Link } from "@inertiajs/react";
import { formatDistanceToNow } from "date-fns";
import {
    ArrowLeft,
    Church,
    Coins,
    Edit,
    Eye,
    EyeOff,
    Home,
    Skull,
    Sparkles,
    Users,
} from "lucide-react";
import {
    edit as editReligion,
    index as religionsIndex,
} from "@/actions/App/Http/Controllers/Admin/ReligionController";
import { show as showUser } from "@/actions/App/Http/Controllers/Admin/UserController";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import AdminLayout from "@/layouts/admin-layout";
import { cn } from "@/lib/utils";
import type { BreadcrumbItem } from "@/types";

interface ReligionData {
    id: number;
    name: string;
    description: string | null;
    type: "cult" | "religion";
    icon: string | null;
    color: string | null;
    is_public: boolean;
    is_active: boolean;
    member_limit: number | null;
    belief_limit: number;
    founding_cost: number;
    hideout_tier: number | null;
    hideout_name: string | null;
    hideout_location_type: string | null;
    hideout_location_name: string | null;
    created_at: string;
    founder: { id: number; username: string } | null;
}

interface Treasury {
    id: number;
    balance: number;
}

interface Belief {
    id: number;
    name: string;
    description: string | null;
    effects: Record<string, number> | null;
}

interface ReligionMember {
    id: number;
    rank: string;
    rank_display: string;
    devotion: number;
    joined_at: string | null;
    user: { id: number; username: string } | null;
}

interface Props {
    religion: ReligionData;
    treasury: Treasury | null;
    beliefs: Belief[];
    members: ReligionMember[];
}

export default function Show({ religion, treasury, beliefs, members }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: "Admin", href: "/admin" },
        { title: "Religions", href: "/admin/religions" },
        { title: religion.name, href: `/admin/religions/${religion.id}` },
    ];

    const formatDate = (dateStr: string | null) => {
        if (!dateStr) return "—";
        return new Date(dateStr).toLocaleDateString("en-US", {
            year: "numeric",
            month: "short",
            day: "numeric",
        });
    };

    const getRankColor = (rank: string) => {
        switch (rank) {
            case "prophet":
                return "bg-amber-900/30 text-amber-400";
            case "archbishop":
            case "apostle":
                return "bg-purple-900/30 text-purple-400";
            case "priest":
            case "acolyte":
                return "bg-blue-900/30 text-blue-400";
            case "deacon":
            case "disciple":
                return "bg-green-900/30 text-green-400";
            default:
                return "bg-stone-800 text-stone-400";
        }
    };

    const isCult = religion.type === "cult";

    return (
        <AdminLayout breadcrumbs={breadcrumbs}>
            <Head title={`${isCult ? "Cult" : "Religion"}: ${religion.name}`} />

            <div className="space-y-6 p-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-4">
                        <Link href={religionsIndex.url()}>
                            <Button variant="ghost" size="sm">
                                <ArrowLeft className="size-4" />
                                Back
                            </Button>
                        </Link>
                        <div className="flex items-center gap-3">
                            <div
                                className={cn(
                                    "rounded-lg p-2",
                                    isCult ? "bg-red-900/30" : "bg-purple-900/30",
                                )}
                            >
                                {isCult ? (
                                    <Skull className="size-6 text-red-400" />
                                ) : (
                                    <Church className="size-6 text-purple-400" />
                                )}
                            </div>
                            <div>
                                <div className="flex items-center gap-2">
                                    <h1 className="font-[Cinzel] text-2xl font-bold text-stone-100">
                                        {religion.name}
                                    </h1>
                                    <Badge
                                        variant="secondary"
                                        className={
                                            isCult
                                                ? "bg-red-900/30 text-red-400"
                                                : "bg-purple-900/30 text-purple-400"
                                        }
                                    >
                                        {isCult ? "Cult" : "Religion"}
                                    </Badge>
                                    {!religion.is_active && (
                                        <Badge
                                            variant="secondary"
                                            className="bg-stone-700 text-stone-400"
                                        >
                                            Inactive
                                        </Badge>
                                    )}
                                </div>
                                {religion.description && (
                                    <p className="text-sm text-stone-400 mt-1">
                                        {religion.description}
                                    </p>
                                )}
                            </div>
                        </div>
                    </div>
                    <Link href={editReligion.url(religion.id)}>
                        <Button variant="outline" className="border-stone-700">
                            <Edit className="size-4" />
                            Edit
                        </Button>
                    </Link>
                </div>

                {/* Overview Cards */}
                <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <Card className="border-stone-800 bg-stone-900/50">
                        <CardContent className="pt-6">
                            <div className="flex items-center gap-3">
                                <Users className="size-5 text-blue-400" />
                                <div>
                                    <p className="text-sm text-stone-400">Members</p>
                                    <p className="text-2xl font-bold text-stone-100">
                                        {members.length}
                                    </p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                    <Card className="border-stone-800 bg-stone-900/50">
                        <CardContent className="pt-6">
                            <div className="flex items-center gap-3">
                                <Coins className="size-5 text-amber-400" />
                                <div>
                                    <p className="text-sm text-stone-400">Treasury</p>
                                    <p className="text-2xl font-bold text-amber-400">
                                        {(treasury?.balance ?? 0).toLocaleString()}g
                                    </p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                    <Card className="border-stone-800 bg-stone-900/50">
                        <CardContent className="pt-6">
                            <div className="flex items-center gap-3">
                                <Sparkles className="size-5 text-purple-400" />
                                <div>
                                    <p className="text-sm text-stone-400">Beliefs</p>
                                    <p className="text-2xl font-bold text-stone-100">
                                        {beliefs.length} / {religion.belief_limit}
                                    </p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                    <Card className="border-stone-800 bg-stone-900/50">
                        <CardContent className="pt-6">
                            <div className="flex items-center gap-3">
                                {religion.is_public ? (
                                    <Eye className="size-5 text-green-400" />
                                ) : (
                                    <EyeOff className="size-5 text-stone-400" />
                                )}
                                <div>
                                    <p className="text-sm text-stone-400">Visibility</p>
                                    <p className="text-lg font-medium text-stone-100">
                                        {religion.is_public ? "Public" : "Private"}
                                    </p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* Main Content Grid */}
                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    {/* Details */}
                    <div className="space-y-6">
                        <Card className="border-stone-800 bg-stone-900/50">
                            <CardHeader>
                                <CardTitle className="text-stone-100">Details</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="grid grid-cols-2 gap-4">
                                    <div>
                                        <p className="text-xs text-stone-500 uppercase">Founder</p>
                                        {religion.founder ? (
                                            <Link
                                                href={showUser.url(religion.founder.id)}
                                                className="text-blue-400 hover:underline"
                                            >
                                                {religion.founder.username}
                                            </Link>
                                        ) : (
                                            <p className="text-stone-400">—</p>
                                        )}
                                    </div>
                                    <div>
                                        <p className="text-xs text-stone-500 uppercase">Created</p>
                                        <p className="text-stone-100">
                                            {formatDate(religion.created_at)}
                                        </p>
                                    </div>
                                    {religion.icon && (
                                        <div>
                                            <p className="text-xs text-stone-500 uppercase">Icon</p>
                                            <p className="text-stone-100">{religion.icon}</p>
                                        </div>
                                    )}
                                    {religion.color && (
                                        <div>
                                            <p className="text-xs text-stone-500 uppercase">
                                                Color
                                            </p>
                                            <div className="flex items-center gap-2">
                                                <div
                                                    className="size-4 rounded"
                                                    style={{ backgroundColor: religion.color }}
                                                />
                                                <p className="text-stone-100">{religion.color}</p>
                                            </div>
                                        </div>
                                    )}
                                </div>
                            </CardContent>
                        </Card>

                        {/* Hideout (for cults) */}
                        {isCult && religion.hideout_tier !== null && religion.hideout_tier > 0 && (
                            <Card className="border-stone-800 bg-stone-900/50">
                                <CardHeader>
                                    <CardTitle className="flex items-center gap-2 text-stone-100">
                                        <Home className="size-5" />
                                        Hideout
                                    </CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-3">
                                    <div>
                                        <p className="text-xs text-stone-500 uppercase">Tier</p>
                                        <div className="flex items-center gap-2">
                                            <Badge className="bg-red-900/30 text-red-400">
                                                Tier {religion.hideout_tier}
                                            </Badge>
                                            <span className="text-stone-100">
                                                {religion.hideout_name}
                                            </span>
                                        </div>
                                    </div>
                                    {religion.hideout_location_name && (
                                        <div>
                                            <p className="text-xs text-stone-500 uppercase">
                                                Location
                                            </p>
                                            <p className="text-stone-100">
                                                {religion.hideout_location_name}
                                                {religion.hideout_location_type && (
                                                    <span className="text-stone-500 ml-1">
                                                        ({religion.hideout_location_type})
                                                    </span>
                                                )}
                                            </p>
                                        </div>
                                    )}
                                </CardContent>
                            </Card>
                        )}

                        {/* Beliefs */}
                        <Card className="border-stone-800 bg-stone-900/50">
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2 text-stone-100">
                                    <Sparkles className="size-5" />
                                    Beliefs ({beliefs.length})
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                {beliefs.length === 0 ? (
                                    <p className="text-stone-500 text-center py-4">
                                        No beliefs selected
                                    </p>
                                ) : (
                                    <div className="space-y-3">
                                        {beliefs.map((belief) => (
                                            <div
                                                key={belief.id}
                                                className="p-3 rounded-lg bg-stone-800/30"
                                            >
                                                <p className="font-medium text-stone-100">
                                                    {belief.name}
                                                </p>
                                                {belief.description && (
                                                    <p className="text-sm text-stone-400 mt-1">
                                                        {belief.description}
                                                    </p>
                                                )}
                                                {belief.effects &&
                                                    Object.keys(belief.effects).length > 0 && (
                                                        <div className="flex flex-wrap gap-1 mt-2">
                                                            {Object.entries(belief.effects).map(
                                                                ([stat, value]) => (
                                                                    <Badge
                                                                        key={stat}
                                                                        variant="secondary"
                                                                        className={cn(
                                                                            "text-xs",
                                                                            value > 0
                                                                                ? "bg-green-900/30 text-green-400"
                                                                                : "bg-red-900/30 text-red-400",
                                                                        )}
                                                                    >
                                                                        {stat}:{" "}
                                                                        {value > 0 ? "+" : ""}
                                                                        {value}
                                                                    </Badge>
                                                                ),
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

                    {/* Members */}
                    <Card className="border-stone-800 bg-stone-900/50 lg:col-span-2">
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-stone-100">
                                <Users className="size-5" />
                                Members ({members.length})
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            {members.length === 0 ? (
                                <p className="text-stone-500 text-center py-8">No members</p>
                            ) : (
                                <div className="overflow-x-auto">
                                    <table className="w-full">
                                        <thead>
                                            <tr className="border-b border-stone-800">
                                                <th className="px-3 py-2 text-left text-xs font-medium uppercase tracking-wider text-stone-400">
                                                    User
                                                </th>
                                                <th className="px-3 py-2 text-left text-xs font-medium uppercase tracking-wider text-stone-400">
                                                    Rank
                                                </th>
                                                <th className="px-3 py-2 text-left text-xs font-medium uppercase tracking-wider text-stone-400">
                                                    Devotion
                                                </th>
                                                <th className="px-3 py-2 text-left text-xs font-medium uppercase tracking-wider text-stone-400">
                                                    Joined
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y divide-stone-800">
                                            {members.map((member) => (
                                                <tr
                                                    key={member.id}
                                                    className="hover:bg-stone-800/50 transition"
                                                >
                                                    <td className="px-3 py-2">
                                                        {member.user ? (
                                                            <Link
                                                                href={showUser.url(member.user.id)}
                                                                className="text-blue-400 hover:underline"
                                                            >
                                                                {member.user.username}
                                                            </Link>
                                                        ) : (
                                                            <span className="text-stone-500">
                                                                Unknown
                                                            </span>
                                                        )}
                                                    </td>
                                                    <td className="px-3 py-2">
                                                        <Badge
                                                            variant="secondary"
                                                            className={getRankColor(member.rank)}
                                                        >
                                                            {member.rank_display}
                                                        </Badge>
                                                    </td>
                                                    <td className="px-3 py-2 text-stone-100">
                                                        {member.devotion.toLocaleString()}
                                                    </td>
                                                    <td className="px-3 py-2 text-stone-400">
                                                        {member.joined_at
                                                            ? formatDistanceToNow(
                                                                  new Date(member.joined_at),
                                                                  {
                                                                      addSuffix: true,
                                                                  },
                                                              )
                                                            : "—"}
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AdminLayout>
    );
}
