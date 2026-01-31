import { Link } from "@inertiajs/react";
import {
    Anchor,
    Banknote,
    Beer,
    Building2,
    Church,
    Dumbbell,
    Hammer,
    HeartPulse,
    Landmark,
    Pickaxe,
    Shield,
    Sparkles,
    Store,
    Briefcase,
    type LucideIcon,
} from "lucide-react";
import { cn } from "@/lib/utils";

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
    const Icon = icon ? iconMap[icon] || Store : Store;

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
    // Build location path
    const locationPaths: Record<string, string> = {
        village: "villages",
        town: "towns",
        barony: "baronies",
        duchy: "duchies",
        kingdom: "kingdoms",
    };

    const basePath = locationPaths[locationType] || locationType + "s";

    return (
        <div
            className={cn(
                "grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5",
                className,
            )}
        >
            {services.map((service) => {
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
