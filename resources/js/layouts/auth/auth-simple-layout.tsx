import { Link } from '@inertiajs/react';
import AppLogoIcon from '@/components/app-logo-icon';
import { home } from '@/routes';
import type { AuthLayoutProps } from '@/types';

export default function AuthSimpleLayout({
    children,
    title,
    description,
}: AuthLayoutProps) {
    return (
        <div className="relative flex min-h-svh flex-col items-center justify-center p-6 md:p-10 overflow-hidden">
            {/* Decorative background with radial gradient */}
            <div className="absolute inset-0 bg-background">
                <div className="absolute inset-0 bg-[radial-gradient(ellipse_at_top,_var(--tw-gradient-stops))] from-primary/10 via-background to-background" />
                <div className="absolute inset-0 bg-[radial-gradient(ellipse_at_bottom_right,_var(--tw-gradient-stops))] from-accent/5 via-transparent to-transparent" />
            </div>

            {/* Subtle grid pattern overlay */}
            <div
                className="absolute inset-0 opacity-[0.03]"
                style={{
                    backgroundImage: `
                        linear-gradient(var(--primary) 1px, transparent 1px),
                        linear-gradient(90deg, var(--primary) 1px, transparent 1px)
                    `,
                    backgroundSize: '40px 40px'
                }}
            />

            {/* Main content */}
            <div className="relative z-10 w-full max-w-sm">
                {/* Decorative frame */}
                <div className="relative">
                    {/* Corner decorations */}
                    <div className="absolute -top-2 -left-2 w-6 h-6 border-t-2 border-l-2 border-primary/60" />
                    <div className="absolute -top-2 -right-2 w-6 h-6 border-t-2 border-r-2 border-primary/60" />
                    <div className="absolute -bottom-2 -left-2 w-6 h-6 border-b-2 border-l-2 border-primary/60" />
                    <div className="absolute -bottom-2 -right-2 w-6 h-6 border-b-2 border-r-2 border-primary/60" />

                    {/* Card with glow effect */}
                    <div className="relative bg-card/80 backdrop-blur-sm border border-border/50 p-8 shadow-lg shadow-primary/5">
                        <div className="flex flex-col gap-8">
                            <div className="flex flex-col items-center gap-4">
                                <Link
                                    href={home()}
                                    className="group flex flex-col items-center gap-2 font-medium"
                                >
                                    <div className="relative mb-1 flex h-12 w-12 items-center justify-center">
                                        <AppLogoIcon className="size-10 fill-current text-primary transition-transform group-hover:scale-110" />
                                    </div>
                                    <span className="sr-only">{title}</span>
                                </Link>

                                <div className="space-y-3 text-center">
                                    <h1 className="text-lg font-medium tracking-wide text-primary">
                                        {title}
                                    </h1>
                                    <p className="text-center text-sm text-muted-foreground">
                                        {description}
                                    </p>
                                </div>

                                {/* Decorative divider */}
                                <div className="flex items-center gap-3 w-full">
                                    <div className="flex-1 h-px bg-gradient-to-r from-transparent via-border to-transparent" />
                                    <div className="w-2 h-2 rotate-45 border border-primary/40 bg-primary/10" />
                                    <div className="flex-1 h-px bg-gradient-to-r from-transparent via-border to-transparent" />
                                </div>
                            </div>
                            {children}
                        </div>
                    </div>
                </div>
            </div>

            {/* Bottom decorative element */}
            <div className="relative z-10 mt-8 flex items-center gap-2 text-muted-foreground/50">
                <div className="w-8 h-px bg-gradient-to-r from-transparent to-border" />
                <div className="w-1.5 h-1.5 rotate-45 bg-primary/30" />
                <div className="w-8 h-px bg-gradient-to-l from-transparent to-border" />
            </div>
        </div>
    );
}
