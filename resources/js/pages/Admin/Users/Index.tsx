import { Head, Link, router } from '@inertiajs/react';
import {
    ChevronLeft,
    ChevronRight,
    Eye,
    Search,
    Shield,
    UserX,
    Users,
} from 'lucide-react';
import { useState } from 'react';
import { show as showUser } from '@/actions/App/Http/Controllers/Admin/UserController';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import AdminLayout from '@/layouts/admin-layout';
import type { BreadcrumbItem } from '@/types';

interface User {
    id: number;
    username: string;
    email: string;
    is_admin: boolean;
    is_banned: boolean;
    banned_at: string | null;
    bans_count: number;
    created_at: string;
    email_verified_at: string | null;
}

interface PaginatedUsers {
    data: User[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number;
    to: number;
    links: Array<{
        url: string | null;
        label: string;
        active: boolean;
    }>;
}

interface Filters {
    search: string;
    banned: string;
    admin: string;
}

interface Props {
    users: PaginatedUsers;
    filters: Filters;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Admin', href: '/admin' },
    { title: 'Users', href: '/admin/users' },
];

export default function Index({ users, filters }: Props) {
    const [search, setSearch] = useState(filters.search);

    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault();
        router.get('/admin/users', { ...filters, search }, { preserveState: true });
    };

    const handleFilterChange = (key: string, value: string) => {
        router.get('/admin/users', { ...filters, [key]: value }, { preserveState: true });
    };

