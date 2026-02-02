import { createContext, useContext, useEffect, useState, type ReactNode } from "react";
import { CURRENT_CHANGELOG_VERSION } from "@/components/changelog-modal";

const CHANGELOG_STORAGE_KEY = "myrefell_last_seen_changelog";

interface ChangelogContextType {
    hasUnread: boolean;
    showChangelog: boolean;
    openChangelog: () => void;
    closeChangelog: () => void;
}

const ChangelogContext = createContext<ChangelogContextType | null>(null);

export function ChangelogProvider({ children }: { children: ReactNode }) {
    const [showChangelog, setShowChangelog] = useState(false);
    const [hasUnread, setHasUnread] = useState(false);

    useEffect(() => {
        const lastSeen = localStorage.getItem(CHANGELOG_STORAGE_KEY);
        setHasUnread(lastSeen !== CURRENT_CHANGELOG_VERSION);
    }, []);

    const openChangelog = () => setShowChangelog(true);

    const closeChangelog = () => {
        localStorage.setItem(CHANGELOG_STORAGE_KEY, CURRENT_CHANGELOG_VERSION);
        setHasUnread(false);
        setShowChangelog(false);
    };

    return (
        <ChangelogContext.Provider
            value={{ hasUnread, showChangelog, openChangelog, closeChangelog }}
        >
            {children}
        </ChangelogContext.Provider>
    );
}

export function useChangelog() {
    const context = useContext(ChangelogContext);
    if (!context) {
        throw new Error("useChangelog must be used within a ChangelogProvider");
    }
    return context;
}
