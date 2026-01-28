import { Form, Head } from '@inertiajs/react';
import { Mars, Venus } from 'lucide-react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import AuthLayout from '@/layouts/auth-layout';
import { cn } from '@/lib/utils';
import { login } from '@/routes';
import { store } from '@/routes/register';

export default function Register() {
    const [gender, setGender] = useState<string>('');

    return (
        <AuthLayout
            title="Create your character"
            description="Enter your details below to join Myrefell"
        >
            <Head title="Register" />
            <Form
                {...store.form()}
                resetOnSuccess={['password', 'password_confirmation']}
                disableWhileProcessing
                className="flex flex-col gap-6"
            >
                {({ processing, errors }) => (
                    <>
                        <div className="grid gap-6">
                            <div className="grid gap-2">
                                <Label htmlFor="username">Username</Label>
                                <Input
                                    id="username"
                                    type="text"
                                    required
                                    autoFocus
                                    tabIndex={1}
                                    autoComplete="username"
                                    name="username"
                                    placeholder="Choose a username"
                                    minLength={3}
                                    maxLength={20}
                                />
                                <p className="text-xs text-muted-foreground">
                                    3-20 characters, letters, numbers, and underscores only
                                </p>
                                <InputError
                                    message={errors.username}
                                    className="mt-2"
                                />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="email">Email address</Label>
                                <Input
                                    id="email"
                                    type="email"
                                    required
                                    tabIndex={2}
                                    autoComplete="email"
                                    name="email"
                                    placeholder="email@example.com"
                                />
                                <InputError message={errors.email} />
                            </div>

                            <div className="grid gap-2">
                                <Label>Gender</Label>
                                <input type="hidden" name="gender" value={gender} />
                                <div className="grid grid-cols-2 gap-2">
                                    <button
                                        type="button"
                                        tabIndex={3}
                                        onClick={() => setGender('male')}
                                        className={cn(
                                            'flex items-center justify-center gap-2 rounded-md border px-4 py-2.5 text-sm font-medium transition-colors',
                                            gender === 'male'
                                                ? 'border-primary bg-primary/10 text-primary'
                                                : 'border-input bg-white/5 dark:bg-white/10 text-muted-foreground hover:bg-accent hover:text-accent-foreground'
                                        )}
                                    >
                                        <Mars className="size-4" />
                                        Male
                                    </button>
                                    <button
                                        type="button"
                                        tabIndex={3}
                                        onClick={() => setGender('female')}
                                        className={cn(
                                            'flex items-center justify-center gap-2 rounded-md border px-4 py-2.5 text-sm font-medium transition-colors',
                                            gender === 'female'
                                                ? 'border-primary bg-primary/10 text-primary'
                                                : 'border-input bg-white/5 dark:bg-white/10 text-muted-foreground hover:bg-accent hover:text-accent-foreground'
                                        )}
                                    >
                                        <Venus className="size-4" />
                                        Female
                                    </button>
                                </div>
                                <InputError message={errors.gender} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="password">Password</Label>
                                <Input
                                    id="password"
                                    type="password"
                                    required
                                    tabIndex={4}
                                    autoComplete="new-password"
                                    name="password"
                                    placeholder="Password"
                                />
                                <InputError message={errors.password} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="password_confirmation">
                                    Confirm password
                                </Label>
                                <Input
                                    id="password_confirmation"
                                    type="password"
                                    required
                                    tabIndex={5}
                                    autoComplete="new-password"
                                    name="password_confirmation"
                                    placeholder="Confirm password"
                                />
                                <InputError
                                    message={errors.password_confirmation}
                                />
                            </div>

                            <Button
                                type="submit"
                                className="mt-2 w-full"
                                tabIndex={6}
                                data-test="register-user-button"
                            >
                                {processing && <Spinner />}
                                Create character
                            </Button>
                        </div>

                        <div className="text-center text-sm text-muted-foreground">
                            Already have an account?{' '}
                            <TextLink href={login()} tabIndex={7}>
                                Log in
                            </TextLink>
                        </div>
                    </>
                )}
            </Form>
        </AuthLayout>
    );
}
