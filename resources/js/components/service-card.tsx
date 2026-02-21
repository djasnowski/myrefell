import { Link, router, usePage } from "@inertiajs/react";
import {
    Anchor,
    Anvil,
    Banknote,
    Beer,
    Building2,
    Briefcase,
    Church,
    Dumbbell,
    Flame,
    FlaskConical,
    Footprints,
    Hammer,
    Hand,
    HeartPulse,
    Home,
    Landmark,
    MessageSquare,
    Newspaper,
    Pickaxe,
    Receipt,
    Search,
    Shield,
    Sparkles,
    Star,
    Store,
    Target,
    Warehouse,
    Wheat,
    type LucideIcon,
} from "lucide-react";
import { useState } from "react";
import { cn } from "@/lib/utils";

interface Favorite {
    service_id: string;
    name: string;
    icon: string;
    route: string;
}

interface SidebarData {
    favorites: Favorite[];
    has_house?: boolean;
    house_url?: string;
}

interface ServiceCardProps {
    serviceId: string;
    name: string;
    description: string;
    href: string;
    icon?: string;
    disabled?: boolean;
    badge?: string | number;
}

// Map icon names to Lucide icons
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
    footprints: Footprints,
    target: Target,
    home: Home,
    newspaper: Newspaper,
};

export function ServiceCard({
    serviceId,
    name,
    description,
    href,
    icon,
    disabled = false,
    badge,
}: ServiceCardProps) {
    const { sidebar } = usePage<{ sidebar: SidebarData | null }>().props;
    const [isToggling, setIsToggling] = useState(false);

    const Icon = icon ? iconMap[icon] || Store : Store;

    // Check if this service is favorited
    const isFavorited = sidebar?.favorites?.some((f) => f.service_id === serviceId) ?? false;

    const handleToggleFavorite = (e: React.MouseEvent) => {
        e.preventDefault();
        e.stopPropagation();

        if (isToggling) return;

        setIsToggling(true);
        router.post(
            "/services/favorites/toggle",
            { service_id: serviceId },
            {
                preserveScroll: true,
                onSuccess: () => {
                    router.reload();
                },
                onFinish: () => {
                    setIsToggling(false);
                },
            },
        );
    };

    const content = (
        <div
            data-service-id={serviceId}
            className={cn(
                "group relative flex h-full flex-col items-center gap-2 rounded-lg border p-3 text-center transition-all sm:p-4",
                disabled
                    ? "cursor-not-allowed border-muted bg-muted/20 opacity-50"
                    : "border-border bg-card hover:border-primary/50 hover:bg-accent hover:shadow-md",
            )}
        >
            {/* Favorite star button */}
            {!disabled && (
                <button
                    type="button"
                    onClick={handleToggleFavorite}
                    disabled={isToggling}
                    className={cn(
                        "absolute top-1.5 right-1.5 z-10 rounded-full p-1 transition-all",
                        "hover:bg-amber-100 dark:hover:bg-amber-900/30",
                        isToggling && "opacity-50",
                    )}
                    title={isFavorited ? "Remove from favorites" : "Add to favorites"}
                >
                    <Star
                        className={cn(
                            "h-4 w-4 transition-colors",
                            isFavorited
                                ? "fill-amber-400 text-amber-400"
                                : "text-muted-foreground/40 hover:text-amber-400",
                        )}
                    />
                </button>
            )}
            {badge && (
                <span className="absolute -top-2 -right-2 flex h-5 min-w-5 items-center justify-center rounded-full bg-primary px-1 text-[10px] font-bold text-primary-foreground">
                    {badge}
                </span>
            )}
            <div
                className={cn(
                    "flex h-10 w-10 shrink-0 items-center justify-center rounded-full transition-colors sm:h-12 sm:w-12",
                    disabled ? "bg-muted/50" : "bg-primary/10 group-hover:bg-primary/20",
                )}
            >
                <Icon
                    className={cn(
                        "h-5 w-5 sm:h-6 sm:w-6",
                        disabled ? "text-muted-foreground" : "text-primary",
                    )}
                />
            </div>
            <div className="flex flex-1 flex-col justify-center">
                <h3
                    className={cn(
                        "font-pixel text-xs font-medium sm:text-sm",
                        disabled ? "text-muted-foreground" : "",
                    )}
                >
                    {name}
                </h3>
                <p className="mt-0.5 text-[10px] text-muted-foreground sm:text-xs">{description}</p>
            </div>
        </div>
    );

    if (disabled) {
        return content;
    }

    return (
        <Link href={href} prefetch className="h-full">
            {content}
        </Link>
    );
}

