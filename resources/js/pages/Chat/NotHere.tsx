import { Head, router, usePage } from '@inertiajs/react';
import { MapPin, MessageCircle } from 'lucide-react';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

interface PageProps {
    message: string;
    [key: string]: unknown;
}

export default function ChatNotHere() {
    const { message } = usePage<PageProps>().props;

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Chat', href: '#' },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Chat" />
            <div className="flex h-full flex-1 flex-col items-center justify-center p-4">
                <div className="rounded-xl border-2 border-stone-700 bg-stone-800/50 p-8 text-center">
                    <div className="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-stone-700">
                        <MessageCircle className="h-8 w-8 text-stone-500" />
                    </div>
                    <h1 className="mb-2 font-pixel text-xl text-stone-300">Cannot Access Chat</h1>
                    <p className="mb-6 font-pixel text-xs text-stone-500">{message}</p>
                    <button
                        onClick={() => router.visit('/dashboard')}
                        className="flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 font-pixel text-xs text-stone-100 transition hover:bg-blue-500"
                    >
                        <MapPin className="h-4 w-4" />
                        Return to Map
                    </button>
                </div>
            </div>
        </AppLayout>
    );
}
