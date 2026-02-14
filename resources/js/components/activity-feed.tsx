import {
    Dumbbell,
    Hammer,
    Pickaxe,
    Sparkles,
    Store,
    HeartPulse,
    Banknote,
    Briefcase,
    Wheat,
    MapPin,
    Coins,
    Crown,
    AlertTriangle,
    ArrowUpRight,
    Users,
    type LucideIcon,
} from "lucide-react";
import { cn } from "@/lib/utils";

interface ActivityLogEntry {
    id: number;
    username: string | null;
    activity_type?: string;
    description: string;
    subtype: string | null;
    metadata: Record<string, unknown> | null;
    created_at: string;
    time_ago: string;
}

interface ActivityFeedProps {
    activities: ActivityLogEntry[];
    activityType?: string;
    title?: string;
    emptyMessage?: string;
    maxHeight?: string;
    showIcon?: boolean;
    className?: string;
}

// Map activity types to icons
const activityIcons: Record<string, LucideIcon> = {
    // Player activities
    training: Dumbbell,
    gathering: Pickaxe,
    crafting: Hammer,
    trading: Store,
    healing: HeartPulse,
    blessing: Sparkles,
    banking: Banknote,
    working: Briefcase,
    farming: Wheat,
    travel: MapPin,
    // System activities
    tax_collection: Coins,
    salary_payment: Banknote,
    salary_failed: AlertTriangle,
    upstream_tax: ArrowUpRight,
    role_change: Crown,
    disaster: AlertTriangle,
    migration: Users,
};

// Get color classes for activity types
const activityColors: Record<string, string> = {
    // Player activities
    training: "text-red-400",
    gathering: "text-amber-400",
    crafting: "text-orange-400",
    trading: "text-green-400",
    healing: "text-pink-400",
    blessing: "text-purple-400",
    banking: "text-yellow-400",
    working: "text-blue-400",
    farming: "text-emerald-400",
    travel: "text-cyan-400",
    // System activities
    tax_collection: "text-yellow-500",
    salary_payment: "text-green-500",
    salary_failed: "text-red-500",
    upstream_tax: "text-amber-500",
    role_change: "text-purple-500",
    disaster: "text-red-600",
    migration: "text-cyan-500",
};

function ActivityItem({
    activity,
    activityType,
    showIcon = true,
}: {
    activity: ActivityLogEntry;
    activityType?: string;
    showIcon?: boolean;
}) {
    // Use activity's own type if available, otherwise fall back to prop
    const effectiveType = activity.activity_type || activityType;
    const Icon = effectiveType ? activityIcons[effectiveType] || MapPin : MapPin;
    const colorClass = effectiveType
        ? activityColors[effectiveType] || "text-muted-foreground"
        : "text-muted-foreground";

    const isSystemEvent = !activity.username;
    const displayName = activity.username || "[System]";

    return (
        <div className="flex items-start gap-2 py-2 first:pt-0 last:pb-0">
            {showIcon && (
                <div className={cn("mt-0.5 flex-shrink-0", colorClass)}>
                    <Icon className="h-4 w-4" />
                </div>
            )}
            <div className="min-w-0 flex-1">
                <p className="text-sm leading-tight">
                    {isSystemEvent ? (
                        <span className="text-muted-foreground">{activity.description}</span>
                    ) : (
                        <>
                            <span className="font-medium text-foreground">{displayName}</span>{" "}
                            <span className="text-muted-foreground">
                                {formatDescription(activity.description, activity.username || "")}
                            </span>
                        </>
                    )}
                </p>
                {activity.metadata && (
                    <div className="mt-0.5 flex flex-wrap gap-2 text-xs text-muted-foreground">
                        {activity.metadata.xp_gained && (
                            <span className="text-primary">
                                +{activity.metadata.xp_gained as number} XP
                            </span>
                        )}
                        {activity.metadata.leveled_up && (
                            <span className="text-yellow-500">Level Up!</span>
                        )}
                        {activity.metadata.quantity && activity.metadata.item && (
                            <span>
                                {activity.metadata.quantity as number}x{" "}
                                {activity.metadata.item as string}
                            </span>
                        )}
                        {activity.metadata.amount && !activity.metadata.xp_gained && (
                            <span className="text-yellow-400">
                                {activity.metadata.amount as number}g
                            </span>
                        )}
                    </div>
                )}
            </div>
            <span className="flex-shrink-0 text-xs text-muted-foreground">{activity.time_ago}</span>
        </div>
    );
}

// Remove username from description if it starts with it
function formatDescription(description: string, username: string): string {
    if (description.startsWith(username + " ")) {
        return description.substring(username.length + 1);
    }
    return description;
}

export function ActivityFeed({
    activities,
    activityType,
    title = "Recent Activity",
    emptyMessage = "No recent activity",
    maxHeight = "300px",
    showIcon = true,
    className,
}: ActivityFeedProps) {
    if (!activities || activities.length === 0) {
        return (
            <div className={cn("rounded-lg border bg-card p-4", className)}>
                {title && <h3 className="mb-3 font-pixel text-sm font-medium">{title}</h3>}
                <p className="text-center text-sm text-muted-foreground">{emptyMessage}</p>
            </div>
        );
    }

    return (
        <div className={cn("rounded-lg border bg-card", className)}>
            {title && (
                <div className="border-b px-4 py-3">
                    <h3 className="font-pixel text-sm font-medium">{title}</h3>
                </div>
            )}
            <div style={{ maxHeight }} className="overflow-y-auto px-4">
                <div className="divide-y">
                    {activities.map((activity) => (
                        <ActivityItem
                            key={activity.id}
                            activity={activity}
                            activityType={activityType}
                            showIcon={showIcon}
                        />
                    ))}
                </div>
            </div>
        </div>
    );
}

interface CompactActivityFeedProps {
    activities: ActivityLogEntry[];
    activityType?: string;
    limit?: number;
    className?: string;
}

export function CompactActivityFeed({
    activities,
    activityType,
    limit = 5,
    className,
}: CompactActivityFeedProps) {
    const displayActivities = activities.slice(0, limit);

    if (!displayActivities || displayActivities.length === 0) {
        return null;
    }

    return (
        <div className={cn("space-y-1", className)}>
            {displayActivities.map((activity) => {
                const effectiveType = activity.activity_type || activityType;
                const Icon = effectiveType ? activityIcons[effectiveType] || MapPin : MapPin;
                const colorClass = effectiveType
                    ? activityColors[effectiveType] || "text-muted-foreground"
                    : "text-muted-foreground";
                const isSystemEvent = !activity.username;

                return (
                    <div
                        key={activity.id}
                        className="flex items-center gap-2 text-xs text-muted-foreground"
                    >
                        <Icon className={cn("h-3 w-3 flex-shrink-0", colorClass)} />
                        {isSystemEvent ? (
                            <span className="truncate">{activity.description}</span>
                        ) : (
                            <>
                                <span className="font-medium text-foreground">
                                    {activity.username}
                                </span>
                                <span className="truncate">
                                    {formatDescription(
                                        activity.description,
                                        activity.username || "",
                                    )}
                                </span>
                            </>
                        )}
                        <span className="ml-auto flex-shrink-0">{activity.time_ago}</span>
                    </div>
                );
            })}
        </div>
    );
}
