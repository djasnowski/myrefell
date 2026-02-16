import { Head, router, usePage } from "@inertiajs/react";
import {
    AlertTriangle,
    Award,
    Beef,
    Beer,
    Briefcase,
    Check,
    Church,
    Clock,
    Coins,
    Croissant,
    Crown,
    Fish,
    Flag,
    Gavel,
    Heart,
    Hop,
    Loader2,
    LogOut,
    Pickaxe,
    Scale,
    Scroll,
    Shield,
    ShieldCheck,
    Sparkles,
    Swords,
    Target,
    TreeDeciduous,
    User,
    Users,
    Wallet,
    Wheat,
    Wrench,
    X,
} from "lucide-react";
import { useState } from "react";
import { gameToast } from "@/components/ui/game-toast";
import AppLayout from "@/layouts/app-layout";
import { formatBonus, locationPath } from "@/lib/utils";
import type { BreadcrumbItem } from "@/types";

interface RoleHolder {
    player_role_id: number;
    user_id: number;
    username: string;
    title: string;
    social_class: string;
    total_level: number;
    appointed_at: string;
    expires_at: string | null;
    total_salary_earned: number;
}

interface RoleNpc {
    id: number;
    name: string;
    description: string;
    icon: string;
}

interface Role {
    id: number;
    name: string;
    slug: string;
    icon: string;
    description: string;
    location_type: string;
    permissions: string[];
    bonuses: Record<string, number>;
    salary: number;
    tier: number;
    is_elected: boolean;
    max_per_location: number;
    holder: RoleHolder | null;
    npc: RoleNpc | null;
    is_vacant: boolean;
}

interface UserRole {
    id: number;
    role_id: number;
    name: string;
    slug: string;
    icon: string;
    description: string;
    location_type: string;
    location_id: number;
    location_name: string;
    permissions: string[];
    bonuses: Record<string, number>;
    salary: number;
    tier: number;
    status: string;
    appointed_at: string;
    expires_at: string | null;
    total_salary_earned: number;
}

interface PageProps {
    location_type: string;
    location_id: number;
    location_name: string;
    roles: Role[];
    user_roles: UserRole[];
    user_roles_here: UserRole[];
    population: number;
    can_self_appoint: boolean;
    user_resides_here: boolean;
    user_is_here: boolean;
    self_appoint_threshold: number;
    king_claimable: boolean;
    king_unlock_date: string | null;
    player: {
        id: number;
        username: string;
        gold: number;
        title_tier: number;
    };
    [key: string]: unknown;
}

const iconMap: Record<string, typeof Crown> = {
    crown: Crown,
    shield: Shield,
    gavel: Gavel,
    wrench: Wrench,
    heart: Heart,
    swords: Swords,
    scale: Scale,
    briefcase: Briefcase,
    wallet: Wallet,
    award: Award,
    users: Users,
    user: User,
    shieldcheck: ShieldCheck,
    wheat: Wheat,
    beer: Beer,
    "tree-deciduous": TreeDeciduous,
    fish: Fish,
    church: Church,
    croissant: Croissant,
    beef: Beef,
    target: Target,
    pickaxe: Pickaxe,
    hop: Hop,
};

const tierColors: Record<number, string> = {
    1: "border-stone-600/50 bg-stone-800/40 hover:bg-stone-800/60",
    2: "border-blue-600/50 bg-blue-900/20 hover:bg-blue-900/40",
    3: "border-purple-600/50 bg-purple-900/20 hover:bg-purple-900/40",
    4: "border-amber-600/50 bg-amber-900/20 hover:bg-amber-900/40",
    5: "border-red-600/50 bg-red-900/20 hover:bg-red-900/40",
};

const tierModalColors: Record<number, string> = {
    1: "border-stone-500 bg-stone-800",
    2: "border-blue-500 bg-[#1a1a2e]",
    3: "border-purple-500 bg-[#1f1a2e]",
    4: "border-amber-500 bg-[#2a2518]",
    5: "border-red-500 bg-[#2a1a1a]",
};

const tierBadgeColors: Record<number, string> = {
    1: "bg-stone-700 text-stone-300",
    2: "bg-blue-800 text-blue-200",
    3: "bg-purple-800 text-purple-200",
    4: "bg-amber-800 text-amber-200",
    5: "bg-red-800 text-red-200",
};

// Short descriptions for role cards
const roleShortDesc: Record<string, string> = {
    elder: "Governs the village and sets taxes",
    guard_captain: "Defends the village and keeps peace",
    priest: "Conducts ceremonies and gives blessings",
    blacksmith: "Forges and repairs equipment",
    merchant: "Runs the market and trades goods",
    healer: "Treats wounds and cures disease",
    master_farmer: "Oversees crops and farming",
    innkeeper: "Runs the tavern and rents rooms",
    forester: "Manages the woods and timber",
    fisherman: "Provides fresh fish to the village",
    baker: "Bakes bread and pastries",
    butcher: "Processes meat for the village",
    hunter: "Hunts game and provides hides",
    miner: "Extracts ore from the mines",
    brewer: "Brews ale and mead",
    "stable-master-village": "Cares for horses and mounts",
    baron: "Rules the barony and its lands",
    steward: "Administers barony affairs",
    marshal: "Commands the military forces",
    treasurer: "Manages the treasury",
    jailsman: "Oversees the dungeon",
    king: "Rules the entire kingdom",
    chancellor: "Advises the king on diplomacy",
    general: "Commands the royal armies",
    royal_treasurer: "Keeps the royal treasury",
};

