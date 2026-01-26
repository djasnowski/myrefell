import { Link, router, usePage } from '@inertiajs/react';
import {
    Anchor,
    Award,
    Axe,
    Backpack,
    Banknote,
    BarChart3,
    Briefcase,
    Calendar,
    Castle,
    Church,
    ClipboardList,
    Clock,
    Crown,
    Dumbbell,
    Flame,
    Gavel,
    Hammer,
    Home,
    Loader2,
    Mail,
    Map,
    MapPin,
    Pickaxe,
    ScrollText,
    Shield,
    Sparkles,
    Store,
    Swords,
    Trees,
    Truck,
    Users,
    UsersRound,
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
}

interface SidebarData {
    location: LocationData | null;
    home_village: HomeVillage | null;
    travel: TravelStatus | null;
    nearby_destinations: TravelDestination[];
    context: PlayerContext;
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
    kingdom: Crown,
    wilderness: Trees,
};

// Get buildings/services at current location
function getLocationServices(location: LocationData | null): NavItem[] {
    const items: NavItem[] = [];

    if (!location || location.type === 'wilderness') {
        return items;
    }

    switch (location.type) {
        case 'village':
            items.push({
                title: 'Bank',
                href: `/villages/${location.id}/bank`,
                icon: Banknote,
                description: 'Deposit and withdraw gold',
            });
            items.push({
                title: 'Healer',
                href: `/villages/${location.id}/healer`,
                icon: Church,
                description: 'Restore your health',
            });
            items.push({
                title: 'Notice Board',
                href: `/villages/${location.id}/quests`,
                icon: ScrollText,
                description: 'Find work and quests',
            });
            items.push({
                title: 'Court',
                href: '/crime',
                icon: Gavel,
                description: 'Justice and bounties',
            });
            items.push({
                title: 'Residents',
                href: `/villages/${location.id}/residents`,
                icon: Users,
                description: 'People who live here',
            });
            if (location.is_port) {
                items.push({
                    title: 'Harbor',
                    href: `/villages/${location.id}/port`,
                    icon: Anchor,
                    description: 'Book passage to other kingdoms',
                });
            }
            break;

        case 'barony':
            items.push({
                title: 'Bank',
                href: `/baronies/${location.id}/bank`,
                icon: Banknote,
                description: 'Secure vault storage',
            });
            items.push({
                title: 'Infirmary',
                href: `/baronies/${location.id}/infirmary`,
                icon: Church,
                description: 'Full medical care',
            });
            items.push({
                title: 'Barracks',
                href: `/baronies/${location.id}/barracks`,
                icon: Shield,
                description: 'Train combat skills',
            });
            items.push({
                title: 'Arena',
                href: `/baronies/${location.id}/arena`,
                icon: Swords,
                description: 'Fight for glory',
            });
            items.push({
                title: 'Court',
                href: '/crime',
                icon: Gavel,
                description: 'Justice and bounties',
            });
            items.push({
                title: 'Armies',
                href: '/warfare/armies',
                icon: Shield,
                description: 'Raise and command forces',
            });
            items.push({
                title: 'Trade Routes',
                href: '/trade/routes',
                icon: Truck,
                description: 'Caravan routes',
            });
            break;

        case 'town':
            items.push({
                title: 'Bank',
                href: `/towns/${location.id}/bank`,
                icon: Banknote,
            });
            items.push({
                title: 'Infirmary',
                href: `/towns/${location.id}/infirmary`,
                icon: Church,
            });
            items.push({
                title: 'Town Hall',
                href: `/towns/${location.id}/hall`,
                icon: Crown,
                description: 'Civic affairs',
            });
            items.push({
                title: 'Court',
                href: '/crime',
                icon: Gavel,
                description: 'Justice and bounties',
            });
            items.push({
                title: 'Armies',
                href: '/warfare/armies',
                icon: Shield,
                description: 'Military forces',
            });
            items.push({
                title: 'Wars',
                href: '/warfare/wars',
                icon: Swords,
                description: 'Active conflicts',
            });
            items.push({
                title: 'Trade Routes',
                href: '/trade/routes',
                icon: Truck,
                description: 'Caravan routes',
            });
            break;
    }

    return items;
}

