import { Head, Link, router, usePage } from "@inertiajs/react";
import { ArrowLeft, Crown, Save } from "lucide-react";
import { useState } from "react";
import { show as showDynasty } from "@/actions/App/Http/Controllers/Admin/DynastyController";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
import { Textarea } from "@/components/ui/textarea";
import AdminLayout from "@/layouts/admin-layout";
import type { BreadcrumbItem, SharedData } from "@/types";

interface DynastyData {
    id: number;
    name: string;
    motto: string | null;
    coat_of_arms: string | null;
    prestige: number;
    current_head_id: number | null;
}

interface DynastyMember {
    id: number;
    first_name: string;
    full_name: string;
    user: { id: number; username: string } | null;
}

interface Props {
    dynasty: DynastyData;
    members: DynastyMember[];
}

interface Errors {
    name?: string;
    motto?: string;
    coat_of_arms?: string;
    prestige?: string;
    current_head_id?: string;
}

export default function Edit({ dynasty, members }: Props) {
    const { errors } = usePage<SharedData & { errors: Errors }>().props;
    const [name, setName] = useState(dynasty.name);
    const [motto, setMotto] = useState(dynasty.motto ?? "");
    const [coatOfArms, setCoatOfArms] = useState(dynasty.coat_of_arms ?? "");
    const [prestige, setPrestige] = useState(dynasty.prestige.toString());
    const [currentHeadId, setCurrentHeadId] = useState(dynasty.current_head_id?.toString() ?? "");
    const [loading, setLoading] = useState(false);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: "Admin", href: "/admin" },
        { title: "Dynasties", href: "/admin/dynasties" },
        { title: dynasty.name, href: `/admin/dynasties/${dynasty.id}` },
        { title: "Edit", href: `/admin/dynasties/${dynasty.id}/edit` },
    ];

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        setLoading(true);
        router.put(
            `/admin/dynasties/${dynasty.id}`,
            {
                name,
                motto: motto || null,
                coat_of_arms: coatOfArms || null,
                prestige: parseInt(prestige, 10),
                current_head_id: currentHeadId ? parseInt(currentHeadId, 10) : null,
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

    // Get members with linked users for head selection
    const membersWithUsers = members.filter((m) => m.user !== null);

    return (
        <AdminLayout breadcrumbs={breadcrumbs}>
            <Head title={`Edit Dynasty: ${dynasty.name}`} />

            <div className="space-y-6 p-6">
                {/* Header */}
                <div className="flex items-center gap-4">
                    <Link href={showDynasty.url(dynasty.id)}>
                        <Button variant="ghost" size="sm">
                            <ArrowLeft className="size-4" />
                            Back
                        </Button>
                    </Link>
                    <div className="flex items-center gap-3">
                        <div className="rounded-lg bg-amber-900/30 p-2">
                            <Crown className="size-6 text-amber-400" />
                        </div>
                        <div>
                            <h1 className="font-[Cinzel] text-2xl font-bold text-stone-100">
                                Edit Dynasty
                            </h1>
                            <p className="text-sm text-stone-400">Editing {dynasty.name}</p>
                        </div>
                    </div>
                </div>

                <div className="mx-auto max-w-2xl space-y-6">
                    {/* Dynasty Information */}
                    <Card className="border-stone-800 bg-stone-900/50">
                        <CardHeader>
                            <CardTitle className="text-stone-100">Dynasty Information</CardTitle>
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
                                    <Label htmlFor="motto" className="text-stone-300">
                                        Motto
                                    </Label>
                                    <Input
                                        id="motto"
                                        type="text"
                                        value={motto}
                                        onChange={(e) => setMotto(e.target.value)}
                                        className="border-stone-700 bg-stone-900/50"
                                        placeholder="Family motto..."
                                        aria-invalid={!!errors.motto}
                                    />
                                    {errors.motto && (
                                        <p className="text-sm text-red-400">{errors.motto}</p>
                                    )}
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="coat_of_arms" className="text-stone-300">
                                        Coat of Arms
                                    </Label>
                                    <Textarea
                                        id="coat_of_arms"
                                        value={coatOfArms}
                                        onChange={(e) => setCoatOfArms(e.target.value)}
                                        className="border-stone-700 bg-stone-900/50"
                                        placeholder="Description of coat of arms..."
                                        rows={3}
                                        aria-invalid={!!errors.coat_of_arms}
                                    />
                                    {errors.coat_of_arms && (
                                        <p className="text-sm text-red-400">
                                            {errors.coat_of_arms}
                                        </p>
                                    )}
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="prestige" className="text-stone-300">
                                        Prestige
                                    </Label>
                                    <Input
                                        id="prestige"
                                        type="number"
                                        min="0"
                                        value={prestige}
                                        onChange={(e) => setPrestige(e.target.value)}
                                        className="border-stone-700 bg-stone-900/50"
                                        aria-invalid={!!errors.prestige}
                                    />
                                    {errors.prestige && (
                                        <p className="text-sm text-red-400">{errors.prestige}</p>
                                    )}
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="current_head_id" className="text-stone-300">
                                        Current Head
                                    </Label>
                                    <Select
                                        value={currentHeadId || "none"}
                                        onValueChange={(value) =>
                                            setCurrentHeadId(value === "none" ? "" : value)
                                        }
                                    >
                                        <SelectTrigger className="border-stone-700 bg-stone-900/50">
                                            <SelectValue placeholder="Select dynasty head..." />
                                        </SelectTrigger>
                                        <SelectContent className="border-stone-700 bg-stone-900">
                                            <SelectItem value="none">No head</SelectItem>
                                            {membersWithUsers.map((member) => (
                                                <SelectItem
                                                    key={member.user!.id}
                                                    value={member.user!.id.toString()}
                                                >
                                                    {member.user!.username} ({member.full_name})
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    {errors.current_head_id && (
                                        <p className="text-sm text-red-400">
                                            {errors.current_head_id}
                                        </p>
                                    )}
                                </div>

                                <div className="flex justify-end gap-3 border-t border-stone-800 pt-6">
                                    <Link href={showDynasty.url(dynasty.id)}>
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
