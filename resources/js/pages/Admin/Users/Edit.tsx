import { Head, Link, router, usePage } from "@inertiajs/react";
import { ArrowLeft, Save, User } from "lucide-react";
import { useState } from "react";
import { show as showUser } from "@/actions/App/Http/Controllers/Admin/UserController";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import AdminLayout from "@/layouts/admin-layout";
import type { BreadcrumbItem, SharedData } from "@/types";

interface UserData {
    id: number;
    username: string;
    email: string;
    is_admin: boolean;
}

interface Props {
    user: UserData;
}

interface Errors {
    username?: string;
    email?: string;
}

export default function Edit({ user }: Props) {
    const { errors } = usePage<SharedData & { errors: Errors }>().props;
    const [username, setUsername] = useState(user.username);
    const [email, setEmail] = useState(user.email);
    const [loading, setLoading] = useState(false);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: "Admin", href: "/admin" },
        { title: "Users", href: "/admin/users" },
        { title: user.username, href: `/admin/users/${user.id}` },
        { title: "Edit", href: `/admin/users/${user.id}/edit` },
    ];

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        setLoading(true);
        router.put(
            `/admin/users/${user.id}`,
            { username, email },
            {
                preserveScroll: true,
                onSuccess: () => {
                    router.reload();
                },
                onFinish: () => setLoading(false),
            },
        );
    };

    return (
        <AdminLayout breadcrumbs={breadcrumbs}>
            <Head title={`Edit User: ${user.username}`} />

            <div className="space-y-6 p-6">
                {/* Header */}
                <div className="flex items-center gap-4">
                    <Link href={showUser.url(user.id)}>
                        <Button variant="ghost" size="sm">
                            <ArrowLeft className="size-4" />
                            Back
                        </Button>
                    </Link>
                    <div className="flex items-center gap-3">
                        <div className="rounded-lg bg-blue-900/30 p-2">
                            <User className="size-6 text-blue-400" />
                        </div>
                        <div>
                            <h1 className="font-[Cinzel] text-2xl font-bold text-stone-100">
                                Edit User
                            </h1>
                            <p className="text-sm text-stone-400">Editing {user.username}</p>
                        </div>
                    </div>
                </div>

                <div className="mx-auto max-w-2xl">
                    <Card className="border-stone-800 bg-stone-900/50">
                        <CardHeader>
                            <CardTitle className="text-stone-100">User Information</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <form onSubmit={handleSubmit} className="space-y-6">
                                <div className="space-y-2">
                                    <Label htmlFor="username" className="text-stone-300">
                                        Username
                                    </Label>
                                    <Input
                                        id="username"
                                        type="text"
                                        value={username}
                                        onChange={(e) => setUsername(e.target.value)}
                                        className="border-stone-700 bg-stone-900/50"
                                        aria-invalid={!!errors.username}
                                    />
                                    {errors.username && (
                                        <p className="text-sm text-red-400">{errors.username}</p>
                                    )}
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="email" className="text-stone-300">
                                        Email
                                    </Label>
                                    <Input
                                        id="email"
                                        type="email"
                                        value={email}
                                        onChange={(e) => setEmail(e.target.value)}
                                        className="border-stone-700 bg-stone-900/50"
                                        aria-invalid={!!errors.email}
                                    />
                                    {errors.email && (
                                        <p className="text-sm text-red-400">{errors.email}</p>
                                    )}
                                </div>

                                <div className="flex justify-end gap-3 border-t border-stone-800 pt-6">
                                    <Link href={showUser.url(user.id)}>
                                        <Button
                                            type="button"
                                            variant="outline"
                                            className="border-stone-700"
                                        >
                                            Cancel
                                        </Button>
                                    </Link>
                                    <Button type="submit" disabled={loading}>
                                        <Save className="size-4" />
                                        {loading ? "Saving..." : "Save Changes"}
                                    </Button>
                                </div>
                            </form>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AdminLayout>
    );
}
