import { Head, router } from '@inertiajs/react';
import { MapPin } from 'lucide-react';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

interface PageProps {
    location: string;
}

export default function NotHere({ location }: PageProps) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Businesses', href: '#' },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Not At Location" />
            <div className="flex h-full flex-1 flex-col items-center justify-center p-4">
                <div className="text-center">
                    <MapPin className="mx-auto h-16 w-16 text-stone-600" />
                    <h1 className="mt-4 font-pixel text-2xl text-amber-400">Not At Location</h1>
                    <p className="mt-2 font-pixel text-sm text-stone-400">
                        You must be in {location} to view and manage businesses here.
                    </p>
                    <button
                        onClick={() => router.get('/dashboard')}
                        className="mt-6 rounded-lg border-2 border-amber-500 bg-amber-900/30 px-6 py-2 font-pixel text-sm text-amber-300 transition hover:bg-amber-800/50"
                    >
                        Return to Dashboard
                    </button>
                </div>
            </div>
        </AppLayout>
    );
}
