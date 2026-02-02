import { Head, router, usePage } from "@inertiajs/react";
import {
    Anchor,
    Castle,
    ChevronDown,
    ChevronUp,
    Church,
    Clock,
    Compass,
    Crown,
    Home,
    Loader2,
    MapPin,
    Minus,
    Plus,
    RotateCcw,
    Scroll,
    Search,
    Users,
    X,
    Zap,
} from "lucide-react";
import { useCallback, useEffect, useMemo, useRef, useState } from "react";
import {
    HealthStatusWidget,
    type DiseaseInfection,
    type DiseaseImmunity,
} from "@/components/ui/health-status-widget";
import AppLayout from "@/layouts/app-layout";
import type { BreadcrumbItem } from "@/types";

interface Location {
    id: number;
    name: string;
    biome: string;
    coordinates_x: number;
    coordinates_y: number;
}

interface Kingdom extends Location {
    description: string;
}

interface Town extends Location {
    kingdom_id: number;
    kingdom_name: string;
    is_capital: boolean;
    population: number;
}

interface BaronyType extends Location {
    kingdom_id: number;
    kingdom_name: string;
}

interface Village extends Location {
    barony_id: number;
    barony_name: string;
    kingdom_name: string;
    population: number;
    is_port: boolean;
}

interface PlayerLocation {
    location_type: string;
    location_id: number;
    coordinates_x: number;
    coordinates_y: number;
    home_village_id: number;
    home_village_x: number;
    home_village_y: number;
    is_traveling: boolean;
}

interface MapBounds {
    min_x: number;
    max_x: number;
    min_y: number;
    max_y: number;
}

interface MapData {
    kingdoms: Kingdom[];
    towns: Town[];
    baronies: BaronyType[];
    villages: Village[];
    player: PlayerLocation;
    bounds: MapBounds;
}

interface HealthData {
    infections: DiseaseInfection[];
    immunities: DiseaseImmunity[];
    healer_path: string | null;
}

interface TravelStatus {
    is_traveling: boolean;
    destination: {
        type: string;
        id: number;
        name: string;
    };
    started_at: string;
    arrives_at: string;
    total_seconds: number;
    elapsed_seconds: number;
    remaining_seconds: number;
    progress_percent: number;
    has_arrived: boolean;
}

interface PageProps {
    map_data: MapData;
    health_data: HealthData;
    travel_status: TravelStatus | null;
    is_dev: boolean;
    [key: string]: unknown;
}

const breadcrumbs: BreadcrumbItem[] = [{ title: "World Map", href: "/travel" }];

// Kingdoms to merge into one landmass (keeping distinct biome regions)
const MERGED_KINGDOMS = ["Sandmar", "Frostholm", "Ashenfell"];

// Biome terrain colors
const biomeColors: Record<string, { terrain: string; accent: string; label: string }> = {
    forest: { terrain: "#1a4d1a", accent: "#2d7a2d", label: "Forest" },
    plains: { terrain: "#4a7c23", accent: "#6b9b3a", label: "Plains" },
    mountains: { terrain: "#5c5c5c", accent: "#787878", label: "Mountains" },
    swamps: { terrain: "#2d4a3a", accent: "#3d6b50", label: "Swamps" },
    desert: { terrain: "#c9a227", accent: "#e3bc4a", label: "Desert" },
    tundra: { terrain: "#a8c8d8", accent: "#c5dde8", label: "Tundra" },
    coastal: { terrain: "#2a6a6a", accent: "#3a8a8a", label: "Coastal" },
    volcano: { terrain: "#5c1a1a", accent: "#8a2a2a", label: "Volcano" },
};

// Icon colors for different location types
const iconColors = {
    kingdom: { bg: "#1c1917", icon: "#fbbf24", stroke: "#fbbf24" },
    town: { bg: "#1e3a5f", icon: "#60a5fa", stroke: "#3b82f6" },
    barony: { bg: "#3d3d3d", icon: "#a8a29e", stroke: "#78716c" },
    village: { bg: "#14532d", icon: "#4ade80", stroke: "#22c55e" },
    port: { bg: "#1e3a5f", icon: "#38bdf8", stroke: "#0ea5e9" },
};

// Water colors
const WATER_COLOR = "#1a3a5c";
const DEEP_WATER_COLOR = "#0f2840";

function getBiomeColor(biome: string): { terrain: string; accent: string } {
    return biomeColors[biome] || { terrain: "#3d3d3d", accent: "#5c5c5c" };
}

function formatTime(seconds: number): string {
    const mins = Math.floor(seconds / 60);
    const secs = seconds % 60;
    if (mins > 0) {
        return `${mins}m ${secs}s`;
    }
    return `${secs}s`;
}

function TravelProgressOverlay({ status, isDev }: { status: TravelStatus; isDev: boolean }) {
    const [remaining, setRemaining] = useState(status.remaining_seconds);
    const [progress, setProgress] = useState(status.progress_percent);
    const arrivedRef = useRef(false);

    useEffect(() => {
        if (status.has_arrived && !arrivedRef.current) {
            arrivedRef.current = true;
            router.post(
                "/travel/arrive",
                {},
                {
                    preserveScroll: true,
                    onSuccess: () => router.reload(),
                },
            );
            return;
        }

        const interval = setInterval(() => {
            setRemaining((prev) => {
                const newVal = Math.max(0, prev - 1);
                if (newVal <= 0 && !arrivedRef.current) {
                    arrivedRef.current = true;
                    clearInterval(interval);
                    router.post(
                        "/travel/arrive",
                        {},
                        {
                            preserveScroll: true,
                            onSuccess: () => router.reload(),
                        },
                    );
                }
                return newVal;
            });
            setProgress((prev) => Math.min(100, prev + 100 / status.total_seconds));
        }, 1000);

        return () => clearInterval(interval);
    }, [status.has_arrived, status.total_seconds]);

    const handleCancel = () => {
        router.post(
            "/travel/cancel",
            {},
            {
                preserveScroll: true,
                onSuccess: () => router.reload(),
            },
        );
    };

    const handleSkip = () => {
        router.post(
            "/travel/skip",
            {},
            {
                preserveScroll: true,
                onSuccess: () => router.reload(),
            },
        );
    };

    return (
        <div className="absolute inset-0 z-[100] flex items-center justify-center bg-stone-950/70 backdrop-blur-sm">
            <div className="rounded-xl border-2 border-amber-600/50 bg-stone-900/95 p-6 shadow-2xl">
                <div className="flex flex-col items-center gap-4">
                    <div className="flex h-16 w-16 items-center justify-center rounded-full bg-amber-900/30">
                        <Loader2 className="h-8 w-8 animate-spin text-amber-400" />
                    </div>
                    <div className="text-center">
                        <div className="font-pixel text-sm text-amber-400">Traveling to</div>
                        <div className="font-pixel text-xl text-stone-100">
                            {status.destination.name}
                        </div>
                    </div>
                    <div className="flex items-center gap-2 text-stone-300">
                        <Clock className="h-5 w-5" />
                        <span className="font-pixel text-lg">{formatTime(remaining)}</span>
                    </div>
                </div>

                {/* Progress Bar */}
                <div className="mt-4">
                    <div className="h-3 w-72 overflow-hidden rounded-full bg-stone-700">
                        <div
                            className="h-full bg-gradient-to-r from-amber-600 to-amber-400 transition-all duration-1000"
                            style={{ width: `${progress}%` }}
                        />
                    </div>
                </div>

                <div className="mt-4 flex justify-center gap-3">
                    <button
                        onClick={handleCancel}
                        className="flex items-center gap-2 rounded-lg border border-red-600/50 bg-red-900/20 px-4 py-2 font-pixel text-sm text-red-400 transition hover:bg-red-900/40"
                    >
                        <X className="h-4 w-4" />
                        Cancel
                    </button>
                    {isDev && (
                        <button
                            onClick={handleSkip}
                            className="flex items-center gap-2 rounded-lg border border-blue-600/50 bg-blue-900/20 px-4 py-2 font-pixel text-sm text-blue-400 transition hover:bg-blue-900/40"
                        >
                            <Zap className="h-4 w-4" />
                            Skip
                        </button>
                    )}
                </div>
            </div>
        </div>
    );
}