// Get icon for destination type
function getDestinationIcon(type: string): LucideIcon {
    return locationIcons[type] || MapPin;
}

// Get player actions (always visible)
function getPlayerActions(): NavItem[] {
    return [
        {
            title: 'Skills',
            href: '/skills',
            icon: BarChart3,
            description: 'View your skill levels',
        },
        {
            title: 'Inventory',
            href: '/inventory',
            icon: Backpack,
            description: 'Your items and equipment',
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
        {
            title: 'World Map',
            href: '/travel',
            icon: Map,
            description: 'Travel the realm',
        },
    ];
}

// Get activities available at location
function getActivities(location: LocationData | null): NavItem[] {
    const items: NavItem[] = [];

    if (!location || location.type === 'wilderness') {
        // Wilderness only allows gathering
        items.push({
            title: 'Gathering',
            href: '/gathering',
            icon: Pickaxe,
            description: 'Mine, fish, or chop wood',
        });
        return items;
    }

    // All settlements have training and gathering
    items.push({
        title: 'Training',
        href: '/training',
        icon: Dumbbell,
        description: 'Train combat skills',
    });
    items.push({
        title: 'Gathering',
        href: '/gathering',
        icon: Pickaxe,
        description: 'Mine, fish, or chop wood',
    });
    items.push({
        title: 'Crafting',
        href: '/crafting',
        icon: Hammer,
        description: 'Create items',
    });

    return items;
}

// Get common services
function getCommonServices(location: LocationData | null): NavItem[] {
    const items: NavItem[] = [];

    if (location && location.type !== 'wilderness') {
        items.push({
            title: 'Mailbox',
            href: '/mail',
            icon: Mail,
            description: 'Messages by bird',
        });
        items.push({
            title: 'Events',
            href: location.id ? `/${location.type}s/${location.id}/events` : '/events',
            icon: Calendar,
            description: 'Local happenings',
        });
        items.push({
            title: 'Social Status',
            href: '/social-class',
            icon: Award,
            description: 'Your standing in society',
        });
    }

    return items;
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
        setRemaining(travel.remaining_seconds);
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
    }, [travel.remaining_seconds, arriving]);

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

    const { location, home_village, travel, nearby_destinations, context } = sidebar;
    const services = getLocationServices(location);
    const travelDestinations = nearby_destinations || [];
    const commonServices = getCommonServices(location);
    const playerActions = getPlayerActions();
    const activities = getActivities(location);
    const contextualItems = getContextualItems(context || {});

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
                <TravelingIndicator travel={travel} />
            </div>
        );
    }

    return (
        <>
            {/* Current Location Header */}
            <div className="mb-2 px-2">
                <Link
                    href={location?.id ? `/${location.type}s/${location.id}` : '/dashboard'}
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

            {/* Activities */}
            {activities.length > 0 && (
                <SidebarGroup className="px-2 py-0">
                    <SidebarGroupLabel>Activities</SidebarGroupLabel>
                    <SidebarMenu>
                        {activities.map((item) => (
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

            {/* Services at this location */}
            {services.length > 0 && (
                <SidebarGroup className="px-2 py-0">
                    <SidebarGroupLabel>Here</SidebarGroupLabel>
                    <SidebarMenu>
                        {services.map((item) => (
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

            {/* Common Services */}
            {commonServices.length > 0 && (
                <SidebarGroup className="px-2 py-0">
                    <SidebarGroupLabel>Services</SidebarGroupLabel>
                    <SidebarMenu>
                        {commonServices.map((item) => (
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
                        {travelDestinations.slice(0, 6).map((dest) => {
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
