import { Head, Link } from "@inertiajs/react";
import { ArrowLeft, Crown, LogIn, MessageSquare, Scale, Shield } from "lucide-react";
import { login } from "@/routes";

export default function BanAppeals() {
    return (
        <>
            <Head title="Ban Appeals - Myrefell">
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
                        <Link
                            href={login()}
                            className="rounded-lg bg-primary px-6 py-2 font-semibold text-primary-foreground transition hover:bg-primary/90"
                        >
                            Sign In
                        </Link>
                    </div>
                </nav>

                {/* Content */}
                <section className="relative pt-32 pb-24">
                    <div className="mx-auto max-w-2xl px-6">
                        {/* Header */}
                        <div className="text-center mb-12">
                            <div className="mb-4 inline-flex items-center justify-center w-16 h-16 rounded-full bg-amber-900/30 border-2 border-amber-500/50">
                                <Scale className="w-8 h-8 text-amber-400" />
                            </div>
                            <h1 className="mb-4 font-[Cinzel] text-3xl font-bold text-foreground md:text-4xl">
                                Ban Appeals
                            </h1>
                            <p className="text-muted-foreground">
                                If your account has been suspended, you can submit an appeal.
                            </p>
                        </div>

                        {/* Process Steps */}
                        <div className="rounded-xl border border-border/50 bg-card/50 backdrop-blur-sm p-6 mb-8">
                            <h2 className="font-[Cinzel] text-xl font-bold text-foreground mb-6">
                                How to Submit an Appeal
                            </h2>

                            <div className="space-y-6">
                                <div className="flex gap-4">
                                    <div className="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-primary/20 text-sm font-bold text-primary">
                                        1
                                    </div>
                                    <div>
                                        <h3 className="font-medium text-foreground mb-1">
                                            Log in to your account
                                        </h3>
                                        <p className="text-sm text-muted-foreground">
                                            Even if you're banned, you can still log in. You'll be
                                            redirected to your ban details page.
                                        </p>
                                    </div>
                                </div>

                                <div className="flex gap-4">
                                    <div className="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-primary/20 text-sm font-bold text-primary">
                                        2
                                    </div>
                                    <div>
                                        <h3 className="font-medium text-foreground mb-1">
                                            Review your ban details
                                        </h3>
                                        <p className="text-sm text-muted-foreground">
                                            You'll see the reason for your ban and any evidence
                                            provided by our detection systems.
                                        </p>
                                    </div>
                                </div>

                                <div className="flex gap-4">
                                    <div className="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-primary/20 text-sm font-bold text-primary">
                                        3
                                    </div>
                                    <div>
                                        <h3 className="font-medium text-foreground mb-1">
                                            Submit your appeal
                                        </h3>
                                        <p className="text-sm text-muted-foreground">
                                            Click "Submit an Appeal" and explain why you believe
                                            your ban should be reconsidered. Be honest and provide
                                            any relevant context.
                                        </p>
                                    </div>
                                </div>

                                <div className="flex gap-4">
                                    <div className="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-primary/20 text-sm font-bold text-primary">
                                        4
                                    </div>
                                    <div>
                                        <h3 className="font-medium text-foreground mb-1">
                                            Wait for review
                                        </h3>
                                        <p className="text-sm text-muted-foreground">
                                            Our team will review your appeal. If approved, your ban
                                            will be lifted and you can continue playing.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {/* Important Notes */}
                        <div className="rounded-xl border border-amber-500/30 bg-amber-900/10 p-6 mb-8">
                            <div className="flex items-start gap-3">
                                <Shield className="h-5 w-5 text-amber-400 shrink-0 mt-0.5" />
                                <div>
                                    <h3 className="font-medium text-amber-300 mb-2">
                                        Important Notes
                                    </h3>
                                    <ul className="text-sm text-amber-200/80 space-y-2">
                                        <li>
                                            Be honest in your appeal. False information will result
                                            in permanent denial.
                                        </li>
                                        <li>You can only submit one appeal per ban.</li>
                                        <li>Most appeals are reviewed within 24-48 hours.</li>
                                        <li>
                                            Bans for botting or exploits require strong evidence to
                                            overturn.
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        {/* CTA */}
                        <div className="text-center">
                            <Link
                                href={login()}
                                className="inline-flex items-center gap-2 rounded-lg bg-primary px-8 py-3 font-semibold text-primary-foreground transition hover:bg-primary/90"
                            >
                                <LogIn className="h-5 w-5" />
                                Sign In to Submit Appeal
                            </Link>
                            <p className="mt-4 text-sm text-muted-foreground">
                                Need help?{" "}
                                <a
                                    href="mailto:abuse@myrefell.com"
                                    className="text-primary hover:underline"
                                >
                                    abuse@myrefell.com
                                </a>
                            </p>
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
                            </div>
                        </div>
                    </div>
                </footer>
            </div>
        </>
    );
}
