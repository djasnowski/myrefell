import { router } from '@inertiajs/react';
import {
    Briefcase,
    Coins,
    Compass,
    Crown,
    Heart,
    Map,
    Scroll,
    Shield,
    Swords,
    Users,
    Vote,
    Wheat,
    X,
} from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';

interface LocationInfo {
    name: string;
    biome: string;
}

interface Props {
    playerName: string;
    location?: LocationInfo | null;
    onClose: () => void;
}

const biomeDescriptions: Record<string, string> = {
    plains: 'Rolling golden grasslands stretch to the horizon, dotted with wildflowers swaying in the gentle breeze. The open sky feels endless here.',
    tundra: 'A harsh frozen landscape greets you, where the biting wind carries flurries of snow across the icy ground. Survival here demands resilience.',
    coastal: 'The salty sea air fills your lungs as waves crash against the shore. Seabirds cry overhead and fishing boats bob in the harbor.',
    volcano: 'The air is thick with ash and the distant rumble of the mountain reminds you of the fire sleeping beneath. Heat rises from the blackened earth.',
    forest: 'Ancient trees tower above you, their canopy filtering sunlight into dancing shadows. The rustle of leaves and chirping birds fill the woodland.',
    desert: 'Sun-scorched sands shimmer under the relentless heat. Dunes roll endlessly, hiding oases and secrets beneath their golden waves.',
    swamp: 'Murky waters and twisted trees surround you, the air thick with the smell of decay and buzzing insects. Watch your step here.',
    mountain: 'Jagged peaks pierce the clouds above, their slopes covered in pine and stone. The thin air carries the distant cry of eagles.',
};