// What happens without each role
const roleConsequences: Record<string, { duties: string[]; ifVacant: string }> = {
    elder: {
        duties: [
            "Set village taxes",
            "Appoint other roles",
            "Settle disputes",
            "Manage village funds",
        ],
        ifVacant: "No taxes collected, no new appointments, village decisions stall.",
    },
    guard_captain: {
        duties: [
            "Patrol the village",
            "Arrest criminals",
            "Defend against attacks",
            "Train militia",
        ],
        ifVacant: "Crime increases, no arrests made, village vulnerable to raids.",
    },
    priest: {
        duties: ["Conduct marriages", "Perform funerals", "Give blessings", "Maintain the shrine"],
        ifVacant: "No weddings or funerals, morale drops, no blessings available.",
    },
    blacksmith: {
        duties: ["Repair equipment", "Forge new tools", "Craft weapons and armor"],
        ifVacant: "No equipment repairs, tools degrade faster, crafting limited.",
    },
    merchant: {
        duties: [
            "Buy goods from players",
            "Sell supplies",
            "Manage market prices",
            "Handle trade deals",
        ],
        ifVacant: "Market has limited stock, worse buy/sell prices.",
    },
    healer: {
        duties: ["Heal the wounded", "Cure diseases", "Provide medicine", "Tend to births"],
        ifVacant: "Healing costs more, diseases spread, higher death rate.",
    },
    master_farmer: {
        duties: [
            "Oversee village crops",
            "Distribute seeds",
            "Set harvest schedules",
            "Manage livestock",
        ],
        ifVacant: "No farming bonuses, seed prices higher, crop yields lower.",
    },
    innkeeper: {
        duties: ["Run the tavern", "Rent rooms", "Serve food and drink", "Share local news"],
        ifVacant: "No rest bonuses, tavern services limited, rumors dry up.",
    },
    forester: {
        duties: ["Manage the woods", "Sustainable logging", "Sell timber to merchant"],
        ifVacant: "No woodcutting bonuses, wood supply limited.",
    },
    fisherman: {
        duties: ["Fish local waters", "Supply fresh fish", "Maintain boats"],
        ifVacant: "No fishing bonuses, fish supply limited.",
    },
    baker: {
        duties: ["Bake bread daily", "Supply the tavern", "Process grain into flour"],
        ifVacant: "No bread in market, cooking bonuses unavailable.",
    },
    butcher: {
        duties: ["Process meat", "Cure and preserve food", "Supply the tavern"],
        ifVacant: "No meat processing, raw meat spoils faster.",
    },
    hunter: {
        duties: ["Hunt game", "Provide meat and hides", "Track dangerous animals"],
        ifVacant: "No hunting expeditions, meat and leather scarce.",
    },
    miner: {
        duties: ["Extract ore", "Manage mine safety", "Supply the blacksmith"],
        ifVacant: "No mining bonuses, ore supply limited.",
    },
    brewer: {
        duties: ["Brew ale and mead", "Supply the tavern", "Process hops and grain"],
        ifVacant: "No ale in tavern, brewing bonuses unavailable.",
    },
    "stable-master-village": {
        duties: ["Care for horses", "Sell mounts", "Provide stabling services"],
        ifVacant: "No horses available, mount services limited.",
    },
    // Town roles
    mayor: {
        duties: ["Govern the town", "Set taxes", "Appoint officials", "Manage treasury"],
        ifVacant: "No taxes collected, no appointments, town governance halted.",
    },
    town_guard_captain: {
        duties: ["Command the town guard", "Arrest criminals", "Defend the town", "Patrol streets"],
        ifVacant: "Crime runs rampant, no arrests, town vulnerable to attack.",
    },
    guildmaster: {
        duties: ["Oversee trade guilds", "Set trade standards", "Mediate guild disputes"],
        ifVacant: "Guild disputes unresolved, trade standards slip, no guild oversight.",
    },
    town_clerk: {
        duties: ["Maintain records", "Process permits", "Manage census", "Handle correspondence"],
        ifVacant: "Records fall behind, permits delayed, census data outdated.",
    },
    market_warden: {
        duties: ["Regulate market prices", "Inspect goods", "Prevent fraud", "Collect market fees"],
        ifVacant: "Market prices unstable, counterfeit goods unchecked, no fee collection.",
    },
    town_physician: {
        duties: ["Treat the sick", "Manage the infirmary", "Prevent plagues", "Train healers"],
        ifVacant: "Disease spreads unchecked, healing costs increase, no plague prevention.",
    },
    head_chef: {
        duties: ["Manage food supply", "Run the kitchen", "Prepare feasts", "Train cooks"],
        ifVacant: "No feast bonuses, food quality drops, no cooking training.",
    },
    town_crier: {
        duties: ["Announce news", "Deliver proclamations", "Warn of danger"],
        ifVacant: "News travels slowly, proclamations undelivered.",
    },
    master_blacksmith: {
        duties: ["Oversee all smithing", "Forge masterwork items", "Train apprentices"],
        ifVacant: "No masterwork crafting, apprentice training halted, smithing quality drops.",
    },
    weaponsmith: {
        duties: ["Forge weapons", "Sharpen blades", "Supply the guard", "Repair arms"],
        ifVacant: "No weapon forging, guards poorly armed, blade repairs unavailable.",
    },
    armorsmith: {
        duties: ["Forge armor", "Repair shields", "Supply the guard", "Fit plate mail"],
        ifVacant: "No armor forging, guards poorly protected, repairs unavailable.",
    },
    master_jeweler: {
        duties: ["Craft jewelry", "Appraise gems", "Set precious stones", "Create regalia"],
        ifVacant: "No jewelry crafting, gem appraisals unavailable.",
    },
    master_tailor: {
        duties: ["Sew garments", "Make uniforms", "Repair clothing", "Craft banners"],
        ifVacant: "No tailoring, uniforms tattered, banner crafting unavailable.",
    },
    master_carpenter: {
        duties: ["Build structures", "Repair buildings", "Craft furniture", "Maintain walls"],
        ifVacant: "Buildings fall into disrepair, no new construction, walls weaken.",
    },
    alchemist: {
        duties: ["Brew potions", "Research remedies", "Supply the physician", "Create elixirs"],
        ifVacant: "No potions available, remedies scarce, no alchemical research.",
    },
    tanner: {
        duties: ["Tan hides", "Produce leather", "Supply armorsmith", "Cure pelts"],
        ifVacant: "No leather production, hides rot, leather goods unavailable.",
    },
    scribe: {
        duties: ["Copy documents", "Write letters", "Maintain the library", "Record history"],
        ifVacant: "Records lost, no correspondence, library unmaintained.",
    },
    master_miner: {
        duties: ["Manage the mines", "Ensure miner safety", "Oversee ore production"],
        ifVacant: "Mining output drops, no safety oversight, ore supply dwindles.",
    },
    master_fisher: {
        duties: ["Manage fishing grounds", "Organize fishing fleet", "Supply the market"],
        ifVacant: "Fish supply drops, fishing grounds overworked, no fleet coordination.",
    },
    brewmaster: {
        duties: ["Brew ale and mead", "Manage the brewery", "Supply taverns", "Create recipes"],
        ifVacant: "No ale brewed, taverns run dry, brewery idle.",
    },
    magistrate: {
        duties: ["Preside over court", "Judge disputes", "Sentence criminals", "Interpret law"],
        ifVacant: "No justice served, disputes fester, criminals go unpunished.",
    },
    harbormaster: {
        duties: ["Manage the port", "Collect dock fees", "Inspect cargo", "Direct ships"],
        ifVacant: "Port in disarray, no dock fees collected, cargo unchecked.",
    },
    stablemaster: {
        duties: ["Care for horses", "Sell mounts", "Manage stables", "Breed warhorses"],
        ifVacant: "No horses available, stables neglected, no mount services.",
    },
    // Barony roles
    baron: {
        duties: ["Rule the barony", "Collect taxes", "Dispense justice", "Defend the realm"],
        ifVacant: "No law or order, taxes uncollected, barony ungoverned.",
    },
    marshal: {
        duties: ["Command the garrison", "Train soldiers", "Plan defenses", "Lead in battle"],
        ifVacant: "Garrison leaderless, defenses crumble, no military training.",
    },
    steward: {
        duties: ["Manage the castle", "Oversee supplies", "Handle logistics", "Manage staff"],
        ifVacant: "Castle supplies mismanaged, logistics in chaos, staff unsupervised.",
    },
    castellan: {
        duties: ["Defend the castle", "Manage fortifications", "Command the watch"],
        ifVacant: "Castle defenses weakened, walls unmaintained, no night watch.",
    },
    treasurer: {
        duties: ["Manage finances", "Collect taxes", "Pay wages", "Audit accounts"],
        ifVacant: "Finances in disarray, wages unpaid, taxes uncollected.",
    },
    physician: {
        duties: ["Treat the lord's court", "Manage infirmary", "Research cures"],
        ifVacant: "Court illnesses untreated, no medical care for garrison.",
    },
    court_chaplain: {
        duties: ["Lead prayers", "Advise on moral matters", "Bless the troops", "Keep records"],
        ifVacant: "No spiritual guidance, troops unblessed, morale suffers.",
    },
    jailsman: {
        duties: ["Guard prisoners", "Manage the dungeon", "Carry out sentences"],
        ifVacant: "Prisoners escape, dungeon unguarded, no sentences carried out.",
    },
    master_cook: {
        duties: ["Feed the castle", "Prepare feasts", "Manage the kitchens"],
        ifVacant: "Castle meals poor, no feasts, kitchen staff unsupervised.",
    },
    castle_stablemaster: {
        duties: ["Manage war horses", "Maintain cavalry mounts", "Breed destriers"],
        ifVacant: "War horses neglected, cavalry unmounted, no breeding program.",
    },
    "stable-master-barony": {
        duties: ["Care for horses", "Manage stables", "Provide mounts"],
        ifVacant: "Stables neglected, no mounts available.",
    },
    // Duchy roles
    duke: {
        duties: ["Rule the duchy", "Command vassals", "Dispense high justice", "Advise the king"],
        ifVacant: "Duchy ungoverned, vassals unruly, high justice unavailable.",
    },
    duchy_chancellor: {
        duties: ["Advise the duke", "Draft laws", "Handle diplomacy", "Manage court"],
        ifVacant: "No diplomatic relations, laws unwritten, court in disarray.",
    },
    duchy_marshal: {
        duties: ["Command duchy forces", "Coordinate barons' armies", "Plan campaigns"],
        ifVacant: "No military coordination, duchy defenses fragmented.",
    },
    duchy_treasurer: {
        duties: ["Manage duchy finances", "Audit baronies", "Fund infrastructure"],
        ifVacant: "Duchy finances unchecked, baronies unaudited, no infrastructure funding.",
    },
    high_priest: {
        duties: ["Lead the duchy's faith", "Ordain priests", "Consecrate temples"],
        ifVacant: "Faith wavers, no ordinations, temples unconsecrated.",
    },
    duchy_physician: {
        duties: ["Oversee duchy health", "Combat plagues", "Train physicians"],
        ifVacant: "Plagues spread unchecked, no physician training, health deteriorates.",
    },
    duchy_chef: {
        duties: ["Manage ducal kitchens", "Host grand feasts", "Train cooks"],
        ifVacant: "No grand feasts, ducal kitchens idle, cooks untrained.",
    },
    master_of_hunts: {
        duties: ["Organize hunts", "Manage game reserves", "Supply the court with meat"],
        ifVacant: "No hunts organized, game reserves unmanaged, court meat supply drops.",
    },
    ducal_stablemaster: {
        duties: ["Manage ducal stables", "Breed fine horses", "Supply cavalry"],
        ifVacant: "Ducal stables neglected, no fine horses bred, cavalry undersupplied.",
    },
    // Kingdom roles
    king: {
        duties: ["Rule the kingdom", "Command all forces", "Make law", "Protect the realm"],
        ifVacant: "The realm is without a ruler. Chaos looms.",
    },
    chancellor: {
        duties: ["Chief advisor to the king", "Draft royal decrees", "Handle foreign affairs"],
        ifVacant: "No royal counsel, decrees unwritten, foreign relations collapse.",
    },
    general: {
        duties: ["Command the royal army", "Plan wars", "Defend the borders"],
        ifVacant: "Army leaderless, borders undefended, no war planning.",
    },
    royal_treasurer: {
        duties: ["Manage the royal treasury", "Set kingdom taxes", "Fund the army"],
        ifVacant: "Treasury unmanaged, kingdom taxes uncollected, army unfunded.",
    },
    archbishop: {
        duties: ["Lead the kingdom's faith", "Crown the king", "Advise on spiritual matters"],
        ifVacant: "No coronations, faith leaderless, spiritual guidance absent.",
    },
    royal_steward: {
        duties: ["Manage royal estates", "Oversee the palace", "Coordinate servants"],
        ifVacant: "Royal estates mismanaged, palace in disrepair.",
    },
    lord_marshal: {
        duties: ["Oversee all military", "Coordinate generals", "Enforce martial law"],
        ifVacant: "Military command fractured, no martial coordination.",
    },
    master_of_laws: {
        duties: ["Interpret royal law", "Judge appeals", "Advise on legal matters"],
        ifVacant: "Laws unenforced, appeals unheard, legal chaos.",
    },
    spymaster: {
        duties: ["Gather intelligence", "Root out traitors", "Manage informants"],
        ifVacant: "No intelligence gathered, plots go undetected, traitors unchecked.",
    },
    royal_herald: {
        duties: ["Announce royal decrees", "Organize ceremonies", "Maintain heraldry"],
        ifVacant: "Royal decrees unannounced, ceremonies disorganized.",
    },
    royal_physician: {
        duties: ["Tend to the royal family", "Oversee kingdom health", "Combat epidemics"],
        ifVacant: "Royal family unattended, epidemics unchecked.",
    },
    royal_chef: {
        duties: ["Prepare royal feasts", "Manage palace kitchens", "Taste for poison"],
        ifVacant: "No royal feasts, palace kitchens idle, no food tasting.",
    },
    royal_stablemaster: {
        duties: ["Manage royal stables", "Breed champion horses", "Maintain the royal cavalry"],
        ifVacant: "Royal stables neglected, no champion horses, cavalry weakened.",
    },
};

