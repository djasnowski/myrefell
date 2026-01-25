import { Head, Link, usePage } from '@inertiajs/react';
import { MapPin, Sword } from 'lucide-react';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

interface PageProps {
    message: string;
    [key: string]: unknown;
}

export default function TrainingNotAvailable() {
    const { message } = usePage<PageProps>().props;

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Training Grounds', href: '/training' },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Training Unavailable" />
            <div className="flex h-full flex-1 items-center justify-center p-4">
                <div className="max-w-md text-center">
                    <div className="mx-auto mb-6 flex h-20 w-20 items-center justify-center rounded-full bg-stone-800/50">
                        <Sword className="h-10 w-10 text-stone-500" />
                    </div>
                    <h1 className="mb-2 font-pixel text-xl text-stone-300">No Training Grounds Here</h1>
                    <p className="mb-6 font-pixel text-xs text-stone-500">{message}</p>
                    <Link
                        href="/travel"
                        className="inline-flex items-center gap-2 rounded-lg border border-amber-600 bg-amber-900/30 px-4 py-2 font-pixel text-xs text-amber-300 transition hover:bg-amber-800/50"
                    >
                        <MapPin className="h-4 w-4" />
                        Travel to Find Training
                    </Link>
                </div>
            </div>
        </AppLayout>
    );
}
