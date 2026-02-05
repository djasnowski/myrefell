import { Head, Link, usePage } from "@inertiajs/react";
import { ArrowLeft, Crown, Scroll } from "lucide-react";
import { dashboard, login, register } from "@/routes";
import type { SharedData } from "@/types";

export default function Rules({ canRegister = true }: { canRegister?: boolean }) {
    const { auth } = usePage<SharedData>().props;

    return (
        <>
            <Head title="Rules of Myrefell">
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
                                    {canRegister && (
                                        <Link
                                            href={register()}
                                            className="rounded-lg bg-primary px-6 py-2 font-semibold text-primary-foreground transition hover:bg-primary/90"
                                        >
                                            Play Now
                                        </Link>
                                    )}
                                </>
                            )}
                        </div>
                    </div>
                </nav>

                {/* Header */}
                <section className="relative pt-32 pb-16">
                    <div className="mx-auto max-w-7xl px-6 text-center">
                        <h1 className="mb-4 font-[Cinzel] text-4xl font-bold text-foreground md:text-5xl">
                            Rules of <span className="text-primary">Myrefell</span>
                        </h1>
                        <p className="mx-auto max-w-2xl text-lg text-muted-foreground">
                            The laws that govern all who walk these lands. All players are expected
                            to uphold these rules to ensure a fair and enjoyable realm for everyone.
                        </p>
                        <p className="mt-4 text-sm text-muted-foreground/60">
                            Last updated: February 2026
                        </p>
                    </div>
                </section>

                {/* Content */}
                <section className="relative pb-24">
                    <div className="mx-auto max-w-4xl px-6">
                        <div className="space-y-12">
                            {/* General Rules */}
                            <div className="rounded-xl border border-border/50 bg-card/50 p-8">
                                <h2 className="mb-4 font-[Cinzel] text-2xl font-bold text-foreground">
                                    General Rules
                                </h2>
                                <ul className="space-y-3 text-muted-foreground">
                                    <li className="flex items-start gap-3">
                                        <Scroll className="mt-1 h-4 w-4 flex-shrink-0 text-primary" />
                                        <span>
                                            <strong className="text-foreground">Play Fair:</strong>{" "}
                                            All players are expected to play honestly and in the
                                            spirit of the game. Attempting to gain an unfair
                                            advantage through any means not intended by the game's
                                            design is prohibited.
                                        </span>
                                    </li>
                                    <li className="flex items-start gap-3">
                                        <Scroll className="mt-1 h-4 w-4 flex-shrink-0 text-primary" />
                                        <span>
                                            <strong className="text-foreground">
                                                One Account Per Player:
                                            </strong>{" "}
                                            Each player may only have one active account. Creating
                                            multiple accounts to gain advantages (multiboxing) is
                                            strictly forbidden and will result in all associated
                                            accounts being banned.
                                        </span>
                                    </li>
                                    <li className="flex items-start gap-3">
                                        <Scroll className="mt-1 h-4 w-4 flex-shrink-0 text-primary" />
                                        <span>
                                            <strong className="text-foreground">
                                                No Account Sharing:
                                            </strong>{" "}
                                            Your account is personal. Do not share your login
                                            credentials with anyone. You are responsible for all
                                            activity on your account.
                                        </span>
                                    </li>
                                    <li className="flex items-start gap-3">
                                        <Scroll className="mt-1 h-4 w-4 flex-shrink-0 text-primary" />
                                        <span>
                                            <strong className="text-foreground">
                                                Respect the Staff:
                                            </strong>{" "}
                                            Game moderators and administrators volunteer their time
                                            to keep the realm running. Follow their instructions and
                                            treat them with respect.
                                        </span>
                                    </li>
                                </ul>
                            </div>

                            {/* Chat & Communication */}
                            <div className="rounded-xl border border-border/50 bg-card/50 p-8">
                                <h2 className="mb-4 font-[Cinzel] text-2xl font-bold text-foreground">
                                    Chat & Communication
                                </h2>
                                <ul className="space-y-3 text-muted-foreground">
                                    <li className="flex items-start gap-3">
                                        <Scroll className="mt-1 h-4 w-4 flex-shrink-0 text-primary" />
                                        <span>
                                            <strong className="text-foreground">
                                                No Harassment:
                                            </strong>{" "}
                                            Bullying, threats, stalking, or any form of targeted
                                            harassment toward other players is prohibited. This
                                            includes persistent unwanted contact through private
                                            messages.
                                        </span>
                                    </li>
                                    <li className="flex items-start gap-3">
                                        <Scroll className="mt-1 h-4 w-4 flex-shrink-0 text-primary" />
                                        <span>
                                            <strong className="text-foreground">
                                                No Hate Speech:
                                            </strong>{" "}
                                            Slurs, discriminatory language, or content targeting
                                            race, ethnicity, gender, sexual orientation, religion,
                                            or disability will not be tolerated under any
                                            circumstances.
                                        </span>
                                    </li>
                                    <li className="flex items-start gap-3">
                                        <Scroll className="mt-1 h-4 w-4 flex-shrink-0 text-primary" />
                                        <span>
                                            <strong className="text-foreground">No Spam:</strong> Do
                                            not flood chat channels with repetitive messages,
                                            excessive formatting, or unsolicited advertisements.
                                        </span>
                                    </li>
                                    <li className="flex items-start gap-3">
                                        <Scroll className="mt-1 h-4 w-4 flex-shrink-0 text-primary" />
                                        <span>
                                            <strong className="text-foreground">
                                                No Real-World Politics:
                                            </strong>{" "}
                                            In-game chat is for in-game discussion. Keep real-world
                                            political, religious, and other divisive real-world
                                            topics out of public chat channels.
                                        </span>
                                    </li>
                                    <li className="flex items-start gap-3">
                                        <Scroll className="mt-1 h-4 w-4 flex-shrink-0 text-primary" />
                                        <span>
                                            <strong className="text-foreground">
                                                Civil Roleplay:
                                            </strong>{" "}
                                            In-character conflict and rivalry are welcome and
                                            encouraged, but keep it in-character. Do not use
                                            roleplay as a cover for genuine personal attacks.
                                        </span>
                                    </li>
                                </ul>
                            </div>

                            {/* Gameplay Rules */}
                            <div className="rounded-xl border border-border/50 bg-card/50 p-8">
                                <h2 className="mb-4 font-[Cinzel] text-2xl font-bold text-foreground">
                                    Gameplay Rules
                                </h2>
                                <ul className="space-y-3 text-muted-foreground">
                                    <li className="flex items-start gap-3">
                                        <Scroll className="mt-1 h-4 w-4 flex-shrink-0 text-primary" />
                                        <span>
                                            <strong className="text-foreground">
                                                No Exploiting Bugs:
                                            </strong>{" "}
                                            If you discover a bug or exploit, report it immediately
                                            to the staff. Deliberately using a bug to gain an
                                            advantage — whether for gold, items, skills, or
                                            political power — is a bannable offense.
                                        </span>
                                    </li>
                                    <li className="flex items-start gap-3">
                                        <Scroll className="mt-1 h-4 w-4 flex-shrink-0 text-primary" />
                                        <span>
                                            <strong className="text-foreground">
                                                No Botting or Automation:
                                            </strong>{" "}
                                            You must play the game manually. Using scripts, macros,
                                            bots, auto-clickers, or any other software to automate
                                            gameplay actions is strictly prohibited.
                                        </span>
                                    </li>
                                    <li className="flex items-start gap-3">
                                        <Scroll className="mt-1 h-4 w-4 flex-shrink-0 text-primary" />
                                        <span>
                                            <strong className="text-foreground">
                                                No Real-Money Trading:
                                            </strong>{" "}
                                            Buying, selling, or trading in-game items, gold,
                                            accounts, or services for real-world money or other
                                            real-world value is forbidden.
                                        </span>
                                    </li>
                                    <li className="flex items-start gap-3">
                                        <Scroll className="mt-1 h-4 w-4 flex-shrink-0 text-primary" />
                                        <span>
                                            <strong className="text-foreground">
                                                No Client Modification:
                                            </strong>{" "}
                                            Modifying the game client, using packet manipulation
                                            tools, or otherwise tampering with game communications
                                            is prohibited.
                                        </span>
                                    </li>
                                    <li className="flex items-start gap-3">
                                        <Scroll className="mt-1 h-4 w-4 flex-shrink-0 text-primary" />
                                        <span>
                                            <strong className="text-foreground">
                                                No Tab/Window Manipulation:
                                            </strong>{" "}
                                            Opening multiple browser tabs or windows to gain timer
                                            advantages, bypass cooldowns, or perform actions faster
                                            than intended is not allowed. Play in a single active
                                            session at a time.
                                        </span>
                                    </li>
                                </ul>
                            </div>

                            {/* Political & Governance Rules */}
                            <div className="rounded-xl border border-border/50 bg-card/50 p-8">
                                <h2 className="mb-4 font-[Cinzel] text-2xl font-bold text-foreground">
                                    Political & Governance Rules
                                </h2>
                                <ul className="space-y-3 text-muted-foreground">
                                    <li className="flex items-start gap-3">
                                        <Scroll className="mt-1 h-4 w-4 flex-shrink-0 text-primary" />
                                        <span>
                                            <strong className="text-foreground">
                                                Elections Are Binding:
                                            </strong>{" "}
                                            Election results are final. Players who win elections
                                            hold legitimate authority. Attempting to undermine
                                            election results through out-of-game means is not
                                            permitted.
                                        </span>
                                    </li>
                                    <li className="flex items-start gap-3">
                                        <Scroll className="mt-1 h-4 w-4 flex-shrink-0 text-primary" />
                                        <span>
                                            <strong className="text-foreground">
                                                No Griefing via Political Office:
                                            </strong>{" "}
                                            While rulers have broad powers, deliberately using
                                            political authority solely to ruin other players'
                                            experiences — such as imposing maximum taxes with no
                                            purpose or recklessly destroying infrastructure — may be
                                            considered griefing.
                                        </span>
                                    </li>
                                    <li className="flex items-start gap-3">
                                        <Scroll className="mt-1 h-4 w-4 flex-shrink-0 text-primary" />
                                        <span>
                                            <strong className="text-foreground">
                                                In-Game Disputes Stay In-Game:
                                            </strong>{" "}
                                            Political conflicts, power struggles, and governance
                                            disagreements should be resolved through in-game
                                            mechanisms — elections, no-confidence votes, wars, and
                                            diplomacy. Do not escalate in-game politics to
                                            out-of-game harassment.
                                        </span>
                                    </li>
                                </ul>
                            </div>

                            {/* Combat & Warfare */}
                            <div className="rounded-xl border border-border/50 bg-card/50 p-8">
                                <h2 className="mb-4 font-[Cinzel] text-2xl font-bold text-foreground">
                                    Combat & Warfare
                                </h2>
                                <ul className="space-y-3 text-muted-foreground">
                                    <li className="flex items-start gap-3">
                                        <Scroll className="mt-1 h-4 w-4 flex-shrink-0 text-primary" />
                                        <span>
                                            <strong className="text-foreground">
                                                No Exploiting Combat Mechanics:
                                            </strong>{" "}
                                            Using bugs, animation cancels, or unintended mechanics
                                            to gain advantages in combat is not allowed. Fight
                                            honorably.
                                        </span>
                                    </li>
                                    <li className="flex items-start gap-3">
                                        <Scroll className="mt-1 h-4 w-4 flex-shrink-0 text-primary" />
                                        <span>
                                            <strong className="text-foreground">
                                                Wars Must Be Declared Properly:
                                            </strong>{" "}
                                            Warfare between kingdoms, baronies, and factions must
                                            follow the in-game war declaration system. Attacking
                                            without a proper declaration is not permitted.
                                        </span>
                                    </li>
                                    <li className="flex items-start gap-3">
                                        <Scroll className="mt-1 h-4 w-4 flex-shrink-0 text-primary" />
                                        <span>
                                            <strong className="text-foreground">
                                                No Spawn-Killing:
                                            </strong>{" "}
                                            Repeatedly killing the same player immediately after
                                            they respawn, with the sole intent of preventing them
                                            from playing, is considered griefing and is prohibited.
                                        </span>
                                    </li>
                                </ul>
                            </div>

                            {/* Economy & Trade */}
                            <div className="rounded-xl border border-border/50 bg-card/50 p-8">
                                <h2 className="mb-4 font-[Cinzel] text-2xl font-bold text-foreground">
                                    Economy & Trade
                                </h2>
                                <ul className="space-y-3 text-muted-foreground">
                                    <li className="flex items-start gap-3">
                                        <Scroll className="mt-1 h-4 w-4 flex-shrink-0 text-primary" />
                                        <span>
                                            <strong className="text-foreground">
                                                No Market Manipulation via Exploits:
                                            </strong>{" "}
                                            Using bugs or exploits to duplicate items, generate
                                            gold, or otherwise manipulate the in-game economy is
                                            strictly prohibited. Legitimate market strategies such
                                            as buying low and selling high are perfectly fine.
                                        </span>
                                    </li>
                                    <li className="flex items-start gap-3">
                                        <Scroll className="mt-1 h-4 w-4 flex-shrink-0 text-primary" />
                                        <span>
                                            <strong className="text-foreground">
                                                No Scam Trading:
                                            </strong>{" "}
                                            Deliberately misleading other players about trades using
                                            out-of-game communication is not permitted. All trades
                                            should be conducted honestly through in-game trading
                                            systems.
                                        </span>
                                    </li>
                                </ul>
                            </div>

                            {/* Naming Policy */}
                            <div className="rounded-xl border border-border/50 bg-card/50 p-8">
                                <h2 className="mb-4 font-[Cinzel] text-2xl font-bold text-foreground">
                                    Naming Policy
                                </h2>
                                <p className="text-muted-foreground leading-relaxed mb-4">
                                    All player-chosen names — including character names, dynasty
                                    names, guild names, and religion names — must adhere to the
                                    following guidelines:
                                </p>
                                <ul className="space-y-3 text-muted-foreground">
                                    <li className="flex items-start gap-3">
                                        <Scroll className="mt-1 h-4 w-4 flex-shrink-0 text-primary" />
                                        <span>
                                            <strong className="text-foreground">
                                                No Offensive Names:
                                            </strong>{" "}
                                            Names containing profanity, slurs, sexual content, or
                                            references to violence are prohibited.
                                        </span>
                                    </li>
                                    <li className="flex items-start gap-3">
                                        <Scroll className="mt-1 h-4 w-4 flex-shrink-0 text-primary" />
                                        <span>
                                            <strong className="text-foreground">
                                                No Real-World Political Names:
                                            </strong>{" "}
                                            Names referencing real-world political figures, parties,
                                            movements, or controversial events are not allowed.
                                        </span>
                                    </li>
                                    <li className="flex items-start gap-3">
                                        <Scroll className="mt-1 h-4 w-4 flex-shrink-0 text-primary" />
                                        <span>
                                            <strong className="text-foreground">
                                                No Impersonation:
                                            </strong>{" "}
                                            Do not use names that impersonate game staff,
                                            moderators, or other players.
                                        </span>
                                    </li>
                                    <li className="flex items-start gap-3">
                                        <Scroll className="mt-1 h-4 w-4 flex-shrink-0 text-primary" />
                                        <span>
                                            <strong className="text-foreground">
                                                Thematic Names Encouraged:
                                            </strong>{" "}
                                            While not required, names that fit the medieval fantasy
                                            setting of Myrefell are encouraged. Moderators may
                                            request a name change for names that are clearly
                                            disruptive or immersion-breaking.
                                        </span>
                                    </li>
                                </ul>
                            </div>

                            {/* Reporting & Enforcement */}
                            <div className="rounded-xl border border-border/50 bg-card/50 p-8">
                                <h2 className="mb-4 font-[Cinzel] text-2xl font-bold text-foreground">
                                    Reporting & Enforcement
                                </h2>
                                <div className="space-y-6">
                                    <div>
                                        <h3 className="mb-2 font-semibold text-foreground">
                                            How to Report
                                        </h3>
                                        <p className="text-muted-foreground leading-relaxed">
                                            If you witness a rule violation, report it by emailing{" "}
                                            <a
                                                href="mailto:abuse@myrefell.com"
                                                className="text-primary hover:underline"
                                            >
                                                abuse@myrefell.com
                                            </a>{" "}
                                            with as much detail as possible, including the offending
                                            player's name, a description of the violation, and any
                                            relevant screenshots or timestamps.
                                        </p>
                                    </div>
                                    <div>
                                        <h3 className="mb-2 font-semibold text-foreground">
                                            Enforcement Process
                                        </h3>
                                        <p className="text-muted-foreground leading-relaxed mb-3">
                                            Enforcement is proportional to the severity and
                                            frequency of the offense:
                                        </p>
                                        <ul className="space-y-3 text-muted-foreground">
                                            <li className="flex items-start gap-3">
                                                <Scroll className="mt-1 h-4 w-4 flex-shrink-0 text-primary" />
                                                <span>
                                                    <strong className="text-foreground">
                                                        First Offense:
                                                    </strong>{" "}
                                                    A formal warning and explanation of the violated
                                                    rule.
                                                </span>
                                            </li>
                                            <li className="flex items-start gap-3">
                                                <Scroll className="mt-1 h-4 w-4 flex-shrink-0 text-primary" />
                                                <span>
                                                    <strong className="text-foreground">
                                                        Second Offense:
                                                    </strong>{" "}
                                                    A temporary ban (duration depends on severity).
                                                </span>
                                            </li>
                                            <li className="flex items-start gap-3">
                                                <Scroll className="mt-1 h-4 w-4 flex-shrink-0 text-primary" />
                                                <span>
                                                    <strong className="text-foreground">
                                                        Severe or Repeated Offenses:
                                                    </strong>{" "}
                                                    A permanent ban. Severe violations such as
                                                    exploiting critical bugs, real-money trading, or
                                                    extreme harassment may result in an immediate
                                                    permanent ban without prior warnings.
                                                </span>
                                            </li>
                                        </ul>
                                    </div>
                                    <div>
                                        <h3 className="mb-2 font-semibold text-foreground">
                                            Appeals
                                        </h3>
                                        <p className="text-muted-foreground leading-relaxed">
                                            If you believe you were banned unfairly, you may submit
                                            an appeal by emailing{" "}
                                            <a
                                                href="mailto:appeals@myrefell.com"
                                                className="text-primary hover:underline"
                                            >
                                                appeals@myrefell.com
                                            </a>
                                            . Include your character name and a clear explanation of
                                            why you believe the ban was unjust. Appeals are reviewed
                                            within a reasonable timeframe, and decisions are final.
                                        </p>
                                    </div>
                                </div>
                            </div>

                            {/* Contact */}
                            <div className="rounded-xl border border-border/50 bg-card/50 p-8">
                                <h2 className="mb-4 font-[Cinzel] text-2xl font-bold text-foreground">
                                    Contact
                                </h2>
                                <ul className="space-y-2 text-muted-foreground">
                                    <li className="flex items-start gap-3">
                                        <Scroll className="mt-1 h-4 w-4 flex-shrink-0 text-primary" />
                                        <span>
                                            Report violations:{" "}
                                            <a
                                                href="mailto:abuse@myrefell.com"
                                                className="text-primary hover:underline"
                                            >
                                                abuse@myrefell.com
                                            </a>
                                        </span>
                                    </li>
                                    <li className="flex items-start gap-3">
                                        <Scroll className="mt-1 h-4 w-4 flex-shrink-0 text-primary" />
                                        <span>
                                            Ban appeals:{" "}
                                            <a
                                                href="mailto:appeals@myrefell.com"
                                                className="text-primary hover:underline"
                                            >
                                                appeals@myrefell.com
                                            </a>
                                        </span>
                                    </li>
                                    <li className="flex items-start gap-3">
                                        <Scroll className="mt-1 h-4 w-4 flex-shrink-0 text-primary" />
                                        <span>
                                            General inquiries:{" "}
                                            <a
                                                href="mailto:support@myrefell.com"
                                                className="text-primary hover:underline"
                                            >
                                                support@myrefell.com
                                            </a>
                                        </span>
                                    </li>
                                </ul>
                            </div>
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
                                <Link href="/terms" className="transition hover:text-primary">
                                    Terms of Service
                                </Link>
                                <span>&middot;</span>
                                <Link href="/privacy" className="transition hover:text-primary">
                                    Privacy Policy
                                </Link>
                                <span>&middot;</span>
                                <Link href="/rules" className="text-primary">
                                    Game Rules
                                </Link>
                            </div>
                        </div>
                    </div>
                </footer>
            </div>
        </>
    );
}
