import {
    Anvil,
    Bird,
    Church,
    Dices,
    FlaskConical,
    Footprints,
    Gift,
    Hammer,
    Link2,
    Mail,
    MessageCircle,
    Repeat,
    Scroll,
    Shield,
    Sparkles,
    Sprout,
    Star,
    Swords,
    Trophy,
    Users,
    Wheat,
    Wrench,
    X,
} from "lucide-react";
import { Button } from "@/components/ui/button";

// Update this when adding new changelog entries
export const CURRENT_CHANGELOG_VERSION = "0.9.0";

interface ChangelogEntry {
    version: string;
    date: string;
    title: string;
    description: string;
    changes: {
        type: "added" | "changed" | "fixed" | "removed";
        text: string;
    }[];
    icon: React.ReactNode;
    discordLink?: string;
}

const changelog: ChangelogEntry[] = [
    {
        version: "0.9.0",
        date: "February 14, 2026",
        title: "Carrier Pigeons & Role Petitions",
        description:
            "Send mail to any player in the realm via carrier pigeon for 5g. Plus, if you think a role holder isn't doing their job, you can now challenge them for their position.",
        icon: <Bird className="h-8 w-8 text-amber-500" />,
        changes: [
            {
                type: "added",
                text: "Player mail system — send messages to any player for 5g via carrier pigeon",
            },
            {
                type: "added",
                text: "Inbox with unread badges, sent mail history, and inline reply",
            },
            {
                type: "added",
                text: "Mail icon in the sidebar with unread count",
            },
            {
                type: "added",
                text: "Challenge role holders — click any occupied role and file a petition to have them removed",
            },
            {
                type: "added",
                text: "Petitions go up the authority chain: village roles → Elder, Elder → Baron, Baron → King",
            },
            {
                type: "added",
                text: "If approved, the holder loses their role (and its salary and bonuses). You can request to be appointed in their place",
            },
            {
                type: "added",
                text: "Authority figures see a red badge on their role card and can review, approve, or deny petitions",
            },
        ],
    },
    {
        version: "0.8.5",
        date: "February 12, 2026",
        title: "Action Queues",
        description:
            "Tired of clicking one action at a time? Now you can queue up multiple actions and let them run automatically. Set a quantity or repeat until your energy or materials run out — your character handles the rest while you sit back.",
        icon: <Repeat className="h-8 w-8 text-cyan-500" />,
        changes: [
            {
                type: "added",
                text: "Queue actions for Crafting, Smelting, Gathering, Training, and Agility",
            },
            {
                type: "added",
                text: "Choose x5, x10, x25, or repeat until resources are depleted",
            },
            {
                type: "added",
                text: "Live progress bar and XP tracker while queue is running",
            },
            {
                type: "added",
                text: "Cancel anytime — you keep everything earned so far",
            },
            {
                type: "added",
                text: "Browser notifications when your queue finishes (if tab is in background)",
            },
            {
                type: "changed",
                text: "Sidebar energy bar now updates in real-time during queued actions",
            },
        ],
    },
    {
        version: "0.8.4",
        date: "February 12, 2026",
        title: "Construction Expansion",
        description:
            "Your house is about to become much more than four walls. Skilled builders can now unlock powerful new rooms — some that expand what you can store, others that may restore what the world has taken from you. Construction won't come cheap, but for those willing to invest, the rewards speak for themselves.",
        icon: <Hammer className="h-8 w-8 text-amber-500" />,
        changes: [
            {
                type: "added",
                text: "New rooms available for high-level constructors to discover and build",
            },
            {
                type: "added",
                text: "Certain rooms now offer functional benefits beyond decoration",
            },
            {
                type: "added",
                text: "Expanded home storage for those who find the right room",
            },
            {
                type: "added",
                text: "A hidden amenity for the most dedicated homeowners — worth seeking out",
            },
        ],
    },
    {
        version: "0.8.3",
        date: "February 5, 2026",
        title: "Fair Play Enforcement",
        description:
            "We've improved our systems to detect and prevent cheating. Play fair and enjoy the game as intended - using bots, scripts, or multiple tabs to gain an unfair advantage will result in a ban.",
        icon: <Shield className="h-8 w-8 text-red-500" />,
        changes: [
            {
                type: "added",
                text: "Improved detection for automated botting and scripting",
            },
            {
                type: "added",
                text: "Detection for multi-tab exploitation (playing multiple tabs simultaneously)",
            },
            {
                type: "added",
                text: "Banned players can now view their ban details and submit appeals",
            },
            {
                type: "changed",
                text: "Banned players are now excluded from leaderboards",
            },
        ],
    },
    {
        version: "0.8.2",
        date: "February 5, 2026",
        title: "Kingdom Dungeons",
        description:
            "Each kingdom now holds its own secrets underground. Explore unique dungeons tied to the land you call home.",
        icon: <Swords className="h-8 w-8 text-violet-500" />,
        changes: [
            {
                type: "added",
                text: "Dungeons now vary by kingdom - discover what lurks in each realm",
            },
            {
                type: "added",
                text: "Loot storage lets you collect your spoils when the time is right",
            },
            {
                type: "added",
                text: "Victory screen celebrates your dungeon conquests",
            },
        ],
    },
    {
        version: "0.8.1",
        date: "February 4, 2026",
        title: "Sacred Sanctuaries",
        description:
            "Religions and cults can now establish permanent sanctuaries in the world. Build your faith's headquarters or hidden hideout and unlock powerful new abilities for your followers.",
        icon: <Church className="h-8 w-8 text-amber-500" />,
        changes: [
            {
                type: "added",
                text: "Religions can now build Headquarters - from humble Chapels to grand Cathedrals",
            },
            {
                type: "added",
                text: "Cults can establish secret Hideouts - hidden sanctuaries for forbidden practices",
            },
            {
                type: "added",
                text: "Upgrade your sanctuary through multiple tiers to unlock new powers",
            },
            {
                type: "added",
                text: "Expanded rank system for both religions and cults",
            },
            {
                type: "added",
                text: "Members can contribute gold and devotion to upgrade projects",
            },
        ],
    },
    {
        version: "0.8.0",
        date: "February 2, 2026",
        title: "Tavern Games & Archery",
        description:
            "Test your luck at the tavern with three new dice games, or prove your aim at the archery range! Win gold, earn energy, and compete for the best scores.",
        icon: <Dices className="h-8 w-8 text-purple-500" />,
        changes: [
            {
                type: "added",
                text: "Tavern Dice Games - three games to wager gold: High Roll, Hazard, and Doubles",
            },
            {
                type: "added",
                text: "High Roll: Both you and the house roll 2d6, highest sum wins (1.35x payout)",
            },
            {
                type: "added",
                text: "Hazard: Classic dice game - roll 7/11 to win instantly, avoid craps! (1.6x payout)",
            },
            {
                type: "added",
                text: "Doubles: Roll matching dice to win big (2.7x payout)",
            },
            {
                type: "added",
                text: "Wager 10g to 2500g per game with a 5-minute cooldown",
            },
            {
                type: "added",
                text: "Earn energy from playing: +10 for wins, +3 for losses",
            },
            {
                type: "added",
                text: "Track your win/loss record at each tavern",
            },
            {
                type: "added",
                text: "Archery Competition - test your ranged skill at the archery range",
            },
            {
                type: "added",
                text: "Score points based on accuracy: Bullseye (10), Inner (7), Middle (4), Outer (1)",
            },
            {
                type: "added",
                text: "Skill-based shooting - higher ranged level improves accuracy",
            },
            {
                type: "added",
                text: "Earn ranged XP and gold rewards based on your final score",
            },
        ],
    },
    {
        version: "0.7.1",
        date: "February 1, 2026",
        title: "Join us on Discord!",
        description:
            "We now have an official Discord server! Join the community to chat with other players, report bugs, suggest features, and stay up to date with the latest news.",
        icon: <MessageCircle className="h-8 w-8 text-indigo-500" />,
        changes: [
            {
                type: "added",
                text: "Official Myrefell Discord server launched",
            },
            {
                type: "added",
                text: "Chat with other players and the development team",
            },
            {
                type: "added",
                text: "Report bugs and suggest new features",
            },
            {
                type: "added",
                text: "Get announcements about updates and events",
            },
        ],
        discordLink: "https://discord.gg/PSYAGmFjCh",
    },
    {
        version: "0.7.0",
        date: "February 1, 2026",
        title: "Daily Wheel",
        description:
            "Spin the daily wheel once per day for a chance to win gold and rare items! Build up your streak for better rewards.",
        icon: <Gift className="h-8 w-8 text-purple-500" />,
        changes: [
            {
                type: "added",
                text: "New Daily Wheel minigame - spin once per day for free rewards",
            },
            {
                type: "added",
                text: "Streak system - consecutive daily spins increase your reward chances",
            },
            {
                type: "added",
                text: "4 reward tiers: Common, Uncommon, Rare, and Epic",
            },
            {
                type: "added",
                text: "Common rewards: 50-150 gold",
            },
            {
                type: "added",
                text: "Uncommon rewards: 150-300 gold",
            },
            {
                type: "added",
                text: "Rare rewards: 300-500 gold or a rare tradeable item",
            },
            {
                type: "added",
                text: "Epic rewards: 500-1000 gold AND an epic tradeable item",
            },
            {
                type: "added",
                text: "Rewards history to track your past spins",
            },
        ],
    },
    {
        version: "0.6.0",
        date: "February 1, 2026",
        title: "Agility Training",
        description:
            "A brand new skill arrives! Train your agility on obstacle courses at villages, towns, baronies, and duchies. Higher level obstacles unlock more challenging courses with better XP!",
        icon: <Footprints className="h-8 w-8 text-emerald-500" />,
        changes: [
            {
                type: "added",
                text: "New Agility skill with 35 unique obstacles to master",
            },
            {
                type: "added",
                text: "Obstacles unlock as your agility level increases (levels 1-90)",
            },
            {
                type: "added",
                text: "Success-based training - higher levels improve your success rate",
            },
            {
                type: "added",
                text: "Failed attempts still award 25% XP to keep progressing",
            },
            {
                type: "added",
                text: "Advanced obstacles (40+) only available at baronies and duchies",
            },
            {
                type: "added",
                text: "Legendary course at level 90 - exclusive to duchies",
            },
        ],
    },
    {
        version: "0.5.0",
        date: "February 1, 2026",
        title: "Highscores & Favorites",
        description:
            "Compare your skills with other players on the new highscores leaderboard, and quickly access your favorite services from the sidebar!",
        icon: <Trophy className="h-8 w-8 text-amber-500" />,
        changes: [
            {
                type: "added",
                text: "Highscores leaderboard - see top players for each skill",
            },
            {
                type: "added",
                text: "Filter leaderboards by skill type (combat, gathering, production)",
            },
            {
                type: "added",
                text: "Service favorites - star any service to add it to your quick-access bar",
            },
            {
                type: "added",
                text: "Favorites appear in the sidebar for one-click access",
            },
            {
                type: "added",
                text: "Active service highlighted with amber border in favorites bar",
            },
        ],
    },
    {
        version: "0.4.0",
        date: "February 1, 2026",
        title: "Farming Expansion",
        description:
            "The farming system has been massively expanded! Grow 69 different crops from basic wheat to legendary Celestial Carrots, and donate your harvest to feed your village.",
        icon: <Wheat className="h-8 w-8 text-amber-500" />,
        changes: [
            {
                type: "added",
                text: "69 crops to grow across all farming levels (1-99)",
            },
            {
                type: "added",
                text: "Beginner crops: Wheat, Potatoes, Lettuce, Turnips, Radishes, and more",
            },
            {
                type: "added",
                text: "Intermediate crops: Tomatoes, Peppers, Beans, Broccoli, and more",
            },
            {
                type: "added",
                text: "Advanced crops: Grapes, Hops, Artichoke, and orchard fruits",
            },
            {
                type: "added",
                text: "Exotic crops (65+): Dragon Fruit, Starfruit, Moonberry, Coffee, Cocoa",
            },
            {
                type: "added",
                text: "Legendary crops (85+): Golden Wheat, Crystal Grapes, Void Pepper, Celestial Carrot",
            },
            {
                type: "added",
                text: "Donate crops to your village granary to feed the population",
            },
            {
                type: "added",
                text: "View village food statistics on the farming page",
            },
            {
                type: "added",
                text: "Harvested crops are now actual inventory items you can eat or sell",
            },
        ],
    },
    {
        version: "0.3.0",
        date: "January 31, 2026",
        title: "Thieving & Herblore",
        description:
            "Two new skills arrive in Myrefell! Master the art of thievery or become a skilled alchemist brewing powerful potions.",
        icon: <FlaskConical className="h-8 w-8 text-emerald-500" />,
        changes: [
            {
                type: "added",
                text: "New Thieving skill - pickpocket NPCs and steal from stalls",
            },
            {
                type: "added",
                text: "New Herblore skill - gather herbs and brew potions at the Apothecary",
            },
            {
                type: "added",
                text: "14 gatherable herbs from Herbalism across all skill levels",
            },
            {
                type: "added",
                text: "20+ potion recipes including combat, restoration, and spiritual potions",
            },
            {
                type: "added",
                text: "Monster drops for rare apothecary ingredients (Venom Sac, Phoenix Feather, etc.)",
            },
            {
                type: "added",
                text: "Apothecary shops now stock vials and basic supplies",
            },
            {
                type: "added",
                text: "Combat potions: Attack, Strength, Defense, Accuracy, and Agility buffs",
            },
            {
                type: "added",
                text: "Super potions and Overload potion for high-level alchemists",
            },
        ],
    },
    {
        version: "0.2.0",
        date: "January 31, 2026",
        title: "Smithing & Crafting Expansion",
        description:
            "A massive expansion to the smithing system with 6 metal tiers and over 130 new craftable items!",
        icon: <Anvil className="h-8 w-8 text-amber-500" />,
        changes: [
            {
                type: "added",
                text: "6 metal tiers: Bronze, Iron, Steel, Mithril, Celestial, and Oria",
            },
            {
                type: "added",
                text: "23 item types per metal tier (138 new items total)",
            },
            {
                type: "added",
                text: "New weapons: Daggers, Axes, Maces, Swords, Scimitars, Spears, Longswords, Warhammers, Battleaxes, Claws, and 2h Swords",
            },
            {
                type: "added",
                text: "New armor: Medium Helms, Full Helms, Square Shields, Chainbodies, Kiteshields, Platelegs, Plateskirts, and Platebodies",
            },
            {
                type: "added",
                text: "New ammunition crafting: Dart Tips, Arrowtips, Javelin Tips, and Throwing Knives",
            },
            {
                type: "added",
                text: "New ores: Celestial Ore and Oria Ore",
            },
            {
                type: "added",
                text: "New bars: Mithril Bar, Celestial Bar, and Oria Bar",
            },
            {
                type: "changed",
                text: "Stats now scale based on metal tier multipliers (up to 5x for Oria)",
            },
        ],
    },
    {
        version: "0.1.2",
        date: "January 30, 2026",
        title: "Referral System",
        description: "Invite your friends to Myrefell and earn rewards when they join the realm!",
        icon: <Link2 className="h-8 w-8 text-emerald-500" />,
        changes: [
            {
                type: "added",
                text: "New referral system with unique invite links",
            },
            {
                type: "added",
                text: "Referral dashboard to track your invites and rewards",
            },
            {
                type: "added",
                text: "Bonus rewards for both referrer and new players",
            },
            {
                type: "fixed",
                text: "Fixed referral links not tracking new user signups correctly",
            },
        ],
    },
    {
        version: "0.1.1",
        date: "January 29, 2026",
        title: "Roles & Jobs Fixes",
        description: "Various bug fixes and improvements to the roles and employment systems.",
        icon: <Wrench className="h-8 w-8 text-blue-500" />,
        changes: [
            {
                type: "fixed",
                text: "Fixed URL routing issues causing 404 errors on certain pages",
            },
            {
                type: "fixed",
                text: "Fixed role assignment not updating correctly when changing positions",
            },
            {
                type: "fixed",
                text: "Fixed job applications not being processed properly",
            },
            {
                type: "fixed",
                text: "Fixed job wages not being paid out at the correct intervals",
            },
            {
                type: "fixed",
                text: "Fixed role permissions not applying immediately after assignment",
            },
        ],
    },
];

