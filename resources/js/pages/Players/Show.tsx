import { Head, Link, usePage } from "@inertiajs/react";
import type { LucideIcon } from "lucide-react";
import {
    ArrowLeft,
    Beef,
    BicepsFlexed,
    Crown,
    Crosshair,
    Fish,
    Footprints,
    Hammer,
    Hand,
    Heart,
    Leaf,
    Pickaxe,
    Scissors,
    Shield,
    Sparkles,
    Sword,
    TreeDeciduous,
    Trophy,
    User,
    Wheat,
} from "lucide-react";
import { dashboard, login, register } from "@/routes";
import type { SharedData } from "@/types";

interface PlayerProfile {
    username: string;
    combat_level: number;
    total_level: number;
    total_xp: number;
    total_rank: number | null;
}

interface SkillData {
    name: string;
    level: number;
    xp: number;
    xp_progress: number;
    rank: number | null;
}

interface PageProps extends SharedData {
    player: PlayerProfile;
    skills: SkillData[];
}

const skillIcons: Record<string, LucideIcon> = {
    attack: Sword,
    strength: BicepsFlexed,
    defense: Shield,
    hitpoints: Heart,
    range: Crosshair,
    prayer: Sparkles,
    farming: Wheat,
    mining: Pickaxe,
    fishing: Fish,
    woodcutting: TreeDeciduous,
    cooking: Beef,
    smithing: Hammer,
    crafting: Scissors,
    thieving: Hand,
    herblore: Leaf,
    agility: Footprints,
};

const skillColors: Record<string, string> = {
    attack: "text-red-400",
    strength: "text-red-500",
    defense: "text-blue-400",
    hitpoints: "text-pink-400",
    range: "text-green-400",
    prayer: "text-yellow-300",
    farming: "text-lime-400",
    mining: "text-stone-400",
    fishing: "text-cyan-400",
    woodcutting: "text-emerald-500",
    cooking: "text-orange-400",
    smithing: "text-amber-500",
    crafting: "text-violet-400",
    thieving: "text-purple-400",
    herblore: "text-emerald-400",
    agility: "text-sky-400",
};

const skillBgColors: Record<string, string> = {
    attack: "bg-red-400",
    strength: "bg-red-500",
    defense: "bg-blue-400",
    hitpoints: "bg-pink-400",
    range: "bg-green-400",
    prayer: "bg-yellow-300",
    farming: "bg-lime-400",
    mining: "bg-stone-400",
    fishing: "bg-cyan-400",
    woodcutting: "bg-emerald-500",
    cooking: "bg-orange-400",
    smithing: "bg-amber-500",
    crafting: "bg-violet-400",
    thieving: "bg-purple-400",
    herblore: "bg-emerald-400",
    agility: "bg-sky-400",
};

