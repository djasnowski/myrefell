export type * from "./auth";
export type * from "./navigation";
export type * from "./ui";

import type { Auth } from "./auth";

export type SharedData = {
    name: string;
    auth: Auth;
    sidebarOpen: boolean;
    changelog?: {
        current_version: string;
        has_unread: boolean;
    };
    online_count?: number;
    calendar?: {
        year: number;
        season: "spring" | "summer" | "autumn" | "winter";
        week: number;
        week_of_year: number;
        day: number;
        day_of_year: number;
        formatted_date: string;
    };
    impersonating?: {
        impersonator_username: string;
        leave_url: string;
    } | null;
    [key: string]: unknown;
};
