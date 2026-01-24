import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';
import { Anchor, ArrowLeft } from 'lucide-react';

interface PageProps {
    message: string;
    [key: string]: unknown;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Harbor', href: '#' },
];

export default function PortNotHere() {
    const { message } = usePage<PageProps>().props;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Harbor - Not Available" />
            <div className="flex h-full flex-1 flex-col items-center justify-center p-4">
                <div className="max-w-md text-center">
                    <div className="mx-auto mb-4 flex h-20 w-20 items-center justify-center rounded-full bg-stone-800">
                        <Anchor className="h-10 w-10 text-stone-500" />
                    </div>
                    <h1 className="mb-2 font-pixel text-xl text-stone-300">Harbor Not Available</h1>
                    <p className="mb-6 font-pixel text-sm text-stone-500">{message}</p>
                    <Link
                        href="/dashboard"
                        className="inline-flex items-center gap-2 rounded-lg border-2 border-stone-600 bg-stone-800 px-4 py-2 font-pixel text-xs text-stone-300 transition hover:bg-stone-700"
                    >
                        <ArrowLeft className="h-4 w-4" />
                        Return to Map
                    </Link>
                </div>
            </div>
        </AppLayout>
    );
}