export default function Dashboard() {
    const { map_data, health_data, travel_status, is_dev } = usePage<PageProps>().props;
    const { kingdoms, towns, baronies, villages, player, bounds } = map_data;

    const [zoom, setZoom] = useState(1);
    const [pan, setPan] = useState({ x: 0, y: 0 });
    const [isDragging, setIsDragging] = useState(false);
    const [dragStart, setDragStart] = useState({ x: 0, y: 0 });
    const [hoveredLocation, setHoveredLocation] = useState<{ type: string; data: Location } | null>(
        null,
    );
    const [showLegend, setShowLegend] = useState(true);
    const [mouseCoords, setMouseCoords] = useState<{ x: number; y: number } | null>(null);
    const [searchQuery, setSearchQuery] = useState("");
    const [selectedResult, setSelectedResult] = useState<{ type: string; data: Location } | null>(
        null,
    );
    const [clickedLocation, setClickedLocation] = useState<{
        type: string;
        data: Location & {
            population?: number;
            is_port?: boolean;
            kingdom_name?: string;
            barony_name?: string;
            description?: string;
        };
    } | null>(null);
    const [isTraveling, setIsTraveling] = useState(false);
    const [loreExpanded, setLoreExpanded] = useState(false);
    const [travelError, setTravelError] = useState<string | null>(null);
    const svgRef = useRef<SVGSVGElement>(null);

    // Prevent page scrolling on the map page
    useEffect(() => {
        document.body.style.overflow = "hidden";
        return () => {
            document.body.style.overflow = "";
        };
    }, []);

    // Calculate travel time from player position
    const getTravelTime = useCallback(
        (loc: Location) => {
            const dist = Math.sqrt(
                Math.pow(loc.coordinates_x - player.coordinates_x, 2) +
                    Math.pow(loc.coordinates_y - player.coordinates_y, 2),
            );
            return Math.max(1, Math.ceil(dist / 10));
        },
        [player.coordinates_x, player.coordinates_y],
    );

    // Get rumor-style population description
    const getPopulationRumor = (pop?: number) => {
        if (!pop) return null;
        if (pop < 10) return "a quiet hamlet with few souls";
        if (pop < 25) return "a small settlement";
        if (pop < 50) return "a modest village";
        if (pop < 100) return "a bustling community";
        return "a thriving population";
    };

    // Get rumor-style services - unique per location
    const getServiceRumors = (
        type: string,
        name: string,
        biome: string,
        population?: number,
        isPort?: boolean,
    ) => {
        const rumors: string[] = [];
        // Use name to seed variations
        const seed = name.split("").reduce((acc, char) => acc + char.charCodeAt(0), 0);
        const v = (options: string[]) => options[seed % options.length];

        // Biome-specific flavor words
        const biomeAdjectives: Record<string, string[]> = {
            forest: ["woodland", "sylvan", "verdant", "shaded"],
            plains: ["prairie", "grassland", "windswept", "golden"],
            mountains: ["highland", "alpine", "craggy", "stony"],
            swamps: ["marsh", "bogland", "murky", "fenland"],
            desert: ["oasis", "sunbaked", "arid", "dusty"],
            tundra: ["frozen", "northern", "icebound", "frigid"],
            coastal: ["seaside", "saltwater", "tidal", "harbor"],
            volcano: ["ashen", "smoky", "ember-lit", "volcanic"],
        };
        const adj = biomeAdjectives[biome]?.[seed % 4] || "local";

        if (type === "village") {
            // Healer rumors
            const healerRumors = [
                `A ${adj} healer tends to the sick here`,
                `They say an old herbalist knows the cure for most ailments`,
                `The village healer learned from wandering monks`,
                `A wise woman offers remedies for coin`,
                `Folk seek the ${adj} apothecary when illness strikes`,
            ];
            rumors.push(v(healerRumors));

            // Bank/storage rumors
            const bankRumors = [
                `There's a vault where travelers store their coin`,
                `The village elder keeps a strongbox for safekeeping`,
                `A trusted merchant holds deposits for those passing through`,
                `They've dug a secure cellar for valuables`,
                `The ${adj} bank is said to be honest, at least`,
            ];
            rumors.push(v(bankRumors.slice(1).concat(bankRumors[0])));

            // Market rumors based on population
            if (population && population > 30) {
                const marketRumors = [
                    `A small market gathers on fair days`,
                    `Peddlers bring goods from distant lands`,
                    `The trading post sees regular caravans`,
                ];
                rumors.push(v(marketRumors));
            }

            // Port rumors
            if (isPort) {
                const portRumors = [
                    `Ships from distant shores drop anchor in the harbor`,
                    `The docks bustle with sailors and merchants`,
                    `Sea captains swap tales at the waterfront tavern`,
                    `Exotic goods arrive by ship from foreign ports`,
                    `The ${adj} harbor shelters vessels from storms`,
                ];
                rumors.push(v(portRumors));
            }

            // Tavern rumors
            const tavernRumors = [
                `The local tavern serves a passable ale`,
                `Travelers rest at a humble inn nearby`,
                `There's a hearth where weary folk find warm meals`,
                `The innkeeper knows all the local gossip`,
            ];
            rumors.push(v(tavernRumors));
        } else if (type === "barony") {
            // Training rumors
            const trainingRumors = [
                `Knights drill in the ${adj} barracks daily`,
                `The Baron's men-at-arms train without rest`,
                `Squires practice swordplay in the courtyard`,
                `A weathered sergeant teaches the art of war`,
                `The garrison keeps sharp through endless drills`,
            ];
            rumors.push(v(trainingRumors));

            // Arena rumors
            const arenaRumors = [
                `An arena hosts contests of martial prowess`,
                `Warriors test their mettle in the fighting pits`,
                `Champions earn glory in the ${adj} coliseum`,
                `Blood sports draw crowds from miles around`,
                `The arena's sands have seen many a duel`,
            ];
            rumors.push(v(arenaRumors));

            // Vault rumors
            const vaultRumors = [
                `The Baron's vault holds untold riches`,
                `A fortified treasury guards the realm's coin`,
                `Stone walls protect the ${adj} stronghold's wealth`,
                `The castellan keeps strict watch over the coffers`,
            ];
            rumors.push(v(vaultRumors));

            // Court rumors
            const courtRumors = [
                `Petitioners seek the Baron's justice daily`,
                `Intrigue fills the halls of power`,
                `The Baron holds court on matters of law`,
                `Nobles jockey for favor in these halls`,
            ];
            rumors.push(v(courtRumors));
        } else if (type === "town") {
            // Infirmary rumors
            const infirmaryRumors = [
                `The town infirmary employs trained physicians`,
                `Surgeons and herbalists practice their craft here`,
                `The sick find proper care at the ${adj} hospital`,
                `Healers from the guild tend to the afflicted`,
                `Medical knowledge flows through the infirmary's halls`,
            ];
            rumors.push(v(infirmaryRumors));

            // Town hall rumors
            const hallRumors = [
                `The town hall bustles with civic matters`,
                `Councilors debate policy behind closed doors`,
                `The mayor holds audience in the great chamber`,
                `Bureaucrats shuffle papers and seal documents`,
                `Guild masters gather to set prices and rules`,
            ];
            rumors.push(v(hallRumors));

            // Merchant rumors
            const merchantRumors = [
                `Merchants hawk wares from across the realm`,
                `The market square never sleeps, they say`,
                `Traders strike deals in the ${adj} bazaar`,
                `Fine goods and rare commodities change hands daily`,
                `The merchant guild controls trade with an iron fist`,
            ];
            rumors.push(v(merchantRumors));

            // Crafting rumors
            const craftRumors = [
                `Skilled artisans ply their trades here`,
                `The smiths forge quality arms and tools`,
                `Craftsmen take apprentices from far and wide`,
                `Guild workshops produce fine wares`,
            ];
            rumors.push(v(craftRumors));
        } else if (type === "kingdom") {
            // Royal rumors
            const royalRumors = [
                `The crown rules from this ancient seat of power`,
                `Royal decrees echo from these hallowed halls`,
                `The throne has weathered countless storms`,
                `Courtiers whisper of the monarch's will`,
                `The ${adj} palace gleams with imperial splendor`,
            ];
            rumors.push(v(royalRumors));

            // Army rumors
            const armyRumors = [
                `The royal army musters at the king's command`,
                `Elite guards protect the sovereign day and night`,
                `War councils convene in times of strife`,
                `The realm's finest warriors serve the crown`,
            ];
            rumors.push(v(armyRumors));

            // Treasury rumors
            const treasuryRumors = [
                `The royal treasury overflows with tribute`,
                `Tax collectors bring coin from every corner`,
                `The crown's wealth funds armies and monuments`,
            ];
            rumors.push(v(treasuryRumors));
        }

        return rumors;
    };

    // Find nearby locations for lore generation
    const findNearbyLocations = useCallback(
        (loc: Location, radius: number = 80) => {
            const nearby: { name: string; type: string; distance: number }[] = [];

            const calcDist = (other: Location) =>
                Math.sqrt(
                    Math.pow(other.coordinates_x - loc.coordinates_x, 2) +
                        Math.pow(other.coordinates_y - loc.coordinates_y, 2),
                );

            villages.forEach((v) => {
                if (v.id !== loc.id || loc.biome !== v.biome) {
                    const dist = calcDist(v);
                    if (dist > 0 && dist < radius)
                        nearby.push({ name: v.name, type: "village", distance: dist });
                }
            });
            baronies.forEach((b) => {
                const dist = calcDist(b);
                if (dist > 0 && dist < radius)
                    nearby.push({ name: b.name, type: "barony", distance: dist });
            });
            towns.forEach((t) => {
                const dist = calcDist(t);
                if (dist > 0 && dist < radius)
                    nearby.push({ name: t.name, type: "town", distance: dist });
            });

            return nearby.sort((a, b) => a.distance - b.distance).slice(0, 3);
        },
        [villages, baronies, towns],
    );

    // Generate procedural lore based on biome, type, and nearby locations
    const generateLore = useCallback(
        (
            type: string,
            name: string,
            biome: string,
            loc: Location,
            kingdomName?: string,
            isPort?: boolean,
            description?: string,
        ): string[] => {
            const nearby = findNearbyLocations(loc);
            const paragraphs: string[] = [];

            // Biome-specific opening phrases
            const biomeIntros: Record<string, string[]> = {
                forest: [
                    `${name} lies nestled within ancient woodlands where towering oaks have witnessed centuries pass.`,
                    `The settlement of ${name} emerged from clearings carved into the primeval forest, where hunters first made camp generations ago.`,
                    `Shrouded by the canopy of the great woods, ${name} began as a refuge for those fleeing the conflicts of open lands.`,
                ],
                plains: [
                    `${name} stretches across the fertile grasslands, where golden wheat sways in the wind.`,
                    `Upon the open plains where the sky meets the earth in an endless embrace, ${name} was founded by settlers seeking rich soil.`,
                    `The vast meadows surrounding ${name} have sustained its people through countless harvests and lean winters alike.`,
                ],
                mountains: [
                    `${name} clings to the rocky slopes, built by those hardy enough to carve life from stone.`,
                    `High in the shadow of the peaks, ${name} guards the mountain passes as it has for generations.`,
                    `The folk of ${name} are as unyielding as the granite foundations upon which their homes are built.`,
                ],
                swamps: [
                    `${name} rises from the murky wetlands on stilts and stone, defying the marsh that surrounds it.`,
                    `In the mist-shrouded fens, ${name} persists where others would have been swallowed by the bog.`,
                    `The people of ${name} have learned the swamp's secrets - which paths are safe, which waters hide danger.`,
                ],
                desert: [
                    `${name} is an oasis of civilization amid the endless sands, sustained by hidden wells and ancient cisterns.`,
                    `The sun-scorched settlement of ${name} endures where water is more precious than gold.`,
                    `Beneath the burning sky, ${name} has flourished by the wisdom of those who understand the desert's cruel beauty.`,
                ],
                tundra: [
                    `${name} braves the frozen wastes, its hearths burning bright against the eternal cold.`,
                    `In the land of endless winter, ${name} stands as testament to human resilience against nature's harshest domain.`,
                    `The ice-locked settlement of ${name} was built by folk who found kinship in the howling winds.`,
                ],
                coastal: [
                    `${name} gazes out upon the restless sea, its fortunes forever tied to wind and wave.`,
                    `The salt-spray settlement of ${name} has weathered storms both natural and man-made since time immemorial.`,
                    `Where land meets sea, ${name} has prospered from the bounty of both worlds.`,
                ],
                volcano: [
                    `${name} lies in the shadow of the smoking mountain, its people living with fire as their neighbor.`,
                    `The ash-touched settlement of ${name} draws strength from the volcanic soil that gives life even as it threatens death.`,
                    `In the lands of fire and brimstone, ${name} endures through reverence for the mountain's terrible power.`,
                ],
            };

            // Kingdom connections
            const kingdomPhrases = kingdomName
                ? [
                      `Under the banner of ${kingdomName}, ${name} has known both protection and obligation.`,
                      `The crown of ${kingdomName} casts its shadow here, for better or worse.`,
                      `Fealty to ${kingdomName} has shaped the customs and laws of this place.`,
                  ]
                : [];

            // Port-specific additions
            const portPhrases = isPort
                ? [
                      `The harbor brings traders from distant shores, their strange tongues and stranger goods filling the docks.`,
                      `Ships from across the known world drop anchor here, carrying news of far-off wars and wonders.`,
                      `The smell of salt and tar mingles with the cries of gulls and merchants alike.`,
                  ]
                : [];

            // Nearby location connections
            const nearbyPhrases: string[] = [];
            if (nearby.length > 0) {
                const nearestVillage = nearby.find((n) => n.type === "village");
                const nearestBarony = nearby.find((n) => n.type === "barony");
                const nearestTown = nearby.find((n) => n.type === "town");

                if (nearestBarony) {
                    nearbyPhrases.push(
                        `The Baron of ${nearestBarony.name} holds influence over these lands, collecting tithes and dispensing what passes for justice.`,
                    );
                }
                if (nearestTown) {
                    nearbyPhrases.push(
                        `Traders make regular journeys to ${nearestTown.name}, returning with goods unavailable in simpler settlements.`,
                    );
                }
                if (nearestVillage) {
                    nearbyPhrases.push(
                        `Folk from ${nearestVillage.name} are often seen passing through, bound by ties of trade and kinship.`,
                    );
                }
            }

            // Historical flavor based on type
            const historyPhrases: Record<string, string[]> = {
                village: [
                    `Tales are told of harder times, when plague and famine tested the resolve of every soul.`,
                    `The elders speak of the founding families, whose blood still runs in most who live here.`,
                    `A simple place with simple folk, yet every cottage holds stories the world has forgotten.`,
                ],
                town: [
                    `The town charter dates back generations, its privileges hard-won and jealously guarded.`,
                    `Guilds and merchants have shaped this place as much as any lord or king.`,
                    `The cobblestones remember the tread of armies, the cries of markets, the whispers of conspirators.`,
                ],
                barony: [
                    `The Baron's seat has changed hands through blood and treaty more times than any record tells.`,
                    `Stone walls and iron gates speak of an age when every neighbor was a potential enemy.`,
                    `The great hall has hosted feasts and funerals for the noble families who have ruled here.`,
                ],
                kingdom: [
                    `The throne has been contested by claimants just and unjust throughout the ages.`,
                    `Wars of succession, foreign invasions, and internal strife have forged the kingdom's character.`,
                    `From these halls, decrees have gone forth that changed the lives of thousands.`,
                    `The royal lineage is tangled with legend, and some say the blood of old heroes flows yet in the ruling house.`,
                ],
            };

            // Build paragraphs based on type
            const intro = biomeIntros[biome] || biomeIntros.plains;
            const seededRandom = (name.length + loc.coordinates_x + loc.coordinates_y) % 3;

            // First paragraph - always the biome intro
            paragraphs.push(intro[seededRandom]);

            // Second paragraph - connections and relationships
            let secondPara = "";
            if (kingdomPhrases.length > 0) {
                secondPara += kingdomPhrases[seededRandom % kingdomPhrases.length] + " ";
            }
            if (nearbyPhrases.length > 0) {
                secondPara += nearbyPhrases[0];
            }
            if (portPhrases.length > 0) {
                secondPara = portPhrases[seededRandom % portPhrases.length] + " " + secondPara;
            }
            if (secondPara.trim()) {
                paragraphs.push(secondPara.trim());
            } else {
                // Fallback for isolated locations
                paragraphs.push(
                    historyPhrases[type]?.[seededRandom] || historyPhrases.village[seededRandom],
                );
            }

            // Third paragraph for kingdoms - deeper history
            if (type === "kingdom") {
                const histIdx = (seededRandom + 1) % historyPhrases.kingdom.length;
                paragraphs.push(historyPhrases.kingdom[histIdx]);
                // Add description if available
                if (description) {
                    paragraphs.push(description);
                }
            }

            return paragraphs;
        },
        [findNearbyLocations],
    );

    const handleLocationClick = (
        type: string,
        data: Location & {
            population?: number;
            is_port?: boolean;
            kingdom_name?: string;
            barony_name?: string;
            description?: string;
        },
    ) => {
        setClickedLocation({ type, data });
        setLoreExpanded(false);
        setTravelError(null);
    };

    const handleTravel = () => {
        if (!clickedLocation) return;
        setIsTraveling(true);
        setTravelError(null);
        router.post(
            "/travel/start",
            {
                destination_type:
                    clickedLocation.type === "port" ? "village" : clickedLocation.type,
                destination_id: clickedLocation.data.id,
            },
            {
                preserveScroll: true,
                onFinish: () => setIsTraveling(false),
                onSuccess: () => {
                    setClickedLocation(null);
                    router.visit("/travel");
                },
                onError: (errors) => {
                    const errorMessage =
                        errors.travel || Object.values(errors)[0] || "Failed to start travel.";
                    setTravelError(errorMessage as string);
                },
            },
        );
    };

    // Search all locations
    const searchResults = useMemo(() => {
        if (!searchQuery.trim()) return [];
        const query = searchQuery.toLowerCase();
        const results: { type: string; data: Location; distance: number; travelTime: number }[] =
            [];

        const calcDistance = (loc: Location) => {
            return Math.sqrt(
                Math.pow(loc.coordinates_x - player.coordinates_x, 2) +
                    Math.pow(loc.coordinates_y - player.coordinates_y, 2),
            );
        };

        kingdoms
            .filter((k) => k.name.toLowerCase().includes(query))
            .forEach((k) => {
                const dist = calcDistance(k);
                results.push({
                    type: "kingdom",
                    data: k,
                    distance: dist,
                    travelTime: Math.max(1, Math.ceil(dist / 10)),
                });
            });
        towns
            .filter((t) => t.name.toLowerCase().includes(query))
            .forEach((t) => {
                const dist = calcDistance(t);
                results.push({
                    type: "town",
                    data: t,
                    distance: dist,
                    travelTime: Math.max(1, Math.ceil(dist / 10)),
                });
            });
        baronies
            .filter((c) => c.name.toLowerCase().includes(query))
            .forEach((c) => {
                const dist = calcDistance(c);
                results.push({
                    type: "barony",
                    data: c,
                    distance: dist,
                    travelTime: Math.max(1, Math.ceil(dist / 10)),
                });
            });
        villages
            .filter((v) => v.name.toLowerCase().includes(query))
            .forEach((v) => {
                const dist = calcDistance(v);
                results.push({
                    type: v.is_port ? "port" : "village",
                    data: v,
                    distance: dist,
                    travelTime: Math.max(1, Math.ceil(dist / 10)),
                });
            });

        return results.sort((a, b) => a.distance - b.distance).slice(0, 8);
    }, [
        searchQuery,
        kingdoms,
        towns,
        baronies,
        villages,
        player.coordinates_x,
        player.coordinates_y,
    ]);

    const handleSelectResult = (result: { type: string; data: Location }) => {
        setSelectedResult(result);
        // Center on the location
        const mapCenterX = bounds.min_x + mapWidth / 2;
        const mapCenterY = bounds.min_y + mapHeight / 2;
        setPan({
            x: mapCenterX - result.data.coordinates_x,
            y: mapCenterY - result.data.coordinates_y,
        });
        setZoom(2.5);
        setSearchQuery("");
    };

    const mapWidth = bounds.max_x - bounds.min_x;
    const mapHeight = bounds.max_y - bounds.min_y;
    const viewBoxWidth = mapWidth / zoom;
    const viewBoxHeight = mapHeight / zoom;
    const viewBoxX = bounds.min_x + (mapWidth - viewBoxWidth) / 2 - pan.x;
    const viewBoxY = bounds.min_y + (mapHeight - viewBoxHeight) / 2 - pan.y;

    const handleZoomIn = useCallback(() => setZoom((z) => Math.min(z * 1.5, 5)), []);
    const handleZoomOut = useCallback(() => setZoom((z) => Math.max(z / 1.5, 0.5)), []);
    const handleReset = useCallback(() => {
        setZoom(1);
        setPan({ x: 0, y: 0 });
    }, []);

    const handleLocateMe = useCallback(() => {
        // Center the view on player's position
        // viewBoxX = baseCenterX - pan.x, so to show player.x we need:
        // pan.x = baseCenterX - player.x (negative pan to shift view right)
        const mapCenterX = bounds.min_x + mapWidth / 2;
        const mapCenterY = bounds.min_y + mapHeight / 2;
        setPan({
            x: mapCenterX - player.coordinates_x,
            y: mapCenterY - player.coordinates_y,
        });
        setZoom(2.5);
    }, [
        player.coordinates_x,
        player.coordinates_y,
        bounds.min_x,
        bounds.min_y,
        mapWidth,
        mapHeight,
    ]);

    const handleMouseDown = (e: React.MouseEvent) => {
        if (e.button === 0) {
            setIsDragging(true);
            setDragStart({ x: e.clientX, y: e.clientY });
        }
    };

    const handleMouseMove = (e: React.MouseEvent) => {
        if (svgRef.current) {
            const rect = svgRef.current.getBoundingClientRect();
            const scaleX = viewBoxWidth / rect.width;
            const scaleY = viewBoxHeight / rect.height;

            // Calculate map coordinates from mouse position
            // SVG viewBox starts at (viewBoxX, -(viewBoxY + viewBoxHeight))
            // Locations render at (x, -y), so we need to reverse the Y transform
            const svgX = viewBoxX + (e.clientX - rect.left) * scaleX;
            const svgY = -(viewBoxY + viewBoxHeight) + (e.clientY - rect.top) * scaleY;
            const mapX = Math.round(svgX);
            const mapY = Math.round(-svgY);
            setMouseCoords({ x: mapX, y: mapY });

            if (isDragging) {
                const dx = (e.clientX - dragStart.x) * scaleX;
                const dy = (e.clientY - dragStart.y) * scaleY;
                setPan((prev) => ({ x: prev.x + dx, y: prev.y - dy }));
                setDragStart({ x: e.clientX, y: e.clientY });
            }
        }
    };

    const handleMouseUp = () => setIsDragging(false);
    const handleMouseLeave = () => {
        setIsDragging(false);
        setMouseCoords(null);
    };

    const handleWheel = (e: React.WheelEvent) => {
        e.preventDefault();
        setZoom((z) => (e.deltaY < 0 ? Math.min(z * 1.2, 5) : Math.max(z / 1.2, 0.5)));
    };

    const transformY = (y: number) => -y;

    // Generate terrain - large kingdom islands that encompass all locations
    const terrainRegions = useMemo(() => {
        const regions: React.ReactNode[] = [];

        // Group all locations by kingdom
        const kingdomData: Record<
            number,
            { kingdom: Kingdom; locations: { x: number; y: number }[] }
        > = {};

        kingdoms.forEach((k) => {
            kingdomData[k.id] = {
                kingdom: k,
                locations: [{ x: k.coordinates_x, y: k.coordinates_y }],
            };
        });

        towns.forEach((t) => {
            if (kingdomData[t.kingdom_id]) {
                kingdomData[t.kingdom_id].locations.push({
                    x: t.coordinates_x,
                    y: t.coordinates_y,
                });
            }
        });

        baronies.forEach((b) => {
            if (kingdomData[b.kingdom_id]) {
                kingdomData[b.kingdom_id].locations.push({
                    x: b.coordinates_x,
                    y: b.coordinates_y,
                });
            }
        });

        villages.forEach((v) => {
            const barony = baronies.find((b) => b.id === v.barony_id);
            if (barony && kingdomData[barony.kingdom_id]) {
                kingdomData[barony.kingdom_id].locations.push({
                    x: v.coordinates_x,
                    y: v.coordinates_y,
                });
            }
        });

        // Separate merged kingdoms from standalone ones
        const standaloneKingdoms = Object.values(kingdomData).filter(
            (d) => !MERGED_KINGDOMS.includes(d.kingdom.name),
        );

        const mergedKingdoms = Object.values(kingdomData).filter((d) =>
            MERGED_KINGDOMS.includes(d.kingdom.name),
        );

        // Draw merged landmass for Sandmar, Frostholm, Ashenfell
        if (mergedKingdoms.length > 0) {
            // Get kingdom centers and bounds
            const kingdomInfo: {
                name: string;
                x: number;
                y: number;
                minX: number;
                maxX: number;
                minY: number;
                maxY: number;
                colors: { terrain: string; accent: string };
            }[] = [];

            mergedKingdoms.forEach((data) => {
                const { kingdom, locations } = data;
                const kMinX = Math.min(...locations.map((l) => l.x));
                const kMaxX = Math.max(...locations.map((l) => l.x));
                const kMinY = Math.min(...locations.map((l) => l.y));
                const kMaxY = Math.max(...locations.map((l) => l.y));

                kingdomInfo.push({
                    name: kingdom.name,
                    x: (kMinX + kMaxX) / 2,
                    y: (kMinY + kMaxY) / 2,
                    minX: kMinX,
                    maxX: kMaxX,
                    minY: kMinY,
                    maxY: kMaxY,
                    colors: getBiomeColor(kingdom.biome),
                });
            });

            // Draw each kingdom's biome region on top
            mergedKingdoms.forEach((data, mIndex) => {
                const { kingdom, locations } = data;
                const colors = getBiomeColor(kingdom.biome);

                const kMinX = Math.min(...locations.map((l) => l.x));
                const kMaxX = Math.max(...locations.map((l) => l.x));
                const kMinY = Math.min(...locations.map((l) => l.y));
                const kMaxY = Math.max(...locations.map((l) => l.y));

                const kCenterX = (kMinX + kMaxX) / 2;
                const kCenterY = (kMinY + kMaxY) / 2;

                // Extra padding for Sandmar to include outlying villages
                const padding = kingdom.name === "Sandmar" ? 50 : 30;
                const kRadiusX = (kMaxX - kMinX) / 2 + padding;
                const kRadiusY = (kMaxY - kMinY) / 2 + padding;
                const kBaseRadius = Math.max(kRadiusX, kRadiusY, 110);

                // Generate organic blob for this kingdom
                const biomePoints: string[] = [];
                const biomeSegments = 24;

                for (let i = 0; i < biomeSegments; i++) {
                    const angle = (i / biomeSegments) * Math.PI * 2;

                    // Only expand outward, never shrink (1.0 + positive values only)
                    const variation =
                        1.0 +
                        Math.abs(Math.sin(angle * 2.5 + mIndex * 2)) * 0.08 +
                        Math.abs(Math.cos(angle * 3.5 + mIndex * 1.5)) * 0.05;

                    const stretchX = kRadiusX / kBaseRadius;
                    const stretchY = kRadiusY / kBaseRadius;

                    const r = kBaseRadius * variation;
                    const px = kCenterX + Math.cos(angle) * r * stretchX;
                    const py = kCenterY + Math.sin(angle) * r * stretchY;

                    if (i === 0) {
                        biomePoints.push(`M ${px} ${transformY(py)}`);
                    } else {
                        biomePoints.push(`L ${px} ${transformY(py)}`);
                    }
                }
                biomePoints.push("Z");

                regions.push(
                    <path
                        key={`kingdom-biome-${kingdom.id}`}
                        d={biomePoints.join(" ")}
                        fill={colors.terrain}
                        stroke={colors.accent}
                        strokeWidth={2}
                        opacity={0.95}
                    />,
                );
            });
        }

        // Draw standalone kingdom landmasses
        standaloneKingdoms.forEach((data, kIndex) => {
            const { kingdom, locations } = data;
            const colors = getBiomeColor(kingdom.biome);

            // Find bounding box of all locations
            const minX = Math.min(...locations.map((l) => l.x));
            const maxX = Math.max(...locations.map((l) => l.x));
            const minY = Math.min(...locations.map((l) => l.y));
            const maxY = Math.max(...locations.map((l) => l.y));

            // Center of the landmass
            const centerX = (minX + maxX) / 2;
            const centerY = (minY + maxY) / 2;

            // Radius to encompass all locations plus padding
            const radiusX = (maxX - minX) / 2 + 30;
            const radiusY = (maxY - minY) / 2 + 30;
            const baseRadius = Math.max(radiusX, radiusY, 120);

            // Generate organic blob shape
            const points: string[] = [];
            const segments = 24;

            for (let i = 0; i < segments; i++) {
                const angle = (i / segments) * Math.PI * 2;
                // Only expand outward, never shrink (1.0 + positive values only)
                const variation =
                    1.0 +
                    Math.abs(Math.sin(angle * 2 + kIndex)) * 0.08 +
                    Math.abs(Math.cos(angle * 3 + kIndex * 0.7)) * 0.05;

                // Stretch to fit bounding box
                const stretchX = radiusX / baseRadius;
                const stretchY = radiusY / baseRadius;

                const r = baseRadius * variation;
                const px = centerX + Math.cos(angle) * r * stretchX;
                const py = transformY(centerY) + Math.sin(angle) * r * stretchY;

                if (i === 0) {
                    points.push(`M ${px} ${py}`);
                } else {
                    points.push(`L ${px} ${py}`);
                }
            }
            points.push("Z");

            regions.push(
                <path
                    key={`kingdom-land-${kingdom.id}`}
                    d={points.join(" ")}
                    fill={colors.terrain}
                    stroke={colors.accent}
                    strokeWidth={3}
                    opacity={0.9}
                />,
            );
        });

        return regions;
    }, [kingdoms, towns, baronies, villages]);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="World Map" />
            <div
                className="absolute inset-0 top-16 overflow-hidden"
                style={{ backgroundColor: DEEP_WATER_COLOR }}
            >
                {/* Right Controls */}
                <div className="absolute right-4 top-4 z-10 flex flex-col gap-2">
                    <button
                        onClick={handleZoomIn}
                        className="rounded-lg border-2 border-stone-600 bg-stone-800/90 p-2 text-stone-300 transition hover:bg-stone-700"
                        title="Zoom In"
                    >
                        <Plus className="h-5 w-5" />
                    </button>
                    <button
                        onClick={handleZoomOut}
                        className="rounded-lg border-2 border-stone-600 bg-stone-800/90 p-2 text-stone-300 transition hover:bg-stone-700"
                        title="Zoom Out"
                    >
                        <Minus className="h-5 w-5" />
                    </button>
                    <button
                        onClick={handleReset}
                        className="rounded-lg border-2 border-stone-600 bg-stone-800/90 p-2 text-stone-300 transition hover:bg-stone-700"
                        title="Reset View"
                    >
                        <RotateCcw className="h-5 w-5" />
                    </button>
                </div>

                {/* Search Input - Top Left */}
                <div className="absolute left-4 top-2 z-10 w-80">
                    <div className="relative">
                        <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-stone-500" />
                        <input
                            type="text"
                            value={searchQuery}
                            onChange={(e) => setSearchQuery(e.target.value)}
                            placeholder="Search locations..."
                            className="w-full rounded-lg border-2 border-stone-600 bg-stone-800/95 py-2 pl-10 pr-8 text-sm text-stone-200 placeholder-stone-500 outline-none focus:border-amber-600"
                        />
                        {searchQuery && (
                            <button
                                onClick={() => setSearchQuery("")}
                                className="absolute right-2 top-1/2 -translate-y-1/2 text-stone-500 hover:text-stone-300"
                            >
                                <X className="h-4 w-4" />
                            </button>
                        )}
                    </div>
                    {/* Search Results */}
                    {searchResults.length > 0 && (
                        <div className="mt-1 max-h-64 overflow-y-auto rounded-lg border-2 border-stone-600 bg-stone-800/95">
                            {searchResults.map((result, idx) => {
                                const Icon =
                                    result.type === "kingdom"
                                        ? Crown
                                        : result.type === "town"
                                          ? Church
                                          : result.type === "barony"
                                            ? Castle
                                            : result.type === "port"
                                              ? Anchor
                                              : Home;
                                const color =
                                    iconColors[result.type as keyof typeof iconColors]?.icon ||
                                    iconColors.village.icon;
                                return (
                                    <button
                                        key={`${result.type}-${result.data.id}-${idx}`}
                                        onClick={() => handleSelectResult(result)}
                                        className="flex w-full items-center gap-2 px-3 py-2 text-left transition hover:bg-stone-700/50"
                                    >
                                        <Icon className="h-4 w-4 flex-shrink-0" style={{ color }} />
                                        <div className="min-w-0 flex-1">
                                            <div className="truncate text-sm text-stone-200">
                                                {result.data.name}
                                            </div>
                                            <div className="flex items-center gap-2 text-xs text-stone-500">
                                                <span className="capitalize">{result.type}</span>
                                                <span>•</span>
                                                <span>{result.data.biome}</span>
                                                <span>•</span>
                                                <span>{result.travelTime}m away</span>
                                            </div>
                                        </div>
                                    </button>
                                );
                            })}
                        </div>
                    )}
                    {/* Locate Me Button */}
                    <button
                        onClick={handleLocateMe}
                        className="mt-2 flex items-center gap-2 rounded-lg border-2 border-amber-600 bg-amber-900/90 px-4 py-2 font-pixel text-xs text-amber-300 transition hover:bg-amber-800"
                    >
                        <Compass className="h-4 w-4" />
                        Locate Me
                    </button>
                </div>

                {/* Health Status Widget - Above Legend */}
                {health_data.infections.length > 0 && (
                    <div className="absolute bottom-36 left-4 z-10 w-64">
                        <HealthStatusWidget
                            infections={health_data.infections}
                            immunities={health_data.immunities}
                            currentLocationPath={health_data.healer_path ?? undefined}
                        />
                    </div>
                )}

                {/* Legend Panel - Bottom Left */}
                <div className="absolute bottom-4 left-4 z-10">
                    {showLegend ? (
                        <div className="rounded-lg border-2 border-stone-600 bg-stone-800/95 p-3">
                            <div className="mb-2 flex items-center justify-between">
                                <h3 className="font-pixel text-xs text-amber-400">Legend</h3>
                                <button
                                    onClick={() => setShowLegend(false)}
                                    className="text-stone-500 hover:text-stone-300"
                                >
                                    <X className="h-3.5 w-3.5" />
                                </button>
                            </div>
                            <div className="space-y-1.5">
                                <div className="flex items-center gap-2">
                                    <Crown
                                        className="h-3.5 w-3.5"
                                        style={{ color: iconColors.kingdom.icon }}
                                    />
                                    <span className="font-pixel text-[10px] text-stone-300">
                                        Kingdom
                                    </span>
                                </div>
                                <div className="flex items-center gap-2">
                                    <Church
                                        className="h-3.5 w-3.5"
                                        style={{ color: iconColors.town.icon }}
                                    />
                                    <span className="font-pixel text-[10px] text-stone-300">
                                        Town
                                    </span>
                                </div>
                                <div className="flex items-center gap-2">
                                    <Castle
                                        className="h-3.5 w-3.5"
                                        style={{ color: iconColors.barony.icon }}
                                    />
                                    <span className="font-pixel text-[10px] text-stone-300">
                                        Barony
                                    </span>
                                </div>
                                <div className="flex items-center gap-2">
                                    <Home
                                        className="h-3.5 w-3.5"
                                        style={{ color: iconColors.village.icon }}
                                    />
                                    <span className="font-pixel text-[10px] text-stone-300">
                                        Village
                                    </span>
                                </div>
                                <div className="flex items-center gap-2">
                                    <Anchor
                                        className="h-3.5 w-3.5"
                                        style={{ color: iconColors.port.icon }}
                                    />
                                    <span className="font-pixel text-[10px] text-stone-300">
                                        Port
                                    </span>
                                </div>
                                <div className="flex items-center gap-2">
                                    <MapPin className="h-3.5 w-3.5 text-amber-400" />
                                    <span className="font-pixel text-[10px] text-stone-300">
                                        You
                                    </span>
                                </div>
                            </div>
                            <div className="mt-2 border-t border-stone-700 pt-2">
                                <div className="grid grid-cols-2 gap-1">
                                    {Object.entries(biomeColors)
                                        .slice(0, 6)
                                        .map(([biome, colors]) => (
                                            <div key={biome} className="flex items-center gap-1">
                                                <div
                                                    className="h-2 w-2 rounded-sm"
                                                    style={{ backgroundColor: colors.terrain }}
                                                />
                                                <span className="font-pixel text-[8px] text-stone-500">
                                                    {colors.label}
                                                </span>
                                            </div>
                                        ))}
                                </div>
                            </div>
                        </div>
                    ) : (
                        <button
                            onClick={() => setShowLegend(true)}
                            className="rounded-lg border-2 border-stone-600 bg-stone-800/90 px-3 py-2 font-pixel text-xs text-stone-300 transition hover:bg-stone-700"
                        >
                            Legend
                        </button>
                    )}
                </div>

                {/* Hover Tooltip */}
                {hoveredLocation && (
                    <div className="pointer-events-none absolute left-1/2 top-16 z-20 -translate-x-1/2 rounded-lg border-2 border-amber-600/50 bg-stone-800/95 px-4 py-2">
                        <div className="flex items-center gap-2">
                            {hoveredLocation.type === "kingdom" && (
                                <Crown
                                    className="h-5 w-5 flex-shrink-0"
                                    style={{ color: iconColors.kingdom.icon }}
                                />
                            )}
                            {hoveredLocation.type === "town" && (
                                <Church
                                    className="h-5 w-5 flex-shrink-0"
                                    style={{ color: iconColors.town.icon }}
                                />
                            )}
                            {hoveredLocation.type === "barony" && (
                                <Castle
                                    className="h-5 w-5 flex-shrink-0"
                                    style={{ color: iconColors.barony.icon }}
                                />
                            )}
                            {hoveredLocation.type === "village" && (
                                <Home
                                    className="h-5 w-5 flex-shrink-0"
                                    style={{ color: iconColors.village.icon }}
                                />
                            )}
                            {hoveredLocation.type === "port" && (
                                <Anchor
                                    className="h-5 w-5 flex-shrink-0"
                                    style={{ color: iconColors.port.icon }}
                                />
                            )}
                            <span className="font-pixel text-sm leading-none text-amber-300">
                                {hoveredLocation.data.name}
                            </span>
                        </div>
                    </div>
                )}

                {/* SVG Map */}
                <svg
                    ref={svgRef}
                    className={`h-full w-full ${isDragging ? "cursor-grabbing" : "cursor-grab"}`}
                    viewBox={`${viewBoxX} ${transformY(viewBoxY + viewBoxHeight)} ${viewBoxWidth} ${viewBoxHeight}`}
                    preserveAspectRatio="xMidYMid meet"
                    onMouseDown={handleMouseDown}
                    onMouseMove={handleMouseMove}
                    onMouseUp={handleMouseUp}
                    onMouseLeave={handleMouseLeave}
                    onWheel={handleWheel}
                >
                    <defs>
                        <radialGradient id="waterGradient" cx="50%" cy="50%" r="70%">
                            <stop offset="0%" stopColor={WATER_COLOR} />
                            <stop offset="100%" stopColor={DEEP_WATER_COLOR} />
                        </radialGradient>
                        <pattern
                            id="wavePattern"
                            patternUnits="userSpaceOnUse"
                            width="80"
                            height="80"
                        >
                            <path
                                d="M0 40 Q20 35, 40 40 T80 40"
                                fill="none"
                                stroke="rgba(255,255,255,0.04)"
                                strokeWidth="1"
                            />
                            <path
                                d="M0 60 Q20 55, 40 60 T80 60"
                                fill="none"
                                stroke="rgba(255,255,255,0.03)"
                                strokeWidth="1"
                            />
                        </pattern>
                    </defs>

                    {/* Ocean */}
                    <rect
                        x={bounds.min_x - 500}
                        y={transformY(bounds.max_y + 500)}
                        width={mapWidth + 1000}
                        height={mapHeight + 1000}
                        fill="url(#waterGradient)"
                    />
                    <rect
                        x={bounds.min_x - 500}
                        y={transformY(bounds.max_y + 500)}
                        width={mapWidth + 1000}
                        height={mapHeight + 1000}
                        fill="url(#wavePattern)"
                    />

                    {/* Terrain */}
                    {terrainRegions}

                    {/* Villages - Green (Ports - Blue) */}
                    {villages.map((village) => {
                        const isPort = village.is_port;
                        const colors = isPort ? iconColors.port : iconColors.village;
                        return (
                            <g
                                key={`village-${village.id}`}
                                transform={`translate(${village.coordinates_x}, ${transformY(village.coordinates_y)})`}
                                className="cursor-pointer"
                                onMouseEnter={() =>
                                    setHoveredLocation({
                                        type: isPort ? "port" : "village",
                                        data: village,
                                    })
                                }
                                onMouseLeave={() => setHoveredLocation(null)}
                                onClick={() =>
                                    handleLocationClick(isPort ? "port" : "village", village)
                                }
                            >
                                <circle
                                    r={isPort ? 8 : 6}
                                    fill={colors.bg}
                                    stroke={colors.stroke}
                                    strokeWidth={1.5}
                                />
                                {isPort ? (
                                    <Anchor
                                        x={-5}
                                        y={-5}
                                        width={10}
                                        height={10}
                                        color={colors.icon}
                                        strokeWidth={2}
                                    />
                                ) : (
                                    <Home
                                        x={-4}
                                        y={-4}
                                        width={8}
                                        height={8}
                                        color={colors.icon}
                                        strokeWidth={2}
                                    />
                                )}
                            </g>
                        );
                    })}

                    {/* Baronies - Gray/Stone */}
                    {baronies.map((barony) => {
                        const colors = iconColors.barony;
                        return (
                            <g
                                key={`barony-${barony.id}`}
                                transform={`translate(${barony.coordinates_x}, ${transformY(barony.coordinates_y)})`}
                                className="cursor-pointer"
                                onMouseEnter={() =>
                                    setHoveredLocation({ type: "barony", data: barony })
                                }
                                onMouseLeave={() => setHoveredLocation(null)}
                                onClick={() => handleLocationClick("barony", barony)}
                            >
                                <circle
                                    r={10}
                                    fill={colors.bg}
                                    stroke={colors.stroke}
                                    strokeWidth={2}
                                />
                                <Castle
                                    x={-7}
                                    y={-7}
                                    width={14}
                                    height={14}
                                    color={colors.icon}
                                    strokeWidth={1.5}
                                />
                            </g>
                        );
                    })}

                    {/* Towns - Blue */}
                    {towns.map((town) => {
                        const colors = iconColors.town;
                        return (
                            <g
                                key={`town-${town.id}`}
                                transform={`translate(${town.coordinates_x}, ${transformY(town.coordinates_y)})`}
                                className="cursor-pointer"
                                onMouseEnter={() =>
                                    setHoveredLocation({ type: "town", data: town })
                                }
                                onMouseLeave={() => setHoveredLocation(null)}
                                onClick={() => handleLocationClick("town", town)}
                            >
                                <circle
                                    r={12}
                                    fill={colors.bg}
                                    stroke={colors.stroke}
                                    strokeWidth={2}
                                />
                                <Church
                                    x={-8}
                                    y={-8}
                                    width={16}
                                    height={16}
                                    color={colors.icon}
                                    strokeWidth={1.5}
                                />
                                {town.is_capital && (
                                    <circle
                                        r={16}
                                        fill="none"
                                        stroke="#fbbf24"
                                        strokeWidth={2}
                                        strokeDasharray="4 3"
                                    />
                                )}
                            </g>
                        );
                    })}

                    {/* Kingdoms - Gold */}
                    {kingdoms.map((kingdom) => {
                        const colors = iconColors.kingdom;
                        return (
                            <g
                                key={`kingdom-${kingdom.id}`}
                                transform={`translate(${kingdom.coordinates_x}, ${transformY(kingdom.coordinates_y)})`}
                                className="cursor-pointer"
                                onMouseEnter={() =>
                                    setHoveredLocation({ type: "kingdom", data: kingdom })
                                }
                                onMouseLeave={() => setHoveredLocation(null)}
                                onClick={() => handleLocationClick("kingdom", kingdom)}
                            >
                                <circle
                                    r={18}
                                    fill={colors.bg}
                                    stroke={colors.stroke}
                                    strokeWidth={3}
                                />
                                <Crown
                                    x={-11}
                                    y={-11}
                                    width={22}
                                    height={22}
                                    color={colors.icon}
                                    fill={colors.icon}
                                    strokeWidth={0}
                                />
                            </g>
                        );
                    })}

                    {/* Selected search result indicator */}
                    {selectedResult && (
                        <g
                            transform={`translate(${selectedResult.data.coordinates_x}, ${transformY(selectedResult.data.coordinates_y)})`}
                        >
                            <circle
                                r={22}
                                fill="none"
                                stroke="#38bdf8"
                                strokeWidth={2}
                                strokeDasharray="6 3"
                            >
                                <animate
                                    attributeName="r"
                                    from="18"
                                    to="30"
                                    dur="2s"
                                    repeatCount="indefinite"
                                />
                                <animate
                                    attributeName="opacity"
                                    from="1"
                                    to="0"
                                    dur="2s"
                                    repeatCount="indefinite"
                                />
                            </circle>
                        </g>
                    )}

                    {/* Player location - single pulsing ring */}
                    <g
                        transform={`translate(${player.coordinates_x}, ${transformY(player.coordinates_y)})`}
                    >
                        <circle r={18} fill="none" stroke="#f59e0b" strokeWidth={3}>
                            <animate
                                attributeName="r"
                                from="12"
                                to="25"
                                dur="1.5s"
                                repeatCount="indefinite"
                            />
                            <animate
                                attributeName="opacity"
                                from="1"
                                to="0"
                                dur="1.5s"
                                repeatCount="indefinite"
                            />
                        </circle>
                    </g>

                    {/* Kingdom Labels */}
                    {kingdoms.map((kingdom) => (
                        <text
                            key={`label-${kingdom.id}`}
                            x={kingdom.coordinates_x}
                            y={transformY(kingdom.coordinates_y) + 35}
                            textAnchor="middle"
                            fill="#fbbf24"
                            fontSize={11}
                            fontWeight="bold"
                            style={{ textShadow: "0 1px 3px rgba(0,0,0,0.9)" }}
                        >
                            {kingdom.name}
                        </text>
                    ))}
                </svg>

                {/* Position Display */}
                <div className="absolute bottom-4 right-4 rounded-lg border-2 border-stone-600 bg-stone-800/95 px-3 py-1.5">
                    <span className="font-pixel text-[10px] text-stone-400">
                        {mouseCoords ? (
                            <>
                                ({mouseCoords.x}, {mouseCoords.y})
                            </>
                        ) : (
                            <>
                                ({player.coordinates_x}, {player.coordinates_y})
                            </>
                        )}
                    </span>
                </div>

                {/* Location Info Modal */}
                {clickedLocation && (
                    <div
                        className="absolute inset-0 z-30 flex items-center justify-center bg-black/50"
                        onClick={() => setClickedLocation(null)}
                    >
                        <div
                            className="mx-4 max-h-[85vh] w-full max-w-md overflow-y-auto rounded-lg border-2 border-amber-600/70 bg-stone-900/95 shadow-xl"
                            onClick={(e) => e.stopPropagation()}
                        >
                            {/* Header */}
                            <div className="flex items-center justify-between border-b border-stone-700 px-4 py-3">
                                <div className="flex items-center gap-2">
                                    {clickedLocation.type === "kingdom" && (
                                        <Crown
                                            className="h-5 w-5"
                                            style={{ color: iconColors.kingdom.icon }}
                                        />
                                    )}
                                    {clickedLocation.type === "town" && (
                                        <Church
                                            className="h-5 w-5"
                                            style={{ color: iconColors.town.icon }}
                                        />
                                    )}
                                    {clickedLocation.type === "barony" && (
                                        <Castle
                                            className="h-5 w-5"
                                            style={{ color: iconColors.barony.icon }}
                                        />
                                    )}
                                    {clickedLocation.type === "village" && (
                                        <Home
                                            className="h-5 w-5"
                                            style={{ color: iconColors.village.icon }}
                                        />
                                    )}
                                    {clickedLocation.type === "port" && (
                                        <Anchor
                                            className="h-5 w-5"
                                            style={{ color: iconColors.port.icon }}
                                        />
                                    )}
                                    <span className="font-pixel text-base text-amber-300">
                                        {clickedLocation.data.name}
                                    </span>
                                </div>
                                <button
                                    onClick={() => setClickedLocation(null)}
                                    className="text-stone-500 hover:text-stone-300"
                                >
                                    <X className="h-5 w-5" />
                                </button>
                            </div>

                            {/* Content */}
                            <div className="space-y-3 px-4 py-3">
                                {/* Type & Region */}
                                <div className="text-sm text-stone-400">
                                    <span className="capitalize">
                                        {clickedLocation.type === "port"
                                            ? "Port Village"
                                            : clickedLocation.type}
                                    </span>
                                    {clickedLocation.data.kingdom_name && (
                                        <span> in {clickedLocation.data.kingdom_name}</span>
                                    )}
                                </div>

                                {/* Lore / History Section */}
                                {(() => {
                                    const lore = generateLore(
                                        clickedLocation.type === "port"
                                            ? "village"
                                            : clickedLocation.type,
                                        clickedLocation.data.name,
                                        clickedLocation.data.biome,
                                        clickedLocation.data,
                                        clickedLocation.data.kingdom_name,
                                        clickedLocation.data.is_port,
                                        clickedLocation.data.description,
                                    );
                                    const firstPara = lore[0];
                                    const restParas = lore.slice(1);

                                    return (
                                        <div className="rounded border border-stone-700/50 bg-stone-800/30">
                                            <button
                                                onClick={() => setLoreExpanded(!loreExpanded)}
                                                className="flex w-full items-start gap-2 px-3 py-2 text-left transition hover:bg-stone-800/50"
                                            >
                                                <Scroll className="mt-0.5 h-4 w-4 flex-shrink-0 text-amber-600" />
                                                <div className="min-w-0 flex-1">
                                                    <p
                                                        className={`text-sm leading-relaxed text-stone-300 ${!loreExpanded ? "line-clamp-2" : ""}`}
                                                    >
                                                        {firstPara}
                                                    </p>
                                                    {!loreExpanded && restParas.length > 0 && (
                                                        <span className="mt-1 flex items-center gap-1 text-xs text-amber-500">
                                                            <ChevronDown className="h-3 w-3" />
                                                            Read more...
                                                        </span>
                                                    )}
                                                </div>
                                            </button>
                                            {loreExpanded && restParas.length > 0 && (
                                                <div className="space-y-3 border-t border-stone-700/50 px-3 py-3">
                                                    {restParas.map((para, idx) => (
                                                        <p
                                                            key={idx}
                                                            className="text-sm leading-relaxed text-stone-300"
                                                        >
                                                            {para}
                                                        </p>
                                                    ))}
                                                    <button
                                                        onClick={() => setLoreExpanded(false)}
                                                        className="flex items-center gap-1 text-xs text-amber-500 transition hover:text-amber-400"
                                                    >
                                                        <ChevronUp className="h-3 w-3" />
                                                        Show less
                                                    </button>
                                                </div>
                                            )}
                                        </div>
                                    );
                                })()}

                                {/* Population Rumor */}
                                {clickedLocation.data.population && (
                                    <div className="flex items-start gap-2 text-sm">
                                        <Users className="mt-0.5 h-4 w-4 flex-shrink-0 text-stone-500" />
                                        <span className="italic text-stone-300">
                                            "They say it's{" "}
                                            {getPopulationRumor(clickedLocation.data.population)}
                                            ..."
                                        </span>
                                    </div>
                                )}

                                {/* Service Rumors */}
                                <div className="space-y-1">
                                    <div className="text-xs font-medium uppercase tracking-wide text-stone-500">
                                        Rumors speak of...
                                    </div>
                                    <ul className="space-y-1 text-sm text-stone-300">
                                        {getServiceRumors(
                                            clickedLocation.type === "port"
                                                ? "village"
                                                : clickedLocation.type,
                                            clickedLocation.data.name,
                                            clickedLocation.data.biome,
                                            clickedLocation.data.population,
                                            clickedLocation.data.is_port,
                                        ).map((rumor, i) => (
                                            <li key={i} className="flex items-center gap-2">
                                                <span className="text-stone-600">•</span>
                                                <span className="italic">{rumor}</span>
                                            </li>
                                        ))}
                                    </ul>
                                </div>

                                {/* Travel Time */}
                                <div className="flex items-center gap-2 rounded border border-stone-700 bg-stone-800/50 px-3 py-2">
                                    <MapPin className="h-4 w-4 text-amber-500" />
                                    <span className="text-sm text-stone-300">
                                        About{" "}
                                        <span className="font-medium text-amber-400">
                                            {getTravelTime(clickedLocation.data)} minutes
                                        </span>{" "}
                                        journey from here
                                    </span>
                                </div>
                            </div>

                            {/* Footer */}
                            <div className="border-t border-stone-700 px-4 py-3">
                                {travelError && (
                                    <div className="mb-3 flex items-center gap-2 rounded border border-red-600/50 bg-red-900/30 px-3 py-2">
                                        <Zap className="h-4 w-4 text-red-400" />
                                        <span className="text-sm text-red-300">{travelError}</span>
                                    </div>
                                )}
                                <button
                                    onClick={handleTravel}
                                    disabled={
                                        isTraveling ||
                                        (player.location_type ===
                                            (clickedLocation.type === "port"
                                                ? "village"
                                                : clickedLocation.type) &&
                                            player.location_id === clickedLocation.data.id)
                                    }
                                    className="w-full rounded-lg border-2 border-amber-600 bg-amber-900/80 px-4 py-2 font-pixel text-sm text-amber-300 transition hover:bg-amber-800 disabled:cursor-not-allowed disabled:opacity-50"
                                >
                                    {isTraveling
                                        ? "Setting out..."
                                        : player.location_type ===
                                                (clickedLocation.type === "port"
                                                    ? "village"
                                                    : clickedLocation.type) &&
                                            player.location_id === clickedLocation.data.id
                                          ? "You are here"
                                          : "Travel Here"}
                                </button>
                            </div>
                        </div>
                    </div>
                )}

                {/* Travel Progress Overlay */}
                {travel_status?.is_traveling && (
                    <TravelProgressOverlay status={travel_status} isDev={is_dev} />
                )}
            </div>
        </AppLayout>
    );
}