function formatDate(dateString: string): string {
    return new Date(dateString).toLocaleDateString("en-US", {
        month: "short",
        day: "numeric",
    });
}

function ChallengeModal({ role, onClose }: { role: Role; onClose: () => void }) {
    const [reason, setReason] = useState("");
    const [requestAppointment, setRequestAppointment] = useState(false);
    const [loading, setLoading] = useState(false);

    const handleSubmit = () => {
        if (!role.holder || !reason.trim()) return;
        setLoading(true);
        router.post(
            `/roles/${role.holder.player_role_id}/petition`,
            {
                reason: reason.trim(),
                request_appointment: requestAppointment,
            },
            {
                preserveScroll: true,
                onSuccess: () => {
                    onClose();
                    router.reload();
                },
                onFinish: () => {
                    setLoading(false);
                },
            },
        );
    };

    return (
        <div
            className="fixed inset-0 z-[60] flex items-center justify-center bg-black/70 p-4"
            onClick={onClose}
        >
            <div
                className="relative w-full max-w-md rounded-xl border-2 border-amber-600/50 bg-stone-800 p-6"
                onClick={(e) => e.stopPropagation()}
            >
                <button
                    onClick={onClose}
                    className="absolute right-3 top-3 text-stone-400 hover:text-white"
                >
                    <X className="h-5 w-5" />
                </button>

                <div className="mb-4 flex items-center gap-3">
                    <div className="rounded-lg bg-amber-900/50 p-2">
                        <Scroll className="h-5 w-5 text-amber-400" />
                    </div>
                    <div>
                        <h2 className="font-pixel text-lg text-amber-300">Challenge Role</h2>
                        <p className="font-pixel text-[10px] text-stone-400">
                            Petition to remove {role.holder?.username} as {role.name}
                        </p>
                    </div>
                </div>

                <p className="mb-4 font-pixel text-xs text-stone-400">
                    Your petition will be reviewed by the local authority figure. If approved, the
                    current holder will be removed from their position.
                </p>

                <div className="mb-4">
                    <label className="mb-1 block font-pixel text-xs text-stone-300">
                        Reason for challenge
                    </label>
                    <textarea
                        value={reason}
                        onChange={(e) => setReason(e.target.value)}
                        maxLength={1000}
                        rows={4}
                        className="w-full rounded-lg border border-stone-600 bg-stone-900/80 px-3 py-2 font-pixel text-xs text-stone-200 placeholder-stone-500 focus:border-amber-500 focus:outline-none"
                        placeholder="Explain why this role holder should be removed..."
                    />
                    <div className="mt-1 text-right font-pixel text-[10px] text-stone-500">
                        {reason.length}/1000
                    </div>
                </div>

                <label className="mb-4 flex cursor-pointer items-center gap-2">
                    <input
                        type="checkbox"
                        checked={requestAppointment}
                        onChange={(e) => setRequestAppointment(e.target.checked)}
                        className="h-4 w-4 rounded border-stone-600 bg-stone-900 text-amber-500 focus:ring-amber-500"
                    />
                    <span className="font-pixel text-xs text-stone-300">
                        I want to be appointed to this role if approved
                    </span>
                </label>

                <div className="flex gap-2">
                    <button
                        onClick={onClose}
                        className="flex-1 rounded-lg border border-stone-600 bg-stone-700/50 px-4 py-2 font-pixel text-xs text-stone-300 hover:bg-stone-700"
                    >
                        Cancel
                    </button>
                    <button
                        onClick={handleSubmit}
                        disabled={loading || !reason.trim()}
                        className="flex flex-1 items-center justify-center gap-2 rounded-lg border border-amber-600/50 bg-amber-900/30 px-4 py-2 font-pixel text-xs text-amber-300 hover:bg-amber-800/40 disabled:opacity-50"
                    >
                        {loading ? (
                            <Loader2 className="h-4 w-4 animate-spin" />
                        ) : (
                            <>
                                <Scroll className="h-4 w-4" />
                                File Petition
                            </>
                        )}
                    </button>
                </div>
            </div>
        </div>
    );
}

