import { useState } from 'react';
import { formatDistanceToNow } from 'date-fns';
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
} from 'lucide-react';
import { cn } from '@/lib/utils';
import { Button } from '@/components/ui/button';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

interface ActivityItem {
    id: number;
    activity_type: string;
    activity_subtype: string | null;
    description: string;
    location_type: string;
    created_at: string;
}

interface Props {
    activities: ActivityItem[];
}

const activityConfig: Record<
    string,
    { icon: typeof Activity; color: string; bgColor: string }
> = {
    training: { icon: Swords, color: 'text-blue-400', bgColor: 'bg-blue-900/30' },
    gathering: { icon: Axe, color: 'text-green-400', bgColor: 'bg-green-900/30' },
    crafting: { icon: Hammer, color: 'text-orange-400', bgColor: 'bg-orange-900/30' },
    trading: { icon: ShoppingCart, color: 'text-yellow-400', bgColor: 'bg-yellow-900/30' },
    healing: { icon: Cross, color: 'text-red-400', bgColor: 'bg-red-900/30' },
    blessing: { icon: Church, color: 'text-purple-400', bgColor: 'bg-purple-900/30' },
    banking: { icon: Banknote, color: 'text-emerald-400', bgColor: 'bg-emerald-900/30' },
    working: { icon: Briefcase, color: 'text-cyan-400', bgColor: 'bg-cyan-900/30' },
    farming: { icon: Wheat, color: 'text-lime-400', bgColor: 'bg-lime-900/30' },
    travel: { icon: Footprints, color: 'text-amber-400', bgColor: 'bg-amber-900/30' },
    rest: { icon: Bed, color: 'text-indigo-400', bgColor: 'bg-indigo-900/30' },
    cooking: { icon: ChefHat, color: 'text-rose-400', bgColor: 'bg-rose-900/30' },
};

const ITEMS_PER_PAGE = 10;

export function ActivityTable({ activities }: Props) {
    const [filter, setFilter] = useState<string>('all');
    const [page, setPage] = useState(1);

    const filteredActivities =
        filter === 'all'
            ? activities
            : activities.filter((a) => a.activity_type === filter);

    const totalPages = Math.ceil(filteredActivities.length / ITEMS_PER_PAGE);
    const paginatedActivities = filteredActivities.slice(
        (page - 1) * ITEMS_PER_PAGE,
        page * ITEMS_PER_PAGE
    );

    const activityTypes = [...new Set(activities.map((a) => a.activity_type))];

    const getActivityConfig = (type: string) => {
        return (
            activityConfig[type] || {
                icon: Activity,
                color: 'text-stone-400',
                bgColor: 'bg-stone-900/30',
            }
        );
    };

    return (
        <div className="space-y-4">
            <div className="flex items-center justify-between">
                <Select
                    value={filter}
                    onValueChange={(value) => {
                        setFilter(value);
                        setPage(1);
                    }}
                >
                    <SelectTrigger className="w-[180px] border-stone-700 bg-stone-900/50">
                        <SelectValue placeholder="Filter by type" />
                    </SelectTrigger>
                    <SelectContent className="border-stone-700 bg-stone-900">
                        <SelectItem value="all">All Activities</SelectItem>
                        {activityTypes.map((type) => (
                            <SelectItem key={type} value={type} className="capitalize">
                                {type}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
                <span className="text-sm text-stone-500">
                    {filteredActivities.length} activities
                </span>
            </div>

            {paginatedActivities.length === 0 ? (
                <p className="py-8 text-center text-stone-500">No activities found</p>
            ) : (
                <div className="space-y-2">
                    {paginatedActivities.map((activity) => {
                        const config = getActivityConfig(activity.activity_type);
                        const Icon = config.icon;
                        return (
                            <div
                                key={activity.id}
                                className="flex items-start gap-3 rounded-lg border border-stone-800 bg-stone-900/30 p-3"
                            >
                                <div className={cn('rounded-lg p-2', config.bgColor)}>
                                    <Icon className={cn('size-4', config.color)} />
                                </div>
                                <div className="min-w-0 flex-1">
                                    <div className="flex items-center gap-2">
                                        <span
                                            className={cn(
                                                'text-sm font-medium capitalize',
                                                config.color
                                            )}
                                        >
                                            {activity.activity_type}
                                        </span>
                                        {activity.activity_subtype && (
                                            <span className="text-xs text-stone-500">
                                                ({activity.activity_subtype})
                                            </span>
                                        )}
                                        <span className="text-xs text-stone-600">
                                            {formatDistanceToNow(new Date(activity.created_at), {
                                                addSuffix: true,
                                            })}
                                        </span>
                                    </div>
                                    <p className="truncate text-sm text-stone-400">
                                        {activity.description}
                                    </p>
                                    <p className="mt-1 text-xs text-stone-600">
                                        Location:{' '}
                                        <span className="capitalize">
                                            {activity.location_type}
                                        </span>
                                    </p>
                                </div>
                            </div>
                        );
                    })}
                </div>
            )}

            {totalPages > 1 && (
                <div className="flex items-center justify-between pt-4">
                    <Button
                        variant="outline"
                        size="sm"
                        onClick={() => setPage((p) => Math.max(1, p - 1))}
                        disabled={page === 1}
                        className="border-stone-700"
                    >
                        Previous
                    </Button>
                    <span className="text-sm text-stone-500">
                        Page {page} of {totalPages}
                    </span>
                    <Button
                        variant="outline"
                        size="sm"
                        onClick={() => setPage((p) => Math.min(totalPages, p + 1))}
                        disabled={page === totalPages}
                        className="border-stone-700"
                    >
                        Next
                    </Button>
                </div>
            )}
        </div>
    );
}
