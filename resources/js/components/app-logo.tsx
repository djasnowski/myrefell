import { Crown } from "lucide-react";

export default function AppLogo() {
    return (
        <div className="flex items-center justify-center gap-2 w-full">
            <Crown className="size-6 text-sidebar-primary" />
            <span className="font-[Cinzel] text-lg font-bold tracking-wide text-sidebar-primary uppercase">
                Myrefell
            </span>
        </div>
    );
}
