import { Link, router, usePage } from '@inertiajs/react';
import {
    Backpack,
    BarChart3,
    Castle,
    Church,
    ClipboardList,
    Clock,
    Crown,
    Dumbbell,
    Gavel,
    Hammer,
    Home,
    Loader2,
    Map,
    MapPin,
    Pickaxe,
    ScrollText,
    Shield,
    Sparkles,
    Store,
    Trees,
    UsersRound,
    Wheat,
    type LucideIcon,
} from 'lucide-react';
import { useEffect, useState } from 'react';
import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    useSidebar,
} from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/hooks/use-current-url';

interface LocationData {
    type: string;
    id: number | null;
    name: string;
    biome: string;
    is_port?: boolean;
}

interface HomeVillage {
    id: number;
    name: string;
    resident_count: number;
    barony: { id: number; name: string } | null;
    town: { id: number; name: string } | null;
    kingdom: { id: number; name: string } | null;
}

interface TravelStatus {
    is_traveling: boolean;
    destination: {
        type: string;
        id: number;
        name: string;
    };
    remaining_seconds: number;
    progress_percent: number;
    has_arrived: boolean;
}

interface PlayerContext {
    dynasty?: { id: number; name: string };
    guild?: { id: number; name: string };
    business?: { id: number; name: string };
    religion?: { id: number; name: string };
    army?: { id: number; name: string };
    has_role_at_location?: boolean;
}

interface FarmData {
    has_crops: boolean;
    crops_ready: number;
    total_plots: number;
}

interface SidebarData {
    location: LocationData | null;
    home_village: HomeVillage | null;
    travel: TravelStatus | null;
    nearby_destinations: TravelDestination[];
    context: PlayerContext;
    farm: FarmData | null;
}

interface NavItem {
    title: string;
    href: string;
    icon: LucideIcon;
    description?: string;
}

interface TravelDestination {
    type: string;
    id: number;
    name: string;
    biome?: string;
    distance?: number;
    travel_time: number;
}

// Location type icons
const locationIcons: Record<string, LucideIcon> = {
    village: Home,
    barony: Castle,
    town: Church,
    duchy: Crown,
    kingdom: Crown,
    wilderness: Trees,
};

// Location type to URL path mapping (handles irregular plurals)
const locationPaths: Record<string, string> = {
    village: 'villages',
    barony: 'baronies',
    town: 'towns',
    duchy: 'duchies',
    kingdom: 'kingdoms',
};

// Build location-scoped URL for a service
function buildLocationUrl(location: LocationData, service: string): string {
    const basePath = locationPaths[location.type] || location.type + 's';
    return `/${basePath}/${location.id}/${service}`;
}

// Get everything available at current location (merged: services, activities, common)
function getLocationItems(location: LocationData | null, farm: FarmData | null, context?: PlayerContext): NavItem[] {
    const items: NavItem[] = [];

    if (!location) {
        return items;
    }

    // If the player has a role at this location, add a roles link
    if (context?.has_role_at_location) {
        items.push({
            title: 'My Role',
            href: buildLocationUrl(location, 'roles'),
            icon: Shield,
            description: 'Manage your official duties',
        });
    }

    // Wilderness - only gathering
    if (location.type === 'wilderness') {
        items.push({
            title: 'Gathering',
            href: '/gathering',
            icon: Pickaxe,
            description: 'Mine, fish, or chop wood',
        });
        return items;
    }

    // Activities available at settlements - now location-scoped
    if (['village', 'town', 'barony', 'duchy', 'kingdom'].includes(location.type)) {
        items.push({
            title: 'Training',
            href: buildLocationUrl(location, 'training'),
            icon: Dumbbell,
            description: 'Train combat skills',
        });
    }

    // Gathering only in villages
    if (location.type === 'village') {
        items.push({
            title: 'Gathering',
            href: buildLocationUrl(location, 'gathering'),
            icon: Pickaxe,
            description: 'Mine, fish, or chop wood',
        });
    }

    // Crafting in villages, towns, and baronies
    if (['village', 'town', 'barony'].includes(location.type)) {
        items.push({
            title: 'Crafting',
            href: buildLocationUrl(location, 'crafting'),
            icon: Hammer,
            description: 'Create items',
        });
    }

    // Farming - only shows if player has crops at this location
    if (farm?.has_crops) {
        items.push({
            title: farm.crops_ready > 0 ? `Farming (${farm.crops_ready})` : 'Farming',
            href: '/farming',
            icon: Wheat,
            description: farm.crops_ready > 0 ? `${farm.crops_ready} crops ready to harvest` : 'Tend to your crops',
        });
    }

    // Court is always available at settlements
    if (['village', 'town', 'barony', 'duchy', 'kingdom'].includes(location.type)) {
        items.push({
            title: 'Court',
            href: '/crime',
            icon: Gavel,
            description: 'Justice and bounties',
        });
    }

    return items;
}

