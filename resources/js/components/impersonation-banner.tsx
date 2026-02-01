import { usePage } from "@inertiajs/react";
import { LogOut, UserCheck } from "lucide-react";
import type { SharedData } from "@/types";

export function ImpersonationBanner() {
    const { impersonating } = usePage<{ impersonating: SharedData["impersonating"] }>().props;

    if (!impersonating) {
        return null;
    }

    return (
        <div className="flex items-center justify-between gap-4 bg-amber-900/80 px-4 py-2 text-amber-100">
            <div className="flex items-center gap-2 text-sm">
                <UserCheck className="size-4" />
                <span>
                    Impersonating as{" "}
                    <strong>
                        {
                            usePage<{ auth: { user: { username: string } } }>().props.auth.user
                                .username
                        }
                    </strong>
                </span>
                <span className="text-amber-300/70">
                    (logged in as {impersonating.impersonator_username})
                </span>
            </div>
            <a
                href={impersonating.leave_url}
                className="flex items-center gap-1 rounded bg-amber-800 px-3 py-1 text-sm font-medium text-amber-100 transition hover:bg-amber-700"
            >
                <LogOut className="size-4" />
                Leave Impersonation
            </a>
        </div>
    );
}
