import { Link, usePage } from "@inertiajs/react";
import { useCurrentUrl } from "@/hooks/use-current-url";
import {
    Anchor,
    Anvil,
    Banknote,
    Beer,
    Briefcase,
    Building2,
    Church,
    Dumbbell,
    Flame,
    FlaskConical,
    Hammer,
    Hand,
    HeartPulse,
    Landmark,
    MessageSquare,
    Pickaxe,
    Receipt,
    Shield,
    Sparkles,
    Star,
    Store,
    Warehouse,
    Wheat,
    type LucideIcon,
} from "lucide-react";
import { useSidebar } from "@/components/ui/sidebar";
import { Tooltip, TooltipContent, TooltipTrigger } from "@/components/ui/tooltip";

interface Favorite {
    service_id: string;
    name: string;
    icon: string;
    route: string;
}

interface LocationData {
    type: string;
    id: number | null;
}

interface SidebarData {
    favorites: Favorite[];
    location: LocationData | null;
}

// Map icon names to Lucide icons (same as service-card.tsx)
const iconMap: Record<string, LucideIcon> = {
    swords: Dumbbell,
    pickaxe: Pickaxe,
    hammer: Hammer,
    anvil: Anvil,
    store: Store,
    landmark: Landmark,
    "heart-pulse": HeartPulse,
    sparkles: Sparkles,
    briefcase: Briefcase,
    ship: Anchor,
    beer: Beer,
    banknote: Banknote,
    shield: Shield,
    "building-columns": Building2,
    hospital: Church,
    "message-square": MessageSquare,
    receipt: Receipt,
    hand: Hand,
    flame: Flame,
    wheat: Wheat,
    warehouse: Warehouse,
    "flask-conical": FlaskConical,
};

// Location type to URL path mapping
const locationPaths: Record<string, string> = {
    village: "villages",
    barony: "baronies",
    town: "towns",
    duchy: "duchies",
    kingdom: "kingdoms",
};

// Services available at each location type (matching backend LocationServices)
const locationServices: Record<string, string[]> = {
    village: [
        "training",
        "gathering",
        "crafting",
        "forge",
        "anvil",
        "market",
        "bank",
        "healer",
        "shrine",
        "jobs",
        "port",
        "stables",
        "tavern",
        "thieving",
        "apothecary",
        "farming",
    ],
    town: [
        "training",
        "gathering",
        "crafting",
        "forge",
        "anvil",
        "market",
        "bank",
        "infirmary",
        "shrine",
        "jobs",
        "port",
        "hall",
        "stables",
        "tavern",
        "thieving",
        "apothecary",
        "farming",
    ],
    barony: [
        "training",
        "crafting",
        "forge",
        "anvil",
        "market",
        "bank",
        "infirmary",
        "shrine",
        "jobs",
        "stables",
        "tavern",
        "thieving",
        "businesses",
        "chat",
        "taxes",
        "apothecary",
    ],
    duchy: [
        "training",
        "crafting",
        "forge",
        "anvil",
        "shrine",
        "jobs",
        "stables",
        "tavern",
        "thieving",
        "apothecary",
    ],
    kingdom: [
        "training",
        "crafting",
        "forge",
        "anvil",
        "shrine",
        "jobs",
        "stables",
        "tavern",
        "thieving",
        "apothecary",
    ],
};

function FavoriteBadge({
    favorite,
    location,
}: {
    favorite: Favorite;
    location: LocationData | null;
}) {
    const { isCurrentUrl } = useCurrentUrl();
    const Icon = iconMap[favorite.icon] || Store;

    // Check if service is available at current location
    const isAvailable =
        location?.type &&
        location?.id &&
        locationServices[location.type]?.includes(favorite.service_id);

    // Build the URL for the service at current location
    const href =
        isAvailable && location?.type && location?.id
            ? `/${locationPaths[location.type]}/${location.id}/${favorite.route}`
            : null;

    // Check if this is the current page
    const isActive = href && isCurrentUrl(href);

    const content = (
        <div
            className={`relative flex aspect-square items-center justify-center overflow-hidden rounded-lg border bg-sidebar-accent/30 hover:bg-sidebar-accent/50 ${
                isActive ? "border-amber-500" : "border-sidebar-border"
            }`}
        >
            <Icon className="h-5 w-5 text-sidebar-foreground/80" />
            {/* Star badge in top-right */}
            <Star className="absolute right-0.5 top-0.5 h-2.5 w-2.5 fill-amber-400 text-amber-400" />
        </div>
    );

    return (
        <Tooltip>
            <TooltipTrigger asChild>
                {href ? (
                    <Link href={href} prefetch>
                        {content}
                    </Link>
                ) : (
                    <div className="cursor-not-allowed">{content}</div>
                )}
            </TooltipTrigger>
            <TooltipContent side="top">
                <div className="font-pixel text-xs">{favorite.name}</div>
                {!isAvailable && (
                    <div className="font-pixel text-[9px] text-muted-foreground">
                        Not available here
                    </div>
                )}
            </TooltipContent>
        </Tooltip>
    );
}

export function NavFavorites() {
    const { sidebar } = usePage<{ sidebar: SidebarData | null }>().props;
    const { state } = useSidebar();

    if (!sidebar || !sidebar.favorites || sidebar.favorites.length === 0) return null;

    // When collapsed, show nothing
    if (state === "collapsed") {
        return null;
    }

    return (
        <div className="px-2 pb-2">
            <div className="grid grid-cols-5 gap-1.5">
                {sidebar.favorites.map((favorite) => (
                    <FavoriteBadge
                        key={favorite.service_id}
                        favorite={favorite}
                        location={sidebar.location}
                    />
                ))}
            </div>
        </div>
    );
}
