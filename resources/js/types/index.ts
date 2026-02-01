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
    impersonating?: {
        impersonator_username: string;
        leave_url: string;
    } | null;
    [key: string]: unknown;
};
