import { Head, Link, usePage } from '@inertiajs/react';
import { Sword } from 'lucide-react';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

interface PageProps {
    message: string;
    [key: string]: unknown;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Combat', href: '/combat' },
];

export default function CombatNotAvailable() {
    const { message } = usePage<PageProps>().props;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Combat Unavailable" />
            <div className="flex h-full flex-1 flex-col items-center justify-center p-4">
                <Sword className="mb-4 h-16 w-16 text-stone-600" />
                <h1 className="mb-2 font-pixel text-xl text-amber-400">Combat Unavailable</h1>
                <p className="mb-6 text-center font-pixel text-sm text-stone-400">{message}</p>
                <Link
                    href="/dashboard"
                    className="rounded-lg border border-stone-600 bg-stone-800/50 px-6 py-2 font-pixel text-sm text-stone-300 transition hover:bg-stone-700/50"
                >
                    Return to Dashboard
                </Link>
            </div>
        </AppLayout>
    );
}
