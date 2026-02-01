// Components
import { Form, Head, Link } from "@inertiajs/react";
import { LoaderCircle } from "lucide-react";
import InputError from "@/components/input-error";
import TextLink from "@/components/text-link";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import AuthLayout from "@/layouts/auth-layout";
import { login } from "@/routes";

export default function ForgotUsername({ status }: { status?: string }) {
    return (
        <AuthLayout title="Forgot username" description="Enter your email to receive your username">
            <Head title="Forgot username" />

            {status && (
                <div className="mb-4 text-center text-sm font-medium text-green-600">{status}</div>
            )}

            <div className="space-y-6">
                <Form action="/forgot-username" method="post">
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="email">Email address</Label>
                                <Input
                                    id="email"
                                    type="email"
                                    name="email"
                                    autoComplete="off"
                                    autoFocus
                                    placeholder="email@example.com"
                                />

                                <InputError message={errors.email} />
                            </div>

                            <div className="my-6 flex items-center justify-start">
                                <Button type="submit" className="w-full" disabled={processing}>
                                    {processing && (
                                        <LoaderCircle className="h-4 w-4 animate-spin" />
                                    )}
                                    Email my username
                                </Button>
                            </div>
                        </>
                    )}
                </Form>

                <div className="flex flex-col items-center gap-2 text-center text-sm text-muted-foreground">
                    <div className="space-x-1">
                        <span>Remember your username?</span>
                        <TextLink href={login()}>Log in</TextLink>
                    </div>
                    <div className="space-x-1">
                        <span>Forgot your password?</span>
                        <TextLink href="/forgot-password">Reset it here</TextLink>
                    </div>
                </div>
            </div>
        </AuthLayout>
    );
}
