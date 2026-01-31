import { router, usePage } from "@inertiajs/react";
import { Battery, BatteryFull } from "lucide-react";
import { useState } from "react";

interface SidebarData {
    player: {
        is_admin: boolean;
        max_energy: number;
    };
}

export function NavAdminControls() {
    const { sidebar } = usePage<{ sidebar: SidebarData | null }>().props;
    const [loading, setLoading] = useState<"min" | "max" | null>(null);

    if (!sidebar?.player?.is_admin) {
        return null;
    }

    const setEnergy = async (amount: number, type: "min" | "max") => {
        setLoading(type);
        try {
            const response = await fetch("/dev/set-energy", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN":
                        document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')
                            ?.content || "",
                },
                body: JSON.stringify({ energy: amount }),
            });

            if (response.ok) {
                router.reload();
            }
        } finally {
            setLoading(null);
        }
    };

    return (
        <div className="rounded-lg border border-red-600/30 bg-red-900/20 p-2">
            <div className="mb-1 text-center font-pixel text-[8px] text-red-400">DEV TOOLS</div>
            <div className="grid grid-cols-2 gap-1">
                <button
                    onClick={() => setEnergy(4, "min")}
                    disabled={loading !== null}
                    className="flex items-center justify-center gap-1 rounded border border-red-600/50 bg-red-900/30 px-2 py-1 font-pixel text-[10px] text-red-300 transition hover:bg-red-800/50 disabled:opacity-50"
                >
                    <Battery className="h-3 w-3" />
                    {loading === "min" ? "..." : "No Energy"}
                </button>
                <button
                    onClick={() => setEnergy(sidebar.player.max_energy, "max")}
                    disabled={loading !== null}
                    className="flex items-center justify-center gap-1 rounded border border-green-600/50 bg-green-900/30 px-2 py-1 font-pixel text-[10px] text-green-300 transition hover:bg-green-800/50 disabled:opacity-50"
                >
                    <BatteryFull className="h-3 w-3" />
                    {loading === "max" ? "..." : "Max Energy"}
                </button>
            </div>
        </div>
    );
}
