import { Head, Link, usePage } from "@inertiajs/react";
import { ArrowLeft, Crown, Scroll } from "lucide-react";
import { dashboard, login, register } from "@/routes";
import type { SharedData } from "@/types";

export default function Terms({ canRegister = true }: { canRegister?: boolean }) {
    const { auth } = usePage<SharedData>().props;

    return (
        <>
            <Head title="Terms of Service - Myrefell">
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
                            Terms of <span className="text-primary">Service</span>
                        </h1>
                        <p className="mx-auto max-w-2xl text-lg text-muted-foreground">
                            The rules and agreements governing your participation in the world of
                            Myrefell.
                        </p>
                        <p className="mt-4 text-sm text-muted-foreground/60">
                            Last updated: January 2026
                        </p>
                    </div>
                </section>

                {/* Content */}
                <section className="relative pb-24">
                    <div className="mx-auto max-w-4xl px-6">
                        <div className="space-y-12">
                            {/* Acceptance */}
                            <div className="rounded-xl border border-border/50 bg-card/50 p-8">
                                <h2 className="mb-4 font-[Cinzel] text-2xl font-bold text-foreground">
                                    Acceptance of Terms
                                </h2>
                                <p className="text-muted-foreground leading-relaxed">
                                    By creating an account and accessing Myrefell, you agree to be
                                    bound by these Terms of Service. If you do not agree to these
                                    terms, do not create an account or use the service. We reserve
                                    the right to modify these terms at any time. Continued use of
                                    Myrefell after changes are posted constitutes acceptance of the
                                    updated terms.
                                </p>
                            </div>

                            {/* Account Responsibilities */}
                            <div className="rounded-xl border border-border/50 bg-card/50 p-8">
                                <h2 className="mb-4 font-[Cinzel] text-2xl font-bold text-foreground">
                                    Account Responsibilities
                                </h2>
                                <ul className="space-y-3 text-muted-foreground">
                                    <li className="flex items-start gap-3">
                                        <Scroll className="mt-1 h-4 w-4 flex-shrink-0 text-primary" />
                                        <span>
                                            <strong className="text-foreground">
                                                One Person, One Character:
                                            </strong>{" "}
                                            Each player may only have one active account and one
                                            character at a time. Creating multiple accounts to gain
                                            an unfair advantage is prohibited.
                                        </span>
                                    </li>
                                    <li className="flex items-start gap-3">
                                        <Scroll className="mt-1 h-4 w-4 flex-shrink-0 text-primary" />
                                        <span>
                                            <strong className="text-foreground">
                                                Account Security:
                                            </strong>{" "}
                                            You are responsible for maintaining the security of your
                                            account credentials. Do not share your password with
                                            anyone. We will never ask for your password.
                                        </span>
                                    </li>
                                    <li className="flex items-start gap-3">
                                        <Scroll className="mt-1 h-4 w-4 flex-shrink-0 text-primary" />
                                        <span>
                                            <strong className="text-foreground">
                                                Account Ownership:
                                            </strong>{" "}
                                            Your account is personal and non-transferable. Selling,
                                            trading, or giving away your account is prohibited.
                                        </span>
                                    </li>
                                    <li className="flex items-start gap-3">
                                        <Scroll className="mt-1 h-4 w-4 flex-shrink-0 text-primary" />
                                        <span>
                                            <strong className="text-foreground">
                                                Age Requirement:
                                            </strong>{" "}
                                            You must be at least 13 years old to create an account
                                            and play Myrefell.
                                        </span>
                                    </li>
                                </ul>
                            </div>

                            {/* Code of Conduct */}
                            <div className="rounded-xl border border-border/50 bg-card/50 p-8">
                                <h2 className="mb-4 font-[Cinzel] text-2xl font-bold text-foreground">
                                    Code of Conduct
                                </h2>
                                <p className="mb-4 text-muted-foreground leading-relaxed">
                                    Myrefell is a shared world. The following behaviors are strictly
                                    prohibited:
                                </p>
                                <ul className="space-y-3 text-muted-foreground">
                                    <li className="flex items-start gap-3">
                                        <Scroll className="mt-1 h-4 w-4 flex-shrink-0 text-primary" />
                                        <span>
                                            <strong className="text-foreground">
                                                Cheating & Exploits:
                                            </strong>{" "}
                                            Using bots, scripts, macros, exploits, or any automated
                                            tools to play the game. If you discover an exploit,
                                            report it to{" "}
                                            <a
                                                href="mailto:abuse@myrefell.com"
                                                className="text-primary hover:underline"
                                            >
                                                abuse@myrefell.com
                                            </a>{" "}
                                            instead of using it.
                                        </span>
                                    </li>
                                    <li className="flex items-start gap-3">
                                        <Scroll className="mt-1 h-4 w-4 flex-shrink-0 text-primary" />
                                        <span>
                                            <strong className="text-foreground">Harassment:</strong>{" "}
                                            Targeted harassment, hate speech, threats, doxxing, or
                                            any behavior intended to make another player's
                                            experience hostile. In-game conflict (wars, trials,
                                            political rivalry) is part of the game; personal attacks
                                            are not.
                                        </span>
                                    </li>
                                    <li className="flex items-start gap-3">
                                        <Scroll className="mt-1 h-4 w-4 flex-shrink-0 text-primary" />
                                        <span>
                                            <strong className="text-foreground">
                                                Real-Money Trading (RMT):
                                            </strong>{" "}
                                            Buying, selling, or trading in-game currency, items,
                                            characters, or services for real-world money or goods
                                            outside the game is strictly prohibited.
                                        </span>
                                    </li>
                                    <li className="flex items-start gap-3">
                                        <Scroll className="mt-1 h-4 w-4 flex-shrink-0 text-primary" />
                                        <span>
                                            <strong className="text-foreground">
                                                Impersonation:
                                            </strong>{" "}
                                            Impersonating game administrators, moderators, or other
                                            players with intent to deceive.
                                        </span>
                                    </li>
                                    <li className="flex items-start gap-3">
                                        <Scroll className="mt-1 h-4 w-4 flex-shrink-0 text-primary" />
                                        <span>
                                            <strong className="text-foreground">
                                                Inappropriate Content:
                                            </strong>{" "}
                                            Sharing sexually explicit, excessively violent, or
                                            illegal content through any in-game communication
                                            system.
                                        </span>
                                    </li>
                                </ul>
                            </div>

                            {/* In-Game Governance */}
                            <div className="rounded-xl border border-border/50 bg-card/50 p-8">
                                <h2 className="mb-4 font-[Cinzel] text-2xl font-bold text-foreground">
                                    In-Game Governance
                                </h2>
                                <p className="text-muted-foreground leading-relaxed mb-4">
                                    Myrefell features player-driven governance systems including
                                    elections, laws, courts, and political offices. These are game
                                    mechanics designed for entertainment and are not real legal,
                                    governmental, or democratic systems.
                                </p>
                                <p className="text-muted-foreground leading-relaxed mb-4">
                                    In-game laws, court rulings, and political decisions are part of
                                    the gameplay experience. Being convicted of an in-game crime,
                                    losing an election, or being removed from office are normal game
                                    outcomes and do not constitute unfair treatment by the game
                                    operators.
                                </p>
                                <p className="text-muted-foreground leading-relaxed">
                                    Game administrators reserve the right to intervene in in-game
                                    governance systems if they are being used to grief, harass, or
                                    otherwise violate these Terms of Service.
                                </p>
                            </div>

                            {/* Intellectual Property */}
                            <div className="rounded-xl border border-border/50 bg-card/50 p-8">
                                <h2 className="mb-4 font-[Cinzel] text-2xl font-bold text-foreground">
                                    Intellectual Property
                                </h2>
                                <p className="text-muted-foreground leading-relaxed mb-4">
                                    Myrefell, including its code, art, text, game systems, and world
                                    design, is the intellectual property of the Myrefell team. You
                                    may not copy, distribute, modify, or create derivative works
                                    based on any part of Myrefell without written permission.
                                </p>
                                <p className="text-muted-foreground leading-relaxed">
                                    Player-created content within the game (character names, dynasty
                                    names, religion names, chat messages) remains subject to our
                                    moderation policies and may be removed if it violates these
                                    terms.
                                </p>
                            </div>

                            {/* Account Suspension & Termination */}
                            <div className="rounded-xl border border-border/50 bg-card/50 p-8">
                                <h2 className="mb-4 font-[Cinzel] text-2xl font-bold text-foreground">
                                    Account Suspension & Termination
                                </h2>
                                <p className="text-muted-foreground leading-relaxed mb-4">
                                    We reserve the right to suspend or permanently terminate any
                                    account that violates these Terms of Service. Actions that may
                                    result in account action include but are not limited to:
                                </p>
                                <ul className="space-y-2 text-muted-foreground mb-4">
                                    <li className="flex items-start gap-3">
                                        <Scroll className="mt-1 h-4 w-4 flex-shrink-0 text-primary" />
                                        <span>Cheating, botting, or exploiting game mechanics</span>
                                    </li>
                                    <li className="flex items-start gap-3">
                                        <Scroll className="mt-1 h-4 w-4 flex-shrink-0 text-primary" />
                                        <span>
                                            Harassment or toxic behavior toward other players
                                        </span>
                                    </li>
                                    <li className="flex items-start gap-3">
                                        <Scroll className="mt-1 h-4 w-4 flex-shrink-0 text-primary" />
                                        <span>Operating multiple accounts</span>
                                    </li>
                                    <li className="flex items-start gap-3">
                                        <Scroll className="mt-1 h-4 w-4 flex-shrink-0 text-primary" />
                                        <span>Real-money trading</span>
                                    </li>
                                    <li className="flex items-start gap-3">
                                        <Scroll className="mt-1 h-4 w-4 flex-shrink-0 text-primary" />
                                        <span>Any other violation of these terms</span>
                                    </li>
                                </ul>
                                <p className="text-muted-foreground leading-relaxed">
                                    If you believe your account was suspended or terminated in
                                    error, you may submit an appeal to{" "}
                                    <a
                                        href="mailto:appeals@myrefell.com"
                                        className="text-primary hover:underline"
                                    >
                                        appeals@myrefell.com
                                    </a>
                                    .
                                </p>
                            </div>

                            {/* Virtual Goods & Currency */}
                            <div className="rounded-xl border border-border/50 bg-card/50 p-8">
                                <h2 className="mb-4 font-[Cinzel] text-2xl font-bold text-foreground">
                                    Virtual Goods & Currency
                                </h2>
                                <p className="text-muted-foreground leading-relaxed mb-4">
                                    All in-game items, currency (gold), property, titles, and other
                                    virtual goods exist solely within the game and have no
                                    real-world monetary value. You do not own any virtual goods; you
                                    are granted a limited, revocable license to use them within the
                                    game.
                                </p>
                                <p className="text-muted-foreground leading-relaxed">
                                    We reserve the right to modify, remove, or reset any virtual
                                    goods or currency for game balance, technical, or moderation
                                    reasons. You will not be entitled to any real-world compensation
                                    for changes to virtual goods.
                                </p>
                            </div>

                            {/* Service Availability */}
                            <div className="rounded-xl border border-border/50 bg-card/50 p-8">
                                <h2 className="mb-4 font-[Cinzel] text-2xl font-bold text-foreground">
                                    Service Availability & Modifications
                                </h2>
                                <p className="text-muted-foreground leading-relaxed mb-4">
                                    We strive to keep Myrefell available at all times, but we do not
                                    guarantee uninterrupted access. The service may be temporarily
                                    unavailable due to maintenance, updates, server issues, or
                                    circumstances beyond our control.
                                </p>
                                <p className="text-muted-foreground leading-relaxed">
                                    We reserve the right to modify, update, or discontinue any
                                    aspect of the game at any time, including game mechanics,
                                    features, balance changes, and world events. These changes are
                                    part of the evolving nature of a living game world.
                                </p>
                            </div>

                            {/* Limitation of Liability */}
                            <div className="rounded-xl border border-border/50 bg-card/50 p-8">
                                <h2 className="mb-4 font-[Cinzel] text-2xl font-bold text-foreground">
                                    Limitation of Liability
                                </h2>
                                <p className="text-muted-foreground leading-relaxed mb-4">
                                    Myrefell is provided "as is" and "as available" without
                                    warranties of any kind, either express or implied. To the
                                    fullest extent permitted by law, we disclaim all warranties
                                    including fitness for a particular purpose, merchantability, and
                                    non-infringement.
                                </p>
                                <p className="text-muted-foreground leading-relaxed">
                                    We shall not be liable for any indirect, incidental, special,
                                    consequential, or punitive damages arising out of or relating to
                                    your use of Myrefell, including but not limited to loss of data,
                                    loss of virtual goods, or interruption of service.
                                </p>
                            </div>

                            {/* Contact */}
                            <div className="rounded-xl border border-border/50 bg-card/50 p-8">
                                <h2 className="mb-4 font-[Cinzel] text-2xl font-bold text-foreground">
                                    Contact Us
                                </h2>
                                <p className="text-muted-foreground leading-relaxed mb-4">
                                    If you have questions about these Terms of Service, please
                                    contact us:
                                </p>
                                <ul className="space-y-2 text-muted-foreground">
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
                                    <li className="flex items-start gap-3">
                                        <Scroll className="mt-1 h-4 w-4 flex-shrink-0 text-primary" />
                                        <span>
                                            Report cheating or abuse:{" "}
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
                                <Link href="/terms" className="text-primary">
                                    Terms of Service
                                </Link>
                                <span>&middot;</span>
                                <Link href="/privacy" className="transition hover:text-primary">
                                    Privacy Policy
                                </Link>
                                <span>&middot;</span>
                                <Link href="/rules" className="transition hover:text-primary">
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