export default function TutorialModal({ playerName, location, onClose }: Props) {
    const [step, setStep] = useState(0);

    const locationName = location?.name ?? 'an unknown village';
    const biome = location?.biome ?? 'plains';
    const biomeDescription = biomeDescriptions[biome] ?? biomeDescriptions.plains;

    const handleDismiss = () => {
        router.post('/tutorial/dismiss', {}, {
            preserveScroll: true,
            onSuccess: onClose,
        });
    };

    const steps = [
        // Step 1: Welcome - Dynamic based on location
        {
            title: 'You Awaken',
            content: (
                <div className="space-y-4">
                    <div className="flex justify-center">
                        <div className="rounded-full bg-primary/20 p-4">
                            <Crown className="h-12 w-12 text-primary" />
                        </div>
                    </div>
                    <p className="text-center text-muted-foreground">
                        You wake in <span className="font-semibold text-foreground">{locationName}</span> with only the faintest memory of how you arrived...
                    </p>
                    <p className="text-center text-sm italic text-muted-foreground/80">
                        {biomeDescription}
                    </p>
                    <p className="text-center text-muted-foreground">
                        You are <span className="font-semibold text-foreground">{playerName}</span>, a <span className="font-semibold text-primary">peasant</span> — the lowest rung of society. But every king was once a commoner.
                    </p>
                </div>
            ),
        },
        // Step 2: The World
        {
            title: 'A Living World',
            content: (
                <div className="space-y-4">
                    <div className="grid grid-cols-3 gap-3">
                        <div className="flex flex-col items-center rounded-lg border border-border/50 bg-card/50 p-3">
                            <Map className="h-6 w-6 text-primary" />
                            <span className="mt-1 text-xs text-muted-foreground">Explore</span>
                        </div>
                        <div className="flex flex-col items-center rounded-lg border border-border/50 bg-card/50 p-3">
                            <Compass className="h-6 w-6 text-primary" />
                            <span className="mt-1 text-xs text-muted-foreground">Travel</span>
                        </div>
                        <div className="flex flex-col items-center rounded-lg border border-border/50 bg-card/50 p-3">
                            <Users className="h-6 w-6 text-primary" />
                            <span className="mt-1 text-xs text-muted-foreground">Meet Others</span>
                        </div>
                    </div>
                    <p className="text-center text-muted-foreground">
                        Myrefell is a vast realm of unexplored lands, bustling villages, and great kingdoms. The world lives and breathes — even when you're away.
                    </p>
                    <p className="text-center text-sm text-muted-foreground/70">
                        NPCs work, trade, marry, and die. Seasons change. Wars are fought. History is written.
                    </p>
                </div>
            ),
        },
        // Step 3: Survival & Skills
        {
            title: 'Train & Survive',
            content: (
                <div className="space-y-4">
                    <div className="grid grid-cols-4 gap-2">
                        <div className="flex flex-col items-center rounded-lg border border-border/50 bg-card/50 p-2">
                            <Swords className="h-5 w-5 text-red-400" />
                            <span className="mt-1 text-[10px] text-muted-foreground">Combat</span>
                        </div>
                        <div className="flex flex-col items-center rounded-lg border border-border/50 bg-card/50 p-2">
                            <Shield className="h-5 w-5 text-blue-400" />
                            <span className="mt-1 text-[10px] text-muted-foreground">Defense</span>
                        </div>
                        <div className="flex flex-col items-center rounded-lg border border-border/50 bg-card/50 p-2">
                            <Wheat className="h-5 w-5 text-amber-400" />
                            <span className="mt-1 text-[10px] text-muted-foreground">Farming</span>
                        </div>
                        <div className="flex flex-col items-center rounded-lg border border-border/50 bg-card/50 p-2">
                            <Heart className="h-5 w-5 text-pink-400" />
                            <span className="mt-1 text-[10px] text-muted-foreground">Hitpoints</span>
                        </div>
                    </div>
                    <p className="text-center text-muted-foreground">
                        Train <span className="text-primary">12 skills</span> to become stronger. Combat skills protect you. Gathering skills provide resources. Crafting skills create valuable goods.
                    </p>
                    <p className="text-center text-sm text-muted-foreground/70">
                        Your body is your instrument — hone it well.
                    </p>
                </div>
            ),
        },
        // Step 4: Economy
        {
            title: 'Work & Trade',
            content: (
                <div className="space-y-4">
                    <div className="grid grid-cols-3 gap-3">
                        <div className="flex flex-col items-center rounded-lg border border-border/50 bg-card/50 p-3">
                            <Briefcase className="h-6 w-6 text-emerald-400" />
                            <span className="mt-1 text-xs text-muted-foreground">Get a Job</span>
                        </div>
                        <div className="flex flex-col items-center rounded-lg border border-border/50 bg-card/50 p-3">
                            <Coins className="h-6 w-6 text-amber-400" />
                            <span className="mt-1 text-xs text-muted-foreground">Earn Gold</span>
                        </div>
                        <div className="flex flex-col items-center rounded-lg border border-border/50 bg-card/50 p-3">
                            <Scroll className="h-6 w-6 text-purple-400" />
                            <span className="mt-1 text-xs text-muted-foreground">Trade Goods</span>
                        </div>
                    </div>
                    <p className="text-center text-muted-foreground">
                        Villages need workers. Farms need farmers. Mines need miners. Find a <span className="text-primary">job</span> to earn wages, or gather resources and sell them at the <span className="text-primary">market</span>.
                    </p>
                    <p className="text-center text-sm text-muted-foreground/70">
                        Gold opens doors. Poverty closes them.
                    </p>
                </div>
            ),
        },
        // Step 5: Rise to Power
        {
            title: 'Rise to Power',
            content: (
                <div className="space-y-4">
                    <div className="grid grid-cols-3 gap-3">
                        <div className="flex flex-col items-center rounded-lg border border-border/50 bg-card/50 p-3">
                            <Vote className="h-6 w-6 text-blue-400" />
                            <span className="mt-1 text-xs text-muted-foreground">Elections</span>
                        </div>
                        <div className="flex flex-col items-center rounded-lg border border-border/50 bg-card/50 p-3">
                            <Crown className="h-6 w-6 text-amber-400" />
                            <span className="mt-1 text-xs text-muted-foreground">Titles</span>
                        </div>
                        <div className="flex flex-col items-center rounded-lg border border-border/50 bg-card/50 p-3">
                            <Users className="h-6 w-6 text-pink-400" />
                            <span className="mt-1 text-xs text-muted-foreground">Dynasty</span>
                        </div>
                    </div>
                    <p className="text-center text-muted-foreground">
                        Climb the <span className="text-primary">social ladder</span>. Earn your freedom, gain citizenship, join a guild, or achieve nobility. Hold office. Build a dynasty. Perhaps one day... wear the crown.
                    </p>
                    <p className="text-center text-sm text-muted-foreground/70">
                        Every position of power is earned, not given.
                    </p>
                </div>
            ),
        },
        // Step 6: Your Journey Begins
        {
            title: 'Your Story Begins',
            content: (
                <div className="space-y-4">
                    <div className="flex justify-center">
                        <div className="rounded-full bg-primary/20 p-4">
                            <Compass className="h-12 w-12 text-primary" />
                        </div>
                    </div>
                    <p className="text-center text-muted-foreground">
                        The path ahead is yours to forge. Will you become a wealthy merchant? A fearsome warrior? A cunning politician? A beloved leader?
                    </p>
                    <div className="rounded-lg border border-primary/30 bg-primary/10 p-3">
                        <p className="text-center text-sm text-primary">
                            Start by exploring your village, training your skills, and finding work. The world of Myrefell awaits.
                        </p>
                    </div>
                    <p className="text-center text-xs text-muted-foreground/70">
                        Remember: every king started as a peasant.
                    </p>
                </div>
            ),
        },
    ];

    const currentStep = steps[step];
    const isLastStep = step === steps.length - 1;
    const isFirstStep = step === 0;

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
            {/* Backdrop */}
            <div className="absolute inset-0 bg-background/80 backdrop-blur-sm" onClick={handleDismiss} />

            {/* Modal */}
            <div className="relative w-full max-w-md">
                {/* Corner decorations */}
                <div className="absolute -top-2 -left-2 w-6 h-6 border-t-2 border-l-2 border-primary/60" />
                <div className="absolute -top-2 -right-2 w-6 h-6 border-t-2 border-r-2 border-primary/60" />
                <div className="absolute -bottom-2 -left-2 w-6 h-6 border-b-2 border-l-2 border-primary/60" />
                <div className="absolute -bottom-2 -right-2 w-6 h-6 border-b-2 border-r-2 border-primary/60" />

                <div className="relative bg-card border border-border/50 shadow-lg shadow-primary/5">
                    {/* Close button */}
                    <button
                        onClick={handleDismiss}
                        className="absolute right-3 top-3 rounded-sm opacity-70 ring-offset-background transition-opacity hover:opacity-100 focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2"
                    >
                        <X className="h-4 w-4" />
                        <span className="sr-only">Close</span>
                    </button>

                    {/* Header */}
                    <div className="border-b border-border/50 px-6 py-4">
                        <h2 className="font-[Cinzel] text-xl font-bold text-foreground pr-6">
                            {currentStep.title}
                        </h2>
                        {/* Progress dots */}
                        <div className="mt-2 flex gap-1.5">
                            {steps.map((_, i) => (
                                <div
                                    key={i}
                                    className={`h-1.5 w-1.5 rounded-full transition-colors ${
                                        i === step ? 'bg-primary' : i < step ? 'bg-primary/50' : 'bg-muted'
                                    }`}
                                />
                            ))}
                        </div>
                    </div>

                    {/* Content */}
                    <div className="px-6 py-6">
                        {currentStep.content}
                    </div>

                    {/* Footer */}
                    <div className="border-t border-border/50 px-6 py-4">
                        <div className="flex items-center justify-between">
                            <Button
                                variant="ghost"
                                size="sm"
                                onClick={() => setStep(step - 1)}
                                disabled={isFirstStep}
                                className={isFirstStep ? 'invisible' : ''}
                            >
                                Back
                            </Button>

                            <div className="flex gap-2">
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    onClick={handleDismiss}
                                >
                                    Skip
                                </Button>
                                {isLastStep ? (
                                    <Button size="sm" onClick={handleDismiss}>
                                        Begin Journey
                                    </Button>
                                ) : (
                                    <Button size="sm" onClick={() => setStep(step + 1)}>
                                        Next
                                    </Button>
                                )}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}
