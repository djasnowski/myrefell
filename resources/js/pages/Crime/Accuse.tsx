import { Head, Link, router, usePage } from "@inertiajs/react";
import { AlertTriangle, ArrowLeft, ChevronDown, FileText, Gavel, Search, User } from "lucide-react";
import { useEffect, useRef, useState } from "react";
import AppLayout from "@/layouts/app-layout";
import type { BreadcrumbItem } from "@/types";

interface CrimeType {
    slug: string;
    name: string;
    description: string;
    severity: string;
    severity_display: string;
    court_level: string;
    court_display: string;
}

interface Player {
    id: number;
    username: string;
}

interface PageProps {
    crime_types: CrimeType[];
    players_in_location: Player[];
    current_location: string;
    [key: string]: unknown;
}

const severityColors: Record<string, { bg: string; text: string }> = {
    minor: { bg: "bg-green-900/30", text: "text-green-400" },
    moderate: { bg: "bg-yellow-900/30", text: "text-yellow-400" },
    major: { bg: "bg-orange-900/30", text: "text-orange-400" },
    capital: { bg: "bg-red-900/30", text: "text-red-400" },
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: "Dashboard", href: "/dashboard" },
    { title: "Criminal Record", href: "/crime" },
    { title: "File Accusation", href: "#" },
];