// Get icon for destination type
function getDestinationIcon(type: string): LucideIcon {
    return locationIcons[type] || MapPin;
}

// Get player actions (always visible regardless of location)
function getPlayerActions(): NavItem[] {
    return [
        {
            title: 'World Map',
            href: '/travel',
            icon: Map,
            description: 'Travel the realm',
        },
        {
            title: 'Inventory',
            href: '/inventory',
            icon: Backpack,
            description: 'Your items and equipment',
        },
        {
            title: 'Skills',
            href: '/skills',
            icon: BarChart3,
            description: 'View your skill levels',
        },
        {
            title: 'Quests',
            href: '/quests',
            icon: ScrollText,
            description: 'Active quest log',
        },
        {
            title: 'Daily Tasks',
            href: '/daily-tasks',
            icon: ClipboardList,
            description: 'Earn daily rewards',
        },
    ];
}


// Get contextual items based on player's affiliations
function getContextualItems(context: PlayerContext): NavItem[] {
    const items: NavItem[] = [];

    if (context.dynasty) {
        items.push({
            title: 'My Dynasty',
            href: `/dynasties/${context.dynasty.id}`,
            icon: Crown,
            description: context.dynasty.name,
        });
    }

    if (context.guild) {
        items.push({
            title: 'My Guild',
            href: `/guilds/${context.guild.id}`,
            icon: UsersRound,
            description: context.guild.name,
        });
    }

    if (context.business) {
        items.push({
            title: 'My Business',
            href: `/businesses/${context.business.id}`,
            icon: Store,
            description: context.business.name,
        });
    }

    if (context.religion) {
        items.push({
            title: 'My Faith',
            href: `/religions/${context.religion.id}`,
            icon: Sparkles,
            description: context.religion.name,
        });
    }

    if (context.army) {
        items.push({
            title: 'My Army',
            href: `/warfare/armies/${context.army.id}`,
            icon: Shield,
            description: context.army.name,
        });
    }

    return items;
}


function formatTime(seconds: number): string {
    const mins = Math.floor(seconds / 60);
    const secs = seconds % 60;
    if (mins > 0) {
        return `${mins}m ${secs}s`;
    }
    return `${secs}s`;
}

