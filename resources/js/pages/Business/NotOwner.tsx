import { Head, router } from '@inertiajs/react';
import { Lock } from 'lucide-react';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

interface PageProps {
    business_name: string;
}

export default function NotOwner({ business_name }: PageProps) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Businesses', href: '/businesses' },
        { title: 'Access Denied', href: '#' },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Access Denied" />
            <div className="flex h-full flex-1 flex-col items-center justify-center p-4">
                <div className="text-center">
                    <Lock className="mx-auto h-16 w-16 text-stone-600" />
                    <h1 className="mt-4 font-pixel text-2xl text-red-400">Access Denied</h1>
                    <p className="mt-2 font-pixel text-sm text-stone-400">
                        You do not own {business_name} and cannot manage it.
                    </p>
                    <button
                        onClick={() => router.get('/businesses')}
                        className="mt-6 rounded-lg border-2 border-amber-500 bg-amber-900/30 px-6 py-2 font-pixel text-sm text-amber-300 transition hover:bg-amber-800/50"
                    >
                        View My Businesses
                    </button>
                </div>
            </div>
        </AppLayout>
    );
}
