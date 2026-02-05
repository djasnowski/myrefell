import { Head, useForm, usePage } from "@inertiajs/react";
import { AlertTriangle, LogOut, Send } from "lucide-react";
import { FormEvent, useState } from "react";

interface BanInfo {
    reason: string;
    banned_at: string;
}

interface PageProps {
    ban: BanInfo;
    username: string;
    flash?: {
        success?: string;
        error?: string;
    };
    [key: string]: unknown;
}

export default function Banned() {
    const { ban, username, flash } = usePage<PageProps>().props;
    const [showAppealForm, setShowAppealForm] = useState(false);

    const { data, setData, post, processing, errors } = useForm({
        appeal: "",
    });

    const handleSubmit = (e: FormEvent) => {
        e.preventDefault();
        post("/banned/appeal", {
            onSuccess: () => {
                setShowAppealForm(false);
            },
        });
    };

    const bannedDate = new Date(ban.banned_at).toLocaleDateString("en-US", {
        year: "numeric",
        month: "long",
        day: "numeric",
        hour: "2-digit",
        minute: "2-digit",
    });

    return (
        <>
            <Head title="Account Suspended" />
            <div className="min-h-screen bg-stone-950 flex items-center justify-center p-4">
                <div className="max-w-lg w-full">
                    {/* Header */}
                    <div className="text-center mb-8">
                        <div className="inline-flex items-center justify-center w-20 h-20 rounded-full bg-red-900/30 border-2 border-red-500/50 mb-4">
                            <AlertTriangle className="w-10 h-10 text-red-400" />
                        </div>
                        <h1 className="font-pixel text-3xl text-red-400 mb-2">Account Suspended</h1>
                        <p className="font-pixel text-stone-400">Hello, {username}</p>
                    </div>

                    {/* Ban Details Card */}
                    <div className="bg-stone-900/50 border border-stone-700 rounded-lg p-6 mb-6">
                        <h2 className="font-pixel text-lg text-stone-200 mb-4">Ban Details</h2>

                        <div className="space-y-4">
                            <div>
                                <label className="font-pixel text-xs text-stone-500 uppercase">
                                    Reason
                                </label>
                                <p className="font-pixel text-stone-300 mt-1">{ban.reason}</p>
                            </div>

                            <div>
                                <label className="font-pixel text-xs text-stone-500 uppercase">
                                    Banned On
                                </label>
                                <p className="font-pixel text-sm text-stone-400 mt-1">
                                    {bannedDate}
                                </p>
                            </div>
                        </div>
                    </div>

                    {/* Success Message */}
                    {flash?.success && (
                        <div className="bg-green-900/30 border border-green-500/50 rounded-lg p-4 mb-6">
                            <p className="font-pixel text-sm text-green-300">{flash.success}</p>
                        </div>
                    )}

                    {/* Appeal Section */}
                    {!showAppealForm ? (
                        <div className="bg-stone-900/30 border border-stone-700 rounded-lg p-6 mb-6">
                            <h3 className="font-pixel text-stone-200 mb-2">
                                Think this was a mistake?
                            </h3>
                            <p className="font-pixel text-sm text-stone-400 mb-4">
                                You can submit an appeal to have your ban reviewed by our team.
                            </p>
                            <button
                                onClick={() => setShowAppealForm(true)}
                                className="w-full py-3 px-4 bg-amber-600 hover:bg-amber-500 text-white font-pixel rounded-lg transition flex items-center justify-center gap-2"
                            >
                                <Send className="w-4 h-4" />
                                Submit an Appeal
                            </button>
                        </div>
                    ) : (
                        <form
                            onSubmit={handleSubmit}
                            className="bg-stone-900/30 border border-stone-700 rounded-lg p-6 mb-6"
                        >
                            <h3 className="font-pixel text-stone-200 mb-4">Submit Appeal</h3>
                            <div className="mb-4">
                                <label className="font-pixel text-xs text-stone-500 uppercase block mb-2">
                                    Your Appeal (minimum 20 characters)
                                </label>
                                <textarea
                                    value={data.appeal}
                                    onChange={(e) => setData("appeal", e.target.value)}
                                    className="w-full h-32 bg-stone-800 border border-stone-600 rounded-lg p-3 font-pixel text-sm text-stone-200 placeholder-stone-500 focus:outline-none focus:border-amber-500"
                                    placeholder="Explain why you believe this ban should be reconsidered..."
                                />
                                {errors.appeal && (
                                    <p className="font-pixel text-xs text-red-400 mt-1">
                                        {errors.appeal}
                                    </p>
                                )}
                            </div>
                            <div className="flex gap-3">
                                <button
                                    type="button"
                                    onClick={() => setShowAppealForm(false)}
                                    className="flex-1 py-3 px-4 bg-stone-700 hover:bg-stone-600 text-stone-200 font-pixel rounded-lg transition"
                                >
                                    Cancel
                                </button>
                                <button
                                    type="submit"
                                    disabled={processing}
                                    className="flex-1 py-3 px-4 bg-amber-600 hover:bg-amber-500 disabled:opacity-50 text-white font-pixel rounded-lg transition flex items-center justify-center gap-2"
                                >
                                    {processing ? (
                                        "Submitting..."
                                    ) : (
                                        <>
                                            <Send className="w-4 h-4" />
                                            Submit
                                        </>
                                    )}
                                </button>
                            </div>
                        </form>
                    )}

                    {/* Logout */}
                    <div className="text-center">
                        <a
                            href="/logout"
                            className="inline-flex items-center gap-2 font-pixel text-sm text-stone-500 hover:text-stone-300 transition"
                            onClick={(e) => {
                                e.preventDefault();
                                const form = document.createElement("form");
                                form.method = "POST";
                                form.action = "/logout";
                                const csrf =
                                    document.querySelector<HTMLMetaElement>(
                                        'meta[name="csrf-token"]',
                                    );
                                if (csrf) {
                                    const input = document.createElement("input");
                                    input.type = "hidden";
                                    input.name = "_token";
                                    input.value = csrf.content;
                                    form.appendChild(input);
                                }
                                document.body.appendChild(form);
                                form.submit();
                            }}
                        >
                            <LogOut className="w-4 h-4" />
                            Log out
                        </a>
                    </div>
                </div>
            </div>
        </>
    );
}