export default function Accuse() {
    const { crime_types, players_in_location, current_location } = usePage<PageProps>().props;

    const [formData, setFormData] = useState({
        accused_id: "",
        crime_type_slug: "",
        accusation_text: "",
        evidence: "",
    });
    const [searchTerm, setSearchTerm] = useState("");
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const [crimeDropdownOpen, setCrimeDropdownOpen] = useState(false);
    const crimeDropdownRef = useRef<HTMLDivElement>(null);

    // Close dropdown when clicking outside
    useEffect(() => {
        const handleClickOutside = (event: MouseEvent) => {
            if (
                crimeDropdownRef.current &&
                !crimeDropdownRef.current.contains(event.target as Node)
            ) {
                setCrimeDropdownOpen(false);
            }
        };
        document.addEventListener("mousedown", handleClickOutside);
        return () => document.removeEventListener("mousedown", handleClickOutside);
    }, []);

    const filteredPlayers = players_in_location.filter((p) =>
        p.username.toLowerCase().includes(searchTerm.toLowerCase()),
    );

    const selectedPlayer = players_in_location.find((p) => p.id.toString() === formData.accused_id);
    const selectedCrime = crime_types.find((ct) => ct.slug === formData.crime_type_slug);

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();

        if (!formData.accused_id || !formData.crime_type_slug || !formData.accusation_text.trim()) {
            setError("Please fill in all required fields.");
            return;
        }

        setIsSubmitting(true);
        setError(null);

        router.post(
            "/crime/accuse",
            {
                accused_id: parseInt(formData.accused_id),
                crime_type_slug: formData.crime_type_slug,
                accusation_text: formData.accusation_text,
                evidence: formData.evidence ? [formData.evidence] : [],
            },
            {
                onSuccess: () => {
                    // Redirect happens automatically via Inertia
                },
                onError: (errors) => {
                    setError(
                        Object.values(errors).flat().join(", ") || "Failed to file accusation.",
                    );
                    setIsSubmitting(false);
                },
                onFinish: () => {
                    setIsSubmitting(false);
                },
            },
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="File an Accusation" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-y-auto p-4">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="font-pixel text-2xl text-amber-400">File an Accusation</h1>
                        <p className="font-pixel text-sm text-stone-400">
                            Report a crime committed in {current_location}
                        </p>
                    </div>
                    <Link
                        href="/crime"
                        className="flex items-center gap-2 rounded-lg border border-stone-600/50 bg-stone-800/50 px-3 py-2 font-pixel text-xs text-stone-300 transition hover:bg-stone-700/50"
                    >
                        <ArrowLeft className="h-4 w-4" />
                        Back to Record
                    </Link>
                </div>

                {/* Warning */}
                <div className="rounded-xl border-2 border-amber-500/50 bg-amber-900/20 p-4">
                    <div className="flex items-center gap-3">
                        <div className="rounded-lg bg-amber-800/50 p-3">
                            <AlertTriangle className="h-6 w-6 text-amber-300" />
                        </div>
                        <div>
                            <h2 className="font-pixel text-sm text-amber-300">Warning</h2>
                            <p className="font-pixel text-xs text-stone-400">
                                Filing a false accusation is itself a crime! If your accusation is
                                found to be knowingly false, you may face charges for false
                                accusation.
                            </p>
                        </div>
                    </div>
                </div>

                {/* Error Message */}
                {error && (
                    <div className="rounded-lg border border-red-500/50 bg-red-900/30 p-3 font-pixel text-sm text-red-300">
                        {error}
                    </div>
                )}

                {/* Form */}
                <form onSubmit={handleSubmit} className="space-y-4">
                    {/* Accused Player Selection */}
                    <div className="rounded-xl border-2 border-stone-600/50 bg-stone-800/30 p-4">
                        <label className="mb-3 flex items-center gap-2 font-pixel text-sm text-stone-300">
                            <User className="h-4 w-4" />
                            Accused Player <span className="text-red-400">*</span>
                        </label>

                        {/* Search */}
                        <div className="relative mb-3">
                            <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-stone-500" />
                            <input
                                type="text"
                                value={searchTerm}
                                onChange={(e) => setSearchTerm(e.target.value)}
                                placeholder="Search players in your location..."
                                className="w-full rounded border border-stone-600 bg-stone-800 py-2 pl-10 pr-3 font-pixel text-sm text-white placeholder-stone-500 focus:border-amber-500 focus:outline-none"
                            />
                        </div>

                        {/* Player List */}
                        {players_in_location.length === 0 ? (
                            <div className="rounded-lg bg-stone-900/50 p-4 text-center">
                                <p className="font-pixel text-xs text-stone-500">
                                    No other players in your location.
                                </p>
                            </div>
                        ) : filteredPlayers.length === 0 ? (
                            <div className="rounded-lg bg-stone-900/50 p-4 text-center">
                                <p className="font-pixel text-xs text-stone-500">
                                    No players match your search.
                                </p>
                            </div>
                        ) : (
                            <div className="max-h-40 space-y-1 overflow-y-auto">
                                {filteredPlayers.map((player) => (
                                    <label
                                        key={player.id}
                                        className={`flex cursor-pointer items-center gap-3 rounded-lg border p-2 transition ${
                                            formData.accused_id === player.id.toString()
                                                ? "border-amber-500/50 bg-amber-900/20"
                                                : "border-stone-700 bg-stone-800/50 hover:border-stone-600"
                                        }`}
                                    >
                                        <input
                                            type="radio"
                                            name="accused_id"
                                            value={player.id}
                                            checked={formData.accused_id === player.id.toString()}
                                            onChange={(e) =>
                                                setFormData({
                                                    ...formData,
                                                    accused_id: e.target.value,
                                                })
                                            }
                                            className="h-4 w-4 border-stone-600 bg-stone-700 text-amber-500 focus:ring-amber-500"
                                        />
                                        <span className="font-pixel text-sm text-white">
                                            {player.username}
                                        </span>
                                    </label>
                                ))}
                            </div>
                        )}

                        {selectedPlayer && (
                            <div className="mt-2 rounded-lg bg-amber-900/20 p-2 font-pixel text-xs text-amber-300">
                                Selected: {selectedPlayer.username}
                            </div>
                        )}
                    </div>

                    {/* Crime Type Selection */}
                    <div className="rounded-xl border-2 border-stone-600/50 bg-stone-800/30 p-4">
                        <label className="mb-3 flex items-center gap-2 font-pixel text-sm text-stone-300">
                            <Gavel className="h-4 w-4" />
                            Crime Type <span className="text-red-400">*</span>
                        </label>

                        {/* Custom Crime Type Dropdown */}
                        <div className="relative" ref={crimeDropdownRef}>
                            <button
                                type="button"
                                onClick={() => setCrimeDropdownOpen(!crimeDropdownOpen)}
                                className="flex w-full items-center justify-between rounded border border-stone-600 bg-stone-800 px-3 py-2 font-pixel text-sm text-white focus:border-amber-500 focus:outline-none"
                            >
                                {selectedCrime ? (
                                    <div className="flex items-center gap-2">
                                        <span
                                            className={`h-2.5 w-2.5 rounded-full ${
                                                severityColors[selectedCrime.severity]?.bg.replace(
                                                    "/30",
                                                    "",
                                                ) || "bg-stone-500"
                                            }`}
                                            style={{
                                                backgroundColor:
                                                    selectedCrime.severity === "minor"
                                                        ? "#4ade80"
                                                        : selectedCrime.severity === "moderate"
                                                          ? "#facc15"
                                                          : selectedCrime.severity === "major"
                                                            ? "#fb923c"
                                                            : selectedCrime.severity === "capital"
                                                              ? "#f87171"
                                                              : "#78716c",
                                            }}
                                        />
                                        <span>{selectedCrime.name}</span>
                                    </div>
                                ) : (
                                    <span className="text-stone-500">-- Select a crime --</span>
                                )}
                                <ChevronDown
                                    className={`h-4 w-4 text-stone-400 transition-transform ${crimeDropdownOpen ? "rotate-180" : ""}`}
                                />
                            </button>

                            {crimeDropdownOpen && (
                                <div className="absolute z-50 mt-1 max-h-60 w-full overflow-y-auto rounded border border-stone-600 bg-stone-800 shadow-lg">
                                    {crime_types.map((ct) => (
                                        <button
                                            key={ct.slug}
                                            type="button"
                                            onClick={() => {
                                                setFormData({
                                                    ...formData,
                                                    crime_type_slug: ct.slug,
                                                });
                                                setCrimeDropdownOpen(false);
                                            }}
                                            className={`flex w-full items-center gap-3 px-3 py-2 text-left transition hover:bg-stone-700 ${
                                                formData.crime_type_slug === ct.slug
                                                    ? "bg-stone-700/50"
                                                    : ""
                                            }`}
                                        >
                                            <span
                                                className="h-2.5 w-2.5 flex-shrink-0 rounded-full"
                                                style={{
                                                    backgroundColor:
                                                        ct.severity === "minor"
                                                            ? "#4ade80"
                                                            : ct.severity === "moderate"
                                                              ? "#facc15"
                                                              : ct.severity === "major"
                                                                ? "#fb923c"
                                                                : ct.severity === "capital"
                                                                  ? "#f87171"
                                                                  : "#78716c",
                                                }}
                                            />
                                            <div className="flex-1">
                                                <div className="font-pixel text-sm text-white">
                                                    {ct.name}
                                                </div>
                                                <div className="font-pixel text-[10px] text-stone-500">
                                                    {ct.description}
                                                </div>
                                            </div>
                                            <span
                                                className={`rounded px-1.5 py-0.5 font-pixel text-[10px] ${
                                                    severityColors[ct.severity]?.bg ||
                                                    "bg-stone-700"
                                                } ${severityColors[ct.severity]?.text || "text-stone-300"}`}
                                            >
                                                {ct.severity_display}
                                            </span>
                                        </button>
                                    ))}
                                </div>
                            )}
                        </div>

                        {/* Selected Crime Details */}
                        {selectedCrime && (
                            <div className="mt-3 rounded-lg border border-stone-700 bg-stone-900/50 p-3">
                                <div className="mb-2 flex items-center justify-between">
                                    <span className="font-pixel text-sm text-white">
                                        {selectedCrime.name}
                                    </span>
                                    <span
                                        className={`rounded px-2 py-0.5 font-pixel text-[10px] ${
                                            severityColors[selectedCrime.severity]?.bg ||
                                            "bg-stone-700"
                                        } ${severityColors[selectedCrime.severity]?.text || "text-stone-300"}`}
                                    >
                                        {selectedCrime.severity_display}
                                    </span>
                                </div>
                                <p className="mb-2 font-pixel text-xs text-stone-400">
                                    {selectedCrime.description}
                                </p>
                                <div className="flex items-center gap-1 font-pixel text-[10px] text-stone-500">
                                    <Gavel className="h-3 w-3" />
                                    Tried at: {selectedCrime.court_display}
                                </div>
                            </div>
                        )}
                    </div>

                    {/* Description */}
                    <div className="rounded-xl border-2 border-stone-600/50 bg-stone-800/30 p-4">
                        <label className="mb-3 flex items-center gap-2 font-pixel text-sm text-stone-300">
                            <FileText className="h-4 w-4" />
                            Description <span className="text-red-400">*</span>
                        </label>
                        <textarea
                            value={formData.accusation_text}
                            onChange={(e) =>
                                setFormData({ ...formData, accusation_text: e.target.value })
                            }
                            maxLength={2000}
                            rows={4}
                            placeholder="Describe what happened in detail..."
                            className="w-full resize-none rounded border border-stone-600 bg-stone-800 px-3 py-2 font-pixel text-sm text-white placeholder-stone-500 focus:border-amber-500 focus:outline-none"
                        />
                        <p className="mt-1 font-pixel text-[10px] text-stone-500">
                            {formData.accusation_text.length}/2000 characters
                        </p>
                    </div>

                    {/* Evidence (Optional) */}
                    <div className="rounded-xl border-2 border-stone-600/50 bg-stone-800/30 p-4">
                        <label className="mb-3 flex items-center gap-2 font-pixel text-sm text-stone-300">
                            <FileText className="h-4 w-4" />
                            Evidence{" "}
                            <span className="font-pixel text-[10px] text-stone-500">
                                (Optional)
                            </span>
                        </label>
                        <textarea
                            value={formData.evidence}
                            onChange={(e) => setFormData({ ...formData, evidence: e.target.value })}
                            maxLength={1000}
                            rows={3}
                            placeholder="Any proof you have (witnesses, items, etc.)..."
                            className="w-full resize-none rounded border border-stone-600 bg-stone-800 px-3 py-2 font-pixel text-sm text-white placeholder-stone-500 focus:border-amber-500 focus:outline-none"
                        />
                        <p className="mt-1 font-pixel text-[10px] text-stone-500">
                            {formData.evidence.length}/1000 characters
                        </p>
                    </div>

                    {/* Actions */}
                    <div className="flex gap-3">
                        <Link
                            href="/crime"
                            className="flex-1 rounded border-2 border-stone-600/50 bg-stone-800/50 py-3 text-center font-pixel text-sm text-stone-300 transition hover:bg-stone-700/50"
                        >
                            Cancel
                        </Link>
                        <button
                            type="submit"
                            disabled={
                                isSubmitting ||
                                !formData.accused_id ||
                                !formData.crime_type_slug ||
                                !formData.accusation_text.trim()
                            }
                            className="flex-1 rounded border-2 border-amber-600/50 bg-amber-900/30 py-3 font-pixel text-sm text-amber-300 transition hover:bg-amber-900/50 disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            {isSubmitting ? "Filing..." : "File Accusation"}
                        </button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
