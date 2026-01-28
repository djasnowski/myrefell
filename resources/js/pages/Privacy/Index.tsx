import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowLeft, Crown, Scroll } from 'lucide-react';
import { dashboard, login, register } from '@/routes';
import type { SharedData } from '@/types';

export default function Privacy({ canRegister = true }: { canRegister?: boolean }) {
    const { auth } = usePage<SharedData>().props;

    return (
        <>
            <Head title="Privacy Policy - Myrefell">
                <link rel="preconnect" href="https://fonts.bunny.net" />
                <link href="https://fonts.bunny.net/css?family=cinzel:400,700&family=inter:400,500,600" rel="stylesheet" />
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
                            <Link href="/" className="flex items-center gap-2 text-muted-foreground hover:text-primary transition">
                                <ArrowLeft className="h-4 w-4" />
                                <span className="text-sm">Back</span>
                            </Link>
                            <div className="h-4 w-px bg-border" />
                            <Link href="/" className="flex items-center gap-2">
                                <Crown className="h-6 w-6 text-primary" />
                                <span className="font-[Cinzel] text-xl font-bold tracking-wide text-primary">Myrefell</span>
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
                            Privacy <span className="text-primary">Policy</span>
                        </h1>
                        <p className="mx-auto max-w-2xl text-lg text-muted-foreground">
                            How we collect, use, and protect your data in the realm of Myrefell.
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
                            {/* Introduction */}
                            <div className="rounded-xl border border-border/50 bg-card/50 p-8">
                                <h2 className="mb-4 font-[Cinzel] text-2xl font-bold text-foreground">Introduction</h2>
                                <p className="text-muted-foreground leading-relaxed">
                                    Myrefell ("we", "our", or "us") is committed to protecting the privacy of our players. This Privacy Policy explains what information we collect when you use Myrefell, how we use that information, and what choices you have. By creating an account and playing Myrefell, you agree to the collection and use of information as described in this policy.
                                </p>
                            </div>

                            {/* Information We Collect */}
                            <div className="rounded-xl border border-border/50 bg-card/50 p-8">
                                <h2 className="mb-4 font-[Cinzel] text-2xl font-bold text-foreground">Information We Collect</h2>
                                <div className="space-y-6">
                                    <div>
                                        <h3 className="mb-2 font-semibold text-foreground">Account Information</h3>
                                        <p className="text-muted-foreground leading-relaxed">
                                            When you create an account, we collect your username, email address, and password (stored securely using industry-standard hashing). We do not collect your real name unless you choose to provide it.
                                        </p>
                                    </div>
                                    <div>
                                        <h3 className="mb-2 font-semibold text-foreground">Character & Gameplay Data</h3>
                                        <p className="text-muted-foreground leading-relaxed">
                                            We store all data related to your in-game character, including skills, inventory, location, employment, political positions, dynasty membership, financial transactions, combat history, and quest progress. This data is essential for the game to function.
                                        </p>
                                    </div>
                                    <div>
                                        <h3 className="mb-2 font-semibold text-foreground">Chat & Communication Logs</h3>
                                        <p className="text-muted-foreground leading-relaxed">
                                            We store messages sent through in-game chat systems, including local settlement chat and private messages. These logs are retained for moderation purposes and to enforce our Code of Conduct.
                                        </p>
                                    </div>
                                    <div>
                                        <h3 className="mb-2 font-semibold text-foreground">Technical Data</h3>
                                        <p className="text-muted-foreground leading-relaxed">
                                            We automatically collect technical information such as your IP address, browser type, operating system, and access times. This data helps us maintain security, prevent abuse, and improve performance.
                                        </p>
                                    </div>
                                </div>
                            </div>

                            {/* How We Use Your Data */}
                            <div className="rounded-xl border border-border/50 bg-card/50 p-8">
                                <h2 className="mb-4 font-[Cinzel] text-2xl font-bold text-foreground">How We Use Your Data</h2>
                                <ul className="space-y-3 text-muted-foreground">
                                    <li className="flex items-start gap-3">
                                        <Scroll className="mt-1 h-4 w-4 flex-shrink-0 text-primary" />
                                        <span><strong className="text-foreground">Game Operation:</strong> To run the game world, process your actions, maintain your character state, and deliver gameplay features.</span>
                                    </li>
                                    <li className="flex items-start gap-3">
                                        <Scroll className="mt-1 h-4 w-4 flex-shrink-0 text-primary" />
                                        <span><strong className="text-foreground">Moderation:</strong> To enforce our Terms of Service and Code of Conduct, investigate reports of cheating or harassment, and maintain a fair game environment.</span>
                                    </li>
                                    <li className="flex items-start gap-3">
                                        <Scroll className="mt-1 h-4 w-4 flex-shrink-0 text-primary" />
                                        <span><strong className="text-foreground">Security:</strong> To detect and prevent unauthorized access, exploits, bots, and other threats to the game's integrity.</span>
                                    </li>
                                    <li className="flex items-start gap-3">
                                        <Scroll className="mt-1 h-4 w-4 flex-shrink-0 text-primary" />
                                        <span><strong className="text-foreground">Analytics:</strong> To understand how the game is being played, identify balance issues, and improve game systems.</span>
                                    </li>
                                    <li className="flex items-start gap-3">
                                        <Scroll className="mt-1 h-4 w-4 flex-shrink-0 text-primary" />
                                        <span><strong className="text-foreground">Communication:</strong> To send you essential account notifications such as password resets and critical service updates.</span>
                                    </li>
                                </ul>
                            </div>

                            {/* Chat & Communication Monitoring */}
                            <div className="rounded-xl border border-border/50 bg-card/50 p-8">
                                <h2 className="mb-4 font-[Cinzel] text-2xl font-bold text-foreground">Chat & Communication Monitoring</h2>
                                <p className="text-muted-foreground leading-relaxed mb-4">
                                    In-game chat messages (both local and private) may be reviewed by moderators when investigating reports of harassment, cheating, real-money trading, or other violations of our Terms of Service. We do not proactively read private messages, but we reserve the right to access them when a report is filed or when automated systems flag suspicious activity.
                                </p>
                                <p className="text-muted-foreground leading-relaxed">
                                    Chat logs are retained for a reasonable period to support moderation and then automatically purged.
                                </p>
                            </div>

                            {/* Data Storage & Security */}
                            <div className="rounded-xl border border-border/50 bg-card/50 p-8">
                                <h2 className="mb-4 font-[Cinzel] text-2xl font-bold text-foreground">Data Storage & Security</h2>
                                <p className="text-muted-foreground leading-relaxed mb-4">
                                    Your data is stored on secure servers with industry-standard protections including encryption in transit (TLS), encrypted database connections, and regular security updates. Passwords are hashed using bcrypt and are never stored in plain text.
                                </p>
                                <p className="text-muted-foreground leading-relaxed">
                                    While we take reasonable measures to protect your information, no system is completely secure. We cannot guarantee absolute security of your data.
                                </p>
                            </div>

                            {/* Third-Party Services */}
                            <div className="rounded-xl border border-border/50 bg-card/50 p-8">
                                <h2 className="mb-4 font-[Cinzel] text-2xl font-bold text-foreground">Third-Party Services</h2>
                                <p className="text-muted-foreground leading-relaxed mb-4">
                                    We may use third-party services for hosting, analytics, and email delivery. These providers are selected for their security practices and are only given the minimum data necessary to provide their services. We do not sell your personal data to third parties.
                                </p>
                                <p className="text-muted-foreground leading-relaxed">
                                    We do not display third-party advertisements in Myrefell.
                                </p>
                            </div>

                            {/* Children's Privacy */}
                            <div className="rounded-xl border border-border/50 bg-card/50 p-8">
                                <h2 className="mb-4 font-[Cinzel] text-2xl font-bold text-foreground">Children's Privacy</h2>
                                <p className="text-muted-foreground leading-relaxed">
                                    Myrefell is not intended for children under the age of 13. We do not knowingly collect personal information from children under 13. If you are a parent or guardian and believe your child has provided us with personal information, please contact us at <a href="mailto:support@myrefell.com" className="text-primary hover:underline">support@myrefell.com</a> and we will promptly delete that information.
                                </p>
                            </div>

                            {/* Data Retention & Deletion */}
                            <div className="rounded-xl border border-border/50 bg-card/50 p-8">
                                <h2 className="mb-4 font-[Cinzel] text-2xl font-bold text-foreground">Data Retention & Deletion</h2>
                                <p className="text-muted-foreground leading-relaxed mb-4">
                                    We retain your account and gameplay data for as long as your account is active. If you wish to delete your account and associated data, you may request deletion by contacting us at <a href="mailto:support@myrefell.com" className="text-primary hover:underline">support@myrefell.com</a>.
                                </p>
                                <p className="text-muted-foreground leading-relaxed">
                                    Upon account deletion, your personal data (email, IP logs) will be permanently removed. Some anonymized gameplay data (e.g., historical election results, battle outcomes) may be retained as part of the game world's history, but will no longer be linked to your identity.
                                </p>
                            </div>

                            {/* Changes to This Policy */}
                            <div className="rounded-xl border border-border/50 bg-card/50 p-8">
                                <h2 className="mb-4 font-[Cinzel] text-2xl font-bold text-foreground">Changes to This Policy</h2>
                                <p className="text-muted-foreground leading-relaxed">
                                    We may update this Privacy Policy from time to time. We will notify you of significant changes by posting a notice in the game or sending an email to your registered address. Your continued use of Myrefell after changes take effect constitutes acceptance of the updated policy.
                                </p>
                            </div>

                            {/* Contact */}
                            <div className="rounded-xl border border-border/50 bg-card/50 p-8">
                                <h2 className="mb-4 font-[Cinzel] text-2xl font-bold text-foreground">Contact Us</h2>
                                <p className="text-muted-foreground leading-relaxed">
                                    If you have questions about this Privacy Policy or wish to exercise your data rights, please contact us at <a href="mailto:support@myrefell.com" className="text-primary hover:underline">support@myrefell.com</a>.
                                </p>
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
                                <span className="font-[Cinzel] text-sm font-bold text-muted-foreground">Myrefell</span>
                            </div>
                            <div className="flex items-center gap-4 text-xs text-muted-foreground">
                                <Link href="/terms" className="transition hover:text-primary">Terms of Service</Link>
                                <span>&middot;</span>
                                <Link href="/privacy" className="text-primary">Privacy Policy</Link>
                                <span>&middot;</span>
                                <Link href="/rules" className="transition hover:text-primary">Game Rules</Link>
                            </div>
                        </div>
                    </div>
                </footer>
            </div>
        </>
    );
}