function TravelingIndicator({ travel }: { travel: TravelStatus }) {
    const [remaining, setRemaining] = useState(travel.remaining_seconds);
    const [arriving, setArriving] = useState(false);

    useEffect(() => {
        const interval = setInterval(() => {
            setRemaining((prev) => {
                const newVal = Math.max(0, prev - 1);
                if (newVal <= 0 && !arriving) {
                    // Call arrive endpoint to complete travel
                    setArriving(true);
                    router.post('/travel/arrive', {}, {
                        preserveScroll: true,
                        onFinish: () => {
                            router.reload();
                        },
                    });
                }
                return newVal;
            });
        }, 1000);
        return () => clearInterval(interval);
    }, [arriving]);

    const Icon = locationIcons[travel.destination.type] || MapPin;

    return (
        <Link
            href="/travel"
            className="flex items-center gap-2 rounded-md border border-amber-500/50 bg-amber-900/30 px-2 py-1.5 transition-colors hover:bg-amber-900/50"
        >
            <Loader2 className="h-4 w-4 animate-spin text-amber-400" />
            <div className="min-w-0 flex-1">
                <div className="flex items-center gap-1">
                    <Icon className="h-3 w-3 text-amber-300" />
                    <span className="truncate font-pixel text-[10px] text-amber-300">{travel.destination.name}</span>
                </div>
                <div className="flex items-center gap-1 text-amber-400/70">
                    <Clock className="h-2.5 w-2.5" />
                    <span className="font-pixel text-[9px]">{formatTime(remaining)}</span>
                </div>
            </div>
        </Link>
    );
}

