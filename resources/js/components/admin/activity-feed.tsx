import { Link } from "@inertiajs/react";
import { formatDistanceToNow } from "date-fns";
import {
    Activity,
    Axe,
    Banknote,
    Bed,
    Briefcase,
    ChefHat,
    Church,
    Cross,
    Footprints,
    Hammer,
    ShoppingCart,
    Swords,
    Wheat,
} from "lucide-react";
import { show as showUser } from "@/actions/App/Http/Controllers/Admin/UserController";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";

interface ActivityItem {
    id: number;
    username: string;
    user_id: number;
    activity_type: string;
    activity_subtype: string | null;
    description: string;
    location_type: string;
    location_name: string | null;
    created_at: string;
}

interface Props {
    activities: ActivityItem[];
}

const activityConfig: Record<string, { icon: typeof Activity; color: string; bgColor: string }> = {
    training: { icon: Swords, color: "text-blue-400", bgColor: "bg-blue-900/30" },
    gathering: { icon: Axe, color: "text-green-400", bgColor: "bg-green-900/30" },
    crafting: { icon: Hammer, color: "text-orange-400", bgColor: "bg-orange-900/30" },
    trading: { icon: ShoppingCart, color: "text-yellow-400", bgColor: "bg-yellow-900/30" },
    healing: { icon: Cross, color: "text-red-400", bgColor: "bg-red-900/30" },
    blessing: { icon: Church, color: "text-purple-400", bgColor: "bg-purple-900/30" },
    banking: { icon: Banknote, color: "text-emerald-400", bgColor: "bg-emerald-900/30" },
    working: { icon: Briefcase, color: "text-cyan-400", bgColor: "bg-cyan-900/30" },
    farming: { icon: Wheat, color: "text-lime-400", bgColor: "bg-lime-900/30" },
    travel: { icon: Footprints, color: "text-amber-400", bgColor: "bg-amber-900/30" },
    rest: { icon: Bed, color: "text-indigo-400", bgColor: "bg-indigo-900/30" },
    cooking: { icon: ChefHat, color: "text-rose-400", bgColor: "bg-rose-900/30" },
};

export function ActivityFeed({ activities }: Props) {
    const getActivityConfig = (type: string) => {
        return (
            activityConfig[type] || {
                icon: Activity,
                color: "text-stone-400",
                bgColor: "bg-stone-900/30",
            }
        );
    };

    return (
        <Card className="border-stone-800 bg-stone-900/50">
            <CardHeader>
                <CardTitle className="flex items-center gap-2 text-stone-100">
                    <Activity className="size-5 text-blue-400" />
                    Global Activity
                </CardTitle>
                <CardDescription className="text-stone-400">
                    Recent player activity across all locations
                </CardDescription>
            </CardHeader>
            <CardContent>
                {activities.length === 0 ? (
                    <p className="py-8 text-center text-stone-500">No recent activity</p>
                ) : (
                    <div className="space-y-3">
                        {activities.map((activity) => {
                            const config = getActivityConfig(activity.activity_type);
                            const Icon = config.icon;
                            return (
                                <div
                                    key={activity.id}
                                    className="flex items-start gap-3 rounded-lg border border-stone-800 bg-stone-900/30 p-3"
                                >
                                    <div className={`rounded-lg ${config.bgColor} p-2`}>
                                        <Icon className={`size-4 ${config.color}`} />
                                    </div>
                                    <div className="min-w-0 flex-1">
                                        <div className="flex items-center gap-2">
                                            <Link
                                                href={showUser.url(activity.user_id)}
                                                className="font-medium text-stone-100 hover:text-blue-400 hover:underline"
                                            >
                                                {activity.username}
                                            </Link>
                                            <span className="text-stone-500">
                                                {formatDistanceToNow(
                                                    new Date(activity.created_at),
                                                    { addSuffix: true },
                                                )}
                                            </span>
                                        </div>
                                        <p className="truncate text-sm text-stone-400">
                                            {activity.description}
                                        </p>
                                        {activity.location_name && (
                                            <p className="mt-1 text-xs text-stone-500">
                                                <span className="capitalize">
                                                    {activity.location_type}
                                                </span>
                                                : {activity.location_name}
                                            </p>
                                        )}
                                    </div>
                                </div>
                            );
                        })}
                    </div>
                )}
            </CardContent>
        </Card>
    );
}
