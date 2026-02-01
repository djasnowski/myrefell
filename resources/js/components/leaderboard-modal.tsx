import type { LucideIcon } from "lucide-react";
import {
    Beef,
    BicepsFlexed,
    Crosshair,
    Fish,
    FlaskConical,
    Hammer,
    Hand,
    Heart,
    Leaf,
    Loader2,
    Pickaxe,
    Scissors,
    Shield,
    Sparkles,
    Sword,
    TreeDeciduous,
    Trophy,
    Wheat,
    X,
} from "lucide-react";
import { useEffect, useState } from "react";
import { Button } from "@/components/ui/button";

interface LeaderboardEntry {
    rank: number;
    username: string;
    level: number;
    xp: number;
}

interface LeaderboardData {
    leaderboards: Record<string, LeaderboardEntry[]>;
    skills: string[];
}

interface Props {
    onClose: () => void;
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
};

export default function LeaderboardModal({ onClose }: Props) {
    const [data, setData] = useState<LeaderboardData | null>(null);
    const [loading, setLoading] = useState(true);
    const [selectedSkill, setSelectedSkill] = useState<string>("attack");

    useEffect(() => {
        fetch("/leaderboard")
            .then((res) => res.json())
            .then((json) => {
                setData(json);
                if (json.skills?.length > 0) {
                    setSelectedSkill(json.skills[0]);
                }
            })
            .finally(() => setLoading(false));
    }, []);

    const currentLeaderboard = data?.leaderboards?.[selectedSkill] || [];
    const Icon = skillIcons[selectedSkill] || Sword;
    const iconColor = skillColors[selectedSkill] || "text-amber-400";

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
            {/* Backdrop */}
            <div className="absolute inset-0 bg-background/80 backdrop-blur-sm" onClick={onClose} />

            {/* Modal */}
            <div className="relative w-full max-w-3xl max-h-[85vh] overflow-hidden">
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
                            <Trophy className="h-6 w-6 text-amber-400" />
                            <h2 className="font-[Cinzel] text-xl font-bold text-foreground">
                                Leaderboards
                            </h2>
                        </div>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Top 15 players for each skill
                        </p>
                    </div>

                    {loading ? (
                        <div className="flex items-center justify-center py-20">
                            <Loader2 className="h-8 w-8 animate-spin text-muted-foreground" />
                        </div>
                    ) : (
                        <>
                            {/* Skill Tabs */}
                            <div className="border-b border-border/50 px-4 py-3 shrink-0 overflow-x-auto">
                                <div className="flex gap-1 min-w-max">
                                    {data?.skills.map((skill) => {
                                        const SkillIcon = skillIcons[skill] || Sword;
                                        const color = skillColors[skill] || "text-amber-400";
                                        const isSelected = selectedSkill === skill;

                                        return (
                                            <button
                                                key={skill}
                                                onClick={() => setSelectedSkill(skill)}
                                                className={`flex items-center gap-1.5 px-3 py-1.5 rounded-md text-sm font-medium transition-colors capitalize ${
                                                    isSelected
                                                        ? "bg-primary/20 text-primary"
                                                        : "text-muted-foreground hover:text-foreground hover:bg-muted/50"
                                                }`}
                                            >
                                                <SkillIcon className={`h-4 w-4 ${color}`} />
                                                <span className="hidden sm:inline">{skill}</span>
                                            </button>
                                        );
                                    })}
                                </div>
                            </div>

                            {/* Leaderboard Content */}
                            <div className="overflow-y-auto flex-1 px-6 py-4">
                                <div className="flex items-center gap-2 mb-4">
                                    <Icon className={`h-6 w-6 ${iconColor}`} />
                                    <h3 className="font-[Cinzel] text-lg font-semibold capitalize">
                                        {selectedSkill}
                                    </h3>
                                </div>

                                {currentLeaderboard.length === 0 ? (
                                    <p className="text-center text-muted-foreground py-8">
                                        No players yet
                                    </p>
                                ) : (
                                    <div className="space-y-2">
                                        {currentLeaderboard.map((entry) => (
                                            <div
                                                key={entry.rank}
                                                className={`flex items-center gap-4 px-4 py-3 rounded-lg border ${
                                                    entry.rank === 1
                                                        ? "bg-amber-500/10 border-amber-500/30"
                                                        : entry.rank === 2
                                                          ? "bg-slate-400/10 border-slate-400/30"
                                                          : entry.rank === 3
                                                            ? "bg-orange-600/10 border-orange-600/30"
                                                            : "bg-card/50 border-border/30"
                                                }`}
                                            >
                                                {/* Rank */}
                                                <div className="w-8 text-center shrink-0">
                                                    {entry.rank === 1 ? (
                                                        <Trophy className="h-5 w-5 text-amber-400 mx-auto" />
                                                    ) : entry.rank === 2 ? (
                                                        <Trophy className="h-5 w-5 text-slate-400 mx-auto" />
                                                    ) : entry.rank === 3 ? (
                                                        <Trophy className="h-5 w-5 text-orange-600 mx-auto" />
                                                    ) : (
                                                        <span className="text-sm font-medium text-muted-foreground">
                                                            #{entry.rank}
                                                        </span>
                                                    )}
                                                </div>

                                                {/* Username */}
                                                <div className="flex-1 min-w-0">
                                                    <p
                                                        className={`font-medium truncate ${
                                                            entry.rank <= 3
                                                                ? "text-foreground"
                                                                : "text-muted-foreground"
                                                        }`}
                                                    >
                                                        {entry.username}
                                                    </p>
                                                </div>

                                                {/* Level */}
                                                <div className="text-right shrink-0">
                                                    <p className="text-sm font-semibold text-foreground">
                                                        Lv. {entry.level}
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
                        </>
                    )}

                    {/* Footer */}
                    <div className="border-t border-border/50 px-6 py-4 shrink-0">
                        <div className="flex items-center justify-end">
                            <Button size="sm" onClick={onClose}>
                                Close
                            </Button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}
