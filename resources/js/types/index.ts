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
    [key: string]: unknown;
};
