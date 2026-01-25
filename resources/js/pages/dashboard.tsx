import { Head, router, usePage } from '@inertiajs/react';
import { Anchor, Castle, Church, Compass, Crown, Home, MapPin, Minus, Plus, RotateCcw, Search, Users, X } from 'lucide-react';
import { useCallback, useMemo, useRef, useState } from 'react';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

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

interface PageProps {
    map_data: MapData;
    [key: string]: unknown;
}

const breadcrumbs: BreadcrumbItem[] = [{ title: 'World Map', href: '/dashboard' }];

// Biome terrain colors
const biomeColors: Record<string, { terrain: string; accent: string; label: string }> = {
    forest: { terrain: '#1a4d1a', accent: '#2d7a2d', label: 'Forest' },
    plains: { terrain: '#4a7c23', accent: '#6b9b3a', label: 'Plains' },
    mountains: { terrain: '#5c5c5c', accent: '#787878', label: 'Mountains' },
    swamps: { terrain: '#2d4a3a', accent: '#3d6b50', label: 'Swamps' },
    desert: { terrain: '#c9a227', accent: '#e3bc4a', label: 'Desert' },
    tundra: { terrain: '#a8c8d8', accent: '#c5dde8', label: 'Tundra' },
    coastal: { terrain: '#2a6a6a', accent: '#3a8a8a', label: 'Coastal' },
    volcano: { terrain: '#5c1a1a', accent: '#8a2a2a', label: 'Volcano' },
};

// Icon colors for different location types
const iconColors = {
    kingdom: { bg: '#1c1917', icon: '#fbbf24', stroke: '#fbbf24' },
    town: { bg: '#1e3a5f', icon: '#60a5fa', stroke: '#3b82f6' },
    barony: { bg: '#3d3d3d', icon: '#a8a29e', stroke: '#78716c' },
    village: { bg: '#14532d', icon: '#4ade80', stroke: '#22c55e' },
    port: { bg: '#1e3a5f', icon: '#38bdf8', stroke: '#0ea5e9' },
};

// Water colors
const WATER_COLOR = '#1a3a5c';
const DEEP_WATER_COLOR = '#0f2840';

function getBiomeColor(biome: string): { terrain: string; accent: string } {
    return biomeColors[biome] || { terrain: '#3d3d3d', accent: '#5c5c5c' };
}

// Generate unique island shape based on kingdom index
function generateIslandPath(cx: number, cy: number, index: number): string {
    const baseRadius = 160;
    const points: string[] = [];

    // Different shape parameters for each kingdom
    const shapeParams = [
        { segments: 16, freq1: 2.3, freq2: 3.7, amp1: 0.25, amp2: 0.15 }, // Rounded blob
        { segments: 14, freq1: 1.8, freq2: 4.2, amp1: 0.3, amp2: 0.1 },  // Elongated
        { segments: 18, freq1: 3.1, freq2: 2.4, amp1: 0.2, amp2: 0.2 },  // Star-ish
        { segments: 12, freq1: 2.7, freq2: 1.9, amp1: 0.35, amp2: 0.12 }, // Organic
    ];

    const params = shapeParams[index % shapeParams.length];

    for (let i = 0; i < params.segments; i++) {
        const angle = (i / params.segments) * Math.PI * 2;
        const variation = 0.7 +
            Math.sin(angle * params.freq1 + index) * params.amp1 +
            Math.cos(angle * params.freq2 + index * 0.5) * params.amp2;
        const r = baseRadius * variation;
        const px = cx + Math.cos(angle) * r;
        const py = cy + Math.sin(angle) * r;

        if (i === 0) {
            points.push(`M ${px} ${py}`);
        } else {
            // Use quadratic curves for smoother edges
            const prevAngle = ((i - 1) / params.segments) * Math.PI * 2;
            const midAngle = (angle + prevAngle) / 2;
            const midVariation = 0.7 +
                Math.sin(midAngle * params.freq1 + index) * params.amp1 +
                Math.cos(midAngle * params.freq2 + index * 0.5) * params.amp2;
            const midR = baseRadius * midVariation * 1.05;
            const cpx = cx + Math.cos(midAngle) * midR;
            const cpy = cy + Math.sin(midAngle) * midR;
            points.push(`Q ${cpx} ${cpy} ${px} ${py}`);
        }
    }
    points.push('Z');

    return points.join(' ');
}