export default function PlayerShow() {
    const { player, skills, auth } = usePage<PageProps>().props;

    const combatSkills = skills.filter((s) =>
        ["attack", "strength", "defense", "hitpoints", "range", "prayer"].includes(s.name),
    );
    const gatheringSkills = skills.filter((s) =>
        ["mining", "fishing", "woodcutting", "farming"].includes(s.name),
    );
    const craftingSkills = skills.filter((s) =>
        ["cooking", "smithing", "crafting", "herblore"].includes(s.name),
    );
    const supportSkills = skills.filter((s) => ["thieving", "agility"].includes(s.name));

    const renderSkillCard = (skill: SkillData) => {
        const Icon = skillIcons[skill.name] || Sword;
        const iconColor = skillColors[skill.name] || "text-amber-400";
        const bgColor = skillBgColors[skill.name] || "bg-amber-400";
        const isRanked = skill.rank !== null && skill.rank < 16;

        return (
            <div
                key={skill.name}
                className="rounded-lg border border-border/50 bg-card/30 p-3 transition-colors hover:border-border"
            >
                <div className="flex items-start justify-between gap-2">
                    <div className="flex items-center gap-2">
                        <div className={`rounded-lg p-2 ${bgColor}/20`}>
                            <Icon className={`h-5 w-5 ${iconColor}`} />
                        </div>
                        <div>
                            <p className="text-sm capitalize text-foreground">{skill.name}</p>
                            <p className="text-xs text-muted-foreground">
                                {skill.xp.toLocaleString()} XP
                            </p>
                        </div>
                    </div>
                    <div className="text-right">
                        {isRanked ? (
                            <div className="flex items-center gap-2">
                                <Link
                                    href="/leaderboard"
                                    className="text-2xl font-bold text-primary hover:text-primary/80 hover:underline"
                                >
                                    #{skill.rank}
                                </Link>
                                <span className="text-sm text-muted-foreground">
                                    Lv. {skill.level}
                                </span>
                            </div>
                        ) : (
                            <p className="text-lg text-muted-foreground/60">Lv. {skill.level}</p>
                        )}
                    </div>
                </div>
                <div className="mt-2">
                    <div className="h-1.5 w-full overflow-hidden rounded-full bg-muted/30">
                        <div
                            className={`h-full transition-all ${bgColor}`}
                            style={{ width: `${skill.xp_progress}%` }}
                        />
                    </div>
                </div>
            </div>
        );
    };

    const renderSkillSection = (title: string, sectionSkills: SkillData[]) => (
        <div className="mb-6">
            <h3 className="mb-3 text-sm text-muted-foreground">{title}</h3>
            <div className="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                {sectionSkills.map(renderSkillCard)}
            </div>
        </div>
    );

    return (
        <>
            <Head title={`${player.username}'s Profile - Myrefell`}>
                <link rel="preconnect" href="https://fonts.bunny.net" />
                <link
                    href="https://fonts.bunny.net/css?family=cinzel:400,700&family=inter:400,500,600"
                    rel="stylesheet"
                />
            </Head>

            <div className="relative min-h-screen bg-background text-foreground">
                {/* Background */}
                <div className="fixed inset-0 bg-background">
                    <div className="absolute inset-0 bg-[radial-gradient(ellipse_at_top,_var(--tw-gradient-stops))] from-primary/10 via-background to-background" />
                </div>

                {/* Navigation */}
                <nav className="fixed top-0 z-50 w-full border-b border-border/50 bg-background/90 backdrop-blur-sm">
                    <div className="mx-auto flex max-w-7xl items-center justify-between px-6 py-4">
                        <div className="flex items-center gap-4">
                            <Link
                                href="/leaderboard"
                                className="flex items-center gap-2 text-muted-foreground hover:text-primary transition"
                            >
                                <ArrowLeft className="h-4 w-4" />
                                <span className="text-sm">Highscores</span>
                            </Link>
                            <div className="h-4 w-px bg-border" />
                            <Link href="/" className="flex items-center gap-2">
                                <Crown className="h-6 w-6 text-primary" />
                                <span className="font-[Cinzel] text-xl font-bold tracking-wide text-primary">
                                    Myrefell
                                </span>
                            </Link>
                        </div>
                        <div className="flex items-center gap-4">
                            {auth.user ? (
                                <Link
                                    href={dashboard()}
                                    className="rounded-lg bg-primary px-6 py-2 font-semibold text-primary-foreground transition hover:bg-primary/90"
                                >
                                    Enter World
                                </Link>
                            ) : (
                                <>
                                    <Link
                                        href={login()}
                                        className="px-4 py-2 text-muted-foreground transition hover:text-primary"
                                    >
                                        Sign In
                                    </Link>
                                    <Link
                                        href={register()}
                                        className="rounded-lg bg-primary px-6 py-2 font-semibold text-primary-foreground transition hover:bg-primary/90"
                                    >
                                        Play Now
                                    </Link>
                                </>
                            )}
                        </div>
                    </div>
                </nav>

                {/* Player Header */}
                <section className="relative pt-32 pb-8">
                    <div className="mx-auto max-w-4xl px-6">
                        <div className="rounded-xl border border-border/50 bg-card/50 backdrop-blur-sm p-6">
                            <div className="flex flex-col items-center gap-4 sm:flex-row sm:items-start">
                                <div className="rounded-full bg-primary/20 p-4">
                                    <User className="h-12 w-12 text-primary" />
                                </div>
                                <div className="flex-1 text-center sm:text-left">
                                    <h1 className="font-[Cinzel] text-2xl font-bold text-primary">
                                        {player.username}
                                    </h1>
                                    <div className="mt-3 flex flex-wrap justify-center gap-3 sm:justify-start">
                                        <div className="rounded-lg bg-card/50 border border-border/30 px-3 py-1.5">
                                            <span className="text-xs text-muted-foreground">
                                                Combat Level
                                            </span>
                                            <p className="text-lg text-red-400">
                                                {player.combat_level}
                                            </p>
                                        </div>
                                        <div className="rounded-lg bg-card/50 border border-border/30 px-3 py-1.5">
                                            <span className="text-xs text-muted-foreground">
                                                Total Level
                                            </span>
                                            <p className="text-lg text-primary">
                                                {player.total_level}
                                            </p>
                                        </div>
                                        <div className="rounded-lg bg-card/50 border border-border/30 px-3 py-1.5">
                                            <span className="text-xs text-muted-foreground">
                                                Total XP
                                            </span>
                                            <p className="text-lg text-emerald-400">
                                                {player.total_xp.toLocaleString()}
                                            </p>
                                        </div>
                                        {player.total_rank !== null && (
                                            <div className="rounded-lg bg-primary/20 border border-primary/30 px-3 py-1.5">
                                                <span className="text-xs text-primary/80">
                                                    Overall Rank
                                                </span>
                                                <div className="flex items-center gap-1">
                                                    <Trophy className="h-4 w-4 text-primary" />
                                                    <p className="text-lg text-primary">
                                                        #{player.total_rank}
                                                    </p>
                                                </div>
                                            </div>
                                        )}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                {/* Skills Grid */}
                <section className="relative pb-24">
                    <div className="mx-auto max-w-4xl px-6">
                        <div className="rounded-xl border border-border/50 bg-card/50 backdrop-blur-sm p-6">
                            <div className="mb-6 flex items-center gap-2 border-b border-border/50 pb-4">
                                <Sword className="h-6 w-6 text-primary" />
                                <h2 className="font-[Cinzel] text-xl font-bold text-foreground">
                                    Skills
                                </h2>
                            </div>

                            {renderSkillSection("Combat", combatSkills)}
                            {renderSkillSection("Gathering", gatheringSkills)}
                            {renderSkillSection("Crafting", craftingSkills)}
                            {renderSkillSection("Support", supportSkills)}
                        </div>
                    </div>
                </section>

                {/* Footer */}
                <footer className="relative border-t border-border/50 py-8">
                    <div className="mx-auto max-w-7xl px-6">
                        <div className="flex flex-col items-center justify-between gap-4 md:flex-row">
                            <div className="flex items-center gap-2">
                                <Crown className="h-5 w-5 text-primary" />
                                <span className="font-[Cinzel] text-sm font-bold text-muted-foreground">
                                    Myrefell
                                </span>
                            </div>
                            <div className="flex items-center gap-4 text-xs text-muted-foreground">
                                <Link href="/leaderboard" className="transition hover:text-primary">
                                    Highscores
                                </Link>
                                <span>&middot;</span>
                                <Link href="/features" className="transition hover:text-primary">
                                    Features
                                </Link>
                                <span>&middot;</span>
                                <Link href="/terms" className="transition hover:text-primary">
                                    Terms of Service
                                </Link>
                                <span>&middot;</span>
                                <Link href="/privacy" className="transition hover:text-primary">
                                    Privacy Policy
                                </Link>
                            </div>
                        </div>
                    </div>
                </footer>
            </div>
        </>
    );
}