interface Props {
    onClose: () => void;
}

export default function ChangelogModal({ onClose }: Props) {
    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
            {/* Backdrop */}
            <div className="absolute inset-0 bg-background/80 backdrop-blur-sm" onClick={onClose} />

            {/* Modal */}
            <div className="relative w-full max-w-2xl max-h-[85vh] overflow-hidden">
                {/* Corner decorations */}
                <div className="absolute -top-2 -left-2 w-6 h-6 border-t-2 border-l-2 border-primary/60" />
                <div className="absolute -top-2 -right-2 w-6 h-6 border-t-2 border-r-2 border-primary/60" />
                <div className="absolute -bottom-2 -left-2 w-6 h-6 border-b-2 border-l-2 border-primary/60" />
                <div className="absolute -bottom-2 -right-2 w-6 h-6 border-b-2 border-r-2 border-primary/60" />

                <div className="relative bg-card border border-border/50 shadow-lg shadow-primary/5 flex flex-col max-h-[85vh]">
                    {/* Close button */}
                    <button
                        onClick={onClose}
                        className="absolute right-3 top-3 z-10 rounded-sm opacity-70 ring-offset-background transition-opacity hover:opacity-100 focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2"
                    >
                        <X className="h-4 w-4" />
                        <span className="sr-only">Close</span>
                    </button>

                    {/* Header */}
                    <div className="border-b border-border/50 px-6 py-4 shrink-0">
                        <div className="flex items-center gap-3 pr-6">
                            <Sparkles className="h-6 w-6 text-primary" />
                            <h2 className="font-[Cinzel] text-xl font-bold text-foreground">
                                What's New in Myrefell
                            </h2>
                        </div>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Latest updates and improvements to the realm
                        </p>
                    </div>

                    {/* Content - Scrollable */}
                    <div className="overflow-y-auto flex-1 px-6 py-4">
                        <div className="space-y-6">
                            {changelog.map((entry, index) => (
                                <div
                                    key={index}
                                    className="rounded-lg border border-border/50 bg-card/50 overflow-hidden"
                                >
                                    {/* Entry Header */}
                                    <div className="relative bg-muted/30 px-4 py-3 border-b border-border/30">
                                        {/* Date in top right - desktop only */}
                                        <span className="absolute top-2 right-3 hidden text-xs text-muted-foreground sm:block">
                                            {entry.date.replace(/(\w+)\s+(\d+),\s+\d+/, "$1 $2")}
                                        </span>
                                        {/* Icon in top right - mobile only */}
                                        <div className="absolute top-2 right-3 rounded-lg bg-primary/10 p-1.5 sm:hidden">
                                            {entry.icon}
                                        </div>
                                        <div className="flex items-start gap-3 pr-12 sm:pr-16">
                                            {/* Icon on left - desktop only */}
                                            <div className="hidden rounded-lg bg-primary/10 p-2 shrink-0 sm:block">
                                                {entry.icon}
                                            </div>
                                            <div className="flex-1 min-w-0">
                                                <div className="flex items-center gap-2 flex-wrap">
                                                    <h3 className="font-semibold text-foreground">
                                                        {entry.title}
                                                    </h3>
                                                    <span className="text-xs font-medium px-2 py-0.5 rounded-full bg-primary/20 text-primary">
                                                        v{entry.version}
                                                    </span>
                                                    {/* Date inline - mobile only */}
                                                    <span className="text-xs text-muted-foreground sm:hidden">
                                                        ·{" "}
                                                        {entry.date.replace(
                                                            /(\w+)\s+(\d+),\s+\d+/,
                                                            "$1 $2",
                                                        )}
                                                    </span>
                                                </div>
                                                <p className="text-sm text-muted-foreground mt-2">
                                                    {entry.description}
                                                </p>
                                            </div>
                                        </div>
                                    </div>

                                    {/* Changes List */}
                                    <div className="px-4 py-3">
                                        <ul className="space-y-2">
                                            {entry.changes.map((change, changeIndex) => (
                                                <li
                                                    key={changeIndex}
                                                    className="flex items-start gap-2 text-sm"
                                                >
                                                    <span
                                                        className={`shrink-0 text-xs font-medium px-1.5 py-0.5 rounded mt-0.5 ${
                                                            change.type === "added"
                                                                ? "bg-emerald-500/20 text-emerald-500"
                                                                : change.type === "changed"
                                                                  ? "bg-blue-500/20 text-blue-500"
                                                                  : change.type === "fixed"
                                                                    ? "bg-amber-500/20 text-amber-500"
                                                                    : "bg-red-500/20 text-red-500"
                                                        }`}
                                                    >
                                                        {change.type === "added" && "+"}
                                                        {change.type === "changed" && "~"}
                                                        {change.type === "fixed" && "!"}
                                                        {change.type === "removed" && "-"}
                                                    </span>
                                                    <span className="text-muted-foreground">
                                                        {change.text}
                                                    </span>
                                                </li>
                                            ))}
                                        </ul>
                                    </div>

                                    {/* Discord Link */}
                                    {entry.discordLink && (
                                        <div className="px-4 py-3 border-t border-border/30 bg-indigo-500/10">
                                            <a
                                                href={entry.discordLink}
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                className="flex items-center justify-center gap-2 w-full rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white font-medium py-2.5 px-4 transition-colors"
                                            >
                                                <MessageCircle className="h-5 w-5" />
                                                Join the Discord Server
                                            </a>
                                        </div>
                                    )}

                                    {/* Quick Stats for Smithing Update */}
                                    {entry.version === "0.2.0" && (
                                        <div className="px-4 py-3 border-t border-border/30 bg-muted/20">
                                            <p className="text-xs font-medium text-muted-foreground mb-2">
                                                Metal Tiers Overview
                                            </p>
                                            <div className="grid grid-cols-3 sm:grid-cols-6 gap-2">
                                                {[
                                                    {
                                                        name: "Bronze",
                                                        level: 1,
                                                        color: "text-orange-400",
                                                    },
                                                    {
                                                        name: "Iron",
                                                        level: 15,
                                                        color: "text-gray-400",
                                                    },
                                                    {
                                                        name: "Steel",
                                                        level: 30,
                                                        color: "text-slate-300",
                                                    },
                                                    {
                                                        name: "Mithril",
                                                        level: 45,
                                                        color: "text-blue-400",
                                                    },
                                                    {
                                                        name: "Celestial",
                                                        level: 60,
                                                        color: "text-purple-400",
                                                    },
                                                    {
                                                        name: "Oria",
                                                        level: 75,
                                                        color: "text-amber-400",
                                                    },
                                                ].map((tier) => (
                                                    <div
                                                        key={tier.name}
                                                        className="text-center p-2 rounded bg-background/50 border border-border/30"
                                                    >
                                                        <Hammer
                                                            className={`h-4 w-4 mx-auto ${tier.color}`}
                                                        />
                                                        <p
                                                            className={`text-xs font-medium mt-1 ${tier.color}`}
                                                        >
                                                            {tier.name}
                                                        </p>
                                                        <p className="text-[10px] text-muted-foreground">
                                                            Lvl {tier.level}+
                                                        </p>
                                                    </div>
                                                ))}
                                            </div>
                                        </div>
                                    )}
                                </div>
                            ))}
                        </div>
                    </div>

                    {/* Footer */}
                    <div className="border-t border-border/50 px-6 py-4 shrink-0">
                        <div className="flex items-center justify-between">
                            <p className="text-xs text-muted-foreground">
                                More updates coming soon!
                            </p>
                            <Button size="sm" onClick={onClose}>
                                Got it!
                            </Button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}