export default function Dashboard() {
    const { map_data } = usePage<PageProps>().props;
    const { kingdoms, towns, baronies, villages, player, bounds } = map_data;

    const [zoom, setZoom] = useState(1);
    const [pan, setPan] = useState({ x: 0, y: 0 });
    const [isDragging, setIsDragging] = useState(false);
    const [dragStart, setDragStart] = useState({ x: 0, y: 0 });
    const [hoveredLocation, setHoveredLocation] = useState<{ type: string; data: Location } | null>(null);
    const [showLegend, setShowLegend] = useState(true);
    const [mouseCoords, setMouseCoords] = useState<{ x: number; y: number } | null>(null);
    const [searchQuery, setSearchQuery] = useState('');
    const [selectedResult, setSelectedResult] = useState<{ type: string; data: Location } | null>(null);
    const [clickedLocation, setClickedLocation] = useState<{ type: string; data: Location & { population?: number; is_port?: boolean; kingdom_name?: string; barony_name?: string } } | null>(null);
    const [isTraveling, setIsTraveling] = useState(false);
    const svgRef = useRef<SVGSVGElement>(null);

    // Calculate travel time from player position
    const getTravelTime = useCallback((loc: Location) => {
        const dist = Math.sqrt(
            Math.pow(loc.coordinates_x - player.coordinates_x, 2) +
            Math.pow(loc.coordinates_y - player.coordinates_y, 2)
        );
        return Math.max(1, Math.ceil(dist / 10));
    }, [player.coordinates_x, player.coordinates_y]);

    // Get rumor-style population description
    const getPopulationRumor = (pop?: number) => {
        if (!pop) return null;
        if (pop < 10) return "a quiet hamlet with few souls";
        if (pop < 25) return "a small settlement";
        if (pop < 50) return "a modest village";
        if (pop < 100) return "a bustling community";
        return "a thriving population";
    };

    // Get rumor-style services
    const getServiceRumors = (type: string, isPort?: boolean) => {
        const rumors: string[] = [];
        if (type === 'village') {
            rumors.push("Folk speak of a healer");
            rumors.push("There's said to be a bank");
            if (isPort) rumors.push("Ships dock at the harbor");
        } else if (type === 'barony') {
            rumors.push("Knights train in the barracks");
            rumors.push("An arena for combat");
            rumors.push("A secure vault");
        } else if (type === 'town') {
            rumors.push("A proper infirmary");
            rumors.push("The town hall handles affairs");
            rumors.push("Merchants trade freely");
        } else if (type === 'kingdom') {
            rumors.push("The seat of royal power");
        }
        return rumors;
    };

    const handleLocationClick = (type: string, data: Location & { population?: number; is_port?: boolean; kingdom_name?: string; barony_name?: string }) => {
        setClickedLocation({ type, data });
    };

    const handleTravel = () => {
        if (!clickedLocation) return;
        setIsTraveling(true);
        router.post('/travel/start', {
            destination_type: clickedLocation.type === 'port' ? 'village' : clickedLocation.type,
            destination_id: clickedLocation.data.id,
        }, {
            preserveScroll: true,
            onFinish: () => setIsTraveling(false),
            onSuccess: () => {
                setClickedLocation(null);
                router.visit('/travel');
            },
        });
    };

    // Search all locations
    const searchResults = useMemo(() => {
        if (!searchQuery.trim()) return [];
        const query = searchQuery.toLowerCase();
        const results: { type: string; data: Location; distance: number; travelTime: number }[] = [];

        const calcDistance = (loc: Location) => {
            return Math.sqrt(
                Math.pow(loc.coordinates_x - player.coordinates_x, 2) +
                Math.pow(loc.coordinates_y - player.coordinates_y, 2)
            );
        };

        kingdoms.filter(k => k.name.toLowerCase().includes(query)).forEach(k => {
            const dist = calcDistance(k);
            results.push({ type: 'kingdom', data: k, distance: dist, travelTime: Math.max(1, Math.ceil(dist / 10)) });
        });
        towns.filter(t => t.name.toLowerCase().includes(query)).forEach(t => {
            const dist = calcDistance(t);
            results.push({ type: 'town', data: t, distance: dist, travelTime: Math.max(1, Math.ceil(dist / 10)) });
        });
        baronies.filter(c => c.name.toLowerCase().includes(query)).forEach(c => {
            const dist = calcDistance(c);
            results.push({ type: 'barony', data: c, distance: dist, travelTime: Math.max(1, Math.ceil(dist / 10)) });
        });
        villages.filter(v => v.name.toLowerCase().includes(query)).forEach(v => {
            const dist = calcDistance(v);
            results.push({ type: v.is_port ? 'port' : 'village', data: v, distance: dist, travelTime: Math.max(1, Math.ceil(dist / 10)) });
        });

        return results.sort((a, b) => a.distance - b.distance).slice(0, 8);
    }, [searchQuery, kingdoms, towns, baronies, villages, player.coordinates_x, player.coordinates_y]);

    const handleSelectResult = (result: { type: string; data: Location }) => {
        setSelectedResult(result);
        // Center on the location
        const mapCenterX = bounds.min_x + mapWidth / 2;
        const mapCenterY = bounds.min_y + mapHeight / 2;
        setPan({
            x: mapCenterX - result.data.coordinates_x,
            y: mapCenterY - result.data.coordinates_y
        });
        setZoom(2.5);
        setSearchQuery('');
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
            y: mapCenterY - player.coordinates_y
        });
        setZoom(2.5);
    }, [player.coordinates_x, player.coordinates_y, bounds.min_x, bounds.min_y, mapWidth, mapHeight]);

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
        setZoom((z) => e.deltaY < 0 ? Math.min(z * 1.2, 5) : Math.max(z / 1.2, 0.5));
    };

    const transformY = (y: number) => -y;

    // Kingdoms to merge into one landmass (keeping distinct biome regions)
    const MERGED_KINGDOMS = ['Sandmar', 'Frostholm', 'Ashenfell'];

    // Generate terrain - large kingdom islands that encompass all locations
    const terrainRegions = useMemo(() => {
        const regions: JSX.Element[] = [];

        // Group all locations by kingdom
        const kingdomData: Record<number, { kingdom: Kingdom; locations: { x: number; y: number }[] }> = {};

        kingdoms.forEach((k) => {
            kingdomData[k.id] = { kingdom: k, locations: [{ x: k.coordinates_x, y: k.coordinates_y }] };
        });

        towns.forEach((t) => {
            if (kingdomData[t.kingdom_id]) {
                kingdomData[t.kingdom_id].locations.push({ x: t.coordinates_x, y: t.coordinates_y });
            }
        });

        baronies.forEach((b) => {
            if (kingdomData[b.kingdom_id]) {
                kingdomData[b.kingdom_id].locations.push({ x: b.coordinates_x, y: b.coordinates_y });
            }
        });

        villages.forEach((v) => {
            const barony = baronies.find((b) => b.id === v.barony_id);
            if (barony && kingdomData[barony.kingdom_id]) {
                kingdomData[barony.kingdom_id].locations.push({ x: v.coordinates_x, y: v.coordinates_y });
            }
        });

        // Separate merged kingdoms from standalone ones
        const mergedKingdomIds = Object.values(kingdomData)
            .filter((d) => MERGED_KINGDOMS.includes(d.kingdom.name))
            .map((d) => d.kingdom.id);

        const standaloneKingdoms = Object.values(kingdomData).filter(
            (d) => !MERGED_KINGDOMS.includes(d.kingdom.name)
        );

        const mergedKingdoms = Object.values(kingdomData).filter((d) =>
            MERGED_KINGDOMS.includes(d.kingdom.name)
        );

        // Draw merged landmass for Sandmar, Frostholm, Ashenfell
        if (mergedKingdoms.length > 0) {
            // Get kingdom centers and bounds
            const kingdomInfo: { name: string; x: number; y: number; minX: number; maxX: number; minY: number; maxY: number; colors: { terrain: string; accent: string } }[] = [];

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
                const padding = kingdom.name === 'Sandmar' ? 50 : 30;
                const kRadiusX = (kMaxX - kMinX) / 2 + padding;
                const kRadiusY = (kMaxY - kMinY) / 2 + padding;
                const kBaseRadius = Math.max(kRadiusX, kRadiusY, 110);

                // Generate organic blob for this kingdom
                const biomePoints: string[] = [];
                const biomeSegments = 24;

                for (let i = 0; i < biomeSegments; i++) {
                    const angle = (i / biomeSegments) * Math.PI * 2;

                    // Only expand outward, never shrink (1.0 + positive values only)
                    const variation = 1.0 +
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
                biomePoints.push('Z');

                regions.push(
                    <path
                        key={`kingdom-biome-${kingdom.id}`}
                        d={biomePoints.join(' ')}
                        fill={colors.terrain}
                        stroke={colors.accent}
                        strokeWidth={2}
                        opacity={0.95}
                    />
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
                const variation = 1.0 +
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
            points.push('Z');

            regions.push(
                <path
                    key={`kingdom-land-${kingdom.id}`}
                    d={points.join(' ')}
                    fill={colors.terrain}
                    stroke={colors.accent}
                    strokeWidth={3}
                    opacity={0.9}
                />
            );
        });

        return regions;
    }, [kingdoms, towns, baronies, villages]);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="World Map" />
            <div className="relative flex h-0 min-h-0 w-full flex-1 overflow-hidden" style={{ backgroundColor: DEEP_WATER_COLOR }}>
                {/* Right Controls */}
                <div className="absolute right-4 top-4 z-10 flex flex-col gap-2">
                    <button onClick={handleZoomIn} className="rounded-lg border-2 border-stone-600 bg-stone-800/90 p-2 text-stone-300 transition hover:bg-stone-700" title="Zoom In">
                        <Plus className="h-5 w-5" />
                    </button>
                    <button onClick={handleZoomOut} className="rounded-lg border-2 border-stone-600 bg-stone-800/90 p-2 text-stone-300 transition hover:bg-stone-700" title="Zoom Out">
                        <Minus className="h-5 w-5" />
                    </button>
                    <button onClick={handleReset} className="rounded-lg border-2 border-stone-600 bg-stone-800/90 p-2 text-stone-300 transition hover:bg-stone-700" title="Reset View">
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
                                onClick={() => setSearchQuery('')}
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
                                const Icon = result.type === 'kingdom' ? Crown
                                    : result.type === 'town' ? Church
                                    : result.type === 'barony' ? Castle
                                    : result.type === 'port' ? Anchor
                                    : Home;
                                const color = iconColors[result.type as keyof typeof iconColors]?.icon || iconColors.village.icon;
                                return (
                                    <button
                                        key={`${result.type}-${result.data.id}-${idx}`}
                                        onClick={() => handleSelectResult(result)}
                                        className="flex w-full items-center gap-2 px-3 py-2 text-left transition hover:bg-stone-700/50"
                                    >
                                        <Icon className="h-4 w-4 flex-shrink-0" style={{ color }} />
                                        <div className="min-w-0 flex-1">
                                            <div className="truncate text-sm text-stone-200">{result.data.name}</div>
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
                                <Crown className="h-3.5 w-3.5" style={{ color: iconColors.kingdom.icon }} />
                                <span className="font-pixel text-[10px] text-stone-300">Kingdom</span>
                            </div>
                            <div className="flex items-center gap-2">
                                <Church className="h-3.5 w-3.5" style={{ color: iconColors.town.icon }} />
                                <span className="font-pixel text-[10px] text-stone-300">Town</span>
                            </div>
                            <div className="flex items-center gap-2">
                                <Castle className="h-3.5 w-3.5" style={{ color: iconColors.barony.icon }} />
                                <span className="font-pixel text-[10px] text-stone-300">Barony</span>
                            </div>
                            <div className="flex items-center gap-2">
                                <Home className="h-3.5 w-3.5" style={{ color: iconColors.village.icon }} />
                                <span className="font-pixel text-[10px] text-stone-300">Village</span>
                            </div>
                            <div className="flex items-center gap-2">
                                <Anchor className="h-3.5 w-3.5" style={{ color: iconColors.port.icon }} />
                                <span className="font-pixel text-[10px] text-stone-300">Port</span>
                            </div>
                            <div className="flex items-center gap-2">
                                <MapPin className="h-3.5 w-3.5 text-amber-400" />
                                <span className="font-pixel text-[10px] text-stone-300">You</span>
                            </div>
                        </div>
                        <div className="mt-2 border-t border-stone-700 pt-2">
                            <div className="grid grid-cols-2 gap-1">
                                {Object.entries(biomeColors).slice(0, 6).map(([biome, colors]) => (
                                    <div key={biome} className="flex items-center gap-1">
                                        <div className="h-2 w-2 rounded-sm" style={{ backgroundColor: colors.terrain }} />
                                        <span className="font-pixel text-[8px] text-stone-500">{colors.label}</span>
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
                            {hoveredLocation.type === 'kingdom' && <Crown className="h-5 w-5 flex-shrink-0" style={{ color: iconColors.kingdom.icon }} />}
                            {hoveredLocation.type === 'town' && <Church className="h-5 w-5 flex-shrink-0" style={{ color: iconColors.town.icon }} />}
                            {hoveredLocation.type === 'barony' && <Castle className="h-5 w-5 flex-shrink-0" style={{ color: iconColors.barony.icon }} />}
                            {hoveredLocation.type === 'village' && <Home className="h-5 w-5 flex-shrink-0" style={{ color: iconColors.village.icon }} />}
                            {hoveredLocation.type === 'port' && <Anchor className="h-5 w-5 flex-shrink-0" style={{ color: iconColors.port.icon }} />}
                            <span className="font-pixel text-sm leading-none text-amber-300">{hoveredLocation.data.name}</span>
                        </div>
                    </div>
                )}

                {/* SVG Map */}
                <svg
                    ref={svgRef}
                    className={`h-full w-full ${isDragging ? 'cursor-grabbing' : 'cursor-grab'}`}
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
                        <pattern id="wavePattern" patternUnits="userSpaceOnUse" width="80" height="80">
                            <path d="M0 40 Q20 35, 40 40 T80 40" fill="none" stroke="rgba(255,255,255,0.04)" strokeWidth="1" />
                            <path d="M0 60 Q20 55, 40 60 T80 60" fill="none" stroke="rgba(255,255,255,0.03)" strokeWidth="1" />
                        </pattern>
                    </defs>

                    {/* Ocean */}
                    <rect x={bounds.min_x - 500} y={transformY(bounds.max_y + 500)} width={mapWidth + 1000} height={mapHeight + 1000} fill="url(#waterGradient)" />
                    <rect x={bounds.min_x - 500} y={transformY(bounds.max_y + 500)} width={mapWidth + 1000} height={mapHeight + 1000} fill="url(#wavePattern)" />

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
                                onMouseEnter={() => setHoveredLocation({ type: isPort ? 'port' : 'village', data: village })}
                                onMouseLeave={() => setHoveredLocation(null)}
                                onClick={() => handleLocationClick(isPort ? 'port' : 'village', village)}
                            >
                                <circle r={isPort ? 8 : 6} fill={colors.bg} stroke={colors.stroke} strokeWidth={1.5} />
                                {isPort ? (
                                    <Anchor x={-5} y={-5} width={10} height={10} color={colors.icon} strokeWidth={2} />
                                ) : (
                                    <Home x={-4} y={-4} width={8} height={8} color={colors.icon} strokeWidth={2} />
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
                                onMouseEnter={() => setHoveredLocation({ type: 'barony', data: barony })}
                                onMouseLeave={() => setHoveredLocation(null)}
                                onClick={() => handleLocationClick('barony', barony)}
                            >
                                <circle r={10} fill={colors.bg} stroke={colors.stroke} strokeWidth={2} />
                                <Castle x={-7} y={-7} width={14} height={14} color={colors.icon} strokeWidth={1.5} />
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
                                onMouseEnter={() => setHoveredLocation({ type: 'town', data: town })}
                                onMouseLeave={() => setHoveredLocation(null)}
                                onClick={() => handleLocationClick('town', town)}
                            >
                                <circle r={12} fill={colors.bg} stroke={colors.stroke} strokeWidth={2} />
                                <Church x={-8} y={-8} width={16} height={16} color={colors.icon} strokeWidth={1.5} />
                                {town.is_capital && <circle r={16} fill="none" stroke="#fbbf24" strokeWidth={2} strokeDasharray="4 3" />}
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
                                onMouseEnter={() => setHoveredLocation({ type: 'kingdom', data: kingdom })}
                                onMouseLeave={() => setHoveredLocation(null)}
                                onClick={() => handleLocationClick('kingdom', kingdom)}
                            >
                                <circle r={18} fill={colors.bg} stroke={colors.stroke} strokeWidth={3} />
                                <Crown x={-11} y={-11} width={22} height={22} color={colors.icon} fill={colors.icon} strokeWidth={0} />
                            </g>
                        );
                    })}

                    {/* Player location - single pulsing ring */}
                    <g transform={`translate(${player.coordinates_x}, ${transformY(player.coordinates_y)})`}>
                        <circle r={18} fill="none" stroke="#f59e0b" strokeWidth={3}>
                            <animate attributeName="r" from="12" to="25" dur="1.5s" repeatCount="indefinite" />
                            <animate attributeName="opacity" from="1" to="0" dur="1.5s" repeatCount="indefinite" />
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
                            style={{ textShadow: '0 1px 3px rgba(0,0,0,0.9)' }}
                        >
                            {kingdom.name}
                        </text>
                    ))}
                </svg>

                {/* Position Display */}
                <div className="absolute bottom-4 right-4 rounded-lg border-2 border-stone-600 bg-stone-800/95 px-3 py-1.5">
                    <span className="font-pixel text-[10px] text-stone-400">
                        {mouseCoords ? (
                            <>({mouseCoords.x}, {mouseCoords.y})</>
                        ) : (
                            <>({player.coordinates_x}, {player.coordinates_y})</>
                        )}
                    </span>
                </div>

                {/* Location Info Modal */}
                {clickedLocation && (
                    <div className="absolute inset-0 z-30 flex items-center justify-center bg-black/50" onClick={() => setClickedLocation(null)}>
                        <div
                            className="mx-4 w-full max-w-sm rounded-lg border-2 border-amber-600/70 bg-stone-900/95 shadow-xl"
                            onClick={(e) => e.stopPropagation()}
                        >
                            {/* Header */}
                            <div className="flex items-center justify-between border-b border-stone-700 px-4 py-3">
                                <div className="flex items-center gap-2">
                                    {clickedLocation.type === 'kingdom' && <Crown className="h-5 w-5" style={{ color: iconColors.kingdom.icon }} />}
                                    {clickedLocation.type === 'town' && <Church className="h-5 w-5" style={{ color: iconColors.town.icon }} />}
                                    {clickedLocation.type === 'barony' && <Castle className="h-5 w-5" style={{ color: iconColors.barony.icon }} />}
                                    {clickedLocation.type === 'village' && <Home className="h-5 w-5" style={{ color: iconColors.village.icon }} />}
                                    {clickedLocation.type === 'port' && <Anchor className="h-5 w-5" style={{ color: iconColors.port.icon }} />}
                                    <span className="font-pixel text-base text-amber-300">{clickedLocation.data.name}</span>
                                </div>
                                <button onClick={() => setClickedLocation(null)} className="text-stone-500 hover:text-stone-300">
                                    <X className="h-5 w-5" />
                                </button>
                            </div>

                            {/* Content */}
                            <div className="space-y-3 px-4 py-3">
                                {/* Type & Region */}
                                <div className="text-sm text-stone-400">
                                    <span className="capitalize">{clickedLocation.type === 'port' ? 'Port Village' : clickedLocation.type}</span>
                                    {clickedLocation.data.kingdom_name && (
                                        <span> in {clickedLocation.data.kingdom_name}</span>
                                    )}
                                </div>

                                {/* Population Rumor */}
                                {clickedLocation.data.population && (
                                    <div className="flex items-start gap-2 text-sm">
                                        <Users className="mt-0.5 h-4 w-4 flex-shrink-0 text-stone-500" />
                                        <span className="italic text-stone-300">
                                            "They say it's {getPopulationRumor(clickedLocation.data.population)}..."
                                        </span>
                                    </div>
                                )}

                                {/* Service Rumors */}
                                <div className="space-y-1">
                                    <div className="text-xs font-medium uppercase tracking-wide text-stone-500">Rumors speak of...</div>
                                    <ul className="space-y-1 text-sm text-stone-300">
                                        {getServiceRumors(clickedLocation.type === 'port' ? 'village' : clickedLocation.type, clickedLocation.data.is_port).map((rumor, i) => (
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
                                        About <span className="font-medium text-amber-400">{getTravelTime(clickedLocation.data)} minutes</span> journey from here
                                    </span>
                                </div>
                            </div>

                            {/* Footer */}
                            <div className="border-t border-stone-700 px-4 py-3">
                                <button
                                    onClick={handleTravel}
                                    disabled={isTraveling || (player.location_type === (clickedLocation.type === 'port' ? 'village' : clickedLocation.type) && player.location_id === clickedLocation.data.id)}
                                    className="w-full rounded-lg border-2 border-amber-600 bg-amber-900/80 px-4 py-2 font-pixel text-sm text-amber-300 transition hover:bg-amber-800 disabled:cursor-not-allowed disabled:opacity-50"
                                >
                                    {isTraveling ? 'Setting out...' : player.location_type === (clickedLocation.type === 'port' ? 'village' : clickedLocation.type) && player.location_id === clickedLocation.data.id ? 'You are here' : 'Travel Here'}
                                </button>
                            </div>
                        </div>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
