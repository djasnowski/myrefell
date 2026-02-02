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
    Landmark,
    MessageSquare,
    Pickaxe,
    Receipt,
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
                "group relative flex flex-col items-center gap-2 rounded-lg border p-4 text-center transition-all",
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
                    "flex h-12 w-12 items-center justify-center rounded-full transition-colors",
                    disabled ? "bg-muted/50" : "bg-primary/10 group-hover:bg-primary/20",
                )}
            >
                <Icon
                    className={cn("h-6 w-6", disabled ? "text-muted-foreground" : "text-primary")}
                />
            </div>
            <div>
                <h3
                    className={cn(
                        "font-pixel text-sm font-medium",
                        disabled ? "text-muted-foreground" : "",
                    )}
                >
                    {name}
                </h3>
                <p className="mt-0.5 text-xs text-muted-foreground">{description}</p>
            </div>
        </div>
    );

    if (disabled) {
        return content;
    }

    return (
        <Link href={href} prefetch>
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
    }>;
    locationType: string;
    locationId: number;
    isPort?: boolean;
    className?: string;
}

export function ServicesGrid({
    services,
    locationType,
    locationId,
    isPort = false,
    className,
}: ServicesGridProps) {
    const { sidebar } = usePage<{ sidebar: SidebarData | null }>().props;

    // Build location path
    const locationPaths: Record<string, string> = {
        village: "villages",
        town: "towns",
        barony: "baronies",
        duchy: "duchies",
        kingdom: "kingdoms",
    };

    const basePath = locationPaths[locationType] || locationType + "s";

    // Sort services: favorites first, then alphabetically
    const sortedServices = [...services].sort((a, b) => {
        const aIsFavorited = sidebar?.favorites?.some((f) => f.service_id === a.id) ?? false;
        const bIsFavorited = sidebar?.favorites?.some((f) => f.service_id === b.id) ?? false;

        if (aIsFavorited && !bIsFavorited) return -1;
        if (!aIsFavorited && bIsFavorited) return 1;
        return 0; // Keep original order within each group
    });

    return (
        <div
            className={cn(
                "grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6",
                className,
            )}
        >
            {sortedServices.map((service) => {
                // Skip port services if location is not a port
                if (service.id === "port" && !isPort) {
                    return null;
                }

                return (
                    <ServiceCard
                        key={service.id}
                        serviceId={service.id}
                        name={service.name}
                        description={service.description}
                        href={`/${basePath}/${locationId}/${service.route}`}
                        icon={service.icon}
                    />
                );
            })}
        </div>
    );
}
