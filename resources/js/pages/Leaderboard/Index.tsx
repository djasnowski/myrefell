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
    Wheat,
} from "lucide-react";
import { useState } from "react";
import { dashboard, login, register } from "@/routes";
import type { SharedData } from "@/types";

interface LeaderboardEntry {
    rank: number;
    username: string;
    level: number;
    xp: number;
}

interface PageProps extends SharedData {
    leaderboards: Record<string, LeaderboardEntry[]>;
    skills: string[];
}

const skillIcons: Record<string, LucideIcon> = {
    total: Crown,
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
    total: "text-amber-400",
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

export default function LeaderboardIndex() {
    const { leaderboards, skills, auth } = usePage<PageProps>().props;
    const [selectedSkill, setSelectedSkill] = useState<string>(skills[0] || "attack");

    const currentLeaderboard = leaderboards[selectedSkill] || [];
    const Icon = skillIcons[selectedSkill] || Sword;
    const iconColor = skillColors[selectedSkill] || "text-amber-400";

    return (
        <>
            <Head title="Highscores - Myrefell">
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
                                href="/"
                                className="flex items-center gap-2 text-muted-foreground hover:text-primary transition"
                            >
                                <ArrowLeft className="h-4 w-4" />
                                <span className="text-sm">Back</span>
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

                {/* Header */}
                <section className="relative pt-32 pb-8">
                    <div className="mx-auto max-w-7xl px-6 text-center">
                        <div className="mb-4 inline-flex items-center gap-2">
                            <Trophy className="h-10 w-10 text-amber-400" />
                        </div>
                        <h1 className="mb-4 font-[Cinzel] text-4xl font-bold text-foreground md:text-5xl">
                            <span className="text-primary">Highscores</span>
                        </h1>
                        <p className="mx-auto max-w-2xl text-lg text-muted-foreground">
                            Top 15 players for each skill. Train hard, rise through the ranks.
                        </p>
                    </div>
                </section>

                {/* Skill Tabs */}
                <section className="relative pb-6">
                    <div className="mx-auto max-w-7xl px-6">
                        <div className="flex flex-wrap justify-center gap-2">
                            {skills.map((skill) => {
                                const SkillIcon = skillIcons[skill] || Sword;
                                const color = skillColors[skill] || "text-amber-400";
                                const isSelected = selectedSkill === skill;

                                return (
                                    <button
                                        key={skill}
                                        onClick={() => setSelectedSkill(skill)}
                                        className={`flex items-center gap-2 px-4 py-2 rounded-lg text-sm transition-colors capitalize ${
                                            isSelected
                                                ? "bg-primary/20 border-2 border-primary/50 text-primary"
                                                : "bg-card/50 border-2 border-border/50 text-muted-foreground hover:text-foreground hover:border-border"
                                        }`}
                                    >
                                        <SkillIcon className={`h-4 w-4 ${color}`} />
                                        {skill === "total" ? "Total" : skill}
                                    </button>
                                );
                            })}
                        </div>
                    </div>
                </section>

                {/* Leaderboard Content */}
                <section className="relative pb-24">
                    <div className="mx-auto max-w-3xl px-6">
                        <div className="rounded-xl border border-border/50 bg-card/50 backdrop-blur-sm p-6">
                            <div className="flex items-center gap-2 mb-6 pb-4 border-b border-border/50">
                                <Icon className={`h-6 w-6 ${iconColor}`} />
                                <h2 className="font-[Cinzel] text-xl font-bold capitalize text-foreground">
                                    {selectedSkill === "total" ? "Total Level" : selectedSkill}{" "}
                                    Rankings
                                </h2>
                            </div>

                            {currentLeaderboard.length === 0 ? (
                                <p className="text-center text-muted-foreground py-12">
                                    {selectedSkill === "total"
                                        ? "No players have leveled up yet"
                                        : "No players with 10+ XP yet"}
                                </p>
                            ) : (
                                <div className="space-y-2">
                                    {currentLeaderboard.map((entry) => (
                                        <div
                                            key={entry.rank}
                                            className={`flex items-center gap-4 px-4 py-3 rounded-lg border transition-all ${
                                                entry.rank === 1
                                                    ? "bg-amber-500/10 border-amber-500/30"
                                                    : entry.rank === 2
                                                      ? "bg-slate-400/10 border-slate-400/30"
                                                      : entry.rank === 3
                                                        ? "bg-orange-500/10 border-orange-500/30"
                                                        : "bg-card/30 border-border/30"
                                            }`}
                                        >
                                            {/* Rank */}
                                            <div className="w-10 text-center shrink-0">
                                                {entry.rank === 1 ? (
                                                    <Trophy className="h-6 w-6 text-amber-400 mx-auto" />
                                                ) : entry.rank === 2 ? (
                                                    <Trophy className="h-6 w-6 text-slate-300 mx-auto" />
                                                ) : entry.rank === 3 ? (
                                                    <Trophy className="h-6 w-6 text-orange-500 mx-auto" />
                                                ) : (
                                                    <span className="text-sm text-muted-foreground">
                                                        #{entry.rank}
                                                    </span>
                                                )}
                                            </div>

                                            {/* Username */}
                                            <div className="flex-1 min-w-0">
                                                <Link
                                                    href={`/players/${entry.username}`}
                                                    className={`truncate block hover:underline ${
                                                        entry.rank === 1
                                                            ? "text-amber-300 hover:text-amber-200"
                                                            : entry.rank === 2
                                                              ? "text-slate-200 hover:text-slate-100"
                                                              : entry.rank === 3
                                                                ? "text-orange-300 hover:text-orange-200"
                                                                : "text-foreground/80 hover:text-foreground"
                                                    }`}
                                                >
                                                    {entry.username}
                                                </Link>
                                            </div>

                                            {/* Level & XP */}
                                            <div className="text-right shrink-0">
                                                <p
                                                    className={`text-sm ${
                                                        entry.rank <= 3
                                                            ? "text-foreground"
                                                            : "text-foreground/80"
                                                    }`}
                                                >
                                                    {selectedSkill === "total"
                                                        ? "Total Lv."
                                                        : "Lv."}{" "}
                                                    {entry.level}
                                                </p>
                                                <p className="text-xs text-muted-foreground">
                                                    {entry.xp.toLocaleString()} XP
                                                </p>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            )}
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