    const formatDate = (dateStr: string) => {
        return new Date(dateStr).toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
        });
    };

    return (
        <AdminLayout breadcrumbs={breadcrumbs}>
            <Head title="Manage Users" />

            <div className="space-y-6 p-6">
                {/* Header */}
                <div className="flex items-center gap-3">
                    <div className="rounded-lg bg-blue-900/30 p-2">
                        <Users className="size-6 text-blue-400" />
                    </div>
                    <div>
                        <h1 className="font-[Cinzel] text-2xl font-bold text-stone-100">
                            Manage Users
                        </h1>
                        <p className="text-sm text-stone-400">
                            {users.total.toLocaleString()} total users
                        </p>
                    </div>
                </div>

                {/* Filters */}
                <Card className="border-stone-800 bg-stone-900/50">
                    <CardContent className="pt-6">
                        <div className="flex flex-wrap items-center gap-4">
                            {/* Search */}
                            <form onSubmit={handleSearch} className="flex-1">
                                <div className="relative">
                                    <Search className="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-stone-500" />
                                    <Input
                                        type="text"
                                        placeholder="Search by username or email..."
                                        value={search}
                                        onChange={(e) => setSearch(e.target.value)}
                                        className="pl-10 border-stone-700 bg-stone-900/50"
                                    />
                                </div>
                            </form>

                            {/* Banned Filter */}
                            <Select
                                value={filters.banned || 'all'}
                                onValueChange={(value) =>
                                    handleFilterChange('banned', value === 'all' ? '' : value)
                                }
                            >
                                <SelectTrigger className="w-[150px] border-stone-700 bg-stone-900/50">
                                    <SelectValue placeholder="Ban status" />
                                </SelectTrigger>
                                <SelectContent className="border-stone-700 bg-stone-900">
                                    <SelectItem value="all">All users</SelectItem>
                                    <SelectItem value="true">Banned only</SelectItem>
                                    <SelectItem value="false">Active only</SelectItem>
                                </SelectContent>
                            </Select>

                            {/* Admin Filter */}
                            <Select
                                value={filters.admin || 'all'}
                                onValueChange={(value) =>
                                    handleFilterChange('admin', value === 'all' ? '' : value)
                                }
                            >
                                <SelectTrigger className="w-[150px] border-stone-700 bg-stone-900/50">
                                    <SelectValue placeholder="Admin status" />
                                </SelectTrigger>
                                <SelectContent className="border-stone-700 bg-stone-900">
                                    <SelectItem value="all">All users</SelectItem>
                                    <SelectItem value="true">Admins only</SelectItem>
                                    <SelectItem value="false">Non-admins</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </CardContent>
                </Card>

                {/* Users Table */}
                <Card className="border-stone-800 bg-stone-900/50">
                    <CardHeader>
                        <CardTitle className="text-stone-100">Users</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="overflow-x-auto">
                            <table className="w-full">
                                <thead>
                                    <tr className="border-b border-stone-800">
                                        <th className="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-stone-400">
                                            User
                                        </th>
                                        <th className="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-stone-400">
                                            Email
                                        </th>
                                        <th className="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-stone-400">
                                            Status
                                        </th>
                                        <th className="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-stone-400">
                                            Joined
                                        </th>
                                        <th className="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-stone-400">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-stone-800">
                                    {users.data.map((user) => (
                                        <tr
                                            key={user.id}
                                            className="hover:bg-stone-800/50 transition"
                                        >
                                            <td className="px-4 py-3">
                                                <div className="flex items-center gap-2">
                                                    <span className="font-medium text-stone-100">
                                                        {user.username}
                                                    </span>
                                                    {user.is_admin && (
                                                        <Badge
                                                            variant="secondary"
                                                            className="bg-purple-900/30 text-purple-400"
                                                        >
                                                            <Shield className="size-3" />
                                                            Admin
                                                        </Badge>
                                                    )}
                                                </div>
                                            </td>
                                            <td className="px-4 py-3 text-stone-400">
                                                {user.email}
                                            </td>
                                            <td className="px-4 py-3">
                                                <div className="flex items-center gap-2">
                                                    {user.is_banned ? (
                                                        <Badge
                                                            variant="destructive"
                                                            className="bg-red-900/30 text-red-400"
                                                        >
                                                            <UserX className="size-3" />
                                                            Banned
                                                        </Badge>
                                                    ) : (
                                                        <Badge
                                                            variant="secondary"
                                                            className="bg-green-900/30 text-green-400"
                                                        >
                                                            Active
                                                        </Badge>
                                                    )}
                                                    {user.bans_count > 0 && !user.is_banned && (
                                                        <span className="text-xs text-stone-500">
                                                            ({user.bans_count} prior{' '}
                                                            {user.bans_count === 1 ? 'ban' : 'bans'})
                                                        </span>
                                                    )}
                                                </div>
                                            </td>
                                            <td className="px-4 py-3 text-stone-400">
                                                {formatDate(user.created_at)}
                                            </td>
                                            <td className="px-4 py-3">
                                                <Link href={showUser.url(user.id)}>
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        className="text-stone-400 hover:text-stone-100"
                                                    >
                                                        <Eye className="size-4" />
                                                        View
                                                    </Button>
                                                </Link>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>

                        {/* Pagination */}
                        {users.last_page > 1 && (
                            <div className="mt-6 flex items-center justify-between border-t border-stone-800 pt-6">
                                <p className="text-sm text-stone-400">
                                    Showing {users.from} to {users.to} of {users.total} users
                                </p>
                                <div className="flex gap-2">
                                    {users.links.map((link, index) => {
                                        if (link.label.includes('Previous')) {
                                            return (
                                                <Button
                                                    key={index}
                                                    variant="outline"
                                                    size="sm"
                                                    disabled={!link.url}
                                                    onClick={() =>
                                                        link.url && router.get(link.url)
                                                    }
                                                    className="border-stone-700"
                                                >
                                                    <ChevronLeft className="size-4" />
                                                </Button>
                                            );
                                        }
                                        if (link.label.includes('Next')) {
                                            return (
                                                <Button
                                                    key={index}
                                                    variant="outline"
                                                    size="sm"
                                                    disabled={!link.url}
                                                    onClick={() =>
                                                        link.url && router.get(link.url)
                                                    }
                                                    className="border-stone-700"
                                                >
                                                    <ChevronRight className="size-4" />
                                                </Button>
                                            );
                                        }
                                        return (
                                            <Button
                                                key={index}
                                                variant={link.active ? 'default' : 'outline'}
                                                size="sm"
                                                onClick={() =>
                                                    link.url && router.get(link.url)
                                                }
                                                className={
                                                    link.active
                                                        ? ''
                                                        : 'border-stone-700'
                                                }
                                            >
                                                {link.label}
                                            </Button>
                                        );
                                    })}
                                </div>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AdminLayout>
    );
}
