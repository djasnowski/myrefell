import { Head, usePage } from '@inertiajs/react';
import { Castle } from 'lucide-react';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

interface PageProps {
    message: string;
    [key: string]: unknown;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Dungeons', href: '/dungeons' },
];

export default function DungeonsNotAvailable() {
    const { message } = usePage<PageProps>().props;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dungeons Unavailable" />
            <div className="flex h-full flex-1 flex-col items-center justify-center p-4">
                <Castle className="mb-4 h-16 w-16 text-stone-600" />
                <h1 className="mb-2 font-pixel text-xl text-amber-400">Dungeons Unavailable</h1>
                <p className="font-pixel text-sm text-stone-400">{message}</p>
            </div>
        </AppLayout>
    );
}