function RoleModal({
    role,
    currentUserId,
    userRoleHere,
    currentUserRole,
    canSelfAppoint,
    userResidesHere,
    userIsHere,
    locationType,
    locationId,
    onClose,
    onResign,
    onClaim,
    onChallenge,
    resignLoading,
    claimLoading,
    kingClaimable,
    kingUnlockDate,
}: {
    role: Role;
    currentUserId: number;
    userRoleHere: UserRole | undefined;
    currentUserRole: UserRole | undefined;
    canSelfAppoint: boolean;
    userResidesHere: boolean;
    userIsHere: boolean;
    locationType: string;
    locationId: number;
    onClose: () => void;
    onResign: (playerRoleId: number) => void;
    onClaim: (roleId: number) => void;
    onChallenge: () => void;
    resignLoading: number | null;
    claimLoading: number | null;
    kingClaimable: boolean;
    kingUnlockDate: string | null;
}) {
    const Icon = iconMap[role.icon.toLowerCase()] || Crown;
    const isCurrentUser = role.holder?.user_id === currentUserId;
    const isUserRole = userRoleHere?.role_id === role.id;
    // King has special time-locked claiming
    const isKingLocked = role.slug === "king" && !kingClaimable;
    // Can claim if: vacant, self-appointment allowed, user resides here, user is physically here, not their current role, and not time-locked
    const canClaim =
        role.is_vacant &&
        canSelfAppoint &&
        userResidesHere &&
        userIsHere &&
        !isUserRole &&
        !isKingLocked;
    const willReplaceRole = canClaim && currentUserRole !== undefined;
    // Can challenge if: has a holder, user is at location, user is a resident, not the holder, and not an elected role
    const canChallenge =
        role.holder && !isCurrentUser && userIsHere && userResidesHere && !role.is_elected;
    // Can start no-confidence vote if: elected role, has a holder, not the holder, user resides here
    const canNoConfidence = role.is_elected && role.holder && !isCurrentUser && userResidesHere;
    const locationLabel = locationType.charAt(0).toUpperCase() + locationType.slice(1);
    const consequences = roleConsequences[role.slug] || {
        duties: [],
        ifVacant: `${locationLabel} functions reduced.`,
    };

    return (
        <div
            className="fixed inset-0 z-50 flex items-center justify-center bg-black/70 p-4"
            onClick={onClose}
        >
            <div
                className={`relative max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-xl border-2 ${tierModalColors[role.tier]} p-6`}
                onClick={(e) => e.stopPropagation()}
            >
                {/* Close button */}
                <button
                    onClick={onClose}
                    className="absolute right-3 top-3 text-stone-400 hover:text-white"
                >
                    <X className="h-5 w-5" />
                </button>

                {/* Header */}
                <div className="mb-4 flex items-center gap-3">
                    <div className={`rounded-lg p-3 ${tierBadgeColors[role.tier]}`}>
                        <Icon className="h-6 w-6" />
                    </div>
                    <div>
                        <h2 className="font-pixel text-lg text-amber-300">{role.name}</h2>
                        <div className="flex items-center gap-2">
                            <span
                                className={`rounded px-1.5 py-0.5 font-pixel text-[10px] ${tierBadgeColors[role.tier]}`}
                            >
                                Tier {role.tier}
                            </span>
                            {role.is_elected && (
                                <span className="font-pixel text-[10px] text-purple-400">
                                    Elected Position
                                </span>
                            )}
                            <span
                                className={`font-pixel text-[10px] ${role.is_vacant ? "text-red-400" : "text-green-400"}`}
                            >
                                {role.is_vacant ? "VACANT" : "Filled"}
                            </span>
                        </div>
                    </div>
                </div>

                <p className="mb-4 text-sm text-stone-300">{role.description}</p>

                {/* Salary & Bonuses */}
                <div className="mb-4 rounded-lg bg-stone-900/50 p-3">
                    <div className="mb-2 flex items-center gap-2">
                        <Coins className="h-4 w-4 text-amber-400" />
                        <span className="font-pixel text-sm text-amber-300">
                            {role.salary}g / day
                        </span>
                    </div>
                    {Object.keys(role.bonuses).length > 0 && (
                        <div className="flex flex-wrap gap-2">
                            {Object.entries(role.bonuses).map(([key, value]) => (
                                <span
                                    key={key}
                                    className="rounded bg-green-900/50 px-2 py-0.5 font-pixel text-[10px] text-green-300"
                                >
                                    <Sparkles className="mr-1 inline h-3 w-3" />
                                    {formatBonus(key, value)}
                                </span>
                            ))}
                        </div>
                    )}
                </div>

                {/* Duties */}
                <div className="mb-4">
                    <h3 className="mb-2 font-pixel text-xs text-stone-400">YOUR DUTIES:</h3>
                    <ul className="space-y-1">
                        {consequences.duties.map((duty, i) => (
                            <li
                                key={i}
                                className="flex items-center gap-2 font-pixel text-xs text-stone-300"
                            >
                                <Check className="h-3 w-3 text-green-400" />
                                {duty}
                            </li>
                        ))}
                    </ul>
                </div>

                {/* Warning if vacant */}
                {role.is_vacant && (
                    <div className="mb-4 rounded-lg border border-red-600/50 bg-red-900/20 p-3">
                        <div className="flex items-start gap-2">
                            <AlertTriangle className="h-4 w-4 flex-shrink-0 text-red-400" />
                            <div>
                                <span className="font-pixel text-xs text-red-300">
                                    Without a {role.name}:
                                </span>
                                <p className="font-pixel text-[10px] text-red-400/80">
                                    {consequences.ifVacant}
                                </p>
                            </div>
                        </div>
                    </div>
                )}

                {/* Current Holder */}
                {role.holder && (
                    <div className="mb-4 rounded-lg bg-stone-900/50 p-3">
                        <div className="flex items-center justify-between">
                            <div className="flex items-center gap-2">
                                <User className="h-4 w-4 text-stone-400" />
                                <span
                                    className={`font-pixel text-sm ${isCurrentUser ? "text-green-300" : "text-stone-200"}`}
                                >
                                    {role.holder.username}
                                    {isCurrentUser && " (You)"}
                                </span>
                            </div>
                            <div className="flex items-center gap-1 text-stone-500">
                                <Clock className="h-3 w-3" />
                                <span className="font-pixel text-[10px]">
                                    Since {formatDate(role.holder.appointed_at)}
                                </span>
                            </div>
                        </div>
                    </div>
                )}

                {/* Warning about replacing role */}
                {willReplaceRole && (
                    <div className="mb-4 rounded-lg border border-amber-600/50 bg-amber-900/20 p-3">
                        <div className="flex items-start gap-2">
                            <AlertTriangle className="h-4 w-4 flex-shrink-0 text-amber-400" />
                            <div>
                                <span className="font-pixel text-xs text-amber-300">
                                    You will lose your current job
                                </span>
                                <p className="font-pixel text-[10px] text-amber-400/80">
                                    Claiming this role will resign you from {currentUserRole.name}{" "}
                                    at {currentUserRole.location_name}.
                                </p>
                            </div>
                        </div>
                    </div>
                )}

                {/* Actions */}
                <div className="flex gap-2">
                    {isUserRole && (
                        <button
                            onClick={() => onResign(userRoleHere!.id)}
                            disabled={resignLoading === userRoleHere!.id}
                            className="flex flex-1 items-center justify-center gap-2 rounded-lg border border-red-600/50 bg-red-900/30 px-4 py-2 font-pixel text-xs text-red-300 hover:bg-red-800/40"
                        >
                            {resignLoading === userRoleHere!.id ? (
                                <Loader2 className="h-4 w-4 animate-spin" />
                            ) : (
                                <>
                                    <LogOut className="h-4 w-4" />
                                    Resign
                                </>
                            )}
                        </button>
                    )}
                    {canClaim && (
                        <button
                            onClick={() => onClaim(role.id)}
                            disabled={claimLoading === role.id}
                            className={`flex flex-1 items-center justify-center gap-2 rounded-lg border px-4 py-2 font-pixel text-xs ${
                                willReplaceRole
                                    ? "border-amber-600/50 bg-amber-900/30 text-amber-300 hover:bg-amber-800/40"
                                    : "border-green-600/50 bg-green-900/30 text-green-300 hover:bg-green-800/40"
                            }`}
                        >
                            {claimLoading === role.id ? (
                                <Loader2 className="h-4 w-4 animate-spin" />
                            ) : (
                                <>
                                    <Crown className="h-4 w-4" />
                                    {willReplaceRole ? "Switch to This Role" : "Claim This Role"}
                                </>
                            )}
                        </button>
                    )}
                    {isKingLocked && role.is_vacant && userIsHere && userResidesHere && (
                        <div className="flex flex-1 items-center justify-center gap-2 rounded-lg border border-purple-600/50 bg-purple-900/30 px-4 py-2 font-pixel text-xs text-purple-300">
                            <Clock className="h-4 w-4" />
                            Coming Soon - {kingUnlockDate}
                        </div>
                    )}
                    {!canClaim && !isUserRole && role.is_vacant && !isKingLocked && (
                        <div className="flex-1 rounded-lg border border-dashed border-stone-600 bg-stone-900/30 p-2 text-center">
                            <span className="font-pixel text-[10px] text-stone-500">
                                {!userIsHere
                                    ? "You must travel here to claim this role"
                                    : !userResidesHere
                                      ? "You must reside here to claim this role"
                                      : canSelfAppoint
                                        ? "Cannot claim this role"
                                        : "Election required to fill this position"}
                            </span>
                        </div>
                    )}
                    {canChallenge && (
                        <button
                            onClick={onChallenge}
                            className="flex flex-1 items-center justify-center gap-2 rounded-lg border border-amber-600/50 bg-amber-900/30 px-4 py-2 font-pixel text-xs text-amber-300 hover:bg-amber-800/40"
                        >
                            <Scroll className="h-4 w-4" />
                            Challenge
                        </button>
                    )}
                    {canNoConfidence && (
                        <button
                            onClick={() => {
                                router.post(
                                    `/${locationType}s/${locationId}/no-confidence`,
                                    { role: role.slug },
                                    {
                                        preserveScroll: true,
                                        onSuccess: () => {
                                            router.reload();
                                            onClose();
                                        },
                                    },
                                );
                            }}
                            className="flex flex-1 items-center justify-center gap-2 rounded-lg border border-red-600/50 bg-red-900/30 px-4 py-2 font-pixel text-xs text-red-300 hover:bg-red-800/40"
                        >
                            <Flag className="h-4 w-4" />
                            Start No-Confidence Vote
                        </button>
                    )}
                </div>
            </div>
        </div>
    );
}