export function NavLocation() {
    const { sidebar } = usePage<{ sidebar: SidebarData | null }>().props;
    const { isCurrentUrl } = useCurrentUrl();
    const { state } = useSidebar();
    const [travelingTo, setTravelingTo] = useState<string | null>(null);

    if (!sidebar) return null;

    const { location, home_village, travel, nearby_destinations, context, farm } = sidebar;
    const travelDestinations = nearby_destinations || [];
    const playerActions = getPlayerActions();
    const locationItems = getLocationItems(location, farm, context);
    const contextualItems = getContextualItems(context || {});

    // Add home village link if player is away from home
    if (home_village && location && (location.type !== 'village' || location.id !== home_village.id)) {
        playerActions.push({
            title: 'Home Village',
            href: `/villages/${home_village.id}`,
            icon: Home,
            description: home_village.name,
        });
    }

    const LocationIcon = location ? locationIcons[location.type] || MapPin : MapPin;

    const handleTravel = (dest: TravelDestination) => {
        setTravelingTo(`${dest.type}-${dest.id}`);
        router.post(
            '/travel/start',
            {
                destination_type: dest.type,
                destination_id: dest.id,
            },
            {
                preserveScroll: true,
                onFinish: () => setTravelingTo(null),
                onSuccess: () => router.visit('/travel'),
                onError: (errors) => {
                    const message = errors.travel || Object.values(errors)[0] || 'Failed to start travel';
                    alert(message);
                },
            }
        );
    };

    // Collapsed view
    if (state === 'collapsed') {
        if (travel?.is_traveling) {
            return (
                <SidebarGroup className="px-2 py-0">
                    <SidebarMenu>
                        <SidebarMenuItem>
                            <SidebarMenuButton tooltip={{ children: `Traveling to ${travel.destination.name}` }} className="justify-center">
                                <Loader2 className="h-4 w-4 animate-spin text-amber-400" />
                            </SidebarMenuButton>
                        </SidebarMenuItem>
                    </SidebarMenu>
                </SidebarGroup>
            );
        }

        return (
            <SidebarGroup className="px-2 py-0">
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton tooltip={{ children: location?.name || 'Wandering...' }} className="justify-center">
                            <LocationIcon className="h-4 w-4" />
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarGroup>
        );
    }

    // Show traveling status
    if (travel?.is_traveling) {
        return (
            <div className="px-2">
                <TravelingIndicator key={travel.remaining_seconds} travel={travel} />
            </div>
        );
    }

    return (
        <>
            {/* Current Location Header */}
            <div className="px-2">
                <Link
                    href={location?.id && locationPaths[location.type] ? `/${locationPaths[location.type]}/${location.id}` : '/dashboard'}
                    className="flex items-center gap-2 rounded-md border border-sidebar-border bg-sidebar-accent/30 px-2 py-1.5 transition-colors hover:bg-sidebar-accent/50"
                >
                    <LocationIcon className="h-4 w-4 flex-shrink-0 text-sidebar-primary" />
                    <div className="min-w-0 flex-1">
                        <div className="truncate font-pixel text-xs text-sidebar-accent-foreground">
                            {location?.name || 'Wandering...'}
                        </div>
                        {location && (
                            <div className="font-pixel text-[10px] capitalize text-sidebar-foreground/50">
                                {location.type} • {location.biome}
                            </div>
                        )}
                    </div>
                </Link>
            </div>

            {/* Player Actions - Always visible */}
            <SidebarGroup className="px-2 py-0">
                <SidebarGroupLabel>You</SidebarGroupLabel>
                <SidebarMenu>
                    {playerActions.map((item) => (
                        <SidebarMenuItem key={item.title}>
                            <SidebarMenuButton
                                asChild
                                isActive={isCurrentUrl(item.href)}
                                tooltip={{ children: item.description || item.title }}
                            >
                                <Link href={item.href} prefetch>
                                    <item.icon className="h-4 w-4" />
                                    <span>{item.title}</span>
                                </Link>
                            </SidebarMenuButton>
                        </SidebarMenuItem>
                    ))}
                </SidebarMenu>
            </SidebarGroup>

            {/* Everything at this location */}
            {locationItems.length > 0 && location && (
                <SidebarGroup className="px-2 py-0">
                    <SidebarMenu>
                        {locationItems.map((item) => (
                            <SidebarMenuItem key={item.title}>
                                <SidebarMenuButton
                                    asChild
                                    isActive={isCurrentUrl(item.href)}
                                    tooltip={{ children: item.description || item.title }}
                                >
                                    <Link href={item.href} prefetch>
                                        <item.icon className="h-4 w-4" />
                                        <span>{item.title}</span>
                                    </Link>
                                </SidebarMenuButton>
                            </SidebarMenuItem>
                        ))}
                    </SidebarMenu>
                </SidebarGroup>
            )}

            {/* Contextual Items (Dynasty, Guild, Business, Religion, Army) */}
            {contextualItems.length > 0 && (
                <SidebarGroup className="px-2 py-0">
                    <SidebarGroupLabel>Affiliations</SidebarGroupLabel>
                    <SidebarMenu>
                        {contextualItems.map((item) => (
                            <SidebarMenuItem key={item.title}>
                                <SidebarMenuButton
                                    asChild
                                    isActive={isCurrentUrl(item.href)}
                                    tooltip={{ children: item.description || item.title }}
                                >
                                    <Link href={item.href} prefetch>
                                        <item.icon className="h-4 w-4" />
                                        <span>{item.title}</span>
                                    </Link>
                                </SidebarMenuButton>
                            </SidebarMenuItem>
                        ))}
                    </SidebarMenu>
                </SidebarGroup>
            )}

            {/* Travel Destinations */}
            {travelDestinations.length > 0 && (
                <SidebarGroup className="px-2 py-0">
                    <SidebarGroupLabel>Nearby ({travelDestinations.length})</SidebarGroupLabel>
                    <SidebarMenu>
                        {travelDestinations.slice(0, 5).map((dest) => {
                            const isLoading = travelingTo === `${dest.type}-${dest.id}`;
                            const Icon = getDestinationIcon(dest.type);
                            return (
                                <SidebarMenuItem key={`${dest.type}-${dest.id}`}>
                                    <SidebarMenuButton
                                        onClick={() => handleTravel(dest)}
                                        disabled={isLoading}
                                        tooltip={{ children: `${dest.name} (${dest.travel_time} min)` }}
                                    >
                                        {isLoading ? (
                                            <Loader2 className="h-4 w-4 animate-spin" />
                                        ) : (
                                            <Icon className="h-4 w-4" />
                                        )}
                                        <span className="flex-1 truncate">{dest.name}</span>
                                        <span className="text-[10px] text-sidebar-foreground/50">{dest.travel_time}m</span>
                                    </SidebarMenuButton>
                                </SidebarMenuItem>
                            );
                        })}
                    </SidebarMenu>
                </SidebarGroup>
            )}
        </>
    );
}
