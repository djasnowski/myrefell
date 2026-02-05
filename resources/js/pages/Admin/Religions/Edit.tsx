import { Head, Link, router, usePage } from "@inertiajs/react";
import { ArrowLeft, Church, Save, Skull } from "lucide-react";
import { useState } from "react";
import { show as showReligion } from "@/actions/App/Http/Controllers/Admin/ReligionController";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Checkbox } from "@/components/ui/checkbox";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import AdminLayout from "@/layouts/admin-layout";
import { cn } from "@/lib/utils";
import type { BreadcrumbItem, SharedData } from "@/types";

interface ReligionData {
    id: number;
    name: string;
    description: string | null;
    type: "cult" | "religion";
    icon: string | null;
    color: string | null;
    is_public: boolean;
    is_active: boolean;
    member_limit: number | null;
}

interface Props {
    religion: ReligionData;
}

interface Errors {
    name?: string;
    description?: string;
    icon?: string;
    color?: string;
    is_public?: string;
    is_active?: string;
    member_limit?: string;
}

export default function Edit({ religion }: Props) {
    const { errors } = usePage<SharedData & { errors: Errors }>().props;
    const [name, setName] = useState(religion.name);
    const [description, setDescription] = useState(religion.description ?? "");
    const [icon, setIcon] = useState(religion.icon ?? "");
    const [color, setColor] = useState(religion.color ?? "");
    const [isPublic, setIsPublic] = useState(religion.is_public);
    const [isActive, setIsActive] = useState(religion.is_active);
    const [memberLimit, setMemberLimit] = useState(religion.member_limit?.toString() ?? "");
    const [loading, setLoading] = useState(false);

    const isCult = religion.type === "cult";

    const breadcrumbs: BreadcrumbItem[] = [
        { title: "Admin", href: "/admin" },
        { title: "Religions", href: "/admin/religions" },
        { title: religion.name, href: `/admin/religions/${religion.id}` },
        { title: "Edit", href: `/admin/religions/${religion.id}/edit` },
    ];

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        setLoading(true);
        router.put(
            `/admin/religions/${religion.id}`,
            {
                name,
                description: description || null,
                icon: icon || null,
                color: color || null,
                is_public: isPublic,
                is_active: isActive,
                member_limit: memberLimit ? parseInt(memberLimit, 10) : null,
            },
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
            <Head title={`Edit ${isCult ? "Cult" : "Religion"}: ${religion.name}`} />

            <div className="space-y-6 p-6">
                {/* Header */}
                <div className="flex items-center gap-4">
                    <Link href={showReligion.url(religion.id)}>
                        <Button variant="ghost" size="sm">
                            <ArrowLeft className="size-4" />
                            Back
                        </Button>
                    </Link>
                    <div className="flex items-center gap-3">
                        <div
                            className={cn(
                                "rounded-lg p-2",
                                isCult ? "bg-red-900/30" : "bg-purple-900/30",
                            )}
                        >
                            {isCult ? (
                                <Skull className="size-6 text-red-400" />
                            ) : (
                                <Church className="size-6 text-purple-400" />
                            )}
                        </div>
                        <div>
                            <div className="flex items-center gap-2">
                                <h1 className="font-[Cinzel] text-2xl font-bold text-stone-100">
                                    Edit {isCult ? "Cult" : "Religion"}
                                </h1>
                                <Badge
                                    variant="secondary"
                                    className={
                                        isCult
                                            ? "bg-red-900/30 text-red-400"
                                            : "bg-purple-900/30 text-purple-400"
                                    }
                                >
                                    {isCult ? "Cult" : "Religion"}
                                </Badge>
                            </div>
                            <p className="text-sm text-stone-400">Editing {religion.name}</p>
                        </div>
                    </div>
                </div>

                <div className="mx-auto max-w-2xl space-y-6">
                    {/* Basic Information */}
                    <Card className="border-stone-800 bg-stone-900/50">
                        <CardHeader>
                            <CardTitle className="text-stone-100">Basic Information</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <form onSubmit={handleSubmit} className="space-y-6">
                                <div className="space-y-2">
                                    <Label htmlFor="name" className="text-stone-300">
                                        Name
                                    </Label>
                                    <Input
                                        id="name"
                                        type="text"
                                        value={name}
                                        onChange={(e) => setName(e.target.value)}
                                        className="border-stone-700 bg-stone-900/50"
                                        aria-invalid={!!errors.name}
                                    />
                                    {errors.name && (
                                        <p className="text-sm text-red-400">{errors.name}</p>
                                    )}
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="description" className="text-stone-300">
                                        Description
                                    </Label>
                                    <Textarea
                                        id="description"
                                        value={description}
                                        onChange={(e) => setDescription(e.target.value)}
                                        className="border-stone-700 bg-stone-900/50"
                                        placeholder="Description of the religion..."
                                        rows={4}
                                        aria-invalid={!!errors.description}
                                    />
                                    {errors.description && (
                                        <p className="text-sm text-red-400">{errors.description}</p>
                                    )}
                                </div>

                                <div className="grid grid-cols-2 gap-4">
                                    <div className="space-y-2">
                                        <Label htmlFor="icon" className="text-stone-300">
                                            Icon
                                        </Label>
                                        <Input
                                            id="icon"
                                            type="text"
                                            value={icon}
                                            onChange={(e) => setIcon(e.target.value)}
                                            className="border-stone-700 bg-stone-900/50"
                                            placeholder="Icon name..."
                                            aria-invalid={!!errors.icon}
                                        />
                                        {errors.icon && (
                                            <p className="text-sm text-red-400">{errors.icon}</p>
                                        )}
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="color" className="text-stone-300">
                                            Color
                                        </Label>
                                        <div className="flex gap-2">
                                            <Input
                                                id="color"
                                                type="text"
                                                value={color}
                                                onChange={(e) => setColor(e.target.value)}
                                                className="border-stone-700 bg-stone-900/50 flex-1"
                                                placeholder="#hex or color name..."
                                                aria-invalid={!!errors.color}
                                            />
                                            {color && (
                                                <div
                                                    className="size-10 rounded border border-stone-700"
                                                    style={{ backgroundColor: color }}
                                                />
                                            )}
                                        </div>
                                        {errors.color && (
                                            <p className="text-sm text-red-400">{errors.color}</p>
                                        )}
                                    </div>
                                </div>

                                {isCult && (
                                    <div className="space-y-2">
                                        <Label htmlFor="member_limit" className="text-stone-300">
                                            Member Limit
                                        </Label>
                                        <Input
                                            id="member_limit"
                                            type="number"
                                            min="1"
                                            value={memberLimit}
                                            onChange={(e) => setMemberLimit(e.target.value)}
                                            className="border-stone-700 bg-stone-900/50"
                                            placeholder="No limit"
                                            aria-invalid={!!errors.member_limit}
                                        />
                                        {errors.member_limit && (
                                            <p className="text-sm text-red-400">
                                                {errors.member_limit}
                                            </p>
                                        )}
                                    </div>
                                )}

                                {/* Toggles */}
                                <div className="space-y-4 border-t border-stone-800 pt-6">
                                    <div className="flex items-center gap-3">
                                        <Checkbox
                                            id="is_public"
                                            checked={isPublic}
                                            onCheckedChange={(checked) =>
                                                setIsPublic(checked === true)
                                            }
                                        />
                                        <div className="space-y-0.5">
                                            <Label
                                                htmlFor="is_public"
                                                className="text-stone-300 cursor-pointer"
                                            >
                                                Public
                                            </Label>
                                            <p className="text-xs text-stone-500">
                                                Anyone can see and join this{" "}
                                                {isCult ? "cult" : "religion"}
                                            </p>
                                        </div>
                                    </div>

                                    <div className="flex items-center gap-3">
                                        <Checkbox
                                            id="is_active"
                                            checked={isActive}
                                            onCheckedChange={(checked) =>
                                                setIsActive(checked === true)
                                            }
                                        />
                                        <div className="space-y-0.5">
                                            <Label
                                                htmlFor="is_active"
                                                className="text-stone-300 cursor-pointer"
                                            >
                                                Active
                                            </Label>
                                            <p className="text-xs text-stone-500">
                                                Inactive {isCult ? "cults" : "religions"} are hidden
                                                from players
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <div className="flex justify-end gap-3 border-t border-stone-800 pt-6">
                                    <Link href={showReligion.url(religion.id)}>
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