interface ServicesGridProps {
    services: Array<{
        id: string;
        name: string;
        description: string;
        icon: string;
        route: string;
        requires_house?: boolean;
    }>;
    locationType: string;
    locationId: number;
    isPort?: boolean;
    className?: string;
    title?: string;
}

export function ServicesGrid({
    services,
    locationType,
    locationId,
    isPort = false,
    className,
    title = "Services",
}: ServicesGridProps) {
    const { sidebar } = usePage<{ sidebar: SidebarData | null }>().props;
    const [searchQuery, setSearchQuery] = useState("");

    // Build location path
    const locationPaths: Record<string, string> = {
        village: "villages",
        town: "towns",
        barony: "baronies",
        duchy: "duchies",
        kingdom: "kingdoms",
    };

    const basePath = locationPaths[locationType] || locationType + "s";

    const hasHouse = sidebar?.has_house ?? false;

    // Filter services by search query and requirements
    const filteredServices = services.filter((service) => {
        // Skip port services if location is not a port
        if (service.id === "port" && !isPort) {
            return false;
        }

        // Skip house service if player doesn't have a house
        if (service.requires_house && !hasHouse) {
            return false;
        }

        if (!searchQuery.trim()) {
            return true;
        }

        const query = searchQuery.toLowerCase();
        return (
            service.name.toLowerCase().includes(query) ||
            service.description.toLowerCase().includes(query)
        );
    });

    // Sort services: favorites first, then alphabetically
    const sortedServices = [...filteredServices].sort((a, b) => {
        const aIsFavorited = sidebar?.favorites?.some((f) => f.service_id === a.id) ?? false;
        const bIsFavorited = sidebar?.favorites?.some((f) => f.service_id === b.id) ?? false;

        if (aIsFavorited && !bIsFavorited) return -1;
        if (!aIsFavorited && bIsFavorited) return 1;
        return 0; // Keep original order within each group
    });

    return (
        <div className={className}>
            {/* Header with title and search */}
            <div className="mb-3 flex items-center justify-between gap-4">
                <h2 className="font-pixel text-sm text-stone-400">{title}</h2>
                <div className="relative w-48">
                    <Search className="absolute left-2.5 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-muted-foreground" />
                    <input
                        type="text"
                        placeholder="Search..."
                        value={searchQuery}
                        onChange={(e) => setSearchQuery(e.target.value)}
                        className="w-full rounded-md border border-border bg-card py-1.5 pl-8 pr-3 text-xs placeholder:text-muted-foreground focus:border-primary/50 focus:outline-none focus:ring-1 focus:ring-primary/50"
                    />
                </div>
            </div>

            {/* Services grid */}
            {sortedServices.length > 0 ? (
                <div className="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6">
                    {sortedServices.map((service) => (
                        <ServiceCard
                            key={service.id}
                            serviceId={service.id}
                            name={service.name}
                            description={service.description}
                            href={
                                service.requires_house && sidebar?.house_url
                                    ? sidebar.house_url
                                    : `/${basePath}/${locationId}/${service.route}`
                            }
                            icon={service.icon}
                        />
                    ))}
                </div>
            ) : (
                <div className="rounded-lg border border-border bg-card/50 p-6 text-center">
                    <p className="text-sm text-muted-foreground">
                        No services found matching "{searchQuery}"
                    </p>
                </div>
            )}
        </div>
    );
}