export default function RolesIndex() {
    const {
        location_type,
        location_id,
        location_name,
        roles,
        user_roles,
        user_roles_here,
        population,
        can_self_appoint,
        user_resides_here,
        user_is_here,
        self_appoint_threshold,
        king_claimable,
        king_unlock_date,
        player,
    } = usePage<PageProps>().props;

    const [selectedRole, setSelectedRole] = useState<Role | null>(null);
    const [challengeRole, setChallengeRole] = useState<Role | null>(null);
    const [resignLoading, setResignLoading] = useState<number | null>(null);
    const [claimLoading, setClaimLoading] = useState<number | null>(null);

    const locationTypeDisplay = location_type.charAt(0).toUpperCase() + location_type.slice(1);
    // User's current role (anywhere in the game - they can only have one)
    const currentUserRole = user_roles.length > 0 ? user_roles[0] : undefined;

    const breadcrumbs: BreadcrumbItem[] = [
        { title: "Dashboard", href: "/dashboard" },
        { title: location_name, href: locationPath(location_type, location_id) },
        { title: "Roles", href: "#" },
    ];

    const handleResign = (playerRoleId: number) => {
        setResignLoading(playerRoleId);
        router.post(
            `/roles/${playerRoleId}/resign`,
            {},
            {
                preserveScroll: true,
                onSuccess: () => {
                    setSelectedRole(null);
                    router.reload();
                },
                onFinish: () => {
                    setResignLoading(null);
                },
            },
        );
    };

    const handleClaim = (roleId: number) => {
        setClaimLoading(roleId);
        router.post(
            "/roles/claim",
            { role_id: roleId, location_type, location_id },
            {
                preserveScroll: true,
                onSuccess: () => {
                    setSelectedRole(null);
                    router.reload();
                },
                onFinish: () => {
                    setClaimLoading(null);
                },
            },
        );
    };

    // Group roles by tier
    const rolesByTier = roles.reduce(
        (acc, role) => {
            if (!acc[role.tier]) acc[role.tier] = [];
            acc[role.tier].push(role);
            return acc;
        },
        {} as Record<number, Role[]>,
    );

    const sortedTiers = Object.keys(rolesByTier)
        .map(Number)
        .sort((a, b) => b - a);
    const tierLabels: Record<number, string> = {
        5: "Rulers",
        4: "Leadership",
        3: "Senior",
        2: "Skilled",
        1: "Workers",
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Roles - ${location_name}`} />
            <div className="flex h-full flex-1 flex-col overflow-hidden p-4">
                {/* Header */}
                <div className="mb-4 flex items-center justify-between">
                    <div>
                        <h1 className="font-pixel text-xl text-amber-400">
                            {locationTypeDisplay} Roles
                        </h1>
                        <p className="font-pixel text-xs text-stone-400">
                            {location_name} • Pop: {population} •{" "}
                            {can_self_appoint
                                ? "Claim vacant roles"
                                : `Election required (${self_appoint_threshold}+)`}
                        </p>
                    </div>
                    {currentUserRole && (
                        <div className="rounded border border-green-600/50 bg-green-900/20 px-3 py-1">
                            <span className="font-pixel text-xs text-green-300">
                                Your job: {currentUserRole.name}
                                {currentUserRole.location_type !== location_type ||
                                currentUserRole.location_id !== location_id
                                    ? ` (${currentUserRole.location_name})`
                                    : " (here)"}
                            </span>
                        </div>
                    )}
                </div>

                {/* Roles Grid by Tier */}
                <div className="-mx-1 space-y-4 overflow-y-auto px-1">
                    {sortedTiers.map((tier) => (
                        <div key={tier}>
                            <h2 className="mb-2 font-pixel text-sm text-stone-400">
                                {tierLabels[tier] || `Tier ${tier}`}
                            </h2>
                            <div className="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                                {rolesByTier[tier].map((role) => {
                                    const Icon = iconMap[role.icon.toLowerCase()] || Crown;
                                    const isUserRole = user_roles_here.some(
                                        (ur) => ur.role_id === role.id,
                                    );

                                    return (
                                        <button
                                            key={role.id}
                                            onClick={() => setSelectedRole(role)}
                                            className={`flex items-center gap-3 rounded-lg border p-3 text-left transition ${tierColors[role.tier]} ${isUserRole ? "ring-2 ring-green-500" : ""}`}
                                        >
                                            <div className="flex-shrink-0">
                                                <Icon className="h-8 w-8 text-amber-300" />
                                            </div>
                                            <div className="min-w-0 flex-1">
                                                <div className="flex items-center gap-2">
                                                    <span className="font-pixel text-sm text-amber-300 truncate">
                                                        {role.name}
                                                    </span>
                                                    <span
                                                        className={`h-2 w-2 flex-shrink-0 rounded-full ${role.is_vacant ? "bg-red-500" : "bg-green-500"}`}
                                                    />
                                                </div>
                                                {role.holder ? (
                                                    <div className="font-pixel text-[10px] text-stone-400 truncate">
                                                        {role.holder.username}
                                                        <span className="text-stone-500">
                                                            {" "}
                                                            · Lv.{role.holder.total_level} ·{" "}
                                                            {role.salary}g/day
                                                        </span>
                                                    </div>
                                                ) : (
                                                    <div className="font-pixel text-[10px] text-stone-500">
                                                        Vacant · {role.salary}g/day
                                                    </div>
                                                )}
                                            </div>
                                        </button>
                                    );
                                })}
                            </div>
                        </div>
                    ))}
                </div>

                {roles.length === 0 && (
                    <div className="flex flex-1 items-center justify-center">
                        <div className="text-center">
                            <Crown className="mx-auto mb-2 h-12 w-12 text-stone-600" />
                            <p className="font-pixel text-sm text-stone-500">No roles available</p>
                        </div>
                    </div>
                )}
            </div>

            {/* Role Detail Modal */}
            {selectedRole && (
                <RoleModal
                    role={selectedRole}
                    currentUserId={player.id}
                    userRoleHere={user_roles_here.find((ur) => ur.role_id === selectedRole.id)}
                    currentUserRole={currentUserRole}
                    canSelfAppoint={can_self_appoint}
                    userResidesHere={user_resides_here}
                    userIsHere={user_is_here}
                    locationType={location_type}
                    locationId={location_id}
                    onClose={() => setSelectedRole(null)}
                    onResign={handleResign}
                    onClaim={handleClaim}
                    onChallenge={() => {
                        setChallengeRole(selectedRole);
                        setSelectedRole(null);
                    }}
                    resignLoading={resignLoading}
                    claimLoading={claimLoading}
                    kingClaimable={king_claimable}
                    kingUnlockDate={king_unlock_date}
                />
            )}

            {/* Challenge Petition Modal */}
            {challengeRole && (
                <ChallengeModal role={challengeRole} onClose={() => setChallengeRole(null)} />
            )}
        </AppLayout>
    );
}
